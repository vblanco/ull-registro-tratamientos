<?php
/**
 * Gestión de Propuestas de Nuevos Tratamientos
 * 
 * Permite que usuarios propongan nuevos tratamientos que deben
 * ser revisados y aprobados por el DPD antes de ser publicados.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_RT_Propuestas {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooks de inicialización
        add_action('init', array($this, 'procesar_propuesta_publica'));
    }
    
    /**
     * Crear una propuesta de tratamiento
     */
    public function crear_propuesta($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'ull_rt_propuestas';
        
        // Log para debugging
        error_log('=== INICIO crear_propuesta ===');
        error_log('Datos recibidos: ' . print_r(array_keys($datos), true));
        
        // Validar datos requeridos
        $campos_requeridos = array('nombre', 'finalidad', 'responsable_nombre', 'responsable_email');
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                error_log("ERROR: Campo requerido vacío: $campo");
                return new WP_Error('campo_requerido', "El campo {$campo} es obligatorio");
            }
        }
        
        // Validar email
        if (!is_email($datos['responsable_email'])) {
            error_log("ERROR: Email inválido: " . $datos['responsable_email']);
            return new WP_Error('email_invalido', 'El email proporcionado no es válido');
        }
        
        // Procesar arrays de checkboxes
        $base_juridica = '';
        if (isset($datos['base_juridica']) && is_array($datos['base_juridica'])) {
            $base_juridica = implode(". \n", array_map('sanitize_text_field', $datos['base_juridica'])) . '.';
            error_log("Base jurídica procesada: $base_juridica");
        } else {
            error_log("WARNING: base_juridica no es array o está vacío");
        }
        
        $colectivos = '';
        if (isset($datos['colectivos']) && is_array($datos['colectivos'])) {
            $colectivos = implode(", ", array_map('sanitize_text_field', $datos['colectivos'])) . '.';
            error_log("Colectivos procesados: $colectivos");
        } else {
            error_log("WARNING: colectivos no es array o está vacío");
        }
        
        $categorias_datos = '';
        if (isset($datos['categorias_datos']) && is_array($datos['categorias_datos'])) {
            $categorias_datos = implode(". \n", array_map('sanitize_text_field', $datos['categorias_datos'])) . '.';
            error_log("Categorías procesadas (longitud): " . strlen($categorias_datos));
        } else {
            error_log("WARNING: categorias_datos no es array o está vacío");
        }
        
        // Procesar cesiones
        $cesiones = 'No previstas';
        if (isset($datos['cesiones_select']) && $datos['cesiones_select'] === 'Sí' && !empty($datos['cesiones'])) {
            $cesiones = sanitize_textarea_field($datos['cesiones']);
        }
        
        // Procesar plazo de conservación
        $plazo_conservacion = '';
        if (isset($datos['plazo_conservacion_select'])) {
            if ($datos['plazo_conservacion_select'] === 'Otro' && !empty($datos['plazo_conservacion'])) {
                $plazo_conservacion = sanitize_textarea_field($datos['plazo_conservacion']);
            } else {
                $plazo_conservacion = sanitize_text_field($datos['plazo_conservacion_select']);
            }
        }
        error_log("Plazo conservación: $plazo_conservacion");
        
        // Preparar datos para inserción
        $datos_propuesta = array(
            'numero_propuesta' => $this->generar_numero_propuesta(),
            'nombre' => sanitize_text_field($datos['nombre']),
            'finalidad' => sanitize_textarea_field($datos['finalidad']),
            'base_juridica' => $base_juridica,
            'colectivos' => $colectivos,
            'categorias_datos' => $categorias_datos,
            'cesiones' => $cesiones,
            'transferencias_internacionales' => isset($datos['transferencias_internacionales']) ? sanitize_text_field($datos['transferencias_internacionales']) : 'No previstas',
            'plazo_conservacion' => $plazo_conservacion,
            'medidas_seguridad' => isset($datos['medidas_seguridad']) ? sanitize_text_field($datos['medidas_seguridad']) : '',
            'area_responsable' => sanitize_text_field($datos['area_responsable']),
            'responsable_nombre' => sanitize_text_field($datos['responsable_nombre']),
            'responsable_cargo' => isset($datos['responsable_cargo']) ? sanitize_text_field($datos['responsable_cargo']) : '',
            'responsable_email' => sanitize_email($datos['responsable_email']),
            'responsable_telefono' => isset($datos['responsable_telefono']) ? sanitize_text_field($datos['responsable_telefono']) : '',
            'justificacion' => sanitize_textarea_field($datos['justificacion']),
            'estado' => 'pendiente',
            'fecha_propuesta' => current_time('mysql'),
            'ip_origen' => $this->get_client_ip()
        );
        
        error_log("Número propuesta generado: " . $datos_propuesta['numero_propuesta']);
        error_log("Intentando insertar en tabla: $tabla");
        
        // Insertar en la base de datos
        $resultado = $wpdb->insert($tabla, $datos_propuesta);
        
        if ($resultado === false) {
            error_log("ERROR DB: " . $wpdb->last_error);
            error_log("Query: " . $wpdb->last_query);
            return new WP_Error('error_db', 'Error al guardar la propuesta: ' . $wpdb->last_error);
        }
        
        $propuesta_id = $wpdb->insert_id;
        error_log("Propuesta creada con ID: $propuesta_id");
        
        // Enviar notificación al DPD
        try {
            $this->notificar_nueva_propuesta($propuesta_id);
            error_log("Email al DPD enviado");
        } catch (Exception $e) {
            error_log("ERROR enviando email al DPD: " . $e->getMessage());
        }
        
        // Enviar confirmación al solicitante
        try {
            $this->enviar_confirmacion_propuesta($datos_propuesta);
            error_log("Email de confirmación enviado");
        } catch (Exception $e) {
            error_log("ERROR enviando email de confirmación: " . $e->getMessage());
        }
        
        // Registrar en audit log
        try {
            ULL_RT_Audit_Log::registrar(
                'propuesta_creada',
                'propuestas',
                $propuesta_id,
                "Nueva propuesta de tratamiento: {$datos_propuesta['nombre']}",
                array('numero_propuesta' => $datos_propuesta['numero_propuesta'])
            );
            error_log("Audit log registrado");
        } catch (Exception $e) {
            error_log("ERROR en audit log: " . $e->getMessage());
        }
        
        error_log('=== FIN crear_propuesta EXITOSO ===');
        
        return array(
            'success' => true,
            'propuesta_id' => $propuesta_id,
            'numero_propuesta' => $datos_propuesta['numero_propuesta']
        );
    }
    
    /**
     * Listar propuestas con filtros
     */
    public function listar_propuestas($args = array()) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'ull_rt_propuestas';
        
        $defaults = array(
            'estado' => '',
            'area' => '',
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'fecha_propuesta',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if (!empty($args['estado'])) {
            $where[] = $wpdb->prepare('estado = %s', $args['estado']);
        }
        
        if (!empty($args['area'])) {
            $where[] = $wpdb->prepare('area_responsable = %s', $args['area']);
        }
        
        $where_sql = implode(' AND ', $where);
        
        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        $sql = "SELECT * FROM {$tabla} WHERE {$where_sql} {$order_sql} {$limit_sql}";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Obtener una propuesta por ID
     */
    public function obtener_propuesta($id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'ull_rt_propuestas';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Emitir informe del DPD sobre una propuesta
     */
    public function emitir_informe($propuesta_id, $decision, $informe_texto, $informe_pdf = null, $usuario_id = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'ull_rt_propuestas';
        
        // Validar decisión
        $decisiones_validas = array('aprobada', 'denegada', 'modificaciones_requeridas');
        if (!in_array($decision, $decisiones_validas)) {
            return new WP_Error('decision_invalida', 'Decisión no válida');
        }
        
        // Validar que hay al menos un informe (texto o PDF)
        if (empty($informe_texto) && empty($informe_pdf)) {
            return new WP_Error('informe_vacio', 'Debe proporcionar el informe en texto, PDF, o ambos');
        }
        
        $propuesta = $this->obtener_propuesta($propuesta_id);
        if (!$propuesta) {
            return new WP_Error('propuesta_no_encontrada', 'Propuesta no encontrada');
        }
        
        // Procesar PDF si se proporcionó
        $pdf_filename = null;
        if ($informe_pdf && isset($informe_pdf['tmp_name']) && !empty($informe_pdf['tmp_name'])) {
            $upload_result = $this->subir_informe_pdf($informe_pdf, $propuesta->numero_propuesta);
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
            $pdf_filename = $upload_result['filename'];
        }
        
        // Actualizar propuesta
        $datos_actualizacion = array(
            'estado' => $decision,
            'informe_dpd' => !empty($informe_texto) ? sanitize_textarea_field($informe_texto) : '',
            'fecha_informe' => current_time('mysql'),
            'usuario_informe' => $usuario_id ? $usuario_id : get_current_user_id()
        );
        
        if ($pdf_filename) {
            $datos_actualizacion['informe_dpd_pdf'] = $pdf_filename;
        }
        
        $resultado = $wpdb->update(
            $tabla,
            $datos_actualizacion,
            array('id' => $propuesta_id)
        );
        
        if ($resultado === false) {
            return new WP_Error('error_db', 'Error al actualizar la propuesta');
        }
        
        // Si está aprobada, crear el tratamiento y guardar el ID
        $tratamiento_id = null;
        if ($decision === 'aprobada') {
            $tratamiento_id = $this->crear_tratamiento_desde_propuesta($propuesta);
            
            // Actualizar la propuesta con el ID del tratamiento creado
            if ($tratamiento_id) {
                $wpdb->update(
                    $tabla,
                    array('tratamiento_id' => $tratamiento_id),
                    array('id' => $propuesta_id)
                );
            }
        }
        
        // Enviar notificación al solicitante (con PDF si existe)
        $this->notificar_decision_propuesta($propuesta, $decision, $informe_texto, $pdf_filename);
        
        // Registrar en audit log
        ULL_RT_Audit_Log::registrar(
            'propuesta_evaluada',
            'propuestas',
            $propuesta_id,
            "Propuesta {$decision}: {$propuesta->nombre}",
            array(
                'decision' => $decision,
                'tiene_pdf' => !empty($pdf_filename),
                'tratamiento_id' => $tratamiento_id
            )
        );
        
        return array(
            'success' => true, 
            'pdf_filename' => $pdf_filename,
            'tratamiento_id' => $tratamiento_id
        );
    }
    
    /**
     * Subir PDF del informe DPD
     */
    private function subir_informe_pdf($archivo, $numero_propuesta) {
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
        $informes_dir = $upload_dir['basedir'] . '/ull-informes-dpd';
        
        if (!file_exists($informes_dir)) {
            wp_mkdir_p($informes_dir);
            // Añadir .htaccess para seguridad
            file_put_contents($informes_dir . '/.htaccess', 'Options -Indexes');
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $filename = 'informe-' . $numero_propuesta . '-' . time() . '.' . $extension;
        $filepath = $informes_dir . '/' . $filename;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $filepath)) {
            return new WP_Error('error_subida', 'Error al subir el archivo');
        }
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $upload_dir['baseurl'] . '/ull-informes-dpd/' . $filename
        );
    }
    
    /**
     * Crear tratamiento desde propuesta aprobada
     */
    private function crear_tratamiento_desde_propuesta($propuesta) {
        $tratamientos = ULL_RT_Tratamientos::get_instance();
        
        // Preparar datos del tratamiento con todos los campos de la propuesta
        $datos_tratamiento = array(
            'nombre' => $propuesta->nombre,
            'finalidad' => $propuesta->finalidad,
            'base_juridica' => $propuesta->base_juridica,
            'colectivos_interesados' => $propuesta->colectivos,
            'categorias_datos' => $propuesta->categorias_datos,
            'cesiones_comunicaciones' => $propuesta->cesiones,
            'transferencias_internacionales' => $propuesta->transferencias_internacionales,
            'plazo_conservacion' => $propuesta->plazo_conservacion,
            'medidas_seguridad' => $propuesta->medidas_seguridad,
            'area_responsable' => $propuesta->area_responsable,
            'estado' => 'activo'
        );
        
        // Intentar crear el tratamiento
        $tratamiento_id = $tratamientos->crear_tratamiento($datos_tratamiento);
        
        if (!$tratamiento_id || is_wp_error($tratamiento_id)) {
            $error_msg = is_wp_error($tratamiento_id) ? $tratamiento_id->get_error_message() : 'Error desconocido';
            error_log("ERROR al crear tratamiento desde propuesta {$propuesta->numero_propuesta}: {$error_msg}");
            return false;
        }
        
        // Registrar en audit log
        ULL_RT_Audit_Log::registrar(
            'tratamiento_creado_desde_propuesta',
            'tratamientos',
            $tratamiento_id,
            "Tratamiento creado automáticamente desde propuesta aprobada: {$propuesta->numero_propuesta}",
            array(
                'propuesta_id' => $propuesta->id,
                'numero_propuesta' => $propuesta->numero_propuesta,
                'nombre_tratamiento' => $propuesta->nombre
            )
        );
        
        error_log("✅ Tratamiento ID {$tratamiento_id} creado automáticamente desde propuesta {$propuesta->numero_propuesta}");
        
        return $tratamiento_id;
    }
    
    /**
     * Generar número único de propuesta
     */
    private function generar_numero_propuesta() {
        return 'PROP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Notificar al DPD sobre nueva propuesta
     */
    private function notificar_nueva_propuesta($propuesta_id) {
        $propuesta = $this->obtener_propuesta($propuesta_id);
        $email_dpd = get_option('ull_rt_dpd_email', 'dpd@ull.es');
        
        $asunto = '[ULL RGPD] Nueva propuesta de tratamiento - ' . $propuesta->numero_propuesta;
        
        $mensaje = "Se ha recibido una nueva propuesta de tratamiento de datos personales.\n\n";
        $mensaje .= "DATOS DE LA PROPUESTA:\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "Número de propuesta: {$propuesta->numero_propuesta}\n";
        $mensaje .= "Fecha: " . date('d/m/Y H:i', strtotime($propuesta->fecha_propuesta)) . "\n\n";
        $mensaje .= "TRATAMIENTO PROPUESTO:\n";
        $mensaje .= "Nombre: {$propuesta->nombre}\n";
        $mensaje .= "Área responsable: {$propuesta->area_responsable}\n";
        $mensaje .= "Finalidad: {$propuesta->finalidad}\n\n";
        $mensaje .= "SOLICITANTE:\n";
        $mensaje .= "Nombre: {$propuesta->responsable_nombre}\n";
        $mensaje .= "Cargo: {$propuesta->responsable_cargo}\n";
        $mensaje .= "Email: {$propuesta->responsable_email}\n";
        $mensaje .= "Teléfono: {$propuesta->responsable_telefono}\n\n";
        $mensaje .= "JUSTIFICACIÓN:\n";
        $mensaje .= "{$propuesta->justificacion}\n\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "Acceda al panel de administración para revisar y emitir su informe:\n";
        $mensaje .= admin_url('admin.php?page=ull-rt-propuestas') . "\n\n";
        $mensaje .= "Este es un mensaje automático del sistema de Registro de Tratamientos RGPD.\n";
        
        wp_mail($email_dpd, $asunto, $mensaje);
    }
    
    /**
     * Enviar confirmación al solicitante
     */
    private function enviar_confirmacion_propuesta($datos) {
        $asunto = '[ULL RGPD] Confirmación de propuesta recibida - ' . $datos['numero_propuesta'];
        
        $mensaje = "Estimado/a {$datos['responsable_nombre']},\n\n";
        $mensaje .= "Su propuesta de nuevo tratamiento de datos personales ha sido recibida correctamente.\n\n";
        $mensaje .= "RESUMEN DE SU PROPUESTA:\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "Número de propuesta: {$datos['numero_propuesta']}\n";
        $mensaje .= "Nombre del tratamiento: {$datos['nombre']}\n";
        $mensaje .= "Área responsable: {$datos['area_responsable']}\n";
        $mensaje .= "Fecha de propuesta: " . date('d/m/Y H:i') . "\n\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "El Delegado de Protección de Datos (DPD) revisará su propuesta y emitirá un informe ";
        $mensaje .= "en un plazo máximo de 10 días hábiles.\n\n";
        $mensaje .= "Recibirá una notificación por email cuando se emita el informe.\n\n";
        $mensaje .= "Si tiene alguna pregunta, puede contactar con el DPD en: dpd@ull.es\n\n";
        $mensaje .= "Atentamente,\n";
        $mensaje .= "Universidad de La Laguna\n";
        $mensaje .= "Delegado de Protección de Datos\n\n";
        $mensaje .= "Este es un mensaje automático. Por favor, no responda a este correo.\n";
        
        wp_mail($datos['responsable_email'], $asunto, $mensaje);
    }
    
    /**
     * Notificar decisión al solicitante
     */
    private function notificar_decision_propuesta($propuesta, $decision, $informe, $pdf_filename = null) {
        $estados_texto = array(
            'aprobada' => 'APROBADA',
            'denegada' => 'DENEGADA',
            'modificaciones_requeridas' => 'REQUIERE MODIFICACIONES'
        );
        
        $asunto = "[ULL RGPD] Informe DPD: Propuesta {$estados_texto[$decision]} - {$propuesta->numero_propuesta}";
        
        $mensaje = "Estimado/a {$propuesta->responsable_nombre},\n\n";
        $mensaje .= "El Delegado de Protección de Datos ha emitido su informe sobre su propuesta de tratamiento.\n\n";
        $mensaje .= "DATOS DE LA PROPUESTA:\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensaje .= "Número: {$propuesta->numero_propuesta}\n";
        $mensaje .= "Tratamiento: {$propuesta->nombre}\n";
        $mensaje .= "Fecha propuesta: " . date('d/m/Y', strtotime($propuesta->fecha_propuesta)) . "\n";
        $mensaje .= "Fecha informe: " . date('d/m/Y', strtotime($propuesta->fecha_informe)) . "\n\n";
        $mensaje .= "DECISIÓN: {$estados_texto[$decision]}\n\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        if (!empty($informe)) {
            $mensaje .= "INFORME DEL DPD:\n\n";
            $mensaje .= $informe . "\n\n";
            $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        if ($pdf_filename) {
            $mensaje .= "📎 Se adjunta el informe oficial del DPD en formato PDF.\n\n";
            $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        if ($decision === 'aprobada') {
            $mensaje .= "Su propuesta ha sido aprobada y el tratamiento ha sido incorporado al registro ";
            $mensaje .= "de actividades de tratamiento de la ULL.\n\n";
        } elseif ($decision === 'modificaciones_requeridas') {
            $mensaje .= "Para proceder con la aprobación de su propuesta, es necesario realizar las ";
            $mensaje .= "modificaciones indicadas en el informe del DPD.\n\n";
            $mensaje .= "Por favor, póngase en contacto con el DPD para coordinar las modificaciones necesarias.\n\n";
        } else {
            $mensaje .= "Si tiene dudas sobre los motivos de la denegación, puede contactar con el DPD.\n\n";
        }
        
        $mensaje .= "Para cualquier consulta, contacte con:\n";
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
            $pdf_path = $upload_dir['basedir'] . '/ull-informes-dpd/' . $pdf_filename;
            if (file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }
        
        // Enviar email con o sin adjunto
        if (!empty($attachments)) {
            wp_mail($propuesta->responsable_email, $asunto, $mensaje, '', $attachments);
        } else {
            wp_mail($propuesta->responsable_email, $asunto, $mensaje);
        }
    }
    
    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }
    
    /**
     * Procesar envío de propuesta desde formulario público
     */
    public function procesar_propuesta_publica() {
        // Verificar que es un envío de propuesta
        if (!isset($_POST['ull_rt_propuesta_nonce'])) {
            return;
        }
        
        error_log('=== INICIO procesar_propuesta_publica ===');
        error_log('POST keys: ' . print_r(array_keys($_POST), true));
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['ull_rt_propuesta_nonce'], 'ull_rt_enviar_propuesta')) {
            error_log('ERROR: Nonce inválido');
            wp_redirect(add_query_arg('propuesta_error', 'nonce_invalido', wp_get_referer()));
            exit;
        }
        
        error_log('Nonce válido, continuando...');
        
        // Validar campos requeridos básicos
        $campos_requeridos = array(
            'nombre' => 'Nombre del tratamiento',
            'area_responsable' => 'Área responsable',
            'finalidad' => 'Finalidad',
            'justificacion' => 'Justificación',
            'responsable_nombre' => 'Nombre del responsable',
            'responsable_cargo' => 'Cargo',
            'responsable_email' => 'Email',
            'responsable_telefono' => 'Teléfono'
        );
        
        foreach ($campos_requeridos as $campo => $nombre) {
            if (empty($_POST[$campo])) {
                error_log("ERROR: Campo requerido vacío - $campo");
                wp_redirect(add_query_arg(array(
                    'propuesta_error' => 'campo_requerido',
                    'campo_faltante' => urlencode($nombre)
                ), wp_get_referer()));
                exit;
            }
        }
        
        error_log('Campos básicos validados');
        
        // Validar que se marcó al menos una base jurídica
        if (empty($_POST['base_juridica']) || !is_array($_POST['base_juridica'])) {
            error_log('ERROR: Base jurídica vacía o no es array');
            wp_redirect(add_query_arg(array(
                'propuesta_error' => 'campo_requerido',
                'campo_faltante' => 'Base jurídica (debe seleccionar al menos una)'
            ), wp_get_referer()));
            exit;
        }
        
        error_log('Base jurídica: ' . print_r($_POST['base_juridica'], true));
        
        // Validar que se marcó al menos un colectivo
        if (empty($_POST['colectivos']) || !is_array($_POST['colectivos'])) {
            error_log('ERROR: Colectivos vacío o no es array');
            wp_redirect(add_query_arg(array(
                'propuesta_error' => 'campo_requerido',
                'campo_faltante' => 'Colectivos interesados (debe seleccionar al menos uno)'
            ), wp_get_referer()));
            exit;
        }
        
        error_log('Colectivos: ' . print_r($_POST['colectivos'], true));
        
        // Validar que se marcó al menos una categoría de datos
        if (empty($_POST['categorias_datos']) || !is_array($_POST['categorias_datos'])) {
            error_log('ERROR: Categorías datos vacío o no es array');
            wp_redirect(add_query_arg(array(
                'propuesta_error' => 'campo_requerido',
                'campo_faltante' => 'Categorías de datos (debe seleccionar al menos una)'
            ), wp_get_referer()));
            exit;
        }
        
        error_log('Categorías datos: ' . count($_POST['categorias_datos']) . ' seleccionadas');
        error_log('Todas las validaciones pasadas, llamando a crear_propuesta...');
        
        // Crear la propuesta
        $resultado = $this->crear_propuesta($_POST);
        
        if (is_wp_error($resultado)) {
            error_log('ERROR al crear propuesta: ' . $resultado->get_error_message());
            wp_redirect(add_query_arg(array(
                'propuesta_error' => $resultado->get_error_code(),
                'mensaje_error' => urlencode($resultado->get_error_message())
            ), wp_get_referer()));
        } else {
            error_log('Propuesta creada exitosamente: ' . $resultado['numero_propuesta']);
            wp_redirect(add_query_arg(array(
                'propuesta_enviada' => 'success',
                'numero_propuesta' => $resultado['numero_propuesta']
            ), wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Obtener estadísticas de propuestas
     */
    public function obtener_estadisticas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'ull_rt_propuestas';
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}"),
            'pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"),
            'aprobadas' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'aprobada'"),
            'denegadas' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'denegada'"),
            'modificaciones' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'modificaciones_requeridas'")
        );
    }
}
