<?php
$page_title = "Accueil";
$page_description = "Bienvenue sur le site officiel de SOCOU_U - Société Coopérative UMUSHINGE W'UBUZIMA. Découvrez nos produits agropastoraux, nos formations et nos projets sociaux.";

require_once 'includes/header.php';
require_once 'includes/functions.php';

// Obtenir les statistiques et données pour l'accueil
$stats = getGeneralStats();
$latest_news = getLatestNews(3);
$latest_products = getLatestProducts(8);
$testimonials = getApprovedTestimonials(3);
?>

<!-- Section Hero -->
<section class="hero-section">
    <div class="sun"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="hero-title animated-text">
                    Bienvenue dans notre <span class="text-warning glowing-text">SOCOU_U</span>
                </h1>
                <p class="hero-subtitle slide-in-left"><?php echo ConfigManager::get('slogan', 'Solidarité, Autonomie et Développement Durable'); ?></p>
                <p class="lead mb-4 fade-in-up">
                    Société Coopérative dédiée au développement agropastoral et social au Burundi. 
                    Ensemble, construisons un avenir prospère pour nos communautés.
                </p>
                <div class="hero-buttons bounce-in">
                    <a href="<?php echo SITE_URL; ?>/a-propos.php" class="btn btn-warning btn-lg btn-rounded me-3 pulse-btn">
                        <i class="fas fa-info-circle"></i> Découvrir SOCOU_U
                    </a>
                    <a href="<?php echo SITE_URL; ?>/produits.php" class="btn btn-outline-light btn-lg btn-rounded shine-btn">
                        <i class="fas fa-shopping-bag"></i> Nos Produits
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image-container">
                    <img src="<?php echo SITE_URL; ?>/assets/images/hero-illustration.png" alt="SOCOU_U" class="img-fluid floating-image" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Statistiques -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number counter" data-target="<?php echo $stats['membres_actifs']; ?>">0</div>
                    <div class="stats-label">Membres Actifs</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number counter" data-target="<?php echo $stats['produits_disponibles']; ?>">0</div>
                    <div class="stats-label">Produits Disponibles</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number counter" data-target="<?php echo $stats['projets_actifs']; ?>">0</div>
                    <div class="stats-label">Projets Actifs</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number counter" data-target="<?php echo $stats['formations_programmees']; ?>">0</div>
                    <div class="stats-label">Formations Programmées</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section À propos -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title text-start">Qui Sommes-Nous ?</h2>
                <p class="lead">
                    Fondée en <?php echo ConfigManager::get('annee_fondation', '2019'); ?>, la Société Coopérative UMUSHINGE W'UBUZIMA (SOCOU_U) 
                    est un pilier du développement agropastoral au Burundi.
                </p>
                <p>
                    Notre coopérative regroupe des producteurs, transformateurs et commerçants unis par une vision commune : 
                    améliorer les conditions de vie des populations rurales à travers une agriculture moderne, durable et profitable.
                </p>
                <div class="row g-3 mt-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-seedling text-dark fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1">Agriculture Moderne</h6>
                                <small class="text-muted">Techniques innovantes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users text-dark fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1">Solidarité</h6>
                                <small class="text-muted">Entraide communautaire</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-leaf text-dark fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1">Durabilité</h6>
                                <small class="text-muted">Respect de l'environnement</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-chart-line text-dark fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1">Croissance</h6>
                                <small class="text-muted">Développement économique</small>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="<?php echo SITE_URL; ?>/a-propos.php" class="btn btn-primary btn-rounded mt-4">
                    <i class="fas fa-arrow-right"></i> En savoir plus
                </a>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo SITE_URL; ?>/assets/images/about-us.jpg" alt="À propos de SOCOU_U" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Section Actualités -->
<?php if (!empty($latest_news)): ?>
<section class="section-padding bg-light">
    <div class="container">
        <h2 class="section-title">Dernières Actualités</h2>
        <p class="section-subtitle">Restez informés de nos dernières activités et réalisations</p>
        
        <div class="row g-4">
            <?php foreach ($latest_news as $news): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card news-card">
                    <?php if ($news['image']): ?>
                    <img src="<?php echo UPLOAD_URL . $news['image']; ?>" alt="<?php echo escape($news['titre']); ?>" class="news-image">
                    <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-news.jpg" alt="<?php echo escape($news['titre']); ?>" class="news-image">
                    <?php endif; ?>
                    
                    <div class="news-content">
                        <h5 class="news-title">
                            <a href="<?php echo SITE_URL; ?>/actualite.php?id=<?php echo $news['id_actualite']; ?>" class="text-decoration-none">
                                <?php echo escape($news['titre']); ?>
                            </a>
                        </h5>
                        
                        <?php if ($news['resume']): ?>
                        <p class="news-excerpt"><?php echo escape(truncateText($news['resume'], 100)); ?></p>
                        <?php else: ?>
                        <p class="news-excerpt"><?php echo escape(truncateText(strip_tags($news['contenu']), 100)); ?></p>
                        <?php endif; ?>
                        
                        <div class="news-meta d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> <?php echo formatDate($news['date_creation']); ?>
                            </small>
                            <a href="<?php echo SITE_URL; ?>/actualite.php?id=<?php echo $news['id_actualite']; ?>" class="btn btn-sm btn-outline-primary">
                                Lire plus
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/actualites.php" class="btn btn-primary btn-rounded">
                <i class="fas fa-newspaper"></i> Toutes les actualités
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Section Produits -->
<?php if (!empty($latest_products)): ?>
<section class="section-padding">
    <div class="container">
        <h2 class="section-title">Nos Produits</h2>
        <p class="section-subtitle">Découvrez notre sélection de produits agropastoraux de qualité</p>
        
        <div class="row g-4">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card product-card">
                    <?php if ($product['image_principale']): ?>
                    <img src="<?php echo UPLOAD_URL . $product['image_principale']; ?>" alt="<?php echo escape($product['nom_produit']); ?>" class="product-image">
                    <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-product.jpg" alt="<?php echo escape($product['nom_produit']); ?>" class="product-image">
                    <?php endif; ?>
                    
                    <span class="badge product-badge bg-dark"><?php echo escape($product['nom_categorie']); ?></span>
                    
                    <div class="product-content">
                        <h6 class="product-title"><?php echo escape($product['nom_produit']); ?></h6>
                        
                        <?php if ($product['prix_unitaire']): ?>
                        <div class="product-price"><?php echo formatMoney($product['prix_unitaire']); ?>/<?php echo escape($product['unite_mesure']); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($product['description']): ?>
                        <p class="product-description"><?php echo escape(truncateText($product['description'], 80)); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Par <?php echo escape($product['prenom'] . ' ' . $product['nom']); ?>
                            </small>
                            <a href="<?php echo SITE_URL; ?>/produit.php?id=<?php echo $product['id_produit']; ?>" class="btn btn-sm btn-primary">
                                Voir détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/produits.php" class="btn btn-primary btn-rounded">
                <i class="fas fa-shopping-bag"></i> Tous nos produits
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Section Services -->
<section class="section-padding bg-light">
    <div class="container">
        <h2 class="section-title">Nos Services</h2>
        <p class="section-subtitle">SOCOU_U vous accompagne dans tous vos projets agropastoraux</p>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h5 class="card-title">Formations</h5>
                        <p class="card-text">
                            Formations techniques en agriculture moderne, élevage, transformation 
                            et commercialisation des produits agropastoraux.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/formations.php" class="btn btn-outline-primary">
                            Découvrir les formations
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h5 class="card-title">Projets Sociaux</h5>
                        <p class="card-text">
                            Initiatives sociales pour le développement communautaire, 
                            l'amélioration des conditions de vie et l'autonomisation.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/projets.php" class="btn btn-outline-primary">
                            Voir nos projets
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-store"></i>
                        </div>
                        <h5 class="card-title">Commerce</h5>
                        <p class="card-text">
                            Facilitation de l'accès aux marchés, commercialisation 
                            des produits et développement des chaînes de valeur.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/produits.php" class="btn btn-outline-primary">
                            Voir nos produits
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Témoignages -->
<?php if (!empty($testimonials)): ?>
<section class="section-padding">
    <div class="container">
        <h2 class="section-title">Témoignages</h2>
        <p class="section-subtitle">Ce que disent nos membres et partenaires</p>
        
        <div class="row g-4">
            <?php foreach ($testimonials as $testimonial): ?>
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <p class="testimonial-content"><?php echo escape($testimonial['contenu']); ?></p>
                    
                    <div class="testimonial-author">
                        <?php if ($testimonial['photo']): ?>
                        <img src="<?php echo UPLOAD_URL . $testimonial['photo']; ?>" alt="<?php echo escape($testimonial['auteur']); ?>" class="testimonial-avatar">
                        <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="<?php echo escape($testimonial['auteur']); ?>" class="testimonial-avatar">
                        <?php endif; ?>
                        
                        <div class="testimonial-info">
                            <h6><?php echo escape($testimonial['auteur']); ?></h6>
                            <?php if ($testimonial['fonction']): ?>
                            <small><?php echo escape($testimonial['fonction']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Section Call to Action -->
<section class="section-padding bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Rejoignez SOCOU_U</h2>
        <p class="lead mb-5">
            Ensemble, construisons un avenir prospère pour l'agriculture burundaise. 
            Devenez membre de notre coopérative et bénéficiez de nos services.
        </p>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row g-3">
                    <?php if (!isLoggedIn()): ?>
                    <div class="col-md-6">
                        <a href="<?php echo SITE_URL; ?>/inscription.php" class="btn btn-warning btn-lg btn-rounded w-100">
                            <i class="fas fa-user-plus"></i> Devenir Membre
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-light btn-lg btn-rounded w-100">
                            <i class="fas fa-phone"></i> Nous Contacter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>