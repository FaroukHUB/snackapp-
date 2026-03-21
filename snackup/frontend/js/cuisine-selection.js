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

// Cuisine types with local images
const cuisineTypes = [
    {
        id: 'burger',
        name: 'Burgers',
        emoji: '🍔',
        image: 'images/cuisine-types/burger.svg',
        description: 'Burgers juteux et frites croustillantes'
    },
    {
        id: 'pizza',
        name: 'Pizza',
        emoji: '🍕',
        image: 'images/cuisine-types/pizza.svg',
        description: 'Pizzas artisanales et italiennes'
    },
    {
        id: 'sushi',
        name: 'Sushi',
        emoji: '🍣',
        image: 'images/cuisine-types/sushi.svg',
        description: 'Sushi frais et cuisine japonaise'
    },
    {
        id: 'tacos',
        name: 'Tacos',
        emoji: '🌮',
        image: 'images/cuisine-types/tacos.svg',
        description: 'Tacos mexicains et tex-mex'
    },
    {
        id: 'asian',
        name: 'Asiatique',
        emoji: '🍜',
        image: 'images/cuisine-types/asian.svg',
        description: 'Wok, noodles et cuisine asiatique'
    },
    {
        id: 'kebab',
        name: 'Kebab',
        emoji: '🥙',
        image: 'images/cuisine-types/kebab.svg',
        description: 'Kebabs, sandwichs et grillades'
    },
    {
        id: 'poke',
        name: 'Poké Bowl',
        emoji: '🥗',
        image: 'images/cuisine-types/poke.svg',
        description: 'Poké bowls frais et healthy'
    },
    {
        id: 'desserts',
        name: 'Desserts',
        emoji: '🍰',
        image: 'images/cuisine-types/desserts.svg',
        description: 'Pâtisseries et douceurs'
    },
    {
        id: 'vegan',
        name: 'Végétarien',
        emoji: '🥬',
        image: 'images/cuisine-types/vegan.svg',
        description: 'Cuisine végétarienne et vegan'
    },
    {
        id: 'french',
        name: 'Française',
        emoji: '🥖',
        image: 'images/cuisine-types/french.svg',
        description: 'Cuisine française traditionnelle'
    },
    {
        id: 'seafood',
        name: 'Fruits de mer',
        emoji: '🦞',
        image: 'images/cuisine-types/seafood.svg',
        description: 'Poissons et fruits de mer frais'
    },
    {
        id: 'bbq',
        name: 'BBQ & Grillades',
        emoji: '🍖',
        image: 'images/cuisine-types/bbq.svg',
        description: 'Viandes grillées et BBQ'
    }
];

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

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    renderCuisineCards();
    setupEventListeners();
    loadPreviousSelections();
});

// Render cuisine cards
function renderCuisineCards() {
    const grid = document.getElementById('cuisineGrid');
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

// Toggle cuisine selection
function toggleCuisine(cuisineId) {
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

// Load previous selections
function loadPreviousSelections() {
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
function finishSelection() {
    if (selectionState.selectedCuisines.length === 0) {
        alert('Veuillez sélectionner au moins un type de cuisine 🍴');
        return;
    }

    const btnContinue = document.getElementById('btnContinue');
    btnContinue.textContent = '⏳ Chargement...';
    btnContinue.disabled = true;

    // Save final selection with session ID
    const finalData = {
        sessionId: SESSION_ID,
        cuisines: selectionState.selectedCuisines,
        completedAt: new Date().toISOString(),
        onboardingConfig: JSON.parse(localStorage.getItem('onboardingConfig') || '{}')
    };

    localStorage.setItem('cuisineSelectionComplete', 'true');
    localStorage.setItem('finalDemoConfig', JSON.stringify(finalData));

    // Show success and redirect
    setTimeout(() => {
        showAssistantMessage('readyToContinue');

        setTimeout(() => {
            // Redirect to admin with session parameter
            window.location.href = `../admin/index.php?session=${SESSION_ID}&demo=1`;
        }, 1500);
    }, 800);
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
