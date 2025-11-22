<?php
$page_title = "Gestion des Membres";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

// Traitement des actions
$action = $_GET['action'] ?? 'list';
$id_membre = $_GET['id'] ?? null;
$message = '';

// Actions CRUD
if ($_POST) {
    try {
        $pdo = getConnection();
        switch ($_POST['action']) {
            case 'add':
                // Génération du code membre
                $code_membre = 'MBR' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO membres (code_membre, nom, prenom, email, telephone, adresse, province, commune, zone, date_naissance, genre, date_adhesion, type_membre, specialisation, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $code_membre,
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'],
                    $_POST['adresse'],
                    $_POST['province'],
                    $_POST['commune'],
                    $_POST['zone'],
                    $_POST['date_naissance'],
                    $_POST['genre'],
                    $_POST['date_adhesion'],
                    $_POST['type_membre'],
                    $_POST['specialisation'],
                    $_POST['statut']
                ]);
                $message = '<div class="alert alert-success">Membre ajouté avec succès!</div>';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE membres SET nom=?, prenom=?, email=?, telephone=?, adresse=?, province=?, commune=?, zone=?, date_naissance=?, genre=?, type_membre=?, specialisation=?, statut=? WHERE id_membre=?");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'],
                    $_POST['adresse'],
                    $_POST['province'],
                    $_POST['commune'],
                    $_POST['zone'],
                    $_POST['date_naissance'],
                    $_POST['genre'],
                    $_POST['type_membre'],
                    $_POST['specialisation'],
                    $_POST['statut'],
                    $_POST['id_membre']
                ]);
                $message = '<div class="alert alert-success">Membre modifié avec succès!</div>';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM membres WHERE id_membre = ?");
                $stmt->execute([$_POST['id_membre']]);
                $message = '<div class="alert alert-success">Membre supprimé avec succès!</div>';
                break;
        }
        $action = 'list'; // Retour à la liste
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération des données pour édition
$membre_data = [];
if ($action === 'edit' && $id_membre) {
    $stmt = $pdo->prepare("SELECT * FROM membres WHERE id_membre = ?");
    $stmt->execute([$id_membre]);
    $membre_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Liste des membres avec pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(nom LIKE ? OR prenom LIKE ? OR code_membre LIKE ?)";
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

$where_clause = '';
if ($where_conditions) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}
 $pdo = getConnection();
// Compter le total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM membres $where_clause");
$count_stmt->execute($params);
$total_membres = $count_stmt->fetchColumn();
$total_pages = ceil($total_membres / $limit);

// Récupérer les membres
$membres_stmt = $pdo->prepare("SELECT * FROM membres $where_clause ORDER BY date_creation DESC LIMIT $limit OFFSET $offset");
$membres_stmt->execute($params);
$membres = $membres_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <?php echo $message; ?>
    
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Administration</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/admin/" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/membres.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i>Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/produits.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/commandes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/projets.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Projets Sociaux
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/formations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i>Formations
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Gestion des Membres</h2>
                    <p class="text-muted">Total: <?php echo $total_membres; ?> membres</p>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau Membre
                </a>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="filter_type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <option value="producteur" <?php echo $filter_type === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                                    <option value="transformateur" <?php echo $filter_type === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                                    <option value="commercial" <?php echo $filter_type === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                    <option value="administratif" <?php echo $filter_type === 'administratif' ? 'selected' : ''; ?>>Administratif</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter_statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="actif" <?php echo $filter_statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactif" <?php echo $filter_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                    <option value="suspendu" <?php echo $filter_statut === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des membres -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom Complet</th>
                                        <th>Type</th>
                                        <th>Contact</th>
                                        <th>Statut</th>
                                        <th>Adhésion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($membres as $membre): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($membre['code_membre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($membre['nom'] . ' ' . $membre['prenom']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($membre['type_membre']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($membre['telephone']); ?><br>
                                            <?php echo htmlspecialchars($membre['email'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = $membre['statut'] === 'actif' ? 'bg-success' : 
                                                          ($membre['statut'] === 'suspendu' ? 'bg-danger' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($membre['statut']); ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($membre['date_adhesion'])); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $membre['id_membre']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $membre['id_membre']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_type ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/modification -->
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo $action === 'edit' ? 'Modifier le Membre' : 'Nouveau Membre'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id_membre" value="<?php echo $id_membre; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" class="form-control" name="nom" required value="<?php echo htmlspecialchars($membre_data['nom'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" name="prenom" required value="<?php echo htmlspecialchars($membre_data['prenom'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($membre_data['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone *</label>
                                        <input type="text" class="form-control" name="telephone" required value="<?php echo htmlspecialchars($membre_data['telephone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Province</label>
                                        <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($membre_data['province'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Commune</label>
                                        <input type="text" class="form-control" name="commune" value="<?php echo htmlspecialchars($membre_data['commune'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Zone</label>
                                        <input type="text" class="form-control" name="zone" value="<?php echo htmlspecialchars($membre_data['zone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <textarea class="form-control" name="adresse" rows="2"><?php echo htmlspecialchars($membre_data['adresse'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Naissance</label>
                                        <input type="date" class="form-control" name="date_naissance" value="<?php echo $membre_data['date_naissance'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Genre</label>
                                        <select class="form-select" name="genre">
                                            <option value="">Sélectionnez</option>
                                            <option value="M" <?php echo ($membre_data['genre'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                            <option value="F" <?php echo ($membre_data['genre'] ?? '') === 'F' ? 'selected' : ''; ?>>Féminin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date d'Adhésion *</label>
                                        <input type="date" class="form-control" name="date_adhesion" required value="<?php echo $membre_data['date_adhesion'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Type de Membre *</label>
                                        <select class="form-select" name="type_membre" required>
                                            <option value="">Sélectionnez</option>
                                            <option value="producteur" <?php echo ($membre_data['type_membre'] ?? '') === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                                            <option value="transformateur" <?php echo ($membre_data['type_membre'] ?? '') === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                                            <option value="commercial" <?php echo ($membre_data['type_membre'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                            <option value="administratif" <?php echo ($membre_data['type_membre'] ?? '') === 'administratif' ? 'selected' : ''; ?>>Administratif</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Spécialisation</label>
                                        <input type="text" class="form-control" name="specialisation" value="<?php echo htmlspecialchars($membre_data['specialisation'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" name="statut">
                                            <option value="actif" <?php echo ($membre_data['statut'] ?? 'actif') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                            <option value="inactif" <?php echo ($membre_data['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                            <option value="suspendu" <?php echo ($membre_data['statut'] ?? '') === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $action === 'edit' ? 'Modifier' : 'Ajouter'; ?>
                                </button>
                                <a href="?action=list" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce membre ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_membre" id="delete_id">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>