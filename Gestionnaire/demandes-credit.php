<?php
$page_title = "Gestion des Demandes de Crédit";
include '../config/config.php';
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

$pdo = getConnection();
$message = '';
$error = '';

// Traitement des demandes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_demande = $_POST['id_demande'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1;
    
    if ($action === 'evaluer') {
        try {
            $pdo->beginTransaction();
            
            $statut_evaluation = $_POST['statut_evaluation'];
            $montant_approuve = $statut_evaluation === 'approuvee' ? $_POST['montant_approuve'] : null;
            $duree_approuvee = $statut_evaluation === 'approuvee' ? $_POST['duree_approuvee'] : null;
            $taux_applique = $statut_evaluation === 'approuvee' ? $_POST['taux_applique'] : null;
            
            // Mise à jour de la demande
            $stmt = $pdo->prepare("
                UPDATE demandes_credit 
                SET statut = ?, 
                    evaluee_par = ?, 
                    date_evaluation = NOW(),
                    commentaire_evaluation = ?,
                    montant_approuve = ?,
                    duree_approuvee = ?,
                    taux_applique = ?
                WHERE id_demande = ?
            ");
            
            $stmt->execute([
                $statut_evaluation,
                $user_id,
                $_POST['commentaire_evaluation'],
                $montant_approuve,
                $duree_approuvee,
                $taux_applique,
                $id_demande
            ]);
            
            // Si approuvée, créer le crédit accordé
            if ($statut_evaluation === 'approuvee') {
                // Récupérer les infos de la demande
                $stmt = $pdo->prepare("
                    SELECT dc.*, m.nom, m.prenom 
                    FROM demandes_credit dc 
                    JOIN membres m ON dc.id_membre = m.id_membre 
                    WHERE dc.id_demande = ?
                ");
                $stmt->execute([$id_demande]);
                $demande = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculer le montant total à rembourser
                $montant_total = $montant_approuve * (1 + ($taux_applique / 100));
                $montant_mensuel = $montant_total / $duree_approuvee;
                
                // Générer le numéro de crédit
                $stmt = $pdo->query("SELECT COUNT(*) FROM credits_accordes");
                $count = $stmt->fetchColumn() + 1;
                $numero_credit = 'CRD' . date('Y') . str_pad($count, 4, '0', STR_PAD_LEFT);
                
                // Insérer le crédit accordé
                $stmt = $pdo->prepare("
                    INSERT INTO credits_accordes (
                        numero_credit, id_demande, id_membre, id_groupe,
                        montant_accorde, taux_interet, duree_mois,
                        montant_total_a_rembourser, montant_mensuel,
                        date_debut, date_fin_prevue, accorde_par
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 
                             DATE_ADD(CURDATE(), INTERVAL ? MONTH), ?)
                ");
                
                $stmt->execute([
                    $numero_credit,
                    $id_demande,
                    $demande['id_membre'],
                    $demande['id_groupe'],
                    $montant_approuve,
                    $taux_applique,
                    $duree_approuvee,
                    $montant_total,
                    $montant_mensuel,
                    $duree_approuvee,
                    $user_id
                ]);
                
                $credit_id = $pdo->lastInsertId();
                
                // Créer les échéanciers de remboursement
                for ($i = 1; $i <= $duree_approuvee; $i++) {
                    $numero_remb = $numero_credit . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                    $date_echeance = date('Y-m-d', strtotime("+$i month"));
                    
                    $montant_capital = $montant_approuve / $duree_approuvee;
                    $montant_interet = $montant_mensuel - $montant_capital;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO remboursements_credit (
                            numero_remboursement, id_credit, montant_capital,
                            montant_interet, montant_total, date_echeance
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $numero_remb,
                        $credit_id,
                        $montant_capital,
                        $montant_interet,
                        $montant_mensuel,
                        $date_echeance
                    ]);
                }
                
                // Ajouter le montant au compte du membre
                $stmt = $pdo->prepare("
                    UPDATE compte_cooperative 
                    SET solde_disponible = solde_disponible + ?,
                        date_derniere_operation = NOW()
                    WHERE id_membre = ?
                ");
                $stmt->execute([$montant_approuve, $demande['id_membre']]);
                
                $message = "Demande approuvée et crédit accordé avec succès! Numéro: $numero_credit";
            } else {
                $message = "Demande évaluée avec succès.";
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'évaluation: " . $e->getMessage();
        }
    }
}

// Récupération des types de crédit
$types_credit = $pdo->query("
    SELECT * FROM types_credit WHERE statut = 'actif' ORDER BY nom_type
")->fetchAll(PDO::FETCH_ASSOC);

// Filtrage et pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$filter_statut = $_GET['statut'] ?? 'soumise';
$filter_groupe = $_GET['groupe'] ?? '';

$where_conditions = [];
$params = [];

if ($filter_statut) {
    $where_conditions[] = "dc.statut = ?";
    $params[] = $filter_statut;
}

if ($filter_groupe) {
    $where_conditions[] = "dc.id_groupe = ?";
    $params[] = $filter_groupe;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Comptage total
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM demandes_credit dc 
    JOIN membres m ON dc.id_membre = m.id_membre 
    $where_clause
");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Récupération des demandes - CORRECTION: Utiliser des placeholders séparés pour LIMIT et OFFSET
$stmt = $pdo->prepare("
    SELECT dc.*, m.nom, m.prenom, m.code_membre, m.telephone,
           g.nom_groupe, g.montant_max_credit,
           tc.nom_type as type_credit_nom, tc.taux_interet as taux_type,
           u.username as evaluateur,
           cc.solde_disponible
    FROM demandes_credit dc
    JOIN membres m ON dc.id_membre = m.id_membre
    LEFT JOIN groupes g ON dc.id_groupe = g.id_groupe
    LEFT JOIN types_credit tc ON dc.id_type_credit = tc.id_type_credit
    LEFT JOIN utilisateurs u ON dc.evaluee_par = u.id_utilisateur
    LEFT JOIN compte_cooperative cc ON m.id_membre = cc.id_membre
    $where_clause
    ORDER BY dc.date_creation DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN statut = 'soumise' THEN 1 END) as soumises,
        COUNT(CASE WHEN statut = 'en_etude' THEN 1 END) as en_etude,
        COUNT(CASE WHEN statut = 'approuvee' THEN 1 END) as approuvees,
        COUNT(CASE WHEN statut = 'rejetee' THEN 1 END) as rejetees,
        SUM(CASE WHEN statut = 'approuvee' THEN montant_approuve ELSE 0 END) as total_approuve
    FROM demandes_credit
")->fetch(PDO::FETCH_ASSOC);

// Groupes pour le filtre
$groupes = $pdo->query("
    SELECT id_groupe, nom_groupe 
    FROM groupes 
    WHERE statut = 'actif' 
    ORDER BY nom_groupe
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h4><?php echo $stats['soumises']; ?></h4>
                    <p class="mb-0">Soumises</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h4><?php echo $stats['en_etude']; ?></h4>
                    <p class="mb-0">En étude</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h4><?php echo $stats['approuvees']; ?></h4>
                    <p class="mb-0">Approuvées</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <h4><?php echo $stats['rejetees']; ?></h4>
                    <p class="mb-0">Rejetées</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['total_approuve'], 0, ',', ' '); ?> BIF</h4>
                    <p class="mb-0">Total Approuvé</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des demandes -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Demandes de Crédit (<?php echo $total; ?>)</h5>
            
            <!-- Filtres -->
            <div class="d-flex gap-2">
                <select onchange="window.location.href='?statut='+this.value+'&groupe=<?php echo $filter_groupe; ?>'" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Tous les statuts</option>
                    <option value="soumise" <?php echo $filter_statut === 'soumise' ? 'selected' : ''; ?>>Soumises (<?php echo $stats['soumises']; ?>)</option>
                    <option value="en_etude" <?php echo $filter_statut === 'en_etude' ? 'selected' : ''; ?>>En étude (<?php echo $stats['en_etude']; ?>)</option>
                    <option value="approuvee" <?php echo $filter_statut === 'approuvee' ? 'selected' : ''; ?>>Approuvées (<?php echo $stats['approuvees']; ?>)</option>
                    <option value="rejetee" <?php echo $filter_statut === 'rejetee' ? 'selected' : ''; ?>>Rejetées (<?php echo $stats['rejetees']; ?>)</option>
                </select>
                
                <select onchange="window.location.href='?statut=<?php echo $filter_statut; ?>&groupe='+this.value" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Tous les groupes</option>
                    <?php foreach ($groupes as $groupe): ?>
                        <option value="<?php echo $groupe['id_groupe']; ?>" <?php echo $filter_groupe == $groupe['id_groupe'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($groupe['nom_groupe']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>N° Demande</th>
                        <th>Membre</th>
                        <th>Groupe</th>
                        <th>Montant Demandé</th>
                        <th>Durée</th>
                        <th>Type Crédit</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandes as $demande): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($demande['numero_demande']); ?></strong>
                            <br><small class="text-muted"><?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($demande['code_membre']); ?></strong>
                            <br><?php echo htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']); ?>
                            <?php if ($demande['telephone']): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-phone fa-sm me-1"></i><?php echo htmlspecialchars($demande['telephone']); ?>
                                </small>
                            <?php endif; ?>
                            <?php if ($demande['solde_disponible'] !== null): ?>
                                <br><small class="text-info">
                                    Solde: <?php echo number_format($demande['solde_disponible'], 0, ',', ' '); ?> BIF
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($demande['nom_groupe']): ?>
                                <?php echo htmlspecialchars($demande['nom_groupe']); ?>
                                <br><small class="text-muted">
                                    Max: <?php echo number_format($demande['montant_max_credit'], 0, ',', ' '); ?> BIF
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Aucun groupe</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="text-primary"><?php echo number_format($demande['montant_demande'], 0, ',', ' '); ?> BIF</strong>
                            <?php if ($demande['montant_approuve'] && $demande['montant_approuve'] != $demande['montant_demande']): ?>
                                <br><small class="text-success">
                                    Approuvé: <?php echo number_format($demande['montant_approuve'], 0, ',', ' '); ?> BIF
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $demande['duree_mois']; ?> mois
                            <?php if ($demande['duree_approuvee'] && $demande['duree_approuvee'] != $demande['duree_mois']): ?>
                                <br><small class="text-success">
                                    Approuvée: <?php echo $demande['duree_approuvee']; ?> mois
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($demande['type_credit_nom']): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($demande['type_credit_nom']); ?></span>
                                <br><small class="text-muted">
                                    Taux: <?php echo $demande['taux_type']; ?>%
                                    <?php if ($demande['taux_applique'] && $demande['taux_applique'] != $demande['taux_type']): ?>
                                        → <?php echo $demande['taux_applique']; ?>%
                                    <?php endif; ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Non spécifié</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge_class = match($demande['statut']) {
                                'soumise' => 'bg-primary',
                                'en_etude' => 'bg-warning',
                                'approuvee' => 'bg-success',
                                'rejetee' => 'bg-danger',
                                'annulee' => 'bg-secondary',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $demande['statut'])); ?>
                            </span>
                            
                            <?php if ($demande['date_evaluation']): ?>
                                <br><small class="text-muted">
                                    <?php echo date('d/m/Y', strtotime($demande['date_evaluation'])); ?>
                                    <?php if ($demande['evaluateur']): ?>
                                        par <?php echo htmlspecialchars($demande['evaluateur']); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" 
                                        onclick="showDetailsModal(<?php echo htmlspecialchars(json_encode($demande)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if (in_array($demande['statut'], ['soumise', 'en_etude'])): ?>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="showEvaluationModal(<?php echo htmlspecialchars(json_encode($demande)); ?>)">
                                        <i class="fas fa-gavel"></i> Évaluer
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($demande['statut'] === 'approuvee'): ?>
                                    <a href="credits_accordes.php?demande=<?php echo $demande['id_demande']; ?>" 
                                       class="btn btn-outline-success">
                                        <i class="fas fa-money-check"></i> Crédit
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&statut=<?php echo urlencode($filter_statut); ?>&groupe=<?php echo urlencode($filter_groupe); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal d'évaluation -->
<div class="modal fade" id="evaluationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Évaluer la demande de crédit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="evaluationForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="evaluer">
                    <input type="hidden" name="id_demande" id="evalId">
                    
                    <div id="demandeInfo" class="alert alert-light mb-3">
                        <!-- Informations sur la demande -->
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Décision *</label>
                                <select name="statut_evaluation" id="statutEvaluation" class="form-select" required onchange="toggleApprovalFields()">
                                    <option value="">-- Choisir --</option>
                                    <option value="en_etude">Mettre en étude</option>
                                    <option value="approuvee">Approuver</option>
                                    <option value="rejetee">Rejeter</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Commentaire *</label>
                                <textarea name="commentaire_evaluation" class="form-control" rows="2" required
                                          placeholder="Justification de votre décision..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div id="approvalFields" style="display: none;">
                        <h6 class="border-top pt-3 mb-3">Paramètres d'approbation</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Montant approuvé (BIF) *</label>
                                    <input type="number" name="montant_approuve" id="montantApprouve" 
                                           class="form-control" step="1000" min="1000">
                                    <small class="text-muted">Montant demandé: <span id="montantDemande"></span></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Durée approuvée (mois) *</label>
                                    <input type="number" name="duree_approuvee" id="dureeApprouvee" 
                                           class="form-control" min="1" max="60">
                                    <small class="text-muted">Durée demandée: <span id="dureeDemandee"></span> mois</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Taux d'intérêt (%) *</label>
                                    <input type="number" name="taux_applique" id="tauxApplique" 
                                           class="form-control" step="0.1" min="0" max="50">
                                    <small class="text-muted">Taux du type: <span id="tauxType"></span>%</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6>Simulation de remboursement</h6>
                            <div id="simulationResult">
                                <!-- Résultat de la simulation -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-gavel me-2"></i>Valider l'évaluation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Contenu des détails -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function showEvaluationModal(demande) {
    document.getElementById('evalId').value = demande.id_demande;
    
    const info = `
        <div class="row">
            <div class="col-md-6">
                <strong>Membre:</strong> ${demande.code_membre} - ${demande.nom} ${demande.prenom}<br>
                <strong>Groupe:</strong> ${demande.nom_groupe || 'Aucun'}<br>
                <strong>Solde actuel:</strong> ${demande.solde_disponible ? new Intl.NumberFormat('fr-FR').format(demande.solde_disponible) + ' BIF' : 'Inconnu'}
            </div>
            <div class="col-md-6">
                <strong>Montant demandé:</strong> ${new Intl.NumberFormat('fr-FR').format(demande.montant_demande)} BIF<br>
                <strong>Durée:</strong> ${demande.duree_mois} mois<br>
                <strong>Type de crédit:</strong> ${demande.type_credit_nom || 'Non spécifié'}
            </div>
        </div>
        ${demande.motif ? `<div class="mt-2"><strong>Motif:</strong> ${demande.motif}</div>` : ''}
    `;
    
    document.getElementById('demandeInfo').innerHTML = info;
    document.getElementById('montantDemande').textContent = new Intl.NumberFormat('fr-FR').format(demande.montant_demande) + ' BIF';
    document.getElementById('dureeDemandee').textContent = demande.duree_mois;
    document.getElementById('tauxType').textContent = demande.taux_type || '0';
    
    // Pré-remplir avec les valeurs demandées
    document.getElementById('montantApprouve').value = demande.montant_demande;
    document.getElementById('dureeApprouvee').value = demande.duree_mois;
    document.getElementById('tauxApplique').value = demande.taux_type || 0;
    
    updateSimulation();
    
    new bootstrap.Modal(document.getElementById('evaluationModal')).show();
}

function toggleApprovalFields() {
    const statut = document.getElementById('statutEvaluation').value;
    const approvalFields = document.getElementById('approvalFields');
    
    if (statut === 'approuvee') {
        approvalFields.style.display = 'block';
        // Rendre les champs requis
        document.getElementById('montantApprouve').required = true;
        document.getElementById('dureeApprouvee').required = true;
        document.getElementById('tauxApplique').required = false;
    }
}

function updateSimulation() {
    const montant = parseFloat(document.getElementById('montantApprouve').value) || 0;
    const duree = parseInt(document.getElementById('dureeApprouvee').value) || 0;
    const taux = parseFloat(document.getElementById('tauxApplique').value) || 0;
    
    if (montant > 0 && duree > 0) {
        const montantTotal = montant * (1 + taux / 100);
        const mensualite = montantTotal / duree;
        const interetTotal = montantTotal - montant;
        
        document.getElementById('simulationResult').innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <strong>Montant total:</strong><br>
                    <span class="text-primary">${new Intl.NumberFormat('fr-FR').format(montantTotal)} BIF</span>
                </div>
                <div class="col-md-3">
                    <strong>Mensualité:</strong><br>
                    <span class="text-info">${new Intl.NumberFormat('fr-FR').format(mensualite)} BIF</span>
                </div>
                <div class="col-md-3">
                    <strong>Intérêts totaux:</strong><br>
                    <span class="text-warning">${new Intl.NumberFormat('fr-FR').format(interetTotal)} BIF</span>
                </div>
                <div class="col-md-3">
                    <strong>Durée:</strong><br>
                    <span class="text-muted">${duree} mois</span>
                </div>
            </div>
        `;
    } else {
        document.getElementById('simulationResult').innerHTML = 'Veuillez saisir tous les paramètres pour voir la simulation.';
    }
}

// Event listeners pour la simulation
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['montantApprouve', 'dureeApprouvee', 'tauxApplique'];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', updateSimulation);
    });
});

function showDetailsModal(demande) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informations Membre</h6>
                <table class="table table-sm">
                    <tr><td><strong>Code:</strong></td><td>${demande.code_membre}</td></tr>
                    <tr><td><strong>Nom:</strong></td><td>${demande.nom} ${demande.prenom}</td></tr>
                    <tr><td><strong>Téléphone:</strong></td><td>${demande.telephone || 'Non renseigné'}</td></tr>
                    <tr><td><strong>Groupe:</strong></td><td>${demande.nom_groupe || 'Aucun groupe'}</td></tr>
                    <tr><td><strong>Solde actuel:</strong></td><td>${demande.solde_disponible ? new Intl.NumberFormat('fr-FR').format(demande.solde_disponible) + ' BIF' : 'Inconnu'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Détails de la Demande</h6>
                <table class="table table-sm">
                    <tr><td><strong>N° Demande:</strong></td><td>${demande.numero_demande}</td></tr>
                    <tr><td><strong>Date:</strong></td><td>${new Date(demande.date_demande).toLocaleDateString('fr-FR')}</td></tr>
                    <tr><td><strong>Montant:</strong></td><td>${new Intl.NumberFormat('fr-FR').format(demande.montant_demande)} BIF</td></tr>
                    <tr><td><strong>Durée:</strong></td><td>${demande.duree_mois} mois</td></tr>
                    <tr><td><strong>Type crédit:</strong></td><td>${demande.type_credit_nom || 'Non spécifié'}</td></tr>
                </table>
            </div>
        </div>
        
        ${demande.motif ? `
            <div class="mt-3">
                <h6>Motif de la demande</h6>
                <div class="alert alert-light">${demande.motif}</div>
            </div>
        ` : ''}
        
        ${demande.garanties_proposees ? `
            <div class="mt-3">
                <h6>Garanties proposées</h6>
                <div class="alert alert-light">${demande.garanties_proposees}</div>
            </div>
        ` : ''}
        
        ${demande.commentaire_evaluation ? `
            <div class="mt-3">
                <h6>Évaluation</h6>
                <p><strong>Date:</strong> ${new Date(demande.date_evaluation).toLocaleString('fr-FR')}</p>
                ${demande.evaluateur ? `<p><strong>Par:</strong> ${demande.evaluateur}</p>` : ''}
                <div class="alert alert-light">${demande.commentaire_evaluation}</div>
                
                ${demande.statut === 'approuvee' ? `
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <strong>Montant approuvé:</strong><br>
                            <span class="text-success">${new Intl.NumberFormat('fr-FR').format(demande.montant_approuve)} BIF</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Durée approuvée:</strong><br>
                            <span class="text-info">${demande.duree_approuvee} mois</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Taux appliqué:</strong><br>
                            <span class="text-warning">${demande.taux_applique}%</span>
                        </div>
                    </div>
                ` : ''}
            </div>
        ` : ''}
    `;
    
    document.getElementById('detailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>