<!-- CHATBOT WIDGET HTML - VERSIÓN SIMPLE CON SEGURIDAD BÁSICA -->
<div id="chatbot-widget" class="chatbot-widget minimized">
    <div id="chatbot-header" class="chatbot-header">
        <div class="chatbot-header-title">
            <span class="chatbot-header-icon"></span>
            <span>IntiBot 👋</span>
        </div>
        <span id="chatbot-toggle-icon">▼</span>
    </div>
    <div id="chatbot-body" class="chatbot-body">
        <div id="chatbot-messages" class="chatbot-messages"></div>
        <div id="chatbot-input" class="chatbot-input">
            <!-- 🛡️ CAMBIO: Agregado maxlength y autocomplete="off" -->
            <input 
                type="text" 
                id="chatbot-user-input" 
                placeholder="Escribe tu mensaje..." 
                aria-label="Mensaje para IntiBot"
                autocomplete="off"
                maxlength="500"
            />
            <button id="chatbot-send-btn" title="Enviar mensaje"></button>
        </div>
        <div id="status-indicator" class="status-indicator"></div>
    </div>
</div>

<!-- 🛡️ ESTILOS CSS CON PROTECCIÓN BÁSICA -->
<style>
    .chatbot-widget {
        /* Tu CSS existente aquí */
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        max-height: 500px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        z-index: 1000;
    }

    .chatbot-widget.minimized .chatbot-body {
        display: none;
    }

    .chatbot-header {
        background: #007cba;
        color: white;
        padding: 12px 16px;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chatbot-messages {
        height: 300px;
        overflow-y: auto;
        padding: 16px;
        /* 🛡️ CAMBIO: Protección contra desbordamiento */
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .chatbot-message {
        margin-bottom: 12px;
        /* 🛡️ CAMBIO: Aislamiento de contenido */
        max-width: 100%;
        word-break: break-word;
    }

    .chatbot-message.usuario {
        text-align: right;
    }

    .chatbot-message.usuario .message-content {
        background: #007cba;
        color: white;
        padding: 8px 12px;
        border-radius: 18px 18px 4px 18px;
        display: inline-block;
        max-width: 80%;
    }

    .chatbot-message.bot .message-content {
        background: #f1f1f1;
        color: #333;
        padding: 8px 12px;
        border-radius: 18px 18px 18px 4px;
        display: inline-block;
        max-width: 80%;
    }

    .message-time {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 4px;
    }

    .chatbot-input {
        display: flex;
        padding: 16px;
        border-top: 1px solid #eee;
        gap: 8px;
    }

    #chatbot-user-input {
        flex: 1;
        border: 1px solid #ddd;
        border-radius: 20px;
        padding: 8px 16px;
        outline: none;
        font-size: 14px;
        /* 🛡️ CAMBIO: Protección contra inyección */
        background: white;
        color: #333;
    }

    #chatbot-user-input:focus {
        border-color: #007cba;
        box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2);
    }

    #chatbot-send-btn {
        background: #007cba;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #chatbot-send-btn:before {
        content: '▶';
        font-size: 12px;
    }

    #chatbot-send-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    #chatbot-send-btn:hover:not(:disabled) {
        background: #005a87;
    }

    .typing-animation {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .typing-animation span {
        width: 6px;
        height: 6px;
        background: #999;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }

    .typing-animation span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-animation span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0%, 60%, 100% { opacity: 0.3; }
        30% { opacity: 1; }
    }

    /* 🛡️ CAMBIO: Protección contra manipulación CSS */
    .chatbot-widget * {
        max-width: 100%;
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .chatbot-widget {
            width: calc(100% - 40px);
            right: 20px;
            left: 20px;
        }
    }
</style>