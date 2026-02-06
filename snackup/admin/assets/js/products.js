// ========== GESTION PRODUITS & TOO GOOD TO GO ==========

let productsData = [];
let tgtgOffers = [];

// Afficher erreur API visuelle
function showApiError(message, statusCode) {
    const productsList = document.getElementById('products-list');
    if (!productsList) return;

    productsList.innerHTML = `
        <div class="glass-strong rounded-2xl p-8 text-center">
            <div class="text-6xl mb-4">⚠️</div>
            <h3 class="text-white font-bold text-xl mb-2">Erreur de chargement</h3>
            <p class="text-gray-400 mb-4">${message}</p>
            ${statusCode ? `<p class="text-gray-500 text-sm mb-4">Code: ${statusCode}</p>` : ''}
            <button onclick="loadProducts()" class="px-6 py-3 rounded-xl primary-gradient text-white font-semibold btn">
                <i class="fas fa-sync-alt mr-2"></i>Réessayer
            </button>
            <div class="mt-6 p-4 bg-blue-500/10 rounded-lg text-left">
                <p class="text-blue-400 text-sm font-semibold mb-2">🔍 Diagnostic:</p>
                <ul class="text-gray-400 text-sm space-y-1">
                    <li>• Vérifiez que le serveur MySQL est actif</li>
                    <li>• Ouvrez <a href="test-api-status.php" target="_blank" class="text-blue-400 underline">test-api-status.php</a> pour diagnostiquer</li>
                    <li>• Consultez les logs PHP du serveur</li>
                </ul>
            </div>
        </div>
    `;
}

// Charger les produits
async function loadProducts() {
    try {
        const response = await fetch('api/products.php?action=list');

        // Vérifier si la réponse est OK (status 200-299)
        if (!response.ok) {
            console.error(`API Error: ${response.status} ${response.statusText}`);
            showApiError('Impossible de charger les produits', response.status);
            return;
        }

        // Vérifier que c'est bien du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('API returned non-JSON response:', contentType);
            showApiError('Réponse API invalide (non-JSON)', response.status);
            return;
        }

        const data = await response.json();

        if (data.success) {
            productsData = data.products;
            renderProducts(data.products);
        } else {
            showApiError(data.message || 'Erreur inconnue', response.status);
        }

        // Charger aussi les offres Too Good To Go
        loadTooGoodToGo();
    } catch (error) {
        console.error('Erreur chargement produits:', error);
        showApiError('Erreur réseau: ' + error.message, 0);
    }
}

// Afficher les produits
function renderProducts(products) {
    const productsList = document.getElementById('products-list');

    // Grouper par catégorie
    const byCategory = {};
    products.forEach(p => {
        if (!byCategory[p.category]) {
            byCategory[p.category] = [];
        }
        byCategory[p.category].push(p);
    });

    let html = '';

    // Section Too Good To Go en premier
    html += `
        <div class="glass-strong rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-white font-bold text-xl flex items-center gap-2">
                        <i class="fas fa-leaf text-green-400"></i>
                        Too Good To Go
                    </h3>
                    <p class="text-gray-400 text-sm mt-1">Vendez vos invendus à prix réduit</p>
                </div>
                <button onclick="showAddTgtgModal()" class="px-4 py-2 rounded-xl primary-gradient text-white font-semibold btn">
                    <i class="fas fa-plus mr-2"></i>Nouvelle offre
                </button>
            </div>
            <div id="tgtg-list" class="space-y-3">
                <!-- Offres Too Good To Go -->
            </div>
        </div>
    `;

    // Produits par catégorie
    Object.keys(byCategory).forEach(category => {
        html += `
            <div class="mb-6">
                <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-utensils"></i>${category}
                </h3>
                <div class="space-y-3">
                    ${byCategory[category].map(product => `
                        <div class="glass-strong rounded-xl p-4 flex items-center gap-4">
                            ${product.image ? `
                                <img src="../../${product.image}" alt="${product.name}" class="w-16 h-16 rounded-lg object-cover">
                            ` : `
                                <div class="w-16 h-16 rounded-lg bg-white/10 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-600"></i>
                                </div>
                            `}

                            <div class="flex-1">
                                <h4 class="text-white font-semibold">${product.name}</h4>
                                <div class="text-gray-400 text-sm mt-1">
                                    ${product.priceSolo ? `Solo: ${product.priceSolo} ${window.CURRENCY}` : ''}
                                    ${product.priceMenu ? ` | Menu: ${product.priceMenu} ${window.CURRENCY}` : ''}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    onclick="toggleProductAvailability('${product.id}')"
                                    class="px-4 py-2 rounded-xl ${product.available !== false ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'} font-semibold btn"
                                >
                                    <i class="fas fa-${product.available !== false ? 'check-circle' : 'times-circle'} mr-2"></i>
                                    ${product.available !== false ? 'Disponible' : 'Rupture'}
                                </button>

                                <button onclick="addProductToTgtg('${product.id}', '${product.name}', ${product.priceSolo || product.priceMenu})" class="px-3 py-2 rounded-xl glass text-white btn" title="Ajouter à Too Good To Go">
                                    <i class="fas fa-leaf"></i>
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    });

    productsList.innerHTML = html;
    renderTgtgOffers();
}

// Basculer disponibilité produit
async function toggleProductAvailability(productId) {
    try {
        const response = await fetch('api/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_availability',
                product_id: productId
            })
        });

        if (!response.ok) {
            console.error(`API Error: ${response.status} ${response.statusText}`);
            alert(`Erreur ${response.status}: Impossible de modifier la disponibilité`);
            return;
        }

        const data = await response.json();
        if (data.success) {
            loadProducts();
        } else {
            alert(data.message || 'Erreur inconnue');
        }
    } catch (error) {
        console.error('Erreur mise à jour disponibilité:', error);
        alert('Erreur réseau: ' + error.message);
    }
}

// ========== TOO GOOD TO GO ==========

// Charger les offres Too Good To Go
async function loadTooGoodToGo() {
    try {
        const response = await fetch('api/products.php?action=tgtg_list');

        if (!response.ok) {
            console.error(`API Error: ${response.status} ${response.statusText}`);
            return;
        }

        const data = await response.json();

        if (data.success) {
            tgtgOffers = data.offers;
            renderTgtgOffers();
        }
    } catch (error) {
        console.error('Erreur chargement TGTG:', error);
    }
}

// Afficher les offres Too Good To Go
function renderTgtgOffers() {
    const tgtgList = document.getElementById('tgtg-list');
    if (!tgtgList) return;

    if (tgtgOffers.length === 0) {
        tgtgList.innerHTML = `
            <div class="glass rounded-xl p-6 text-center text-gray-400">
                <i class="fas fa-info-circle mb-2"></i>
                <p>Aucune offre active</p>
                <p class="text-sm mt-1">Ajoutez vos invendus pour réduire le gaspillage</p>
            </div>
        `;
        return;
    }

    tgtgList.innerHTML = tgtgOffers.map(offer => {
        const discount = Math.round(((offer.original_price - offer.discount_price) / offer.original_price) * 100);

        return `
            <div class="glass rounded-xl p-4 flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 rounded-lg bg-green-500/20 flex flex-col items-center justify-center">
                        <div class="text-2xl font-bold text-green-400">-${discount}%</div>
                        <div class="text-xs text-green-300">TGTG</div>
                    </div>
                </div>

                <div class="flex-1">
                    <h4 class="text-white font-semibold text-lg">${offer.product_name}</h4>
                    <p class="text-gray-400 text-sm mt-1">${offer.description}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm">
                        <div class="text-gray-400">
                            <span class="line-through">${offer.original_price} ${window.CURRENCY}</span>
                            <span class="text-green-400 font-bold ml-2">${offer.discount_price} ${window.CURRENCY}</span>
                        </div>
                        <div class="text-gray-400">
                            <i class="fas fa-box mr-1"></i>${offer.quantity_available}/${offer.quantity_total} restants
                        </div>
                        <div class="text-gray-400">
                            <i class="fas fa-clock mr-1"></i>${offer.pickup_time}
                        </div>
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <button onclick="removeTgtgOffer('${offer.id}')" class="px-3 py-2 rounded-xl bg-red-500/20 text-red-400 btn hover:bg-red-500/30">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Afficher modal ajout TGTG
function showAddTgtgModal() {
    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-leaf text-green-400"></i>
            Nouvelle offre Too Good To Go
        </h3>

        <form onsubmit="submitTgtgOffer(event)" class="space-y-4">
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Nom du produit / Panier</label>
                <input type="text" id="tgtg-name" required class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="Ex: Panier Surprise Burger">
            </div>

            <div>
                <label class="block text-gray-300 mb-2 text-sm">Description</label>
                <textarea id="tgtg-desc" rows="2" class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="Détails du panier"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Prix original</label>
                    <input type="number" id="tgtg-original" step="0.01" required class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="18.00">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Prix réduit</label>
                    <input type="number" id="tgtg-discount" step="0.01" required class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="5.99">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Quantité disponible</label>
                    <input type="number" id="tgtg-quantity" required class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="3">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Heure de retrait</label>
                    <input type="text" id="tgtg-pickup" required class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="20:00 - 21:00">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('order-modal').classList.add('hidden')" class="flex-1 py-3 rounded-xl glass text-white btn">
                    Annuler
                </button>
                <button type="submit" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold btn">
                    <i class="fas fa-check mr-2"></i>Créer l'offre
                </button>
            </div>
        </form>
    `;

    modal.classList.remove('hidden');
}

// Ajouter produit existant à TGTG
function addProductToTgtg(productId, productName, originalPrice) {
    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    const discountPrice = (originalPrice * 0.33).toFixed(2); // -67%

    content.innerHTML = `
        <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-leaf text-green-400"></i>
            Ajouter "${productName}" à Too Good To Go
        </h3>

        <form onsubmit="submitTgtgOffer(event)" class="space-y-4">
            <input type="hidden" id="tgtg-name" value="${productName}">

            <div>
                <label class="block text-gray-300 mb-2 text-sm">Description</label>
                <textarea id="tgtg-desc" rows="2" class="w-full px-4 py-3 rounded-xl glass text-white" placeholder="Invendu du jour">Invendu du jour à prix réduit</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Prix original</label>
                    <input type="number" id="tgtg-original" step="0.01" required class="w-full px-4 py-3 rounded-xl glass text-white" value="${originalPrice}">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Prix réduit (-67%)</label>
                    <input type="number" id="tgtg-discount" step="0.01" required class="w-full px-4 py-3 rounded-xl glass text-white" value="${discountPrice}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Quantité disponible</label>
                    <input type="number" id="tgtg-quantity" required class="w-full px-4 py-3 rounded-xl glass text-white" value="2">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Heure de retrait</label>
                    <input type="text" id="tgtg-pickup" required class="w-full px-4 py-3 rounded-xl glass text-white" value="20:00 - 21:30">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('order-modal').classList.add('hidden')" class="flex-1 py-3 rounded-xl glass text-white btn">
                    Annuler
                </button>
                <button type="submit" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold btn">
                    <i class="fas fa-check mr-2"></i>Créer l'offre
                </button>
            </div>
        </form>
    `;

    modal.classList.remove('hidden');
}

// Soumettre offre TGTG
async function submitTgtgOffer(e) {
    e.preventDefault();

    const offerData = {
        action: 'tgtg_add',
        product_name: document.getElementById('tgtg-name').value,
        description: document.getElementById('tgtg-desc').value || 'Invendu du jour à prix réduit',
        original_price: parseFloat(document.getElementById('tgtg-original').value),
        discount_price: parseFloat(document.getElementById('tgtg-discount').value),
        quantity: parseInt(document.getElementById('tgtg-quantity').value),
        pickup_time: document.getElementById('tgtg-pickup').value
    };

    try {
        const response = await fetch('api/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(offerData)
        });

        const data = await response.json();
        if (data.success) {
            alert('Offre Too Good To Go créée ! 🌱');
            document.getElementById('order-modal').classList.add('hidden');
            loadProducts();
        }
    } catch (error) {
        console.error('Erreur création offre TGTG:', error);
        alert('Erreur lors de la création');
    }
}

// Supprimer offre TGTG
async function removeTgtgOffer(offerId) {
    if (!confirm('Supprimer cette offre Too Good To Go ?')) return;

    try {
        const response = await fetch('api/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'tgtg_remove',
                offer_id: offerId
            })
        });

        const data = await response.json();
        if (data.success) {
            loadProducts();
        }
    } catch (error) {
        console.error('Erreur suppression offre:', error);
    }
}
