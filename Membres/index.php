<?php
$page_title = "Espace Membre";
include '../config/config.php';
include '../includes/functions.php';
requireRole('membre');
require_once '../includes/header.php';

// Récupération des informations du membre connecté
$membre_id = $_SESSION['user_id'];

// Statistiques du membre
try {
    $pdo = getConnection();
    
    // Informations du compte coopérative
    $stmt = $pdo->prepare("
        SELECT cc.*, m.nom, m.prenom, m.code_membre, m.type_membre, m.statut as statut_membre
        FROM compte_cooperative cc 
        JOIN membres m ON cc.id_membre = m.id_membre 
        WHERE cc.id_membre = ?
    ");
    $stmt->execute([$membre_id]);
    $compte_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques des investissements
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM investissement WHERE id_membre = ? AND statut = 'valide'");
    $stmt->execute([$membre_id]);
    $total_investissements_valides = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM investissement WHERE id_membre = ? AND statut = 'en_attente'");
    $stmt->execute([$membre_id]);
    $investissements_en_attente = $stmt->fetchColumn();
    
    // Crédits actifs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM credits_accordes WHERE id_membre = ? AND statut = 'actif'");
    $stmt->execute([$membre_id]);
    $credits_actifs = $stmt->fetchColumn();
    
    // Montant total des crédits actifs
    $stmt = $pdo->prepare("
        SELECT SUM(montant_accorde) as total_credit 
        FROM credits_accordes 
        WHERE id_membre = ? AND statut = 'actif'
    ");
    $stmt->execute([$membre_id]);
    $montant_total_credit = $stmt->fetchColumn() ?: 0;
    
    // Prochaine échéance
    $stmt = $pdo->prepare("
        SELECT MIN(rc.date_echeance) as prochaine_echeance, rc.montant_total
        FROM remboursements_credit rc
        JOIN credits_accordes ca ON rc.id_credit = ca.id_credit
        WHERE ca.id_membre = ? AND rc.statut = 'en_attente' AND rc.date_echeance >= CURDATE()
        ORDER BY rc.date_echeance ASC
        LIMIT 1
    ");
    $stmt->execute([$membre_id]);
    $prochaine_echeance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Informations du groupe
    $stmt = $pdo->prepare("
        SELECT g.*, mg.role, mg.date_adhesion
        FROM groupes g
        JOIN membres_groupes mg ON g.id_groupe = mg.id_groupe
        WHERE mg.id_membre = ? AND mg.statut = 'actif'
    ");
    $stmt->execute([$membre_id]);
    $groupe_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Demandes de crédit récentes
    $stmt = $pdo->prepare("
        SELECT dc.*, tc.nom_type, tc.taux_interet
        FROM demandes_credit dc
        LEFT JOIN types_credit tc ON dc.id_type_credit = tc.id_type_credit
        WHERE dc.id_membre = ?
        ORDER BY dc.date_creation DESC
        LIMIT 5
    ");
    $stmt->execute([$membre_id]);
    $demandes_credit = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Investissements récents
    $stmt = $pdo->prepare("
        SELECT i.*, u.username as validateur
        FROM investissement i
        LEFT JOIN utilisateurs u ON i.valide_par = u.id_utilisateur
        WHERE i.id_membre = ?
        ORDER BY i.date_creation DESC
        LIMIT 5
    ");
    $stmt->execute([$membre_id]);
    $investissements_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Projets auxquels le membre participe
    $stmt = $pdo->prepare("
        SELECT ps.*, bp.statut_participation, bp.date_inscription
        FROM projets_sociaux ps
        JOIN beneficiaires_projets bp ON ps.id_projet = bp.id_projet
        WHERE bp.id_membre = ?
        ORDER BY bp.date_inscription DESC
    ");
    $stmt->execute([$membre_id]);
    $projets_participation = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $compte_info = [];
    $total_investissements_valides = 0;
    $investissements_en_attente = 0;
    $credits_actifs = 0;
    $montant_total_credit = 0;
    $prochaine_echeance = null;
    $groupe_info = null;
    $demandes_credit = [];
    $investissements_recents = [];
    $projets_participation = [];
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'nouveau_investissement':
                try {
                    $montant = floatval($_POST['montant']);
                    $type_investissement = $_POST['type_investissement'];
                    $preuve_paiement = '';
                    
                    // Gestion de l'upload de fichier
                    if (isset($_FILES['preuve_paiement']) && $_FILES['preuve_paiement']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../uploads/preuves_paiement/';
                        $filename = time() . '_' . $_FILES['preuve_paiement']['name'];
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['preuve_paiement']['tmp_name'], $upload_path)) {
                            $preuve_paiement = $filename;
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO investissement (id_membre, montant, type_investissement, date_investissement, preuve_paiement)
                        VALUES (?, ?, ?, CURDATE(), ?)
                    ");
                    $stmt->execute([$membre_id, $montant, $type_investissement, $preuve_paiement]);
                    
                    $_SESSION['success'] = "Investissement soumis avec succès. En attente de validation.";
                    
                } catch(PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la soumission de l'investissement.";
                }
                break;
                
            case 'demande_credit':
                try {
                    $id_type_credit = intval($_POST['id_type_credit']);
                    $montant_demande = floatval($_POST['montant_demande']);
                    $duree_mois = intval($_POST['duree_mois']);
                    $motif = $_POST['motif'];
                    $garanties = $_POST['garanties_proposees'];
                    
                    // Génération du numéro de demande
                    $numero_demande = 'DEM-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    
                    $groupe_id = $groupe_info ? $groupe_info['id_groupe'] : null;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO demandes_credit (numero_demande, id_membre, id_groupe, id_type_credit, montant_demande, duree_mois, motif, garanties_proposees, date_demande)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                    ");
                    $stmt->execute([$numero_demande, $membre_id, $groupe_id, $id_type_credit, $montant_demande, $duree_mois, $motif, $garanties]);
                    
                    $_SESSION['success'] = "Demande de crédit soumise avec succès. Numéro: " . $numero_demande;
                    
                } catch(PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la soumission de la demande de crédit.";
                }
                break;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Récupération des types de crédit pour le formulaire
try {
    $stmt = $pdo->query("SELECT * FROM types_credit WHERE statut = 'actif'");
    $types_credit = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $types_credit = [];
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>Espace Membre
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/Membres/" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="#" onclick="showSection('compte')" class="list-group-item list-group-item-action">
                        <i class="fas fa-wallet me-2"></i>Mon Compte
                    </a>
                    <a href="#" onclick="showSection('investissement')" class="list-group-item list-group-item-action">
                        <i class="fas fa-coins me-2"></i>Investissements
                    </a>
                    <a href="#" onclick="showSection('credit')" class="list-group-item list-group-item-action">
                        <i class="fas fa-money-bill-wave me-2"></i>Demande de Crédit
                    </a>
                    <a href="#" onclick="showSection('groupe')" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Mon Groupe
                    </a>
                    <a href="#" onclick="showSection('remboursement')" class="list-group-item list-group-item-action">
                        <i class="fas fa-hand-holding-usd me-2"></i>Remboursements
                    </a>
                    <a href="#" onclick="showSection('projets')" class="list-group-item list-group-item-action">
                        <i class="fas fa-project-diagram me-2"></i>Projets Sociaux
                    </a>
                    <a href="<?php echo SITE_URL; ?>/formations/" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i>Formations
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête -->
            <div class="row mb-4">
                <div class="col">
                    <h2>Tableau de Bord Membre</h2>
                    <p class="text-muted">
                        Bienvenue, <strong><?php echo htmlspecialchars($compte_info['prenom'] ?? '') . ' ' . htmlspecialchars($compte_info['nom'] ?? ''); ?></strong>
                        <?php if ($compte_info): ?>
                        - Code: <span class="badge bg-primary"><?php echo htmlspecialchars($compte_info['code_membre']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Section Tableau de Bord -->
            <div id="dashboard" class="section">
                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo number_format($compte_info['solde_disponible'] ?? 0, 0, ',', ' '); ?></h3>
                                        <p class="mb-0">BIF Disponible</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-wallet fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo number_format($compte_info['total_investi'] ?? 0, 0, ',', ' '); ?></h3>
                                        <p class="mb-0">Total Investi</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-coins fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo number_format($montant_total_credit, 0, ',', ' '); ?></h3>
                                        <p class="mb-0">Crédit Actif</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $prochaine_echeance ? number_format($prochaine_echeance['montant_total'], 0, ',', ' ') : '0'; ?></h3>
                                        <p class="mb-0">Prochaine Échéance</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <?php if ($prochaine_echeance): ?>
                            <div class="card-footer">
                                <small>Date: <?php echo date('d/m/Y', strtotime($prochaine_echeance['prochaine_echeance'])); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides et activité récente -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Actions Rapides</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-sm" onclick="showSection('investissement')">
                                        <i class="fas fa-plus me-2"></i>Nouveau Investissement
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="showSection('credit')">
                                        <i class="fas fa-money-bill me-2"></i>Demander un Crédit
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="showSection('compte')">
                                        <i class="fas fa-eye me-2"></i>Consulter mon Compte
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="showSection('groupe')">
                                        <i class="fas fa-users me-2"></i>Voir mon Groupe
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations du groupe -->
                        <?php if ($groupe_info): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Mon Groupe</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-primary"><?php echo htmlspecialchars($groupe_info['nom_groupe']); ?></h6>
                                <p class="small text-muted"><?php echo htmlspecialchars($groupe_info['description']); ?></p>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h5 class="text-info"><?php echo $groupe_info['nombre_membres_actuel']; ?>/<?php echo $groupe_info['nombre_max_membres']; ?></h5>
                                        <small>Membres</small>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="text-success"><?php echo ucfirst($groupe_info['role']); ?></h5>
                                        <small>Mon Rôle</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Activité Récente</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach (array_slice($investissements_recents, 0, 3) as $inv): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker <?php echo $inv['statut'] == 'valide' ? 'bg-success' : ($inv['statut'] == 'en_attente' ? 'bg-warning' : 'bg-danger'); ?>"></div>
                                        <div class="timeline-content">
                                            <h6>Investissement <?php echo ucfirst($inv['statut']); ?></h6>
                                            <p class="text-muted mb-1">
                                                <?php echo number_format($inv['montant'], 0, ',', ' '); ?> BIF 
                                                - <?php echo htmlspecialchars($inv['type_investissement']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($inv['date_creation'])); ?>
                                                <?php if ($inv['statut'] == 'valide' && $inv['validateur']): ?>
                                                - Validé par <?php echo htmlspecialchars($inv['validateur']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach (array_slice($demandes_credit, 0, 2) as $demande): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker <?php echo $demande['statut'] == 'approuvee' ? 'bg-success' : ($demande['statut'] == 'en_etude' ? 'bg-info' : 'bg-warning'); ?>"></div>
                                        <div class="timeline-content">
                                            <h6>Demande de Crédit <?php echo ucfirst(str_replace('_', ' ', $demande['statut'])); ?></h6>
                                            <p class="text-muted mb-1">
                                                <?php echo $demande['numero_demande']; ?> - 
                                                <?php echo number_format($demande['montant_demande'], 0, ',', ' '); ?> BIF
                                            </p>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($demande['date_creation'])); ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Investissement -->
            <div id="investissement" class="section" style="display: none;">
                <h3 class="mb-4">Gestion des Investissements</h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Nouveau Investissement</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="nouveau_investissement">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Montant (BIF) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="montant" required min="1000">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type d'investissement <span class="text-danger">*</span></label>
                                        <select class="form-select" name="type_investissement" required>
                                            <option value="">Choisissez le type</option>
                                            <option value="cotisation_mensuelle">Cotisation mensuelle</option>
                                            <option value="epargne_volontaire">Épargne volontaire</option>
                                            <option value="parts_sociales">Parts sociales</option>
                                            <option value="investissement_projet">Investissement projet</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Preuve de paiement</label>
                                        <input type="file" class="form-control" name="preuve_paiement" accept="image/*,application/pdf">
                                        <small class="text-muted">Formats acceptés: JPG, PNG, PDF</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Soumettre l'Investissement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Historique des Investissements</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($investissements_recents as $inv): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($inv['date_investissement'])); ?></td>
                                                <td><?php echo number_format($inv['montant'], 0, ',', ' '); ?> BIF</td>
                                                <td><?php echo htmlspecialchars($inv['type_investissement']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = $inv['statut'] == 'valide' ? 'success' : ($inv['statut'] == 'en_attente' ? 'warning' : 'danger');
                                                    $status_text = $inv['statut'] == 'valide' ? 'Validé' : ($inv['statut'] == 'en_attente' ? 'En attente' : 'Rejeté');
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($inv['preuve_paiement']): ?>
                                                    <a href="../uploads/preuves_paiement/<?php echo $inv['preuve_paiement']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Demande de Crédit -->
            <div id="credit" class="section" style="display: none;">
                <h3 class="mb-4">Demandes de Crédit</h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Nouvelle Demande</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="demande_credit">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type de crédit <span class="text-danger">*</span></label>
                                        <select class="form-select" name="id_type_credit" required onchange="updateCreditInfo(this)">
                                            <option value="">Choisissez le type</option>
                                            <?php foreach ($types_credit as $type): ?>
                                            <option value="<?php echo $type['id_type_credit']; ?>" 
                                                    data-taux="<?php echo $type['taux_interet']; ?>"
                                                    data-max="<?php echo $type['montant_max']; ?>"
                                                    data-min="<?php echo $type['montant_min']; ?>">
                                                <?php echo htmlspecialchars($type['nom_type']); ?> 
                                                (<?php echo $type['taux_interet']; ?>%)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Montant demandé (BIF) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="montant_demande" required id="montant_demande">
                                        <small class="text-muted" id="montant_info"></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Durée (mois) <span class="text-danger">*</span></label>
                                        <select class="form-select" name="duree_mois" required>
                                            <option value="">Choisissez la durée</option>
                                            <option value="3">3 mois</option>
                                            <option value="6">6 mois</option>
                                            <option value="12">12 mois</option>
                                            <option value="18">18 mois</option>
                                            <option value="24">24 mois</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Motif de la demande <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="motif" rows="3" required 
                                                  placeholder="Expliquez l'usage prévu du crédit..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Garanties proposées</label>
                                        <textarea class="form-control" name="garanties_proposees" rows="2" 
                                                  placeholder="Décrivez les garanties que vous proposez..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Soumettre la Demande
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Mes Demandes de Crédit</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>N° Demande</th>
                                                <th>Montant</th>
                                                <th>Durée</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($demandes_credit as $demande): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($demande['numero_demande']); ?></td>
                                                <td><?php echo number_format($demande['montant_demande'], 0, ',', ' '); ?> BIF</td>
                                                <td><?php echo $demande['duree_mois']; ?> mois</td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'soumise' => 'secondary',
                                                        'en_etude' => 'info',
                                                        'approuvee' => 'success',
                                                        'rejetee' => 'danger',
                                                        'annulee' => 'warning'
                                                    ];
                                                    $status_text = [
                                                        'soumise' => 'Soumise',
                                                        'en_etude' => 'En étude',
                                                        'approuvee' => 'Approuvée',
                                                        'rejetee' => 'Rejetée',
                                                        'annulee' => 'Annulée'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class[$demande['statut']]; ?>">
                                                        <?php echo $status_text[$demande['statut']]; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($demande['date_creation'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="voirDetailsDemande(<?php echo $demande['id_demande']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Compte -->
            <div id="compte" class="section" style="display: none;">
                <h3 class="mb-4">Consultation de Mon Compte</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Informations Personnelles</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($compte_info): ?>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Code Membre:</strong></div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($compte_info['code_membre']); ?></div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Nom Complet:</strong></div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($compte_info['prenom'] . ' ' . $compte_info['nom']); ?></div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Type:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-info"><?php echo ucfirst($compte_info['type_membre']); ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Statut:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-success"><?php echo ucfirst($compte_info['statut_membre']); ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Date d'ouverture:</strong></div>
                                    <div class="col-sm-8"><?php echo date('d/m/Y', strtotime($compte_info['date_ouverture'])); ?></div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Aucune information de compte disponible.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Résumé Financier</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($compte_info): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Solde Disponible:</span>
                                        <strong class="text-success"><?php echo number_format($compte_info['solde_disponible'], 0, ',', ' '); ?> BIF</strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Total Investi:</span>
                                        <strong class="text-info"><?php echo number_format($compte_info['total_investi'], 0, ',', ' '); ?> BIF</strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Crédit Actif:</span>
                                        <strong class="text-warning"><?php echo number_format($montant_total_credit, 0, ',', ' '); ?> BIF</strong>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Investissements Validés:</span>
                                        <span class="badge bg-success"><?php echo $total_investissements_valides; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>En Attente:</span>
                                        <span class="badge bg-warning"><?php echo $investissements_en_attente; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Crédits Actifs:</span>
                                        <span class="badge bg-info"><?php echo $credits_actifs; ?></span>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Aucune information financière disponible.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Dernières Activités</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($compte_info['date_derniere_operation']): ?>
                                <p><strong>Dernière Opération:</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($compte_info['date_derniere_operation'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo $compte_info ? min(100, ($compte_info['total_investi'] / 1000000) * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">Progression vers 1M BIF d'investissement</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Groupe -->
            <div id="groupe" class="section" style="display: none;">
                <h3 class="mb-4">Mon Groupe</h3>
                
                <?php if ($groupe_info): ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Informations du Groupe</h5>
                            </div>
                            <div class="card-body">
                                <h4 class="text-primary"><?php echo htmlspecialchars($groupe_info['nom_groupe']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($groupe_info['description']); ?></p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Membres:</span>
                                        <strong><?php echo $groupe_info['nombre_membres_actuel']; ?>/<?php echo $groupe_info['nombre_max_membres']; ?></strong>
                                    </div>
                                    <div class="progress mt-1">
                                        <div class="progress-bar" 
                                             style="width: <?php echo ($groupe_info['nombre_membres_actuel'] / $groupe_info['nombre_max_membres']) * 100; ?>%">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Crédit Max:</span>
                                        <strong><?php echo number_format($groupe_info['montant_max_credit'], 0, ',', ' '); ?> BIF</strong>
                                    </div>
                                </div>
                                
                                <hr>
                                <p><strong>Mon Rôle:</strong> 
                                    <span class="badge bg-primary"><?php echo ucfirst($groupe_info['role']); ?></span>
                                </p>
                                <p><strong>Date d'adhésion:</strong> 
                                    <?php echo date('d/m/Y', strtotime($groupe_info['date_adhesion'])); ?>
                                </p>
                                <p><strong>Statut:</strong> 
                                    <span class="badge bg-success"><?php echo ucfirst($groupe_info['statut']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Membres de l'Équipe</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT m.nom, m.prenom, m.type_membre, mg.role, mg.statut, mg.date_adhesion
                                        FROM membres m
                                        JOIN membres_groupes mg ON m.id_membre = mg.id_membre
                                        WHERE mg.id_groupe = ? AND mg.statut = 'actif'
                                        ORDER BY 
                                            CASE mg.role 
                                                WHEN 'responsable' THEN 1 
                                                WHEN 'tresorier' THEN 2 
                                                ELSE 3 
                                            END, m.nom
                                    ");
                                    $stmt->execute([$groupe_info['id_groupe']]);
                                    $membres_groupe = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch(PDOException $e) {
                                    $membres_groupe = [];
                                }
                                ?>
                                
                                <div class="row">
                                    <?php foreach ($membres_groupe as $membre): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-2 border rounded">
                                            <div class="avatar bg-<?php 
                                                echo $membre['role'] == 'responsable' ? 'primary' : 
                                                     ($membre['role'] == 'tresorier' ? 'info' : 'secondary'); 
                                            ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($membre['role']); ?> - 
                                                    <?php echo ucfirst($membre['type_membre']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success">Actif</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Aucun Groupe</h5>
                    <p>Vous n'êtes actuellement membre d'aucun groupe. Contactez l'administrateur pour être ajouté à un groupe.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Section Remboursements -->
            <div id="remboursement" class="section" style="display: none;">
                <h3 class="mb-4">Remboursements de Crédit</h3>
                
                <?php
                // Récupération des remboursements
                try {
                    $stmt = $pdo->prepare("
                        SELECT rc.*, ca.numero_credit, ca.montant_accorde, ca.taux_interet
                        FROM remboursements_credit rc
                        JOIN credits_accordes ca ON rc.id_credit = ca.id_credit
                        WHERE ca.id_membre = ?
                        ORDER BY rc.date_echeance DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$membre_id]);
                    $remboursements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch(PDOException $e) {
                    $remboursements = [];
                }
                ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Échéancier de Remboursement</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($remboursements) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>N° Crédit</th>
                                                <th>Date Échéance</th>
                                                <th>Montant Capital</th>
                                                <th>Intérêts</th>
                                                <th>Total</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($remboursements as $remb): ?>
                                            <tr class="<?php echo $remb['statut'] == 'retard' ? 'table-danger' : ''; ?>">
                                                <td><?php echo htmlspecialchars($remb['numero_credit']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($remb['date_echeance'])); ?></td>
                                                <td><?php echo number_format($remb['montant_capital'], 0, ',', ' '); ?> BIF</td>
                                                <td><?php echo number_format($remb['montant_interet'], 0, ',', ' '); ?> BIF</td>
                                                <td><strong><?php echo number_format($remb['montant_total'], 0, ',', ' '); ?> BIF</strong></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'en_attente' => 'warning',
                                                        'paye' => 'success',
                                                        'retard' => 'danger',
                                                        'partiel' => 'info'
                                                    ];
                                                    $status_text = [
                                                        'en_attente' => 'En attente',
                                                        'paye' => 'Payé',
                                                        'retard' => 'En retard',
                                                        'partiel' => 'Partiel'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class[$remb['statut']]; ?>">
                                                        <?php echo $status_text[$remb['statut']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($remb['statut'] == 'en_attente' || $remb['statut'] == 'partiel'): ?>
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="effectuerRemboursement(<?php echo $remb['id_remboursement']; ?>)">
                                                        <i class="fas fa-money-bill"></i> Payer
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun remboursement en cours.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Projets -->
            <div id="projets" class="section" style="display: none;">
                <h3 class="mb-4">Projets de la Coopérative</h3>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Mes Participations aux Projets</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($projets_participation) > 0): ?>
                                <?php foreach ($projets_participation as $projet): ?>
                                <div class="card mb-3 border-left-success">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="card-title"><?php echo htmlspecialchars($projet['nom_projet']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($projet['description'], 0, 150)); ?>...</p>
                                                <p class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($projet['date_debut'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($projet['date_fin'])); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <?php
                                                    $status_class = [
                                                        'inscrit' => 'info',
                                                        'actif' => 'success',
                                                        'termine' => 'secondary',
                                                        'abandonne' => 'danger'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class[$projet['statut_participation']]; ?> mb-2">
                                                        <?php echo ucfirst($projet['statut_participation']); ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">
                                                        Inscrit le <?php echo date('d/m/Y', strtotime($projet['date_inscription'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Vous ne participez actuellement à aucun projet.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Projets Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Récupération des projets disponibles
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT ps.* 
                                        FROM projets_sociaux ps
                                        WHERE ps.statut IN ('planifie', 'en_cours')
                                        AND ps.id_projet NOT IN (
                                            SELECT bp.id_projet 
                                            FROM beneficiaires_projets bp 
                                            WHERE bp.id_membre = ?
                                        )
                                        ORDER BY ps.date_creation DESC
                                        LIMIT 3
                                    ");
                                    $stmt->execute([$membre_id]);
                                    $projets_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch(PDOException $e) {
                                    $projets_disponibles = [];
                                }
                                ?>
                                
                                <?php foreach ($projets_disponibles as $projet): ?>
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <h6 class="card-title"><?php echo htmlspecialchars($projet['nom_projet']); ?></h6>
                                        <p class="card-text small"><?php echo htmlspecialchars(substr($projet['objectif'], 0, 100)); ?>...</p>
                                        <div class="d-grid">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="participerProjet(<?php echo $projet['id_projet']; ?>)">
                                                <i class="fas fa-hand-paper me-1"></i>Participer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($projets_disponibles) == 0): ?>
                                <p class="text-muted text-center">Aucun projet disponible actuellement.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour les actions -->
<!-- Modal Détails Demande -->
<div class="modal fade" id="detailsDemandeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Détails de la Demande</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsDemandeContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Remboursement -->
<div class="modal fade" id="remboursementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Effectuer un Remboursement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="remboursementForm">
                    <div class="mb-3">
                        <label class="form-label">Méthode de paiement</label>
                        <select class="form-select" name="methode_paiement" required>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="espece">Espèce</option>
                            <option value="virement">Virement bancaire</option>
                            <option value="cheque">Chèque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Référence de paiement</label>
                        <input type="text" class="form-control" name="reference_paiement" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant à payer</label>
                        <input type="number" class="form-control" name="montant_paye" required readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="confirmerRemboursement()">Confirmer le Paiement</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.border-left-success {
    border-left: 4px solid #28a745 !important;
}

.section {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
// Variables globales
let currentRemboursementId = null;

// Fonction pour afficher les sections
function showSection(sectionName) {
    // Masquer toutes les sections
    document.querySelectorAll('.section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Afficher la section demandée
    const targetSection = document.getElementById(sectionName);
    if (targetSection) {
        targetSection.style.display = 'block';
    }
    
    // Mettre à jour la navigation
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Activer l'élément de navigation correspondant
    const navLink = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
    if (navLink) {
        navLink.classList.add('active');
    }
}

// Fonction pour mettre à jour les informations de crédit
function updateCreditInfo(select) {
    const option = select.options[select.selectedIndex];
    const montantInput = document.getElementById('montant_demande');
    const montantInfo = document.getElementById('montant_info');
    
    if (option.value) {
        const taux = option.dataset.taux;
        const min = parseFloat(option.dataset.min);
        const max = parseFloat(option.dataset.max);
        
        montantInput.min = min;
        montantInput.max = max;
        
        montantInfo.innerHTML = `Montant: ${min.toLocaleString()} - ${max.toLocaleString()} BIF (Taux: ${taux}%)`;
    } else {
        montantInfo.innerHTML = '';
        montantInput.removeAttribute('min');
        montantInput.removeAttribute('max');
    }
}

// Fonction pour voir les détails d'une demande
function voirDetailsDemande(idDemande) {
    // Simuler le chargement des détails
    fetch(`<?php echo SITE_URL; ?>/api/demande_details.php?id=${idDemande}`)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('detailsDemandeContent');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Numéro:</strong> ${data.numero_demande}</p>
                        <p><strong>Montant demandé:</strong> ${data.montant_demande.toLocaleString()} BIF</p>
                        <p><strong>Durée:</strong> ${data.duree_mois} mois</p>
                        <p><strong>Statut:</strong> <span class="badge bg-info">${data.statut}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date de demande:</strong> ${data.date_demande}</p>
                        <p><strong>Type de crédit:</strong> ${data.type_credit}</p>
                        <p><strong>Taux d'intérêt:</strong> ${data.taux_interet}%</p>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <strong>Motif:</strong>
                    <p class="mt-2">${data.motif}</p>
                </div>
                <div class="mb-3">
                    <strong>Garanties proposées:</strong>
                    <p class="mt-2">${data.garanties || 'Aucune garantie spécifiée'}</p>
                </div>
                ${data.commentaire_evaluation ? `
                <div class="mb-3">
                    <strong>Commentaire d'évaluation:</strong>
                    <p class="mt-2">${data.commentaire_evaluation}</p>
                </div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('detailsDemandeModal')).show();
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails');
        });
}

// Fonction pour effectuer un remboursement
function effectuerRemboursement(idRemboursement) {
    currentRemboursementId = idRemboursement;
    
    // Charger les détails du remboursement
    fetch(`<?php echo SITE_URL; ?>/api/remboursement_details.php?id=${idRemboursement}`)
        .then(response => response.json())
        .then(data => {
            document.querySelector('[name="montant_paye"]').value = data.montant_total;
            new bootstrap.Modal(document.getElementById('remboursementModal')).show();
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails du remboursement');
        });
}

// Fonction pour confirmer le remboursement
function confirmerRemboursement() {
    const form = document.getElementById('remboursementForm');
    const formData = new FormData(form);
    formData.append('id_remboursement', currentRemboursementId);
    formData.append('action', 'confirmer_remboursement');
    
    fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('remboursementModal')).hide();
            location.reload(); // Recharger la page pour voir les changements
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la confirmation du remboursement');
    });
}

// Fonction pour participer à un projet
function participerProjet(idProjet) {
    if (confirm('Êtes-vous sûr de vouloir participer à ce projet ?')) {
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=participer_projet&id_projet=${idProjet}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Inscription réussie ! Vous participez maintenant au projet.');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'inscription au projet');
        });
    }
}

// Gestion des formulaires
document.addEventListener('DOMContentLoaded', function() {
    // Formulaire d'investissement
    const investForm = document.getElementById('investissement-form');
    if (investForm) {
        investForm.addEventListener('submit', function(e) {
            const montant = parseFloat(document.querySelector('[name="montant"]').value);
            const type = document.querySelector('[name="type_investissement"]').value;
            
            if (montant < 1000) {
                e.preventDefault();
                alert('Le montant minimum est de 1,000 BIF');
                return;
            }
            
            if (!type) {
                e.preventDefault();
                alert('Veuillez sélectionner le type d\'investissement');
                return;
            }
            
            // Confirmation avant soumission
            if (!confirm(`Confirmer l'investissement de ${montant.toLocaleString()} BIF ?`)) {
                e.preventDefault();
            }
        });
    }
    
    // Formulaire de demande de crédit
    const creditForm = document.getElementById('credit-form');
    if (creditForm) {
        creditForm.addEventListener('submit', function(e) {
            const typeSelect = document.querySelector('[name="id_type_credit"]');
            const montant = parseFloat(document.querySelector('[name="montant_demande"]').value);
            const motif = document.querySelector('[name="motif"]').value.trim();
            
            if (!typeSelect.value) {
                e.preventDefault();
                alert('Veuillez sélectionner le type de crédit');
                return;
            }
            
            const option = typeSelect.options[typeSelect.selectedIndex];
            const min = parseFloat(option.dataset.min);
            const max = parseFloat(option.dataset.max);
            
            if (montant < min || montant > max) {
                e.preventDefault();
                alert(`Le montant doit être entre ${min.toLocaleString()} et ${max.toLocaleString()} BIF`);
                return;
            }
            
            if (motif.length < 10) {
                e.preventDefault();
                alert('Le motif doit contenir au moins 10 caractères');
                return;
            }
            
            // Confirmation avant soumission
            if (!confirm(`Confirmer la demande de crédit de ${montant.toLocaleString()} BIF ?`)) {
                e.preventDefault();
            }
        });
    }
    
    // Auto-refresh des notifications (simulation)
    setInterval(function() {
        // Simuler la mise à jour des notifications
        const badge = document.querySelector('.badge');
        if (badge && Math.random() > 0.8) {
            let current = parseInt(badge.textContent);
            badge.textContent = current + Math.floor(Math.random() * 2);
        }
    }, 30000); // Chaque 30 secondes
    
    // Initialisation des tooltips Bootstrap si nécessaire
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Fonction utilitaire pour formater les nombres
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Fonction pour afficher des messages de succès/erreur
function showMessage(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.col-md-9.col-lg-10');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Validation côté client pour les fichiers
function validateFile(input) {
    const file = input.files[0];
    if (file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!allowedTypes.includes(file.type)) {
            alert('Type de fichier non autorisé. Utilisez JPG, PNG ou PDF.');
            input.value = '';
            return false;
        }
        
        if (file.size > maxSize) {
            alert('Le fichier est trop volumineux. Taille maximum: 2MB.');
            input.value = '';
            return false;
        }
    }
    return true;
}

// Ajouter la validation de fichier aux inputs de type file
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateFile(this);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>