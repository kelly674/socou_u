<?php
$page_title = "Validation des Investissements";
include '../config/config.php';
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

$pdo = getConnection();
$message = '';
$error = '';

// Traitement des validations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_investissement = $_POST['id_investissement'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1; // ID du gestionnaire connecté
    
    if ($action === 'valider') {
        try {
            $pdo->beginTransaction();
            
            // Récupérer les détails de l'investissement
            $stmt = $pdo->prepare("
                SELECT i.*, m.nom, m.prenom, m.code_membre 
                FROM investissement i 
                JOIN membres m ON i.id_membre = m.id_membre 
                WHERE i.id_investissement = ? AND i.statut = 'en_attente'
            ");
            $stmt->execute([$id_investissement]);
            $investissement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$investissement) {
                throw new Exception("Investissement non trouvé ou déjà traité");
            }
            
            // Valider l'investissement
            $stmt = $pdo->prepare("
                UPDATE investissement 
                SET statut = 'valide', 
                    valide_par = ?, 
                    date_validation = NOW(),
                    commentaire = ?
                WHERE id_investissement = ?
            ");
            $stmt->execute([
                $user_id,
                $_POST['commentaire'] ?? 'Investissement validé',
                $id_investissement
            ]);
            
            // Mettre à jour le compte coopérative
            $stmt = $pdo->prepare("
                UPDATE compte_cooperative 
                SET solde_disponible = solde_disponible + ?,
                    total_investi = total_investi + ?,
                    date_derniere_operation = NOW()
                WHERE id_membre = ?
            ");
            $stmt->execute([
                $investissement['montant'],
                $investissement['montant'],
                $investissement['id_membre']
            ]);
            
            // Log de l'activité
            $stmt = $pdo->prepare("
                INSERT INTO logs_activites (utilisateur_id, action, table_concernee, id_enregistrement, details)
                VALUES (?, 'VALIDATION_INVESTISSEMENT', 'investissement', ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $id_investissement,
                "Validation investissement de {$investissement['montant']} BIF pour {$investissement['nom']} {$investissement['prenom']}"
            ]);
            
            $pdo->commit();
            $message = "Investissement validé avec succès! Montant ajouté au compte du membre.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la validation: " . $e->getMessage();
        }
        
    } elseif ($action === 'rejeter') {
        try {
            $stmt = $pdo->prepare("
                UPDATE investissement 
                SET statut = 'rejete', 
                    valide_par = ?, 
                    date_validation = NOW(),
                    commentaire = ?
                WHERE id_investissement = ? AND statut = 'en_attente'
            ");
            $stmt->execute([
                $user_id,
                $_POST['commentaire'] ?? 'Investissement rejeté',
                $id_investissement
            ]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Investissement rejeté.";
            } else {
                $error = "Impossible de rejeter cet investissement.";
            }
            
        } catch (PDOException $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

// Pagination
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$filter_statut = $_GET['statut'] ?? 'en_attente';

// Récupération des investissements
$where_clause = "";
$params = [];

if ($filter_statut) {
    $where_clause = "WHERE i.statut = ?";
    $params[] = $filter_statut;
}

// Count query
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM investissement i 
    JOIN membres m ON i.id_membre = m.id_membre 
    $where_clause
");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Main query - build SQL with integer values directly for LIMIT and OFFSET
$sql = "
    SELECT i.*, m.nom, m.prenom, m.code_membre, m.telephone,
           cc.solde_disponible, cc.total_investi,
           u.username as validateur
    FROM investissement i 
    JOIN membres m ON i.id_membre = m.id_membre 
    LEFT JOIN compte_cooperative cc ON m.id_membre = cc.id_membre
    LEFT JOIN utilisateurs u ON i.valide_par = u.id_utilisateur
    $where_clause
    ORDER BY i.date_creation DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);

// Execute with only the filter parameters (not LIMIT/OFFSET)
$stmt->execute($params);
$investissements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as en_attente,
        COUNT(CASE WHEN statut = 'valide' THEN 1 END) as valides,
        COUNT(CASE WHEN statut = 'rejete' THEN 1 END) as rejetes,
        SUM(CASE WHEN statut = 'valide' THEN montant ELSE 0 END) as total_valide
    FROM investissement
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
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
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $stats['en_attente']; ?></h3>
                            <p class="mb-0">En Attente</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $stats['valides']; ?></h3>
                            <p class="mb-0">Validés</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x opacity-75"></i>
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
                            <h3><?php echo $stats['rejetes']; ?></h3>
                            <p class="mb-0">Rejetés</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo number_format($stats['total_valide'], 0, ',', ' '); ?> BIF</h4>
                            <p class="mb-0">Total Validé</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-money-bill fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des investissements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Investissements (<?php echo $total; ?>)</h5>
            
            <!-- Filtres -->
            <div class="btn-group">
                <a href="?statut=en_attente" class="btn btn-sm <?php echo $filter_statut === 'en_attente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    En attente (<?php echo $stats['en_attente']; ?>)
                </a>
                <a href="?statut=valide" class="btn btn-sm <?php echo $filter_statut === 'valide' ? 'btn-success' : 'btn-outline-success'; ?>">
                    Validés (<?php echo $stats['valides']; ?>)
                </a>
                <a href="?statut=rejete" class="btn btn-sm <?php echo $filter_statut === 'rejete' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                    Rejetés (<?php echo $stats['rejetes']; ?>)
                </a>
                <a href="?" class="btn btn-sm <?php echo !$filter_statut ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Tous
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Membre</th>
                        <th>Montant</th>
                        <th>Type</th>
                        <th>Preuve</th>
                        <th>Compte Actuel</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($investissements as $inv): ?>
                    <tr class="<?php echo $inv['statut'] === 'en_attente' ? 'table-warning' : ''; ?>">
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($inv['date_investissement'])); ?></strong>
                            <br><small class="text-muted"><?php echo date('H:i', strtotime($inv['date_creation'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($inv['code_membre']); ?></strong>
                            <br><?php echo htmlspecialchars($inv['nom'] . ' ' . $inv['prenom']); ?>
                            <?php if ($inv['telephone']): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-phone fa-sm me-1"></i><?php echo htmlspecialchars($inv['telephone']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="text-primary"><?php echo number_format($inv['montant'], 0, ',', ' '); ?> BIF</strong>
                        </td>
                        <td>
                            <?php if ($inv['type_investissement']): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($inv['type_investissement']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Non spécifié</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($inv['preuve_paiement']): ?>
                                <a href="<?php echo SITE_URL; ?>/uploads/preuves/<?php echo htmlspecialchars($inv['preuve_paiement']); ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-image me-1"></i>Voir
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Aucune preuve</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($inv['solde_disponible'] !== null): ?>
                                <strong><?php echo number_format($inv['solde_disponible'], 0, ',', ' '); ?> BIF</strong>
                                <?php if ($inv['total_investi'] > 0): ?>
                                    <br><small class="text-muted">Total investi: <?php echo number_format($inv['total_investi'], 0, ',', ' '); ?> BIF</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-warning">Pas de compte</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge_class = match($inv['statut']) {
                                'en_attente' => 'bg-warning',
                                'valide' => 'bg-success',
                                'rejete' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $inv['statut'])); ?>
                            </span>
                            
                            <?php if ($inv['date_validation']): ?>
                                <br><small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($inv['date_validation'])); ?>
                                    <?php if ($inv['validateur']): ?>
                                        par <?php echo htmlspecialchars($inv['validateur']); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($inv['statut'] === 'en_attente'): ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-success" 
                                            onclick="showValidationModal(<?php echo $inv['id_investissement']; ?>, 'valider', '<?php echo htmlspecialchars($inv['nom'] . ' ' . $inv['prenom']); ?>', <?php echo $inv['montant']; ?>)">
                                        <i class="fas fa-check"></i> Valider
                                    </button>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="showValidationModal(<?php echo $inv['id_investissement']; ?>, 'rejeter', '<?php echo htmlspecialchars($inv['nom'] . ' ' . $inv['prenom']); ?>', <?php echo $inv['montant']; ?>)">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>
                                </div>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        onclick="showDetailsModal(<?php echo htmlspecialchars(json_encode($inv)); ?>)">
                                    <i class="fas fa-eye"></i> Détails
                                </button>
                            <?php endif; ?>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&statut=<?php echo urlencode($filter_statut); ?>">
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

<!-- Modal de validation -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="validationForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="validationAction">
                    <input type="hidden" name="id_investissement" id="validationId">
                    
                    <div id="validationInfo" class="alert alert-info">
                        <!-- Informations sur l'investissement -->
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Commentaire</label>
                        <textarea name="commentaire" class="form-control" rows="3" 
                                  placeholder="Commentaire sur cette décision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn" id="validationBtn"></button>
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
                <h5 class="modal-title">Détails de l'investissement</h5>
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
function showValidationModal(id, action, membre, montant) {
    document.getElementById('validationId').value = id;
    document.getElementById('validationAction').value = action;
    
    if (action === 'valider') {
        document.getElementById('validationModalTitle').textContent = 'Valider l\'investissement';
        document.getElementById('validationInfo').innerHTML = `
            <strong>Membre:</strong> ${membre}<br>
            <strong>Montant:</strong> ${new Intl.NumberFormat('fr-FR').format(montant)} BIF<br>
            <br>⚠️ <strong>Attention:</strong> Cette validation ajoutera automatiquement le montant au compte du membre.
        `;
        document.getElementById('validationBtn').className = 'btn btn-success';
        document.getElementById('validationBtn').innerHTML = '<i class="fas fa-check me-2"></i>Valider';
    } else {
        document.getElementById('validationModalTitle').textContent = 'Rejeter l\'investissement';
        document.getElementById('validationInfo').innerHTML = `
            <strong>Membre:</strong> ${membre}<br>
            <strong>Montant:</strong> ${new Intl.NumberFormat('fr-FR').format(montant)} BIF<br>
            <br>⚠️ <strong>Attention:</strong> Cette action rejettera définitivement l'investissement.
        `;
        document.getElementById('validationBtn').className = 'btn btn-danger';
        document.getElementById('validationBtn').innerHTML = '<i class="fas fa-times me-2"></i>Rejeter';
    }
    
    new bootstrap.Modal(document.getElementById('validationModal')).show();
}

function showDetailsModal(investissement) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informations Membre</h6>
                <table class="table table-sm">
                    <tr><td><strong>Code:</strong></td><td>${investissement.code_membre}</td></tr>
                    <tr><td><strong>Nom:</strong></td><td>${investissement.nom} ${investissement.prenom}</td></tr>
                    <tr><td><strong>Téléphone:</strong></td><td>${investissement.telephone || 'Non renseigné'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Détails Investissement</h6>
                <table class="table table-sm">
                    <tr><td><strong>Montant:</strong></td><td>${new Intl.NumberFormat('fr-FR').format(investissement.montant)} BIF</td></tr>
                    <tr><td><strong>Type:</strong></td><td>${investissement.type_investissement || 'Non spécifié'}</td></tr>
                    <tr><td><strong>Date:</strong></td><td>${new Date(investissement.date_investissement).toLocaleDateString('fr-FR')}</td></tr>
                    <tr><td><strong>Statut:</strong></td><td>
                        <span class="badge ${investissement.statut === 'valide' ? 'bg-success' : (investissement.statut === 'rejete' ? 'bg-danger' : 'bg-warning')}">
                            ${investissement.statut.replace('_', ' ').toUpperCase()}
                        </span>
                    </td></tr>
                </table>
            </div>
        </div>
        
        ${investissement.commentaire ? `
            <div class="mt-3">
                <h6>Commentaire</h6>
                <div class="alert alert-light">${investissement.commentaire}</div>
            </div>
        ` : ''}
        
        ${investissement.date_validation ? `
            <div class="mt-3">
                <h6>Validation</h6>
                <p><strong>Date:</strong> ${new Date(investissement.date_validation).toLocaleString('fr-FR')}</p>
                ${investissement.validateur ? `<p><strong>Par:</strong> ${investissement.validateur}</p>` : ''}
            </div>
        ` : ''}
    `;
    
    document.getElementById('detailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>