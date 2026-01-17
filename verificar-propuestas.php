<?php
/**
 * Script de Verificación de Propuestas
 * Colocar en: /wp-content/plugins/ull-registro-tratamientos/verificar-propuestas.php
 * Acceder vía: /wp-content/plugins/ull-registro-tratamientos/verificar-propuestas.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar permisos de administrador
if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificación de Propuestas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #1F4E78;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1F4E78;
            color: white;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-pendiente {
            background: #ffc107;
            color: #000;
        }
        .badge-aprobada {
            background: #28a745;
            color: white;
        }
        .badge-denegada {
            background: #dc3545;
            color: white;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🔍 Verificación del Sistema de Propuestas</h1>
    
    <?php
    global $wpdb;
    $tabla_propuestas = $wpdb->prefix . 'ull_rt_propuestas';
    
    // 1. Verificar que existe la tabla
    $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_propuestas'") === $tabla_propuestas;
    
    if (!$tabla_existe) {
        ?>
        <div class="info-box error">
            <h3>❌ Tabla de Propuestas NO existe</h3>
            <p>La tabla <code><?php echo $tabla_propuestas; ?></code> no se encuentra en la base de datos.</p>
            <p><strong>Solución:</strong></p>
            <ol>
                <li>Desactiva el plugin</li>
                <li>Vuelve a activar el plugin (esto creará las tablas)</li>
                <li>Recarga esta página</li>
            </ol>
        </div>
        <?php
        exit;
    }
    ?>
    
    <div class="info-box success">
        <h3>✅ Tabla de Propuestas existe correctamente</h3>
        <p>Tabla: <code><?php echo $tabla_propuestas; ?></code></p>
    </div>
    
    <?php
    // 2. Verificar estructura de la tabla
    $columnas = $wpdb->get_results("SHOW COLUMNS FROM $tabla_propuestas");
    ?>
    
    <div class="info-box">
        <h3>📋 Estructura de la Tabla</h3>
        <table>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Key</th>
            </tr>
            <?php foreach ($columnas as $columna): ?>
            <tr>
                <td><code><?php echo $columna->Field; ?></code></td>
                <td><?php echo $columna->Type; ?></td>
                <td><?php echo $columna->Null; ?></td>
                <td><?php echo $columna->Key; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <?php
    // 3. Contar propuestas
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas");
    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'pendiente'");
    $aprobadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'aprobada'");
    $denegadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'denegada'");
    ?>
    
    <div class="info-box">
        <h3>📊 Estadísticas de Propuestas</h3>
        <table>
            <tr>
                <th>Estado</th>
                <th>Cantidad</th>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td><strong><?php echo $total; ?></strong></td>
            </tr>
            <tr>
                <td>Pendientes</td>
                <td><?php echo $pendientes; ?></td>
            </tr>
            <tr>
                <td>Aprobadas</td>
                <td><?php echo $aprobadas; ?></td>
            </tr>
            <tr>
                <td>Denegadas</td>
                <td><?php echo $denegadas; ?></td>
            </tr>
        </table>
    </div>
    
    <?php
    if ($total == 0) {
        ?>
        <div class="info-box warning">
            <h3>⚠️ No hay propuestas en la base de datos</h3>
            <p>Esto puede significar:</p>
            <ul>
                <li>Nadie ha enviado propuestas aún</li>
                <li>Hay un problema con el formulario de envío</li>
            </ul>
            <p><strong>Para probar:</strong></p>
            <ol>
                <li>Ve a la página con el shortcode <code>[ull_proponer_tratamiento]</code></li>
                <li>Completa y envía el formulario de prueba</li>
                <li>Vuelve a esta página para verificar</li>
            </ol>
        </div>
        <?php
    } else {
        // 4. Mostrar últimas propuestas
        $propuestas = $wpdb->get_results("SELECT * FROM $tabla_propuestas ORDER BY fecha_propuesta DESC LIMIT 10");
        ?>
        <div class="info-box success">
            <h3>✅ Últimas 10 Propuestas Registradas</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Solicitante</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
                <?php foreach ($propuestas as $prop): 
                    $badge_class = 'badge-' . $prop->estado;
                    $estado_texto = '';
                    switch ($prop->estado) {
                        case 'pendiente': $estado_texto = '⏳ Pendiente'; break;
                        case 'aprobada': $estado_texto = '✅ Aprobada'; break;
                        case 'denegada': $estado_texto = '❌ Denegada'; break;
                        case 'modificaciones_requeridas': $estado_texto = '🔄 Modificaciones'; break;
                    }
                ?>
                <tr>
                    <td><?php echo $prop->id; ?></td>
                    <td><code><?php echo $prop->numero_propuesta; ?></code></td>
                    <td><?php echo esc_html(substr($prop->nombre, 0, 40)); ?><?php echo strlen($prop->nombre) > 40 ? '...' : ''; ?></td>
                    <td><?php echo esc_html($prop->area_responsable); ?></td>
                    <td><?php echo esc_html($prop->responsable_nombre); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($prop->fecha_propuesta)); ?></td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $estado_texto; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }
    ?>
    
    <div class="info-box">
        <h3>🔗 Enlaces Útiles</h3>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=ull-rt-propuestas'); ?>">Panel de Administración de Propuestas</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=ull-registro-rgpd'); ?>">Dashboard Principal</a></li>
            <li><a href="<?php echo admin_url('plugins.php'); ?>">Gestión de Plugins</a></li>
        </ul>
    </div>
    
    <div class="info-box">
        <h3>🛠️ Información Técnica</h3>
        <ul>
            <li><strong>Plugin activo:</strong> <?php echo is_plugin_active('ull-registro-tratamientos/ull-registro-tratamientos.php') ? '✅ Sí' : '❌ No'; ?></li>
            <li><strong>Versión WordPress:</strong> <?php echo get_bloginfo('version'); ?></li>
            <li><strong>Versión PHP:</strong> <?php echo phpversion(); ?></li>
            <li><strong>Tabla propuestas:</strong> <code><?php echo $tabla_propuestas; ?></code></li>
        </ul>
    </div>
    
    <p style="text-align: center; color: #666; margin-top: 40px;">
        <small>Script de verificación - Universidad de La Laguna</small>
    </p>
</body>
</html>
