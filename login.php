<?php
$page_title = "Connexion";
require_once 'config/config.php';
include 'includes/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: ' . determineRedirectURL($_SESSION['role']));
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Token de sécurité invalide. Veuillez réessayer.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($username) || empty($password)) {
            $error_message = "Veuillez remplir tous les champs.";
        } else {
            $conn = getConnection();
            
            // Rechercher l'utilisateur avec informations de rôle
            $query = "SELECT u.*, m.nom, m.prenom, m.id_membre, m.telephone, m.email as membre_email
                      FROM utilisateurs u 
                      LEFT JOIN membres m ON u.id_membre = m.id_membre 
                      WHERE u.username = ? AND u.statut = 'actif'";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Vérifier si le rôle est valide
                $valid_roles = ['admin', 'gestionnaire', 'membre'];
                if (!in_array($user['role'], $valid_roles)) {
                    $error_message = "Rôle utilisateur invalide. Contactez l'administrateur.";
                    logActivity('connexion_echec', 'utilisateurs', $user['id_utilisateur'], 'Tentative de connexion avec rôle invalide: ' . $user['role']);
                } else {
                    // Connexion réussie - Initialiser la session
                    initializeUserSession($user);
                    
                    // Mettre à jour la dernière connexion
                    updateLastLogin($user['id_utilisateur']);
                    
                    // Logger l'activité de connexion
                    logActivity('connexion', 'utilisateurs', $user['id_utilisateur'], 'Connexion réussie - Rôle: ' . $user['role']);
                    
                    // Gérer "Se souvenir de moi"
                    if ($remember_me) {
                        handleRememberMe($user['id_utilisateur']);
                    }
                    
                    // Redirection basée sur le rôle et paramètres
                    $redirect_url = determineRedirectURL($user['role']);
                    
                    // Message de bienvenue dans la session
                    $_SESSION['welcome_message'] = getWelcomeMessage($user['role'], $user['prenom'] ?? $user['username']);
                    
                    header('Location: ' . $redirect_url);
                    exit();
                }
            } else {
                $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
                logActivity('echec_connexion', 'utilisateurs', null, 'Tentative de connexion échouée pour: ' . $username . ' - IP: ' . $_SERVER['REMOTE_ADDR']);
                
                // Optionnel: Limiter les tentatives de connexion
                recordFailedLoginAttempt($username, $_SERVER['REMOTE_ADDR']);
            }
        }
    }
}

/**
 * Initialise la session utilisateur avec toutes les données nécessaires
 */
function initializeUserSession($user) {
    $_SESSION['user_id'] = $user['id_utilisateur'];
    $_SESSION['member_id'] = $user['id_membre'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?: $user['username'];
    $_SESSION['email'] = $user['email'] ?? $user['membre_email'] ?? '';
    $_SESSION['phone'] = $user['telephone'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Permissions spécifiques par rôle
    $_SESSION['permissions'] = getRolePermissions($user['role']);
}

/**
 * Détermine l'URL de redirection basée sur le rôle et les paramètres
 */
function determineRedirectURL($role) {
    // Vérifier s'il y a une URL de redirection spécifiée
    $custom_redirect = $_GET['redirect'] ?? null;
    
    if ($custom_redirect && isValidRedirectURL($custom_redirect)) {
        return SITE_URL . $custom_redirect;
    }
    
    // Redirection par défaut selon le rôle
    switch ($role) {
        case 'admin':
            return SITE_URL . '/admin/index.php';
        case 'gestionnaire':
            return SITE_URL . '/gestionnaire/index.php';
        case 'membre':
            return SITE_URL . '/Membres/index.php';
        default:
            return SITE_URL . '/index.php';
    }
}

/**
 * Obtient les permissions selon le rôle
 */
function getRolePermissions($role) {
    $permissions = [
        'admin' => [
            'manage_users', 'manage_members', 'view_reports', 'manage_system',
            'delete_records', 'export_data', 'manage_roles', 'view_logs'
        ],
        'gestionnaire' => [
            'manage_members', 'view_reports', 'export_data', 'view_member_details'
        ],
        'membre' => [
            'view_profile', 'edit_profile', 'view_personal_data'
        ]
    ];
    
    return $permissions[$role] ?? [];
}

/**
 * Génère un message de bienvenue personnalisé
 */
function getWelcomeMessage($role, $name) {
    $messages = [
        'admin' => "Bienvenue dans l'espace administration, $name !",
        'gestionnaire' => "Bienvenue dans votre espace de gestion, $name !",
        'membre' => "Bienvenue dans votre espace membre, $name !"
    ];
    
    return $messages[$role] ?? "Bienvenue, $name !";
}

/**
 * Gère la fonctionnalité "Se souvenir de moi"
 */
function handleRememberMe($user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60); // 30 jours
    
    // Enregistrer le token en base de données
    $conn = getConnection();
    $query = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
    
    // Définir le cookie
    setcookie('remember_token', $token, $expires, '/', '', true, true);
}

/**
 * Met à jour la dernière connexion
 */
function updateLastLogin($user_id) {
    $conn = getConnection();
    $query = "UPDATE utilisateurs SET derniere_connexion = NOW(), nb_connexions = COALESCE(nb_connexions, 0) + 1 WHERE id_utilisateur = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
}

/**
 * Vérifie si l'URL de redirection est valide
 */
function isValidRedirectURL($url) {
    // Liste des URLs autorisées pour la redirection
    $allowed_paths = [
        '/admin/', '/gestionnaire/', '/membre/',
        '/index.php', '/index.php', '/index.php'
    ];
    
    foreach ($allowed_paths as $path) {
        if (strpos($url, $path) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Enregistre les tentatives de connexion échouées (pour sécurité)
 */
function recordFailedLoginAttempt($username, $ip) {
    $conn = getConnection();
    $query = "INSERT INTO failed_login_attempts (username, ip_address, attempt_time) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([$username, $ip]);
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient-primary text-white text-center py-4">
                    <h3 class="font-weight-light my-2">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </h3>
                    <p class="mb-0">Accédez à votre espace personnalisé</p>
                   
                </div>
                
                <div class="card-body p-5">
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo escape($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                
                    
                    <form method="POST" action="" class="needs-validation" novalidate id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Nom d'utilisateur"
                                   value="<?php echo escape($_POST['username'] ?? ''); ?>"
                                   autocomplete="username"
                                   required>
                            <label for="username">
                                <i class="fas fa-user"></i> Nom d'utilisateur
                            </label>
                            <div class="invalid-feedback">
                                Veuillez saisir votre nom d'utilisateur.
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Mot de passe"
                                   autocomplete="current-password"
                                   required>
                            <label for="password">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="invalid-feedback">
                                Veuillez saisir votre mot de passe.
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="remember_me" 
                                           name="remember_me"
                                           <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="remember_me">
                                        <i class="fas fa-memory"></i> Se souvenir
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <small>
                                    <i class="fas fa-shield-alt text-success"></i> 
                                    Connexion sécurisée
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginButton">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer text-center py-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="small">
                                <a href="<?php echo SITE_URL; ?>/mot-de-passe-oublie.php" class="text-decoration-none">
                                    <i class="fas fa-question-circle"></i> Mot de passe oublié ?
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="small">
                                <a href="<?php echo SITE_URL; ?>/inscription.php" class="text-decoration-none">
                                    <i class="fas fa-user-plus"></i> Créer un compte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-2">
                    
                    <div class="small text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Chaque rôle dispose d'un espace personnalisé adapté à ses besoins
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}

.role-indicator {
    transition: all 0.3s ease;
}

.role-indicator:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.role-indicator i {
    font-size: 1.2em;
    margin-bottom: 4px;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.btn-primary {
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.4);
}

.form-floating input:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}
</style>

<script>
// Validation du formulaire avec amélioration UX
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Afficher le spinner
                    const button = document.getElementById('loginButton');
                    const spinner = button.querySelector('.spinner-border');
                    button.disabled = true;
                    spinner.classList.remove('d-none');
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Gestion du toggle mot de passe
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordContainer = passwordInput.parentNode;
    
    // Créer le bouton toggle
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 p-0';
    toggleButton.innerHTML = '<i class="fas fa-eye text-muted"></i>';
    toggleButton.style.border = 'none';
    toggleButton.style.background = 'none';
    toggleButton.style.zIndex = '10';
    toggleButton.setAttribute('tabindex', '-1');
    
    passwordContainer.style.position = 'relative';
    passwordContainer.appendChild(toggleButton);
    
    toggleButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        if (type === 'password') {
            icon.className = 'fas fa-eye text-muted';
        } else {
            icon.className = 'fas fa-eye-slash text-primary';
        }
    });
});

// Animation des indicateurs de rôles
document.addEventListener('DOMContentLoaded', function() {
    const indicators = document.querySelectorAll('.role-indicator');
    
    indicators.forEach((indicator, index) => {
        indicator.style.animationDelay = `${index * 0.1}s`;
        indicator.classList.add('animate__animated', 'animate__fadeInUp');
    });
});

// Auto-focus sur le champ username
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }
});

// Gestion des erreurs avec auto-hide
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert-danger');
    if (alert) {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>