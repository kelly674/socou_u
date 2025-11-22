<?php
$page_title = "Gestion des Formations";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id_formation = $_GET['id'] ?? null;
$message = '';
$error = '';
$pdo = getConnection();
// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';
        $objectifs = $_POST['objectifs'] ?? '';
        $formateur = $_POST['formateur'] ?? '';
        $date_formation = $_POST['date_formation'] ?? '';
        $heure_debut = $_POST['heure_debut'] ?? '';
        $duree_heures = $_POST['duree_heures'] ?? 0;
        $lieu = $_POST['lieu'] ?? '';
        $max_participants = $_POST['max_participants'] ?? 0;
        $frais_participation = $_POST['frais_participation'] ?? 0;
        $statut = $_POST['statut'] ?? 'programmee';
        $supports = $_POST['supports'] ?? '';
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO formations (titre, description, objectifs, formateur, date_formation, heure_debut, duree_heures, lieu, max_participants, frais_participation, statut, supports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titre, $description, $objectifs, $formateur, $date_formation, $heure_debut, $duree_heures, $lieu, $max_participants, $frais_participation, $statut, $supports]);
                $message = "Formation ajoutée avec succès";
            } else {
                $stmt = $pdo->prepare("UPDATE formations SET titre = ?, description = ?, objectifs = ?, formateur = ?, date_formation = ?, heure_debut = ?, duree_heures = ?, lieu = ?, max_participants = ?, frais_participation = ?, statut = ?, supports = ? WHERE id_formation = ?");
                $stmt->execute([$titre, $description, $objectifs, $formateur, $date_formation, $heure_debut, $duree_heures, $lieu, $max_participants, $frais_participation, $statut, $supports, $id_formation]);
                $message = "Formation modifiée avec succès";
            }
            $action = 'list';
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Suppression
if ($action === 'delete' && $id_formation) {
    try {
        $pdo->prepare("DELETE FROM formations WHERE id_formation = ?")->execute([$id_formation]);
        $message = "Formation supprimée avec succès";
        $action = 'list';
    } catch(PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupération des données pour édition
$formation = null;
if (($action === 'edit' || $action === 'view') && $id_formation) {
    $stmt = $pdo->prepare("SELECT * FROM formations WHERE id_formation = ?");
    $stmt->execute([$id_formation]);
    $formation = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Formations</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="?action=list" class="list-group-item list-group-item-action <?php echo $action === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>Liste des formations
                    </a>
                    <a href="?action=add" class="list-group-item list-group-item-action <?php echo $action === 'add' ? 'active' : ''; ?>">
                        <i class="fas fa-plus me-2"></i>Ajouter formation
                    </a>
                    <a href="?action=inscriptions" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Inscriptions
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
                <!-- Liste des formations -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Formations</h2>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouvelle formation
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Formateur</th>
                                        <th>Date</th>
                                        <th>Durée</th>
                                        <th>Participants</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT f.*, COUNT(i.id_inscription) as nb_inscrits FROM formations f LEFT JOIN inscriptions_formations i ON f.id_formation = i.id_formation GROUP BY f.id_formation ORDER BY f.date_formation DESC");
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['titre']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['lieu']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['formateur']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['date_formation'])); ?></td>
                                        <td><?php echo $row['duree_heures']; ?>h</td>
                                        <td><?php echo $row['nb_inscrits']; ?>/<?php echo $row['max_participants']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($row['statut']) {
                                                    'programmee' => 'primary',
                                                    'en_cours' => 'warning',
                                                    'terminee' => 'success',
                                                    'annulee' => 'danger'
                                                };
                                            ?>">
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=view&id=<?php echo $row['id_formation']; ?>" class="btn btn-outline-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $row['id_formation']; ?>" class="btn btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $row['id_formation']; ?>" 
                                                   class="btn btn-outline-danger" title="Supprimer"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette formation ?')">
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
                    <h2><?php echo $action === 'add' ? 'Nouvelle Formation' : 'Modifier Formation'; ?></h2>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Titre *</label>
                                        <input type="text" class="form-control" name="titre" 
                                               value="<?php echo htmlspecialchars($formation['titre'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($formation['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Objectifs</label>
                                        <textarea class="form-control" name="objectifs" rows="3"><?php echo htmlspecialchars($formation['objectifs'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Supports de formation</label>
                                        <textarea class="form-control" name="supports" rows="2"><?php echo htmlspecialchars($formation['supports'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Formateur *</label>
                                        <input type="text" class="form-control" name="formateur" 
                                               value="<?php echo htmlspecialchars($formation['formateur'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Date *</label>
                                        <input type="date" class="form-control" name="date_formation" 
                                               value="<?php echo $formation['date_formation'] ?? ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Heure de début</label>
                                        <input type="time" class="form-control" name="heure_debut" 
                                               value="<?php echo $formation['heure_debut'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Durée (heures)</label>
                                        <input type="number" class="form-control" name="duree_heures" min="1" max="24"
                                               value="<?php echo $formation['duree_heures'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Lieu</label>
                                        <input type="text" class="form-control" name="lieu" 
                                               value="<?php echo htmlspecialchars($formation['lieu'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Max participants</label>
                                        <input type="number" class="form-control" name="max_participants" min="1"
                                               value="<?php echo $formation['max_participants'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Frais participation (BIF)</label>
                                        <input type="number" class="form-control" name="frais_participation" min="0" step="0.01"
                                               value="<?php echo $formation['frais_participation'] ?? '0'; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-control" name="statut">
                                            <option value="programmee" <?php echo ($formation['statut'] ?? '') === 'programmee' ? 'selected' : ''; ?>>Programmée</option>
                                            <option value="en_cours" <?php echo ($formation['statut'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                            <option value="terminee" <?php echo ($formation['statut'] ?? '') === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                                            <option value="annulee" <?php echo ($formation['statut'] ?? '') === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <a href="?action=list" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($action === 'view' && $formation): ?>
                <!-- Vue détaillée -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo htmlspecialchars($formation['titre']); ?></h2>
                    <div>
                        <a href="?action=edit&id=<?php echo $formation['id_formation']; ?>" class="btn btn-warning">
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
                            <div class="card-header">
                                <h5>Détails de la formation</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($formation['description']): ?>
                                <div class="mb-3">
                                    <h6>Description</h6>
                                    <p><?php echo nl2br(htmlspecialchars($formation['description'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($formation['objectifs']): ?>
                                <div class="mb-3">
                                    <h6>Objectifs</h6>
                                    <p><?php echo nl2br(htmlspecialchars($formation['objectifs'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($formation['supports']): ?>
                                <div class="mb-3">
                                    <h6>Supports de formation</h6>
                                    <p><?php echo nl2br(htmlspecialchars($formation['supports'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Informations pratiques</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Formateur:</strong></td>
                                        <td><?php echo htmlspecialchars($formation['formateur']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($formation['date_formation'])); ?></td>
                                    </tr>
                                    <?php if ($formation['heure_debut']): ?>
                                    <tr>
                                        <td><strong>Heure:</strong></td>
                                        <td><?php echo date('H:i', strtotime($formation['heure_debut'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Durée:</strong></td>
                                        <td><?php echo $formation['duree_heures']; ?> heures</td>
                                    </tr>
                                    <?php if ($formation['lieu']): ?>
                                    <tr>
                                        <td><strong>Lieu:</strong></td>
                                        <td><?php echo htmlspecialchars($formation['lieu']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Max participants:</strong></td>
                                        <td><?php echo $formation['max_participants']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Frais:</strong></td>
                                        <td><?php echo number_format($formation['frais_participation'], 0, ',', ' '); ?> BIF</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statut:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($formation['statut']) {
                                                    'programmee' => 'primary',
                                                    'en_cours' => 'warning',
                                                    'terminee' => 'success',
                                                    'annulee' => 'danger'
                                                };
                                            ?>">
                                                <?php echo ucfirst($formation['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Liste des inscrits -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6>Participants inscrits</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $pdo->prepare("SELECT m.nom, m.prenom, i.statut, i.date_inscription FROM inscriptions_formations i JOIN membres m ON i.id_membre = m.id_membre WHERE i.id_formation = ? ORDER BY i.date_inscription");
                                $stmt->execute([$formation['id_formation']]);
                                $inscrits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php if (empty($inscrits)): ?>
                                    <p class="text-muted">Aucun participant inscrit</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($inscrits as $inscrit): ?>
                                        <div class="list-group-item px-0 py-2">
                                            <div class="d-flex justify-content-between">
                                                <span><?php echo htmlspecialchars($inscrit['nom'] . ' ' . $inscrit['prenom']); ?></span>
                                                <small class="badge bg-<?php echo $inscrit['statut'] === 'present' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($inscrit['statut']); ?>
                                                </small>
                                            </div>
                                        </div>
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

<?php require_once '../includes/footer.php'; ?>