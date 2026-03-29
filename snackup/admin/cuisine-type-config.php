<?php
// cuisine-type-config.php - Configuration détaillée d'un type de cuisine
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();

require_once __DIR__ . '/../../database/repositories/CuisineTypeRepository.php';

$restaurantId = SNACK_RESTAURANT_ID;
$typeId = (int) ($_GET['id'] ?? 0);

if (!$typeId) {
    header('Location: cuisine-types-manager.php');
    exit;
}

// Récupérer le type
$types = CuisineTypeRepository::getRestaurantTypes($restaurantId, true);
$currentType = null;
foreach ($types as $t) {
    if ((int)$t['id'] === $typeId) {
        $currentType = $t;
        break;
    }
}

if (!$currentType) {
    header('Location: cuisine-types-manager.php');
    exit;
}
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Configurer <?= e($currentType['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    :root{
      --bg:#0b1220;--panel:#0f1a2b;
      --card:rgba(255,255,255,.06);--stroke:rgba(255,255,255,.10);
      --text:#e5e7eb;--muted:#9ca3af;
      --good:#16a34a;--bad:#ef4444;--brand:#60a5fa;--warning:#f59e0b;
      --shadow:0 14px 50px rgba(0,0,0,.45);--radius:16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;
      background:radial-gradient(1200px 800px at 10% 0%,#122044 0%,var(--bg) 60%);
      color:var(--text);min-height:100vh;
    }
    .wrap{max-width:1000px;margin:0 auto;padding:24px 16px 64px}
    .topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:24px}
    .title h1{margin:0;font-size:24px;font-weight:700}
    .title p{margin:6px 0 0;color:var(--muted);font-size:14px}

    .btn{
      border:1px solid var(--stroke);
      background:linear-gradient(135deg,rgba(255,255,255,.08) 0%,rgba(255,255,255,.04) 100%);
      color:#fff;padding:10px 18px;border-radius:12px;cursor:pointer;font-weight:500;
      transition:all .2s;display:inline-flex;align-items:center;gap:8px;
      user-select:none;font-size:14px;text-decoration:none;
    }
    .btn:hover{transform:translateY(-2px);background:linear-gradient(135deg,rgba(255,255,255,.12) 0%,rgba(255,255,255,.08) 100%);box-shadow:0 4px 12px rgba(0,0,0,.2)}
    .btn-primary{background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);border-color:transparent;box-shadow:0 2px 12px rgba(34,197,94,.4)}
    .btn-primary:hover{background:linear-gradient(135deg,#4ade80 0%,#22c55e 100%)}
    .btn-sm{padding:6px 12px;font-size:12px;border-radius:8px}
    .btn-danger{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.3);color:#ef4444}
    .btn-danger:hover{background:rgba(239,68,68,.2)}
    .btn-ghost{background:transparent;border-color:var(--stroke)}
    .btn-warning{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.3);color:#f59e0b}

    /* Steps */
    .step-card{
      background:linear-gradient(135deg,rgba(255,255,255,.06) 0%,rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);border-radius:var(--radius);
      margin-bottom:16px;overflow:hidden;
    }
    .step-header{
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 20px;cursor:pointer;gap:12px;
    }
    .step-header:hover{background:rgba(255,255,255,.03)}
    .step-left{display:flex;align-items:center;gap:12px;flex:1;min-width:0}
    .step-drag{color:var(--muted);cursor:grab;font-size:16px}
    .step-drag:active{cursor:grabbing}
    .step-name{font-size:16px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .step-meta{display:flex;gap:10px;font-size:12px;color:var(--muted)}
    .step-meta span{display:flex;align-items:center;gap:4px}
    .step-badges{display:flex;gap:6px;flex-shrink:0}
    .badge{padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600}
    .badge-green{background:rgba(34,197,94,.15);color:#22c55e}
    .badge-red{background:rgba(239,68,68,.15);color:#ef4444}
    .badge-blue{background:rgba(96,165,250,.15);color:#60a5fa}
    .badge-gray{background:rgba(156,163,175,.15);color:var(--muted)}

    .step-body{
      padding:0 20px 20px;border-top:1px solid var(--stroke);
      display:none;
    }
    .step-body.open{display:block}

    /* Settings grid */
    .settings-grid{
      display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
      gap:12px;margin:16px 0;
    }
    .setting-item{
      background:rgba(255,255,255,.04);border:1px solid var(--stroke);
      border-radius:10px;padding:12px;
    }
    .setting-item label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
    .setting-item input[type="text"],
    .setting-item input[type="number"],
    .setting-item select{
      width:100%;background:rgba(0,0,0,.3);border:1px solid var(--stroke);
      color:var(--text);padding:8px 10px;border-radius:8px;font-size:14px;
    }
    .setting-item input:focus,.setting-item select:focus{outline:none;border-color:var(--brand)}

    .toggle{position:relative;display:inline-block;width:44px;height:24px}
    .toggle input{opacity:0;width:0;height:0}
    .toggle .slider{
      position:absolute;cursor:pointer;inset:0;
      background:rgba(255,255,255,.1);border-radius:24px;transition:.3s;
    }
    .toggle .slider:before{
      content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;
      background:#fff;border-radius:50%;transition:.3s;
    }
    .toggle input:checked+.slider{background:#22c55e}
    .toggle input:checked+.slider:before{transform:translateX(20px)}

    /* Options table */
    .options-section{margin-top:16px}
    .options-section h4{margin:0 0 10px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
    .option-row{
      display:flex;align-items:center;gap:10px;padding:8px 12px;
      background:rgba(0,0,0,.2);border-radius:8px;margin-bottom:6px;
    }
    .option-row:hover{background:rgba(0,0,0,.3)}
    .option-drag{color:var(--muted);cursor:grab;font-size:14px}
    .option-name{flex:1;font-size:14px}
    .option-price{
      font-size:13px;color:var(--warning);font-weight:600;
      min-width:60px;text-align:right;
    }
    .option-actions{display:flex;gap:4px}
    .icon-btn{
      background:none;border:none;color:var(--muted);cursor:pointer;
      padding:4px 6px;border-radius:6px;font-size:14px;
    }
    .icon-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    .icon-btn.danger:hover{color:#ef4444}

    .add-option-row{
      display:flex;gap:8px;margin-top:8px;
    }
    .add-option-row input{
      background:rgba(0,0,0,.3);border:1px solid var(--stroke);
      color:var(--text);padding:8px 10px;border-radius:8px;font-size:13px;
    }
    .add-option-row input:focus{outline:none;border-color:var(--brand)}
    .add-option-row .input-name{flex:1}
    .add-option-row .input-price{width:80px}

    /* Modal */
    .modal-overlay{
      display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
      z-index:9999;justify-content:center;align-items:center;
    }
    .modal-overlay.active{display:flex}
    .modal{
      background:var(--panel);border:1px solid var(--stroke);border-radius:var(--radius);
      padding:24px;width:90%;max-width:420px;box-shadow:var(--shadow);
    }
    .modal h3{margin:0 0 16px;font-size:18px}
    .modal .form-group{margin-bottom:12px}
    .modal .form-group label{display:block;font-size:13px;color:var(--muted);margin-bottom:4px}
    .modal .form-group input{
      width:100%;background:rgba(0,0,0,.3);border:1px solid var(--stroke);
      color:var(--text);padding:10px 12px;border-radius:8px;font-size:14px;
    }
    .modal .form-group input:focus{outline:none;border-color:var(--brand)}
    .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}

    /* Toast */
    .toast{
      position:fixed;top:24px;right:24px;background:#fff;color:#000;
      padding:16px 20px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.4);
      z-index:10000;min-width:280px;animation:slideIn .3s ease;
    }
    @keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
    .toast.success{background:#22c55e;color:#fff}
    .toast.error{background:#ef4444;color:#fff}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1>⚙️ <?= e($currentType['name']) ?></h1>
        <p>Configurez les étapes et options de composition</p>
      </div>
      <div style="display:flex;gap:10px">
        <a href="cuisine-types-manager.php" class="btn btn-ghost">
          <i class="fas fa-arrow-left"></i> Retour
        </a>
      </div>
    </div>

    <div id="stepsContainer">
      <div style="text-align:center;padding:40px;color:var(--muted)">
        <i class="fas fa-spinner fa-spin" style="font-size:24px"></i>
        <p>Chargement...</p>
      </div>
    </div>
  </div>

  <!-- Modal édition option -->
  <div class="modal-overlay" id="editOptionModal">
    <div class="modal">
      <h3>Modifier l'option</h3>
      <input type="hidden" id="editOptionId">
      <div class="form-group">
        <label>Nom</label>
        <input type="text" id="editOptionName">
      </div>
      <div class="form-group">
        <label>Prix (+/- €)</label>
        <input type="number" id="editOptionPrice" step="0.01">
      </div>
      <div class="modal-actions">
        <button class="btn btn-ghost btn-sm" onclick="closeEditModal()">Annuler</button>
        <button class="btn btn-primary btn-sm" onclick="saveOptionEdit()">
          <i class="fas fa-check"></i> Enregistrer
        </button>
      </div>
    </div>
  </div>

  <script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const TYPE_ID = <?= $typeId ?>;
    let stepsData = [];

    function toast(type, message) {
      const div = document.createElement('div');
      div.className = `toast ${type}`;
      div.innerHTML = `<strong>${message}</strong>`;
      document.body.appendChild(div);
      setTimeout(() => div.remove(), 3000);
    }

    async function apiCall(data) {
      const response = await fetch('/snackup/admin/api/cuisine-types.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({...data, csrf_token: CSRF_TOKEN})
      });
      return response.json();
    }

    async function loadConfig() {
      try {
        const response = await fetch(`/snackup/admin/api/cuisine-types.php?type_id=${TYPE_ID}`);
        const data = await response.json();
        if (data.success) {
          stepsData = data.steps || [];
          renderSteps();
        }
      } catch (error) {
        console.error('Erreur:', error);
        toast('error', 'Erreur lors du chargement');
      }
    }

    function renderSteps() {
      const container = document.getElementById('stepsContainer');

      if (stepsData.length === 0) {
        container.innerHTML = `
          <div style="text-align:center;padding:60px 20px;background:rgba(255,255,255,.04);border:1px dashed var(--stroke);border-radius:var(--radius)">
            <i class="fas fa-layer-group" style="font-size:48px;color:var(--muted);opacity:.5"></i>
            <h3 style="margin:16px 0 8px">Aucune étape configurée</h3>
            <p style="color:var(--muted)">Ce type n'a pas encore d'étapes</p>
          </div>`;
        return;
      }

      container.innerHTML = stepsData.map((step, index) => {
        const displayName = step.custom_name || step.template_name;
        const isActive = step.is_active == 1;
        const isRequired = step.is_required == 1;
        const options = step.options || [];

        return `
          <div class="step-card" data-step-id="${step.id}" data-index="${index}">
            <div class="step-header" onclick="toggleStep(${index})">
              <div class="step-left">
                <span class="step-drag" title="Glisser pour réorganiser">
                  <i class="fas fa-grip-vertical"></i>
                </span>
                <div>
                  <div class="step-name">${escapeHtml(displayName)}</div>
                  <div class="step-meta">
                    <span><i class="fas fa-list-ul"></i> ${options.length} options</span>
                    <span><i class="fas fa-arrows-alt-h"></i> ${step.min_choices || 0}-${step.max_choices || '∞'}</span>
                  </div>
                </div>
              </div>
              <div class="step-badges">
                ${isRequired ? '<span class="badge badge-blue">Obligatoire</span>' : '<span class="badge badge-gray">Optionnel</span>'}
                ${isActive ? '<span class="badge badge-green">Actif</span>' : '<span class="badge badge-red">Inactif</span>'}
                <i class="fas fa-chevron-down" style="color:var(--muted);margin-left:4px"></i>
              </div>
            </div>
            <div class="step-body" id="stepBody${index}">
              <div class="settings-grid">
                <div class="setting-item">
                  <label>Nom personnalisé</label>
                  <input type="text" value="${escapeHtml(step.custom_name || '')}"
                    placeholder="${escapeHtml(step.template_name)}"
                    onchange="updateStep(${step.id}, 'custom_name', this.value || null)">
                </div>
                <div class="setting-item">
                  <label>Choix minimum</label>
                  <input type="number" min="0" value="${step.min_choices || 0}"
                    onchange="updateStep(${step.id}, 'min_choices', parseInt(this.value))">
                </div>
                <div class="setting-item">
                  <label>Choix maximum</label>
                  <input type="number" min="0" value="${step.max_choices || 0}"
                    onchange="updateStep(${step.id}, 'max_choices', parseInt(this.value))">
                </div>
                <div class="setting-item">
                  <label>Obligatoire</label>
                  <label class="toggle">
                    <input type="checkbox" ${isRequired ? 'checked' : ''}
                      onchange="updateStep(${step.id}, 'is_required', this.checked ? 1 : 0)">
                    <span class="slider"></span>
                  </label>
                </div>
                <div class="setting-item">
                  <label>Actif</label>
                  <label class="toggle">
                    <input type="checkbox" ${isActive ? 'checked' : ''}
                      onchange="updateStep(${step.id}, 'is_active', this.checked ? 1 : 0)">
                    <span class="slider"></span>
                  </label>
                </div>
                <div class="setting-item">
                  <label>Modifie le prix</label>
                  <label class="toggle">
                    <input type="checkbox" ${step.has_price_modifier == 1 ? 'checked' : ''}
                      onchange="updateStep(${step.id}, 'has_price_modifier', this.checked ? 1 : 0)">
                    <span class="slider"></span>
                  </label>
                </div>
              </div>

              <div class="options-section">
                <h4><i class="fas fa-list-ul" style="color:var(--brand)"></i> Options (${options.length})</h4>
                ${options.map(opt => `
                  <div class="option-row" data-option-id="${opt.id}">
                    <span class="option-drag"><i class="fas fa-grip-vertical"></i></span>
                    <span class="option-name">${escapeHtml(opt.name)}</span>
                    <span class="option-price">${parseFloat(opt.price_modifier) !== 0 ? (parseFloat(opt.price_modifier) > 0 ? '+' : '') + parseFloat(opt.price_modifier).toFixed(2) + ' €' : '-'}</span>
                    <div class="option-actions">
                      <button class="icon-btn" onclick="openEditModal(${opt.id}, '${escapeHtml(opt.name)}', ${opt.price_modifier})" title="Modifier">
                        <i class="fas fa-pen"></i>
                      </button>
                      <button class="icon-btn danger" onclick="deleteOption(${opt.id}, '${escapeHtml(opt.name)}')" title="Supprimer">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                `).join('')}
                <div class="add-option-row">
                  <input type="text" class="input-name" placeholder="Nouvelle option..." id="newOptName${step.id}">
                  <input type="number" class="input-price" placeholder="Prix" step="0.01" value="0" id="newOptPrice${step.id}">
                  <button class="btn btn-primary btn-sm" onclick="addOption(${step.id})">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>`;
      }).join('');
    }

    function escapeHtml(str) {
      if (!str) return '';
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function toggleStep(index) {
      const body = document.getElementById('stepBody' + index);
      body.classList.toggle('open');
    }

    async function updateStep(stepId, field, value) {
      try {
        const data = await apiCall({action: 'update_step', step_id: stepId, [field]: value});
        if (data.success) {
          toast('success', 'Étape mise à jour');
          await loadConfig();
        } else {
          toast('error', data.error || 'Erreur');
        }
      } catch (e) {
        toast('error', 'Erreur réseau');
      }
    }

    async function addOption(stepId) {
      const nameInput = document.getElementById('newOptName' + stepId);
      const priceInput = document.getElementById('newOptPrice' + stepId);
      const name = nameInput.value.trim();
      if (!name) { nameInput.focus(); return; }

      try {
        const data = await apiCall({
          action: 'create_option',
          step_id: stepId,
          name: name,
          price_modifier: parseFloat(priceInput.value) || 0
        });
        if (data.success) {
          toast('success', 'Option ajoutée');
          nameInput.value = '';
          priceInput.value = '0';
          await loadConfig();
        } else {
          toast('error', data.error || 'Erreur');
        }
      } catch (e) {
        toast('error', 'Erreur réseau');
      }
    }

    function openEditModal(optionId, name, price) {
      document.getElementById('editOptionId').value = optionId;
      document.getElementById('editOptionName').value = name;
      document.getElementById('editOptionPrice').value = price;
      document.getElementById('editOptionModal').classList.add('active');
    }

    function closeEditModal() {
      document.getElementById('editOptionModal').classList.remove('active');
    }

    async function saveOptionEdit() {
      const optionId = parseInt(document.getElementById('editOptionId').value);
      const name = document.getElementById('editOptionName').value.trim();
      const price = parseFloat(document.getElementById('editOptionPrice').value) || 0;

      if (!name) return;

      try {
        const data = await apiCall({
          action: 'update_option',
          option_id: optionId,
          name: name,
          price_modifier: price
        });
        if (data.success) {
          toast('success', 'Option modifiée');
          closeEditModal();
          await loadConfig();
        } else {
          toast('error', data.error || 'Erreur');
        }
      } catch (e) {
        toast('error', 'Erreur réseau');
      }
    }

    async function deleteOption(optionId, name) {
      if (!confirm(`Supprimer l'option "${name}" ?`)) return;

      try {
        const data = await apiCall({action: 'delete_option', option_id: optionId});
        if (data.success) {
          toast('success', 'Option supprimée');
          await loadConfig();
        } else {
          toast('error', data.error || 'Erreur');
        }
      } catch (e) {
        toast('error', 'Erreur réseau');
      }
    }

    // Fermer modal en cliquant en dehors
    document.getElementById('editOptionModal').addEventListener('click', function(e) {
      if (e.target === this) closeEditModal();
    });

    // Chargement initial
    loadConfig();
  </script>
</body>
</html>
