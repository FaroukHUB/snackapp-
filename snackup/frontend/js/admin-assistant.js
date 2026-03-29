/**
 * Admin Assistant - Interactive Guide for Demo Users
 * Helps new users understand the admin interface
 */

class AdminAssistant {
    constructor() {
        this.isDemo = this.checkIfDemo();
        this.currentStep = 0;
        this.hasShownWelcome = localStorage.getItem('adminAssistantWelcome') === 'true';
        this.tourSteps = this.getTourSteps();

        if (this.isDemo) {
            this.init();
        }
    }

    checkIfDemo() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('demo') === '1';
    }

    init() {
        this.injectStyles();
        this.createAssistant();

        if (!this.hasShownWelcome) {
            setTimeout(() => {
                this.showWelcome();
            }, 1000);
        }
    }

    injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .admin-assistant-container {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
            }

            .admin-assistant-bubble {
                position: relative;
                background: white;
                border-radius: 20px 20px 4px 20px;
                padding: 20px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                max-width: 350px;
                margin-bottom: 15px;
                animation: slideInRight 0.5s ease;
                display: none;
            }

            .admin-assistant-bubble.active {
                display: block;
            }

            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(50px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .admin-assistant-bubble::before {
                content: '';
                position: absolute;
                bottom: -8px;
                right: 20px;
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 8px 8px 0 8px;
                border-color: white transparent transparent transparent;
            }

            .admin-assistant-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }

            .admin-assistant-title {
                font-weight: 700;
                font-size: 15px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .admin-assistant-text {
                font-size: 14px;
                line-height: 1.6;
                color: #555;
                margin-bottom: 15px;
            }

            .admin-assistant-actions {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }

            .admin-assistant-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .admin-assistant-btn-primary {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
            }

            .admin-assistant-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }

            .admin-assistant-btn-secondary {
                background: #f0f0f0;
                color: #666;
            }

            .admin-assistant-btn-secondary:hover {
                background: #e0e0e0;
            }

            .admin-assistant-icon-container {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 30px;
                box-shadow: 0 6px 24px rgba(102, 126, 234, 0.4);
                cursor: pointer;
                transition: all 0.3s ease;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% {
                    box-shadow: 0 6px 24px rgba(102, 126, 234, 0.4);
                }
                50% {
                    box-shadow: 0 6px 32px rgba(102, 126, 234, 0.6);
                }
            }

            .admin-assistant-icon-container:hover {
                transform: scale(1.1);
            }

            .admin-assistant-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ff4444;
                color: white;
                border-radius: 50%;
                width: 22px;
                height: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 700;
                animation: bounce 1s infinite;
            }

            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-5px); }
            }

            .close-bubble {
                background: none;
                border: none;
                font-size: 18px;
                color: #999;
                cursor: pointer;
                padding: 0;
                line-height: 1;
            }

            .close-bubble:hover {
                color: #333;
            }

            @media (max-width: 768px) {
                .admin-assistant-container {
                    bottom: 20px;
                    right: 20px;
                }

                .admin-assistant-bubble {
                    max-width: 280px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    createAssistant() {
        const container = document.createElement('div');
        container.className = 'admin-assistant-container';
        container.innerHTML = `
            <div class="admin-assistant-bubble" id="adminAssistantBubble">
                <div class="admin-assistant-header">
                    <div class="admin-assistant-title">
                        <span>🤖</span>
                        <span>Assistant SnackUp</span>
                    </div>
                    <button class="close-bubble" onclick="adminAssistant.closeBubble()">✕</button>
                </div>
                <div class="admin-assistant-text" id="adminAssistantText">
                    Bienvenue ! Je suis là pour vous aider.
                </div>
                <div class="admin-assistant-actions" id="adminAssistantActions">
                    <!-- Actions will be populated dynamically -->
                </div>
            </div>
            <div class="admin-assistant-icon-container" onclick="adminAssistant.toggleBubble()">
                🤖
                <span class="admin-assistant-badge" id="adminAssistantBadge" style="display: none;">!</span>
            </div>
        `;

        document.body.appendChild(container);
        this.bubble = document.getElementById('adminAssistantBubble');
        this.text = document.getElementById('adminAssistantText');
        this.actions = document.getElementById('adminAssistantActions');
        this.badge = document.getElementById('adminAssistantBadge');
    }

    getTourSteps() {
        return [
            {
                title: "Bienvenue dans votre admin ! 🎉",
                message: "Vous voici dans votre tableau de bord. C'est ici que vous gérez vos commandes, votre menu et vos paramètres. Voulez-vous que je vous fasse visiter ?",
                actions: [
                    { text: "Oui, montrez-moi !", action: () => this.startTour() },
                    { text: "Non merci", action: () => this.skipTour() }
                ]
            },
            {
                title: "📊 Le tableau de bord",
                message: "Ici vous voyez vos statistiques en temps réel : commandes du jour, revenus, clients. C'est votre vue d'ensemble quotidienne.",
                actions: [
                    { text: "Suivant →", action: () => this.nextStep() }
                ]
            },
            {
                title: "📋 Gestion des commandes",
                message: "Dans l'onglet 'Commandes', vous pouvez voir toutes vos commandes actives, les marquer comme prêtes ou terminées.",
                actions: [
                    { text: "Suivant →", action: () => this.nextStep() }
                ]
            },
            {
                title: "🍕 Votre menu",
                message: "L'onglet 'Menu' vous permet d'ajouter, modifier ou supprimer des produits. Vous pouvez aussi organiser par catégories.",
                actions: [
                    { text: "Suivant →", action: () => this.nextStep() }
                ]
            },
            {
                title: "⚙️ Paramètres",
                message: "Dans 'Paramètres', vous gérez vos horaires, moyens de paiement, zones de livraison et toutes les infos de votre restaurant.",
                actions: [
                    { text: "Suivant →", action: () => this.nextStep() }
                ]
            },
            {
                title: "🎯 C'est tout !",
                message: "Vous êtes prêt ! N'hésitez pas à cliquer sur moi si vous avez besoin d'aide. Bonne découverte !",
                actions: [
                    { text: "Merci ! ✨", action: () => this.finishTour() }
                ]
            }
        ];
    }

    showWelcome() {
        this.showMessage(this.tourSteps[0]);
        this.hasShownWelcome = true;
        localStorage.setItem('adminAssistantWelcome', 'true');
    }

    showMessage(step) {
        this.text.innerHTML = `<strong>${step.title}</strong><br><br>${step.message}`;

        this.actions.innerHTML = step.actions.map(action => `
            <button class="admin-assistant-btn ${action.text.includes('Oui') || action.text.includes('Suivant') || action.text.includes('Merci') ? 'admin-assistant-btn-primary' : 'admin-assistant-btn-secondary'}"
                    onclick="adminAssistant.handleAction('${step.actions.indexOf(action)}')">
                ${action.text}
            </button>
        `).join('');

        this.bubble.classList.add('active');
        this.badge.style.display = 'flex';
    }

    handleAction(actionIndex) {
        const currentStep = this.tourSteps[this.currentStep];
        if (currentStep.actions[actionIndex]) {
            currentStep.actions[actionIndex].action();
        }
    }

    startTour() {
        this.currentStep = 1;
        this.showMessage(this.tourSteps[this.currentStep]);
    }

    nextStep() {
        this.currentStep++;
        if (this.currentStep < this.tourSteps.length) {
            this.showMessage(this.tourSteps[this.currentStep]);
        }
    }

    skipTour() {
        this.closeBubble();
        this.badge.style.display = 'none';
    }

    finishTour() {
        this.closeBubble();
        this.badge.style.display = 'none';
        localStorage.setItem('adminAssistantTourComplete', 'true');
    }

    toggleBubble() {
        this.bubble.classList.toggle('active');

        if (this.bubble.classList.contains('active')) {
            this.badge.style.display = 'none';

            // Show help menu
            const helpStep = {
                title: "Comment puis-je vous aider ? 💡",
                message: "Choisissez un sujet pour obtenir de l'aide spécifique.",
                actions: [
                    { text: "📋 Commandes", action: () => this.showHelp('orders') },
                    { text: "🍕 Menu", action: () => this.showHelp('menu') },
                    { text: "⚙️ Paramètres", action: () => this.showHelp('settings') }
                ]
            };
            this.showMessage(helpStep);
        }
    }

    closeBubble() {
        this.bubble.classList.remove('active');
    }

    showHelp(topic) {
        const helpMessages = {
            orders: {
                title: "📋 Aide - Commandes",
                message: "Pour gérer vos commandes :<br>1. Cliquez sur 'Commandes' dans le menu<br>2. Changez le statut avec les boutons<br>3. Archivez les anciennes commandes",
                actions: [{ text: "Compris !", action: () => this.closeBubble() }]
            },
            menu: {
                title: "🍕 Aide - Menu",
                message: "Pour gérer votre menu :<br>1. Allez dans 'Menu'<br>2. Cliquez sur '+ Ajouter' pour créer<br>3. Modifiez en cliquant sur un produit",
                actions: [{ text: "Compris !", action: () => this.closeBubble() }]
            },
            settings: {
                title: "⚙️ Aide - Paramètres",
                message: "Dans Paramètres vous pouvez :<br>- Modifier vos horaires<br>- Gérer les moyens de paiement<br>- Configurer la livraison",
                actions: [{ text: "Compris !", action: () => this.closeBubble() }]
            }
        };

        if (helpMessages[topic]) {
            this.showMessage(helpMessages[topic]);
        }
    }
}

// Initialize assistant when DOM is ready
let adminAssistant;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        adminAssistant = new AdminAssistant();
    });
} else {
    adminAssistant = new AdminAssistant();
}
