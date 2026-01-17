<?php
/**
 * VER ERRORES DE ACTIVACIÓN
 * 
 * Este script te mostrará el error exacto que impide activar el plugin
 * 
 * INSTRUCCIONES:
 * 1. Subir a: /wp-content/plugins/ull-registro-tratamientos/
 * 2. Acceder a: https://tu-sitio.es/wp-content/plugins/ull-registro-tratamientos/ver-errores.php
 * 3. Copiar el error completo
 */

// Activar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ver Errores de Activación</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #1F4E78; }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2E75B5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>🔍 Ver Errores de Activación del Plugin</h1>
    
    <div class="box warning">
        <h2>⚠️ Información</h2>
        <p>Este script intentará cargar el plugin y mostrará cualquier error que ocurra.</p>
        <p><strong>IMPORTANTE:</strong> Por favor, copia TODO el mensaje de error que aparezca abajo.</p>
    </div>
    
    <?php
    echo "<div class='box'>";
    echo "<h2>📊 Estado Actual</h2>";
    
    // Verificar si el plugin existe
    $plugin_file = 'ull-registro-tratamientos/ull-registro-tratamientos.php';
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    
    echo "<p><strong>Ruta del plugin:</strong> <code>$plugin_path</code></p>";
    echo "<p><strong>¿Archivo existe?</strong> " . (file_exists($plugin_path) ? '✅ SÍ' : '❌ NO') . "</p>";
    
    if (file_exists($plugin_path)) {
        echo "<p><strong>Permisos:</strong> " . substr(sprintf('%o', fileperms($plugin_path)), -4) . "</p>";
        echo "<p><strong>Tamaño:</strong> " . filesize($plugin_path) . " bytes</p>";
    }
    
    // Verificar si está activo
    $is_active = is_plugin_active($plugin_file);
    echo "<p><strong>¿Plugin activo?</strong> " . ($is_active ? '✅ SÍ' : '❌ NO') . "</p>";
    
    echo "</div>";
    
    // Intentar cargar el plugin manualmente para ver errores
    if (!$is_active && file_exists($plugin_path)) {
        echo "<div class='box error'>";
        echo "<h2>🔴 Intentando Cargar el Plugin...</h2>";
        echo "<p>Si hay errores, aparecerán abajo:</p>";
        echo "<pre>";
        
        ob_start();
        try {
            include_once($plugin_path);
            $output = ob_get_clean();
            
            if (empty($output)) {
                echo "✅ El archivo principal se cargó sin errores PHP.\n\n";
                echo "Verificando clases...\n\n";
                
                // Verificar clases
                $clases = array(
                    'ULL_Registro_Tratamientos',
                    'ULL_RT_Database',
                    'ULL_RT_Propuestas',
                    'ULL_RT_Tratamientos',
                    'ULL_RT_Admin_Menu',
                    'ULL_RT_Shortcodes'
                );
                
                foreach ($clases as $clase) {
                    if (class_exists($clase)) {
                        echo "✅ Clase '$clase' existe\n";
                    } else {
                        echo "❌ Clase '$clase' NO existe\n";
                    }
                }
                
            } else {
                echo "⚠️ Salida capturada:\n\n";
                echo htmlspecialchars($output);
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "❌ EXCEPCIÓN CAPTURADA:\n\n";
            echo "Mensaje: " . $e->getMessage() . "\n";
            echo "Archivo: " . $e->getFile() . "\n";
            echo "Línea: " . $e->getLine() . "\n\n";
            echo "Traza:\n" . $e->getTraceAsString();
        } catch (Error $e) {
            ob_end_clean();
            echo "❌ ERROR FATAL CAPTURADO:\n\n";
            echo "Mensaje: " . $e->getMessage() . "\n";
            echo "Archivo: " . $e->getFile() . "\n";
            echo "Línea: " . $e->getLine() . "\n\n";
            echo "Traza:\n" . $e->getTraceAsString();
        }
        
        echo "</pre>";
        echo "</div>";
    }
    
    // Verificar archivos necesarios
    echo "<div class='box'>";
    echo "<h2>📁 Archivos del Plugin</h2>";
    
    $archivos_necesarios = array(
        'ull-registro-tratamientos.php' => 'Archivo principal',
        'includes/class-database.php' => 'Clase Database',
        'includes/class-propuestas.php' => 'Clase Propuestas',
        'includes/class-tratamientos.php' => 'Clase Tratamientos',
        'includes/class-admin-menu.php' => 'Clase Admin Menu',
        'includes/class-shortcodes.php' => 'Clase Shortcodes',
        'includes/class-rest-api.php' => 'Clase REST API',
        'includes/class-ejercicio-derechos.php' => 'Clase Ejercicio Derechos',
        'includes/class-audit-log.php' => 'Clase Audit Log'
    );
    
    $plugin_dir = WP_PLUGIN_DIR . '/ull-registro-tratamientos/';
    
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background:#1F4E78; color:white;'><th style='padding:10px; text-align:left;'>Archivo</th><th style='padding:10px; text-align:left;'>Estado</th></tr>";
    
    foreach ($archivos_necesarios as $archivo => $descripcion) {
        $ruta = $plugin_dir . $archivo;
        $existe = file_exists($ruta);
        echo "<tr style='border-bottom:1px solid #ddd;'>";
        echo "<td style='padding:10px;'><code>$archivo</code><br><small>$descripcion</small></td>";
        echo "<td style='padding:10px;'>" . ($existe ? '✅ Existe' : '❌ NO existe') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // Verificar versión de PHP y requisitos
    echo "<div class='box'>";
    echo "<h2>⚙️ Requisitos del Sistema</h2>";
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background:#1F4E78; color:white;'><th style='padding:10px; text-align:left;'>Requisito</th><th style='padding:10px; text-align:left;'>Actual</th><th style='padding:10px; text-align:left;'>Estado</th></tr>";
    
    $php_version = phpversion();
    $php_ok = version_compare($php_version, '7.4', '>=');
    echo "<tr style='border-bottom:1px solid #ddd;'>";
    echo "<td style='padding:10px;'>PHP >= 7.4</td>";
    echo "<td style='padding:10px;'>$php_version</td>";
    echo "<td style='padding:10px;'>" . ($php_ok ? '✅ OK' : '❌ Actualizar PHP') . "</td>";
    echo "</tr>";
    
    $wp_version = get_bloginfo('version');
    $wp_ok = version_compare($wp_version, '5.0', '>=');
    echo "<tr style='border-bottom:1px solid #ddd;'>";
    echo "<td style='padding:10px;'>WordPress >= 5.0</td>";
    echo "<td style='padding:10px;'>$wp_version</td>";
    echo "<td style='padding:10px;'>" . ($wp_ok ? '✅ OK' : '❌ Actualizar WordPress') . "</td>";
    echo "</tr>";
    
    $memory = ini_get('memory_limit');
    echo "<tr style='border-bottom:1px solid #ddd;'>";
    echo "<td style='padding:10px;'>Límite de memoria</td>";
    echo "<td style='padding:10px;'>$memory</td>";
    echo "<td style='padding:10px;'>ℹ️ Info</td>";
    echo "</tr>";
    
    echo "</table>";
    echo "</div>";
    
    // Log de errores de WordPress
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        echo "<div class='box'>";
        echo "<h2>📝 Últimos Errores del Log de WordPress</h2>";
        
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $log_lines = array_reverse($log_lines);
        $log_lines = array_slice($log_lines, 0, 50);
        
        // Filtrar líneas relacionadas con el plugin
        $plugin_errors = array_filter($log_lines, function($line) {
            return stripos($line, 'ull') !== false || 
                   stripos($line, 'registro') !== false ||
                   stripos($line, 'tratamiento') !== false ||
                   stripos($line, 'Fatal') !== false ||
                   stripos($line, 'Parse') !== false;
        });
        
        if (!empty($plugin_errors)) {
            echo "<pre>";
            foreach ($plugin_errors as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p>✅ No se encontraron errores relacionados con el plugin en el log.</p>";
        }
        
        echo "</div>";
    }
    ?>
    
    <div class="box error">
        <h2>📋 POR FAVOR, COPIA Y ENVÍA:</h2>
        <p><strong>1. Todo el contenido de "Intentando Cargar el Plugin"</strong> (el mensaje de error completo)</p>
        <p><strong>2. La lista de archivos que NO existen (si hay alguno)</strong></p>
        <p><strong>3. El contenido de "Últimos Errores del Log" (si hay alguno)</strong></p>
        <p><strong>4. Screenshot de esta página completa</strong></p>
    </div>
    
    <div class="box">
        <h2>🔗 Enlaces Útiles</h2>
        <a href="<?php echo admin_url('plugins.php'); ?>" class="btn">Ir a Plugins</a>
        <a href="?refresh=1" class="btn">🔄 Recargar</a>
    </div>
    
</body>
</html>
