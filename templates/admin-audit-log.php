<?php
if (!defined('ABSPATH')) exit;

$logs = ULL_RT_Audit_Log::obtener_logs(array('limit' => 100));
?>

<div class="wrap">
    <h1>Audit Log - Registro de Auditoría</h1>
    
    <p>Registro completo de todas las acciones realizadas en el sistema</p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Descripción</th>
                <th>IP</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No hay registros</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><?php echo esc_html($log->usuario_nombre); ?></td>
                        <td><?php echo esc_html($log->accion); ?></td>
                        <td><?php echo esc_html($log->modulo); ?></td>
                        <td><?php echo esc_html($log->descripcion); ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log->fecha)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
