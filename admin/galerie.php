<?php
$page_title = "Gestion de la Galerie";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id_media = $_GET['id'] ?? null;
$message = '';
$error = '';
$pdo = getConnection();
// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';
        $type_media = $_POST['type_media'] ?? 'image';
        $categorie = $_POST['categorie'] ?? 'production';
        $date_publication = $_POST['date_publication'] ?? date('Y-m-d');
        $statut = $_POST['statut'] ?? 'public';
        $auteur_id = $_SESSION['user_id'];
        
        // Gestion de l'upload de fichier
        $fichier = null;
        if (!empty($_FILES['fichier']['name'])) {
            $upload_dir = '../uploads/galerie/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = [];
            
            switch ($type_media) {
                case 'image':
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    break;
                case 'video':
                    $allowed_extensions = ['mp4', 'avi', 'mov', 'wmv', 'flv'];
                    break;
                case 'document':
                    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                    break;
            }
            
            if (in_array($file_extension, $allowed_extensions)) {
                $fichier = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $fichier;
                
                if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_path)) {
                    $error = "Erreur lors de l'upload du fichier";
                    $fichier = null;
                }
            } else {
                $error = "Format de fichier non autorisé pour ce type de média";
            }
        }
        
        if (!$error) {
            try {
                if ($action === 'add') {
                    if (!$fichier) {
                        $error = "Le fichier est obligatoire";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO medias (titre, description, fichier, type_media, categorie, date_publication, auteur_id, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$titre, $description, $fichier, $type_media, $categorie, $date_publication, $auteur_id, $statut]);
                        $message = "Média ajouté avec succès";
                        $action = 'list';
                    }
                } else {
                    // Si pas de nouveau fichier, conserver l'ancien
                    if (!$fichier && $action === 'edit') {
                        $stmt_old = $pdo->prepare("SELECT fichier FROM medias WHERE id_media = ?");
                        $stmt_old->execute([$id_media]);
                        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
                        $fichier = $old_data['fichier'];
                    }
                    
                    $stmt = $pdo->prepare("UPDATE medias SET titre = ?, description = ?, fichier = ?, type_media = ?, categorie = ?, date_publication = ?, statut = ? WHERE id_media = ?");
                    $stmt->execute([$titre, $description, $fichier, $type_media, $categorie, $date_publication, $statut, $id_media]);
                    $message = "Média modifié avec succès";
                    $action = 'list';
                }
            } catch(PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Suppression
if ($action === 'delete' && $id_media) {
    try {
        // Récupérer le fichier pour le supprimer
        $stmt = $pdo->prepare("SELECT fichier FROM medias WHERE id_media = ?");
        $stmt->execute([$id_media]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($media && $media['fichier']) {
            $file_path = '../uploads/galerie/' . $media['fichier'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $pdo->prepare("DELETE FROM medias WHERE id_media = ?")->execute([$id_media]);
        $message = "Média supprimé avec succès";
        $action = 'list';
    } catch(PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupération des données pour édition
$media = null;
if (($action === 'edit' || $action === 'view') && $id_media) {
    $stmt = $pdo->prepare("SELECT m.*, u.username FROM medias m LEFT JOIN utilisateurs u ON m.auteur_id = u.id_utilisateur WHERE m.id_media = ?");
    $stmt->execute([$id_media]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir l'icône du type de fichier
function getFileIcon($type_media, $fichier) {
    switch ($type_media) {
        case 'image':
            return 'fas fa-image text-primary';
        case 'video':
            return 'fas fa-video text-danger';
        case 'document':
            $extension = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'pdf':
                    return 'fas fa-file-pdf text-danger';
                case 'doc':
                case 'docx':
                    return 'fas fa-file-word text-primary';
                case 'xls':
                case 'xlsx':
                    return 'fas fa-file-excel text-success';
                case 'ppt':
                case 'pptx':
                    return 'fas fa-file-powerpoint text-warning';
                default:
                    return 'fas fa-file text-secondary';
            }
        default:
            return 'fas fa-file text-secondary';
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Galerie</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="?action=list" class="list-group-item list-group-item-action <?php echo $action === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-th me-2"></i>Tous les médias
                    </a>
                    <a href="?action=add" class="list-group-item list-group-item-action <?php echo $action === 'add' ? 'active' : ''; ?>">
                        <i class="fas fa-plus me-2"></i>Ajouter média
                    </a>
                    <a href="?action=list&type=image" class="list-group-item list-group-item-action">
                        <i class="fas fa-image me-2"></i>Images
                    </a>
                    <a href="?action=list&type=video" class="list-group-item list-group-item-action">
                        <i class="fas fa-video me-2"></i>Vidéos
                    </a>
                    <a href="?action=list&type=document" class="list-group-item list-group-item-action">
                        <i class="fas fa-file me-2"></i>Documents
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
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <!-- Liste des médias -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Galerie Multimédia</h2>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter un média
                    </a>
                </div>
                
                <!-- Filtres -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="action" value="list">
                            <div class="col-md-2">
                                <select name="type" class="form-control">
                                    <option value="">Tous les types</option>
                                    <option value="image" <?php echo ($_GET['type'] ?? '') === 'image' ? 'selected' : ''; ?>>Images</option>
                                    <option value="video" <?php echo ($_GET['type'] ?? '') === 'video' ? 'selected' : ''; ?>>Vidéos</option>
                                    <option value="document" <?php echo ($_GET['type'] ?? '') === 'document' ? 'selected' : ''; ?>>Documents</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="categorie" class="form-control">
                                    <option value="">Toutes catégories</option>
                                    <option value="production" <?php echo ($_GET['categorie'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                                    <option value="formation" <?php echo ($_GET['categorie'] ?? '') === 'formation' ? 'selected' : ''; ?>>Formation</option>
                                    <option value="evenement" <?php echo ($_GET['categorie'] ?? '') === 'evenement' ? 'selected' : ''; ?>>Événement</option>
                                    <option value="projet" <?php echo ($_GET['categorie'] ?? '') === 'projet' ? 'selected' : ''; ?>>Projet</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="statut" class="form-control">
                                    <option value="">Tous statuts</option>
                                    <option value="public" <?php echo ($_GET['statut'] ?? '') === 'public' ? 'selected' : ''; ?>>Public</option>
                                    <option value="prive" <?php echo ($_GET['statut'] ?? '') === 'prive' ? 'selected' : ''; ?>>Privé</option>
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
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleView()">
                                    <i class="fas fa-th" id="view-icon"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php
                // Construction de la requête avec filtres
                $where_conditions = [];
                $params = [];
                
                if (!empty($_GET['type'])) {
                    $where_conditions[] = "m.type_media = ?";
                    $params[] = $_GET['type'];
                }
                
                if (!empty($_GET['categorie'])) {
                    $where_conditions[] = "m.categorie = ?";
                    $params[] = $_GET['categorie'];
                }
                
                if (!empty($_GET['statut'])) {
                    $where_conditions[] = "m.statut = ?";
                    $params[] = $_GET['statut'];
                }
                
                if (!empty($_GET['search'])) {
                    $where_conditions[] = "(m.titre LIKE ? OR m.description LIKE ?)";
                    $search_term = '%' . $_GET['search'] . '%';
                    $params[] = $search_term;
                    $params[] = $search_term;
                }
                
                $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                
                $stmt = $pdo->prepare("SELECT m.*, u.username FROM medias m LEFT JOIN utilisateurs u ON m.auteur_id = u.id_utilisateur $where_sql ORDER BY m.date_creation DESC");
                $stmt->execute($params);
                $medias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <!-- Vue grille -->
                <div id="grid-view">
                    <div class="row">
                        <?php foreach ($medias as $row): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-img-top d-flex justify-content-center align-items-center bg-light" style="height: 200px;">
                                    <?php if ($row['type_media'] === 'image'): ?>
                                        <img src="../uploads/galerie/<?php echo $row['fichier']; ?>" 
                                             class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="<?php echo getFileIcon($row['type_media'], $row['fichier']); ?> fa-3x"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($row['titre'] ?: 'Sans titre'); ?></h6>
                                    <p class="card-text small text-muted">
                                        <?php echo $row['description'] ? substr(htmlspecialchars($row['description']), 0, 80) . '...' : 'Aucune description'; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <span class="badge bg-<?php 
                                                echo match($row['categorie']) {
                                                    'production' => 'success',
                                                    'formation' => 'info',
                                                    'evenement' => 'warning',
                                                    'projet' => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($row['categorie']); ?>
                                            </span>
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=view&id=<?php echo $row['id_media']; ?>" class="btn btn-outline-info btn-sm" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $row['id_media']; ?>" class="btn btn-outline-warning btn-sm" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $row['id_media']; ?>" 
                                               class="btn btn-outline-danger btn-sm" title="Supprimer"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce média ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Par <?php echo htmlspecialchars($row['username'] ?? 'Inconnu'); ?> 
                                        le <?php echo date('d/m/Y', strtotime($row['date_creation'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($medias)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun média trouvé</h5>
                            <p class="text-muted">Ajoutez votre premier média à la galerie</p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ajouter un média
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Vue liste -->
                <div id="list-view" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Aperçu</th>
                                            <th>Titre</th>
                                            <th>Type</th>
                                            <th>Catégorie</th>
                                            <th>Auteur</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medias as $row): ?>
                                        <tr>
                                            <td>
                                                <div style="width: 50px; height: 50px;" class="d-flex justify-content-center align-items-center bg-light rounded">
                                                    <?php if ($row['type_media'] === 'image'): ?>
                                                        <img src="../uploads/galerie/<?php echo $row['fichier']; ?>" 
                                                             class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <i class="<?php echo getFileIcon($row['type_media'], $row['fichier']); ?>"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['titre'] ?: 'Sans titre'); ?></strong>
                                                <?php if ($row['description']): ?>
                                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($row['description']), 0, 50) . '...'; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="<?php echo getFileIcon($row['type_media'], $row['fichier']); ?> me-1"></i>
                                                <?php echo ucfirst($row['type_media']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($row['categorie']) {
                                                        'production' => 'success',
                                                        'formation' => 'info',
                                                        'evenement' => 'warning',
                                                        'projet' => 'primary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($row['categorie']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['username'] ?? 'Inconnu'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['date_creation'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['statut'] === 'public' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($row['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=view&id=<?php echo $row['id_media']; ?>" class="btn btn-outline-info" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?php echo $row['id_media']; ?>" class="btn btn-outline-warning" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $row['id_media']; ?>" 
                                                       class="btn btn-outline-danger" title="Supprimer"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce média ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/édition -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $action === 'add' ? 'Ajouter un Média' : 'Modifier Média'; ?></h2>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Informations du média</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Titre</label>
                                        <input type="text" class="form-control" name="titre" 
                                               value="<?php echo htmlspecialchars($media['titre'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($media['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Fichier <?php echo $action === 'add' ? '*' : ''; ?></label>
                                        <?php if (isset($media['fichier']) && $media['fichier']): ?>
                                            <div class="mb-2">
                                                <?php if ($media['type_media'] === 'image'): ?>
                                                    <img src="../uploads/galerie/<?php echo $media['fichier']; ?>" 
                                                         class="img-fluid rounded" style="max-height: 200px;">
                                                <?php else: ?>
                                                    <div class="alert alert-info">
                                                        <i class="<?php echo getFileIcon($media['type_media'], $media['fichier']); ?> me-2"></i>
                                                        Fichier actuel: <?php echo htmlspecialchars($media['fichier']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="fichier" 
                                               <?php echo $action === 'add' ? 'required' : ''; ?>>
                                        <small class="form-text text-muted">
                                            Images: JPG, PNG, GIF, WEBP | Vidéos: MP4, AVI, MOV | Documents: PDF, DOC, XLS, PPT
                                        </small>
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
                                        <label class="form-label">Type de média</label>
                                        <select class="form-control" name="type_media" required>
                                            <option value="image" <?php echo ($media['type_media'] ?? '') === 'image' ? 'selected' : ''; ?>>Image</option>
                                            <option value="video" <?php echo ($media['type_media'] ?? '') === 'video' ? 'selected' : ''; ?>>Vidéo</option>
                                            <option value="document" <?php echo ($media['type_media'] ?? '') === 'document' ? 'selected' : ''; ?>>Document</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Catégorie</label>
                                        <select class="form-control" name="categorie">
                                            <option value="production" <?php echo ($media['categorie'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                                            <option value="formation" <?php echo ($media['categorie'] ?? '') === 'formation' ? 'selected' : ''; ?>>Formation</option>
                                            <option value="evenement" <?php echo ($media['categorie'] ?? '') === 'evenement' ? 'selected' : ''; ?>>Événement</option>
                                            <option value="projet" <?php echo ($media['categorie'] ?? '') === 'projet' ? 'selected' : ''; ?>>Projet</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Date de publication</label>
                                        <input type="date" class="form-control" name="date_publication" 
                                               value="<?php echo $media['date_publication'] ?? date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-control" name="statut">
                                            <option value="public" <?php echo ($media['statut'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Public</option>
                                            <option value="prive" <?php echo ($media['statut'] ?? '') === 'prive' ? 'selected' : ''; ?>>Privé</option>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Enregistrer
                                        </button>
                                        <a href="?action=list" class="btn btn-secondary">Annuler</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'view' && $media): ?>
                <!-- Vue détaillée -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><?php echo htmlspecialchars($media['titre'] ?: 'Sans titre'); ?></h2>
                        <p class="text-muted">
                            Par <?php echo htmlspecialchars($media['username'] ?? 'Inconnu'); ?> 
                            le <?php echo date('d/m/Y à H:i', strtotime($media['date_creation'])); ?>
                        </p>
                    </div>
                    <div>
                        <a href="?action=edit&id=<?php echo $media['id_media']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </a>
                        <a href="../uploads/galerie/<?php echo $media['fichier']; ?>" target="_blank" class="btn btn-info">
                            <i class="fas fa-download me-2"></i>Télécharger
                        </a>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if ($media['type_media'] === 'image'): ?>
                                    <img src="../uploads/galerie/<?php echo $media['fichier']; ?>" 
                                         class="img-fluid rounded" style="max-width: 100%; max-height: 600px;">
                                <?php elseif ($media['type_media'] === 'video'): ?>
                                    <video controls class="img-fluid rounded" style="max-width: 100%; max-height: 600px;">
                                        <source src="../uploads/galerie/<?php echo $media['fichier']; ?>" type="video/mp4">
                                        Votre navigateur ne supporte pas la lecture vidéo.
                                    </video>
                                <?php else: ?>
                                    <div class="py-5">
                                        <i class="<?php echo getFileIcon($media['type_media'], $media['fichier']); ?> fa-5x mb-3"></i>
                                        <h4><?php echo htmlspecialchars($media['fichier']); ?></h4>
                                        <p class="text-muted">Cliquez sur "Télécharger" pour ouvrir le document</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($media['description']): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Description</h5>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($media['description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
                                            <i class="<?php echo getFileIcon($media['type_media'], $media['fichier']); ?> me-1"></i>
                                            <?php echo ucfirst($media['type_media']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Catégorie:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($media['categorie']) {
                                                    'production' => 'success',
                                                    'formation' => 'info',
                                                    'evenement' => 'warning',
                                                    'projet' => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($media['categorie']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statut:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $media['statut'] === 'public' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($media['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fichier:</strong></td>
                                        <td><?php echo htmlspecialchars($media['fichier']); ?></td>
                                    </tr>
                                    <?php if ($media['date_publication']): ?>
                                    <tr>
                                        <td><strong>Publié le:</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($media['date_publication'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Ajouté le:</strong></td>
                                        <td><?php echo date('d/m/Y à H:i', strtotime($media['date_creation'])); ?></td>
                                    </tr>
                                    <?php
                                    $file_path = '../uploads/galerie/' . $media['fichier'];
                                    if (file_exists($file_path)):
                                    ?>
                                    <tr>
                                        <td><strong>Taille:</strong></td>
                                        <td><?php echo number_format(filesize($file_path) / 1024, 2); ?> KB</td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Basculer entre vue grille et liste
function toggleView() {
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const icon = document.getElementById('view-icon');
    
    if (gridView.style.display === 'none') {
        gridView.style.display = 'block';
        listView.style.display = 'none';
        icon.className = 'fas fa-th';
    } else {
        gridView.style.display = 'none';
        listView.style.display = 'block';
        icon.className = 'fas fa-list';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>