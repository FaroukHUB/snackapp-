/**
 * STEPPER - Gestion du parcours en 3 étapes
 * Étape 1 : Révision du panier
 * Étape 2 : Informations de contact
 * Étape 3 : Confirmation
 */

const OrderStepper = {
    currentStep: 1,
    totalSteps: 3,

    steps: {
        1: {
            title: 'Panier',
            icon: 'fa-shopping-bag',
            description: 'Vérifiez vos produits',
            help: {
                title: 'Vérifiez votre commande',
                text: 'Assurez-vous que tous vos articles sont corrects. Vous pouvez modifier les quantités ou supprimer des produits.'
            }
        },
        2: {
            title: 'Infos',
            icon: 'fa-user',
            description: 'Vos coordonnées',
            help: {
                title: 'Renseignez vos informations',
                text: 'Indiquez votre nom, téléphone et l\'heure de retrait souhaitée pour votre commande Click & Collect.'
            }
        },
        3: {
            title: 'Validation',
            icon: 'fa-check-circle',
            description: 'Finalisez',
            help: {
                title: 'Confirmez votre commande',
                text: 'Vérifiez une dernière fois tous les détails avant de valider votre commande.'
            }
        }
    },

    init() {
        console.log('🎯 Initialisation du Stepper');
        this.createStepperUI();
        this.goToStep(1);
        this.attachEventListeners();
    },

    createStepperUI() {
        const container = document.getElementById('stepperContainer');
        if (!container) return;

        let progressWidth = 0;
        if (this.currentStep === 2) progressWidth = 50;
        if (this.currentStep === 3) progressWidth = 100;

        let html = `
            <div class="order-stepper">
                <div class="stepper-container">
                    <div class="stepper-progress" style="width: ${progressWidth}%"></div>
        `;

        // Créer les 3 étapes
        for (let i = 1; i <= this.totalSteps; i++) {
            const step = this.steps[i];
            const isActive = i === this.currentStep;
            const isCompleted = i < this.currentStep;
            const statusClass = isActive ? 'active' : (isCompleted ? 'completed' : '');

            html += `
                <div class="step ${statusClass}" data-step="${i}">
                    <div class="step-circle">
                        ${isCompleted ? '<i class="fas fa-check"></i>' : '<i class="fas ' + step.icon + '"></i>'}
                    </div>
                    <div class="step-label">${step.title}</div>
                    <div class="step-description">${step.description}</div>
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
    },

    goToStep(stepNumber) {
        if (stepNumber < 1 || stepNumber > this.totalSteps) return;

        console.log(`📍 Navigation vers l'étape ${stepNumber}`);
        this.currentStep = stepNumber;

        // Mettre à jour l'UI du stepper
        this.updateStepperUI();

        // Afficher le contenu de l'étape
        this.showStepContent(stepNumber);

        // Afficher l'aide de l'étape
        this.showStepHelp(stepNumber);

        // Mettre à jour les boutons de navigation
        this.updateNavigationButtons();

        // Scroll vers le haut
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    updateStepperUI() {
        // Mettre à jour les classes des étapes
        document.querySelectorAll('.step').forEach((el, index) => {
            const stepNum = index + 1;
            el.classList.remove('active', 'completed');

            if (stepNum === this.currentStep) {
                el.classList.add('active');
            } else if (stepNum < this.currentStep) {
                el.classList.add('completed');
            }

            // Mettre à jour l'icône
            const circle = el.querySelector('.step-circle');
            const step = this.steps[stepNum];

            if (stepNum < this.currentStep) {
                circle.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                circle.innerHTML = `<i class="fas ${step.icon}"></i>`;
            }
        });

        // Mettre à jour la barre de progression
        const progress = document.querySelector('.stepper-progress');
        if (progress) {
            let width = 0;
            if (this.currentStep === 2) width = 50;
            if (this.currentStep === 3) width = 100;
            progress.style.width = width + '%';
        }
    },

    showStepContent(stepNumber) {
        // Cacher tous les contenus
        document.querySelectorAll('.step-content').forEach(el => {
            el.classList.remove('active');
        });

        // Afficher le contenu de l'étape actuelle
        const content = document.getElementById(`step${stepNumber}Content`);
        if (content) {
            content.classList.add('active');
        }
    },

    showStepHelp(stepNumber) {
        const helpContainer = document.getElementById('stepHelpContainer');
        if (!helpContainer) return;

        const step = this.steps[stepNumber];
        helpContainer.innerHTML = `
            <div class="step-help">
                <div class="step-help-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="step-help-content">
                    <h4>${step.help.title}</h4>
                    <p>${step.help.text}</p>
                </div>
            </div>
        `;
    },

    updateNavigationButtons() {
        const prevBtn = document.getElementById('stepPrevBtn');
        const nextBtn = document.getElementById('stepNextBtn');

        if (prevBtn) {
            if (this.currentStep === 1) {
                prevBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
            }
        }

        if (nextBtn) {
            if (this.currentStep === this.totalSteps) {
                nextBtn.textContent = 'Valider la commande';
                nextBtn.innerHTML = '<i class="fas fa-check"></i> <span>Valider la commande</span>';
            } else {
                nextBtn.innerHTML = '<span>Étape suivante</span> <i class="fas fa-arrow-right"></i>';
            }

            // Vérifier si on peut passer à l'étape suivante
            const canProceed = this.validateCurrentStep();
            nextBtn.disabled = !canProceed;
        }
    },

    validateCurrentStep() {
        switch (this.currentStep) {
            case 1:
                // Vérifier que le panier n'est pas vide
                return Cart && Cart.items && Cart.items.length > 0;

            case 2:
                // Vérifier que les champs requis sont remplis
                const name = document.getElementById('customerName')?.value.trim();
                const phone = document.getElementById('customerPhone')?.value.trim();
                const time = document.getElementById('pickupTime')?.value;
                return name && phone && time;

            case 3:
                // Toujours valide à la dernière étape
                return true;

            default:
                return true;
        }
    },

    attachEventListeners() {
        // Bouton précédent
        const prevBtn = document.getElementById('stepPrevBtn');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.currentStep > 1) {
                    this.goToStep(this.currentStep - 1);
                }
            });
        }

        // Bouton suivant
        const nextBtn = document.getElementById('stepNextBtn');
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (this.currentStep < this.totalSteps) {
                    this.goToStep(this.currentStep + 1);
                } else {
                    // Dernière étape : valider la commande
                    this.finalizeOrder();
                }
            });
        }

        // Écouter les changements dans les champs du formulaire
        ['customerName', 'customerPhone', 'pickupTime'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => {
                    this.updateNavigationButtons();
                });
            }
        });

        // Écouter les changements dans le panier
        if (typeof Cart !== 'undefined') {
            const originalUpdateUI = Cart.updateUI;
            Cart.updateUI = function() {
                originalUpdateUI.call(Cart);
                OrderStepper.updateNavigationButtons();
            };
        }
    },

    finalizeOrder() {
        console.log('🎉 Validation de la commande');

        // Créer des confettis
        this.createConfetti();

        // Simuler l'envoi de la commande
        // Ici, vous devriez appeler votre API backend
        setTimeout(() => {
            alert('Commande validée avec succès ! 🎉\n\nVous recevrez un SMS de confirmation.');
            window.location.href = 'index.html';
        }, 1500);
    },

    createConfetti() {
        const colors = ['#2ec4b6', '#ff6b6b', '#ffd93d', '#6bcf7f', '#a78bfa'];

        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (Math.random() * 2 + 1) + 's';
                document.body.appendChild(confetti);

                setTimeout(() => confetti.remove(), 3000);
            }, i * 30);
        }
    }
};

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('stepperContainer')) {
        OrderStepper.init();
    }
});
