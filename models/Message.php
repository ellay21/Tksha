<?php
class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $match_id;
    public $sender_id;
    public $content;
    public $sent_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a message (PostgreSQL version)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (match_id, sender_id, content)
                VALUES (:match_id, :sender_id, :content)
                RETURNING id";

        $stmt = $this->conn->prepare($query);

        $this->content = htmlspecialchars(strip_tags($this->content));

        $stmt->bindParam(":match_id", $this->match_id);
        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":content", $this->content);

        if($stmt->execute()) {
            // Get the returned ID from PostgreSQL
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            return true;
        }

        return false;
    }

    // Get messages for a match (PostgreSQL version)
    public function getMatchMessages($match_id) {
        $query = "SELECT 
                    m.id, 
                    m.sender_id, 
                    u.name as sender_name, 
                    m.content, 
                    TO_CHAR(m.sent_at, 'YYYY-MM-DD HH24:MI:SS') as sent_at
                  FROM " . $this->table_name . " m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.match_id = :match_id
                  ORDER BY m.sent_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":match_id", $match_id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>