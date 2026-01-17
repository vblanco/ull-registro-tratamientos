<?php
/**
 * Gestión de Consultas al DPD
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Consultas_DPD {
    
    private static $instance = null;
    
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';
    
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_RESPONDIDA = 'respondida';
    const ESTADO_CERRADA = 'cerrada';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function crear_consulta($datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        
        $defaults = array(
            'estado' => self::ESTADO_PENDIENTE,
            'prioridad' => self::PRIORIDAD_NORMAL,
            'privada' => 0,
        );
        
        $datos = wp_parse_args($datos, $defaults);
        
        // Validar campos requeridos
        if (empty($datos['asunto']) || empty($datos['consulta'])) {
            return new WP_Error('campos_requeridos', 'Asunto y consulta son requeridos');
        }
        
        $result = $wpdb->insert($table, $datos);
        
        if ($result) {
            $id = $wpdb->insert_id;
            
            // Enviar notificación al DPD
            ULL_RT_Email_Notifications::enviar_notificacion_dpd_consulta($id);
            
            // Audit log
            ULL_RT_Audit_Log::registrar('crear_consulta', 'consultas_dpd', 
                "Nueva consulta: {$datos['asunto']}");
            
            return $id;
        }
        
        return new WP_Error('error_bd', 'Error al crear la consulta');
    }
    
    public function responder_consulta($id, $respuesta, $respuesta_pdf = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        
        // Validar que hay al menos una respuesta (texto o PDF)
        if (empty($respuesta) && empty($respuesta_pdf)) {
            return new WP_Error('respuesta_vacia', 'Debe proporcionar la respuesta en texto, PDF, o ambos');
        }
        
        $consulta = $this->obtener_consulta($id);
        
        if (!$consulta) {
            return new WP_Error('not_found', 'Consulta no encontrada');
        }
        
        // Procesar PDF si se proporcionó
        $pdf_filename = null;
        if ($respuesta_pdf && isset($respuesta_pdf['tmp_name']) && !empty($respuesta_pdf['tmp_name'])) {
            $upload_result = $this->subir_respuesta_pdf($respuesta_pdf, $consulta->numero_consulta);
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
            $pdf_filename = $upload_result['filename'];
        }
        
        $tiempo_respuesta = strtotime('now') - strtotime($consulta->fecha_consulta);
        $tiempo_respuesta_minutos = round($tiempo_respuesta / 60);
        
        $datos_update = array(
            'respuesta' => !empty($respuesta) ? $respuesta : '',
            'estado' => 'respondida',
            'fecha_respuesta' => current_time('mysql'),
            'respondido_por' => get_current_user_id()
        );
        
        if ($pdf_filename) {
            $datos_update['respuesta_pdf'] = $pdf_filename;
        }
        
        $result = $wpdb->update(
            $table,
            $datos_update,
            array('id' => $id)
        );
        
        if ($result !== false) {
            // Enviar email al consultante con PDF si existe
            $this->notificar_respuesta_consulta($consulta, $respuesta, $pdf_filename);
            
            // Audit log
            ULL_RT_Audit_Log::registrar(
                'responder_consulta', 
                'consultas_dpd',
                $id,
                "Respondida consulta: {$consulta->numero_consulta}",
                array('tiene_pdf' => !empty($pdf_filename))
            );
            
            return array('success' => true, 'pdf_filename' => $pdf_filename);
        }
        
        return new WP_Error('error_bd', 'Error al guardar la respuesta');
    }
    
    /**
     * Subir PDF de respuesta
     */
    private function subir_respuesta_pdf($archivo, $numero_consulta) {
        // Verificar que es un PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type !== 'application/pdf') {
            return new WP_Error('tipo_archivo_invalido', 'El archivo debe ser un PDF');
        }
        
        // Verificar tamaño (máximo 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            return new WP_Error('archivo_grande', 'El archivo no debe superar 5MB');
        }
        
        // Crear directorio si no existe
        $upload_dir = wp_upload_dir();
        $consultas_dir = $upload_dir['basedir'] . '/ull-consultas-dpd';
        
        if (!file_exists($consultas_dir)) {
            wp_mkdir_p($consultas_dir);
            file_put_contents($consultas_dir . '/.htaccess', 'Options -Indexes');
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $filename = 'respuesta-' . $numero_consulta . '-' . time() . '.' . $extension;
        $filepath = $consultas_dir . '/' . $filename;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $filepath)) {
            return new WP_Error('error_subida', 'Error al subir el archivo');
        }
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $upload_dir['baseurl'] . '/ull-consultas-dpd/' . $filename
        );
    }
    
    /**
     * Notificar respuesta al consultante
     */
    private function notificar_respuesta_consulta($consulta, $respuesta, $pdf_filename = null) {
        $asunto = "[ULL RGPD] Respuesta a su consulta - {$consulta->numero_consulta}";
        
        $mensaje = "Estimado/a {$consulta->nombre_solicitante},\n\n";
        $mensaje .= "El Delegado de Protección de Datos ha emitido respuesta a su consulta.\n\n";
        $mensaje .= "DATOS DE SU CONSULTA:\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "Número: {$consulta->numero_consulta}\n";
        $mensaje .= "Asunto: {$consulta->asunto}\n";
        $mensaje .= "Fecha consulta: " . date('d/m/Y', strtotime($consulta->fecha_consulta)) . "\n";
        $mensaje .= "Fecha respuesta: " . date('d/m/Y') . "\n\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        if (!empty($respuesta)) {
            $mensaje .= "RESPUESTA DEL DPD:\n\n";
            $mensaje .= $respuesta . "\n\n";
            $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        if ($pdf_filename) {
            $mensaje .= "📎 Se adjunta la respuesta oficial del DPD en formato PDF.\n\n";
            $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        $mensaje .= "Si necesita más información o tiene dudas adicionales, puede contactar con:\n";
        $mensaje .= "Delegado de Protección de Datos\n";
        $mensaje .= "Email: dpd@ull.es\n";
        $mensaje .= "Tel: 922 319 000\n\n";
        $mensaje .= "Atentamente,\n";
        $mensaje .= "Universidad de La Laguna\n\n";
        $mensaje .= "Este es un mensaje automático. Por favor, no responda a este correo.\n";
        
        // Preparar adjuntos si hay PDF
        $attachments = array();
        if ($pdf_filename) {
            $upload_dir = wp_upload_dir();
            $pdf_path = $upload_dir['basedir'] . '/ull-consultas-dpd/' . $pdf_filename;
            if (file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }
        
        // Enviar email
        if (!empty($attachments)) {
            wp_mail($consulta->email_solicitante, $asunto, $mensaje, '', $attachments);
        } else {
            wp_mail($consulta->email_solicitante, $asunto, $mensaje);
        }
    }
    
    public function obtener_consulta($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        
        $consulta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        if ($consulta && !empty($consulta->archivos_adjuntos)) {
            $consulta->archivos_adjuntos = maybe_unserialize($consulta->archivos_adjuntos);
        }
        
        return $consulta;
    }
    
    public function listar_consultas($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'estado' => '',
            'prioridad' => '',
            'buscar' => '',
            'orden' => 'fecha_desc',
            'limit' => -1,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        $where = array("1=1"); // Base para simplificar concatenación
        $where_values = array();
        
        // Filtro por estado
        if (!empty($args['estado'])) {
            $where[] = "estado = %s";
            $where_values[] = $args['estado'];
        }
        
        // Filtro por prioridad
        if (!empty($args['prioridad'])) {
            $where[] = "prioridad = %s";
            $where_values[] = $args['prioridad'];
        }
        
        // Búsqueda
        if (!empty($args['buscar'])) {
            $buscar = '%' . $wpdb->esc_like($args['buscar']) . '%';
            $where[] = "(numero_consulta LIKE %s OR asunto LIKE %s OR nombre_solicitante LIKE %s OR email_solicitante LIKE %s)";
            $where_values[] = $buscar;
            $where_values[] = $buscar;
            $where_values[] = $buscar;
            $where_values[] = $buscar;
        }
        
        // Ordenación
        $order_sql = "fecha_consulta DESC"; // Por defecto
        
        switch ($args['orden']) {
            case 'fecha_asc':
                $order_sql = "fecha_consulta ASC";
                break;
            case 'fecha_desc':
                $order_sql = "fecha_consulta DESC";
                break;
            case 'prioridad':
                // Orden personalizado: urgente, alta, normal, baja
                $order_sql = "FIELD(prioridad, 'urgente', 'alta', 'normal', 'baja'), fecha_consulta DESC";
                break;
            case 'estado':
                // Orden: pendiente, en_proceso, respondida, cerrada
                $order_sql = "FIELD(estado, 'pendiente', 'en_proceso', 'respondida', 'cerrada'), fecha_consulta DESC";
                break;
            case 'asunto':
                $order_sql = "asunto ASC";
                break;
        }
        
        // Construir query
        $where_sql = implode(' AND ', $where);
        $limit_sql = ($args['limit'] > 0) ? "LIMIT {$args['offset']}, {$args['limit']}" : "";
        
        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $order_sql $limit_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public function obtener_estadisticas() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_consultas_dpd';
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'pendientes' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE estado = %s",
                self::ESTADO_PENDIENTE
            )),
            'en_proceso' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE estado = %s",
                self::ESTADO_EN_PROCESO
            )),
            'respondidas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE estado = %s",
                self::ESTADO_RESPONDIDA
            )),
            'tiempo_promedio' => round((float)($wpdb->get_var(
                "SELECT AVG(tiempo_respuesta) FROM $table WHERE tiempo_respuesta IS NOT NULL"
            ) ?: 0), 0),
            'mes_actual' => $wpdb->get_var("
                SELECT COUNT(*) FROM $table 
                WHERE MONTH(fecha_consulta) = MONTH(CURRENT_DATE())
                AND YEAR(fecha_consulta) = YEAR(CURRENT_DATE())
            "),
        );
    }
}
