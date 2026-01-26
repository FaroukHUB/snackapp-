<?php
// promo-manager.php
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Codes Promo</title>
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
      --shadow: 0 14px 50px rgba(0,0,0,.45);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: radial-gradient(1200px 800px at 10% 0%, #122044 0%, var(--bg) 60%);
      color:var(--text);
    }

    .wrap{max-width:1200px;margin:0 auto;padding:24px 16px 64px}
    .topbar{
      display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;
      margin-bottom:18px;
    }
    .title h1{margin:0;font-size:22px;letter-spacing:.2px}
    .title p{margin:6px 0 0;color:var(--muted);font-size:12px}
    .actions{display:flex;gap:10px;flex-wrap:wrap}

    .btn{
      border:1px solid var(--stroke);
      background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.04) 100%);
      color:#fff;
      padding:10px 16px;
      border-radius:12px;
      cursor:pointer;
      font-weight:500;
      transition: all .2s ease;
      display:inline-flex;align-items:center;gap:8px;
      user-select:none;
      text-decoration:none;
    }
    .btn:hover{transform: translateY(-2px);background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, rgba(255,255,255,.08) 100%);box-shadow: 0 4px 12px rgba(0,0,0,.2)}
    .btn:active{transform: translateY(0)}
    .btn-primary{
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      border-color: transparent;
      box-shadow: 0 2px 12px rgba(34,197,94,.4);
      color: #fff;
      font-weight: 600;
    }
    .btn-primary:hover{background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);box-shadow: 0 4px 18px rgba(34,197,94,.5)}
    .btn-danger{background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.32)}
    .btn-danger:hover{background: rgba(239,68,68,.18)}

    .panel{
      background: linear-gradient(180deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius: var(--radius);
      backdrop-filter:blur(20px);
      box-shadow:var(--shadow);
      padding:20px;
      margin-bottom:16px;
    }

    .stats{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
      gap:12px;
      margin-bottom:24px;
    }
    .stat-card{
      background:var(--card);
      border:1px solid var(--stroke);
      border-radius:12px;
      padding:14px 16px;
    }
    .stat-label{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
    .stat-value{font-size:24px;font-weight:700;color:#fff}

    table{width:100%;border-collapse:collapse;font-size:13px}
    thead{background:var(--panel);position:sticky;top:0}
    th{padding:12px 15px;text-align:left;font-weight:600;color:var(--muted);white-space:nowrap;font-size:11px;text-transform:uppercase;letter-spacing:.5px}
    td{padding:12px 15px;border-bottom:1px solid var(--stroke)}
    tr:hover{background:rgba(255,255,255,.02)}

    .badge{
      display:inline-block;
      padding:3px 10px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:.3px;
    }
    .badge-success{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
    .badge-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
    .badge-warning{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
    .badge-info{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}

    /* Modal */
    .modal{
      display:none;
      position:fixed;
      top:0;left:0;right:0;bottom:0;
      background:rgba(0,0,0,.7);
      backdrop-filter:blur(8px);
      z-index:999;
      align-items:center;
      justify-content:center;
    }
    .modal.active{display:flex}
    .modal-content{
      background:var(--panel);
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      max-width:600px;
      width:90%;
      max-height:90vh;
      overflow-y:auto;
      padding:24px;
      box-shadow:0 25px 60px rgba(0,0,0,.5);
    }
    .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .modal-header h2{margin:0;font-size:18px}
    .close-btn{background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:24px;padding:0;line-height:1}
    .close-btn:hover{color:var(--text)}

    .form-group{margin-bottom:18px}
    .form-group label{display:block;margin-bottom:6px;color:var(--text);font-weight:500;font-size:13px}
    .form-group input,
    .form-group select,
    .form-group textarea{
      width:100%;
      padding:10px 12px;
      background:rgba(255,255,255,.05);
      border:1px solid var(--stroke);
      border-radius:8px;
      color:var(--text);
      font-size:14px;
      font-family:inherit;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus{
      outline:none;
      border-color:var(--brand);
      background:rgba(255,255,255,.08);
    }
    .form-group small{display:block;margin-top:4px;color:var(--muted);font-size:11px}

    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media (max-width:600px){.form-row{grid-template-columns:1fr}}

    .checkbox-group{display:flex;align-items:center;gap:8px}
    .checkbox-group input{width:auto}

    .empty-state{
      text-align:center;
      padding:48px 24px;
      color:var(--muted);
    }
    .empty-state i{font-size:48px;margin-bottom:16px;opacity:.3}
    .empty-state p{margin:8px 0 0;font-size:14px}

    .btn-group{display:flex;gap:8px;justify-content:flex-end}
  </style>
</head>
<body>

<div class="wrap">
  <div class="topbar">
    <div class="title">
      <h1><i class="fas fa-tags"></i> Gestion des Codes Promo</h1>
      <p>Créez et gérez vos codes de réduction</p>
    </div>
    <div class="actions">
      <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Retour</a>
      <button onclick="openAddModal()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouveau code promo
      </button>
    </div>
  </div>

  <!-- Statistiques -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">Total Codes</div>
      <div class="stat-value" id="statTotal">-</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Codes Actifs</div>
      <div class="stat-value" id="statActive">-</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Utilisations Totales</div>
      <div class="stat-value" id="statUses">-</div>
    </div>
  </div>

  <!-- Liste des codes promo -->
  <div class="panel">
    <div id="promoList">
      <div class="empty-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Chargement...</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajout/Édition -->
<div class="modal" id="promoModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modalTitle">Nouveau code promo</h2>
      <button class="close-btn" onclick="closeModal()">&times;</button>
    </div>
    <form id="promoForm">
      <input type="hidden" id="promoId" name="id">

      <div class="form-group">
        <label>Code <span style="color:var(--bad)">*</span></label>
        <input type="text" id="promoCode" name="code" required placeholder="Ex: BIENVENUE10" style="text-transform:uppercase">
        <small>Le code sera automatiquement converti en majuscules</small>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea id="promoDescription" name="description" rows="2" placeholder="Ex: -10% pour les nouveaux clients"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Type de réduction <span style="color:var(--bad)">*</span></label>
          <select id="promoType" name="discount_type" required>
            <option value="percent">Pourcentage (%)</option>
            <option value="fixed">Montant fixe (DA)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Valeur <span style="color:var(--bad)">*</span></label>
          <input type="number" id="promoValue" name="discount_value" required min="0" step="0.01" placeholder="Ex: 10">
          <small id="valueHint">En pourcentage (ex: 10 pour -10%)</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Commande minimum (DA)</label>
          <input type="number" id="promoMinOrder" name="min_order_amount" min="0" step="0.01" placeholder="Ex: 1000">
          <small>Laisser vide si pas de minimum</small>
        </div>
        <div class="form-group">
          <label>Utilisations max</label>
          <input type="number" id="promoMaxUses" name="max_uses" min="1" step="1" placeholder="Ex: 100">
          <small>Laisser vide pour illimité</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Date de début</label>
          <input type="datetime-local" id="promoStartsAt" name="starts_at">
        </div>
        <div class="form-group">
          <label>Date d'expiration</label>
          <input type="datetime-local" id="promoExpiresAt" name="expires_at">
        </div>
      </div>

      <div class="form-group">
        <div class="checkbox-group">
          <input type="checkbox" id="promoActive" name="is_active" checked>
          <label for="promoActive" style="margin:0">Code actif</label>
        </div>
      </div>

      <div class="btn-group">
        <button type="button" onclick="closeModal()" class="btn">Annuler</button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let currentEditId = null;

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
  loadPromoCodes();
  loadStats();

  // Changer le hint selon le type
  document.getElementById('promoType').addEventListener('change', function() {
    const hint = document.getElementById('valueHint');
    if (this.value === 'percent') {
      hint.textContent = 'En pourcentage (ex: 10 pour -10%)';
    } else {
      hint.textContent = 'Montant en DA (ex: 500 pour -500 DA)';
    }
  });
});

// Charger les codes promo
async function loadPromoCodes() {
  try {
    const res = await fetch('api/promo-codes.php?action=list');
    const data = await res.json();

    if (!data.success) {
      throw new Error(data.message);
    }

    const promoCodes = data.promo_codes || [];
    renderPromoCodes(promoCodes);
  } catch (err) {
    showError('Erreur de chargement: ' + err.message);
  }
}

// Afficher les codes promo
function renderPromoCodes(promoCodes) {
  const container = document.getElementById('promoList');

  if (promoCodes.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-tags"></i>
        <p>Aucun code promo créé</p>
      </div>
    `;
    return;
  }

  let html = `
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Réduction</th>
          <th>Utilisations</th>
          <th>Expiration</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
  `;

  promoCodes.forEach(promo => {
    const discount = promo.discount_type === 'percent'
      ? `-${promo.discount_value}%`
      : `-${promo.discount_value} DA`;

    const uses = promo.max_uses
      ? `${promo.current_uses}/${promo.max_uses}`
      : promo.current_uses;

    const now = new Date();
    const expires = promo.expires_at ? new Date(promo.expires_at) : null;
    const starts = promo.starts_at ? new Date(promo.starts_at) : null;

    let statusBadge = '';
    if (!promo.is_active) {
      statusBadge = '<span class="badge badge-danger">Inactif</span>';
    } else if (starts && starts > now) {
      statusBadge = '<span class="badge badge-warning">À venir</span>';
    } else if (expires && expires < now) {
      statusBadge = '<span class="badge badge-danger">Expiré</span>';
    } else if (promo.max_uses && promo.current_uses >= promo.max_uses) {
      statusBadge = '<span class="badge badge-warning">Épuisé</span>';
    } else {
      statusBadge = '<span class="badge badge-success">Actif</span>';
    }

    const expiresText = expires ? expires.toLocaleDateString('fr-FR') : 'Illimité';

    html += `
      <tr>
        <td><strong>${escapeHtml(promo.code)}</strong><br>
            <small style="color:var(--muted)">${escapeHtml(promo.description || '')}</small></td>
        <td>${discount}</td>
        <td>${uses}</td>
        <td>${expiresText}</td>
        <td>${statusBadge}</td>
        <td>
          <button onclick="editPromo(${promo.id})" class="btn" style="padding:6px 12px;font-size:12px">
            <i class="fas fa-edit"></i>
          </button>
          <button onclick="deletePromo(${promo.id}, '${escapeHtml(promo.code)}')" class="btn btn-danger" style="padding:6px 12px;font-size:12px">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  });

  html += `
      </tbody>
    </table>
  `;

  container.innerHTML = html;
}

// Charger les statistiques
async function loadStats() {
  try {
    const res = await fetch('api/promo-codes.php?action=stats');
    const data = await res.json();

    if (data.success && data.stats) {
      document.getElementById('statTotal').textContent = data.stats.total;
      document.getElementById('statActive').textContent = data.stats.active;
      document.getElementById('statUses').textContent = data.stats.total_uses;
    }
  } catch (err) {
    console.error('Erreur stats:', err);
  }
}

// Ouvrir modal ajout
function openAddModal() {
  currentEditId = null;
  document.getElementById('modalTitle').textContent = 'Nouveau code promo';
  document.getElementById('promoForm').reset();
  document.getElementById('promoId').value = '';
  document.getElementById('promoActive').checked = true;
  document.getElementById('promoModal').classList.add('active');
}

// Éditer un code promo
async function editPromo(id) {
  try {
    const res = await fetch('api/promo-codes.php?action=list');
    const data = await res.json();
    const promo = data.promo_codes.find(p => p.id == id);

    if (!promo) {
      throw new Error('Code promo non trouvé');
    }

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Modifier le code promo';
    document.getElementById('promoId').value = promo.id;
    document.getElementById('promoCode').value = promo.code;
    document.getElementById('promoDescription').value = promo.description || '';
    document.getElementById('promoType').value = promo.discount_type;
    document.getElementById('promoValue').value = promo.discount_value;
    document.getElementById('promoMinOrder').value = promo.min_order_amount || '';
    document.getElementById('promoMaxUses').value = promo.max_uses || '';

    if (promo.starts_at) {
      document.getElementById('promoStartsAt').value = promo.starts_at.replace(' ', 'T').substring(0, 16);
    }
    if (promo.expires_at) {
      document.getElementById('promoExpiresAt').value = promo.expires_at.replace(' ', 'T').substring(0, 16);
    }

    document.getElementById('promoActive').checked = promo.is_active == 1;
    document.getElementById('promoModal').classList.add('active');
  } catch (err) {
    showError(err.message);
  }
}

// Supprimer un code promo
async function deletePromo(id, code) {
  if (!confirm(`Voulez-vous vraiment supprimer le code "${code}" ?`)) {
    return;
  }

  try {
    const res = await fetch('api/promo-codes.php?action=delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id })
    });

    const data = await res.json();

    if (!data.success) {
      throw new Error(data.message);
    }

    showSuccess('Code promo supprimé');
    loadPromoCodes();
    loadStats();
  } catch (err) {
    showError(err.message);
  }
}

// Soumettre le formulaire
document.getElementById('promoForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);
  const data = {
    code: formData.get('code'),
    description: formData.get('description'),
    discount_type: formData.get('discount_type'),
    discount_value: formData.get('discount_value'),
    min_order_amount: formData.get('min_order_amount') || null,
    max_uses: formData.get('max_uses') || null,
    starts_at: formData.get('starts_at') || null,
    expires_at: formData.get('expires_at') || null,
    is_active: formData.get('is_active') === 'on'
  };

  if (currentEditId) {
    data.id = currentEditId;
  }

  try {
    const action = currentEditId ? 'update' : 'create';
    const res = await fetch(`api/promo-codes.php?action=${action}`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });

    const result = await res.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    showSuccess(currentEditId ? 'Code promo modifié' : 'Code promo créé');
    closeModal();
    loadPromoCodes();
    loadStats();
  } catch (err) {
    showError(err.message);
  }
});

// Fermer modal
function closeModal() {
  document.getElementById('promoModal').classList.remove('active');
}

// Utilitaires
function showSuccess(message) {
  alert('✅ ' + message);
}

function showError(message) {
  alert('❌ ' + message);
}

function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
</script>

</body>
</html>
