// ========== PATCH URGENT - FIX BOUCLES REQUÊTES ==========
// Remplacer la fonction addBonusPoints dans customers.js

// VERSION CORRIGÉE - Ajouter points bonus (SANS double requête)
async function addBonusPoints(customerId) {
    const points = prompt('Combien de points bonus voulez-vous ajouter ?');
    if (!points || isNaN(points)) return;

    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_points',
                customer_id: customerId,
                points: parseInt(points)
            })
        });

        const data = await response.json();
        if (data.success) {
            showToast(`${points} points ajoutés !`, 'success');
            // ⚡ FIX: Une seule requête pour rafraîchir
            showCustomerDetails(customerId);
            // NE PAS appeler loadCustomers() ici - trop de données
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// ========== AUTRES OPTIMISATIONS ==========

// Protection contre les appels multiples
let isLoadingCustomers = false;

async function loadCustomers(filterTag = null) {
    // ⚡ FIX: Empêcher appels multiples simultanés
    if (isLoadingCustomers) {
        console.log('Chargement déjà en cours...');
        return;
    }

    isLoadingCustomers = true;

    try {
        let url = 'api/customers.php?action=list';
        if (filterTag) {
            url += '&filter_tag=' + encodeURIComponent(filterTag);
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            customersData = data.customers;
            renderCustomers(data.customers);
            updateCustomerStats(data.stats);
            currentFilter = filterTag;
        }
    } catch (error) {
        console.error('Erreur chargement clients:', error);
        document.getElementById('customers-list').innerHTML =
            '<p class="text-red-400 text-center py-8">Erreur de chargement</p>';
    } finally {
        isLoadingCustomers = false;
    }
}

// Protection contre double-clic
let isShowingDetails = false;

async function showCustomerDetails(customerId) {
    if (isShowingDetails) {
        console.log('Chargement détails déjà en cours...');
        return;
    }

    isShowingDetails = true;

    try {
        const response = await fetch(`api/customers.php?action=get&customer_id=${customerId}`);
        const data = await response.json();

        if (!data.success) {
            alert('Erreur chargement client');
            return;
        }

        currentCustomer = data.customer;
        const customer = data.customer;

        const modal = document.getElementById('order-modal');
        const content = document.getElementById('modal-content');

        // [... reste du code inchangé ...]

        modal.classList.remove('hidden');
    } catch (error) {
        console.error('Erreur détails client:', error);
        alert('Erreur lors du chargement des détails');
    } finally {
        isShowingDetails = false;
    }
}
