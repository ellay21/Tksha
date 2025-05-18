<?php
// Main entry point for the API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode(array(
    "name" => "Tksha Dating App API",
    "version" => "1.0.0",
    "description" => "RESTful API for Tksha Dating App"
));
?>
