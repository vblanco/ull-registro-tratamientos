<?php
/**
 * Diagnóstico de Errores de Activación
 * 
 * Subir a: /wp-content/plugins/ull-registro-tratamientos/
 * Acceder a: https://tu-sitio.com/wp-content/plugins/ull-registro-tratamientos/diagnostico-activacion.php
 */

// Activar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar permisos
if (!current_user_can('manage_options')) {
    die('Acceso denegado. Debes ser administrador.');
}

echo "<h1>Diagnóstico de Activación del Plugin</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; border-radius: 4px; }
    .section { background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px; }
    h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    table td, table th { border: 1px solid #ddd; padding: 8px; text-align: left; }
    table th { background: #0073aa; color: white; }
</style>";

echo "<div class='section'>";
echo "<h2>1. Verificación de Archivos Principales</h2>";

$archivos_requeridos = array(
    'ull-registro-tratamientos.php' => 'Archivo principal del plugin',
    'includes/class-database.php' => 'Clase de base de datos',
    'includes/class-shortcodes.php' => 'Clase de shortcodes',
    'includes/class-consultas-dpd.php' => 'Clase de consultas DPD',
    'includes/class-tratamientos.php' => 'Clase de tratamientos',
    'includes/class-admin-menu.php' => 'Clase de menú admin',
);

$plugin_dir = WP_PLUGIN_DIR . '/ull-registro-tratamientos/';

echo "<table>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Tamaño</th></tr>";

$archivos_ok = true;
foreach ($archivos_requeridos as $archivo => $descripcion) {
    $ruta_completa = $plugin_dir . $archivo;
    $existe = file_exists($ruta_completa);
    $tamanio = $existe ? filesize($ruta_completa) : 0;
    
    echo "<tr>";
    echo "<td>$archivo<br><small>$descripcion</small></td>";
    echo "<td>" . ($existe ? "<span class='ok'>✓ Existe</span>" : "<span class='error'>✗ No existe</span>") . "</td>";
    echo "<td>" . ($existe ? number_format($tamanio) . " bytes" : "-") . "</td>";
    echo "</tr>";
    
    if (!$existe) $archivos_ok = false;
}

echo "</table>";

if (!$archivos_ok) {
    echo "<p class='error'>⚠ Faltan archivos importantes. Reinstala el plugin.</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Verificación de Sintaxis PHP</h2>";

echo "<p>Comprobando sintaxis del archivo principal...</p>";

$archivo_principal = $plugin_dir . 'ull-registro-tratamientos.php';

if (file_exists($archivo_principal)) {
    $output = array();
    $return_var = 0;
    
    // Ejecutar php -l para verificar sintaxis
    exec("php -l " . escapeshellarg($archivo_principal) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "<p class='ok'>✓ Sintaxis correcta</p>";
    } else {
        echo "<p class='error'>✗ Error de sintaxis:</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ No se puede verificar (archivo no existe)</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Verificación de Errores PHP</h2>";

// Buscar errores en el log de WordPress
$debug_log = WP_CONTENT_DIR . '/debug.log';

if (file_exists($debug_log)) {
    $log_size = filesize($debug_log);
    echo "<p>Archivo debug.log encontrado (Tamaño: " . number_format($log_size) . " bytes)</p>";
    
    if ($log_size > 0) {
        // Leer últimas 50 líneas
        $lines = file($debug_log);
        $ultimas_lineas = array_slice($lines, -50);
        
        // Buscar errores relacionados con nuestro plugin
        $errores_plugin = array();
        foreach ($ultimas_lineas as $linea) {
            if (stripos($linea, 'ull') !== false || stripos($linea, 'registro') !== false || stripos($linea, 'tratamientos') !== false) {
                $errores_plugin[] = $linea;
            }
        }
        
        if (!empty($errores_plugin)) {
            echo "<p class='warning'>⚠ Errores encontrados en debug.log:</p>";
            echo "<pre>" . htmlspecialchars(implode("", $errores_plugin)) . "</pre>";
        } else {
            echo "<p class='ok'>✓ No se encontraron errores del plugin en el log</p>";
        }
    } else {
        echo "<p class='ok'>✓ El log está vacío (no hay errores)</p>";
    }
} else {
    echo "<p class='warning'>⚠ No hay archivo debug.log. Para activar:</p>";
    echo "<pre>En wp-config.php añade:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Prueba de Carga del Plugin</h2>";

echo "<p>Intentando cargar el archivo principal...</p>";

ob_start();
$error = null;

try {
    include_once($archivo_principal);
    echo "<p class='ok'>✓ Archivo cargado sin errores fatales</p>";
} catch (Exception $e) {
    $error = $e->getMessage();
    echo "<p class='error'>✗ Error al cargar: " . htmlspecialchars($error) . "</p>";
} catch (Error $e) {
    $error = $e->getMessage();
    echo "<p class='error'>✗ Error fatal: " . htmlspecialchars($error) . "</p>";
}

$output = ob_get_clean();
echo $output;

// Mostrar warnings/notices si los hay
if ($output && trim($output) !== '') {
    echo "<p class='warning'>Output capturado:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>5. Estado del Plugin en WordPress</h2>";

$all_plugins = get_plugins();
$plugin_file = 'ull-registro-tratamientos/ull-registro-tratamientos.php';

if (isset($all_plugins[$plugin_file])) {
    echo "<p class='ok'>✓ WordPress detecta el plugin</p>";
    echo "<table>";
    echo "<tr><th>Propiedad</th><th>Valor</th></tr>";
    foreach ($all_plugins[$plugin_file] as $key => $value) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
    // Ver si está activo
    if (is_plugin_active($plugin_file)) {
        echo "<p class='ok'>✓ El plugin está ACTIVO</p>";
    } else {
        echo "<p class='error'>✗ El plugin NO está activo</p>";
        echo "<p><a href='" . admin_url('plugins.php') . "' class='button'>Ir a Plugins</a></p>";
    }
} else {
    echo "<p class='error'>✗ WordPress NO detecta el plugin</p>";
    echo "<p>Plugins detectados en el directorio:</p>";
    echo "<ul>";
    foreach ($all_plugins as $key => $plugin) {
        if (stripos($key, 'ull') !== false || stripos($key, 'registro') !== false) {
            echo "<li>" . htmlspecialchars($key) . " - " . htmlspecialchars($plugin['Name']) . "</li>";
        }
    }
    echo "</ul>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Requisitos del Sistema</h2>";

echo "<table>";
echo "<tr><th>Requisito</th><th>Requerido</th><th>Actual</th><th>Estado</th></tr>";

// PHP Version
$php_ok = version_compare(PHP_VERSION, '7.4', '>=');
echo "<tr>";
echo "<td>Versión PHP</td>";
echo "<td>7.4+</td>";
echo "<td>" . PHP_VERSION . "</td>";
echo "<td>" . ($php_ok ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>") . "</td>";
echo "</tr>";

// WordPress Version
global $wp_version;
$wp_ok = version_compare($wp_version, '5.0', '>=');
echo "<tr>";
echo "<td>Versión WordPress</td>";
echo "<td>5.0+</td>";
echo "<td>" . $wp_version . "</td>";
echo "<td>" . ($wp_ok ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>") . "</td>";
echo "</tr>";

// MySQL Version
global $wpdb;
$mysql_version = $wpdb->db_version();
$mysql_ok = version_compare($mysql_version, '5.6', '>=');
echo "<tr>";
echo "<td>Versión MySQL</td>";
echo "<td>5.6+</td>";
echo "<td>" . $mysql_version . "</td>";
echo "<td>" . ($mysql_ok ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>") . "</td>";
echo "</tr>";

echo "</table>";

echo "</div>";

echo "<div class='section'>";
echo "<h2>7. Permisos de Archivos</h2>";

$directorios = array(
    $plugin_dir => 'Directorio principal',
    $plugin_dir . 'includes/' => 'Directorio includes',
    $plugin_dir . 'templates/' => 'Directorio templates',
);

echo "<table>";
echo "<tr><th>Directorio</th><th>Permisos</th><th>Estado</th></tr>";

foreach ($directorios as $dir => $desc) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $readable = is_readable($dir);
        
        echo "<tr>";
        echo "<td>$desc</td>";
        echo "<td>$perms</td>";
        echo "<td>" . ($readable ? "<span class='ok'>✓ Legible</span>" : "<span class='error'>✗ No legible</span>") . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "</div>";

echo "<hr>";
echo "<p><strong>Siguiente paso:</strong></p>";
echo "<ul>";
echo "<li>Si hay errores de sintaxis PHP: Revisa el código</li>";
echo "<li>Si faltan archivos: Reinstala el plugin</li>";
echo "<li>Si hay errores en debug.log: Léelos y corrígelos</li>";
echo "<li>Si todo está OK pero no se activa: <a href='" . admin_url('plugins.php') . "'>Intenta activar manualmente</a></li>";
echo "</ul>";

echo "<p><a href='" . admin_url() . "'>← Volver al Panel de WordPress</a></p>";
