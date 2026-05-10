/* =========================
   CHATBOT JS
   Dynamically injects UI and handles API calls
   ========================= */

// Execute immediately since it's injected after DOM load
(function() {
    // 1. Inject CSS dynamically
    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    // Handle root vs pages path difference
    const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/foldnest/') || window.location.pathname.endsWith('foldnest/frontend/');
    styleLink.href = isRoot ? 'css/chatbot.css' : '../css/chatbot.css';
    document.head.appendChild(styleLink);
    
    const apiUrl = isRoot ? '../backend/api/chatbot.php' : '../../backend/api/chatbot.php';

    // 2. Inject HTML UI dynamically
    const chatbotHTML = `
        <div id="chatbot-container">
            <div id="chatbot-window">
                <div id="chatbot-header">
                    <div>💬 FoldNest Support</div>
                    <button id="chatbot-close">&times;</button>
                </div>
                <div id="chatbot-messages">
                    <div class="chat-bubble chat-bot">Hi there! 👋 I'm your FoldNest AI Assistant. How can I help you today?</div>
                </div>
                <div id="chatbot-input-area">
                    <input type="text" id="chatbot-input" placeholder="Type your message..." autocomplete="off">
                    <button id="chatbot-send">➤</button>
                </div>
            </div>
            <button id="chatbot-toggle">💬</button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', chatbotHTML);

    // 3. Logic & Event Listeners
    const toggleBtn = document.getElementById('chatbot-toggle');
    const closeBtn = document.getElementById('chatbot-close');
    const chatWindow = document.getElementById('chatbot-window');
    const inputField = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send');
    const messagesContainer = document.getElementById('chatbot-messages');

    let isOpen = false;

    toggleBtn.addEventListener('click', () => {
        isOpen = !isOpen;
        chatWindow.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) inputField.focus();
    });

    closeBtn.addEventListener('click', () => {
        isOpen = false;
        chatWindow.style.display = 'none';
    });

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-bubble chat-${sender}`;
        
        // Strip markdown stars for simple bolding
        if (sender === 'bot') {
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        }
        
        msgDiv.innerHTML = text;
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function showTyping() {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-bubble chat-bot typing-indicator-bubble';
        msgDiv.id = 'typing-indicator';
        msgDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }

    async function sendMessage() {
        const text = inputField.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        inputField.value = '';
        showTyping();

        try {
            // Force localhost to ensure API resolves correctly in dev
            const fetchUrl = window.location.origin + '/foldnest/backend/api/chatbot.php';
            
            const response = await fetch(fetchUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            
            const data = await response.json();
            removeTyping();
            
            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage("Sorry, I'm having trouble connecting to the server.", 'bot');
            }
        } catch (error) {
            removeTyping();
            appendMessage("Network error. Please try again later.", 'bot');
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
})();
