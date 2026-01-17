<?php
/**
 * DEBUG EN TIEMPO REAL - VER LOG MIENTRAS ENVÍAS
 * 
 * Este script muestra el log en tiempo real mientras envías el formulario
 * 
 * INSTRUCCIONES:
 * 1. Abrir esta página en una pestaña
 * 2. En otra pestaña, ir al formulario y enviarlo
 * 3. Volver a esta pestaña y recargar
 * 4. Ver qué error apareció
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

// Función para leer últimas líneas del log
function tail_log($file, $lines = 100) {
    if (!file_exists($file)) {
        return array();
    }
    
    $content = file_get_contents($file);
    $log_lines = explode("\n", $content);
    $log_lines = array_reverse($log_lines);
    $log_lines = array_slice($log_lines, 0, $lines);
    return array_reverse($log_lines);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Debug en Tiempo Real</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        h1 {
            color: #4ec9b0;
            font-size: 18px;
            margin: 0 0 20px 0;
        }
        .timestamp {
            color: #4fc1ff;
            display: inline-block;
            min-width: 200px;
        }
        .info { color: #d4d4d4; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #dcdcaa; }
        .success { color: #4ec9b0; }
        .separator {
            border-top: 2px solid #4ec9b0;
            margin: 20px 0;
        }
        .line {
            padding: 4px 0;
            line-height: 1.4;
        }
        .highlight {
            background: #264f78;
            padding: 2px 4px;
        }
        .box {
            background: #252526;
            border: 1px solid #3e3e42;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .counter {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007acc;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="counter">
        🔄 Auto-recarga: 5s
    </div>
    
    <h1>📊 DEBUG EN TIEMPO REAL - Log de WordPress</h1>
    
    <div class="box">
        <strong>INSTRUCCIONES:</strong><br>
        1. Deja esta pestaña abierta (se recarga automáticamente cada 5 segundos)<br>
        2. En otra pestaña, ve al formulario y envía una propuesta<br>
        3. Vuelve aquí y busca los errores en rojo<br>
        4. Copia TODO el bloque de error
    </div>
    
    <div class="separator"></div>
    
    <?php
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if (!file_exists($log_file)) {
        echo '<div class="box error">';
        echo '<strong>⚠️ ADVERTENCIA:</strong> El archivo debug.log no existe.<br><br>';
        echo 'Para activar el logging, edita <strong>wp-config.php</strong> y añade:<br><br>';
        echo '<pre style="background:#1e1e1e; padding:10px; border:1px solid #3e3e42;">';
        echo "define('WP_DEBUG', true);\n";
        echo "define('WP_DEBUG_LOG', true);\n";
        echo "define('WP_DEBUG_DISPLAY', false);";
        echo '</pre>';
        echo '</div>';
    } else {
        $log_lines = tail_log($log_file, 200);
        
        // Filtrar solo líneas recientes (últimos 5 minutos)
        $time_threshold = time() - 300;
        
        // Buscar líneas relacionadas con propuestas
        $relevant_lines = array();
        foreach ($log_lines as $line) {
            if (empty(trim($line))) continue;
            
            // Detectar si es relevante
            if (stripos($line, 'propuesta') !== false ||
                stripos($line, 'ull_rt') !== false ||
                stripos($line, 'crear_propuesta') !== false ||
                stripos($line, 'procesar_propuesta') !== false ||
                stripos($line, 'ERROR') !== false ||
                stripos($line, 'error_db') !== false ||
                stripos($line, 'INICIO') !== false ||
                stripos($line, 'FIN') !== false) {
                $relevant_lines[] = $line;
            }
        }
        
        if (empty($relevant_lines)) {
            echo '<div class="box warning">';
            echo '<strong>⚠️ No se encontraron entradas recientes</strong><br><br>';
            echo 'Esto puede significar:<br>';
            echo '• Nadie ha enviado el formulario recientemente<br>';
            echo '• El formulario no está llamando al backend<br>';
            echo '• El log se limpió recientemente<br><br>';
            echo '<strong>ACCIÓN:</strong> Envía el formulario AHORA y recarga esta página en 5 segundos.';
            echo '</div>';
        } else {
            echo '<div class="box success">';
            echo '<strong>✅ Se encontraron ' . count($relevant_lines) . ' entradas relacionadas</strong>';
            echo '</div>';
            
            echo '<div class="separator"></div>';
            echo '<h2 style="color:#4ec9b0; font-size:16px;">📝 ÚLTIMAS ENTRADAS DEL LOG:</h2>';
            
            foreach ($relevant_lines as $line) {
                // Detectar tipo de línea
                $class = 'info';
                if (stripos($line, 'ERROR') !== false || stripos($line, 'error_db') !== false) {
                    $class = 'error';
                } elseif (stripos($line, 'WARNING') !== false) {
                    $class = 'warning';
                } elseif (stripos($line, 'EXITOSO') !== false || stripos($line, 'exitosamente') !== false) {
                    $class = 'success';
                }
                
                // Resaltar partes importantes
                $line = htmlspecialchars($line);
                $line = preg_replace('/(ERROR[^:]*:)/i', '<span class="highlight">$1</span>', $line);
                $line = preg_replace('/(INICIO [^=]+===)/i', '<span class="highlight">$1</span>', $line);
                $line = preg_replace('/(FIN [^=]+===)/i', '<span class="highlight">$1</span>', $line);
                
                echo "<div class='line $class'>$line</div>";
            }
        }
        
        echo '<div class="separator"></div>';
        echo '<div class="box">';
        echo '<strong>📋 INFORMACIÓN DEL LOG:</strong><br>';
        echo 'Archivo: <code>' . $log_file . '</code><br>';
        echo 'Tamaño: ' . number_format(filesize($log_file) / 1024, 2) . ' KB<br>';
        echo 'Última modificación: ' . date('Y-m-d H:i:s', filemtime($log_file)) . '<br>';
        echo 'Hora actual: ' . date('Y-m-d H:i:s') . '<br>';
        echo '</div>';
    }
    ?>
    
    <div class="separator"></div>
    
    <div class="box">
        <strong>🔍 QUÉ BUSCAR:</strong><br><br>
        
        <div style="margin: 10px 0;">
            <strong class="success">✅ Flujo exitoso (esto es lo que debería verse):</strong><br>
            <code>
            === INICIO procesar_propuesta_publica ===<br>
            Nonce válido, continuando...<br>
            Campos básicos validados<br>
            === INICIO crear_propuesta ===<br>
            Propuesta creada con ID: X<br>
            === FIN crear_propuesta EXITOSO ===
            </code>
        </div>
        
        <div style="margin: 10px 0;">
            <strong class="error">❌ Si hay error, busca líneas como:</strong><br>
            <code class="error">
            ERROR DB: ...<br>
            Query: INSERT INTO ...<br>
            ERROR: Campo requerido vacío<br>
            ERROR: Email inválido
            </code>
        </div>
        
        <div style="margin: 10px 0;">
            <strong class="warning">⚠️ Si NO ves nada:</strong><br>
            Significa que el formulario no está llegando al PHP.<br>
            Problema: JavaScript, nonce, o configuración del formulario.
        </div>
    </div>
    
    <div class="box" style="background: #2d2d30; border-color: #007acc;">
        <strong style="color: #4fc1ff;">💡 PRÓXIMOS PASOS:</strong><br><br>
        1. Envía el formulario en otra pestaña<br>
        2. Espera 5 segundos (o recarga manualmente)<br>
        3. Busca líneas en ROJO (errores)<br>
        4. Copia TODO el bloque de error<br>
        5. Envíamelo
    </div>
    
</body>
</html>
