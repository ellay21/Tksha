<?php
include_once __DIR__ . '/../models/UserMatch.php';  // Updated filename

class Swipe {
    private $conn;
    private $table_name = "swipes";

    public $id;
    public $swiper_id;
    public $swiped_id;
    public $is_like;
    public $swiped_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a swipe
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (swiper_id, swiped_id, is_like)
                  VALUES (:swiper_id, :swiped_id, :is_like)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":swiper_id", $this->swiper_id);
        $stmt->bindParam(":swiped_id", $this->swiped_id);
        $stmt->bindParam(":is_like", $this->is_like, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Check if this creates a match
            if ($this->is_like) {
                $this->checkForMatch();
            }
            return true;
        }

        return false;
    }

    // Check if this swipe creates a match
    private function checkForMatch() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE swiper_id = :swiped_id
                  AND swiped_id = :swiper_id
                  AND is_like = 1";

        $stmt = $this->conn->prepare($query);

        // Swap parameters here to check reverse swipe
        $stmt->bindParam(":swiped_id", $this->swiper_id);
        $stmt->bindParam(":swiper_id", $this->swiped_id);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Create a match
            $match = new UserMatch($this->conn);
            $match->user1_id = $this->swiper_id;
            $match->user2_id = $this->swiped_id;
            $match->create();
        }
    }
}
?>
