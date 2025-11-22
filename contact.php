<?php
$page_title = "Contact";
$page_description = "Contactez SOCOU_U pour toute information sur nos services, produits ou pour devenir membre de notre coopérative.";

require_once 'includes/header.php';
require_once 'includes/functions.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token de sécurité invalide.";
    } else {
        // Récupérer et valider les données
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validations
        if (empty($nom)) $errors[] = "Le nom est requis.";
        if (empty($email) || !isValidEmail($email)) $errors[] = "Une adresse email valide est requise.";
        if (empty($sujet)) $errors[] = "Le sujet est requis.";
        if (empty($message)) $errors[] = "Le message est requis.";
        if (!empty($telephone) && !isValidPhone($telephone)) $errors[] = "Le numéro de téléphone n'est pas valide.";
        
        // Si pas d'erreurs, enregistrer le message
        if (empty($errors)) {
            try {
                $conn = getConnection();
                $query = "INSERT INTO messages_contact (nom, email, telephone, sujet, message) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$nom, $email, $telephone, $sujet, $message]);
                
                // Logger l'activité
                logActivity('contact', 'messages_contact', $conn->lastInsertId(), 'Nouveau message de contact de: ' . $nom);
                
                $success_message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
                
                // Envoyer une notification par email aux administrateurs
                $admin_email = ConfigManager::get('email', 'janviernzambimana91@gmail.com');
                $subject = "Nouveau message de contact - " . $sujet;
                $admin_message = "
                <h3>Nouveau message de contact</h3>
                <p><strong>Nom:</strong> $nom</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Téléphone:</strong> $telephone</p>
                <p><strong>Sujet:</strong> $sujet</p>
                <p><strong>Message:</strong></p>
                <div style='background: #f8f9fa; padding: 15px; border-left: 3px solid #007bff;'>
                    " . nl2br(escape($message)) . "
                </div>
                <p><em>Message reçu le " . date('d/m/Y à H:i') . "</em></p>
                ";
                
                sendEmail($admin_email, $subject, $admin_message);
                
                // Réinitialiser le formulaire
                $_POST = [];
                
            } catch (Exception $e) {
                $errors[] = "Erreur lors de l'envoi du message : " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Breadcrumb -->
<div class="container py-3">
    <?php echo createBreadcrumb([
        ['title' => 'Accueil', 'url' => SITE_URL],
        ['title' => 'Contact']
    ]); ?>
</div>

<!-- Section Contact -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-envelope"></i> Nous Contacter
                        </h4>
                    </div>
                    <div class="card-body p-4">
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
                        </div>
                        <?php else: ?>
                        
                        <form method="POST" action="" id="contactForm" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" 
                                               class="form-control" 
                                               id="nom" 
                                               name="nom" 
                                               placeholder="Votre nom"
                                               value="<?php echo escape($_POST['nom'] ?? ''); ?>"
                                               required>
                                        <label for="nom">Nom complet *</label>
                                        <div class="invalid-feedback">Veuillez saisir votre nom.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               placeholder="Votre email"
                                               value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                               required>
                                        <label for="email">Email *</label>
                                        <div class="invalid-feedback">Veuillez saisir une adresse email valide.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="tel" 
                                               class="form-control" 
                                               id="telephone" 
                                               name="telephone" 
                                               placeholder="Votre téléphone"
                                               value="<?php echo escape($_POST['telephone'] ?? ''); ?>"
                                               pattern="(\+?257)?[0-9]{8}">
                                        <label for="telephone">Téléphone</label>
                                        <div class="invalid-feedback">Veuillez saisir un numéro de téléphone valide.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="sujet" name="sujet" required>
                                            <option value="">Choisir un sujet...</option>
                                            <option value="Information générale" <?php echo ($_POST['sujet'] ?? '') === 'Information générale' ? 'selected' : ''; ?>>Information générale</option>
                                            <option value="Adhésion" <?php echo ($_POST['sujet'] ?? '') === 'Adhésion' ? 'selected' : ''; ?>>Devenir membre</option>
                                            <option value="Produits" <?php echo ($_POST['sujet'] ?? '') === 'Produits' ? 'selected' : ''; ?>>Nos produits</option>
                                            <option value="Formations" <?php echo ($_POST['sujet'] ?? '') === 'Formations' ? 'selected' : ''; ?>>Formations</option>
                                            <option value="Projets sociaux" <?php echo ($_POST['sujet'] ?? '') === 'Projets sociaux' ? 'selected' : ''; ?>>Projets sociaux</option>
                                            <option value="Partenariat" <?php echo ($_POST['sujet'] ?? '') === 'Partenariat' ? 'selected' : ''; ?>>Partenariat</option>
                                            <option value="Autre" <?php echo ($_POST['sujet'] ?? '') === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                        </select>
                                        <label for="sujet">Sujet *</label>
                                        <div class="invalid-feedback">Veuillez sélectionner un sujet.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <textarea class="form-control" 
                                         id="message" 
                                         name="message" 
                                         placeholder="Votre message" 
                                         style="height: 150px"
                                         required><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                                <label for="message">Votre message *</label>
                                <div class="invalid-feedback">Veuillez saisir votre message.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane"></i> Envoyer le message
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Informations de contact -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informations de contact
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="contact-info-item text-start p-0 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-map-marker-alt text-success me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Adresse</h6>
                                    <p class="mb-0 text-muted">
                                        <?php echo ConfigManager::get('adresse', 'Province de Bujumbura, Zone Rohero, Commune Mukaza'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-info-item text-start p-0 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-phone text-success me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Téléphone</h6>
                                    <p class="mb-0">
                                        <a href="tel:<?php echo ConfigManager::get('telephone', '+257XXXXXXXX'); ?>" class="text-decoration-none">
                                            <?php echo ConfigManager::get('telephone', '+257 XX XX XX XX'); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-info-item text-start p-0 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-envelope text-success me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Email</h6>
                                    <p class="mb-0">
                                        <a href="mailto:<?php echo ConfigManager::get('email', 'contact@socou-u.bi'); ?>" class="text-decoration-none">
                                            <?php echo ConfigManager::get('email', 'contact@socou-u.bi'); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-info-item text-start p-0 mb-0">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock text-success me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Horaires</h6>
                                    <p class="mb-1 text-muted">Lundi - Vendredi : 08h00 - 17h00</p>
                                    <p class="mb-0 text-muted">Samedi : 08h00 - 12h00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Réseaux sociaux -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-share-alt"></i> Suivez-nous
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="social-links">
                            <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="#" class="btn btn-outline-info btn-sm me-2 mb-2">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </a>
                            <a href="#" class="btn btn-outline-danger btn-sm mb-2">
                                <i class="fab fa-instagram"></i> Instagram
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Carte (optionnelle) -->
<section class="py-5 bg-light">
    <div class="container">
        <h3 class="text-center mb-4">Notre Localisation</h3>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <!-- Remplacer par une vraie carte Google Maps ou OpenStreetMap -->
                        <div class="bg-secondary text-white text-center p-5">
                            <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                            <h5>Carte de localisation</h5>
                            <p class="mb-0">Province de Bujumbura, Zone Rohero, Commune Mukaza</p>
                            <small class="text-light">Intégration Google Maps à configurer</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Validation du formulaire
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php require_once 'includes/footer.php'; ?>