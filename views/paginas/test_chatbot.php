<?php
/**
 * SISTEMA DE TESTING Y DEBUG PARA CHATBOT UV
 * Archivo: test_chatbot.php
 */

// Configuración de debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Headers para CORS y JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class ChatbotTester 
{
    private $testResults = [];
    private $chatbotFile = 'chatbot_uv.php'; // Nombre de tu archivo principal
    
    public function runAllTests() {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Test Chatbot UV - Sistema de Diagnóstico</title>
            <style>
                * { box-sizing: border-box; }
                body { 
                    font-family: 'Segoe UI', sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }
                .container { 
                    max-width: 1200px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 15px; 
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(45deg, #2196F3, #21CBF3); 
                    color: white; 
                    padding: 30px; 
                    text-align: center;
                }
                .header h1 { margin: 0; font-size: 2.5em; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .content { padding: 30px; }
                .test-section { 
                    margin-bottom: 30px; 
                    padding: 25px; 
                    border: 2px solid #f0f0f0; 
                    border-radius: 10px;
                    background: #fafafa;
                }
                .test-section h2 { 
                    color: #333; 
                    margin-top: 0; 
                    padding-bottom: 10px; 
                    border-bottom: 2px solid #eee;
                }
                .status { 
                    padding: 10px 15px; 
                    border-radius: 5px; 
                    margin: 10px 0; 
                    font-weight: bold;
                }
                .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                .status.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
                .status.info { background: #cce7ff; color: #004085; border: 1px solid #99d6ff; }
                .code-block { 
                    background: #f8f9fa; 
                    padding: 15px; 
                    border-radius: 5px; 
                    border-left: 4px solid #007bff;
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    overflow-x: auto;
                    margin: 10px 0;
                }
                .chat-tester {
                    background: white;
                    border: 2px solid #ddd;
                    border-radius: 10px;
                    padding: 20px;
                    margin-top: 20px;
                }
                .chat-messages {
                    height: 300px;
                    overflow-y: auto;
                    border: 1px solid #eee;
                    padding: 15px;
                    margin-bottom: 15px;
                    background: #f9f9f9;
                }
                .message {
                    margin-bottom: 10px;
                    padding: 10px;
                    border-radius: 10px;
                }
                .message.user { background: #007bff; color: white; margin-left: 20%; }
                .message.bot { background: #e9ecef; color: #333; margin-right: 20%; }
                .message.system { background: #ffc107; color: #333; text-align: center; font-style: italic; }
                .input-group { display: flex; gap: 10px; }
                .input-group input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
                .input-group button { 
                    padding: 10px 20px; 
                    background: #007bff; 
                    color: white; 
                    border: none; 
                    border-radius: 5px; 
                    cursor: pointer;
                }
                .input-group button:hover { background: #0056b3; }
                .metrics { 
                    display: grid; 
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                    gap: 15px; 
                    margin: 20px 0;
                }
                .metric-card { 
                    background: white; 
                    padding: 20px; 
                    border-radius: 10px; 
                    border: 1px solid #eee;
                    text-align: center;
                }
                .metric-value { font-size: 2em; font-weight: bold; color: #007bff; }
                .metric-label { color: #666; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔧 Diagnóstico Chatbot UV</h1>
                    <p>Sistema completo de testing y debugging</p>
                </div>
                <div class='content'>";
        
        // Ejecutar todas las pruebas
        $this->testPHPEnvironment();
        $this->testChatbotFile();
        $this->testAPIsConnection();
        $this->testResponseLogic();
        $this->showInteractiveTester();
        $this->showJavaScriptIntegration();
        
        echo "      </div>
            </div>
            
            <script>
                // JavaScript para el tester interactivo
                async function testMessage() {
                    const input = document.getElementById('test-input');
                    const messages = document.getElementById('chat-messages');
                    const message = input.value.trim();
                    
                    if (!message) return;
                    
                    // Mostrar mensaje del usuario
                    addMessage('user', message);
                    input.value = '';
                    
                    // Mostrar indicador de carga
                    addMessage('system', 'Procesando...');
                    
                    try {
                        const response = await fetch('chatbot_uv.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ message: message })
                        });
                        
                        const data = await response.json();
                        
                        // Remover indicador de carga
                        const loadingMsg = messages.lastElementChild;
                        if (loadingMsg && loadingMsg.textContent === 'Procesando...') {
                            loadingMsg.remove();
                        }
                        
                        if (data.success) {
                            addMessage('bot', data.message);
                            updateMetrics('success');
                        } else {
                            addMessage('system', 'Error: ' + (data.message || 'Error desconocido'));
                            updateMetrics('error');
                        }
                        
                    } catch (error) {
                        // Remover indicador de carga
                        const loadingMsg = messages.lastElementChild;
                        if (loadingMsg && loadingMsg.textContent === 'Procesando...') {
                            loadingMsg.remove();
                        }
                        
                        addMessage('system', 'Error de conexión: ' + error.message);
                        updateMetrics('error');
                    }
                }
                
                function addMessage(type, text) {
                    const messages = document.getElementById('chat-messages');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message ' + type;
                    messageDiv.textContent = text;
                    messages.appendChild(messageDiv);
                    messages.scrollTop = messages.scrollHeight;
                }
                
                let totalRequests = 0;
                let successfulRequests = 0;
                let errorRequests = 0;
                
                function updateMetrics(type) {
                    totalRequests++;
                    if (type === 'success') successfulRequests++;
                    if (type === 'error') errorRequests++;
                    
                    document.getElementById('total-requests').textContent = totalRequests;
                    document.getElementById('success-rate').textContent = 
                        totalRequests > 0 ? Math.round((successfulRequests / totalRequests) * 100) + '%' : '0%';
                    document.getElementById('error-count').textContent = errorRequests;
                }
                
                // Enter para enviar mensaje
                document.addEventListener('DOMContentLoaded', function() {
                    const input = document.getElementById('test-input');
                    if (input) {
                        input.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                testMessage();
                            }
                        });
                    }
                });
                
                // Auto-test al cargar
                window.onload = function() {
                    setTimeout(() => {
                        document.getElementById('test-input').value = 'hola';
                        testMessage();
                    }, 1000);
                };
            </script>
        </body>
        </html>";
    }
    
    private function testPHPEnvironment() {
        echo "<div class='test-section'>
                <h2>🖥️ Entorno PHP</h2>";
        
        // Versión PHP
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.0', '>=');
        echo "<div class='status " . ($phpOk ? 'success' : 'error') . "'>
                PHP Version: {$phpVersion} " . ($phpOk ? '✅' : '❌') . "
              </div>";
        
        // Extensiones necesarias
        $extensions = ['curl', 'json', 'openssl'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "<div class='status " . ($loaded ? 'success' : 'error') . "'>
                    Extensión {$ext}: " . ($loaded ? 'Cargada ✅' : 'No encontrada ❌') . "
                  </div>";
        }
        
        // Permisos de archivo
        $writeable = is_writable('.');
        echo "<div class='status " . ($writeable ? 'success' : 'warning') . "'>
                Permisos de escritura: " . ($writeable ? 'OK ✅' : 'Limitados ⚠️') . "
              </div>";
              
        echo "</div>";
    }
    
    private function testChatbotFile() {
        echo "<div class='test-section'>
                <h2>📄 Archivo Chatbot</h2>";
        
        // Verificar si existe el archivo
        $exists = file_exists($this->chatbotFile);
        echo "<div class='status " . ($exists ? 'success' : 'error') . "'>
                Archivo {$this->chatbotFile}: " . ($exists ? 'Existe ✅' : 'No encontrado ❌') . "
              </div>";
        
        if ($exists) {
            // Verificar sintaxis PHP
            $syntax = $this->checkPHPSyntax($this->chatbotFile);
            echo "<div class='status " . ($syntax ? 'success' : 'error') . "'>
                    Sintaxis PHP: " . ($syntax ? 'Correcta ✅' : 'Errores encontrados ❌') . "
                  </div>";
            
            // Tamaño del archivo
            $size = filesize($this->chatbotFile);
            echo "<div class='status info'>
                    Tamaño: " . number_format($size / 1024, 2) . " KB
                  </div>";
        }
        
        echo "</div>";
    }
    
    private function checkPHPSyntax($file) {
        $output = [];
        $return = 0;
        exec("php -l {$file} 2>&1", $output, $return);
        return $return === 0;
    }
    
    private function testAPIsConnection() {
        echo "<div class='test-section'>
                <h2>🌐 Conexión APIs</h2>";
        
        // Test Hugging Face
        $hfStatus = $this->testHuggingFaceAPI();
        echo "<div class='status " . ($hfStatus['success'] ? 'success' : 'warning') . "'>
                Hugging Face API: " . $hfStatus['message'] . "
              </div>";
        
        // Test conectividad general
        $internetStatus = $this->testInternetConnection();
        echo "<div class='status " . ($internetStatus ? 'success' : 'error') . "'>
                Conectividad Internet: " . ($internetStatus ? 'OK ✅' : 'Sin conexión ❌') . "
              </div>";
        
        echo "</div>";
    }
    
    private function testHuggingFaceAPI() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-medium',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['inputs' => 'test']),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => 'Error CURL: ' . $error . ' ❌'];
        }
        
        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Conectado correctamente ✅'];
        } else {
            return ['success' => false, 'message' => "HTTP {$httpCode} - Puede funcionar con límites ⚠️"];
        }
    }
    
    private function testInternetConnection() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://www.google.com',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function testResponseLogic() {
        echo "<div class='test-section'>
                <h2>🧠 Lógica de Respuestas</h2>";
        
        // Simular respuestas locales
        $testMessages = [
            'hola' => 'Debería saludar',
            'precio' => 'Debería mostrar precios',
            'contacto' => 'Debería dar información de contacto',
            'mensaje random xyz123' => 'Debería dar respuesta por defecto'
        ];
        
        foreach ($testMessages as $msg => $expected) {
            echo "<div class='status info'>
                    <strong>Test:</strong> '{$msg}' → {$expected}
                  </div>";
        }
        
        echo "</div>";
    }
    
    private function showInteractiveTester() {
        echo "<div class='test-section'>
                <h2>💬 Tester Interactivo</h2>
                
                <div class='metrics'>
                    <div class='metric-card'>
                        <div class='metric-value' id='total-requests'>0</div>
                        <div class='metric-label'>Total Requests</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value' id='success-rate'>0%</div>
                        <div class='metric-label'>Success Rate</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value' id='error-count'>0</div>
                        <div class='metric-label'>Errors</div>
                    </div>
                </div>
                
                <div class='chat-tester'>
                    <div class='chat-messages' id='chat-messages'></div>
                    <div class='input-group'>
                        <input type='text' id='test-input' placeholder='Escribe un mensaje de prueba...' />
                        <button onclick='testMessage()'>Enviar</button>
                    </div>
                </div>
                
                <div style='margin-top: 15px;'>
                    <strong>Mensajes de prueba sugeridos:</strong>
                    <div class='code-block'>
                        • hola<br>
                        • ¿cuánto cuesta la lámpara UV?<br>
                        • información de contacto<br>
                        • beneficios de los dispositivos UV<br>
                        • ¿es seguro usar radiación UV?
                    </div>
                </div>
              </div>";
    }
    
    private function showJavaScriptIntegration() {
        echo "<div class='test-section'>
                <h2>🔌 Código de Integración JavaScript</h2>
                <p>Usa este código en tu frontend para conectar con el chatbot:</p>
                
                <div class='code-block'>
// Función para enviar mensaje al chatbot<br>
async function sendToChatbot(message) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;try {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const response = await fetch('chatbot_uv.php', {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;method: 'POST',<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;headers: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'Content-Type': 'application/json',<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;},<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;body: JSON.stringify({ message: message })<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;});<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const data = await response.json();<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if (data.success) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return data.message;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} else {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;console.error('Error:', data.message);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return 'Lo siento, hubo un problema.';<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;} catch (error) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;console.error('Network error:', error);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return 'Error de conexión.';<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}<br>
<br>
// Ejemplo de uso<br>
sendToChatbot('hola').then(response => {<br>
&nbsp;&nbsp;&nbsp;&nbsp;console.log('Respuesta:', response);<br>
});
                </div>
                
                <div style='margin-top: 20px;'>
                    <strong>✅ Checklist de Integración:</strong>
                    <ul>
                        <li>Archivo chatbot_uv.php en el servidor</li>
                        <li>Permisos de ejecución PHP</li>
                        <li>CORS configurado correctamente</li>
                        <li>JavaScript usando fetch() con POST</li>
                        <li>Manejo de errores implementado</li>
                    </ul>
                </div>
              </div>";
    }
}

// Ejecutar tests
if (isset($_GET['test']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $tester = new ChatbotTester();
    $tester->runAllTests();
} else {
    // Respuesta JSON para requests POST
    echo json_encode([
        'success' => true,
        'message' => 'Sistema de testing disponible. Visita: test_chatbot.php?test=1',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>