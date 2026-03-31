document.addEventListener('DOMContentLoaded', () => {
    const expandButtons = document.querySelectorAll('.expand-btn');
  
    expandButtons.forEach(button => {
      button.addEventListener('click', () => {
        const description = button.closest('.integrante').querySelector('.descripcion');
  
        // Alternar clase 'active'
        description.classList.toggle('active');
  
        // Cambiar el ícono del botón
        const plusIcon = button.querySelector('.plus-icon');
        if (description.classList.contains('active')) {
          plusIcon.textContent = '-';
        } else {
          plusIcon.textContent = '+';
        }
      });
    });
  });
  