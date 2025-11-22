<?php
// Désactiver l'affichage des erreurs pour éviter la pollution du JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Nettoyer le buffer de sortie
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Protection CSRF basique
session_start();

// Vérifier si les fichiers requis existent avant de les inclure
$config_file = __DIR__ . '/../config/database.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration manquante']);
    exit();
}

require_once $config_file;

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupération et validation des données
$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validation des données
$errors = [];

if (empty($nom)) {
    $errors[] = 'Le nom est requis';
} elseif (strlen($nom) < 2) {
    $errors[] = 'Le nom doit contenir au moins 2 caractères';
} elseif (strlen($nom) > 100) {
    $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
}

if (empty($email)) {
    $errors[] = 'L\'email est requis';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L\'adresse email n\'est pas valide';
} elseif (strlen($email) > 150) {
    $errors[] = 'L\'adresse email est trop longue';
}

// Si il y a des erreurs de validation
if (!empty($errors)) {
    echo json_encode([
        'success' => false, 
        'message' => implode('. ', $errors)
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Vérifier que la fonction getConnection existe
    if (!function_exists('getConnection')) {
        throw new Exception('Fonction de connexion à la base de données non disponible');
    }
    
    $conn = getConnection();
    
    // Vérifier la connexion
    if (!$conn) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Vérifier si l'email existe déjà
    $check_query = "SELECT id_abonne, statut FROM newsletter_abonnes WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $check_stmt->execute();
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['statut'] === 'actif') {
            echo json_encode([
                'success' => false,
                'message' => 'Cette adresse email est déjà abonnée à notre newsletter'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            // Réactiver l'abonnement s'il était désactivé
            $update_query = "UPDATE newsletter_abonnes 
                           SET nom = :nom, statut = 'actif', date_inscription = NOW() 
                           WHERE email = :email";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($update_stmt->execute()) {
                // Log de l'activité
                logActivity(null, 'newsletter_reactivation', 'newsletter_abonnes', $existing['id_abonne'], 
                           "Réactivation newsletter pour $email");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Votre abonnement à notre newsletter a été réactivé avec succès !'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Erreur lors de la réactivation de l\'abonnement');
            }
        }
    } else {
        // Nouvel abonnement
        $insert_query = "INSERT INTO newsletter_abonnes (nom, email, statut, date_inscription) 
                        VALUES (:nom, :email, 'actif', NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $insert_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        
        if ($insert_stmt->execute()) {
            $new_id = $conn->lastInsertId();
            
            // Log de l'activité
            logActivity(null, 'newsletter_subscription', 'newsletter_abonnes', $new_id, 
                       "Nouvel abonnement newsletter pour $email");
            
            // Envoyer un email de confirmation (optionnel)
            if (function_exists('sendWelcomeEmail')) {
                sendWelcomeEmail($email, $nom);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Merci ! Vous êtes maintenant abonné(e) à notre newsletter. Vous recevrez bientôt nos dernières actualités.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Erreur lors de l\'inscription à la newsletter');
        }
    }
    
} catch(PDOException $e) {
    // Log l'erreur pour le débogage (sans l'afficher)
    error_log("Erreur newsletter subscription PDO: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur de base de données est survenue. Veuillez réessayer plus tard.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    // Log l'erreur pour le débogage (sans l'afficher)
    error_log("Erreur newsletter subscription: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur technique est survenue. Veuillez réessayer plus tard.'
    ], JSON_UNESCAPED_UNICODE);
}

// Fonction pour logger les activités (si elle n'existe pas dans functions.php)
function logActivity($user_id, $action, $table, $record_id, $details) {
    try {
        if (!function_exists('getConnection')) {
            return false;
        }
        
        $conn = getConnection();
        if (!$conn) {
            return false;
        }
        
        $log_query = "INSERT INTO logs_activites 
                     (utilisateur_id, action, table_concernee, id_enregistrement, details, adresse_ip, date_action) 
                     VALUES (:user_id, :action, :table, :record_id, :details, :ip, NOW())";
        
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $log_stmt->bindParam(':table', $table, PDO::PARAM_STR);
        $log_stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':details', $details, PDO::PARAM_STR);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $log_stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
        
        return $log_stmt->execute();
    } catch(Exception $e) {
        // Ignorer les erreurs de log pour ne pas affecter le processus principal
        error_log("Erreur log activity: " . $e->getMessage());
        return false;
    }
}

// Fonction optionnelle pour envoyer un email de bienvenue
function sendWelcomeEmail($email, $nom) {
    try {
        // Vérifier si la constante SITE_URL est définie
        $site_url = defined('SITE_URL') ? SITE_URL : 'https://votre-site.com';
        
        $subject = "Bienvenue dans la newsletter de SOCOU_U !";
        $message = "
        <html>
        <head>
            <title>Bienvenue chez SOCOU_U</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>SOCOU_U</h1>
                <p>Société Coopérative UMUSHINGE W'UBUZIMA</p>
            </div>
            
            <div class='content'>
                <h2>Bienvenue " . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . " !</h2>
                
                <p>Merci de vous être abonné(e) à notre newsletter. Vous recevrez désormais toutes nos actualités, événements et annonces importantes directement dans votre boîte mail.</p>
                
                <h3>Ce que vous recevrez :</h3>
                <ul>
                    <li>📰 Nos dernières actualités</li>
                    <li>📅 Les événements et formations à venir</li>
                    <li>📢 Les annonces importantes</li>
                    <li>🌱 Les conseils agricoles et d'élevage</li>
                    <li>🤝 Les opportunités de partenariat</li>
                </ul>
                
                <p>Notre mission est de promouvoir la <strong>solidarité</strong>, l'<strong>autonomie</strong> et le <strong>développement durable</strong> au sein de notre communauté.</p>
                
                <p>Si vous avez des questions, n'hésitez pas à nous contacter à <a href='mailto:contact@socou-u.bi'>contact@socou-u.bi</a></p>
                
                <p>Cordialement,<br>L'équipe SOCOU_U</p>
            </div>
            
            <div class='footer'>
                <p>SOCOU_U - Province de Bujumbura, Zone Rohero, Commune Mukaza</p>
                <p>
                    <a href='" . $site_url . "/newsletter/unsubscribe.php?email=" . urlencode($email) . "'>Se désabonner</a>
                </p>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: SOCOU_U <noreply@socou-u.bi>',
            'Reply-To: contact@socou-u.bi',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Utiliser mail() de PHP ou une bibliothèque comme PHPMailer selon la configuration
        return mail($email, $subject, $message, implode("\r\n", $headers));
        
    } catch(Exception $e) {
        // Log l'erreur mais ne pas interrompre le processus
        error_log("Erreur envoi email bienvenue: " . $e->getMessage());
        return false;
    }
}
?>