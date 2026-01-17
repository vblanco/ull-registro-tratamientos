<?php
/**
 * VERIFICACIÓN RÁPIDA DE ERRORES DE ACTIVACIÓN
 * 
 * Este script intenta cargar el plugin manualmente para ver errores
 */

// Activar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #1e1e1e; color: #4ec9b0; padding: 20px; font-family: monospace;'>";
echo "=== VERIFICACIÓN DE PLUGIN ===\n\n";

// Verificar que existe el archivo principal
$plugin_file = __DIR__ . '/ull-registro-tratamientos.php';
echo "1. Verificando archivo principal...\n";
if (file_exists($plugin_file)) {
    echo "   ✅ Archivo existe: $plugin_file\n\n";
} else {
    echo "   ❌ Archivo NO existe: $plugin_file\n\n";
    die("ERROR: No se encuentra el archivo principal del plugin");
}

// Intentar cargar el archivo
echo "2. Intentando cargar el archivo principal...\n";
try {
    ob_start();
    include_once $plugin_file;
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "   ⚠️ El archivo produjo output:\n";
        echo "   " . str_replace("\n", "\n   ", $output) . "\n\n";
    } else {
        echo "   ✅ Archivo cargado sin output\n\n";
    }
} catch (ParseError $e) {
    echo "   ❌ ERROR DE SINTAXIS:\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Mensaje: " . $e->getMessage() . "\n\n";
    die();
} catch (Error $e) {
    echo "   ❌ ERROR FATAL:\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Mensaje: " . $e->getMessage() . "\n\n";
    die();
} catch (Exception $e) {
    echo "   ❌ EXCEPCIÓN:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n\n";
    die();
}

// Verificar que las clases se definieron
echo "3. Verificando clases principales...\n";
$required_classes = array(
    'ULL_Registro_Tratamientos',
    'ULL_RT_Database',
    'ULL_RT_Admin_Menu',
    'ULL_RT_Tratamientos',
    'ULL_RT_Propuestas',
    'ULL_RT_Consultas_DPD',
    'ULL_RT_Ejercicio_Derechos',
    'ULL_RT_Audit_Log',
    'ULL_RT_PDF_Generator',
    'ULL_RT_Shortcodes'
);

$missing_classes = array();
foreach ($required_classes as $class) {
    if (class_exists($class)) {
        echo "   ✅ $class\n";
    } else {
        echo "   ❌ $class - NO ENCONTRADA\n";
        $missing_classes[] = $class;
    }
}

if (!empty($missing_classes)) {
    echo "\n   ⚠️ Faltan " . count($missing_classes) . " clases\n\n";
} else {
    echo "\n   ✅ Todas las clases están definidas\n\n";
}

// Verificar archivos include
echo "4. Verificando archivos includes...\n";
$includes_dir = __DIR__ . '/includes/';
$required_files = array(
    'class-database.php',
    'class-admin-menu.php',
    'class-tratamientos.php',
    'class-propuestas.php',
    'class-consultas-dpd.php',
    'class-ejercicio-derechos.php',
    'class-audit-log.php',
    'class-pdf-generator.php',
    'class-shortcodes.php'
);

$missing_files = array();
foreach ($required_files as $file) {
    $filepath = $includes_dir . $file;
    if (file_exists($filepath)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - NO ENCONTRADO\n";
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    echo "\n   ⚠️ Faltan " . count($missing_files) . " archivos\n\n";
} else {
    echo "\n   ✅ Todos los archivos existen\n\n";
}

// Verificar sintaxis de cada archivo include
echo "5. Verificando sintaxis de archivos includes...\n";
foreach ($required_files as $file) {
    $filepath = $includes_dir . $file;
    if (file_exists($filepath)) {
        $result = exec("php -l " . escapeshellarg($filepath) . " 2>&1", $output, $return_var);
        if ($return_var === 0) {
            echo "   ✅ $file - Sintaxis OK\n";
        } else {
            echo "   ❌ $file - ERROR DE SINTAXIS:\n";
            echo "   " . implode("\n   ", $output) . "\n";
        }
    }
}

echo "\n";
echo "6. Verificando permisos...\n";
echo "   Directorio plugin: " . (is_readable(__DIR__) ? "✅ Legible" : "❌ No legible") . "\n";
echo "   Directorio includes: " . (is_readable($includes_dir) ? "✅ Legible" : "❌ No legible") . "\n";

echo "\n=== FIN VERIFICACIÓN ===\n";
echo "\n";
echo "INSTRUCCIONES:\n";
echo "- Si ves errores de sintaxis, copia el mensaje completo\n";
echo "- Si ves clases faltantes, hay un problema con los includes\n";
echo "- Si ves archivos faltantes, el ZIP se descomprimió mal\n";
echo "- Envía TODO este output\n";

echo "</pre>";
?>
