document.addEventListener("DOMContentLoaded", () => {
    const items = document.querySelectorAll('.contenido-evolucion');
  
    const mostrarItems = () => {
      const triggerBottom = window.innerHeight * 0.85; 
  
      items.forEach(item => {
        const boxTop = item.getBoundingClientRect().top; 
  
        if (boxTop < triggerBottom) {
          item.classList.add('visible');
        }
      });
    };
  
    window.addEventListener('scroll', mostrarItems); 
    mostrarItems(); 
  });
  