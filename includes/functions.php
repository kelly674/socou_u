<?php
// Fichier des fonctions utilitaires pour SOCOU_U

// Fonction pour afficher les messages d'alerte
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Fonction pour générer un code membre unique
function generateMemberCode() {
    $conn = getConnection();
    $year = date('Y');
    
    // Compter le nombre de membres pour cette année
    $query = "SELECT COUNT(*) as count FROM membres WHERE YEAR(date_adhesion) = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$year]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
    
    return 'SOCOU' . $year . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// Fonction pour générer un numéro de commande unique
function generateOrderNumber() {
    $date = date('Ymd');
    $conn = getConnection();
    
    // Compter les commandes du jour
    $query = "SELECT COUNT(*) as count FROM commandes WHERE DATE(date_commande) = CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
    
    return 'CMD' . $date . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// Fonction pour valider l'email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fonction pour valider le téléphone burundais
function isValidPhone($phone) {
    // Format: +257XXXXXXXX ou 257XXXXXXXX ou XXXXXXXX
    $pattern = '/^(\+?257)?[0-9]{8}$/';
    return preg_match($pattern, $phone);
}

// Fonction pour hacher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour obtenir les statistiques générales
function getGeneralStats() {
    $conn = getConnection();
    $stats = [];
    
    // Nombre total de membres actifs
    $query = "SELECT COUNT(*) as count FROM membres WHERE statut = 'actif'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['membres_actifs'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Nombre total de produits disponibles
    $query = "SELECT COUNT(*) as count FROM produits WHERE statut = 'disponible'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['produits_disponibles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Nombre de projets en cours
    $query = "SELECT COUNT(*) as count FROM projets_sociaux WHERE statut = 'en_cours'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['projets_actifs'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Nombre de formations programmées
    $query = "SELECT COUNT(*) as count FROM formations WHERE statut = 'programmee' AND date_formation >= CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['formations_programmees'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    return $stats;
}

// Fonction pour obtenir les dernières actualités
function getLatestNews($limit = 5) {
    $conn = getConnection();
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 5;
    
    $query = "SELECT a.*, u.username as auteur 
              FROM actualites a 
              LEFT JOIN utilisateurs u ON a.auteur_id = u.id_utilisateur
              WHERE a.statut = 'publie' AND (a.date_publication <= NOW() OR a.date_publication IS NULL)
              ORDER BY a.date_creation DESC 
              LIMIT " . $limit;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir les produits les plus récents
function getLatestProducts($limit = 8) {
    $conn = getConnection();
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 8;
    
    $query = "SELECT p.*, c.nom_categorie, m.nom, m.prenom 
              FROM produits p 
              LEFT JOIN categories_produits c ON p.id_categorie = c.id_categorie
              LEFT JOIN membres m ON p.producteur_id = m.id_membre
              WHERE p.statut = 'disponible' 
              ORDER BY p.date_creation DESC 
              LIMIT " . $limit;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir les témoignages approuvés
function getApprovedTestimonials($limit = 3) {
    $conn = getConnection();
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 3;
    
    $query = "SELECT * FROM temoignages WHERE statut = 'approuve' ORDER BY date_creation DESC LIMIT " . $limit;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir les formations à venir
function getUpcomingTrainings($limit = 5) {
    $conn = getConnection();
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 5;
    
    $query = "SELECT * FROM formations 
              WHERE statut = 'programmee' AND date_formation >= CURDATE() 
              ORDER BY date_formation ASC 
              LIMIT " . $limit;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour calculer l'âge
function calculateAge($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    return $birth->diff($today)->y;
}

// Fonction pour tronquer le texte
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

// Fonction pour générer une URL-friendly slug
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Fonction pour obtenir le chemin de l'avatar par défaut
function getDefaultAvatar($gender = null) {
    $default = '/assets/images/default-avatar.png';
    if ($gender == 'M') {
        return '/assets/images/default-male-avatar.png';
    } elseif ($gender == 'F') {
        return '/assets/images/default-female-avatar.png';
    }
    return $default;
}

// Fonction pour envoyer un email simple
function sendEmail($to, $subject, $message, $from = null) {
    $from = $from ?: ConfigManager::get('email', 'noreply@socou-u.bi');
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
// Fonction pour requérir une authentification (redirige si non connecté)
function requireAuth($redirect_url = '/login.php') {
    if (!isLoggedIn()) {
        setMessage('Vous devez être connecté pour accéder à cette page.', 'warning');
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Fonction pour requérir un rôle spécifique
function requireRole($required_role, $redirect_url = '/') {
    requireAuth();
    
    if (!hasRole($required_role)) {
        setMessage('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'danger');
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Fonction pour nettoyer les données d'entrée
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier la force d'un mot de passe
function isStrongPassword($password, $min_length = 8) {
    if (strlen($password) < $min_length) {
        return false;
    }
    
    // Vérifier qu'il contient au moins une majuscule, une minuscule et un chiffre
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

// Fonction pour vérifier les permissions sur une ressource
function canEdit($resource_type, $resource_id, $owner_field = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // L'admin peut tout éditer
    if (isAdmin()) {
        return true;
    }
    
    // Si pas de champ propriétaire spécifié, seul l'admin peut éditer
    if (!$owner_field) {
        return false;
    }
    
    $conn = getConnection();
    $current_user_id = getCurrentUserId();
    
    // Vérifier si l'utilisateur est le propriétaire
    $query = "SELECT {$owner_field} FROM {$resource_type} WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$resource_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result[$owner_field] == $current_user_id;
}

// Fonction pour obtenir un champ de token CSRF caché
function getCSRFField() {
    return '<input type="hidden" name="csrf_token" value="' . escape(generateCSRFToken()) . '">';
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return hasRole('admin');
}


function redirect($url) {
    header("Location: $url");
    exit();
}
// Fonction pour vérifier si un membre peut accéder à une ressource
function canAccessResource($resource_type, $resource_id) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // L'admin peut tout voir
    if (hasRole('admin')) {
        return true;
    }
    
    $conn = getConnection();
    $member_id = $_SESSION['member_id'];
    
    switch ($resource_type) {
        case 'commande':
            $query = "SELECT client_id FROM commandes WHERE id_commande = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$resource_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['client_id'] == $member_id;
            
        case 'produit':
            $query = "SELECT producteur_id FROM produits WHERE id_produit = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$resource_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['producteur_id'] == $member_id;
            
        default:
            return false;
    }
}

// Fonction pour obtenir le statut badge
function getStatusBadge($status, $type = 'membre') {
    $badges = [
        'membre' => [
            'actif' => 'success',
            'inactif' => 'secondary',
            'suspendu' => 'danger'
        ],
        'commande' => [
            'en_attente' => 'warning',
            'confirmee' => 'info',
            'preparation' => 'primary',
            'expediee' => 'success',
            'livree' => 'success',
            'annulee' => 'danger'
        ],
        'formation' => [
            'programmee' => 'info',
            'en_cours' => 'warning',
            'terminee' => 'success',
            'annulee' => 'danger'
        ]
    ];
    
    $badge_class = $badges[$type][$status] ?? 'secondary';
    return "<span class='badge bg-{$badge_class}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}

// Fonction pour créer un breadcrumb
function createBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $is_last = ($index === count($items) - 1);
        
        if ($is_last) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . escape($item['title']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . escape($item['url']) . '">' . escape($item['title']) . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}
?>