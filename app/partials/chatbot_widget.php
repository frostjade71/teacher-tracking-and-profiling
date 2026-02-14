<?php
// app/partials/chatbot_widget.php
$avatarUrl = url('images/linky.webp');
?>
<!-- Faculty Assistant Chatbot Widget -->
<div id="chatbotContainer" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Window (Hidden by default) -->
    <div id="chatWindow" class="hidden mb-4 w-96 max-w-[calc(100vw-3rem)] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden transition-all duration-300">
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center overflow-hidden">
                    <img src="<?= $avatarUrl ?>" alt="Linky" class="w-full h-full object-cover">
                </div>
                <div>
                    <h3 class="text-white font-bold text-sm">LinkyBot</h3>
                    <p class="text-white/80 text-xs">Your personal faculty tracker assistant</p>
                </div>
            </div>
            <button id="closeChatBtn" class="text-white/80 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div id="chatMessages" class="h-96 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-slate-900">
            <!-- Welcome Message -->
            <div class="flex gap-3 items-start">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200 dark:border-slate-600">
                    <img src="<?= $avatarUrl ?>" alt="Linky" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm border border-gray-200 dark:border-slate-700">
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Hi! I can help you find teachers and check their status, location, and schedules. Try asking:
                    </p>
                    <div class="mt-2 space-y-1">
                        <button class="suggestion-btn w-full text-left text-xs px-3 py-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg text-blue-700 dark:text-blue-300 transition-colors">
                            "Ongoing Classes"
                        </button>
                        <button class="suggestion-btn w-full text-left text-xs px-3 py-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg text-blue-700 dark:text-blue-300 transition-colors">
                            "Are there any available teachers?"
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700">
            <form id="chatForm" class="flex gap-2">
                <input 
                    type="text" 
                    id="chatInput" 
                    placeholder="Ask about a teacher..." 
                    class="flex-1 px-4 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-sm text-slate-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    autocomplete="off"
                >
                <button 
                    type="submit" 
                    id="sendBtn"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium text-sm transition-colors shadow-lg shadow-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Floating Chat Button -->
    <button id="openChatBtn" class="w-16 h-16 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-110 group">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
        <!-- Notification Pulse -->
        <span class="absolute top-0 right-0 w-4 h-4 bg-red-500 rounded-full animate-pulse"></span>
    </button>
</div>

<script>
// Chatbot functionality
(function() {
    const chatWindow = document.getElementById('chatWindow');
    const openBtn = document.getElementById('openChatBtn');
    const closeBtn = document.getElementById('closeChatBtn');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const sendBtn = document.getElementById('sendBtn');
    const linkyAvatarUrl = "<?= $avatarUrl ?>";
    
    // Toggle chat window
    openBtn?.addEventListener('click', () => {
        chatWindow.classList.remove('hidden');
        openBtn.classList.add('hidden');
        chatInput.focus();
    });
    
    closeBtn?.addEventListener('click', () => {
        chatWindow.classList.add('hidden');
        openBtn.classList.remove('hidden');
    });
    
    // Add message to chat
    function addMessage(content, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex gap-3 items-start ${isUser ? 'flex-row-reverse' : ''}`;
        
        const avatar = document.createElement('div');
        
        if (isUser) {
            avatar.innerHTML = `<svg class="w-5 h-5 text-gray-600 dark:text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>`;
            avatar.className = `w-8 h-8 bg-gray-200 dark:bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0`;
        } else {
            avatar.innerHTML = `<img src="${linkyAvatarUrl}" alt="Linky" class="w-full h-full object-cover">`;
            avatar.className = `w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200 dark:border-slate-600`;
        }
        
        const bubble = document.createElement('div');
        bubble.className = `flex-1 ${isUser ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700'} rounded-xl p-3 shadow-sm max-w-[80%]`;
        
        if (isUser) {
            // User messages: escape HTML
            const div = document.createElement('div');
            div.textContent = content;
            bubble.innerHTML = `<p class="text-sm text-white whitespace-pre-wrap">${div.innerHTML}</p>`;
        } else {
            // Bot messages: parse markdown
            bubble.innerHTML = `<p class="text-sm text-slate-700 dark:text-slate-300">${parseMarkdown(content)}</p>`;
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(bubble);
        chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Add typing indicator
    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typingIndicator';
        typingDiv.className = 'flex gap-3 items-start';
        typingDiv.innerHTML = `
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200 dark:border-slate-600">
                <img src="${linkyAvatarUrl}" alt="Linky" class="w-full h-full object-cover">
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm border border-gray-200 dark:border-slate-700">
                <div class="flex gap-1">
                    <div class="w-2 h-2 bg-gray-400 dark:bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-slate-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-slate-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        `;
        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function hideTyping() {
        document.getElementById('typingIndicator')?.remove();
    }
    
    // Send message
    async function sendMessage(message) {
        if (!message.trim()) return;
        
        // Add user message
        addMessage(message, true);
        chatInput.value = '';
        sendBtn.disabled = true;
        
        // Show typing
        showTyping();
        
        try {
            const response = await fetch('<?= url('?page=chatbot_api') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });
            
            const data = await response.json();
            hideTyping();
            
            if (data.success) {
                addMessage(data.message);
            } else {
                addMessage(data.error || 'Sorry, I encountered an error. Please try again.');
            }
        } catch (error) {
            hideTyping();
            addMessage('Sorry, I\'m having trouble connecting. Please try again later.');
            console.error('Chatbot error:', error);
        } finally {
            sendBtn.disabled = false;
            chatInput.focus();
        }
    }
    
    // Form submit
    chatForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage(chatInput.value);
    });
    
    // Suggestion buttons
    document.querySelectorAll('.suggestion-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const suggestion = btn.textContent.trim().replace(/^"|"$/g, '');
            chatInput.value = suggestion;
            sendMessage(suggestion);
        });
    });
    
    // Simple markdown parser for chatbot responses
    function parseMarkdown(text) {
        // Escape HTML first to prevent XSS
        const div = document.createElement('div');
        div.textContent = text;
        let escaped = div.innerHTML;
        
        // Convert **bold** to <strong>
        escaped = escaped.replace(/\*\*([^\*]+)\*\*/g, '<strong>$1</strong>');
        
        // Convert *italic* to <em>
        escaped = escaped.replace(/\*([^\*]+)\*/g, '<em>$1</em>');
        
        // Convert newlines to <br>
        escaped = escaped.replace(/\n/g, '<br>');
        
        // Convert bullet points (- item) to actual list items
        escaped = escaped.replace(/^- (.+)$/gm, '<span style="display:block; margin-left: 1em;">• $1</span>');
        
        // Convert [Live Campus Map] to clickable link
        escaped = escaped.replace(/\[Live Campus Map\]/g, '<button onclick="document.getElementById(\'campusMapModal\').showModal()" class="text-blue-600 dark:text-blue-400 hover:underline font-bold inline-flex items-center gap-1 cursor-pointer">Live Campus Map <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></button>');
        
        return escaped;
    }
    
    // Keyboard shortcut: Esc to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !chatWindow.classList.contains('hidden')) {
            chatWindow.classList.add('hidden');
            openBtn.classList.remove('hidden');
        }
    });
})();
</script>
