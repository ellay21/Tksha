<?php
include_once '../config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Sample passwords
    $passwords = ['SecurePass1!', 'Testing@123', 'DevPassword#1', 'TempPass2023', 'Sample!123'];
    
    // Sample male users with profile pictures
    $male_users = [
        ['John Doe', 'john@example.com', $passwords[0], 40.7128, -74.0060, 50, 
         'I love hiking and outdoor activities.', 'https://randomuser.me/api/portraits/men/1.jpg'],
        ['Michael Smith', 'michael@example.com', $passwords[1], 34.0522, -118.2437, 30, 
         'Music lover and coffee enthusiast.', 'https://randomuser.me/api/portraits/men/2.jpg'],
        ['Robert Johnson', 'robert@example.com', $passwords[2], 41.8781, -87.6298, 40, 
         'Foodie and travel addict.', 'https://randomuser.me/api/portraits/men/3.jpg'],
        ['Carlos Mendez', 'carlos@example.com', $passwords[3], 19.4326, -99.1332, 30, 
         'Tech entrepreneur and soccer fan.', 'https://randomuser.me/api/portraits/men/4.jpg']
    ];
    
    // Sample female users with profile pictures
    $female_users = [
        ['Jane Smith', 'jane@example.com', $passwords[4], 40.7282, -73.7949, 45, 
         'Yoga instructor and book lover.', 'https://randomuser.me/api/portraits/women/1.jpg'],
        ['Emily Johnson', 'emily@example.com', $passwords[0], 34.1478, -118.1445, 35, 
         'Artist and nature enthusiast.', 'https://randomuser.me/api/portraits/women/2.jpg'],
        ['Sophie Martin', 'sophie@example.com', $passwords[1], 48.8566, 2.3522, 40, 
         'Fashion designer and photographer.', 'https://randomuser.me/api/portraits/women/3.jpg'],
        ['Priya Patel', 'priya@example.com', $passwords[2], 28.6139, 77.2090, 50, 
         'Doctor and classical dancer.', 'https://randomuser.me/api/portraits/women/4.jpg']
    ];
    
    // Prepare user insert statement once
    $user_query = "INSERT INTO users (name, email, password, gender, location_coords, radius_preference, bio, profile_picture, created_at) 
                   VALUES (?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?, ?, ?, ?)";
    $user_stmt = $db->prepare($user_query);
    
    // Function to insert users
    function insertUsers($users, $gender, $stmt) {
        foreach ($users as $user) {
            $password_hash = password_hash($user[2], PASSWORD_BCRYPT);
            $created_at = date('Y-m-d H:i:s', strtotime('-'.rand(0, 365).' days'));
            
            $stmt->execute([
                $user[0],           // name
                $user[1],           // email
                $password_hash,     // password
                $gender,            // gender
                $user[4],           // longitude (lng)
                $user[3],           // latitude (lat)
                $user[5],           // radius_preference
                $user[6],           // bio
                $user[7] ?? null,   // profile_picture
                $created_at         // created_at
            ]);
        }
    }
    
    // Insert all users
    insertUsers($male_users, 'male', $user_stmt);
    insertUsers($female_users, 'female', $user_stmt);
    
    echo "Sample users inserted successfully<br>";
    
    // Get all user IDs
    $user_ids = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all activity IDs
    $activity_ids = $db->query("SELECT id FROM activities")->fetchAll(PDO::FETCH_COLUMN);
    
    // Assign activities to users
    $activity_insert_query = "INSERT INTO user_activities (user_id, activity_id) VALUES (?, ?)";
    $activity_insert_stmt = $db->prepare($activity_insert_query);
    
    foreach ($user_ids as $user_id) {
        $num_activities = rand(3, 5);
        $selected_activities = array_rand(array_flip($activity_ids), $num_activities);
        $selected_activities = is_array($selected_activities) ? $selected_activities : [$selected_activities];
        
        foreach ($selected_activities as $activity_id) {
            $activity_insert_stmt->execute([$user_id, $activity_id]);
        }
    }
    
    echo "Sample user activities inserted successfully<br>";
    echo "Seed data inserted successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
