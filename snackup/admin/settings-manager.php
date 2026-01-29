<?php
/**
 * Settings Manager - Paramètres généraux, Livraison par VILLE, Paiement
 * Source de vérité: MySQL
 */
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin - Paramètres</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

    .wrap{max-width:1000px;margin:0 auto;padding:24px 16px 64px}
    .topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:24px}
    .title h1{margin:0;font-size:22px;letter-spacing:.2px}
    .title p{margin:6px 0 0;color:var(--muted);font-size:12px}

    .btn{
      border:1px solid var(--stroke);
      background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.04) 100%);
      color:#fff;padding:10px 16px;border-radius:12px;cursor:pointer;font-weight:500;
      transition: all .2s ease;display:inline-flex;align-items:center;gap:8px;
      user-select:none;text-decoration:none;font-size:13px;
    }
    .btn:hover{transform: translateY(-2px);background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, rgba(255,255,255,.08) 100%)}
    .btn-primary{background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);border-color: transparent;box-shadow: 0 2px 12px rgba(34,197,94,.4)}
    .btn-danger{background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.32);color:#f87171}
    .btn-sm{padding:6px 12px;font-size:12px}
    .btn-ghost{background:transparent;border-color:var(--stroke)}

    .tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid var(--stroke);padding-bottom:0}
    .tab{padding:12px 20px;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-weight:500;font-size:13px;transition:all .2s}
    .tab:hover{color:var(--text)}
    .tab.active{color:var(--brand);border-bottom-color:var(--brand)}

    .panel{background: linear-gradient(180deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);border:1px solid var(--stroke);border-radius: var(--radius);padding:24px;margin-bottom:20px;display:none}
    .panel.active{display:block}
    .panel-title{font-size:16px;font-weight:600;margin:0 0 20px;display:flex;align-items:center;gap:10px}
    .panel-title i{color:var(--brand)}

    .form-row{display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:16px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;margin-bottom:6px;color:var(--text);font-weight:500;font-size:13px}
    .form-group .hint{color:var(--muted);font-size:11px;margin-top:4px}
    .form-group input,.form-group select{width:100%;background:rgba(0,0,0,.3);border:1px solid var(--stroke);border-radius:10px;color:#fff;padding:10px 14px;font-size:14px}
    .form-group input:focus,.form-group select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(96,165,250,.15)}

    .toggle-group{display:flex;align-items:center;gap:12px}
    .toggle{position:relative;width:48px;height:26px;background:rgba(255,255,255,.1);border-radius:13px;cursor:pointer;transition:background .2s}
    .toggle.active{background:var(--good)}
    .toggle::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .2s}
    .toggle.active::after{transform:translateX(22px)}
    .toggle-label{font-size:14px}

    /* Villes de livraison */
    .city-list{display:flex;flex-direction:column;gap:12px;margin-bottom:20px}
    .city-card{
      background:var(--card);border:1px solid var(--stroke);border-radius:12px;padding:16px;
      display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;
    }
    .city-info h4{margin:0 0 6px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
    .city-info p{margin:0;color:var(--muted);font-size:12px}
    .city-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600}
    .city-badge.active{background:rgba(34,197,94,.15);color:#4ade80}
    .city-badge.inactive{background:rgba(239,68,68,.15);color:#f87171}
    .city-badge.home{background:rgba(96,165,250,.15);color:#60a5fa}
    .city-fee{font-size:18px;font-weight:700;color:#4ade80}
    .city-fee.paid{color:var(--warning)}
    .city-actions{display:flex;gap:8px}

    /* Payment methods */
    .payment-method{display:flex;align-items:center;justify-content:space-between;padding:16px;background:var(--card);border:1px solid var(--stroke);border-radius:12px;margin-bottom:12px}
    .payment-method-info{display:flex;align-items:center;gap:14px}
    .payment-method-icon{width:44px;height:44px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
    .payment-method h4{margin:0 0 4px;font-size:14px}
    .payment-method p{margin:0;color:var(--muted);font-size:12px}

    /* Modal */
    .modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.8);z-index:999;align-items:center;justify-content:center}
    .modal.active{display:flex}
    .modal-content{background:var(--panel);border:1px solid var(--stroke);border-radius:var(--radius);max-width:500px;width:90%;padding:24px;box-shadow:0 25px 60px rgba(0,0,0,.5)}
    .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .modal-header h3{margin:0;font-size:16px}
    .close-btn{background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:20px}

    /* Toast */
    .toast{position:fixed;bottom:24px;right:24px;background:var(--panel);border:1px solid var(--stroke);border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:12px;box-shadow:0 10px 40px rgba(0,0,0,.4);transform:translateY(100px);opacity:0;transition:all .3s ease;z-index:9999}
    .toast.show{transform:translateY(0);opacity:1}
    .toast.success{border-left:4px solid var(--good)}
    .toast.error{border-left:4px solid var(--bad)}

    @media(max-width:600px){
      .form-row{grid-template-columns:1fr}
      .city-card{grid-template-columns:1fr}
      .city-actions{margin-top:12px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1><i class="fas fa-cog"></i> Paramètres</h1>
        <p>Configuration générale, livraison et paiements</p>
      </div>
      <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <div class="tabs">
      <div class="tab active" data-tab="general"><i class="fas fa-sliders-h"></i> Général</div>
      <div class="tab" data-tab="delivery"><i class="fas fa-truck"></i> Livraison</div>
      <div class="tab" data-tab="payment"><i class="fas fa-credit-card"></i> Paiements</div>
    </div>

    <!-- Panel: Général -->
    <div class="panel active" id="panel-general">
      <h3 class="panel-title"><i class="fas fa-globe"></i> Paramètres régionaux</h3>
      <form id="formGeneral">
        <div class="form-row">
          <div class="form-group">
            <label>Code devise</label>
            <input type="text" name="currency_code" id="currencyCode" placeholder="EUR" maxlength="5">
            <div class="hint">Ex: EUR, DA, USD</div>
          </div>
          <div class="form-group">
            <label>Symbole devise</label>
            <input type="text" name="currency_symbol" id="currencySymbol" placeholder="€" maxlength="5">
          </div>
          <div class="form-group">
            <label>Position symbole</label>
            <select name="currency_position" id="currencyPosition">
              <option value="after">Après (10 €)</option>
              <option value="before">Avant (€ 10)</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Indicatif pays</label>
            <input type="text" name="country_code" id="countryCode" placeholder="+33" maxlength="5">
          </div>
          <div class="form-group">
            <label>Nom du pays</label>
            <input type="text" name="country_name" id="countryName" placeholder="France">
          </div>
        </div>

        <h3 class="panel-title" style="margin-top:32px"><i class="fas fa-shopping-cart"></i> Commandes</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Montant minimum commande (€)</label>
            <input type="number" name="min_order_amount" id="minOrderAmount" step="0.01" min="0" placeholder="0">
          </div>
          <div class="form-group">
            <label>Livraison gratuite à partir de (€)</label>
            <input type="number" name="free_delivery_threshold" id="freeDeliveryThreshold" step="0.01" min="0">
            <div class="hint">Laisser vide = pas de seuil gratuit</div>
          </div>
        </div>
        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="toggleDelivery" data-field="delivery_enabled"></div>
            <span class="toggle-label">Livraison activée</span>
          </div>
        </div>
        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="togglePickup" data-field="pickup_enabled"></div>
            <span class="toggle-label">Click & Collect activé</span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:20px"><i class="fas fa-save"></i> Enregistrer</button>
      </form>
    </div>

    <!-- Panel: Livraison par ville -->
    <div class="panel" id="panel-delivery">
      <h3 class="panel-title"><i class="fas fa-city"></i> Villes de livraison</h3>
      <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
        Définissez les villes où vous livrez et les frais associés. La ville du restaurant apparaît en premier.
      </p>
      <div class="city-list" id="cityList"></div>
      <button class="btn btn-primary" id="btnAddCity"><i class="fas fa-plus"></i> Ajouter une ville</button>
    </div>

    <!-- Panel: Paiements -->
    <div class="panel" id="panel-payment">
      <h3 class="panel-title"><i class="fas fa-wallet"></i> Méthodes de paiement</h3>
      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon"><i class="fas fa-money-bill-wave"></i></div>
          <div><h4>Espèces</h4><p>Paiement en espèces à la livraison/retrait</p></div>
        </div>
        <div class="toggle" id="toggleCash" data-field="cash_enabled"></div>
      </div>
      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon"><i class="fas fa-credit-card"></i></div>
          <div><h4>CB à la livraison</h4><p>Paiement par carte avec TPE mobile</p></div>
        </div>
        <div class="toggle" id="toggleCardDelivery" data-field="card_on_delivery_enabled"></div>
      </div>
      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon" style="color:#635bff"><i class="fab fa-stripe-s"></i></div>
          <div><h4>Paiement en ligne (Stripe)</h4><p>Paiement sécurisé par carte bancaire</p></div>
        </div>
        <div class="toggle" id="toggleOnline" data-field="online_payment_enabled"></div>
      </div>
      <div id="stripeConfig" style="margin-top:24px;display:none">
        <h3 class="panel-title"><i class="fab fa-stripe"></i> Configuration Stripe</h3>
        <div class="form-group">
          <label>Mode</label>
          <select name="stripe_mode" id="stripeMode">
            <option value="test">Test (développement)</option>
            <option value="live">Live (production)</option>
          </select>
        </div>
        <div id="stripeTestKeys">
          <div class="form-row">
            <div class="form-group"><label>Clé publique (Test)</label><input type="text" id="stripePublicTest" placeholder="pk_test_..."></div>
            <div class="form-group"><label>Clé secrète (Test)</label><input type="password" id="stripeSecretTest" placeholder="sk_test_..."></div>
          </div>
        </div>
        <div id="stripeLiveKeys" style="display:none">
          <div class="form-row">
            <div class="form-group"><label>Clé publique (Live)</label><input type="text" id="stripePublicLive" placeholder="pk_live_..."></div>
            <div class="form-group"><label>Clé secrète (Live)</label><input type="password" id="stripeSecretLive" placeholder="sk_live_..."></div>
          </div>
        </div>
        <button type="button" class="btn btn-primary" id="btnSaveStripe"><i class="fas fa-save"></i> Enregistrer Stripe</button>
      </div>
    </div>
  </div>

  <!-- Modal Ville -->
  <div class="modal" id="modalCity">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalCityTitle">Ajouter une ville</h3>
        <button class="close-btn" onclick="closeModal('modalCity')">&times;</button>
      </div>
      <form id="formCity">
        <input type="hidden" name="id" id="cityId">
        <div class="form-group">
          <label>Nom de la ville *</label>
          <input type="text" name="city_name" id="cityName" placeholder="Ex: Roubaix" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Code postal</label>
            <input type="text" name="postal_code" id="cityPostalCode" placeholder="59100">
          </div>
          <div class="form-group">
            <label>Frais de livraison (€) *</label>
            <input type="number" name="delivery_fee" id="cityFee" step="0.01" min="0" value="0" required>
            <div class="hint">0 = gratuit</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Temps estimé (min)</label>
            <input type="number" name="estimated_time_min" id="cityTime" min="1" value="30">
          </div>
          <div class="form-group">
            <label>Montant min. (€)</label>
            <input type="number" name="min_order_amount" id="cityMinOrder" step="0.01" min="0" placeholder="Global">
          </div>
        </div>
        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle active" id="toggleCityActive"></div>
            <span class="toggle-label">Livraison active dans cette ville</span>
          </div>
        </div>
        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="toggleCityHome"></div>
            <span class="toggle-label">Ville du restaurant (affichée en premier)</span>
          </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalCity')">Annuler</button>
        </div>
      </form>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const API = 'api/settings.php';
    let settings = {}, paymentSettings = {}, deliveryCities = [];

    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function toast(msg, type = 'success') {
      const t = $('#toast');
      t.className = 'toast ' + type;
      t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }

    async function api(action, data = {}) {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...data })
      });
      return res.json();
    }

    function openModal(id) { $('#' + id).classList.add('active'); }
    function closeModal(id) { $('#' + id).classList.remove('active'); }

    // Tabs
    $$('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        $$('.tab').forEach(t => t.classList.remove('active'));
        $$('.panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        $('#panel-' + tab.dataset.tab).classList.add('active');
      });
    });

    // Toggles
    function setupToggle(el, value) {
      if (value) el.classList.add('active');
      else el.classList.remove('active');
      el.onclick = () => el.classList.toggle('active');
    }

    // Load settings
    async function loadSettings() {
      const res = await api('get_admin');
      if (!res.success) return toast('Erreur chargement', 'error');

      settings = res.settings;
      paymentSettings = settings.payment || {};
      deliveryCities = settings.delivery_cities || [];

      $('#currencyCode').value = settings.currency_code || 'EUR';
      $('#currencySymbol').value = settings.currency_symbol || '€';
      $('#currencyPosition').value = settings.currency_position || 'after';
      $('#countryCode').value = settings.country_code || '+33';
      $('#countryName').value = settings.country_name || 'France';
      $('#minOrderAmount').value = settings.min_order_amount || 0;
      $('#freeDeliveryThreshold').value = settings.free_delivery_threshold || '';

      setupToggle($('#toggleDelivery'), settings.delivery_enabled == 1);
      setupToggle($('#togglePickup'), settings.pickup_enabled == 1);
      setupToggle($('#toggleCash'), paymentSettings.cash_enabled == 1);
      setupToggle($('#toggleCardDelivery'), paymentSettings.card_on_delivery_enabled == 1);
      setupToggle($('#toggleOnline'), paymentSettings.online_payment_enabled == 1);

      updateStripeVisibility();
      $('#stripeMode').value = paymentSettings.stripe_mode || 'test';
      updateStripeModeVisibility();

      renderCities();
    }

    function updateStripeVisibility() {
      $('#stripeConfig').style.display = $('#toggleOnline').classList.contains('active') ? 'block' : 'none';
    }
    function updateStripeModeVisibility() {
      const mode = $('#stripeMode').value;
      $('#stripeTestKeys').style.display = mode === 'test' ? 'block' : 'none';
      $('#stripeLiveKeys').style.display = mode === 'live' ? 'block' : 'none';
    }
    $('#toggleOnline').addEventListener('click', updateStripeVisibility);
    $('#stripeMode').addEventListener('change', updateStripeModeVisibility);

    // Save general
    $('#formGeneral').addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = {
        currency_code: $('#currencyCode').value,
        currency_symbol: $('#currencySymbol').value,
        currency_position: $('#currencyPosition').value,
        country_code: $('#countryCode').value,
        country_name: $('#countryName').value,
        delivery_enabled: $('#toggleDelivery').classList.contains('active') ? 1 : 0,
        pickup_enabled: $('#togglePickup').classList.contains('active') ? 1 : 0,
        min_order_amount: parseFloat($('#minOrderAmount').value) || 0,
        free_delivery_threshold: $('#freeDeliveryThreshold').value ? parseFloat($('#freeDeliveryThreshold').value) : null
      };
      const res = await api('update_settings', data);
      toast(res.success ? 'Paramètres enregistrés' : (res.error || res.message), res.success ? 'success' : 'error');
    });

    // Save Stripe
    $('#btnSaveStripe').addEventListener('click', async () => {
      const data = {
        cash_enabled: $('#toggleCash').classList.contains('active') ? 1 : 0,
        card_on_delivery_enabled: $('#toggleCardDelivery').classList.contains('active') ? 1 : 0,
        online_payment_enabled: $('#toggleOnline').classList.contains('active') ? 1 : 0,
        stripe_mode: $('#stripeMode').value
      };
      const res = await api('update_payment_settings', data);
      toast(res.success ? 'Configuration paiement enregistrée' : (res.error || res.message), res.success ? 'success' : 'error');
    });

    // Cities
    function renderCities() {
      const list = $('#cityList');
      if (!deliveryCities.length) {
        list.innerHTML = '<p style="color:var(--muted);text-align:center;padding:40px">Aucune ville configurée. Ajoutez votre première ville !</p>';
        return;
      }
      list.innerHTML = deliveryCities.map(city => {
        const fee = parseFloat(city.delivery_fee) || 0;
        const badges = [];
        if (city.is_home_city == 1) badges.push('<span class="city-badge home"><i class="fas fa-home"></i> Ville du resto</span>');
        badges.push(`<span class="city-badge ${city.is_active == 1 ? 'active' : 'inactive'}">${city.is_active == 1 ? 'Active' : 'Inactive'}</span>`);

        return `
          <div class="city-card">
            <div class="city-info">
              <h4>${badges.join(' ')} ${escapeHtml(city.city_name)} ${city.postal_code ? `<span style="color:var(--muted);font-weight:400">(${city.postal_code})</span>` : ''}</h4>
              <p><i class="fas fa-clock"></i> ~${city.estimated_time_min || 30} min ${city.min_order_amount ? `&nbsp;•&nbsp;<i class="fas fa-shopping-cart"></i> Min ${city.min_order_amount}€` : ''}</p>
            </div>
            <div style="display:flex;align-items:center;gap:16px">
              <div class="city-fee ${fee > 0 ? 'paid' : ''}">${fee > 0 ? fee.toFixed(2) + ' €' : 'Gratuit'}</div>
              <div class="city-actions">
                <button class="btn btn-sm" onclick="editCity(${city.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteCity(${city.id})"><i class="fas fa-trash"></i></button>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    $('#btnAddCity').addEventListener('click', () => {
      $('#modalCityTitle').textContent = 'Ajouter une ville';
      $('#formCity').reset();
      $('#cityId').value = '';
      $('#toggleCityActive').classList.add('active');
      $('#toggleCityHome').classList.remove('active');
      openModal('modalCity');
    });

    function editCity(id) {
      const city = deliveryCities.find(c => c.id == id);
      if (!city) return;
      $('#modalCityTitle').textContent = 'Modifier la ville';
      $('#cityId').value = city.id;
      $('#cityName').value = city.city_name;
      $('#cityPostalCode').value = city.postal_code || '';
      $('#cityFee').value = city.delivery_fee;
      $('#cityTime').value = city.estimated_time_min || 30;
      $('#cityMinOrder').value = city.min_order_amount || '';
      city.is_active == 1 ? $('#toggleCityActive').classList.add('active') : $('#toggleCityActive').classList.remove('active');
      city.is_home_city == 1 ? $('#toggleCityHome').classList.add('active') : $('#toggleCityHome').classList.remove('active');
      openModal('modalCity');
    }

    async function deleteCity(id) {
      if (!confirm('Supprimer cette ville ?')) return;
      const res = await api('delete_delivery_city', { id });
      if (res.success) {
        deliveryCities = res.cities;
        renderCities();
        toast('Ville supprimée');
      } else toast((res.error || res.message), 'error');
    }

    $('#formCity').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = $('#cityId').value;
      const data = {
        city_name: $('#cityName').value,
        postal_code: $('#cityPostalCode').value || null,
        delivery_fee: parseFloat($('#cityFee').value) || 0,
        estimated_time_min: parseInt($('#cityTime').value) || 30,
        min_order_amount: $('#cityMinOrder').value ? parseFloat($('#cityMinOrder').value) : null,
        is_active: $('#toggleCityActive').classList.contains('active') ? 1 : 0,
        is_home_city: $('#toggleCityHome').classList.contains('active') ? 1 : 0
      };
      const res = id ? await api('update_delivery_city', { id: parseInt(id), ...data }) : await api('add_delivery_city', data);
      if (res.success) {
        deliveryCities = res.cities;
        renderCities();
        closeModal('modalCity');
        toast(id ? 'Ville modifiée' : 'Ville ajoutée');
      } else toast((res.error || res.message), 'error');
    });

    $('#toggleCityActive').onclick = function() { this.classList.toggle('active'); };
    $('#toggleCityHome').onclick = function() { this.classList.toggle('active'); };

    loadSettings();
  </script>
</body>
</html>
