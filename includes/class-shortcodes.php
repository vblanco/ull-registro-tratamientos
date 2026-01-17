<?php
/**
 * Shortcodes Públicos del Sistema RGPD
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Registrar shortcodes
        add_shortcode('ull_ejercicio_derechos', array($this, 'shortcode_ejercicio_derechos'));
        add_shortcode('ull_listado_tratamientos', array($this, 'shortcode_listado_tratamientos'));
        add_shortcode('ull_detalle_tratamiento', array($this, 'shortcode_detalle_tratamiento'));
        add_shortcode('ull_estadisticas_rgpd', array($this, 'shortcode_estadisticas'));
        add_shortcode('ull_consultar_solicitud', array($this, 'shortcode_consultar_solicitud'));
        add_shortcode('ull_informacion_dpd', array($this, 'shortcode_informacion_dpd'));
        add_shortcode('ull_proponer_tratamiento', array($this, 'shortcode_proponer_tratamiento'));
        add_shortcode('ull_consulta_dpd', array($this, 'shortcode_consulta_dpd'));
        
        // AJAX para consultar solicitud
        add_action('wp_ajax_nopriv_ull_consultar_estado_solicitud', array($this, 'ajax_consultar_estado'));
        add_action('wp_ajax_ull_consultar_estado_solicitud', array($this, 'ajax_consultar_estado'));
        
        // Registrar scripts públicos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
    }
    
    /**
     * Registrar scripts públicos
     */
    public function enqueue_public_scripts() {
        // Solo cargar si hay un shortcode en la página
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'ull_consultar_solicitud') ||
            has_shortcode($post->post_content, 'ull_ejercicio_derechos') ||
            has_shortcode($post->post_content, 'ull_listado_tratamientos')
        )) {
            wp_localize_script('jquery', 'ullRTPublic', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ull_rt_public_nonce')
            ));
        }
    }
    
    /**
     * Shortcode: Listado de tratamientos
     * Uso: [ull_listado_tratamientos limite="10" area="Secretaría General" busqueda="si" por_pagina="10" paginacion="si"]
     */
    public function shortcode_listado_tratamientos($atts) {
        $atts = shortcode_atts(array(
            'limite' => -1,
            'area' => '',
            'busqueda' => 'no',
            'vista' => 'tabla', // tabla o tarjetas
            'por_pagina' => 10, // número de items por página
            'paginacion' => 'si', // activar/desactivar paginación
        ), $atts);
        
        // Si hay parámetro ver_tratamiento, mostrar detalle en lugar de listado
        if (isset($_GET['ver_tratamiento']) && !empty($_GET['ver_tratamiento'])) {
            return $this->shortcode_detalle_tratamiento(array());
        }
        
        ob_start();
        
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        
        // Obtener página actual
        $pagina_actual = isset($_GET['pag_tratamientos']) ? max(1, intval($_GET['pag_tratamientos'])) : 1;
        
        // Procesar búsqueda
        $args = array('estado' => 'activo');
        
        // Filtro por área desde parámetro del shortcode
        if (!empty($atts['area'])) {
            $args['area_responsable'] = $atts['area'];
        }
        
        // Filtro por área desde URL (tiene prioridad)
        if (!empty($_GET['filtro_area'])) {
            $args['area_responsable'] = sanitize_text_field($_GET['filtro_area']);
        }
        
        if (!empty($_GET['buscar_tratamiento'])) {
            $args['search'] = sanitize_text_field($_GET['buscar_tratamiento']);
        }
        
        // Obtener TODOS los tratamientos que cumplen los criterios
        $todos_tratamientos = $tratamientos_obj->listar_tratamientos($args);
        $total_tratamientos = count($todos_tratamientos);
        
        // Aplicar límite si está definido y no hay paginación
        if ($atts['limite'] > 0 && $atts['paginacion'] == 'no') {
            $todos_tratamientos = array_slice($todos_tratamientos, 0, intval($atts['limite']));
            $tratamientos = $todos_tratamientos;
            $total_paginas = 1;
        } 
        // Aplicar paginación
        elseif ($atts['paginacion'] == 'si') {
            $por_pagina = intval($atts['por_pagina']);
            $total_paginas = ceil($total_tratamientos / $por_pagina);
            $offset = ($pagina_actual - 1) * $por_pagina;
            $tratamientos = array_slice($todos_tratamientos, $offset, $por_pagina);
        }
        // Sin límite ni paginación
        else {
            $tratamientos = $todos_tratamientos;
            $total_paginas = 1;
        }
        
        ?>
        <div class="ull-rt-listado-tratamientos">
            
            <?php if ($atts['busqueda'] == 'si'): ?>
            <div class="ull-rt-busqueda">
                <form method="get" class="ull-rt-form-busqueda">
                    <input type="text" 
                           name="buscar_tratamiento" 
                           placeholder="Buscar tratamiento..." 
                           value="<?php echo isset($_GET['buscar_tratamiento']) ? esc_attr($_GET['buscar_tratamiento']) : ''; ?>">
                    <button type="submit" class="ull-rt-btn-buscar">Buscar</button>
                    <?php if (isset($_GET['buscar_tratamiento'])): ?>
                        <a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="ull-rt-btn-limpiar">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>
            
            <?php 
            // Mostrar filtro activo si existe
            $filtro_area_activo = !empty($_GET['filtro_area']) ? sanitize_text_field($_GET['filtro_area']) : '';
            if ($filtro_area_activo): 
            ?>
            <div class="ull-rt-filtro-activo">
                <span class="ull-rt-filtro-texto">
                    Filtrando por área: <strong><?php echo esc_html($filtro_area_activo); ?></strong>
                </span>
                <a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="ull-rt-btn-limpiar-filtro">
                    Mostrar todas las áreas
                </a>
            </div>
            <?php endif; ?>
            
            <div class="ull-rt-info-resultados">
                <?php if ($atts['paginacion'] == 'si' && $total_tratamientos > 0): ?>
                    <p class="ull-rt-total">
                        <strong>Mostrando:</strong> 
                        <?php 
                        $inicio = (($pagina_actual - 1) * intval($atts['por_pagina'])) + 1;
                        $fin = min($pagina_actual * intval($atts['por_pagina']), $total_tratamientos);
                        echo $inicio . ' - ' . $fin . ' de ' . $total_tratamientos . ' tratamientos';
                        ?>
                    </p>
                <?php else: ?>
                    <p class="ull-rt-total">
                        <strong>Total de tratamientos:</strong> <?php echo count($tratamientos); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['vista'] == 'tabla'): ?>
                
                <!-- Vista tabla -->
                <div class="ull-rt-tabla-responsive">
                    <table class="ull-rt-tabla">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Finalidad</th>
                                <th>Área Responsable</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tratamientos)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No se encontraron tratamientos</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tratamientos as $tratamiento): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($tratamiento->nombre); ?></strong></td>
                                        <td><?php echo esc_html(wp_trim_words($tratamiento->finalidad, 15)); ?></td>
                                        <td><?php echo esc_html($tratamiento->area_responsable); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(add_query_arg('ver_tratamiento', $tratamiento->id)); ?>" class="ull-rt-btn-ver">
                                                Ver detalles
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php else: ?>
                
                <!-- Vista tarjetas -->
                <div class="ull-rt-tarjetas">
                    <?php if (empty($tratamientos)): ?>
                        <p style="text-align: center;">No se encontraron tratamientos</p>
                    <?php else: ?>
                        <?php foreach ($tratamientos as $tratamiento): ?>
                            <div class="ull-rt-tarjeta">
                                <h3><?php echo esc_html($tratamiento->nombre); ?></h3>
                                <p class="ull-rt-area"><?php echo esc_html($tratamiento->area_responsable); ?></p>
                                <p class="ull-rt-finalidad"><?php echo esc_html(wp_trim_words($tratamiento->finalidad, 20)); ?></p>
                                <a href="<?php echo esc_url(add_query_arg('ver_tratamiento', $tratamiento->id)); ?>" class="ull-rt-btn-detalle">
                                    Ver información completa
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
            
            <?php if ($atts['paginacion'] == 'si' && $total_paginas > 1): ?>
                <!-- Paginación -->
                <div class="ull-rt-paginacion">
                    <?php
                    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                    $query_params = $_GET;
                    unset($query_params['pag_tratamientos']);
                    $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
                    
                    // Botón Primera página
                    if ($pagina_actual > 1) {
                        echo '<a href="' . $base_url . '?pag_tratamientos=1' . $query_string . '" class="ull-rt-pag-btn ull-rt-pag-first">« Primera</a>';
                    }
                    
                    // Botón Anterior
                    if ($pagina_actual > 1) {
                        echo '<a href="' . $base_url . '?pag_tratamientos=' . ($pagina_actual - 1) . $query_string . '" class="ull-rt-pag-btn ull-rt-pag-prev">‹ Anterior</a>';
                    }
                    
                    // Números de página
                    $rango = 2; // Mostrar 2 páginas a cada lado de la actual
                    $inicio_rango = max(1, $pagina_actual - $rango);
                    $fin_rango = min($total_paginas, $pagina_actual + $rango);
                    
                    // Mostrar primera página si no está en el rango
                    if ($inicio_rango > 1) {
                        echo '<a href="' . $base_url . '?pag_tratamientos=1' . $query_string . '" class="ull-rt-pag-numero">1</a>';
                        if ($inicio_rango > 2) {
                            echo '<span class="ull-rt-pag-dots">...</span>';
                        }
                    }
                    
                    // Mostrar páginas en el rango
                    for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                        if ($i == $pagina_actual) {
                            echo '<span class="ull-rt-pag-numero ull-rt-pag-actual">' . $i . '</span>';
                        } else {
                            echo '<a href="' . $base_url . '?pag_tratamientos=' . $i . $query_string . '" class="ull-rt-pag-numero">' . $i . '</a>';
                        }
                    }
                    
                    // Mostrar última página si no está en el rango
                    if ($fin_rango < $total_paginas) {
                        if ($fin_rango < $total_paginas - 1) {
                            echo '<span class="ull-rt-pag-dots">...</span>';
                        }
                        echo '<a href="' . $base_url . '?pag_tratamientos=' . $total_paginas . $query_string . '" class="ull-rt-pag-numero">' . $total_paginas . '</a>';
                    }
                    
                    // Botón Siguiente
                    if ($pagina_actual < $total_paginas) {
                        echo '<a href="' . $base_url . '?pag_tratamientos=' . ($pagina_actual + 1) . $query_string . '" class="ull-rt-pag-btn ull-rt-pag-next">Siguiente ›</a>';
                    }
                    
                    // Botón Última página
                    if ($pagina_actual < $total_paginas) {
                        echo '<a href="' . $base_url . '?pag_tratamientos=' . $total_paginas . $query_string . '" class="ull-rt-pag-btn ull-rt-pag-last">Última »</a>';
                    }
                    ?>
                </div>
                
                <!-- Info adicional de paginación -->
                <div class="ull-rt-pag-info">
                    <p>Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></p>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Detalle de tratamiento
     * Uso: [ull_detalle_tratamiento id="5"]
     * O automáticamente con ?ver_tratamiento=5
     */
    public function shortcode_detalle_tratamiento($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        // Obtener ID de URL o atributo
        $tratamiento_id = !empty($_GET['ver_tratamiento']) ? intval($_GET['ver_tratamiento']) : intval($atts['id']);
        
        if ($tratamiento_id <= 0) {
            return '<p>Por favor, especifique un tratamiento válido.</p>';
        }
        
        ob_start();
        
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $tratamiento = $tratamientos_obj->obtener_tratamiento($tratamiento_id);
        
        if (!$tratamiento || $tratamiento->estado != 'activo') {
            return '<p>Tratamiento no encontrado o no disponible.</p>';
        }
        
        ?>
        <div class="ull-rt-detalle-tratamiento">
            
            <!-- Header limpio -->
            <div class="ull-rt-detalle-header">
                <div class="ull-rt-detalle-metadata">
                    <span class="ull-rt-detalle-id">Tratamiento #<?php echo $tratamiento->id; ?></span>
                    <?php if (!empty($tratamiento->area_responsable)): ?>
                        <span class="ull-rt-detalle-separator">•</span>
                        <span class="ull-rt-detalle-area"><?php echo esc_html($tratamiento->area_responsable); ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="ull-rt-detalle-titulo"><?php echo esc_html($tratamiento->nombre); ?></h1>
            </div>
            
            <!-- Contenido en dos columnas -->
            <div class="ull-rt-detalle-contenido">
                
                <!-- Columna principal -->
                <div class="ull-rt-detalle-principal">
                    
                    <?php if (!empty($tratamiento->finalidad)): ?>
                    <section class="ull-rt-seccion">
                        <h2 class="ull-rt-seccion-titulo">Finalidad del tratamiento</h2>
                        <div class="ull-rt-seccion-contenido">
                            <?php echo nl2br(esc_html($tratamiento->finalidad)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->base_juridica)): ?>
                    <section class="ull-rt-seccion">
                        <h2 class="ull-rt-seccion-titulo">Base jurídica</h2>
                        <div class="ull-rt-seccion-contenido">
                            <?php echo nl2br(esc_html($tratamiento->base_juridica)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->colectivos_interesados)): ?>
                    <section class="ull-rt-seccion">
                        <h2 class="ull-rt-seccion-titulo">Colectivos de interesados</h2>
                        <div class="ull-rt-seccion-contenido">
                            <?php echo nl2br(esc_html($tratamiento->colectivos_interesados)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->categorias_datos)): ?>
                    <section class="ull-rt-seccion">
                        <h2 class="ull-rt-seccion-titulo">Categorías de datos personales</h2>
                        <div class="ull-rt-seccion-contenido">
                            <?php echo nl2br(esc_html($tratamiento->categorias_datos)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Columna lateral -->
                <aside class="ull-rt-detalle-lateral">
                    
                    <?php if (!empty($tratamiento->cesiones_comunicaciones) && $tratamiento->cesiones_comunicaciones != 'No previstas'): ?>
                    <section class="ull-rt-seccion-lateral">
                        <h3 class="ull-rt-seccion-lateral-titulo">Destinatarios</h3>
                        <div class="ull-rt-seccion-lateral-contenido">
                            <?php echo nl2br(esc_html($tratamiento->cesiones_comunicaciones)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->transferencias_internacionales)): ?>
                    <section class="ull-rt-seccion-lateral">
                        <h3 class="ull-rt-seccion-lateral-titulo">Transferencias internacionales</h3>
                        <div class="ull-rt-seccion-lateral-contenido">
                            <?php echo nl2br(esc_html($tratamiento->transferencias_internacionales)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->plazo_conservacion)): ?>
                    <section class="ull-rt-seccion-lateral">
                        <h3 class="ull-rt-seccion-lateral-titulo">Plazo de conservación</h3>
                        <div class="ull-rt-seccion-lateral-contenido">
                            <?php echo nl2br(esc_html($tratamiento->plazo_conservacion)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($tratamiento->medidas_seguridad)): ?>
                    <section class="ull-rt-seccion-lateral">
                        <h3 class="ull-rt-seccion-lateral-titulo">Medidas de seguridad</h3>
                        <div class="ull-rt-seccion-lateral-contenido">
                            <?php echo nl2br(esc_html($tratamiento->medidas_seguridad)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                </aside>
                
            </div>
            
            <!-- Footer con acciones -->
            <div class="ull-rt-detalle-footer">
                <a href="<?php echo esc_url(remove_query_arg('ver_tratamiento')); ?>" class="ull-rt-btn-secundario">Volver al listado</a>
                <button type="button" class="ull-rt-btn-secundario" onclick="window.print()">Imprimir</button>
            </div>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Estadísticas RGPD
     * Uso: [ull_estadisticas_rgpd]
     * O con enlace específico: [ull_estadisticas_rgpd enlace_tratamientos="/mi-pagina/"]
     */
    public function shortcode_estadisticas($atts) {
        $atts = shortcode_atts(array(
            'enlace_tratamientos' => 'auto', // 'auto' = detectar, URL específica, o 'no' para desactivar
        ), $atts);
        
        ob_start();
        
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $stats = $tratamientos_obj->obtener_estadisticas();
        
        // Determinar si activar enlaces
        $activar_enlaces = false;
        $base_url = '';
        
        if ($atts['enlace_tratamientos'] !== 'no') {
            global $post;
            
            // Si es 'auto' o no se especificó, detectar automáticamente
            if ($atts['enlace_tratamientos'] === 'auto' || empty($atts['enlace_tratamientos'])) {
                // Verificar si en la página actual está el shortcode de listado
                if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ull_listado_tratamientos')) {
                    $activar_enlaces = true;
                    $base_url = get_permalink();
                }
            } 
            // Si se especificó una URL, usarla
            else {
                $activar_enlaces = true;
                $base_url = $atts['enlace_tratamientos'];
            }
        }
        
        ?>
        <div class="ull-rt-estadisticas">
            <div class="ull-rt-estadisticas-header">
                <h2 class="ull-rt-estadisticas-titulo">Estadísticas del Registro de Tratamientos</h2>
                <p class="ull-rt-estadisticas-descripcion">Información general sobre los tratamientos registrados en la Universidad de La Laguna</p>
            </div>
            
            <div class="ull-rt-stats-grid">
                
                <div class="ull-rt-stat-card">
                    <div class="ull-rt-stat-numero"><?php echo $stats['total']; ?></div>
                    <div class="ull-rt-stat-label">Tratamientos registrados</div>
                    <?php if ($activar_enlaces): ?>
                        <a href="<?php echo esc_url($base_url); ?>" class="ull-rt-stat-link">Ver todos</a>
                    <?php endif; ?>
                </div>
                
                <div class="ull-rt-stat-card">
                    <div class="ull-rt-stat-numero"><?php echo $stats['con_transferencias']; ?></div>
                    <div class="ull-rt-stat-label">Con transferencias internacionales</div>
                </div>
                
                <div class="ull-rt-stat-card">
                    <div class="ull-rt-stat-numero"><?php echo $stats['con_datos_sensibles']; ?></div>
                    <div class="ull-rt-stat-label">Con datos sensibles</div>
                </div>
                
            </div>
            
            <?php if (!empty($stats['por_area'])): ?>
            <div class="ull-rt-areas-section">
                <h3 class="ull-rt-areas-titulo">Tratamientos por área</h3>
                <div class="ull-rt-areas-lista">
                    <?php 
                    arsort($stats['por_area']);
                    foreach ($stats['por_area'] as $area => $total): 
                        // Crear URL con filtro de área si los enlaces están activos
                        if ($activar_enlaces) {
                            $area_url = add_query_arg('filtro_area', urlencode($area), $base_url);
                            ?>
                            <a href="<?php echo esc_url($area_url); ?>" class="ull-rt-area-item ull-rt-area-clickable" title="Ver tratamientos de <?php echo esc_attr($area); ?>">
                                <span class="ull-rt-area-nombre"><?php echo esc_html($area); ?></span>
                                <span class="ull-rt-area-badge"><?php echo $total; ?></span>
                            </a>
                        <?php } else { ?>
                            <div class="ull-rt-area-item">
                                <span class="ull-rt-area-nombre"><?php echo esc_html($area); ?></span>
                                <span class="ull-rt-area-badge"><?php echo $total; ?></span>
                            </div>
                        <?php } ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Consultar estado de solicitud
     * Uso: [ull_consultar_solicitud]
     */
    public function shortcode_consultar_solicitud($atts) {
        ob_start();
        ?>
        
        <div class="ull-rt-consultar-solicitud">
            <h3>Consultar Estado de Solicitud</h3>
            <p>Introduzca su número de solicitud y email para consultar el estado:</p>
            
            <form id="ull-rt-form-consultar" class="ull-rt-form">
                <?php wp_nonce_field('ull_rt_public_nonce', 'nonce'); ?>
                
                <div class="ull-rt-form-group">
                    <label for="numero_solicitud">Número de Solicitud *</label>
                    <input type="text" name="numero_solicitud" id="numero_solicitud" required 
                           placeholder="Ej: ED-202601-0001">
                    <small>El número de solicitud fue enviado a su email al presentar la solicitud</small>
                </div>
                
                <div class="ull-rt-form-group">
                    <label for="email">Email *</label>
                    <input type="email" name="email" id="email" required 
                           placeholder="su.email@ejemplo.com">
                </div>
                
                <button type="submit" class="ull-rt-btn-consultar">Consultar Estado</button>
            </form>
            
            <div id="ull-rt-resultado-consulta" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ull-rt-form-consultar').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=ull_consultar_estado_solicitud';
                
                $('#ull-rt-resultado-consulta').hide();
                $('.ull-rt-btn-consultar').prop('disabled', true).text('Consultando...');
                
                $.ajax({
                    url: ullRTPublic.ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            var solicitud = response.data.solicitud;
                            var html = '<div class="ull-rt-resultado-exitoso">';
                            html += '<h4>✅ Solicitud Encontrada</h4>';
                            html += '<table class="ull-rt-tabla-resultado">';
                            html += '<tr><th>Número de Solicitud:</th><td><strong>' + solicitud.numero_solicitud + '</strong></td></tr>';
                            html += '<tr><th>Tipo de Derecho:</th><td>' + solicitud.tipo_derecho_texto + '</td></tr>';
                            html += '<tr><th>Fecha de Solicitud:</th><td>' + solicitud.fecha_solicitud_formateada + '</td></tr>';
                            html += '<tr><th>Estado Actual:</th><td><span class="ull-rt-estado-' + solicitud.estado + '">' + solicitud.estado_texto + '</span></td></tr>';
                            html += '<tr><th>Fecha Límite de Respuesta:</th><td>' + solicitud.fecha_limite_formateada + '</td></tr>';
                            
                            if (solicitud.respuesta) {
                                html += '<tr><th>Respuesta:</th><td>' + solicitud.respuesta + '</td></tr>';
                                html += '<tr><th>Fecha de Respuesta:</th><td>' + solicitud.fecha_respuesta_formateada + '</td></tr>';
                            }
                            
                            html += '</table>';
                            html += '</div>';
                            
                            $('#ull-rt-resultado-consulta').html(html).fadeIn();
                        } else {
                            $('#ull-rt-resultado-consulta')
                                .html('<div class="ull-rt-resultado-error">❌ ' + response.data.message + '</div>')
                                .fadeIn();
                        }
                    },
                    error: function() {
                        $('#ull-rt-resultado-consulta')
                            .html('<div class="ull-rt-resultado-error">❌ Error al consultar. Inténtelo de nuevo.</div>')
                            .fadeIn();
                    },
                    complete: function() {
                        $('.ull-rt-btn-consultar').prop('disabled', false).text('🔍 Consultar Estado');
                    }
                });
            });
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Información del DPD
     * Uso: [ull_informacion_dpd]
     */
    public function shortcode_informacion_dpd($atts) {
        ob_start();
        ?>
        
        <div class="ull-rt-info-dpd">
            
            <div class="ull-rt-dpd-header">
                <h2 class="ull-rt-dpd-titulo">Delegado de Protección de Datos</h2>
                <p class="ull-rt-dpd-subtitulo">Universidad de La Laguna</p>
            </div>
            
            <div class="ull-rt-dpd-contenido">
                
                <div class="ull-rt-dpd-contacto">
                    <h3 class="ull-rt-dpd-seccion-titulo">Datos de contacto</h3>
                    
                    <div class="ull-rt-dpd-grid">
                        <div class="ull-rt-dpd-item">
                            <div class="ull-rt-dpd-label">Email</div>
                            <div class="ull-rt-dpd-valor">
                                <a href="mailto:dpd@ull.es">dpd@ull.es</a>
                            </div>
                        </div>
                        
                        <div class="ull-rt-dpd-item">
                            <div class="ull-rt-dpd-label">Web</div>
                            <div class="ull-rt-dpd-valor">
                                <a href="https://www.ull.es" target="_blank" rel="noopener">www.ull.es</a>
                            </div>
                        </div>
                        
                        <div class="ull-rt-dpd-item">
                            <div class="ull-rt-dpd-label">Dirección</div>
                            <div class="ull-rt-dpd-valor">
                                Calle Padre Herrera s/n<br>
                                38200 - La Laguna, Tenerife
                            </div>
                        </div>
                        
                        <div class="ull-rt-dpd-item">
                            <div class="ull-rt-dpd-label">Teléfono</div>
                            <div class="ull-rt-dpd-valor">
                                922 319 000
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="ull-rt-dpd-funciones">
                    <h3 class="ull-rt-dpd-seccion-titulo">Funciones del Delegado de Protección de Datos</h3>
                    <ul class="ull-rt-dpd-lista">
                        <li>Supervisar el cumplimiento del Reglamento General de Protección de Datos</li>
                        <li>Asesorar sobre evaluaciones de impacto en la protección de datos</li>
                        <li>Gestionar y mantener actualizado el Registro de Actividades de Tratamiento</li>
                        <li>Atender y tramitar las solicitudes de ejercicio de derechos de los interesados</li>
                        <li>Cooperar con la autoridad de control (Agencia Española de Protección de Datos)</li>
                        <li>Actuar como punto de contacto para cuestiones relativas a protección de datos</li>
                    </ul>
                </div>
                
                <div class="ull-rt-dpd-derechos">
                    <h3 class="ull-rt-dpd-seccion-titulo">Sus derechos como interesado</h3>
                    <p class="ull-rt-dpd-derechos-intro">Como titular de datos personales, puede ejercer los siguientes derechos contactando con el Delegado de Protección de Datos:</p>
                    
                    <div class="ull-rt-derechos-grid">
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Acceso</div>
                            <div class="ull-rt-derecho-desc">Obtener información sobre sus datos</div>
                        </div>
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Rectificación</div>
                            <div class="ull-rt-derecho-desc">Corregir datos inexactos</div>
                        </div>
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Supresión</div>
                            <div class="ull-rt-derecho-desc">Solicitar la eliminación</div>
                        </div>
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Oposición</div>
                            <div class="ull-rt-derecho-desc">Oponerse al tratamiento</div>
                        </div>
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Limitación</div>
                            <div class="ull-rt-derecho-desc">Limitar el tratamiento</div>
                        </div>
                        <div class="ull-rt-derecho-item">
                            <div class="ull-rt-derecho-nombre">Portabilidad</div>
                            <div class="ull-rt-derecho-desc">Recibir sus datos en formato estructurado</div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Formulario para enviar consulta al DPD
     * Uso: [ull_consulta_dpd]
     */
    public function shortcode_consulta_dpd($atts) {
        // Procesar envío del formulario
        if (isset($_POST['enviar_consulta_dpd']) && isset($_POST['ull_consulta_dpd_nonce'])) {
            if (!wp_verify_nonce($_POST['ull_consulta_dpd_nonce'], 'ull_enviar_consulta_dpd')) {
                $error_mensaje = 'Error de seguridad. Por favor, recargue la página e inténtelo de nuevo.';
            } else {
                $resultado = $this->procesar_consulta_dpd($_POST);
                
                if (is_wp_error($resultado)) {
                    $error_mensaje = $resultado->get_error_message();
                } else {
                    $numero_consulta = $resultado;
                    $exito_mensaje = true;
                }
            }
        }
        
        ob_start();
        ?>
        
        <div class="ull-rt-form-consulta-dpd">
            
            <?php if (isset($exito_mensaje)): ?>
                <div class="ull-rt-consulta-exito">
                    <div class="ull-rt-exito-icono">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h2 class="ull-rt-exito-titulo">¡Consulta Enviada Correctamente!</h2>
                    
                    <div class="ull-rt-exito-numero">
                        <span class="ull-rt-exito-label">Número de consulta:</span>
                        <code class="ull-rt-numero-consulta"><?php echo esc_html($numero_consulta); ?></code>
                    </div>
                    
                    <div class="ull-rt-exito-mensaje">
                        <p><strong>Su consulta ha sido recibida</strong> y será atendida por nuestro Delegado de Protección de Datos.</p>
                        
                        <div class="ull-rt-exito-detalles">
                            <div class="ull-rt-detalle-item">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <div>
                                    <strong>Plazo de respuesta:</strong>
                                    <span>Máximo 10 días hábiles</span>
                                </div>
                            </div>
                            
                            <div class="ull-rt-detalle-item">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <div>
                                    <strong>Email de confirmación:</strong>
                                    <span>Enviado a su dirección de correo</span>
                                </div>
                            </div>
                            
                            <div class="ull-rt-detalle-item">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                <div>
                                    <strong>Conserve este número:</strong>
                                    <span>Para consultar el estado de su solicitud</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ull-rt-exito-acciones">
                        <button onclick="window.print()" class="ull-rt-btn-secundario">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Imprimir comprobante
                        </button>
                        <a href="<?php echo home_url(); ?>" class="ull-rt-btn-primario">Volver al inicio</a>
                    </div>
                </div>
                
                <style>
                .ull-rt-consulta-exito {
                    max-width: 600px;
                    margin: 40px auto;
                    padding: 40px 30px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    text-align: center;
                }
                
                .ull-rt-exito-icono {
                    margin: 0 auto 20px;
                    width: 80px;
                    height: 80px;
                    color: #00a32a;
                    animation: checkmark 0.5s ease-in-out;
                }
                
                @keyframes checkmark {
                    0% { transform: scale(0); opacity: 0; }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); opacity: 1; }
                }
                
                .ull-rt-exito-titulo {
                    color: #00a32a;
                    font-size: 28px;
                    margin: 0 0 25px 0;
                    font-weight: 600;
                }
                
                .ull-rt-exito-numero {
                    background: #f0f6fc;
                    padding: 20px;
                    border-radius: 6px;
                    margin: 25px 0;
                    border: 2px solid #0073aa;
                }
                
                .ull-rt-exito-label {
                    display: block;
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 8px;
                    font-weight: 500;
                }
                
                .ull-rt-numero-consulta {
                    display: inline-block;
                    font-size: 24px;
                    font-weight: bold;
                    color: #0073aa;
                    font-family: 'Courier New', monospace;
                    letter-spacing: 1px;
                    padding: 8px 16px;
                    background: white;
                    border-radius: 4px;
                    border: 1px solid #0073aa;
                }
                
                .ull-rt-exito-mensaje {
                    text-align: left;
                    margin: 30px 0;
                }
                
                .ull-rt-exito-mensaje > p {
                    font-size: 16px;
                    color: #333;
                    margin-bottom: 25px;
                    text-align: center;
                }
                
                .ull-rt-exito-detalles {
                    background: #f9f9f9;
                    padding: 20px;
                    border-radius: 6px;
                }
                
                .ull-rt-detalle-item {
                    display: flex;
                    align-items: flex-start;
                    gap: 15px;
                    padding: 15px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                .ull-rt-detalle-item:last-child {
                    border-bottom: none;
                }
                
                .ull-rt-detalle-item svg {
                    flex-shrink: 0;
                    color: #0073aa;
                    margin-top: 2px;
                }
                
                .ull-rt-detalle-item > div {
                    flex: 1;
                    text-align: left;
                }
                
                .ull-rt-detalle-item strong {
                    display: block;
                    color: #333;
                    font-size: 14px;
                    margin-bottom: 4px;
                }
                
                .ull-rt-detalle-item span {
                    color: #666;
                    font-size: 14px;
                }
                
                .ull-rt-exito-acciones {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    margin-top: 30px;
                    flex-wrap: wrap;
                }
                
                .ull-rt-btn-primario,
                .ull-rt-btn-secundario {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 12px 24px;
                    border-radius: 4px;
                    font-weight: 600;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                    font-size: 14px;
                }
                
                .ull-rt-btn-primario {
                    background: #0073aa;
                    color: white;
                }
                
                .ull-rt-btn-primario:hover {
                    background: #005a87;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(0,115,170,0.3);
                }
                
                .ull-rt-btn-secundario {
                    background: white;
                    color: #0073aa;
                    border: 2px solid #0073aa;
                }
                
                .ull-rt-btn-secundario:hover {
                    background: #f0f6fc;
                }
                
                @media print {
                    .ull-rt-exito-acciones {
                        display: none;
                    }
                }
                
                @media (max-width: 600px) {
                    .ull-rt-consulta-exito {
                        margin: 20px;
                        padding: 30px 20px;
                    }
                    
                    .ull-rt-exito-titulo {
                        font-size: 22px;
                    }
                    
                    .ull-rt-numero-consulta {
                        font-size: 18px;
                    }
                    
                    .ull-rt-exito-acciones {
                        flex-direction: column;
                    }
                    
                    .ull-rt-btn-primario,
                    .ull-rt-btn-secundario {
                        width: 100%;
                        justify-content: center;
                    }
                }
                </style>
            <?php endif; ?>
            
            <?php if (isset($error_mensaje)): ?>
                <div class="ull-rt-mensaje-error">
                    <h3>Error</h3>
                    <p><?php echo esc_html($error_mensaje); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="ull-rt-consulta-header">
                <h2>Consulta al Delegado de Protección de Datos</h2>
                <p class="ull-rt-consulta-descripcion">
                    Utilice este formulario para realizar consultas sobre protección de datos, tratamiento de información personal, 
                    o cualquier cuestión relacionada con el RGPD en la Universidad de La Laguna.
                </p>
            </div>
            
            <form method="post" class="ull-rt-form" id="formConsultaDPD">
                <?php wp_nonce_field('ull_enviar_consulta_dpd', 'ull_consulta_dpd_nonce'); ?>
                
                <!-- Sección: Datos del consultante -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Datos del consultante</h3>
                    
                    <div class="ull-rt-form-row">
                        <div class="form-group">
                            <label for="consulta_nombre">Nombre completo <span class="required">*</span></label>
                            <input type="text" 
                                   id="consulta_nombre" 
                                   name="consulta_nombre" 
                                   required 
                                   placeholder="Nombre y apellidos">
                        </div>
                        
                        <div class="form-group">
                            <label for="consulta_email">Email <span class="required">*</span></label>
                            <input type="email" 
                                   id="consulta_email" 
                                   name="consulta_email" 
                                   required 
                                   placeholder="email@ejemplo.com">
                            <small>Recibirá la respuesta en este correo</small>
                        </div>
                    </div>
                    
                    <div class="ull-rt-form-row">
                        <div class="form-group">
                            <label for="consulta_telefono">Teléfono (opcional)</label>
                            <input type="tel" 
                                   id="consulta_telefono" 
                                   name="consulta_telefono" 
                                   placeholder="922 XXX XXX">
                        </div>
                        
                        <div class="form-group">
                            <label for="consulta_vinculo">Vinculación con la ULL <span class="required">*</span></label>
                            <select id="consulta_vinculo" name="consulta_vinculo" required>
                                <option value="">-- Seleccione una opción --</option>
                                <option value="Estudiante">Estudiante</option>
                                <option value="PDI">Personal Docente e Investigador</option>
                                <option value="PAS">Personal de Administración y Servicios</option>
                                <option value="Antiguo alumno">Antiguo alumno</option>
                                <option value="Proveedor">Proveedor / Colaborador externo</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección: Detalles de la consulta -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Detalles de la consulta</h3>
                    
                    <div class="form-group">
                        <label for="consulta_asunto">Asunto <span class="required">*</span></label>
                        <input type="text" 
                               id="consulta_asunto" 
                               name="consulta_asunto" 
                               required 
                               maxlength="200"
                               placeholder="Resuma brevemente su consulta">
                    </div>
                    
                    <div class="form-group">
                        <label for="consulta_mensaje">Mensaje <span class="required">*</span></label>
                        <textarea id="consulta_mensaje" 
                                  name="consulta_mensaje" 
                                  required 
                                  rows="8"
                                  placeholder="Describa su consulta con el mayor detalle posible..."></textarea>
                        <small>Mínimo 50 caracteres</small>
                    </div>
                </div>
                
                <!-- Aceptación -->
                <div class="ull-rt-form-seccion">
                    <div class="ull-rt-checkbox-group">
                        <label>
                            <input type="checkbox" required>
                            <span>He leído y acepto que mis datos sean tratados conforme a la <a href="https://www.ull.es/proteccion-datos/" target="_blank">Política de Privacidad de la ULL</a> para responder a mi consulta <span class="required">*</span></span>
                        </label>
                    </div>
                </div>
                
                <!-- Botón de envío -->
                <div class="ull-rt-form-actions">
                    <button type="submit" name="enviar_consulta_dpd" class="ull-rt-btn-enviar">
                        Enviar Consulta
                    </button>
                    <p class="ull-rt-form-nota">
                        Al enviar esta consulta, el Delegado de Protección de Datos revisará su solicitud y le responderá en un plazo máximo de 10 días hábiles.
                    </p>
                </div>
            </form>
            
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Procesar consulta al DPD
     */
    private function procesar_consulta_dpd($data) {
        // Validar campos requeridos
        $campos_requeridos = array(
            'consulta_nombre' => 'Nombre',
            'consulta_email' => 'Email',
            'consulta_vinculo' => 'Vinculación con la ULL',
            'consulta_asunto' => 'Asunto',
            'consulta_mensaje' => 'Mensaje'
        );
        
        foreach ($campos_requeridos as $campo => $label) {
            if (empty($data[$campo])) {
                return new WP_Error('campo_requerido', 'El campo ' . $label . ' es obligatorio.');
            }
        }
        
        // Validar email
        if (!is_email($data['consulta_email'])) {
            return new WP_Error('email_invalido', 'El email proporcionado no es válido.');
        }
        
        // Validar longitud del mensaje
        if (strlen($data['consulta_mensaje']) < 50) {
            return new WP_Error('mensaje_corto', 'El mensaje debe tener al menos 50 caracteres.');
        }
        
        // Generar número de consulta único
        $numero_consulta = 'CONS-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Preparar datos según la estructura de la tabla
        $consulta_data = array(
            'numero_consulta' => $numero_consulta,
            'nombre_solicitante' => sanitize_text_field($data['consulta_nombre']),
            'email_solicitante' => sanitize_email($data['consulta_email']),
            'departamento' => sanitize_text_field($data['consulta_vinculo']),
            'asunto' => sanitize_text_field($data['consulta_asunto']),
            'consulta' => sanitize_textarea_field($data['consulta_mensaje']),
            'estado' => 'pendiente',
            'fecha_consulta' => current_time('mysql'),
            'ip_origen' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'
        );
        
        // Verificar y crear tabla si no existe
        global $wpdb;
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        
        // Comprobar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        
        if (!$table_exists) {
            // Intentar crear la tabla
            $db_obj = ULL_RT_Database::get_instance();
            $db_obj->crear_tablas();
            
            // Verificar de nuevo
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if (!$table_exists) {
                error_log("ULL RT: Tabla $table no existe y no se pudo crear");
                return new WP_Error('error_tabla', 'Error: La tabla de consultas no existe. Active el modo de depuración WP_DEBUG para más información.');
            }
        }
        
        // Intentar insertar con manejo de errores detallado
        $result = $wpdb->insert($table, $consulta_data);
        
        if ($result === false) {
            // Log del error real para debug
            error_log("ULL RT - Error al insertar consulta DPD:");
            error_log("Error: " . $wpdb->last_error);
            error_log("Query: " . $wpdb->last_query);
            error_log("Datos: " . print_r($consulta_data, true));
            
            // Mensaje de error para el usuario
            $error_msg = 'Error al guardar la consulta.';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_msg .= ' Error de BD: ' . $wpdb->last_error;
            }
            
            return new WP_Error('error_db', $error_msg);
        }
        
        $id_consulta = $wpdb->insert_id;
        
        // Registrar en audit log
        ULL_RT_Audit_Log::registrar('crear_consulta_publica', 'consultas_dpd', 
            "Nueva consulta desde formulario público: {$numero_consulta}");
        
        // Enviar notificación por email
        $this->enviar_email_consulta($numero_consulta, $consulta_data);
        
        return $numero_consulta;
    }
    
    /**
     * Enviar emails de notificación para consulta
     */
    private function enviar_email_consulta($numero_consulta, $datos) {
        // Email al usuario
        $to = $datos['email_solicitante'];
        $subject = 'Consulta recibida - ' . $numero_consulta;
        
        $message = "Estimado/a {$datos['nombre_solicitante']},\n\n";
        $message .= "Su consulta al Delegado de Protección de Datos ha sido recibida correctamente.\n\n";
        $message .= "Número de consulta: {$numero_consulta}\n";
        $message .= "Asunto: {$datos['asunto']}\n\n";
        $message .= "Recibirá una respuesta en un plazo máximo de 10 días hábiles a la dirección: {$datos['email_solicitante']}\n\n";
        $message .= "Universidad de La Laguna\n";
        $message .= "Delegado de Protección de Datos\n";
        $message .= "Email: dpd@ull.es\n";
        $message .= "Web: www.ull.es\n";
        
        wp_mail($to, $subject, $message);
        
        // Email al DPD
        $to_dpd = 'dpd@ull.es';
        $subject_dpd = 'Nueva consulta - ' . $numero_consulta;
        
        $message_dpd = "Nueva consulta recibida desde el formulario web.\n\n";
        $message_dpd .= "Número: {$numero_consulta}\n";
        $message_dpd .= "Consultante: {$datos['nombre_solicitante']}\n";
        $message_dpd .= "Email: {$datos['email_solicitante']}\n";
        $message_dpd .= "Vinculación: {$datos['departamento']}\n";
        $message_dpd .= "Asunto: {$datos['asunto']}\n\n";
        $message_dpd .= "Mensaje:\n{$datos['consulta']}\n\n";
        $message_dpd .= "---\n";
        $message_dpd .= "Ver en el panel de administración:\n";
        $message_dpd .= admin_url('admin.php?page=ull-registro-consultas') . "\n";
        
        wp_mail($to_dpd, $subject_dpd, $message_dpd);
    }
    
    /**
     * Shortcode: Formulario de ejercicio de derechos (mejorado)
     */
    public function shortcode_ejercicio_derechos($atts) {
        return ULL_RT_Ejercicio_Derechos::get_instance()->render_formulario_publico($atts);
    }
    
    /**
     * AJAX: Consultar estado de solicitud
     */
    public function ajax_consultar_estado() {
        check_ajax_referer('ull_rt_public_nonce', 'nonce');
        
        $numero_solicitud = sanitize_text_field($_POST['numero_solicitud']);
        $email = sanitize_email($_POST['email']);
        
        if (empty($numero_solicitud) || empty($email)) {
            wp_send_json_error(array('message' => 'Debe proporcionar número de solicitud y email'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE numero_solicitud = %s AND interesado_email = %s",
            $numero_solicitud,
            $email
        ));
        
        if (!$solicitud) {
            wp_send_json_error(array('message' => 'No se encontró ninguna solicitud con esos datos. Verifique el número de solicitud y el email.'));
        }
        
        // Preparar datos para respuesta
        $estados = ULL_RT_Ejercicio_Derechos::get_estados();
        $tipos = ULL_RT_Ejercicio_Derechos::get_tipos_derechos();
        
        $datos_respuesta = array(
            'numero_solicitud' => $solicitud->numero_solicitud,
            'tipo_derecho' => $solicitud->tipo_derecho,
            'tipo_derecho_texto' => isset($tipos[$solicitud->tipo_derecho]) ? $tipos[$solicitud->tipo_derecho] : ucfirst($solicitud->tipo_derecho),
            'estado' => $solicitud->estado,
            'estado_texto' => isset($estados[$solicitud->estado]) ? $estados[$solicitud->estado] : ucfirst($solicitud->estado),
            'fecha_solicitud' => $solicitud->fecha_solicitud,
            'fecha_solicitud_formateada' => date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)),
            'fecha_limite' => $solicitud->fecha_limite,
            'fecha_limite_formateada' => date('d/m/Y', strtotime($solicitud->fecha_limite)),
            'respuesta' => !empty($solicitud->respuesta) ? nl2br(esc_html($solicitud->respuesta)) : null,
            'fecha_respuesta' => $solicitud->fecha_respuesta,
            'fecha_respuesta_formateada' => $solicitud->fecha_respuesta ? date('d/m/Y H:i', strtotime($solicitud->fecha_respuesta)) : null,
        );
        
        wp_send_json_success(array('solicitud' => $datos_respuesta));
    }
    
    /**
     * Shortcode: Formulario para proponer nuevo tratamiento
     * Uso: [ull_proponer_tratamiento]
     */
    public function shortcode_proponer_tratamiento($atts) {
        ob_start();
        
        // Mostrar mensaje de éxito o error
        if (isset($_GET['propuesta_enviada']) && $_GET['propuesta_enviada'] === 'success') {
            ?>
            <div class="ull-rt-mensaje-exito">
                <h3>✅ Propuesta Enviada Correctamente</h3>
                <p><strong>Número de propuesta:</strong> <code><?php echo esc_html($_GET['numero_propuesta']); ?></code></p>
                <p>✉️ Su propuesta ha sido recibida y será revisada por el Delegado de Protección de Datos.</p>
                <p>📧 Recibirá un <strong>email de confirmación</strong> en breve con todos los detalles.</p>
                <p>⏱️ <strong>Plazo de respuesta:</strong> Máximo 10 días hábiles.</p>
                <p>📝 <strong>Próximos pasos:</strong></p>
                <ol style="margin: 10px 0 0 20px;">
                    <li>Recibirá email de confirmación con el número de propuesta</li>
                    <li>El DPD revisará la propuesta en detalle</li>
                    <li>Recibirá email con el informe y la decisión</li>
                </ol>
                <p style="margin-top: 15px; padding: 10px; background: #d1ecf1; border-left: 3px solid #0c5460; border-radius: 3px;">
                    💡 <strong>Consejo:</strong> Guarde el número de propuesta para futuras consultas.
                </p>
            </div>
            <?php
        }
        
        if (isset($_GET['propuesta_error'])) {
            $error_code = sanitize_text_field($_GET['propuesta_error']);
            $mensaje_error = '';
            $detalles_error = '';
            
            switch ($error_code) {
                case 'nonce_invalido':
                    $mensaje_error = 'Error de seguridad. El formulario ha caducado.';
                    $detalles_error = 'Por favor, recargue la página e inténtelo de nuevo.';
                    break;
                    
                case 'campo_requerido':
                    $campo_faltante = isset($_GET['campo_faltante']) ? urldecode($_GET['campo_faltante']) : 'desconocido';
                    $mensaje_error = 'Campo requerido faltante';
                    $detalles_error = "El campo <strong>{$campo_faltante}</strong> es obligatorio. Por favor, complételo e inténtelo de nuevo.";
                    break;
                    
                case 'email_invalido':
                    $mensaje_error = 'Email no válido';
                    $detalles_error = 'El email proporcionado no tiene un formato válido. Por favor, verifíquelo e inténtelo de nuevo.';
                    break;
                    
                case 'error_db':
                    $mensaje_error = 'Error al guardar la propuesta';
                    $detalles_error = 'Ha ocurrido un error técnico al guardar su propuesta. Por favor, inténtelo de nuevo en unos minutos o contacte con el administrador.';
                    break;
                    
                default:
                    if (isset($_GET['mensaje_error'])) {
                        $mensaje_error = urldecode($_GET['mensaje_error']);
                    } else {
                        $mensaje_error = 'Error desconocido';
                        $detalles_error = 'Ha ocurrido un error inesperado. Por favor, inténtelo de nuevo.';
                    }
                    break;
            }
            ?>
            <div class="ull-rt-mensaje-error">
                <h3>❌ <?php echo esc_html($mensaje_error); ?></h3>
                <?php if ($detalles_error): ?>
                    <p><?php echo $detalles_error; // Ya contiene HTML seguro ?></p>
                <?php endif; ?>
                <p style="margin-top: 15px;">
                    <strong>Si el problema persiste:</strong><br>
                    📧 Contacte con el DPD: <a href="mailto:dpd@ull.es">dpd@ull.es</a><br>
                    📞 Teléfono: 922 319 000
                </p>
            </div>
            <?php
        }
        
        ?>
        <div class="ull-rt-form-proponer">
            
            <div class="ull-rt-proponer-header">
                <h2>Proponer Nuevo Tratamiento de Datos</h2>
                <p class="ull-rt-proponer-descripcion">Complete este formulario para proponer un nuevo tratamiento de datos personales que no esté actualmente en el registro de la Universidad de La Laguna.</p>
            </div>
            
            <div class="ull-rt-proponer-info">
                <h3>Información importante</h3>
                <ul>
                    <li>Su propuesta será revisada por el Delegado de Protección de Datos (DPD)</li>
                    <li>Recibirá un informe con la decisión en un plazo máximo de 10 días hábiles</li>
                    <li>Todas las decisiones se notificarán por email</li>
                    <li>Los campos marcados con asterisco (*) son obligatorios</li>
                </ul>
            </div>
            
            <form method="post" class="ull-rt-form-propuesta" id="formPropuesta">
                <?php wp_nonce_field('ull_rt_enviar_propuesta', 'ull_rt_propuesta_nonce'); ?>
                
                <!-- Sección 1: Datos del Tratamiento -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Datos del tratamiento propuesto</h3>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre del tratamiento <span class="required">*</span></label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               required 
                               placeholder="Ej: Gestión de becas de investigación"
                               maxlength="200">
                        <small>Nombre descriptivo del tratamiento</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="area_responsable">Área responsable <span class="required">*</span></label>
                        <select id="area_responsable" name="area_responsable" required>
                            <option value="">-- Seleccione un área --</option>
                            <option value="Rectorado">Rectorado</option>
                            <option value="Vicerrectorado de Investigación">Vicerrectorado de Investigación</option>
                            <option value="Vicerrectorado de Estudiantes">Vicerrectorado de Estudiantes</option>
                            <option value="Vicerrectorado de Doctorado">Vicerrectorado de Doctorado</option>
                            <option value="Secretaría General">Secretaría General</option>
                            <option value="Gerencia">Gerencia</option>
                            <option value="Recursos Humanos">Recursos Humanos</option>
                            <option value="Servicio de Deportes">Servicio de Deportes</option>
                            <option value="Servicio de Biblioteca">Servicio de Biblioteca</option>
                            <option value="Servicios Sociales">Servicios Sociales</option>
                            <option value="Servicio de Informática">Servicio de Informática</option>
                            <option value="Prácticas y Empleo">Prácticas y Empleo</option>
                            <option value="Relaciones Internacionales">Relaciones Internacionales</option>
                            <option value="Comunicación">Comunicación</option>
                            <option value="Otro">Otro (especificar en justificación)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="finalidad">Finalidad del tratamiento <span class="required">*</span></label>
                        <textarea id="finalidad" 
                                  name="finalidad" 
                                  required 
                                  rows="4"
                                  placeholder="Describa la finalidad del tratamiento de datos..."></textarea>
                        <small>¿Para qué se van a tratar los datos?</small>
                    </div>
                </div>
                
                <!-- Sección 2: Base Jurídica -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Base jurídica</h3>
                    
                    <div class="form-group">
                        <label>Seleccione las bases jurídicas que aplican <span class="required">*</span></label>
                        <div class="ull-rt-checkbox-grid">
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="RGPD 6.1.a) Consentimiento del interesado">
                                <span>RGPD 6.1.a) Consentimiento del interesado</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="RGPD 6.1.b) Ejecución de un contrato">
                                <span>RGPD 6.1.b) Ejecución de un contrato</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="RGPD 6.1.c) Obligación legal">
                                <span>RGPD 6.1.c) Obligación legal</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="RGPD 6.1.e) Misión de interés público">
                                <span>RGPD 6.1.e) Misión de interés público</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="LO 2/2023 del Sistema Universitario">
                                <span>LO 2/2023 del Sistema Universitario</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="base_juridica[]" value="Ley 14/2011 de Ciencia, Tecnología e Innovación">
                                <span>Ley 14/2011 de Ciencia, Tecnología e Innovación</span>
                            </label>
                        </div>
                        <small>Puede seleccionar múltiples opciones</small>
                    </div>
                </div>
                
                <!-- Sección 3: Colectivos -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Colectivos de interesados</h3>
                    
                    <div class="form-group">
                        <label>Seleccione los colectivos afectados <span class="required">*</span></label>
                        <div class="ull-rt-checkbox-grid">
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Estudiantes de grado">
                                <span>Estudiantes de grado</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Estudiantes de posgrado">
                                <span>Estudiantes de posgrado</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Estudiantes de doctorado">
                                <span>Estudiantes de doctorado</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Personal Docente e Investigador (PDI)">
                                <span>Personal Docente e Investigador (PDI)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Personal de Administración y Servicios (PAS)">
                                <span>Personal de Administración y Servicios (PAS)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Antiguos alumnos">
                                <span>Antiguos alumnos</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Solicitantes / Candidatos">
                                <span>Solicitantes / Candidatos</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Proveedores / Colaboradores externos">
                                <span>Proveedores / Colaboradores externos</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Familiares / Representantes legales">
                                <span>Familiares / Representantes legales</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="colectivos[]" value="Visitantes / Público general">
                                <span>Visitantes / Público general</span>
                            </label>
                        </div>
                        <small>Puede seleccionar múltiples opciones</small>
                    </div>
                </div>
                
                <!-- Sección 4: Categorías de Datos -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Categorías de datos personales</h3>
                    
                    <div class="form-group">
                        <label>Seleccione las categorías de datos a tratar <span class="required">*</span></label>
                        <div class="ull-rt-checkbox-grid">
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos identificativos (nombre, DNI, dirección, teléfono, email)">
                                <span>Datos identificativos (nombre, DNI, dirección, teléfono, email)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos académicos (expediente, calificaciones, títulos)">
                                <span>Datos académicos (expediente, calificaciones, títulos)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos profesionales (puesto, categoría, CV)">
                                <span>Datos profesionales (puesto, categoría, CV)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos económicos y bancarios">
                                <span>Datos económicos y bancarios</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos de características personales (edad, sexo, nacionalidad)">
                                <span>Datos de características personales (edad, sexo, nacionalidad)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Datos familiares (estado civil, hijos)">
                                <span>Datos familiares (estado civil, hijos)</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Imagen / Fotografía">
                                <span>Imagen / Fotografía</span>
                            </label>
                            <label class="ull-rt-checkbox-item">
                                <input type="checkbox" name="categorias_datos[]" value="Firma">
                                <span>Firma</span>
                            </label>
                        </div>
                        
                        <div class="ull-rt-datos-sensibles">
                            <p class="ull-rt-sensibles-titulo">Datos de categorías especiales (sensibles)</p>
                            <div class="ull-rt-checkbox-grid">
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Salud">
                                    <span><strong>Salud</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Origen étnico o racial">
                                    <span><strong>Origen étnico o racial</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Opiniones políticas">
                                    <span><strong>Opiniones políticas</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Convicciones religiosas o filosóficas">
                                    <span><strong>Convicciones religiosas o filosóficas</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Afiliación sindical">
                                    <span><strong>Afiliación sindical</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Datos biométricos">
                                    <span><strong>Datos biométricos</strong></span>
                                </label>
                                <label class="ull-rt-checkbox-item ull-rt-checkbox-sensible">
                                    <input type="checkbox" name="categorias_datos[]" value="DATOS SENSIBLES: Vida sexual u orientación sexual">
                                    <span><strong>Vida sexual u orientación sexual</strong></span>
                                </label>
                            </div>
                            <small class="ull-rt-sensibles-nota">Los datos sensibles requieren garantías adicionales de protección</small>
                        </div>
                        
                        <small>Puede seleccionar múltiples opciones</small>
                    </div>
                </div>
                
                <!-- Sección 5: Cesiones y Otras Informaciones -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Cesiones y transferencias</h3>
                    
                    <div class="form-group">
                        <label for="cesiones">¿Se realizarán cesiones o comunicaciones de datos a terceros?</label>
                        <select id="cesiones_select" name="cesiones_select" onchange="toggleCesionesDetalle(this.value)">
                            <option value="No previstas">No, no se prevén cesiones</option>
                            <option value="Sí">Sí, se realizarán cesiones</option>
                        </select>
                    </div>
                    
                    <div id="cesiones_detalle" class="form-group" style="display: none;">
                        <label for="cesiones">Especifique las cesiones o comunicaciones</label>
                        <textarea id="cesiones" 
                                  name="cesiones" 
                                  rows="3"
                                  placeholder="Ej: Ministerio de Universidades, entidades financieras, organismos de evaluación..."></textarea>
                        <small>Indique a qué entidades se cederán datos y con qué finalidad</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="transferencias_internacionales">Transferencias internacionales <span class="required">*</span></label>
                        <select id="transferencias_internacionales" name="transferencias_internacionales" required>
                            <option value="No previstas">No, no se prevén transferencias fuera de la UE/EEE</option>
                            <option value="Sí - Con decisión de adecuación">Sí - A países con decisión de adecuación de la UE</option>
                            <option value="Sí - Con cláusulas contractuales tipo">Sí - Con cláusulas contractuales tipo (SCC)</option>
                            <option value="Sí - Otras garantías">Sí - Con otras garantías adecuadas</option>
                        </select>
                        <small>¿Se transferirán datos fuera del Espacio Económico Europeo?</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="plazo_conservacion">Plazo de conservación <span class="required">*</span></label>
                        <select id="plazo_conservacion_select" name="plazo_conservacion_select" required onchange="togglePlazoDetalle(this.value)">
                            <option value="">-- Seleccione una opción --</option>
                            <option value="Durante la relación">Durante la relación con el interesado</option>
                            <option value="Durante + 5 años">Durante la relación + 5 años (responsabilidades derivadas)</option>
                            <option value="Durante + 10 años">Durante la relación + 10 años</option>
                            <option value="Permanente">Permanente (aplicable normativa de archivos)</option>
                            <option value="Otro">Otro plazo (especificar)</option>
                        </select>
                    </div>
                    
                    <div id="plazo_detalle" class="form-group" style="display: none;">
                        <label for="plazo_conservacion">Especifique el plazo de conservación</label>
                        <textarea id="plazo_conservacion" 
                                  name="plazo_conservacion" 
                                  rows="2"
                                  placeholder="Ej: Durante la vigencia del programa + 5 años para responsabilidades derivadas"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medidas_seguridad">Medidas de seguridad <span class="required">*</span></label>
                        <select id="medidas_seguridad" name="medidas_seguridad" required>
                            <option value="">-- Seleccione una opción --</option>
                            <option value="Conforme al Esquema Nacional de Seguridad (ENS)">Conforme al Esquema Nacional de Seguridad (ENS)</option>
                            <option value="Medidas técnicas y organizativas del RGPD">Medidas técnicas y organizativas del RGPD</option>
                            <option value="Medidas específicas (detallar en justificación)">Medidas específicas (detallar en justificación)</option>
                        </select>
                        <small>Medidas técnicas y organizativas de seguridad previstas</small>
                    </div>
                </div>
                
                <!-- Sección 6: Justificación -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Justificación de la propuesta</h3>
                    
                    <div class="form-group">
                        <label for="justificacion">Justificación <span class="required">*</span></label>
                        <textarea id="justificacion" 
                                  name="justificacion" 
                                  required 
                                  rows="5"
                                  placeholder="Explique por qué es necesario este nuevo tratamiento, qué necesidad cubre, marco normativo aplicable, etc."></textarea>
                        <small>Explique las razones que justifican la necesidad de este nuevo tratamiento</small>
                    </div>
                </div>
                
                <!-- Sección 7: Datos del Solicitante -->
                <div class="ull-rt-form-seccion">
                    <h3 class="ull-rt-form-seccion-titulo">Datos del responsable/solicitante</h3>
                    
                    <div class="ull-rt-form-row">
                        <div class="form-group">
                            <label for="responsable_nombre">Nombre completo <span class="required">*</span></label>
                            <input type="text" 
                                   id="responsable_nombre" 
                                   name="responsable_nombre" 
                                   required 
                                   placeholder="Nombre y apellidos">
                        </div>
                        
                        <div class="form-group">
                            <label for="responsable_cargo">Cargo/función <span class="required">*</span></label>
                            <input type="text" 
                                   id="responsable_cargo" 
                                   name="responsable_cargo" 
                                   required 
                                   placeholder="Ej: Coordinador/a del programa">
                        </div>
                    </div>
                    
                    <div class="ull-rt-form-row">
                        <div class="form-group">
                            <label for="responsable_email">Email institucional <span class="required">*</span></label>
                            <input type="email" 
                                   id="responsable_email" 
                                   name="responsable_email" 
                                   required 
                                   placeholder="usuario@ull.edu.es">
                            <small>Se enviará la confirmación y el informe del DPD a este email</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="responsable_telefono">Teléfono de contacto <span class="required">*</span></label>
                            <input type="tel" 
                                   id="responsable_telefono" 
                                   name="responsable_telefono" 
                                   required 
                                   placeholder="922 XXX XXX">
                        </div>
                    </div>
                </div>
                
                <div class="ull-rt-form-group ull-rt-checkbox-group">
                    <label>
                        <input type="checkbox" required>
                        <span>He leído y acepto que mis datos sean tratados conforme a la <a href="https://www.ull.es/proteccion-datos/" target="_blank">Política de Privacidad de la ULL</a> <span class="required">*</span></span>
                    </label>
                </div>
                
                <div class="ull-rt-form-group ull-rt-checkbox-group">
                    <label>
                        <input type="checkbox" required>
                        <span>Confirmo que la información proporcionada es veraz y que tengo autorización para proponer este tratamiento <span class="required">*</span></span>
                    </label>
                </div>
                
                <div class="ull-rt-form-actions">
                    <button type="submit" class="ull-rt-btn-enviar">
                        Enviar Propuesta
                    </button>
                    <p class="ull-rt-form-nota">
                        Al enviar esta propuesta, el Delegado de Protección de Datos revisará la información y emitirá un informe en un plazo máximo de 10 días hábiles.
                    </p>
                </div>
            </form>
        </div>
        
        <script>
        function toggleCesionesDetalle(value) {
            var detalle = document.getElementById('cesiones_detalle');
            if (value === 'Sí') {
                detalle.style.display = 'block';
                document.getElementById('cesiones').required = true;
            } else {
                detalle.style.display = 'none';
                document.getElementById('cesiones').required = false;
            }
        }
        
        function togglePlazoDetalle(value) {
            var detalle = document.getElementById('plazo_detalle');
            if (value === 'Otro') {
                detalle.style.display = 'block';
                document.getElementById('plazo_conservacion').required = true;
            } else {
                detalle.style.display = 'none';
                document.getElementById('plazo_conservacion').required = false;
            }
        }
        </script>
        <?php
        
        return ob_get_clean();
    }
}

// Inicializar
ULL_RT_Shortcodes::get_instance();
