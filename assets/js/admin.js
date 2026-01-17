jQuery(document).ready(function($) {
    // Funcionalidad general del admin
    console.log('ULL Registro Tratamientos Admin JS cargado');
    
    // Confirmación de eliminación
    $('.ull-rt-delete').on('click', function(e) {
        if (!confirm(ullRT.i18n.confirmar_eliminar)) {
            e.preventDefault();
            return false;
        }
    });
});
