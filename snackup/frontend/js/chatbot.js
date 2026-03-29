/**
 * CHATBOT - Assistant virtuel d'aide à la commande
 * Répond aux questions fréquentes et guide les utilisateurs
 */

const Chatbot = {
    isOpen: false,
    messages: [],

    // Base de connaissances FAQ
    knowledgeBase: {
        'comment commander': {
            response: "Pour commander, c'est très simple en 3 étapes :",
            card: {
                title: "Processus de commande",
                items: [
                    "1️⃣ Parcourez le menu et ajoutez des produits à votre panier",
                    "2️⃣ Cliquez sur le panier et renseignez vos informations",
                    "3️⃣ Validez votre commande et venez la récupérer !"
                ]
            }
        },
        'horaires': {
            response: "Nos horaires d'ouverture sont :",
            card: {
                title: "Horaires",
                items: [
                    "📅 Lundi - Jeudi : 7h - 21h30",
                    "📅 Vendredi : 7h - 11h30",
                    "📅 Samedi - Dimanche : 7h - 21h30"
                ]
            }
        },
        'livraison': {
            response: "Nous proposons plusieurs options :",
            card: {
                title: "Modes de récupération",
                items: [
                    "🛍️ Click & Collect : Commandez en ligne, récupérez sur place",
                    "🏍️ Livraison : Via nos partenaires de livraison"
                ]
            }
        },
        'paiement': {
            response: "Concernant le paiement :",
            card: {
                title: "Moyens de paiement",
                items: [
                    "💵 Espèces à la récupération",
                    "💳 Carte bancaire sur place",
                    "📱 Paiement mobile accepté"
                ]
            }
        },
        'annuler': {
            response: "Pour annuler ou modifier une commande :",
            text: "Appelez-nous rapidement au numéro affiché sur le site. Si votre commande n'est pas encore en préparation, nous pourrons la modifier ou l'annuler."
        },
        'allergenes': {
            response: "Pour les allergènes et intolérances :",
            text: "Vous pouvez retirer des ingrédients lors de la personnalisation de vos produits. Pour des questions spécifiques sur les allergènes, contactez-nous directement."
        },
        'fidelite': {
            response: "Notre programme de fidélité :",
            card: {
                title: "Programme Fidélité",
                items: [
                    "⭐ Cumulez des points à chaque commande",
                    "🎁 Échangez vos points contre des récompenses",
                    "🎂 Offres spéciales pour votre anniversaire"
                ]
            },
            action: {
                text: "En savoir plus",
                link: "fidelite.html"
            }
        },
        'contact': {
            response: "Pour nous contacter :",
            card: {
                title: "Coordonnées",
                items: [
                    "📞 Téléphone : Voir le numéro sur le site",
                    "📍 Adresse : Voir la section 'Nous trouver'",
                    "💬 WhatsApp : Cliquez sur le bouton vert flottant"
                ]
            }
        }
    },

    // Suggestions rapides
    quickReplies: [
        { text: '🛒 Comment commander ?', keyword: 'comment commander' },
        { text: '🕐 Horaires', keyword: 'horaires' },
        { text: '🚚 Livraison', keyword: 'livraison' },
        { text: '💳 Paiement', keyword: 'paiement' },
        { text: '⭐ Programme fidélité', keyword: 'fidelite' },
        { text: '📞 Contact', keyword: 'contact' }
    ],

    init() {
        console.log('🤖 Initialisation du Chatbot');
        this.createChatbotUI();
        this.attachEventListeners();
        this.sendWelcomeMessage();
    },

    createChatbotUI() {
        const existingToggle = document.getElementById('chatbotToggle');
        if (existingToggle) return; // Déjà créé

        const html = `
            <!-- Bouton du chatbot -->
            <button class="chatbot-toggle" id="chatbotToggle" aria-label="Assistant virtuel">
                <i class="fas fa-comments"></i>
                <span class="chatbot-badge">1</span>
            </button>

            <!-- Fenêtre du chatbot -->
            <div class="chatbot-window" id="chatbotWindow">
                <div class="chatbot-header">
                    <div class="chatbot-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="chatbot-info">
                        <h3>Assistant Virtuel</h3>
                        <div class="chatbot-status">
                            <span class="status-dot"></span>
                            <span>En ligne - Réponse instantanée</span>
                        </div>
                    </div>
                    <button class="chatbot-close" id="chatbotClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="chatbot-messages" id="chatbotMessages">
                    <!-- Messages will be added here -->
                </div>

                <div class="chatbot-suggestions" id="chatbotSuggestions">
                    <!-- Quick replies will be added here -->
                </div>

                <div class="chatbot-input-area">
                    <input
                        type="text"
                        class="chatbot-input"
                        id="chatbotInput"
                        placeholder="Posez votre question..."
                        autocomplete="off"
                    >
                    <button class="chatbot-send" id="chatbotSend">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    },

    attachEventListeners() {
        const toggle = document.getElementById('chatbotToggle');
        const close = document.getElementById('chatbotClose');
        const send = document.getElementById('chatbotSend');
        const input = document.getElementById('chatbotInput');

        if (toggle) {
            toggle.addEventListener('click', () => this.toggle());
        }

        if (close) {
            close.addEventListener('click', () => this.close());
        }

        if (send) {
            send.addEventListener('click', () => this.sendUserMessage());
        }

        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendUserMessage();
                }
            });
        }
    },

    toggle() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbotWindow');
        const toggle = document.getElementById('chatbotToggle');
        const badge = document.querySelector('.chatbot-badge');

        if (window && toggle) {
            window.classList.toggle('active', this.isOpen);
            toggle.classList.toggle('active', this.isOpen);

            if (this.isOpen) {
                toggle.innerHTML = '<i class="fas fa-times"></i>';
                if (badge) badge.style.display = 'none';
                document.getElementById('chatbotInput')?.focus();
            } else {
                toggle.innerHTML = '<i class="fas fa-comments"></i>';
            }
        }
    },

    open() {
        if (!this.isOpen) {
            this.toggle();
        }
    },

    close() {
        if (this.isOpen) {
            this.toggle();
        }
    },

    sendWelcomeMessage() {
        setTimeout(() => {
            this.addBotMessage(
                "👋 Bonjour ! Je suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?"
            );
            this.showQuickReplies();
        }, 500);
    },

    showQuickReplies() {
        const container = document.getElementById('chatbotSuggestions');
        if (!container) return;

        container.innerHTML = '';

        this.quickReplies.forEach(reply => {
            const chip = document.createElement('button');
            chip.className = 'suggestion-chip';
            chip.innerHTML = reply.text;
            chip.addEventListener('click', () => {
                this.handleQuickReply(reply.keyword);
            });
            container.appendChild(chip);
        });
    },

    handleQuickReply(keyword) {
        // Ajouter le message de l'utilisateur
        const reply = this.quickReplies.find(r => r.keyword === keyword);
        if (reply) {
            this.addUserMessage(reply.text);
        }

        // Répondre
        setTimeout(() => {
            this.respondToKeyword(keyword);
        }, 600);
    },

    sendUserMessage() {
        const input = document.getElementById('chatbotInput');
        if (!input) return;

        const message = input.value.trim();
        if (!message) return;

        this.addUserMessage(message);
        input.value = '';

        // Analyser et répondre
        setTimeout(() => {
            this.analyzeAndRespond(message);
        }, 600);
    },

    addUserMessage(text) {
        this.messages.push({ type: 'user', text, time: new Date() });
        this.renderMessage('user', text);
    },

    addBotMessage(text, card = null, action = null) {
        this.messages.push({ type: 'bot', text, card, action, time: new Date() });
        this.renderMessage('bot', text, card, action);
    },

    renderMessage(type, text, card = null, action = null) {
        const container = document.getElementById('chatbotMessages');
        if (!container) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${type}`;

        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';

        const content = document.createElement('div');
        content.className = `message-content ${type}`;
        content.textContent = text;

        // Ajouter une carte si fournie
        if (card) {
            const cardDiv = document.createElement('div');
            cardDiv.className = 'message-card';
            cardDiv.innerHTML = `
                <h4>${card.title}</h4>
                <ul>
                    ${card.items.map(item => `<li>${item}</li>`).join('')}
                </ul>
            `;
            content.appendChild(cardDiv);
        }

        // Ajouter une action si fournie
        if (action) {
            const actionDiv = document.createElement('div');
            actionDiv.className = 'message-action';
            actionDiv.textContent = action.text;
            actionDiv.addEventListener('click', () => {
                if (action.link) {
                    window.location.href = action.link;
                }
            });
            content.appendChild(actionDiv);
        }

        if (type === 'bot') {
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(content);
        } else {
            messageDiv.appendChild(content);
            messageDiv.appendChild(avatar);
        }

        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    },

    showTypingIndicator() {
        const container = document.getElementById('chatbotMessages');
        if (!container) return;

        const typing = document.createElement('div');
        typing.className = 'chatbot-message bot';
        typing.id = 'typingIndicator';
        typing.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;

        container.appendChild(typing);
        container.scrollTop = container.scrollHeight;
    },

    hideTypingIndicator() {
        const typing = document.getElementById('typingIndicator');
        if (typing) {
            typing.remove();
        }
    },

    analyzeAndRespond(message) {
        this.showTypingIndicator();

        // Rechercher dans la base de connaissances
        const lowerMessage = message.toLowerCase();
        let foundKeyword = null;

        for (const keyword in this.knowledgeBase) {
            if (lowerMessage.includes(keyword)) {
                foundKeyword = keyword;
                break;
            }
        }

        setTimeout(() => {
            this.hideTypingIndicator();

            if (foundKeyword) {
                this.respondToKeyword(foundKeyword);
            } else {
                // Réponse par défaut
                this.addBotMessage(
                    "Je ne suis pas sûr de comprendre votre question. Voici quelques sujets sur lesquels je peux vous aider :"
                );
                this.showQuickReplies();
            }
        }, 800);
    },

    respondToKeyword(keyword) {
        const knowledge = this.knowledgeBase[keyword];
        if (!knowledge) return;

        this.addBotMessage(
            knowledge.response,
            knowledge.card || null,
            knowledge.action || null
        );

        if (knowledge.text) {
            setTimeout(() => {
                this.addBotMessage(knowledge.text);
            }, 500);
        }
    }
};

// Initialiser le chatbot au chargement
document.addEventListener('DOMContentLoaded', () => {
    // Attendre que les styles soient chargés
    setTimeout(() => {
        Chatbot.init();
    }, 1000);
});

// Rendre accessible globalement
window.Chatbot = Chatbot;
