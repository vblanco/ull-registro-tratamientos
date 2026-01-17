<?php
/**
 * Importación y Exportación de Tratamientos
 */

if (!defined('ABSPATH')) exit;

class ULL_RT_Import_Export {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_ull_exportar_tratamientos', array($this, 'ajax_exportar_tratamientos'));
        add_action('wp_ajax_ull_importar_tratamientos', array($this, 'ajax_importar_tratamientos'));
    }
    
    /**
     * Exportar tratamientos a CSV
     */
    public function exportar_a_csv($formato = 'csv') {
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $tratamientos = $tratamientos_obj->listar_tratamientos(array('estado' => 'activo'));
        
        if ($formato == 'csv') {
            return $this->generar_csv($tratamientos);
        } else {
            return $this->generar_excel($tratamientos);
        }
    }
    
    /**
     * Generar archivo CSV
     */
    private function generar_csv($tratamientos) {
        $filename = 'tratamientos_ull_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        $headers = array(
            'ID',
            'Nombre',
            'Base Jurídica',
            'Finalidad',
            'Colectivos Interesados',
            'Categorías de Datos',
            'Cesiones/Comunicaciones',
            'Transferencias Internacionales',
            'Plazo de Conservación',
            'Medidas de Seguridad',
            'Área Responsable',
            'Estado',
            'Versión',
            'Fecha Creación',
            'Fecha Modificación'
        );
        
        fputcsv($output, $headers, ';');
        
        // Datos
        foreach ($tratamientos as $tratamiento) {
            $row = array(
                $tratamiento->id,
                $tratamiento->nombre,
                $tratamiento->base_juridica,
                $tratamiento->finalidad,
                $tratamiento->colectivos_interesados,
                $tratamiento->categorias_datos,
                $tratamiento->cesiones_comunicaciones,
                $tratamiento->transferencias_internacionales,
                $tratamiento->plazo_conservacion,
                $tratamiento->medidas_seguridad,
                $tratamiento->area_responsable,
                $tratamiento->estado,
                $tratamiento->version,
                $tratamiento->fecha_creacion,
                $tratamiento->fecha_modificacion
            );
            
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Importar tratamientos desde CSV
     */
    public function importar_desde_csv($file) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('no_file', 'No se ha proporcionado ningún archivo');
        }
        
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            return new WP_Error('cant_open', 'No se puede abrir el archivo');
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, ';');
        
        if (empty($headers)) {
            fclose($handle);
            return new WP_Error('invalid_format', 'Formato de archivo inválido');
        }
        
        $tratamientos_obj = ULL_RT_Tratamientos::get_instance();
        $importados = 0;
        $errores = array();
        $actualizados = 0;
        $nuevos = 0;
        
        // Leer datos
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (empty($data[0]) && empty($data[1])) {
                continue; // Saltar líneas vacías
            }
            
            // Mapear datos
            $datos_tratamiento = array(
                'nombre' => isset($data[1]) ? $data[1] : '',
                'base_juridica' => isset($data[2]) ? $data[2] : '',
                'finalidad' => isset($data[3]) ? $data[3] : '',
                'colectivos_interesados' => isset($data[4]) ? $data[4] : '',
                'categorias_datos' => isset($data[5]) ? $data[5] : '',
                'cesiones_comunicaciones' => isset($data[6]) ? $data[6] : '',
                'transferencias_internacionales' => isset($data[7]) ? $data[7] : '',
                'plazo_conservacion' => isset($data[8]) ? $data[8] : '',
                'medidas_seguridad' => isset($data[9]) ? $data[9] : '',
                'area_responsable' => isset($data[10]) ? $data[10] : '',
                'estado' => isset($data[11]) ? $data[11] : 'activo',
            );
            
            // Validar campos requeridos
            if (empty($datos_tratamiento['nombre'])) {
                $errores[] = "Fila " . ($importados + 2) . ": El nombre es obligatorio";
                continue;
            }
            
            // Verificar si existe (por nombre)
            global $wpdb;
            $table = $wpdb->prefix . 'ull_tratamientos';
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE nombre = %s",
                $datos_tratamiento['nombre']
            ));
            
            if ($existe) {
                // Actualizar existente
                $resultado = $tratamientos_obj->actualizar_tratamiento($existe, $datos_tratamiento);
                if ($resultado) {
                    $actualizados++;
                    $importados++;
                } else {
                    $errores[] = "Fila " . ($importados + 2) . ": Error al actualizar '{$datos_tratamiento['nombre']}'";
                }
            } else {
                // Crear nuevo
                $resultado = $tratamientos_obj->crear_tratamiento($datos_tratamiento);
                if ($resultado) {
                    $nuevos++;
                    $importados++;
                } else {
                    $errores[] = "Fila " . ($importados + 2) . ": Error al crear '{$datos_tratamiento['nombre']}'";
                }
            }
        }
        
        fclose($handle);
        
        // Audit log
        ULL_RT_Audit_Log::registrar(
            'importar_tratamientos',
            'tratamientos',
            "Importados: $importados tratamientos (Nuevos: $nuevos, Actualizados: $actualizados)"
        );
        
        return array(
            'success' => true,
            'importados' => $importados,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'errores' => $errores
        );
    }
    
    /**
     * Generar plantilla CSV vacía
     */
    public function generar_plantilla_csv() {
        $filename = 'plantilla_tratamientos_ull.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        $headers = array(
            'ID',
            'Nombre',
            'Base Jurídica',
            'Finalidad',
            'Colectivos Interesados',
            'Categorías de Datos',
            'Cesiones/Comunicaciones',
            'Transferencias Internacionales',
            'Plazo de Conservación',
            'Medidas de Seguridad',
            'Área Responsable',
            'Estado',
            'Versión',
            'Fecha Creación',
            'Fecha Modificación'
        );
        
        fputcsv($output, $headers, ';');
        
        // Ejemplo de fila (comentada con #)
        $ejemplo = array(
            '',
            'Gestión Académica de Estudiantes',
            'RGPD: 6.1.e) Misión de interés público',
            'Gestión de alumnos y vida académica',
            'Estudiantes, representantes legales',
            'Datos identificativos, académicos',
            'Servicios sanitarios (emergencias)',
            'No previstas',
            'Indefinido para expediente académico',
            'Conforme al Esquema Nacional de Seguridad',
            'Secretaría General',
            'activo',
            '1',
            '',
            ''
        );
        
        fputcsv($output, $ejemplo, ';');
        
        fclose($output);
        exit;
    }
    
    /**
     * Generar CSV con los 33 tratamientos de la ULL
     */
    public function generar_csv_33_tratamientos() {
        $filename = 'tratamientos_ull_33_completos.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        $headers = array(
            'ID',
            'Nombre',
            'Base Jurídica',
            'Finalidad',
            'Colectivos Interesados',
            'Categorías de Datos',
            'Cesiones/Comunicaciones',
            'Transferencias Internacionales',
            'Plazo de Conservación',
            'Medidas de Seguridad',
            'Área Responsable',
            'Estado'
        );
        
        fputcsv($output, $headers, ';');
        
        // Los 33 tratamientos completos
        $tratamientos_33 = $this->obtener_33_tratamientos_ull();
        
        foreach ($tratamientos_33 as $tratamiento) {
            fputcsv($output, $tratamiento, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Obtener los 33 tratamientos de la ULL
     */
    private function obtener_33_tratamientos_ull() {
        return array(
            array('', 'Actividades Deportivas y Competiciones', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión de las competiciones de la ULL.', 'Miembros de la comunidad universitaria: alumnos, PAS, PDI, docentes, antiguos alumnos, familiares, funcionarios de otras entidades con convenio.', 'Datos identificativos (nombre, DNI, dirección, teléfono). Datos académicos y profesionales. Datos personales (fecha nacimiento, edad, sexo). Datos de competición (modalidad, clasificación).', 'Entidades organizadoras de competiciones deportivas.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicio de Deportes', 'activo'),
            
            array('', 'Alojamiento', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.c) Obligación legal. RGPD: 6.1.e) Misión de interés público.', 'Gestión de los colegios mayores y alojamientos de la Universidad.', 'Alumnos, familiares, representantes legales, usuarios, solicitantes, beneficiarios.', 'DNI/NIF, Nº SS/Mutualidad, nombre y apellidos, dirección, teléfono. Datos personales (edad, sexo). Datos familiares, académicos y profesionales. Datos económicos y de seguros.', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Colegios Mayores', 'activo'),
            
            array('', 'Apoyo a la Investigación: Subvenciones, Convenios y Contratos', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público. LO 4/2007 de Universidades. Ley 14/2011 de Ciencia, Tecnología e Innovación.', 'Apoyo a la investigación. Gestión de proyectos de investigación.', 'Personal Docente e Investigador, investigadores/as, colaboradores/as.', 'Nombre, DNI, dirección, correo-e, teléfono, Nº SS/Mutualidad, firma. Datos personales. Datos académicos y profesionales. Detalles de empleo, económicos y de seguros.', 'Administraciones públicas y entidades externas para colaboración en investigación. Bancos. Organismos obligados por ley.', 'Entidades colaboradoras en el extranjero con convenio de colaboración.', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Vicerrectorado de Investigación', 'activo'),
            
            array('', 'Control de Acceso', 'RGPD: 6.1.b) Ejecución de contrato.', 'Gestión de plataformas de acceso a las instalaciones de la Universidad de La Laguna.', 'Comunidad universitaria (estudiantes, PAS y PDI). Otras personas con acceso a edificios y aparcamientos.', 'Nombre, DNI, dirección, teléfono, tarjeta de acceso, imagen (fotografía). Datos de acceso a instalaciones.', 'Fuerzas y cuerpos de seguridad. Órganos judiciales (por obligación legal).', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Seguridad', 'activo'),
            
            array('', 'Neurorrehabilitación (Estimulación Cortical y Seguimiento Ocular)', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.e) Misión de interés público (art. 1 LO 6/2001 de Universidades).', 'Proyectos de investigación en necesidades específicas de apoyo educativo: altas capacidades, TDAH y dificultades de aprendizaje.', 'Sujetos de investigación. Representantes legales.', 'Datos identificativos (email, teléfono, NIF, nombre). Categorías especiales: Salud.', 'No previstas', 'No previstas', 'Durante la investigación hasta publicación de resultados. Bloqueados para prescripción de responsabilidades.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Investigación', 'activo'),
            
            array('', 'Entrenamientos y Clases Deportivas Online', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público (arts. 1, 2 y 90 LO 6/2001).', 'Prestación de servicios deportivos online: entrenamientos virtuales y clases deportivas.', 'Usuarios del Servicio de Deportes: alumnos, PAS, PDI, docentes, otros empleados, antiguos alumnos Alumni ULL. Personal del Servicio de Deportes.', 'Datos identificativos: email, teléfono, NIF, nombre, ID usuario y contraseña.', 'Interesados en expediente. Órganos judiciales. Fuerzas y cuerpos de seguridad.', 'No previstas', 'Conforme al Esquema Nacional de Seguridad (RD 3/2010).', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicio de Deportes', 'activo'),
            
            array('', 'Evaluación y Calidad Institucional', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Solicitar, recabar y facilitar información para procesos de evaluación y calidad institucional.', 'Alumnos, PAS, PDI, docentes, egresados, empleadores, tutores externos, evaluadores externos, Agencias de Calidad, usuarios externos y proveedores.', 'Datos identificativos (nacionalidad, sexo, edad, residencia). Datos académicos y profesionales. Datos ocupacionales. Datos económicos (ingresos, becas). Opiniones y valoraciones.', 'Administraciones Públicas competentes. Otras Universidades. Agencias de Calidad y Evaluadores Externos.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Calidad', 'activo'),
            
            array('', 'Expedientes y Actividad de Inspección', 'RGPD: 6.1.c) Obligación legal. RGPD: 6.1.e) Misión de interés público. LO 4/2007 de Universidades.', 'Gestión e inspección de la calidad y denuncias.', 'Denunciantes, profesores, intervinientes en expediente, terceros afectados.', 'Nombre, DNI, dirección, correo-e. Datos académicos y profesionales. Datos relativos a denuncia y expediente.', 'Interesados en expediente. Órganos judiciales. Fuerzas y cuerpos de seguridad.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Inspección', 'activo'),
            
            array('', 'Gestión Académica de Estudiantes', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.e) Misión de interés público. LO 6/2001 modificada por LO 4/2007 de Universidades.', 'Gestión de alumnos y vida académica: acceso, matrícula, actas y expedientes.', 'Alumnos, representantes legales, usuarios, solicitantes.', 'Datos identificativos (nombre, email, dirección, teléfono, DNI, firma, imagen, Nº SS). Datos personales (edad, sexo, nacimiento, nacionalidad). Datos académicos.', 'Servicios sanitarios o seguros (urgencias). Publicación en web y tablones oficiales. AEPD, jueces y tribunales (a requerimiento).', 'No previstas', 'Preinscripciones: plazo para recursos. Expediente académico: indefinidamente.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Secretaría General', 'activo'),
            
            array('', 'Gestión de Ayudas Asistenciales a Estudiantes', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión de ayudas asistenciales dentro de la política asistencial de la ULL.', 'Estudiantes, familiares, representantes legales, beneficiarios.', 'Nombre, DNI, dirección, correo-e, teléfono, Nº SS/Mutualidad. Datos personales (edad, nacimiento, nacionalidad, sexo). Datos académicos, familiares y económicos.', 'Administración Pública competente.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicios Sociales', 'activo'),
            
            array('', 'Gestión de Consentimientos para Proyectos de Investigación', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.e) Misión de interés público (arts. 1 y 39 LO 6/2001).', 'Gestión, archivo y registro de consentimientos de participantes en proyectos de investigación.', 'Participantes en proyectos (sujetos fuente). Representantes legales.', 'Nombre, DNI, firma, imagen.', 'No previstas', 'No previstas', 'Durante el proyecto. Bloqueados para prescripción de responsabilidades.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Investigación', 'activo'),
            
            array('', 'Gestión de la Contratación Pública', 'RGPD: 6.1.c) Obligación legal. Ley 9/2017 Contratos del Sector Público. Ley 47/2003 General Presupuestaria. Ley 58/2003 General Tributaria. Ley 40/2015 Régimen Jurídico Sector Público.', 'Gestión de contratos con proveedores y adjudicatarios. Alta de terceros.', 'Proveedores, prestadores de servicio, licitadores, personas de contacto, representantes legales.', 'Datos identificativos (nombre, teléfono, dirección, DNI/NIF, firma). Datos económico-financieros. Datos bancarios y empresariales.', 'Plataforma de contratación. Diarios y Boletines Oficiales. Audiencia de Cuentas, Agencia Tributaria y organismos obligados.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Contratación', 'activo'),
            
            array('', 'Gestión de la Universidad de Mayores', 'RGPD: 6.1.b) Ejecución de contrato.', 'Gestión de los alumnos de la universidad de mayores.', 'Alumnos, profesores y conferenciantes.', 'Datos identificativos (nombre, teléfono, dirección, DNI, Nº SS/Mutualidad, firma). Datos personales (nacimiento, edad, sexo). Datos financieros (cuenta bancaria).', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Extensión Universitaria', 'activo'),
            
            array('', 'Gestión de Instalaciones Deportivas', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión de las instalaciones deportivas de la ULL.', 'Comunidad universitaria (estudiantes, PAS, PDI), antiguos alumnos, familiares. Personal de entidades con convenio.', 'Nombre, DNI, dirección, teléfono, correo-e, nº registro personal. Datos académicos y profesionales. Datos de actividad e instalaciones reservadas. Datos bancarios.', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicio de Deportes', 'activo'),
            
            array('', 'Gestión de Cursos y Talleres de Extensión Universitaria', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.b) Ejecución de contrato.', 'Gestión de cursos de extensión universitaria. Envío de información de cursos.', 'Solicitantes, asistentes y conferenciantes.', 'Datos identificativos (nombre, teléfono, dirección, DNI, Nº SS/Mutualidad, firma). Datos personales (nacimiento, edad, sexo). Datos financieros (cuenta bancaria).', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Extensión Universitaria', 'activo'),
            
            array('', 'Gestión de Grupos y Espacios para la Cultura', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público. LO 4/2007 de Universidades.', 'Gestión de las funciones de difusión de la cultura por parte de la ULL.', 'Personas de contacto, representantes legales, participantes.', 'Nombre, dirección, teléfono, correo-e, DNI/NIF. Información comercial.', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Cultura', 'activo'),
            
            array('', 'Gestión de Procesos Selectivos del Personal', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.c) Obligación legal. LO 4/2007 Universidades. Ley 14/2011 Ciencia. RDL 5/2015 EBEP. RDL 2/2015 Estatuto Trabajadores.', 'Selección de personal y provisión de puestos mediante convocatorias públicas.', 'Candidatos a procedimientos de provisión de puestos.', 'Nombre, DNI, nº registro personal, dirección, firma, teléfono. Categorías especiales: salud (discapacidades). Datos personales (sexo, estado civil, nacionalidad, edad, nacimiento, familia). Datos académicos y profesionales. Datos de empleo y carrera administrativa.', 'Registro de Personal. Dirección General de Función Pública. BOE, BOC. Usuarios web del proceso selectivo.', 'No previstas', 'Durante el proceso selectivo. Si se incorpora, durante la relación con la ULL. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Recursos Humanos', 'activo'),
            
            array('', 'Gestión de Recursos Humanos', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.c) Obligación legal. LO 4/2007 Universidades. Ley 14/2011 Ciencia. Ley 30/1984 Función Pública. RDL 5/2015 EBEP. RDL 2/2015 Estatuto Trabajadores.', 'Gestión del personal funcionario y laboral: expediente, incompatibilidades, promoción, régimen disciplinario, formación, acción social. Nómina, retenciones, cotizaciones SS. Gestión de ausencias. Afiliaciones sindicales. Estadísticas.', 'Personal de la ULL.', 'Categorías especiales: afiliación sindical (cuotas y representación), salud. Infracciones administrativas. Datos identificativos (NIF, Nº SS, nombre, dirección, teléfono, Nº Registro Personal, firma). Datos personales (estado civil, familia, nacimiento, edad, sexo, nacionalidad). Circunstancias sociales/familiares (licencias, permisos). Datos académicos y profesionales (formación, incompatibilidades). Datos de empleo (cuerpo/escala, categoría, puesto, historial). Datos económico-financieros (bancarios, nómina, impuestos, retenciones judiciales).', 'Registro de Personal. DG Función Pública. INSS y mutualidades. Entidad gestora Plan de Pensiones. TGSS. DG Costes de Personal. AEAT. Tribunal de Cuentas. Entidades financieras. Ministerio de Universidades (estadísticas).', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Datos económicos conforme a Ley 58/2003 General Tributaria. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Recursos Humanos', 'activo'),
            
            array('', 'Gestión del PAED (Programa de Ayuda a Estudiantes con Necesidades Especiales)', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.b) Ejecución de contrato. RGPD: 9.2.h) Tratamiento necesario para fines de medicina preventiva, diagnóstico médico, asistencia sanitaria o social.', 'Gestión de medios y ayudas a alumnos con necesidades especiales.', 'Estudiantes matriculados, solicitantes, beneficiarios, representantes legales.', 'Categorías especiales: Salud. Datos identificativos (nombre, teléfono, dirección). Datos personales (nacimiento, edad, sexo, familia). Datos académicos (formación, titulaciones).', 'Empresa especializada que gestiona el programa (con consentimiento expreso).', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicios Sociales', 'activo'),
            
            array('', 'Gestión Económica, Contable y Financiera', 'RGPD: 6.1.c) Obligación legal. LO 4/2007 Universidades. Ley 9/2017 Contratos. Ley 47/2003 General Presupuestaria. Ley 58/2003 General Tributaria. Ley 38/2003 General de Subvenciones. Ley 40/2015 Régimen Jurídico.', 'Tramitación de expedientes de gasto e ingresos derivados de la ejecución del presupuesto.', 'Personal funcionario y laboral, proveedores, beneficiarios de subvenciones, licitadores.', 'Datos identificativos (nombre, teléfono, dirección, DNI/NIF, firma). Datos de empleo (puesto de trabajo). Datos económico-financieros y bancarios.', 'Entidades financieras. INSS y mutualidades. AEAT. Intervención General. Tribunal de Cuentas.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Gestión Económica', 'activo'),
            
            array('', 'Gestión y Control de la Biblioteca', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión de usuarios y servicios de la Biblioteca Universitaria.', 'Usuarios de la Biblioteca: comunidad universitaria y usuarios externos autorizados.', 'Datos identificativos (nombre, DNI, correo-e, teléfono). Datos de préstamos y servicios bibliotecarios.', 'No previstas', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Biblioteca', 'activo'),
            
            array('', 'Investigación de Enfermedades Tropicales', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.e) Misión de interés público (investigación científica).', 'Investigación científica en el ámbito de enfermedades tropicales.', 'Sujetos de investigación. Representantes legales.', 'Datos identificativos. Categorías especiales: Salud.', 'Entidades colaboradoras en investigación (con consentimiento).', 'Posibles a entidades colaboradoras internacionales en investigación.', 'Durante la investigación. Bloqueados para prescripción de responsabilidades.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Investigación', 'activo'),
            
            array('', 'Movilidad', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión de programas de movilidad nacional e internacional de estudiantes y personal.', 'Estudiantes, PDI y PAS participantes en programas de movilidad.', 'Datos identificativos (nombre, DNI, dirección, teléfono, correo-e). Datos académicos y profesionales. Datos económicos (becas, ayudas).', 'Universidades de destino. Organismos gestores de programas de movilidad (Erasmus+, SICUE, etc.).', 'Universidades y entidades de países de destino.', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Relaciones Internacionales', 'activo'),
            
            array('', 'Plan de Organización Docente', 'RGPD: 6.1.c) Obligación legal. RGPD: 6.1.e) Misión de interés público.', 'Planificación y organización de la actividad docente del profesorado.', 'Personal Docente e Investigador.', 'Datos identificativos (nombre, DNI). Datos profesionales (departamento, área, categoría). Datos de actividad docente (asignaturas, horarios, grupos).', 'Publicación en web institucional (información docente).', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Ordenación Académica', 'activo'),
            
            array('', 'Prácticas Externas e Inserción Laboral', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público. RD 592/2014 de prácticas académicas externas.', 'Gestión de prácticas externas curriculares y extracurriculares. Inserción laboral.', 'Estudiantes. Tutores académicos y de empresa. Entidades colaboradoras.', 'Datos identificativos (nombre, DNI, dirección, teléfono, correo-e). Datos académicos. Datos de la práctica (entidad, periodo, tutor, evaluación).', 'Entidades colaboradoras en prácticas. Servicio Público de Empleo.', 'Entidades colaboradoras en el extranjero.', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Prácticas y Empleo', 'activo'),
            
            array('', 'Prevención de Riesgos Laborales', 'RGPD: 6.1.c) Obligación legal. Ley 31/1995 de Prevención de Riesgos Laborales. RD 39/1997 Reglamento de los Servicios de Prevención.', 'Prevención de riesgos laborales y vigilancia de la salud del personal.', 'Personal de la ULL (funcionario y laboral).', 'Datos identificativos (nombre, DNI, puesto). Categorías especiales: Salud (reconocimientos médicos, aptitud laboral). Datos de siniestralidad y accidentes.', 'Mutua de accidentes. Inspección de Trabajo. Autoridad sanitaria.', 'No previstas', 'Conforme a legislación de PRL. Historiales médicos: mínimo 5 años tras fin de relación laboral.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Prevención Riesgos', 'activo'),
            
            array('', 'Pruebas de Acceso a la Universidad', 'RGPD: 6.1.c) Obligación legal. RGPD: 6.1.e) Misión de interés público. RD 310/2016 regulador de pruebas de acceso.', 'Gestión y realización de las pruebas de acceso a la universidad (EBAU/EvAU).', 'Estudiantes que realizan pruebas de acceso. Correctores.', 'Datos identificativos (nombre, DNI, foto). Datos académicos (centro de procedencia, calificaciones). Categorías especiales: Salud (adaptaciones por discapacidad).', 'Consejería de Educación. Otras universidades del distrito.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Acceso', 'activo'),
            
            array('', 'Publicaciones', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión del Servicio de Publicaciones: edición, distribución y venta de publicaciones.', 'Autores. Compradores. Colaboradores.', 'Datos identificativos (nombre, DNI, dirección, teléfono, correo-e). Datos bancarios (para pagos de derechos de autor o compras). Datos profesionales de autores.', 'Distribuidores. Entidades de gestión de derechos de autor.', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Publicaciones', 'activo'),
            
            array('', 'Realización de Pruebas de Detección de COVID-19', 'RGPD: 6.1.e) Misión de interés público. RGPD: 9.2.i) Interés público en salud pública.', 'Realización de pruebas de detección de COVID-19 en la comunidad universitaria.', 'Miembros de la comunidad universitaria que se someten a pruebas.', 'Datos identificativos (nombre, DNI, teléfono). Categorías especiales: Salud (resultado de pruebas).', 'Autoridades sanitarias (conforme a normativa COVID).', 'No previstas', 'Conforme a normativa sanitaria aplicable.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Servicio de Salud', 'activo'),
            
            array('', 'Registro', 'RGPD: 6.1.c) Obligación legal. Ley 39/2015 PACAP. Ley 40/2015 Régimen Jurídico.', 'Gestión del Registro General de entrada y salida de documentos.', 'Cualquier persona que presente o reciba documentos de la Universidad.', 'Datos identificativos (nombre, DNI, dirección). Datos del documento registrado.', 'Administraciones Públicas destinatarias de documentos.', 'No previstas', 'Permanente (registro oficial). Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Registro General', 'activo'),
            
            array('', 'Tratamiento Académico de Estudiantes', 'RGPD: 6.1.b) Ejecución de contrato. RGPD: 6.1.e) Misión de interés público.', 'Gestión integral del expediente académico del estudiante.', 'Estudiantes matriculados en cualquier titulación.', 'Datos identificativos (nombre, DNI, foto). Datos académicos (expediente completo, calificaciones, títulos). Datos económicos (tasas, becas).', 'Ministerio de Universidades. Administraciones educativas. Entidades verificadoras de títulos.', 'No previstas', 'Expediente académico: permanente. Datos económicos: conforme a legislación tributaria.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Secretaría General', 'activo'),
            
            array('', 'Tratamiento Gestión de Eventos', 'RGPD: 6.1.a) Consentimiento. RGPD: 6.1.b) Ejecución de contrato.', 'Gestión de la participación en eventos organizados por la ULL (congresos, jornadas, seminarios).', 'Participantes, ponentes, organizadores de eventos.', 'Datos identificativos (nombre, DNI, correo-e, teléfono). Datos profesionales. Datos económicos (inscripciones). Imagen (fotografías del evento).', 'Publicación en medios institucionales (con consentimiento para imagen).', 'No previstas', 'Tiempo necesario para la finalidad y responsabilidades derivadas. Aplicable normativa de archivos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Eventos', 'activo'),
            
            array('', 'Videovigilancia', 'RGPD: 6.1.e) Misión de interés público (seguridad). LO 4/1997 reguladora de videovigilancia. Instrucción 1/2006 AEPD.', 'Seguridad de personas, bienes e instalaciones universitarias.', 'Cualquier persona que acceda a zonas videovigiladas.', 'Imagen (grabaciones de video).', 'Fuerzas y cuerpos de seguridad. Órganos judiciales (a requerimiento).', 'No previstas', 'Máximo 1 mes, salvo que se requieran para acreditar infracciones o delitos.', 'Conforme al Esquema Nacional de Seguridad (RD 311/2022)', 'Seguridad', 'activo'),
        );
    }
    
    /**
     * AJAX: Exportar tratamientos
     */
    public function ajax_exportar_tratamientos() {
        check_ajax_referer('ull_rt_nonce', 'nonce');
        
        if (!current_user_can('ull_manage_tratamientos')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        $formato = isset($_POST['formato']) ? sanitize_text_field($_POST['formato']) : 'csv';
        
        $this->exportar_a_csv($formato);
    }
    
    /**
     * AJAX: Importar tratamientos
     */
    public function ajax_importar_tratamientos() {
        check_ajax_referer('ull_rt_nonce', 'nonce');
        
        if (!current_user_can('ull_manage_tratamientos')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        if (empty($_FILES['archivo'])) {
            wp_send_json_error(array('message' => 'No se ha proporcionado ningún archivo'));
        }
        
        $resultado = $this->importar_desde_csv($_FILES['archivo']);
        
        if (is_wp_error($resultado)) {
            wp_send_json_error(array('message' => $resultado->get_error_message()));
        }
        
        wp_send_json_success($resultado);
    }
}

// Inicializar
ULL_RT_Import_Export::get_instance();
