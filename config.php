<?php
// Database credentials - renamed variables to avoid conflict with form inputs
$db_host = "localhost";
$db_user = "root";      // your DB username
$db_pass = "";          // your DB password
$db_name = "meal_plan_recommender_main";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
