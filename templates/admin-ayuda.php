<?php
/**
 * Template: Ayuda y Documentación de Shortcodes
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>📖 Ayuda y Documentación</h1>
    
    <div class="ull-rt-ayuda-container">
        
        <!-- Introducción -->
        <div class="ull-rt-ayuda-intro">
            <p>Esta página contiene la documentación completa de todos los shortcodes disponibles en el plugin <strong>ULL Registro de Tratamientos RGPD</strong>.</p>
            <p>Los shortcodes te permiten mostrar información del registro en páginas públicas de WordPress.</p>
        </div>
        
        <!-- Índice -->
        <div class="ull-rt-ayuda-indice">
            <h2>Índice de Shortcodes</h2>
            <ul>
                <li><a href="#listado-tratamientos">Listado de Tratamientos</a></li>
                <li><a href="#detalle-tratamiento">Detalle de Tratamiento</a></li>
                <li><a href="#estadisticas">Estadísticas RGPD</a></li>
                <li><a href="#ejercicio-derechos">Formulario de Ejercicio de Derechos</a></li>
                <li><a href="#consultar-solicitud">Consultar Estado de Solicitud</a></li>
                <li><a href="#informacion-dpd">Información del DPD</a></li>
                <li><a href="#proponer-tratamiento">Proponer Tratamiento</a></li>
            </ul>
        </div>
        
        <!-- Shortcode 1: Listado de Tratamientos -->
        <div id="listado-tratamientos" class="ull-rt-ayuda-shortcode">
            <h2>1. Listado de Tratamientos</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_listado_tratamientos]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Muestra un listado público de todos los tratamientos registrados y activos.</p>
            </div>
            
            <div class="ull-rt-ayuda-parametros">
                <h3>Parámetros disponibles:</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Valores</th>
                            <th>Por defecto</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>vista</code></td>
                            <td>tabla | tarjetas</td>
                            <td>tabla</td>
                            <td>Tipo de visualización del listado</td>
                        </tr>
                        <tr>
                            <td><code>busqueda</code></td>
                            <td>si | no</td>
                            <td>no</td>
                            <td>Mostrar barra de búsqueda</td>
                        </tr>
                        <tr>
                            <td><code>paginacion</code></td>
                            <td>si | no</td>
                            <td>si</td>
                            <td>Activar paginación del listado</td>
                        </tr>
                        <tr>
                            <td><code>por_pagina</code></td>
                            <td>número</td>
                            <td>10</td>
                            <td>Tratamientos por página (si paginación activa)</td>
                        </tr>
                        <tr>
                            <td><code>limite</code></td>
                            <td>número | -1</td>
                            <td>-1</td>
                            <td>Límite total de tratamientos a mostrar (-1 = todos)</td>
                        </tr>
                        <tr>
                            <td><code>area</code></td>
                            <td>texto</td>
                            <td>-</td>
                            <td>Filtrar por área responsable específica</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplos de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Listado básico en formato tabla</h4>
                    <code>[ull_listado_tratamientos]</code>
                </div>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Vista de tarjetas con búsqueda</h4>
                    <code>[ull_listado_tratamientos vista="tarjetas" busqueda="si"]</code>
                </div>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Listado completo con búsqueda y paginación</h4>
                    <code>[ull_listado_tratamientos vista="tarjetas" busqueda="si" paginacion="si" por_pagina="12"]</code>
                </div>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Filtrado por área específica</h4>
                    <code>[ull_listado_tratamientos area="Secretaría General"]</code>
                </div>
            </div>
        </div>
        
        <!-- Shortcode 2: Detalle de Tratamiento -->
        <div id="detalle-tratamiento" class="ull-rt-ayuda-shortcode">
            <h2>2. Detalle de Tratamiento</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_detalle_tratamiento id="5"]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Muestra la información completa de un tratamiento específico.</p>
                <p><strong>Nota:</strong> Este shortcode se activa automáticamente cuando se hace clic en "Ver detalles" desde el listado.</p>
            </div>
            
            <div class="ull-rt-ayuda-parametros">
                <h3>Parámetros disponibles:</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Valores</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td>número</td>
                            <td>ID del tratamiento a mostrar</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplo de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Mostrar tratamiento específico</h4>
                    <code>[ull_detalle_tratamiento id="5"]</code>
                </div>
            </div>
        </div>
        
        <!-- Shortcode 3: Estadísticas -->
        <div id="estadisticas" class="ull-rt-ayuda-shortcode">
            <h2>3. Estadísticas RGPD</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_estadisticas_rgpd]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Muestra estadísticas generales del registro (total de tratamientos, transferencias internacionales, datos sensibles y distribución por áreas).</p>
            </div>
            
            <div class="ull-rt-ayuda-parametros">
                <h3>Parámetros disponibles:</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Valores</th>
                            <th>Por defecto</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>enlace_tratamientos</code></td>
                            <td>auto | URL | no</td>
                            <td>auto</td>
                            <td>Activar enlaces a listado de tratamientos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplos de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Estadísticas básicas</h4>
                    <code>[ull_estadisticas_rgpd]</code>
                </div>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Con enlaces a página específica</h4>
                    <code>[ull_estadisticas_rgpd enlace_tratamientos="/registro-tratamientos/"]</code>
                </div>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Sin enlaces</h4>
                    <code>[ull_estadisticas_rgpd enlace_tratamientos="no"]</code>
                </div>
            </div>
        </div>
        
        <!-- Shortcode 4: Ejercicio de Derechos -->
        <div id="ejercicio-derechos" class="ull-rt-ayuda-shortcode">
            <h2>4. Formulario de Ejercicio de Derechos</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_ejercicio_derechos]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Muestra un formulario público para que los interesados puedan ejercer sus derechos ARCO (Acceso, Rectificación, Cancelación, Oposición, Limitación, Portabilidad).</p>
                <p><strong>Importante:</strong> Las solicitudes se registran en el sistema y generan notificaciones al DPD.</p>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplo de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Formulario estándar</h4>
                    <code>[ull_ejercicio_derechos]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-nota">
                <p><strong>Nota:</strong> Este formulario incluye:</p>
                <ul>
                    <li>Selección del tipo de derecho a ejercer</li>
                    <li>Datos de identificación del solicitante</li>
                    <li>Descripción de la solicitud</li>
                    <li>Posibilidad de adjuntar documentación</li>
                    <li>Generación automática de número de solicitud</li>
                </ul>
            </div>
        </div>
        
        <!-- Shortcode 5: Consultar Solicitud -->
        <div id="consultar-solicitud" class="ull-rt-ayuda-shortcode">
            <h2>5. Consultar Estado de Solicitud</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_consultar_solicitud]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Formulario para que los usuarios consulten el estado de sus solicitudes de ejercicio de derechos usando su número de solicitud y email.</p>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplo de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Formulario de consulta</h4>
                    <code>[ull_consultar_solicitud]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-nota">
                <p><strong>Estados posibles:</strong></p>
                <ul>
                    <li><span class="ull-estado-badge pendiente">Pendiente</span> - En revisión</li>
                    <li><span class="ull-estado-badge en_proceso">En Proceso</span> - Se está tramitando</li>
                    <li><span class="ull-estado-badge resuelta">Resuelta</span> - Completada</li>
                    <li><span class="ull-estado-badge denegada">Denegada</span> - No procede</li>
                </ul>
            </div>
        </div>
        
        <!-- Shortcode 6: Información DPD -->
        <div id="informacion-dpd" class="ull-rt-ayuda-shortcode">
            <h2>6. Información del Delegado de Protección de Datos</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_informacion_dpd]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Muestra la información de contacto del DPD, sus funciones y los derechos que pueden ejercer los interesados.</p>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplo de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Información completa del DPD</h4>
                    <code>[ull_informacion_dpd]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-nota">
                <p><strong>Incluye:</strong></p>
                <ul>
                    <li>Datos de contacto (email, teléfono, dirección)</li>
                    <li>Funciones del Delegado de Protección de Datos</li>
                    <li>Descripción de los derechos de los interesados</li>
                </ul>
            </div>
        </div>
        
        <!-- Shortcode 7: Proponer Tratamiento -->
        <div id="proponer-tratamiento" class="ull-rt-ayuda-shortcode">
            <h2>7. Formulario para Proponer Tratamiento</h2>
            
            <div class="ull-rt-ayuda-code">
                <code>[ull_proponer_tratamiento]</code>
            </div>
            
            <div class="ull-rt-ayuda-descripcion">
                <p><strong>Descripción:</strong> Formulario para que las áreas de la Universidad propongan nuevos tratamientos de datos al DPD para su evaluación y registro.</p>
                <p><strong>Importante:</strong> Una vez aprobado por el DPD, el tratamiento se registra automáticamente.</p>
            </div>
            
            <div class="ull-rt-ayuda-ejemplos">
                <h3>Ejemplo de uso:</h3>
                
                <div class="ull-rt-ayuda-ejemplo">
                    <h4>Formulario de propuesta</h4>
                    <code>[ull_proponer_tratamiento]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-nota">
                <p><strong>Flujo del proceso:</strong></p>
                <ol>
                    <li>Área responsable completa el formulario</li>
                    <li>Propuesta se envía al DPD para evaluación</li>
                    <li>DPD revisa y emite informe</li>
                    <li>Si se aprueba, el tratamiento se registra automáticamente</li>
                    <li>Se notifica al área solicitante</li>
                </ol>
            </div>
        </div>
        
        <!-- Sección de Casos de Uso -->
        <div class="ull-rt-ayuda-casos">
            <h2>💡 Casos de Uso Comunes</h2>
            
            <div class="ull-rt-ayuda-caso">
                <h3>Página de Registro Público</h3>
                <p>Crear una página pública con el registro completo de tratamientos:</p>
                <div class="ull-rt-ayuda-code">
                    <code>[ull_estadisticas_rgpd]</code><br>
                    <code>[ull_listado_tratamientos vista="tarjetas" busqueda="si" paginacion="si" por_pagina="12"]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-caso">
                <h3>Página de Protección de Datos</h3>
                <p>Crear una página con información del DPD y formulario de derechos:</p>
                <div class="ull-rt-ayuda-code">
                    <code>[ull_informacion_dpd]</code><br>
                    <code>[ull_ejercicio_derechos]</code>
                </div>
            </div>
            
            <div class="ull-rt-ayuda-caso">
                <h3>Portal del DPD</h3>
                <p>Crear una página completa con todas las funcionalidades:</p>
                <div class="ull-rt-ayuda-code">
                    <code>[ull_informacion_dpd]</code><br>
                    <code>[ull_estadisticas_rgpd enlace_tratamientos="/registro/"]</code><br>
                    <code>[ull_ejercicio_derechos]</code><br>
                    <code>[ull_consultar_solicitud]</code>
                </div>
            </div>
        </div>
        
        <!-- Consejos y Buenas Prácticas -->
        <div class="ull-rt-ayuda-consejos">
            <h2>✅ Consejos y Buenas Prácticas</h2>
            
            <div class="ull-rt-ayuda-consejo">
                <h4>1. Página dedicada para cada shortcode</h4>
                <p>Crea páginas separadas para diferentes funcionalidades (registro, ejercicio de derechos, consultas) para mejor organización.</p>
            </div>
            
            <div class="ull-rt-ayuda-consejo">
                <h4>2. Enlaces entre páginas</h4>
                <p>Usa el parámetro <code>enlace_tratamientos</code> en estadísticas para vincular correctamente al listado.</p>
            </div>
            
            <div class="ull-rt-ayuda-consejo">
                <h4>3. Vista de tarjetas para mejor UX</h4>
                <p>La vista <code>tarjetas</code> es más visual y amigable que la vista tabla para usuarios públicos.</p>
            </div>
            
            <div class="ull-rt-ayuda-consejo">
                <h4>4. Activar búsqueda en listados grandes</h4>
                <p>Si tienes más de 20 tratamientos, activa la búsqueda con <code>busqueda="si"</code>.</p>
            </div>
            
            <div class="ull-rt-ayuda-consejo">
                <h4>5. Paginación para mejor rendimiento</h4>
                <p>Usa paginación con <code>paginacion="si"</code> y ajusta <code>por_pagina</code> según tus necesidades.</p>
            </div>
        </div>
        
        <!-- Soporte -->
        <div class="ull-rt-ayuda-soporte">
            <h2>🆘 ¿Necesitas Ayuda?</h2>
            <p>Si tienes dudas sobre el uso de los shortcodes o encuentras algún problema:</p>
            <ul>
                <li>Revisa que has copiado el shortcode correctamente</li>
                <li>Verifica que los parámetros tienen los valores correctos</li>
                <li>Consulta los ejemplos de esta página</li>
                <li>Contacta con el equipo de desarrollo</li>
            </ul>
        </div>
        
    </div>
</div>

<style>
.ull-rt-ayuda-container {
    max-width: 1200px;
    margin: 20px 0;
}

.ull-rt-ayuda-intro {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 4px;
}

.ull-rt-ayuda-indice {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 4px;
}

.ull-rt-ayuda-indice h2 {
    margin-top: 0;
    color: #0073aa;
}

.ull-rt-ayuda-indice ul {
    list-style: none;
    padding: 0;
}

.ull-rt-ayuda-indice li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.ull-rt-ayuda-indice li:last-child {
    border-bottom: none;
}

.ull-rt-ayuda-shortcode {
    background: #fff;
    border: 1px solid #ddd;
    padding: 30px;
    margin-bottom: 30px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.ull-rt-ayuda-shortcode h2 {
    margin-top: 0;
    color: #0073aa;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
}

.ull-rt-ayuda-code {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-left: 4px solid #0073aa;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    overflow-x: auto;
}

.ull-rt-ayuda-code code {
    background: none;
    padding: 0;
    font-size: 14px;
    color: #d63638;
}

.ull-rt-ayuda-descripcion {
    margin: 20px 0;
}

.ull-rt-ayuda-parametros {
    margin: 30px 0;
}

.ull-rt-ayuda-parametros h3 {
    color: #333;
    margin-bottom: 15px;
}

.ull-rt-ayuda-parametros table {
    margin-top: 10px;
}

.ull-rt-ayuda-parametros table code {
    background: #f0f0f0;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 13px;
}

.ull-rt-ayuda-ejemplos {
    margin: 30px 0;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    padding: 20px;
    border-radius: 4px;
}

.ull-rt-ayuda-ejemplos h3 {
    margin-top: 0;
    color: #333;
}

.ull-rt-ayuda-ejemplo {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border-left: 3px solid #00a32a;
    border-radius: 3px;
}

.ull-rt-ayuda-ejemplo h4 {
    margin: 0 0 10px 0;
    color: #00a32a;
    font-size: 14px;
}

.ull-rt-ayuda-ejemplo code {
    display: block;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 3px;
    font-size: 13px;
    color: #d63638;
}

.ull-rt-ayuda-nota {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
}

.ull-estado-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.ull-estado-badge.pendiente {
    background: #f0f0f0;
    color: #666;
}

.ull-estado-badge.en_proceso {
    background: #fff3cd;
    color: #856404;
}

.ull-estado-badge.resuelta {
    background: #d4edda;
    color: #155724;
}

.ull-estado-badge.denegada {
    background: #f8d7da;
    color: #721c24;
}

.ull-rt-ayuda-casos {
    background: #e7f3ff;
    border: 1px solid #0073aa;
    padding: 30px;
    margin: 30px 0;
    border-radius: 4px;
}

.ull-rt-ayuda-casos h2 {
    margin-top: 0;
    color: #0073aa;
}

.ull-rt-ayuda-caso {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.ull-rt-ayuda-caso h3 {
    margin-top: 0;
    color: #333;
}

.ull-rt-ayuda-consejos {
    background: #d4edda;
    border: 1px solid #28a745;
    padding: 30px;
    margin: 30px 0;
    border-radius: 4px;
}

.ull-rt-ayuda-consejos h2 {
    margin-top: 0;
    color: #155724;
}

.ull-rt-ayuda-consejo {
    background: #fff;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    border-left: 4px solid #28a745;
}

.ull-rt-ayuda-consejo h4 {
    margin: 0 0 10px 0;
    color: #155724;
}

.ull-rt-ayuda-soporte {
    background: #f8d7da;
    border: 1px solid #dc3545;
    border-left: 4px solid #dc3545;
    padding: 20px;
    margin: 30px 0;
    border-radius: 4px;
}

.ull-rt-ayuda-soporte h2 {
    margin-top: 0;
    color: #721c24;
}
</style>
