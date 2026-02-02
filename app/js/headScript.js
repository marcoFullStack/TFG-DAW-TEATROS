/* This JavaScript code is adding an event listener to the `DOMContentLoaded` event, which fires when
the initial HTML document has been completely loaded and parsed. */
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    menuToggle.addEventListener('click', () => {
      mainNav.classList.toggle('active');
      
      menuToggle.classList.toggle('open');
    });
  });