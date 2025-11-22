<!-- Footer -->
    <footer class="bg-dark text-light mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-seedling text-success"></i> SOCOU_U</h5>
                    <p><?php echo ConfigManager::get('slogan', 'Solidarité, Autonomie et Développement Durable'); ?></p>
                    <p><small><?php echo ConfigManager::get('description', 'Coopérative dédiée au développement agropastoral et social au Burundi'); ?></small></p>
                    
                    <!-- Réseaux sociaux -->
                    <div class="social-links mt-3">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Navigation</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-light-50">Accueil</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/a-propos.php" class="text-light-50">À propos</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/actualites.php" class="text-light-50">Actualités</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/produits.php" class="text-light-50">Produits</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-light-50">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Nos Services</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/formations.php" class="text-light-50">Formations</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/projets.php" class="text-light-50">Projets Sociaux</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/galerie.php" class="text-light-50">Galerie</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/temoignages.php" class="text-light-50">Témoignages</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Contact</h6>
                    <div class="contact-info">
                        <p><i class="fas fa-map-marker-alt text-success"></i> <?php echo ConfigManager::get('adresse', 'Province de Bujumbura, Zone Rohero, Commune Mukaza'); ?></p>
                        <p><i class="fas fa-phone text-success"></i> <?php echo ConfigManager::get('telephone', '+257 XX XX XX XX'); ?></p>
                        <p><i class="fas fa-envelope text-success"></i> <?php echo ConfigManager::get('email', 'contact@socou-u.bi'); ?></p>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="mt-3">
                        <h6>Newsletter</h6>
                        <form action="<?php echo SITE_URL; ?>/newsletter.php" method="POST" class="input-group">
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Votre email" required>
                            <button class="btn btn-success btn-sm" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-darker py-3">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> SOCOU_U. Tous droits réservés.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small>Fondée en <?php echo ConfigManager::get('annee_fondation', '2019'); ?> - Burundi</small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript personnalisé -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>