<?php
/**
 * Generador de PDFs
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_PDF_Generator {
    
    /**
     * Generar informe (método original)
     */
    public static function generar_informe($informe) {
        // Requiere TCPDF o similar
        // Por ahora retornamos HTML que puede ser convertido a PDF
        
        $html = self::generar_html_informe($informe);
        
        // Aquí se integraría con una librería PDF como TCPDF o Dompdf
        // Por simplicidad, retornamos el HTML
        
        return array(
            'html' => $html,
            'titulo' => $informe->titulo,
        );
    }
    
    /**
     * Generar PDF de un tratamiento individual
     */
    public static function generar_pdf_tratamiento($tratamiento_id) {
        $tratamientos = ULL_RT_Tratamientos::get_instance();
        $tratamiento = $tratamientos->obtener_tratamiento($tratamiento_id);
        
        if (!$tratamiento) {
            return new WP_Error('not_found', 'Tratamiento no encontrado');
        }
        
        $html = self::generar_html_tratamiento($tratamiento);
        
        return array(
            'html' => $html,
            'titulo' => 'Tratamiento_' . sanitize_title($tratamiento->nombre),
            'tratamiento' => $tratamiento
        );
    }
    
    /**
     * Generar HTML para PDF de tratamiento individual
     */
    private static function generar_html_tratamiento($tratamiento) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0073aa;
            font-size: 24pt;
            margin: 0 0 10px 0;
            font-weight: 300;
        }
        .header .subtitle {
            color: #666;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section {
            margin: 25px 0;
            page-break-inside: avoid;
        }
        .section-title {
            color: #0073aa;
            font-size: 14pt;
            font-weight: 600;
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e1e1e1;
        }
        .field {
            margin: 15px 0;
        }
        .field-label {
            font-weight: 600;
            color: #555;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .field-value {
            background: #f8f9fa;
            padding: 12px 15px;
            border-left: 3px solid #0073aa;
            font-size: 10pt;
            line-height: 1.7;
        }
        .metadata {
            background: #f0f6fc;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 9pt;
            color: #666;
        }
        .metadata-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e1e1e1;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-activo {
            background: #d4edda;
            color: #155724;
        }
        .status-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>';
        
        // Encabezado
        $html .= '<div class="header">';
        $html .= '<h1>UNIVERSIDAD DE LA LAGUNA</h1>';
        $html .= '<div class="subtitle">Registro de Actividades de Tratamiento</div>';
        $html .= '</div>';
        
        // Título y estado
        $html .= '<div class="section">';
        $html .= '<h2 style="font-size: 18pt; color: #222; margin: 0;">' . esc_html($tratamiento->nombre) . '</h2>';
        $estado_class = $tratamiento->estado === 'activo' ? 'status-activo' : 'status-inactivo';
        $estado_texto = $tratamiento->estado === 'activo' ? 'ACTIVO' : 'INACTIVO';
        $html .= '<div style="margin-top: 10px;"><span class="status-badge ' . $estado_class . '">' . $estado_texto . '</span></div>';
        $html .= '</div>';
        
        // Metadata
        $html .= '<div class="metadata">';
        $html .= '<div class="metadata-row">';
        $html .= '<span><strong>ID:</strong> ' . $tratamiento->id . '</span>';
        $html .= '<span><strong>Versión:</strong> ' . ($tratamiento->version ?? 1) . '</span>';
        $html .= '</div>';
        if (!empty($tratamiento->area_responsable)) {
            $html .= '<div class="metadata-row">';
            $html .= '<span><strong>Área Responsable:</strong> ' . esc_html($tratamiento->area_responsable) . '</span>';
            $html .= '</div>';
        }
        $html .= '<div class="metadata-row">';
        $html .= '<span><strong>Fecha de registro:</strong> ' . date('d/m/Y', strtotime($tratamiento->fecha_creacion)) . '</span>';
        if (!empty($tratamiento->fecha_modificacion)) {
            $html .= '<span><strong>Última modificación:</strong> ' . date('d/m/Y', strtotime($tratamiento->fecha_modificacion)) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        // Finalidad
        if (!empty($tratamiento->finalidad)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Finalidad del Tratamiento</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->finalidad)) . '</div>';
            $html .= '</div>';
        }
        
        // Base Jurídica
        if (!empty($tratamiento->base_juridica)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Base Jurídica</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->base_juridica)) . '</div>';
            $html .= '</div>';
        }
        
        // Colectivos Interesados
        if (!empty($tratamiento->colectivos_interesados)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Colectivos de Interesados</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->colectivos_interesados)) . '</div>';
            $html .= '</div>';
        }
        
        // Categorías de Datos
        if (!empty($tratamiento->categorias_datos)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Categorías de Datos Personales</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->categorias_datos)) . '</div>';
            $html .= '</div>';
        }
        
        // Cesiones/Comunicaciones
        if (!empty($tratamiento->cesiones_comunicaciones)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Destinatarios / Cesiones</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->cesiones_comunicaciones)) . '</div>';
            $html .= '</div>';
        }
        
        // Transferencias Internacionales
        if (!empty($tratamiento->transferencias_internacionales)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Transferencias Internacionales</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->transferencias_internacionales)) . '</div>';
            $html .= '</div>';
        }
        
        // Plazo de Conservación
        if (!empty($tratamiento->plazo_conservacion)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Plazo de Conservación</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->plazo_conservacion)) . '</div>';
            $html .= '</div>';
        }
        
        // Medidas de Seguridad
        if (!empty($tratamiento->medidas_seguridad)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Medidas de Seguridad</div>';
            $html .= '<div class="field-value">' . nl2br(esc_html($tratamiento->medidas_seguridad)) . '</div>';
            $html .= '</div>';
        }
        
        // Footer
        $html .= '<div class="footer">';
        $html .= '<p><strong>UNIVERSIDAD DE LA LAGUNA</strong></p>';
        $html .= '<p>Delegado de Protección de Datos</p>';
        $html .= '<p>Email: dpd@ull.es | Teléfono: 922 319 000</p>';
        $html .= '<p style="margin-top: 15px; font-size: 8pt;">Documento generado el ' . date('d/m/Y H:i') . ' desde el Sistema de Registro de Tratamientos RGPD</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private static function generar_html_informe($informe) {
        $contenido = maybe_unserialize($informe->contenido);
        
        $html = '<html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 12pt; }
            h1 { color: #1F4E78; border-bottom: 2px solid #2E75B5; }
            h2 { color: #2E75B5; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #1F4E78; color: white; }
            .header { text-align: center; margin-bottom: 30px; }
            .footer { margin-top: 30px; font-size: 10pt; color: #666; }
        </style></head><body>';
        
        $html .= '<div class="header">';
        $html .= '<h1>Universidad de La Laguna</h1>';
        $html .= '<h2>' . esc_html($informe->titulo) . '</h2>';
        $html .= '<p>Fecha: ' . date('d/m/Y', strtotime($informe->fecha_informe)) . '</p>';
        $html .= '</div>';
        
        switch ($informe->tipo_informe) {
            case 'registro_completo':
                $html .= self::generar_html_registro_completo($contenido);
                break;
            case 'transferencias':
                $html .= self::generar_html_transferencias($contenido);
                break;
            case 'datos_sensibles':
                $html .= self::generar_html_datos_sensibles($contenido);
                break;
            case 'ejercicio_derechos':
                $html .= self::generar_html_derechos($contenido);
                break;
            default:
                $html .= '<p>' . esc_html($informe->descripcion) . '</p>';
        }
        
        $html .= '<div class="footer">';
        $html .= '<p>Delegado de Protección de Datos - dpd@ull.es</p>';
        $html .= '<p>Generado automáticamente por el Sistema de Registro de Tratamientos ULL</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private static function generar_html_registro_completo($contenido) {
        $html = '<h2>Registro de Actividades de Tratamiento</h2>';
        $html .= '<p>Total de tratamientos activos: ' . $contenido['total_tratamientos'] . '</p>';
        
        foreach ($contenido['tratamientos'] as $tratamiento) {
            $html .= '<h3>' . esc_html($tratamiento->nombre) . '</h3>';
            $html .= '<table>';
            $html .= '<tr><th>Base Jurídica</th><td>' . nl2br(esc_html($tratamiento->base_juridica)) . '</td></tr>';
            $html .= '<tr><th>Finalidad</th><td>' . nl2br(esc_html($tratamiento->finalidad)) . '</td></tr>';
            $html .= '<tr><th>Colectivos</th><td>' . nl2br(esc_html($tratamiento->colectivos_interesados)) . '</td></tr>';
            $html .= '<tr><th>Categorías de Datos</th><td>' . nl2br(esc_html($tratamiento->categorias_datos)) . '</td></tr>';
            $html .= '<tr><th>Plazo de Conservación</th><td>' . esc_html($tratamiento->plazo_conservacion) . '</td></tr>';
            $html .= '</table>';
        }
        
        return $html;
    }
    
    private static function generar_html_transferencias($contenido) {
        $html = '<h2>Transferencias Internacionales de Datos</h2>';
        $html .= '<p>Tratamientos con transferencias: ' . $contenido['total_con_transferencias'] . '</p>';
        
        foreach ($contenido['tratamientos'] as $tratamiento) {
            $html .= '<h3>' . esc_html($tratamiento->nombre) . '</h3>';
            $html .= '<p><strong>Transferencias:</strong> ' . nl2br(esc_html($tratamiento->transferencias_internacionales)) . '</p>';
        }
        
        return $html;
    }
    
    private static function generar_html_datos_sensibles($contenido) {
        $html = '<h2>Categorías Especiales de Datos Personales</h2>';
        $html .= '<p>Tratamientos con datos sensibles: ' . $contenido['total_con_datos_sensibles'] . '</p>';
        
        foreach ($contenido['tratamientos'] as $tratamiento) {
            $html .= '<h3>' . esc_html($tratamiento->nombre) . '</h3>';
            $html .= '<p><strong>Categorías:</strong> ' . nl2br(esc_html($tratamiento->categorias_datos)) . '</p>';
        }
        
        return $html;
    }
    
    private static function generar_html_derechos($contenido) {
        $html = '<h2>Informe de Ejercicio de Derechos</h2>';
        $html .= '<p>Período: ' . $contenido['periodo']['desde'] . ' al ' . $contenido['periodo']['hasta'] . '</p>';
        $html .= '<p>Total de solicitudes: ' . $contenido['total_solicitudes'] . '</p>';
        
        $html .= '<h3>Estadísticas</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Métrica</th><th>Valor</th></tr>';
        $html .= '<tr><td>Pendientes</td><td>' . $contenido['estadisticas']['pendientes'] . '</td></tr>';
        $html .= '<tr><td>Resueltas</td><td>' . ($contenido['estadisticas']['por_estado']['resuelta'] ?? 0) . '</td></tr>';
        $html .= '<tr><td>Tiempo Promedio (días)</td><td>' . $contenido['estadisticas']['tiempo_promedio_respuesta'] . '</td></tr>';
        $html .= '</table>';
        
        return $html;
    }
    
    /**
     * Generar PDF desde HTML usando Dompdf
     * 
     * @param string $html Contenido HTML
     * @param string $filename Nombre del archivo (sin extensión)
     * @param bool $output_download Si es true, envía al navegador para descarga
     * @return string|void Contenido del PDF o void si se envía al navegador
     */
    public function generar_desde_html($html, $filename = 'documento', $output_download = false) {
        // Verificar si Dompdf está disponible
        if (!class_exists('Dompdf\Dompdf')) {
            // Intentar cargar Dompdf si está instalado
            $dompdf_path = ABSPATH . 'wp-content/plugins/ull-registro-tratamientos/vendor/autoload.php';
            
            if (file_exists($dompdf_path)) {
                require_once $dompdf_path;
            } else {
                // Si no está disponible, usar método alternativo simple
                return $this->generar_pdf_simple($html, $filename, $output_download);
            }
        }
        
        try {
            $dompdf = new \Dompdf\Dompdf(array(
                'enable_remote' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false
            ));
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            if ($output_download) {
                $dompdf->stream($filename . '.pdf', array('Attachment' => true));
            } else {
                return $dompdf->output();
            }
        } catch (Exception $e) {
            // Si falla Dompdf, usar método alternativo
            return $this->generar_pdf_simple($html, $filename, $output_download);
        }
    }
    
    /**
     * Método alternativo simple para generar PDF cuando Dompdf no está disponible
     * Crea un HTML imprimible que se puede convertir a PDF desde el navegador
     */
    private function generar_pdf_simple($html, $filename, $output_download) {
        // Añadir estilos para impresión
        $html_completo = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($filename) . '</title>
    <style>
        @media print {
            @page {
                margin: 2cm;
            }
        }
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
' . $html . '
</body>
</html>';
        
        if ($output_download) {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $filename . '.html"');
            echo $html_completo;
            exit;
        } else {
            return $html_completo;
        }
    }
}
