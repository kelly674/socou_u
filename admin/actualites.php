<?php
$page_title = "Gestion des Actualités";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id_actualite = $_GET['id'] ?? null;
$message = '';
$error = '';
 $pdo = getConnection();
// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $titre = $_POST['titre'] ?? '';
        $resume = $_POST['resume'] ?? '';
        $contenu = $_POST['contenu'] ?? '';
        $type_actualite = $_POST['type_actualite'] ?? 'nouvelle';
        $date_debut = $_POST['date_debut'] ?? null;
        $date_fin = $_POST['date_fin'] ?? null;
        $lieu = $_POST['lieu'] ?? '';
        $statut = $_POST['statut'] ?? 'brouillon';
        $mots_cles = $_POST['mots_cles'] ?? '';
        $auteur_id = $_SESSION['user_id'];
        
        // Gestion de l'upload d'image
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = '../uploads/actualites/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $image = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $image;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Erreur lors de l'upload de l'image";
                    $image = null;
                }
            } else {
                $error = "Format d'image non autorisé";
            }
        }
        
        if (!$error) {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO actualites (titre, resume, contenu, image, type_actualite, date_debut, date_fin, lieu, auteur_id, statut, mots_cles, date_publication) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $date_publication = ($statut === 'publie') ? date('Y-m-d H:i:s') : null;
                    $stmt->execute([$titre, $resume, $contenu, $image, $type_actualite, $date_debut, $date_fin, $lieu, $auteur_id, $statut, $mots_cles, $date_publication]);
                    $message = "Actualité ajoutée avec succès";
                } else {
                    // Si pas de nouvelle image, conserver l'ancienne
                    if (!$image && $action === 'edit') {
                        $stmt_old = $pdo->prepare("SELECT image FROM actualites WHERE id_actualite = ?");
                        $stmt_old->execute([$id_actualite]);
                        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
                        $image = $old_data['image'];
                    }
                    
                    $stmt = $pdo->prepare("UPDATE actualites SET titre = ?, resume = ?, contenu = ?, image = ?, type_actualite = ?, date_debut = ?, date_fin = ?, lieu = ?, statut = ?, mots_cles = ?, date_publication = ? WHERE id_actualite = ?");
                    $date_publication = ($statut === 'publie') ? date('Y-m-d H:i:s') : null;
                    $stmt->execute([$titre, $resume, $contenu, $image, $type_actualite, $date_debut, $date_fin, $lieu, $statut, $mots_cles, $date_publication, $id_actualite]);
                    $message = "Actualité modifiée avec succès";
                }
                $action = 'list';
            } catch(PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Suppression
if ($action === 'delete' && $id_actualite) {
    try {
        // Récupérer l'image pour la supprimer
        $stmt = $pdo->prepare("SELECT image FROM actualites WHERE id_actualite = ?");
        $stmt->execute([$id_actualite]);
        $actualite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($actualite && $actualite['image']) {
            $image_path = '../uploads/actualites/' . $actualite['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $pdo->prepare("DELETE FROM actualites WHERE id_actualite = ?")->execute([$id_actualite]);
        $message = "Actualité supprimée avec succès";
        $action = 'list';
    } catch(PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupération des données pour édition
$actualite = null;
if (($action === 'edit' || $action === 'view') && $id_actualite) {
    $stmt = $pdo->prepare("SELECT a.*, u.username FROM actualites a LEFT JOIN utilisateurs u ON a.auteur_id = u.id_utilisateur WHERE a.id_actualite = ?");
    $stmt->execute([$id_actualite]);
    $actualite = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-newspaper me-2"></i>Actualités</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="?action=list" class="list-group-item list-group-item-action <?php echo $action === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>Toutes les actualités
                    </a>
                    <a href="?action=add" class="list-group-item list-group-item-action <?php echo $action === 'add' ? 'active' : ''; ?>">
                        <i class="fas fa-plus me-2"></i>Nouvelle actualité
                    </a>
                    <a href="?action=list&filter=publie" class="list-group-item list-group-item-action">
                        <i class="fas fa-globe me-2"></i>Publiées
                    </a>
                    <a href="?action=list&filter=brouillon" class="list-group-item list-group-item-action">
                        <i class="fas fa-edit me-2"></i>Brouillons
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <!-- Liste des actualités -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Actualités</h2>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouvelle actualité
                    </a>
                </div>
                
                <!-- Filtres -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="action" value="list">
                            <div class="col-md-3">
                                <select name="filter" class="form-control">
                                    <option value="">Tous les statuts</option>
                                    <option value="publie" <?php echo ($_GET['filter'] ?? '') === 'publie' ? 'selected' : ''; ?>>Publiées</option>
                                    <option value="brouillon" <?php echo ($_GET['filter'] ?? '') === 'brouillon' ? 'selected' : ''; ?>>Brouillons</option>
                                    <option value="archive" <?php echo ($_GET['filter'] ?? '') === 'archive' ? 'selected' : ''; ?>>Archivées</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-control">
                                    <option value="">Tous les types</option>
                                    <option value="nouvelle" <?php echo ($_GET['type'] ?? '') === 'nouvelle' ? 'selected' : ''; ?>>Nouvelles</option>
                                    <option value="evenement" <?php echo ($_GET['type'] ?? '') === 'evenement' ? 'selected' : ''; ?>>Événements</option>
                                    <option value="annonce" <?php echo ($_GET['type'] ?? '') === 'annonce' ? 'selected' : ''; ?>>Annonces</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Rechercher..." 
                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Type</th>
                                        <th>Auteur</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Vues</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $where_conditions = [];
                                    $params = [];
                                    
                                    if (!empty($_GET['filter'])) {
                                        $where_conditions[] = "a.statut = ?";
                                        $params[] = $_GET['filter'];
                                    }
                                    
                                    if (!empty($_GET['type'])) {
                                        $where_conditions[] = "a.type_actualite = ?";
                                        $params[] = $_GET['type'];
                                    }
                                    
                                    if (!empty($_GET['search'])) {
                                        $where_conditions[] = "(a.titre LIKE ? OR a.contenu LIKE ?)";
                                        $search_term = '%' . $_GET['search'] . '%';
                                        $params[] = $search_term;
                                        $params[] = $search_term;
                                    }
                                    
                                    $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                                    
                                    $stmt = $pdo->prepare("SELECT a.*, u.username FROM actualites a LEFT JOIN utilisateurs u ON a.auteur_id = u.id_utilisateur $where_sql ORDER BY a.date_creation DESC");
                                    $stmt->execute($params);
                                    
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($row['image']): ?>
                                                    <img src="../uploads/actualites/<?php echo $row['image']; ?>" 
                                                         class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['titre']); ?></strong>
                                                    <?php if ($row['resume']): ?>
                                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($row['resume']), 0, 80); ?>...</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($row['type_actualite']) {
                                                    'nouvelle' => 'info',
                                                    'evenement' => 'warning',
                                                    'annonce' => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($row['type_actualite']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['username'] ?? 'Inconnu'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['date_creation'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($row['statut']) {
                                                    'brouillon' => 'secondary',
                                                    'publie' => 'success',
                                                    'archive' => 'dark'
                                                };
                                            ?>">
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($row['vues']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=view&id=<?php echo $row['id_actualite']; ?>" class="btn btn-outline-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $row['id_actualite']; ?>" class="btn btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $row['id_actualite']; ?>" 
                                                   class="btn btn-outline-danger" title="Supprimer"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette actualité ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/édition -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $action === 'add' ? 'Nouvelle Actualité' : 'Modifier Actualité'; ?></h2>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Contenu</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Titre *</label>
                                        <input type="text" class="form-control" name="titre" 
                                               value="<?php echo htmlspecialchars($actualite['titre'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Résumé</label>
                                        <textarea class="form-control" name="resume" rows="3"><?php echo htmlspecialchars($actualite['resume'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Description courte qui apparaît dans la liste</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contenu *</label>
                                        <textarea class="form-control" name="contenu" rows="10" required><?php echo htmlspecialchars($actualite['contenu'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mots-clés</label>
                                        <input type="text" class="form-control" name="mots_cles" 
                                               value="<?php echo htmlspecialchars($actualite['mots_cles'] ?? ''); ?>"
                                               placeholder="Séparez les mots-clés par des virgules">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Paramètres</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-control" name="type_actualite">
                                            <option value="nouvelle" <?php echo ($actualite['type_actualite'] ?? '') === 'nouvelle' ? 'selected' : ''; ?>>Nouvelle</option>
                                            <option value="evenement" <?php echo ($actualite['type_actualite'] ?? '') === 'evenement' ? 'selected' : ''; ?>>Événement</option>
                                            <option value="annonce" <?php echo ($actualite['type_actualite'] ?? '') === 'annonce' ? 'selected' : ''; ?>>Annonce</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-control" name="statut">
                                            <option value="brouillon" <?php echo ($actualite['statut'] ?? '') === 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                                            <option value="publie" <?php echo ($actualite['statut'] ?? '') === 'publie' ? 'selected' : ''; ?>>Publié</option>
                                            <option value="archive" <?php echo ($actualite['statut'] ?? '') === 'archive' ? 'selected' : ''; ?>>Archivé</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Champs spécifiques aux événements -->
                                    <div id="event-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Date début</label>
                                            <input type="date" class="form-control" name="date_debut" 
                                                   value="<?php echo $actualite['date_debut'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Date fin</label>
                                            <input type="date" class="form-control" name="date_fin" 
                                                   value="<?php echo $actualite['date_fin'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Lieu</label>
                                            <input type="text" class="form-control" name="lieu" 
                                                   value="<?php echo htmlspecialchars($actualite['lieu'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Image</label>
                                        <?php if (isset($actualite['image']) && $actualite['image']): ?>
                                            <div class="mb-2">
                                                <img src="../uploads/actualites/<?php echo $actualite['image']; ?>" 
                                                     class="img-fluid rounded" style="max-height: 150px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Enregistrer
                                        </button>
                                        <a href="?action=list" class="btn btn-secondary">Annuler</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($action === 'view' && $actualite): ?>
                <!-- Vue détaillée -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><?php echo htmlspecialchars($actualite['titre']); ?></h2>
                        <p class="text-muted">
                            Par <?php echo htmlspecialchars($actualite['username'] ?? 'Inconnu'); ?> 
                            le <?php echo date('d/m/Y à H:i', strtotime($actualite['date_creation'])); ?>
                        </p>
                    </div>
                    <div>
                        <a href="?action=edit&id=<?php echo $actualite['id_actualite']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </a>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <?php if ($actualite['image']): ?>
                                    <div class="mb-3">
                                        <img src="../uploads/actualites/<?php echo $actualite['image']; ?>" 
                                             class="img-fluid rounded">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($actualite['resume']): ?>
                                    <div class="alert alert-info">
                                        <strong>Résumé:</strong> <?php echo nl2br(htmlspecialchars($actualite['resume'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="content">
                                    <?php echo nl2br(htmlspecialchars($actualite['contenu'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Informations</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($actualite['type_actualite']) {
                                                    'nouvelle' => 'info',
                                                    'evenement' => 'warning',
                                                    'annonce' => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($actualite['type_actualite']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statut:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($actualite['statut']) {
                                                    'brouillon' => 'secondary',
                                                    'publie' => 'success',
                                                    'archive' => 'dark'
                                                };
                                            ?>">
                                                <?php echo ucfirst($actualite['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Vues:</strong></td>
                                        <td><?php echo number_format($actualite['vues']); ?></td>
                                    </tr>
                                    <?php if ($actualite['date_publication']): ?>
                                    <tr>
                                        <td><strong>Publié le:</strong></td>
                                        <td><?php echo date('d/m/Y à H:i', strtotime($actualite['date_publication'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($actualite['type_actualite'] === 'evenement'): ?>
                                        <?php if ($actualite['date_debut']): ?>
                                        <tr>
                                            <td><strong>Date début:</strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($actualite['date_debut'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($actualite['date_fin']): ?>
                                        <tr>
                                            <td><strong>Date fin:</strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($actualite['date_fin'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($actualite['lieu']): ?>
                                        <tr>
                                            <td><strong>Lieu:</strong></td>
                                            <td><?php echo htmlspecialchars($actualite['lieu']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </table>
                                
                                <?php if ($actualite['mots_cles']): ?>
                                    <div class="mt-3">
                                        <strong>Mots-clés:</strong><br>
                                        <?php 
                                        $mots_cles = explode(',', $actualite['mots_cles']);
                                        foreach ($mots_cles as $mot_cle): ?>
                                            <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($mot_cle)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Afficher/masquer les champs spécifiques aux événements
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type_actualite"]');
    const eventFields = document.getElementById('event-fields');
    
    function toggleEventFields() {
        if (typeSelect && eventFields) {
            eventFields.style.display = typeSelect.value === 'evenement' ? 'block' : 'none';
        }
    }
    
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleEventFields);
        toggleEventFields(); // Appel initial
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>