<?php
/**
 * Notificaciones por Email
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Email_Notifications {
    
    public static function enviar_confirmacion_solicitud_derecho($solicitud) {
        $to = $solicitud->interesado_email;
        $subject = 'Confirmación de recepción de solicitud - ' . $solicitud->numero_solicitud;
        
        $message = "Estimado/a {$solicitud->interesado_nombre},\n\n";
        $message .= "Hemos recibido su solicitud de ejercicio del derecho de {$solicitud->tipo_derecho}.\n\n";
        $message .= "Número de solicitud: {$solicitud->numero_solicitud}\n";
        $message .= "Fecha de solicitud: " . date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)) . "\n";
        $message .= "Plazo de respuesta: " . date('d/m/Y', strtotime($solicitud->fecha_limite)) . "\n\n";
        $message .= "Procesaremos su solicitud en el menor tiempo posible, conforme a la normativa vigente.\n\n";
        $message .= "Atentamente,\n";
        $message .= "Delegado de Protección de Datos\n";
        $message .= "Universidad de La Laguna\n";
        $message .= "dpd@ull.es";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    public static function enviar_notificacion_dpd_solicitud($solicitud) {
        $dpd_email = get_option('ull_rt_dpd_email', 'dpd@ull.es');
        
        $subject = 'Nueva solicitud de ejercicio de derecho - ' . $solicitud->numero_solicitud;
        
        $message = "Se ha recibido una nueva solicitud de ejercicio de derecho.\n\n";
        $message .= "Número de solicitud: {$solicitud->numero_solicitud}\n";
        $message .= "Tipo de derecho: {$solicitud->tipo_derecho}\n";
        $message .= "Interesado: {$solicitud->interesado_nombre}\n";
        $message .= "Email: {$solicitud->interesado_email}\n";
        $message .= "Fecha límite de respuesta: " . date('d/m/Y', strtotime($solicitud->fecha_limite)) . "\n\n";
        $message .= "Descripción:\n{$solicitud->descripcion_solicitud}\n\n";
        $message .= "Ver solicitud: " . admin_url('admin.php?page=ull-registro-derechos&action=view&id=' . $solicitud->id);
        
        return wp_mail($dpd_email, $subject, $message);
    }
    
    public static function enviar_resolucion_solicitud_derecho($solicitud) {
        $to = $solicitud->interesado_email;
        $subject = 'Respuesta a su solicitud - ' . $solicitud->numero_solicitud;
        
        $message = "Estimado/a {$solicitud->interesado_nombre},\n\n";
        $message .= "En relación a su solicitud de ejercicio del derecho de {$solicitud->tipo_derecho} ";
        $message .= "(Nº {$solicitud->numero_solicitud}), le informamos:\n\n";
        $message .= $solicitud->respuesta . "\n\n";
        $message .= "Si tiene alguna duda, puede contactar con nosotros en dpd@ull.es\n\n";
        $message .= "Atentamente,\n";
        $message .= "Delegado de Protección de Datos\n";
        $message .= "Universidad de La Laguna";
        
        return wp_mail($to, $subject, $message);
    }
    
    public static function enviar_alerta_plazo_derecho($solicitud) {
        $dpd_email = get_option('ull_rt_dpd_email', 'dpd@ull.es');
        
        $dias_restantes = ceil((strtotime($solicitud->fecha_limite) - time()) / 86400);
        
        $subject = "ALERTA: Solicitud {$solicitud->numero_solicitud} - {$dias_restantes} días para vencer";
        
        $message = "La solicitud {$solicitud->numero_solicitud} está próxima a vencer.\n\n";
        $message .= "Días restantes: {$dias_restantes}\n";
        $message .= "Fecha límite: " . date('d/m/Y', strtotime($solicitud->fecha_limite)) . "\n";
        $message .= "Interesado: {$solicitud->interesado_nombre}\n";
        $message .= "Tipo de derecho: {$solicitud->tipo_derecho}\n\n";
        $message .= "Ver solicitud: " . admin_url('admin.php?page=ull-registro-derechos&action=view&id=' . $solicitud->id);
        
        return wp_mail($dpd_email, $subject, $message);
    }
    
    public static function enviar_alerta_vencida_derecho($solicitud) {
        $dpd_email = get_option('ull_rt_dpd_email', 'dpd@ull.es');
        
        $subject = "URGENTE: Solicitud VENCIDA - {$solicitud->numero_solicitud}";
        
        $message = "La solicitud {$solicitud->numero_solicitud} ha VENCIDO.\n\n";
        $message .= "Fecha límite: " . date('d/m/Y', strtotime($solicitud->fecha_limite)) . "\n";
        $message .= "Interesado: {$solicitud->interesado_nombre} ({$solicitud->interesado_email})\n";
        $message .= "Tipo de derecho: {$solicitud->tipo_derecho}\n\n";
        $message .= "Es necesario dar respuesta de forma URGENTE.\n\n";
        $message .= "Ver solicitud: " . admin_url('admin.php?page=ull-registro-derechos&action=view&id=' . $solicitud->id);
        
        return wp_mail($dpd_email, $subject, $message);
    }
    
    public static function enviar_notificacion_dpd_consulta($consulta_id) {
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        $consulta = $consultas_obj->obtener_consulta($consulta_id);
        
        if (!$consulta) {
            return false;
        }
        
        $dpd_email = get_option('ull_rt_dpd_email', 'dpd@ull.es');
        
        $subject = 'Nueva consulta: ' . $consulta->asunto;
        
        $message = "Se ha recibido una nueva consulta.\n\n";
        $message .= "Asunto: {$consulta->asunto}\n";
        $message .= "De: {$consulta->consultante_nombre} ({$consulta->consultante_email})\n";
        $message .= "Área: {$consulta->consultante_area}\n";
        $message .= "Prioridad: {$consulta->prioridad}\n\n";
        $message .= "Consulta:\n{$consulta->consulta}\n\n";
        $message .= "Ver y responder: " . admin_url('admin.php?page=ull-registro-consultas&action=view&id=' . $consulta->id);
        
        return wp_mail($dpd_email, $subject, $message);
    }
    
    public static function enviar_respuesta_consulta($consulta_id) {
        $consultas_obj = ULL_RT_Consultas_DPD::get_instance();
        $consulta = $consultas_obj->obtener_consulta($consulta_id);
        
        if (!$consulta || empty($consulta->consultante_email)) {
            return false;
        }
        
        $to = $consulta->consultante_email;
        $subject = 'Respuesta a su consulta: ' . $consulta->asunto;
        
        $message = "Estimado/a {$consulta->consultante_nombre},\n\n";
        $message .= "En relación a su consulta sobre: {$consulta->asunto}\n\n";
        $message .= "Respuesta:\n{$consulta->respuesta}\n\n";
        $message .= "Si necesita más información, puede contactarnos en dpd@ull.es\n\n";
        $message .= "Atentamente,\n";
        $message .= "Delegado de Protección de Datos\n";
        $message .= "Universidad de La Laguna";
        
        return wp_mail($to, $subject, $message);
    }
}
