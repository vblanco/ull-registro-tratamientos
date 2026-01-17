<?php
if (!defined('ABSPATH')) exit;

$consultas_obj = ULL_RT_Consultas_DPD::get_instance();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$consulta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$tipo_mensaje = '';

// Procesar filtros
$filtro_estado = isset($_GET['filtro_estado']) ? sanitize_text_field($_GET['filtro_estado']) : '';
$filtro_prioridad = isset($_GET['filtro_prioridad']) ? sanitize_text_field($_GET['filtro_prioridad']) : '';
$filtro_buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'fecha_desc';

// Procesar formulario de respuesta
if (isset($_POST['responder_consulta'])) {
    check_admin_referer('ull_rt_consulta_nonce');
    $respuesta = sanitize_textarea_field($_POST['respuesta']);
    
    $respuesta_pdf = null;
    if (isset($_FILES['respuesta_pdf']) && $_FILES['respuesta_pdf']['error'] === UPLOAD_ERR_OK) {
        $respuesta_pdf = $_FILES['respuesta_pdf'];
    }
    
    $resultado = $consultas_obj->responder_consulta($consulta_id, $respuesta, $respuesta_pdf);
    
    if (is_wp_error($resultado)) {
        $mensaje = 'Error: ' . $resultado->get_error_message();
        $tipo_mensaje = 'error';
    } elseif ($resultado) {
        $mensaje_extra = (!empty($resultado['pdf_filename'])) ? ' El PDF se ha adjuntado al email.' : '';
        $mensaje = 'Consulta respondida correctamente.' . $mensaje_extra;
        $tipo_mensaje = 'success';
        $action = 'list';
    } else {
        $mensaje = 'Error al responder la consulta';
        $tipo_mensaje = 'error';
    }
}

// Nueva consulta
if (isset($_POST['guardar_consulta'])) {
    check_admin_referer('ull_rt_consulta_nonce');
    $datos = array(
        'asunto' => sanitize_text_field($_POST['asunto']),
        'consulta' => sanitize_textarea_field($_POST['consulta']),
        'consultante_nombre' => sanitize_text_field($_POST['consultante_nombre']),
        'consultante_email' => sanitize_email($_POST['consultante_email']),
        'consultante_area' => sanitize_text_field($_POST['consultante_area']),
        'categoria' => sanitize_text_field($_POST['categoria']),
        'prioridad' => sanitize_text_field($_POST['prioridad']),
    );
    $resultado = $consultas_obj->crear_consulta($datos);
    if ($resultado) {
        $mensaje = 'Consulta creada correctamente';
        $tipo_mensaje = 'success';
        $action = 'list';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Consultas al DPD</h1>
    
    <?php if ($action == 'list'): ?>
        <a href="?page=ull-registro-consultas&action=new" class="page-title-action">Nueva Consulta</a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($mensaje): ?>
        <div class="notice notice-<?php echo $tipo_mensaje; ?> is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action == 'list'): ?>
        
        <!-- Filtros y búsqueda -->
        <div class="ull-rt-filtros-consultas" style="margin: 20px 0 30px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px; clear: both;">
            <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                <input type="hidden" name="page" value="ull-registro-consultas">
                
                <!-- Búsqueda -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Buscar</label>
                    <input type="text" 
                           name="buscar" 
                           value="<?php echo esc_attr($filtro_buscar); ?>" 
                           placeholder="Número, asunto, nombre..."
                           class="regular-text"
                           style="width: 100%;">
                </div>
                
                <!-- Estado -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Estado</label>
                    <select name="filtro_estado" style="width: 100%;">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>>Pendiente</option>
                        <option value="en_proceso" <?php selected($filtro_estado, 'en_proceso'); ?>>En Proceso</option>
                        <option value="respondida" <?php selected($filtro_estado, 'respondida'); ?>>Respondida</option>
                        <option value="cerrada" <?php selected($filtro_estado, 'cerrada'); ?>>Cerrada</option>
                    </select>
                </div>
                
                <!-- Prioridad -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Prioridad</label>
                    <select name="filtro_prioridad" style="width: 100%;">
                        <option value="">Todas</option>
                        <option value="baja" <?php selected($filtro_prioridad, 'baja'); ?>>Baja</option>
                        <option value="normal" <?php selected($filtro_prioridad, 'normal'); ?>>Normal</option>
                        <option value="alta" <?php selected($filtro_prioridad, 'alta'); ?>>Alta</option>
                        <option value="urgente" <?php selected($filtro_prioridad, 'urgente'); ?>>Urgente</option>
                    </select>
                </div>
                
                <!-- Ordenar por -->
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Ordenar por</label>
                    <select name="orden" style="width: 100%;">
                        <option value="fecha_desc" <?php selected($orden, 'fecha_desc'); ?>>Más recientes</option>
                        <option value="fecha_asc" <?php selected($orden, 'fecha_asc'); ?>>Más antiguas</option>
                        <option value="prioridad" <?php selected($orden, 'prioridad'); ?>>Prioridad</option>
                        <option value="estado" <?php selected($orden, 'estado'); ?>>Estado</option>
                        <option value="asunto" <?php selected($orden, 'asunto'); ?>>Asunto (A-Z)</option>
                    </select>
                </div>
                
                <!-- Botones -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="button button-primary" style="height: 32px;">Filtrar</button>
                    <a href="?page=ull-registro-consultas" class="button" style="height: 32px; line-height: 30px;">Limpiar</a>
                </div>
            </form>
        </div>
        
        <?php 
        // Obtener consultas con filtros
        $args = array();
        if ($filtro_estado) $args['estado'] = $filtro_estado;
        if ($filtro_prioridad) $args['prioridad'] = $filtro_prioridad;
        if ($filtro_buscar) $args['buscar'] = $filtro_buscar;
        if ($orden) $args['orden'] = $orden;
        
        $consultas = $consultas_obj->listar_consultas($args);
        
        // Estadísticas rápidas
        $stats = $consultas_obj->obtener_estadisticas();
        ?>
        
        <!-- Estadísticas rápidas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 0 0 20px 0; clear: both;">
            <div style="background: #fff; padding: 15px; border-left: 4px solid #0073aa; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 3px;">
                <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $stats['total']; ?></div>
                <div style="color: #666; font-size: 13px;">Total de consultas</div>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 3px;">
                <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $stats['pendientes']; ?></div>
                <div style="color: #666; font-size: 13px;">Pendientes</div>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #f0c930; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 3px;">
                <div style="font-size: 24px; font-weight: bold; color: #f0c930;"><?php echo isset($stats['por_estado']['en_proceso']) ? $stats['por_estado']['en_proceso'] : 0; ?></div>
                <div style="color: #666; font-size: 13px;">En proceso</div>
            </div>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 3px;">
                <div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo isset($stats['por_estado']['respondida']) ? $stats['por_estado']['respondida'] : 0; ?></div>
                <div style="color: #666; font-size: 13px;">Respondidas</div>
            </div>
        </div>
        
        <p style="margin: 15px 0; color: #666;">
            Mostrando <strong><?php echo count($consultas); ?></strong> consulta(s)
            <?php if ($filtro_estado || $filtro_prioridad || $filtro_buscar): ?>
                - <a href="?page=ull-registro-consultas">Ver todas</a>
            <?php endif; ?>
        </p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Asunto</th>
                    <th>Consultante</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha</th>
                    <th style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($consultas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No hay consultas</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consultas as $consulta): ?>
                        <tr>
                            <td><?php echo $consulta->id; ?></td>
                            <td><strong><?php echo esc_html($consulta->asunto); ?></strong></td>
                            <td><?php echo esc_html($consulta->consultante_nombre); ?></td>
                            <td><?php echo ucfirst($consulta->estado); ?></td>
                            <td><?php echo ucfirst($consulta->prioridad); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($consulta->fecha_consulta)); ?></td>
                            <td>
                                <a href="?page=ull-registro-consultas&action=view&id=<?php echo $consulta->id; ?>" class="button button-small">Ver</a>
                                <?php if ($consulta->estado == 'pendiente' || $consulta->estado == 'en_proceso'): ?>
                                    <a href="?page=ull-registro-consultas&action=responder&id=<?php echo $consulta->id; ?>" class="button button-small button-primary">Responder</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    <?php elseif ($action == 'new'): ?>
        
        <h2>Nueva Consulta</h2>
        <form method="post">
            <?php wp_nonce_field('ull_rt_consulta_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="asunto">Asunto *</label></th>
                    <td><input type="text" name="asunto" id="asunto" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="consultante_nombre">Nombre *</label></th>
                    <td><input type="text" name="consultante_nombre" id="consultante_nombre" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="consultante_email">Email *</label></th>
                    <td><input type="email" name="consultante_email" id="consultante_email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="consultante_area">Área</label></th>
                    <td><input type="text" name="consultante_area" id="consultante_area" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="categoria">Categoría</label></th>
                    <td>
                        <select name="categoria" id="categoria">
                            <option value="general">General</option>
                            <option value="tratamientos">Tratamientos</option>
                            <option value="derechos">Derechos</option>
                            <option value="seguridad">Seguridad</option>
                            <option value="normativa">Normativa</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="prioridad">Prioridad</label></th>
                    <td>
                        <select name="prioridad" id="prioridad">
                            <option value="baja">Baja</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="consulta">Consulta *</label></th>
                    <td><textarea name="consulta" id="consulta" rows="8" class="large-text" required></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="guardar_consulta" class="button button-primary">Crear Consulta</button>
                <a href="?page=ull-registro-consultas" class="button">Cancelar</a>
            </p>
        </form>
        
    <?php elseif ($action == 'responder' && $consulta_id > 0): ?>
        
        <?php
        $consulta = $consultas_obj->obtener_consulta($consulta_id);
        if (!$consulta) {
            echo '<div class="notice notice-error"><p>Consulta no encontrada</p></div>';
            return;
        }
        ?>
        
        <h2>Responder Consulta</h2>
        
        <div class="postbox">
            <div class="inside">
                <h3>Consulta Original</h3>
                <p><strong>De:</strong> <?php echo esc_html($consulta->consultante_nombre); ?> (<?php echo esc_html($consulta->consultante_email); ?>)</p>
                <p><strong>Asunto:</strong> <?php echo esc_html($consulta->asunto); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($consulta->fecha_consulta)); ?></p>
                <p><strong>Consulta:</strong></p>
                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #2271b1;">
                    <?php echo nl2br(esc_html($consulta->consulta)); ?>
                </div>
            </div>
        </div>
        
        <form method="post" enctype="multipart/form-data" action="?page=ull-registro-consultas&action=responder&id=<?php echo $consulta_id; ?>">
            <?php wp_nonce_field('ull_rt_consulta_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="respuesta">Respuesta (texto)</label></th>
                    <td>
                        <textarea name="respuesta" id="respuesta" rows="10" class="large-text"></textarea>
                        <p class="description">Texto de la respuesta que se enviará por email.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="respuesta_pdf">Respuesta (PDF)</label></th>
                    <td>
                        <input type="file" name="respuesta_pdf" id="respuesta_pdf" accept=".pdf">
                        <p class="description">📎 Opcionalmente, puede adjuntar un PDF con la respuesta oficial. Máximo: 5MB.</p>
                    </td>
                </tr>
            </table>
            <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">
                <p><strong>💡 Nota:</strong> Puede proporcionar la respuesta en texto, en PDF, o ambos. Si proporciona ambos, el PDF se adjuntará al email.</p>
                <p><strong>⚠️ Importante:</strong> Debe proporcionar al menos una respuesta (texto o PDF).</p>
            </div>
            <p class="submit">
                <button type="submit" name="responder_consulta" class="button button-primary">Enviar Respuesta</button>
                <a href="?page=ull-registro-consultas" class="button">Cancelar</a>
            </p>
        </form>
        
    <?php elseif ($action == 'view' && $consulta_id > 0): ?>
        
        <?php
        $consulta = $consultas_obj->obtener_consulta($consulta_id);
        if (!$consulta) {
            echo '<div class="notice notice-error"><p>Consulta no encontrada</p></div>';
            return;
        }
        ?>
        
        <h2>Detalle de Consulta</h2>
        
        <table class="form-table">
            <tr>
                <th>ID:</th>
                <td><?php echo $consulta->id; ?></td>
            </tr>
            <tr>
                <th>Asunto:</th>
                <td><?php echo esc_html($consulta->asunto); ?></td>
            </tr>
            <tr>
                <th>Consultante:</th>
                <td><?php echo esc_html($consulta->consultante_nombre); ?> (<?php echo esc_html($consulta->consultante_email); ?>)</td>
            </tr>
            <tr>
                <th>Área:</th>
                <td><?php echo esc_html($consulta->consultante_area); ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><?php echo ucfirst($consulta->estado); ?></td>
            </tr>
            <tr>
                <th>Prioridad:</th>
                <td><?php echo ucfirst($consulta->prioridad); ?></td>
            </tr>
            <tr>
                <th>Fecha Consulta:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($consulta->fecha_consulta)); ?></td>
            </tr>
            <tr>
                <th>Consulta:</th>
                <td><?php echo nl2br(esc_html($consulta->consulta)); ?></td>
            </tr>
            <?php if (!empty($consulta->respuesta)): ?>
            <tr>
                <th>Respuesta:</th>
                <td><?php echo nl2br(esc_html($consulta->respuesta)); ?></td>
            </tr>
            <tr>
                <th>Fecha Respuesta:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($consulta->fecha_respuesta)); ?></td>
            </tr>
            <tr>
                <th>Tiempo de Respuesta:</th>
                <td><?php echo round($consulta->tiempo_respuesta / 60, 1); ?> horas</td>
            </tr>
            <?php endif; ?>
        </table>
        
        <p>
            <?php if ($consulta->estado == 'pendiente' || $consulta->estado == 'en_proceso'): ?>
                <a href="?page=ull-registro-consultas&action=responder&id=<?php echo $consulta->id; ?>" class="button button-primary">Responder</a>
            <?php endif; ?>
            <a href="?page=ull-registro-consultas" class="button">Volver al Listado</a>
        </p>
        
    <?php endif; ?>
</div>
