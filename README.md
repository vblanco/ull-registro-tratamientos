# Plugin ULL Registro de Tratamientos RGPD

Sistema completo de gestión del Registro de Actividades de Tratamiento de Datos Personales para la Universidad de La Laguna (ULL), conforme al RGPD.

## Características

### 1. Gestión de Actividades de Tratamiento
- Registro de las 33 actividades de tratamiento de la ULL
- Gestión completa de bases jurídicas, finalidades, colectivos y categorías de datos
- Control de transferencias internacionales
- Gestión de plazos de conservación
- Historial de versiones y cambios

### 2. Sistema de Informes del DPD
- Generación automática de informes:
  - Registro completo de tratamientos
  - Transferencias internacionales
  - Categorías especiales de datos
  - Ejercicio de derechos
  - Consultas al DPD
  - Estadísticas generales
- Exportación a PDF
- Informes personalizados

### 3. Gestión de Consultas al DPD
- Sistema de tickets para consultas internas
- Gestión de prioridades
- Seguimiento de tiempos de respuesta
- Notificaciones automáticas por email
- Estadísticas de consultas

### 4. Ejercicio de Derechos RGPD
- Formulario público para interesados
- Gestión de los 7 derechos RGPD:
  - Derecho de Acceso
  - Derecho de Rectificación
  - Derecho de Supresión (Derecho al Olvido)
  - Derecho de Oposición
  - Derecho a la Limitación del Tratamiento
  - Derecho a la Portabilidad
  - Derecho a no ser objeto de decisiones automatizadas
- Control de plazos (1 mes)
- Alertas automáticas de vencimientos
- Notificaciones por email
- Numeración automática de solicitudes

### 5. Audit Log
- Registro completo de todas las acciones
- Trazabilidad total del sistema
- Información de IP y usuario
- Búsqueda y filtrado avanzado

## Instalación

1. Subir el archivo ZIP a WordPress: `Plugins > Añadir nuevo > Subir plugin`
2. Activar el plugin
3. Acceder al menú "Registro RGPD" en el panel de administración

El plugin creará automáticamente:
- 6 tablas en la base de datos
- Roles de usuario (DPD, Consultor)
- Datos iniciales de las actividades de tratamiento

## Uso

### Para el DPD (Delegado de Protección de Datos)

1. **Dashboard**: Acceso rápido a estadísticas y acciones
2. **Tratamientos**: Gestionar las 33 actividades
3. **Informes DPD**: Generar informes automáticos
4. **Consultas**: Gestionar consultas internas
5. **Ejercicio de Derechos**: Gestionar solicitudes de interesados
6. **Audit Log**: Revisar el registro de auditoría

### Para Interesados (Público)

Usar el shortcode en cualquier página/entrada:
```
[ull_ejercicio_derechos]
```

Esto mostrará el formulario público para ejercer derechos RGPD.

## Roles y Capacidades

### Rol DPD
- Gestión completa del sistema
- Acceso a todos los módulos
- Generación de informes
- Visualización del audit log

### Rol Consultor
- Solo lectura de tratamientos e informes
- Sin capacidad de edición

### Administrador
- Acceso completo como el DPD

## Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Soporte

Para soporte técnico contactar con:
- Email: dpd@ull.es
- Web: www.ull.es

## Licencia

GPL v2 o posterior

## Autor

Universidad de La Laguna - Delegado de Protección de Datos

## Versión

1.0.0 - Enero 2026
