<?php
// ajax/mes_participations.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../functions/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
     $pdo = getConnection();
    // Récupérer l'ID du membre
    $stmt = $pdo->prepare("SELECT id_membre FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    $membre_id = $user_data['id_membre'];
    
    // Récupérer toutes les participations du membre
    $query = "
        SELECT 
            bp.id_beneficiaire,
            bp.date_inscription,
            bp.statut_participation,
            bp.evaluation,
            bp.observations,
            ps.id_projet,
            ps.nom_projet,
            ps.description,
            ps.statut as statut_projet,
            ps.date_debut,
            ps.date_fin,
            ps.zone_intervention,
            CASE 
                WHEN ps.statut = 'termine' AND bp.statut_participation = 'termine' THEN 'complete'
                WHEN ps.statut = 'en_cours' AND bp.statut_participation IN ('inscrit', 'actif') THEN 'active'
                WHEN bp.statut_participation = 'abandonne' THEN 'abandonne'
                ELSE 'autre'
            END as statut_participation_global
        FROM beneficiaires_projets bp
        INNER JOIN projets_sociaux ps ON bp.id_projet = ps.id_projet
        WHERE bp.id_membre = ?
        ORDER BY bp.date_inscription DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$membre_id]);
    $participations = $stmt->fetchAll();
    
    // Statistiques
    $stats = [
        'total' => count($participations),
        'actives' => 0,
        'terminees' => 0,
        'abandonnees' => 0
    ];
    
    foreach ($participations as $participation) {
        switch ($participation['statut_participation_global']) {
            case 'active':
                $stats['actives']++;
                break;
            case 'complete':
                $stats['terminees']++;
                break;
            case 'abandonne':
                $stats['abandonnees']++;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'participations' => $participations,
        'statistiques' => $stats
    ]);
    
} catch(PDOException $e) {
    error_log("Erreur récupération participations: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique']);
}
?>