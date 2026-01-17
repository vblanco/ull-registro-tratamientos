<?php
/**
 * Plugin Name: ULL Registro de Tratamientos RGPD
 * Plugin URI: https://www.ull.es
 * Description: Sistema completo de gestión del Registro de Actividades de Tratamiento de Datos Personales según RGPD. Incluye gestión de tratamientos, informes del DPD, consultas y ejercicio de derechos.
 * Version: 1.0.0
 * Author: Universidad de La Laguna - DPD
 * Author URI: https://www.ull.es
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ull-registro-tratamientos
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('ULL_RT_VERSION', '1.0.0');
define('ULL_RT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ULL_RT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ULL_RT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class ULL_Registro_Tratamientos {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Cargar clases principales
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-database.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-tratamientos.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-propuestas.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-informes-dpd.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-consultas-dpd.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-ejercicio-derechos.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-audit-log.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-import-export.php';
        require_once ULL_RT_PLUGIN_DIR . 'includes/class-shortcodes.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Soporte Multisite: Hook para cuando se crea un sitio nuevo en el multisite
        add_action('wp_initialize_site', array($this, 'activate_new_site'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Hook para exportar informe a PDF
        add_action('admin_post_ull_exportar_informe_pdf', array($this, 'handle_exportar_informe_pdf'));
        
        // Inicializar componentes
        ULL_RT_Admin_Menu::get_instance();
        ULL_RT_REST_API::get_instance();
        ULL_RT_Ejercicio_Derechos::get_instance();
        ULL_RT_Propuestas::get_instance();
    }
    
    /**
     * Multisite: Soporte para activación en red
     */
    public function activate($network_wide) {
        if (is_multisite() && $network_wide) {
            // Recorrer todos los sitios de la red
            $sites = get_sites(['fields' => 'ids', 'number' => 0]);
            foreach ($sites as $site_id) {
                switch_to_blog($site_id);
                $this->run_site_setup();
                restore_current_blog();
            }
        } else {
            // Activación en un solo sitio
            $this->run_site_setup();
        }
        
        error_log('ULL Registro Tratamientos: Plugin activado');
    }

    /**
     * Multisite: Setup específico para cada sitio
     */
    private function run_site_setup() {
        ULL_RT_Database::create_tables();
        $this->create_roles();
        flush_rewrite_rules();
    }

    /**
     * Multisite: Hook para nuevos sitios
     * Si el plugin está activo en la red, se asegura de crear tablas en el nuevo blog
     */
    public function activate_new_site($site) {
        if (is_plugin_active_for_network(ULL_RT_PLUGIN_BASENAME)) {
            switch_to_blog($site->blog_id);
            $this->run_site_setup();
            restore_current_blog();
        }
    }
      
    public function deactivate() {
        flush_rewrite_rules();
        error_log('ULL Registro Tratamientos: Plugin desactivado');
    }
    
    private function create_roles() {
        // Rol DPD (Delegado de Protección de Datos)
        add_role('ull_dpd', 'DPD ULL', array(
            'read' => true,
            'ull_manage_tratamientos' => true,
            'ull_manage_informes' => true,
            'ull_manage_consultas' => true,
            'ull_manage_derechos' => true,
            'ull_view_audit' => true,
        ));
        
        // Rol Consultor (solo lectura)
        add_role('ull_consultor', 'Consultor RGPD', array(
            'read' => true,
            'ull_view_tratamientos' => true,
            'ull_view_informes' => true,
        ));
        
        // Añadir capacidades al administrador
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('ull_manage_tratamientos');
            $admin->add_cap('ull_manage_informes');
            $admin->add_cap('ull_manage_consultas');
            $admin->add_cap('ull_manage_derechos');
            $admin->add_cap('ull_view_audit');
        }
    }
    
    public function init() {
        load_plugin_textdomain('ull-registro-tratamientos', false, dirname(ULL_RT_PLUGIN_BASENAME) . '/languages');
    }
    
    public function enqueue_admin_assets($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'ull-registro') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style('ull-rt-admin', ULL_RT_PLUGIN_URL . 'assets/css/admin.css', array(), ULL_RT_VERSION);
        
        // CSS moderno para tratamientos
        if (isset($_GET['page']) && $_GET['page'] === 'ull-registro-tratamientos') {
            wp_enqueue_style('ull-rt-tratamientos-modern', ULL_RT_PLUGIN_URL . 'assets/css/tratamientos-modern.css', array(), ULL_RT_VERSION);
        }
        
        // JS
        wp_enqueue_script('ull-rt-admin', ULL_RT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ULL_RT_VERSION, true);
        
        // Localización
        wp_localize_script('ull-rt-admin', 'ullRT', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ull_rt_nonce'),
            'i18n' => array(
                'confirmar_eliminar' => __('¿Está seguro de que desea eliminar este elemento?', 'ull-registro-tratamientos'),
                'guardando' => __('Guardando...', 'ull-registro-tratamientos'),
                'error' => __('Ha ocurrido un error', 'ull-registro-tratamientos'),
            )
        ));
    }
    
    public function enqueue_public_assets() {
        // CSS público limpio (sin iconos, diseño profesional)
        wp_enqueue_style('ull-rt-public', ULL_RT_PLUGIN_URL . 'assets/css/public-clean.css', array(), ULL_RT_VERSION);
        
        // JS público
        wp_enqueue_script('ull-rt-public', ULL_RT_PLUGIN_URL . 'assets/js/public.js', array('jquery'), ULL_RT_VERSION, true);
        
        wp_localize_script('ull-rt-public', 'ullRTPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ull_rt_public_nonce'),
        ));
    }
    
    /**
     * Manejar la exportación de informe a PDF
     */
    public function handle_exportar_informe_pdf() {
        // Verificar permisos
        if (!current_user_can('ull_manage_informes') && !current_user_can('manage_options')) {
            wp_die('No tiene permisos para exportar informes.');
        }
        
        // Obtener ID del informe
        $informe_id = isset($_GET['informe_id']) ? intval($_GET['informe_id']) : 0;
        
        if (!$informe_id) {
            wp_die('ID de informe no válido.');
        }
        
        // Exportar el informe
        $informes_obj = ULL_RT_Informes_DPD::get_instance();
        $informes_obj->exportar_informe_pdf($informe_id);
    }
}

// Inicializar el plugin
function ull_registro_tratamientos() {
    return ULL_Registro_Tratamientos::get_instance();
}

ull_registro_tratamientos();
