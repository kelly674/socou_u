<?php
// Désactiver l'affichage des erreurs pour éviter la pollution de la sortie
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Nettoyer le buffer de sortie
if (ob_get_level()) {
    ob_clean();
}

// Démarrer la session
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Rediriger vers l'accueil si accès direct
    header('Location: ' . SITE_URL);
    exit();
}

// Récupération et validation des données
$email = trim($_POST['email'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? SITE_URL;

// Validation de l'email
if (empty($email)) {
    $_SESSION['newsletter_error'] = 'L\'adresse email est requise.';
    header('Location: ' . $referer);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['newsletter_error'] = 'Veuillez saisir une adresse email valide.';
    header('Location: ' . $referer);
    exit();
}

if (strlen($email) > 150) {
    $_SESSION['newsletter_error'] = 'L\'adresse email est trop longue.';
    header('Location: ' . $referer);
    exit();
}

try {
    $conn = getConnection();
    
    // Générer un nom temporaire à partir de l'email
    $nom = explode('@', $email)[0];
    
    // Vérifier si l'email existe déjà
    $check_query = "SELECT id_abonne, statut, nom FROM newsletter_abonnes WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $check_stmt->execute();
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['statut'] === 'actif') {
            $_SESSION['newsletter_info'] = 'Cette adresse email est déjà abonnée à notre newsletter.';
        } else {
            // Réactiver l'abonnement s'il était désactivé
            $update_query = "UPDATE newsletter_abonnes 
                           SET nom = :nom, statut = 'actif', date_inscription = NOW() 
                           WHERE email = :email";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($update_stmt->execute()) {
                $_SESSION['newsletter_success'] = 'Votre abonnement à notre newsletter a été réactivé avec succès !';
                
                // Log de l'activité
                logActivity(null, 'newsletter_reactivation', 'newsletter_abonnes', $existing['id_abonne'], 
                           "Réactivation newsletter pour $email");
            } else {
                $_SESSION['newsletter_error'] = 'Une erreur est survenue lors de la réactivation. Veuillez réessayer.';
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
            $_SESSION['newsletter_success'] = 'Merci ! Vous êtes maintenant abonné(e) à notre newsletter.';
            
            // Log de l'activité
            logActivity(null, 'newsletter_subscription', 'newsletter_abonnes', $new_id, 
                       "Nouvel abonnement newsletter pour $email");
            
            // Envoyer un email de bienvenue en arrière-plan (optionnel)
            if (function_exists('sendWelcomeEmailAsync')) {
                sendWelcomeEmailAsync($email, $nom);
            } elseif (function_exists('sendWelcomeEmail')) {
                try {
                    sendWelcomeEmail($email, $nom);
                } catch (Exception $e) {
                    // Log l'erreur mais ne pas interrompre le processus
                    error_log("Erreur envoi email bienvenue: " . $e->getMessage());
                }
            }
        } else {
            $_SESSION['newsletter_error'] = 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.';
        }
    }
    
} catch (PDOException $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur newsletter PDO: " . $e->getMessage());
    $_SESSION['newsletter_error'] = 'Une erreur de base de données est survenue. Veuillez réessayer plus tard.';
    
} catch (Exception $e) {
    // Log l'erreur pour le débogage  
    error_log("Erreur newsletter: " . $e->getMessage());
    $_SESSION['newsletter_error'] = 'Une erreur technique est survenue. Veuillez réessayer plus tard.';
}

// Rediriger vers la page d'origine avec ancre vers la section newsletter
$redirect_url = $referer;
if (strpos($redirect_url, '#') === false) {
    $redirect_url .= '#newsletter-section';
}

header('Location: ' . $redirect_url);
exit();

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

// Fonction pour envoyer un email de bienvenue de manière asynchrone
function sendWelcomeEmailAsync($email, $nom) {
    // Créer une tâche en arrière-plan ou utiliser une queue
    // Pour une implémentation simple, on peut utiliser exec avec nohup
    $command = "php " . __DIR__ . "/scripts/send_welcome_email.php " . 
               escapeshellarg($email) . " " . escapeshellarg($nom) . " > /dev/null 2>&1 &";
    
    if (function_exists('exec')) {
        exec($command);
    }
}

// Fonction pour envoyer un email de bienvenue (version synchrone)
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
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background-color: #198754; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #ffffff; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .highlight { background-color: #d4edda; padding: 10px; border-left: 4px solid #198754; margin: 15px 0; }
                ul { padding-left: 20px; }
                li { margin-bottom: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>SOCOU_U</h1>
                    <p style='margin: 5px 0 0 0;'>Société Coopérative UMUSHINGE W'UBUZIMA</p>
                </div>
                
                <div class='content'>
                    <h2>Bienvenue " . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . " !</h2>
                    
                    <p>Merci de vous être abonné(e) à notre newsletter. Vous recevrez désormais toutes nos actualités, événements et annonces importantes directement dans votre boîte mail.</p>
                    
                    <div class='highlight'>
                        <h3 style='margin-top: 0;'>Ce que vous recevrez :</h3>
                        <ul>
                            <li>📰 <strong>Nos dernières actualités</strong> - Restez au courant de nos projets</li>
                            <li>📅 <strong>Les événements et formations</strong> - Ne ratez aucune opportunité</li>
                            <li>📢 <strong>Les annonces importantes</strong> - Informations prioritaires</li>
                            <li>🌱 <strong>Les conseils agricoles et d'élevage</strong> - Expertise pratique</li>
                            <li>🤝 <strong>Les opportunités de partenariat</strong> - Développez votre réseau</li>
                        </ul>
                    </div>
                    
                    <p><strong>Notre mission</strong> est de promouvoir la <em>solidarité</em>, l'<em>autonomie</em> et le <em>développement durable</em> au sein de notre communauté.</p>
                    
                    <p>Si vous avez des questions, n'hésitez pas à nous contacter à <a href='mailto:contact@socou-u.bi' style='color: #198754;'>contact@socou-u.bi</a></p>
                    
                    <p>Cordialement,<br><strong>L'équipe SOCOU_U</strong></p>
                </div>
                
                <div class='footer'>
                    <p><strong>SOCOU_U</strong><br>Province de Bujumbura, Zone Rohero, Commune Mukaza</p>
                    <p>
                        Vous recevez cet email car vous vous êtes abonné(e) à notre newsletter.<br>
                        <a href='" . $site_url . "/newsletter/unsubscribe.php?email=" . urlencode($email) . "' style='color: #666;'>Se désabonner</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: SOCOU_U <noreply@socou-u.bi>',
            'Reply-To: contact@socou-u.bi',
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3'
        ];
        
        // Utiliser mail() de PHP
        return mail($email, $subject, $message, implode("\r\n", $headers));
        
    } catch(Exception $e) {
        // Log l'erreur mais ne pas interrompre le processus
        error_log("Erreur envoi email bienvenue: " . $e->getMessage());
        return false;
    }
}
?>