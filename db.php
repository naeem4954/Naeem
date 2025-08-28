<?php
$host = 'localhost';
$db = 'u635821533_LbhOO';
$user = 'u635821533_ykUFD';
$pass = '2ADCO51#;k';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die('DB connection error: ' . $conn->connect_error); }
$conn->set_charset('utf8mb4');
?>