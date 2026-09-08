document.addEventListener('DOMContentLoaded', () => {
    
    // Determine API Path based on current URL
    const isRoomPage = window.location.pathname.includes('/room/');
    const API_BASE = isRoomPage ? '../api/' : 'api/';
    const ROOT_BASE = isRoomPage ? '../' : '';
    
    fetch(API_BASE + 'chat_config.php')
    .then(res => res.json())
    .then(config => {
        const botName = config.chatbot_name || 'Yuncha AI';
        const botColor = config.chatbot_color || '#ff003c';
        const botBg = config.chatbot_bg || 'rgba(248, 250, 252, 0.95)';
        
        const hexToRgb = (hex) => {
            let r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return r ? parseInt(r[1], 16) + ', ' + parseInt(r[2], 16) + ', ' + parseInt(r[3], 16) : '255, 0, 60';
        };
        const botColorRgb = hexToRgb(botColor);
        document.documentElement.style.setProperty('--chat-theme-rgb', botColorRgb);
        document.documentElement.style.setProperty('--chat-theme-hex', botColor);
        
        let avatarHtml = `<span style="font-size: 1.2rem;">🏮</span>`;
        if (config.chatbot_avatar) {
            avatarHtml = `<img src="${ROOT_BASE}${config.chatbot_avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        }
        
        const currentLang = document.documentElement.lang || 'th';
        const t = {
            'th': {
                'greeting': `สวัสดีค่ะ ยินดีต้อนรับสู่ ${botName} มีอะไรให้ฉันช่วยไหมคะ?`,
                'qr1': '🛏️ ห้องพักมีแบบไหนบ้าง และราคาเท่าไหร่?',
                'qr2': '📅 สนใจจองห้องพัก ต้องทำอย่างไร?',
                'qr3': '🍵 รีสอร์ทมีกิจกรรมอะไรให้ทำบ้าง?',
                'qr4': '📍 การเดินทางมารีสอร์ท และที่จอดรถ',
                'placeholder': 'พิมพ์ข้อความของคุณ...',
                'err_temp': 'ขออภัยค่ะ ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง',
                'err_server': 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
            },
            'en': {
                'greeting': `Hello! Welcome to ${botName}. How can I assist you today?`,
                'qr1': '🛏️ What room types are available and what are the rates?',
                'qr2': '📅 How can I book a room?',
                'qr3': '🍵 What activities are available at the resort?',
                'qr4': '📍 How to get to the resort and parking',
                'placeholder': 'Type your message...',
                'err_temp': 'Sorry, the system is temporarily down. Please try again later.',
                'err_server': 'Cannot connect to the server.'
            }
        };
        const langData = t[currentLang] || t['th'];

        // Inject Premium Chatbot HTML dynamically
        const chatbotHTML = `
        <!-- Chatbot Popup System -->
        <div class="chatbot-container" id="chatbot-container" style="background: rgba(248, 250, 252, 0.95); backdrop-filter: blur(12px); border: 1px solid ${botColor}; overflow: hidden;">
            
            <div class="chatbot-header" style="background: ${botColor}; border-bottom: 1px solid rgba(255,255,255,0.2); position:relative; z-index:1; padding: 15px;">
                <div class="flex items-center gap-3">
                    <div class="chatbot-avatar" style="border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.2); background: white;">
                        ${avatarHtml}
                    </div>
                    <div>
                        <h4 class="font-cinzel font-bold text-sm tracking-wider" style="margin:0; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">${botName}</h4>
                        <span class="text-[10px]" style="color: rgba(255,255,255,0.8);">Premium Virtual Assistant</span>
                    </div>
                </div>
                <button id="close-chat" style="color: white; background:none; border:none; cursor:pointer; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                    <svg class="w-5 h-5" style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="chatbot-messages" id="chatbot-messages" data-lenis-prevent onwheel="event.stopPropagation()" ontouchmove="event.stopPropagation()" style="position:relative; z-index:1; background: ${botBg};">
                <!-- Initial greeting -->
                <div class="chat-msg bot-msg" style="border: 1px solid ${botColor}40; background: white; color: #333; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    ${langData.greeting}
                </div>
                
                <div class="quick-replies" id="quick-replies">
                    <button class="qr-btn" style="border: 1px solid ${botColor}40; background: white; color: ${botColor};">${langData.qr1}</button>
                    <button class="qr-btn" style="border: 1px solid ${botColor}40; background: white; color: ${botColor};">${langData.qr2}</button>
                    <button class="qr-btn" style="border: 1px solid ${botColor}40; background: white; color: ${botColor};">${langData.qr3}</button>
                    <button class="qr-btn" style="border: 1px solid ${botColor}40; background: white; color: ${botColor};">${langData.qr4}</button>
                </div>
            </div>
            
            <div class="chatbot-input" style="background: ${botColor}; border-top: 1px solid rgba(255,255,255,0.2); position:relative; z-index:1; padding: 10px;">
                <input type="text" id="chat-input" placeholder="${langData.placeholder}" autocomplete="off" style="border: none; background: white; color: #333; padding: 10px 15px; border-radius: 20px; flex-1; width: calc(100% - 40px);">
                <button id="send-chat" class="transition-colors" style="color: white; border:none; background:none; cursor:pointer; padding: 5px;">
                    <svg class="w-6 h-6" style="width:24px;height:24px;" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>

        <button class="chat-fab" id="chat-fab">
            <div class="chat-tooltip">
                <span id="chat-tooltip-text"></span>
            </div>
            <svg viewBox="0 0 100 100" class="modern-robot-svg">
                <defs>
                    <filter id="robo-glow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="2" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                    <linearGradient id="robo-body" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffffff"/>
                        <stop offset="100%" stop-color="#94a3b8"/>
                    </linearGradient>
                    <linearGradient id="robo-accent" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="${botColor}"/>
                        <stop offset="100%" stop-color="${botColor}80"/>
                    </linearGradient>
                </defs>

                <rect x="48" y="5" width="4" height="10" fill="#cbd5e1" />
                <circle cx="50" cy="5" r="4" fill="url(#robo-accent)" filter="url(#robo-glow)" />

                <rect x="15" y="45" width="12" height="28" rx="6" fill="url(#robo-body)" />
                
                <g class="robot-wave-arm">
                    <rect x="73" y="45" width="12" height="28" rx="6" fill="url(#robo-body)" />
                </g>

                <rect x="25" y="52" width="50" height="35" rx="15" fill="url(#robo-body)" />
                <rect x="40" y="65" width="20" height="4" rx="2" fill="#334155" />
                <circle cx="50" cy="78" r="4" fill="url(#robo-accent)" />
                
                <rect x="20" y="15" width="60" height="42" rx="21" fill="url(#robo-body)" />
                <rect x="15" y="28" width="10" height="16" rx="3" fill="#334155" />
                <rect x="75" y="28" width="10" height="16" rx="3" fill="#334155" />
                <rect x="28" y="24" width="44" height="24" rx="12" fill="#0f172a" />
                
                <g class="robot-eyes">
                    <path d="M 38 34 Q 40 32 42 34" stroke="${botColor}" stroke-width="4" stroke-linecap="round" fill="none" filter="url(#robo-glow)" />
                    <path d="M 58 34 Q 60 32 62 34" stroke="${botColor}" stroke-width="4" stroke-linecap="round" fill="none" filter="url(#robo-glow)" />
                </g>
            </svg>
        </button>
        `;

        document.body.insertAdjacentHTML('beforeend', chatbotHTML);

        // Set Tooltip language
        const tooltipEl = document.getElementById('chat-tooltip-text');
        if (tooltipEl) {
            const currentLang = document.documentElement.lang || 'th';
            tooltipEl.textContent = currentLang === 'en' ? 'Welcome' : 'ยินดีต้อนรับ';
        }

        const chatFab = document.getElementById('chat-fab');
        const chatContainer = document.getElementById('chatbot-container');
        const closeChat = document.getElementById('close-chat');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-chat');
        const chatMessages = document.getElementById('chatbot-messages');

        let isWaitingForResponse = false;

        // Toggle Chat
        chatFab.addEventListener('click', () => {
            chatContainer.classList.add('open');
            chatFab.style.transform = 'scale(0)';
            setTimeout(() => chatInput.focus(), 300);
        });

        closeChat.addEventListener('click', () => {
            chatContainer.classList.remove('open');
            chatFab.style.transform = 'scale(1)';
        });

        // Send Message
        const sendMessage = async (presetText = null) => {
            const text = presetText !== null ? presetText : chatInput.value.trim();
            if (!text || isWaitingForResponse) return;

            // Hide quick replies if they exist
            const qrContainer = document.getElementById('quick-replies');
            if (qrContainer) qrContainer.style.display = 'none';

            // Append User Message
            appendMessage(text, 'user-msg');
            if (presetText === null) chatInput.value = '';
            isWaitingForResponse = true;

            // Show Typing Indicator
            const typingId = showTypingIndicator();

            try {
                const formData = new FormData();
                formData.append('message', text);
                formData.append('lang', currentLang);

                const response = await fetch(API_BASE + 'chat_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                removeTypingIndicator(typingId);

                if (data.status === 'success') {
                    let rawText = data.message;
                    
                    // Extract quick replies in format [Button Text]
                    let quickReplies = [];
                    const qrRegex = /\[([\s\S]*?)\]/g;
                    let match;
                    while ((match = qrRegex.exec(rawText)) !== null) {
                        quickReplies.push(match[1].trim());
                    }
                    
                    // Remove brackets from main text
                    rawText = rawText.replace(/\[([\s\S]*?)\]/g, '').trim();

                    let formattedText = rawText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    
                    // Parse Markdown Images: ![alt text](url)
                    formattedText = formattedText.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, function(match, alt, url) {
                        let src = url.startsWith('http') ? url : ROOT_BASE + url;
                        return `<a href="${src}" target="_blank" data-fancybox="chat-gallery" style="display:block; margin-top:8px; cursor:zoom-in;"><img src="${src}" alt="${alt}" style="max-width:100%; border-radius:8px; width:100%; box-shadow:0 2px 8px rgba(0,0,0,0.1);"></a>`;
                    });
                    
                    // Parse faulty markdown images without alt text: !(url)
                    formattedText = formattedText.replace(/!\(([^)]+)\)/g, function(match, url) {
                        let src = url.startsWith('http') ? url : ROOT_BASE + url;
                        return `<a href="${src}" target="_blank" data-fancybox="chat-gallery" style="display:block; margin-top:8px; cursor:zoom-in;"><img src="${src}" alt="image" style="max-width:100%; border-radius:8px; width:100%; box-shadow:0 2px 8px rgba(0,0,0,0.1);"></a>`;
                    });
                    
                    formattedText = formattedText.replace(/\n/g, '<br>');
                    appendMessage(formattedText, 'bot-msg', true, quickReplies);
                } else {
                    appendMessage(langData.err_temp, 'bot-msg');
                }

            } catch (error) {
                removeTypingIndicator(typingId);
                appendMessage(langData.err_server, 'bot-msg');
            } finally {
                isWaitingForResponse = false;
            }
        };

        // Events
        sendBtn.addEventListener('click', () => sendMessage());
        
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Quick Replies Events
        document.querySelectorAll('.qr-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                sendMessage(btn.textContent);
            });
        });

        // Helpers
        function appendMessage(text, className, isHTML = false, quickReplies = []) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-msg ${className}`;
            
            // Add custom border color dynamically to bot messages
            if(className === 'bot-msg') {
                msgDiv.style.border = `1px solid ${botColor}40`;
                msgDiv.style.background = 'white';
                msgDiv.style.color = '#333';
                msgDiv.style.boxShadow = '0 4px 10px rgba(0,0,0,0.05)';
            }
            if(className === 'user-msg') {
                msgDiv.style.background = botColor;
            }
            
            if (isHTML) {
                msgDiv.innerHTML = text;
            } else {
                msgDiv.textContent = text;
            }
            
            chatMessages.appendChild(msgDiv);

            if (quickReplies && quickReplies.length > 0) {
                const qrDiv = document.createElement('div');
                qrDiv.className = 'quick-replies';
                qrDiv.style.opacity = '1';
                qrDiv.style.animation = 'none';
                qrDiv.style.marginTop = '0px';
                qrDiv.style.alignSelf = 'flex-start';
                
                quickReplies.forEach(qr => {
                    const btn = document.createElement('button');
                    btn.className = 'qr-btn';
                    btn.style.border = `1px solid ${botColor}40`;
                    btn.style.background = 'white';
                    btn.style.color = botColor;
                    btn.style.boxShadow = '0 2px 6px rgba(0,0,0,0.05)';
                    btn.textContent = qr;
                    btn.onclick = () => {
                        qrDiv.style.display = 'none';
                        if (qr.includes('เช็กห้องว่าง') || qr.includes('จองห้องพัก')) {
                            window.location.href = 'booking.php';
                        } else if (qr.includes('ติดต่อRESORTโดยตรง')) {
                            window.location.href = 'contact.php';
                        } else {
                            sendMessage(qr);
                        }
                    };
                    qrDiv.appendChild(btn);
                });
                chatMessages.appendChild(qrDiv);
            }
            
            scrollToBottom();
        }

        function showTypingIndicator() {
            const id = 'typing-' + Date.now();
            const typingDiv = document.createElement('div');
            typingDiv.id = id;
            typingDiv.className = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="typing-dot" style="background:${botColor}"></div>
                <div class="typing-dot" style="background:${botColor}"></div>
                <div class="typing-dot" style="background:${botColor}"></div>
            `;
            chatMessages.appendChild(typingDiv);
            scrollToBottom();
            return id;
        }

        function removeTypingIndicator(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
    }); // end fetch
});
