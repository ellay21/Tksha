<?php
// test_spatial.php
include_once '../config/database.php';
include_once '../models/User.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Test points - including edge cases
$test_points = [
    ['New York', 40.7128, -74.0060],
    ['Los Angeles', 34.0522, -118.2437],
    ['Null Island', 0, 0],
    ['North Pole', 90, 0],
    ['South Pole', -90, 0]
];

echo "<h2>Spatial Function Test</h2>";
echo "<table border='1'>
        <tr>
            <th>Location</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Test Result</th>
        </tr>";

foreach ($test_points as $point) {
    $name = $point[0];
    $lat = (float)$point[1];
    $lng = (float)$point[2];
    
    try {
        // Simple distance calculation (point to itself should be 0)
        $stmt = $db->prepare("
            SELECT 
                ST_Distance_Sphere(
                    POINT(:lat, :lng),
                    POINT(:lat, :lng)
                ) as distance,
                ST_X(POINT(:lat, :lng)) as test_lat,
                ST_Y(POINT(:lat, :lng)) as test_lng
        ");
        
        $stmt->bindValue(':lat', $lat, PDO::PARAM_STR);
        $stmt->bindValue(':lng', $lng, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>
                <td>$name</td>
                <td>$lat</td>
                <td>$lng</td>
                <td style='color: green'>Success (Distance: {$result['distance']})</td>
              </tr>";
    } catch (PDOException $e) {
        echo "<tr>
                <td>$name</td>
                <td>$lat</td>
                <td>$lng</td>
                <td style='color: red'>Failed: " . htmlspecialchars($e->getMessage()) . "</td>
              </tr>";
    }
}

echo "</table>";

// Check MySQL version and spatial support
echo "<h3>MySQL Configuration</h3>";
$version = $db->query("SELECT VERSION()")->fetchColumn();
$spatial = $db->query("SHOW VARIABLES LIKE 'have%spatial%'")->fetchAll();

echo "<p>MySQL Version: $version</p>";
echo "<pre>Spatial Support: " . print_r($spatial, true) . "</pre>";
?>