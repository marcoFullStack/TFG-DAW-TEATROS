<link href="styles/footer.css" rel="stylesheet">

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Sección de información -->
            <div class="footer-section">
                <div class="footer-logo">
                    <div class="footer-logo-icon">N</div>
                    <a href="index.php" class="footer-site-name">Naregador</a>
                </div>
                <p class="footer-description">
                    Una plataforma innovadora diseñada para ofrecer la mejor experiencia de navegación y servicios digitales de calidad.
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
                    <li><a href="nosotros.php"><i class="fas fa-users"></i> Sobre Nosotros</a></li>
                    <li><a href="servicios.php"><i class="fas fa-concierge-bell"></i> Servicios</a></li>
                    <li><a href="contacto.php"><i class="fas fa-envelope"></i> Contacto</a></li>
                </ul>
            </div>
            
            <!-- Sección legal -->
            <div class="footer-section">
                <h3 class="footer-heading">Información Legal</h3>
                <ul class="footer-links">
                    <li><a href="#" id="cookies-link"><i class="fas fa-cookie-bite"></i> Política de Cookies</a></li>
                    <li><a href="privacidad.php"><i class="fas fa-user-shield"></i> Privacidad de Datos</a></li>
                    <li><a href="terminos.php"><i class="fas fa-file-contract"></i> Términos de Uso</a></li>
                    <li><a href="aviso-legal.php"><i class="fas fa-balance-scale"></i> Aviso Legal</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Enlaces legales en línea -->
        <div class="legal-links">
            <a href="#" id="accept-cookies">Aceptar Cookies</a>
            <a href="#" id="manage-cookies">Gestionar Cookies</a>
            <a href="privacidad.php">Privacidad de Datos</a>
            <a href="nosotros.php">Sobre Nosotros</a>
            <a href="contacto.php">Contacto</a>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Naregador. Todos los derechos reservados.</p>
            <p>Diseñado con los colores corporativos: Granate, Dorado, Crema y Negro.</p>
        </div>
    </div>
</footer>

<!-- Banner de cookies -->
<div class="cookie-consent" id="cookieConsent">
    <p>Utilizamos cookies propias y de terceros para mejorar nuestros servicios y mostrarle publicidad relacionada con sus preferencias. Al continuar con la navegación entendemos que acepta nuestra <a href="privacidad.php" style="color:#C5A059;">Política de Cookies</a>.</p>
    <div class="cookie-buttons">
        <button class="cookie-btn cookie-accept" id="acceptAllCookies">Aceptar todas</button>
        <button class="cookie-btn cookie-reject" id="rejectCookies">Rechazar</button>
        <button class="cookie-btn cookie-reject" id="customizeCookies">Personalizar</button>
    </div>
</div>

<!-- Font Awesome para iconos (puedes usar CDN o descargarlo) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Control del banner de cookies
        const cookieConsent = document.getElementById('cookieConsent');
        const acceptAllCookies = document.getElementById('acceptAllCookies');
        const rejectCookies = document.getElementById('rejectCookies');
        const customizeCookies = document.getElementById('customizeCookies');
        const cookiesLink = document.getElementById('cookies-link');
        const acceptCookiesLink = document.getElementById('accept-cookies');
        const manageCookiesLink = document.getElementById('manage-cookies');
        
        // Mostrar banner de cookies si no se ha aceptado/rechazado
        if (!localStorage.getItem('cookieConsent')) {
            setTimeout(() => {
                cookieConsent.classList.add('active');
            }, 1000);
        }
        
        // Aceptar todas las cookies
        acceptAllCookies.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            cookieConsent.classList.remove('active');
            alert('Cookies aceptadas. Gracias por su preferencia.');
        });
        
        // Rechazar cookies
        rejectCookies.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'rejected');
            cookieConsent.classList.remove('active');
            alert('Cookies rechazadas. Algunas funciones pueden no estar disponibles.');
        });
        
        // Personalizar cookies
        customizeCookies.addEventListener('click', function() {
            alert('Redirigiendo a la página de configuración de cookies...');
            // Aquí podrías redirigir a una página específica
            // window.location.href = 'configurar-cookies.php';
        });
        
        // Enlace de política de cookies
        cookiesLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Redirigiendo a la política de cookies...');
            // window.location.href = 'cookies.php';
        });
        
        // Enlace "Aceptar Cookies" en footer
        acceptCookiesLink.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.setItem('cookieConsent', 'accepted');
            alert('Cookies aceptadas. Gracias por su preferencia.');
        });
        
        // Enlace "Gestionar Cookies" en footer
        manageCookiesLink.addEventListener('click', function(e) {
            e.preventDefault();
            cookieConsent.classList.add('active');
        });
        
        // Para el enlace "Sobre Nosotros" en el footer
        const aboutLinks = document.querySelectorAll('a[href*="nosotros"]');
        aboutLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href') === '#') {
                    e.preventDefault();
                    alert('Redirigiendo a la página "Sobre Nosotros"...');
                    // window.location.href = 'nosotros.php';
                }
            });
        });
    });
</script>