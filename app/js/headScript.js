document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    menuToggle.addEventListener('click', () => {
      mainNav.classList.toggle('active');
      
      menuToggle.classList.toggle('open');
    });
  });