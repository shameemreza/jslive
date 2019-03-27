<?php

$servername = 'localhost';
$database = '';
$username = '';
$password = '';

// Create connection

$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection

if (!$conn) {
      die('');
}
