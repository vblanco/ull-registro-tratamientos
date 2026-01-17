<?php
/**
 * VER ERRORES PHP FATALES
 * 
 * Muestra los últimos errores PHP del log para diagnosticar errores críticos
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="3">
    <title>Errores PHP Fatales</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        h1 { color: #f48771; }
        .error { 
            background: #3f1d1d; 
            border-left: 4px solid #f48771;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .fatal { 
            background: #5a1d1d; 
            border-left: 4px solid #ff0000;
            font-weight: bold;
        }
        .timestamp { color: #4fc1ff; }
        .file { color: #dcdcaa; }
        .line { color: #b5cea8; }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .counter {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #dc3232;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="counter">
        🔄 Auto-recarga: 3s
    </div>
    
    <h1>⚠️ ERRORES PHP FATALES - Debug en Tiempo Real</h1>
    
    <p style="color: #4ec9b0;">
        <strong>INSTRUCCIONES:</strong><br>
        1. Deja esta página abierta (se recarga cada 3 segundos)<br>
        2. En otra pestaña, intenta enviar el formulario<br>
        3. Vuelve aquí y verás el error exacto<br>
        4. Copia TODO el error y envíamelo
    </p>
    
    <hr style="border-color: #3e3e42;">
    
    <?php
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if (!file_exists($log_file)) {
        echo '<div class="error fatal">';
        echo '<strong>⚠️ ADVERTENCIA:</strong> El archivo debug.log no existe.<br><br>';
        echo 'Para activar el logging, edita <strong>wp-config.php</strong> y añade:<br><br>';
        echo '<pre>';
        echo "define('WP_DEBUG', true);\n";
        echo "define('WP_DEBUG_LOG', true);\n";
        echo "define('WP_DEBUG_DISPLAY', false);";
        echo '</pre>';
        echo '</div>';
    } else {
        // Leer últimas 500 líneas
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $log_lines = array_reverse($log_lines);
        $log_lines = array_slice($log_lines, 0, 500);
        
        // Buscar errores fatales, parse errors, y errores de WordPress
        $errores = array();
        $error_actual = '';
        $capturando_error = false;
        
        foreach ($log_lines as $line) {
            if (empty(trim($line))) continue;
            
            // Detectar inicio de error
            if (preg_match('/PHP Fatal error:|PHP Parse error:|WordPress database error/i', $line)) {
                if ($capturando_error && !empty($error_actual)) {
                    $errores[] = $error_actual;
                }
                $error_actual = $line;
                $capturando_error = true;
            } elseif ($capturando_error) {
                // Continuar capturando líneas del error
                if (preg_match('/^\[.*?\]/', $line)) {
                    // Nueva entrada de log, terminar error actual
                    if (!empty($error_actual)) {
                        $errores[] = $error_actual;
                    }
                    $error_actual = '';
                    $capturando_error = false;
                    
                    // Si esta nueva línea también es un error, empezar a capturarla
                    if (preg_match('/PHP Fatal error:|PHP Parse error:|WordPress database error/i', $line)) {
                        $error_actual = $line;
                        $capturando_error = true;
                    }
                } else {
                    // Línea de continuación del error actual
                    $error_actual .= "\n" . $line;
                }
            }
        }
        
        // Añadir último error si existe
        if ($capturando_error && !empty($error_actual)) {
            $errores[] = $error_actual;
        }
        
        if (empty($errores)) {
            echo '<div class="error" style="background: #1d3f1d; border-left-color: #46b450;">';
            echo '<strong>✅ No se encontraron errores fatales recientes.</strong><br><br>';
            echo 'Esto puede significar:<br>';
            echo '• No ha ocurrido ningún error fatal<br>';
            echo '• El error ya pasó y no está en las últimas 500 líneas<br>';
            echo '• El log fue limpiado recientemente<br><br>';
            echo '<strong>ACCIÓN:</strong> Intenta enviar el formulario AHORA y espera 3 segundos.';
            echo '</div>';
        } else {
            echo '<h2 style="color: #f48771;">⚠️ ERRORES ENCONTRADOS: ' . count($errores) . '</h2>';
            
            foreach (array_slice($errores, 0, 10) as $i => $error) {
                $es_fatal = stripos($error, 'Fatal error') !== false || stripos($error, 'Parse error') !== false;
                $clase = $es_fatal ? 'error fatal' : 'error';
                
                echo "<div class='$clase'>";
                echo "<strong>Error #" . ($i + 1) . ":</strong><br>";
                
                // Resaltar partes importantes
                $error_html = htmlspecialchars($error);
                $error_html = preg_replace('/(\[.*?\])/', '<span class="timestamp">$1</span>', $error_html);
                $error_html = preg_replace('/(Fatal error:|Parse error:|WordPress database error)/i', '<strong style="color:#ff6b6b;">$1</strong>', $error_html);
                $error_html = preg_replace('/(in )(\/.*?\.php)/', '$1<span class="file">$2</span>', $error_html);
                $error_html = preg_replace('/(on line )(\d+)/', '$1<span class="line">$2</span>', $error_html);
                
                echo "<pre>$error_html</pre>";
                echo '</div>';
            }
        }
        
        echo '<hr style="border-color: #3e3e42; margin: 30px 0;">';
        
        echo '<div style="background: #252526; padding: 15px; border-radius: 4px;">';
        echo '<h3 style="color: #4ec9b0;">📋 Información del Log:</h3>';
        echo '<p>Archivo: <code>' . $log_file . '</code></p>';
        echo '<p>Tamaño: ' . number_format(filesize($log_file) / 1024, 2) . ' KB</p>';
        echo '<p>Última modificación: ' . date('Y-m-d H:i:s', filemtime($log_file)) . '</p>';
        echo '<p>Hora actual: ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p>Líneas analizadas: 500 (últimas)</p>';
        echo '</div>';
    }
    ?>
    
    <hr style="border-color: #3e3e42; margin: 30px 0;">
    
    <div style="background: #2d2d30; border: 1px solid #007acc; padding: 15px; border-radius: 4px;">
        <h3 style="color: #4fc1ff;">💡 QUÉ HACER:</h3>
        <ol style="color: #d4d4d4;">
            <li><strong>Si ves errores arriba:</strong> Cópialos TODOS y envíamelos</li>
            <li><strong>Si NO ves errores:</strong> 
                <ul>
                    <li>Intenta enviar el formulario en otra pestaña</li>
                    <li>Espera 3-5 segundos</li>
                    <li>Esta página se recargará automáticamente</li>
                    <li>El error debería aparecer</li>
                </ul>
            </li>
            <li><strong>Especialmente importante:</strong> Busca líneas que mencionen:
                <ul>
                    <li>"class-propuestas.php"</li>
                    <li>"class-shortcodes.php"</li>
                    <li>"Fatal error"</li>
                    <li>"Parse error"</li>
                </ul>
            </li>
        </ol>
    </div>
    
</body>
</html>
