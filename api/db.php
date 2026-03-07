<?php
/**
 * Database connection helper for BioElectrode AI
 * Database: bioelectrodeai (MySQL via XAMPP)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Default XAMPP username
define('DB_PASS', '');           // Default XAMPP password (empty)
define('DB_NAME', 'bioelectrodeai');

function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Show a user-friendly error instead of a raw PHP error
        http_response_code(503);
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
