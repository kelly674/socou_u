<?php
$page_title = "Inscription";
require_once 'config/config.php';
require_once 'includes/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token de sécurité invalide.";
    } else {
        // Récupérer et valider les données
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $commune = trim($_POST['commune'] ?? '');
        $zone = trim($_POST['zone'] ?? '');
        $date_naissance = $_POST['date_naissance'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $type_membre = $_POST['type_membre'] ?? '';
        $specialisation = trim($_POST['specialisation'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $accept_terms = isset($_POST['accept_terms']);
        
        // Validations
        if (empty($nom)) $errors[] = "Le nom est requis.";
        if (empty($prenom)) $errors[] = "Le prénom est requis.";
        if (empty($email) || !isValidEmail($email)) $errors[] = "Une adresse email valide est requise.";
        if (empty($telephone) || !isValidPhone($telephone)) $errors[] = "Un numéro de téléphone valide est requis.";
        if (empty($date_naissance)) $errors[] = "La date de naissance est requise.";
        if (empty($genre)) $errors[] = "Le genre est requis.";
        if (empty($type_membre)) $errors[] = "Le type de membre est requis.";
        if (empty($username) || strlen($username) < 3) $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        if (empty($password) || strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas.";
        if (!$accept_terms) $errors[] = "Vous devez accepter les conditions générales.";
        
        // Vérifier l'unicité de l'email et du nom d'utilisateur
        if (empty($errors)) {
            $conn = getConnection();
            
            // Vérifier l'email
            $query = "SELECT COUNT(*) FROM membres WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }
            
            // Vérifier le nom d'utilisateur
            $query = "SELECT COUNT(*) FROM utilisateurs WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Ce nom d'utilisateur est déjà pris.";
            }
        }
        
        // Si pas d'erreurs, créer le membre et l'utilisateur
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Générer le code membre
                $code_membre = generateMemberCode();
                
                // Insérer le membre
                $query = "INSERT INTO membres (code_membre, nom, prenom, email, telephone, adresse, province, commune, zone, date_naissance, genre, type_membre, specialisation, date_adhesion) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $code_membre, $nom, $prenom, $email, $telephone, $adresse,
                    $province, $commune, $zone, $date_naissance, $genre, $type_membre, $specialisation
                ]);
                
                $member_id = $conn->lastInsertId();
                
                // Insérer l'utilisateur
                $hashed_password = hashPassword($password);
                $query = "INSERT INTO utilisateurs (id_membre, username, password, role) VALUES (?, ?, ?, 'membre')";
                $stmt = $conn->prepare($query);
                $stmt->execute([$member_id, $username, $hashed_password]);
                
                $user_id = $conn->lastInsertId();
                
                $conn->commit();
                
                // Logger l'activité
                logActivity('inscription', 'membres', $member_id, 'Nouveau membre inscrit: ' . $nom . ' ' . $prenom);
                
                $success_message = "Inscription réussie ! Votre code membre est : <strong>$code_membre</strong>. Vous pouvez maintenant vous connecter.";
                
                // Envoyer un email de bienvenue (optionnel)
                $subject = "Bienvenue à SOCOU_U";
                $message = "<h2>Bienvenue à SOCOU_U</h2>
                           <p>Cher(e) $prenom $nom,</p>
                           <p>Votre inscription à la coopérative SOCOU_U a été validée avec succès.</p>
                           <p>Votre code membre : <strong>$code_membre</strong></p>
                           <p>Nom d'utilisateur : <strong>$username</strong></p>
                           <p>Vous pouvez maintenant vous connecter à votre espace membre.</p>
                           <p>Cordialement,<br>L'équipe SOCOU_U</p>";
                
                sendEmail($email, $subject, $message);
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="font-weight-light my-2">
                        <i class="fas fa-user-plus"></i> Devenir Membre
                    </h3>
                    <p class="mb-0">Rejoignez la coopérative SOCOU_U</p>
                </div>
                
                <div class="card-body p-5">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> Veuillez corriger les erreurs suivantes :
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <div class="mt-3">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Se connecter maintenant
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Informations personnelles -->
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-user"></i> Informations personnelles
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="nom" 
                                           name="nom" 
                                           placeholder="Nom"
                                           value="<?php echo escape($_POST['nom'] ?? ''); ?>"
                                           required>
                                    <label for="nom">Nom *</label>
                                    <div class="invalid-feedback">Veuillez saisir votre nom.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="prenom" 
                                           name="prenom" 
                                           placeholder="Prénom"
                                           value="<?php echo escape($_POST['prenom'] ?? ''); ?>"
                                           required>
                                    <label for="prenom">Prénom *</label>
                                    <div class="invalid-feedback">Veuillez saisir votre prénom.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           placeholder="Email"
                                           value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                           required>
                                    <label for="email">Email *</label>
                                    <div class="invalid-feedback">Veuillez saisir une adresse email valide.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telephone" 
                                           name="telephone" 
                                           placeholder="Téléphone"
                                           value="<?php echo escape($_POST['telephone'] ?? ''); ?>"
                                           pattern="(\+?257)?[0-9]{8}"
                                           required>
                                    <label for="telephone">Téléphone *</label>
                                    <div class="invalid-feedback">Veuillez saisir un numéro de téléphone valide.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="date" 
                                           class="form-control" 
                                           id="date_naissance" 
                                           name="date_naissance" 
                                           value="<?php echo escape($_POST['date_naissance'] ?? ''); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                           required>
                                    <label for="date_naissance">Date de naissance *</label>
                                    <div class="invalid-feedback">Veuillez saisir votre date de naissance.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="genre" name="genre" required>
                                        <option value="">Choisir...</option>
                                        <option value="M" <?php echo ($_POST['genre'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                        <option value="F" <?php echo ($_POST['genre'] ?? '') === 'F' ? 'selected' : ''; ?>>Féminin</option>
                                    </select>
                                    <label for="genre">Genre *</label>
                                    <div class="invalid-feedback">Veuillez sélectionner votre genre.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="type_membre" name="type_membre" required>
                                        <option value="">Choisir...</option>
                                        <option value="producteur" <?php echo ($_POST['type_membre'] ?? '') === 'producteur' ? 'selected' : ''; ?>>Producteur</option>
                                        <option value="transformateur" <?php echo ($_POST['type_membre'] ?? '') === 'transformateur' ? 'selected' : ''; ?>>Transformateur</option>
                                        <option value="commercial" <?php echo ($_POST['type_membre'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commerçant</option>
                                    </select>
                                    <label for="type_membre">Type de membre *</label>
                                    <div class="invalid-feedback">Veuillez sélectionner votre type de membre.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="specialisation" 
                                   name="specialisation" 
                                   placeholder="Spécialisation"
                                   value="<?php echo escape($_POST['specialisation'] ?? ''); ?>">
                            <label for="specialisation">Spécialisation (ex: maïs, élevage bovin, etc.)</label>
                        </div>
                        
                        <!-- Adresse -->
                        <h5 class="mb-3 text-primary mt-4">
                            <i class="fas fa-map-marker-alt"></i> Adresse
                        </h5>
                        
                        <div class="form-floating mb-3">
                            <textarea class="form-control" 
                                     id="adresse" 
                                     name="adresse" 
                                     placeholder="Adresse" 
                                     style="height: 100px"><?php echo escape($_POST['adresse'] ?? ''); ?></textarea>
                            <label for="adresse">Adresse complète</label>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="province" 
                                           name="province" 
                                           placeholder="Province"
                                           value="<?php echo escape($_POST['province'] ?? 'Bujumbura'); ?>">
                                    <label for="province">Province</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="commune" 
                                           name="commune" 
                                           placeholder="Commune"
                                           value="<?php echo escape($_POST['commune'] ?? ''); ?>">
                                    <label for="commune">Commune</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="zone" 
                                           name="zone" 
                                           placeholder="Zone"
                                           value="<?php echo escape($_POST['zone'] ?? ''); ?>">
                                    <label for="zone">Zone</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations de connexion -->
                        <h5 class="mb-3 text-primary mt-4">
                            <i class="fas fa-key"></i> Informations de connexion
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-floating mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Nom d'utilisateur"
                                           value="<?php echo escape($_POST['username'] ?? ''); ?>"
                                           pattern="[a-zA-Z0-9_]{3,}"
                                           required>
                                    <label for="username">Nom d'utilisateur *</label>
                                    <div class="form-text">Au moins 3 caractères (lettres, chiffres, underscore uniquement)</div>
                                    <div class="invalid-feedback">Veuillez saisir un nom d'utilisateur valide.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Mot de passe"
                                           minlength="6"
                                           required>
                                    <label for="password">Mot de passe *</label>
                                    <div class="form-text">Au moins 6 caractères</div>
                                    <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Confirmer le mot de passe"
                                           required>
                                    <label for="confirm_password">Confirmer le mot de passe *</label>
                                    <div class="invalid-feedback">Veuillez confirmer votre mot de passe.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conditions générales -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="accept_terms" 
                                   name="accept_terms"
                                   required>
                            <label class="form-check-label" for="accept_terms">
                                J'accepte les <a href="<?php echo SITE_URL; ?>/conditions.php" target="_blank">conditions générales</a> 
                                et la <a href="<?php echo SITE_URL; ?>/confidentialite.php" target="_blank">politique de confidentialité</a> *
                            </label>
                            <div class="invalid-feedback">Vous devez accepter les conditions générales.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Créer mon compte
                            </button>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
                
                <div class="card-footer text-center py-3">
                    <div class="small">
                        Déjà membre ? 
                        <a href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du formulaire
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // Vérifier la correspondance des mots de passe
                var password = document.getElementById('password').value;
                var confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    document.getElementById('confirm_password').setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    document.getElementById('confirm_password').setCustomValidity('');
                }
                
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Vérification en temps réel des mots de passe
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function checkPasswords() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
            if (confirmPassword.value) {
                confirmPassword.classList.add('is-valid');
            }
        }
    }
    
    password.addEventListener('input', checkPasswords);
    confirmPassword.addEventListener('input', checkPasswords);
    
    // Vérification de la disponibilité du nom d'utilisateur
    const usernameInput = document.getElementById('username');
    let usernameTimeout;
    
    usernameInput.addEventListener('input', function() {
        clearTimeout(usernameTimeout);
        const username = this.value;
        
        if (username.length >= 3) {
            usernameTimeout = setTimeout(() => {
                fetch('<?php echo SITE_URL; ?>/ajax/check-username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        usernameInput.setCustomValidity('Ce nom d\'utilisateur est déjà pris');
                        usernameInput.classList.add('is-invalid');
                    } else {
                        usernameInput.setCustomValidity('');
                        usernameInput.classList.remove('is-invalid');
                        usernameInput.classList.add('is-valid');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
            }, 500);
        }
    });
    
    // Générer un nom d'utilisateur automatique
    const nomInput = document.getElementById('nom');
    const prenomInput = document.getElementById('prenom');
    
    function generateUsername() {
        const nom = nomInput.value.toLowerCase().replace(/[^a-z]/g, '');
        const prenom = prenomInput.value.toLowerCase().replace(/[^a-z]/g, '');
        
        if (nom && prenom && !usernameInput.value) {
            const suggestion = prenom + '.' + nom;
            usernameInput.value = suggestion;
            usernameInput.dispatchEvent(new Event('input'));
        }
    }
    
    nomInput.addEventListener('blur', generateUsername);
    prenomInput.addEventListener('blur', generateUsername);
});
</script>

<?php require_once 'includes/footer.php'; ?>