<?php
// Check if requesting the YAML file
if ($_SERVER['REQUEST_URI'] === '/swagger.yaml') {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/x-yaml");
    readfile(__DIR__.'/swagger.yaml');
    exit();
}

// For all other requests, redirect to Swagger UI
header('Location: https://petstore.swagger.io/?url='.urlencode('https://'.$_SERVER['HTTP_HOST'].'/swagger.yaml'));
exit();
