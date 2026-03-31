# 🌞 IntiSmart - Sistema de Monitoreo UV

<img src="src/img/logo/LOGO_BLANCO.avif" alt="IntiSmart Logo" width="200">



**IntiSmart** es una empresa peruana líder en tecnología de monitoreo ambiental, especializada en sistemas de alerta temprana para radiación ultravioleta (UV). Desarrollamos dispositivos inteligentes que protegen la salud pública mediante el monitoreo en tiempo real de los niveles de radiación solar.

## 🚀 Descripción del Proyecto

Este repositorio contiene el sitio web corporativo de IntiSmart, una aplicación web completa que incluye:

- **Portal web corporativo** con información de productos y servicios
- **Sistema de monitoreo UV en tiempo real** con datos de múltiples estaciones
- **Chatbot inteligente (IntiBot)** powered by Google Gemini AI
- **Sistema de registro y visualización de datos** ambientales
- **Formularios de contacto** con integración de email
- **Panel administrativo** para gestión de contenido

## 🏗️ Tecnologías Utilizadas

### Backend
- **PHP 8.x** - Lenguaje principal del servidor
- **MySQL** - Base de datos (AWS RDS)
- **Composer** - Gestión de dependencias PHP
- **Patrón MVC** - Arquitectura del proyecto

### Frontend
- **HTML5/CSS3** - Estructura y estilos
- **JavaScript (ES6+)** - Interactividad
- **SCSS/Sass** - Preprocesador CSS
- **Gulp** - Automatización de tareas
- **Bootstrap/CSS Grid** - Responsive design

### APIs & Servicios
- **Google Gemini AI** - Chatbot inteligente
- **Amazon SES** - Servicio de emails
- **AWS RDS** - Base de datos en la nube
- **Chart.js** - Visualización de datos

### Herramientas de Desarrollo
- **Gulp** - Build system y automatización
- **Babel** - Transpilación de JavaScript
- **Autoprefixer** - Compatibilidad CSS
- **Sourcemaps** - Debugging

## 📁 Estructura del Proyecto

```
INTISMART-FINAL-WEB/
├── 📁 controllers/          # Controladores MVC
│   ├── ApiController.php
│   ├── ChatbotController.php
│   └── PaginasController.php
├── 📁 models/              # Modelos de datos
│   ├── ActiveRecord.php
│   ├── RegistrosRadiacion.php
│   └── ChatbotMensaje.php
├── 📁 services/            # Servicios y lógica de negocio
│   ├── ChatbotService.php
│   └── EmailService.php
├── 📁 views/               # Vistas y templates
│   ├── 📁 paginas/
│   └── 📁 layout/
├── 📁 public/              # Directorio público
│   ├── index.php
│   └── 📁 build/          # Assets compilados
├── 📁 src/                 # Código fuente
│   ├── 📁 scss/           # Estilos SCSS
│   ├── 📁 js/             # JavaScript
│   └── 📁 img/            # Imágenes
├── 📁 includes/            # Configuración
├── 📁 cache/              # Cache de datos UV
├── 📁 vendor/             # Dependencias Composer
├── gulpfile.js            # Configuración Gulp
├── composer.json          # Dependencias PHP
├── package.json           # Dependencias Node.js
└── .env                   # Variables de entorno
```

## ⚙️ Instalación y Configuración

### Prerrequisitos
- **PHP 8.0+** con extensiones: PDO, mysqli, curl, mbstring
- **MySQL 5.7+** o **MariaDB 10.2+**
- **Composer** para gestión de dependencias PHP
- **Node.js 16+** y **npm** para el build system
- **Servidor web** (Apache/Nginx) con mod_rewrite

### 🔧 Instalación

1. **Clonar el repositorio:**
```bash
git clone https://github.com/tu-usuario/intismart-final-web.git
cd intismart-final-web
```

2. **Instalar dependencias PHP:**
```bash
composer install
```

3. **Instalar dependencias Node.js:**
```bash
npm install
```

4. **Configurar variables de entorno:**
```bash
cp .env.example .env
# Editar .env con tus configuraciones
```

5. **Configurar base de datos:**
```sql
-- Importar estructura desde basedatosbot.txt
mysql -u usuario -p nombre_bd < basedatosbot.txt
```

6. **Compilar assets:**
```bash
# Desarrollo (con watch)
npm run dev
# Producción
npm run build
```

### 🔑 Variables de Entorno Principales

```env
# Base de datos
DB_HOST=localhost
DB_NAME=dbintismart
DB_USER=tu_usuario
DB_PASSWORD=tu_password

# APIs
GEMINI_API_KEY=tu_api_key_gemini

# Email (Amazon SES)
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_USERNAME=tu_ses_username
MAIL_PASSWORD=tu_ses_password
```

## 🎯 Características Principales

### 📊 Monitoreo UV en Tiempo Real
- Datos de múltiples estaciones meteorológicas
- Visualización con gráficos interactivos
- Alertas automáticas por niveles peligrosos
- Historial de registros por fecha

### 🤖 Chatbot Inteligente (IntiBot)
- Powered by Google Gemini AI
- FAQs automatizadas sobre protección UV
- Agendamiento de citas
- Información de productos en tiempo real

### 📱 Responsive Design
- Optimizado para móviles, tablets y desktop
- PWA-ready (Progressive Web App)
- Carga rápida con lazy loading
- Imágenes optimizadas (WebP)

### 🔒 Seguridad
- Rate limiting en APIs
- Validación de datos server-side
- Headers de seguridad configurados
- Protección CSRF

## 🚀 Scripts Disponibles

```bash
# Desarrollo
npm run dev          # Compila assets y activa watch mode
npm run build        # Compilación para producción
npm run clean        # Limpia archivos compilados

# PHP
composer install     # Instala dependencias
composer dump-autoload  # Regenera autoloader
```

## 📈 API Endpoints

### Datos UV
```
GET  /api/registros           # Obtener registros UV
GET  /api/registros/{fecha}   # Registros por fecha
POST /api/registros          # Crear nuevo registro
```

### Chatbot
```
POST /api/chatbot/mensaje    # Enviar mensaje al bot
GET  /api/chatbot/session    # Obtener sesión actual
```

### Contacto
```
POST /api/contacto          # Enviar formulario de contacto
```

## 🌍 Despliegue

### Producción (Hostinger/cPanel)
1. Subir archivos via FTP/Git
2. Configurar variables de entorno
3. Ejecutar `composer install --no-dev`
4. Ejecutar `npm run build`
5. Configurar virtual host apuntando a `/public`

### Desarrollo Local (XAMPP)
1. Colocar proyecto en `htdocs/`
2. Crear base de datos en phpMyAdmin
3. Configurar `.env` con datos locales
4. Acceder via `http://localhost/INTISMART-FINAL-WEB/public`

## 🧪 Testing

```bash
# Testing básico de endpoints
php public/test_prueba.php?estacion=1&fecha=2025-06-29&debug=full

# Validar compilación
npm run build
```

## 📝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📋 Roadmap

- [ ] 🔄 API REST completa
- [ ] 📱 App móvil nativa
- [ ] 🌐 Internacionalización (i18n)
- [ ] 📊 Dashboard avanzado de analytics
- [ ] 🔔 Notificaciones push
- [ ] 🗺️ Mapa interactivo de estaciones

## 🏢 Acerca de IntiSmart

**IntiSmart** es una empresa peruana pionera en sistemas de alerta temprana ambiental. Nuestro dispositivo estrella **IntiUV+** está instalado en más de 25 ubicaciones, protegiendo comunidades mediante monitoreo continuo de radiación UV.

### 🤝 Alianzas Estratégicas
- Universidad Nacional Agraria La Molina (UNALM)
- Ministerio de Energía y Minas (MINEM)
- Incubagraria
- Centro de Gestión y Tecnología Ambiental

## 📞 Contacto

- **Sitio Web:** [www.intismart.com](https://www.intismart.com)
- **Email:** info@intismart.com
- **Teléfono:** +51 994-146-924
- **WhatsApp:** [Chat directo](https://wa.me/+51994146924)

### 🌐 Redes Sociales
- **LinkedIn:** [IntiSmart](https://linkedin.com/company/intismart)
- **Instagram:** [@inti.smart](https://instagram.com/inti.smart)
- **TikTok:** [@.intismart](https://tiktok.com/@.intismart)
- **Twitter:** [@Intismart](https://x.com/Intismart)

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

**Desarrollado con ❤️ por el equipo IntiSmart**  
*Protegiendo vidas mediante tecnología e innovación* 🌞🛡️
