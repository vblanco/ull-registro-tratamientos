<?php
/**
 * Gestión de base de datos
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Activar mostrar errores para debug
        $wpdb->show_errors();
        
        // Array para almacenar las queries
        $queries = array();
        
        // Tabla de propuestas de tratamientos
        $table_propuestas = $wpdb->prefix . 'ull_rt_propuestas';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_propuestas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            numero_propuesta varchar(50) NOT NULL,
            nombre varchar(255) NOT NULL,
            finalidad text NOT NULL,
            base_juridica text,
            colectivos text,
            categorias_datos text,
            cesiones text,
            transferencias_internacionales varchar(100),
            plazo_conservacion text,
            medidas_seguridad text,
            area_responsable varchar(255),
            responsable_nombre varchar(255) NOT NULL,
            responsable_cargo varchar(255),
            responsable_email varchar(255) NOT NULL,
            responsable_telefono varchar(50),
            justificacion text NOT NULL,
            estado varchar(50) NOT NULL DEFAULT 'pendiente',
            informe_dpd text,
            informe_dpd_pdf varchar(255),
            tratamiento_id bigint(20),
            fecha_propuesta datetime NOT NULL,
            fecha_informe datetime,
            usuario_informe bigint(20),
            ip_origen varchar(100),
            PRIMARY KEY (id),
            KEY numero_propuesta (numero_propuesta),
            KEY estado (estado),
            KEY tratamiento_id (tratamiento_id),
            KEY fecha_propuesta (fecha_propuesta)
        ) $charset_collate;";
        
        // Tabla de Tratamientos
        $table_tratamientos = $wpdb->prefix . 'ull_tratamientos';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_tratamientos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            base_juridica text NOT NULL,
            finalidad text NOT NULL,
            colectivos_interesados text,
            categorias_datos text,
            cesiones_comunicaciones text,
            transferencias_internacionales text,
            plazo_conservacion varchar(255),
            medidas_seguridad text,
            area_responsable varchar(255),
            estado varchar(50) DEFAULT 'activo',
            version int(11) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            creado_por bigint(20),
            modificado_por bigint(20),
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY area_responsable (area_responsable)
        ) $charset_collate;";
        
        // Tabla de Informes DPD
        $table_informes = $wpdb->prefix . 'ull_informes_dpd';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_informes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            tipo_informe varchar(100) NOT NULL,
            descripcion text,
            contenido longtext,
            tratamientos_relacionados text,
            estado varchar(50) DEFAULT 'borrador',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_publicacion datetime,
            creado_por bigint(20),
            PRIMARY KEY (id),
            KEY tipo_informe (tipo_informe),
            KEY estado (estado)
        ) $charset_collate;";
        
        // Tabla de Consultas DPD
        $table_consultas = $wpdb->prefix . 'ull_consultas_dpd';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_consultas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            numero_consulta varchar(50) NOT NULL,
            nombre_solicitante varchar(255) NOT NULL,
            email_solicitante varchar(255) NOT NULL,
            departamento varchar(255),
            asunto varchar(255) NOT NULL,
            consulta text NOT NULL,
            respuesta text,
            respuesta_pdf varchar(255),
            estado varchar(50) DEFAULT 'pendiente',
            prioridad varchar(20) DEFAULT 'normal',
            privada tinyint(1) DEFAULT 0,
            fecha_consulta datetime NOT NULL,
            fecha_respuesta datetime,
            respondido_por bigint(20),
            ip_origen varchar(100),
            PRIMARY KEY (id),
            KEY numero_consulta (numero_consulta),
            KEY estado (estado),
            KEY fecha_consulta (fecha_consulta)
        ) $charset_collate;";
        
        // Tabla de Ejercicio de Derechos
        $table_derechos = $wpdb->prefix . 'ull_ejercicio_derechos';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_derechos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            numero_solicitud varchar(50) NOT NULL,
            tipo_derecho varchar(50) NOT NULL,
            nombre varchar(255) NOT NULL,
            apellidos varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            telefono varchar(50),
            dni varchar(20),
            descripcion text NOT NULL,
            documentos text,
            respuesta text,
            estado varchar(50) DEFAULT 'pendiente',
            fecha_solicitud datetime NOT NULL,
            fecha_limite datetime,
            fecha_respuesta datetime,
            respondido_por bigint(20),
            ip_origen varchar(100),
            PRIMARY KEY (id),
            UNIQUE KEY numero_solicitud (numero_solicitud),
            KEY tipo_derecho (tipo_derecho),
            KEY estado (estado),
            KEY fecha_solicitud (fecha_solicitud)
        ) $charset_collate;";
        
        // Tabla de Audit Log
        $table_audit = $wpdb->prefix . 'ull_rt_audit_log';
        $queries[] = "CREATE TABLE IF NOT EXISTS $table_audit (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            accion varchar(100) NOT NULL,
            tabla_afectada varchar(100),
            registro_id bigint(20),
            descripcion text,
            datos_adicionales text,
            usuario_id bigint(20),
            ip varchar(100),
            fecha_hora datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY accion (accion),
            KEY tabla_afectada (tabla_afectada),
            KEY usuario_id (usuario_id),
            KEY fecha_hora (fecha_hora)
        ) $charset_collate;";
        
        // Ejecutar queries
        foreach ($queries as $query) {
            $result = $wpdb->query($query);
            if ($result === false) {
                error_log("Error creando tabla: " . $wpdb->last_error);
                error_log("Query: " . $query);
            }
        }
        
        // Verificar que las tablas se crearon
        $tables_created = array();
        $tables_to_check = array(
            'ull_rt_propuestas',
            'ull_tratamientos',
            'ull_informes_dpd',
            'ull_consultas_dpd',
            'ull_ejercicio_derechos',
            'ull_rt_audit_log'
        );
        
        foreach ($tables_to_check as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
            $tables_created[$table] = $exists;
            
            if (!$exists) {
                error_log("ADVERTENCIA: La tabla $full_table_name NO se creó correctamente");
            } else {
                error_log("ÉXITO: Tabla $full_table_name creada correctamente");
            }
        }
        
        return $tables_created;
    }
}
