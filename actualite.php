<?php
require_once __DIR__ . '/config/database.php';

// Récupération de l'ID de l'actualité
$id_actualite = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_actualite <= 0) {
    header("Location: " . SITE_URL . "/actualites.php");
    exit();
}

try {
    $conn = getConnection();
    
    // Récupération de l'article
    $query = "
        SELECT a.*, u.username, m.nom, m.prenom
        FROM actualites a
        LEFT JOIN utilisateurs u ON a.auteur_id = u.id_utilisateur
        LEFT JOIN membres m ON u.id_membre = m.id_membre
        WHERE a.id_actualite = :id AND a.statut = 'publie'
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_actualite, PDO::PARAM_INT);
    $stmt->execute();
    $article = $stmt->fetch();
    
    if (!$article) {
        // Article par défaut si non trouvé en base
        $articles_defaut = [
            1 => [
                'id_actualite' => 1,
                'titre' => 'Lancement de la nouvelle saison agricole 2025',
                'resume' => 'SOCOU_U accompagne ses membres dans le démarrage de la saison agricole avec de nouvelles variétés améliorées.',
                'contenu' => '<h3>Une saison prometteuse s\'annonce</h3>
                <p>La coopérative SOCOU_U a officiellement lancé la saison agricole 2025 avec la distribution de semences améliorées à plus de 150 producteurs membres. Cette initiative s\'inscrit dans notre mission de moderniser l\'agriculture locale et d\'améliorer les rendements.</p>

                <h4>Nouvelles variétés distribuées</h4>
                <ul>
                    <li><strong>Maïs hybride :</strong> Variété résistante à la sécheresse avec un rendement 40% supérieur</li>
                    <li><strong>Haricots améliorés :</strong> Cycle court de 75 jours, adapté au climat local</li>
                    <li><strong>Légumes diversifiés :</strong> Tomates, choux, carottes pour la sécurité alimentaire</li>
                </ul>

                <p>Chaque producteur a reçu un kit complet comprenant les semences, les conseils techniques et un suivi personnalisé durant toute la saison.</p>

                <blockquote class="blockquote">
                    "Cette initiative va révolutionner notre agriculture. Nous attendons des résultats exceptionnels cette saison."
                    <footer class="blockquote-footer">Jean Baptiste, producteur membre depuis 2020</footer>
                </blockquote>

                <h4>Accompagnement technique</h4>
                <p>SOCOU_U ne se contente pas de distribuer les semences. Nos techniciens agricoles assureront un suivi régulier sur le terrain pour :</p>
                <ul>
                    <li>Conseiller sur les bonnes pratiques de semis</li>
                    <li>Superviser l\'application des engrais</li>
                    <li>Prévenir et traiter les maladies des cultures</li>
                    <li>Optimiser les rendements</li>
                </ul>

                <p>Les premiers résultats sont attendus dès le mois de juin 2025. Cette initiative s\'inscrit dans notre vision de faire de SOCOU_U le leader de l\'agriculture moderne au Burundi.</p>',
                'image' => 'saison-agricole-2025.jpg',
                'type_actualite' => 'nouvelle',
                'date_publication' => date('Y-m-d'),
                'date_creation' => date('Y-m-d H:i:s'),
                'vues' => 127,
                'mots_cles' => 'agriculture, semences, saison 2025, producteurs',
                'nom' => 'SOCOU_U',
                'prenom' => 'Équipe'
            ],
            2 => [
                'id_actualite' => 2,
                'titre' => 'Formation en techniques modernes d\'élevage',
                'resume' => 'Une formation de 3 jours sur les techniques modernes d\'élevage organisée pour nos membres éleveurs.',
                'contenu' => '<h3>Formation intensive du 15 au 17 mars 2025</h3>
                <p>SOCOU_U organise une formation de trois jours sur les techniques modernes d\'élevage destinée à ses membres éleveurs. Cette formation vise à améliorer la productivité et la rentabilité des activités d\'élevage.</p>

                <h4>Programme de formation</h4>
                <p><strong>Jour 1 :</strong> Amélioration génétique et sélection des races</p>
                <ul>
                    <li>Critères de sélection des reproducteurs</li>
                    <li>Techniques d\'insémination artificielle</li>
                    <li>Gestion de la consanguinité</li>
                </ul>

                <p><strong>Jour 2 :</strong> Nutrition et alimentation</p>
                <ul>
                    <li>Calcul des rations alimentaires</li>
                    <li>Production de fourrage de qualité</li>
                    <li>Conservation des aliments</li>
                </ul>

                <p><strong>Jour 3 :</strong> Santé animale et gestion</p>
                <ul>
                    <li>Prévention des maladies courantes</li>
                    <li>Calendrier de vaccination</li>
                    <li>Gestion des records d\'élevage</li>
                </ul>

                <h4>Informations pratiques</h4>
                <p><strong>Dates :</strong> Du 15 au 17 mars 2025<br>
                <strong>Lieu :</strong> Centre de formation SOCOU_U, Zone Rohero<br>
                <strong>Horaires :</strong> 8h00 - 17h00<br>
                <strong>Formateur :</strong> Dr. Pierre Niyongabo, Vétérinaire expert</p>

                <div class="alert alert-info">
                    <strong>Inscription gratuite pour les membres !</strong><br>
                    Les non-membres peuvent participer moyennant 15.000 BIF.
                </div>

                <p>À l\'issue de la formation, chaque participant recevra un certificat de participation et un manuel de référence sur l\'élevage moderne.</p>',
                'image' => 'formation-elevage.jpg',
                'type_actualite' => 'evenement',
                'date_publication' => date('Y-m-d', strtotime('+5 days')),
                'date_creation' => date('Y-m-d H:i:s'),
                'vues' => 89,
                'mots_cles' => 'formation, élevage, techniques modernes',
                'nom' => 'Formation',
                'prenom' => 'Équipe'
            ],
            3 => [
                'id_actualite' => 3,
                'titre' => 'Nouveau partenariat avec la Banque Agricole',
                'resume' => 'SOCOU_U signe un accord de partenariat avec la Banque Agricole pour faciliter l\'accès au crédit.',
                'contenu' => '<h3>Un partenariat stratégique pour nos membres</h3>
                <p>La coopérative SOCOU_U a signé un accord de partenariat stratégique avec la Banque Agricole du Burundi pour faciliter l\'accès au crédit agricole à nos membres. Cet accord ouvre de nouvelles perspectives de financement pour le développement des activités agropastorales.</p>

                <h4>Avantages du partenariat</h4>
                <ul>
                    <li><strong>Taux préférentiels :</strong> Réduction de 2% sur les taux d\'intérêt classiques</li>
                    <li><strong>Procédures simplifiées :</strong> Dossiers traités en 15 jours maximum</li>
                    <li><strong>Garantie collective :</strong> SOCOU_U se porte garant pour ses membres</li>
                    <li><strong>Accompagnement technique :</strong> Suivi des projets financés</li>
                </ul>

                <h4>Types de crédit disponibles</h4>
                <p><strong>Crédit de campagne :</strong> Pour l\'achat d\'intrants agricoles (semences, engrais, pesticides)</p>
                <ul>
                    <li>Montant : 50.000 à 500.000 BIF</li>
                    <li>Durée : 6 à 12 mois</li>
                    <li>Taux : 12% par an</li>
                </ul>

                <p><strong>Crédit d\'équipement :</strong> Pour l\'acquisition de matériel agricole</p>
                <ul>
                    <li>Montant : 200.000 à 2.000.000 BIF</li>
                    <li>Durée : 2 à 5 ans</li>
                    <li>Taux : 14% par an</li>
                </ul>

                <p><strong>Crédit élevage :</strong> Pour le développement des activités d\'élevage</p>
                <ul>
                    <li>Montant : 100.000 à 1.500.000 BIF</li>
                    <li>Durée : 1 à 3 ans</li>
                    <li>Taux : 13% par an</li>
                </ul>

                <div class="alert alert-success">
                    <h5>Comment bénéficier de ces crédits ?</h5>
                    <ol>
                        <li>Être membre actif de SOCOU_U</li>
                        <li>Présenter un projet viable</li>
                        <li>Déposer le dossier au secrétariat de la coopérative</li>
                        <li>Validation par le comité de crédit</li>
                        <li>Signature du contrat avec la banque</li>
                    </ol>
                </div>

                <p>Ce partenariat s\'inscrit dans notre stratégie de modernisation et d\'expansion des activités de nos membres. Il permettra de financer de nombreux projets qui étaient jusqu\'ici difficilement réalisables.</p>',
                'image' => 'partenariat-banque.jpg',
                'type_actualite' => 'annonce',
                'date_publication' => date('Y-m-d', strtotime('-3 days')),
                'date_creation' => date('Y-m-d H:i:s'),
                'vues' => 203,
                'mots_cles' => 'partenariat, crédit, banque agricole, financement',
                'nom' => 'Direction',
                'prenom' => 'Équipe'
            ]
        ];
        
        $article = isset($articles_defaut[$id_actualite]) ? $articles_defaut[$id_actualite] : null;
    }
    
    if (!$article) {
        header("Location: " . SITE_URL . "/actualites.php");
        exit();
    }
    
    // Mise à jour du nombre de vues si l'article existe en base
    if (isset($article['id_actualite'])) {
        try {
            $update_query = "UPDATE actualites SET vues = vues + 1 WHERE id_actualite = :id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':id', $id_actualite, PDO::PARAM_INT);
            $update_stmt->execute();
        } catch(PDOException $e) {
            // Ignorer l'erreur de mise à jour des vues
        }
    }
    
    // Récupération d'articles similaires
    $related_query = "
        SELECT id_actualite, titre, image, date_publication, type_actualite
        FROM actualites 
        WHERE id_actualite != :id AND statut = 'publie' 
        ORDER BY date_publication DESC 
        LIMIT 3
    ";
    
    try {
        $related_stmt = $conn->prepare($related_query);
        $related_stmt->bindParam(':id', $id_actualite, PDO::PARAM_INT);
        $related_stmt->execute();
        $articles_similaires = $related_stmt->fetchAll();
    } catch(PDOException $e) {
        $articles_similaires = [];
    }
    
} catch(PDOException $e) {
    header("Location: " . SITE_URL . "/actualites.php");
    exit();
}

// Configuration de la page
$page_title = $article['titre'];
$page_description = $article['resume'] ?: substr(strip_tags($article['contenu']), 0, 160);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Accueil</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo SITE_URL; ?>/actualites.php">Actualités</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo escape($article['titre']); ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <!-- Contenu principal -->
        <div class="col-lg-8">
            <article class="mb-5">
                <!-- En-tête de l'article -->
                <header class="mb-4">
                    <!-- Badge type -->
                    <div class="mb-3">
                        <?php
                        $badge_class = 'badge bg-info fs-6';
                        $badge_text = 'Nouvelle';
                        $badge_icon = 'fas fa-newspaper';
                        
                        if ($article['type_actualite'] == 'evenement') {
                            $badge_class = 'badge bg-success fs-6';
                            $badge_text = 'Événement';
                            $badge_icon = 'fas fa-calendar-alt';
                        } elseif ($article['type_actualite'] == 'annonce') {
                            $badge_class = 'badge bg-warning fs-6';
                            $badge_text = 'Annonce';
                            $badge_icon = 'fas fa-bullhorn';
                        }
                        ?>
                        <span class="<?php echo $badge_class; ?>">
                            <i class="<?php echo $badge_icon; ?> me-1"></i><?php echo $badge_text; ?>
                        </span>
                    </div>

                    <!-- Titre -->
                    <h1 class="display-5 fw-bold text-primary mb-3">
                        <?php echo escape($article['titre']); ?>
                    </h1>

                    <!-- Métadonnées -->
                    <div class="row align-items-center text-muted mb-4">
                        <div class="col-md-6">
                            <i class="fas fa-calendar me-1"></i>
                            Publié le <?php echo date('d/m/Y à H:i', strtotime($article['date_publication'] ?: $article['date_creation'])); ?>
                        </div>
                        <div class="col-md-3">
                            <?php if (!empty($article['nom'])): ?>
                                <i class="fas fa-user me-1"></i>
                                <?php echo escape($article['prenom'] . ' ' . $article['nom']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <i class="fas fa-eye me-1"></i>
                            <?php echo number_format($article['vues'] ?? 0); ?> vues
                        </div>
                    </div>

                    <!-- Image principale -->
                    <?php if (!empty($article['image'])): ?>
                        <div class="mb-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/actualites/<?php echo escape($article['image']); ?>" 
                                 alt="<?php echo escape($article['titre']); ?>"
                                 class="img-fluid rounded shadow">
                        </div>
                    <?php endif; ?>

                    <!-- Résumé -->
                    <?php if (!empty($article['resume'])): ?>
                        <div class="alert alert-light border-start border-primary border-4 ps-4">
                            <p class="lead mb-0">
                                <strong><?php echo escape($article['resume']); ?></strong>
                            </p>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Contenu de l'article -->
                <div class="article-content">
                    <?php echo $article['contenu']; ?>
                </div>

                <!-- Mots-clés -->
                <?php if (!empty($article['mots_cles'])): ?>
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-tags me-2"></i>Mots-clés :
                        </h6>
                        <div>
                            <?php
                            $mots_cles = explode(',', $article['mots_cles']);
                            foreach ($mots_cles as $mot_cle):
                                $mot_cle = trim($mot_cle);
                            ?>
                                <span class="badge bg-secondary me-2 mb-1">
                                    <?php echo escape($mot_cle); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Partage social -->
                <div class="mt-4 pt-4 border-top">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-share-alt me-2"></i>Partager cet article :
                    </h6>
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/actualite.php?id=' . $article['id_actualite']); ?>" 
                           target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-facebook-f me-1"></i>Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/actualite.php?id=' . $article['id_actualite']); ?>&text=<?php echo urlencode($article['titre']); ?>" 
                           target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fab fa-twitter me-1"></i>Twitter
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/actualite.php?id=' . $article['id_actualite']); ?>" 
                           target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-linkedin-in me-1"></i>LinkedIn
                        </a>
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard()">
                            <i class="fas fa-link me-1"></i>Copier le lien
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Articles similaires -->
            <?php if (!empty($articles_similaires)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-newspaper me-2"></i>Articles similaires
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($articles_similaires as $similaire): ?>
                            <div class="d-flex mb-3 <?php echo $similaire !== end($articles_similaires) ? 'border-bottom pb-3' : ''; ?>">
                                <div class="flex-shrink-0 me-3">
                                    <?php if (!empty($similaire['image'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/assets/images/actualites/<?php echo escape($similaire['image']); ?>" 
                                             alt="<?php echo escape($similaire['titre']); ?>"
                                             class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-newspaper text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?php echo SITE_URL; ?>/actualite.php?id=<?php echo $similaire['id_actualite']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo escape($similaire['titre']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($similaire['date_publication'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Newsletter -->
            <div class="card border-0 shadow-sm bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white text-center">
                    <i class="fas fa-envelope fa-3x mb-3 opacity-75"></i>
                    <h5 class="card-title">Ne manquez rien !</h5>
                    <p class="card-text mb-4">
                        Abonnez-vous à notre newsletter pour recevoir toutes nos actualités.
                    </p>
                    <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newsletterModal">
                        <i class="fas fa-bell me-2"></i>S'abonner
                    </button>
                </div>
            </div>

            <!-- Retour aux actualités -->
            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>/actualites.php" class="btn btn-outline-primary btn-lg w-100">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux actualités
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.article-content {
    font-size: 1.1em;
    line-height: 1.8;
}

.article-content h3 {
    color: #0d6efd;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-content h4 {
    color: #6f42c1;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.article-content ul, .article-content ol {
    margin-bottom: 1.5rem;
}

.article-content li {
    margin-bottom: 0.5rem;
}

.article-content blockquote {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
    margin: 2rem 0;
    font-style: italic;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
}

.article-content .alert {
    margin: 2rem 0;
}

.badge {
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }
    
    .article-content {
        font-size: 1rem;
    }
}
</style>

<script>
function copyToClipboard() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(function() {
        alert('Lien copié dans le presse-papiers !');
    }, function(err) {
        console.error('Erreur lors de la copie : ', err);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>