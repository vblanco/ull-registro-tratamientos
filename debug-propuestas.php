<?php
/**
 * Debug de Propuestas - Ver errores del log
 * Colocar en: /wp-content/plugins/ull-registro-tratamientos/debug-propuestas.php
 * Acceder vía: /wp-content/plugins/ull-registro-tratamientos/debug-propuestas.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar permisos de administrador
if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

// Activar debug si no está activo
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug de Propuestas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #1F4E78; }
        h2 { color: #2E75B5; margin-top: 30px; }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; }
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
        .log-entry {
            padding: 8px;
            margin: 5px 0;
            border-left: 3px solid #ddd;
            background: #fafafa;
        }
        .log-error { border-left-color: #dc3545; background: #ffe6e6; }
        .log-warning { border-left-color: #ffc107; background: #fff9e6; }
        .log-success { border-left-color: #28a745; background: #e6ffe6; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1F4E78;
            color: white;
        }
        .btn {
            padding: 10px 20px;
            background: #2E75B5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #1F4E78;
        }
    </style>
</head>
<body>
    <h1>🐛 Debug del Sistema de Propuestas</h1>
    
    <div class="box info">
        <h3>ℹ️ Información</h3>
        <p>Esta página muestra información de debug para ayudar a diagnosticar problemas con el sistema de propuestas.</p>
        <p><strong>Última actualización:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
    
    <?php
    // 1. Verificar configuración de WordPress
    ?>
    <div class="box">
        <h2>⚙️ Configuración de WordPress</h2>
        <table>
            <tr>
                <th>Configuración</th>
                <th>Valor</th>
                <th>Estado</th>
            </tr>
            <tr>
                <td>WP_DEBUG</td>
                <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'; ?></td>
                <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>WP_DEBUG_LOG</td>
                <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false'; ?></td>
                <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>Archivo de log</td>
                <td><code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code></td>
                <td><?php echo file_exists(WP_CONTENT_DIR . '/debug.log') ? '✅ Existe' : '❌ No existe'; ?></td>
            </tr>
        </table>
    </div>
    
    <?php
    // 2. Verificar tabla
    global $wpdb;
    $tabla = $wpdb->prefix . 'ull_rt_propuestas';
    $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;
    
    if (!$tabla_existe):
    ?>
    <div class="box error">
        <h2>❌ Tabla NO existe</h2>
        <p>La tabla <code><?php echo $tabla; ?></code> no existe en la base de datos.</p>
        <p><strong>Solución:</strong> Desactivar y reactivar el plugin.</p>
    </div>
    <?php else: ?>
    <div class="box success">
        <h2>✅ Tabla Existe</h2>
        <p>Tabla: <code><?php echo $tabla; ?></code></p>
    </div>
    <?php endif; ?>
    
    <?php
    // 3. Verificar propuestas recientes
    if ($tabla_existe):
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
        $ultima = $wpdb->get_row("SELECT * FROM $tabla ORDER BY id DESC LIMIT 1");
    ?>
    <div class="box">
        <h2>📊 Propuestas en Base de Datos</h2>
        <p><strong>Total:</strong> <?php echo $total; ?></p>
        
        <?php if ($total > 0 && $ultima): ?>
        <h3>Última Propuesta:</h3>
        <table>
            <tr>
                <th>Campo</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>ID</td>
                <td><?php echo $ultima->id; ?></td>
            </tr>
            <tr>
                <td>Número</td>
                <td><?php echo $ultima->numero_propuesta; ?></td>
            </tr>
            <tr>
                <td>Nombre</td>
                <td><?php echo esc_html($ultima->nombre); ?></td>
            </tr>
            <tr>
                <td>Área</td>
                <td><?php echo esc_html($ultima->area_responsable); ?></td>
            </tr>
            <tr>
                <td>Solicitante</td>
                <td><?php echo esc_html($ultima->responsable_nombre); ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?php echo esc_html($ultima->responsable_email); ?></td>
            </tr>
            <tr>
                <td>Estado</td>
                <td><?php echo esc_html($ultima->estado); ?></td>
            </tr>
            <tr>
                <td>Fecha</td>
                <td><?php echo $ultima->fecha_propuesta; ?></td>
            </tr>
        </table>
        <?php else: ?>
        <p class="warning">⚠️ No hay propuestas en la base de datos aún.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php
    // 4. Leer últimas líneas del log
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)):
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $log_lines = array_reverse($log_lines);
        $log_lines = array_slice($log_lines, 0, 100); // Últimas 100 líneas
        
        // Filtrar solo líneas relacionadas con propuestas
        $propuestas_logs = array_filter($log_lines, function($line) {
            return stripos($line, 'propuesta') !== false || 
                   stripos($line, 'procesar_propuesta') !== false ||
                   stripos($line, 'crear_propuesta') !== false;
        });
    ?>
    <div class="box">
        <h2>📝 Log de Propuestas (Últimas 100 líneas)</h2>
        
        <?php if (!empty($propuestas_logs)): ?>
            <p><strong>Entradas encontradas:</strong> <?php echo count($propuestas_logs); ?></p>
            <div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa;">
                <?php foreach ($propuestas_logs as $line): 
                    $class = '';
                    if (stripos($line, 'ERROR') !== false || stripos($line, 'error_db') !== false) {
                        $class = 'log-error';
                    } elseif (stripos($line, 'WARNING') !== false) {
                        $class = 'log-warning';
                    } elseif (stripos($line, 'EXITOSO') !== false || stripos($line, 'exitosamente') !== false) {
                        $class = 'log-success';
                    }
                ?>
                <div class="log-entry <?php echo $class; ?>">
                    <code><?php echo esc_html($line); ?></code>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="warning">⚠️ No se encontraron entradas de propuestas en el log.</p>
            <p><strong>Esto puede significar:</strong></p>
            <ul>
                <li>Nadie ha intentado enviar una propuesta aún</li>
                <li>El WP_DEBUG_LOG no está activado</li>
                <li>El archivo de log fue limpiado recientemente</li>
            </ul>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="box warning">
        <h2>⚠️ Archivo de Log No Existe</h2>
        <p>El archivo <code><?php echo $log_file; ?></code> no existe.</p>
        <p><strong>Para activar el logging:</strong></p>
        <ol>
            <li>Editar <code>wp-config.php</code></li>
            <li>Añadir/asegurarse de tener estas líneas antes de <code>/* That's all, stop editing! */</code>:</li>
        </ol>
        <pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
    </div>
    <?php endif; ?>
    
    <div class="box">
        <h2>🔗 Enlaces Útiles</h2>
        <a href="<?php echo admin_url('admin.php?page=ull-rt-propuestas'); ?>" class="btn">Panel de Propuestas</a>
        <a href="<?php echo plugin_dir_url(__FILE__) . 'verificar-propuestas.php'; ?>" class="btn">Script de Verificación</a>
        <a href="?refresh=1" class="btn">🔄 Recargar</a>
    </div>
    
    <div class="box info">
        <h2>📋 Instrucciones para Debugging</h2>
        <ol>
            <li><strong>Activar WP_DEBUG_LOG</strong> si no está activo (ver arriba)</li>
            <li><strong>Ir al formulario</strong> de propuestas</li>
            <li><strong>Completar y enviar</strong> el formulario</li>
            <li><strong>Volver a esta página</strong> y recargar</li>
            <li><strong>Revisar el log</strong> para ver qué ocurrió</li>
        </ol>
        
        <h3>¿Qué buscar en el log?</h3>
        <ul>
            <li>✅ <strong>"INICIO procesar_propuesta_publica"</strong> → El formulario se envió</li>
            <li>✅ <strong>"Nonce válido"</strong> → La seguridad pasó</li>
            <li>✅ <strong>"INICIO crear_propuesta"</strong> → Empezó a crear la propuesta</li>
            <li>✅ <strong>"Propuesta creada con ID"</strong> → Se guardó en la BD</li>
            <li>✅ <strong>"FIN crear_propuesta EXITOSO"</strong> → Todo funcionó</li>
            <li>❌ <strong>"ERROR:"</strong> → Problema encontrado (leer el mensaje)</li>
        </ul>
    </div>
    
</body>
</html>
