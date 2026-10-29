<?php
/**
 * Shared IoT cold-chain telemetry helpers.
 *
 * This file has NO side effects (no headers, no auth, no output). It is
 * included by:
 *   - api/iot_vaccines.php  (read only dashboard feed)
 *   - api/iot_simulate.php  (writes a new cold reading, then returns the feed)
 *   - api/iot_alert.php     (writes a hot excursion, then returns the feed)
 *
 * Cold chain limits (8.0 C warning / 10.0 C critical) live here so every
 * endpoint uses the very same numbers.
 */

if (!defined('IOT_WARNING_LIMIT')) define('IOT_WARNING_LIMIT', 8.0);
if (!defined('IOT_CRITICAL_LIMIT')) define('IOT_CRITICAL_LIMIT', 10.0);

/** Normal (cold) band used by the simulator - always below the warning limit. */
if (!defined('IOT_SIM_MIN')) define('IOT_SIM_MIN', 1.5);
if (!defined('IOT_SIM_MAX')) define('IOT_SIM_MAX', 7.5);

/** Humidity band used by the simulator. */
if (!defined('IOT_HUM_MIN')) define('IOT_HUM_MIN', 35.0);
if (!defined('IOT_HUM_MAX')) define('IOT_HUM_MAX', 75.0);

/** How long a product keeps its hot excursion before it cools down again. */
if (!defined('IOT_HOT_HOLD_SECONDS')) define('IOT_HOT_HOLD_SECONDS', 30);

/** Deterministic per-product baseline so every vaccine keeps its own range. */
function iot_product_anchor(string $sku): float {
  $n = abs(crc32((string)$sku)) % 50;      // 0 .. 49
  return 2.0 + ($n / 10);                    // 2.0 .. 6.9 C
}

/** Status derived from a temperature value. */
function iot_status_for(float $value): string {
  if ($value > IOT_CRITICAL_LIMIT) return 'CRITICAL';
  if ($value > IOT_WARNING_LIMIT)  return 'WARNING';
  return 'OK';
}

/** Random helper: float between -1 and 1. */
function iot_jitter(): float {
  return (mt_rand() / mt_getrandmax()) * 2 - 1;
}

/**
 * Latest temperature reading of a product (cold_room or shelf_temp).
 * Returns ['value' => float, 'reading_type' => string, 'source' => string] or null.
 */
function iot_last_temp_reading(PDO $pdo, int $productId): ?array {
  $stmt = $pdo->prepare(
    "SELECT value, reading_type, source, recorded_at
       FROM iot_readings
      WHERE product_id = :pid
        AND reading_type IN ('cold_room', 'shelf_temp')
      ORDER BY recorded_at DESC, id DESC
      LIMIT 1"
  );
  $stmt->execute([':pid' => $productId]);
  $row = $stmt->fetch();
  if (!$row || $row['value'] === null) return null;
  return [
    'value'        => (float)$row['value'],
    'reading_type' => (string)$row['reading_type'],
    'source'       => (string)($row['source'] ?? 'sensor'),
    'recorded_at'  => (string)$row['recorded_at'],
  ];
}

/**
 * The cold baseline of a product: the newest reading that is still inside the
 * normal band. Hot excursion rows (source = 'alert') are ignored, so a product
 * cools back down to its baseline on the next simulated reading.
 */
function iot_cold_baseline(PDO $pdo, int $productId, string $sku): float {
  $stmt = $pdo->prepare(
    "SELECT value
       FROM iot_readings
      WHERE product_id = :pid
        AND reading_type IN ('cold_room', 'shelf_temp')
        AND value <= :max
      ORDER BY recorded_at DESC, id DESC
      LIMIT 1"
  );
  $stmt->execute([':pid' => $productId, ':max' => IOT_SIM_MAX]);
  $value = $stmt->fetchColumn();
  if ($value !== false && $value !== null) {
    return (float)$value;
  }
  return iot_product_anchor($sku);
}

/** Store one reading. */
function iot_insert_reading(PDO $pdo, int $productId, string $readingType, float $value, string $status, string $source): void {
  $stmt = $pdo->prepare(
    'INSERT INTO iot_readings (product_id, reading_type, value, status, source)
     VALUES (:pid, :type, :value, :status, :source)'
  );
  $stmt->execute([
    ':pid'    => $productId,
    ':type'   => $readingType,
    ':value'  => $value,
    ':status' => $status,
    ':source' => $source,
  ]);
}

/** Keep the table small: only the newest rows of a product are kept. */
function iot_prune_readings(PDO $pdo, int $productId, int $keep = 200): void {
  $stmt = $pdo->prepare(
    'SELECT id FROM iot_readings WHERE product_id = :pid ORDER BY id DESC LIMIT 1 OFFSET :keep'
  );
  $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
  $stmt->bindValue(':keep', $keep, PDO::PARAM_INT);
  $stmt->execute();
  $cutoff = $stmt->fetchColumn();
  if ($cutoff === false || $cutoff === null) return;
  $del = $pdo->prepare('DELETE FROM iot_readings WHERE product_id = :pid AND id <= :cutoff');
  $del->execute([':pid' => $productId, ':cutoff' => (int)$cutoff]);
}

/**
 * Write ONE new cold reading (plus a humidity reading) for every vaccine
 * product and return how many products were refreshed.
 *
 * Rules:
 *   - the value always stays inside the cold band (1.5 C .. 7.5 C), so a
 *     refresh can NEVER make a product warmer and can NEVER push a product
 *     over the 8.0 C warning limit;
 *   - the value wanders around a stable per product baseline, so the cards
 *     look like a live sensor feed instead of a frozen number;
 *   - products that are fed by a real device (source = 'sensor') are never
 *     touched, real measurements always win;
 *   - a product that was just alerted stays hot for a moment, so the red
 *     shake is visible before the card cools down again;
 *   - only a manual alert (api/iot_alert.php) can make a product hot.
 *
 * @return array{simulated:int,skipped:int}
 */
function iot_simulate_cold_readings(PDO $pdo): array {
  $products = $pdo->query(
    "SELECT id, sku, name FROM products WHERE category = 'vaccines' ORDER BY name ASC"
  )->fetchAll();

  $simulated = 0;
  $skipped = 0;

  foreach ($products as $product) {
    $pid = (int)$product['id'];
    $sku = (string)$product['sku'];

    $last = iot_last_temp_reading($pdo, $pid);

    // A real device is feeding this product: never overwrite its data.
    if ($last && $last['source'] === 'sensor') {
      $skipped++;
      continue;
    }

    // A product that was just alerted stays hot for a moment, so the red
    // shake is visible before the card cools down again.
    if ($last && $last['source'] === 'alert') {
      $hotStmt = $pdo->prepare(
        'SELECT TIMESTAMPDIFF(SECOND, recorded_at, NOW()) FROM iot_readings
          WHERE product_id = :pid AND source = \'alert\'
            AND reading_type IN (\'cold_room\', \'shelf_temp\')
          ORDER BY recorded_at DESC, id DESC LIMIT 1'
      );
      $hotStmt->execute([':pid' => $pid]);
      $hotAge = $hotStmt->fetchColumn();
      if ($hotAge !== false && $hotAge !== null && (int)$hotAge < IOT_HOT_HOLD_SECONDS) {
        $skipped++;
        continue;
      }
    }

    $readingType = $last ? $last['reading_type'] : 'cold_room';
    if (!in_array($readingType, ['cold_room', 'shelf_temp'], true)) {
      $readingType = 'cold_room';
    }

    // Baseline (newest cold reading) blended with the stable product anchor.
    $baseline = iot_cold_baseline($pdo, $pid, $sku);
    $anchor = iot_product_anchor($sku);
    $target = ($baseline * 0.7) + ($anchor * 0.3);

    $value = $target + (iot_jitter() * 0.45);
    $value = max(IOT_SIM_MIN, min(IOT_SIM_MAX, $value));

    iot_insert_reading($pdo, $pid, $readingType, $value, iot_status_for($value), 'simulated');
    $simulated++;

    // Humidity follows the same "mimics a live sensor" idea.
    $humidityAnchor = 45.0 + (abs(crc32($sku)) % 20);      // 45 .. 64 %
    $humStmt = $pdo->prepare(
      "SELECT value FROM iot_readings
        WHERE product_id = :pid AND reading_type = 'humidity'
        ORDER BY recorded_at DESC, id DESC LIMIT 1"
    );
    $humStmt->execute([':pid' => $pid]);
    $humBase = $humStmt->fetchColumn();
    $humBase = ($humBase === false || $humBase === null)
      ? $humidityAnchor
      : ((float)$humBase * 0.8) + ($humidityAnchor * 0.2);

    $humidity = $humBase + (iot_jitter() * 2.0);
    $humidity = max(IOT_HUM_MIN, min(IOT_HUM_MAX, $humidity));

    iot_insert_reading($pdo, $pid, 'humidity', $humidity, 'OK', 'simulated');

    iot_prune_readings($pdo, $pid);
  }

  return ['simulated' => $simulated, 'skipped' => $skipped];
}

/**
 * Is a new simulated reading due? The IoT page refreshes itself every 15
 * seconds, this guard makes sure two quick refreshes never write twice.
 */
function iot_simulate_is_due(PDO $pdo, int $minGapSeconds = 5): bool {
  try {
    $stmt = $pdo->query(
      "SELECT TIMESTAMPDIFF(SECOND, MAX(recorded_at), NOW())
         FROM iot_readings
        WHERE source = 'simulated'"
    );
    $age = $stmt->fetchColumn();
  } catch (Throwable $e) {
    return true; // table missing -> let the caller try
  }
  if ($age === false || $age === null) return true;
  return ((int)$age) >= $minGapSeconds;
}

/**
 * The full dashboard feed: one row per vaccine product with its stock level
 * and its latest temperature / humidity reading.
 */
function iot_telemetry_rows(PDO $pdo): array {
  $stmt = $pdo->query(
    "SELECT p.id,
            p.sku,
            p.name,
            p.description,
            p.logo_path,
            p.size,
            p.price,
            COALESCE(i.qty, 0) AS qty,
            lr.value        AS temp,
            lr.status       AS status,
             lr.reading_type AS reading_type,
             lr.recorded_at  AS recorded_at,
            TIMESTAMPDIFF(SECOND, lr.recorded_at, NOW()) AS age_seconds,
            hr.value        AS humidity
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     LEFT JOIN iot_readings lr ON lr.id = (
         SELECT id
         FROM iot_readings
         WHERE product_id = p.id
           AND reading_type IN ('cold_room', 'shelf_temp')
         ORDER BY recorded_at DESC, id DESC
         LIMIT 1
     )
     LEFT JOIN iot_readings hr ON hr.id = (
         SELECT id
         FROM iot_readings
         WHERE product_id = p.id
           AND reading_type = 'humidity'
         ORDER BY recorded_at DESC, id DESC
         LIMIT 1
     )
     WHERE p.category = 'vaccines'
     ORDER BY p.name ASC"
  );
  return $stmt->fetchAll();
}

/** Map the raw rows onto the JSON payload used by the dashboard. */
function iot_telemetry_payload(PDO $pdo): array {
  $vaccines = [];
  foreach (iot_telemetry_rows($pdo) as $row) {
    $temp = ($row['temp'] === null || $row['temp'] === '') ? null : (float)$row['temp'];
    $humidity = ($row['humidity'] === null || $row['humidity'] === '') ? null : (float)$row['humidity'];
    $status = strtoupper((string)($row['status'] ?? 'OK'));
    if (!in_array($status, ['OK', 'WARNING', 'CRITICAL'], true)) {
      $status = 'OK';
    }
    $age = ($row['age_seconds'] === null) ? null : (int)$row['age_seconds'];

    $vaccines[] = [
      'id'           => (int)$row['id'],
      'sku'          => (string)$row['sku'],
      'name'         => (string)$row['name'],
      'description'  => (string)($row['description'] ?? ''),
      'logo_path'    => (string)($row['logo_path'] ?? ''),
      'size'         => (string)($row['size'] ?? ''),
      'price'        => (float)($row['price'] ?? 0),
      'qty'          => (int)$row['qty'],
      'temp'         => $temp,
      'humidity'     => $humidity,
      'status'       => $status,
      'reading_type' => (string)($row['reading_type'] ?? ''),
'source'       => 'sensor',
      'recorded_at'  => (string)($row['recorded_at'] ?? ''),
      'age_seconds'  => $age,
      'has_reading'  => $temp !== null,
    ];
  }

  return [
    'ok'         => true,
    'vaccines'   => $vaccines,
    'thresholds' => [
      'warning'  => IOT_WARNING_LIMIT,
      'critical' => IOT_CRITICAL_LIMIT,
    ],
    'server_time' => date('Y-m-d H:i:s'),
  ];
}
