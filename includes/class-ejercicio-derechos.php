<?php
/**
 * Gestión del Ejercicio de Derechos RGPD
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Ejercicio_Derechos {
    
    private static $instance = null;
    
    // Tipos de derechos RGPD
    const DERECHO_ACCESO = 'acceso';
    const DERECHO_RECTIFICACION = 'rectificacion';
    const DERECHO_SUPRESION = 'supresion';
    const DERECHO_OPOSICION = 'oposicion';
    const DERECHO_LIMITACION = 'limitacion';
    const DERECHO_PORTABILIDAD = 'portabilidad';
    const DERECHO_NO_DECISIONES_AUTOMATIZADAS = 'no_decisiones_automatizadas';
    
    // Estados de solicitudes
    const ESTADO_RECIBIDA = 'recibida';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_RESUELTA = 'resuelta';
    const ESTADO_DENEGADA = 'denegada';
    const ESTADO_PARCIAL = 'parcial';
    const ESTADO_CANCELADA = 'cancelada';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Shortcode para formulario público
        add_shortcode('ull_ejercicio_derechos', array($this, 'render_formulario_publico'));
        
        // AJAX para formulario público
        add_action('wp_ajax_nopriv_ull_enviar_solicitud_derecho', array($this, 'procesar_solicitud_publica'));
        add_action('wp_ajax_ull_enviar_solicitud_derecho', array($this, 'procesar_solicitud_publica'));
        
        // Verificar plazos diariamente
        add_action('ull_verificar_plazos_derechos', array($this, 'verificar_plazos'));
        if (!wp_next_scheduled('ull_verificar_plazos_derechos')) {
            wp_schedule_event(time(), 'daily', 'ull_verificar_plazos_derechos');
        }
    }
    
    public function crear_solicitud($datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        // Generar número de solicitud único
        $numero_solicitud = $this->generar_numero_solicitud();
        
        // Calcular fecha límite (1 mes desde la solicitud)
        $fecha_limite = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $defaults = array(
            'numero_solicitud' => $numero_solicitud,
            'estado' => self::ESTADO_RECIBIDA,
            'fecha_limite' => $fecha_limite,
            'responsable_gestion' => get_current_user_id(),
        );
        
        $datos = wp_parse_args($datos, $defaults);
        
        // Validar datos requeridos
        $required = array('tipo_derecho', 'interesado_nombre', 'interesado_email', 'descripcion_solicitud');
        foreach ($required as $field) {
            if (empty($datos[$field])) {
                return new WP_Error('campo_requerido', "El campo $field es requerido");
            }
        }
        
        // Validar email
        if (!is_email($datos['interesado_email'])) {
            return new WP_Error('email_invalido', 'El email proporcionado no es válido');
        }
        
        // Serializar arrays
        if (isset($datos['archivos_adjuntos']) && is_array($datos['archivos_adjuntos'])) {
            $datos['archivos_adjuntos'] = maybe_serialize($datos['archivos_adjuntos']);
        }
        
        $result = $wpdb->insert($table, $datos);
        
        if ($result) {
            $id = $wpdb->insert_id;
            
            // Enviar notificación al interesado
            $this->enviar_notificacion_recibida($id);
            
            // Enviar notificación al DPD
            $this->enviar_notificacion_dpd_nueva_solicitud($id);
            
            // Audit log
            ULL_RT_Audit_Log::registrar('crear_solicitud_derecho', 'ejercicio_derechos', 
                "Nueva solicitud: {$numero_solicitud} - Derecho de {$datos['tipo_derecho']}");
            
            return $id;
        }
        
        return new WP_Error('error_bd', 'Error al crear la solicitud');
    }
    
    public function actualizar_solicitud($id, $datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        // Obtener datos anteriores
        $solicitud_anterior = $this->obtener_solicitud($id);
        
        if (!$solicitud_anterior) {
            return new WP_Error('not_found', 'Solicitud no encontrada');
        }
        
        // Si cambia el estado, actualizar fecha de respuesta
        if (isset($datos['estado']) && 
            in_array($datos['estado'], array(self::ESTADO_RESUELTA, self::ESTADO_DENEGADA, self::ESTADO_PARCIAL)) &&
            $solicitud_anterior->estado != $datos['estado']) {
            $datos['fecha_respuesta'] = current_time('mysql');
        }
        
        // Serializar arrays
        if (isset($datos['archivos_adjuntos']) && is_array($datos['archivos_adjuntos'])) {
            $datos['archivos_adjuntos'] = maybe_serialize($datos['archivos_adjuntos']);
        }
        
        if (isset($datos['notificaciones_enviadas']) && is_array($datos['notificaciones_enviadas'])) {
            $datos['notificaciones_enviadas'] = maybe_serialize($datos['notificaciones_enviadas']);
        }
        
        $result = $wpdb->update(
            $table,
            $datos,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Si se ha resuelto, enviar notificación
            if (isset($datos['estado']) && $datos['estado'] == self::ESTADO_RESUELTA) {
                $this->enviar_notificacion_resuelta($id);
            }
            
            // Audit log
            ULL_RT_Audit_Log::registrar('actualizar_solicitud_derecho', 'ejercicio_derechos', 
                "Actualizada solicitud: {$solicitud_anterior->numero_solicitud}");
            
            return true;
        }
        
        return new WP_Error('error_bd', 'Error al actualizar la solicitud');
    }
    
    public function obtener_solicitud($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        if ($solicitud) {
            // Deserializar campos
            $solicitud->archivos_adjuntos = maybe_unserialize($solicitud->archivos_adjuntos);
            $solicitud->notificaciones_enviadas = maybe_unserialize($solicitud->notificaciones_enviadas);
        }
        
        return $solicitud;
    }
    
    public function listar_solicitudes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'estado' => '',
            'tipo_derecho' => '',
            'orderby' => 'fecha_solicitud',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
            'search' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        $where = array();
        $where_values = array();
        
        if (!empty($args['estado'])) {
            $where[] = "estado = %s";
            $where_values[] = $args['estado'];
        }
        
        if (!empty($args['tipo_derecho'])) {
            $where[] = "tipo_derecho = %s";
            $where_values[] = $args['tipo_derecho'];
        }
        
        if (!empty($args['search'])) {
            $where[] = "(numero_solicitud LIKE %s OR interesado_nombre LIKE %s OR interesado_email LIKE %s OR descripcion_solicitud LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        if (!empty($args['fecha_desde'])) {
            $where[] = "fecha_solicitud >= %s";
            $where_values[] = $args['fecha_desde'];
        }
        
        if (!empty($args['fecha_hasta'])) {
            $where[] = "fecha_solicitud <= %s";
            $where_values[] = $args['fecha_hasta'] . ' 23:59:59';
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
    
    public function obtener_estadisticas() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        $stats = array(
            'total' => 0,
            'por_tipo' => array(),
            'por_estado' => array(),
            'pendientes' => 0,
            'vencidas' => 0,
            'tiempo_promedio_respuesta' => 0,
            'mes_actual' => 0,
        );
        
        // Total de solicitudes
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        // Por tipo de derecho
        $tipos = $wpdb->get_results("
            SELECT tipo_derecho, COUNT(*) as total 
            FROM $table 
            GROUP BY tipo_derecho
        ");
        foreach ($tipos as $tipo) {
            $stats['por_tipo'][$tipo->tipo_derecho] = $tipo->total;
        }
        
        // Por estado
        $estados = $wpdb->get_results("
            SELECT estado, COUNT(*) as total 
            FROM $table 
            GROUP BY estado
        ");
        foreach ($estados as $estado) {
            $stats['por_estado'][$estado->estado] = $estado->total;
        }
        
        // Solicitudes pendientes
        $stats['pendientes'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE estado IN (%s, %s)
        ", self::ESTADO_RECIBIDA, self::ESTADO_EN_PROCESO));
        
        // Solicitudes vencidas (más de 1 mes sin responder)
        $stats['vencidas'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE fecha_limite < %s 
            AND estado IN (%s, %s)
        ", current_time('mysql'), self::ESTADO_RECIBIDA, self::ESTADO_EN_PROCESO));
        
        // Tiempo promedio de respuesta (en días)
        $tiempo_promedio = $wpdb->get_var("
            SELECT AVG(DATEDIFF(fecha_respuesta, fecha_solicitud)) 
            FROM $table 
            WHERE fecha_respuesta IS NOT NULL
        ");
        $stats['tiempo_promedio_respuesta'] = round((float)($tiempo_promedio ?: 0), 1);
        
        // Solicitudes del mes actual
        $stats['mes_actual'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $table 
            WHERE MONTH(fecha_solicitud) = MONTH(CURRENT_DATE())
            AND YEAR(fecha_solicitud) = YEAR(CURRENT_DATE())
        ");
        
        return $stats;
    }
    
    private function generar_numero_solicitud() {
        $year = date('Y');
        $month = date('m');
        
        global $wpdb;
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        // Contar solicitudes del mes
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE numero_solicitud LIKE %s
        ", "ED-{$year}{$month}-%"));
        
        $numero = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return "ED-{$year}{$month}-{$numero}";
    }
    
    public function verificar_plazos() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_ejercicio_derechos';
        
        // Buscar solicitudes próximas a vencer (7 días)
        $proximas_vencer = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE fecha_limite BETWEEN %s AND %s
            AND estado IN (%s, %s)
        ", 
            current_time('mysql'), 
            date('Y-m-d H:i:s', strtotime('+7 days')),
            self::ESTADO_RECIBIDA,
            self::ESTADO_EN_PROCESO
        ));
        
        foreach ($proximas_vencer as $solicitud) {
            $this->enviar_alerta_plazo($solicitud->id);
        }
        
        // Buscar solicitudes vencidas
        $vencidas = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE fecha_limite < %s
            AND estado IN (%s, %s)
        ", 
            current_time('mysql'),
            self::ESTADO_RECIBIDA,
            self::ESTADO_EN_PROCESO
        ));
        
        foreach ($vencidas as $solicitud) {
            $this->enviar_alerta_vencida($solicitud->id);
        }
    }
    
    public function render_formulario_publico($atts) {
        ob_start();
        include ULL_RT_PLUGIN_DIR . 'templates/formulario-ejercicio-derechos.php';
        return ob_get_clean();
    }
    
    public function procesar_solicitud_publica() {
        check_ajax_referer('ull_rt_public_nonce', 'nonce');
        
        $datos = array(
            'tipo_derecho' => sanitize_text_field($_POST['tipo_derecho']),
            'interesado_nombre' => sanitize_text_field($_POST['nombre']),
            'interesado_email' => sanitize_email($_POST['email']),
            'interesado_dni' => sanitize_text_field($_POST['dni']),
            'interesado_telefono' => sanitize_text_field($_POST['telefono']),
            'descripcion_solicitud' => sanitize_textarea_field($_POST['descripcion']),
        );
        
        // Manejar archivos adjuntos si existen
        if (!empty($_FILES['archivos'])) {
            $archivos = $this->procesar_archivos_adjuntos($_FILES['archivos']);
            if (!is_wp_error($archivos)) {
                $datos['archivos_adjuntos'] = $archivos;
            }
        }
        
        $resultado = $this->crear_solicitud($datos);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error(array(
                'message' => $resultado->get_error_message()
            ));
        } else {
            $solicitud = $this->obtener_solicitud($resultado);
            wp_send_json_success(array(
                'message' => 'Solicitud enviada correctamente',
                'numero_solicitud' => $solicitud->numero_solicitud
            ));
        }
    }
    
    private function procesar_archivos_adjuntos($files) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $archivos_subidos = array();
        
        // Soporte para múltiples archivos
        $file_count = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $file_count; $i++) {
            $file = array(
                'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            );
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                return new WP_Error('upload_error', $upload['error']);
            }
            
            $archivos_subidos[] = array(
                'url' => $upload['url'],
                'file' => $upload['file'],
                'type' => $upload['type'],
            );
        }
        
        return $archivos_subidos;
    }
    
    private function enviar_notificacion_recibida($solicitud_id) {
        $solicitud = $this->obtener_solicitud($solicitud_id);
        
        ULL_RT_Email_Notifications::enviar_confirmacion_solicitud_derecho($solicitud);
    }
    
    private function enviar_notificacion_dpd_nueva_solicitud($solicitud_id) {
        $solicitud = $this->obtener_solicitud($solicitud_id);
        
        ULL_RT_Email_Notifications::enviar_notificacion_dpd_solicitud($solicitud);
    }
    
    private function enviar_notificacion_resuelta($solicitud_id) {
        $solicitud = $this->obtener_solicitud($solicitud_id);
        
        ULL_RT_Email_Notifications::enviar_resolucion_solicitud_derecho($solicitud);
    }
    
    private function enviar_alerta_plazo($solicitud_id) {
        $solicitud = $this->obtener_solicitud($solicitud_id);
        
        ULL_RT_Email_Notifications::enviar_alerta_plazo_derecho($solicitud);
    }
    
    private function enviar_alerta_vencida($solicitud_id) {
        $solicitud = $this->obtener_solicitud($solicitud_id);
        
        ULL_RT_Email_Notifications::enviar_alerta_vencida_derecho($solicitud);
    }
    
    public static function get_tipos_derechos() {
        return array(
            self::DERECHO_ACCESO => __('Derecho de Acceso', 'ull-registro-tratamientos'),
            self::DERECHO_RECTIFICACION => __('Derecho de Rectificación', 'ull-registro-tratamientos'),
            self::DERECHO_SUPRESION => __('Derecho de Supresión (Derecho al Olvido)', 'ull-registro-tratamientos'),
            self::DERECHO_OPOSICION => __('Derecho de Oposición', 'ull-registro-tratamientos'),
            self::DERECHO_LIMITACION => __('Derecho a la Limitación del Tratamiento', 'ull-registro-tratamientos'),
            self::DERECHO_PORTABILIDAD => __('Derecho a la Portabilidad', 'ull-registro-tratamientos'),
            self::DERECHO_NO_DECISIONES_AUTOMATIZADAS => __('Derecho a no ser objeto de decisiones automatizadas', 'ull-registro-tratamientos'),
        );
    }
    
    public static function get_estados() {
        return array(
            self::ESTADO_RECIBIDA => __('Recibida', 'ull-registro-tratamientos'),
            self::ESTADO_EN_PROCESO => __('En Proceso', 'ull-registro-tratamientos'),
            self::ESTADO_RESUELTA => __('Resuelta', 'ull-registro-tratamientos'),
            self::ESTADO_DENEGADA => __('Denegada', 'ull-registro-tratamientos'),
            self::ESTADO_PARCIAL => __('Resuelta Parcialmente', 'ull-registro-tratamientos'),
            self::ESTADO_CANCELADA => __('Cancelada', 'ull-registro-tratamientos'),
        );
    }
}
