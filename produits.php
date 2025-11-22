<?php
$page_title = "Nos Produits";
$page_description = "Découvrez la gamme complète des produits agropastoraux de SOCOU_U : céréales, légumes, fruits, élevage et produits transformés";
require_once 'includes/header.php';

// Récupération des catégories et produits
$categorie_filtre = isset($_GET['categorie']) ? $_GET['categorie'] : '';

try {
     $pdo = getConnection();
    // Récupération des catégories
    $stmt_categories = $pdo->query("
        SELECT * FROM categories_produits 
        WHERE statut = 'actif' 
        ORDER BY ordre_affichage, nom_categorie
    ");
    $categories = $stmt_categories->fetchAll();

    // Construction de la requête produits
    $where_clause = "WHERE p.statut IN ('disponible', 'saisonnier')";
    if ($categorie_filtre && is_numeric($categorie_filtre)) {
        $where_clause .= " AND p.id_categorie = :categorie";
    }

    // Récupération des produits
    $query = "
        SELECT p.*, c.nom_categorie, c.icone,
               m.nom as producteur_nom, m.prenom as producteur_prenom
        FROM produits p
        LEFT JOIN categories_produits c ON p.id_categorie = c.id_categorie
        LEFT JOIN membres m ON p.producteur_id = m.id_membre
        $where_clause
        ORDER BY c.nom_categorie, p.nom_produit
    ";
    
    $stmt = $pdo->prepare($query);
    if ($categorie_filtre && is_numeric($categorie_filtre)) {
        $stmt->bindParam(':categorie', $categorie_filtre, PDO::PARAM_INT);
    }
    $stmt->execute();
    $produits = $stmt->fetchAll();

} catch(PDOException $e) {
    $categories = [];
    $produits = [];
}

// Données par défaut si la base est vide
if (empty($categories)) {
    $categories = [
        ['id_categorie' => 1, 'nom_categorie' => 'Céréales', 'icone' => 'fas fa-seedling', 'description' => 'Maïs, riz, sorgho et autres céréales'],
        ['id_categorie' => 2, 'nom_categorie' => 'Légumes', 'icone' => 'fas fa-carrot', 'description' => 'Légumes frais de saison'],
        ['id_categorie' => 3, 'nom_categorie' => 'Fruits', 'icone' => 'fas fa-apple-alt', 'description' => 'Fruits tropicaux et de saison'],
        ['id_categorie' => 4, 'nom_categorie' => 'Élevage', 'icone' => 'fas fa-cow', 'description' => 'Produits d\'élevage et volaille'],
        ['id_categorie' => 5, 'nom_categorie' => 'Transformés', 'icone' => 'fas fa-industry', 'description' => 'Produits transformés et conditionnés']
    ];
}

if (empty($produits)) {
    $produits_defaut = [
        [
            'id_produit' => 1,
            'nom_produit' => 'Maïs blanc',
            'description' => 'Maïs blanc de qualité supérieure, récolte locale',
            'prix_unitaire' => 800,
            'unite_mesure' => 'kg',
            'stock_disponible' => 500,
            'nom_categorie' => 'Céréales',
            'icone' => 'fas fa-seedling',
            'producteur_nom' => 'Cooperative',
            'producteur_prenom' => 'SOCOU_U',
            'statut' => 'disponible',
            'image_principale' => 'mais-blanc.jpg'
        ],
        [
            'id_produit' => 2,
            'nom_produit' => 'Haricots rouges',
            'description' => 'Haricots rouges riches en protéines',
            'prix_unitaire' => 1200,
            'unite_mesure' => 'kg',
            'stock_disponible' => 200,
            'nom_categorie' => 'Légumes',
            'icone' => 'fas fa-carrot',
            'producteur_nom' => 'Producteurs',
            'producteur_prenom' => 'Groupe',
            'statut' => 'disponible',
            'image_principale' => 'haricots-rouges.jpg'
        ]
    ];
    
    // Filtrer par catégorie si nécessaire
    if ($categorie_filtre) {
        $cat_names = ['', 'Céréales', 'Légumes', 'Fruits', 'Élevage', 'Transformés'];
        $cat_name = isset($cat_names[$categorie_filtre]) ? $cat_names[$categorie_filtre] : '';
        $produits_defaut = array_filter($produits_defaut, function($p) use ($cat_name) {
            return $p['nom_categorie'] === $cat_name;
        });
    }
    
    $produits = $produits_defaut;
}
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold text-primary mb-3">Nos Produits</h1>
            <p class="lead">
                Découvrez notre gamme complète de produits agropastoraux de qualité, 
                cultivés et élevés selon les meilleures pratiques par nos membres producteurs.
            </p>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle p-2 me-3">
                            <i class="fas fa-leaf text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">100% Naturel</h6>
                            <small class="text-muted">Sans pesticides</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle p-2 me-3">
                            <i class="fas fa-certificate text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Qualité Contrôlée</h6>
                            <small class="text-muted">Standards élevés</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle p-2 me-3">
                            <i class="fas fa-truck text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Livraison Rapide</h6>
                            <small class="text-muted">Service efficace</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-center">
            <i class="fas fa-shopping-basket fa-5x text-primary opacity-75"></i>
        </div>
    </div>

    <!-- Filtres par catégorie -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-filter me-2"></i>Nos Catégories
                    </h5>
                    <div class="row">
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <a href="<?php echo SITE_URL; ?>/produits.php" 
                               class="btn <?php echo empty($categorie_filtre) ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                                <i class="fas fa-th-large d-block mb-2"></i>
                                <small>Tous</small>
                            </a>
                        </div>
                        <?php foreach($categories as $categorie): ?>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="<?php echo SITE_URL; ?>/produits.php?categorie=<?php echo $categorie['id_categorie']; ?>" 
                                   class="btn <?php echo $categorie_filtre == $categorie['id_categorie'] ? 'btn-success' : 'btn-outline-success'; ?> w-100 h-100 d-flex flex-column justify-content-center">
                                    <i class="<?php echo escape($categorie['icone']); ?> d-block mb-2"></i>
                                    <small><?php echo escape($categorie['nom_categorie']); ?></small>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits -->
    <?php if (!empty($produits)): ?>
        <div class="row">
            <?php foreach($produits as $produit): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 product-card">
                        <!-- Badge statut -->
                        <div class="position-relative">
                            <?php if (!empty($produit['image_principale'])): ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/produits/<?php echo escape($produit['image_principale']); ?>" 
                                     alt="<?php echo escape($produit['nom_produit']); ?>"
                                     class="card-img-top" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="<?php echo isset($produit['icone']) ? escape($produit['icone']) : 'fas fa-box'; ?> fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badge statut -->
                            <?php if ($produit['statut'] == 'saisonnier'): ?>
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-clock me-1"></i>Saisonnier
                                </span>
                            <?php elseif (isset($produit['stock_disponible']) && $produit['stock_disponible'] > 0): ?>
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-check me-1"></i>Disponible
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <!-- Catégorie -->
                            <div class="mb-2">
                                <span class="badge bg-light text-dark">
                                    <i class="<?php echo isset($produit['icone']) ? escape($produit['icone']) : 'fas fa-tag'; ?> me-1"></i>
                                    <?php echo escape($produit['nom_categorie'] ?? 'Non classé'); ?>
                                </span>
                            </div>

                            <!-- Nom du produit -->
                            <h5 class="card-title">
                                <?php echo escape($produit['nom_produit']); ?>
                            </h5>

                            <!-- Description -->
                            <p class="card-text flex-grow-1 text-muted">
                                <?php 
                                $description = $produit['description'] ?? 'Description non disponible';
                                echo escape(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '');
                                ?>
                            </p>

                            <!-- Prix et informations -->
                            <div class="mt-auto">
                                <div class="row align-items-center mb-3">
                                    <div class="col">
                                        <h4 class="text-primary mb-0">
                                            <?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> FBU
                                        </h4>
                                        <small class="text-muted">par <?php echo escape($produit['unite_mesure'] ?? 'unité'); ?></small>
                                    </div>
                                    <div class="col-auto">
                                        <?php if (isset($produit['stock_disponible']) && $produit['stock_disponible'] > 0): ?>
                                            <small class="text-success">
                                                <i class="fas fa-warehouse me-1"></i>
                                                Stock: <?php echo number_format($produit['stock_disponible'], 0, ',', ' '); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-warning">
                                                <i class="fas fa-clock me-1"></i>Sur commande
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Producteur -->
                                <?php if (!empty($produit['producteur_nom'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Producteur: <?php echo escape($produit['producteur_prenom'] . ' ' . $produit['producteur_nom']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="d-grid gap-2">
                                    <?php if (isLoggedIn()): ?>
                                        <button type="button" class="btn btn-primary" onclick="ajouterAuPanier(<?php echo $produit['id_produit']; ?>)">
                                            <i class="fas fa-shopping-cart me-2"></i>Commander
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour commander
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#produitModal"
                                            onclick="afficherDetailsProduit(<?php echo htmlspecialchars(json_encode($produit)); ?>)">
                                        <i class="fas fa-info-circle me-1"></i>Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Statistiques -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <h3 class="text-primary"><?php echo count($produits); ?></h3>
                                <p class="mb-0">Produits disponibles</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-success"><?php echo count($categories); ?></h3>
                                <p class="mb-0">Catégories</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-warning">200+</h3>
                                <p class="mb-0">Producteurs membres</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-info">100%</h3>
                                <p class="mb-0">Qualité garantie</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Aucun produit trouvé -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-basket fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucun produit trouvé</h4>
                    <p class="text-muted">
                        <?php if ($categorie_filtre): ?>
                            Aucun produit n'est disponible dans cette catégorie pour le moment.
                        <?php else: ?>
                            Aucun produit n'est disponible pour le moment.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/produits.php" class="btn btn-primary">
                        <i class="fas fa-th-large me-2"></i>Voir tous les produits
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Avantages SOCOU_U -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-5">Pourquoi choisir SOCOU_U ?</h3>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="text-center">
                        <div class="bg-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-leaf text-white fa-2x"></i>
                        </div>
                        <h5>Production Naturelle</h5>
                        <p class="text-muted">
                            Nos produits sont cultivés selon des méthodes naturelles, 
                            sans pesticides ni engrais chimiques nocifs.
                        </p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center">
                        <div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-handshake text-white fa-2x"></i>
                        </div>
                        <h5>Commerce Équitable</h5>
                        <p class="text-muted">
                            Nous garantissons un prix équitable aux producteurs 
                            et une qualité supérieure aux consommateurs.
                        </p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center">
                        <div class="bg-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-truck text-white fa-2x"></i>
                        </div>
                        <h5>Livraison Efficace</h5>
                        <p class="text-muted">
                            Service de livraison rapide et fiable 
                            dans toute la région de Bujumbura.
                        </p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="text-center">
                        <div class="bg-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-certificate text-white fa-2x"></i>
                        </div>
                        <h5>Qualité Garantie</h5>
                        <p class="text-muted">
                            Contrôle qualité rigoureux à chaque étape, 
                            de la production à la livraison.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails produit -->
<div class="modal fade" id="produitModal" tabindex="-1" aria-labelledby="produitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="produitModalLabel">Détails du produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="produitModalBody">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <?php if (isLoggedIn()): ?>
                    <button type="button" class="btn btn-primary" id="commanderModalBtn">
                        <i class="fas fa-shopping-cart me-2"></i>Commander
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card-img-top {
    transition: transform 0.3s ease;
}

.product-card:hover .card-img-top {
    transform: scale(1.05);
}

.btn-group-vertical .btn {
    border-radius: 0.375rem !important;
    margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
    .display-4 {
        font-size: 2.5rem;
    }
    
    .row > div[class*="col-"] {
        margin-bottom: 1rem;
    }
}

.badge {
    font-size: 0.75em;
}

.position-absolute.badge {
    z-index: 1;
}
</style>

<script>
let produitActuel = null;

function afficherDetailsProduit(produit) {
    produitActuel = produit;
    
    const modalBody = document.getElementById('produitModalBody');
    const modalLabel = document.getElementById('produitModalLabel');
    const commanderBtn = document.getElementById('commanderModalBtn');
    
    modalLabel.textContent = produit.nom_produit;
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                ${produit.image_principale ? 
                    `<img src="${SITE_URL}/assets/images/produits/${produit.image_principale}" 
                         alt="${produit.nom_produit}" class="img-fluid rounded">` :
                    `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                         <i class="fas fa-box fa-5x text-muted"></i>
                     </div>`
                }
            </div>
            <div class="col-md-6">
                <h4 class="text-primary">${produit.prix_unitaire ? new Intl.NumberFormat().format(produit.prix_unitaire) : 'Prix sur demande'} FBU</h4>
                <p class="text-muted">par ${produit.unite_mesure || 'unité'}</p>
                
                <div class="mb-3">
                    <h6>Description :</h6>
                    <p>${produit.description || 'Description non disponible'}</p>
                </div>
                
                ${produit.stock_disponible ? 
                    `<div class="mb-3">
                         <h6>Stock disponible :</h6>
                         <span class="badge bg-success">${new Intl.NumberFormat().format(produit.stock_disponible)} ${produit.unite_mesure || 'unités'}</span>
                     </div>` : ''
                }
                
                ${produit.producteur_nom ? 
                    `<div class="mb-3">
                         <h6>Producteur :</h6>
                         <p>${produit.producteur_prenom} ${produit.producteur_nom}</p>
                     </div>` : ''
                }
                
                <div class="mb-3">
                    <h6>Catégorie :</h6>
                    <span class="badge bg-light text-dark">
                        <i class="${produit.icone || 'fas fa-tag'} me-1"></i>
                        ${produit.nom_categorie || 'Non classé'}
                    </span>
                </div>
            </div>
        </div>
    `;
    
    if (commanderBtn) {
        commanderBtn.onclick = function() {
            ajouterAuPanier(produit.id_produit);
        };
    }
}

function ajouterAuPanier(produitId) {
    // Simuler l'ajout au panier
    alert('Fonctionnalité de commande en cours de développement. Contactez-nous pour commander ce produit.');
    
    // TODO: Implémenter la logique réelle d'ajout au panier
    /*
    fetch(`${SITE_URL}/ajax/ajouter_panier.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            produit_id: produitId,
            quantite: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit ajouté au panier !');
            // Mettre à jour l'icône du panier si nécessaire
        } else {
            alert('Erreur : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue. Veuillez réessayer.');
    });
    */
}

// Variables globales
const SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<?php require_once 'includes/footer.php'; ?>