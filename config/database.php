<?php
// Configuration de la base de données pour SOCOU_U
define('DB_HOST', 'localhost');
define('DB_NAME', 'socou_u_db');
define('DB_USER', 'root');
define('DB_PASS', '00000000');
define('DB_CHARSET', 'utf8mb4');

// Classe de connexion à la base de données
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erreur de connexion: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Fonction pour obtenir une connexion
function getConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>