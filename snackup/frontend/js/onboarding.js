// Onboarding Wizard - Interactive & Visual
// Configuration state
const onboardingState = {
    currentStep: 1,
    totalSteps: 4,
    selectedCuisine: null,
    config: {
        deliveryMode: 'delivery',
        hours: 'hours-standard'
    }
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
    }
];

// Initialize onboarding
document.addEventListener('DOMContentLoaded', () => {
    renderCuisineCards();
    setupEventListeners();
    updateUI();
});

// Render cuisine cards
function renderCuisineCards() {
    const grid = document.getElementById('cuisineGrid');
    grid.innerHTML = cuisineTypes.map(cuisine => `
        <div class="cuisine-card" data-cuisine="${cuisine.id}">
            <div class="selected-badge">✓ Sélectionné</div>
            <img src="${cuisine.image}" alt="${cuisine.name}" class="cuisine-image">
            <div class="cuisine-overlay">
                <div class="cuisine-name">${cuisine.name}</div>
            </div>
        </div>
    `).join('');

    // Add click handlers
    document.querySelectorAll('.cuisine-card').forEach(card => {
        card.addEventListener('click', () => selectCuisine(card.dataset.cuisine));
    });
}

// Select cuisine type
function selectCuisine(cuisineId) {
    // Remove previous selection
    document.querySelectorAll('.cuisine-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add new selection
    const selectedCard = document.querySelector(`[data-cuisine="${cuisineId}"]`);
    selectedCard.classList.add('selected');

    onboardingState.selectedCuisine = cuisineId;
    updateNextButton();
}

// Setup event listeners
function setupEventListeners() {
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');

    btnNext.addEventListener('click', nextStep);
    btnPrev.addEventListener('click', prevStep);

    // Config options
    document.querySelectorAll('.config-option').forEach(option => {
        option.addEventListener('click', function() {
            const section = this.parentElement;
            section.querySelectorAll('.config-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');

            // Save config
            const configType = this.dataset.config;
            if (configType.startsWith('hours-')) {
                onboardingState.config.hours = configType;
            } else {
                onboardingState.config.deliveryMode = configType;
            }
        });
    });
}

// Next step
function nextStep() {
    // Validation
    if (onboardingState.currentStep === 2 && !onboardingState.selectedCuisine) {
        alert('Veuillez choisir un type de cuisine 🍴');
        return;
    }

    if (onboardingState.currentStep === 4) {
        finishOnboarding();
        return;
    }

    onboardingState.currentStep++;
    updateUI();
}

// Previous step
function prevStep() {
    if (onboardingState.currentStep > 1) {
        onboardingState.currentStep--;
        updateUI();
    }
}

// Update UI
function updateUI() {
    const { currentStep, totalSteps } = onboardingState;

    // Update progress bar
    const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
    document.getElementById('progressFill').style.width = `${progress}%`;

    // Update step indicators
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');

        if (stepNum === currentStep) {
            step.classList.add('active');
        } else if (stepNum < currentStep) {
            step.classList.add('completed');
        }
    });

    // Show/hide content
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');

    // Update header
    updateHeader();

    // Update buttons
    updateButtons();

    // Update summary if on last step
    if (currentStep === 4) {
        updateSummary();
    }
}

// Update header based on step
function updateHeader() {
    const headers = {
        1: { title: '🎉 Bienvenue !', subtitle: 'Configuration rapide en 3 étapes' },
        2: { title: '🍴 Votre spécialité', subtitle: 'Choisissez votre type de cuisine' },
        3: { title: '⚙️ Configuration', subtitle: 'Personnalisez vos préférences' },
        4: { title: '✅ C\'est terminé !', subtitle: 'Vérifiez votre configuration' }
    };

    const header = headers[onboardingState.currentStep];
    document.getElementById('headerTitle').textContent = header.title;
    document.getElementById('headerSubtitle').textContent = header.subtitle;
}

// Update buttons
function updateButtons() {
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const { currentStep, totalSteps } = onboardingState;

    // Previous button
    btnPrev.style.display = currentStep > 1 ? 'block' : 'none';

    // Next button
    if (currentStep === 1) {
        btnNext.textContent = 'Commencer →';
        btnNext.disabled = false;
    } else if (currentStep === totalSteps) {
        btnNext.textContent = '🚀 Lancer mon espace';
        btnNext.disabled = false;
    } else {
        btnNext.textContent = 'Suivant →';
        updateNextButton();
    }
}

// Update next button state
function updateNextButton() {
    const btnNext = document.getElementById('btnNext');
    const { currentStep, selectedCuisine } = onboardingState;

    if (currentStep === 2) {
        btnNext.disabled = !selectedCuisine;
    } else {
        btnNext.disabled = false;
    }
}

// Update summary
function updateSummary() {
    // Selected cuisine
    const selectedCuisine = cuisineTypes.find(c => c.id === onboardingState.selectedCuisine);
    document.getElementById('summarySelectedCuisine').innerHTML = `
        <div class="summary-item">
            <div class="summary-item-icon">${selectedCuisine.emoji}</div>
            <div class="summary-item-text">
                <div class="summary-item-label">Type de cuisine</div>
                <div class="summary-item-value">${selectedCuisine.name}</div>
            </div>
        </div>
    `;

    // Delivery mode
    const deliveryModes = {
        'delivery': 'Livraison à domicile',
        'takeaway': 'Click & Collect',
        'both': 'Livraison + Click & Collect'
    };
    document.getElementById('summaryDelivery').textContent = deliveryModes[onboardingState.config.deliveryMode];

    // Hours
    const hours = {
        'hours-standard': '11h-14h, 18h-22h',
        'hours-extended': '11h-23h (continu)',
        'hours-custom': 'Horaires personnalisés'
    };
    document.getElementById('summaryHours').textContent = hours[onboardingState.config.hours];
}

// Finish onboarding
function finishOnboarding() {
    // Show loading state
    const btnNext = document.getElementById('btnNext');
    btnNext.textContent = '⏳ Configuration...';
    btnNext.disabled = true;

    // Save configuration
    const config = {
        cuisineType: onboardingState.selectedCuisine,
        deliveryMode: onboardingState.config.deliveryMode,
        hours: onboardingState.config.hours,
        completedAt: new Date().toISOString()
    };

    // Save to localStorage
    localStorage.setItem('onboardingComplete', 'true');
    localStorage.setItem('onboardingConfig', JSON.stringify(config));

    // Simulate API call
    setTimeout(() => {
        // Show success message
        document.querySelector('.content').innerHTML = `
            <div class="welcome-content">
                <div class="welcome-icon">🎉</div>
                <h2>Félicitations !</h2>
                <p style="font-size: 18px; margin-bottom: 30px;">Votre espace est prêt. Vous allez être redirigé...</p>
                <div class="features">
                    <div class="feature-card">
                        <div class="icon">✅</div>
                        <h3>Configuré</h3>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🚀</div>
                        <h3>Activé</h3>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎯</div>
                        <h3>Prêt</h3>
                    </div>
                </div>
            </div>
        `;

        document.querySelector('.button-group').style.display = 'none';

        // Redirect after 2 seconds
        setTimeout(() => {
            // Redirect to admin dashboard or main app
            window.location.href = '../admin/index.php';
        }, 2000);
    }, 1500);
}

// Check if onboarding was already completed
function checkOnboardingStatus() {
    if (localStorage.getItem('onboardingComplete') === 'true') {
        // Redirect to main app
        // window.location.href = '../admin/index.php';
    }
}

// Initialize check
checkOnboardingStatus();
