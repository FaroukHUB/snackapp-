<?php
// livreurs-manager.php - Gestion des livreurs WhatsApp
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Gestion Livreurs</title>
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
    .btn-sm{padding:6px 12px;font-size:12px}

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
    .form-group select{
      width:100%;
      padding:10px 14px;
      background:rgba(255,255,255,.04);
      border:1px solid var(--stroke);
      border-radius:10px;
      color:var(--text);
      font-size:14px;
    }
    .form-group input:focus,
    .form-group select:focus{
      outline:none;
      border-color:var(--brand);
      background:rgba(255,255,255,.06);
    }

    .toast{
      position:fixed;
      top:20px;
      right:20px;
      padding:14px 18px;
      background:var(--panel);
      border:1px solid var(--stroke);
      border-radius:12px;
      box-shadow:0 10px 40px rgba(0,0,0,.5);
      z-index:9999;
      display:flex;
      align-items:center;
      gap:10px;
      animation: slideIn .3s ease;
    }
    .toast.success{border-left:3px solid var(--good)}
    .toast.error{border-left:3px solid var(--bad)}
    @keyframes slideIn{
      from{transform:translateX(400px);opacity:0}
      to{transform:translateX(0);opacity:1}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1><i class="fas fa-motorcycle"></i> Gestion des Livreurs</h1>
        <p>Gérez vos livreurs WhatsApp pour les livraisons</p>
      </div>
      <div class="actions">
        <a href="index.php#settings" class="btn"><i class="fas fa-arrow-left"></i> Retour</a>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Ajouter un livreur</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-label">Total livreurs</div>
        <div class="stat-value" id="statTotal">0</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Actifs</div>
        <div class="stat-value" id="statActifs" style="color:#4ade80">0</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Inactifs</div>
        <div class="stat-value" id="statInactifs" style="color:#f87171">0</div>
      </div>
    </div>

    <!-- Liste -->
    <div class="panel">
      <table>
        <thead>
          <tr>
            <th>Prénom</th>
            <th>Téléphone</th>
            <th>WhatsApp</th>
            <th>Statut</th>
            <th>Ajouté le</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody id="livreursTable">
          <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">
              <i class="fas fa-spinner fa-spin"></i> Chargement...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Add/Edit -->
  <div class="modal" id="livreurModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Ajouter un livreur</h2>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form id="livreurForm" onsubmit="saveLivreur(event)">
        <input type="hidden" id="livreurId">

        <div class="form-group">
          <label>Prénom *</label>
          <input type="text" id="prenom" required placeholder="Ex: Ahmed">
        </div>

        <div class="form-group">
          <label>Indicatif téléphone *</label>
          <select id="indicatif" required>
            <?= getPhoneIndicatorsOptions(DEFAULT_PHONE_INDICATOR) ?>
          </select>
        </div>

        <div class="form-group">
          <label>Numéro de téléphone *</label>
          <input type="tel" id="numero" required placeholder="Ex: 612345678">
          <small style="color:var(--muted);font-size:11px">Sans l'indicatif, uniquement les chiffres</small>
        </div>

        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="actif" checked>
            <span>Livreur actif</span>
          </label>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
          <button type="button" class="btn" onclick="closeModal()">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const API = 'api/livreurs.php';
    let livreurs = [];

    // Charger les livreurs
    async function loadLivreurs() {
      try {
        const res = await fetch(`${API}?action=list`);
        const data = await res.json();

        if (data.success && data.livreurs) {
          livreurs = Object.values(data.livreurs);
          renderLivreurs();
          updateStats();
        }
      } catch (err) {
        console.error('Erreur chargement:', err);
        showToast('Erreur de chargement', 'error');
      }
    }

    // Afficher la liste
    function renderLivreurs() {
      const tbody = document.getElementById('livreursTable');

      if (livreurs.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">
              <i class="fas fa-motorcycle" style="font-size:48px;opacity:.3;display:block;margin-bottom:12px"></i>
              Aucun livreur ajouté
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = livreurs.map(l => {
        const statusBadge = l.actif
          ? '<span class="badge badge-success">Actif</span>'
          : '<span class="badge badge-danger">Inactif</span>';

        const date = new Date(l.created_at).toLocaleDateString('fr-FR');

        return `
          <tr>
            <td><strong>${escapeHtml(l.prenom)}</strong></td>
            <td>${escapeHtml(l.indicatif)} ${escapeHtml(l.numero)}</td>
            <td>
              <a href="https://wa.me/${l.whatsapp}" target="_blank" style="color:var(--brand);text-decoration:none">
                <i class="fab fa-whatsapp"></i> ${escapeHtml(l.whatsapp)}
              </a>
            </td>
            <td>${statusBadge}</td>
            <td style="color:var(--muted);font-size:12px">${date}</td>
            <td style="text-align:right">
              <button class="btn btn-sm" onclick="editLivreur('${l.id}')">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteLivreur('${l.id}')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Stats
    function updateStats() {
      const total = livreurs.length;
      const actifs = livreurs.filter(l => l.actif).length;

      document.getElementById('statTotal').textContent = total;
      document.getElementById('statActifs').textContent = actifs;
      document.getElementById('statInactifs').textContent = total - actifs;
    }

    // Modal
    function openAddModal() {
      document.getElementById('modalTitle').textContent = 'Ajouter un livreur';
      document.getElementById('livreurForm').reset();
      document.getElementById('livreurId').value = '';
      document.getElementById('actif').checked = true;
      document.getElementById('livreurModal').classList.add('active');
    }

    function editLivreur(id) {
      const livreur = livreurs.find(l => l.id === id);
      if (!livreur) return;

      document.getElementById('modalTitle').textContent = 'Modifier le livreur';
      document.getElementById('livreurId').value = livreur.id;
      document.getElementById('prenom').value = livreur.prenom;
      document.getElementById('indicatif').value = livreur.indicatif;
      document.getElementById('numero').value = livreur.numero;
      document.getElementById('actif').checked = livreur.actif;
      document.getElementById('livreurModal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('livreurModal').classList.remove('active');
    }

    // Sauvegarder
    async function saveLivreur(e) {
      e.preventDefault();

      const id = document.getElementById('livreurId').value;
      const action = id ? 'edit' : 'add';

      const formData = new FormData();
      formData.append('action', action);
      formData.append('prenom', document.getElementById('prenom').value.trim());
      formData.append('indicatif', document.getElementById('indicatif').value);
      formData.append('numero', document.getElementById('numero').value.trim());
      formData.append('actif', document.getElementById('actif').checked);
      formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

      if (id) {
        formData.append('id', id);
      }

      try {
        const res = await fetch(API, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          showToast(action === 'add' ? 'Livreur ajouté' : 'Livreur modifié', 'success');
          closeModal();
          loadLivreurs();
        } else {
          showToast(data.error || 'Erreur', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('Erreur réseau', 'error');
      }
    }

    // Supprimer
    async function deleteLivreur(id) {
      if (!confirm('Supprimer ce livreur ?')) return;

      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', id);
      formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

      try {
        const res = await fetch(API, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          showToast('Livreur supprimé', 'success');
          loadLivreurs();
        } else {
          showToast(data.error || 'Erreur', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('Erreur réseau', 'error');
      }
    }

    // Toast
    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${escapeHtml(message)}</span>
      `;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    }

    // XSS protection
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Init
    loadLivreurs();
  </script>
</body>
</html>
