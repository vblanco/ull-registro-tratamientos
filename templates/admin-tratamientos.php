<?php
/**
 * Template de Gestión de Tratamientos
 */

if (!defined('ABSPATH')) exit;

$tratamientos_obj = ULL_RT_Tratamientos::get_instance();

// Procesar acciones
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$tratamiento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$tipo_mensaje = '';

// Generar PDF
if ($action === 'generate_pdf' && $tratamiento_id > 0) {
    $pdf_data = ULL_RT_PDF_Generator::generar_pdf_tratamiento($tratamiento_id);
    
    if (is_wp_error($pdf_data)) {
        $mensaje = 'Error: ' . $pdf_data->get_error_message();
        $tipo_mensaje = 'error';
        $action = 'list';
    } else {
        // Enviar HTML como respuesta para impresión/guardado
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $pdf_data['titulo'] . '.html"');
        echo $pdf_data['html'];
        exit;
    }
}


// Guardar tratamiento
if (isset($_POST['guardar_tratamiento'])) {
    check_admin_referer('ull_rt_tratamiento_nonce');
    
    $datos = array(
        'nombre' => sanitize_text_field($_POST['nombre']),
        'base_juridica' => sanitize_textarea_field($_POST['base_juridica']),
        'finalidad' => sanitize_textarea_field($_POST['finalidad']),
        'colectivos_interesados' => sanitize_textarea_field($_POST['colectivos_interesados']),
        'categorias_datos' => sanitize_textarea_field($_POST['categorias_datos']),
        'cesiones_comunicaciones' => sanitize_textarea_field($_POST['cesiones_comunicaciones']),
        'transferencias_internacionales' => sanitize_textarea_field($_POST['transferencias_internacionales']),
        'plazo_conservacion' => sanitize_text_field($_POST['plazo_conservacion']),
        'medidas_seguridad' => sanitize_textarea_field($_POST['medidas_seguridad']),
        'area_responsable' => sanitize_text_field($_POST['area_responsable']),
        'estado' => sanitize_text_field($_POST['estado']),
    );
    
    if ($tratamiento_id > 0) {
        // Actualizar
        $resultado = $tratamientos_obj->actualizar_tratamiento($tratamiento_id, $datos);
        if ($resultado) {
            $mensaje = 'Tratamiento actualizado correctamente';
            $tipo_mensaje = 'success';
            $action = 'list';
        } else {
            $mensaje = 'Error al actualizar el tratamiento';
            $tipo_mensaje = 'error';
        }
    } else {
        // Crear nuevo
        $resultado = $tratamientos_obj->crear_tratamiento($datos);
        if ($resultado) {
            $mensaje = 'Tratamiento creado correctamente';
            $tipo_mensaje = 'success';
            $action = 'list';
        } else {
            $mensaje = 'Error al crear el tratamiento';
            $tipo_mensaje = 'error';
        }
    }
}

// Eliminar tratamiento
if ($action == 'delete' && $tratamiento_id > 0) {
    check_admin_referer('ull_rt_delete_' . $tratamiento_id);
    
    $resultado = $tratamientos_obj->eliminar_tratamiento($tratamiento_id);
    if ($resultado) {
        $mensaje = 'Tratamiento eliminado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar el tratamiento';
        $tipo_mensaje = 'error';
    }
    $action = 'list';
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Actividades de Tratamiento</h1>
    
    <?php if ($action == 'list'): ?>
        <a href="?page=ull-registro-tratamientos&action=new" class="page-title-action">Añadir Nuevo</a>
        <a href="?page=ull-registro-tratamientos&action=importar" class="page-title-action">Importar</a>
        <a href="?page=ull-registro-tratamientos&action=exportar" class="page-title-action">Exportar</a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($mensaje): ?>
        <div class="notice notice-<?php echo $tipo_mensaje; ?> is-dismissible">
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action == 'list'): ?>
        
        <?php
        // Obtener tratamientos primero (antes de usarlos)
        $args = array('estado' => 'activo');
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $args['search'] = sanitize_text_field($_GET['s']);
        }
        
        $tratamientos = $tratamientos_obj->listar_tratamientos($args);
        ?>
        
        <!-- Filtros y búsqueda -->
        <div class="tablenav top">
        <!-- Cabecera de página -->
        <div class="tratamientos-page-header">
            <h1 class="tratamientos-page-title">Registro de Tratamientos</h1>
            <div class="tratamientos-stats">
                <div class="tratamientos-stat-item">
                    <span class="tratamientos-stat-number"><?php echo count($tratamientos); ?></span>
                    <span>Tratamientos activos</span>
                </div>
            </div>
        </div>
        
        <!-- Barra de búsqueda y nuevo -->
        <div class="tratamientos-search">
            <div class="tratamientos-search-form">
                <form method="get" style="flex: 1; display: flex; gap: 12px;">
                    <input type="hidden" name="page" value="ull-registro-tratamientos">
                    <input type="text" 
                           name="s" 
                           class="tratamientos-search-input" 
                           placeholder="Buscar por nombre, área o base jurídica..." 
                           value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    <button type="submit" class="btn-tratamiento btn-tratamiento-primary">
                        🔍 Buscar
                    </button>
                    <?php if (isset($_GET['s']) && !empty($_GET['s'])): ?>
                        <a href="?page=ull-registro-tratamientos" class="btn-tratamiento btn-tratamiento-ghost">
                            ✕ Limpiar
                        </a>
                    <?php endif; ?>
                </form>
                <a href="?page=ull-registro-tratamientos&action=new" class="btn-tratamiento btn-tratamiento-primary">
                    ➕ Nuevo Tratamiento
                </a>
            </div>
        </div>
        
        <!-- Lista de tratamientos en modo tarjetas -->
        <div class="ull-tratamientos-wrapper">
            <?php if (empty($tratamientos)): ?>
                <div class="tratamientos-empty">
                    <div class="tratamientos-empty-icon">📋</div>
                    <h3 class="tratamientos-empty-title">No se encontraron tratamientos</h3>
                    <p class="tratamientos-empty-text">
                        <?php if (isset($_GET['s']) && !empty($_GET['s'])): ?>
                            No hay resultados para "<?php echo esc_html($_GET['s']); ?>"
                        <?php else: ?>
                            Comienza creando tu primer tratamiento
                        <?php endif; ?>
                    </p>
                    <a href="?page=ull-registro-tratamientos&action=new" class="btn-tratamiento btn-tratamiento-primary">
                        ➕ Crear Primer Tratamiento
                    </a>
                </div>
            <?php else: ?>
                <div class="tratamientos-grid">
                    <?php foreach ($tratamientos as $tratamiento): ?>
                        <div class="tratamiento-card" onclick="window.location.href='?page=ull-registro-tratamientos&action=view&id=<?php echo $tratamiento->id; ?>'">
                            <div class="tratamiento-card-header">
                                <div class="tratamiento-id">ID: <?php echo $tratamiento->id; ?></div>
                                <h3 class="tratamiento-nombre"><?php echo esc_html($tratamiento->nombre); ?></h3>
                                <?php if (!empty($tratamiento->area_responsable)): ?>
                                    <div class="tratamiento-area"><?php echo esc_html($tratamiento->area_responsable); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($tratamiento->base_juridica)): ?>
                                <div style="font-size: 13px; color: #666; line-height: 1.6;">
                                    <strong style="color: #0073aa;">Base jurídica:</strong><br>
                                    <?php echo esc_html(mb_substr($tratamiento->base_juridica, 0, 120)) . (mb_strlen($tratamiento->base_juridica) > 120 ? '...' : ''); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tratamiento-meta">
                                <?php if (!empty($tratamiento->fecha_creacion)): ?>
                                    <div class="tratamiento-meta-item">
                                        <span>📅</span>
                                        <span><?php echo date('d/m/Y', strtotime($tratamiento->fecha_creacion)); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="tratamiento-meta-item">
                                    <span class="status-badge status-<?php echo $tratamiento->estado; ?>">
                                        <?php echo strtoupper($tratamiento->estado); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="tratamiento-actions" onclick="event.stopPropagation();">
                                <a href="?page=ull-registro-tratamientos&action=view&id=<?php echo $tratamiento->id; ?>" 
                                   class="btn-tratamiento btn-tratamiento-primary">
                                    👁️ Ver Detalle
                                </a>
                                <a href="?page=ull-registro-tratamientos&action=edit&id=<?php echo $tratamiento->id; ?>" 
                                   class="btn-tratamiento btn-tratamiento-secondary">
                                    ✏️ Editar
                                </a>
                                <a href="?page=ull-registro-tratamientos&action=generate_pdf&id=<?php echo $tratamiento->id; ?>" 
                                   class="btn-tratamiento btn-tratamiento-ghost"
                                   target="_blank"
                                   title="Generar PDF">
                                    📄 PDF
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($action == 'new' || $action == 'edit'): ?>
        
        <?php
        $tratamiento = null;
        if ($action == 'edit' && $tratamiento_id > 0) {
            $tratamiento = $tratamientos_obj->obtener_tratamiento($tratamiento_id);
            if (!$tratamiento) {
                echo '<div class="notice notice-error"><p>Tratamiento no encontrado</p></div>';
                return;
            }
        }
        ?>
        
        <h2><?php echo $action == 'edit' ? 'Editar Tratamiento' : 'Nuevo Tratamiento'; ?></h2>
        
        <form method="post" action="?page=ull-registro-tratamientos&action=<?php echo $action; ?>&id=<?php echo $tratamiento_id; ?>">
            <?php wp_nonce_field('ull_rt_tratamiento_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nombre">Nombre del Tratamiento *</label></th>
                    <td>
                        <input type="text" name="nombre" id="nombre" class="regular-text" required
                               value="<?php echo $tratamiento ? esc_attr($tratamiento->nombre) : ''; ?>">
                        <p class="description">Denominación clara e identificativa de la actividad de tratamiento</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="area_responsable">Área Responsable *</label></th>
                    <td>
                        <input type="text" name="area_responsable" id="area_responsable" class="regular-text" required
                               value="<?php echo $tratamiento ? esc_attr($tratamiento->area_responsable) : ''; ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="base_juridica">Base Jurídica *</label></th>
                    <td>
                        <textarea name="base_juridica" id="base_juridica" rows="4" class="large-text" required><?php echo $tratamiento ? esc_textarea($tratamiento->base_juridica) : ''; ?></textarea>
                        <p class="description">Art. 6.1 RGPD y normativa sectorial aplicable</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="finalidad">Finalidad *</label></th>
                    <td>
                        <textarea name="finalidad" id="finalidad" rows="4" class="large-text" required><?php echo $tratamiento ? esc_textarea($tratamiento->finalidad) : ''; ?></textarea>
                        <p class="description">Descripción clara y específica de la finalidad del tratamiento</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="colectivos_interesados">Colectivos de Interesados *</label></th>
                    <td>
                        <textarea name="colectivos_interesados" id="colectivos_interesados" rows="3" class="large-text" required><?php echo $tratamiento ? esc_textarea($tratamiento->colectivos_interesados) : ''; ?></textarea>
                        <p class="description">Ej: Estudiantes, Personal Docente, PAS, etc.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="categorias_datos">Categorías de Datos Personales *</label></th>
                    <td>
                        <textarea name="categorias_datos" id="categorias_datos" rows="4" class="large-text" required><?php echo $tratamiento ? esc_textarea($tratamiento->categorias_datos) : ''; ?></textarea>
                        <p class="description">Datos identificativos, académicos, económicos, etc. Indicar si incluye categorías especiales (salud, datos biométricos, etc.)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="cesiones_comunicaciones">Cesiones o Comunicaciones</label></th>
                    <td>
                        <textarea name="cesiones_comunicaciones" id="cesiones_comunicaciones" rows="3" class="large-text"><?php echo $tratamiento ? esc_textarea($tratamiento->cesiones_comunicaciones) : ''; ?></textarea>
                        <p class="description">Destinatarios de los datos (si aplica)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="transferencias_internacionales">Transferencias Internacionales</label></th>
                    <td>
                        <textarea name="transferencias_internacionales" id="transferencias_internacionales" rows="3" class="large-text"><?php echo $tratamiento ? esc_textarea($tratamiento->transferencias_internacionales) : 'No previstas'; ?></textarea>
                        <p class="description">Países de destino y garantías (o "No previstas")</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="plazo_conservacion">Plazo de Conservación *</label></th>
                    <td>
                        <input type="text" name="plazo_conservacion" id="plazo_conservacion" class="regular-text" required
                               value="<?php echo $tratamiento ? esc_attr($tratamiento->plazo_conservacion) : ''; ?>">
                        <p class="description">Tiempo de conservación de los datos</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="medidas_seguridad">Medidas de Seguridad</label></th>
                    <td>
                        <textarea name="medidas_seguridad" id="medidas_seguridad" rows="3" class="large-text"><?php echo $tratamiento ? esc_textarea($tratamiento->medidas_seguridad) : 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)'; ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="estado">Estado</label></th>
                    <td>
                        <select name="estado" id="estado">
                            <option value="activo" <?php echo ($tratamiento && $tratamiento->estado == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo ($tratamiento && $tratamiento->estado == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="guardar_tratamiento" class="button button-primary">
                    <?php echo $action == 'edit' ? 'Actualizar Tratamiento' : 'Crear Tratamiento'; ?>
                </button>
                <a href="?page=ull-registro-tratamientos" class="button">Cancelar</a>
            </p>
        </form>
        
    <?php elseif ($action == 'view' && $tratamiento_id > 0): ?>
        
        <?php
        $tratamiento = $tratamientos_obj->obtener_tratamiento($tratamiento_id);
        if (!$tratamiento) {
            echo '<div class="notice notice-error"><p>Tratamiento no encontrado</p></div>';
            return;
        }
        ?>
        
        <h2><?php echo esc_html($tratamiento->nombre); ?></h2>
        
        <div class="ull-rt-view-tratamiento">
            <table class="form-table">
                <tr>
                    <th>ID:</th>
                    <td><?php echo $tratamiento->id; ?></td>
                </tr>
                <tr>
                    <th>Área Responsable:</th>
                    <td><?php echo esc_html($tratamiento->area_responsable); ?></td>
                </tr>
                <tr>
                    <th>Base Jurídica:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->base_juridica)); ?></td>
                </tr>
                <tr>
                    <th>Finalidad:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->finalidad)); ?></td>
                </tr>
                <tr>
                    <th>Colectivos de Interesados:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->colectivos_interesados)); ?></td>
                </tr>
                <tr>
                    <th>Categorías de Datos:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->categorias_datos)); ?></td>
                </tr>
                <tr>
                    <th>Cesiones/Comunicaciones:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->cesiones_comunicaciones)); ?></td>
                </tr>
                <tr>
                    <th>Transferencias Internacionales:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->transferencias_internacionales)); ?></td>
                </tr>
                <tr>
                    <th>Plazo de Conservación:</th>
                    <td><?php echo esc_html($tratamiento->plazo_conservacion); ?></td>
                </tr>
                <tr>
                    <th>Medidas de Seguridad:</th>
                    <td><?php echo nl2br(esc_html($tratamiento->medidas_seguridad)); ?></td>
                </tr>
                <tr>
                    <th>Estado:</th>
                    <td>
                        <span class="ull-rt-status-<?php echo $tratamiento->estado; ?>">
                            <?php echo ucfirst($tratamiento->estado); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Versión:</th>
                    <td><?php echo $tratamiento->version; ?></td>
                </tr>
                <tr>
                    <th>Fecha de Creación:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($tratamiento->fecha_creacion)); ?></td>
                </tr>
                <tr>
                    <th>Última Modificación:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($tratamiento->fecha_modificacion)); ?></td>
                </tr>
            </table>
            
            <p>
                <a href="?page=ull-registro-tratamientos&action=edit&id=<?php echo $tratamiento->id; ?>" class="button button-primary">Editar</a>
                <a href="?page=ull-registro-tratamientos" class="button">Volver al Listado</a>
            </p>
        </div>
        
    <?php elseif ($action == 'importar'): ?>
        
        <h2>Importar Tratamientos desde CSV</h2>
        
        <div class="notice notice-info">
            <p><strong>Instrucciones:</strong></p>
            <ul>
                <li>El archivo debe estar en formato CSV con separador punto y coma (;)</li>
                <li>La primera fila debe contener los encabezados de columnas</li>
                <li>Si un tratamiento ya existe (mismo nombre), se actualizará</li>
                <li>Si no existe, se creará uno nuevo</li>
            </ul>
        </div>
        
        <div class="postbox" style="max-width: 800px;">
            <div class="inside">
                <h3>Descargar Plantillas</h3>
                <p>Descargue una de estas plantillas para importar sus datos:</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=ull-registro-tratamientos&action=descargar_plantilla'); ?>" class="button">
                        📄 Descargar Plantilla Vacía
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ull-registro-tratamientos&action=descargar_33_tratamientos'); ?>" class="button button-primary">
                        📋 Descargar 33 Tratamientos ULL Completos
                    </a>
                </p>
                <p class="description">
                    <strong>Plantilla Vacía:</strong> Use esta plantilla para añadir sus propios tratamientos.<br>
                    <strong>33 Tratamientos ULL:</strong> Plantilla completa con los 33 tratamientos oficiales de la ULL listos para importar.
                </p>
            </div>
        </div>
        
        <div class="postbox" style="max-width: 800px; margin-top: 20px;">
            <div class="inside">
                <h3>Importar Archivo CSV</h3>
                <form method="post" enctype="multipart/form-data" id="form-importar">
                    <?php wp_nonce_field('ull_rt_importar_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="archivo_csv">Archivo CSV *</label></th>
                            <td>
                                <input type="file" name="archivo_csv" id="archivo_csv" accept=".csv" required>
                                <p class="description">Seleccione el archivo CSV con los tratamientos a importar</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="importar_csv" class="button button-primary">
                            ⬆️ Importar Tratamientos
                        </button>
                        <a href="?page=ull-registro-tratamientos" class="button">Cancelar</a>
                    </p>
                </form>
                
                <div id="resultado-importacion" style="display: none; margin-top: 20px;"></div>
            </div>
        </div>
        
    <?php elseif ($action == 'exportar'): ?>
        
        <h2>Exportar Tratamientos</h2>
        
        <div class="postbox" style="max-width: 800px;">
            <div class="inside">
                <h3>Exportar a CSV</h3>
                <p>Exporte todos los tratamientos activos a un archivo CSV que puede abrir con Excel u otras aplicaciones.</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=ull-registro-tratamientos&action=descargar_exportacion'); ?>" class="button button-primary button-hero">
                        ⬇️ Descargar Tratamientos (CSV)
                    </a>
                </p>
                <p class="description">
                    El archivo incluirá todos los tratamientos activos con todos sus campos.<br>
                    Puede modificar el archivo y volver a importarlo si lo desea.
                </p>
            </div>
        </div>
        
        <p style="margin-top: 20px;">
            <a href="?page=ull-registro-tratamientos" class="button">Volver al Listado</a>
        </p>
        
    <?php endif; ?>
</div>

<?php
// Procesar importación
if (isset($_POST['importar_csv'])) {
    check_admin_referer('ull_rt_importar_nonce');
    
    if (!empty($_FILES['archivo_csv']['tmp_name'])) {
        $import_export = ULL_RT_Import_Export::get_instance();
        $resultado = $import_export->importar_desde_csv($_FILES['archivo_csv']);
        
        if (is_wp_error($resultado)) {
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($resultado->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Importación Completada</strong></p>';
            echo '<ul>';
            echo '<li>✅ Total importados: <strong>' . $resultado['importados'] . '</strong></li>';
            echo '<li>🆕 Nuevos: <strong>' . $resultado['nuevos'] . '</strong></li>';
            echo '<li>🔄 Actualizados: <strong>' . $resultado['actualizados'] . '</strong></li>';
            if (!empty($resultado['errores'])) {
                echo '<li>⚠️ Errores: <strong>' . count($resultado['errores']) . '</strong></li>';
                echo '<ul style="margin-left: 20px;">';
                foreach ($resultado['errores'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            echo '</ul>';
            echo '<p><a href="?page=ull-registro-tratamientos" class="button button-primary">Ver Tratamientos</a></p>';
            echo '</div>';
        }
    }
}

// Procesar descargas
if ($action == 'descargar_plantilla') {
    $import_export = ULL_RT_Import_Export::get_instance();
    $import_export->generar_plantilla_csv();
    exit;
}

if ($action == 'descargar_33_tratamientos') {
    $import_export = ULL_RT_Import_Export::get_instance();
    $import_export->generar_csv_33_tratamientos();
    exit;
}

if ($action == 'descargar_exportacion') {
    $import_export = ULL_RT_Import_Export::get_instance();
    $import_export->exportar_a_csv();
    exit;
}
?>
