/* ===== Panier unifié — vanilla JS ===== */
"use strict";

/* Helpers */
const $  = (s,c=document)=>c.querySelector(s);
const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));
const RESTO_PHONE = "33757831883"; // WhatsApp du resto sans + ni espaces
const euro = n => (Number(n)||0).toFixed(2).replace(".",",")+"€";
const norm = v => {
  const x = (v ?? "6.90").toString().trim().replace(",",".");
  return /^\d+(\.\d+)?$/.test(x) ? x : "6.90";
};

/* ===== Panier state (in memory only) ===== */
const CartState = {
  items:[], // {id, title, quantity, unitPrice, total, optionsText, category, size}
  addItem(payload){
    const { id, title, quantity, unitPrice, optionsText, category, size } = payload;
    const qty = quantity || 1;
    const existing = this.items.find(
      it => it.id===id && it.optionsText===optionsText && it.size===size
    );
    if (existing){
      existing.quantity += qty;
      existing.total = existing.quantity * existing.unitPrice;
    } else {
      this.items.push({
        id,
        title,
        quantity: qty,
        unitPrice,
        total: qty*unitPrice,
        optionsText: optionsText || "",
        category,
        size
      });
    }
  },
  clear(){
    this.items = [];
  }
};

/* ===== Logique produits (catalogue générique) ===== */
const CATEGORY = {
  BURGER:"burger",
  TACO:"taco",
  GALLETTE:"galette",
  ASSIETTE:"assiette",
  PITA:"pita",
  PANINI:"panini",
  TEXMEX:"texmex",
  DESSERT:"dessert",
  BOISSON:"boisson",
  ENFANT:"enfant"
};

const SIZE = {
  SIMPLE:"simple",
  DOUBLE:"double"
};

function isLargeSize(category, sizeLabel="simple"){
  const size = (sizeLabel||"simple").toLowerCase();
  return (category === CATEGORY.BURGER || category === CATEGORY.TACO) && size === SIZE.DOUBLE;
}

/* ===== Formulaire panier (actions.php) ===== */
function normalizeActionUrl(action){
  if (!action) return "/cart/actions.php";
  try {
    const u = new URL(action, location.href);
    const path = u.pathname.includes("actions.php") ? u.pathname : "/cart/actions.php";
    return path + (u.search || "");
  } catch (e) {
    return "/cart/actions.php";
  }
}

function ensureCartForm(){
  let form = document.querySelector('form[action*="actions.php"]');
  if (!form){
    form = document.createElement("form");
    form.method = "post";
    form.action = "/cart/actions.php";
    form.className = "hidden";
    document.body.appendChild(form);
  } else {
    const original = form.getAttribute("action") || "/cart/actions.php";
    form.action = normalizeActionUrl(original);
  }

  const ensure = (name, value) => {
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input){
      input = document.createElement("input");
      input.type  = "hidden";
      input.name  = name;
      form.appendChild(input);
    }
    if (value !== undefined){
      input.value = value;
    }
    return input;
  };

  ensure("action");
  ensure("product_id");
  ensure("quantity");
  ensure("price");
  ensure("title");
  ensure("options");
  ensure("notes");

  return form;
}

function submitCartAction({ action, product_id, quantity, price, title, options, notes }){
  const form = ensureCartForm();
  form.querySelector('input[name="action"]').value      = action || "add";
  form.querySelector('input[name="product_id"]').value  = product_id || "";
  form.querySelector('input[name="quantity"]').value    = quantity != null ? quantity : 1;
  form.querySelector('input[name="price"]').value       = price != null ? price : "";
  form.querySelector('input[name="title"]').value       = title || "";
  form.querySelector('input[name="options"]').value     = options || "";
  form.querySelector('input[name="notes"]').value       = notes || "";
  form.submit();
}

/* ==== WIRE ADD-TO-CART BUTTONS ==== */
document.addEventListener("DOMContentLoaded", ()=>{
  const addButtons = $$("[data-add-to-cart]");
  addButtons.forEach(btn=>{
    btn.addEventListener("click", (e)=>{
      e.preventDefault();
      const id   = btn.getAttribute("data-id") || "";
      const cat  = btn.getAttribute("data-category") || "";
      const size = (btn.getAttribute("data-size") || "simple").toLowerCase();

      const rawBase = btn.getAttribute("data-price") || btn.getAttribute("data-base-price") || "0";
      const base    = Number(norm(rawBase));
      let finalPrice = base;

      if (isLargeSize(cat, size)){
        const up = btn.getAttribute("data-supplement") || "2.00";
        finalPrice = base + Number(norm(up));
      }

      const title = btn.getAttribute("data-title")
        || btn.closest("[data-title]")?.getAttribute("data-title")
        || "Produit";

      const opt = btn.getAttribute("data-options") || "";
      const qty = Number(btn.getAttribute("data-qty") || 1);

      CartState.addItem({
        id,
        title,
        quantity: qty,
        unitPrice: finalPrice,
        optionsText: opt,
        category: cat,
        size
      });

      submitCartAction({
        action:"add",
        product_id: id,
        quantity: qty,
        price: finalPrice,
        title,
        options: opt,
        notes:""
      });
    });
  });
});

/* ==== WhatsApp CTA ==== */
function openWhatsAppCart(message){
  const phone = RESTO_PHONE || "33700000000";
  const base  = "https://wa.me/"+phone;
  const url   = base + "?text=" + encodeURIComponent(message||"Bonjour, je souhaite passer commande.");
  window.open(url, "_blank");
}

/* ===== Recherche intelligente (smart-search) ===== */
document.addEventListener("DOMContentLoaded", ()=>{
  const form = document.getElementById("smart-search");
  if (!form) return;

  const input         = form.querySelector("input[name='q'], #q");
  const resultsSection= document.getElementById("search-results");
  const resultsTitle  = document.getElementById("search-results-title");
  const resultsTrack  = document.getElementById("search-results-track");
  const resetBtn      = document.getElementById("reset-search");

  if (!input || !resultsSection || !resultsTrack) return;

  function normalise(str){
    return (str||"")
      .toString()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g,"")
      .replace(/[^a-z0-9\s]/g," ")
      .replace(/\s+/g," ")
      .trim();
  }

  function getKeywords(card){
    const data = card.getAttribute("data-keywords") || "";
    const title= card.querySelector(".card-title")?.textContent || "";
    const desc = card.querySelector("p")?.textContent || "";
    return normalise(data+" "+title+" "+desc);
  }

  function doSearch(queryRaw){
    const query = normalise(queryRaw);
    const allCards = $$("[data-item]"); // <-- on récupère les cartes AU MOMENT de la recherche
    if (!query || !allCards.length){
      resultsSection.classList.add("hidden");
      resultsTrack.innerHTML = "";
      return;
    }

    const matches = [];
    allCards.forEach(card=>{
      const kw = getKeywords(card);
      if (kw && kw.includes(query)){
        matches.push(card);
      }
    });

    resultsTrack.innerHTML = "";
    if (!matches.length){
      resultsSection.classList.remove("hidden");
      if (resultsTitle){
        resultsTitle.textContent = `Aucun résultat pour « ${queryRaw} »`;
      }
      return;
    }

    resultsSection.classList.remove("hidden");
    if (resultsTitle){
      resultsTitle.textContent = `Résultats pour « ${queryRaw} » (${matches.length})`;
    }

    matches.forEach(card=>{
      const clone = card.cloneNode(true);
      resultsTrack.appendChild(clone);
    });

    resultsSection.scrollIntoView({ behavior:"smooth", block:"start" });
  }

  form.addEventListener("submit", (e)=>{
    e.preventDefault();
    doSearch(input.value);
  });

  if (resetBtn){
    resetBtn.addEventListener("click", ()=>{
      input.value = "";
      resultsSection.classList.add("hidden");
      resultsTrack.innerHTML = "";
    });
  }
});

/* ==== Tabs & sliders — en délégation (compatible DOM généré après) ==== */
document.addEventListener("DOMContentLoaded", ()=>{
  /* --- Tabs : on écoute les clics sur le conteneur, pas sur les boutons eux-mêmes --- */
  const tabsContainer = document.querySelector(".category-tabs");
  if (tabsContainer){
    tabsContainer.addEventListener("click", (e)=>{
      const btn = e.target.closest(".tab-btn");
      if (!btn) return;
      const targetSelector = btn.dataset.target;
      if (!targetSelector) return;

      const panels  = $$(".tab-panel");
      const tabBtns = $$(".tab-btn");

      const target = $(targetSelector);
      if (!target) return;

      panels.forEach(p=>p.removeAttribute("data-active"));
      target.setAttribute("data-active","true");

      tabBtns.forEach(b=>b.setAttribute("aria-selected","false"));
      btn.setAttribute("aria-selected","true");
    });
  }

  /* --- Sliders : on écoute les clics sur document, et on remonte vers .slider .arrow --- */
  document.addEventListener("click", (e)=>{
    const arrow = e.target.closest(".slider .arrow");
    if (!arrow) return;

    const slider = arrow.closest(".slider");
    if (!slider) return;
    const track = slider.querySelector(".track,[data-track]");
    if (!track) return;

    const dir = arrow.classList.contains("left") ? -1 : 1;
    track.scrollBy({
      left: dir * track.clientWidth,
      behavior:"smooth"
    });
  });
});

/* ==== Carte interactive ==== */
document.addEventListener("DOMContentLoaded", ()=>{
  const btn   = document.getElementById("loadMap");
  const img   = document.getElementById("map-static");
  const cont  = document.getElementById("map-container");
  if (!btn || !img || !cont) return;

  let loaded = false;
  btn.addEventListener("click", ()=>{
    if (loaded) return;
    loaded = true;

    const addr = btn.getAttribute("data-address") || img.alt || "";
    const q    = encodeURIComponent(addr);
    const iframe = document.createElement("iframe");
    iframe.src = `https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=${q}`;
    iframe.loading = "lazy";
    iframe.referrerPolicy = "no-referrer-when-downgrade";
    iframe.className = "w-full h-64 md:h-80 border-0";

    cont.innerHTML = "";
    cont.appendChild(iframe);
  });
});

/* ==== Bouton retour aux catégories (pour résultats recherche) ==== */
document.addEventListener("DOMContentLoaded", ()=>{
  const section = document.getElementById("search-results");
  const track   = document.getElementById("search-results-track");
  if (!section || !track) return;

  function findTabsBar(){
    return document.querySelector(".category-tabs");
  }

  function scrollToWithOffset(target, offset){
    const rect = target.getBoundingClientRect();
    const y = rect.top + window.scrollY - offset;
    window.scrollTo({ top:y, behavior:"smooth" });
  }

  section.addEventListener("transitionend", ()=>{
    const existing = track.querySelector(".grid-return");
    if (existing) return;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "grid-return";
    btn.innerHTML = `↑ <span>Retour aux catégories</span>`;
    btn.addEventListener("click", () => {
      const tabs = findTabsBar();
      if (tabs) {
        scrollToWithOffset(tabs, 80);
      } else {
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    });

    track.appendChild(btn);
  });
});

// ==========================================================================
// TICKET BUILDER – panier multi-produits
// ==========================================================================

let ticketLines = [];
let activeLine = null;
let ticketPanel = null;
let ticketToggle = null;

function ensureTicketShell() {
  if (ticketPanel && ticketToggle) return;

  // Bouton flottant 🎟️
  ticketToggle = document.createElement("button");
  ticketToggle.id = "ticket-toggle";
  ticketToggle.type = "button";
  ticketToggle.className =
    "fixed left-4 bottom-4 z-40 flex items-center gap-2 px-3 py-2 rounded-full bg-brand text-white shadow-lg text-sm font-semibold";
  ticketToggle.innerHTML = `<span class="text-lg">🎟️</span><span>Ticket</span>`;

  // Panel
  ticketPanel = document.createElement("aside");
  ticketPanel.id = "ticket-panel";
  ticketPanel.className =
    "fixed inset-x-0 bottom-0 z-40 md:left-4 md:right-auto md:bottom-20 md:w-80 max-h-[85vh] bg-white rounded-t-3xl md:rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden hidden";

  ticketPanel.innerHTML = `
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <div class="flex items-center gap-2">
        <span class="text-lg">🎟️</span>
        <p class="font-semibold text-sm">Ticket</p>
      </div>
      <button type="button" class="text-slate-500 text-xl leading-none" data-ticket-action="close">&times;</button>
    </div>

    <div id="ticket-body" class="p-4 flex-1 overflow-y-auto space-y-4 text-sm"></div>

    <div class="px-4 pb-4 pt-2 border-t space-y-3 bg-slate-50/80">
      <div class="flex flex-col gap-1">
        <label for="ticket-name" class="text-xs text-slate-500">Prénom <span class="text-red-500">*</span></label>
        <input id="ticket-name" type="text"
               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ring)]"
               placeholder="Votre prénom" />
      </div>

      <div class="flex flex-col gap-1">
        <label for="ticket-phone" class="text-xs text-slate-500">Téléphone <span class="text-red-500">*</span></label>
        <input id="ticket-phone" type="tel"
               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ring)]"
               placeholder="Votre numéro" />
        <button type="button" id="ticket-check-loyalty"
                class="mt-1 text-xs text-brand hover:underline text-left">
          🎁 Vérifier mes points fidélité
        </button>
      </div>

      <div id="ticket-loyalty-section" class="hidden flex-col gap-2 p-3 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200">
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-amber-800">🏆 Vos points fidélité</span>
          <span id="ticket-loyalty-points" class="text-lg font-bold text-brand">0 pts</span>
        </div>
        <div id="ticket-loyalty-rewards" class="space-y-2"></div>
        <p id="ticket-loyalty-empty" class="text-xs text-amber-600 hidden">Pas assez de points pour une récompense</p>
      </div>

      <div class="flex flex-col gap-1">
        <label for="ticket-message" class="text-xs text-slate-500">Message (optionnel)</label>
        <textarea id="ticket-message" rows="2"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[var(--ring)]"
                  placeholder="Précision, heure souhaitée, etc."></textarea>
      </div>

      <div class="flex items-center justify-between text-sm font-semibold">
        <span>Total</span>
        <span id="ticket-total">0,00 €</span>
      </div>

      <button id="ticket-send-resto" type="button"
              class="w-full btn-brand rounded-full py-2 text-sm font-semibold flex items-center justify-center gap-2">
        <span>📱 Envoyer au resto</span>
      </button>

      <button id="ticket-share" type="button"
              class="w-full bg-slate-600 hover:bg-slate-700 text-white rounded-full py-2 text-sm font-semibold flex items-center justify-center gap-2">
        <span>📤 Partager</span>
      </button>
    </div>
  `;

  document.body.appendChild(ticketToggle);
  document.body.appendChild(ticketPanel);

  // Ouvrir / fermer
  ticketToggle.addEventListener("click", () => {
    ticketPanel.classList.toggle("hidden");
  });

  // Délégation des clics dans le panel
  ticketPanel.addEventListener("click", (e) => {
    const actionEl = e.target.closest("[data-ticket-action]");
    if (!actionEl) return;

    const action = actionEl.dataset.ticketAction;

    if (action === "close") {
      ticketPanel.classList.add("hidden");
    }

    if (action === "add-active-line") {
      addActiveLineToTicket();
    }

    if (action === "remove-line") {
      const id = actionEl.dataset.lineId;
      removeTicketLine(id);
    }

    if (action === "toggle-supp") {
      const sid = actionEl.dataset.suppId;
      toggleSupplementOnActive(sid);
    }

    if (action === "toggle-ingredient") {
      const ing = actionEl.dataset.ingredient;
      toggleRemovedIngredientOnActive(ing);
    }
  });

  // Bouton envoyer au resto
  const sendRestoBtn = ticketPanel.querySelector("#ticket-send-resto");
  if (sendRestoBtn) {
    sendRestoBtn.addEventListener("click", sendToRestaurant);
  }

  // Bouton partager
  const shareBtn = ticketPanel.querySelector("#ticket-share");
  if (shareBtn) {
    shareBtn.addEventListener("click", shareTicket);
  }

  // Bouton vérifier fidélité
  const checkLoyaltyBtn = ticketPanel.querySelector("#ticket-check-loyalty");
  if (checkLoyaltyBtn) {
    checkLoyaltyBtn.addEventListener("click", checkLoyaltyPoints);
  }
}

// Variable globale pour stocker la récompense sélectionnée
let selectedLoyaltyReward = null;

// Vérifier les points fidélité du client
async function checkLoyaltyPoints() {
  const phoneInput = document.getElementById("ticket-phone");
  const phone = (phoneInput?.value || "").trim();

  if (!phone) {
    phoneInput?.classList.add("ring-2", "ring-red-400");
    alert("Entrez votre numéro de téléphone pour vérifier vos points.");
    return;
  }

  phoneInput?.classList.remove("ring-2", "ring-red-400");

  const loyaltySection = document.getElementById("ticket-loyalty-section");
  const loyaltyPoints = document.getElementById("ticket-loyalty-points");
  const loyaltyRewards = document.getElementById("ticket-loyalty-rewards");
  const loyaltyEmpty = document.getElementById("ticket-loyalty-empty");

  try {
    const response = await fetch(`api/public.php?endpoint=loyalty&phone=${encodeURIComponent(phone)}`);
    const data = await response.json();

    if (!data.success) {
      alert(data.error || "Erreur lors de la vérification");
      return;
    }

    // Afficher la section fidélité
    loyaltySection.classList.remove("hidden");
    loyaltySection.classList.add("flex");

    // Afficher les points
    loyaltyPoints.textContent = `${data.points} pts`;

    // Réinitialiser la sélection
    selectedLoyaltyReward = null;
    loyaltyRewards.innerHTML = "";

    if (data.rewards && data.rewards.length > 0) {
      loyaltyEmpty.classList.add("hidden");

      data.rewards.forEach((reward) => {
        const rewardEl = document.createElement("label");
        rewardEl.className = "flex items-center gap-2 p-2 rounded-lg bg-white border border-amber-200 cursor-pointer hover:bg-amber-50 transition";
        rewardEl.innerHTML = `
          <input type="radio" name="loyalty-reward" value="${reward.id}" class="accent-brand">
          <div class="flex-1">
            <p class="text-sm font-medium text-slate-800">${reward.name}</p>
            <p class="text-xs text-slate-500">${reward.points_required} pts</p>
          </div>
          <span class="text-xs font-semibold text-green-600">
            ${formatRewardValue(reward)}
          </span>
        `;

        const radio = rewardEl.querySelector("input");
        radio.addEventListener("change", () => {
          selectedLoyaltyReward = reward;
          updateTicketTotalWithLoyalty();
        });

        loyaltyRewards.appendChild(rewardEl);
      });

      // Ajouter option "ne pas utiliser"
      const noRewardEl = document.createElement("label");
      noRewardEl.className = "flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 cursor-pointer hover:bg-slate-50 transition";
      noRewardEl.innerHTML = `
        <input type="radio" name="loyalty-reward" value="" class="accent-brand" checked>
        <span class="text-sm text-slate-600">Ne pas utiliser de récompense</span>
      `;
      const noRadio = noRewardEl.querySelector("input");
      noRadio.addEventListener("change", () => {
        selectedLoyaltyReward = null;
        updateTicketTotalWithLoyalty();
      });
      loyaltyRewards.appendChild(noRewardEl);

    } else {
      loyaltyEmpty.classList.remove("hidden");
      if (data.all_rewards && data.all_rewards.length > 0) {
        const nextReward = data.all_rewards[0];
        const pointsNeeded = nextReward.points_required - data.points;
        loyaltyEmpty.textContent = `Plus que ${pointsNeeded} pts pour "${nextReward.name}" !`;
      } else {
        loyaltyEmpty.textContent = "Continuez à commander pour accumuler des points !";
      }
    }

  } catch (error) {
    console.error("Erreur fidélité:", error);
    alert("Impossible de vérifier vos points. Réessayez plus tard.");
  }
}

// Formater la valeur de la récompense
function formatRewardValue(reward) {
  switch (reward.reward_type) {
    case "discount_percent":
      return `-${reward.reward_value}%`;
    case "discount_amount":
      return `-${parseFloat(reward.reward_value).toFixed(2)}€`;
    case "free_item":
      return "🎁 Offert";
    default:
      return "";
  }
}

// Mettre à jour le total avec la récompense
function updateTicketTotalWithLoyalty() {
  const totalEl = document.getElementById("ticket-total");
  if (!totalEl) return;

  let total = ticketLines.reduce((sum, line) => sum + (line.lineTotal || 0), 0);

  if (selectedLoyaltyReward) {
    const discount = calculateDiscount(total, selectedLoyaltyReward);
    total = Math.max(0, total - discount);
  }

  totalEl.textContent = total.toFixed(2) + " €";
}

// Calculer la réduction
function calculateDiscount(total, reward) {
  if (!reward) return 0;

  switch (reward.reward_type) {
    case "discount_percent":
      return total * (parseFloat(reward.reward_value) / 100);
    case "discount_amount":
      return parseFloat(reward.reward_value);
    case "free_item":
      return 0; // Le free item est géré différemment
    default:
      return 0;
  }
}

// Cherche un produit dans cfg.menu
function findProductById(productId) {
  const cfg = window.SNACK_CONFIG || {};
  if (!cfg.menu?.categories) return null;
  for (const cat of cfg.menu.categories) {
    for (const item of cat.items || []) {
      if (item.id === productId) {
        return { categoryId: cat.id, categoryName: cat.name, item };
      }
    }
  }
  return null;
}

// Quels suppléments autorisés pour cette catégorie ?
function getDefaultSuppForCategory(categoryId) {
  const cfg = window.SNACK_CONFIG || {};
  const sup = cfg.supplements;
  if (!sup || !sup.catalog) return [];
  const ids =
    sup.defaultForCategories?.[categoryId] ||
    Object.keys(sup.catalog);
  return ids
    .map((id) => sup.catalog[id])
    .filter(Boolean);
}

// Ouvrir la modale de personnalisation (remplace l'ancien openTicketBuilder)
function openTicketBuilder(productId, variant) {
  openCustomizeModal(productId, variant);
}

// Ajout de la ligne active dans le panier
function addActiveLineToTicket() {
  if (!activeLine) return;
  ticketLines.push({ ...activeLine });
  activeLine = null;
  renderTicketPanel();
}

function removeTicketLine(lineId) {
  ticketLines = ticketLines.filter((l) => l.id !== lineId);
  renderTicketPanel();
}

function toggleSupplementOnActive(suppId) {
  if (!activeLine) return;
  const cfg = window.SNACK_CONFIG || {};
  const supCfg = cfg.supplements?.catalog?.[suppId];
  if (!supCfg) return;

  const idx = activeLine.supplements.indexOf(suppId);
  if (idx === -1) {
    activeLine.supplements.push(suppId);
    activeLine.lineTotal += supCfg.price || 0;
  } else {
    activeLine.supplements.splice(idx, 1);
    activeLine.lineTotal -= supCfg.price || 0;
  }
  renderTicketPanel();
}

function toggleRemovedIngredientOnActive(ingredient) {
  if (!activeLine) return;
  const idx = activeLine.removedIngredients.indexOf(ingredient);
  if (idx === -1) {
    activeLine.removedIngredients.push(ingredient);
  } else {
    activeLine.removedIngredients.splice(idx, 1);
  }
  renderTicketPanel();
}

// Rendu du contenu du ticket (SIMPLIFIÉ - ne montre que les produits ajoutés)
function renderTicketPanel() {
  if (!ticketPanel) return;
  const body = ticketPanel.querySelector("#ticket-body");
  const totalEl = ticketPanel.querySelector("#ticket-total");
  if (!body || !totalEl) return;

  body.innerHTML = "";

  // --- Liste des lignes déjà ajoutées ---
  if (ticketLines.length) {
    const block = document.createElement("div");
    block.innerHTML = `
      <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Votre commande</p>
      <ul class="space-y-2" id="ticket-lines-list"></ul>
    `;
    body.appendChild(block);

    const ul = block.querySelector("#ticket-lines-list");
    const cfg = window.SNACK_CONFIG || {};

    ticketLines.forEach((line) => {
      const li = document.createElement("li");
      li.className =
        "flex items-start justify-between gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs";
      const variantLabel = line.variant === "menu" ? "menu" : "seul";

      const details = [];

      // Afficher la boisson si menu
      if (line.variant === "menu" && line.selectedDrink) {
        const boissonsCategory = cfg.menu?.categories?.find(cat => cat.id === "boissons");
        const drinkItem = boissonsCategory?.items?.find(d => d.id === line.selectedDrink);
        if (drinkItem) {
          details.push("Boisson : " + drinkItem.name);
        }
      }

      if (line.supplements.length && cfg.supplements?.catalog) {
        const names = line.supplements
          .map((id) => cfg.supplements.catalog[id]?.name)
          .filter(Boolean);
        if (names.length) {
          details.push("Suppléments : " + names.join(", "));
        }
      }

      if (line.removedIngredients.length) {
        details.push(
          "Sans " + line.removedIngredients.join(", ")
        );
      }

      li.innerHTML = `
        <div>
          <p class="font-semibold text-[13px]">${line.productName} <span class="text-slate-500">(${variantLabel})</span></p>
          ${
            details.length
              ? `<p class="text-[11px] text-slate-500 mt-1">${details.join(" · ")}</p>`
              : ""
          }
        </div>
        <div class="flex flex-col items-end gap-1">
          <span class="text-[13px] font-semibold">${line.lineTotal.toFixed(
            2
          )} €</span>
          <button type="button"
                  class="text-[11px] text-red-500 hover:text-red-700"
                  data-ticket-action="remove-line"
                  data-line-id="${line.id}">
            Retirer
          </button>
        </div>
      `;
      ul.appendChild(li);
    });
  } else {
    // Ticket vide
    const empty = document.createElement("div");
    empty.className = "text-center py-8 text-slate-400";
    empty.innerHTML = `
      <p class="text-sm mb-2">🛒 Votre panier est vide</p>
      <p class="text-xs">Ajoutez des produits avec le bouton <span class="font-bold">+</span></p>
    `;
    body.appendChild(empty);
  }

  // Total global (lignes ajoutées uniquement)
  const total = ticketLines.reduce(
    (sum, line) => sum + (line.lineTotal || 0),
    0
  );
  totalEl.textContent = total.toFixed(2) + " €";
}

// Envoi du ticket au restaurant (via WhatsApp)
async function sendToRestaurant() {
  if (!ticketPanel) return;

  // ✅ VÉRIFIER SI LE RESTAURANT ACCEPTE LES COMMANDES
  try {
    const statusResponse = await fetch('admin-panel-v2/api/restaurant-status.php?action=get');
    const statusData = await statusResponse.json();

    if (statusData.success && statusData.status && !statusData.status.accepting_orders) {
      alert('🔒 Désolé, le restaurant n\'accepte pas de commandes pour le moment.\n\nMerci de réessayer plus tard ou de nous appeler directement.');
      return;
    }
  } catch (err) {
    console.warn('⚠️ Impossible de vérifier le statut du restaurant:', err);
    // On continue même si la vérification échoue (mode dégradé)
  }

  const nameInput = ticketPanel.querySelector("#ticket-name");
  const phoneInput = ticketPanel.querySelector("#ticket-phone");
  const msgInput = ticketPanel.querySelector("#ticket-message");

  const name = (nameInput.value || "").trim();
  const phone = (phoneInput.value || "").trim();

  // reset styles
  nameInput.classList.remove("ring-2", "ring-red-400");
  phoneInput.classList.remove("ring-2", "ring-red-400");

  if (!name || !phone) {
    if (!name) {
      nameInput.classList.add("ring-2", "ring-red-400");
    }
    if (!phone) {
      phoneInput.classList.add("ring-2", "ring-red-400");
    }
    return;
  }

  if (!ticketLines.length) {
    alert("Ajoutez au moins un produit dans le ticket.");
    return;
  }

  const subtotal = ticketLines.reduce(
    (sum, line) => sum + (line.lineTotal || 0),
    0
  );

  // Calculer la réduction fidélité si applicable
  let loyaltyDiscount = 0;
  let finalTotal = subtotal;

  if (selectedLoyaltyReward) {
    loyaltyDiscount = calculateDiscount(subtotal, selectedLoyaltyReward);
    finalTotal = Math.max(0, subtotal - loyaltyDiscount);
  }

  const cfg = window.SNACK_CONFIG || {};

  const linesText = ticketLines
    .map((line) => {
      const variantLabel = line.variant === "menu" ? "menu" : "seul";
      const parts = [`- ${line.productName} (${variantLabel})`];

      // Ajouter la boisson si menu
      if (line.variant === "menu" && line.selectedDrink) {
        const boissonsCategory = cfg.menu?.categories?.find(cat => cat.id === "boissons");
        const drinkItem = boissonsCategory?.items?.find(d => d.id === line.selectedDrink);
        if (drinkItem) {
          parts.push("Boisson : " + drinkItem.name);
        }
      }

      if (line.supplements.length && cfg.supplements?.catalog) {
        const names = line.supplements
          .map((id) => cfg.supplements.catalog[id]?.name)
          .filter(Boolean);
        if (names.length) {
          parts.push("Suppléments : " + names.join(", "));
        }
      }

      if (line.removedIngredients.length) {
        parts.push("Sans " + line.removedIngredients.join(", "));
      }

      parts.push(`= ${line.lineTotal.toFixed(2)} €`);

      return parts.join(" | ");
    })
    .join("\n");

  const extra = msgInput.value.trim();
  const snackName = cfg.name || cfg.legalName || "Snack";

  // Construire le texte avec réduction fidélité si applicable
  let totalText = `Total : ${finalTotal.toFixed(2)} €`;
  if (selectedLoyaltyReward && loyaltyDiscount > 0) {
    totalText = `Sous-total : ${subtotal.toFixed(2)} €\n🎁 Fidélité (${selectedLoyaltyReward.name}) : -${loyaltyDiscount.toFixed(2)} €\nTotal : ${finalTotal.toFixed(2)} €`;
  }

  const txt =
    `Commande de ${name} (${phone}) – ${snackName}\n\n` +
    `${linesText}\n\n` +
    totalText +
    (extra ? `\n\nMessage : ${extra}` : "");

  const encoded = encodeURIComponent(txt);

  // Récupérer le numéro WhatsApp du resto depuis le config
  const restoPhone = cfg.contact?.whatsappOrdersNumber || "";

  if (!restoPhone) {
    alert("Numéro WhatsApp du restaurant non configuré.");
    return;
  }

  // 1️⃣ D'abord envoyer au webhook admin pour enregistrer la commande
  const orderData = {
    customer_name: name,
    customer_phone: phone,
    items: ticketLines.map(line => ({
      name: line.productName,
      quantity: 1,
      price: line.lineTotal,
      variant: line.variant,
      drink: line.selectedDrink || null,
      supplements: line.supplements.map(id => {
        const supp = cfg.supplements?.catalog?.[id];
        return supp ? { id, name: supp.name, price: supp.price } : null;
      }).filter(Boolean),
      removed: line.removedIngredients
    })),
    subtotal: subtotal,
    total: finalTotal,
    loyalty_reward_id: selectedLoyaltyReward?.id || null,
    loyalty_discount: loyaltyDiscount,
    notes: extra,
    whatsapp_message: txt
  };

  // Envoyer au webhook admin
  fetch('admin-panel-v2/webhook.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(orderData)
  })
  .then(r => {
    if (!r.ok) {
      throw new Error('Erreur HTTP: ' + r.status);
    }
    return r.json();
  })
  .then(data => {
    if (data.success) {
      console.log('✅ Commande enregistrée dans l\'admin:', data);

      // Afficher message de confirmation avec info fidélité
      let confirmMsg = '✅ Merci ' + name + ' !\n\n' +
            'Votre commande a bien été envoyée au restaurant.\n' +
            'Montant : ' + finalTotal.toFixed(2) + '€';

      if (selectedLoyaltyReward) {
        confirmMsg += '\n🎁 Récompense utilisée : ' + selectedLoyaltyReward.name;
      }

      confirmMsg += '\n\nNous préparons votre commande !';
      alert(confirmMsg);

      // Vider le panier et fermer le ticket (dans un try/catch pour éviter les erreurs non critiques)
      try {
        ticketLines = [];
        selectedLoyaltyReward = null;

        // Réinitialiser l'affichage fidélité
        const loyaltySection = document.getElementById("ticket-loyalty-section");
        if (loyaltySection) {
          loyaltySection.classList.add("hidden");
          loyaltySection.classList.remove("flex");
        }

        renderTicketPanel();
        ticketPanel.classList.add("hidden");
      } catch (cleanupErr) {
        console.warn('⚠️ Erreur nettoyage (non critique):', cleanupErr);
      }
    } else {
      alert('❌ Erreur lors de l\'envoi de la commande.\nVeuillez réessayer ou appeler le restaurant.');
    }
  })
  .catch(err => {
    console.error('⚠️ Erreur webhook admin:', err);
    alert('❌ Impossible d\'envoyer la commande au restaurant.\n\nVeuillez vérifier votre connexion internet ou appeler directement le restaurant.');
  });
}

// Partage du ticket (WhatsApp / partage natif)
function shareTicket() {
  if (!ticketPanel) return;

  const nameInput = ticketPanel.querySelector("#ticket-name");
  const phoneInput = ticketPanel.querySelector("#ticket-phone");
  const msgInput = ticketPanel.querySelector("#ticket-message");

  const name = (nameInput.value || "").trim();
  const phone = (phoneInput.value || "").trim();

  // reset styles
  nameInput.classList.remove("ring-2", "ring-red-400");
  phoneInput.classList.remove("ring-2", "ring-red-400");

  if (!name || !phone) {
    if (!name) {
      nameInput.classList.add("ring-2", "ring-red-400");
    }
    if (!phone) {
      phoneInput.classList.add("ring-2", "ring-red-400");
    }
    return;
  }

  if (!ticketLines.length) {
    alert("Ajoutez au moins un produit dans le ticket.");
    return;
  }

  const total = ticketLines.reduce(
    (sum, line) => sum + (line.lineTotal || 0),
    0
  );

  const cfg = window.SNACK_CONFIG || {};

  const linesText = ticketLines
    .map((line) => {
      const variantLabel = line.variant === "menu" ? "menu" : "seul";
      const parts = [`- ${line.productName} (${variantLabel})`];

      // Ajouter la boisson si menu
      if (line.variant === "menu" && line.selectedDrink) {
        const boissonsCategory = cfg.menu?.categories?.find(cat => cat.id === "boissons");
        const drinkItem = boissonsCategory?.items?.find(d => d.id === line.selectedDrink);
        if (drinkItem) {
          parts.push("Boisson : " + drinkItem.name);
        }
      }

      if (line.supplements.length && cfg.supplements?.catalog) {
        const names = line.supplements
          .map((id) => cfg.supplements.catalog[id]?.name)
          .filter(Boolean);
        if (names.length) {
          parts.push("Suppléments : " + names.join(", "));
        }
      }

      if (line.removedIngredients.length) {
        parts.push("Sans " + line.removedIngredients.join(", "));
      }

      parts.push(`= ${line.lineTotal.toFixed(2)} €`);

      return parts.join(" | ");
    })
    .join("\n");

  const extra = msgInput.value.trim();
  const snackName = cfg.name || cfg.legalName || "Snack";
  const txt =
    `Commande de ${name} (${phone}) – ${snackName}\n\n` +
    `${linesText}\n\n` +
    `Total : ${total.toFixed(2)} €` +
    (extra ? `\n\nMessage : ${extra}` : "");

  const encoded = encodeURIComponent(txt);

  if (navigator.share) {
    navigator
      .share({
        title: "Ticket commande",
        text: txt,
      })
      .catch(() => {
        // fallback silencieux vers WhatsApp
        window.open(`https://wa.me/?text=${encoded}`, "_blank");
      });
  } else {
    window.open(`https://wa.me/?text=${encoded}`, "_blank");
  }
}

// ==================== MODALE DE PERSONNALISATION ====================

let currentCustomization = null; // Stocke les données de personnalisation en cours

// Ouvrir la modale de personnalisation
function openCustomizeModal(productId, variant = "solo") {
  console.log("🔵 [MODALE] openCustomizeModal appelée", { productId, variant });

  const found = findProductById(productId);
  if (!found) {
    console.error("❌ [MODALE] Produit non trouvé:", productId);
    return;
  }
  const { categoryId, item } = found;
  console.log("✅ [MODALE] Produit trouvé:", item.name);

  // Prix de base selon variant
  const priceMenu = item.priceMenu;
  const priceSolo = item.priceSolo ?? item.price;

  // Initialiser la personnalisation
  currentCustomization = {
    item,
    categoryId,
    variant,
    priceSolo,
    priceMenu,
    supplements: [],
    removedIngredients: [],
    selectedDrink: null // Pour les menus
  };

  // Éléments DOM
  const overlay = document.getElementById("customize-overlay");
  if (!overlay) {
    console.error("❌ [MODALE] Overlay non trouvé dans le DOM !");
    return;
  }
  console.log("✅ [MODALE] Overlay trouvé");

  const productName = document.getElementById("customize-product-name");
  const variantLabel = document.getElementById("customize-variant-label");
  const drinkSection = document.getElementById("customize-drink-section");
  const drinkSelect = document.getElementById("customize-drink-select");

  // Remplir les infos produit
  if (productName) productName.textContent = item.name;

  // Afficher le variant choisi
  if (variantLabel) {
    const price = variant === "menu" ? priceMenu : priceSolo;
    const label = variant === "menu" ? "Menu" : "Seul";
    variantLabel.textContent = `${label} - ${price ? price.toFixed(2).replace('.', ',') : '0,00'}€`;
  }

  // Afficher/masquer et remplir la section boisson
  if (variant === "menu" && drinkSection && drinkSelect) {
    drinkSection.style.display = "block";

    // Remplir le dropdown avec les boissons
    const cfg = window.SNACK_CONFIG || {};
    const boissonsCategory = cfg.menu?.categories?.find(cat => cat.id === "boissons");
    drinkSelect.innerHTML = '<option value="">Sélectionnez une boisson</option>';

    if (boissonsCategory?.items) {
      boissonsCategory.items.forEach(drink => {
        const option = document.createElement("option");
        option.value = drink.id;
        option.textContent = drink.name;
        drinkSelect.appendChild(option);
      });
    }

    // Écouter les changements de boisson
    drinkSelect.onchange = () => {
      currentCustomization.selectedDrink = drinkSelect.value || null;
    };
  } else if (drinkSection) {
    drinkSection.style.display = "none";
  }

  // Remplir les suppléments
  renderCustomizeSupplements();

  // Remplir les ingrédients
  renderCustomizeIngredients();

  // Calculer le total initial
  updateCustomizeTotal();

  // Afficher la modale
  console.log("🔵 [MODALE] Tentative d'affichage...");
  overlay.classList.remove("hidden");
  console.log("✅ [MODALE] Modale affichée !");

  // Empêcher le scroll du body
  document.body.style.overflow = "hidden";
}

// Fermer la modale
function closeCustomizeModal() {
  const overlay = document.getElementById("customize-overlay");
  overlay.classList.add("hidden");
  document.body.style.overflow = "";
  currentCustomization = null;
}

// Rendre les suppléments disponibles
function renderCustomizeSupplements() {
  if (!currentCustomization) return;

  const container = document.getElementById("customize-supplements-list");
  const section = document.getElementById("customize-supplements-section");
  container.innerHTML = "";

  // Récupérer les suppléments pour cette catégorie
  const allSupp = getDefaultSuppForCategory(currentCustomization.categoryId);
  if (allSupp.length === 0) {
    section.style.display = "none";
    return;
  }

  section.style.display = "block";

  // Filtrer selon variant (cheddar frites uniquement pour menu)
  const isMenu = currentCustomization.variant === "menu";
  const forbiddenIds = isMenu ? [] : ["sup-cheddar-frites"];
  const availableSupp = allSupp.filter(s => !forbiddenIds.includes(s.id));

  // Créer les checkboxes
  availableSupp.forEach(supp => {
    const label = document.createElement("label");
    label.innerHTML = `
      <div class="flex items-center gap-3">
        <input type="checkbox" data-supp-id="${supp.id}" ${currentCustomization.supplements.includes(supp.id) ? 'checked' : ''}>
        <span>${supp.name}</span>
      </div>
      <span class="text-brand font-semibold">+${supp.price.toFixed(2).replace('.', ',')}€</span>
    `;
    container.appendChild(label);

    // Écouter les changements
    const checkbox = label.querySelector('input');
    checkbox.addEventListener('change', () => {
      if (checkbox.checked) {
        if (!currentCustomization.supplements.includes(supp.id)) {
          currentCustomization.supplements.push(supp.id);
        }
      } else {
        currentCustomization.supplements = currentCustomization.supplements.filter(id => id !== supp.id);
      }
      updateCustomizeTotal();
    });
  });
}

// Rendre les ingrédients à retirer
function renderCustomizeIngredients() {
  if (!currentCustomization) return;

  const container = document.getElementById("customize-ingredients-list");
  const section = document.getElementById("customize-ingredients-section");
  container.innerHTML = "";

  const ingredients = currentCustomization.item.baseIngredients || [];
  if (ingredients.length === 0) {
    section.style.display = "none";
    return;
  }

  section.style.display = "block";

  // Créer les boutons toggle
  ingredients.forEach(ing => {
    const button = document.createElement("button");
    button.textContent = `Sans ${ing}`;
    button.setAttribute("data-ingredient", ing);
    if (currentCustomization.removedIngredients.includes(ing)) {
      button.classList.add("active");
    }

    button.addEventListener("click", () => {
      if (currentCustomization.removedIngredients.includes(ing)) {
        currentCustomization.removedIngredients = currentCustomization.removedIngredients.filter(i => i !== ing);
        button.classList.remove("active");
      } else {
        currentCustomization.removedIngredients.push(ing);
        button.classList.add("active");
      }
    });

    container.appendChild(button);
  });
}

// Mettre à jour le total
function updateCustomizeTotal() {
  if (!currentCustomization) return;

  const cfg = window.SNACK_CONFIG || {};
  const suppCatalog = cfg.supplements?.catalog || {};

  // Prix de base selon variant
  let total = currentCustomization.variant === "menu"
    ? currentCustomization.priceMenu
    : currentCustomization.priceSolo;

  // Ajouter les suppléments
  currentCustomization.supplements.forEach(suppId => {
    const supp = suppCatalog[suppId];
    if (supp) {
      total += supp.price;
    }
  });

  // Afficher
  const totalEl = document.getElementById("customize-total");
  totalEl.textContent = `${total.toFixed(2).replace('.', ',')}€`;
}


// Ajouter au panier (ticket)
function addToCartFromCustomize() {
  if (!currentCustomization) return;

  ensureTicketShell();

  const { item, categoryId, variant, priceSolo, priceMenu, selectedDrink } = currentCustomization;

  const basePrice = variant === "menu" ? priceMenu : priceSolo;

  // Calculer le total avec suppléments
  const cfg = window.SNACK_CONFIG || {};
  const suppCatalog = cfg.supplements?.catalog || {};
  let lineTotal = basePrice;

  currentCustomization.supplements.forEach(suppId => {
    const supp = suppCatalog[suppId];
    if (supp) {
      lineTotal += supp.price;
    }
  });

  // Créer la ligne
  const newLine = {
    id: "line_" + Date.now() + "_" + Math.random().toString(16).slice(2),
    productId: item.id,
    productName: item.name,
    categoryId,
    variant,
    basePrice,
    supplements: [...currentCustomization.supplements],
    removedIngredients: [...currentCustomization.removedIngredients],
    baseIngredients: Array.isArray(item.baseIngredients) ? item.baseIngredients : [],
    selectedDrink: selectedDrink || null, // Ajouter la boisson choisie
    lineTotal
  };

  // Ajouter au ticket
  ticketLines.push(newLine);

  // Fermer la modale
  closeCustomizeModal();

  // Afficher le ticket
  if (ticketPanel) {
    ticketPanel.classList.remove("hidden");
    renderTicketPanel();
  }

  // Scroll vers le ticket (optionnel)
  if (ticketPanel) {
    ticketPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
}

// Initialiser les événements de la modale
document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("customize-overlay");
  const closeBtn = document.getElementById("customize-close");
  const addBtn = document.getElementById("customize-add-to-cart");

  // Fermer au clic sur X
  if (closeBtn) {
    closeBtn.addEventListener("click", closeCustomizeModal);
  }

  // Fermer au clic sur le fond sombre
  if (overlay) {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeCustomizeModal();
      }
    });
  }

  // Fermer avec Échap
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeCustomizeModal();
    }
  });

  // Ajouter au panier
  if (addBtn) {
    addBtn.addEventListener("click", addToCartFromCustomize);
  }
});

window.openAddProduct = function () {
  const modal = document.getElementById('addProductModal');
  if (!modal) {
    console.error('addProductModal introuvable');
    return;
  }
  modal.classList.add('active');
  console.log('Modal ouverte');
};

window.closeAddProduct = function () {
  const modal = document.getElementById('addProductModal');
  if (!modal) return;
  modal.classList.remove('active');
};

window.openAddCategory = function () {
  const modal = document.getElementById('addCategoryModal');
  if (!modal) {
    console.error('addCategoryModal introuvable');
    return;
  }
  modal.classList.add('active');
  console.log('Modal catégorie ouverte');
};

window.closeAddCategory = function () {
  const modal = document.getElementById('addCategoryModal');
  if (!modal) return;
  modal.classList.remove('active');
};

document.addEventListener('DOMContentLoaded', () => {
  const addCategoryForm = document.getElementById('addCategoryForm');

  if (!addCategoryForm) {
    console.warn('addCategoryForm introuvable');
    return;
  }

  addCategoryForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // ⛔ empêche le reload

    const formData = new FormData(addCategoryForm);
    const name = formData.get('name').trim();

    if (!name) {
      alert('Le nom de la catégorie est obligatoire');
      return;
    }

    // slug généré automatiquement (le restaurateur ne le voit pas)
    const id = name
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '');

    const payload = {
      action: 'add_category',
      id,
      name,
      description: formData.get('description'),
    };

    try {
      const res = await fetch('api/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const json = await res.json();

      if (!json.success) {
        alert(json.error || 'Erreur serveur');
        return;
      }

      closeAddCategory();
      location.reload();
    } catch (err) {
      console.error(err);
      alert('Erreur réseau');
    }
  });
});





// 🔴 IMPORTANT : on expose openTicketBuilder au scope global
window.openTicketBuilder = openTicketBuilder;
