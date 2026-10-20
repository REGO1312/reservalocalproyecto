/**
 * Reserva Local - Wizard Interactivo de Citas y Selección de Horarios
 */

document.addEventListener('DOMContentLoaded', () => {
    const wizardForm = document.getElementById('bookingForm');
    if (!wizardForm) return;

    const negocioId = wizardForm.dataset.negocioId;
    const selectServicio = document.getElementById('selectServicio');
    const selectProfesional = document.getElementById('selectProfesional');
    const inputFecha = document.getElementById('inputFecha');
    const slotsContainer = document.getElementById('slotsContainer');
    const slotsLoading = document.getElementById('slotsLoading');
    const slotsEmpty = document.getElementById('slotsEmpty');
    const inputHoraInicio = document.getElementById('inputHoraInicio');
    const inputHoraFin = document.getElementById('inputHoraFin');
    const hiddenProfId = document.getElementById('hiddenProfId');

    const summaryServicio = document.getElementById('summaryServicio');
    const summaryPrecio = document.getElementById('summaryPrecio');
    const summaryDuracion = document.getElementById('summaryDuracion');
    const summaryFecha = document.getElementById('summaryFecha');
    const summaryHora = document.getElementById('summaryHora');
    const summaryProfesional = document.getElementById('summaryProfesional');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const bookingError = document.getElementById('bookingError');

    // Cambios que disparan recarga de slots disponibles
    selectServicio.addEventListener('change', () => {
        updateSummary();
        cargarDisponibilidad();
    });

    selectProfesional.addEventListener('change', () => {
        updateSummary();
        cargarDisponibilidad();
    });

    inputFecha.addEventListener('change', () => {
        updateSummary();
        cargarDisponibilidad();
    });

    function updateSummary() {
        const servOpt = selectServicio.selectedOptions[0];
        if (servOpt && servOpt.value) {
            summaryServicio.textContent = servOpt.dataset.nombre || servOpt.text;
            summaryPrecio.textContent = '$' + parseFloat(servOpt.dataset.precio || 0).toFixed(2);
            summaryDuracion.textContent = (servOpt.dataset.duracion || 0) + ' min';
        } else {
            summaryServicio.textContent = '-';
            summaryPrecio.textContent = '-';
            summaryDuracion.textContent = '-';
        }

        const fechaVal = inputFecha.value;
        summaryFecha.textContent = fechaVal || '-';

        const profOpt = selectProfesional.selectedOptions[0];
        summaryProfesional.textContent = (profOpt && profOpt.value) ? profOpt.text : 'Cualquiera disponible';
    }

    async function cargarDisponibilidad() {
        const sId = selectServicio.value;
        const fecha = inputFecha.value;
        const pId = selectProfesional.value || '';

        // Reset selección previa de slot
        inputHoraInicio.value = '';
        inputHoraFin.value = '';
        summaryHora.textContent = '-';
        if (btnConfirmar) btnConfirmar.disabled = true;
        if (bookingError) bookingError.style.display = 'none';

        if (!sId || !fecha) {
            slotsContainer.innerHTML = '';
            slotsEmpty.style.display = 'block';
            slotsEmpty.textContent = 'Selecciona un servicio y una fecha para ver los horarios disponibles.';
            return;
        }

        slotsContainer.innerHTML = '';
        slotsLoading.style.display = 'block';
        slotsEmpty.style.display = 'none';

        try {
            const apiUrl = `${wizardForm.dataset.apiUrl}?negocio_id=${negocioId}&servicio_id=${sId}&fecha=${fecha}&profesional_id=${pId}`;
            const res = await apiFetch(apiUrl);

            slotsLoading.style.display = 'none';

            if (!res.slots || res.slots.length === 0) {
                slotsEmpty.style.display = 'block';
                slotsEmpty.textContent = res.motivo || 'No hay horarios disponibles para esta fecha. Intenta con otro día o especialista.';
                return;
            }

            // Renderizar botones de slots
            res.slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'slot-btn';
                btn.innerHTML = `<strong>${slot.hora_inicio_formato}</strong><br><small class="text-muted">${slot.profesionales.length} disp.</small>`;

                btn.addEventListener('click', () => {
                    // Remover clase selected de todos
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');

                    inputHoraInicio.value = slot.hora_inicio;
                    inputHoraFin.value = slot.hora_fin;
                    summaryHora.textContent = `${slot.hora_inicio_formato} - ${slot.hora_fin_formato}`;

                    // Asignar profesional: si el usuario eligió uno en el select, usar ese; si no, el primero libre del slot
                    if (pId) {
                        hiddenProfId.value = pId;
                    } else if (slot.profesionales && slot.profesionales.length > 0) {
                        hiddenProfId.value = slot.profesionales[0].id;
                        summaryProfesional.textContent = slot.profesionales[0].nombre;
                    }

                    if (btnConfirmar) btnConfirmar.disabled = false;
                });

                slotsContainer.appendChild(btn);
            });

        } catch (err) {
            slotsLoading.style.display = 'none';
            slotsEmpty.style.display = 'block';
            slotsEmpty.textContent = 'Error al cargar horarios: ' + err.message;
        }
    }

    // Envío final del formulario
    wizardForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!inputHoraInicio.value) {
            alert('Por favor selecciona un horario de cita disponible.');
            return;
        }

        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="spinner" style="width:18px;height:18px;"></span> Confirmando Cita...';
        if (bookingError) bookingError.style.display = 'none';

        const formData = new FormData(wizardForm);

        try {
            const resp = await apiFetch(wizardForm.action, {
                method: 'POST',
                body: formData
            });

            if (resp.success && resp.redirect) {
                window.location.href = resp.redirect;
            } else {
                window.location.href = 'mis-citas.php?reserva=' + (resp.codigo_reserva || '');
            }
        } catch (err) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Confirmar y Reservar Cita';
            if (bookingError) {
                bookingError.textContent = err.message;
                bookingError.style.display = 'block';
            } else {
                alert('Error al reservar: ' + err.message);
            }
        }
    });

    // Inicializar estado inicial
    updateSummary();
    if (selectServicio.value && inputFecha.value) {
        cargarDisponibilidad();
    }
});
