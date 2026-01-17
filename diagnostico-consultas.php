<?php
/**
 * Diagnóstico de Tabla de Consultas DPD
 * 
 * Subir este archivo a /wp-content/plugins/ull-registro-tratamientos/
 * Acceder a: https://tu-sitio.com/wp-content/plugins/ull-registro-tratamientos/diagnostico-consultas.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que es admin
if (!current_user_can('manage_options')) {
    die('Acceso denegado');
}

global $wpdb;

echo "<h1>Diagnóstico de Tabla de Consultas DPD</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    table { border-collapse: collapse; margin: 10px 0; }
    table td, table th { border: 1px solid #ddd; padding: 8px; }
    table th { background: #0073aa; color: white; }
</style>";

$table = $wpdb->prefix . 'ull_consultas_dpd';

echo "<h2>1. Verificación de Tabla</h2>";

// Comprobar si existe
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($exists === $table) {
    echo "<p class='ok'>✓ La tabla existe: $table</p>";
} else {
    echo "<p class='error'>✗ La tabla NO existe: $table</p>";
    echo "<p>Intentando crear la tabla...</p>";
    
    // Intentar crear
    require_once(WP_PLUGIN_DIR . '/ull-registro-tratamientos/includes/class-database.php');
    $db = ULL_RT_Database::get_instance();
    $db->crear_tablas();
    
    // Verificar de nuevo
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists === $table) {
        echo "<p class='ok'>✓ Tabla creada correctamente</p>";
    } else {
        echo "<p class='error'>✗ No se pudo crear la tabla</p>";
        echo "<p>Error de MySQL: " . $wpdb->last_error . "</p>";
        exit;
    }
}

echo "<h2>2. Estructura de la Tabla</h2>";

$columns = $wpdb->get_results("DESCRIBE $table");

echo "<table>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";

foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col->Field}</td>";
    echo "<td>{$col->Type}</td>";
    echo "<td>{$col->Null}</td>";
    echo "<td>{$col->Key}</td>";
    echo "<td>{$col->Default}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>3. Campos Esperados vs Reales</h2>";

$expected_fields = array(
    'id',
    'numero_consulta',
    'nombre_solicitante',
    'email_solicitante',
    'departamento',
    'asunto',
    'consulta',
    'respuesta',
    'respuesta_pdf',
    'estado',
    'fecha_consulta',
    'fecha_respuesta',
    'respondido_por',
    'ip_origen'
);

$actual_fields = array();
foreach ($columns as $col) {
    $actual_fields[] = $col->Field;
}

echo "<table>";
echo "<tr><th>Campo</th><th>Estado</th></tr>";

foreach ($expected_fields as $field) {
    $status = in_array($field, $actual_fields) ? "<span class='ok'>✓ Presente</span>" : "<span class='error'>✗ Falta</span>";
    echo "<tr><td>$field</td><td>$status</td></tr>";
}

echo "</table>";

echo "<h2>4. Test de Inserción</h2>";

$test_data = array(
    'numero_consulta' => 'TEST-' . time(),
    'nombre_solicitante' => 'Usuario Test',
    'email_solicitante' => 'test@example.com',
    'departamento' => 'Estudiante',
    'asunto' => 'Consulta de prueba',
    'consulta' => 'Este es un mensaje de prueba para verificar que la tabla funciona correctamente. Tiene más de 50 caracteres.',
    'estado' => 'pendiente',
    'fecha_consulta' => current_time('mysql'),
    'ip_origen' => '127.0.0.1'
);

echo "<p>Intentando insertar registro de prueba...</p>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

$result = $wpdb->insert($table, $test_data);

if ($result === false) {
    echo "<p class='error'>✗ Error al insertar</p>";
    echo "<p>Error MySQL: " . $wpdb->last_error . "</p>";
    echo "<p>Query ejecutado: " . $wpdb->last_query . "</p>";
} else {
    echo "<p class='ok'>✓ Inserción exitosa</p>";
    $insert_id = $wpdb->insert_id;
    echo "<p>ID insertado: $insert_id</p>";
    
    // Eliminar el registro de prueba
    $wpdb->delete($table, array('id' => $insert_id));
    echo "<p class='warning'>ℹ Registro de prueba eliminado</p>";
}

echo "<h2>5. Registros Actuales</h2>";

$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "<p>Total de consultas en la tabla: <strong>$count</strong></p>";

if ($count > 0) {
    $recent = $wpdb->get_results("SELECT * FROM $table ORDER BY fecha_consulta DESC LIMIT 5");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Nombre</th><th>Asunto</th><th>Estado</th><th>Fecha</th></tr>";
    
    foreach ($recent as $row) {
        echo "<tr>";
        echo "<td>{$row->id}</td>";
        echo "<td>{$row->numero_consulta}</td>";
        echo "<td>{$row->nombre_solicitante}</td>";
        echo "<td>{$row->asunto}</td>";
        echo "<td>{$row->estado}</td>";
        echo "<td>{$row->fecha_consulta}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<h2>6. Permisos de WordPress</h2>";

echo "<p>Usuario actual: " . wp_get_current_user()->user_login . "</p>";
echo "<p>Puede gestionar opciones: " . (current_user_can('manage_options') ? '<span class="ok">Sí</span>' : '<span class="error">No</span>') . "</p>";

echo "<h2>7. Configuración MySQL</h2>";

echo "<table>";
echo "<tr><th>Variable</th><th>Valor</th></tr>";
echo "<tr><td>Prefijo de tablas</td><td>{$wpdb->prefix}</td></tr>";
echo "<tr><td>Nombre completo tabla</td><td>$table</td></tr>";
echo "<tr><td>Charset</td><td>" . $wpdb->charset . "</td></tr>";
echo "<tr><td>Collate</td><td>" . $wpdb->collate . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=ull-registro-consultas') . "'>← Volver al panel de Consultas DPD</a></p>";
