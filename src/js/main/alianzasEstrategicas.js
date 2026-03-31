document.addEventListener('DOMContentLoaded', () => {
    const track = document.querySelector('.carousel .track');
    const items = Array.from(track.children);
  
    // Clonar tus N ítems para tener 2N en el track
    items.forEach(item => {
      track.append(item.cloneNode(true));
    });
  });
  