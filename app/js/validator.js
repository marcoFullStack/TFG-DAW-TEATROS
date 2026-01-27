document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('registerForm') || document.getElementById('loginForm');
  if (!form) return;

  const errorDiv = document.getElementById('js-errors');
  if (!errorDiv) return;

  form.addEventListener('submit', function(e) {
    let errors = [];
    errorDiv.style.display = 'none';
    errorDiv.innerHTML = '';

    const nombre = document.getElementById('Nombre');   // puede no existir
    const email  = document.getElementById('Email');
    const pass   = document.getElementById('Password');

    if (nombre && nombre.value.trim() === '') {
      errors.push('El nombre no puede estar vacío.');
    }

    if (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email.value.trim())) {
        errors.push('Introduce un email válido.');
      }
    }

    if (pass && pass.value.length < 4) {
      errors.push('La contraseña debe tener al menos 4 caracteres.');
    }

    if (errors.length > 0) {
      e.preventDefault();
      errorDiv.style.display = 'block';
      errorDiv.innerHTML = errors.map(e => `• ${e}`).join('<br>') + '<br>';
      window.scrollTo(0, 0);
    }
  });
});
