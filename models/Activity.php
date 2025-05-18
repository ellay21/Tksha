<?php
class Activity {
    private $conn;
    private $table_name = "activities";

    public $id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all activities
    public function getAll() {
        $query = "SELECT id, name FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get user activities
    public function getUserActivities($user_id) {
        $query = "SELECT a.id, a.name 
                FROM " . $this->table_name . " a
                JOIN user_activities ua ON a.id = ua.activity_id
                WHERE ua.user_id = ?
                ORDER BY a.name";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Save user activities
    public function saveUserActivities($user_id, $activity_ids) {
        // First delete existing activities
        $delete_query = "DELETE FROM user_activities WHERE user_id = ?";
        $delete_stmt = $this->conn->prepare($delete_query);
        $delete_stmt->bindParam(1, $user_id);
        $delete_stmt->execute();
        
        // Then insert new activities
        $insert_query = "INSERT INTO user_activities (user_id, activity_id) VALUES (?, ?)";
        $insert_stmt = $this->conn->prepare($insert_query);
        
        foreach($activity_ids as $activity_id) {
            $insert_stmt->bindParam(1, $user_id);
            $insert_stmt->bindParam(2, $activity_id);
            $insert_stmt->execute();
        }
        
        return true;
    }
}
?>
