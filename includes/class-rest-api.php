<?php
/**
 * REST API
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_REST_API {
    
    private static $instance = null;
    private $namespace = 'ull-rt/v1';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // Tratamientos
        register_rest_route($this->namespace, '/tratamientos', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tratamientos'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        register_rest_route($this->namespace, '/tratamientos/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tratamiento'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Consultas
        register_rest_route($this->namespace, '/consultas', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_consultas'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Derechos
        register_rest_route($this->namespace, '/derechos', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_solicitudes_derechos'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Estadísticas
        register_rest_route($this->namespace, '/estadisticas', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_estadisticas'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }
    
    public function get_tratamientos($request) {
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $tratamientos = $tratamientos_obj->listar_tratamientos();
        
        return rest_ensure_response($tratamientos);
    }
    
    public function get_tratamiento($request) {
        $id = $request->get_param('id');
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $tratamiento = $tratamientos_obj->obtener_tratamiento($id);
        
        if (!$tratamiento) {
            return new WP_Error('not_found', 'Tratamiento no encontrado', array('status' => 404));
        }
        
        return rest_ensure_response($tratamiento);
    }
    
    public function get_consultas($request) {
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        $consultas = $consultas_obj->listar_consultas();
        
        return rest_ensure_response($consultas);
    }
    
    public function get_solicitudes_derechos($request) {
        $derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
        $solicitudes = $derechos_obj->listar_solicitudes();
        
        return rest_ensure_response($solicitudes);
    }
    
    public function get_estadisticas($request) {
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        
        $stats = array(
            'tratamientos' => $tratamientos_obj->obtener_estadisticas(),
            'derechos' => $derechos_obj->obtener_estadisticas(),
            'consultas' => $consultas_obj->obtener_estadisticas(),
        );
        
        return rest_ensure_response($stats);
    }
    
    public function check_permissions($request) {
        return current_user_can('ull_manage_tratamientos');
    }
}
