<?php
$page_title = "Désabonnement Newsletter";
$page_description = "Se désabonner de la newsletter SOCOU_U";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$show_form = true;

// Traitement du formulaire de désabonnement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_post = trim($_POST['email'] ?? '');
    $raison = trim($_POST['raison'] ?? '');
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    if (empty($email_post)) {
        $message = "L'adresse email est requise.";
        $message_type = "danger";
    } elseif (!filter_var($email_post, FILTER_VALIDATE_EMAIL)) {
        $message = "L'adresse email n'est pas valide.";
        $message_type = "danger";
    } else {
        try {
            $conn = getConnection();
            
            // Vérifier si l'email existe et est actif
            $check_query = "SELECT id_abonne, statut FROM newsletter_abonnes WHERE email = :email";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':email', $email_post);
            $check_stmt->execute();
            $abonne = $check_stmt->fetch();
            
            if (!$abonne) {
                $message = "Cette adresse email n'est pas trouvée dans notre base d'abonnés.";
                $message_type = "warning";
            } elseif ($abonne['statut'] === 'desabonne') {
                $message = "Vous êtes déjà désabonné(e) de notre newsletter.";
                $message_type = "info";
                $show_form = false;
            } else {
                // Désabonner l'utilisateur
                $unsubscribe_query = "UPDATE newsletter_abonnes 
                                     SET statut = 'desabonne', date_modification = NOW() 
                                     WHERE email = :email";
                $unsubscribe_stmt = $conn->prepare($unsubscribe_query);
                $unsubscribe_stmt->bindParam(':email', $email_post);
                
                if ($unsubscribe_stmt->execute()) {
                    // Enregistrer la raison du désabonnement si fournie
                    if (!empty($raison) || !empty($commentaire)) {
                        $feedback_query = "INSERT INTO newsletter_feedback 
                                          (email, raison, commentaire, date_creation) 
                                          VALUES (:email, :raison, :commentaire, NOW())";
                        try {
                            $feedback_stmt = $conn->prepare($feedback_query);
                            $feedback_stmt->bindParam(':email', $email_post);
                            $feedback_stmt->bindParam(':raison', $raison);
                            $feedback_stmt->bindParam(':commentaire', $commentaire);
                            $feedback_stmt->execute();
                        } catch(PDOException $e) {
                            // Ignorer les erreurs de feedback, ce n'est pas critique
                        }
                    }
                    
                    // Log de l'activité
                    logUnsubscribeActivity($abonne['id_abonne'], $email_post, $raison);
                    
                    $message = "Vous avez été désabonné(e) avec succès de notre newsletter. Merci pour votre retour.";
                    $message_type = "success";
                    $show_form = false;
                    
                    // Envoyer un email de confirmation si souhaité
                    if (function_exists('sendUnsubscribeConfirmation')) {
                        sendUnsubscribeConfirmation($email_post);
                    }
                } else {
                    $message = "Une erreur est survenue lors du désabonnement. Veuillez réessayer.";
                    $message_type = "danger";
                }
            }
            
        } catch(PDOException $e) {
            error_log("Erreur désabonnement newsletter: " . $e->getMessage());
            $message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
            $message_type = "danger";
        }
    }
}

// Pré-remplir l'email si fourni dans l'URL
$email = htmlspecialchars($email);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- En-tête -->
            <div class="text-center mb-5">
                <i class="fas fa-unlink fa-4x text-warning mb-3"></i>
                <h1 class="h2 text-primary">Désabonnement Newsletter</h1>
                <p class="text-muted">
                    Nous sommes désolés de vous voir partir. Votre opinion nous importe.
                </p>
            </div>

            <!-- Message de retour -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : ($message_type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle'); ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <!-- Formulaire de désabonnement -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope-open-text me-2"></i>
                            Confirmer le désabonnement
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold">
                                    Adresse email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo $email; ?>" 
                                       required>
                                <div class="form-text">
                                    Confirmez l'adresse email que vous souhaitez désabonner.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Pourquoi vous désabonnez-vous ? (optionnel)
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="trop_emails" id="raison1">
                                            <label class="form-check-label" for="raison1">
                                                Trop d'emails
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="contenu_non_pertinent" id="raison2">
                                            <label class="form-check-label" for="raison2">
                                                Contenu non pertinent
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="jamais_inscrit" id="raison3">
                                            <label class="form-check-label" for="raison3">
                                                Je ne me suis jamais inscrit(e)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="changement_email" id="raison4">
                                            <label class="form-check-label" for="raison4">
                                                Changement d'adresse email
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="plus_interesse" id="raison5">
                                            <label class="form-check-label" for="raison5">
                                                Plus intéressé(e)
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="raison" value="autre" id="raison6">
                                            <label class="form-check-label" for="raison6">
                                                Autre raison
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="commentaire" class="form-label fw-bold">
                                    Commentaire (optionnel)
                                </label>
                                <textarea class="form-control" 
                                          id="commentaire" 
                                          name="commentaire" 
                                          rows="4" 
                                          placeholder="Vos suggestions pour améliorer notre newsletter..."></textarea>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Une fois désabonné(e), vous ne recevrez plus nos actualités, 
                                événements et annonces importantes. Vous pourrez vous réabonner à tout moment.
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>/actualites.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr(e) de vouloir vous désabonner ?')">
                                    <i class="fas fa-unlink me-2"></i>Confirmer le désabonnement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Alternatives -->
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-lightbulb me-2"></i>Plutôt que de vous désabonner...
                        </h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-cog fa-2x text-info mb-2"></i>
                                <h6>Modifiez vos préférences</h6>
                                <p class="small text-muted">Choisissez le type d'emails que vous souhaitez recevoir</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-clock fa-2x text-success mb-2"></i>
                                <h6>Réduisez la fréquence</h6>
                                <p class="small text-muted">Recevez uniquement les actualités importantes</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h6>Contactez-nous</h6>
                                <p class="small text-muted">Dites-nous comment améliorer nos communications</p>
                            </div>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary">
                            <i class="fas fa-comments me-2"></i>Nous contacter
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Message de succès ou déjà désabonné -->
                <div class="card border-0 shadow">
                    <div class="card-body text-center py-5">
                        <?php if ($message_type === 'success'): ?>
                            <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                            <h3 class="text-success">Désabonnement confirmé</h3>
                            <p class="text-muted mb-4">
                                Vous ne recevrez plus d'emails de notre part. 
                                Merci d'avoir été abonné(e) à notre newsletter.
                            </p>
                        <?php else: ?>
                            <i class="fas fa-info-circle fa-4x text-info mb-4"></i>
                            <h3 class="text-info">Information</h3>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary me-3">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                            <a href="<?php echo SITE_URL; ?>/actualites.php" class="btn btn-outline-primary">
                                <i class="fas fa-newspaper me-2"></i>Voir les actualités
                            </a>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <p class="small text-muted mb-0">
                                Vous souhaitez vous réabonner ? 
                                <a href="<?php echo SITE_URL; ?>/actualites.php#newsletter">Cliquez ici</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Fonctions utilitaires

function logUnsubscribeActivity($abonne_id, $email, $raison) {
    try {
        $conn = getConnection();
        $details = "Désabonnement newsletter pour $email";
        if (!empty($raison)) {
            $details .= " - Raison: $raison";
        }
        
        $log_query = "INSERT INTO logs_activites 
                     (utilisateur_id, action, table_concernee, id_enregistrement, details, adresse_ip, date_action) 
                     VALUES (NULL, 'newsletter_unsubscribe', 'newsletter_abonnes', :abonne_id, :details, :ip, NOW())";
        
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bindParam(':abonne_id', $abonne_id);
        $log_stmt->bindParam(':details', $details);
        $log_stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
    } catch(Exception $e) {
        error_log("Erreur log unsubscribe: " . $e->getMessage());
    }
}

function sendUnsubscribeConfirmation($email) {
    try {
        $subject = "Confirmation de désabonnement - SOCOU_U";
        $message = "
        <html>
        <head>
            <title>Désabonnement confirmé</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>SOCOU_U</h1>
                <p>Désabonnement confirmé</p>
            </div>
            
            <div class='content'>
                <h2>Nous confirmons votre désabonnement</h2>
                
                <p>Votre adresse email <strong>" . htmlspecialchars($email) . "</strong> a été supprimée de notre liste de diffusion.</p>
                
                <p>Vous ne recevrez plus :</p>
                <ul>
                    <li>Nos actualités</li>
                    <li>Les annonces d'événements</li>
                    <li>Les informations sur nos formations</li>
                    <li>Nos communications promotionnelles</li>
                </ul>
                
                <p>Si vous changez d'avis, vous pourrez vous réabonner à tout moment sur notre site web.</p>
                
                <p>Merci d'avoir été abonné(e) à notre newsletter. Nous espérons vous revoir bientôt !</p>
                
                <p>Cordialement,<br>L'équipe SOCOU_U</p>
            </div>
            
            <div class='footer'>
                <p>SOCOU_U - Province de Bujumbura, Zone Rohero, Commune Mukaza</p>
                <p><a href='" . SITE_URL . "'>Visiter notre site web</a></p>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: SOCOU_U <noreply@socou-u.bi>',
            'Reply-To: contact@socou-u.bi',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        mail($email, $subject, $message, implode("\r\n", $headers));
        
    } catch(Exception $e) {
        error_log("Erreur envoi confirmation désabonnement: " . $e->getMessage());
    }
}

require_once __DIR__ . '/../includes/footer.php';
?>