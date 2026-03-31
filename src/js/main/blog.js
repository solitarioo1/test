// FAQ: Expansión y colapso
document.querySelectorAll('.faq-pregunta').forEach(pregunta => {
    pregunta.addEventListener('click', () => {
        const respuesta = pregunta.nextElementSibling;
        const icono = pregunta.querySelector('.faq-icono i');

        // Alternar visibilidad
        if (respuesta.style.display === 'block') {
            respuesta.style.display = 'none';
            icono.classList.remove('fa-minus');
            icono.classList.add('fa-plus');
        } else {
            respuesta.style.display = 'block';
            icono.classList.remove('fa-plus');
            icono.classList.add('fa-minus');
        }
    });
});

// Botón "Volver arriba" visible al hacer scroll
const btnVolverArriba = document.querySelector('.volver-arriba');
if (btnVolverArriba) {
    btnVolverArriba.style.display = 'none'; // Ocultar inicialmente

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btnVolverArriba.style.display = 'flex';
        } else {
            btnVolverArriba.style.display = 'none';
        }
    });

    btnVolverArriba.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Scroll suave para navegación interna
document.querySelectorAll('a[href^="#"]').forEach(enlace => {
    enlace.addEventListener('click', function(e) {
        const destino = document.querySelector(this.getAttribute('href'));
        if (destino) {
            e.preventDefault();
            destino.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Confirmación de suscripción (simulado)
const formularioSuscripcion = document.querySelector('.formulario-suscripcion');
if (formularioSuscripcion) {
    formularioSuscripcion.addEventListener('submit', function(e) {
        e.preventDefault();
        alert("¡Gracias por suscribirte! Te mantendremos informado.");
        this.reset();
    });
}

// Simulación de recomendación en sección "Protección"
const botonRecomendacion = document.querySelector('.selector-proteccion .boton--primario');
if (botonRecomendacion) {
    botonRecomendacion.addEventListener('click', e => {
        e.preventDefault();
        const actividad = document.querySelector('.selector-actividad select')?.value || "actividad no especificada";
        alert(`Recomendación personalizada generada para: "${actividad}".`);
    });
}
