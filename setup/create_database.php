<?php
// Database setup script
$host = "dpg-d0ku13buibrs739rm5o0-a.oregon-postgres.render.com"; // full hostname from Render external URL
$dbname = "tksha"; // your database name
$username = "ellay"; // your db username
$password = "5TCdJiB7Xy6zuSAs2rw1mok5izBxZf8F"; // your db password

try {
    // Connect directly to the existing database
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        gender VARCHAR(10) NOT NULL CHECK (gender IN ('male', 'female')),
        location_coords GEOGRAPHY(POINT) NOT NULL,
        radius_preference INT NOT NULL DEFAULT 50 
            CHECK (radius_preference BETWEEN 1 AND 1000),
        profile_picture VARCHAR(255) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Create spatial index for location_coords
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_users_location ON users USING GIST(location_coords)");

    // Create activities table
    $sql = "CREATE TABLE IF NOT EXISTS activities (
        id SERIAL PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL
    )";
    $conn->exec($sql);

    // Create user_activities table
    $sql = "CREATE TABLE IF NOT EXISTS user_activities (
        user_id INT NOT NULL,
        activity_id INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
        PRIMARY KEY (user_id, activity_id)
    )";
    $conn->exec($sql);

    // Create swipes table
    $sql = "CREATE TABLE IF NOT EXISTS swipes (
        id SERIAL PRIMARY KEY,
        swiper_id INT NOT NULL,
        swiped_id INT NOT NULL,
        is_like BOOLEAN NOT NULL,
        swiped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (swiper_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (swiped_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Create matches table
    $sql = "CREATE TABLE IF NOT EXISTS matches (
        id SERIAL PRIMARY KEY,
        user1_id INT NOT NULL,
        user2_id INT NOT NULL,
        matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY,
        match_id INT NOT NULL,
        sender_id INT NOT NULL,
        content TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Insert default activities
    $activities = [
        'Hiking', 'Cooking', 'Reading', 'Movies', 'Music', 'Dancing',
        'Photography', 'Travel', 'Fitness', 'Yoga', 'Swimming', 'Cycling',
        'Running', 'Gaming', 'Art', 'Theater', 'Concerts', 'Sports',
        'Camping', 'Fishing', 'Gardening', 'Volunteering', 'Shopping',
        'Wine Tasting', 'Coffee', 'Foodie', 'Pets', 'Technology'
    ];

    $stmt = $conn->prepare("INSERT INTO activities (name) VALUES (?) ON CONFLICT (name) DO NOTHING");
    foreach($activities as $activity) {
        $stmt->execute([$activity]);
    }

    echo "Database setup completed successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
