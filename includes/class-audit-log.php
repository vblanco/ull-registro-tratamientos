<?php
/**
 * Audit Log
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Audit_Log {
    
    public static function registrar($accion, $modulo, $descripcion, $datos_adicionales = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ull_audit_log';
        
        $usuario = wp_get_current_user();
        
        $wpdb->insert($table, array(
            'usuario_id' => get_current_user_id(),
            'usuario_nombre' => $usuario->display_name,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
            'datos_adicionales' => maybe_serialize($datos_adicionales),
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
        ));
    }
    
    public static function obtener_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'modulo' => '',
            'usuario_id' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ull_audit_log';
        $where = array();
        $where_values = array();
        
        if (!empty($args['modulo'])) {
            $where[] = "modulo = %s";
            $where_values[] = $args['modulo'];
        }
        
        if (!empty($args['usuario_id'])) {
            $where[] = "usuario_id = %d";
            $where_values[] = $args['usuario_id'];
        }
        
        if (!empty($args['fecha_desde'])) {
            $where[] = "fecha >= %s";
            $where_values[] = $args['fecha_desde'];
        }
        
        if (!empty($args['fecha_hasta'])) {
            $where[] = "fecha <= %s";
            $where_values[] = $args['fecha_hasta'] . ' 23:59:59';
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        
        $query = "SELECT * FROM $table $where_clause ORDER BY fecha DESC $limit_clause";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    private static function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        return $ip;
    }
}
