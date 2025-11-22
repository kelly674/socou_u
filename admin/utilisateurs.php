<?php
$page_title = "Gestion des Utilisateurs";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

// Traitement des actions
$action = $_GET['action'] ?? 'list';
$id_utilisateur = $_GET['id'] ?? null;
$message = '';
 $pdo = getConnection();
// Actions CRUD
if ($_POST) {
    try {
        $pdo->beginTransaction();
        
        switch ($_POST['action']) {
            case 'add':
                // 1. Créer d'abord le membre
                $code_membre = 'MBR' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmt_membre = $pdo->prepare("INSERT INTO membres (code_membre, nom, prenom, email, telephone, adresse, province, commune, zone, date_naissance, genre, date_adhesion, type_membre, specialisation, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_membre->execute([
                    $code_membre,
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'],
                    $_POST['adresse'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['commune'] ?: null,
                    $_POST['zone'] ?: null,
                    $_POST['date_naissance'] ?: null,
                    $_POST['genre'] ?: null,
                    $_POST['date_adhesion'] ?: date('Y-m-d'),
                    $_POST['type_membre'],
                    $_POST['specialisation'] ?: null,
                    'actif'
                ]);
                
                $id_membre = $pdo->lastInsertId();
                
                // 2. Créer l'utilisateur
                $salt = bin2hex(random_bytes(16));
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt_user = $pdo->prepare("INSERT INTO utilisateurs (id_membre, username, password, salt, role, statut) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_user->execute([
                    $id_membre,
                    $_POST['username'],
                    $password_hash,
                    $salt,
                    $_POST['role'],
                    $_POST['statut']
                ]);
                
                $pdo->commit();
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Utilisateur et membre créés avec succès! Code membre: <strong>' . $code_membre . '</strong></div>';
                break;
                
            case 'edit':
                // Mettre à jour le membre
                $stmt_membre = $pdo->prepare("UPDATE membres SET nom=?, prenom=?, email=?, telephone=?, adresse=?, province=?, commune=?, zone=?, date_naissance=?, genre=?, type_membre=?, specialisation=? WHERE id_membre=?");
                $stmt_membre->execute([
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'] ?: null,
                    $_POST['telephone'],
                    $_POST['adresse'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['commune'] ?: null,
                    $_POST['zone'] ?: null,
                    $_POST['date_naissance'] ?: null,
                    $_POST['genre'] ?: null,
                    $_POST['type_membre'],
                    $_POST['specialisation'] ?: null,
                    $_POST['id_membre']
                ]);
                
                // Mettre à jour l'utilisateur
                if (!empty($_POST['password'])) {
                    $salt = bin2hex(random_bytes(16));
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt_user = $pdo->prepare("UPDATE utilisateurs SET username=?, password=?, salt=?, role=?, statut=? WHERE id_utilisateur=?");
                    $stmt_user->execute([
                        $_POST['username'],
                        $password_hash,
                        $salt,
                        $_POST['role'],
                        $_POST['statut'],
                        $_POST['id_utilisateur']
                    ]);
                } else {
                    $stmt_user = $pdo->prepare("UPDATE utilisateurs SET username=?, role=?, statut=? WHERE id_utilisateur=?");
                    $stmt_user->execute([
                        $_POST['username'],
                        $_POST['role'],
                        $_POST['statut'],
                        $_POST['id_utilisateur']
                    ]);
                }
                
                $pdo->commit();
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Utilisateur modifié avec succès!</div>';
                break;
                
            case 'delete':
                // Supprimer l'utilisateur et le membre associé
                $stmt_user = $pdo->prepare("SELECT id_membre FROM utilisateurs WHERE id_utilisateur = ?");
                $stmt_user->execute([$_POST['id_utilisateur']]);
                $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data) {
                    $stmt_del_user = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
                    $stmt_del_user->execute([$_POST['id_utilisateur']]);
                    
                    if ($user_data['id_membre']) {
                        $stmt_del_membre = $pdo->prepare("DELETE FROM membres WHERE id_membre = ?");
                        $stmt_del_membre->execute([$user_data['id_membre']]);
                    }
                }
                
                $pdo->commit();
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Utilisateur et membre supprimés avec succès!</div>';
                break;
        }
        $action = 'list'; // Retour à la liste
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération des données pour édition
$user_data = [];
if ($action === 'edit' && $id_utilisateur) {
    $stmt = $pdo->prepare("SELECT u.*, m.* FROM utilisateurs u LEFT JOIN membres m ON u.id_membre = m.id_membre WHERE u.id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Liste des utilisateurs avec pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR m.nom LIKE ? OR m.prenom LIKE ? OR m.code_membre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_role) {
    $where_conditions[] = "u.role = ?";
    $params[] = $filter_role;
}

if ($filter_statut) {
    $where_conditions[] = "u.statut = ?";
    $params[] = $filter_statut;
}

$where_clause = '';
if ($where_conditions) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$pdo = getConnection();
// Compter le total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs u LEFT JOIN membres m ON u.id_membre = m.id_membre $where_clause");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Récupérer les utilisateurs
$users_stmt = $pdo->prepare("SELECT u.*, m.nom, m.prenom, m.code_membre, m.email as membre_email, m.telephone, m.type_membre FROM utilisateurs u LEFT JOIN membres m ON u.id_membre = m.id_membre $where_clause ORDER BY u.id_utilisateur DESC LIMIT $limit OFFSET $offset");
$users_stmt->execute($params);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <a href="<?php echo SITE_URL; ?>/admin/utilisateurs.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users-cog me-2"></i>Utilisateurs
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/membres.php" class="list-group-item list-group-item-action">
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
                    <h2><i class="fas fa-users-cog me-2"></i>Gestion des Utilisateurs</h2>
                    <p class="text-muted">Total: <?php echo $total_users; ?> utilisateurs</p>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur
                </a>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <?php
                    $stats = [
                        ['role' => 'admin', 'label' => 'Administrateurs', 'icon' => 'fa-user-shield', 'color' => 'danger'],
                        ['role' => 'gestionnaire', 'label' => 'Gestionnaires', 'icon' => 'fa-user-cog', 'color' => 'warning'],
                        ['role' => 'membre', 'label' => 'Membres', 'icon' => 'fa-user', 'color' => 'success']
                    ];
                    
                    foreach ($stats as $stat) {
                        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = ? AND statut = 'actif'");
                        $count_stmt->execute([$stat['role']]);
                        $count = $count_stmt->fetchColumn();
                    ?>
                    <div class="col-md-4">
                        <div class="card bg-<?php echo $stat['color']; ?> text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas <?php echo $stat['icon']; ?> fa-2x"></i>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo $count; ?></div>
                                        <div class="small"><?php echo $stat['label']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                
                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="filter_role" class="form-select">
                                    <option value="">Tous les rôles</option>
                                    <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="gestionnaire" <?php echo $filter_role === 'gestionnaire' ? 'selected' : ''; ?>>Gestionnaire</option>
                                    <option value="membre" <?php echo $filter_role === 'membre' ? 'selected' : ''; ?>>Membre</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter_statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="actif" <?php echo $filter_statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactif" <?php echo $filter_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des utilisateurs -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilisateur</th>
                                        <th>Membre Associé</th>
                                        <th>Rôle</th>
                                        <th>Contact</th>
                                        <th>Statut</th>
                                        <th>Dernière Connexion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id_utilisateur']; ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $user['id_utilisateur']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['code_membre']): ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['code_membre']); ?></small>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Aucun membre associé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $role_badges = [
                                                'admin' => ['class' => 'bg-danger', 'icon' => 'fa-user-shield'],
                                                'gestionnaire' => ['class' => 'bg-warning', 'icon' => 'fa-user-cog'],
                                                'membre' => ['class' => 'bg-success', 'icon' => 'fa-user']
                                            ];
                                            $badge = $role_badges[$user['role']] ?? ['class' => 'bg-secondary', 'icon' => 'fa-user'];
                                            ?>
                                            <span class="badge <?php echo $badge['class']; ?>">
                                                <i class="fas <?php echo $badge['icon']; ?> me-1"></i><?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['telephone'] || $user['membre_email']): ?>
                                                <small>
                                                    <?php if ($user['telephone']): ?>
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['telephone']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($user['membre_email']): ?>
                                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['membre_email']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = $user['statut'] === 'actif' ? 'bg-success' : 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <i class="fas fa-circle me-1"></i><?php echo ucfirst($user['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['derniere_connexion']): ?>
                                                <small><?php echo date('d/m/Y H:i', strtotime($user['derniere_connexion'])); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Jamais connecté</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=edit&id=<?php echo $user['id_utilisateur']; ?>" 
                                                   class="btn btn-outline-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $user['id_utilisateur']; ?>)" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_role ? '&filter_role=' . urlencode($filter_role) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_role ? '&filter_role=' . urlencode($filter_role) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_role ? '&filter_role=' . urlencode($filter_role) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/modification -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas <?php echo $action === 'edit' ? 'fa-edit' : 'fa-user-plus'; ?> me-2"></i>
                            <?php echo $action === 'edit' ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id_utilisateur" value="<?php echo $id_utilisateur; ?>">
                                <input type="hidden" name="id_membre" value="<?php echo $user_data['id_membre']; ?>">
                            <?php endif; ?>
                            
                            <!-- Informations de connexion -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-key me-2"></i>Informations de Connexion</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nom d'utilisateur *</label>
                                                <input type="text" class="form-control" name="username" required 
                                                       value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>">
                                                <div class="invalid-feedback">Veuillez saisir un nom d'utilisateur</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Mot de passe <?php echo $action === 'edit' ? '(laisser vide si inchangé)' : '*'; ?>
                                                </label>
                                                <input type="password" class="form-control" name="password" 
                                                       <?php echo $action === 'add' ? 'required' : ''; ?>
                                                       minlength="6">
                                                <div class="invalid-feedback">Le mot de passe doit faire au moins 6 caractères</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Rôle *</label>
                                                <select class="form-select" name="role" required>
                                                    <option value="">Sélectionnez un rôle</option>
                                                    <option value="admin" <?php echo ($user_data['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                                                        <i class="fas fa-user-shield"></i> Administrateur
                                                    </option>
                                                    <option value="gestionnaire" <?php echo ($user_data['role'] ?? '') === 'gestionnaire' ? 'selected' : ''; ?>>
                                                        <i class="fas fa-user-cog"></i> Gestionnaire
                                                    </option>
                                                    <option value="membre" <?php echo ($user_data['role'] ?? 'membre') === 'membre' ? 'selected' : ''; ?>>
                                                        <i class="fas fa-user"></i> Membre
                                                    </option>
                                                </select>
                                                <div class="invalid-feedback">Veuillez sélectionner un rôle</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Statut</label>
                                                <select class="form-select" name="statut">
                                                    <option value="actif" <?php echo ($user_data['statut'] ?? 'actif') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                                    <option value="inactif" <?php echo ($user_data['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informations personnelles du membre -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informations du Membre</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nom *</label>
                                                <input type="text" class="form-control" name="nom" required 
                                                       value="<?php echo htmlspecialchars($user_data['nom'] ?? ''); ?>">
                                                <div class="invalid-feedback">Veuillez saisir le nom</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Prénom *</label>
                                                <input type="text" class="form-control" name="prenom" required 
                                                       value="<?php echo htmlspecialchars($user_data['prenom'] ?? ''); ?>">
                                                <div class="invalid-feedback">Veuillez saisir le prénom</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Téléphone *</label>
                                                <input type="text" class="form-control" name="telephone" required 
                                                       value="<?php echo htmlspecialchars($user_data['telephone'] ?? ''); ?>">
                                                <div class="invalid-feedback">Veuillez saisir le téléphone</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" name="province" 
                                                       value="<?php echo htmlspecialchars($user_data['province'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Commune</label>
                                                <input type="text" class="form-control" name="commune" 
                                                       value="<?php echo htmlspecialchars($user_data['commune'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Zone</label>
                                                <input type="text" class="form-control" name="zone" 
                                                       value="<?php echo htmlspecialchars($user_data['zone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Adresse</label>
                                        <textarea class="form-control" name="adresse" rows="2"><?php echo htmlspecialchars($user_data['adresse'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Date de Naissance</label>
                                                <input type="date" class="form-control" name="date_naissance" 
                                                       value="<?php echo $user_data['date_naissance'] ?? ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Genre</label>
                                                <select class="form-select" name="genre">
                                                    <option value="">Sélectionnez</option>
                                                    <option value="M" <?php echo ($user_data['genre'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                                    <option value="F" <?php echo ($user_data['genre'] ?? '') === 'F' ? 'selected' : ''; ?>>Féminin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Date d'Adhésion</label>
                                                <input type="date" class="form-control" name="date_adhesion" 
                                                       value="<?php echo $user_data['date_adhesion'] ?? date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Type de Membre *</label>
                                                <select class="form-select" name="type_membre" required>
                                                    <option value="">Sélectionnez</option>
                                                    <option value="producteur" <?php echo ($user_data['type_membre'] ?? '') === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                                                    <option value="transformateur" <?php echo ($user_data['type_membre'] ?? '') === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                                                    <option value="commercial" <?php echo ($user_data['type_membre'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                                    <option value="administratif" <?php echo ($user_data['type_membre'] ?? '') === 'administratif' ? 'selected' : ''; ?>>Administratif</option>
                                                </select>
                                                <div class="invalid-feedback">Veuillez sélectionner un type de membre</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Spécialisation</label>
                                        <input type="text" class="form-control" name="specialisation" 
                                               placeholder="Ex: Culture de maïs, Élevage de volailles, etc."
                                               value="<?php echo htmlspecialchars($user_data['specialisation'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $action === 'edit' ? 'Modifier' : 'Créer'; ?> l'Utilisateur
                                </button>
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h6>Attention : Cette action est irréversible !</h6>
                </div>
                <p class="text-center">
                    Êtes-vous sûr de vouloir supprimer cet utilisateur ?<br>
                    <strong>Le membre associé sera également supprimé.</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_utilisateur" id="delete_id">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Styles personnalisés -->
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-group-sm .btn {
    --bs-btn-padding-y: 0.25rem;
    --bs-btn-padding-x: 0.5rem;
    --bs-btn-font-size: 0.875rem;
}

.badge {
    font-size: 0.75em;
}

.form-floating input:focus,
.form-floating textarea:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.needs-validation .form-control:invalid {
    border-color: #dc3545;
}

.needs-validation .form-control:valid {
    border-color: #198754;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.125rem 0.25rem;
    }
}

.list-group-item-action.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.pagination .page-link {
    color: var(--bs-primary);
}

.pagination .page-item.active .page-link {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.alert {
    border: none;
    border-radius: 0.5rem;
}
</style>

<!-- Scripts -->
<script>
// Fonction de confirmation de suppression
function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Validation du formulaire
(function() {
    'use strict';
    
    // Validation Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Validation personnalisée du nom d'utilisateur
    const usernameInput = document.querySelector('input[name="username"]');
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            const username = this.value;
            const regex = /^[a-zA-Z0-9_]{3,20}$/;
            
            if (!regex.test(username)) {
                this.setCustomValidity('Le nom d\'utilisateur doit contenir 3-20 caractères (lettres, chiffres, underscore uniquement)');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Validation du mot de passe
    const passwordInput = document.querySelector('input[name="password"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const minLength = 6;
            
            if (password.length > 0 && password.length < minLength) {
                this.setCustomValidity(`Le mot de passe doit contenir au moins ${minLength} caractères`);
            } else {
                this.setCustomValidity('');
            }
        });
    }
})();

// Auto-hide alerts après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.parentNode) {
                alert.classList.add('fade');
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 150);
            }
        }, 5000);
    });
});

// Amélioration UX - Confirmation avant suppression avec double-clic
let deleteClicked = false;
function confirmDelete(id) {
    if (!deleteClicked) {
        deleteClicked = true;
        document.querySelector(`button[onclick="confirmDelete(${id})"]`).innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        document.querySelector(`button[onclick="confirmDelete(${id})"]`).classList.remove('btn-outline-danger');
        document.querySelector(`button[onclick="confirmDelete(${id})"]`).classList.add('btn-danger');
        
        setTimeout(() => {
            deleteClicked = false;
            document.querySelector(`button[onclick="confirmDelete(${id})"]`).innerHTML = '<i class="fas fa-trash"></i>';
            document.querySelector(`button[onclick="confirmDelete(${id})"]`).classList.remove('btn-danger');
            document.querySelector(`button[onclick="confirmDelete(${id})"]`).classList.add('btn-outline-danger');
        }, 3000);
        
        return false;
    } else {
        document.getElementById('delete_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
        deleteClicked = false;
    }
}

// Tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Génération automatique du nom d'utilisateur basé sur le nom/prénom
document.addEventListener('DOMContentLoaded', function() {
    const nomInput = document.querySelector('input[name="nom"]');
    const prenomInput = document.querySelector('input[name="prenom"]');
    const usernameInput = document.querySelector('input[name="username"]');
    
    if (nomInput && prenomInput && usernameInput) {
        function generateUsername() {
            const nom = nomInput.value.trim().toLowerCase().replace(/[^a-z]/g, '');
            const prenom = prenomInput.value.trim().toLowerCase().replace(/[^a-z]/g, '');
            
            if (nom && prenom && !usernameInput.value) {
                const username = prenom + '.' + nom;
                usernameInput.value = username;
                usernameInput.dispatchEvent(new Event('input'));
            }
        }
        
        nomInput.addEventListener('blur', generateUsername);
        prenomInput.addEventListener('blur', generateUsername);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>