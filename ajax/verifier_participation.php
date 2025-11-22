<?php
// ajax/verifier_participation.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../functions/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['participe' => false]);
    exit;
}

$projet_id = $_GET['projet_id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    $pdo = getConnection();
    // Récupérer l'ID du membre
    $stmt = $pdo->prepare("SELECT id_membre FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        echo json_encode(['participe' => false]);
        exit;
    }
    
    $membre_id = $user_data['id_membre'];
    
    // Vérifier la participation
    $stmt = $pdo->prepare("
        SELECT bp.*, ps.nom_projet 
        FROM beneficiaires_projets bp
        INNER JOIN projets_sociaux ps ON bp.id_projet = ps.id_projet
        WHERE bp.id_membre = ? AND bp.id_projet = ? 
        AND bp.statut_participation IN ('inscrit', 'actif')
    ");
    
    $stmt->execute([$membre_id, $projet_id]);
    $participation = $stmt->fetch();
    
    echo json_encode([
        'participe' => (bool)$participation,
        'statut' => $participation ? $participation['statut_participation'] : null,
        'date_inscription' => $participation ? $participation['date_inscription'] : null
    ]);
    
} catch(PDOException $e) {
    error_log("Erreur vérification participation: " . $e->getMessage());
    echo json_encode(['participe' => false]);
}
?>