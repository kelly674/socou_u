<?php
$page_title = "Gestion des Commandes";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

// Traitement des actions
$action = $_GET['action'] ?? 'list';
$id_commande = $_GET['id'] ?? null;
$message = '';
$pdo = getConnection();
// Actions CRUD
if ($_POST) {
    try {
        switch ($_POST['action']) {
            case 'add':
                // Génération du numéro de commande
                $numero_commande = 'CMD' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO commandes (numero_commande, client_id, date_commande, statut, montant_total, frais_livraison, mode_paiement, statut_paiement, date_livraison, adresse_livraison, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $numero_commande,
                    $_POST['client_id'],
                    $_POST['date_commande'],
                    $_POST['statut'],
                    $_POST['montant_total'],
                    $_POST['frais_livraison'],
                    $_POST['mode_paiement'],
                    $_POST['statut_paiement'],
                    $_POST['date_livraison'] ?: null,
                    $_POST['adresse_livraison'],
                    $_POST['notes']
                ]);
                $message = '<div class="alert alert-success">Commande ajoutée avec succès!</div>';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE commandes SET client_id=?, date_commande=?, statut=?, montant_total=?, frais_livraison=?, mode_paiement=?, statut_paiement=?, date_livraison=?, adresse_livraison=?, notes=? WHERE id_commande=?");
                $stmt->execute([
                    $_POST['client_id'],
                    $_POST['date_commande'],
                    $_POST['statut'],
                    $_POST['montant_total'],
                    $_POST['frais_livraison'],
                    $_POST['mode_paiement'],
                    $_POST['statut_paiement'],
                    $_POST['date_livraison'] ?: null,
                    $_POST['adresse_livraison'],
                    $_POST['notes'],
                    $_POST['id_commande']
                ]);
                $message = '<div class="alert alert-success">Commande modifiée avec succès!</div>';
                break;
                
            case 'update_status':
                $stmt = $pdo->prepare("UPDATE commandes SET statut=? WHERE id_commande=?");
                $stmt->execute([$_POST['nouveau_statut'], $_POST['id_commande']]);
                $message = '<div class="alert alert-success">Statut mis à jour avec succès!</div>';
                break;
                
            case 'delete':
                // Supprimer les détails d'abord
                $stmt = $pdo->prepare("DELETE FROM details_commandes WHERE id_commande = ?");
                $stmt->execute([$_POST['id_commande']]);
                
                // Puis la commande
                $stmt = $pdo->prepare("DELETE FROM commandes WHERE id_commande = ?");
                $stmt->execute([$_POST['id_commande']]);
                $message = '<div class="alert alert-success">Commande supprimée avec succès!</div>';
                break;
        }
        $action = 'list';
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération des données pour édition
$commande_data = [];
if ($action === 'edit' && $id_commande) {
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id_commande = ?");
    $stmt->execute([$id_commande]);
    $commande_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupération des membres clients
$clients_stmt = $pdo->query("SELECT id_membre, nom, prenom FROM membres WHERE statut = 'actif' ORDER BY nom, prenom");
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des commandes avec pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';
$filter_paiement = $_GET['filter_paiement'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.numero_commande LIKE ? OR CONCAT(m.nom, ' ', m.prenom) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_statut) {
    $where_conditions[] = "c.statut = ?";
    $params[] = $filter_statut;
}

if ($filter_paiement) {
    $where_conditions[] = "c.statut_paiement = ?";
    $params[] = $filter_paiement;
}

$where_clause = '';
if ($where_conditions) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes c LEFT JOIN membres m ON c.client_id = m.id_membre $where_clause");
$count_stmt->execute($params);
$total_commandes = $count_stmt->fetchColumn();
$total_pages = ceil($total_commandes / $limit);

// Récupérer les commandes avec jointures
$commandes_stmt = $pdo->prepare("
    SELECT c.*, CONCAT(m.nom, ' ', m.prenom) as client_nom, m.telephone as client_telephone
    FROM commandes c 
    LEFT JOIN membres m ON c.client_id = m.id_membre
    $where_clause 
    ORDER BY c.date_creation DESC 
    LIMIT $limit OFFSET $offset
");
$commandes_stmt->execute($params);
$commandes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'confirmee' THEN 1 ELSE 0 END) as confirmees,
        SUM(CASE WHEN statut_paiement = 'en_attente' THEN 1 ELSE 0 END) as paiement_attente,
        SUM(montant_total) as chiffre_affaires
    FROM commandes 
    WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
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
                    <a href="<?php echo SITE_URL; ?>/admin/membres.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/produits.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/commandes.php" class="list-group-item list-group-item-action active">
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
                    <h2>Gestion des Commandes</h2>
                    <p class="text-muted">Total: <?php echo $total_commandes; ?> commandes</p>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouvelle Commande
                </a>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total']; ?></h4>
                                <small>Commandes (30j)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['en_attente']; ?></h4>
                                <small>En attente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['confirmees']; ?></h4>
                                <small>Confirmées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php echo number_format($stats['chiffre_affaires'], 0, ',', ' '); ?> BIF</h4>
                                <small>CA (30j)</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher par numéro ou client..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="filter_statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="en_attente" <?php echo $filter_statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="confirmee" <?php echo $filter_statut === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                    <option value="preparation" <?php echo $filter_statut === 'preparation' ? 'selected' : ''; ?>>En préparation</option>
                                    <option value="expediee" <?php echo $filter_statut === 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                    <option value="livree" <?php echo $filter_statut === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                    <option value="annulee" <?php echo $filter_statut === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter_paiement" class="form-select">
                                    <option value="">Statut paiement</option>
                                    <option value="en_attente" <?php echo $filter_paiement === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="partiel" <?php echo $filter_paiement === 'partiel' ? 'selected' : ''; ?>>Partiel</option>
                                    <option value="paye" <?php echo $filter_paiement === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des commandes -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Paiement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($commande['numero_commande']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($commande['client_nom'] ?? 'Client supprimé'); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($commande['client_telephone'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                                        <td>
                                            <strong><?php echo number_format($commande['montant_total'], 0, ',', ' '); ?> BIF</strong>
                                            <?php if ($commande['frais_livraison'] > 0): ?>
                                                <br><small class="text-muted">+ <?php echo number_format($commande['frais_livraison'], 0, ',', ' '); ?> BIF livraison</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_colors = [
                                                'en_attente' => 'bg-warning',
                                                'confirmee' => 'bg-primary',
                                                'preparation' => 'bg-info',
                                                'expediee' => 'bg-success',
                                                'livree' => 'bg-success',
                                                'annulee' => 'bg-danger'
                                            ];
                                            $badge_class = $badge_colors[$commande['statut']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $commande['statut'])); ?></span>
                                            
                                            <!-- Bouton de changement rapide de statut -->
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                                            <input type="hidden" name="nouveau_statut" value="confirmee">
                                                            <button type="submit" class="dropdown-item">Confirmer</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                                            <input type="hidden" name="nouveau_statut" value="preparation">
                                                            <button type="submit" class="dropdown-item">En préparation</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                                            <input type="hidden" name="nouveau_statut" value="expediee">
                                                            <button type="submit" class="dropdown-item">Expédier</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                                            <input type="hidden" name="nouveau_statut" value="livree">
                                                            <button type="submit" class="dropdown-item">Marquer livrée</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $payment_colors = [
                                                'en_attente' => 'bg-warning',
                                                'partiel' => 'bg-info',
                                                'paye' => 'bg-success'
                                            ];
                                            $payment_class = $payment_colors[$commande['statut_paiement']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $payment_class; ?>"><?php echo ucfirst($commande['statut_paiement']); ?></span>
                                            <br><small class="text-muted"><?php echo ucfirst($commande['mode_paiement']); ?></small>
                                        </td>
                                        <td>
                                            <a href="?action=view&id=<?php echo $commande['id_commande']; ?>" class="btn btn-sm btn-outline-info" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $commande['id_commande']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $commande['id_commande']; ?>)" title="Supprimer">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?><?php echo $filter_paiement ? '&filter_paiement=' . urlencode($filter_paiement) : ''; ?>"><?php echo $i; ?></a>
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
                        <h5><?php echo $action === 'edit' ? 'Modifier la Commande' : 'Nouvelle Commande'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id_commande" value="<?php echo $id_commande; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Client *</label>
                                        <select class="form-select" name="client_id" required>
                                            <option value="">Sélectionnez un client</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id_membre']; ?>" <?php echo ($commande_data['client_id'] ?? '') == $client['id_membre'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Commande *</label>
                                        <input type="date" class="form-control" name="date_commande" required value="<?php echo $commande_data['date_commande'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Montant Total (BIF) *</label>
                                        <input type="number" step="0.01" class="form-control" name="montant_total" required value="<?php echo $commande_data['montant_total'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Frais de Livraison (BIF)</label>
                                        <input type="number" step="0.01" class="form-control" name="frais_livraison" value="<?php echo $commande_data['frais_livraison'] ?? '0'; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Livraison</label>
                                        <input type="date" class="form-control" name="date_livraison" value="<?php echo $commande_data['date_livraison'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" name="statut">
                                            <option value="en_attente" <?php echo ($commande_data['statut'] ?? 'en_attente') === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                            <option value="confirmee" <?php echo ($commande_data['statut'] ?? '') === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                            <option value="preparation" <?php echo ($commande_data['statut'] ?? '') === 'preparation' ? 'selected' : ''; ?>>En préparation</option>
                                            <option value="expediee" <?php echo ($commande_data['statut'] ?? '') === 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                            <option value="livree" <?php echo ($commande_data['statut'] ?? '') === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                            <option value="annulee" <?php echo ($commande_data['statut'] ?? '') === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Mode de Paiement</label>
                                        <select class="form-select" name="mode_paiement">
                                            <option value="espece" <?php echo ($commande_data['mode_paiement'] ?? 'espece') === 'espece' ? 'selected' : ''; ?>>Espèce</option>
                                            <option value="mobile" <?php echo ($commande_data['mode_paiement'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile Money</option>
                                            <option value="virement" <?php echo ($commande_data['mode_paiement'] ?? '') === 'virement' ? 'selected' : ''; ?>>Virement</option>
                                            <option value="cheque" <?php echo ($commande_data['mode_paiement'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Chèque</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Statut Paiement</label>
                                        <select class="form-select" name="statut_paiement">
                                            <option value="en_attente" <?php echo ($commande_data['statut_paiement'] ?? 'en_attente') === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                            <option value="partiel" <?php echo ($commande_data['statut_paiement'] ?? '') === 'partiel' ? 'selected' : ''; ?>>Partiel</option>
                                            <option value="paye" <?php echo ($commande_data['statut_paiement'] ?? '') === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adresse de Livraison</label>
                                <textarea class="form-control" name="adresse_livraison" rows="2"><?php echo htmlspecialchars($commande_data['adresse_livraison'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($commande_data['notes'] ?? ''); ?></textarea>
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
                
            <?php elseif ($action === 'view' && $id_commande): ?>
                <!-- Vue détaillée de la commande -->
                <?php
                $stmt = $pdo->prepare("
                    SELECT c.*, CONCAT(m.nom, ' ', m.prenom) as client_nom, 
                           m.telephone, m.email, m.adresse as client_adresse
                    FROM commandes c 
                    LEFT JOIN membres m ON c.client_id = m.id_membre
                    WHERE c.id_commande = ?
                ");
                $stmt->execute([$id_commande]);
                $commande_detail = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Récupérer les détails de la commande
                $details_stmt = $pdo->prepare("
                    SELECT dc.*, p.nom_produit, p.unite_mesure
                    FROM details_commandes dc
                    LEFT JOIN produits p ON dc.id_produit = p.id_produit
                    WHERE dc.id_commande = ?
                ");
                $details_stmt->execute([$id_commande]);
                $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Commande <?php echo htmlspecialchars($commande_detail['numero_commande']); ?></h5>
                        <div>
                            <a href="?action=edit&id=<?php echo $id_commande; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <a href="?action=list" class="btn btn-secondary btn-sm">Retour</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informations Client</h6>
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($commande_detail['client_nom']); ?></p>
                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($commande_detail['telephone'] ?? 'N/A'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($commande_detail['email'] ?? 'N/A'); ?></p>
                                
                                <h6 class="mt-4">Livraison</h6>
                                <p><strong>Adresse:</strong><br><?php echo nl2br(htmlspecialchars($commande_detail['adresse_livraison'] ?? $commande_detail['client_adresse'] ?? 'N/A')); ?></p>
                                <p><strong>Date prévue:</strong> <?php echo $commande_detail['date_livraison'] ? date('d/m/Y', strtotime($commande_detail['date_livraison'])) : 'Non définie'; ?></p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Détails de la Commande</h6>
                                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($commande_detail['date_commande'])); ?></p>
                                <p><strong>Statut:</strong> 
                                    <?php 
                                    $badge_colors = [
                                        'en_attente' => 'bg-warning',
                                        'confirmee' => 'bg-primary',
                                        'preparation' => 'bg-info',
                                        'expediee' => 'bg-success',
                                        'livree' => 'bg-success',
                                        'annulee' => 'bg-danger'
                                    ];
                                    $badge_class = $badge_colors[$commande_detail['statut']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $commande_detail['statut'])); ?></span>
                                </p>
                                <p><strong>Paiement:</strong> 
                                    <?php 
                                    $payment_colors = [
                                        'en_attente' => 'bg-warning',
                                        'partiel' => 'bg-info',
                                        'paye' => 'bg-success'
                                    ];
                                    $payment_class = $payment_colors[$commande_detail['statut_paiement']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $payment_class; ?>"><?php echo ucfirst($commande_detail['statut_paiement']); ?></span>
                                    (<?php echo ucfirst($commande_detail['mode_paiement']); ?>)
                                </p>
                                
                                <?php if ($commande_detail['notes']): ?>
                                <h6 class="mt-3">Notes</h6>
                                <p><?php echo nl2br(htmlspecialchars($commande_detail['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($details): ?>
                        <hr>
                        <h6>Détails des Produits</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité</th>
                                        <th>Prix Unitaire</th>
                                        <th>Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_produits = 0;
                                    foreach ($details as $detail): 
                                        $total_produits += $detail['sous_total'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detail['nom_produit']); ?></td>
                                        <td><?php echo $detail['quantite']; ?> <?php echo htmlspecialchars($detail['unite_mesure']); ?></td>
                                        <td><?php echo number_format($detail['prix_unitaire'], 0, ',', ' '); ?> BIF</td>
                                        <td><?php echo number_format($detail['sous_total'], 0, ',', ' '); ?> BIF</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"><strong>Sous-total produits:</strong></td>
                                        <td><strong><?php echo number_format($total_produits, 0, ',', ' '); ?> BIF</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"><strong>Frais de livraison:</strong></td>
                                        <td><strong><?php echo number_format($commande_detail['frais_livraison'], 0, ',', ' '); ?> BIF</strong></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td colspan="3"><strong>TOTAL:</strong></td>
                                        <td><strong><?php echo number_format($commande_detail['montant_total'] + $commande_detail['frais_livraison'], 0, ',', ' '); ?> BIF</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>
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
                Êtes-vous sûr de vouloir supprimer cette commande ? Cette action supprimera aussi tous les détails associés et est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_commande" id="delete_id">
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