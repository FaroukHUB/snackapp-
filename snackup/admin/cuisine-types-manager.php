<?php
// cuisine-types-manager.php
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();

// Charger le repository
require_once __DIR__ . '/../../database/repositories/CuisineTypeRepository.php';

$restaurantId = SNACK_RESTAURANT_ID;
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Types de Cuisine</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    :root{
      --bg:#0b1220;
      --panel:#0f1a2b;
      --card:rgba(255,255,255,.06);
      --stroke:rgba(255,255,255,.10);
      --text:#e5e7eb;
      --muted:#9ca3af;
      --good:#16a34a;
      --bad:#ef4444;
      --brand:#60a5fa;
      --warning:#f59e0b;
      --shadow: 0 14px 50px rgba(0,0,0,.45);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: radial-gradient(1200px 800px at 10% 0%, #122044 0%, var(--bg) 60%);
      color:var(--text);
      min-height:100vh;
    }

    .wrap{max-width:1200px;margin:0 auto;padding:24px 16px 64px}

    .topbar{
      display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;
      margin-bottom:24px;
    }
    .title h1{margin:0;font-size:28px;letter-spacing:.3px;font-weight:700}
    .title p{margin:8px 0 0;color:var(--muted);font-size:14px;line-height:1.5}
    .actions{display:flex;gap:10px;flex-wrap:wrap}

    .btn{
      border:1px solid var(--stroke);
      background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.04) 100%);
      color:#fff;
      padding:10px 18px;
      border-radius:12px;
      cursor:pointer;
      font-weight:500;
      transition: all .2s ease;
      display:inline-flex;align-items:center;gap:8px;
      user-select:none;
      font-size:14px;
      text-decoration:none;
    }
    .btn:hover{transform: translateY(-2px);background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, rgba(255,255,255,.08) 100%);box-shadow: 0 4px 12px rgba(0,0,0,.2)}
    .btn:active{transform: translateY(0)}
    .btn-primary{
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      border-color: transparent;
      box-shadow: 0 2px 12px rgba(34,197,94,.4);
    }
    .btn-primary:hover{background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);box-shadow: 0 4px 18px rgba(34,197,94,.5)}

    .stats{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap:14px;
      margin-bottom:32px;
    }
    .stat-card{
      background: linear-gradient(135deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      padding:20px;
      display:flex;
      align-items:center;
      gap:16px;
    }
    .stat-icon{
      width:48px;height:48px;
      border-radius:12px;
      display:flex;align-items:center;justify-content:center;
      font-size:22px;
    }
    .stat-icon.good{background:rgba(34,197,94,.15);color:#22c55e}
    .stat-icon.brand{background:rgba(96,165,250,.15);color:#60a5fa}
    .stat-icon.warning{background:rgba(245,158,11,.15);color:#f59e0b}
    .stat-content h3{margin:0;font-size:32px;font-weight:700;letter-spacing:-.5px}
    .stat-content p{margin:4px 0 0;color:var(--muted);font-size:13px}

    .section{margin-bottom:40px}
    .section-header{
      display:flex;align-items:center;justify-content:space-between;
      margin-bottom:16px;
    }
    .section-title{
      font-size:20px;font-weight:600;letter-spacing:.2px;
      display:flex;align-items:center;gap:10px;
    }

    .types-grid{
      display:grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap:16px;
    }
    .type-card{
      background: linear-gradient(135deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      padding:20px;
      transition:all .2s ease;
      cursor:pointer;
      position:relative;
      overflow:hidden;
    }
    .type-card:hover{
      transform:translateY(-4px);
      box-shadow:0 8px 24px rgba(0,0,0,.3);
      border-color:rgba(96,165,250,.4);
    }
    .type-card.active{
      border-color:#22c55e;
      background: linear-gradient(135deg, rgba(34,197,94,.08) 0%, rgba(34,197,94,.02) 100%);
    }
    .type-card.active::before{
      content:'';
      position:absolute;
      top:0;left:0;right:0;
      height:3px;
      background:linear-gradient(90deg, #22c55e, #4ade80);
    }
    .type-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:12px;
    }
    .type-icon{
      width:44px;height:44px;
      border-radius:10px;
      background:rgba(96,165,250,.12);
      color:#60a5fa;
      display:flex;align-items:center;justify-content:center;
      font-size:20px;
    }
    .type-card.active .type-icon{
      background:rgba(34,197,94,.15);
      color:#22c55e;
    }
    .type-badge{
      padding:4px 10px;
      border-radius:8px;
      font-size:11px;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:.5px;
    }
    .type-badge.active{
      background:rgba(34,197,94,.2);
      color:#22c55e;
    }
    .type-badge.inactive{
      background:rgba(156,163,175,.15);
      color:var(--muted);
    }
    .type-name{
      font-size:18px;
      font-weight:600;
      margin:0 0 6px;
    }
    .type-desc{
      color:var(--muted);
      font-size:13px;
      line-height:1.5;
      margin:0 0 16px;
      min-height:40px;
    }
    .type-stats{
      display:flex;
      gap:12px;
      padding-top:12px;
      border-top:1px solid var(--stroke);
      font-size:12px;
      color:var(--muted);
    }
    .type-stat{
      display:flex;
      align-items:center;
      gap:6px;
    }
    .type-stat i{color:var(--brand)}
    .type-actions{
      display:flex;
      gap:8px;
      margin-top:12px;
    }
    .btn-sm{
      padding:6px 12px;
      font-size:12px;
      border-radius:8px;
    }
    .btn-success{
      background:rgba(34,197,94,.12);
      border-color:rgba(34,197,94,.3);
      color:#22c55e;
    }
    .btn-success:hover{
      background:rgba(34,197,94,.2);
    }
    .btn-danger{
      background:rgba(239,68,68,.12);
      border-color:rgba(239,68,68,.3);
      color:#ef4444;
    }
    .btn-danger:hover{
      background:rgba(239,68,68,.2);
    }
    .btn-ghost{
      background:transparent;
      border-color:var(--stroke);
    }

    .empty-state{
      text-align:center;
      padding:60px 20px;
      background:linear-gradient(135deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.01) 100%);
      border:1px dashed var(--stroke);
      border-radius:var(--radius);
    }
    .empty-state i{
      font-size:48px;
      color:var(--muted);
      margin-bottom:16px;
      opacity:.5;
    }
    .empty-state h3{
      margin:0 0 8px;
      font-size:18px;
      color:var(--text);
    }
    .empty-state p{
      margin:0;
      color:var(--muted);
      font-size:14px;
    }

    /* Toast notifications */
    .toast{
      position:fixed;
      top:24px;
      right:24px;
      background:#fff;
      color:#000;
      padding:16px 20px;
      border-radius:12px;
      box-shadow:0 8px 32px rgba(0,0,0,.4);
      z-index:10000;
      min-width:300px;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn{
      from{transform:translateX(400px);opacity:0}
      to{transform:translateX(0);opacity:1}
    }
    .toast.success{background:#22c55e;color:#fff}
    .toast.error{background:#ef4444;color:#fff}
    .toast.info{background:#60a5fa;color:#fff}

    /* Guide d'utilisation */
    .help-guide{
      background: linear-gradient(135deg, rgba(96,165,250,.12) 0%, rgba(96,165,250,.05) 100%);
      border:2px solid rgba(96,165,250,.3);
      border-radius:var(--radius);
      padding:24px;
      margin-bottom:32px;
      position:relative;
      overflow:hidden;
    }
    .help-guide::before{
      content:'';
      position:absolute;
      top:0;left:0;right:0;
      height:4px;
      background:linear-gradient(90deg, #60a5fa, #3b82f6);
    }
    .help-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:20px;
    }
    .help-title{
      font-size:20px;
      font-weight:700;
      display:flex;
      align-items:center;
      gap:12px;
      color:#60a5fa;
    }
    .help-toggle{
      background:rgba(96,165,250,.15);
      border:1px solid rgba(96,165,250,.3);
      color:#60a5fa;
      padding:8px 16px;
      border-radius:8px;
      cursor:pointer;
      font-size:13px;
      font-weight:600;
      transition:all .2s ease;
    }
    .help-toggle:hover{
      background:rgba(96,165,250,.25);
    }
    .help-steps{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
      gap:16px;
      margin-top:20px;
    }
    .help-step{
      background:rgba(255,255,255,.04);
      border:1px solid rgba(255,255,255,.08);
      border-radius:12px;
      padding:20px;
      position:relative;
    }
    .help-step-number{
      position:absolute;
      top:-12px;
      left:20px;
      width:32px;
      height:32px;
      background:linear-gradient(135deg, #60a5fa, #3b82f6);
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
      font-size:16px;
      box-shadow:0 4px 12px rgba(96,165,250,.4);
    }
    .help-step h4{
      margin:12px 0 8px;
      font-size:16px;
      color:#fff;
    }
    .help-step p{
      margin:0;
      color:var(--muted);
      font-size:13px;
      line-height:1.6;
    }
    .help-step ul{
      margin:8px 0 0;
      padding-left:20px;
      color:var(--muted);
      font-size:13px;
      line-height:1.8;
    }
    .help-step ul li{
      margin-bottom:4px;
    }
    .help-example{
      background:rgba(34,197,94,.1);
      border-left:3px solid #22c55e;
      padding:12px 16px;
      margin-top:12px;
      border-radius:6px;
      font-size:12px;
      color:#4ade80;
    }

    /* Tooltip */
    .tooltip-trigger{
      position:relative;
      cursor:help;
      border-bottom:1px dotted currentColor;
    }
    .tooltip{
      position:absolute;
      bottom:calc(100% + 8px);
      left:50%;
      transform:translateX(-50%);
      background:#1f2937;
      color:#fff;
      padding:8px 12px;
      border-radius:8px;
      font-size:12px;
      white-space:nowrap;
      pointer-events:none;
      opacity:0;
      transition:opacity .2s ease;
      z-index:1000;
      box-shadow:0 4px 12px rgba(0,0,0,.3);
    }
    .tooltip::after{
      content:'';
      position:absolute;
      top:100%;
      left:50%;
      transform:translateX(-50%);
      border:6px solid transparent;
      border-top-color:#1f2937;
    }
    .tooltip-trigger:hover .tooltip{
      opacity:1;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1>🍽️ Types de Cuisine</h1>
        <p>Configurez les types de cuisine disponibles pour votre restaurant</p>
      </div>
      <div class="actions">
        <a href="index.php" class="btn btn-ghost">
          <i class="fas fa-arrow-left"></i> Retour
        </a>
      </div>
    </div>

    <!-- Guide d'utilisation -->
    <div class="help-guide" id="helpGuide">
      <div class="help-header">
        <div class="help-title">
          <i class="fas fa-graduation-cap"></i>
          <span>Comment ça marche ?</span>
        </div>
        <button class="help-toggle" onclick="toggleHelp()">
          <i class="fas fa-times"></i> Masquer
        </button>
      </div>

      <div class="help-steps" id="helpContent">
        <div class="help-step">
          <div class="help-step-number">1</div>
          <h4>📋 Activer des types de cuisine</h4>
          <p>Choisissez les types de cuisine que propose votre restaurant :</p>
          <ul>
            <li>Parcourez la section "Types disponibles"</li>
            <li>Cliquez sur "Activer ce type" sur ceux que vous proposez</li>
            <li>Les étapes et options par défaut sont copiées automatiquement</li>
          </ul>
          <div class="help-example">
            <strong>💡 Exemple :</strong> Vous faites des burgers ET des tacos ? Activez les deux types !
          </div>
        </div>

        <div class="help-step">
          <div class="help-step-number">2</div>
          <h4>⚙️ Configurer chaque type</h4>
          <p>Personnalisez les étapes de commande pour chaque type :</p>
          <ul>
            <li>Cliquez sur "Configurer" sur un type activé</li>
            <li>Définissez les étapes de personnalisation (ex: sauce, garniture, cuisson)</li>
            <li>Ajoutez les options pour chaque étape</li>
            <li>Configurez les prix et les limites</li>
          </ul>
          <div class="help-example">
            <strong>💡 Exemple :</strong> Pour un burger : Étape 1 = Pain, Étape 2 = Viande, Étape 3 = Garnitures, Étape 4 = Sauce
          </div>
        </div>

        <div class="help-step">
          <div class="help-step-number">3</div>
          <h4>✅ Vérifier et tester</h4>
          <p>Assurez-vous que tout fonctionne correctement :</p>
          <ul>
            <li>Vérifiez que vos types sont bien activés (section verte en haut)</li>
            <li>Consultez les statistiques pour voir le nombre d'étapes configurées</li>
            <li>Testez sur le site client pour valider le parcours</li>
          </ul>
          <div class="help-example">
            <strong>💡 Astuce :</strong> Vous pouvez désactiver temporairement un type sans perdre sa configuration !
          </div>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon brand">
          <i class="fas fa-list"></i>
        </div>
        <div class="stat-content">
          <h3 id="statTotal">0</h3>
          <p>Types disponibles</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon good">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3 id="statActive">0</h3>
          <p>Types activés</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-content">
          <h3 id="statSteps">0</h3>
          <p>Étapes configurées</p>
        </div>
      </div>
    </div>

    <!-- Types activés -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">
          <i class="fas fa-star" style="color:#22c55e"></i>
          Types activés
        </div>
      </div>
      <div id="activeTypesContainer"></div>
    </div>

    <!-- Types disponibles -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">
          <i class="fas fa-plus-circle" style="color:#60a5fa"></i>
          Types disponibles
        </div>
      </div>
      <div id="availableTypesContainer" class="types-grid"></div>
    </div>
  </div>

  <script>
    const $ = s => document.querySelector(s);
    const $$ = s => document.querySelectorAll(s);
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const RESTAURANT_ID = <?= $restaurantId ?>;

    let allTypes = [];
    let activeTypes = [];

    // Toast notification
    function toast(type, message) {
      const div = document.createElement('div');
      div.className = `toast ${type}`;
      div.innerHTML = `<strong>${message}</strong>`;
      document.body.appendChild(div);
      setTimeout(() => div.remove(), 3000);
    }

    // Charger tous les types
    async function loadAllTypes() {
      try {
        const response = await fetch('/snackup/admin/api/cuisine-types.php');
        const data = await response.json();
        if (data.success) {
          allTypes = data.types || [];
          updateStats();
          renderAvailableTypes();
        }
      } catch (error) {
        console.error('Erreur chargement types:', error);
        toast('error', 'Erreur lors du chargement des types');
      }
    }

    // Charger les types activés
    async function loadActiveTypes() {
      try {
        const response = await fetch(`/snackup/admin/api/cuisine-types.php?restaurant_id=${RESTAURANT_ID}&active_only=1`);
        const data = await response.json();
        if (data.success) {
          activeTypes = data.types || [];
          updateStats();
          renderActiveTypes();
          renderAvailableTypes();
        }
      } catch (error) {
        console.error('Erreur chargement types actifs:', error);
      }
    }

    // Mettre à jour les statistiques
    function updateStats() {
      $('#statTotal').textContent = allTypes.length;
      $('#statActive').textContent = activeTypes.length;

      const totalSteps = activeTypes.reduce((sum, type) => {
        return sum + (type.steps ? type.steps.length : 0);
      }, 0);
      $('#statSteps').textContent = totalSteps;
    }

    // Afficher les types activés
    function renderActiveTypes() {
      const container = $('#activeTypesContainer');

      if (activeTypes.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-utensils"></i>
            <h3>🚀 Commencez par activer vos types de cuisine</h3>
            <p>👇 Descendez dans la section "Types disponibles" ci-dessous</p>
            <p style="margin-top:12px;color:#60a5fa">Cliquez sur "Activer ce type" sur ceux que vous proposez (Burger, Tacos, etc.)</p>
          </div>
        `;
        return;
      }

      container.innerHTML = `
        <div class="types-grid">
          ${activeTypes.map(type => `
            <div class="type-card active">
              <div class="type-badge active">Actif</div>
              <div class="type-header">
                <div class="type-icon">
                  <i class="fas fa-utensils"></i>
                </div>
              </div>
              <h3 class="type-name">${type.name}</h3>
              <p class="type-desc">${type.description || ''}</p>
              <div class="type-stats">
                <div class="type-stat">
                  <i class="fas fa-layer-group"></i>
                  <span>${type.steps_count || 0} étapes</span>
                </div>
                <div class="type-stat">
                  <i class="fas fa-list-ul"></i>
                  <span>${type.options_count || 0} options</span>
                </div>
              </div>
              <div class="type-actions">
                <button class="btn btn-sm btn-primary" onclick="configureType(${type.id})" title="Personnaliser les étapes et options de ce type">
                  <i class="fas fa-cog"></i> Configurer
                </button>
                <button class="btn btn-sm btn-danger" onclick="deactivateType(${type.id})" title="Désactiver temporairement (la configuration sera conservée)">
                  <i class="fas fa-times"></i> Désactiver
                </button>
              </div>
            </div>
          `).join('')}
        </div>
      `;
    }

    // Afficher les types disponibles
    function renderAvailableTypes() {
      const container = $('#availableTypesContainer');
      const activeIds = activeTypes.map(t => t.cuisine_type_id || t.id);
      const available = allTypes.filter(t => !activeIds.includes(t.id));

      if (available.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>Tous les types sont activés !</h3>
            <p>Vous utilisez tous les types de cuisine disponibles</p>
          </div>
        `;
        return;
      }

      container.innerHTML = available.map(type => `
        <div class="type-card">
          <div class="type-badge inactive">Disponible</div>
          <div class="type-header">
            <div class="type-icon">
              <i class="fas fa-utensils"></i>
            </div>
          </div>
          <h3 class="type-name">${type.name}</h3>
          <p class="type-desc">${type.description || ''}</p>
          <div class="type-actions">
            <button class="btn btn-sm btn-success" onclick="activateType(${type.id}, '${type.name}')" title="Activer ce type pour votre restaurant (copie les étapes par défaut)">
              <i class="fas fa-plus"></i> Activer ce type
            </button>
          </div>
        </div>
      `).join('');
    }

    // Activer un type
    async function activateType(cuisineTypeId, name) {
      if (!confirm(`✅ Activer le type "${name}" ?\n\n📋 Ce qui va se passer :\n• Les étapes par défaut seront copiées automatiquement\n• Les options standard seront ajoutées\n• Vous pourrez ensuite personnaliser dans "Configurer"\n\nContinuer ?`)) {
        return;
      }

      try {
        const response = await fetch('/snackup/admin/api/cuisine-types.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            action: 'activate',
            restaurant_id: RESTAURANT_ID,
            cuisine_type_id: cuisineTypeId,
            csrf_token: CSRF_TOKEN
          })
        });

        const data = await response.json();
        if (data.success) {
          toast('success', `✅ Type "${name}" activé ! Cliquez sur "Configurer" pour personnaliser`);
          await loadActiveTypes();
        } else {
          toast('error', data.error || 'Erreur lors de l\'activation');
        }
      } catch (error) {
        console.error('Erreur:', error);
        toast('error', 'Erreur lors de l\'activation');
      }
    }

    // Désactiver un type
    async function deactivateType(restaurantCuisineTypeId) {
      if (!confirm('⚠️ Désactiver ce type ?\n\n💾 Rassurez-vous :\n• Votre configuration (étapes, options) sera conservée\n• Vous pouvez le réactiver à tout moment\n• Il ne sera juste plus visible sur le site client\n\nContinuer ?')) {
        return;
      }

      try {
        const response = await fetch('/snackup/admin/api/cuisine-types.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            action: 'deactivate',
            restaurant_cuisine_type_id: restaurantCuisineTypeId,
            csrf_token: CSRF_TOKEN
          })
        });

        const data = await response.json();
        if (data.success) {
          toast('success', 'Type désactivé avec succès');
          await loadActiveTypes();
        } else {
          toast('error', data.error || 'Erreur lors de la désactivation');
        }
      } catch (error) {
        console.error('Erreur:', error);
        toast('error', 'Erreur lors de la désactivation');
      }
    }

    // Configurer un type
    function configureType(restaurantCuisineTypeId) {
      window.location.href = `cuisine-type-config.php?id=${restaurantCuisineTypeId}`;
    }

    // Chargement initial
    loadAllTypes();
    loadActiveTypes();

    // Toggle du guide d'aide
    function toggleHelp() {
      const guide = $('#helpGuide');
      const content = $('#helpContent');
      const btn = guide.querySelector('.help-toggle');

      if (content.style.display === 'none') {
        content.style.display = 'grid';
        btn.innerHTML = '<i class="fas fa-times"></i> Masquer';
        localStorage.setItem('helpGuideVisible', 'true');
      } else {
        content.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-question-circle"></i> Afficher l\'aide';
        localStorage.setItem('helpGuideVisible', 'false');
      }
    }

    // Restaurer l'état du guide
    window.addEventListener('DOMContentLoaded', () => {
      const isVisible = localStorage.getItem('helpGuideVisible');
      if (isVisible === 'false') {
        toggleHelp();
      }
    });
  </script>
</body>
</html>
