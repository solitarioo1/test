/**
 * WhatsApp Flotante - JS Simple y Efectivo
 */

document.addEventListener('DOMContentLoaded', function() {
    const whatsappContainer = document.querySelector('.whatsapp-container');
    const whatsappFloat = document.querySelector('.whatsapp-float');
    const tooltip = document.querySelector('.whatsapp-tooltip');
    const badge = document.querySelector('.notification-badge');
    
    if (!whatsappContainer) return;

    // ========================================
    // CONFIGURACIÓN
    // ========================================
    const config = {
        autoExpandDelay: 4000,    // 4 segundos
        hideTooltipDelay: 8000,   // 8 segundos  
        hideOnScroll: true,       // Ocultar al hacer scroll
        trackClicks: true         // Rastrear clics
    };

    // ========================================
    // AUTO-EXPANSIÓN Y TOOLTIP
    // ========================================
    
    // Auto-expandir después del delay
    setTimeout(() => {
        whatsappFloat.classList.add('expanded');
    }, config.autoExpandDelay);

    // Ocultar tooltip después del delay
    setTimeout(() => {
        if (tooltip) {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(-1rem)';
        }
    }, config.hideTooltipDelay);

    // ========================================
    // INTERACCIONES
    // ========================================
    
    // Click en WhatsApp
    whatsappFloat.addEventListener('click', function(e) {
        // Ocultar badge al hacer clic
        if (badge) {
            badge.style.opacity = '0';
            badge.style.transform = 'scale(0)';
        }
        
        // Ocultar tooltip
        if (tooltip) {
            tooltip.style.display = 'none';
        }

        // Analytics (opcional)
        if (config.trackClicks) {
            console.log('WhatsApp clicked:', new Date().toISOString());
            // Aquí puedes agregar Google Analytics o similar:
            // gtag('event', 'whatsapp_click', { page: window.location.pathname });
        }
    });

    // Hover para mantener expandido
    whatsappFloat.addEventListener('mouseenter', () => {
        whatsappFloat.classList.add('expanded');
    });

    // ========================================
    // COMPORTAMIENTO DE SCROLL
    // ========================================
    
    if (config.hideOnScroll) {
        let scrollTimer;
        let isScrolling = false;

        window.addEventListener('scroll', () => {
            if (!isScrolling) {
                whatsappContainer.style.opacity = '0.7';
                whatsappContainer.style.transform = 'scale(0.9)';
                isScrolling = true;
            }

            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                whatsappContainer.style.opacity = '1';
                whatsappContainer.style.transform = 'scale(1)';
                isScrolling = false;
            }, 150);
        });
    }

    // ========================================
    // MENSAJES DINÁMICOS POR PÁGINA
    // ========================================
    
    const pageMessages = {
        '/': 'Hola, me interesa conocer INTI UV+',
        '/productos': 'Quiero información sobre precios de INTI UV+',
        '/contacto': 'Tengo dudas sobre el formulario de contacto',
        '/registros': 'Necesito ayuda con los datos UV',
        '/nosotros': 'Me gustaría agendar una demostración'
    };

    // Cambiar mensaje según la página
    const currentPath = window.location.pathname;
    const message = pageMessages[currentPath] || pageMessages['/'];
    
    // Actualizar href con mensaje personalizado
    const encodedMessage = encodeURIComponent(message);
    whatsappFloat.href = `https://api.whatsapp.com/send?phone=51994146924&text=${encodedMessage}`;

    // ========================================
    // CONTADOR DE NOTIFICACIONES
    // ========================================
    
    // Simular notificaciones (puedes conectar con tu API)
    let notificationCount = 1;
    
    function updateNotificationBadge(count) {
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.opacity = '1';
                badge.style.transform = 'scale(1)';
            } else {
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0)';
            }
        }
    }

    // Inicializar badge
    updateNotificationBadge(notificationCount);

    // ========================================
    // UTILIDADES
    // ========================================
    
    // Función pública para controlar desde afuera
    window.WhatsAppWidget = {
        show() {
            whatsappContainer.style.display = 'flex';
        },
        hide() {
            whatsappContainer.style.display = 'none';
        },
        expand() {
            whatsappFloat.classList.add('expanded');
        },
        collapse() {
            whatsappFloat.classList.remove('expanded');
        },
        setNotifications(count) {
            updateNotificationBadge(count);
        },
        setMessage(newMessage) {
            const encoded = encodeURIComponent(newMessage);
            whatsappFloat.href = `https://api.whatsapp.com/send?phone=51994146924&text=${encoded}`;
        }
    };

    // ========================================
    // ACCESIBILIDAD
    // ========================================
    
    // Keyboard navigation
    whatsappFloat.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });

    // Reducir animaciones si está configurado
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        whatsappContainer.style.animation = 'none';
        if (tooltip) tooltip.style.animation = 'none';
        if (badge) badge.style.animation = 'none';
    }

    console.log('🚀 WhatsApp Widget cargado correctamente');
});