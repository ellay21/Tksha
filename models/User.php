<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $gender;
    public $lat;
    public $lng;
    public $radius_preference;
    public $profile_picture;
    public $bio;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
        $this->radius_preference = 50; // Default value
    }

    public function ensureCompleteProfile() {
        if (empty($this->lat) || empty($this->lng)) {
            $this->readOne(); // Reload all data
        }
        
        if (empty($this->radius_preference)) {
            $this->radius_preference = 50;
        }
        
        return !empty($this->id) && !empty($this->gender);
    }

    public function validateLocation() {
        if (empty($this->lat) || empty($this->lng)) {
            return false;
        }
        
        return $this->lat >= -90 && $this->lat <= 90 && 
               $this->lng >= -180 && $this->lng <= 180;
    }

    // Create new user (PostgreSQL version)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, email, password, gender, location_coords, radius_preference, profile_picture, bio)
                VALUES (:name, :email, :password, :gender, ST_MakePoint(:lng, :lat), :radius_preference, :profile_picture, :bio)
                RETURNING id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->gender = htmlspecialchars(strip_tags($this->gender));
        $this->radius_preference = htmlspecialchars(strip_tags($this->radius_preference));
        $this->profile_picture = $this->profile_picture ? htmlspecialchars(strip_tags($this->profile_picture)) : null;
        $this->bio = $this->bio ? htmlspecialchars(strip_tags($this->bio)) : null;

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        
        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $password_hash);
        
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":lat", $this->lat);
        $stmt->bindParam(":lng", $this->lng);
        $stmt->bindParam(":radius_preference", $this->radius_preference);
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        $stmt->bindParam(":bio", $this->bio);

        if($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            return true;
        }

        return false;
    }

    // Check if email exists (PostgreSQL version)
    public function emailExists() {
        $query = "SELECT id, name, email, password, gender, 
                    ST_X(location_coords::geometry) as lng, 
                    ST_Y(location_coords::geometry) as lat, 
                    radius_preference, profile_picture, bio
                FROM " . $this->table_name . "
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->gender = $row['gender'];
            $this->lat = $row['lat'];
            $this->lng = $row['lng'];
            $this->radius_preference = $row['radius_preference'];
            $this->profile_picture = $row['profile_picture'];
            $this->bio = $row['bio'];
            
            return true;
        }

        return false;
    }

    // Get user by ID (PostgreSQL version)
    public function readOne() {
        $query = "SELECT id, name, email, gender, 
                    ST_X(location_coords::geometry) as lng, 
                    ST_Y(location_coords::geometry) as lat, 
                    radius_preference, profile_picture, bio, 
                    TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at
                FROM " . $this->table_name . "
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->gender = $row['gender'];
            $this->lat = $row['lat'];
            $this->lng = $row['lng'];
            $this->radius_preference = $row['radius_preference'];
            $this->profile_picture = $row['profile_picture'];
            $this->bio = $row['bio'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }

    // Update user profile (PostgreSQL version)
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    name = :name,
                    bio = :bio
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->bio = $this->bio ? htmlspecialchars(strip_tags($this->bio)) : null;

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Update profile picture (PostgreSQL version)
    public function updateProfilePicture() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    profile_picture = :profile_picture
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->profile_picture = htmlspecialchars(strip_tags($this->profile_picture));

        $stmt->bindParam(":profile_picture", $this->profile_picture);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function testDistanceCalculation($lat1, $lng1, $lat2, $lng2) {
        $query = "SELECT 
            (6371 * acos(
                cos(radians(:lat1)) 
                * cos(radians(:lat2)) 
                * cos(radians(:lng2) - radians(:lng1)) 
                + sin(radians(:lat1)) 
                * sin(radians(:lat2))
            )) as distance";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':lat1' => $lat1,
            ':lng1' => $lng1,
            ':lat2' => $lat2,
            ':lng2' => $lng2
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['distance'];
    }

    // Get potential matches (PostgreSQL version)
    public function getPotentialMatches() {
        if (!$this->validateLocation()) {
            throw new Exception("Current user has invalid location coordinates");
        }

        $query = "SELECT u.id, u.name, u.gender, u.profile_picture, u.bio,
                    ST_X(u.location_coords::geometry) as lng, 
                    ST_Y(u.location_coords::geometry) as lat,
                    (6371 * acos(
                        cos(radians(:current_lat)) 
                        * cos(radians(ST_Y(u.location_coords::geometry))) 
                        * cos(radians(ST_X(u.location_coords::geometry)) - radians(:current_lng)) 
                        + sin(radians(:current_lat)) 
                        * sin(radians(ST_Y(u.location_coords::geometry)))
                    )) as distance,
                    COUNT(DISTINCT ua.activity_id) as shared_activities
                FROM " . $this->table_name . " u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                LEFT JOIN user_activities current_ua ON 
                    current_ua.user_id = :user_id AND 
                    current_ua.activity_id = ua.activity_id
                LEFT JOIN swipes s ON 
                    s.swiper_id = :user_id AND 
                    s.swiped_id = u.id
                WHERE u.gender != :gender
                AND u.id != :user_id
                AND s.id IS NULL
                AND ST_Y(u.location_coords::geometry) BETWEEN -90 AND 90
                AND ST_X(u.location_coords::geometry) BETWEEN -180 AND 180
                GROUP BY u.id
                HAVING distance IS NOT NULL
                AND distance <= :radius
                ORDER BY shared_activities DESC, distance ASC
                LIMIT 50";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':gender', $this->gender, PDO::PARAM_STR);
        $stmt->bindValue(':current_lat', $this->lat, PDO::PARAM_STR);
        $stmt->bindValue(':current_lng', $this->lng, PDO::PARAM_STR);
        $stmt->bindValue(':radius', $this->radius_preference, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Matching query failed: " . $e->getMessage());
            throw new Exception("Could not process matching");
        }
    }

    // Update user location (PostgreSQL version)
    public function updateLocation() {
        $query = "UPDATE " . $this->table_name . "
                SET location_coords = ST_MakePoint(:lng, :lat)
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':lat', $this->lat);
        $stmt->bindParam(':lng', $this->lng);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    // Update radius preference (PostgreSQL version)
    public function updateRadiusPreference() {
        $query = "UPDATE " . $this->table_name . "
                SET radius_preference = :radius_preference
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':radius_preference', $this->radius_preference);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
?>