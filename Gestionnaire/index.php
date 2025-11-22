<?php
$page_title = "Espace Gestionnaire";
include '../config/config.php';
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

// Statistiques spécifiques au gestionnaire
try {
    $pdo = getConnection();
    
    // Membres par groupe
    $stmt = $pdo->query("
        SELECT g.nom_groupe, COUNT(mg.id_membre) as nb_membres 
        FROM groupes g 
        LEFT JOIN membres_groupes mg ON g.id_groupe = mg.id_groupe 
        WHERE g.statut = 'actif' AND mg.statut = 'actif'
        GROUP BY g.id_groupe
    ");
    $groupes_stats = $stmt->fetchAll();
    
    // Investissements en attente de validation
    $stmt = $pdo->query("SELECT COUNT(*) FROM investissement WHERE statut = 'en_attente'");
    $investissements_attente = $stmt->fetchColumn();
    
    // Demandes de crédit à étudier
    $stmt = $pdo->query("SELECT COUNT(*) FROM demandes_credit WHERE statut = 'soumise'");
    $demandes_credit_attente = $stmt->fetchColumn();
    
    // Crédits actifs
    $stmt = $pdo->query("SELECT COUNT(*) FROM credits_accordes WHERE statut = 'actif'");
    $credits_actifs = $stmt->fetchColumn();
    
    // Remboursements en retard
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM remboursements_credit 
        WHERE statut = 'retard' OR (statut = 'en_attente' AND date_echeance < CURDATE())
    ");
    $remboursements_retard = $stmt->fetchColumn();
    
    // Montants total des crédits par groupe
    $stmt = $pdo->query("
        SELECT g.nom_groupe, g.montant_max_credit, 
               COALESCE(SUM(ca.montant_accorde), 0) as credit_utilise
        FROM groupes g 
        LEFT JOIN credits_accordes ca ON g.id_groupe = ca.id_groupe AND ca.statut = 'actif'
        WHERE g.statut = 'actif'
        GROUP BY g.id_groupe
    ");
    $credits_groupes = $stmt->fetchAll();
    
    // Dernières activités
    $stmt = $pdo->query("
        SELECT la.*, u.username 
        FROM logs_activites la
        LEFT JOIN utilisateurs u ON la.utilisateur_id = u.id_utilisateur
        ORDER BY la.date_action DESC 
        LIMIT 10
    ");
    $dernieres_activites = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $investissements_attente = 0;
    $demandes_credit_attente = 0;
    $credits_actifs = 0;
    $remboursements_retard = 0;
    $groupes_stats = [];
    $credits_groupes = [];
    $dernieres_activites = [];
}

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'valider_investissement':
                $id_investissement = $_POST['id_investissement'];
                $commentaire = $_POST['commentaire'] ?? '';
                
                $stmt = $pdo->prepare("
                    UPDATE investissement 
                    SET statut = 'valide', valide_par = ?, date_validation = NOW(), commentaire = ?
                    WHERE id_investissement = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $commentaire, $id_investissement]);
                
                // Mettre à jour le compte coopérative
                $stmt = $pdo->prepare("
                    UPDATE compte_cooperative cc
                    INNER JOIN investissement i ON cc.id_membre = i.id_membre
                    SET cc.solde_disponible = cc.solde_disponible + i.montant,
                        cc.total_investi = cc.total_investi + i.montant,
                        cc.date_derniere_operation = NOW()
                    WHERE i.id_investissement = ?
                ");
                $stmt->execute([$id_investissement]);
                
                echo json_encode(['success' => true, 'message' => 'Investissement validé avec succès']);
                break;
                
            case 'rejeter_investissement':
                $id_investissement = $_POST['id_investissement'];
                $commentaire = $_POST['commentaire'] ?? '';
                
                $stmt = $pdo->prepare("
                    UPDATE investissement 
                    SET statut = 'rejete', valide_par = ?, date_validation = NOW(), commentaire = ?
                    WHERE id_investissement = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $commentaire, $id_investissement]);
                
                echo json_encode(['success' => true, 'message' => 'Investissement rejeté']);
                break;
                
            case 'approuver_credit':
                $id_demande = $_POST['id_demande'];
                $montant_approuve = $_POST['montant_approuve'];
                $duree_approuvee = $_POST['duree_approuvee'];
                $taux_applique = $_POST['taux_applique'];
                
                // Mettre à jour la demande
                $stmt = $pdo->prepare("
                    UPDATE demandes_credit 
                    SET statut = 'approuvee', evaluee_par = ?, date_evaluation = NOW(),
                        montant_approuve = ?, duree_approuvee = ?, taux_applique = ?
                    WHERE id_demande = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $montant_approuve, $duree_approuvee, $taux_applique, $id_demande]);
                
                echo json_encode(['success' => true, 'message' => 'Demande de crédit approuvée']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>Gestionnaire
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/membres.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Gestion Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/groupes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-layer-group me-2"></i>Gestion Groupes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/investissements.php" class="list-group-item list-group-item-action position-relative">
                        <i class="fas fa-money-bill-wave me-2"></i>Investissements
                        <?php if ($investissements_attente > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 end-0 me-2 mt-1"><?php echo $investissements_attente; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/demandes-credit.php" class="list-group-item list-group-item-action position-relative">
                        <i class="fas fa-hand-holding-usd me-2"></i>Demandes Crédit
                        <?php if ($demandes_credit_attente > 0): ?>
                        <span class="badge bg-warning position-absolute top-0 end-0 me-2 mt-1"><?php echo $demandes_credit_attente; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/credits-accordes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-coins me-2"></i>Crédits Accordés
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/remboursements.php" class="list-group-item list-group-item-action position-relative">
                        <i class="fas fa-calendar-check me-2"></i>Remboursements
                        <?php if ($remboursements_retard > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 end-0 me-2 mt-1"><?php echo $remboursements_retard; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/produits.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/projets.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Projets Sociaux
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête -->
            <div class="row mb-4">
                <div class="col">
                    <h2>Tableau de Bord Gestionnaire</h2>
                    <p class="text-muted">Gestion des membres, groupes, investissements et crédits</p>
                </div>
            </div>
            
            <!-- Cartes de statistiques principales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $investissements_attente; ?></h3>
                                    <p class="mb-0">Investissements à Valider</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/gestionnaire/investissements.php" class="text-white text-decoration-none">
                                <small>Traiter maintenant <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $demandes_credit_attente; ?></h3>
                                    <p class="mb-0">Demandes à Étudier</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/gestionnaire/demandes-credit.php" class="text-white text-decoration-none">
                                <small>Étudier demandes <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $credits_actifs; ?></h3>
                                    <p class="mb-0">Crédits Actifs</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-hand-holding-usd fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/gestionnaire/credits-accordes.php" class="text-white text-decoration-none">
                                <small>Voir détails <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $remboursements_retard; ?></h3>
                                    <p class="mb-0">Retards de Paiement</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/gestionnaire/remboursements.php" class="text-white text-decoration-none">
                                <small>Actions requises <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- État des groupes -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">État des Crédits par Groupe</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshGroupeStats()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <?php foreach ($credits_groupes as $groupe): 
                                $pourcentage = $groupe['montant_max_credit'] > 0 ? 
                                             ($groupe['credit_utilise'] / $groupe['montant_max_credit']) * 100 : 0;
                                $classe_progress = $pourcentage > 80 ? 'bg-danger' : ($pourcentage > 60 ? 'bg-warning' : 'bg-success');
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($groupe['nom_groupe']); ?></strong>
                                    <span class="text-muted">
                                        <?php echo number_format($groupe['credit_utilise'], 0, ',', ' '); ?> / 
                                        <?php echo number_format($groupe['montant_max_credit'], 0, ',', ' '); ?> BIF
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?php echo $classe_progress; ?>" 
                                         style="width: <?php echo $pourcentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo number_format($pourcentage, 1); ?>% utilisé</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Actions rapides sur les investissements -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Investissements en Attente de Validation</h5>
                        </div>
                        <div class="card-body" id="investissements-container">
                            <?php
                            $stmt = $pdo->query("
                                SELECT i.*, m.nom, m.prenom, m.code_membre 
                                FROM investissement i
                                JOIN membres m ON i.id_membre = m.id_membre
                                WHERE i.statut = 'en_attente'
                                ORDER BY i.date_creation DESC
                                LIMIT 5
                            ");
                            $investissements = $stmt->fetchAll();
                            ?>
                            
                            <?php if (empty($investissements)): ?>
                                <p class="text-muted">Aucun investissement en attente</p>
                            <?php else: ?>
                                <?php foreach ($investissements as $inv): ?>
                                <div class="border rounded p-3 mb-2">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6><?php echo htmlspecialchars($inv['nom'] . ' ' . $inv['prenom']); ?></h6>
                                            <p class="mb-1">
                                                <strong><?php echo number_format($inv['montant'], 0, ',', ' '); ?> BIF</strong> - 
                                                <?php echo htmlspecialchars($inv['type_investissement']); ?>
                                            </p>
                                            <small class="text-muted">
                                                Demandé le <?php echo date('d/m/Y', strtotime($inv['date_investissement'])); ?>
                                            </small>
                                            <?php if ($inv['preuve_paiement']): ?>
                                                <br><a href="<?php echo SITE_URL; ?>/uploads/preuves/<?php echo $inv['preuve_paiement']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-outline-info mt-1">
                                                    <i class="fas fa-file"></i> Voir preuve
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-sm btn-success me-1" 
                                                    onclick="validerInvestissement(<?php echo $inv['id_investissement']; ?>)">
                                                <i class="fas fa-check"></i> Valider
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="rejeterInvestissement(<?php echo $inv['id_investissement']; ?>)">
                                                <i class="fas fa-times"></i> Rejeter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar droite -->
                <div class="col-md-4">
                    <!-- Actions rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions Rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/gestionnaire/membres.php?action=add" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user-plus me-2"></i>Ajouter un membre
                                </a>
                                <a href="<?php echo SITE_URL; ?>/gestionnaire/groupes.php?action=add" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-2"></i>Créer un groupe
                                </a>
                                <a href="<?php echo SITE_URL; ?>/gestionnaire/credits-accordes.php?action=add" class="btn btn-info btn-sm">
                                    <i class="fas fa-hand-holding-usd me-2"></i>Accorder un crédit
                                </a>
                                <a href="<?php echo SITE_URL; ?>/gestionnaire/remboursements.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-search me-2"></i>Suivre remboursements
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques groupes -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Répartition des Membres</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($groupes_stats as $stat): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($stat['nom_groupe']); ?></span>
                                <span class="badge bg-primary"><?php echo $stat['nb_membres']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Dernières activités -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Activités Récentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-simple">
                                <?php foreach (array_slice($dernieres_activites, 0, 5) as $activite): ?>
                                <div class="timeline-item-simple mb-3">
                                    <div class="timeline-content-simple">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activite['action']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?php echo htmlspecialchars($activite['table_concernee']); ?>
                                            <?php if ($activite['details']): ?>
                                                - <?php echo htmlspecialchars(substr($activite['details'], 0, 50)); ?>...
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($activite['date_action'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour validation investissements -->
<div class="modal fade" id="modalValidationInvestissement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validation Investissement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formValidationInvestissement">
                <div class="modal-body">
                    <input type="hidden" id="investissement_id" name="id_investissement">
                    <input type="hidden" id="action_type" name="action">
                    
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-confirmer">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.timeline-simple {
    position: relative;
}

.timeline-item-simple {
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
    position: relative;
}

.timeline-item-simple::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
}

.progress {
    border-radius: 10px;
}

.badge {
    font-size: 0.7em;
}
</style>

<script>
function validerInvestissement(id) {
    document.getElementById('investissement_id').value = id;
    document.getElementById('action_type').value = 'valider_investissement';
    document.getElementById('btn-confirmer').className = 'btn btn-success';
    document.getElementById('btn-confirmer').innerHTML = '<i class="fas fa-check"></i> Valider';
    
    const modal = new bootstrap.Modal(document.getElementById('modalValidationInvestissement'));
    modal.show();
}

function rejeterInvestissement(id) {
    document.getElementById('investissement_id').value = id;
    document.getElementById('action_type').value = 'rejeter_investissement';
    document.getElementById('btn-confirmer').className = 'btn btn-danger';
    document.getElementById('btn-confirmer').innerHTML = '<i class="fas fa-times"></i> Rejeter';
    
    const modal = new bootstrap.Modal(document.getElementById('modalValidationInvestissement'));
    modal.show();
}

document.getElementById('formValidationInvestissement').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalValidationInvestissement')).hide();
            
            // Afficher message de succès
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').prepend(alert);
            
            // Recharger la section des investissements
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du traitement');
    });
});

function refreshGroupeStats() {
    location.reload();
}
</script>

<?php require_once '../includes/footer.php'; ?>