<?php
$page_title = "Gestion des Membres";
include '../config/config.php';
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

$pdo = getConnection();

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add':
            try {
                // Génération du code membre unique
                $stmt = $pdo->query("SELECT COUNT(*) FROM membres");
                $count = $stmt->fetchColumn() + 1;
                $code_membre = 'MBR' . str_pad($count, 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO membres (code_membre, nom, prenom, email, telephone, adresse, 
                                       province, commune, zone, date_naissance, genre, 
                                       type_membre, specialisation, date_adhesion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                
                $stmt->execute([
                    $code_membre,
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'] ?: null,
                    $_POST['adresse'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['commune'] ?: null,
                    $_POST['zone'] ?: null,
                    $_POST['date_naissance'] ?: null,
                    $_POST['genre'] ?: null,
                    $_POST['type_membre'],
                    $_POST['specialisation'] ?: null
                ]);
                
                // Création du compte coopérative
                $membre_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("
                    INSERT INTO compte_cooperative (id_membre, date_ouverture)
                    VALUES (?, CURDATE())
                ");
                $stmt->execute([$membre_id]);
                
                $message = "Membre ajouté avec succès! Code: $code_membre";
                
            } catch (PDOException $e) {
                $error = "Erreur lors de l'ajout: " . $e->getMessage();
            }
            break;
            
        case 'update':
            try {
                $stmt = $pdo->prepare("
                    UPDATE membres SET 
                        nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?,
                        province = ?, commune = ?, zone = ?, date_naissance = ?, 
                        genre = ?, type_membre = ?, specialisation = ?, statut = ?
                    WHERE id_membre = ?
                ");
                
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'] ?: null,
                    $_POST['adresse'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['commune'] ?: null,
                    $_POST['zone'] ?: null,
                    $_POST['date_naissance'] ?: null,
                    $_POST['genre'] ?: null,
                    $_POST['type_membre'],
                    $_POST['specialisation'] ?: null,
                    $_POST['statut'],
                    $_POST['id_membre']
                ]);
                
                $message = "Membre modifié avec succès!";
                
            } catch (PDOException $e) {
                $error = "Erreur lors de la modification: " . $e->getMessage();
            }
            break;
            
        case 'delete':
            try {
                // Vérifier s'il y a des dépendances
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM compte_cooperative WHERE id_membre = ?");
                $stmt->execute([$_POST['id_membre']]);
                $has_account = $stmt->fetchColumn() > 0;
                
                if ($has_account) {
                    $error = "Impossible de supprimer: le membre a un compte actif.";
                } else {
                    $stmt = $pdo->prepare("UPDATE membres SET statut = 'inactif' WHERE id_membre = ?");
                    $stmt->execute([$_POST['id_membre']]);
                    $message = "Membre désactivé avec succès!";
                }
                
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
            break;
    }
}

// Récupération des données pour modification
$membre = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM membres WHERE id_membre = ?");
    $stmt->execute([$id]);
    $membre = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination et filtrage
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_statut = $_GET['statut'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(nom LIKE ? OR prenom LIKE ? OR code_membre LIKE ? OR telephone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_type) {
    $where_conditions[] = "type_membre = ?";
    $params[] = $filter_type;
}

if ($filter_statut) {
    $where_conditions[] = "statut = ?";
    $params[] = $filter_statut;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Comptage total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM membres $where_clause");
$count_stmt->execute($params);
$total_membres = $count_stmt->fetchColumn();
$total_pages = ceil($total_membres / $limit);

// Récupération des membres - FIXED: Direct embedding of LIMIT and OFFSET
$sql = "
    SELECT m.*, cc.solde_disponible, cc.total_investi 
    FROM membres m 
    LEFT JOIN compte_cooperative cc ON m.id_membre = cc.id_membre
    $where_clause 
    ORDER BY m.date_creation DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params); // Only execute with filter parameters, not LIMIT/OFFSET
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <!-- Formulaire d'ajout/modification -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo $action === 'add' ? 'Ajouter un Membre' : 'Modifier le Membre'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add' : 'update'; ?>">
                <?php if ($membre): ?>
                    <input type="hidden" name="id_membre" value="<?php echo $membre['id_membre']; ?>">
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['nom'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['prenom'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['email'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['telephone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Province</label>
                    <input type="text" name="province" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['province'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Commune</label>
                    <input type="text" name="commune" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['commune'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Zone</label>
                    <input type="text" name="zone" class="form-control" 
                           value="<?php echo htmlspecialchars($membre['zone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control" rows="2"><?php echo htmlspecialchars($membre['adresse'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Date de Naissance</label>
                    <input type="date" name="date_naissance" class="form-control" 
                           value="<?php echo $membre['date_naissance'] ?? ''; ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Genre</label>
                    <select name="genre" class="form-select">
                        <option value="">-- Choisir --</option>
                        <option value="M" <?php echo ($membre['genre'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                        <option value="F" <?php echo ($membre['genre'] ?? '') === 'F' ? 'selected' : ''; ?>>Féminin</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Type de Membre *</label>
                    <select name="type_membre" class="form-select" required>
                        <option value="producteur" <?php echo ($membre['type_membre'] ?? '') === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                        <option value="transformateur" <?php echo ($membre['type_membre'] ?? '') === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                        <option value="commercial" <?php echo ($membre['type_membre'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                        <option value="administratif" <?php echo ($membre['type_membre'] ?? '') === 'administratif' ? 'selected' : ''; ?>>Administratif</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Spécialisation</label>
                    <input type="text" name="specialisation" class="form-control" 
                           placeholder="Ex: Culture de haricots, Transformation du miel..."
                           value="<?php echo htmlspecialchars($membre['specialisation'] ?? ''); ?>">
                </div>
                
                <?php if ($action === 'edit'): ?>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="actif" <?php echo ($membre['statut'] ?? '') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactif" <?php echo ($membre['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                        <option value="suspendu" <?php echo ($membre['statut'] ?? '') === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo $action === 'add' ? 'Ajouter' : 'Modifier'; ?>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/membres.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des membres -->
    <?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des Membres (<?php echo $total_membres; ?>)</h5>
            <a href="?action=add" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-2"></i>Nouveau Membre
            </a>
        </div>
        
        <!-- Filtres -->
        <div class="card-body border-bottom">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="producteur" <?php echo $filter_type === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                        <option value="transformateur" <?php echo $filter_type === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                        <option value="commercial" <?php echo $filter_type === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                        <option value="administratif" <?php echo $filter_type === 'administratif' ? 'selected' : ''; ?>>Administratif</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="actif" <?php echo $filter_statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactif" <?php echo $filter_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                        <option value="suspendu" <?php echo $filter_statut === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom Complet</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Localisation</th>
                        <th>Solde</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($membres as $m): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($m['code_membre']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($m['nom'] . ' ' . $m['prenom']); ?>
                            <?php if ($m['specialisation']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($m['specialisation']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['telephone']): ?>
                                <i class="fas fa-phone fa-sm text-muted me-1"></i><?php echo htmlspecialchars($m['telephone']); ?><br>
                            <?php endif; ?>
                            <?php if ($m['email']): ?>
                                <i class="fas fa-envelope fa-sm text-muted me-1"></i><?php echo htmlspecialchars($m['email']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $m['type_membre'] === 'producteur' ? 'success' : 
                                    ($m['type_membre'] === 'transformateur' ? 'info' : 
                                    ($m['type_membre'] === 'commercial' ? 'warning' : 'primary')); 
                            ?>">
                                <?php echo ucfirst($m['type_membre']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($m['commune']): ?>
                                <?php echo htmlspecialchars($m['commune']); ?>
                                <?php if ($m['province']): ?>, <?php echo htmlspecialchars($m['province']); ?><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['solde_disponible'] !== null): ?>
                                <strong><?php echo number_format($m['solde_disponible'], 0, ',', ' '); ?> BIF</strong>
                                <?php if ($m['total_investi'] > 0): ?>
                                    <br><small class="text-muted">Investi: <?php echo number_format($m['total_investi'], 0, ',', ' '); ?> BIF</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Aucun compte</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $m['statut'] === 'actif' ? 'success' : 
                                    ($m['statut'] === 'suspendu' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($m['statut']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?action=edit&id=<?php echo $m['id_membre']; ?>" 
                                   class="btn btn-outline-primary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="membre_details.php?id=<?php echo $m['id_membre']; ?>" 
                                   class="btn btn-outline-info" title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($m['statut'] === 'actif'): ?>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDelete(<?php echo $m['id_membre']; ?>)" title="Désactiver">
                                    <i class="fas fa-ban"></i>
                                </button>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($filter_type); ?>&statut=<?php echo urlencode($filter_statut); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir désactiver ce membre ?
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_membre" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Désactiver</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>