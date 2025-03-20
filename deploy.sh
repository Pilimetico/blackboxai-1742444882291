#!/bin/bash

# Sistema de Rifas - Script de Despliegue
# Este script automatiza el proceso de despliegue del sistema

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${2}${1}${NC}"
}

# Función para verificar resultado
check_result() {
    if [ $? -eq 0 ]; then
        print_message "✅ $1" "${GREEN}"
    else
        print_message "❌ $1" "${RED}"
        exit 1
    fi
}

# Inicio del despliegue
print_message "🚀 Iniciando despliegue del Sistema de Rifas..." "${YELLOW}"

# Verificar permisos de ejecución
print_message "\n📋 Verificando permisos..." "${YELLOW}"

# Crear directorios necesarios
directories=(
    "includes"
    "admin"
    "assets/uploads"
    "assets/images"
    "installer"
)

for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        check_result "Creando directorio: $dir"
    fi
done

# Establecer permisos
print_message "\n🔒 Configurando permisos..." "${YELLOW}"

# Permisos para directorios
find . -type d -exec chmod 755 {} \;
check_result "Permisos de directorios (755)"

# Permisos para archivos
find . -type f -exec chmod 644 {} \;
check_result "Permisos de archivos (644)"

# Permisos especiales para uploads
chmod -R 755 assets/uploads
check_result "Permisos especiales para directorio uploads"

# Crear archivos .htaccess
print_message "\n📝 Creando archivos .htaccess..." "${YELLOW}"

# .htaccess principal
cat > .htaccess << 'EOL'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Seguridad
Options -Indexes
ServerSignature Off

# PHP configuración
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300

# Seguridad adicional
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options SAMEORIGIN
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
EOL
check_result "Creación de .htaccess principal"

# .htaccess para uploads
cat > assets/uploads/.htaccess << 'EOL'
# Denegar acceso a archivos PHP
<FilesMatch "\.(?i:php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Permitir solo imágenes
<FilesMatch "\.(?i:gif|jpg|jpeg|png)$">
    Order deny,allow
    Allow from all
</FilesMatch>
EOL
check_result "Creación de .htaccess para uploads"

# Crear archivo de configuración de ejemplo
print_message "\n⚙️ Creando archivo de configuración de ejemplo..." "${YELLOW}"

cat > includes/config.example.php << 'EOL'
<?php
// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombre_db');
define('DB_USER', 'usuario_db');
define('DB_PASS', 'password_db');

// Configuración de WhatsApp
define('COUNTRY_CODE', '34');
define('ADMIN_WHATSAPP', '123456789');

// Configuración del sitio
define('SITE_NAME', 'Sistema de Rifas');
define('SITE_URL', 'https://tu-dominio.com');

// Configuración de seguridad
define('SECURITY_KEY', ''); // Se generará durante la instalación
define('SESSION_LIFETIME', 3600);

// Configuración de archivos
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif');

// Configuración de bloqueo temporal
define('DEFAULT_BLOCK_DURATION', 30); // minutos
EOL
check_result "Creación de archivo de configuración de ejemplo"

# Crear archivo de validación
print_message "\n🔍 Verificando archivo de validación..." "${YELLOW}"
if [ -f "validate.php" ]; then
    check_result "Archivo de validación existe"
else
    print_message "❌ Archivo validate.php no encontrado" "${RED}"
    exit 1
fi

# Verificar archivos requeridos
print_message "\n📋 Verificando archivos requeridos..." "${YELLOW}"
required_files=(
    "README.md"
    "improved_prompt.md"
    "technical_implementation.md"
    "deployment_guide.md"
    "validate.php"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        check_result "Archivo encontrado: $file"
    else
        print_message "❌ Archivo no encontrado: $file" "${RED}"
        exit 1
    fi
done

# Crear archivo de compresión para distribución
print_message "\n📦 Creando archivo de distribución..." "${YELLOW}"
zip -r sistema-rifas.zip . -x "*.git*" "*.DS_Store" "node_modules/*" "deploy.sh"
check_result "Creación de archivo sistema-rifas.zip"

# Ejecutar validación
print_message "\n🔍 Ejecutando validación del sistema..." "${YELLOW}"
php validate.php
check_result "Validación del sistema"

print_message "\n✨ Despliegue completado exitosamente!" "${GREEN}"
print_message "\n📝 Próximos pasos:" "${YELLOW}"
print_message "1. Subir sistema-rifas.zip a tu hosting" "${NC}"
print_message "2. Descomprimir el archivo en el servidor" "${NC}"
print_message "3. Acceder a https://tu-dominio.com/installer" "${NC}"
print_message "4. Seguir el asistente de instalación" "${NC}"
print_message "5. Verificar el funcionamiento en https://tu-dominio.com/admin" "${NC}"