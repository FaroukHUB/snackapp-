// Cuisine Selection Page - Interactive & Guided
// Generate or retrieve session ID
function getSessionId() {
    let sessionId = localStorage.getItem('demoSessionId');
    if (!sessionId) {
        // Generate unique session ID: demo_timestamp_random
        sessionId = `demo_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        localStorage.setItem('demoSessionId', sessionId);
    }
    return sessionId;
}

// Initialize session
const SESSION_ID = getSessionId();
console.log('Demo Session ID:', SESSION_ID);

// State management
const selectionState = {
    selectedCuisines: [],
    sessionId: SESSION_ID
};

// Cuisine types - CHARGÉS DEPUIS L'API (pas hardcodés)
let cuisineTypes = [];

// Emoji par défaut selon le slug (fallback si pas d'icône)
const defaultEmojis = {
    'burger': '🍔',
    'pizza': '🍕',
    'sushi': '🍣',
    'tacos': '🌮',
    'asian': '🍜',
    'kebab': '🥙',
    'poke': '🥗',
    'bowl': '🥗',
    'desserts': '🍰',
    'vegan': '🥬',
    'vegetarien': '🥬',
    'french': '🥖',
    'francaise': '🥖',
    'seafood': '🦞',
    'bbq': '🍖',
    'sandwich': '🥪',
    'pates': '🍝',
    'riz-crousty': '🍚'
};

// Assistant messages
const assistantMessages = [
    {
        trigger: 'start',
        message: '👋 Bonjour ! Je suis votre assistant virtuel. Sélectionnez un ou plusieurs types de cuisine qui correspondent à votre restaurant. Vous pourrez toujours les modifier plus tard dans l\'administration.'
    },
    {
        trigger: 'firstSelection',
        message: '🎉 Excellent choix ! N\'hésitez pas à en sélectionner plusieurs si votre restaurant propose différents types de cuisine.'
    },
    {
        trigger: 'multipleSelections',
        message: '✨ Parfait ! Vous pouvez continuer à ajouter d\'autres types ou cliquer sur "Continuer" pour accéder à votre espace admin.'
    },
    {
        trigger: 'readyToContinue',
        message: '🚀 Super ! Vous êtes prêt. Cliquez sur "Continuer vers mon admin" pour découvrir votre tableau de bord et commencer à gérer votre menu.'
    }
];

// Load cuisine types from API
async function loadCuisineTypesFromAPI() {
    try {
        console.log('📡 Chargement des types de cuisine depuis l\'API...');
        const response = await fetch('api/onboarding.php?action=get_cuisine_types');
        const data = await response.json();

        if (data.success && data.types) {
            cuisineTypes = data.types.map(type => {
                // Déterminer l'emoji à utiliser
                const emoji = defaultEmojis[type.slug] || defaultEmojis[type.slug?.toLowerCase()] || '🍽️';

                return {
                    id: type.id,
                    name: type.name,
                    emoji: emoji,
                    image: `images/cuisine-types/${type.slug}.svg`,
                    description: type.description || `Type de cuisine ${type.name}`,
                    slug: type.slug,
                    steps_count: type.steps_count || 0,
                    options_count: type.options_count || 0
                };
            });
            console.log(`✅ ${cuisineTypes.length} types de cuisine chargés depuis la DB`);
        } else {
            console.error('❌ Erreur lors du chargement:', data.error);
            showError('Impossible de charger les types de cuisine');
        }
    } catch (error) {
        console.error('❌ Erreur réseau:', error);
        showError('Erreur de connexion au serveur');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Initialisation de la sélection de cuisine');
    await loadCuisineTypesFromAPI();
    renderCuisineCards();
    setupEventListeners();
    await loadPreviousSelections();
});

// Render cuisine cards
function renderCuisineCards() {
    const grid = document.getElementById('cuisineGrid');

    if (cuisineTypes.length === 0) {
        grid.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">Chargement...</p>';
        return;
    }

    grid.innerHTML = cuisineTypes.map(cuisine => `
        <div class="cuisine-card" data-cuisine="${cuisine.id}">
            <div class="selected-badge">✓ Sélectionné</div>
            <img src="${cuisine.image}" alt="${cuisine.name}" class="cuisine-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2260%22 text-anchor=%22middle%22 dy=%22.3em%22%3E${cuisine.emoji}%3C/text%3E%3C/svg%3E'">
            <div class="cuisine-emoji">${cuisine.emoji}</div>
            <div class="cuisine-overlay">
                <div class="cuisine-name">${cuisine.name}</div>
                <div class="cuisine-description">${cuisine.description}</div>
            </div>
        </div>
    `).join('');

    // Add click handlers
    document.querySelectorAll('.cuisine-card').forEach(card => {
        card.addEventListener('click', () => toggleCuisine(card.dataset.cuisine));
    });
}

// Show error message
function showError(message) {
    const grid = document.getElementById('cuisineGrid');
    grid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:40px;background:rgba(239,68,68,0.1);border-radius:12px;color:#ef4444;">
            <p style="font-size:18px;margin:0;"><strong>⚠️ ${message}</strong></p>
            <p style="margin:10px 0 0;font-size:14px;">Veuillez réessayer ou contacter le support.</p>
        </div>
    `;
}

// Toggle cuisine selection
function toggleCuisine(cuisineId) {
    // Convertir en nombre
    cuisineId = parseInt(cuisineId);

    const index = selectionState.selectedCuisines.indexOf(cuisineId);
    const card = document.querySelector(`[data-cuisine="${cuisineId}"]`);

    if (index > -1) {
        // Deselect
        selectionState.selectedCuisines.splice(index, 1);
        card.classList.remove('selected');
    } else {
        // Select
        selectionState.selectedCuisines.push(cuisineId);
        card.classList.add('selected');

        // Show assistant message on first selection
        if (selectionState.selectedCuisines.length === 1) {
            showAssistantMessage('firstSelection');
        } else if (selectionState.selectedCuisines.length === 2) {
            showAssistantMessage('multipleSelections');
        } else if (selectionState.selectedCuisines.length >= 3) {
            showAssistantMessage('readyToContinue');
        }
    }

    updateUI();
    saveSelections();
}

// Update UI
function updateUI() {
    const count = selectionState.selectedCuisines.length;
    document.getElementById('selectionCount').textContent = count;

    // Enable/disable continue button
    const btnContinue = document.getElementById('btnContinue');
    btnContinue.disabled = count === 0;

    if (count > 0) {
        btnContinue.style.opacity = '1';
    } else {
        btnContinue.style.opacity = '0.5';
    }
}

// Setup event listeners
function setupEventListeners() {
    const btnContinue = document.getElementById('btnContinue');
    btnContinue.addEventListener('click', finishSelection);
}

// Save selections to localStorage with session ID
function saveSelections() {
    const data = {
        sessionId: SESSION_ID,
        cuisines: selectionState.selectedCuisines,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('cuisineSelection', JSON.stringify(data));
}

// Load previous selections (from localStorage AND from DB)
async function loadPreviousSelections() {
    // 1. Charger depuis la DB les types déjà activés
    try {
        const response = await fetch('api/onboarding.php?action=get_selected_types');
        const data = await response.json();

        if (data.success && data.selected && data.selected.length > 0) {
            console.log('📋 Types déjà activés en DB:', data.selected);
            selectionState.selectedCuisines = data.selected.map(id => parseInt(id));

            // Update UI
            selectionState.selectedCuisines.forEach(cuisineId => {
                const card = document.querySelector(`[data-cuisine="${cuisineId}"]`);
                if (card) {
                    card.classList.add('selected');
                }
            });

            updateUI();
            return; // Priorité aux données DB
        }
    } catch (e) {
        console.error('Erreur chargement depuis DB:', e);
    }

    // 2. Sinon, charger depuis localStorage (session en cours)
    const saved = localStorage.getItem('cuisineSelection');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            // Only load if same session
            if (data.sessionId === SESSION_ID) {
                selectionState.selectedCuisines = data.cuisines || [];

                // Update UI
                selectionState.selectedCuisines.forEach(cuisineId => {
                    const card = document.querySelector(`[data-cuisine="${cuisineId}"]`);
                    if (card) {
                        card.classList.add('selected');
                    }
                });

                updateUI();
            }
        } catch (e) {
            console.error('Error loading selections:', e);
        }
    }
}

// Finish selection and redirect
async function finishSelection() {
    if (selectionState.selectedCuisines.length === 0) {
        alert('Veuillez sélectionner au moins un type de cuisine 🍴');
        return;
    }

    const btnContinue = document.getElementById('btnContinue');
    btnContinue.textContent = '⏳ Enregistrement...';
    btnContinue.disabled = true;

    try {
        // Récupérer la config de l'onboarding
        const onboardingConfig = JSON.parse(localStorage.getItem('onboardingConfig') || '{}');

        // Enregistrer dans la DB via l'API
        console.log('💾 Enregistrement des sélections dans la DB...');
        const response = await fetch('api/onboarding.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_selections',
                cuisine_ids: selectionState.selectedCuisines,
                onboarding_config: onboardingConfig
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Enregistrement réussi:', data);

            // Marquer l'onboarding comme terminé
            localStorage.setItem('cuisineSelectionComplete', 'true');
            localStorage.setItem('onboardingComplete', 'true');

            // Afficher le message de succès
            btnContinue.textContent = `✅ ${data.total_activated} type(s) activé(s) !`;

            // Attendre un peu puis rediriger vers la page de gestion des types
            setTimeout(() => {
                showAssistantMessage('readyToContinue');

                setTimeout(() => {
                    // Rediriger vers la page de gestion des types de cuisine dans l'admin
                    window.location.href = `../admin/cuisine-types-manager.php?onboarding_complete=1`;
                }, 1500);
            }, 800);
        } else {
            console.error('❌ Erreur:', data.error);
            alert(`Erreur lors de l'enregistrement: ${data.error}`);
            btnContinue.textContent = 'Continuer vers mon admin 🚀';
            btnContinue.disabled = false;
        }
    } catch (error) {
        console.error('❌ Erreur réseau:', error);
        alert('Erreur de connexion au serveur. Veuillez réessayer.');
        btnContinue.textContent = 'Continuer vers mon admin 🚀';
        btnContinue.disabled = false;
    }
}

// Assistant bubble functions
function showAssistantMessage(trigger) {
    const message = assistantMessages.find(m => m.trigger === trigger);
    if (!message) return;

    const bubble = document.getElementById('assistantBubble');
    const text = document.getElementById('assistantText');

    text.textContent = message.message;
    bubble.classList.add('active');

    // Auto-hide after 8 seconds
    setTimeout(() => {
        bubble.classList.remove('active');
    }, 8000);
}

function toggleBubble() {
    const bubble = document.getElementById('assistantBubble');
    bubble.classList.toggle('active');
}

function closeBubble() {
    const bubble = document.getElementById('assistantBubble');
    bubble.classList.remove('active');
}

// Session cleanup (optional - cleanup old demo sessions after 24h)
function cleanupOldSessions() {
    const keys = Object.keys(localStorage);
    const now = Date.now();
    const dayInMs = 24 * 60 * 60 * 1000;

    keys.forEach(key => {
        if (key.startsWith('demo_')) {
            try {
                const data = JSON.parse(localStorage.getItem(key));
                if (data.timestamp) {
                    const age = now - new Date(data.timestamp).getTime();
                    if (age > dayInMs) {
                        localStorage.removeItem(key);
                    }
                }
            } catch (e) {
                // Invalid data, skip
            }
        }
    });
}

// Run cleanup on load
cleanupOldSessions();
