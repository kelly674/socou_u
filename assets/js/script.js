// JavaScript personnalisé pour SOCOU_U

document.addEventListener('DOMContentLoaded', function() {
    
    // Animation au scroll
    function animateOnScroll() {
        const elements = document.querySelectorAll('.card, .stats-card');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        elements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });
    }
    
    // Initialiser les animations
    animateOnScroll();
    
    // Gestion du formulaire de newsletter
    const newsletterForm = document.querySelector('form[action*="newsletter"]');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            
            // Désactiver le bouton et afficher le loader
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simuler l'envoi (remplacer par vraie requête AJAX)
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Inscription réussie à la newsletter!', 'success');
                    this.reset();
                } else {
                    showAlert(data.message || 'Erreur lors de l\'inscription', 'danger');
                }
            })
            .catch(error => {
                showAlert('Erreur de connexion', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            });
        });
    }
    
    // Gestion du formulaire de contact
    const contactForm = document.querySelector('#contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Message envoyé avec succès!', 'success');
                    this.reset();
                } else {
                    showAlert(data.message || 'Erreur lors de l\'envoi', 'danger');
                }
            })
            .catch(error => {
                showAlert('Erreur de connexion', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            });
        });
    }
    
    // Fonction pour afficher les alertes
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insérer l'alerte en haut de la page
        const container = document.querySelector('.container:first-of-type') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-masquer après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Gestion de la galerie d'images (lightbox simple)
    const galleryItems = document.querySelectorAll('.gallery-item img');
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const src = this.src;
            const alt = this.alt;
            
            // Créer le lightbox
            const lightbox = document.createElement('div');
            lightbox.className = 'lightbox';
            lightbox.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
            `;
            
            const img = document.createElement('img');
            img.src = src;
            img.alt = alt;
            img.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
                border-radius: 10px;
            `;
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = `
                position: absolute;
                top: 20px;
                right: 30px;
                background: none;
                border: none;
                color: white;
                font-size: 3rem;
                cursor: pointer;
                z-index: 10000;
            `;
            
            lightbox.appendChild(img);
            lightbox.appendChild(closeBtn);
            document.body.appendChild(lightbox);
            
            // Fermer le lightbox
            function closeLightbox() {
                document.body.removeChild(lightbox);
            }
            
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });
            
            closeBtn.addEventListener('click', closeLightbox);
            
            // Fermer avec Escape
            function handleKeydown(e) {
                if (e.key === 'Escape') {
                    closeLightbox();
                    document.removeEventListener('keydown', handleKeydown);
                }
            }
            document.addEventListener('keydown', handleKeydown);
        });
    });
    
    // Gestion du sidebar admin (mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const content = document.querySelector('.admin-content');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });
        
        // Fermer le sidebar mobile en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
    
    // Validation des formulaires
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Prévisualisation d'image
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Confirmation de suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.dataset.message || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            const href = this.href;
            
            if (confirm(message)) {
                window.location.href = href;
            }
        });
    });
    
    // Recherche en temps réel
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const query = this.value.toLowerCase();
                const target = document.querySelector(this.dataset.target);
                
                if (target) {
                    const items = target.querySelectorAll('.searchable-item');
                    items.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        if (text.includes(query)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
            }, 300);
        });
    });
    
    // Compteur animé pour les statistiques
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    
    const countUp = (counter) => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(() => countUp(counter), 1);
        } else {
            counter.innerText = target;
        }
    };
    
    // Observer pour démarrer le comptage quand visible
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                countUp(entry.target);
                entry.target.classList.add('counted');
            }
        });
    });
    
    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
    
    // Gestion des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-refresh pour les données dynamiques
    function refreshData() {
        const refreshElements = document.querySelectorAll('[data-refresh]');
        refreshElements.forEach(element => {
            const url = element.dataset.refresh;
            const interval = parseInt(element.dataset.interval) || 30000;
            
            setInterval(() => {
                fetch(url)
                .then(response => response.text())
                .then(html => {
                    element.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur lors du refresh:', error);
                });
            }, interval);
        });
    }
    
    refreshData();
    
    // Smooth scrolling pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Fonction utilitaire pour formater les nombres
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

// Fonction utilitaire pour formater les devises
function formatCurrency(amount, currency = 'BIF') {
    return new Intl.NumberFormat('fr-BI', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Fonction pour copier du texte dans le presse-papiers
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('Texte copié dans le presse-papiers!', 'success');
    }, function() {
        showAlert('Impossible de copier le texte', 'danger');
    });
}