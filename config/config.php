<?php
// Configuration générale pour SOCOU_U
session_start();

// Inclusion de la configuration de la base de données
require_once 'database.php';

// Configuration générale du site
define('SITE_URL', 'http://localhost/socou_u'); // À modifier selon votre configuration
define('SITE_NAME', 'SOCOU_U');
define('SITE_ICONE','socou_u.png');
define('SITE_TITLE', 'Société Coopérative UMUSHINGE W\'UBUZIMA');
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Configuration de l'upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
/*
// Configuration de sécurité
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 si HTTPS
*/
// Classe pour la gestion des configurations
class ConfigManager {
    private $conn;
    private static $configs = [];

    public function __construct() {
        $this->conn = getConnection();
        $this->loadConfigs();
    }

    private function loadConfigs() {
        if (empty(self::$configs)) {
            $query = "SELECT cle, valeur FROM configurations";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$configs[$row['cle']] = $row['valeur'];
            }
        }
    }

    public static function get($key, $default = '') {
        return isset(self::$configs[$key]) ? self::$configs[$key] : $default;
    }

    public function set($key, $value) {
        $query = "INSERT INTO configurations (cle, valeur) VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$key, $value]);
        self::$configs[$key] = $value;
    }
}

// Initialisation du gestionnaire de configuration
$config = new ConfigManager();

// Fonction d'authentification
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fonction pour vérifier le rôle
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Fonction pour rediriger si pas admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

// Fonction de sécurité pour échapper les données
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction pour logger les activités
function logActivity($action, $table, $record_id, $details = '') {
    if (isLoggedIn()) {
        $conn = getConnection();
        $query = "INSERT INTO logs_activites (utilisateur_id, action, table_concernee, id_enregistrement, details, adresse_ip) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $table,
            $record_id,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
}

// Fonction pour formater la date
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Fonction pour formater l'argent
function formatMoney($amount, $currency = 'BIF') {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

// Fonction pour uploader un fichier
function uploadFile($file, $type = 'image', $subfolder = '') {
    $upload_dir = UPLOAD_PATH . $subfolder . '/';
    $upload_url = UPLOAD_URL . $subfolder . '/';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ($type == 'image') ? ALLOWED_IMAGE_TYPES : ALLOWED_DOCUMENT_TYPES;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Vérifications
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('Fichier trop volumineux');
    }
    
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('Type de fichier non autorisé');
    }
    
    // Générer un nom unique
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $subfolder . '/' . $filename;
    } else {
        throw new Exception('Erreur lors de l\'upload');
    }
}

// Fonction pour paginer
function paginate($total_records, $records_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}
?>