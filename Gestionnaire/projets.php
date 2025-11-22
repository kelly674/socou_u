<?php
$page_title = "Gestion des Projets Sociaux";
include '../config/config.php'; 
include '../includes/functions.php';
requireRole('gestionnaire');
require_once '../includes/header.php';

// Traitement des actions
$action = $_GET['action'] ?? 'list';
$id_projet = $_GET['id'] ?? null;
$message = '';
    $pdo = getConnection();
// Actions CRUD
if ($_POST) {
    try {

        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO projets_sociaux (nom_projet, description, objectif, resultats_attendus, date_debut, date_fin, budget_previsto, responsable_id, beneficiaires_cibles, zone_intervention, partenaires, indicateurs_succes, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nom_projet'],
                    $_POST['description'],
                    $_POST['objectif'],
                    $_POST['resultats_attendus'],
                    $_POST['date_debut'],
                    $_POST['date_fin'] ?: null,
                    $_POST['budget_previsto'],
                    $_SESSION['user_id'],
                    $_POST['beneficiaires_cibles'],
                    $_POST['zone_intervention'],
                    $_POST['partenaires'],
                    $_POST['indicateurs_succes'],
                    $_POST['statut']
                ]);
                $message = '<div class="alert alert-success">Projet ajouté avec succès!</div>';
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE projets_sociaux SET nom_projet=?, description=?, objectif=?, resultats_attendus=?, date_debut=?, date_fin=?, budget_previsto=?, budget_depense=?, beneficiaires_cibles=?, zone_intervention=?, partenaires=?, indicateurs_succes=?, statut=? WHERE id_projet=?");
                $stmt->execute([
                    $_POST['nom_projet'],
                    $_POST['description'],
                    $_POST['objectif'],
                    $_POST['resultats_attendus'],
                    $_POST['date_debut'],
                    $_POST['date_fin'] ?: null,
                    $_POST['budget_previsto'],
                    $_POST['budget_depense'],
                    $_POST['beneficiaires_cibles'],
                    $_POST['zone_intervention'],
                    $_POST['partenaires'],
                    $_POST['indicateurs_succes'],
                    $_POST['statut'],
                    $_POST['id_projet']
                ]);
                $message = '<div class="alert alert-success">Projet modifié avec succès!</div>';
                break;
                
            case 'delete':
                // Supprimer les bénéficiaires d'abord
                $stmt = $pdo->prepare("DELETE FROM beneficiaires_projets WHERE id_projet = ?");
                $stmt->execute([$_POST['id_projet']]);
                
                // Puis le projet
                $stmt = $pdo->prepare("DELETE FROM projets_sociaux WHERE id_projet = ?");
                $stmt->execute([$_POST['id_projet']]);
                $message = '<div class="alert alert-success">Projet supprimé avec succès!</div>';
                break;
        }
        $action = 'list';
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération des données pour édition
$projet_data = [];
if ($action === 'edit' && $id_projet) {
    $stmt = $pdo->prepare("SELECT * FROM projets_sociaux WHERE id_projet = ?");
    $stmt->execute([$id_projet]);
    $projet_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Liste des projets avec pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_statut = $_GET['filter_statut'] ?? '';
$filter_zone = $_GET['filter_zone'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(ps.nom_projet LIKE ? OR ps.zone_intervention LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_statut) {
    $where_conditions[] = "ps.statut = ?";
    $params[] = $filter_statut;
}

if ($filter_zone) {
    $where_conditions[] = "ps.zone_intervention LIKE ?";
    $params[] = "%$filter_zone%";
}

$where_clause = '';
if ($where_conditions) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM projets_sociaux ps $where_clause");
$count_stmt->execute($params);
$total_projets = $count_stmt->fetchColumn();
$total_pages = ceil($total_projets / $limit);

// Récupérer les projets avec jointures
$projets_stmt = $pdo->prepare("
    SELECT ps.*, CONCAT(u.username) as responsable_nom,
           (SELECT COUNT(*) FROM beneficiaires_projets bp WHERE bp.id_projet = ps.id_projet AND bp.statut_participation = 'actif') as beneficiaires_actifs
    FROM projets_sociaux ps 
    LEFT JOIN utilisateurs u ON ps.responsable_id = u.id_utilisateur
    $where_clause 
    ORDER BY ps.date_creation DESC 
    LIMIT $limit OFFSET $offset
");
$projets_stmt->execute($params);
$projets = $projets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
        SUM(budget_previsto) as budget_total,
        SUM(budget_depense) as budget_depense_total
    FROM projets_sociaux
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
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/membres.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/produits.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/commandes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gestionnaire/projets.php" class="list-group-item list-group-item-action active">
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
                    <h2>Gestion des Projets Sociaux</h2>
                    <p class="text-muted">Total: <?php echo $total_projets; ?> projets</p>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau Projet
                </a>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total']; ?></h4>
                                <small>Total Projets</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['en_cours']; ?></h4>
                                <small>En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['termines']; ?></h4>
                                <small>Terminés</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo number_format($stats['budget_total'], 0, ',', ' '); ?> BIF</h4>
                                <small>Budget Total</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher un projet..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="filter_statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="planifie" <?php echo $filter_statut === 'planifie' ? 'selected' : ''; ?>>Planifié</option>
                                    <option value="en_cours" <?php echo $filter_statut === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="termine" <?php echo $filter_statut === 'termine' ? 'selected' : ''; ?>>Terminé</option>
                                    <option value="suspendu" <?php echo $filter_statut === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="filter_zone" placeholder="Zone d'intervention" value="<?php echo htmlspecialchars($filter_zone); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des projets -->
                <div class="row">
                    <?php foreach ($projets as $projet): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($projet['nom_projet']); ?></h6>
                                <?php 
                                $badge_colors = [
                                    'planifie' => 'bg-secondary',
                                    'en_cours' => 'bg-primary',
                                    'termine' => 'bg-success',
                                    'suspendu' => 'bg-danger'
                                ];
                                $badge_class = $badge_colors[$projet['statut']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($projet['statut']); ?></span>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($projet['description'], 0, 100)) . '...'; ?></p>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Début:</small><br>
                                        <strong><?php echo date('d/m/Y', strtotime($projet['date_debut'])); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Fin prévue:</small><br>
                                        <strong><?php echo $projet['date_fin'] ? date('d/m/Y', strtotime($projet['date_fin'])) : 'Non définie'; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Budget:</small><br>
                                        <strong><?php echo number_format($projet['budget_previsto'], 0, ',', ' '); ?> BIF</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Dépenses:</small><br>
                                        <strong class="<?php echo $projet['budget_depense'] > $projet['budget_previsto'] ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo number_format($projet['budget_depense'], 0, ',', ' '); ?> BIF
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="progress mb-3" style="height: 8px;">
                                    <?php 
                                    $pourcentage_budget = $projet['budget_previsto'] > 0 ? ($projet['budget_depense'] / $projet['budget_previsto']) * 100 : 0;
                                    $progress_class = $pourcentage_budget > 100 ? 'bg-danger' : ($pourcentage_budget > 80 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo min($pourcentage_budget, 100); ?>%"></div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <h5 class="text-primary"><?php echo $projet['beneficiaires_actifs'] ?: 0; ?></h5>
                                        <small>Bénéficiaires actifs</small>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="text-info"><?php echo $projet['beneficiaires_cibles']; ?></h5>
                                        <small>Cible</small>
                                    </div>
                                </div>
                                
                                <p class="mb-2"><i class="fas fa-map-marker-alt text-muted me-1"></i> <?php echo htmlspecialchars($projet['zone_intervention']); ?></p>
                                <p class="mb-0"><i class="fas fa-user text-muted me-1"></i> <?php echo htmlspecialchars($projet['responsable_nom'] ?? 'Non assigné'); ?></p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <a href="?action=view&id=<?php echo $projet['id_projet']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="?action=edit&id=<?php echo $projet['id_projet']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $projet['id_projet']; ?>)">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_statut ? '&filter_statut=' . urlencode($filter_statut) : ''; ?><?php echo $filter_zone ? '&filter_zone=' . urlencode($filter_zone) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/modification -->
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo $action === 'edit' ? 'Modifier le Projet' : 'Nouveau Projet'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id_projet" value="<?php echo $id_projet; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Nom du Projet *</label>
                                        <input type="text" class="form-control" name="nom_projet" required value="<?php echo htmlspecialchars($projet_data['nom_projet'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" name="statut">
                                            <option value="planifie" <?php echo ($projet_data['statut'] ?? 'planifie') === 'planifie' ? 'selected' : ''; ?>>Planifié</option>
                                            <option value="en_cours" <?php echo ($projet_data['statut'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                            <option value="termine" <?php echo ($projet_data['statut'] ?? '') === 'termine' ? 'selected' : ''; ?>>Terminé</option>
                                            <option value="suspendu" <?php echo ($projet_data['statut'] ?? '') === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($projet_data['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Objectif</label>
                                <textarea class="form-control" name="objectif" rows="3"><?php echo htmlspecialchars($projet_data['objectif'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Résultats Attendus</label>
                                <textarea class="form-control" name="resultats_attendus" rows="3"><?php echo htmlspecialchars($projet_data['resultats_attendus'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Début *</label>
                                        <input type="date" class="form-control" name="date_debut" required value="<?php echo $projet_data['date_debut'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Fin</label>
                                        <input type="date" class="form-control" name="date_fin" value="<?php echo $projet_data['date_fin'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Budget Prévisible (BIF) *</label>
                                        <input type="number" step="0.01" class="form-control" name="budget_previsto" required value="<?php echo $projet_data['budget_previsto'] ?? ''; ?>">
                                    </div>
                                </div>
                                <?php if ($action === 'edit'): ?>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Budget Dépensé (BIF)</label>
                                        <input type="number" step="0.01" class="form-control" name="budget_depense" value="<?php echo $projet_data['budget_depense'] ?? '0'; ?>">
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Bénéficiaires Ciblés</label>
                                        <input type="number" class="form-control" name="beneficiaires_cibles" value="<?php echo $projet_data['beneficiaires_cibles'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Zone d'Intervention *</label>
                                <input type="text" class="form-control" name="zone_intervention" required value="<?php echo htmlspecialchars($projet_data['zone_intervention'] ?? ''); ?>" placeholder="Ex: Province de Bujumbura, Commune Mukike">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Partenaires</label>
                                <textarea class="form-control" name="partenaires" rows="2" placeholder="Organisations partenaires, bailleurs..."><?php echo htmlspecialchars($projet_data['partenaires'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Indicateurs de Succès</label>
                                <textarea class="form-control" name="indicateurs_succes" rows="3" placeholder="Comment mesurer le succès du projet..."><?php echo htmlspecialchars($projet_data['indicateurs_succes'] ?? ''); ?></textarea>
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
                
            <?php elseif ($action === 'view' && $id_projet): ?>
                <!-- Vue détaillée du projet -->
                <?php
                $stmt = $pdo->prepare("SELECT ps.*, CONCAT(u.username) as responsable_nom FROM projets_sociaux ps LEFT JOIN utilisateurs u ON ps.responsable_id = u.id_utilisateur WHERE ps.id_projet = ?");
                $stmt->execute([$id_projet]);
                $projet_detail = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Récupérer les bénéficiaires
                $beneficiaires_stmt = $pdo->prepare("
                    SELECT bp.*, CONCAT(m.nom, ' ', m.prenom) as nom_beneficiaire, m.telephone
                    FROM beneficiaires_projets bp
                    LEFT JOIN membres m ON bp.id_membre = m.id_membre
                    WHERE bp.id_projet = ?
                    ORDER BY bp.date_inscription DESC
                ");
                $beneficiaires_stmt->execute([$id_projet]);
                $beneficiaires = $beneficiaires_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?php echo htmlspecialchars($projet_detail['nom_projet']); ?></h5>
                        <div>
                            <a href="?action=edit&id=<?php echo $id_projet; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <a href="?action=list" class="btn btn-secondary btn-sm">Retour</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Description</h6>
                                <p><?php echo nl2br(htmlspecialchars($projet_detail['description'])); ?></p>
                                
                                <?php if ($projet_detail['objectif']): ?>
                                <h6>Objectif</h6>
                                <p><?php echo nl2br(htmlspecialchars($projet_detail['objectif'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($projet_detail['resultats_attendus']): ?>
                                <h6>Résultats Attendus</h6>
                                <p><?php echo nl2br(htmlspecialchars($projet_detail['resultats_attendus'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($projet_detail['indicateurs_succes']): ?>
                                <h6>Indicateurs de Succès</h6>
                                <p><?php echo nl2br(htmlspecialchars($projet_detail['indicateurs_succes'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Informations Générales</h6>
                                        <p><strong>Statut:</strong> 
                                            <?php 
                                            $badge_colors = [
                                                'planifie' => 'bg-secondary',
                                                'en_cours' => 'bg-primary',
                                                'termine' => 'bg-success',
                                                'suspendu' => 'bg-danger'
                                            ];
                                            $badge_class = $badge_colors[$projet_detail['statut']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($projet_detail['statut']); ?></span>
                                        </p>
                                        <p><strong>Responsable:</strong> <?php echo htmlspecialchars($projet_detail['responsable_nom'] ?? 'Non assigné'); ?></p>
                                        <p><strong>Zone:</strong> <?php echo htmlspecialchars($projet_detail['zone_intervention']); ?></p>
                                        <p><strong>Début:</strong> <?php echo date('d/m/Y', strtotime($projet_detail['date_debut'])); ?></p>
                                        <p><strong>Fin prévue:</strong> <?php echo $projet_detail['date_fin'] ? date('d/m/Y', strtotime($projet_detail['date_fin'])) : 'Non définie'; ?></p>
                                        
                                        <hr>
                                        <h6>Budget</h6>
                                        <p><strong>Prévu:</strong> <?php echo number_format($projet_detail['budget_previsto'], 0, ',', ' '); ?> BIF</p>
                                        <p><strong>Dépensé:</strong> 
                                            <span class="<?php echo $projet_detail['budget_depense'] > $projet_detail['budget_previsto'] ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo number_format($projet_detail['budget_depense'], 0, ',', ' '); ?> BIF
                                            </span>
                                        </p>
                                        <p><strong>Restant:</strong> <?php echo number_format($projet_detail['budget_previsto'] - $projet_detail['budget_depense'], 0, ',', ' '); ?> BIF</p>
                                        
                                        <div class="progress mb-2" style="height: 10px;">
                                            <?php 
                                            $pourcentage_budget = $projet_detail['budget_previsto'] > 0 ? ($projet_detail['budget_depense'] / $projet_detail['budget_previsto']) * 100 : 0;
                                            $progress_class = $pourcentage_budget > 100 ? 'bg-danger' : ($pourcentage_budget > 80 ? 'bg-warning' : 'bg-success');
                                            ?>
                                            <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo min($pourcentage_budget, 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo round($pourcentage_budget, 1); ?>% du budget utilisé</small>
                                        
                                        <?php if ($projet_detail['partenaires']): ?>
                                        <hr>
                                        <h6>Partenaires</h6>
                                        <p><?php echo nl2br(htmlspecialchars($projet_detail['partenaires'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($beneficiaires): ?>
                        <hr>
                        <h6>Bénéficiaires (<?php echo count($beneficiaires); ?>)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Téléphone</th>
                                        <th>Inscription</th>
                                        <th>Statut</th>
                                        <th>Observations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($beneficiaires as $beneficiaire): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($beneficiaire['nom_beneficiaire'] ?? 'Membre supprimé'); ?></td>
                                        <td><?php echo htmlspecialchars($beneficiaire['telephone'] ?? ''); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($beneficiaire['date_inscription'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_colors = [
                                                'inscrit' => 'bg-secondary',
                                                'actif' => 'bg-success',
                                                'termine' => 'bg-info',
                                                'abandonne' => 'bg-danger'
                                            ];
                                            $status_class = $status_colors[$beneficiaire['statut_participation']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($beneficiaire['statut_participation']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($beneficiaire['observations'] ?? '', 0, 50)) . (strlen($beneficiaire['observations'] ?? '') > 50 ? '...' : ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
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
                Êtes-vous sûr de vouloir supprimer ce projet ? Cette action supprimera aussi tous les bénéficiaires associés et est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_projet" id="delete_id">
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
                                                