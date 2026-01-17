<?php
/**
 * Gestión de Informes del DPD
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Informes_DPD {
    
    private static $instance = null;
    
    const TIPO_REGISTRO_COMPLETO = 'registro_completo';
    const TIPO_TRANSFERENCIAS = 'transferencias';
    const TIPO_DATOS_SENSIBLES = 'datos_sensibles';
    const TIPO_BASES_JURIDICAS = 'bases_juridicas';
    const TIPO_PLAZOS = 'plazos_conservacion';
    const TIPO_ESTADISTICAS = 'estadisticas';
    const TIPO_DERECHOS = 'ejercicio_derechos';
    const TIPO_CONSULTAS = 'consultas_dpd';
    const TIPO_PERSONALIZADO = 'personalizado';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function crear_informe($datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_informes_dpd';
        
        $defaults = array(
            'estado' => 'borrador',
            'fecha_informe' => current_time('mysql'),
            'creado_por' => get_current_user_id(),
        );
        
        $datos = wp_parse_args($datos, $defaults);
        
        $result = $wpdb->insert($table, $datos);
        
        if ($result) {
            $id = $wpdb->insert_id;
            
            ULL_RT_Audit_Log::registrar('crear_informe', 'informes_dpd', 
                "Creado informe: {$datos['titulo']}");
            
            return $id;
        }
        
        return false;
    }
    
    public function generar_informe_automatico($tipo, $parametros = array()) {
        switch ($tipo) {
            case self::TIPO_REGISTRO_COMPLETO:
                return $this->generar_registro_completo($parametros);
            
            case self::TIPO_TRANSFERENCIAS:
                return $this->generar_informe_transferencias($parametros);
            
            case self::TIPO_DATOS_SENSIBLES:
                return $this->generar_informe_datos_sensibles($parametros);
            
            case self::TIPO_BASES_JURIDICAS:
                return $this->generar_informe_bases_juridicas($parametros);
            
            case self::TIPO_PLAZOS:
                return $this->generar_informe_plazos($parametros);
            
            case self::TIPO_DERECHOS:
                return $this->generar_informe_derechos($parametros);
            
            case self::TIPO_CONSULTAS:
                return $this->generar_informe_consultas($parametros);
            
            case self::TIPO_ESTADISTICAS:
                return $this->generar_informe_estadisticas($parametros);
            
            default:
                return new WP_Error('tipo_invalido', 'Tipo de informe no válido: ' . $tipo);
        }
    }
    
    private function generar_registro_completo($parametros) {
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $tratamientos = $tratamientos_obj->listar_tratamientos(array('estado' => 'activo'));
        
        $contenido = array(
            'tipo' => self::TIPO_REGISTRO_COMPLETO,
            'fecha_generacion' => current_time('mysql'),
            'total_tratamientos' => count($tratamientos),
            'tratamientos' => $tratamientos,
        );
        
        $titulo = 'Registro Completo de Actividades de Tratamiento - ' . date('d/m/Y');
        
        return $this->crear_informe(array(
            'titulo' => $titulo,
            'tipo_informe' => self::TIPO_REGISTRO_COMPLETO,
            'descripcion' => 'Registro completo de todas las actividades de tratamiento activas',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_transferencias($parametros) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        $tratamientos = $wpdb->get_results("
            SELECT * FROM $table 
            WHERE estado = 'activo' 
            AND transferencias_internacionales NOT LIKE '%No previstas%'
            AND transferencias_internacionales != ''
            ORDER BY nombre ASC
        ");
        
        $contenido = array(
            'tipo' => self::TIPO_TRANSFERENCIAS,
            'fecha_generacion' => current_time('mysql'),
            'total_con_transferencias' => count($tratamientos),
            'tratamientos' => $tratamientos,
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Transferencias Internacionales - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_TRANSFERENCIAS,
            'descripcion' => 'Tratamientos con transferencias internacionales de datos',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_datos_sensibles($parametros) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        $tratamientos = $wpdb->get_results("
            SELECT * FROM $table 
            WHERE estado = 'activo' 
            AND (categorias_datos LIKE '%Salud%' 
                 OR categorias_datos LIKE '%biométric%'
                 OR categorias_datos LIKE '%afiliación sindical%'
                 OR categorias_datos LIKE '%origen racial%'
                 OR categorias_datos LIKE '%religión%')
            ORDER BY nombre ASC
        ");
        
        $contenido = array(
            'tipo' => self::TIPO_DATOS_SENSIBLES,
            'fecha_generacion' => current_time('mysql'),
            'total_con_datos_sensibles' => count($tratamientos),
            'tratamientos' => $tratamientos,
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Categorías Especiales de Datos - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_DATOS_SENSIBLES,
            'descripcion' => 'Tratamientos que involucran categorías especiales de datos personales',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_derechos($parametros) {
        $derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
        
        $fecha_desde = isset($parametros['fecha_desde']) ? $parametros['fecha_desde'] : date('Y-m-d', strtotime('-1 month'));
        $fecha_hasta = isset($parametros['fecha_hasta']) ? $parametros['fecha_hasta'] : date('Y-m-d');
        
        $solicitudes = $derechos_obj->listar_solicitudes(array(
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
        ));
        
        $estadisticas = $derechos_obj->obtener_estadisticas();
        
        $contenido = array(
            'tipo' => self::TIPO_DERECHOS,
            'fecha_generacion' => current_time('mysql'),
            'periodo' => array('desde' => $fecha_desde, 'hasta' => $fecha_hasta),
            'total_solicitudes' => count($solicitudes),
            'solicitudes' => $solicitudes,
            'estadisticas' => $estadisticas,
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Ejercicio de Derechos - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_DERECHOS,
            'descripcion' => "Solicitudes de ejercicio de derechos del $fecha_desde al $fecha_hasta",
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_consultas($parametros) {
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        
        $consultas = $consultas_obj->listar_consultas();
        $estadisticas = $consultas_obj->obtener_estadisticas();
        
        $contenido = array(
            'tipo' => self::TIPO_CONSULTAS,
            'fecha_generacion' => current_time('mysql'),
            'total_consultas' => count($consultas),
            'consultas' => $consultas,
            'estadisticas' => $estadisticas,
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Consultas al DPD - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_CONSULTAS,
            'descripcion' => 'Resumen de consultas recibidas y respondidas',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_bases_juridicas($parametros) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        // Obtener todos los tratamientos activos
        $tratamientos = $wpdb->get_results("
            SELECT * FROM $table 
            WHERE estado = 'activo'
            ORDER BY base_juridica ASC, nombre ASC
        ");
        
        // Agrupar por base jurídica
        $por_base = array();
        foreach ($tratamientos as $t) {
            // Separar las bases jurídicas si hay múltiples
            $bases = explode("\n", $t->base_juridica);
            foreach ($bases as $base) {
                $base = trim($base);
                if (!empty($base)) {
                    if (!isset($por_base[$base])) {
                        $por_base[$base] = array();
                    }
                    $por_base[$base][] = $t;
                }
            }
        }
        
        $contenido = array(
            'tipo' => self::TIPO_BASES_JURIDICAS,
            'fecha_generacion' => current_time('mysql'),
            'total_tratamientos' => count($tratamientos),
            'por_base_juridica' => $por_base,
            'resumen' => array_map('count', $por_base),
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Bases Jurídicas - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_BASES_JURIDICAS,
            'descripcion' => 'Análisis de las bases jurídicas utilizadas en los tratamientos',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_plazos($parametros) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        $tratamientos = $wpdb->get_results("
            SELECT * FROM $table 
            WHERE estado = 'activo'
            ORDER BY plazo_conservacion ASC, nombre ASC
        ");
        
        // Agrupar por tipo de plazo
        $por_plazo = array();
        foreach ($tratamientos as $t) {
            $plazo = !empty($t->plazo_conservacion) ? $t->plazo_conservacion : 'No especificado';
            
            if (!isset($por_plazo[$plazo])) {
                $por_plazo[$plazo] = array();
            }
            $por_plazo[$plazo][] = $t;
        }
        
        $contenido = array(
            'tipo' => self::TIPO_PLAZOS,
            'fecha_generacion' => current_time('mysql'),
            'total_tratamientos' => count($tratamientos),
            'por_plazo' => $por_plazo,
            'resumen' => array_map('count', $por_plazo),
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe de Plazos de Conservación - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_PLAZOS,
            'descripcion' => 'Análisis de los plazos de conservación de datos personales',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    private function generar_informe_estadisticas($parametros) {
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        
        $contenido = array(
            'tipo' => self::TIPO_ESTADISTICAS,
            'fecha_generacion' => current_time('mysql'),
            'tratamientos' => $tratamientos_obj->obtener_estadisticas(),
            'derechos' => $derechos_obj->obtener_estadisticas(),
            'consultas' => $consultas_obj->obtener_estadisticas(),
        );
        
        return $this->crear_informe(array(
            'titulo' => 'Informe Estadístico General - ' . date('d/m/Y'),
            'tipo_informe' => self::TIPO_ESTADISTICAS,
            'descripcion' => 'Estadísticas generales del sistema de protección de datos',
            'contenido' => maybe_serialize($contenido),
            'estado' => 'publicado',
        ));
    }
    
    public function generar_pdf($informe_id) {
        $informe = $this->obtener_informe($informe_id);
        
        if (!$informe) {
            return new WP_Error('not_found', 'Informe no encontrado');
        }
        
        return ULL_RT_PDF_Generator::generar_informe($informe);
    }
    
    public function obtener_informe($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_informes_dpd';
        
        $informe = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        if ($informe && !empty($informe->contenido)) {
            $informe->contenido = maybe_unserialize($informe->contenido);
        }
        
        return $informe;
    }
    
    public function listar_informes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'tipo_informe' => '',
            'estado' => '',
            'orderby' => 'fecha_informe',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ull_informes_dpd';
        $where = array();
        $where_values = array();
        
        if (!empty($args['tipo_informe'])) {
            $where[] = "tipo_informe = %s";
            $where_values[] = $args['tipo_informe'];
        }
        
        if (!empty($args['estado'])) {
            $where[] = "estado = %s";
            $where_values[] = $args['estado'];
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']) : '';
        
        $query = "SELECT * FROM $table $where_clause ORDER BY $orderby $limit_clause";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    public static function get_tipos_informes() {
        return array(
            self::TIPO_REGISTRO_COMPLETO => __('Registro Completo de Tratamientos', 'ull-registro-tratamientos'),
            self::TIPO_TRANSFERENCIAS => __('Transferencias Internacionales', 'ull-registro-tratamientos'),
            self::TIPO_DATOS_SENSIBLES => __('Categorías Especiales de Datos', 'ull-registro-tratamientos'),
            self::TIPO_BASES_JURIDICAS => __('Bases Jurídicas', 'ull-registro-tratamientos'),
            self::TIPO_PLAZOS => __('Plazos de Conservación', 'ull-registro-tratamientos'),
            self::TIPO_DERECHOS => __('Ejercicio de Derechos', 'ull-registro-tratamientos'),
            self::TIPO_CONSULTAS => __('Consultas al DPD', 'ull-registro-tratamientos'),
            self::TIPO_ESTADISTICAS => __('Estadísticas Generales', 'ull-registro-tratamientos'),
            self::TIPO_PERSONALIZADO => __('Informe Personalizado', 'ull-registro-tratamientos'),
        );
    }
    
    /**
     * Exportar informe a PDF
     */
    public function exportar_informe_pdf($informe_id) {
        $informe = $this->obtener_informe($informe_id);
        
        if (!$informe) {
            wp_die('Informe no encontrado');
        }
        
        // Deserializar el contenido
        $contenido = maybe_unserialize($informe->contenido);
        
        // Formatear el contenido según el tipo
        $contenido_formateado = $this->formatear_contenido_informe($contenido, $informe->tipo_informe);
        
        // Preparar datos para el PDF
        $datos_pdf = array(
            'titulo' => $informe->titulo,
            'tipo' => $informe->tipo_informe,
            'fecha' => date('d/m/Y H:i', strtotime($informe->fecha_informe)),
            'descripcion' => $informe->descripcion,
            'contenido' => $contenido_formateado,
        );
        
        // Generar HTML
        $html = $this->generar_html_informe($datos_pdf);
        
        // Nombre del archivo
        $filename = 'informe-dpd-' . $informe->id . '-' . date('Y-m-d');
        
        // Enviar como HTML imprimible (método simple sin dependencias)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '.html"');
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($informe->titulo); ?></title>
            <style>
                @media print {
                    @page {
                        margin: 2cm;
                    }
                    .no-print {
                        display: none !important;
                    }
                }
                body {
                    font-family: 'Arial', 'Helvetica', sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                    color: #333;
                }
                .print-actions {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    background: #0073aa;
                    padding: 15px;
                    border-radius: 4px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                    z-index: 1000;
                }
                .print-actions button {
                    background: white;
                    color: #0073aa;
                    border: none;
                    padding: 10px 20px;
                    margin: 0 5px;
                    border-radius: 3px;
                    cursor: pointer;
                    font-weight: 600;
                }
                .print-actions button:hover {
                    background: #f0f0f0;
                }
            </style>
            <script>
                function imprimirDocumento() {
                    window.print();
                }
                function cerrarVentana() {
                    window.close();
                }
            </script>
        </head>
        <body>
            <div class="print-actions no-print">
                <button onclick="imprimirDocumento()">Imprimir / Guardar como PDF</button>
                <button onclick="cerrarVentana()">Cerrar</button>
            </div>
            
            <?php echo $html; ?>
        </body>
        </html>
        <?php
        
        // Registrar en audit log
        ULL_RT_Audit_Log::registrar('exportar_informe_pdf', 'informes_dpd', 
            "Exportado informe #{$informe->id} a PDF");
        
        exit;
    }
    
    /**
     * Formatear contenido del informe según su tipo
     */
    private function formatear_contenido_informe($contenido, $tipo) {
        if (!is_array($contenido)) {
            return $contenido;
        }
        
        $html = '';
        
        switch ($tipo) {
            case self::TIPO_REGISTRO_COMPLETO:
                $html .= '<p><strong>Total de tratamientos activos:</strong> ' . count($contenido['tratamientos']) . '</p>';
                $html .= '<hr>';
                
                foreach ($contenido['tratamientos'] as $index => $tratamiento) {
                    $html .= '<div style="margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-left: 3px solid #0073aa;">';
                    $html .= '<h3 style="margin-top: 0; color: #0073aa;">' . ($index + 1) . '. ' . esc_html($tratamiento->nombre) . '</h3>';
                    
                    if (!empty($tratamiento->area_responsable)) {
                        $html .= '<p><strong>Área Responsable:</strong> ' . esc_html($tratamiento->area_responsable) . '</p>';
                    }
                    
                    if (!empty($tratamiento->finalidad)) {
                        $html .= '<p><strong>Finalidad:</strong> ' . nl2br(esc_html($tratamiento->finalidad)) . '</p>';
                    }
                    
                    if (!empty($tratamiento->base_juridica)) {
                        $html .= '<p><strong>Base Jurídica:</strong> ' . nl2br(esc_html($tratamiento->base_juridica)) . '</p>';
                    }
                    
                    if (!empty($tratamiento->colectivos_interesados)) {
                        $html .= '<p><strong>Colectivos:</strong> ' . nl2br(esc_html($tratamiento->colectivos_interesados)) . '</p>';
                    }
                    
                    if (!empty($tratamiento->categorias_datos)) {
                        $html .= '<p><strong>Categorías de Datos:</strong> ' . nl2br(esc_html($tratamiento->categorias_datos)) . '</p>';
                    }
                    
                    $html .= '</div>';
                }
                break;
                
            case self::TIPO_TRANSFERENCIAS:
                $html .= '<p><strong>Total de tratamientos con transferencias internacionales:</strong> ' . count($contenido['tratamientos']) . '</p>';
                $html .= '<hr>';
                
                foreach ($contenido['tratamientos'] as $index => $tratamiento) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9;">';
                    $html .= '<h3 style="margin-top: 0;">' . ($index + 1) . '. ' . esc_html($tratamiento->nombre) . '</h3>';
                    $html .= '<p><strong>Área:</strong> ' . esc_html($tratamiento->area_responsable) . '</p>';
                    $html .= '<p><strong>Transferencias:</strong> ' . nl2br(esc_html($tratamiento->transferencias_internacionales)) . '</p>';
                    $html .= '</div>';
                }
                break;
                
            case self::TIPO_DATOS_SENSIBLES:
                $html .= '<p><strong>Total de tratamientos con datos sensibles:</strong> ' . count($contenido['tratamientos']) . '</p>';
                $html .= '<hr>';
                
                foreach ($contenido['tratamientos'] as $index => $tratamiento) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 3px solid #ffc107;">';
                    $html .= '<h3 style="margin-top: 0;">' . ($index + 1) . '. ' . esc_html($tratamiento->nombre) . '</h3>';
                    $html .= '<p><strong>Área:</strong> ' . esc_html($tratamiento->area_responsable) . '</p>';
                    $html .= '<p><strong>Categorías de Datos:</strong> ' . nl2br(esc_html($tratamiento->categorias_datos)) . '</p>';
                    $html .= '</div>';
                }
                break;
                
            case self::TIPO_ESTADISTICAS:
                if (isset($contenido['estadisticas'])) {
                    $stats = $contenido['estadisticas'];
                    $html .= '<div style="margin-bottom: 20px;">';
                    $html .= '<h3>Resumen General</h3>';
                    $html .= '<ul>';
                    $html .= '<li><strong>Total de tratamientos:</strong> ' . $stats['total'] . '</li>';
                    $html .= '<li><strong>Con transferencias internacionales:</strong> ' . $stats['con_transferencias'] . '</li>';
                    $html .= '<li><strong>Con datos sensibles:</strong> ' . $stats['con_datos_sensibles'] . '</li>';
                    $html .= '</ul>';
                    $html .= '</div>';
                    
                    if (isset($contenido['por_area']) && !empty($contenido['por_area'])) {
                        $html .= '<h3>Distribución por Área</h3>';
                        $html .= '<table style="width: 100%; border-collapse: collapse;">';
                        $html .= '<tr style="background: #0073aa; color: white;"><th style="padding: 10px; text-align: left;">Área</th><th style="padding: 10px; text-align: center;">Tratamientos</th></tr>';
                        
                        foreach ($contenido['por_area'] as $area => $total) {
                            $html .= '<tr style="border-bottom: 1px solid #ddd;">';
                            $html .= '<td style="padding: 8px;">' . esc_html($area) . '</td>';
                            $html .= '<td style="padding: 8px; text-align: center;">' . $total . '</td>';
                            $html .= '</tr>';
                        }
                        
                        $html .= '</table>';
                    }
                }
                break;
                
            case self::TIPO_BASES_JURIDICAS:
                $html .= '<p><strong>Total de tratamientos analizados:</strong> ' . (isset($contenido['total_tratamientos']) ? $contenido['total_tratamientos'] : 0) . '</p>';
                
                if (isset($contenido['resumen']) && !empty($contenido['resumen'])) {
                    $html .= '<h3>Resumen por Base Jurídica</h3>';
                    $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
                    $html .= '<tr style="background: #0073aa; color: white;"><th style="padding: 10px; text-align: left;">Base Jurídica</th><th style="padding: 10px; text-align: center;">Tratamientos</th></tr>';
                    
                    arsort($contenido['resumen']);
                    foreach ($contenido['resumen'] as $base => $cantidad) {
                        $html .= '<tr style="border-bottom: 1px solid #ddd;">';
                        $html .= '<td style="padding: 8px;">' . esc_html($base) . '</td>';
                        $html .= '<td style="padding: 8px; text-align: center;">' . $cantidad . '</td>';
                        $html .= '</tr>';
                    }
                    
                    $html .= '</table>';
                }
                
                if (isset($contenido['por_base_juridica']) && !empty($contenido['por_base_juridica'])) {
                    $html .= '<h3>Detalle por Base Jurídica</h3>';
                    
                    foreach ($contenido['por_base_juridica'] as $base => $tratamientos) {
                        $html .= '<h4 style="margin-top: 30px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 5px;">' . esc_html($base) . ' (' . count($tratamientos) . ' tratamientos)</h4>';
                        $html .= '<ul style="margin: 10px 0;">';
                        
                        foreach ($tratamientos as $t) {
                            $html .= '<li>' . esc_html($t->nombre);
                            if (!empty($t->area_responsable)) {
                                $html .= ' <em>(' . esc_html($t->area_responsable) . ')</em>';
                            }
                            $html .= '</li>';
                        }
                        
                        $html .= '</ul>';
                    }
                }
                break;
                
            case self::TIPO_PLAZOS:
                $html .= '<p><strong>Total de tratamientos analizados:</strong> ' . (isset($contenido['total_tratamientos']) ? $contenido['total_tratamientos'] : 0) . '</p>';
                
                if (isset($contenido['resumen']) && !empty($contenido['resumen'])) {
                    $html .= '<h3>Resumen por Plazo de Conservación</h3>';
                    $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
                    $html .= '<tr style="background: #0073aa; color: white;"><th style="padding: 10px; text-align: left;">Plazo</th><th style="padding: 10px; text-align: center;">Tratamientos</th></tr>';
                    
                    arsort($contenido['resumen']);
                    foreach ($contenido['resumen'] as $plazo => $cantidad) {
                        $html .= '<tr style="border-bottom: 1px solid #ddd;">';
                        $html .= '<td style="padding: 8px;">' . esc_html($plazo) . '</td>';
                        $html .= '<td style="padding: 8px; text-align: center;">' . $cantidad . '</td>';
                        $html .= '</tr>';
                    }
                    
                    $html .= '</table>';
                }
                
                if (isset($contenido['por_plazo']) && !empty($contenido['por_plazo'])) {
                    $html .= '<h3>Detalle por Plazo</h3>';
                    
                    foreach ($contenido['por_plazo'] as $plazo => $tratamientos) {
                        $html .= '<h4 style="margin-top: 30px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 5px;">' . esc_html($plazo) . ' (' . count($tratamientos) . ' tratamientos)</h4>';
                        $html .= '<ul style="margin: 10px 0;">';
                        
                        foreach ($tratamientos as $t) {
                            $html .= '<li>' . esc_html($t->nombre);
                            if (!empty($t->area_responsable)) {
                                $html .= ' <em>(' . esc_html($t->area_responsable) . ')</em>';
                            }
                            $html .= '</li>';
                        }
                        
                        $html .= '</ul>';
                    }
                }
                break;
                
            case self::TIPO_DERECHOS:
                if (isset($contenido['periodo'])) {
                    $html .= '<p><strong>Período:</strong> Del ' . esc_html($contenido['periodo']['desde']) . ' al ' . esc_html($contenido['periodo']['hasta']) . '</p>';
                }
                $html .= '<p><strong>Total de solicitudes:</strong> ' . (isset($contenido['total_solicitudes']) ? $contenido['total_solicitudes'] : 0) . '</p>';
                
                if (isset($contenido['estadisticas']) && !empty($contenido['estadisticas'])) {
                    $stats = $contenido['estadisticas'];
                    
                    $html .= '<h3>Estadísticas</h3>';
                    $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
                    $html .= '<tr style="background: #0073aa; color: white;"><th style="padding: 10px; text-align: left;">Métrica</th><th style="padding: 10px; text-align: center;">Valor</th></tr>';
                    
                    if (isset($stats['por_estado'])) {
                        foreach ($stats['por_estado'] as $estado => $cantidad) {
                            $html .= '<tr style="border-bottom: 1px solid #ddd;">';
                            $html .= '<td style="padding: 8px;">' . ucfirst(esc_html($estado)) . '</td>';
                            $html .= '<td style="padding: 8px; text-align: center;">' . $cantidad . '</td>';
                            $html .= '</tr>';
                        }
                    }
                    
                    $html .= '</table>';
                }
                break;
                
            case self::TIPO_CONSULTAS:
                $html .= '<p><strong>Total de consultas:</strong> ' . (isset($contenido['total_consultas']) ? $contenido['total_consultas'] : 0) . '</p>';
                
                if (isset($contenido['estadisticas']) && !empty($contenido['estadisticas'])) {
                    $stats = $contenido['estadisticas'];
                    
                    $html .= '<h3>Estadísticas</h3>';
                    $html .= '<ul>';
                    
                    if (isset($stats['total'])) {
                        $html .= '<li><strong>Total:</strong> ' . $stats['total'] . '</li>';
                    }
                    if (isset($stats['pendientes'])) {
                        $html .= '<li><strong>Pendientes:</strong> ' . $stats['pendientes'] . '</li>';
                    }
                    if (isset($stats['respondidas'])) {
                        $html .= '<li><strong>Respondidas:</strong> ' . $stats['respondidas'] . '</li>';
                    }
                    
                    $html .= '</ul>';
                }
                break;
            
            default:
                // Para otros tipos, intentar formatear como texto
                if (isset($contenido['tratamientos'])) {
                    $html .= '<p><strong>Total de registros:</strong> ' . count($contenido['tratamientos']) . '</p>';
                }
                $html .= '<pre>' . print_r($contenido, true) . '</pre>';
                break;
        }
        
        return $html;
    }
    
    /**
     * Generar HTML para el informe PDF
     */
    private function generar_html_informe($datos) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'DejaVu Sans', sans-serif;
                    font-size: 11pt;
                    line-height: 1.6;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #0073aa;
                }
                .header h1 {
                    color: #0073aa;
                    font-size: 20pt;
                    margin: 0 0 10px 0;
                }
                .header .subtitle {
                    font-size: 12pt;
                    color: #666;
                }
                .metadata {
                    background: #f5f5f5;
                    padding: 15px;
                    margin-bottom: 25px;
                    border-left: 4px solid #0073aa;
                }
                .metadata p {
                    margin: 5px 0;
                }
                .metadata strong {
                    color: #0073aa;
                }
                .section {
                    margin-bottom: 25px;
                }
                .section h2 {
                    color: #0073aa;
                    font-size: 14pt;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                    margin-bottom: 15px;
                }
                .content {
                    text-align: justify;
                    white-space: pre-wrap;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 9pt;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>UNIVERSIDAD DE LA LAGUNA</h1>
                <div class="subtitle">Delegado de Protección de Datos</div>
            </div>
            
            <div class="metadata">
                <p><strong>Título:</strong> <?php echo esc_html($datos['titulo']); ?></p>
                <p><strong>Tipo de Informe:</strong> <?php echo esc_html($datos['tipo']); ?></p>
                <p><strong>Fecha de Generación:</strong> <?php echo esc_html($datos['fecha']); ?></p>
            </div>
            
            <?php if (!empty($datos['descripcion'])): ?>
            <div class="section">
                <h2>Descripción</h2>
                <div class="content"><?php echo nl2br(esc_html($datos['descripcion'])); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="section">
                <h2>Contenido del Informe</h2>
                <?php 
                // El contenido viene ya formateado como HTML desde formatear_contenido_informe
                // NO aplicar esc_html ni nl2br porque destruye el formato
                echo $datos['contenido']; 
                ?>
            </div>
            
            <div class="footer">
                <p>Universidad de La Laguna - Delegado de Protección de Datos</p>
                <p>Calle Padre Herrera s/n, 38200 - La Laguna, Tenerife</p>
                <p>Email: dpd@ull.es | Web: www.ull.es</p>
                <p>Documento generado el <?php echo date('d/m/Y \a \l\a\s H:i'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
