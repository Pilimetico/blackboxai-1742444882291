# Sistema de Rifas - Conversión a PHP/MySQL con Instalador para Hostinger

## Objetivo
Convertir el repositorio https://github.com/SrMeticoo/blackboxai-1742405124063.git a un sistema de rifas profesional en PHP con base de datos MySQL, incluyendo un instalador automatizado para Hostinger.

## Características Principales

### 1. Instalador Automatizado
- Formulario de configuración inicial con:
  - Conexión a base de datos (host, nombre, usuario, contraseña)
  - Datos del administrador (usuario y contraseña)
  - Configuración de WhatsApp (indicativo del país y número)
- Creación automática de tablas y estructura inicial
- Validación de requisitos del sistema
- Generación del archivo de configuración

### 2. Sistema de Reservas
- Selección múltiple de boletos
- Formulario de reserva con datos del cliente
- Integración con WhatsApp API para notificaciones
- Sistema de bloqueo temporal de números:
  - Opción configurable para bloquear números por X tiempo
  - Panel de gestión de bloqueos
  - Estado de boletos (libre, bloqueado, pagado)

### 3. Panel de Administración (CMS)
- Dashboard con estadísticas y resumen
- Gestión de rifas:
  - CRUD completo de rifas
  - Carga de imágenes
  - Etiquetas personalizables (Nuevo, Vendido, Descuento)
- Gestión de reservas:
  - Vista agrupada por cliente
  - Marcado de pagos
  - Filtros de estado
- Configuración del sitio:
  - Logo
  - Banner
  - Favicon
  - Plantillas de mensajes
- Gestor de archivos multimedia
- Cambio de contraseña
- Registro de actividades

### 4. Frontend
- Diseño responsive (Mobile First)
- Filtros inteligentes para boletos:
  - Rangos dinámicos basados en cantidad total
  - Ejemplo: 10,000 boletos = 10 rangos de 1,000
- Vista de rifas activas
- Sistema de reserva intuitivo
- Integración con WhatsApp

## Estructura de Base de Datos

### Tablas Principales
1. admin_users
   - Credenciales y datos de administradores
2. raffles
   - Información de rifas
   - Configuración de boletos
   - Estados y etiquetas
3. tickets
   - Números de boletos
   - Estado de pago
   - Bloqueo temporal
4. reservations
   - Datos del cliente
   - Relación con boletos
   - Estado de reserva
5. settings
   - Configuración del sitio
   - Plantillas de mensajes
   - Rutas de archivos
6. blocked_numbers
   - Gestión de bloqueos temporales

## Requisitos Técnicos
- PHP 7.4+
- MySQL 5.7+
- Extensiones PHP:
  - PDO
  - MySQLi
  - GD
  - cURL

## Seguridad
- Validación CSRF
- Sanitización de inputs
- Prevención de SQL Injection
- Manejo seguro de archivos
- Encriptación de contraseñas
- Control de sesiones

## Optimizaciones
- Caché de consultas frecuentes
- Optimización de imágenes
- Paginación de resultados
- Índices de base de datos
- Transacciones para operaciones críticas

## Consideraciones de Implementación
1. Usar PDO para conexiones de base de datos
2. Implementar patrón Singleton para conexión DB
3. Separar lógica de negocio de presentación
4. Mantener código modular y reutilizable
5. Documentar funciones y procedimientos
6. Implementar manejo de errores robusto
7. Seguir estándares PSR para código PHP

## Entregables
1. Código fuente completo
2. Documentación de instalación
3. Manual de usuario
4. Script de instalación
5. Estructura de base de datos
6. Archivos de configuración base

## Notas Adicionales
- El sistema debe ser fácil de instalar en Hostinger
- Interfaz administrativa intuitiva tipo dashboard
- Diseño moderno y profesional
- Código limpio y bien documentado
- Sistema preparado para marca blanca