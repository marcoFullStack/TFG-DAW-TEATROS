<link href="styles/footer.css" rel="stylesheet">

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Sección de información -->
            <div class="footer-section">
                <div class="footer-logo">
                    <a href="index.php" class="footer-site-name">
                        <div class="logo"><img src="images/logo/Logo.png" alt="Logo" class="logo"></div>Teatros Nova
                    </a>
                </div>
                <p class="footer-description">
                    Una plataforma innovadora diseñada para ofrecer la mejor experiencia de navegación y servicios digitales de calidad.
                    <p>Contacto administrador : soporte@redteatros.es / ayuda@redteatros.es</p>

                </p>
                <div class="social-links">
                    <a href="#" class="social-link" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            
            <!-- Sección de enlaces -->
            <div class="footer-section">
                <h3 class="footer-heading">Enlaces Rápidos</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="<?= h(BASE_URL) ?>servicios.php"><i class="fas fa-concierge-bell"></i> Servicios</a></li>
                    <li><a href="<?= h(BASE_URL) ?>nosotros.php"><i class="fas fa-users"></i> Sobre Nosotros</a></li>
                    <li><a href="http://iesgalileo.centros.educa.jcyl.es/sitio/index.cgi?wid_form=1" target="_blank"><i class="fas fa-envelope"></i> Contacto</a></li>
                </ul>
            </div>
            
            <!-- Sección legal -->
            <div class="footer-section">
                <h3 class="footer-heading">Información Legal</h3>
                <ul class="footer-links">
                    <li><a href="#" id="cookies-link"><i class="fas fa-cookie-bite"></i> Política de Cookies</a></li>
                    <li><a href="https://www.aepd.es/" target="_blank"><i class="fas fa-user-shield"></i> Privacidad de Datos</a></li>
                    <li><a href="terminos.php"><i class="fas fa-file-contract"></i> Términos de Uso</a></li>
                    <li><a href="https://www.aepd.es/" target="_blank"><i class="fas fa-balance-scale"></i> Aviso Legal</a></li>
                </ul>
            </div>
        </div>
        
        
        
        <!-- Copyright -->
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Naregador. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<!-- Banner de cookies -->
<div id="cookieConsent" class="cookie-consent">
    <div class="cookie-content">
        <p>Utilizamos cookies propias y de terceros para mejorar su experiencia. Al continuar navegando, acepta nuestra política de cookies.</p>
        <div class="cookie-buttons">
            <button id="acceptCookies" class="cookie-btn cookie-accept">Aceptar todas</button>
            <button id="rejectCookies" class="cookie-btn cookie-reject">Rechazar</button>
        </div>
    </div>
</div>

<div id="modalTerminos" class="modal-terminos">
    <div class="modal-content glass">
        <div class="modal-header">
            <h2><i class="fas fa-file-contract"></i> Términos de Uso - Teatros Nova</h2>
            <button class="close-modal" id="closeTerms">&times;</button>
        </div>
        <div class="modal-body">
            <h3>1. Aceptación de los Términos</h3>
            <p>Al acceder a Teatros Nova, el usuario acepta cumplir con estas normas. Nuestra plataforma es un espacio cultural destinado a la difusión de las artes escénicas en la región.</p>

            <h3>2. Uso de la Galería de Usuarios</h3>
            <p>Al subir fotografías de teatros, el usuario garantiza que es el autor de la imagen. Teatros Nova se reserva el derecho de moderar y eliminar cualquier contenido inapropiado, ofensivo o que no guarde relación con la temática teatral.</p>

            <h3>3. Sistema de Puntos y Reseñas</h3>
            <p>Los puntos obtenidos por interacción no tienen valor monetario. Las reseñas deben ser constructivas. El spam o las críticas malintencionadas resultarán en la pérdida de puntos o baneo de la cuenta.</p>

            <h3>4. Propiedad Intelectual</h3>
            <p>El diseño, logotipos y contenidos propios de la web son propiedad de Teatros Nova. Las fotos de los usuarios pertenecen a sus autores, pero nos ceden el derecho de exhibición en el portal.</p>

            <h3>5. Limitación de Responsabilidad</h3>
            <p>No nos hacemos responsables de cambios de última hora en las programaciones de los teatros externos, aunque nos esforzamos por mantener los datos actualizados.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-aceptar" id="acceptTerms">Entendido</button>
        </div>
    </div>
</div>

<!-- Fuente Awesome para iconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>

    
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA DE COOKIES ---
        const cookieConsent = document.getElementById('cookieConsent');
        const acceptCookies = document.getElementById('acceptCookies');
        const rejectCookies = document.getElementById('rejectCookies');

        // Solo ejecutamos si los elementos existen en el HTML actual
        if (cookieConsent && acceptCookies) {
            // Comprobar si ya aceptó
            if (!localStorage.getItem('cookieConsent')) {
                cookieConsent.classList.add('active');
            }

            acceptCookies.addEventListener('click', function() {
                localStorage.setItem('cookieConsent', 'accepted');
                cookieConsent.classList.remove('active');
            });
        }

        if (rejectCookies) {
            rejectCookies.addEventListener('click', function() {
                cookieConsent.classList.remove('active');
            });
        }

        // --- LÓGICA DE MODAL DE TÉRMINOS ---
        const modalTerms = document.getElementById('modalTerminos');
        const closeTerms = document.getElementById('closeTerms');
        const acceptTerms = document.getElementById('acceptTerms');
        // Buscamos el enlace que abre los términos
        const openTermsBtn = document.querySelector('.open-terms') || document.getElementById('openTerms');

        if (openTermsBtn && modalTerms) {
            openTermsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modalTerms.classList.add('active');
            });
        }

        // Cerramos el modal de términos si los botones existen
        [closeTerms, acceptTerms].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => modalTerms.classList.remove('active'));
            }
        });
    });
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalTerminos');
    const closeBtn = document.getElementById('closeTerms');
    const acceptBtn = document.getElementById('acceptTerms');
    
    // Buscar el enlace de Términos
    const termsLink = document.querySelector('a[href*="terminos"]') || 
                      Array.from(document.querySelectorAll('a')).find(el => el.textContent.includes('Términos'));

    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('active');
        });
    }

    // Cerrar al hacer clic en X o en el botón "Entendido"
    [closeBtn, acceptBtn].forEach(btn => {
        if(btn) {
            btn.onclick = () => modal.classList.remove('active');
        }
    });

    // Cerrar al hacer clic fuera del contenido
    window.onclick = (event) => {
        if (event.target == modal) {
            modal.classList.remove('active');
        }
    };
});
    
</script>