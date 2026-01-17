<?php
/**
 * DIAGNÓSTICO AUTOMÁTICO DE PROPUESTAS
 * 
 * Este archivo hace una prueba completa del sistema y te dice exactamente qué falla.
 * 
 * INSTRUCCIONES:
 * 1. Subir este archivo a: /wp-content/plugins/ull-registro-tratamientos/
 * 2. Acceder a: https://tu-sitio.es/wp-content/plugins/ull-registro-tratamientos/diagnostico-automatico.php
 * 3. Hacer clic en "Ejecutar Prueba Completa"
 * 4. Copiar el resultado y enviarlo
 */

// Cargar WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Acceso denegado. Solo administradores.');
}

// FORZAR LA CARGA DEL PLUGIN
if (!class_exists('ULL_RT_Propuestas')) {
    // Cargar manualmente las clases necesarias
    $plugin_dir = dirname(__FILE__) . '/';
    
    if (file_exists($plugin_dir . 'includes/class-database.php')) {
        require_once $plugin_dir . 'includes/class-database.php';
    }
    if (file_exists($plugin_dir . 'includes/class-audit-log.php')) {
        require_once $plugin_dir . 'includes/class-audit-log.php';
    }
    if (file_exists($plugin_dir . 'includes/class-propuestas.php')) {
        require_once $plugin_dir . 'includes/class-propuestas.php';
    }
}

// Ejecutar prueba si se solicita
$test_ejecutado = false;
$resultados = array();

if (isset($_GET['ejecutar_test'])) {
    $test_ejecutado = true;
    
    // TEST 1: Verificar que la clase existe
    $resultados['clase_existe'] = class_exists('ULL_RT_Propuestas');
    $resultados['clase_cargada_manualmente'] = false;
    
    // Si no existe, intentar cargarla manualmente
    if (!$resultados['clase_existe']) {
        $plugin_dir = dirname(__FILE__) . '/';
        if (file_exists($plugin_dir . 'includes/class-propuestas.php')) {
            require_once $plugin_dir . 'includes/class-propuestas.php';
            $resultados['clase_existe'] = class_exists('ULL_RT_Propuestas');
            $resultados['clase_cargada_manualmente'] = true;
        }
    }
    
    // TEST 2: Verificar que la tabla existe
    global $wpdb;
    $tabla = $wpdb->prefix . 'ull_rt_propuestas';
    $resultados['tabla_existe'] = $wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;
    $resultados['nombre_tabla'] = $tabla;
    
    // TEST 3: Intentar crear una propuesta de prueba
    if ($resultados['clase_existe']) {
        try {
            $propuestas = ULL_RT_Propuestas::get_instance();
            
            $datos_prueba = array(
                'nombre' => 'PRUEBA AUTOMATICA - ' . date('Y-m-d H:i:s'),
                'area_responsable' => 'Rectorado',
                'finalidad' => 'Esta es una prueba automática del sistema',
                'base_juridica' => array('RGPD 6.1.e) Misión de interés público'),
                'colectivos' => array('Estudiantes de grado'),
                'categorias_datos' => array('Datos identificativos (nombre, DNI, dirección, teléfono, email)'),
                'cesiones_select' => 'No previstas',
                'transferencias_internacionales' => 'No previstas',
                'plazo_conservacion_select' => 'Durante la relación + 5 años',
                'medidas_seguridad' => 'Conforme al Esquema Nacional de Seguridad (ENS)',
                'justificacion' => 'Prueba automática del sistema de propuestas',
                'responsable_nombre' => 'Test Automatico',
                'responsable_cargo' => 'Administrador de Sistemas',
                'responsable_email' => 'test@ull.es',
                'responsable_telefono' => '922000000'
            );
            
            $resultado = $propuestas->crear_propuesta($datos_prueba);
            
            if (is_wp_error($resultado)) {
                $resultados['prueba_creacion'] = false;
                $resultados['error_mensaje'] = $resultado->get_error_message();
                $resultados['error_codigo'] = $resultado->get_error_code();
            } else {
                $resultados['prueba_creacion'] = true;
                $resultados['propuesta_id'] = $resultado['propuesta_id'];
                $resultados['numero_propuesta'] = $resultado['numero_propuesta'];
                
                // Verificar que realmente se guardó
                $guardada = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $tabla WHERE id = %d",
                    $resultado['propuesta_id']
                ));
                $resultados['guardada_en_bd'] = !is_null($guardada);
                
                if ($guardada) {
                    $resultados['datos_guardados'] = array(
                        'id' => $guardada->id,
                        'numero' => $guardada->numero_propuesta,
                        'nombre' => $guardada->nombre,
                        'estado' => $guardada->estado
                    );
                }
            }
        } catch (Exception $e) {
            $resultados['prueba_creacion'] = false;
            $resultados['error_excepcion'] = $e->getMessage();
        }
    } else {
        $resultados['prueba_creacion'] = false;
        $resultados['error_mensaje'] = 'La clase ULL_RT_Propuestas no existe';
    }
    
    // TEST 4: Verificar estructura de la tabla
    if ($resultados['tabla_existe']) {
        $columnas = $wpdb->get_results("SHOW COLUMNS FROM $tabla");
        $resultados['columnas'] = array();
        foreach ($columnas as $col) {
            $resultados['columnas'][$col->Field] = array(
                'tipo' => $col->Type,
                'null' => $col->Null,
                'key' => $col->Key
            );
        }
        $resultados['num_columnas'] = count($columnas);
    }
    
    // TEST 5: Error del último query
    if (!empty($wpdb->last_error)) {
        $resultados['ultimo_error_db'] = $wpdb->last_error;
        $resultados['ultimo_query'] = $wpdb->last_query;
    }
    
    // TEST 6: Verificar archivos del plugin
    $plugin_dir = dirname(__FILE__) . '/';
    $resultados['archivos_plugin'] = array(
        'class-propuestas.php' => file_exists($plugin_dir . 'includes/class-propuestas.php'),
        'class-database.php' => file_exists($plugin_dir . 'includes/class-database.php'),
        'class-audit-log.php' => file_exists($plugin_dir . 'includes/class-audit-log.php'),
        'plugin_principal' => file_exists($plugin_dir . 'ull-registro-tratamientos.php')
    );
    
    // TEST 7: Verificar que el plugin está activo
    $resultados['plugin_activo'] = is_plugin_active('ull-registro-tratamientos/ull-registro-tratamientos.php');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Automático - Propuestas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
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
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #2E75B5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #1F4E78; }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
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
        .resultado {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .resultado.ok {
            background: #d4edda;
            color: #155724;
        }
        .resultado.fail {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>🔬 Diagnóstico Automático del Sistema de Propuestas</h1>
    
    <div class="box">
        <h2>📋 Información</h2>
        <p>Esta herramienta ejecutará una <strong>prueba completa automática</strong> del sistema de propuestas.</p>
        <p>Creará una propuesta de prueba y verificará si se guarda correctamente.</p>
        
        <?php if (!$test_ejecutado): ?>
            <p><a href="?ejecutar_test=1" class="btn">▶️ Ejecutar Prueba Completa</a></p>
        <?php endif; ?>
    </div>
    
    <?php if ($test_ejecutado): ?>
        
        <div class="box">
            <h2>✅ Resultados de la Prueba</h2>
            
            <div class="resultado <?php echo $resultados['plugin_activo'] ? 'ok' : 'fail'; ?>">
                <?php echo $resultados['plugin_activo'] ? '✅' : '❌'; ?> 
                TEST 0: El plugin <?php echo $resultados['plugin_activo'] ? 'ESTÁ ACTIVO' : 'NO ESTÁ ACTIVO'; ?>
            </div>
            
            <?php if (!$resultados['plugin_activo']): ?>
                <div class="box error">
                    <h3>❌ ERROR CRÍTICO</h3>
                    <p><strong>El plugin NO está activo.</strong></p>
                    <p><strong>SOLUCIÓN:</strong></p>
                    <ol>
                        <li>Ir a WordPress → Plugins</li>
                        <li>Buscar "ULL Registro Tratamientos RGPD"</li>
                        <li>Hacer clic en "Activar"</li>
                        <li>Volver a esta página y ejecutar de nuevo</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="resultado <?php echo $resultados['clase_existe'] ? 'ok' : 'fail'; ?>">
                <?php echo $resultados['clase_existe'] ? '✅' : '❌'; ?> 
                TEST 1: La clase ULL_RT_Propuestas <?php echo $resultados['clase_existe'] ? 'EXISTE' : 'NO EXISTE'; ?>
                <?php if (isset($resultados['clase_cargada_manualmente']) && $resultados['clase_cargada_manualmente']): ?>
                    <br><small>(Cargada manualmente para este test)</small>
                <?php endif; ?>
            </div>
            
            <?php if (!$resultados['clase_existe']): ?>
                <div class="box error">
                    <h3>❌ ERROR: Clase no existe</h3>
                    <p><strong>Archivos del plugin:</strong></p>
                    <ul>
                        <?php foreach ($resultados['archivos_plugin'] as $archivo => $existe): ?>
                            <li><?php echo $existe ? '✅' : '❌'; ?> <?php echo $archivo; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>SOLUCIÓN:</strong></p>
                    <ol>
                        <li>Verificar que todos los archivos están presentes</li>
                        <li>Desactivar y reactivar el plugin</li>
                        <li>Si el problema persiste, reinstalar el plugin</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="resultado <?php echo $resultados['tabla_existe'] ? 'ok' : 'fail'; ?>">
                <?php echo $resultados['tabla_existe'] ? '✅' : '❌'; ?> 
                TEST 2: La tabla de propuestas <?php echo $resultados['tabla_existe'] ? 'EXISTE' : 'NO EXISTE'; ?>
                <br><small>Tabla: <?php echo $resultados['nombre_tabla']; ?></small>
            </div>
            
            <?php if (!$resultados['tabla_existe']): ?>
                <div class="box error">
                    <h3>❌ ERROR: Tabla no existe</h3>
                    <p><strong>SOLUCIÓN:</strong></p>
                    <ol>
                        <li>Desactivar el plugin</li>
                        <li>Reactivar el plugin (esto crea las tablas)</li>
                        <li>Volver a ejecutar este test</li>
                    </ol>
                </div>
            <?php elseif (isset($resultados['num_columnas'])): ?>
                <div class="box success">
                    <p>✅ Tabla encontrada con <?php echo $resultados['num_columnas']; ?> columnas</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($resultados['prueba_creacion'])): ?>
                <div class="resultado <?php echo $resultados['prueba_creacion'] ? 'ok' : 'fail'; ?>">
                    <?php echo $resultados['prueba_creacion'] ? '✅' : '❌'; ?> 
                    TEST 3: Creación de propuesta de prueba 
                    <?php echo $resultados['prueba_creacion'] ? 'EXITOSA' : 'FALLÓ'; ?>
                </div>
                
                <?php if ($resultados['prueba_creacion']): ?>
                    <div class="resultado <?php echo $resultados['guardada_en_bd'] ? 'ok' : 'fail'; ?>">
                        <?php echo $resultados['guardada_en_bd'] ? '✅' : '❌'; ?> 
                        TEST 4: Propuesta <?php echo $resultados['guardada_en_bd'] ? 'SE GUARDÓ' : 'NO SE GUARDÓ'; ?> en la base de datos
                    </div>
                    
                    <?php if ($resultados['guardada_en_bd']): ?>
                        <div class="box success">
                            <h3>🎉 ¡ÉXITO TOTAL!</h3>
                            <p><strong>El sistema funciona correctamente.</strong></p>
                            <p><strong>Propuesta creada:</strong></p>
                            <ul>
                                <li>ID: <?php echo $resultados['propuesta_id']; ?></li>
                                <li>Número: <?php echo $resultados['numero_propuesta']; ?></li>
                            </ul>
                            <p>Si la prueba automática funciona pero el formulario no, el problema está en:</p>
                            <ul>
                                <li>El formulario HTML (campos mal nombrados)</li>
                                <li>JavaScript del formulario</li>
                                <li>La validación del nonce</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="box error">
                        <h3>❌ Error al Crear la Propuesta</h3>
                        <p><strong>Código de error:</strong> <?php echo $resultados['error_codigo']; ?></p>
                        <p><strong>Mensaje:</strong> <?php echo $resultados['error_mensaje']; ?></p>
                        
                        <?php if (isset($resultados['ultimo_error_db'])): ?>
                            <h4>Error de Base de Datos:</h4>
                            <pre><?php echo htmlspecialchars($resultados['ultimo_error_db']); ?></pre>
                            <h4>Query:</h4>
                            <pre><?php echo htmlspecialchars($resultados['ultimo_query']); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($resultados['tabla_existe'] && isset($resultados['columnas'])): ?>
        <div class="box">
            <h2>🗂️ Estructura de la Tabla</h2>
            <table>
                <tr>
                    <th>Campo</th>
                    <th>Tipo</th>
                    <th>Permite NULL</th>
                    <th>Key</th>
                </tr>
                <?php foreach ($resultados['columnas'] as $nombre => $info): ?>
                <tr>
                    <td><code><?php echo $nombre; ?></code></td>
                    <td><?php echo $info['tipo']; ?></td>
                    <td><?php echo $info['null']; ?></td>
                    <td><?php echo $info['key']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="box">
            <h2>📋 Copiar y Enviar</h2>
            <p>Copia TODO el siguiente bloque y envíalo:</p>
            <pre><?php 
                echo "=== DIAGNÓSTICO AUTOMÁTICO ===\n";
                echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
                echo "WordPress: " . get_bloginfo('version') . "\n";
                echo "PHP: " . phpversion() . "\n";
                echo "\n=== RESULTADOS ===\n";
                print_r($resultados);
            ?></pre>
        </div>
        
        <div class="box">
            <p><a href="?" class="btn">🔄 Ejecutar de Nuevo</a></p>
        </div>
        
    <?php endif; ?>
    
    <div class="box warning">
        <h2>⚠️ Importante</h2>
        <p>Si la prueba automática <strong>FUNCIONA</strong> pero el formulario web no, entonces:</p>
        <ul>
            <li>El backend (PHP/BD) está bien</li>
            <li>El problema está en el frontend (HTML/JavaScript)</li>
        </ul>
        <p>En ese caso, necesitaré ver el código HTML del formulario.</p>
    </div>
    
</body>
</html>
