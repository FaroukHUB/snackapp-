# 🔧 Ajout des filtres par tags - Clients

## Où ajouter le code

Dans **`admin-panel-v2/index.php`**, dans la section Clients (onglet "Clients"), ajouter les boutons de filtres **juste avant** `<div id="customers-list">`.

## Code à ajouter

```html
<!-- FILTRES PAR TAGS -->
<div class="mb-6 flex flex-wrap gap-2">
    <button onclick="loadCustomers(null); this.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active')); this.classList.add('active');"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10 active">
        <i class="fas fa-users mr-2"></i>Tous
    </button>

    <button onclick="filterByTag('VIP')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #fbbf2420, #fbbf2410);">
        <i class="fas fa-crown mr-2" style="color: #fbbf24;"></i>VIP
    </button>

    <button onclick="filterByTag('Régulier')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #60a5fa20, #60a5fa10);">
        <i class="fas fa-user-check mr-2" style="color: #60a5fa;"></i>Régulier
    </button>

    <button onclick="filterByTag('Nouveau')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #34d39920, #34d39910);">
        <i class="fas fa-user-plus mr-2" style="color: #34d399;"></i>Nouveau
    </button>

    <button onclick="filterByTag('Livraison')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #22d3ee20, #22d3ee10);">
        <i class="fas fa-truck mr-2" style="color: #22d3ee;"></i>Livraison
    </button>

    <button onclick="filterByTag('Zone Centre')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #a78bfa20, #a78bfa10);">
        <i class="fas fa-map-marker-alt mr-2" style="color: #a78bfa;"></i>Zone Centre
    </button>

    <button onclick="filterByTag('Entreprise')"
        class="filter-tag-btn px-4 py-2 rounded-lg glass text-white font-semibold hover:bg-white/10"
        style="background: linear-gradient(135deg, #818cf820, #818cf810);">
        <i class="fas fa-building mr-2" style="color: #818cf8;"></i>Entreprise
    </button>
</div>

<style>
.filter-tag-btn.active {
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.2);
}
</style>
```

## Emplacement exact

Cherchez dans `index.php` la section:

```html
<!-- Section Clients -->
<div id="clients-section" class="section hidden">
    <h2 class="text-3xl font-bold text-white mb-6">
        <i class="fas fa-users mr-3"></i>Clients
    </h2>

    <!-- ⬇️ AJOUTER LES FILTRES ICI ⬇️ -->

    <div id="customers-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Les cartes clients seront générées par customers.js -->
    </div>
</div>
```

## Résultat

Une barre de filtres moderne avec:
- ✅ Filtres cliquables avec effet hover
- ✅ Effet actif (glow) sur le filtre sélectionné
- ✅ Icônes et couleurs correspondant aux tags
- ✅ Fonction `filterByTag()` déjà implémentée dans customers.js

## Test

Après ajout:
1. Recharger la page admin
2. Aller dans l'onglet Clients
3. Cliquer sur un filtre (ex: "VIP")
4. La liste se filtre automatiquement

---

**IMPORTANT**: Si vous ne souhaitez pas modifier index.php manuellement, les filtres fonctionnent déjà via l'API (paramètre `filter_tag`). L'ajout des boutons est purement cosmétique pour faciliter l'UX.
