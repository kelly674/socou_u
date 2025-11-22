<?php
$page_title = "Configuration du Système";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin'); // Seuls les admins peuvent modifier la configuration
require_once '../includes/header.php';
 $pdo = getConnection();
$action = $_GET['action'] ?? 'list';
$id_config = $_GET['id'] ?? null;
$message = '';
$error = '';

// Définition des configurations par défaut
$default_configs = [
    'site_name' => [
        'cle' => 'site_name',
        'valeur' => 'SOCOU_U',
        'description' => 'Nom du site/association'
    ],
    'site_description' => [
        'cle' => 'site_description',
        'valeur' => 'Société Coopérative Union des Producteurs',
        'description' => 'Description du site'
    ],
    'site_email' => [
        'cle' => 'site_email',
        'valeur' => 'contact@socou-u.bi',
        'description' => 'Email de contact principal'
    ],
    'site_telephone' => [
        'cle' => 'site_telephone',
        'valeur' => '+257 22 00 00 00',
        'description' => 'Téléphone de contact'
    ],
    'site_adresse' => [
        'cle' => 'site_adresse',
        'valeur' => 'Bujumbura, Burundi',
        'description' => 'Adresse physique'
    ],
    'smtp_host' => [
        'cle' => 'smtp_host',
        'valeur' => 'localhost',
        'description' => 'Serveur SMTP pour l\'envoi d\'emails'
    ],
    'smtp_port' => [
        'cle' => 'smtp_port',
        'valeur' => '587',
        'description' => 'Port SMTP'
    ],
    'smtp_username' => [
        'cle' => 'smtp_username',
        'valeur' => '',
        'description' => 'Nom d\'utilisateur SMTP'
    ],
    'smtp_password' => [
        'cle' => 'smtp_password',
        'valeur' => '',
        'description' => 'Mot de passe SMTP'
    ],
    'currency_symbol' => [
        'cle' => 'currency_symbol',
        'valeur' => 'BIF',
        'description' => 'Symbole de la monnaie'
    ],
    'max_file_size' => [
        'cle' => 'max_file_size',
        'valeur' => '5242880',
        'description' => 'Taille maximale des fichiers uploadés (en octets)'
    ],
    'items_per_page' => [
        'cle' => 'items_per_page',
        'valeur' => '20',
        'description' => 'Nombre d\'éléments par page'
    ],
    'maintenance_mode' => [
        'cle' => 'maintenance_mode',
        'valeur' => '0',
        'description' => 'Mode maintenance (1 = activé, 0 = désactivé)'
    ],
    'registration_enabled' => [
        'cle' => 'registration_enabled',
        'valeur' => '1',
        'description' => 'Autoriser les inscriptions (1 = oui, 0 = non)'
    ],
    'notification_email' => [
        'cle' => 'notification_email',
        'valeur' => 'admin@socou-u.bi',
        'description' => 'Email pour recevoir les notifications'
    ],
    'social_facebook' => [
        'cle' => 'social_facebook',
        'valeur' => '',
        'description' => 'URL de la page Facebook'
    ],
    'social_twitter' => [
        'cle' => 'social_twitter',
        'valeur' => '',
        'description' => 'URL du compte Twitter'
    ],
    'social_instagram' => [
        'cle' => 'social_instagram',
        'valeur' => '',
        'description' => 'URL du compte Instagram'
    ],
    'about_text' => [
        'cle' => 'about_text',
        'valeur' => 'SOCOU_U est une société coopérative qui unit les producteurs pour améliorer leurs conditions de vie.',
        'description' => 'Texte de présentation "À propos"'
    ]
];

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save_all') {
        // Sauvegarde de toutes les configurations
        try {
            $pdo->beginTransaction();
            
            foreach ($_POST as $cle => $valeur) {
                if ($cle !== 'action') {
                    $stmt = $pdo->prepare("INSERT INTO configurations (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?, date_modification = CURRENT_TIMESTAMP");
                    $stmt->execute([$cle, $valeur, $valeur]);
                }
            }
            
            $pdo->commit();
            $message = "Configuration sauvegardée avec succès";
        } catch(PDOException $e) {
            $pdo->rollback();
            $error = "Erreur lors de la sauvegarde : " . $e->getMessage();
        }
    } elseif ($action === 'add' || $action === 'edit') {
        $cle = $_POST['cle'] ?? '';
        $valeur = $_POST['valeur'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($cle)) {
            $error = "La clé de configuration est obligatoire";
        } else {
            try {
                if ($action === 'add') {
                    // Vérifier l'unicité de la clé
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM configurations WHERE cle = ?");
                    $stmt->execute([$cle]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Cette clé de configuration existe déjà";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO configurations (cle, valeur, description) VALUES (?, ?, ?)");
                        $stmt->execute([$cle, $valeur, $description]);
                        $message = "Configuration ajoutée avec succès";
                        $action = 'list';
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE configurations SET valeur = ?, description = ? WHERE id_config = ?");
                    $stmt->execute([$valeur, $description, $id_config]);
                    $message = "Configuration modifiée avec succès";
                    $action = 'list';
                }
            } catch(PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_defaults') {
        // Réinitialiser aux valeurs par défaut
        try {
            $pdo->beginTransaction();
            
            foreach ($default_configs as $config) {
                $stmt = $pdo->prepare("INSERT INTO configurations (cle, valeur, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valeur = ?, description = ?, date_modification = CURRENT_TIMESTAMP");
                $stmt->execute([$config['cle'], $config['valeur'], $config['description'], $config['valeur'], $config['description']]);
            }
            
            $pdo->commit();
            $message = "Configuration réinitialisée aux valeurs par défaut";
        } catch(PDOException $e) {
            $pdo->rollback();
            $error = "Erreur lors de la réinitialisation : " . $e->getMessage();
        }
    }
}

// Suppression
if ($action === 'delete' && $id_config) {
    try {
        $pdo->prepare("DELETE FROM configurations WHERE id_config = ?")->execute([$id_config]);
        $message = "Configuration supprimée avec succès";
        $action = 'list';
    } catch(PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupération des données pour édition
$config = null;
if (($action === 'edit') && $id_config) {
    $stmt = $pdo->prepare("SELECT * FROM configurations WHERE id_config = ?");
    $stmt->execute([$id_config]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupération de toutes les configurations
$stmt = $pdo->query("SELECT * FROM configurations ORDER BY cle");
$configs = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $configs[$row['cle']] = $row;
}

// Fonction pour obtenir la valeur d'une configuration
function getConfigValue($key, $configs, $default_configs) {
    if (isset($configs[$key])) {
        return $configs[$key]['valeur'];
    } elseif (isset($default_configs[$key])) {
        return $default_configs[$key]['valeur'];
    }
    return '';
}

// Fonction pour obtenir la description d'une configuration
function getConfigDescription($key, $configs, $default_configs) {
    if (isset($configs[$key])) {
        return $configs[$key]['description'];
    } elseif (isset($default_configs[$key])) {
        return $default_configs[$key]['description'];
    }
    return '';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Configuration</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="?action=list" class="list-group-item list-group-item-action <?php echo $action === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>Toutes les configurations
                    </a>
                    <a href="?action=add" class="list-group-item list-group-item-action <?php echo $action === 'add' ? 'active' : ''; ?>">
                        <i class="fas fa-plus me-2"></i>Nouvelle configuration
                    </a>
                    <a href="#general" class="list-group-item list-group-item-action">
                        <i class="fas fa-info-circle me-2"></i>Général
                    </a>
                    <a href="#email" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i>Email
                    </a>
                    <a href="#system" class="list-group-item list-group-item-action">
                        <i class="fas fa-server me-2"></i>Système
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
                <!-- Configuration principale -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Configuration du Système</h2>
                    <div>
                        <button type="button" class="btn btn-warning" onclick="resetDefaults()" title="Réinitialiser aux valeurs par défaut">
                            <i class="fas fa-undo me-2"></i>Réinitialiser
                        </button>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nouvelle configuration
                        </a>
                    </div>
                </div>
                
                <form method="POST" id="config-form">
                    <input type="hidden" name="action" value="save_all">
                    
                    <!-- Informations générales -->
                    <div class="card mb-4" id="general">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations Générales</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom du site</label>
                                        <input type="text" class="form-control" name="site_name" 
                                               value="<?php echo htmlspecialchars(getConfigValue('site_name', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('site_name', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email de contact</label>
                                        <input type="email" class="form-control" name="site_email" 
                                               value="<?php echo htmlspecialchars(getConfigValue('site_email', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('site_email', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone</label>
                                        <input type="text" class="form-control" name="site_telephone" 
                                               value="<?php echo htmlspecialchars(getConfigValue('site_telephone', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('site_telephone', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" class="form-control" name="site_adresse" 
                                               value="<?php echo htmlspecialchars(getConfigValue('site_adresse', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('site_adresse', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars(getConfigValue('site_description', $configs, $default_configs)); ?></textarea>
                                        <small class="form-text text-muted"><?php echo getConfigDescription('site_description', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Texte "À propos"</label>
                                        <textarea class="form-control" name="about_text" rows="4"><?php echo htmlspecialchars(getConfigValue('about_text', $configs, $default_configs)); ?></textarea>
                                        <small class="form-text text-muted"><?php echo getConfigDescription('about_text', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuration Email -->
                    <div class="card mb-4" id="email">
                        <div class="card-header">
                            <h5><i class="fas fa-envelope me-2"></i>Configuration Email (SMTP)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Serveur SMTP</label>
                                        <input type="text" class="form-control" name="smtp_host" 
                                               value="<?php echo htmlspecialchars(getConfigValue('smtp_host', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('smtp_host', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Port SMTP</label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?php echo htmlspecialchars(getConfigValue('smtp_port', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('smtp_port', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom d'utilisateur SMTP</label>
                                        <input type="text" class="form-control" name="smtp_username" 
                                               value="<?php echo htmlspecialchars(getConfigValue('smtp_username', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('smtp_username', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe SMTP</label>
                                        <input type="password" class="form-control" name="smtp_password" 
                                               value="<?php echo htmlspecialchars(getConfigValue('smtp_password', $configs, $default_configs)); ?>"
                                               placeholder="Laissez vide pour ne pas modifier">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('smtp_password', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Email de notification</label>
                                        <input type="email" class="form-control" name="notification_email" 
                                               value="<?php echo htmlspecialchars(getConfigValue('notification_email', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('notification_email', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuration Système -->
                    <div class="card mb-4" id="system">
                        <div class="card-header">
                            <h5><i class="fas fa-server me-2"></i>Paramètres Système</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Monnaie</label>
                                        <input type="text" class="form-control" name="currency_symbol" 
                                               value="<?php echo htmlspecialchars(getConfigValue('currency_symbol', $configs, $default_configs)); ?>">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('currency_symbol', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Taille max. fichiers (Mo)</label>
                                        <input type="number" class="form-control" name="max_file_size_mb" 
                                               value="<?php echo htmlspecialchars(getConfigValue('max_file_size', $configs, $default_configs) / 1024 / 1024); ?>"
                                               step="0.1" min="0.1" max="100">
                                        <small class="form-text text-muted">Taille maximale pour les uploads</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Éléments par page</label>
                                        <input type="number" class="form-control" name="items_per_page" 
                                               value="<?php echo htmlspecialchars(getConfigValue('items_per_page', $configs, $default_configs)); ?>"
                                               min="5" max="100">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('items_per_page', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" 
                                                   <?php echo getConfigValue('maintenance_mode', $configs, $default_configs) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Mode maintenance</label>
                                            <small class="form-text text-muted d-block"><?php echo getConfigDescription('maintenance_mode', $configs, $default_configs); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="registration_enabled" value="1" 
                                                   <?php echo getConfigValue('registration_enabled', $configs, $default_configs) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Autoriser les inscriptions</label>
                                            <small class="form-text text-muted d-block"><?php echo getConfigDescription('registration_enabled', $configs, $default_configs); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Réseaux sociaux -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-share-alt me-2"></i>Réseaux Sociaux</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Facebook</label>
                                        <input type="url" class="form-control" name="social_facebook" 
                                               value="<?php echo htmlspecialchars(getConfigValue('social_facebook', $configs, $default_configs)); ?>"
                                               placeholder="https://facebook.com/page">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('social_facebook', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Twitter</label>
                                        <input type="url" class="form-control" name="social_twitter" 
                                               value="<?php echo htmlspecialchars(getConfigValue('social_twitter', $configs, $default_configs)); ?>"
                                               placeholder="https://twitter.com/compte">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('social_twitter', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Instagram</label>
                                        <input type="url" class="form-control" name="social_instagram" 
                                               value="<?php echo htmlspecialchars(getConfigValue('social_instagram', $configs, $default_configs)); ?>"
                                               placeholder="https://instagram.com/compte">
                                        <small class="form-text text-muted"><?php echo getConfigDescription('social_instagram', $configs, $default_configs); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configurations personnalisées -->
                    <?php 
                    $custom_configs = [];
                    foreach ($configs as $key => $config) {
                        if (!isset($default_configs[$key])) {
                            $custom_configs[] = $config;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($custom_configs)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-puzzle-piece me-2"></i>Configurations Personnalisées</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Clé</th>
                                            <th>Valeur</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($custom_configs as $custom_config): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($custom_config['cle']); ?></code></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="<?php echo htmlspecialchars($custom_config['cle']); ?>" 
                                                       value="<?php echo htmlspecialchars($custom_config['valeur']); ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($custom_config['description'] ?? ''); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?php echo $custom_config['id_config']; ?>" class="btn btn-outline-warning btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $custom_config['id_config']; ?>" 
                                                       class="btn btn-outline-danger btn-sm" title="Supprimer"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette configuration ?')">
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
                    <?php endif; ?>
                    
                    <!-- Boutons d'action -->
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Sauvegarder la Configuration
                            </button>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Formulaire d'ajout/édition de configuration personnalisée -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $action === 'add' ? 'Nouvelle Configuration' : 'Modifier Configuration'; ?></h2>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Détails de la configuration</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Clé de configuration *</label>
                                        <input type="text" class="form-control" name="cle" 
                                               value="<?php echo htmlspecialchars($config['cle'] ?? ''); ?>" 
                                               <?php echo $action === 'edit' ? 'readonly' : ''; ?> required>
                                        <small class="form-text text-muted">
                                            Identifiant unique de la configuration (ex: max_users, theme_color)
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Valeur</label>
                                        <textarea class="form-control" name="valeur" rows="3"><?php echo htmlspecialchars($config['valeur'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">
                                            Valeur de la configuration
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" class="form-control" name="description" 
                                               value="<?php echo htmlspecialchars($config['description'] ?? ''); ?>">
                                        <small class="form-text text-muted">
                                            Description de l'utilité de cette configuration
                                        </small>
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
                        
                        <!-- Aide -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6><i class="fas fa-lightbulb me-2"></i>Conseils</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Utilisez des noms de clés explicites (ex: <code>max_file_size</code>, <code>theme_color</code>)</li>
                                    <li>Pour les valeurs booléennes, utilisez <code>1</code> pour vrai et <code>0</code> pour faux</li>
                                    <li>Pour les listes, séparez les valeurs par des virgules</li>
                                    <li>Évitez les caractères spéciaux dans les clés</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour réinitialisation -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réinitialiser la configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir réinitialiser toute la configuration aux valeurs par défaut ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette action écrasera toutes vos configurations personnalisées et ne peut pas être annulée.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_defaults">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>Réinitialiser
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function resetDefaults() {
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));
    modal.show();
}

// Convertir la taille de fichier en octets avant soumission
document.getElementById('config-form').addEventListener('submit', function(e) {
    const maxFileSizeMb = document.querySelector('input[name="max_file_size_mb"]');
    if (maxFileSizeMb) {
        // Créer un champ caché avec la valeur en octets
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'max_file_size';
        hiddenInput.value = Math.round(parseFloat(maxFileSizeMb.value) * 1024 * 1024);
        this.appendChild(hiddenInput);
    }
    
    // Gérer les checkboxes (ajouter une valeur 0 si pas cochée)
    const checkboxes = this.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        if (!checkbox.checked) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = checkbox.name;
            hiddenInput.value = '0';
            checkbox.parentNode.appendChild(hiddenInput);
        }
    });
});

// Smooth scroll pour les liens d'ancre
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Afficher un indicateur de sauvegarde
document.getElementById('config-form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde en cours...';
    submitBtn.disabled = true;
    
    // Restaurer le bouton après 3 secondes (au cas où la page ne se recharge pas)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Animation pour les sections */
.card {
    transition: all 0.3s ease;
}

.card:target {
    transform: scale(1.02);
    box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-group {
        width: 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>