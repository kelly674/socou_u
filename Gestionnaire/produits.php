<?php
$page_title = "Gestion des Produits";
include '../config/config.php';
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

// Traitement des actions
$action = $_GET['action'] ?? 'list';
$id_produit = $_GET['id'] ?? null;
$message = '';
$pdo = getConnection();
// Actions CRUD
if ($_POST) {
    try {
        
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO produits (nom_produit, description, id_categorie, prix_unitaire, unite_mesure, stock_disponible, producteur_id, saisonnalite, caracteristiques, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nom_produit'],
                    $_POST['description'],
                    $_POST['id_categorie'] ?: null,
                    $_POST['prix_unitaire'],
                    $_POST['unite_mesure'],
                    $_POST['stock_disponible'],
                    $_POST['producteur_id'] ?: null,
                    $_POST['saisonnalite'],
                    $_POST['caracteristiques'],
                    $_POST['statut']
                ]);
                $message = '<div class="alert alert-success">Produit ajouté avec succès!</div>';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE produits SET nom_produit=?, description=?, id_categorie=?, prix_unitaire=?, unite_mesure=?, stock_disponible=?, producteur_id=?, saisonnalite=?, caracteristiques=?, statut=? WHERE id_produit=?");
                $stmt->execute([
                    $_POST['nom_produit'],
                    $_POST['description'],
                    $_POST['id_categorie'] ?: null,
                    $_POST['prix_unitaire'],
                    $_POST['unite_mesure'],
                    $_POST['stock_disponible'],
                    $_POST['producteur_id'] ?: null,
                    $_POST['saisonnalite'],
                    $_POST['caracteristiques'],
                    $_POST['statut'],
                    $_POST['id_produit']
                ]);
                $message = '<div class="alert alert-success">Produit modifié avec succès!</div>';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM produits WHERE id_produit = ?");
                $stmt->execute([$_POST['id_produit']]);
                $message = '<div class="alert alert-success">Produit supprimé avec succès!</div>';
                break;
        }
        $action = 'list';
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération des données pour édition
$produit_data = [];
if ($action === 'edit' && $id_produit) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = ?");
    $stmt->execute([$id_produit]);
    $produit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
 $pdo = getConnection();
// Récupération des catégories
$categories_stmt = $pdo->query("SELECT * FROM categories_produits WHERE statut = 'actif' ORDER BY nom_categorie");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des producteurs
$producteurs_stmt = $pdo->query("SELECT id_membre, nom, prenom FROM membres WHERE type_membre = 'producteur' AND statut = 'actif' ORDER BY nom, prenom");
$producteurs = $producteurs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des produits avec pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_categorie = $_GET['filter_categorie'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.nom_produit LIKE ?)";
    $params[] = "%$search%";
}

if ($filter_categorie) {
    $where_conditions[] = "p.id_categorie = ?";
    $params[] = $filter_categorie;
}

if ($filter_statut) {
    $where_conditions[] = "p.statut = ?";
    $params[] = $filter_statut;
}

$where_clause = '';
if ($where_conditions) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM produits p $where_clause");
$count_stmt->execute($params);
$total_produits = $count_stmt->fetchColumn();
$total_pages = ceil($total_produits / $limit);

// Récupérer les produits avec jointures
$produits_stmt = $pdo->prepare("
    SELECT p.*, c.nom_categorie, 
           CONCAT(m.nom, ' ', m.prenom) as producteur_nom
    FROM produits p 
    LEFT JOIN categories_produits c ON p.id_categorie = c.id_categorie
    LEFT JOIN membres m ON p.producteur_id = m.id_membre
    $where_clause 
    ORDER BY p.date_creation DESC 
    LIMIT $limit OFFSET $offset
");
$produits_stmt->execute($params);
$produits = $produits_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/membres.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/produits.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/commandes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/projets.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Projets Sociaux
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/formations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i>Formations
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Gestion des Produits</h2>
                    <p class="text-muted">Total: <?php echo $total_produits; ?> produits</p>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau Produit
                </a>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher un produit..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="filter_categorie" class="form-select">
                                    <option value="">Toutes les catégories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id_categorie']; ?>" <?php echo $filter_categorie == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter_statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="disponible" <?php echo $filter_statut === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                    <option value="epuise" <?php echo $filter_statut === 'epuise' ? 'selected' : ''; ?>>Épuisé</option>
                                    <option value="saisonnier" <?php echo $filter_statut === 'saisonnier' ? 'selected' : ''; ?>>Saisonnier</option>
                                    <option value="inactif" <?php echo $filter_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des produits -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th>Prix</th>
                                        <th>Stock</th>
                                        <th>Producteur</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produits as $produit): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($produit['nom_produit']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($produit['description'], 0, 50)) . '...'; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($produit['nom_categorie'] ?? 'N/A'); ?></td>
                                        <td>
                                            <strong><?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> BIF</strong>
                                            <br><small>par <?php echo htmlspecialchars($produit['unite_mesure']); ?></small>
                                        </td>
                                        <td>
                                            <span class="<?php echo $produit['stock_disponible'] <= 10 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $produit['stock_disponible']; ?> <?php echo htmlspecialchars($produit['unite_mesure']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($produit['producteur_nom'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $badge_colors = [
                                                'disponible' => 'bg-success',
                                                'epuise' => 'bg-danger',
                                                'saisonnier' => 'bg-warning',
                                                'inactif' => 'bg-secondary'
                                            ];
                                            $badge_class = $badge_colors[$produit['statut']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($produit['statut']); ?></span>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $produit['id_produit']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $produit['id_produit']; ?>)">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_categorie ? '&filter_categorie=' . urlencode($filter_categorie) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?>"><?php echo $i; ?></a>
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
                        <h5><?php echo $action === 'edit' ? 'Modifier le Produit' : 'Nouveau Produit'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id_produit" value="<?php echo $id_produit; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Nom du Produit *</label>
                                        <input type="text" class="form-control" name="nom_produit" required value="<?php echo htmlspecialchars($produit_data['nom_produit'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Catégorie</label>
                                        <select class="form-select" name="id_categorie">
                                            <option value="">Sélectionnez une catégorie</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id_categorie']; ?>" <?php echo ($produit_data['id_categorie'] ?? '') == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($produit_data['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Prix Unitaire (BIF) *</label>
                                        <input type="number" step="0.01" class="form-control" name="prix_unitaire" required value="<?php echo $produit_data['prix_unitaire'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Unité de Mesure *</label>
                                        <input type="text" class="form-control" name="unite_mesure" required value="<?php echo htmlspecialchars($produit_data['unite_mesure'] ?? ''); ?>" placeholder="kg, litre, pièce...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Disponible</label>
                                        <input type="number" step="0.01" class="form-control" name="stock_disponible" value="<?php echo $produit_data['stock_disponible'] ?? '0'; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" name="statut">
                                            <option value="disponible" <?php echo ($produit_data['statut'] ?? 'disponible') === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                            <option value="epuise" <?php echo ($produit_data['statut'] ?? '') === 'epuise' ? 'selected' : ''; ?>>Épuisé</option>
                                            <option value="saisonnier" <?php echo ($produit_data['statut'] ?? '') === 'saisonnier' ? 'selected' : ''; ?>>Saisonnier</option>
                                            <option value="inactif" <?php echo ($produit_data['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Producteur</label>
                                        <select class="form-select" name="producteur_id">
                                            <option value="">Sélectionnez un producteur</option>
                                            <?php foreach ($producteurs as $producteur): ?>
                                                <option value="<?php echo $producteur['id_membre']; ?>" <?php echo ($produit_data['producteur_id'] ?? '') == $producteur['id_membre'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($producteur['nom'] . ' ' . $producteur['prenom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Saisonnalité</label>
                                        <input type="text" class="form-control" name="saisonnalite" value="<?php echo htmlspecialchars($produit_data['saisonnalite'] ?? ''); ?>" placeholder="Ex: Janvier-Mars, Toute l'année...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Caractéristiques</label>
                                <textarea class="form-control" name="caracteristiques" rows="3" placeholder="Qualité, origine, certifications..."><?php echo htmlspecialchars($produit_data['caracteristiques'] ?? ''); ?></textarea>
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
                Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_produit" id="delete_id">
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