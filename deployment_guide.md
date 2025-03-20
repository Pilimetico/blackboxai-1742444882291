# Guía de Despliegue - Sistema de Rifas

Esta guía te ayudará a instalar el sistema en tu hosting de Hostinger.

## Requisitos Previos

- Cuenta activa en Hostinger
- Dominio configurado
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Acceso FTP o File Manager de Hostinger

## Pasos de Instalación

### 1. Subir Archivos

#### Usando File Manager:
1. Accede al panel de control de Hostinger
2. Ve a "File Manager"
3. Navega hasta la carpeta `public_html`
4. Sube todos los archivos del sistema
5. Asegúrate que la estructura de carpetas se mantiene:
   ```
   public_html/
   ├── admin/
   ├── frontend/
   ├── includes/
   ├── installer/
   ├── sql/
   ├── uploads/
   ├── .htaccess
   └── error.php
   ```

#### Usando FTP:
1. Usa un cliente FTP (FileZilla, etc.)
2. Conecta usando las credenciales proporcionadas por Hostinger
3. Sube todos los archivos a `public_html`
4. Mantén la misma estructura de carpetas

### 2. Configurar Permisos

Establece los siguientes permisos:
- Carpetas: `755`
- Archivos: `644`
- Carpeta `uploads`: `775`

```bash
chmod 755 */
chmod 644 *.*
chmod 775 uploads/
```

### 3. Crear Base de Datos

1. En el panel de Hostinger, ve a "Databases > MySQL"
2. Crea una nueva base de datos
3. Anota los siguientes datos:
   - Nombre de la base de datos
   - Usuario
   - Contraseña
   - Host (generalmente localhost)

### 4. Ejecutar el Instalador

1. Accede a `http://tu-dominio.com/installer/`
2. Completa el formulario con:
   - Datos de la base de datos creada
   - Información del administrador
   - Configuración de WhatsApp

### 5. Verificar la Instalación

1. Accede al panel de administración: `http://tu-dominio.com/admin/`
2. Inicia sesión con las credenciales configuradas
3. Verifica que puedas:
   - Crear rifas
   - Subir imágenes
   - Gestionar reservas
   - Configurar el sistema

## Solución de Problemas

### Problemas Comunes

1. **Error de Permisos**
   - Verifica los permisos de carpetas y archivos
   - La carpeta `uploads` debe tener permisos de escritura

2. **Error de Base de Datos**
   - Confirma las credenciales de la base de datos
   - Verifica que el usuario tenga todos los permisos necesarios

3. **Error 500**
   - Revisa los logs de error de PHP
   - Verifica la versión de PHP en el hosting

### Contacto de Soporte

Si encuentras problemas durante la instalación:
1. Revisa los logs de error en el panel de Hostinger
2. Contacta al soporte de Hostinger si es un problema del hosting
3. Consulta la documentación del sistema para problemas específicos

## Recomendaciones de Seguridad

1. **Cambiar Contraseñas**
   - Modifica la contraseña del administrador después de la instalación
   - Usa contraseñas fuertes (mínimo 12 caracteres)

2. **Backups**
   - Configura backups automáticos en Hostinger
   - Realiza backups manuales periódicamente

3. **SSL/HTTPS**
   - Activa el certificado SSL desde el panel de Hostinger
   - Configura la redirección HTTPS en el .htaccess

## Mantenimiento

1. **Actualizaciones**
   - Mantén PHP actualizado
   - Revisa periódicamente las actualizaciones del sistema

2. **Monitoreo**
   - Revisa los logs de error regularmente
   - Monitorea el espacio en disco y uso de base de datos

3. **Optimización**
   - Limpia periódicamente archivos temporales
   - Optimiza la base de datos cuando sea necesario

## Notas Adicionales

- El sistema está optimizado para PHP 7.4+
- Se recomienda usar MySQL 5.7 o MariaDB 10+
- Mantén copias de seguridad regulares
- Revisa periódicamente los logs de error
- Configura correctamente el timezone en PHP

## Soporte

Para soporte adicional:
- Consulta la documentación completa
- Contacta al soporte de Hostinger para problemas relacionados con el hosting
- Verifica los logs de error para problemas específicos