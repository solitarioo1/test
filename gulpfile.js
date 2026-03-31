const {src, dest, watch, series, parallel} = require('gulp');
const rimraf = require('rimraf');

//CSS Y SASS
const sass = require('gulp-sass')(require('sass'));
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');

//JavaScript
const babel = require('gulp-babel');
const terser = require('gulp-terser');
const concat = require('gulp-concat');
const sourcemaps = require('gulp-sourcemaps');

//IMAGENES
const fs = require('fs').promises;
const path = require('path');
const sharp = require('sharp');
const glob = require('glob-promise');

// ============================================
// FUNCIONES JAVASCRIPT CORREGIDAS
// ============================================

// JavaScript Chatbot (solo chatbot.js)
function javascriptChatbot(done) {
    return src('src/js/chatbot.js')
        .pipe(sourcemaps.init())
        .pipe(babel({presets: ['@babel/preset-env']}))
        .pipe(concat('chatbot.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('.'))
        .pipe(dest('./public/build/js'))
        .on('end', () => {
            console.log('✓ JavaScript Chatbot compilado');
            done();
        });
}

// JavaScript Principal (todos los archivos de main/)
function javascriptMain(done) {
    return src('src/js/main/**/*.js')
        .pipe(sourcemaps.init())
        .pipe(babel({presets: ['@babel/preset-env']}))
        .pipe(concat('main.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('.'))
        .pipe(dest('./public/build/js'))
        .on('end', () => {
            console.log('✓ JavaScript Principal compilado');
            done();
        });
}

// JavaScript de Charts (todos los archivos de charts/)
function javascriptCharts(done) {
    return src('src/js/charts/**/*.js')
        .pipe(sourcemaps.init())
        .pipe(babel({presets: ['@babel/preset-env']}))
        .pipe(concat('charts.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('.'))
        .pipe(dest('./public/build/js'))
        .on('end', () => {
            console.log('✓ JavaScript de Charts compilado');
            done();
        });
}

// Compilar todo el JavaScript
const javascript = parallel(javascriptChatbot, javascriptMain, javascriptCharts);

// ============================================
// FUNCIÓN CSS MINIFICADA
// ============================================
function css() {
    return src('src/scss/app.scss')
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(cleanCSS()) // 🎯 Minificación real
        .pipe(rename('app.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('./public/build/css'))
        .on('end', () => {
            console.log('✓ CSS compilado y minificado con cleanCSS');
        });
}

// ============================================
// FUNCIONES DE LIMPIEZA
// ============================================

// Función helper para eliminar archivos con promesa
function removeFile(file) {
    return new Promise((resolve, reject) => {
        rimraf(file, (err) => {
            if (err) reject(err);
            else resolve();
        });
    });
}

// Eliminar archivos borrados
async function cleanDeletedFiles() {
    const srcFiles = await glob('src/**/*');
    const buildFiles = await glob('./public/build/**/*');
    
    const srcPaths = srcFiles.map(file => {
        const relativePath = path.relative('src', file);
        return relativePath;
    });
    
    const filesToDelete = buildFiles.filter(file => {
        const relativePath = path.relative('./public/build', file);
        const srcFile = path.join('src', relativePath);
        return !srcPaths.includes(relativePath) && !fs.statSync(file).isDirectory();
    });
    
    if (filesToDelete.length > 0) {
        for (const file of filesToDelete) {
            await removeFile(file);
        }
        console.log(`✓ Eliminados ${filesToDelete.length} archivos obsoletos`);
    }
}

// ============================================
// PROCESAMIENTO DE IMÁGENES (SOLO WEBP)
// ============================================

async function processImageWebP(inputPath, outputPath) {
    try {
        // Asegurar que existe el directorio de salida
        await fs.mkdir(path.dirname(outputPath), { recursive: true });
        
        const image = sharp(inputPath);
        await image.webp({ quality: 80 }).toFile(outputPath);
        
        console.log(`✓ WebP creado: ${path.basename(outputPath)}`);
    } catch (error) {
        console.error(`✗ Error procesando ${path.basename(inputPath)}:`, error.message);
    } 
}

async function images(done) {
    try {
        // Obtener todas las carpetas dentro de src/img automáticamente
        const imageDirs = await fs.readdir('src/img', { withFileTypes: true });
        const folders = imageDirs
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);
            
        // También procesar imágenes directamente en la carpeta img
        folders.push(''); // Para procesar archivos en la raíz de src/img
        
        console.log('🖼️  Iniciando procesamiento de imágenes...');
        
        // Procesar imágenes para cada carpeta
        for (const folder of folders) {
            // Ruta relativa para búsqueda de imágenes
            const searchPath = folder ? `src/img/${folder}/*.{jpg,jpeg,png}` : 'src/img/*.{jpg,jpeg,png}';
            
            // Buscar todas las imágenes en la carpeta actual
            const imageFiles = await glob(searchPath);
            
            if (imageFiles.length > 0) {
                console.log(`📁 Procesando carpeta: ${folder || 'raíz'} (${imageFiles.length} archivos)`);
            }
            
            for (const inputPath of imageFiles) {
                const pathParts = path.parse(inputPath);
                const relativePath = path.relative('src/img', path.dirname(inputPath));
                const outputFolder = relativePath ? `./public/build/img/${relativePath}` : './public/build/img';
                
                // Crear carpeta de destino si no existe
                await fs.mkdir(outputFolder, { recursive: true });
                
                // Solo procesar a WebP
                await processImageWebP(
                    inputPath,
                    path.join(outputFolder, `${pathParts.name}.webp`)
                );
            }
        }
        
        console.log('✅ Todas las imágenes han sido convertidas a WebP');
        done();
    } catch (error) {
        console.error('❌ Error procesando imágenes:', error);
        done(error);
    }
}

// ============================================
// FUNCIÓN DE DESARROLLO
// ============================================
function dev() {
    console.log('🚀 Iniciando modo desarrollo...');
    console.log('👀 Vigilando cambios en:');
    console.log('   - SCSS: src/scss/**/*.scss');
    console.log('   - JS Chatbot: src/js/chatbot.js');
    console.log('   - JS Principal: src/js/main/**/*.js');
    console.log('   - JS Charts: src/js/charts/**/*.js');
    
    // Vigilar archivos CSS
    watch('src/scss/**/*.scss', css);
    
    // Vigilar archivos JavaScript específicos
    watch('src/js/chatbot.js', javascriptChatbot);
    watch('src/js/main/**/*.js', javascriptMain);
    watch('src/js/charts/**/*.js', javascriptCharts);
    
    // Vigilar eliminaciones y crear/actualizar archivos
    watch('src/**/*', { events: ['unlink', 'unlinkDir'] }, cleanDeletedFiles);
}

// ============================================
// TAREAS DE CONSTRUCCIÓN
// ============================================

// Compilar todo para producción
function build(done) {
    console.log('🏗️  Compilando proyecto completo...');
    return series(
        parallel(css, javascript),
        images
    )(done);
}

// Limpiar completamente la carpeta build
function cleanBuild(done) {
    rimraf('./public/build', () => {
        console.log('🗑️  Carpeta build limpiada');
        done();
    });
}

// ============================================
// EXPORTS
// ============================================
exports.css = css;
exports.javascript = javascript;
exports.javascriptChatbot = javascriptChatbot;
exports.javascriptMain = javascriptMain;
exports.javascriptCharts = javascriptCharts;
exports.images = images;
exports.dev = dev;
exports.build = build;
exports.clean = cleanBuild;
exports.cleanDeletedFiles = cleanDeletedFiles;

// Tarea por defecto
// exports.default = series(css, javascript, images, dev);
exports.default = series(css, javascript, dev);
