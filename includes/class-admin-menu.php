<?php
/**
 * Menú de Administración
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Admin_Menu {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }
    
    public function add_menu_pages() {
        // Menú principal
        add_menu_page(
            'ULL Registro RGPD',
            'Registro RGPD',
            'ull_manage_tratamientos',
            'ull-registro-rgpd',
            array($this, 'render_dashboard'),
            'dashicons-shield',
            30
        );
        
        // Submenús
        add_submenu_page(
            'ull-registro-rgpd',
            'Dashboard',
            'Dashboard',
            'ull_manage_tratamientos',
            'ull-registro-rgpd',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Tratamientos',
            'Tratamientos',
            'ull_manage_tratamientos',
            'ull-registro-tratamientos',
            array($this, 'render_tratamientos')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Informes DPD',
            'Informes DPD',
            'ull_manage_informes',
            'ull-registro-informes',
            array($this, 'render_informes')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Consultas DPD',
            'Consultas DPD',
            'ull_manage_consultas',
            'ull-registro-consultas',
            array($this, 'render_consultas')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Ejercicio de Derechos',
            'Ejercicio de Derechos',
            'ull_manage_derechos',
            'ull-registro-derechos',
            array($this, 'render_derechos')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Propuestas de Tratamientos',
            'Propuestas',
            'ull_manage_tratamientos',
            'ull-rt-propuestas',
            array($this, 'render_propuestas')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Audit Log',
            'Audit Log',
            'ull_view_audit',
            'ull-registro-audit',
            array($this, 'render_audit_log')
        );
        
        add_submenu_page(
            'ull-registro-rgpd',
            'Ayuda y Shortcodes',
            'Ayuda',
            'ull_manage_tratamientos',
            'ull-registro-ayuda',
            array($this, 'render_ayuda')
        );
    }
    
    public function render_dashboard() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function render_tratamientos() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-tratamientos.php';
    }
    
    public function render_propuestas() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-propuestas.php';
    }
    
    public function render_informes() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-informes.php';
    }
    
    public function render_consultas() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-consultas.php';
    }
    
    public function render_derechos() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-derechos.php';
    }
    
    public function render_audit_log() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-audit-log.php';
    }
    
    public function render_ayuda() {
        require_once ULL_RT_PLUGIN_DIR . 'templates/admin-ayuda.php';
    }
}
