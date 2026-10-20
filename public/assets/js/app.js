/**
 * Reserva Local - JavaScript Central (Vanilla JS, Sin Frameworks)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Menú Móvil Hamburguesa
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            const isOpen = navMenu.classList.contains('open');
            navToggle.setAttribute('aria-expanded', isOpen);
        });
    }

    // 2. Auto-desvanecer alertas tras 6 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 6000);
    });
});

/**
 * Envoltorio para peticiones Fetch con CSRF y manejo de errores
 */
async function apiFetch(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    // Obtener token CSRF del meta o input hidden si existe
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput && !options.headers['X-CSRF-Token']) {
        options.headers['X-CSRF-Token'] = csrfInput.value;
    }

    try {
        const response = await fetch(url, options);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || `Error del servidor (${response.status})`);
        }
        return data;
    } catch (err) {
        console.error('apiFetch Error:', err);
        throw err;
    }
}

/**
 * Obtener ubicación del usuario mediante Geolocation API
 */
function obtenerUbicacionGPS() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('La geolocalización no está soportada por este navegador.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                });
            },
            (error) => {
                let msg = 'Error desconocido al obtener ubicación.';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        msg = 'Permiso de ubicación denegado. Puedes buscar manualmente por colonia o municipio.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        msg = 'Ubicación no disponible en este momento.';
                        break;
                    case error.TIMEOUT:
                        msg = 'Tiempo de espera agotado al consultar GPS.';
                        break;
                }
                reject(new Error(msg));
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    });
}
