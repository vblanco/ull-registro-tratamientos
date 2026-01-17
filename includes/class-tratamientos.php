<?php
/**
 * Gestión de Actividades de Tratamiento
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Tratamientos {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function crear_tratamiento($datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        $defaults = array(
            'estado' => 'activo',
            'version' => 1,
            'creado_por' => get_current_user_id(),
            'modificado_por' => get_current_user_id(),
        );
        
        $datos = wp_parse_args($datos, $defaults);
        
        $result = $wpdb->insert($table, $datos);
        
        if ($result) {
            $id = $wpdb->insert_id;
            
            // Registrar en historial
            $this->registrar_historial($id, 'crear', null, $datos);
            
            // Audit log
            ULL_RT_Audit_Log::registrar('crear_tratamiento', 'tratamientos', "Creado tratamiento: {$datos['nombre']}");
            
            return $id;
        }
        
        return false;
    }
    
    public function actualizar_tratamiento($id, $datos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        // Obtener datos anteriores
        $datos_anteriores = $this->obtener_tratamiento($id);
        
        if (!$datos_anteriores) {
            return false;
        }
        
        // Incrementar versión
        $datos['version'] = $datos_anteriores->version + 1;
        $datos['modificado_por'] = get_current_user_id();
        
        $result = $wpdb->update(
            $table,
            $datos,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Registrar en historial
            $this->registrar_historial($id, 'actualizar', $datos_anteriores, $datos);
            
            // Audit log
            ULL_RT_Audit_Log::registrar('actualizar_tratamiento', 'tratamientos', "Actualizado tratamiento ID: $id");
            
            return true;
        }
        
        return false;
    }
    
    public function eliminar_tratamiento($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        // Obtener datos antes de eliminar
        $datos = $this->obtener_tratamiento($id);
        
        if (!$datos) {
            return false;
        }
        
        // Soft delete: cambiar estado
        $result = $wpdb->update(
            $table,
            array('estado' => 'eliminado'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
        
        if ($result) {
            // Registrar en historial
            $this->registrar_historial($id, 'eliminar', $datos, array('estado' => 'eliminado'));
            
            // Audit log
            ULL_RT_Audit_Log::registrar('eliminar_tratamiento', 'tratamientos', "Eliminado tratamiento: {$datos->nombre}");
            
            return true;
        }
        
        return false;
    }
    
    public function obtener_tratamiento($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }
    
    public function listar_tratamientos($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'estado' => 'activo',
            'orderby' => 'nombre',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
            'search' => '',
            'area_responsable' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        $where = array();
        $where_values = array();
        
        if (!empty($args['estado'])) {
            $where[] = "estado = %s";
            $where_values[] = $args['estado'];
        }
        
        if (!empty($args['search'])) {
            $where[] = "(nombre LIKE %s OR finalidad LIKE %s OR area_responsable LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        if (!empty($args['area_responsable'])) {
            $where[] = "area_responsable = %s";
            $where_values[] = $args['area_responsable'];
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
    
    public function contar_tratamientos($estado = 'activo') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        if ($estado) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE estado = %s",
                $estado
            ));
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    public function obtener_estadisticas() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_tratamientos';
        
        $stats = array(
            'total' => $this->contar_tratamientos('activo'),
            'por_area' => array(),
            'con_transferencias' => 0,
            'con_datos_sensibles' => 0,
        );
        
        // Estadísticas por área
        $areas = $wpdb->get_results("
            SELECT area_responsable, COUNT(*) as total 
            FROM $table 
            WHERE estado = 'activo' 
            GROUP BY area_responsable
            ORDER BY total DESC
        ");
        
        foreach ($areas as $area) {
            $stats['por_area'][$area->area_responsable] = $area->total;
        }
        
        // Contar con transferencias internacionales
        $stats['con_transferencias'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $table 
            WHERE estado = 'activo' 
            AND transferencias_internacionales NOT LIKE '%No previstas%'
            AND transferencias_internacionales != ''
        ");
        
        // Contar con datos sensibles (categorías especiales)
        $stats['con_datos_sensibles'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $table 
            WHERE estado = 'activo' 
            AND (categorias_datos LIKE '%Salud%' OR categorias_datos LIKE '%datos biométricos%')
        ");
        
        return $stats;
    }
    
    private function registrar_historial($tratamiento_id, $accion, $datos_anteriores, $datos_nuevos) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_historial';
        
        $wpdb->insert($table, array(
            'tabla_origen' => 'tratamientos',
            'registro_id' => $tratamiento_id,
            'accion' => $accion,
            'datos_anteriores' => maybe_serialize($datos_anteriores),
            'datos_nuevos' => maybe_serialize($datos_nuevos),
            'usuario_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
        ));
    }
    
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
