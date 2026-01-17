<?php
if (!defined('ABSPATH')) exit;

$derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$solicitud_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$tipo_mensaje = '';

// Procesar cambio de estado
if (isset($_POST['cambiar_estado'])) {
    check_admin_referer('ull_rt_derecho_nonce');
    $datos = array(
        'estado' => sanitize_text_field($_POST['estado']),
        'respuesta' => sanitize_textarea_field($_POST['respuesta']),
        'accion_realizada' => sanitize_textarea_field($_POST['accion_realizada']),
    );
    $resultado = $derechos_obj->actualizar_solicitud($solicitud_id, $datos);
    if (!is_wp_error($resultado)) {
        $mensaje = 'Solicitud actualizada correctamente';
        $tipo_mensaje = 'success';
        $action = 'list';
    } else {
        $mensaje = $resultado->get_error_message();
        $tipo_mensaje = 'error';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Ejercicio de Derechos RGPD</h1>
    <hr class="wp-header-end">
    
    <?php if ($mensaje): ?>
        <div class="notice notice-<?php echo $tipo_mensaje; ?> is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action == 'list'): ?>
        
        <!-- Estadísticas rápidas -->
        <?php $stats = $derechos_obj->obtener_estadisticas(); ?>
        <div class="ull-rt-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1;">
                <h3 style="margin: 0;">Total Solicitudes</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $stats['total']; ?></p>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #d63638;">
                <h3 style="margin: 0;">Pendientes</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0; color: #d63638;"><?php echo $stats['pendientes']; ?></p>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #d63638;">
                <h3 style="margin: 0;">Vencidas</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0; color: #d63638;"><?php echo $stats['vencidas']; ?></p>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a;">
                <h3 style="margin: 0;">Tiempo Promedio</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $stats['tiempo_promedio_respuesta']; ?> días</p>
            </div>
        </div>
        
        <?php $solicitudes = $derechos_obj->listar_solicitudes(); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nº Solicitud</th>
                    <th>Tipo de Derecho</th>
                    <th>Interesado</th>
                    <th>Estado</th>
                    <th>Fecha Solicitud</th>
                    <th>Fecha Límite</th>
                    <th style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No hay solicitudes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <?php
                        $dias_restantes = ceil((strtotime($solicitud->fecha_limite) - time()) / 86400);
                        $clase_limite = '';
                        if ($dias_restantes < 0) $clase_limite = 'style="color: #d63638; font-weight: bold;"';
                        elseif ($dias_restantes <= 7) $clase_limite = 'style="color: #dba617; font-weight: bold;"';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($solicitud->numero_solicitud); ?></strong></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $solicitud->tipo_derecho)); ?></td>
                            <td><?php echo esc_html($solicitud->interesado_nombre); ?></td>
                            <td><?php echo ucfirst($solicitud->estado); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($solicitud->fecha_solicitud)); ?></td>
                            <td <?php echo $clase_limite; ?>>
                                <?php echo date('d/m/Y', strtotime($solicitud->fecha_limite)); ?>
                                <?php if ($dias_restantes >= 0): ?>
                                    (<?php echo $dias_restantes; ?> días)
                                <?php else: ?>
                                    (VENCIDA)
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=ull-registro-derechos&action=view&id=<?php echo $solicitud->id; ?>" class="button button-small">Ver</a>
                                <?php if ($solicitud->estado == 'recibida' || $solicitud->estado == 'en_proceso'): ?>
                                    <a href="?page=ull-registro-derechos&action=responder&id=<?php echo $solicitud->id; ?>" class="button button-small button-primary">Gestionar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    <?php elseif ($action == 'responder' && $solicitud_id > 0): ?>
        
        <?php
        $solicitud = $derechos_obj->obtener_solicitud($solicitud_id);
        if (!$solicitud) {
            echo '<div class="notice notice-error"><p>Solicitud no encontrada</p></div>';
            return;
        }
        ?>
        
        <h2>Gestionar Solicitud: <?php echo esc_html($solicitud->numero_solicitud); ?></h2>
        
        <div class="postbox">
            <div class="inside">
                <h3>Datos de la Solicitud</h3>
                <p><strong>Tipo de Derecho:</strong> <?php echo ucfirst(str_replace('_', ' ', $solicitud->tipo_derecho)); ?></p>
                <p><strong>Interesado:</strong> <?php echo esc_html($solicitud->interesado_nombre); ?> (<?php echo esc_html($solicitud->interesado_email); ?>)</p>
                <p><strong>DNI:</strong> <?php echo esc_html($solicitud->interesado_dni); ?></p>
                <p><strong>Fecha Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)); ?></p>
                <p><strong>Fecha Límite:</strong> <?php echo date('d/m/Y', strtotime($solicitud->fecha_limite)); ?></p>
                <p><strong>Descripción:</strong></p>
                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #2271b1;">
                    <?php echo nl2br(esc_html($solicitud->descripcion_solicitud)); ?>
                </div>
            </div>
        </div>
        
        <form method="post" action="?page=ull-registro-derechos&action=responder&id=<?php echo $solicitud_id; ?>">
            <?php wp_nonce_field('ull_rt_derecho_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="estado">Estado *</label></th>
                    <td>
                        <select name="estado" id="estado" required>
                            <option value="en_proceso" <?php selected($solicitud->estado, 'en_proceso'); ?>>En Proceso</option>
                            <option value="resuelta">Resuelta</option>
                            <option value="denegada">Denegada</option>
                            <option value="parcial">Resuelta Parcialmente</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="respuesta">Respuesta al Interesado *</label></th>
                    <td><textarea name="respuesta" id="respuesta" rows="8" class="large-text" required><?php echo esc_textarea($solicitud->respuesta); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="accion_realizada">Acción Realizada</label></th>
                    <td><textarea name="accion_realizada" id="accion_realizada" rows="5" class="large-text"><?php echo esc_textarea($solicitud->accion_realizada); ?></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="cambiar_estado" class="button button-primary">Guardar y Enviar Respuesta</button>
                <a href="?page=ull-registro-derechos" class="button">Cancelar</a>
            </p>
        </form>
        
    <?php elseif ($action == 'view' && $solicitud_id > 0): ?>
        
        <?php
        $solicitud = $derechos_obj->obtener_solicitud($solicitud_id);
        if (!$solicitud) {
            echo '<div class="notice notice-error"><p>Solicitud no encontrada</p></div>';
            return;
        }
        ?>
        
        <h2>Detalle de Solicitud: <?php echo esc_html($solicitud->numero_solicitud); ?></h2>
        
        <table class="form-table">
            <tr>
                <th>Número de Solicitud:</th>
                <td><strong><?php echo esc_html($solicitud->numero_solicitud); ?></strong></td>
            </tr>
            <tr>
                <th>Tipo de Derecho:</th>
                <td><?php echo ucfirst(str_replace('_', ' ', $solicitud->tipo_derecho)); ?></td>
            </tr>
            <tr>
                <th>Interesado:</th>
                <td><?php echo esc_html($solicitud->interesado_nombre); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo esc_html($solicitud->interesado_email); ?></td>
            </tr>
            <tr>
                <th>DNI/NIE:</th>
                <td><?php echo esc_html($solicitud->interesado_dni); ?></td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td><?php echo esc_html($solicitud->interesado_telefono); ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><strong><?php echo ucfirst($solicitud->estado); ?></strong></td>
            </tr>
            <tr>
                <th>Fecha Solicitud:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)); ?></td>
            </tr>
            <tr>
                <th>Fecha Límite:</th>
                <td><?php echo date('d/m/Y', strtotime($solicitud->fecha_limite)); ?></td>
            </tr>
            <tr>
                <th>Descripción de la Solicitud:</th>
                <td><?php echo nl2br(esc_html($solicitud->descripcion_solicitud)); ?></td>
            </tr>
            <?php if (!empty($solicitud->respuesta)): ?>
            <tr>
                <th>Respuesta:</th>
                <td><?php echo nl2br(esc_html($solicitud->respuesta)); ?></td>
            </tr>
            <tr>
                <th>Fecha Respuesta:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($solicitud->fecha_respuesta)); ?></td>
            </tr>
            <tr>
                <th>Acción Realizada:</th>
                <td><?php echo nl2br(esc_html($solicitud->accion_realizada)); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <p>
            <?php if ($solicitud->estado == 'recibida' || $solicitud->estado == 'en_proceso'): ?>
                <a href="?page=ull-registro-derechos&action=responder&id=<?php echo $solicitud->id; ?>" class="button button-primary">Gestionar Solicitud</a>
            <?php endif; ?>
            <a href="?page=ull-registro-derechos" class="button">Volver al Listado</a>
        </p>
        
    <?php endif; ?>
</div>
