<?php session_start(); if(isset($_SERVER['HTTP_X_TEST'])) { echo $_SERVER['HTTP_X_TEST']; } else { echo 'not set'; } ?>
