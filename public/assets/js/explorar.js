/**
 * Explorar Negocios - Búsqueda por Distancia (Haversine), Categoría y Texto
 */

document.addEventListener('DOMContentLoaded', () => {
    const btnGps = document.getElementById('btnGps');
    const inputLat = document.getElementById('inputLat');
    const inputLng = document.getElementById('inputLng');
    const gpsStatus = document.getElementById('gpsStatus');
    const formFiltros = document.getElementById('formFiltros');
    const manualLocationGroup = document.getElementById('manualLocationGroup');

    if (btnGps) {
        btnGps.addEventListener('click', async () => {
            btnGps.disabled = true;
            gpsStatus.innerHTML = '<span class="spinner" style="width:16px;height:16px;"></span> Obteniendo ubicación GPS...';
            gpsStatus.style.display = 'block';

            try {
                const coords = await obtenerUbicacionGPS();
                inputLat.value = coords.lat.toFixed(7);
                inputLng.value = coords.lng.toFixed(7);
                gpsStatus.innerHTML = '✓ Ubicación detectada con éxito. Calculando distancias...';
                gpsStatus.className = 'form-text text-success';
                
                // Enviar formulario automáticamente para refrescar resultados con Haversine
                setTimeout(() => {
                    formFiltros.submit();
                }, 500);
            } catch (err) {
                gpsStatus.innerHTML = `⚠️ ${err.message}`;
                gpsStatus.className = 'form-text text-danger';
                btnGps.disabled = false;

                // Enfocar campo de zona manual si el usuario rechazó el permiso
                if (manualLocationGroup) {
                    const inputZona = document.getElementById('inputZona');
                    if (inputZona) {
                        inputZona.focus();
                        inputZona.style.borderColor = 'var(--accent)';
                    }
                }
            }
        });
    }

    // Botón para simular ubicación GPS en San Salvador (Demo)
    const btnGpsDemo = document.getElementById('btnGpsDemo');
    if (btnGpsDemo) {
        btnGpsDemo.addEventListener('click', () => {
            inputLat.value = '13.6989000';
            inputLng.value = '-89.2415000';
            gpsStatus.innerHTML = '✓ Coordenadas demo San Salvador (Col. Escalón / San Benito) asignadas. Calculando distancias...';
            gpsStatus.className = 'form-text text-success';
            gpsStatus.style.display = 'block';
            setTimeout(() => {
                formFiltros.submit();
            }, 300);
        });
    }

    // Botón para limpiar ubicación GPS
    const btnClearGps = document.getElementById('btnClearGps');
    if (btnClearGps) {
        btnClearGps.addEventListener('click', () => {
            inputLat.value = '';
            inputLng.value = '';
            formFiltros.submit();
        });
    }
});
