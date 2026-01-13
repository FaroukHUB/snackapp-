<?php
// products-manager.php
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Produits</title>
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
      --shadow: 0 14px 50px rgba(0,0,0,.45);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Apple Color Emoji","Segoe UI Emoji";
      background: radial-gradient(1200px 800px at 10% 0%, #122044 0%, var(--bg) 60%);
      color:var(--text);
    }

    .wrap{max-width:1100px;margin:0 auto;padding:24px 16px 64px}
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
    .btn-good{background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-color: transparent;box-shadow: 0 2px 10px rgba(22,163,74,.3)}
    .btn-good:hover{background: rgba(22,163,74,.22)}
    .btn-ghost{background: transparent}
    .btn-danger{background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.32)}
    .btn-danger:hover{background: rgba(239,68,68,.18)}
    .btn-icon-sm{
      padding:6px 8px;
      font-size:12px;
      min-width:0;
      border:1px solid var(--stroke);
      background:rgba(255,255,255,.04);
      color:var(--text);
      border-radius:8px;
      cursor:pointer;
      transition:all .2s ease;
    }
    .btn-icon-sm:hover{background:rgba(255,255,255,.08);transform:scale(1.05)}
    .btn-icon-sm.btn-danger{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.24);color:#ef4444}
    .btn-icon-sm.btn-danger:hover{background:rgba(239,68,68,.15)}

    .grid{
      display:grid;
      grid-template-columns: 320px 1fr;
      gap:14px;
    }
    @media (max-width: 920px){
      .grid{grid-template-columns:1fr}
    }

    .panel{
      background: linear-gradient(180deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
      backdrop-filter: blur(10px);
    }
    .panel-h{
      padding:16px 16px 14px;
      border-bottom:1px solid var(--stroke);
      display:flex;align-items:center;justify-content:space-between;gap:10px;
      background: linear-gradient(135deg, rgba(255,255,255,.04) 0%, transparent 100%);
    }
    .panel-h h2{margin:0;font-size:15px;font-weight:700;color:#fff}
    .panel-h .meta{color:var(--muted);font-size:12px}
    .panel-b{padding:14px}

    .search{
      width:100%;
      border:1px solid var(--stroke);
      background: rgba(0,0,0,.18);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      outline:none;
    }

    .cat-list{display:flex;flex-direction:column;gap:8px;margin-top:10px}
    .cat{
      border:1px solid var(--stroke);
      background: linear-gradient(135deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border-radius:14px;
      padding:12px 14px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;
      cursor:pointer;
      transition: all .2s ease;
    }
    .cat:hover{
      background: linear-gradient(135deg, rgba(96,165,250,.15) 0%, rgba(96,165,250,.08) 100%);
      border-color: rgba(96,165,250,.3);
      transform: translateX(4px);
    }
    .cat[aria-selected="true"]{
      border-color: rgba(96,165,250,.5);
      background: linear-gradient(135deg, rgba(96,165,250,.2) 0%, rgba(96,165,250,.1) 100%);
      box-shadow: 0 0 20px rgba(96,165,250,.15);
    }
    .cat strong{display:block;font-size:14px;color:#ffffff;font-weight:600}
    .cat small{display:block;color:var(--muted);font-size:12px;margin-top:3px;line-height:1.3}
    .badge{
      font-size:11px;
      font-weight:600;
      color:#fff;
      padding:6px 12px;
      border:none;
      border-radius:999px;
      background: linear-gradient(135deg, var(--brand) 0%, #3b82f6 100%);
      white-space:nowrap;
      height:fit-content;
      box-shadow: 0 2px 8px rgba(96,165,250,.3);
    }

    .table{
      width:100%;
      border-collapse:separate;
      border-spacing:0 8px;
    }
    .row{
      background: linear-gradient(135deg, rgba(255,255,255,.05) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius:16px;
      transition: all .2s ease;
    }
    .row:hover{
      background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.04) 100%);
      border-color: rgba(255,255,255,.15);
      transform: scale(1.01);
      box-shadow: 0 4px 20px rgba(0,0,0,.2);
    }
    .row td{padding:12px 12px;vertical-align:middle}
    .row td:first-child{border-top-left-radius:16px;border-bottom-left-radius:16px}
    .row td:last-child{border-top-right-radius:16px;border-bottom-right-radius:16px}
    .row td strong{color:#ffffff;font-weight:600}
    .muted{color:var(--muted);font-size:12px}
    .price{font-variant-numeric: tabular-nums;font-weight:600;color:#fff}
    .thumb{
      width:48px;height:48px;border-radius:12px;
      border:1px solid rgba(255,255,255,.1);
      background: linear-gradient(135deg, rgba(0,0,0,.3) 0%, rgba(0,0,0,.2) 100%);
      object-fit:cover;
      display:block;
      box-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .status{
      display:inline-flex;align-items:center;gap:6px;
      padding:6px 12px;border-radius:999px;
      border:none;
      font-size:12px;font-weight:500;
    }
    .status[data-status="available"]{background: rgba(22,163,74,.15);color:#4ade80}
    .status[data-status="unavailable"]{background: rgba(239,68,68,.15);color:#f87171}
    .dot{width:8px;height:8px;border-radius:999px;background: currentColor}

    /* ===== Modal moderne ===== */
    .modal-overlay{
      position:fixed;inset:0;
      display:none;
      align-items:center;justify-content:center;
      padding:12px;
      background: rgba(0,0,0,.62);
      backdrop-filter: blur(8px);
      z-index: 1000;
    }
    .modal-overlay[aria-hidden="false"]{display:flex}
    .modal{
      width:100%;
      max-width: 480px;
      max-height: calc(100vh - 40px);
      max-height: calc(100dvh - 40px);
      display:flex;
      flex-direction:column;
      border-radius: 16px;
      border:1px solid rgba(255,255,255,.14);
      background: linear-gradient(180deg, rgba(20,25,40,.98), rgba(15,20,35,.98));
      box-shadow: var(--shadow);
      overflow:hidden;
      transform: translateY(10px) scale(.98);
      opacity:0;
      transition: transform .16s ease, opacity .16s ease;
    }
    .modal-overlay[aria-hidden="false"] .modal{
      transform: translateY(0) scale(1);
      opacity:1;
    }
    .modal-h{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.12);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-shrink:0}
    .modal-h h3{margin:0;font-size:14px;font-weight:600}
    .modal form{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
    .modal-b{padding:12px 14px;overflow-y:auto;flex:1 1 auto;min-height:0}
    .modal-f{padding:14px;border-top:1px solid rgba(255,255,255,.12);display:flex;gap:10px;justify-content:flex-end;flex-wrap:nowrap;flex-shrink:0;background:rgba(0,0,0,.3)}
    .field{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
    .field label{font-size:11px;color:var(--muted);font-weight:500}
    .input, .textarea, .select{
      width:100%;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.20);
      color:var(--text);
      padding:8px 10px;
      border-radius:10px;
      outline:none;
      font-size:14px;
    }
    .textarea{min-height:70px;resize:vertical}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    @media (max-width: 520px){.two{grid-template-columns:1fr}}
    .upload{
      display:flex;align-items:center;gap:10px;flex-wrap:wrap;
      padding:10px;border:1px dashed rgba(255,255,255,.20);
      border-radius:12px;background: rgba(0,0,0,.16);
    }
    .upload input{color:var(--muted)}
    .preview{width:86px;height:86px;border-radius:16px;border:1px solid rgba(255,255,255,.14);background: rgba(0,0,0,.18);object-fit:cover}

    /* ===== Toast ===== */
    .toast{
      position: fixed; right: 14px; bottom: 14px;
      width: min(420px, calc(100vw - 28px));
      display:flex;flex-direction:column;gap:10px;
      z-index: 2000;
    }
    .toast-item{
      border:1px solid rgba(255,255,255,.14);
      background: rgba(15,23,42,.85);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 12px 12px;
      box-shadow: var(--shadow);
      display:flex;gap:10px;align-items:flex-start;justify-content:space-between;
    }
    .toast-item strong{display:block;font-size:13px;margin-bottom:2px}
    .toast-item p{margin:0;color:var(--muted);font-size:12px;line-height:1.3}
    .toast-item button{border:none;background:transparent;color:var(--muted);cursor:pointer}
    .toast-item[data-type="success"]{border-color: rgba(22,163,74,.35)}
    .toast-item[data-type="error"]{border-color: rgba(239,68,68,.35)}

    /* ===== ICON SELECTOR ===== */
    .icon-option {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      aspect-ratio: 1;
      border: 1px solid var(--stroke);
      border-radius: 10px;
      background: rgba(0,0,0,.18);
      cursor: pointer;
      transition: all .15s ease;
    }
    .icon-option:hover {
      border-color: var(--brand);
      background: rgba(96,165,250,.1);
    }
    .icon-option input {
      display: none;
    }
    .icon-option span {
      font-size: 20px;
      color: var(--muted);
      transition: color .15s ease;
    }
    .icon-option.selected {
      border-color: var(--brand);
      background: rgba(96,165,250,.2);
      box-shadow: 0 0 12px rgba(96,165,250,.3);
    }
    .icon-option.selected span {
      color: var(--brand);
    }

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
      .wrap { padding: 16px 12px 80px; }
      .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
      .title h1 { font-size: 18px; }
      .title p { font-size: 11px; }
      .actions { width: 100%; flex-wrap: wrap; }
      .actions .btn { flex: 1; min-width: 100px; justify-content: center; }
      .grid { grid-template-columns: 1fr; gap: 12px; }
      .panel-h { padding: 12px; flex-wrap: wrap; gap: 8px; }
      .panel-b { padding: 10px; }
      .cat { padding: 12px 10px; }
      .row td { padding: 8px 6px; }
      .thumb { width: 36px; height: 36px; }
      .status { padding: 4px 8px; font-size: 11px; }
      .modal { max-width: calc(100vw - 20px); max-height: calc(100vh - 20px); border-radius: 14px; }
      .modal-h, .modal-f { padding: 10px 12px; }
      .modal-b { padding: 10px 12px; }
      .two { grid-template-columns: 1fr; gap: 8px; }
      .field { margin-bottom: 8px; }
      .field label { font-size: 11px; }
      .input, .textarea, .select { padding: 10px; font-size: 16px; }
      .textarea { min-height: 60px; }
      .upload { flex-direction: column; align-items: flex-start; padding: 8px; }
      .preview { width: 60px; height: 60px; }
    }

    @media (max-width: 480px) {
      .wrap { padding: 12px 8px 70px; }
      .topbar { margin-bottom: 12px; }
      .title h1 { font-size: 16px; }
      .btn { padding: 8px 10px; font-size: 12px; border-radius: 10px; }
      .actions .btn { font-size: 11px; padding: 8px; }
      .panel { border-radius: 12px; }
      .panel-h h2 { font-size: 13px; }
      .search { padding: 10px; font-size: 14px; }
      .cat-list { gap: 6px; }
      .cat strong { font-size: 12px; }
      .cat small { font-size: 11px; }
      .badge { font-size: 11px; padding: 4px 6px; }
      .row { border-radius: 10px; }
      .row td:first-child { width: 44px !important; }
      .thumb { width: 32px; height: 32px; border-radius: 8px; }
      .price { font-size: 12px; }
      .price .muted { display: none; }
      .status { padding: 4px 6px; font-size: 10px; }
      .status .dot { width: 6px; height: 6px; }
      .modal-overlay { padding: 6px; }
      .modal { border-radius: 12px; max-height: calc(100vh - 12px); }
      .modal-h { padding: 10px; }
      .modal-h h3 { font-size: 13px; }
      .modal-b { padding: 10px; }
      .modal-f { padding: 10px; flex-direction: row; }
      .modal-f .btn { flex: 1; justify-content: center; padding: 10px 8px; }
      .field { margin-bottom: 6px; }
      .input, .textarea, .select { padding: 8px; }
      .textarea { min-height: 50px; }
      .toast { right: 8px; bottom: 8px; width: calc(100vw - 16px); }
    }

    /* Très petits écrans */
    @media (max-width: 360px) {
      .actions { gap: 6px; }
      .actions .btn { min-width: 80px; font-size: 10px; padding: 6px; }
      .row td { padding: 6px 4px; font-size: 12px; }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1>Gestion du menu</h1>
        <p>Catégories & produits — ajout rapide + image (upload).</p>
      </div>

      <div class="actions">
        <a class="btn btn-ghost" href="./index.php" style="margin-right:8px;">← Retour</a>
        <button class="btn btn-good" type="button" id="btnAddCategory">+ Catégorie</button>
        <button class="btn btn-primary" type="button" id="btnAddProduct">+ Produit</button>
        <button class="btn" type="button" id="btnAddFormule" style="background:rgba(168,85,247,.14);border-color:rgba(168,85,247,.35);">📦 + Formule</button>
        <button class="btn" type="button" id="btnManageSupplements" style="background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.35);">🧀 Suppléments</button>
      </div>
    </div>

    <div class="grid">
      <section class="panel">
        <div class="panel-h">
          <div>
            <h2>Catégories</h2>
            <div class="meta" id="catMeta">Chargement…</div>
          </div>
        </div>
        <div class="panel-b">
          <input id="catSearch" class="search" type="search" placeholder="Rechercher une catégorie…" />
          <div id="categories" class="cat-list" aria-label="Liste des catégories"></div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-h">
          <div>
            <h2 id="productsTitle">Produits</h2>
            <div class="meta" id="prodMeta">—</div>
          </div>
          <button class="btn btn-primary" type="button" id="btnAddProductInline">+ Ajouter</button>
        </div>
        <div class="panel-b">
          <input id="prodSearch" class="search" type="search" placeholder="Rechercher un produit…" />
          <div style="height:10px"></div>
          <table class="table" aria-label="Liste des produits">
            <tbody id="productsBody"></tbody>
          </table>
          <div id="emptyState" class="muted" style="display:none;padding:10px 2px;">Aucun produit à afficher.</div>
        </div>
      </section>
    </div>
  </div>

  <!-- MODAL: Catégorie -->
  <div id="modalCategory" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalCategoryTitle">
      <div class="modal-h">
        <h3 id="modalCategoryTitle">Ajouter une catégorie</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <form id="formCategory">
        <div class="modal-b">
          <div class="field">
            <label for="catName">Nom</label>
            <input id="catName" name="name" class="input" type="text" placeholder="Ex : Burgers" required />
          </div>
          <div class="field">
            <label for="catDesc">Description (optionnel)</label>
            <textarea id="catDesc" name="description" class="textarea" placeholder="Texte affiché sous la catégorie…"></textarea>
          </div>
          <div class="field">
            <label for="catIcon">Icône</label>
            <div id="iconSelector" style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:6px;">
              <label class="icon-option" data-icon="fa-burger" title="Burger">
                <input type="radio" name="icon" value="fa-burger" checked>
                <span><i class="fas fa-burger"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-pizza-slice" title="Pizza">
                <input type="radio" name="icon" value="fa-pizza-slice">
                <span><i class="fas fa-pizza-slice"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-drumstick-bite" title="Poulet">
                <input type="radio" name="icon" value="fa-drumstick-bite">
                <span><i class="fas fa-drumstick-bite"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-hotdog" title="Hot-dog">
                <input type="radio" name="icon" value="fa-hotdog">
                <span><i class="fas fa-hotdog"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-bowl-food" title="Bowl">
                <input type="radio" name="icon" value="fa-bowl-food">
                <span><i class="fas fa-bowl-food"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-bread-slice" title="Sandwich">
                <input type="radio" name="icon" value="fa-bread-slice">
                <span><i class="fas fa-bread-slice"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-glass-water" title="Boisson">
                <input type="radio" name="icon" value="fa-glass-water">
                <span><i class="fas fa-glass-water"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-mug-hot" title="Café">
                <input type="radio" name="icon" value="fa-mug-hot">
                <span><i class="fas fa-mug-hot"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-wine-glass" title="Vin">
                <input type="radio" name="icon" value="fa-wine-glass">
                <span><i class="fas fa-wine-glass"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-ice-cream" title="Glace">
                <input type="radio" name="icon" value="fa-ice-cream">
                <span><i class="fas fa-ice-cream"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-cookie-bite" title="Dessert">
                <input type="radio" name="icon" value="fa-cookie-bite">
                <span><i class="fas fa-cookie-bite"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-cake-candles" title="Gâteau">
                <input type="radio" name="icon" value="fa-cake-candles">
                <span><i class="fas fa-cake-candles"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-cheese" title="Fromage">
                <input type="radio" name="icon" value="fa-cheese">
                <span><i class="fas fa-cheese"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-pancakes" title="Crêpes">
                <input type="radio" name="icon" value="fa-pancakes">
                <span><i class="fas fa-pancakes"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-waffle" title="Gaufres">
                <input type="radio" name="icon" value="fa-waffle">
                <span><i class="fas fa-waffle"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-candy" title="Sucré">
                <input type="radio" name="icon" value="fa-candy">
                <span><i class="fas fa-candy"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-pepper-hot" title="Salé Épicé">
                <input type="radio" name="icon" value="fa-pepper-hot">
                <span><i class="fas fa-pepper-hot"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-utensils" title="Plats">
                <input type="radio" name="icon" value="fa-utensils">
                <span><i class="fas fa-utensils"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-leaf" title="Végétarien">
                <input type="radio" name="icon" value="fa-leaf">
                <span><i class="fas fa-leaf"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-child" title="Enfant">
                <input type="radio" name="icon" value="fa-child">
                <span><i class="fas fa-child"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-star" title="Spécial">
                <input type="radio" name="icon" value="fa-star">
                <span><i class="fas fa-star"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-fire" title="Épicé">
                <input type="radio" name="icon" value="fa-fire">
                <span><i class="fas fa-fire"></i></span>
              </label>
              <label class="icon-option" data-icon="fa-utensils" title="Général">
                <input type="radio" name="icon" value="fa-utensils">
                <span><i class="fas fa-utensils"></i></span>
              </label>
            </div>
          </div>
          <p class="muted" style="margin:0">L'identifiant technique est généré automatiquement.</p>
        </div>
        <div class="modal-f">
          <button class="btn btn-ghost" type="button" data-close>Annuler</button>
          <button class="btn btn-good" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Produit -->
  <div id="modalProduct" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalProductTitle">
      <div class="modal-h">
        <h3 id="modalProductTitle">Ajouter un produit</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <form id="formProduct" enctype="multipart/form-data">
        <div class="modal-b">
          <div class="field">
            <label for="prodCategory">Catégorie</label>
            <select id="prodCategory" name="category_id" class="select" required></select>
          </div>

          <div class="field">
            <label for="prodName">Nom</label>
            <input id="prodName" name="name" class="input" type="text" placeholder="Ex : Le Fabrik" required />
          </div>

          <div class="field">
            <label for="prodDesc">Description (optionnel)</label>
            <textarea id="prodDesc" name="description" class="textarea" placeholder="Ingrédients, sauce, etc."></textarea>
          </div>

          <div class="two">
            <div class="field">
              <label for="priceSolo">Prix (solo)</label>
              <input id="priceSolo" name="priceSolo" class="input" type="number" step="0.01" min="0" placeholder="11.00" required />
            </div>
            <div class="field">
              <label for="priceMenu">Prix (menu) (optionnel)</label>
              <input id="priceMenu" name="priceMenu" class="input" type="number" step="0.01" min="0" placeholder="14.00" />
            </div>
          </div>

          <div class="field">
            <label for="prodBadge">Badge (optionnel)</label>
            <input id="prodBadge" name="badge" class="input" type="text" placeholder="Ex: Nouveau, Best-seller, Épicé 🌶️" maxlength="30" />
            <span class="muted">Apparaît sur la photo du produit (max 30 caractères)</span>
          </div>

          <div class="field">
            <label>Photo</label>
            <div class="upload">
              <img id="imgPreview" class="preview" alt="Aperçu" />
              <div style="display:flex;flex-direction:column;gap:8px">
                <input id="imageFile" name="imageFile" type="file" accept="image/png,image/jpeg,image/webp" required />
                <span class="muted">Formats: jpg / png / webp • conseillé: 800×800</span>
              </div>
            </div>
          </div>

          <!-- Suppléments : Affichage automatique selon le flavor (salé/sucré) -->
          <div class="field">
            <label>Suppléments</label>
            <p class="muted" style="margin-top:6px;font-size:13px;">
              ✨ Les suppléments s'affichent automatiquement selon le type de produit (salé/sucré)
            </p>
          </div>
        </div>
        <div class="modal-f">
          <button class="btn btn-ghost" type="button" data-close>Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Suppléments -->
  <div id="modalSupplements" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalSupplementsTitle" style="max-width:600px;">
      <div class="modal-h">
        <h3 id="modalSupplementsTitle">Gestion des suppléments</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <div class="modal-b">
        <p class="muted" style="margin-bottom:14px;">Les suppléments sont des options payantes que les clients peuvent ajouter à leurs produits (fromage, bacon, etc.)</p>

        <form id="formAddSupplement" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
          <input id="supName" name="name" class="input" type="text" placeholder="Nom du supplément" style="flex:1;min-width:140px;" required />
          <select id="supCategory" name="category" class="input" style="width:130px;" required>
            <optgroup label="Salé">
              <option value="fromage">Fromage</option>
              <option value="legume">Légume</option>
              <option value="viande">Viande</option>
              <option value="autre">Autre (Salé)</option>
            </optgroup>
            <optgroup label="Sucré">
              <option value="base">Base</option>
              <option value="croquant">Croquant</option>
              <option value="fruit">Fruit</option>
              <option value="prime">Prime</option>
            </optgroup>
          </select>
          <input id="supPrice" name="price" class="input" type="number" step="0.01" min="0" placeholder="Prix DA" style="width:90px;" required />
          <button class="btn btn-good" type="submit">+ Ajouter</button>
        </form>

        <div id="supplementsList" style="display:flex;flex-direction:column;gap:8px;max-height:300px;overflow-y:auto;"></div>
      </div>
      <div class="modal-f">
        <button class="btn btn-ghost" type="button" data-close>Fermer</button>
      </div>
    </div>
  </div>

  <!-- MODAL: Éditer Produit -->
  <div id="modalEditProduct" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalEditProductTitle">
      <div class="modal-h">
        <h3 id="modalEditProductTitle">Modifier le produit</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <form id="formEditProduct">
        <input type="hidden" id="editProductId" name="product_id" />
        <div class="modal-b">
          <div class="field">
            <label for="editProdName">Nom</label>
            <input id="editProdName" name="name" class="input" type="text" required />
          </div>
          <div class="field">
            <label for="editProdDesc">Description</label>
            <textarea id="editProdDesc" name="description" class="textarea"></textarea>
          </div>
          <div class="two">
            <div class="field">
              <label for="editPriceSolo">Prix (solo)</label>
              <input id="editPriceSolo" name="priceSolo" class="input" type="number" step="0.01" min="0" required />
            </div>
            <div class="field">
              <label for="editPriceMenu">Prix (menu)</label>
              <input id="editPriceMenu" name="priceMenu" class="input" type="number" step="0.01" min="0" />
            </div>
          </div>
          <div class="field">
            <label for="editProdBadge">Badge (optionnel)</label>
            <input id="editProdBadge" name="badge" class="input" type="text" placeholder="Ex: Nouveau, Best-seller, Épicé 🌶️" maxlength="30" />
            <span class="muted">Apparaît sur la photo du produit</span>
          </div>
          <div class="field">
            <label>Photo du produit</label>
            <div id="editProductImagePreview" style="margin-bottom:10px;"></div>
            <input type="file" id="editProductImage" name="image" accept="image/jpeg,image/png,image/webp" class="input" style="padding:8px;" />
            <small style="color:#9ca3af;display:block;margin-top:4px;">Formats: JPG, PNG, WebP (max 2MB)</small>
          </div>
          <div class="field">
            <label for="editStatus">Statut</label>
            <select id="editStatus" name="status" class="select">
              <option value="available">Disponible</option>
              <option value="unavailable">Indisponible</option>
            </select>
          </div>
          <!-- Suppléments : Affichage automatique selon le flavor (salé/sucré) -->
          <div class="field">
            <label>Suppléments</label>
            <p class="muted" style="margin-top:6px;font-size:13px;">
              ✨ Les suppléments s'affichent automatiquement selon le type de produit (salé/sucré)
            </p>
          </div>
        </div>
        <div class="modal-f">
          <button class="btn btn-danger" type="button" id="btnDeleteProduct">Supprimer</button>
          <button class="btn btn-ghost" type="button" data-close>Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Gestion Options Pâtisserie -->
  <div id="modalManagePatisserie" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalManagePatisserieTitle" style="max-width:700px;">
      <div class="modal-h">
        <h3 id="modalManagePatisserieTitle">🍰 Gestion Pâtisserie</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <div class="modal-b">
        <p class="muted" style="margin-bottom:16px;">Gérez les pâtisseries disponibles (Tarte au Citron, Tiramisu, etc.)</p>

        <form id="formAddPatisserieOption" style="margin-bottom:20px;padding:16px;border:1px solid var(--stroke);border-radius:12px;background:rgba(0,0,0,.1);">
          <h4 style="font-size:13px;font-weight:600;margin-bottom:12px;">Ajouter une pâtisserie</h4>
          <div class="field">
            <label for="patOptionName">Nom</label>
            <input id="patOptionName" class="input" type="text" placeholder="Ex: Tarte au Citron" required />
          </div>
          <div class="field">
            <label for="patOptionPrice">Prix (DA)</label>
            <input id="patOptionPrice" class="input" type="number" step="1" min="0" placeholder="300" required />
          </div>
          <div class="field">
            <label for="patOptionImage">Photo</label>
            <input type="file" id="patOptionImage" accept="image/jpeg,image/png,image/webp" class="input" style="padding:8px;" />
            <small style="color:#9ca3af;display:block;margin-top:4px;">Formats: JPG, PNG, WebP (max 2MB)</small>
          </div>
          <button class="btn btn-good" type="submit" style="width:100%;margin-top:8px;">+ Ajouter</button>
        </form>

        <div id="patisserieOptionsList" style="display:flex;flex-direction:column;gap:10px;max-height:350px;overflow-y:auto;"></div>
      </div>
      <div class="modal-f">
        <button class="btn btn-ghost" type="button" data-close>Fermer</button>
      </div>
    </div>
  </div>

  <!-- MODAL: Gestion Options Boisson -->
  <div id="modalManageBeverage" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalManageBeverageTitle" style="max-width:700px;">
      <div class="modal-h">
        <h3 id="modalManageBeverageTitle">🥤 Gestion Options Boisson</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <div class="modal-b">
        <p class="muted" style="margin-bottom:16px;">Gérez les options pour <strong id="currentBeverageType"></strong></p>

        <form id="formAddBeverageOption" style="margin-bottom:20px;padding:16px;border:1px solid var(--stroke);border-radius:12px;background:rgba(0,0,0,.1);">
          <h4 style="font-size:13px;font-weight:600;margin-bottom:12px;">Ajouter une option</h4>
          <div class="field">
            <label for="bevOptionName">Nom</label>
            <input id="bevOptionName" class="input" type="text" placeholder="Ex: Coca Cola, Orange, etc." required />
          </div>
          <div class="field">
            <label for="bevOptionPrice">Prix (DA)</label>
            <input id="bevOptionPrice" class="input" type="number" step="1" min="0" placeholder="100" required />
          </div>
          <button class="btn btn-good" type="submit" style="width:100%;margin-top:8px;">+ Ajouter</button>
        </form>

        <div id="beverageOptionsList" style="display:flex;flex-direction:column;gap:10px;max-height:350px;overflow-y:auto;"></div>
      </div>
      <div class="modal-f">
        <button class="btn btn-ghost" type="button" data-close>Fermer</button>
      </div>
    </div>
  </div>

  <!-- MODAL: Formule -->
  <div id="modalFormule" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalFormuleTitle" style="max-width:580px;">
      <div class="modal-h">
        <h3 id="modalFormuleTitle">Ajouter une formule</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <form id="formFormule" enctype="multipart/form-data">
        <input type="hidden" id="formuleId" name="formule_id" />
        <div class="modal-b" style="max-height:60vh;overflow-y:auto;">
          <div class="field">
            <label for="formuleName">Nom de la formule</label>
            <input id="formuleName" name="name" class="input" type="text" placeholder="Ex : Formule Midi" required />
          </div>

          <div class="field">
            <label for="formuleDesc">Description</label>
            <textarea id="formuleDesc" name="description" class="textarea" placeholder="Ex : 1 Burger au choix + Frites + Boisson"></textarea>
          </div>

          <div class="two">
            <div class="field">
              <label for="formulePrice">Prix (DA)</label>
              <input id="formulePrice" name="price" class="input" type="number" step="0.01" min="0" placeholder="12.90" required />
            </div>
            <div class="field">
              <label for="formuleOriginalPrice">Prix barré (optionnel)</label>
              <input id="formuleOriginalPrice" name="originalPrice" class="input" type="number" step="0.01" min="0" placeholder="15.50" />
            </div>
          </div>

          <div class="field">
            <label for="formuleBadge">Badge (optionnel)</label>
            <input id="formuleBadge" name="badge" class="input" type="text" placeholder="Ex : Populaire, -20%, Best Value" />
          </div>

          <div class="field">
            <label>Photo de la formule</label>
            <div class="upload">
              <img id="formuleImgPreview" class="preview" alt="Aperçu" style="display:none;" />
              <div style="display:flex;flex-direction:column;gap:8px">
                <input id="formuleImage" name="image" type="file" accept="image/png,image/jpeg,image/webp" />
                <span class="muted">Formats: jpg / png / webp • conseillé: 800×600</span>
              </div>
            </div>
          </div>

          <div class="field">
            <label>Contenu de la formule</label>
            <div id="formuleIncludes" style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
              <!-- Les includes seront ajoutés dynamiquement -->
            </div>
            <button type="button" class="btn btn-ghost" id="btnAddInclude" style="margin-top:8px;">+ Ajouter un élément</button>
          </div>

          <div class="field">
            <label for="formuleStatus">Statut</label>
            <select id="formuleStatus" name="status" class="select">
              <option value="available">Disponible</option>
              <option value="unavailable">Indisponible</option>
            </select>
          </div>
        </div>
        <div class="modal-f">
          <button class="btn btn-danger" type="button" id="btnDeleteFormule" style="display:none;">Supprimer</button>
          <button class="btn btn-ghost" type="button" data-close>Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Section Formules (affichée sous la grille) -->
  <div class="wrap" style="padding-top:0;">
    <section class="panel" id="formulesPanel" style="margin-top:20px;">
      <div class="panel-h">
        <div>
          <h2>📦 Formules</h2>
          <div class="meta" id="formulesMeta">Chargement…</div>
        </div>
        <button class="btn btn-primary" type="button" id="btnAddFormuleInline">+ Ajouter</button>
      </div>
      <div class="panel-b">
        <div id="formulesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;"></div>
        <div id="formulesEmpty" class="muted" style="display:none;padding:20px;text-align:center;">Aucune formule configurée.</div>
      </div>
    </section>

    <!-- Section Sélection pour vous -->
    <section class="panel" id="featuredPanel" style="margin-top:20px;">
      <div class="panel-h">
        <div>
          <h2>⭐ Sélection pour vous</h2>
          <div class="meta" id="featuredMeta">Produits mis en avant sur la page d'accueil</div>
        </div>
        <button class="btn btn-primary" type="button" id="btnAddFeatured">+ Ajouter</button>
      </div>
      <div class="panel-b">
        <div class="field" style="margin-bottom:12px;">
          <label for="featuredTitle">Titre de la section</label>
          <input id="featuredTitle" class="input" type="text" placeholder="Sélection pour vous" />
        </div>
        <div class="field" style="margin-bottom:12px;">
          <label for="featuredSubtitle">Sous-titre</label>
          <input id="featuredSubtitle" class="input" type="text" placeholder="Nos produits les plus appréciés" />
        </div>
        <div class="field">
          <label>Produits sélectionnés (glisser pour réordonner)</label>
          <div id="featuredGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:8px;"></div>
          <div id="featuredEmpty" class="muted" style="display:none;padding:20px;text-align:center;">Aucun produit sélectionné. Cliquez sur "+ Ajouter" pour en ajouter.</div>
        </div>
        <button class="btn btn-good" type="button" id="btnSaveFeatured" style="margin-top:16px;">Enregistrer la sélection</button>
      </div>
    </section>
  </div>

  <!-- MODAL: Ajouter produit Featured -->
  <div id="modalFeatured" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalFeaturedTitle" style="max-width:600px;">
      <div class="modal-h">
        <h3 id="modalFeaturedTitle">Ajouter à la sélection</h3>
        <button class="btn btn-ghost" type="button" data-close aria-label="Fermer">✕</button>
      </div>
      <div class="modal-b" style="max-height:60vh;overflow-y:auto;">
        <input id="featuredSearch" class="search" type="search" placeholder="Rechercher un produit..." style="margin-bottom:12px;" />
        <div id="featuredProductsList" style="display:grid;gap:8px;"></div>
      </div>
      <div class="modal-f">
        <button class="btn btn-ghost" type="button" data-close>Fermer</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast" aria-live="polite" aria-atomic="true"></div>

  <script>
    "use strict";

    const API = "api/products.php";

    const $ = (sel, root=document) => root.querySelector(sel);
    const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

    const state = {
      menu: null,
      selectedCategoryId: null,
      catQuery: "",
      prodQuery: "",
    };

    function toast(type, title, message){
      const host = $("#toast");
      const item = document.createElement("div");
      item.className = "toast-item";
      item.dataset.type = type;
      item.innerHTML = `
        <div style="min-width:0">
          <strong>${escapeHtml(title)}</strong>
          <p>${escapeHtml(message)}</p>
        </div>
        <button type="button" aria-label="Fermer">✕</button>
      `;
      item.querySelector("button").addEventListener("click", () => item.remove());
      host.appendChild(item);
      setTimeout(() => item.remove(), 4200);
    }

    function escapeHtml(s){
      return String(s ?? "").replace(/[&<>"']/g, c => ({
        "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"
      }[c]));
    }

    // CSRF token for secure requests
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function appendCsrf(formData) {
      if (formData instanceof FormData) {
        formData.append('csrf_token', CSRF_TOKEN);
      }
      return formData;
    }

    function openModal(id){
      const overlay = $(id);
      overlay.setAttribute("aria-hidden","false");
      document.body.style.overflow = "hidden";
      const firstInput = overlay.querySelector("input,select,textarea,button");
      if (firstInput) firstInput.focus({preventScroll:true});
    }
    function closeModal(overlay){
      overlay.setAttribute("aria-hidden","true");
      document.body.style.overflow = "";
    }

    // Fermer toutes les modals avec [data-close]
    $$("[data-close]").forEach(btn=>{
      btn.addEventListener("click", (e)=>{
        const overlay = e.target.closest(".modal-overlay");
        if (overlay) closeModal(overlay);
      });
    });
    $$(".modal-overlay").forEach(overlay=>{
      overlay.addEventListener("click", (e)=>{
        if (e.target === overlay) closeModal(overlay);
      });
    });
    document.addEventListener("keydown", (e)=>{
      if (e.key !== "Escape") return;
      const open = $$('.modal-overlay[aria-hidden="false"]');
      if (open.length) closeModal(open[open.length-1]);
    });

    // ===== ICON SELECTOR LOGIC =====
    function initIconSelector() {
      const container = $("#iconSelector");
      if (!container) return;

      const options = $$(".icon-option", container);

      // Set initial selected state
      options.forEach(opt => {
        const input = opt.querySelector("input");
        if (input && input.checked) {
          opt.classList.add("selected");
        }

        // Handle click/change
        opt.addEventListener("click", () => {
          options.forEach(o => o.classList.remove("selected"));
          opt.classList.add("selected");
          if (input) input.checked = true;
        });
      });
    }

    // Initialize on page load
    initIconSelector();

    let currentEditCategoryId = null;

    $("#btnAddCategory").addEventListener("click", () => {
      currentEditCategoryId = null;
      $("#modalCategoryTitle").textContent = "Ajouter une catégorie";
      $("#formCategory").reset();
      // Reset icon selector to first option
      const options = $$("#iconSelector .icon-option");
      options.forEach((o, i) => {
        o.classList.toggle("selected", i === 0);
        const input = o.querySelector("input");
        if (input) input.checked = (i === 0);
      });
      openModal("#modalCategory");
    });

    function openEditCategoryModal(categoryId){
      currentEditCategoryId = categoryId;
      const cats = getCategories();
      const cat = cats.find(c => c.id === categoryId);
      if (!cat) {
        toast("error", "Erreur", "Catégorie introuvable.");
        return;
      }

      $("#modalCategoryTitle").textContent = "Modifier la catégorie";
      $("#catName").value = cat.name ?? "";
      $("#catDesc").value = cat.description ?? "";

      // Sélectionner l'icône actuelle
      const iconValue = cat.icon ?? "fa-utensils";
      const options = $$("#iconSelector .icon-option");
      options.forEach(o => {
        const input = o.querySelector("input");
        if (input) {
          const isSelected = input.value === iconValue;
          input.checked = isSelected;
          o.classList.toggle("selected", isSelected);
        }
      });

      openModal("#modalCategory");
    }

    async function deleteCategory(categoryId){
      const cats = getCategories();
      const cat = cats.find(c => c.id === categoryId);
      if (!cat) return;

      const itemsCount = Array.isArray(cat.items) ? cat.items.length : 0;
      let confirmMsg = `Voulez-vous vraiment supprimer la catégorie "${cat.name}" ?`;
      if (itemsCount > 0) {
        confirmMsg += `\n\n⚠️ Attention : Cette catégorie contient ${itemsCount} produit(s) qui seront également supprimés.`;
      }

      if (!confirm(confirmMsg)) return;

      try {
        await apiPostJson({ action: "delete_category", category_id: categoryId });
        toast("success", "Catégorie supprimée", `"${cat.name}" a été supprimée.`);
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible de supprimer la catégorie.");
      }
    }

    function getSupplements(){
      return state.menu?.supplements?.catalog ?? {};
    }

    function renderSupplementsCheckboxes(containerId, selectedIds = [], categoryId = null){
      const container = $(containerId);
      const allSupplements = getSupplements();
      container.innerHTML = "";

      // Définir les catégories de suppléments
      const saledCategories = ['fromage', 'legume', 'viande', 'autre'];
      const sucreCategories = ['base', 'croquant', 'fruit', 'prime'];

      // Filtrer les suppléments selon la catégorie du produit
      let supplements = allSupplements;

      if (categoryId) {
        // Pour les crêpes salées signature : uniquement suppléments salés
        if (categoryId === 'crepes-salees-signature') {
          supplements = Object.values(allSupplements).filter(sup =>
            saledCategories.includes(sup.category)
          ).reduce((acc, sup) => ({ ...acc, [sup.id]: sup }), {});
        }
        // Pour les catégories sucrées : uniquement suppléments sucrés
        else if (['crepes-sucrees', 'gaufres', 'bubble-waffle'].includes(categoryId)) {
          supplements = Object.values(allSupplements).filter(sup =>
            sucreCategories.includes(sup.category)
          ).reduce((acc, sup) => ({ ...acc, [sup.id]: sup }), {});
        }
        // Pour les autres catégories : afficher seulement ceux de defaultForCategories
        else {
          const allowedIds = state.menu?.supplements?.defaultForCategories?.[categoryId] ?? [];
          supplements = Object.values(allSupplements).filter(sup =>
            allowedIds.includes(sup.id)
          ).reduce((acc, sup) => ({ ...acc, [sup.id]: sup }), {});
        }
      }

      Object.values(supplements).forEach(sup => {
        const label = document.createElement("label");
        label.style.cssText = "display:flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--stroke);border-radius:10px;cursor:pointer;background:rgba(0,0,0,.18);font-size:13px;";
        const checked = selectedIds.includes(sup.id) ? "checked" : "";
        label.innerHTML = `
          <input type="checkbox" name="supplements[]" value="${escapeHtml(sup.id)}" ${checked} style="width:16px;height:16px;" />
          ${escapeHtml(sup.name)} <span style="color:var(--muted);">(+${sup.price.toFixed(0)} DA)</span>
        `;
        container.appendChild(label);
      });

      if (Object.keys(supplements).length === 0) {
        container.innerHTML = '<span class="muted">Aucun supplément disponible pour cette catégorie.</span>';
      }
    }

    function openProductModal(prefCatId=null){
      $("#formProduct").reset();
      $("#imgPreview").src = "";
      if (prefCatId) $("#prodCategory").value = prefCatId;

      // Suppléments : Affichage automatique selon le flavor (plus de checkboxes)
      // Les suppléments s'affichent automatiquement côté frontend

      openModal("#modalProduct");
    }

    $("#btnAddProduct").addEventListener("click", () => openProductModal(state.selectedCategoryId));
    $("#btnAddProductInline").addEventListener("click", () => openProductModal(state.selectedCategoryId));

    // Suppléments : Affichage automatique (plus besoin de mettre à jour les checkboxes)
    // $("#prodCategory").addEventListener("change", ...) - Désactivé

    $("#imageFile").addEventListener("change", (e)=>{
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      $("#imgPreview").src = url;
    });

    // ===== GESTION DES SUPPLÉMENTS =====
    $("#btnManageSupplements").addEventListener("click", () => {
      renderSupplementsList();
      openModal("#modalSupplements");
    });

    function renderSupplementsList(){
      const container = $("#supplementsList");
      const supplements = getSupplements();
      container.innerHTML = "";

      // Grouper les suppléments par catégorie
      const saledCategories = ['fromage', 'legume', 'viande', 'autre'];
      const sucreCategories = ['base', 'croquant', 'fruit', 'prime'];
      const categoryLabels = {
        'base': 'Base',
        'croquant': 'Croquant',
        'fruit': 'Fruit',
        'prime': 'Prime'
      };

      const saled = {};
      const sucre = {};

      Object.values(supplements).forEach(sup => {
        const cat = sup.category || 'autre';
        if (saledCategories.includes(cat)) {
          if (!saled[cat]) saled[cat] = [];
          saled[cat].push(sup);
        } else if (sucreCategories.includes(cat)) {
          if (!sucre[cat]) sucre[cat] = [];
          sucre[cat].push(sup);
        }
      });

      // Afficher les suppléments salés
      const hasSaled = Object.values(saled).some(arr => arr.length > 0);
      if (hasSaled) {
        const saledTitle = document.createElement("h4");
        saledTitle.style.cssText = "font-size:14px;font-weight:700;color:#E91E63;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 12px 0;padding:8px 12px;background:linear-gradient(135deg,#FCE4EC 0%,#F8BBD0 100%);border-left:4px solid #E91E63;border-radius:6px;";
        saledTitle.textContent = "Suppléments Salés";
        container.appendChild(saledTitle);

        saledCategories.forEach(cat => {
          if (saled[cat] && saled[cat].length > 0) {
            saled[cat].forEach(sup => {
              container.appendChild(createSupplementItem(sup));
            });
          }
        });

        // Espacement entre sections
        const spacer = document.createElement("div");
        spacer.style.cssText = "height:24px;";
        container.appendChild(spacer);
      }

      // Afficher les suppléments sucrés par catégorie
      const hasSucre = Object.values(sucre).some(arr => arr.length > 0);
      if (hasSucre) {
        const sucreTitle = document.createElement("h4");
        sucreTitle.style.cssText = "font-size:14px;font-weight:700;color:#E91E63;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 12px 0;padding:8px 12px;background:linear-gradient(135deg,#FCE4EC 0%,#F8BBD0 100%);border-left:4px solid #E91E63;border-radius:6px;";
        sucreTitle.textContent = "Suppléments Sucrés";
        container.appendChild(sucreTitle);

        sucreCategories.forEach(cat => {
          if (sucre[cat] && sucre[cat].length > 0) {
            // Sous-titre de catégorie
            const catTitle = document.createElement("div");
            catTitle.style.cssText = "font-size:13px;font-weight:600;color:#F06292;margin:12px 0 8px 0;padding-left:8px;text-transform:uppercase;letter-spacing:0.3px;";
            catTitle.textContent = categoryLabels[cat] || cat;
            container.appendChild(catTitle);

            sucre[cat].forEach(sup => {
              container.appendChild(createSupplementItem(sup));
            });
          }
        });
      }

      if (!hasSaled && !hasSucre) {
        container.innerHTML = '<p class="muted" style="text-align:center;padding:20px;">Aucun supplément configuré.</p>';
      }

      // Attacher les événements
      $$("[data-toggle-sup]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const id = btn.dataset.toggleSup;
          const sup = getSupplements()[id];
          if (!sup) return;
          const newStatus = sup.status === 'available' ? 'unavailable' : 'available';
          try {
            await apiPostJson({ action: "update_supplement", supplement_id: id, status: newStatus });
            toast("success", "Statut modifié", `${sup.name} est maintenant ${newStatus === 'available' ? 'disponible' : 'indisponible'}.`);
            await boot();
            renderSupplementsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de modifier le statut.");
          }
        });
      });

      $$("[data-delete-sup]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const id = btn.dataset.deleteSup;
          const sup = getSupplements()[id];
          if (!sup) return;
          if (!confirm(`Supprimer le supplément "${sup.name}" ?`)) return;
          try {
            await apiPostJson({ action: "delete_supplement", supplement_id: id });
            toast("success", "Supprimé", `${sup.name} a été supprimé.`);
            await boot();
            renderSupplementsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de supprimer.");
          }
        });
      });
    }

    // Helper pour créer un item de supplément
    function createSupplementItem(sup) {
      const div = document.createElement("div");
      div.style.cssText = "display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;margin-bottom:8px;border:1px solid var(--stroke);border-radius:12px;background:rgba(255,255,255,.04);";
      div.innerHTML = `
        <div style="flex:1;">
          <strong style="font-size:13px;">${escapeHtml(sup.name)}</strong>
          <span style="color:var(--muted);margin-left:8px;">${sup.price.toFixed(0)} DA</span>
          <span class="status" data-status="${sup.status ?? 'available'}" style="margin-left:8px;padding:4px 8px;">
            <span class="dot"></span>${sup.status === 'available' ? 'Dispo' : 'Indispo'}
          </span>
        </div>
        <div style="display:flex;gap:6px;">
          <button class="btn btn-ghost" type="button" data-toggle-sup="${escapeHtml(sup.id)}" title="Activer/Désactiver">
            ${sup.status === 'available' ? '🔴' : '🟢'}
          </button>
          <button class="btn btn-danger" type="button" data-delete-sup="${escapeHtml(sup.id)}" style="padding:6px 10px;">✕</button>
        </div>
      `;
      return div;
    }

    $("#formAddSupplement").addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = $("#supName").value.trim();
      const category = $("#supCategory").value;
      const price = parseFloat($("#supPrice").value) || 0;

      if (!name) {
        toast("error", "Erreur", "Nom requis");
        return;
      }

      try {
        await apiPostJson({ action: "add_supplement", name, category, price });
        toast("success", "Supplément ajouté", `"${name}" a été créé dans la catégorie "${category}".`);
        $("#formAddSupplement").reset();
        await boot();
        renderSupplementsList();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible d'ajouter.");
      }
    });

    // ===== GESTION OPTIONS PÂTISSERIE =====
    function openManagePatisserieModal(product) {
      renderPatisserieOptionsList();
      openModal("#modalManagePatisserie");
    }

    function renderPatisserieOptionsList() {
      const container = $("#patisserieOptionsList");
      const patisserieProduct = state.menu?.menu?.categories?.flatMap(c => c.items || []).find(p => p.id === 'patisserie');
      const options = patisserieProduct?.pâtisserieOptions || [];

      container.innerHTML = "";
      if (options.length === 0) {
        container.innerHTML = '<p class="muted">Aucune pâtisserie. Ajoutez-en ci-dessus.</p>';
        return;
      }

      options.forEach((opt, idx) => {
        const div = document.createElement("div");
        const status = opt.status ?? 'available';
        const isAvailable = status === 'available';
        div.style.cssText = "display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--stroke);border-radius:10px;background:rgba(255,255,255,.02);";

        let imgHtml = '';
        if (opt.image) {
          let imgSrc = opt.image;
          if (!imgSrc.startsWith('http') && !imgSrc.startsWith('../')) {
            imgSrc = '../' + imgSrc;
          }
          imgHtml = `<img src="${escapeHtml(imgSrc)}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;opacity:${isAvailable ? '1' : '0.4'}">`;
        } else {
          imgHtml = `<div style="width:50px;height:50px;background:var(--stroke);border-radius:8px;opacity:${isAvailable ? '1' : '0.4'}"></div>`;
        }

        div.innerHTML = `
          ${imgHtml}
          <div style="flex:1;opacity:${isAvailable ? '1' : '0.6'}">
            <strong style="font-size:13px;">${escapeHtml(opt.name)}</strong>
            <div style="color:var(--muted);font-size:12px;margin-top:2px;">${opt.price} DA</div>
          </div>
          <button class="btn btn-sm ${isAvailable ? 'btn-good' : 'btn-ghost'}" data-toggle-status-pat="${idx}" type="button" title="${isAvailable ? 'Mettre indisponible' : 'Mettre disponible'}">${isAvailable ? '✓' : '✕'}</button>
          <button class="btn btn-error btn-sm" data-delete-pat="${idx}" type="button">🗑️</button>
        `;
        container.appendChild(div);
      });

      $$("[data-toggle-status-pat]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const idx = parseInt(btn.dataset.toggleStatusPat);
          const opt = options[idx];
          const currentStatus = opt.status ?? 'available';
          const newStatus = currentStatus === 'available' ? 'unavailable' : 'available';
          try {
            await apiPostJson({ action: "update_patisserie_option_status", index: idx, status: newStatus });
            toast("success", "Statut modifié", `${opt.name} est maintenant ${newStatus === 'available' ? 'disponible' : 'indisponible'}.`);
            state.menu = await apiGet();
            renderPatisserieOptionsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de modifier le statut.");
          }
        });
      });

      $$("[data-delete-pat]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const idx = parseInt(btn.dataset.deletePat);
          const opt = options[idx];
          if (!confirm(`Supprimer "${opt.name}" ?`)) return;
          try {
            await apiPostJson({ action: "delete_patisserie_option", index: idx });
            toast("success", "Supprimé", `${opt.name} supprimé.`);
            state.menu = await apiGet();
            renderPatisserieOptionsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de supprimer.");
          }
        });
      });
    }

    $("#formAddPatisserieOption").addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = $("#patOptionName").value.trim();
      const price = parseFloat($("#patOptionPrice").value);
      const imageFile = $("#patOptionImage").files[0];

      if (!name || isNaN(price)) {
        toast("error", "Erreur", "Nom et prix sont requis.");
        return;
      }

      try {
        const formData = new FormData();
        formData.append('name', name);
        formData.append('price', price);
        if (imageFile) {
          formData.append('image', imageFile);
        }

        await apiPostMultipart(formData, 'add_patisserie_option');
        toast("success", "Ajouté", `${name} ajouté avec succès.`);
        $("#formAddPatisserieOption").reset();
        state.menu = await apiGet();
        renderPatisserieOptionsList();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible d'ajouter.");
      }
    });

    // ===== GESTION OPTIONS BOISSONS =====
    let currentBeverageProductId = null;

    function openManageBeverageModal(product) {
      currentBeverageProductId = product.id;
      const typeNames = {
        'soda': 'Soda',
        'jus': 'Jus',
        'jus-frais': 'Jus Frais',
        'smoothie': 'Smoothie',
        'cocktail-maison': 'Cocktail Maison'
      };
      $("#currentBeverageType").textContent = typeNames[product.id] || product.name;
      renderBeverageOptionsList();
      openModal("#modalManageBeverage");
    }

    function renderBeverageOptionsList() {
      const container = $("#beverageOptionsList");
      const beverageProduct = state.menu?.menu?.categories?.flatMap(c => c.items || []).find(p => p.id === currentBeverageProductId);
      const options = beverageProduct?.beverageOptions || [];

      container.innerHTML = "";
      if (options.length === 0) {
        container.innerHTML = '<p class="muted">Aucune option. Ajoutez-en ci-dessus.</p>';
        return;
      }

      options.forEach((opt, idx) => {
        const div = document.createElement("div");
        const status = opt.status ?? 'available';
        const isAvailable = status === 'available';
        div.style.cssText = "display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid var(--stroke);border-radius:8px;background:rgba(255,255,255,.02);";
        div.innerHTML = `
          <div style="flex:1;opacity:${isAvailable ? '1' : '0.6'}">
            <strong style="font-size:13px;">${escapeHtml(opt.name)}</strong>
            ${opt.price ? `<span style="color:var(--muted);margin-left:10px;font-size:12px;">${opt.price} DA</span>` : ''}
          </div>
          <button class="btn btn-sm ${isAvailable ? 'btn-good' : 'btn-ghost'}" data-toggle-status-bev="${idx}" type="button" title="${isAvailable ? 'Mettre indisponible' : 'Mettre disponible'}">${isAvailable ? '✓' : '✕'}</button>
          <button class="btn btn-error btn-sm" data-delete-bev="${idx}" type="button">🗑️</button>
        `;
        container.appendChild(div);
      });

      $$("[data-toggle-status-bev]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const idx = parseInt(btn.dataset.toggleStatusBev);
          const opt = options[idx];
          const currentStatus = opt.status ?? 'available';
          const newStatus = currentStatus === 'available' ? 'unavailable' : 'available';
          try {
            await apiPostJson({ action: "update_beverage_option_status", beverage_type: currentBeverageProductId, index: idx, status: newStatus });
            toast("success", "Statut modifié", `${opt.name} est maintenant ${newStatus === 'available' ? 'disponible' : 'indisponible'}.`);
            state.menu = await apiGet();
            renderBeverageOptionsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de modifier le statut.");
          }
        });
      });

      $$("[data-delete-bev]", container).forEach(btn => {
        btn.addEventListener("click", async () => {
          const idx = parseInt(btn.dataset.deleteBev);
          const opt = options[idx];
          if (!confirm(`Supprimer "${opt.name}" ?`)) return;
          try {
            await apiPostJson({ action: "delete_beverage_option", beverage_type: currentBeverageProductId, index: idx });
            toast("success", "Supprimé", `${opt.name} supprimé.`);
            state.menu = await apiGet();
            renderBeverageOptionsList();
          } catch(err) {
            toast("error", "Erreur", err?.message ?? "Impossible de supprimer.");
          }
        });
      });
    }

    $("#formAddBeverageOption").addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = $("#bevOptionName").value.trim();
      const price = parseFloat($("#bevOptionPrice").value);

      if (!name || isNaN(price)) {
        toast("error", "Erreur", "Nom et prix sont requis.");
        return;
      }

      try {
        await apiPostJson({
          action: "add_beverage_option",
          beverage_type: currentBeverageProductId,
          name,
          price
        });
        toast("success", "Ajouté", `${name} ajouté avec succès.`);
        $("#formAddBeverageOption").reset();
        state.menu = await apiGet();
        renderBeverageOptionsList();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible d'ajouter.");
      }
    });

    // ===== ÉDITION PRODUIT =====
    let currentEditProduct = null;

    function openEditProductModal(product, categoryId){
      currentEditProduct = { ...product, categoryId };
      $("#editProductId").value = product.id;
      $("#editProdName").value = product.name ?? "";
      $("#editProdDesc").value = product.description ?? "";
      $("#editPriceSolo").value = product.priceSolo ?? "";
      $("#editPriceMenu").value = product.priceMenu ?? "";
      $("#editProdBadge").value = product.badge ?? "";
      $("#editStatus").value = product.status ?? "available";

      // Afficher l'image actuelle
      const previewDiv = $("#editProductImagePreview");
      if (product.image) {
        previewDiv.innerHTML = `<img src="../${product.image}" style="max-width:150px;max-height:100px;border-radius:8px;object-fit:cover;">`;
      } else {
        previewDiv.innerHTML = `<span style="color:#6b7280;">Aucune image</span>`;
      }
      // Reset le champ fichier
      $("#editProductImage").value = "";

      // Suppléments : Affichage automatique selon le flavor (plus de checkboxes)
      // Les suppléments s'affichent automatiquement côté frontend

      openModal("#modalEditProduct");
    }

    $("#formEditProduct").addEventListener("submit", async (e) => {
      e.preventDefault();
      const productId = $("#editProductId").value;
      const name = $("#editProdName").value.trim();
      const description = $("#editProdDesc").value;
      const priceSolo = parseFloat($("#editPriceSolo").value) || 0;
      const priceMenu = $("#editPriceMenu").value ? parseFloat($("#editPriceMenu").value) : null;
      const badge = $("#editProdBadge").value.trim() || null;
      const status = $("#editStatus").value;
      const imageFile = $("#editProductImage").files[0];

      // Suppléments : Affichage automatique selon le flavor (pas de sélection manuelle)
      const supplements = []; // Vide car géré automatiquement côté frontend

      try {
        // Si une image est sélectionnée, utiliser FormData
        if (imageFile) {
          const formData = new FormData();
          formData.set("product_id", productId);
          formData.set("name", name);
          formData.set("description", description);
          formData.set("priceSolo", priceSolo);
          if (priceMenu !== null) formData.set("priceMenu", priceMenu);
          if (badge) formData.set("badge", badge);
          formData.set("status", status);
          formData.set("supplements", JSON.stringify(supplements));
          formData.set("image", imageFile);

          await apiPostMultipart(formData, "update_product");
        } else {
          await apiPostJson({
            action: "update_product",
            product_id: productId,
            name,
            description,
            priceSolo,
            priceMenu,
            badge,
            status,
            supplements
          });
        }
        toast("success", "Produit modifié", `"${name}" a été mis à jour.`);
        closeModal($("#modalEditProduct"));
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible de modifier.");
      }
    });

    $("#btnDeleteProduct").addEventListener("click", async () => {
      if (!currentEditProduct) return;
      if (!confirm(`Supprimer "${currentEditProduct.name}" ?`)) return;

      try {
        await apiPostJson({ action: "delete_product", product_id: currentEditProduct.id });
        toast("success", "Supprimé", `"${currentEditProduct.name}" a été supprimé.`);
        closeModal($("#modalEditProduct"));
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible de supprimer.");
      }
    });

    // ===== API =====
    function getErrorMessage(res, data) {
      if (data?.message) return data.message;
      if (res.status === 401) return "Session expirée. Veuillez vous reconnecter.";
      if (res.status === 403) return "Accès refusé. Token CSRF invalide.";
      if (res.status === 404) return "Ressource introuvable.";
      if (res.status >= 500) return "Erreur serveur. Réessayez plus tard.";
      if (!navigator.onLine) return "Pas de connexion internet.";
      return "Erreur de communication avec le serveur.";
    }

    async function apiGet(){
      const res = await fetch(API, { method: "GET" });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(getErrorMessage(res, data));
      return data;
    }

    async function apiPostJson(payload){
      payload.csrf_token = CSRF_TOKEN;
      const res = await fetch(API, {
        method: "POST",
        headers: {"Content-Type":"application/json", "X-CSRF-Token": CSRF_TOKEN},
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(getErrorMessage(res, data));
      return data;
    }

    async function apiPostMultipart(formData, action = null){
      if (action) formData.set("action", action);
      formData.set("csrf_token", CSRF_TOKEN);
      const res = await fetch(API, { method:"POST", body: formData });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(getErrorMessage(res, data));
      return data;
    }

    function money(v){
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";
      return n.toFixed(0) + " DA";
    }

    function getCategories(){
      return state.menu?.menu?.categories ?? [];
    }

    function selectCategory(id){
      state.selectedCategoryId = id;
      render();
    }

    function renderCategories(){
      const list = $("#categories");
      const cats = getCategories();
      const q = state.catQuery.trim().toLowerCase();

      const filtered = cats.filter(c =>
        !q || (String(c.name??"").toLowerCase().includes(q) || String(c.description??"").toLowerCase().includes(q))
      );

      $("#catMeta").textContent = `${filtered.length} catégorie(s)`;

      list.innerHTML = "";
      filtered.forEach(c=>{
        const itemsCount = Array.isArray(c.items) ? c.items.length : 0;
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "cat";
        btn.setAttribute("aria-selected", String(c.id === state.selectedCategoryId));
        btn.innerHTML = `
          <div style="min-width:0;text-align:left">
            <strong>${escapeHtml(c.name ?? "")}</strong>
            <small>${escapeHtml(c.description ?? "")}</small>
          </div>
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="badge">${itemsCount}</span>
            <button type="button" class="btn-icon-sm" onclick="event.stopPropagation(); openEditCategoryModal('${escapeHtml(c.id)}')" title="Modifier" aria-label="Modifier ${escapeHtml(c.name??'')}">
              <i class="fas fa-pen"></i>
            </button>
            <button type="button" class="btn-icon-sm btn-danger" onclick="event.stopPropagation(); deleteCategory('${escapeHtml(c.id)}')" title="Supprimer" aria-label="Supprimer ${escapeHtml(c.name??'')}">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        `;
        btn.addEventListener("click", ()=>selectCategory(c.id));
        list.appendChild(btn);
      });

      if (!state.selectedCategoryId && filtered[0]?.id) {
        state.selectedCategoryId = filtered[0].id;
        renderProducts();
        $$(".cat", list).forEach(el=>{
          el.setAttribute("aria-selected","false");
        });
      }
    }

    function renderProducts(){
      const body = $("#productsBody");
      const empty = $("#emptyState");
      const cats = getCategories();
      const cat = cats.find(c => c.id === state.selectedCategoryId) ?? cats[0] ?? null;

      if (!cat) {
        $("#productsTitle").textContent = "Produits";
        $("#prodMeta").textContent = "—";
        body.innerHTML = "";
        empty.style.display = "block";
        return;
      }

      $("#productsTitle").textContent = `Produits • ${cat.name}`;
      const items = Array.isArray(cat.items) ? cat.items : [];
      const q = state.prodQuery.trim().toLowerCase();
      const filtered = items.filter(it =>
        !q || String(it.name ?? "").toLowerCase().includes(q) || String(it.description ?? "").toLowerCase().includes(q)
      );
      $("#prodMeta").textContent = `${filtered.length} produit(s)`;

      body.innerHTML = "";
      filtered.forEach(it=>{
        const tr = document.createElement("tr");
        tr.className = "row";
        tr.style.cursor = "pointer";
        let img = it.image ? String(it.image) : "";
        // Préfixer avec ../ pour remonter à la racine du site
        if (img && !img.startsWith('http') && !img.startsWith('../')) {
          img = '../' + img;
        }

        tr.innerHTML = `
          <td style="width:56px">
            ${img ? `<img class="thumb" src="${escapeHtml(img)}" alt="${escapeHtml(it.name)}">` : `<div class="thumb"></div>`}
          </td>
          <td>
            <div style="display:flex;flex-direction:column;gap:2px">
              <strong style="font-size:13px">${escapeHtml(it.name ?? "")}</strong>
              <span class="muted">${escapeHtml(it.description ?? "")}</span>
            </div>
          </td>
          <td class="price" style="width:140px">
            <div><span class="muted">Solo</span> ${money(it.priceSolo)}</div>
            <div><span class="muted">Menu</span> ${it.priceMenu ? money(it.priceMenu) : "—"}</div>
          </td>
          <td style="width:160px">
            <span class="status" data-status="${escapeHtml(it.status ?? "available")}">
              <span class="dot"></span>${(it.status ?? "available")==="available" ? "Disponible" : "Indisponible"}
            </span>
          </td>
        `;
        tr.addEventListener("click", () => {
          // Detect special products that need options management instead of regular edit
          if (it.id === 'patisserie') {
            openManagePatisserieModal(it);
          } else if (['soda', 'jus', 'jus-frais', 'smoothie', 'cocktail-maison'].includes(it.id)) {
            openManageBeverageModal(it);
          } else {
            openEditProductModal(it, cat.id);
          }
        });
        body.appendChild(tr);
      });

      empty.style.display = filtered.length ? "none" : "block";
    }

    function renderProductCategorySelect(){
      const select = $("#prodCategory");
      const cats = getCategories();
      select.innerHTML = "";
      cats.forEach(c=>{
        const opt = document.createElement("option");
        opt.value = c.id;
        opt.textContent = c.name;
        select.appendChild(opt);
      });
      if (state.selectedCategoryId) select.value = state.selectedCategoryId;
    }

    function render(){
      renderCategories();
      renderProductCategorySelect();
      renderProducts();
    }

    $("#catSearch").addEventListener("input", (e)=>{ state.catQuery = e.target.value; renderCategories(); });
    $("#prodSearch").addEventListener("input", (e)=>{ state.prodQuery = e.target.value; renderProducts(); });

    $("#formCategory").addEventListener("submit", async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.target);
      const name = String(fd.get("name") ?? "").trim();
      const description = String(fd.get("description") ?? "").trim();
      const icon = String(fd.get("icon") ?? "fa-utensils").trim();

      try{
        if (currentEditCategoryId) {
          // Mode édition
          await apiPostJson({ action:"edit_category", category_id: currentEditCategoryId, name, description, icon });
          toast("success","Catégorie modifiée", `"${name}" a été mise à jour.`);
        } else {
          // Mode ajout
          await apiPostJson({ action:"add_category", name, description, icon });
          toast("success","Catégorie ajoutée", `"${name}" a été enregistrée.`);
        }
        closeModal($("#modalCategory"));
        await boot();
      }catch(err){
        const action = currentEditCategoryId ? "modifier" : "ajouter";
        toast("error","Erreur", err?.message ?? `Impossible de ${action} la catégorie.`);
      }
    });

    $("#formProduct").addEventListener("submit", async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.target);

      // Suppléments : Affichage automatique selon le flavor (pas de sélection manuelle)
      fd.set("supplements", JSON.stringify([]));

      try{
        await apiPostMultipart(fd, "add_product");
        toast("success","Produit ajouté","Le produit a été enregistré avec sa photo.");
        closeModal($("#modalProduct"));
        await boot();
      }catch(err){
        toast("error","Erreur", err?.message ?? "Impossible d'ajouter le produit.");
      }
    });

    // ===== GESTION DES FORMULES =====
    let currentEditFormule = null;
    let formuleIncludesCount = 0;

    function getFormules(){
      return state.menu?.formules ?? [];
    }

    function openFormuleModal(formule = null){
      currentEditFormule = formule;
      const form = $("#formFormule");
      form.reset();
      formuleIncludesCount = 0;
      $("#formuleIncludes").innerHTML = "";
      $("#formuleImgPreview").style.display = "none";

      if (formule) {
        // Mode édition
        $("#modalFormuleTitle").textContent = "Modifier la formule";
        $("#formuleId").value = formule.id;
        $("#formuleName").value = formule.name ?? "";
        $("#formuleDesc").value = formule.description ?? "";
        $("#formulePrice").value = formule.price ?? "";
        $("#formuleOriginalPrice").value = formule.originalPrice ?? "";
        $("#formuleBadge").value = formule.badge ?? "";
        $("#formuleStatus").value = formule.status ?? "available";
        $("#btnDeleteFormule").style.display = "block";

        // Afficher l'image existante
        if (formule.image) {
          $("#formuleImgPreview").src = "../" + formule.image;
          $("#formuleImgPreview").style.display = "block";
        }

        // Ajouter les includes existants
        (formule.includes ?? []).forEach(inc => addFormuleInclude(inc));
      } else {
        // Mode ajout
        $("#modalFormuleTitle").textContent = "Ajouter une formule";
        $("#formuleId").value = "";
        $("#btnDeleteFormule").style.display = "none";
        // Ajouter un include vide par défaut
        addFormuleInclude();
      }

      openModal("#modalFormule");
    }

    function addFormuleInclude(data = null){
      const container = $("#formuleIncludes");
      const cats = getCategories();
      const idx = formuleIncludesCount++;

      const div = document.createElement("div");
      div.className = "formule-include-row";
      div.style.cssText = "display:flex;gap:8px;align-items:center;padding:10px;border:1px solid var(--stroke);border-radius:10px;background:rgba(0,0,0,.18);";

      const typeVal = data?.type ?? "category";
      const labelVal = data?.label ?? "";
      const qtyVal = data?.quantity ?? 1;
      const catIdVal = data?.categoryId ?? "";
      const prodIdVal = data?.productId ?? "";

      div.innerHTML = `
        <select name="include_type_${idx}" class="select" style="width:110px;" onchange="toggleIncludeType(this, ${idx})">
          <option value="category" ${typeVal === 'category' ? 'selected' : ''}>Catégorie</option>
          <option value="product" ${typeVal === 'product' ? 'selected' : ''}>Produit</option>
        </select>
        <select name="include_cat_${idx}" class="select include-cat" style="width:140px;${typeVal === 'product' ? 'display:none;' : ''}">
          <option value="">-- Catégorie --</option>
          ${cats.map(c => `<option value="${escapeHtml(c.id)}" ${c.id === catIdVal ? 'selected' : ''}>${escapeHtml(c.name)}</option>`).join('')}
        </select>
        <input name="include_prod_${idx}" class="input include-prod" type="text" placeholder="ID produit" value="${escapeHtml(prodIdVal)}" style="width:140px;${typeVal === 'category' ? 'display:none;' : ''}" />
        <input name="include_label_${idx}" class="input" type="text" placeholder="Label affiché" value="${escapeHtml(labelVal)}" style="flex:1;min-width:100px;" />
        <input name="include_qty_${idx}" class="input" type="number" min="1" value="${qtyVal}" style="width:60px;" />
        <button type="button" class="btn btn-danger" style="padding:6px 10px;" onclick="this.closest('.formule-include-row').remove()">✕</button>
      `;

      container.appendChild(div);
    }

    window.toggleIncludeType = function(select, idx) {
      const row = select.closest('.formule-include-row');
      const catSelect = row.querySelector('.include-cat');
      const prodInput = row.querySelector('.include-prod');
      if (select.value === 'category') {
        catSelect.style.display = '';
        prodInput.style.display = 'none';
      } else {
        catSelect.style.display = 'none';
        prodInput.style.display = '';
      }
    };

    $("#btnAddFormule").addEventListener("click", () => openFormuleModal());
    $("#btnAddFormuleInline").addEventListener("click", () => openFormuleModal());
    $("#btnAddInclude").addEventListener("click", () => addFormuleInclude());

    $("#formuleImage").addEventListener("change", (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      $("#formuleImgPreview").src = url;
      $("#formuleImgPreview").style.display = "block";
    });

    $("#formFormule").addEventListener("submit", async (e) => {
      e.preventDefault();

      const formuleId = $("#formuleId").value || null;
      const name = $("#formuleName").value.trim();
      const description = $("#formuleDesc").value.trim();
      const price = parseFloat($("#formulePrice").value) || 0;
      const originalPrice = $("#formuleOriginalPrice").value ? parseFloat($("#formuleOriginalPrice").value) : null;
      const badge = $("#formuleBadge").value.trim() || null;
      const status = $("#formuleStatus").value;
      const imageFile = $("#formuleImage").files[0];

      // Collecter les includes
      const includes = [];
      $$(".formule-include-row").forEach((row, idx) => {
        const type = row.querySelector(`[name^="include_type_"]`).value;
        const label = row.querySelector(`[name^="include_label_"]`).value.trim();
        const qty = parseInt(row.querySelector(`[name^="include_qty_"]`).value) || 1;

        if (!label) return;

        const inc = { type, label, quantity: qty };
        if (type === 'category') {
          inc.categoryId = row.querySelector('.include-cat').value;
        } else {
          inc.productId = row.querySelector('.include-prod').value.trim();
        }
        includes.push(inc);
      });

      try {
        const formData = new FormData();
        if (formuleId) formData.set("formule_id", formuleId);
        formData.set("name", name);
        formData.set("description", description);
        formData.set("price", price);
        if (originalPrice !== null) formData.set("originalPrice", originalPrice);
        if (badge) formData.set("badge", badge);
        formData.set("status", status);
        formData.set("includes", JSON.stringify(includes));
        if (imageFile) formData.set("image", imageFile);

        await apiPostMultipart(formData, formuleId ? "update_formule" : "add_formule");

        toast("success", formuleId ? "Formule modifiée" : "Formule ajoutée", `"${name}" a été enregistrée.`);
        closeModal($("#modalFormule"));
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible d'enregistrer.");
      }
    });

    $("#btnDeleteFormule").addEventListener("click", async () => {
      if (!currentEditFormule) return;
      if (!confirm(`Supprimer la formule "${currentEditFormule.name}" ?`)) return;

      try {
        await apiPostJson({ action: "delete_formule", formule_id: currentEditFormule.id });
        toast("success", "Supprimée", `"${currentEditFormule.name}" a été supprimée.`);
        closeModal($("#modalFormule"));
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible de supprimer.");
      }
    });

    function renderFormules(){
      const grid = $("#formulesGrid");
      const empty = $("#formulesEmpty");
      const formules = getFormules();

      $("#formulesMeta").textContent = `${formules.length} formule(s)`;

      grid.innerHTML = "";

      if (formules.length === 0) {
        empty.style.display = "block";
        return;
      }
      empty.style.display = "none";

      formules.forEach(f => {
        const card = document.createElement("div");
        card.style.cssText = "display:flex;gap:12px;padding:12px;border:1px solid var(--stroke);border-radius:14px;background:rgba(255,255,255,.04);cursor:pointer;transition:background .12s ease;";
        card.onmouseenter = () => card.style.background = "rgba(255,255,255,.08)";
        card.onmouseleave = () => card.style.background = "rgba(255,255,255,.04)";

        let imgSrc = f.image ? "../" + f.image : "";

        card.innerHTML = `
          <div style="width:80px;height:80px;flex-shrink:0;border-radius:12px;overflow:hidden;border:1px solid var(--stroke);background:rgba(0,0,0,.18);">
            ${imgSrc ? `<img src="${escapeHtml(imgSrc)}" style="width:100%;height:100%;object-fit:cover;" alt="${escapeHtml(f.name)}">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);">📦</div>'}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
              <strong style="font-size:14px;">${escapeHtml(f.name)}</strong>
              ${f.badge ? `<span style="font-size:11px;padding:2px 8px;border-radius:999px;background:rgba(168,85,247,.2);color:#c084fc;">${escapeHtml(f.badge)}</span>` : ''}
            </div>
            <p style="margin:0 0 6px;color:var(--muted);font-size:12px;line-height:1.3;">${escapeHtml(f.description ?? '')}</p>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="font-size:15px;font-weight:600;">${f.price?.toFixed(0) ?? '—'} DA</span>
              ${f.originalPrice ? `<span style="color:var(--muted);text-decoration:line-through;font-size:12px;">${f.originalPrice.toFixed(0)} DA</span>` : ''}
              <span class="status" data-status="${f.status ?? 'available'}" style="margin-left:auto;padding:4px 8px;">
                <span class="dot"></span>${(f.status ?? 'available') === 'available' ? 'Dispo' : 'Indispo'}
              </span>
            </div>
          </div>
        `;

        card.addEventListener("click", () => openFormuleModal(f));
        grid.appendChild(card);
      });
    }

    // ============================================
    // FEATURED PRODUCTS MANAGEMENT
    // ============================================

    let featuredItems = [];

    function getAllProducts() {
      const categories = getCategories();
      const products = [];
      categories.forEach(cat => {
        (cat.items || []).forEach(item => {
          products.push({ ...item, categoryId: cat.id, categoryName: cat.name });
        });
      });
      return products;
    }

    function getFeaturedConfig() {
      return state.menu?.featured || { enabled: true, title: 'Sélection pour vous', subtitle: 'Nos produits les plus appréciés', items: [] };
    }

    function renderFeatured() {
      const grid = $("#featuredGrid");
      const empty = $("#featuredEmpty");
      const featured = getFeaturedConfig();

      // Set inputs
      $("#featuredTitle").value = featured.title || 'Sélection pour vous';
      $("#featuredSubtitle").value = featured.subtitle || 'Nos produits les plus appréciés';

      featuredItems = featured.items || [];

      grid.innerHTML = "";

      if (featuredItems.length === 0) {
        empty.style.display = "block";
        return;
      }
      empty.style.display = "none";

      const allProducts = getAllProducts();

      featuredItems.forEach((productId, index) => {
        const product = allProducts.find(p => p.id === productId);
        if (!product) return;

        const card = document.createElement("div");
        card.style.cssText = "display:flex;gap:10px;padding:10px;border:1px solid var(--stroke);border-radius:12px;background:rgba(255,255,255,.04);position:relative;";

        let imgSrc = product.image ? "../" + product.image : "";

        card.innerHTML = `
          <div style="width:50px;height:50px;flex-shrink:0;border-radius:8px;overflow:hidden;background:rgba(0,0,0,.18);">
            ${imgSrc ? `<img src="${escapeHtml(imgSrc)}" style="width:100%;height:100%;object-fit:cover;">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);">🍔</div>'}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:600;">${escapeHtml(product.name)}</div>
            <div style="font-size:11px;color:var(--muted);">${escapeHtml(product.categoryName || '')}</div>
            <div style="font-size:12px;font-weight:600;color:var(--brand);margin-top:2px;">${(product.priceSolo || product.price || 0).toFixed(0)} DA</div>
          </div>
          <button type="button" class="btn btn-danger" style="padding:6px 10px;font-size:11px;position:absolute;top:6px;right:6px;" data-remove="${index}">✕</button>
        `;

        card.querySelector('[data-remove]').addEventListener('click', () => {
          featuredItems.splice(index, 1);
          renderFeatured();
        });

        grid.appendChild(card);
      });
    }

    function renderFeaturedModal() {
      const list = $("#featuredProductsList");
      const search = $("#featuredSearch").value.toLowerCase();
      const allProducts = getAllProducts();

      const filtered = allProducts.filter(p =>
        p.name.toLowerCase().includes(search) &&
        !featuredItems.includes(p.id)
      );

      list.innerHTML = "";

      if (filtered.length === 0) {
        list.innerHTML = '<div class="muted" style="padding:20px;text-align:center;">Aucun produit disponible</div>';
        return;
      }

      filtered.forEach(product => {
        const item = document.createElement("div");
        item.style.cssText = "display:flex;gap:10px;align-items:center;padding:10px;border:1px solid var(--stroke);border-radius:10px;cursor:pointer;transition:background .12s;";
        item.onmouseenter = () => item.style.background = "rgba(255,255,255,.06)";
        item.onmouseleave = () => item.style.background = "";

        let imgSrc = product.image ? "../" + product.image : "";

        item.innerHTML = `
          <div style="width:40px;height:40px;flex-shrink:0;border-radius:8px;overflow:hidden;background:rgba(0,0,0,.18);">
            ${imgSrc ? `<img src="${escapeHtml(imgSrc)}" style="width:100%;height:100%;object-fit:cover;">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);">🍔</div>'}
          </div>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:600;">${escapeHtml(product.name)}</div>
            <div style="font-size:11px;color:var(--muted);">${escapeHtml(product.categoryName || '')}</div>
          </div>
          <div style="font-size:13px;font-weight:600;color:var(--brand);">${(product.priceSolo || product.price || 0).toFixed(0)} DA</div>
          <button type="button" class="btn btn-good" style="padding:6px 12px;font-size:11px;">+ Ajouter</button>
        `;

        item.querySelector('button').addEventListener('click', (e) => {
          e.stopPropagation();
          featuredItems.push(product.id);
          renderFeatured();
          renderFeaturedModal();
          toast("success", "Ajouté", `"${product.name}" ajouté à la sélection.`);
        });

        list.appendChild(item);
      });
    }

    $("#btnAddFeatured").addEventListener("click", () => {
      renderFeaturedModal();
      openModal("#modalFeatured");
    });

    $("#featuredSearch").addEventListener("input", () => {
      renderFeaturedModal();
    });

    $("#btnSaveFeatured").addEventListener("click", async () => {
      const title = $("#featuredTitle").value.trim() || 'Sélection pour vous';
      const subtitle = $("#featuredSubtitle").value.trim() || 'Nos produits les plus appréciés';

      try {
        await apiPostJson({
          action: "update_featured",
          featured: {
            enabled: true,
            title,
            subtitle,
            items: featuredItems
          }
        });
        toast("success", "Sauvegardé", "La sélection a été mise à jour.");
        await boot();
      } catch(err) {
        toast("error", "Erreur", err?.message ?? "Impossible de sauvegarder.");
      }
    });

    async function boot(){
      const data = await apiGet();
      state.menu = data;
      const cats = getCategories();
      if (!cats.find(c=>c.id===state.selectedCategoryId)) {
        state.selectedCategoryId = cats[0]?.id ?? null;
      }
      render();
      renderFormules();
      renderFeatured();
    }

    boot().catch((err)=>{
      toast("error","Chargement impossible", err?.message ?? "API indisponible.");
      $("#catMeta").textContent = "Erreur";
    });
  </script>
</body>
</html>
