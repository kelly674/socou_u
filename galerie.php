<?php
$page_title = "Galerie";
$page_description = "Découvrez nos activités en images";
require_once 'includes/header.php';

// Récupération des médias avec données par défaut si vide
try {
     $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM medias WHERE statut = 'public' ORDER BY date_creation DESC");
    $stmt->execute();
    $medias = $stmt->fetchAll();
} catch(PDOException $e) {
    $medias = [];
}

// Données par défaut si la base est vide
if (empty($medias)) {
    $medias = [
        [
            'id_media' => 1,
            'titre' => 'Formation en Agriculture Moderne',
            'description' => 'Session de formation sur les techniques agricoles durables',
            'fichier' => 'formation-agricole.jpg',
            'type_media' => 'image',
            'categorie' => 'formation',
            'date_creation' => '2024-01-15'
        ],
        [
            'id_media' => 2,
            'titre' => 'Récolte de Maïs - Saison A 2024',
            'description' => 'Excellente récolte de nos producteurs membres',
            'fichier' => 'recolte-mais.jpg',
            'type_media' => 'image',
            'categorie' => 'production',
            'date_creation' => '2024-02-20'
        ],
        [
            'id_media' => 3,
            'titre' => 'Assemblée Générale 2024',
            'description' => 'Rassemblement annuel des membres de SOCOU_U',
            'fichier' => 'assemblee-generale.jpg',
            'type_media' => 'image',
            'categorie' => 'evenement',
            'date_creation' => '2024-03-10'
        ],
        [
            'id_media' => 4,
            'titre' => 'Projet Autonomisation Femmes',
            'description' => 'Remise de matériel aux bénéficiaires du projet',
            'fichier' => 'projet-femmes.jpg',
            'type_media' => 'image',
            'categorie' => 'projet',
            'date_creation' => '2024-03-25'
        ],
        [
            'id_media' => 5,
            'titre' => 'Transformation de Tomates',
            'description' => 'Atelier de transformation agroalimentaire',
            'fichier' => 'transformation-tomates.jpg',
            'type_media' => 'image',
            'categorie' => 'production',
            'date_creation' => '2024-04-05'
        ],
        [
            'id_media' => 6,
            'titre' => 'Formation en Élevage',
            'description' => 'Techniques modernes d\'élevage de porcs',
            'fichier' => 'formation-elevage.jpg',
            'type_media' => 'image',
            'categorie' => 'formation',
            'date_creation' => '2024-04-18'
        ]
    ];
}
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold text-primary mb-3">Galerie Multimédia</h1>
            <p class="lead">
                Découvrez en images nos activités, nos projets et les moments forts de notre coopérative.
                Chaque photo raconte une histoire de solidarité, de progrès et d'impact social.
            </p>
        </div>
        <div class="col-lg-4 text-center">
            <i class="fas fa-images fa-5x text-primary opacity-75"></i>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-0">
                                <i class="fas fa-filter me-2"></i>Filtrer par catégorie :
                            </h6>
                        </div>
                        <div class="col-md-9">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-primary active" data-filter="*">
                                    <i class="fas fa-th-large me-1"></i>Tout
                                </button>
                                <button class="btn btn-outline-success" data-filter="production">
                                    <i class="fas fa-seedling me-1"></i>Production
                                </button>
                                <button class="btn btn-outline-info" data-filter="formation">
                                    <i class="fas fa-graduation-cap me-1"></i>Formations
                                </button>
                                <button class="btn btn-outline-warning" data-filter="evenement">
                                    <i class="fas fa-calendar-alt me-1"></i>Événements
                                </button>
                                <button class="btn btn-outline-danger" data-filter="projet">
                                    <i class="fas fa-heart me-1"></i>Projets
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Galerie -->
    <div class="row" id="galerie-container">
        <?php foreach($medias as $media): ?>
        <div class="col-lg-4 col-md-6 mb-4 gallery-item" data-category="<?php echo $media['categorie']; ?>">
            <div class="card border-0 shadow-sm h-100 media-card">
                <div class="position-relative overflow-hidden">
                    <?php if ($media['type_media'] == 'image'): ?>
                        <img src="<?php echo isset($media['fichier']) ? UPLOAD_URL . $media['fichier'] : 'https://via.placeholder.com/400x250?text=' . urlencode($media['titre']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo escape($media['titre']); ?>" 
                             style="height: 250px; object-fit: cover;">
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-primary">
                                <i class="fas fa-image me-1"></i>Photo
                            </span>
                        </div>
                    <?php elseif ($media['type_media'] == 'video'): ?>
                        <video class="card-img-top" style="height: 250px; object-fit: cover;" controls>
                            <source src="<?php echo UPLOAD_URL . $media['fichier']; ?>" type="video/mp4">
                        </video>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-danger">
                                <i class="fas fa-video me-1"></i>Vidéo
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badge catégorie -->
                    <div class="position-absolute bottom-0 start-0 m-2">
                        <?php
                        $badge_class = 'bg-secondary';
                        $badge_icon = 'fas fa-tag';
                        $badge_text = ucfirst($media['categorie']);
                        
                        switch($media['categorie']) {
                            case 'production':
                                $badge_class = 'bg-success';
                                $badge_icon = 'fas fa-seedling';
                                break;
                            case 'formation':
                                $badge_class = 'bg-info';
                                $badge_icon = 'fas fa-graduation-cap';
                                break;
                            case 'evenement':
                                $badge_class = 'bg-warning';
                                $badge_icon = 'fas fa-calendar-alt';
                                break;
                            case 'projet':
                                $badge_class = 'bg-danger';
                                $badge_icon = 'fas fa-heart';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <i class="<?php echo $badge_icon; ?> me-1"></i><?php echo $badge_text; ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title"><?php echo escape($media['titre']); ?></h5>
                    <p class="card-text text-muted"><?php echo escape($media['description']); ?></p>
                </div>
                
                <div class="card-footer bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y', strtotime($media['date_creation'])); ?>
                        </small>
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="voirEnGrand('<?php echo isset($media['fichier']) ? UPLOAD_URL . $media['fichier'] : 'https://via.placeholder.com/800x600?text=' . urlencode($media['titre']); ?>', '<?php echo escape($media['titre']); ?>')">
                            <i class="fas fa-expand me-1"></i>Voir
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Message si aucun résultat -->
    <div class="row" id="no-results" style="display: none;">
        <div class="col-12 text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun média trouvé</h5>
            <p class="text-muted">Aucun contenu ne correspond à votre filtre actuel.</p>
        </div>
    </div>
    
    <!-- Statistiques de la galerie -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h4 class="text-center mb-4">Statistiques de la Galerie</h4>
                    <div class="row text-center">
                        <?php
                        $total_medias = count($medias);
                        $medias_production = count(array_filter($medias, function($m) { return $m['categorie'] == 'production'; }));
                        $medias_formation = count(array_filter($medias, function($m) { return $m['categorie'] == 'formation'; }));
                        $medias_evenement = count(array_filter($medias, function($m) { return $m['categorie'] == 'evenement'; }));
                        $medias_projet = count(array_filter($medias, function($m) { return $m['categorie'] == 'projet'; }));
                        ?>
                        <div class="col-md-3 col-6 mb-3">
                            <h3 class="text-primary"><?php echo $total_medias; ?></h3>
                            <p class="mb-0">Total Médias</p>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <h3 class="text-success"><?php echo $medias_production; ?></h3>
                            <p class="mb-0">Production</p>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <h3 class="text-info"><?php echo $medias_formation; ?></h3>
                            <p class="mb-0">Formations</p>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <h3 class="text-warning"><?php echo $medias_evenement + $medias_projet; ?></h3>
                            <p class="mb-0">Événements & Projets</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour voir en grand -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<style>
.media-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.media-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.gallery-item {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.gallery-item.hidden {
    opacity: 0;
    transform: scale(0.8);
    pointer-events: none;
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

.badge {
    font-size: 0.75em;
}

.card-img-top {
    transition: transform 0.3s ease;
}

.media-card:hover .card-img-top {
    transform: scale(1.02);
}
</style>

<script>
function filterGallery() {
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Mise à jour des boutons
            document.querySelectorAll('[data-filter]').forEach(b => {
                b.classList.remove('active', 'btn-primary', 'btn-success', 'btn-info', 'btn-warning', 'btn-danger');
                const filter = b.dataset.filter;
                switch(filter) {
                    case '*':
                        b.classList.add('btn-outline-primary');
                        break;
                    case 'production':
                        b.classList.add('btn-outline-success');
                        break;
                    case 'formation':
                        b.classList.add('btn-outline-info');
                        break;
                    case 'evenement':
                        b.classList.add('btn-outline-warning');
                        break;
                    case 'projet':
                        b.classList.add('btn-outline-danger');
                        break;
                }
            });
            
            // Style du bouton actif
            this.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-info', 'btn-outline-warning', 'btn-outline-danger');
            const filter = this.dataset.filter;
            switch(filter) {
                case '*':
                    this.classList.add('btn-primary', 'active');
                    break;
                case 'production':
                    this.classList.add('btn-success', 'active');
                    break;
                case 'formation':
                    this.classList.add('btn-info', 'active');
                    break;
                case 'evenement':
                    this.classList.add('btn-warning', 'active');
                    break;
                case 'projet':
                    this.classList.add('btn-danger', 'active');
                    break;
            }
            
            // Filtrage des éléments
            const items = document.querySelectorAll('.gallery-item');
            let visibleCount = 0;
            
            items.forEach(item => {
                if (filter === '*' || item.dataset.category === filter) {
                    item.style.display = 'block';
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    item.classList.add('hidden');
                }
            });
            
            // Afficher/masquer le message "aucun résultat"
            const noResults = document.getElementById('no-results');
            if (visibleCount === 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        });
    });
}

function voirEnGrand(imageSrc, titre) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModalLabel').textContent = titre;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    filterGallery();
});
</script>

<?php require_once 'includes/footer.php'; ?>