<?php
class Database {
    private $host = "dpg-d0ku13buibrs739rm5o0-a.oregon-postgres.render.com";
    private $db_name = "tksha";
    private $username = "ellay";
    private $password = "5TCdJiB7Xy6zuSAs2rw1mok5izBxZf8F";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set client encoding
            $this->conn->exec("SET CLIENT_ENCODING TO 'UTF8'");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
