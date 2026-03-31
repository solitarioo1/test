document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar todas las preguntas
    const preguntas = document.querySelectorAll('.faq-question');
    
    // Añadir evento click a cada pregunta
    preguntas.forEach(pregunta => {
        pregunta.addEventListener('click', function() {
            // Toggle clase active en la pregunta
            this.classList.toggle('active');
            
            // Seleccionar la respuesta asociada a esta pregunta
            const respuesta = this.nextElementSibling;
            
            // Toggle clase show en la respuesta
            respuesta.classList.toggle('show');
            
            // Cerrar otras preguntas que estén abiertas (opcional)
            preguntas.forEach(item => {
                if (item !== pregunta) {
                    item.classList.remove('active');
                    item.nextElementSibling.classList.remove('show');
                }
            });
        });
    });
});