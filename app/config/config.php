<?php
date_default_timezone_set('Asia/Manila');
$servername = "localhost";
$username = "root";
$password = "";
$database = "quickcart";

define("ROOT_PATH", dirname(__DIR__));

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
?>