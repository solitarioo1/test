#!/bin/bash
set -e

# Genera el archivo .env dentro del contenedor a partir de las
# variables de entorno que pasó EasyPanel como build-args
cat > /var/www/html/.env << EOF
# Aplicación
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

# Base de datos principal
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_CHARSET=${DB_CHARSET:-utf8mb4}

# Base de datos chatbot
CHATBOT_DB_NAME=${CHATBOT_DB_NAME}
CHATBOT_DB_USER=${CHATBOT_DB_USER}
CHATBOT_DB_PASSWORD=${CHATBOT_DB_PASSWORD}

# Google Gemini AI
GEMINI_API_KEY=${GEMINI_API_KEY}

# Email (Amazon SES o SMTP)
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_FROM=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-IntiSmart}
EOF

# Ajustar permisos del .env
chown www-data:www-data /var/www/html/.env
chmod 640 /var/www/html/.env

echo "✅ Archivo .env generado correctamente"

exec "$@"
