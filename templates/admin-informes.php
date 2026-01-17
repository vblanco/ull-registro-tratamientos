<?php
if (!defined('ABSPATH')) exit;

$informes_obj = ULL_RT_Informes_DPD::get_instance();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

// Generar informe
if (isset($_POST['generar_informe'])) {
    check_admin_referer('ull_rt_informe_nonce');
    $tipo = sanitize_text_field($_POST['tipo_informe']);
    $resultado = $informes_obj->generar_informe_automatico($tipo);
    
    if (is_wp_error($resultado)) {
        $mensaje = 'Error al generar el informe: ' . $resultado->get_error_message();
        $tipo_mensaje = 'error';
    } elseif ($resultado) {
        $mensaje = 'Informe generado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error desconocido al generar el informe';
        $tipo_mensaje = 'error';
    }
}
?>

<div class="wrap">
    <h1>Informes del DPD</h1>
    
    <?php if (isset($mensaje)): ?>
        <div class="notice notice-<?php echo $tipo_mensaje; ?> is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action == 'list'): ?>
        
        <div class="postbox" style="margin-top: 20px;">
            <div class="inside">
                <h2>Generar Nuevo Informe</h2>
                <form method="post">
                    <?php wp_nonce_field('ull_rt_informe_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="tipo_informe">Tipo de Informe</label></th>
                            <td>
                                <select name="tipo_informe" id="tipo_informe" style="min-width: 300px;">
                                    <?php
                                    $tipos = ULL_RT_Informes_DPD::get_tipos_informes();
                                    foreach ($tipos as $key => $label) {
                                        echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="generar_informe" class="button button-primary">Generar Informe</button>
                    </p>
                </form>
            </div>
        </div>
        
        <h2>Informes Generados</h2>
        
        <?php $informes = $informes_obj->listar_informes(); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($informes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No hay informes generados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($informes as $informe): ?>
                        <tr>
                            <td><?php echo $informe->id; ?></td>
                            <td><strong><?php echo esc_html($informe->titulo); ?></strong></td>
                            <td><?php echo esc_html($informe->tipo_informe); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($informe->fecha_informe)); ?></td>
                            <td><?php echo ucfirst($informe->estado); ?></td>
                            <td>
                                <a href="?page=ull-registro-informes&action=view&id=<?php echo $informe->id; ?>" class="button button-small">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    <?php elseif ($action == 'view'): ?>
        
        <?php
        $informe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $informe = $informes_obj->obtener_informe($informe_id);
        if (!$informe) {
            echo '<div class="notice notice-error"><p>Informe no encontrado</p></div>';
            return;
        }
        ?>
        
        <h2><?php echo esc_html($informe->titulo); ?></h2>
        
        <p><strong>Tipo:</strong> <?php echo esc_html($informe->tipo_informe); ?></p>
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($informe->fecha_informe)); ?></p>
        <p><strong>Descripción:</strong> <?php echo esc_html($informe->descripcion); ?></p>
        
        <div style="margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 4px;">
            <h3>Contenido del Informe</h3>
            <?php 
            // Deserializar y formatear el contenido
            $contenido = maybe_unserialize($informe->contenido);
            
            if (is_array($contenido)) {
                // Mostrar resumen básico
                if (isset($contenido['total_tratamientos'])) {
                    echo '<p><strong>Total de tratamientos:</strong> ' . $contenido['total_tratamientos'] . '</p>';
                }
                if (isset($contenido['total_con_transferencias'])) {
                    echo '<p><strong>Con transferencias internacionales:</strong> ' . $contenido['total_con_transferencias'] . '</p>';
                }
                if (isset($contenido['total_con_datos_sensibles'])) {
                    echo '<p><strong>Con datos sensibles:</strong> ' . $contenido['total_con_datos_sensibles'] . '</p>';
                }
                
                // Mostrar vista resumida de tratamientos si existen
                if (isset($contenido['tratamientos']) && is_array($contenido['tratamientos']) && count($contenido['tratamientos']) > 0) {
                    echo '<hr>';
                    echo '<p><strong>Tratamientos incluidos:</strong></p>';
                    echo '<ol>';
                    foreach (array_slice($contenido['tratamientos'], 0, 10) as $tratamiento) {
                        echo '<li>' . esc_html($tratamiento->nombre);
                        if (!empty($tratamiento->area_responsable)) {
                            echo ' <em>(' . esc_html($tratamiento->area_responsable) . ')</em>';
                        }
                        echo '</li>';
                    }
                    if (count($contenido['tratamientos']) > 10) {
                        echo '<li><em>... y ' . (count($contenido['tratamientos']) - 10) . ' más</em></li>';
                    }
                    echo '</ol>';
                }
                
                // Mostrar estadísticas si existen
                if (isset($contenido['estadisticas'])) {
                    echo '<hr>';
                    echo '<p><strong>Estadísticas:</strong></p>';
                    echo '<ul>';
                    foreach ($contenido['estadisticas'] as $key => $value) {
                        if (!is_array($value)) {
                            echo '<li>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ': ' . esc_html($value) . '</li>';
                        }
                    }
                    echo '</ul>';
                }
            } else {
                echo '<div style="white-space: pre-wrap;">' . esc_html($contenido) . '</div>';
            }
            ?>
            <p style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 3px solid #0073aa;">
                <strong>Nota:</strong> Esta es una vista resumida. El informe completo con todos los detalles se generará al exportar a PDF.
            </p>
        </div>
        
        <p>
            <a href="?page=ull-registro-informes" class="button">Volver</a>
            <a href="<?php echo admin_url('admin-post.php?action=ull_exportar_informe_pdf&informe_id=' . $informe->id); ?>" 
               class="button button-primary" 
               target="_blank">Exportar a PDF</a>
        </p>
        
    <?php endif; ?>
</div>
