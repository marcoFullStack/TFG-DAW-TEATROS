document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    menuToggle.addEventListener('click', () => {
      mainNav.classList.toggle('active');
      
      // Opcional: Animación de las barritas del menú
      menuToggle.classList.toggle('open');
    });
  });