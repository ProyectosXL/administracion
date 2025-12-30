/**
 * Chatbot RAG - Asistente Virtual DocuGest
 * Integración con el servicio RAG de Python para búsquedas en lenguaje natural
 */

// Configuración
const RAG_API_URL = 'http://localhost:8002';
let chatbotOpen = false;
let chatHistory = [];

// Inicializar chatbot al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    verificarServicioRAG();
    configurarInputChatbot();
});

/**
 * Verifica el estado del servicio RAG
 */
async function verificarServicioRAG() {
    const statusIcon = document.getElementById('chatbot-status-icon');
    const statusText = document.getElementById('chatbot-status-text');
    
    try {
        const response = await fetch(`${RAG_API_URL}/health`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            statusIcon.className = 'status-dot online';
            statusText.textContent = `En línea - ${data.total_chunks} chunks indexados`;
            
            // Si no hay chunks, mostrar advertencia
            if (data.total_chunks === 0) {
                statusText.textContent = 'En línea - Sin documentos indexados';
                agregarMensajeBot('⚠️ Aún no hay documentos indexados. Por favor, ejecuta el script de indexación masiva primero.');
            }
        } else {
            throw new Error('Servicio no disponible');
        }
    } catch (error) {
        statusIcon.className = 'status-dot offline';
        statusText.textContent = 'Sin conexión';
        console.error('Error al verificar servicio RAG:', error);
        agregarMensajeBot('❌ No puedo conectarme al servicio de IA. Verifica que el servicio RAG esté ejecutándose en http://localhost:8002');
    }
}

/**
 * Alterna la visibilidad del chatbot
 */
function toggleChatbot() {
    const container = document.getElementById('chatbot-container');
    const toggleBtn = document.getElementById('chatbot-toggle-btn');
    const badge = document.getElementById('chatbot-badge');
    const menuBtn = document.getElementById('chatbotMenuBtn');
    
    chatbotOpen = !chatbotOpen;
    
    if (chatbotOpen) {
        container.classList.add('open');
        toggleBtn.classList.add('hidden');
        badge.style.display = 'none';
        if (menuBtn) menuBtn.classList.add('active');
        
        // Focus en el input
        setTimeout(() => {
            document.getElementById('chatbot-input').focus();
        }, 300);
    } else {
        container.classList.remove('open');
        toggleBtn.classList.remove('hidden');
        if (menuBtn) menuBtn.classList.remove('active');
    }
}

/**
 * Configura el comportamiento del textarea del chatbot
 */
function configurarInputChatbot() {
    const input = document.getElementById('chatbot-input');
    
    if (!input) return;
    
    // Auto-resize del textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Enviar con Enter (Shift+Enter para nueva línea)
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatbotMessage();
        }
    });
}

/**
 * Envía un mensaje al chatbot
 */
async function sendChatbotMessage() {
    const input = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send-btn');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Agregar mensaje del usuario
    agregarMensajeUsuario(message);
    
    // Limpiar input
    input.value = '';
    input.style.height = 'auto';
    
    // Deshabilitar input mientras se procesa
    input.disabled = true;
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Mostrar indicador de escritura
    const typingId = mostrarIndicadorEscritura();
    
    try {
        // Enviar consulta al servicio RAG
        const response = await fetch(`${RAG_API_URL}/api/query`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pregunta: message,
                top_k: 5
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ detail: 'Error desconocido' }));
            
            // Manejo específico de error de cuota
            if (response.status === 429) {
                throw new Error('QUOTA_EXCEEDED: ' + (errorData.detail || 'Has excedido la cuota de la API'));
            }
            
            throw new Error(`Error del servidor: ${response.status} - ${errorData.detail || ''}`);
        }
        
        const data = await response.json();
        
        // Eliminar indicador de escritura
        eliminarIndicadorEscritura(typingId);
        
        // Agregar respuesta del bot
        agregarMensajeBot(data.respuesta, data.documentos_fuente);
        
        // Guardar en historial
        chatHistory.push({
            pregunta: message,
            respuesta: data.respuesta,
            documentos: data.documentos_fuente,
            timestamp: new Date()
        });
        
    } catch (error) {
        console.error('Error al consultar RAG:', error);
        eliminarIndicadorEscritura(typingId);
        
        // Mensaje personalizado según el tipo de error
        let errorMessage;
        if (error.message.includes('QUOTA_EXCEEDED')) {
            errorMessage = '⏳ Has alcanzado el límite de consultas de la API de Gemini. Por favor, espera unos minutos e intenta nuevamente.';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = '🔌 No se pudo conectar con el servicio RAG. Verifica que esté funcionando.';
        } else {
            errorMessage = `❌ Error al procesar tu consulta: ${error.message}`;
        }
        
        agregarMensajeBot(errorMessage);
    } finally {
        // Rehabilitar input
        input.disabled = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        input.focus();
    }
}

/**
 * Agrega un mensaje del usuario al chat
 */
function agregarMensajeUsuario(texto) {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message user';
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <p>${escapeHtml(texto)}</p>
        </div>
        <div class="message-avatar">
            <i class="fas fa-user"></i>
        </div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
}

/**
 * Agrega un mensaje del bot al chat
 */
function agregarMensajeBot(texto, documentosFuente = []) {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';
    
    let html = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <p>${formatearRespuesta(texto)}</p>
    `;
    
    // Si hay documentos fuente, mostrarlos
    if (documentosFuente && documentosFuente.length > 0) {
        html += '<div class="message-sources">';
        html += '<p class="sources-title"><i class="fas fa-file-pdf"></i> Fuentes consultadas:</p>';
        html += '<ul class="sources-list">';
        
        documentosFuente.forEach(doc => {
            html += `
                <li class="source-item">
                    <span class="source-title">${escapeHtml(doc.titulo)}</span>
                    <span class="source-sector">${escapeHtml(doc.sector)}</span>
                    <button class="source-view-btn" onclick="viewDocument(${doc.document_id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </li>
            `;
        });
        
        html += '</ul></div>';
    }
    
    html += '</div>';
    
    messageDiv.innerHTML = html;
    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
}

/**
 * Muestra indicador de que el bot está escribiendo
 */
function mostrarIndicadorEscritura() {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const typingDiv = document.createElement('div');
    const typingId = 'typing-' + Date.now();
    typingDiv.id = typingId;
    typingDiv.className = 'chatbot-message bot typing';
    
    typingDiv.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    `;
    
    messagesContainer.appendChild(typingDiv);
    scrollToBottom();
    
    return typingId;
}

/**
 * Elimina el indicador de escritura
 */
function eliminarIndicadorEscritura(typingId) {
    const typingDiv = document.getElementById(typingId);
    if (typingDiv) {
        typingDiv.remove();
    }
}

/**
 * Scroll automático al final del chat
 */
function scrollToBottom() {
    const messagesContainer = document.getElementById('chatbot-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Formatea la respuesta del bot (convierte markdown básico a HTML)
 */
function formatearRespuesta(texto) {
    let formatted = escapeHtml(texto);
    
    // Convertir **negrita** a <strong>
    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Convertir saltos de línea dobles a párrafos
    formatted = formatted.replace(/\n\n/g, '</p><p>');
    
    // Convertir saltos de línea simples a <br>
    formatted = formatted.replace(/\n/g, '<br>');
    
    // Envolver en párrafo si no hay ya
    if (!formatted.includes('<p>')) {
        formatted = '<p>' + formatted + '</p>';
    }
    
    return formatted;
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Limpia el historial del chat
 */
function limpiarChat() {
    if (confirm('¿Estás seguro de que deseas limpiar el historial del chat?')) {
        const messagesContainer = document.getElementById('chatbot-messages');
        messagesContainer.innerHTML = `
            <div class="chatbot-message bot">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <p>Chat limpiado. ¿En qué puedo ayudarte?</p>
                </div>
            </div>
        `;
        chatHistory = [];
    }
}
