/**
 * Función para alternar campos de comisión sobre local en modal de edición
 */
function toggleCamposComisionLocalEdicion() {
    const selectTope = document.getElementById('edit-tiene-tope-local');
    const camposSinTope = document.getElementById('edit-campos-sin-tope-local');
    const camposConTope = document.getElementById('edit-campos-con-tope-local');
    
    if (!selectTope || !camposSinTope || !camposConTope) {
        console.error('No se encontraron los elementos necesarios para toggle de comisión local');
        return;
    }
    
    const tieneTopeNuevo = selectTope.value === '1';
    console.log('Cambiando estructura de comisión local - Con tope:', tieneTopeNuevo);
    
    if (tieneTopeNuevo) {
        // Mostrar campos con tope (dos porcentajes)
        camposSinTope.style.display = 'none';
        camposConTope.style.display = 'block';
        
        // Limpiar campos sin tope
        const porcentajeUnico = document.getElementById('edit-porcentaje-unico-local');
        if (porcentajeUnico) porcentajeUnico.value = '';
        
        console.log('Mostrando campos CON tope');
    } else {
        // Mostrar campo sin tope (un porcentaje)
        camposSinTope.style.display = 'block';
        camposConTope.style.display = 'none';
        
        // Limpiar campos con tope
        const porcentaje1 = document.getElementById('edit-porcentaje-1-local');
        const porcentaje2 = document.getElementById('edit-porcentaje-2-local');
        if (porcentaje1) porcentaje1.value = '';
        if (porcentaje2) porcentaje2.value = '';
        
        console.log('Mostrando campos SIN tope');
    }
}
