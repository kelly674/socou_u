<?php
$page_title = "Actualités";
$page_description = "Suivez toutes les actualités, événements et annonces de la Société Coopérative UMUSHINGE W'UBUZIMA";
require_once 'includes/header.php';

// Pagination
$page_actuelle = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$articles_par_page = 6;
$offset = ($page_actuelle - 1) * $articles_par_page;

// Filtre par type
$type_filtre = isset($_GET['type']) ? $_GET['type'] : '';
$where_clause = "WHERE statut = 'publie'";
if ($type_filtre && in_array($type_filtre, ['nouvelle', 'evenement', 'annonce'])) {
    $where_clause .= " AND type_actualite = :type";
}

try {
    $conn = getConnection();
    // Comptage total des articles
    $count_query = "SELECT COUNT(*) FROM actualites $where_clause";
    $count_stmt = $conn->prepare($count_query);
    if ($type_filtre) {
        $count_stmt->bindParam(':type', $type_filtre);
    }
    $count_stmt->execute();
    $total_articles = $count_stmt->fetchColumn();
    
    // Récupération des articles
    $query = "
        SELECT a.*, u.username, m.nom, m.prenom
        FROM actualites a
        LEFT JOIN utilisateurs u ON a.auteur_id = u.id_utilisateur
        LEFT JOIN membres m ON u.id_membre = m.id_membre
        $where_clause
        ORDER BY a.date_publication DESC, a.date_creation DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $conn->prepare($query);
    if ($type_filtre) {
        $stmt->bindParam(':type', $type_filtre);
    }
    $stmt->bindParam(':limit', $articles_par_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    // Calcul du nombre de pages
    $total_pages = ceil($total_articles / $articles_par_page);
    
} catch(PDOException $e) {
    $articles = [];
    $total_articles = 0;
    $total_pages = 0;
}

// Articles par défaut si la base est vide
if (empty($articles)) {
    $articles_defaut = [
        [
            'id_actualite' => 1,
            'titre' => 'Lancement de la nouvelle saison agricole 2025',
            'resume' => 'SOCOU_U accompagne ses membres dans le démarrage de la saison agricole avec de nouvelles variétés améliorées.',
            'contenu' => 'La coopérative SOCOU_U a officiellement lancé la saison agricole 2025 avec la distribution de semences améliorées à plus de 150 producteurs membres...',
            'image' => 'saison-agricole-2025.jpg',
            'type_actualite' => 'nouvelle',
            'date_publication' => date('Y-m-d'),
            'auteur_id' => null,
            'nom' => 'SOCOU_U',
            'prenom' => 'Équipe'
        ],
        [
            'id_actualite' => 2,
            'titre' => 'Formation en techniques modernes d\'élevage',
            'resume' => 'Une formation de 3 jours sur les techniques modernes d\'élevage organisée pour nos membres éleveurs.',
            'contenu' => 'Du 15 au 17 mars 2025, SOCOU_U organise une formation intensive sur les techniques modernes d\'élevage...',
            'image' => 'formation-elevage.jpg',
            'type_actualite' => 'evenement',
            'date_publication' => date('Y-m-d', strtotime('+5 days')),
            'auteur_id' => null,
            'nom' => 'Formation',
            'prenom' => 'Équipe'
        ],
        [
            'id_actualite' => 3,
            'titre' => 'Nouveau partenariat avec la Banque Agricole',
            'resume' => 'SOCOU_U signe un accord de partenariat avec la Banque Agricole pour faciliter l\'accès au crédit.',
            'contenu' => 'La coopérative SOCOU_U a signé un accord de partenariat stratégique avec la Banque Agricole du Burundi...',
            'image' => 'partenariat-banque.jpg',
            'type_actualite' => 'annonce',
            'date_publication' => date('Y-m-d', strtotime('-3 days')),
            'auteur_id' => null,
            'nom' => 'Direction',
            'prenom' => 'Équipe'
        ]
    ];
    
    // Filtrer les articles par défaut si nécessaire
    if ($type_filtre) {
        $articles_defaut = array_filter($articles_defaut, function($article) use ($type_filtre) {
            return $article['type_actualite'] === $type_filtre;
        });
    }
    
    $articles = array_slice($articles_defaut, $offset, $articles_par_page);
    $total_articles = count($articles_defaut);
    $total_pages = ceil($total_articles / $articles_par_page);
}
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold text-primary mb-3">Actualités</h1>
            <p class="lead">
                Restez informés des dernières nouvelles, événements et annonces de SOCOU_U. 
                Découvrez nos réalisations, nos projets en cours et nos prochains événements.
            </p>
        </div>
        <div class="col-lg-4 text-center">
            <i class="fas fa-newspaper fa-5x text-primary opacity-75"></i>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2"></i>Filtrer par type :
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group">
                                <a href="<?php echo SITE_URL; ?>/actualites.php" 
                                   class="btn <?php echo empty($type_filtre) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-th-large me-1"></i>Toutes
                                </a>
                                <a href="<?php echo SITE_URL; ?>/actualites.php?type=nouvelle" 
                                   class="btn <?php echo $type_filtre == 'nouvelle' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    <i class="fas fa-newspaper me-1"></i>Nouvelles
                                </a>
                                <a href="<?php echo SITE_URL; ?>/actualites.php?type=evenement" 
                                   class="btn <?php echo $type_filtre == 'evenement' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <i class="fas fa-calendar me-1"></i>Événements
                                </a>
                                <a href="<?php echo SITE_URL; ?>/actualites.php?type=annonce" 
                                   class="btn <?php echo $type_filtre == 'annonce' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                    <i class="fas fa-bullhorn me-1"></i>Annonces
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles -->
    <?php if (!empty($articles)): ?>
        <div class="row">
            <?php foreach($articles as $article): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <!-- Image de l'article -->
                        <div class="card-img-top-container" style="height: 200px; overflow: hidden;">
                            <?php if (!empty($article['image'])): ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/actualites/<?php echo escape($article['image']); ?>" 
                                     alt="<?php echo escape($article['titre']); ?>"
                                     class="card-img-top w-100 h-100" 
                                     style="object-fit: cover;">
                            <?php else: ?>
                                <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                    <?php
                                    $icon = 'fas fa-newspaper';
                                    $color = 'text-info';
                                    if ($article['type_actualite'] == 'evenement') {
                                        $icon = 'fas fa-calendar-alt';
                                        $color = 'text-success';
                                    } elseif ($article['type_actualite'] == 'annonce') {
                                        $icon = 'fas fa-bullhorn';
                                        $color = 'text-warning';
                                    }
                                    ?>
                                    <i class="<?php echo $icon; ?> fa-4x <?php echo $color; ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <!-- Badge type -->
                            <div class="mb-2">
                                <?php
                                $badge_class = 'badge bg-info';
                                $badge_text = 'Nouvelle';
                                $badge_icon = 'fas fa-newspaper';
                                
                                if ($article['type_actualite'] == 'evenement') {
                                    $badge_class = 'badge bg-success';
                                    $badge_text = 'Événement';
                                    $badge_icon = 'fas fa-calendar-alt';
                                } elseif ($article['type_actualite'] == 'annonce') {
                                    $badge_class = 'badge bg-warning';
                                    $badge_text = 'Annonce';
                                    $badge_icon = 'fas fa-bullhorn';
                                }
                                ?>
                                <span class="<?php echo $badge_class; ?>">
                                    <i class="<?php echo $badge_icon; ?> me-1"></i><?php echo $badge_text; ?>
                                </span>
                            </div>

                            <!-- Titre -->
                            <h5 class="card-title">
                                <a href="<?php echo SITE_URL; ?>/actualite.php?id=<?php echo $article['id_actualite']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo escape($article['titre']); ?>
                                </a>
                            </h5>

                            <!-- Résumé -->
                            <p class="card-text flex-grow-1">
                                <?php 
                                $resume = !empty($article['resume']) ? $article['resume'] : substr(strip_tags($article['contenu']), 0, 150);
                                echo escape(substr($resume, 0, 120)) . (strlen($resume) > 120 ? '...' : '');
                                ?>
                            </p>

                            <!-- Métadonnées -->
                            <div class="card-footer bg-transparent border-0 px-0 mt-auto">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($article['date_publication'])); ?>
                                        </small>
                                        <?php if (!empty($article['nom'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo escape($article['prenom'] . ' ' . $article['nom']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-auto">
                                        <a href="<?php echo SITE_URL; ?>/actualite.php?id=<?php echo $article['id_actualite']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            Lire plus <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <nav aria-label="Pagination des actualités">
                        <ul class="pagination justify-content-center">
                            <!-- Page précédente -->
                            <?php if ($page_actuelle > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page_actuelle - 1; ?><?php echo $type_filtre ? '&type=' . $type_filtre : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Précédent
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Numéros de pages -->
                            <?php
                            $debut = max(1, $page_actuelle - 2);
                            $fin = min($total_pages, $page_actuelle + 2);
                            
                            for ($i = $debut; $i <= $fin; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page_actuelle ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $type_filtre ? '&type=' . $type_filtre : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Page suivante -->
                            <?php if ($page_actuelle < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page_actuelle + 1; ?><?php echo $type_filtre ? '&type=' . $type_filtre : ''; ?>">
                                        Suivant <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Aucun article trouvé -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucune actualité trouvée</h4>
                    <p class="text-muted">
                        <?php if ($type_filtre): ?>
                            Aucune actualité de type "<?php echo escape($type_filtre); ?>" n'est disponible pour le moment.
                        <?php else: ?>
                            Aucune actualité n'est disponible pour le moment.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/actualites.php" class="btn btn-primary">
                        <i class="fas fa-refresh me-2"></i>Voir toutes les actualités
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Newsletter subscription -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow-lg">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="fw-bold mb-2">
                                <i class="fas fa-envelope-open me-2"></i>Restez informés !
                            </h4>
                            <p class="mb-0">
                                Abonnez-vous à notre newsletter pour recevoir toutes nos actualités directement dans votre boîte mail.
                            </p>
                        </div>
                        <div class="col-md-4 text-center mt-3 mt-md-0">
                            <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newsletterModal">
                                <i class="fas fa-bell me-2"></i>S'abonner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Newsletter -->
<div class="modal fade" id="newsletterModal" tabindex="-1" aria-labelledby="newsletterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="newsletterModalLabel">
                    <i class="fas fa-envelope me-2"></i>Abonnement Newsletter
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="newsletterForm" action="<?php echo SITE_URL; ?>/ajax/subscribe_newsletter.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newsletter_nom" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="newsletter_nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="newsletter_email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="newsletter_email" name="email" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsletter_consent" required>
                        <label class="form-check-label" for="newsletter_consent">
                            J'accepte de recevoir les actualités de SOCOU_U par email et je peux me désabonner à tout moment.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>S'abonner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card-img-top {
    transition: transform 0.3s ease;
}

.card:hover .card-img-top {
    transform: scale(1.05);
}

.btn-group .btn {
    flex: 1;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
    
    .display-4 {
        font-size: 2.5rem;
    }
}

.page-link {
    color: var(--bs-primary);
}

.page-item.active .page-link {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}
</style>

<script>
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    e.preventDefault();
   
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
   
    // Vérification du consentement
    const consentCheckbox = document.getElementById('newsletter_consent');
    if (!consentCheckbox.checked) {
        alert('Vous devez accepter de recevoir nos actualités pour vous abonner.');
        return;
    }
   
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Inscription...';
    submitBtn.disabled = true;
   
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Vérifier si la réponse est OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Vérifier le type de contenu
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Si ce n'est pas du JSON, lire comme texte pour déboguer
            return response.text().then(text => {
                console.error('Réponse non-JSON reçue:', text);
                throw new Error('Réponse invalide du serveur');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Succès - afficher un message plus élégant
            showSuccessMessage(data.message);
            bootstrap.Modal.getInstance(document.getElementById('newsletterModal')).hide();
            this.reset();
        } else {
            // Erreur - afficher le message d'erreur
            showErrorMessage(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'inscription:', error);
        showErrorMessage('Une erreur technique est survenue. Veuillez réessayer plus tard.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Fonction pour afficher un message de succès
function showSuccessMessage(message) {
    // Utiliser une alerte Bootstrap si disponible, sinon alert()
    if (typeof bootstrap !== 'undefined' && document.querySelector('.container')) {
        const alertHTML = `
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insérer l'alerte au début du container principal
        const container = document.querySelector('.container');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHTML);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                const alert = container.querySelector('.alert-success');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        } else {
            alert(message);
        }
    } else {
        alert(message);
    }
}

// Fonction pour afficher un message d'erreur
function showErrorMessage(message) {
    // Utiliser une alerte Bootstrap si disponible, sinon alert()
    if (typeof bootstrap !== 'undefined' && document.querySelector('.container')) {
        const alertHTML = `
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insérer l'alerte au début du container principal
        const container = document.querySelector('.container');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHTML);
            
            // Auto-dismiss après 8 secondes pour les erreurs
            setTimeout(() => {
                const alert = container.querySelector('.alert-danger');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 8000);
        } else {
            alert('Erreur: ' + message);
        }
    } else {
        alert('Erreur: ' + message);
    }
}

// Validation en temps réel des champs
document.getElementById('newsletter_email').addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.classList.add('is-invalid');
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
            this.insertAdjacentHTML('afterend', '<div class="invalid-feedback">Veuillez saisir une adresse email valide.</div>');
        }
    } else {
        this.classList.remove('is-invalid');
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    }
});

document.getElementById('newsletter_nom').addEventListener('blur', function() {
    const nom = this.value.trim();
    
    if (nom && nom.length < 2) {
        this.classList.add('is-invalid');
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
            this.insertAdjacentHTML('afterend', '<div class="invalid-feedback">Le nom doit contenir au moins 2 caractères.</div>');
        }
    } else {
        this.classList.remove('is-invalid');
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>