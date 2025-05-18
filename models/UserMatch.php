<?php
class UserMatch {
    private $conn;
    private $table_name = "matches";

    public $id;
    public $user1_id;
    public $user2_id;
    public $matched_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a match
    public function create() {
        // Use INSERT ... RETURNING id to get the inserted id in PostgreSQL
        $query = "INSERT INTO " . $this->table_name . " (user1_id, user2_id)
                  VALUES (:user1_id, :user2_id)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user1_id", $this->user1_id);
        $stmt->bindParam(":user2_id", $this->user2_id);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $this->id = $row['id'];
                return true;
            }
        }

        return false;
    }

    // Get user matches
    public function getUserMatches($user_id) {
        $query = "SELECT m.id, 
                    CASE 
                        WHEN m.user1_id = :user_id THEN m.user2_id
                        ELSE m.user1_id
                    END as matched_user_id,
                    u.name as matched_user_name,
                    m.matched_at
                FROM " . $this->table_name . " m
                JOIN users u ON (
                    CASE 
                        WHEN m.user1_id = :user_id THEN m.user2_id
                        ELSE m.user1_id
                    END = u.id
                )
                WHERE m.user1_id = :user_id OR m.user2_id = :user_id
                ORDER BY m.matched_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
