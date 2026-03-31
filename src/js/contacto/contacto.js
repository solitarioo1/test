// Modificar tu función de submit para usar AJAX
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validación frontend (opcional)
    
    try {
        const response = await fetch('ruta-del-controlador', {
            method: 'POST',
            body: new FormData(this)
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensaje(data.message, 'success');
            form.reset();
        } else {
            mostrarMensaje(data.errors.join('<br>'), 'error');
        }
    } catch (error) {
        mostrarMensaje('Error al enviar el formulario', 'error');
    }
});