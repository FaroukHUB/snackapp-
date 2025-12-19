<?php
// products-manager.php
session_start();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Produits</title>

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
      background: rgba(255,255,255,.06);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      cursor:pointer;
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
      display:inline-flex;align-items:center;gap:8px;
      user-select:none;
    }
    .btn:hover{transform: translateY(-1px);background: rgba(255,255,255,.10)}
    .btn:active{transform: translateY(0)}
    .btn-primary{background: rgba(96,165,250,.14); border-color: rgba(96,165,250,.35)}
    .btn-primary:hover{background: rgba(96,165,250,.22)}
    .btn-good{background: rgba(22,163,74,.14); border-color: rgba(22,163,74,.35)}
    .btn-good:hover{background: rgba(22,163,74,.22)}
    .btn-ghost{background: transparent}
    .btn-danger{background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.32)}
    .btn-danger:hover{background: rgba(239,68,68,.18)}

    .grid{
      display:grid;
      grid-template-columns: 320px 1fr;
      gap:14px;
    }
    @media (max-width: 920px){
      .grid{grid-template-columns:1fr}
    }

    .panel{
      background: rgba(255,255,255,.04);
      border:1px solid var(--stroke);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .panel-h{
      padding:14px 14px 12px;
      border-bottom:1px solid var(--stroke);
      display:flex;align-items:center;justify-content:space-between;gap:10px
    }
    .panel-h h2{margin:0;font-size:14px}
    .panel-h .meta{color:var(--muted);font-size:12px}
    .panel-b{padding:12px}

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
      background: rgba(255,255,255,.04);
      border-radius:14px;
      padding:10px 10px;
      display:flex;align-items:flex-start;justify-content:space-between;gap:8px;
      cursor:pointer;
      transition: background .12s ease, border-color .12s ease, transform .12s ease;
    }
    .cat:hover{background: rgba(255,255,255,.08); transform: translateY(-1px)}
    .cat[aria-selected="true"]{
      border-color: rgba(96,165,250,.45);
      background: rgba(96,165,250,.14);
    }
    .cat strong{display:block;font-size:13px}
    .cat small{display:block;color:var(--muted);font-size:12px;margin-top:2px;line-height:1.25}
    .badge{
      font-size:12px;
      color:var(--muted);
      padding:6px 8px;
      border:1px solid var(--stroke);
      border-radius:999px;
      background: rgba(0,0,0,.18);
      white-space:nowrap;
      height:fit-content;
    }

    .table{
      width:100%;
      border-collapse:separate;
      border-spacing:0 10px;
    }
    .row{
      background: rgba(255,255,255,.04);
      border:1px solid var(--stroke);
      border-radius:14px;
    }
    .row td{padding:10px 10px;vertical-align:middle}
    .row td:first-child{border-top-left-radius:14px;border-bottom-left-radius:14px}
    .row td:last-child{border-top-right-radius:14px;border-bottom-right-radius:14px}
    .muted{color:var(--muted);font-size:12px}
    .price{font-variant-numeric: tabular-nums}
    .thumb{
      width:42px;height:42px;border-radius:12px;
      border:1px solid var(--stroke);
      background: rgba(0,0,0,.18);
      object-fit:cover;
      display:block;
    }
    .status{
      display:inline-flex;align-items:center;gap:6px;
      padding:6px 10px;border-radius:999px;
      border:1px solid var(--stroke);
      background: rgba(0,0,0,.18);
      font-size:12px;color:var(--muted);
    }
    .dot{width:8px;height:8px;border-radius:999px;background: var(--muted)}
    .status[data-status="available"] .dot{background: var(--good)}
    .status[data-status="unavailable"] .dot{background: var(--bad)}

    /* ===== Modal moderne ===== */
    .modal-overlay{
      position:fixed;inset:0;
      display:none;
      align-items:center;justify-content:center;
      padding:16px;
      background: rgba(0,0,0,.62);
      backdrop-filter: blur(8px);
      z-index: 1000;
    }
    .modal-overlay[aria-hidden="false"]{display:flex}
    .modal{
      width:100%;
      max-width: 520px;
      border-radius: 18px;
      border:1px solid rgba(255,255,255,.14);
      background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
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
    .modal-h{padding:14px 14px 12px;border-bottom:1px solid rgba(255,255,255,.12);display:flex;align-items:center;justify-content:space-between;gap:10px}
    .modal-h h3{margin:0;font-size:14px}
    .modal-b{padding:14px}
    .modal-f{padding:14px;border-top:1px solid rgba(255,255,255,.12);display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}
    .field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
    .field label{font-size:12px;color:var(--muted)}
    .input, .textarea, .select{
      width:100%;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.20);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      outline:none;
    }
    .textarea{min-height:88px;resize:vertical}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media (max-width: 520px){.two{grid-template-columns:1fr}}
    .upload{
      display:flex;align-items:center;gap:12px;flex-wrap:wrap;
      padding:12px;border:1px dashed rgba(255,255,255,.20);
      border-radius:14px;background: rgba(0,0,0,.16);
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
      .modal { max-width: calc(100vw - 24px); margin: 12px; border-radius: 14px; }
      .modal-h, .modal-b, .modal-f { padding: 12px; }
      .two { grid-template-columns: 1fr; gap: 12px; }
      .field label { font-size: 13px; }
      .input, .textarea, .select { padding: 12px; font-size: 16px; }
      .upload { flex-direction: column; align-items: flex-start; }
      .preview { width: 70px; height: 70px; }
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
      .modal-overlay { padding: 8px; }
      .modal { border-radius: 12px; }
      .modal-h h3 { font-size: 14px; }
      .modal-f { flex-direction: column; }
      .modal-f .btn { width: 100%; justify-content: center; }
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
          <p class="muted" style="margin:0">L’identifiant technique est généré automatiquement.</p>
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
            <label>Photo</label>
            <div class="upload">
              <img id="imgPreview" class="preview" alt="Aperçu" />
              <div style="display:flex;flex-direction:column;gap:8px">
                <input id="imageFile" name="imageFile" type="file" accept="image/png,image/jpeg,image/webp" required />
                <span class="muted">Formats: jpg / png / webp • conseillé: 800×800</span>
              </div>
            </div>
          </div>

          <div class="field">
            <label>Suppléments disponibles pour ce produit</label>
            <div id="productSupplementsList" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;"></div>
            <span class="muted" style="margin-top:4px;display:block;">Cochez les suppléments que le client pourra ajouter</span>
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
          <input id="supPrice" name="price" class="input" type="number" step="0.01" min="0" placeholder="Prix €" style="width:90px;" required />
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
            <label for="editStatus">Statut</label>
            <select id="editStatus" name="status" class="select">
              <option value="available">Disponible</option>
              <option value="unavailable">Indisponible</option>
            </select>
          </div>
          <div class="field">
            <label>Suppléments</label>
            <div id="editProductSupplementsList" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;"></div>
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

    $("#btnAddCategory").addEventListener("click", () => {
      $("#formCategory").reset();
      openModal("#modalCategory");
    });

    function getSupplements(){
      return state.menu?.supplements?.catalog ?? {};
    }

    function renderSupplementsCheckboxes(containerId, selectedIds = []){
      const container = $(containerId);
      const supplements = getSupplements();
      container.innerHTML = "";

      Object.values(supplements).forEach(sup => {
        const label = document.createElement("label");
        label.style.cssText = "display:flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--stroke);border-radius:10px;cursor:pointer;background:rgba(0,0,0,.18);font-size:13px;";
        const checked = selectedIds.includes(sup.id) ? "checked" : "";
        label.innerHTML = `
          <input type="checkbox" name="supplements[]" value="${escapeHtml(sup.id)}" ${checked} style="width:16px;height:16px;" />
          ${escapeHtml(sup.name)} <span style="color:var(--muted);">(+${sup.price.toFixed(2)}€)</span>
        `;
        container.appendChild(label);
      });

      if (Object.keys(supplements).length === 0) {
        container.innerHTML = '<span class="muted">Aucun supplément. Ajoutez-en via le bouton "Suppléments".</span>';
      }
    }

    function openProductModal(prefCatId=null){
      $("#formProduct").reset();
      $("#imgPreview").src = "";
      if (prefCatId) $("#prodCategory").value = prefCatId;

      // Récupérer les suppléments par défaut de la catégorie
      const catId = prefCatId || state.selectedCategoryId;
      const defaultSups = state.menu?.supplements?.defaultForCategories?.[catId] ?? [];
      renderSupplementsCheckboxes("#productSupplementsList", defaultSups);

      openModal("#modalProduct");
    }

    $("#btnAddProduct").addEventListener("click", () => openProductModal(state.selectedCategoryId));
    $("#btnAddProductInline").addEventListener("click", () => openProductModal(state.selectedCategoryId));

    // Quand on change de catégorie dans le select, mettre à jour les suppléments par défaut
    $("#prodCategory").addEventListener("change", (e) => {
      const catId = e.target.value;
      const defaultSups = state.menu?.supplements?.defaultForCategories?.[catId] ?? [];
      renderSupplementsCheckboxes("#productSupplementsList", defaultSups);
    });

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

      Object.values(supplements).forEach(sup => {
        const div = document.createElement("div");
        div.style.cssText = "display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--stroke);border-radius:12px;background:rgba(255,255,255,.04);";
        div.innerHTML = `
          <div style="flex:1;">
            <strong style="font-size:13px;">${escapeHtml(sup.name)}</strong>
            <span style="color:var(--muted);margin-left:8px;">${sup.price.toFixed(2)}€</span>
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
        container.appendChild(div);
      });

      if (Object.keys(supplements).length === 0) {
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

    $("#formAddSupplement").addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = $("#supName").value.trim();
      const price = parseFloat($("#supPrice").value) || 0;

      if (!name) {
        toast("error", "Erreur", "Nom requis");
        return;
      }

      try {
        await apiPostJson({ action: "add_supplement", name, price });
        toast("success", "Supplément ajouté", `"${name}" a été créé.`);
        $("#formAddSupplement").reset();
        await boot();
        renderSupplementsList();
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
      $("#editStatus").value = product.status ?? "available";

      // Suppléments du produit (ou par défaut de la catégorie)
      const productSups = product.supplements ?? state.menu?.supplements?.defaultForCategories?.[categoryId] ?? [];
      renderSupplementsCheckboxes("#editProductSupplementsList", productSups);

      openModal("#modalEditProduct");
    }

    $("#formEditProduct").addEventListener("submit", async (e) => {
      e.preventDefault();
      const productId = $("#editProductId").value;
      const name = $("#editProdName").value.trim();
      const description = $("#editProdDesc").value;
      const priceSolo = parseFloat($("#editPriceSolo").value) || 0;
      const priceMenu = $("#editPriceMenu").value ? parseFloat($("#editPriceMenu").value) : null;
      const status = $("#editStatus").value;

      // Récupérer les suppléments cochés
      const supplements = [];
      $$("#editProductSupplementsList input[type='checkbox']:checked").forEach(cb => {
        supplements.push(cb.value);
      });

      try {
        await apiPostJson({
          action: "update_product",
          product_id: productId,
          name,
          description,
          priceSolo,
          priceMenu,
          status,
          supplements
        });
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
    async function apiGet(){
      const res = await fetch(API, { method: "GET" });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(data?.message ?? "Erreur API");
      return data;
    }

    async function apiPostJson(payload){
      const res = await fetch(API, {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(data?.message ?? "Erreur API");
      return data;
    }

    async function apiPostMultipart(formData){
      formData.set("action", "add_product");
      const res = await fetch(API, { method:"POST", body: formData });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true) throw new Error(data?.message ?? "Erreur API");
      return data;
    }

    function money(v){
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";
      return n.toFixed(2).replace(".", ",") + " €";
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
          <span class="badge">${itemsCount}</span>
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
// Préfixer avec .. si le chemin commence par / pour remonter à la racine
if (img && img.startsWith('/')) {
  img = '..' + img;
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
        tr.addEventListener("click", () => openEditProductModal(it, cat.id));
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

      try{
        await apiPostJson({ action:"add_category", name, description });
        toast("success","Catégorie ajoutée", `"${name}" a été enregistrée.`);
        closeModal($("#modalCategory"));
        await boot();
      }catch(err){
        toast("error","Erreur", err?.message ?? "Impossible d'ajouter la catégorie.");
      }
    });

    $("#formProduct").addEventListener("submit", async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.target);

      // Ajouter les suppléments sélectionnés
      const supplements = [];
      $$("#productSupplementsList input[type='checkbox']:checked").forEach(cb => {
        supplements.push(cb.value);
      });
      fd.set("supplements", JSON.stringify(supplements));

      try{
        await apiPostMultipart(fd);
        toast("success","Produit ajouté","Le produit a été enregistré avec sa photo.");
        closeModal($("#modalProduct"));
        await boot();
      }catch(err){
        toast("error","Erreur", err?.message ?? "Impossible d'ajouter le produit.");
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
    }

    boot().catch((err)=>{
      toast("error","Chargement impossible", err?.message ?? "API indisponible.");
      $("#catMeta").textContent = "Erreur";
    });
  </script>
</body>
</html>
