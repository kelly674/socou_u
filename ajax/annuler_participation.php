<?php
// ajax/annuler_participation.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../functions/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$beneficiaire_id = $input['beneficiaire_id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    $pdo = getConnection();
    // Vérifier que la participation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT bp.*, ps.nom_projet, ps.statut as statut_projet
        FROM beneficiaires_projets bp
        INNER JOIN projets_sociaux ps ON bp.id_projet = ps.id_projet
        INNER JOIN utilisateurs u ON bp.id_membre = u.id_membre
        WHERE bp.id_beneficiaire = ? AND u.id_utilisateur = ?
    ");
    
    $stmt->execute([$beneficiaire_id, $user_id]);
    $participation = $stmt->fetch();
    
    if (!$participation) {
        echo json_encode(['success' => false, 'message' => 'Participation non trouvée']);
        exit;
    }
    
    // Vérifier si l'annulation est possible
    if ($participation['statut_participation'] === 'termine') {
        echo json_encode(['success' => false, 'message' => 'Impossible d\'annuler une participation terminée']);
        exit;
    }
    
    if ($participation['statut_participation'] === 'abandonne') {
        echo json_encode(['success' => false, 'message' => 'Cette participation est déjà annulée']);
        exit;
    }
    
    // Mettre à jour le statut
    $stmt = $pdo->prepare("
        UPDATE beneficiaires_projets 
        SET statut_participation = 'abandonne', 
            observations = CONCAT(COALESCE(observations, ''), '\n--- Annulation le ', CURDATE(), ' via le site web ---')
        WHERE id_beneficiaire = ?
    ");
    
    $stmt->execute([$beneficiaire_id]);
    
    // Log de l'activité
    $stmt = $pdo->prepare("
        INSERT INTO logs_activites (utilisateur_id, action, table_concernee, id_enregistrement, details, adresse_ip) 
        VALUES (?, 'annulation_participation', 'beneficiaires_projets', ?, ?, ?)
    ");
    
    $details = "Annulation participation au projet: " . $participation['nom_projet'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$user_id, $beneficiaire_id, $details, $ip]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre participation a été annulée avec succès'
    ]);
    
} catch(PDOException $e) {
    error_log("Erreur annulation participation: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique']);
}
?>