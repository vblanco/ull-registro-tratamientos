<?php
/**
 * Reparar Tabla de Consultas DPD
 * 
 * Este script elimina y recrea la tabla con la estructura correcta
 * 
 * Subir a: /wp-content/plugins/ull-registro-tratamientos/
 * Acceder a: https://tu-sitio.com/wp-content/plugins/ull-registro-tratamientos/reparar-tabla-consultas.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que es admin
if (!current_user_can('manage_options')) {
    die('Acceso denegado');
}

global $wpdb;

echo "<h1>Reparar Tabla de Consultas DPD</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    .step { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
</style>";

$table = $wpdb->prefix . 'ull_consultas_dpd';

echo "<div class='step'>";
echo "<h2>Paso 1: Verificar tabla actual</h2>";

$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($exists === $table) {
    echo "<p class='warning'>⚠ La tabla existe pero tiene estructura incorrecta</p>";
    
    // Mostrar estructura actual
    $columns = $wpdb->get_results("DESCRIBE $table");
    echo "<p><strong>Columnas actuales:</strong></p><ul>";
    foreach ($columns as $col) {
        echo "<li>{$col->Field} ({$col->Type})</li>";
    }
    echo "</ul>";
    
    // Contar registros
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "<p><strong>Registros en la tabla:</strong> $count</p>";
    
    if ($count > 0) {
        echo "<p class='warning'>⚠ ATENCIÓN: Hay $count consultas guardadas. Se perderán al recrear la tabla.</p>";
        echo "<p>¿Deseas hacer un backup primero?</p>";
    }
} else {
    echo "<p class='error'>✗ La tabla no existe</p>";
}
echo "</div>";

// Botón para confirmar
if (!isset($_GET['confirmar'])) {
    echo "<div class='step'>";
    echo "<h2>¿Proceder con la reparación?</h2>";
    echo "<p><strong>Esta acción:</strong></p>";
    echo "<ul>";
    echo "<li>Eliminará la tabla actual (si existe)</li>";
    echo "<li>Creará una nueva tabla con la estructura correcta</li>";
    if ($count > 0) {
        echo "<li class='error'>SE PERDERÁN LOS $count REGISTROS EXISTENTES</li>";
    }
    echo "</ul>";
    echo "<p><a href='?confirmar=si' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Sí, reparar tabla</a> ";
    echo "<a href='" . admin_url('admin.php?page=ull-registro-consultas') . "' style='background: #ddd; color: #333; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Cancelar</a></p>";
    echo "</div>";
    exit;
}

// Proceso de reparación
echo "<div class='step'>";
echo "<h2>Paso 2: Eliminar tabla antigua</h2>";

$result = $wpdb->query("DROP TABLE IF EXISTS $table");

if ($result === false) {
    echo "<p class='error'>✗ Error al eliminar tabla: " . $wpdb->last_error . "</p>";
    exit;
} else {
    echo "<p class='ok'>✓ Tabla eliminada correctamente</p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Paso 3: Crear tabla nueva</h2>";

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    numero_consulta varchar(50) NOT NULL,
    nombre_solicitante varchar(255) NOT NULL,
    email_solicitante varchar(255) NOT NULL,
    departamento varchar(255) DEFAULT NULL,
    asunto varchar(255) NOT NULL,
    consulta text NOT NULL,
    respuesta text DEFAULT NULL,
    respuesta_pdf varchar(255) DEFAULT NULL,
    estado varchar(50) DEFAULT 'pendiente',
    prioridad varchar(20) DEFAULT 'normal',
    privada tinyint(1) DEFAULT 0,
    fecha_consulta datetime NOT NULL,
    fecha_respuesta datetime DEFAULT NULL,
    respondido_por bigint(20) DEFAULT NULL,
    ip_origen varchar(100) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY numero_consulta (numero_consulta),
    KEY estado (estado),
    KEY fecha_consulta (fecha_consulta)
) $charset_collate";

echo "<p><strong>SQL a ejecutar:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Verificar que se creó correctamente
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($exists === $table) {
    echo "<p class='ok'>✓ Tabla creada correctamente</p>";
} else {
    echo "<p class='error'>✗ Error: La tabla no se creó</p>";
    echo "<p>Error MySQL: " . $wpdb->last_error . "</p>";
    exit;
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Paso 4: Verificar estructura</h2>";

$columns = $wpdb->get_results("DESCRIBE $table");

$expected_columns = array(
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
    'prioridad',
    'privada',
    'fecha_consulta',
    'fecha_respuesta',
    'respondido_por',
    'ip_origen'
);

$actual_columns = array();
foreach ($columns as $col) {
    $actual_columns[] = $col->Field;
}

echo "<p><strong>Verificación de columnas:</strong></p>";
echo "<ul>";

$all_ok = true;
foreach ($expected_columns as $col) {
    if (in_array($col, $actual_columns)) {
        echo "<li class='ok'>✓ $col</li>";
    } else {
        echo "<li class='error'>✗ $col (FALTA)</li>";
        $all_ok = false;
    }
}

echo "</ul>";

if ($all_ok) {
    echo "<p class='ok'>✓ Todas las columnas están presentes</p>";
} else {
    echo "<p class='error'>✗ Faltan algunas columnas</p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Paso 5: Test de inserción</h2>";

$test_data = array(
    'numero_consulta' => 'TEST-' . time(),
    'nombre_solicitante' => 'Usuario Test',
    'email_solicitante' => 'test@example.com',
    'departamento' => 'Test',
    'asunto' => 'Consulta de prueba',
    'consulta' => 'Este es un mensaje de prueba para verificar que la tabla funciona correctamente después de la reparación.',
    'estado' => 'pendiente',
    'fecha_consulta' => current_time('mysql'),
    'ip_origen' => '127.0.0.1'
);

$result = $wpdb->insert($table, $test_data);

if ($result === false) {
    echo "<p class='error'>✗ Error al insertar registro de prueba</p>";
    echo "<p>Error: " . $wpdb->last_error . "</p>";
} else {
    $insert_id = $wpdb->insert_id;
    echo "<p class='ok'>✓ Inserción exitosa (ID: $insert_id)</p>";
    
    // Eliminar el registro de prueba
    $wpdb->delete($table, array('id' => $insert_id));
    echo "<p>ℹ Registro de prueba eliminado</p>";
}
echo "</div>";

echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>";
echo "<h2>✓ Reparación Completada</h2>";
echo "<p><strong>La tabla de consultas DPD ha sido reparada correctamente.</strong></p>";
echo "<p>Ahora puedes:</p>";
echo "<ul>";
echo "<li>Probar el formulario de consultas: <code>[ull_consulta_dpd]</code></li>";
echo "<li>Gestionar consultas desde: <a href='" . admin_url('admin.php?page=ull-registro-consultas') . "'>Panel de Consultas DPD</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=ull-registro-consultas') . "' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Ir al Panel de Consultas DPD</a></p>";
