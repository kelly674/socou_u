<?php
// ajax/participer_projet.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour participer']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$projet_id = $input['projet_id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    $pdo = getConnection();
    // Récupérer l'ID du membre depuis l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT id_membre FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    $membre_id = $user_data['id_membre'];
    
    // Vérifier si le projet existe et est en cours
    $stmt = $pdo->prepare("SELECT id_projet, nom_projet, statut, beneficiaires_cibles FROM projets_sociaux WHERE id_projet = ?");
    $stmt->execute([$projet_id]);
    $projet = $stmt->fetch();
    
    if (!$projet) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé']);
        exit;
    }
    
    if ($projet['statut'] !== 'en_cours') {
        echo json_encode(['success' => false, 'message' => 'Ce projet n\'accepte plus de participants']);
        exit;
    }
    
    // Vérifier si le membre est déjà inscrit
    $stmt = $pdo->prepare("SELECT id_beneficiaire FROM beneficiaires_projets WHERE id_membre = ? AND id_projet = ?");
    $stmt->execute([$membre_id, $projet_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous participez déjà à ce projet']);
        exit;
    }
    
    // Vérifier le nombre de participants actuels
    $stmt = $pdo->prepare("SELECT COUNT(*) as nb_participants FROM beneficiaires_projets WHERE id_projet = ? AND statut_participation IN ('inscrit', 'actif')");
    $stmt->execute([$projet_id]);
    $nb_participants = $stmt->fetchColumn();
    
    if ($nb_participants >= $projet['beneficiaires_cibles']) {
        echo json_encode(['success' => false, 'message' => 'Le nombre maximum de participants est atteint']);
        exit;
    }
    
    // Inscrire le membre au projet
    $stmt = $pdo->prepare("
        INSERT INTO beneficiaires_projets (id_membre, id_projet, date_inscription, statut_participation, observations) 
        VALUES (?, ?, CURDATE(), 'inscrit', 'Inscription via le site web')
    ");
    
    $stmt->execute([$membre_id, $projet_id]);
    
    // Log de l'activité
    $stmt = $pdo->prepare("
        INSERT INTO logs_activites (utilisateur_id, action, table_concernee, id_enregistrement, details, adresse_ip) 
        VALUES (?, 'inscription_projet', 'beneficiaires_projets', ?, ?, ?)
    ");
    
    $details = "Inscription au projet: " . $projet['nom_projet'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$user_id, $pdo->lastInsertId(), $details, $ip]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Votre demande de participation a été enregistrée avec succès ! Nous vous contacterons bientôt.',
        'nouveau_nb_participants' => $nb_participants + 1
    ]);
    
} catch(PDOException $e) {
    error_log("Erreur participation projet: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique. Veuillez réessayer plus tard.']);
}
?>