<?php
/**
 * Formulario público para ejercicio de derechos RGPD
 * Shortcode: [ull_ejercicio_derechos]
 */

if (!defined('ABSPATH')) exit;
?>

<div class="ull-rt-ejercicio-derechos-form">
    <h2>Ejercicio de Derechos RGPD</h2>
    
    <p>Puede ejercer sus derechos de protección de datos personales conforme al RGPD completando este formulario.</p>
    
    <form id="ull-rt-form-derechos" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('ull_rt_public_nonce', 'nonce'); ?>
        
        <div class="form-group">
            <label for="tipo_derecho">Tipo de Derecho *</label>
            <select name="tipo_derecho" id="tipo_derecho" required>
                <option value="">Seleccione un derecho</option>
                <?php
                $tipos = ULL_RT_Ejercicio_Derechos::get_tipos_derechos();
                foreach ($tipos as $valor => $etiqueta) {
                    echo '<option value="' . esc_attr($valor) . '">' . esc_html($etiqueta) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nombre">Nombre Completo *</label>
            <input type="text" name="nombre" id="nombre" required>
        </div>
        
        <div class="form-group">
            <label for="email">Correo Electrónico *</label>
            <input type="email" name="email" id="email" required>
        </div>
        
        <div class="form-group">
            <label for="dni">DNI/NIE</label>
            <input type="text" name="dni" id="dni">
        </div>
        
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="tel" name="telefono" id="telefono">
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción de la Solicitud *</label>
            <textarea name="descripcion" id="descripcion" rows="6" required 
                placeholder="Por favor, describa detalladamente su solicitud"></textarea>
        </div>
        
        <div class="form-group">
            <label for="archivos">Documentos Adjuntos</label>
            <input type="file" name="archivos[]" id="archivos" multiple>
            <small>Puede adjuntar documentos que respalden su solicitud (DNI, justificantes, etc.)</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="acepto_condiciones" required>
                He leído y acepto la <a href="/politica-privacidad" target="_blank">Política de Privacidad</a> *
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn-submit">Enviar Solicitud</button>
        </div>
        
        <div id="ull-rt-mensaje" class="mensaje" style="display: none;"></div>
    </form>
    
    <div class="info-adicional">
        <h3>Información sobre sus derechos</h3>
        <ul>
            <li><strong>Derecho de Acceso:</strong> Conocer qué datos personales tratamos sobre usted.</li>
            <li><strong>Derecho de Rectificación:</strong> Corregir datos inexactos o incompletos.</li>
            <li><strong>Derecho de Supresión:</strong> Solicitar la eliminación de sus datos personales.</li>
            <li><strong>Derecho de Oposición:</strong> Oponerse al tratamiento de sus datos.</li>
            <li><strong>Derecho de Limitación:</strong> Restringir el tratamiento de sus datos.</li>
            <li><strong>Derecho de Portabilidad:</strong> Recibir sus datos en formato estructurado.</li>
        </ul>
        
        <p><strong>Plazo de respuesta:</strong> 1 mes desde la recepción de la solicitud.</p>
        <p><strong>Contacto DPD:</strong> dpd@ull.es</p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ull-rt-form-derechos').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'ull_enviar_solicitud_derecho');
        
        $('#ull-rt-mensaje').hide();
        $('.btn-submit').prop('disabled', true).text('Enviando...');
        
        $.ajax({
            url: ullRTPublic.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#ull-rt-mensaje')
                        .removeClass('error')
                        .addClass('success')
                        .html('<strong>Solicitud enviada correctamente.</strong><br>' + 
                              'Número de solicitud: <strong>' + response.data.numero_solicitud + '</strong><br>' +
                              'Recibirá un email de confirmación en breve.')
                        .show();
                    $('#ull-rt-form-derechos')[0].reset();
                } else {
                    $('#ull-rt-mensaje')
                        .removeClass('success')
                        .addClass('error')
                        .text('Error: ' + response.data.message)
                        .show();
                }
            },
            error: function() {
                $('#ull-rt-mensaje')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error al enviar la solicitud. Por favor, inténtelo de nuevo.')
                    .show();
            },
            complete: function() {
                $('.btn-submit').prop('disabled', false).text('Enviar Solicitud');
            }
        });
    });
});
</script>
