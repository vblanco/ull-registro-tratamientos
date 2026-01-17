<?php
/**
 * Dashboard principal del plugin
 */

if (!defined('ABSPATH')) exit;

$tratamientos_obj = ULL_RT_Tratamientos::get_instance();
$derechos_obj = ULL_RT_Ejercicio_Derechos::get_instance();
$consultas_obj = ULL_RT_Consultas_DPD::get_instance();

$stats_tratamientos = $tratamientos_obj->obtener_estadisticas();
$stats_derechos = $derechos_obj->obtener_estadisticas();
$stats_consultas = $consultas_obj->obtener_estadisticas();
?>

<div class="wrap">
    <h1>Dashboard - Registro de Tratamientos RGPD</h1>
    
    <div class="ull-rt-dashboard">
        <div class="ull-rt-stats-grid">
            <!-- Tratamientos -->
            <div class="ull-rt-stat-box">
                <h3>Tratamientos</h3>
                <div class="ull-rt-stat-number"><?php echo $stats_tratamientos['total']; ?></div>
                <p>Actividades de tratamiento activas</p>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-tratamientos'); ?>" class="button">Ver Todos</a>
            </div>
            
            <!-- Ejercicio de Derechos -->
            <div class="ull-rt-stat-box">
                <h3>Ejercicio de Derechos</h3>
                <div class="ull-rt-stat-number"><?php echo $stats_derechos['pendientes']; ?></div>
                <p>Solicitudes pendientes</p>
                <?php if ($stats_derechos['vencidas'] > 0): ?>
                    <p class="ull-rt-alert">⚠️ <?php echo $stats_derechos['vencidas']; ?> solicitudes vencidas</p>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-derechos'); ?>" class="button">Ver Solicitudes</a>
            </div>
            
            <!-- Consultas DPD -->
            <div class="ull-rt-stat-box">
                <h3>Consultas al DPD</h3>
                <div class="ull-rt-stat-number"><?php echo $stats_consultas['pendientes']; ?></div>
                <p>Consultas pendientes de responder</p>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-consultas'); ?>" class="button">Ver Consultas</a>
            </div>
            
            <!-- Tiempo promedio -->
            <div class="ull-rt-stat-box">
                <h3>Tiempo de Respuesta</h3>
                <div class="ull-rt-stat-number"><?php echo round($stats_derechos['tiempo_promedio_respuesta']); ?></div>
                <p>Días promedio de respuesta</p>
            </div>
        </div>
        
        <div class="ull-rt-dashboard-section">
            <h2>Acciones Rápidas</h2>
            <div class="ull-rt-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=ull-registro-tratamientos&action=new'); ?>" class="button button-primary">
                    ➕ Nuevo Tratamiento
                </a>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-informes&action=generate'); ?>" class="button button-primary">
                    📄 Generar Informe
                </a>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-derechos'); ?>" class="button">
                    📋 Ver Solicitudes de Derechos
                </a>
                <a href="<?php echo admin_url('admin.php?page=ull-registro-consultas'); ?>" class="button">
                    💬 Ver Consultas
                </a>
            </div>
        </div>
        
        <div class="ull-rt-dashboard-section">
            <h2>Resumen de Actividades</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Área Responsable</th>
                        <th>Tratamientos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_tratamientos['por_area'] as $area => $total): ?>
                    <tr>
                        <td><?php echo esc_html($area); ?></td>
                        <td><?php echo $total; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="ull-rt-dashboard-section">
            <h2>Información del Sistema</h2>
            <p><strong>Transferencias Internacionales:</strong> <?php echo $stats_tratamientos['con_transferencias']; ?> tratamientos</p>
            <p><strong>Datos Sensibles:</strong> <?php echo $stats_tratamientos['con_datos_sensibles']; ?> tratamientos</p>
            <p><strong>Consultas del mes:</strong> <?php echo $stats_consultas['mes_actual']; ?></p>
            <p><strong>Solicitudes del mes:</strong> <?php echo $stats_derechos['mes_actual']; ?></p>
        </div>
    </div>
</div>
