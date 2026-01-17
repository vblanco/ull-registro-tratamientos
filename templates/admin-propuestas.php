<?php
/**
 * Template: Gestión de Propuestas de Tratamientos
 */

if (!defined('ABSPATH')) exit;

$propuestas_obj = ULL_RT_Propuestas::get_instance();

// Procesar acciones
if (isset($_POST['emitir_informe']) && wp_verify_nonce($_POST['_wpnonce'], 'emitir_informe')) {
    $informe_pdf = null;
    if (isset($_FILES['informe_dpd_pdf']) && $_FILES['informe_dpd_pdf']['error'] === UPLOAD_ERR_OK) {
        $informe_pdf = $_FILES['informe_dpd_pdf'];
    }
    
    $resultado = $propuestas_obj->emitir_informe(
        intval($_POST['propuesta_id']),
        sanitize_text_field($_POST['decision']),
        sanitize_textarea_field($_POST['informe_dpd']),
        $informe_pdf
    );
    
    if (is_wp_error($resultado)) {
        echo '<div class="notice notice-error"><p>Error: ' . $resultado->get_error_message() . '</p></div>';
    } else {
        $decision = sanitize_text_field($_POST['decision']);
        $mensaje = '✅ <strong>Informe emitido correctamente.</strong><br>';
        
        if (!empty($resultado['pdf_filename'])) {
            $mensaje .= '📎 El PDF se ha adjuntado al email de notificación.<br>';
        }
        
        if ($decision === 'aprobada' && !empty($resultado['tratamiento_id'])) {
            $mensaje .= '🎯 <strong>El tratamiento se ha creado automáticamente en el registro.</strong><br>';
            $mensaje .= '<a href="' . admin_url('admin.php?page=ull-rt-tratamientos&action=edit&id=' . $resultado['tratamiento_id']) . '" class="button button-primary" style="margin-top: 10px;">Ver Tratamiento Registrado (ID: ' . $resultado['tratamiento_id'] . ')</a>';
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . $mensaje . '</p></div>';
    }
}

// Obtener propuestas
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pendientes';

$estadisticas = $propuestas_obj->obtener_estadisticas();

?>

<div class="wrap ull-rt-admin">
    <h1>📝 Propuestas de Nuevos Tratamientos</h1>
    
    <div class="ull-rt-stats-mini">
        <div class="stat-box">
            <span class="number"><?php echo $estadisticas['pendientes']; ?></span>
            <span class="label">Pendientes</span>
        </div>
        <div class="stat-box">
            <span class="number"><?php echo $estadisticas['aprobadas']; ?></span>
            <span class="label">Aprobadas</span>
        </div>
        <div class="stat-box">
            <span class="number"><?php echo $estadisticas['denegadas']; ?></span>
            <span class="label">Denegadas</span>
        </div>
        <div class="stat-box">
            <span class="number"><?php echo $estadisticas['modificaciones']; ?></span>
            <span class="label">Modificaciones</span>
        </div>
        <div class="stat-box">
            <span class="number"><?php echo $estadisticas['total']; ?></span>
            <span class="label">Total</span>
        </div>
    </div>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=ull-rt-propuestas&tab=pendientes" class="nav-tab <?php echo $tab === 'pendientes' ? 'nav-tab-active' : ''; ?>">
            ⏳ Pendientes (<?php echo $estadisticas['pendientes']; ?>)
        </a>
        <a href="?page=ull-rt-propuestas&tab=aprobadas" class="nav-tab <?php echo $tab === 'aprobadas' ? 'nav-tab-active' : ''; ?>">
            ✅ Aprobadas (<?php echo $estadisticas['aprobadas']; ?>)
        </a>
        <a href="?page=ull-rt-propuestas&tab=denegadas" class="nav-tab <?php echo $tab === 'denegadas' ? 'nav-tab-active' : ''; ?>">
            ❌ Denegadas (<?php echo $estadisticas['denegadas']; ?>)
        </a>
        <a href="?page=ull-rt-propuestas&tab=modificaciones" class="nav-tab <?php echo $tab === 'modificaciones' ? 'nav-tab-active' : ''; ?>">
            🔄 Modificaciones (<?php echo $estadisticas['modificaciones']; ?>)
        </a>
        <a href="?page=ull-rt-propuestas&tab=todas" class="nav-tab <?php echo $tab === 'todas' ? 'nav-tab-active' : ''; ?>">
            📋 Todas
        </a>
    </nav>
    
    <div class="ull-rt-content">
        <?php
        // Obtener propuestas según la pestaña
        $estado_filtro = '';
        switch ($tab) {
            case 'pendientes':
                $estado_filtro = 'pendiente';
                break;
            case 'aprobadas':
                $estado_filtro = 'aprobada';
                break;
            case 'denegadas':
                $estado_filtro = 'denegada';
                break;
            case 'modificaciones':
                $estado_filtro = 'modificaciones_requeridas';
                break;
        }
        
        $propuestas = $propuestas_obj->listar_propuestas(array('estado' => $estado_filtro));
        
        if (empty($propuestas)) {
            echo '<div class="ull-rt-empty-state">';
            echo '<p>📭 No hay propuestas en esta categoría.</p>';
            echo '</div>';
        } else {
            foreach ($propuestas as $propuesta) {
                $clase_estado = '';
                $icono_estado = '';
                
                switch ($propuesta->estado) {
                    case 'pendiente':
                        $clase_estado = 'pendiente';
                        $icono_estado = '⏳';
                        break;
                    case 'aprobada':
                        $clase_estado = 'aprobada';
                        $icono_estado = '✅';
                        break;
                    case 'denegada':
                        $clase_estado = 'denegada';
                        $icono_estado = '❌';
                        break;
                    case 'modificaciones_requeridas':
                        $clase_estado = 'modificaciones';
                        $icono_estado = '🔄';
                        break;
                }
                ?>
                
                <div class="ull-rt-propuesta-card estado-<?php echo $clase_estado; ?>">
                    <div class="propuesta-header">
                        <div class="propuesta-info">
                            <h3><?php echo esc_html($propuesta->nombre); ?></h3>
                            <p class="propuesta-meta">
                                <span class="numero"><?php echo $icono_estado; ?> <?php echo esc_html($propuesta->numero_propuesta); ?></span>
                                <span class="fecha">Propuesta: <?php echo date('d/m/Y H:i', strtotime($propuesta->fecha_propuesta)); ?></span>
                                <span class="area">📍 <?php echo esc_html($propuesta->area_responsable); ?></span>
                            </p>
                        </div>
                        <div class="propuesta-acciones">
                            <button class="button toggle-detalles" onclick="toggleDetalles(<?php echo $propuesta->id; ?>)">
                                👁️ Ver Detalles
                            </button>
                        </div>
                    </div>
                    
                    <div id="detalles-<?php echo $propuesta->id; ?>" class="propuesta-detalles" style="display: none;">
                        <div class="detalles-grid">
                            <div class="detalle-section">
                                <h4>📋 Información del Tratamiento</h4>
                                <table class="propuesta-tabla">
                                    <tr>
                                        <th>Finalidad:</th>
                                        <td><?php echo nl2br(esc_html($propuesta->finalidad)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Base Jurídica:</th>
                                        <td><?php echo nl2br(esc_html($propuesta->base_juridica)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Colectivos:</th>
                                        <td><?php echo nl2br(esc_html($propuesta->colectivos)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Categorías de Datos:</th>
                                        <td><?php echo nl2br(esc_html($propuesta->categorias_datos)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cesiones:</th>
                                        <td><?php echo $propuesta->cesiones ? nl2br(esc_html($propuesta->cesiones)) : 'No previstas'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Transferencias Internacionales:</th>
                                        <td><?php echo esc_html($propuesta->transferencias_internacionales); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Plazo de Conservación:</th>
                                        <td><?php echo nl2br(esc_html($propuesta->plazo_conservacion)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Medidas de Seguridad:</th>
                                        <td><?php echo $propuesta->medidas_seguridad ? nl2br(esc_html($propuesta->medidas_seguridad)) : 'Por determinar'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="detalle-section">
                                <h4>💭 Justificación</h4>
                                <div class="justificacion-box">
                                    <?php echo nl2br(esc_html($propuesta->justificacion)); ?>
                                </div>
                                
                                <h4>👤 Solicitante</h4>
                                <table class="propuesta-tabla">
                                    <tr>
                                        <th>Nombre:</th>
                                        <td><?php echo esc_html($propuesta->responsable_nombre); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cargo:</th>
                                        <td><?php echo esc_html($propuesta->responsable_cargo); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><a href="mailto:<?php echo esc_attr($propuesta->responsable_email); ?>"><?php echo esc_html($propuesta->responsable_email); ?></a></td>
                                    </tr>
                                    <tr>
                                        <th>Teléfono:</th>
                                        <td><?php echo esc_html($propuesta->responsable_telefono); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($propuesta->informe_dpd || $propuesta->informe_dpd_pdf): ?>
                            <div class="informe-existente">
                                <h4>📄 Informe del DPD</h4>
                                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($propuesta->fecha_informe)); ?></p>
                                <p><strong>Decisión:</strong> 
                                    <?php 
                                    $badges = array(
                                        'aprobada' => '<span style="background: #46b450; color: white; padding: 3px 8px; border-radius: 3px;">✅ APROBADA</span>',
                                        'denegada' => '<span style="background: #dc3232; color: white; padding: 3px 8px; border-radius: 3px;">❌ DENEGADA</span>',
                                        'modificaciones_requeridas' => '<span style="background: #ffb900; color: white; padding: 3px 8px; border-radius: 3px;">🔄 REQUIERE MODIFICACIONES</span>'
                                    );
                                    echo $badges[$propuesta->estado] ?? $propuesta->estado;
                                    ?>
                                </p>
                                
                                <?php if ($propuesta->estado === 'aprobada' && !empty($propuesta->tratamiento_id)): ?>
                                    <div style="background: #e7f3ff; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; border-radius: 3px;">
                                        <p style="margin: 0;">
                                            <strong>🎯 Tratamiento creado automáticamente:</strong><br>
                                            <a href="<?php echo admin_url('admin.php?page=ull-rt-tratamientos&action=edit&id=' . $propuesta->tratamiento_id); ?>" 
                                               class="button button-primary" 
                                               style="margin-top: 8px;">
                                                📋 Ver Tratamiento Registrado (ID: <?php echo $propuesta->tratamiento_id; ?>)
                                            </a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($propuesta->informe_dpd): ?>
                                    <div class="informe-texto">
                                        <?php echo nl2br(esc_html($propuesta->informe_dpd)); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($propuesta->informe_dpd_pdf): ?>
                                    <div class="informe-pdf" style="margin-top: 10px;">
                                        <a href="<?php echo esc_url(wp_get_upload_dir()['baseurl'] . '/ull-informes-dpd/' . $propuesta->informe_dpd_pdf); ?>" 
                                           class="button button-secondary" target="_blank">
                                            📥 Descargar Informe PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($propuesta->estado === 'pendiente'): ?>
                            <div class="emitir-informe-section">
                                <h4>📝 Emitir Informe del DPD</h4>
                                <form method="post" enctype="multipart/form-data" class="form-informe">
                                    <?php wp_nonce_field('emitir_informe'); ?>
                                    <input type="hidden" name="propuesta_id" value="<?php echo $propuesta->id; ?>">
                                    
                                    <div class="form-group">
                                        <label><strong>Decisión:</strong></label>
                                        <select name="decision" required style="width: 100%; padding: 8px;">
                                            <option value="">-- Seleccione una decisión --</option>
                                            <option value="aprobada">✅ Aprobar (se creará el tratamiento automáticamente)</option>
                                            <option value="denegada">❌ Denegar</option>
                                            <option value="modificaciones_requeridas">🔄 Solicitar Modificaciones</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><strong>Informe del DPD (texto):</strong></label>
                                        <textarea name="informe_dpd" 
                                                  rows="8" 
                                                  style="width: 100%; padding: 8px;"
                                                  placeholder="Redacte aquí el informe que se enviará al solicitante..."></textarea>
                                        <small>Este texto se enviará por email al solicitante.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><strong>Informe del DPD (PDF):</strong></label>
                                        <input type="file" 
                                               name="informe_dpd_pdf" 
                                               accept=".pdf"
                                               style="width: 100%; padding: 8px;">
                                        <small>📎 Opcionalmente, puede adjuntar un PDF del informe oficial firmado. Tamaño máximo: 5MB.</small>
                                    </div>
                                    
                                    <p class="description" style="background: #e7f3ff; padding: 10px; border-left: 4px solid #0073aa;">
                                        💡 <strong>Nota:</strong> Puede proporcionar el informe en texto, en PDF, o ambos. Si proporciona ambos, el PDF se adjuntará al email de notificación.
                                    </p>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="emitir_informe" class="button button-primary button-large">
                                            📤 Emitir Informe y Notificar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php
            }
        }
        ?>
    </div>
</div>

<script>
function toggleDetalles(id) {
    var detalles = document.getElementById('detalles-' + id);
    if (detalles.style.display === 'none') {
        detalles.style.display = 'block';
    } else {
        detalles.style.display = 'none';
    }
}
</script>

<style>
.ull-rt-stats-mini {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.ull-rt-stats-mini .stat-box {
    background: white;
    padding: 15px 20px;
    border-radius: 4px;
    border-left: 4px solid #2E75B5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 100px;
}

.ull-rt-stats-mini .number {
    font-size: 32px;
    font-weight: bold;
    color: #2E75B5;
}

.ull-rt-stats-mini .label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.ull-rt-propuesta-card {
    background: white;
    margin: 20px 0;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #ccc;
}

.ull-rt-propuesta-card.estado-pendiente {
    border-left-color: #FFA500;
}

.ull-rt-propuesta-card.estado-aprobada {
    border-left-color: #4CAF50;
}

.ull-rt-propuesta-card.estado-denegada {
    border-left-color: #f44336;
}

.ull-rt-propuesta-card.estado-modificaciones {
    border-left-color: #2196F3;
}

.propuesta-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.propuesta-info h3 {
    margin: 0 0 10px 0;
}

.propuesta-meta {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #666;
}

.propuesta-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.propuesta-detalles {
    padding: 20px;
}

.detalles-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.detalle-section h4 {
    margin-top: 0;
    color: #1F4E78;
}

.propuesta-tabla {
    width: 100%;
    border-collapse: collapse;
}

.propuesta-tabla th,
.propuesta-tabla td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.propuesta-tabla th {
    width: 200px;
    font-weight: bold;
    color: #333;
}

.justificacion-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 3px solid #2E75B5;
    margin-bottom: 20px;
}

.informe-existente {
    background: #e7f3ff;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}

.informe-texto {
    background: white;
    padding: 15px;
    border-radius: 4px;
    margin-top: 10px;
}

.emitir-informe-section {
    background: #fff3cd;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
    border: 1px solid #ffc107;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
}

.form-actions {
    text-align: right;
}

.ull-rt-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}
</style>
