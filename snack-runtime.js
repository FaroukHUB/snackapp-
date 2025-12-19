(async()=>{
  try { await (window.__SNACK_RUNTIME__?.ready || Promise.resolve()); } catch(e) {}
// ============================================================================
// SNACK RUNTIME – VERSION FINALE (MySQL + JSON fallback)
// 100% générique · 0% Formule · Full data-driven via SNACK_CONFIG
// Remplit automatiquement tout le template (HTML/CSS/JS) via le config du snack
// Support API MySQL ou fichiers JSON statiques
// ============================================================================

console.log("🚀 [INIT] snack-runtime.js is loading...");

(function () {
  console.log("🔍 [INIT] IIFE started");
  const cfg = window.SNACK_CONFIG;
  console.log("🔍 [INIT] SNACK_CONFIG:", cfg ? "EXISTS" : "MISSING");
  if (!cfg) {
    console.error("❌ SNACK_RUNTIME : SNACK_CONFIG manquant.");
    return;
  }
  console.log("✅ [INIT] SNACK_CONFIG loaded successfully");

  // API mode: si /api/public.php existe, on l'utilise
  // Sinon, fallback sur les fichiers JSON statiques
  const API_BASE = cfg.apiBase || "/api/public.php";
  let useAPI = cfg.useAPI !== false; // Par défaut true si non spécifié

  // Helper pour les appels API
  async function apiCall(endpoint, method = "GET", data = null) {
    const url = `${API_BASE}?endpoint=${endpoint}`;
    const options = {
      method,
      headers: { "Content-Type": "application/json" },
      cache: "no-store"
    };
    if (data && method === "POST") {
      options.body = JSON.stringify(data);
    }
    const res = await fetch(url, options);
    if (!res.ok) throw new Error(`API ${endpoint} failed: ${res.status}`);
    return res.json();
  }

  // Fonction pour sauvegarder une commande en base (en plus de WhatsApp)
  async function saveOrderToDatabase(orderData) {
    if (!useAPI) return null;
    try {
      const result = await apiCall("order", "POST", orderData);
      if (result.success) {
        console.log("✅ [ORDER] Commande sauvegardée:", result.order_id);
        return result.order_id;
      }
    } catch (err) {
      console.warn("⚠️ [ORDER] Impossible de sauvegarder en base:", err.message);
    }
    return null;
  }

  // Exposer pour usage global
  window.SNACK_API = { saveOrderToDatabase, apiCall };

  document.addEventListener("DOMContentLoaded", async () => {
    // ===== 1. Charger les données depuis l'API ou JSON =====

    if (useAPI) {
      // Essayer d'abord l'API MySQL
      try {
        const menuRes = await apiCall("menu");
        if (menuRes.success && menuRes.menu) {
          console.log("✅ [MENU] Chargé depuis API MySQL");
          window.SNACK_CONFIG.menu = menuRes.menu;

          if (menuRes.supplements) {
            window.SNACK_CONFIG.supplements = menuRes.supplements;
            console.log("✅ [SUPPLEMENTS] Chargés depuis API");
          }
        }
      } catch (err) {
        console.warn("⚠️ [API] API indisponible, fallback JSON:", err.message);
        useAPI = false;
      }

      if (useAPI) {
        try {
          const restoRes = await apiCall("restaurant");
          if (restoRes.success) {
            if (restoRes.contact) {
              window.SNACK_CONFIG.contact = { ...window.SNACK_CONFIG.contact, ...restoRes.contact };
            }
            if (restoRes.openingHours) {
              window.SNACK_CONFIG.openingHours = restoRes.openingHours;
            }
            if (restoRes.social) {
              window.SNACK_CONFIG.social = { ...window.SNACK_CONFIG.social, ...restoRes.social };
            }
            if (restoRes.faq) {
              window.SNACK_CONFIG.faq = restoRes.faq;
            }
            if (restoRes.location) {
              window.SNACK_CONFIG.location = { ...window.SNACK_CONFIG.location, ...restoRes.location };
            }
            console.log("✅ [SETTINGS] Chargés depuis API MySQL");
          }
        } catch (err) {
          console.warn("⚠️ [SETTINGS] API failed:", err.message);
        }
      }
    }

    // Fallback: Charger depuis fichiers JSON si API non utilisée
    if (!useAPI) {
      // Menu depuis menu.json
      try {
        const res = await fetch("/config/menu.json", { cache: "no-store" });
        if (!res.ok) throw new Error("menu.json introuvable");

        const menuData = await res.json();

        if (menuData && menuData.menu && Array.isArray(menuData.menu.categories)) {
          console.log("✅ [MENU] menu.json chargé (fallback JSON)");
          window.SNACK_CONFIG.menu = menuData.menu;

          if (menuData.supplements) {
            window.SNACK_CONFIG.supplements = menuData.supplements;
            console.log("✅ [SUPPLEMENTS] Suppléments chargés depuis menu.json");
          }

          if (menuData.featured) {
            window.SNACK_CONFIG.featured = menuData.featured;
          }

          if (menuData.formula) {
            window.SNACK_CONFIG.formula = menuData.formula;
          }
        } else {
          console.warn("⚠️ [MENU] menu.json invalide, menu du config conservé");
        }
      } catch (err) {
        console.warn("⚠️ [MENU] Impossible de charger menu.json :", err.message);
      }

      // Settings depuis restaurant.json
      try {
        const restoRes = await fetch("/config/restaurant.json", { cache: "no-store" });
        if (restoRes.ok) {
          const restoData = await restoRes.json();

          if (restoData.contact) {
            window.SNACK_CONFIG.contact = { ...window.SNACK_CONFIG.contact, ...restoData.contact };
          }
          if (restoData.openingHours) {
            window.SNACK_CONFIG.openingHours = restoData.openingHours;
          }
          if (restoData.social) {
            window.SNACK_CONFIG.social = { ...window.SNACK_CONFIG.social, ...restoData.social };
          }
          if (restoData.faq) {
            window.SNACK_CONFIG.faq = restoData.faq;
          }
          if (restoData.location) {
            window.SNACK_CONFIG.location = { ...window.SNACK_CONFIG.location, ...restoData.location };
          }

          console.log("✅ [SETTINGS] restaurant.json chargé (fallback JSON)");
        }
      } catch (err) {
        console.warn("⚠️ [SETTINGS] Impossible de charger restaurant.json :", err.message);
      }
    }

    initSnackRuntime();
  });

  // ==========================================================================
  // HELPERS GÉNÉRAUX
  // ==========================================================================
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const snackName = cfg.name || cfg.legalName || "Snack";
  const fullAddress = [
    cfg.location?.addressLine1,
    cfg.location?.postalCode,
    cfg.location?.city,
  ]
    .filter(Boolean)
    .join(", ");

  const phoneRaw = cfg.contact?.phone || "";
  const phoneDisplay = cfg.contact?.displayPhone || phoneRaw;
  const phoneHref = phoneRaw ? `tel:${phoneRaw.replace(/\s+/g, "")}` : "#";

  // ==========================================================================
  // INIT PRINCIPAL
  // ==========================================================================
  function initSnackRuntime() {
    document.body.setAttribute("data-snack", cfg.id || "snack");

    applyTheme();
    applyHeader();
    applyHero();
    // Ordre: header > hero > recherche (dans menu) > menu > préférés > plateformes > avis > maps > faq
    applyMenu();
    applyFeatured();
    applyDeliverySection(); // Plateformes (Uber Eats, Deliveroo)
    applyReviews(); // Avis clients
    applyMap(); // Carte Google Maps
    applyFaq(); // FAQ
    applyFooter();
    applyDrawer();
    applyFloatingCall();
    applySeo();
  }

  // ==========================================================================
  // 1. THEME (couleurs)
  // ==========================================================================
  function applyTheme() {
    if (!cfg.theme?.colors) return;

    const c = cfg.theme.colors;
    const root = document.documentElement;

    const accent =
      c.accent ||
      c.brand ||
      "#e11b22";

    const text =
      c.text ||
      c.headerText ||
      "#111827";

    const background =
      c.background ||
      c.surface ||
      c.surfaceAlt ||
      "#ffffff";

    if (accent) {
      root.style.setProperty("--brand", accent);
      root.style.setProperty("--ring", hexToRgba(accent, 0.3));
    }
    if (text) {
      root.style.setProperty("--ink", text);
    }
    if (background) {
      root.style.setProperty("--paper", background);
    }

    const b = (cfg.theme && cfg.theme.badges) || {};
    const soloBg   = b.soloBg   || "#111111";
    const soloText = b.soloText || "#ffffff";  // ✅ Blanc au lieu de accent
    const menuBg   = b.menuBg   || accent;
    const menuText = b.menuText || "#ffffff";  // ✅ Blanc au lieu de noir

    const s = document.createElement("style");
    s.textContent = `
      #navBtn {
        background: var(--brand);
        color: #fff;
        border-color: var(--brand);
      }
      #navBtn:hover { filter: brightness(1.05); }

      .btn-brand {
        background: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
      }

      .text-brand { color: var(--brand); }
      .bg-brand { background: var(--brand); }

      .price-chip-solo {
        background: ${soloBg};
        color: ${soloText};
      }
      .price-chip-menu {
        background: ${menuBg};
        color: ${menuText};
      }

      .agency-link {
        color: ${accent} !important;
        font-weight: 600;
      }
      .agency-link:hover {
        opacity: 0.85;
      }

      .price-badge,
      .badge-menu {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        min-width: 9rem;
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        font-size: 0.95rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        white-space: nowrap;
      }

      .price-badge {
        background: ${soloBg};
        color: ${soloText};
      }

      .badge-menu {
        background: ${menuBg};
        color: ${menuText};
      }

      .price-plus {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);  /* Plus clair pour mieux voir le + */
        color: #ffffff;  /* ✅ + en blanc */
        font-size: 1.1rem;
        font-weight: 800;
        flex-shrink: 0;
      }
    `;
    document.head.appendChild(s);
  }

  // ==========================================================================
  // 2. HEADER
  // ==========================================================================
  function applyHeader() {
    const header = document.querySelector("header");
    if (!header) return;

    const link = header.querySelector("a[href='#hero']");
    if (link) {
      const img = link.querySelector("img");
      const span = link.querySelector("span");

      if (img && cfg.assets?.logo) {
        img.src = cfg.assets.logo.dark || cfg.assets.logo.light || img.src;
        img.alt = cfg.assets.logo.alt || snackName;
      }

      if (cfg.id === "fabrik-burger" && span) {
        span.remove();
      }
    }

    const colors = cfg.theme?.colors || {};
    let bg;
    let ink;

    if (cfg.id === "fabrik-burger") {
      bg = colors.accent || colors.surfaceAlt || colors.surface || "#c58a3a";
      ink = colors.textOnDark || "#fdfbf7";
    } else {
      bg =
        colors.headerBackground || colors.surface || colors.surfaceAlt || null;
      ink = colors.headerText || colors.text || "#111111";
    }

    if (!bg) return;

    header.style.backgroundColor = bg;
    header.style.color = ink;

    header.querySelectorAll("nav a, #navBtn").forEach((el) => {
      el.style.color = ink;
    });

    const parent = header.parentElement;
    if (parent && parent.tagName !== "BODY" && parent.tagName !== "HTML") {
      parent.style.backgroundColor = bg;
      parent.style.color = ink;
    }
  }

  // ==========================================================================
  // 3. HERO
  // ==========================================================================
  function applyHero() {
    const hero = $("#hero");
    if (!hero) return;

    const heroImg = $("img", hero);
    if (heroImg && cfg.assets?.hero) {
      heroImg.src = cfg.assets.hero.image || heroImg.src;
      heroImg.alt = cfg.assets.hero.alt || `${snackName}`;
    }

    const kicker = hero.querySelector(".section-kicker");
    if (kicker) {
      const city = cfg.location?.city ? ` · ${cfg.location.city}` : "";
      kicker.textContent = `${snackName}${city}`;
    }

    const title = hero.querySelector("h1.section-title");
    if (title && cfg.heroTitle) {
      title.textContent = cfg.heroTitle;
    }

    const badgesRow = hero.querySelector(
      ".text-slate-600.text-sm.md\\:text-base.mt-2.flex.items-center.gap-2.flex-wrap"
    );

    if (badgesRow) {
      badgesRow.innerHTML = "";
      const list = [];

      if (cfg.google?.rating && cfg.google?.reviewCount) {
        list.push(
          `⭐ ${cfg.google.rating
            .toString()
            .replace(".", ",")}/5 · ${cfg.google.reviewCount}+ avis Google`
        );
      } else if (cfg.uber?.rating && cfg.uber?.reviewCount) {
        list.push(
          `⭐ ${cfg.uber.rating
            .toString()
            .replace(".", ",")}/5 · ${cfg.uber.reviewCount}+ avis Uber Eats`
        );
      }

      if (cfg.priceRange) list.push(`💶 ${cfg.priceRange}`);

      if (cfg.google?.cuisine?.length) {
        list.push(`🍽️ ${cfg.google.cuisine.join(" · ")}`);
      }

      if (cfg.isHalal) list.push("✅ Halal");

      list.forEach((txt) => {
        const span = document.createElement("span");
        span.className = "inline-flex items-center gap-1 text-slate-500";
        span.textContent = txt;
        badgesRow.appendChild(span);
      });
    }
  }

  // ==========================================================================
  // 4. MAP (iframe prioritaire via cfg.urls.googleMapsEmbed)
  // ==========================================================================
  function applyMap() {
    const carte = $("#carte");
    if (!carte) return;

    const addrText = carte.querySelector("p.mt-1");
    if (addrText) addrText.textContent = fullAddress;

    const itin = carte.querySelector("a[href*='maps']");
    if (itin) {
      const url =
        cfg.urls?.googleMaps ||
        (fullAddress
          ? "https://www.google.com/maps/search/?api=1&query=" +
            encodeURIComponent(fullAddress)
          : "https://www.google.com/maps");
      itin.href = url;
    }

    const mapContainer = $("#map-container", carte);

    if (mapContainer && cfg.urls?.googleMapsEmbed) {
      mapContainer.innerHTML = "";

      const iframe = document.createElement("iframe");
      iframe.src = cfg.urls.googleMapsEmbed;
      iframe.loading = "lazy";
      iframe.referrerPolicy = "no-referrer-when-downgrade";
      iframe.className = "w-full h-64 md:h-80 border-0";
      iframe.setAttribute("allowfullscreen", "");

      mapContainer.appendChild(iframe);
      return;
    }

    const mapImg = $("#map-static", carte);
    if (mapImg && fullAddress) {
      const q = encodeURIComponent(fullAddress);
      mapImg.src =
        "https://maps.googleapis.com/maps/api/staticmap?center=" +
        q +
        "&zoom=15&size=640x400&markers=color:red|" +
        q +
        "&key=YOUR_API_KEY";
      mapImg.alt = `Plan d'accès à ${snackName}`;
    }

    const loadBtn = $("#loadMap", carte);
    if (loadBtn) {
      loadBtn.dataset.address = fullAddress || "";
    }
  }

  // ==========================================================================
  // 4 BIS. PRÉFÉRÉS CLIENTS
  // ==========================================================================
  function applyFeatured() {
    if (!cfg.featured || !cfg.featured.enabled || !cfg.featured.items || cfg.featured.items.length === 0) {
      return; // Pas de section featured configurée
    }

    // Trouver ou créer la section featured dans le HTML
    const menuSection = $("#menu");
    if (!menuSection) return;

    // Créer la section featured APRÈS le menu
    let featuredSection = $("#featured");
    if (!featuredSection) {
      featuredSection = document.createElement("section");
      featuredSection.id = "featured";
      featuredSection.className = "max-w-6xl mx-auto px-4 pt-8 mb-8";
      // Insérer APRÈS le menu
      if (menuSection.nextSibling) {
        menuSection.parentNode.insertBefore(featuredSection, menuSection.nextSibling);
      } else {
        menuSection.parentNode.appendChild(featuredSection);
      }
    }

    // Titre et sous-titre
    featuredSection.innerHTML = `
      <div class="text-center mb-6">
        <h2 class="section-title text-2xl md:text-3xl mb-2">${cfg.featured.title || "Nos préférés"}</h2>
        ${cfg.featured.subtitle ? `<p class="text-slate-600">${cfg.featured.subtitle}</p>` : ""}
      </div>
    `;

    // Slider container (même structure que le menu)
    const slider = document.createElement("div");
    slider.className = "slider";

    const left = document.createElement("button");
    left.className = "arrow left";
    left.textContent = "‹";

    const right = document.createElement("button");
    right.className = "arrow right";
    right.textContent = "›";

    const track = document.createElement("div");
    track.className = "track";
    track.dataset.track = "";
    track.id = "featured-track";

    // Parcourir toutes les catégories pour trouver les produits
    const allItems = [];
    if (cfg.menu && cfg.menu.categories) {
      cfg.menu.categories.forEach(cat => {
        if (cat.items && Array.isArray(cat.items)) {
          cat.items.forEach(item => {
            if (cfg.featured.items.includes(item.id)) {
              allItems.push({ ...item, categoryId: cat.id, categoryName: cat.name });
            }
          });
        }
      });
    }

  // Créer les cartes featured (EXACTEMENT comme dans le menu)
allItems.forEach(item => {
  const card = document.createElement("article");
  card.className = "card bg-white border rounded-3xl elev p-5";
  card.style.position = "relative";

  // ===== ÉTAT : NON DISPONIBLE =====
  if (item.status === "unavailable") {
    card.classList.add("is-unavailable");

    const unavailableBadge = document.createElement("div");
    unavailableBadge.className = "product-badge";
    unavailableBadge.textContent = "Indisponible";
    card.appendChild(unavailableBadge);
  }

  card.dataset.item =
    item.id ||
    (item.name || "").toLowerCase().replace(/\s+/g, "-");

  const kwParts = [
    item.name,
    item.description,
    Array.isArray(item.tags) ? item.tags.join(" ") : "",
  ];
  card.dataset.keywords = kwParts.filter(Boolean).join(" ");

  // Badge "PRÉFÉRÉ" en haut à droite
  const badge = document.createElement("div");
  badge.className = "absolute top-2 right-2 z-10 bg-brand text-white px-2 py-1 rounded-full text-xs font-bold";
  badge.textContent = "⭐ PRÉFÉRÉ";
  badge.style.position = "absolute";
  card.appendChild(badge);

  // Image du produit (même code que le menu)
  const imgSrc = item.image || (item.imageKey && cfg.assets?.menuImages?.[item.imageKey]);
  if (imgSrc) {
    const img = document.createElement("img");
    img.className = "food-img";
    img.src = imgSrc;
    img.alt = item.name;
    card.appendChild(img);
  }

  const h4 = document.createElement("h4");
  h4.className = "card-title mt-3";
  h4.textContent = item.name;
  card.appendChild(h4);

  if (item.description) {
    const p = document.createElement("p");
    p.className = "text-sm text-slate-600";
    p.textContent = item.description;
    card.appendChild(p);
  }

  const priceBox = document.createElement("div");
  priceBox.className = "mt-2 flex flex-col gap-2";

  const soloPrice = item.priceSolo ?? item.price;
  if (soloPrice != null && item.status !== "unavailable") {
    const soloBtn = document.createElement("button");
    soloBtn.type = "button";

    soloBtn.className = "price-badge flex items-center justify-between";
    soloBtn.dataset.productId = item.id;
    soloBtn.dataset.variant = "solo";

    soloBtn.innerHTML = `
      <span>${soloPrice} € </span>
      <span class="price-plus">+</span>
    `;

    soloBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (typeof openTicketBuilder === "function") {
        openTicketBuilder(item.id, "solo");
      }
    });

    priceBox.appendChild(soloBtn);
  }

  if (item.priceMenu != null && item.status !== "unavailable") {
    const btnMenu = document.createElement("button");
    btnMenu.type = "button";
    btnMenu.className =
      "badge-menu flex items-center justify-between";
    btnMenu.dataset.productId = item.id;
    btnMenu.dataset.variant = "menu";

    btnMenu.innerHTML = `
      <span>${item.priceMenu} € en menu</span>
      <span class="price-plus">+</span>
    `;

    btnMenu.addEventListener("click", (e) => {
      e.stopPropagation();
      if (typeof openTicketBuilder === "function") {
        openTicketBuilder(item.id, "menu");
      }
    });

    priceBox.appendChild(btnMenu);
  }

  card.appendChild(priceBox);

  track.appendChild(card);
});

slider.appendChild(left);
slider.appendChild(right);
slider.appendChild(track);

featuredSection.appendChild(slider);
}

// ==========================================================================
// 5. MENU
// ==========================================================================
function applyMenu() {
const menu = $("#menu");
if (!menu || !cfg.menu?.categories) return;

const tabs = menu.querySelector(".category-tabs");
const panels =
  menu.querySelector(".panels-wrapper") ||
  menu.querySelector(".mt-6.space-y-10");

if (!tabs || !panels) return;

tabs.innerHTML = "";
panels.innerHTML = "";

cfg.menu.categories.forEach((cat, i) => {
  const cid = cat.id || `cat-${i}`;
  const tabId = `tab-${cid}`;

  const btn = document.createElement("button");
  btn.className =
    "tab-btn px-3 py-2 rounded-xl whitespace-nowrap flex flex-col items-center gap-1 text-sm";
  btn.dataset.target = `#${tabId}`;
  btn.setAttribute("aria-controls", tabId);
  btn.setAttribute("aria-selected", i === 0 ? "true" : "false");
  btn.innerHTML = `${pickIcon(cat.id, cat.name)}<span>${
    cat.name || "Catégorie"
  }</span>`;
  tabs.appendChild(btn);

  const panel = document.createElement("section");
  panel.id = tabId;
  panel.className = "tab-panel";
  if (i === 0) panel.dataset.active = "true";

  const header = document.createElement("div");
  header.className = "flex items-center justify-between mb-3";

  const h3 = document.createElement("h3");
  h3.className = "section-title text-xl md:text-2xl";
  h3.textContent = cat.name || "Catégorie";
  header.appendChild(h3);

  if (cat.description) {
    const p = document.createElement("p");
    p.className = "text-sm text-slate-500";
    p.textContent = cat.description;
    header.appendChild(p);
  }

  panel.appendChild(header);

  const slider = document.createElement("div");
  slider.className = "slider";

  const left = document.createElement("button");
  left.className = "arrow left";
  left.textContent = "‹";

  const right = document.createElement("button");
  right.className = "arrow right";
  right.textContent = "›";

  const track = document.createElement("div");
  track.className = "track";
  track.dataset.track = "";
  track.id = `${cid}-track`;

  (cat.items || []).forEach((item) => {
    const card = document.createElement("article");
    card.className = "card bg-white border rounded-3xl elev p-5";
    card.style.position = "relative";

    // ===== ÉTAT : NON DISPONIBLE =====
    if (item.status === "unavailable") {
      card.classList.add("is-unavailable");

      const unavailableBadge = document.createElement("div");
      unavailableBadge.className = "product-badge";
      unavailableBadge.textContent = "Indisponible";
      card.appendChild(unavailableBadge);
    }

    card.dataset.item =
      item.id ||
      (item.name || "").toLowerCase().replace(/\s+/g, "-");

    const kwParts = [
      item.name,
      item.description,
      cat.name,
      Array.isArray(item.tags) ? item.tags.join(" ") : "",
    ];
    card.dataset.keywords = kwParts.filter(Boolean).join(" ");

    // Image du produit
    const imgSrc = item.image || (item.imageKey && cfg.assets?.menuImages?.[item.imageKey]);
    if (imgSrc) {
      const img = document.createElement("img");
      img.className = "food-img";
      img.src = imgSrc;
      img.alt = item.name;
      card.appendChild(img);
    }

    const h4 = document.createElement("h4");
    h4.className = "card-title mt-3";
    h4.textContent = item.name;
    card.appendChild(h4);

    if (item.description) {
      const p = document.createElement("p");
      p.className = "text-sm text-slate-600";
      p.textContent = item.description;
      card.appendChild(p);
    }

    const priceBox = document.createElement("div");
    priceBox.className = "mt-2 flex flex-col gap-2";

    const soloPrice = item.priceSolo ?? item.price;
    if (soloPrice != null && item.status !== "unavailable") {
      const soloBtn = document.createElement("button");
      soloBtn.type = "button";

      soloBtn.className = "price-badge flex items-center justify-between";
      soloBtn.dataset.productId = item.id;
      soloBtn.dataset.variant = "solo";

      soloBtn.innerHTML = `
        <span>${soloPrice} € </span>
        <span class="price-plus">+</span>
      `;

      soloBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        if (typeof openTicketBuilder === "function") {
          openTicketBuilder(item.id, "solo");
        }
      });

      priceBox.appendChild(soloBtn);
    }

    if (item.priceMenu != null && item.status !== "unavailable") {
      const btnMenu = document.createElement("button");
      btnMenu.type = "button";
      btnMenu.className =
        "badge-menu flex items-center justify-between";
      btnMenu.dataset.productId = item.id;
      btnMenu.dataset.variant = "menu";

      btnMenu.innerHTML = `
        <span>${item.priceMenu} € en menu</span>
        <span class="price-plus">+</span>
      `;

      btnMenu.addEventListener("click", (e) => {
        e.stopPropagation();
        if (typeof openTicketBuilder === "function") {
          openTicketBuilder(item.id, "menu");
        }
      });

      priceBox.appendChild(btnMenu);
    }

    card.appendChild(priceBox);

    track.appendChild(card);
  });

  slider.appendChild(left);
  slider.appendChild(right);
  slider.appendChild(track);
  panel.appendChild(slider);

  const backBtn = document.createElement("button");
  backBtn.className =
    "mt-6 mx-auto block px-4 py-2 rounded-lg text-sm font-semibold btn-brand";
  backBtn.textContent = "↑ Retour au menu";
  backBtn.addEventListener("click", () => {
    const tabsRow = document.querySelector("#menu .category-tabs");
    if (!tabsRow) return;

    const headerEl = document.querySelector("header");
    const headerHeight = headerEl ? headerEl.offsetHeight : 80;

    const rect = tabsRow.getBoundingClientRect();
    const targetTop = rect.top + window.scrollY - headerHeight - 8;

    window.scrollTo({
      top: targetTop,
      behavior: "smooth",
    });
  });

  panel.appendChild(backBtn);
  panels.appendChild(panel);
});
}


  // ==========================================================================
  // 6. AVIS
  // ==========================================================================
  function applyReviews() {
    console.log("🔍 [REVIEWS] applyReviews() called");
    const sec = $("#avis");
    if (!sec) {
      console.error("❌ [REVIEWS] Section #avis not found!");
      return;
    }
    console.log("✅ [REVIEWS] Section #avis found");

    const title = $("h2.section-title", sec);
    const ratingBox = sec.querySelector(".flex.items-center.gap-2.mt-2 .flex.items-center.gap-2.text-sm");
    const starsContainer = ratingBox?.querySelector(".flex.text-yellow-500");
    const reviewsCountSpan = ratingBox?.querySelector("span.text-slate-500");

    let rating = null;
    let reviewCount = null;
    let sourceUrl = null;
    let sourceLabel = null;

    if (cfg.google?.rating && cfg.google?.reviewCount) {
      rating = cfg.google.rating;
      reviewCount = cfg.google.reviewCount;
      sourceUrl = cfg.google.url;
      sourceLabel = "Google";
    } else if (cfg.uber?.rating && cfg.uber?.reviewCount) {
      rating = cfg.uber.rating;
      reviewCount = cfg.uber.reviewCount;
      sourceUrl = cfg.uber.url;
      sourceLabel = "Uber Eats";
    } else {
      sec.classList.add("hidden");
      return;
    }

    if (ratingBox) {
      const ratingEl = ratingBox.querySelector(".font-semibold");
      if (ratingEl) {
        ratingEl.textContent = rating.toString().replace(".", ",");
      }
    }

    if (starsContainer) {
      const rounded = Math.round(rating);
      const stars = starsContainer.querySelectorAll("svg");
      stars.forEach((s, i) => {
        s.style.opacity = i < rounded ? "1" : "0.5";
      });
    }

    if (reviewsCountSpan) {
      reviewsCountSpan.textContent = `· ${reviewCount}+ avis ${sourceLabel}`;
    }

    const allReviewsLink = $("#all-reviews-link", sec);
    if (allReviewsLink && sourceUrl) {
      allReviewsLink.href = sourceUrl;
      allReviewsLink.textContent = `Voir tous les avis ${sourceLabel}`;
    }

    // Générer les cartes d'avis individuels
    const reviewsTrack = $("#reviews-track", sec);
    if (reviewsTrack && cfg.reviews && Array.isArray(cfg.reviews) && cfg.reviews.length > 0) {
      reviewsTrack.innerHTML = "";

      cfg.reviews.forEach((review) => {
        const figure = document.createElement("figure");

        // Header avec nom et étoiles
        const header = document.createElement("div");
        header.className = "flex items-start justify-between mb-3";

        const authorDiv = document.createElement("div");
        const authorName = document.createElement("div");
        authorName.className = "font-semibold text-sm";
        authorName.textContent = review.author;
        authorDiv.appendChild(authorName);

        if (review.relativeTime) {
          const timeSpan = document.createElement("div");
          timeSpan.className = "text-xs text-slate-500 mt-0.5";
          timeSpan.textContent = review.relativeTime;
          authorDiv.appendChild(timeSpan);
        }

        // Étoiles
        const starsDiv = document.createElement("div");
        starsDiv.className = "flex text-yellow-500 text-sm";
        for (let i = 0; i < 5; i++) {
          const star = document.createElement("span");
          star.textContent = i < review.rating ? "★" : "☆";
          starsDiv.appendChild(star);
        }

        header.appendChild(authorDiv);
        header.appendChild(starsDiv);
        figure.appendChild(header);

        // Context
        if (review.context) {
          const contextP = document.createElement("p");
          contextP.className = "text-xs text-slate-500 mb-2";
          contextP.textContent = review.context;
          figure.appendChild(contextP);
        }

        // Texte de l'avis
        if (review.text) {
          const blockquote = document.createElement("blockquote");
          blockquote.className = "text-sm text-slate-700";
          blockquote.textContent = review.text;
          figure.appendChild(blockquote);
        }

        // Aspects (cuisine, service, ambiance)
        if (review.aspects) {
          const aspectsDiv = document.createElement("div");
          aspectsDiv.className = "mt-3 flex gap-3 text-xs";

          Object.entries(review.aspects).forEach(([key, value]) => {
            if (value !== null) {
              const badge = document.createElement("span");
              badge.className = "px-2 py-1 bg-slate-100 rounded-full";
              const label = key.charAt(0).toUpperCase() + key.slice(1);
              badge.textContent = `${label}: ${value}/5`;
              aspectsDiv.appendChild(badge);
            }
          });

          if (aspectsDiv.children.length > 0) {
            figure.appendChild(aspectsDiv);
          }
        }

        reviewsTrack.appendChild(figure);
      });
    }

    // Initialiser le slider d'avis - EN DEHORS du bloc if et avec un délai
    console.log("🕐 [REVIEWS] Scheduling slider initialization in 200ms...");
    setTimeout(() => {
      const prev = document.getElementById("r-prev");
      const next = document.getElementById("r-next");
      const track = document.getElementById("reviews-track");

      console.log("🔍 [SLIDER] Initializing...", {
        prev: !!prev,
        next: !!next,
        track: !!track,
        trackChildren: track ? track.children.length : 0
      });

      if (!prev || !next || !track) {
        console.error("❌ [SLIDER] Elements not found!");
        return;
      }

      let items = Array.from(track.children);
      if (items.length === 0) {
        console.warn("⚠️ [SLIDER] No review items found");
        return;
      }

      let index = 0;

      function updateSlider() {
        items = Array.from(track.children);
        if (items.length === 0) return;

        const offset = -index * 100;
        track.style.transform = `translateX(${offset}%)`;
        console.log("🎯 [SLIDER] Updated:", { index, offset, totalItems: items.length });
      }

      // Handler avec stopPropagation pour empêcher script.js d'interférer
      const handlePrev = (e) => {
        e.stopPropagation();
        e.preventDefault();
        console.log("⬅️ [SLIDER] Prev clicked, current index:", index);
        index = (index - 1 + items.length) % items.length;
        updateSlider();
      };

      const handleNext = (e) => {
        e.stopPropagation();
        e.preventDefault();
        console.log("➡️ [SLIDER] Next clicked, current index:", index);
        index = (index + 1) % items.length;
        updateSlider();
      };

      // Utiliser la phase de capture (true) pour intercepter AVANT script.js
      prev.addEventListener("click", handlePrev, true);
      next.addEventListener("click", handleNext, true);

      console.log("✅ [SLIDER] Event listeners attached");

      // Auto-play toutes les 8 secondes
      let autoSlide = setInterval(() => {
        index = (index + 1) % items.length;
        updateSlider();
      }, 8000);

      // Pause au survol des flèches
      [prev, next].forEach((btn) => {
        if (!btn) return;
        btn.addEventListener("mouseenter", () => clearInterval(autoSlide));
        btn.addEventListener("mouseleave", () => {
          autoSlide = setInterval(() => {
            index = (index + 1) % items.length;
            updateSlider();
          }, 8000);
        });
      });

      console.log("✅ [SLIDER] Fully initialized with", items.length, "items");
    }, 200); // Délai pour s'assurer que tout est dans le DOM
  }

  // ==========================================================================
  // 6 BIS. FAQ
  // ==========================================================================
  function applyFaq() {
    // 🔧 CORRECTION : Sélecteurs multiples pour trouver la section FAQ
    const sec =
      document.querySelector("#faq") ||
      document.querySelector("section#faq") ||
      document.querySelector('[data-section="faq"]') ||
      Array.from(document.querySelectorAll("section")).find(s =>
        s.querySelector("h2")?.textContent.toLowerCase().includes("faq") ||
        s.querySelector("h2")?.textContent.toLowerCase().includes("questions")
      );

    if (!sec) {
      console.warn("⚠️ Section FAQ introuvable dans le HTML");
      return;
    }

    const faqCfg = cfg.faq;

    if (
      !faqCfg ||
      faqCfg.enabled === false ||
      !Array.isArray(faqCfg.items) ||
      !faqCfg.items.length
    ) {
      sec.classList.add("hidden");
      return;
    }

    let list =
      sec.querySelector("[data-faq-list]") ||
      sec.querySelector("dl") ||
      sec.querySelector(".faq-list") ||
      sec.querySelector(".space-y-4");

    if (!list) {
      list = document.createElement("div");
      list.className = "space-y-3 mt-6";
      sec.appendChild(list);
    } else {
      list.innerHTML = "";
    }

    // 🎨 Récupération des couleurs du thème
    const brandColor = cfg.theme?.colors?.brand || "#e11b22";
    const brandSoft = cfg.theme?.colors?.brandSoft || "#fee2e2";

    faqCfg.items.forEach((item, index) => {
      const q = (item && item.question) || "";
      const a = (item && item.answer) || "";

      if (!q && !a) return;

      // Wrapper de l'item accordéon
      const wrapper = document.createElement("div");
      wrapper.className = "faq-item border border-gray-200 rounded-lg overflow-hidden transition-all duration-200 hover:shadow-md";
      wrapper.style.backgroundColor = "#ffffff";

      // Bouton question (cliquable)
      const button = document.createElement("button");
      button.className = "faq-question w-full px-5 py-4 flex items-center justify-between text-left transition-colors duration-200";
      button.setAttribute("aria-expanded", "false");
      button.setAttribute("aria-controls", `faq-answer-${index}`);

      const questionText = document.createElement("span");
      questionText.className = "font-semibold text-base text-gray-900 pr-4 flex-1";
      questionText.textContent = q;

      // Icône + / -
      const icon = document.createElement("span");
      icon.className = "faq-icon flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full transition-all duration-300 font-bold text-lg";
      icon.style.backgroundColor = brandColor;
      icon.style.color = "#ffffff";
      icon.textContent = "+";

      button.appendChild(questionText);
      button.appendChild(icon);

      // Conteneur de la réponse (masqué par défaut)
      const answerWrapper = document.createElement("div");
      answerWrapper.id = `faq-answer-${index}`;
      answerWrapper.className = "faq-answer overflow-hidden transition-all duration-300 ease-in-out";
      answerWrapper.style.maxHeight = "0";
      answerWrapper.style.opacity = "0";

      const answer = document.createElement("div");
      answer.className = "px-5 pb-4 pt-0 text-sm text-gray-700 leading-relaxed";
      answer.style.backgroundColor = brandSoft;
      answer.textContent = a;

      answerWrapper.appendChild(answer);

      // 🎯 Gestion du clic pour ouvrir/fermer
      button.addEventListener("click", function() {
        const isOpen = button.getAttribute("aria-expanded") === "true";

        if (isOpen) {
          // Fermer
          button.setAttribute("aria-expanded", "false");
          answerWrapper.style.maxHeight = "0";
          answerWrapper.style.opacity = "0";
          icon.textContent = "+";
          icon.style.transform = "rotate(0deg)";
          button.style.backgroundColor = "transparent";
        } else {
          // Ouvrir
          button.setAttribute("aria-expanded", "true");
          answerWrapper.style.maxHeight = answerWrapper.scrollHeight + "px";
          answerWrapper.style.opacity = "1";
          icon.textContent = "−";
          icon.style.transform = "rotate(90deg)";
          button.style.backgroundColor = brandSoft;
        }
      });

      wrapper.appendChild(button);
      wrapper.appendChild(answerWrapper);
      list.appendChild(wrapper);
    });

    sec.classList.remove("hidden");
  }

  // ==========================================================================
  // 7. FOOTER
  // ==========================================================================
  function applyFooter() {
    const footer = $("footer");
    if (!footer) return;

    const cols = $$(".max-w-7xl.mx-auto.px-4 > div", footer);
    const identCol = cols[0];
    const horairesCol = cols[1];
    const contactCol = cols[2];
    const linksCol = cols[3];

    if (identCol) {
      const logo = identCol.querySelector("img");
      if (logo && cfg.assets?.logo) {
        logo.src =
          cfg.assets.logo.dark || cfg.assets.logo.light || logo.src;
        logo.alt = cfg.assets.logo.alt || snackName;
        logo.classList.add("footer-logo");

        const bg =
          cfg.theme?.footerLogoBackground ||
          cfg.theme?.colors?.surfaceAlt || null;

        if (
          bg &&
          !logo.parentElement.classList.contains("footer-logo-wrapper")
        ) {
          const wrap = document.createElement("div");
          wrap.className = "footer-logo-wrapper";
          wrap.style.background = bg;
          wrap.style.padding = "16px 28px";
          wrap.style.borderRadius = "14px";
          wrap.style.display = "inline-flex";
          wrap.style.alignItems = "center";
          wrap.style.justifyContent = "center";

          logo.parentNode.insertBefore(wrap, logo);
          wrap.appendChild(logo);
        }
      }

      const p = identCol.querySelector("p.text-slate-600");
      if (cfg.hideFooterAddress) {
        if (p) p.remove();
      } else if (p && fullAddress) {
        p.textContent = fullAddress;
      }
    }

    if (horairesCol && Array.isArray(cfg.openingHours)) {
      const box =
        horairesCol.querySelector(".bg-slate-50") || horairesCol;
      box.innerHTML = "";
      cfg.openingHours.forEach((h) => {
        const p = document.createElement("p");
        p.innerHTML = `<strong>${capitalize(
          h.day
        )} :</strong> ${h.opens}–${h.closes}`;
        box.appendChild(p);
      });
    }

    if (contactCol) {
      const addr = contactCol.querySelector("p.text-slate-600");
      if (addr) addr.textContent = fullAddress;

      const telLink =
        contactCol.querySelector("a[href^='tel']") ||
        contactCol.querySelector("a.mt-1.block");
      if (telLink) {
        telLink.href = phoneHref;
        telLink.textContent = phoneDisplay;
      }

      const socialBox =
        contactCol.querySelector(".footer-social") ||
        contactCol.querySelector(".flex.gap-3.mt-4.items-center");
      if (socialBox) {
        socialBox.innerHTML = "";
        fillSocialIcons(socialBox, cfg.social || {}, "footer");
      }
    }

    if (linksCol && Array.isArray(cfg.platforms) && cfg.platforms.length) {
      const platList =
        linksCol.querySelector("ul.space-y-1") ||
        linksCol.querySelector("ul");
      if (platList) {
        platList.innerHTML = "";
        cfg.platforms.forEach((p) => {
          platList.appendChild(createPlatformItemFooter(p));
        });
      }
    }

    const agency = cfg.agency;
    if (agency?.whatsapp && agency?.name) {
      const creditP = Array.from(footer.querySelectorAll("p")).find((p) =>
        p.textContent.toLowerCase().includes("propulsé par")
      );

      if (creditP) {
        creditP.innerHTML = `
          Propulsé par
          <a href="${agency.whatsapp}" target="_blank" rel="noopener" class="agency-link">
            ${agency.name}
          </a>
        `;
      }
    }
  }

  // ==========================================================================
  // 8. DRAWER (menu mobile)
  // ==========================================================================
  function applyDrawer() {
    const drawer = $("#drawer");
    const navBtn = $("#navBtn");
    const closeBtn = $("#closeNav");
    if (!drawer || !navBtn) return;

    const overlay = drawer.firstElementChild;
    const panel = drawer.lastElementChild;

    if (panel && cfg.theme?.colors) {
      const colors = cfg.theme.colors;
      const drawerBg =
        colors.drawerBackground ||
        colors.surfaceAlt ||
        colors.surface ||
        "#5b4330";
      const drawerText =
        colors.drawerText || colors.textOnDark || "#fdfbf7";

      panel.style.backgroundColor = drawerBg;
      panel.style.color = drawerText;
      panel.style.borderColor = drawerBg;
    }

    if (overlay) {
      overlay.style.display = "none";
      overlay.style.pointerEvents = "none";
      overlay.style.background = "transparent";
    }

    const openDrawer = (e) => {
      if (e) e.preventDefault();
      drawer.classList.add("open");
      document.body.classList.add("navlock");
      if (panel) panel.style.transform = "translateX(0)";
    };
    const closeDrawer = () => {
      drawer.classList.remove("open");
      document.body.classList.remove("navlock");
      if (panel) panel.style.transform = "translateX(100%)";
    };

    navBtn.addEventListener("click", openDrawer);
    if (closeBtn) closeBtn.addEventListener("click", closeDrawer);

    $$(".drawer-link", drawer).forEach((link) =>
      link.addEventListener("click", closeDrawer)
    );

    const header = drawer.querySelector(".p-4 .flex.items-center.gap-2");
    if (header) {
      const img = header.querySelector("img");
      const nameEl = header.querySelector("p.font-semibold");
      const addrEl = header.querySelector("p.text-xs");
      if (img && cfg.assets?.logo) {
        img.src = cfg.assets.logo.dark || cfg.assets.logo.light || img.src;
        img.alt = cfg.assets.logo.alt || snackName;
      }
      if (nameEl) nameEl.textContent = snackName;
      if (addrEl) addrEl.textContent = fullAddress;
    }

    const coordSection = Array.from(drawer.querySelectorAll("section")).find(
      (sec) => sec.textContent.toLowerCase().includes("coordonnées")
    );
    if (coordSection) {
      const telBadge =
        coordSection.querySelector("a[href^='tel']") ||
        coordSection.querySelector("a.inline-flex");
      if (telBadge) {
        telBadge.href = phoneHref;
        const spanNum = telBadge.querySelector("span:last-child");
        if (spanNum) spanNum.textContent = phoneDisplay;
      }
      const addrSpan = coordSection.querySelector("p span:last-child");
      if (addrSpan) addrSpan.textContent = fullAddress;
    }

    const hoursSection = Array.from(drawer.querySelectorAll("section")).find(
      (sec) => sec.textContent.toLowerCase().includes("horaires")
    );
    if (hoursSection && Array.isArray(cfg.openingHours)) {
      hoursSection.innerHTML = "";
      cfg.openingHours.forEach((h) => {
        const p = document.createElement("p");
        p.textContent = `${capitalize(h.day)} : ${h.opens}–${h.closes}`;
        p.className = "text-sm";
        hoursSection.appendChild(p);
      });
    }

    const platsSection = Array.from(drawer.querySelectorAll("section")).find(
      (sec) => sec.textContent.toLowerCase().includes("livraison")
    );
    if (platsSection && Array.isArray(cfg.platforms) && cfg.platforms.length) {
      let grid = platsSection.querySelector(".grid");
      if (!grid) {
        grid = document.createElement("div");
        grid.className = "grid grid-cols-1 gap-2";
        platsSection.appendChild(grid);
      }
      grid.innerHTML = "";
      cfg.platforms.forEach((p) => {
        grid.appendChild(createPlatformCardDrawer(p));
      });
    }

    const socialSection = Array.from(drawer.querySelectorAll("section")).find(
      (sec) => sec.textContent.toLowerCase().includes("réseaux")
    );
    if (socialSection) {
      const box =
        socialSection.querySelector(".flex") ||
        socialSection.querySelector("div");
      if (box) {
        box.innerHTML = "";
        fillSocialIcons(box, cfg.social || {}, "drawer");
      }
    }
  }

  // ==========================================================================
  // 9. BOUTON FLOTTANT APPEL
  // ==========================================================================
  function applyFloatingCall() {
    const callBtn =
      document.querySelector("a[aria-label^='Appeler']") ||
      document.querySelector(".floating-call");
    if (!callBtn) return;
    callBtn.href = phoneHref;
    callBtn.setAttribute("aria-label", `Appeler ${snackName}`);
  }

  // ==========================================================================
  // 10. SEO & JSON-LD
  // ==========================================================================
  function applySeo() {
    if (cfg.seo?.title) document.title = cfg.seo.title;

    if (cfg.seo?.description) {
      let m = $("meta[name='description']");
      if (!m) {
        m = document.createElement("meta");
        m.name = "description";
        document.head.appendChild(m);
      }
      m.content = cfg.seo.description;
    }

    if (cfg.urls?.website) {
      let c = $("link[rel='canonical']");
      if (!c) {
        c = document.createElement("link");
        c.rel = "canonical";
        document.head.appendChild(c);
      }
      c.href = cfg.urls.website;
    }

    const sameAs = [];
    if (cfg.social?.instagram) sameAs.push(cfg.social.instagram);
    if (cfg.social?.facebook) sameAs.push(cfg.social.facebook);
    if (cfg.social?.tiktok) sameAs.push(cfg.social.tiktok);
    if (cfg.social?.snapchat) sameAs.push(cfg.social.snapchat);
    if (cfg.social?.snap) sameAs.push(cfg.social.snap);

    const ld = {
      "@context": "https://schema.org",
      "@type": "Restaurant",
      name: snackName,
      telephone: phoneRaw || undefined,
      image: cfg.assets?.hero?.image || undefined,
      address: {
        "@type": "PostalAddress",
        streetAddress: cfg.location?.addressLine1,
        postalCode: cfg.location?.postalCode,
        addressLocality: cfg.location?.city,
        addressCountry: cfg.location?.countryCode || "FR",
      },
      url: cfg.urls?.website || undefined,
      priceRange: cfg.priceRange || undefined,
      servesCuisine: cfg.google?.cuisine || undefined,
      sameAs: sameAs,
    };

    if (cfg.google?.rating && cfg.google?.reviewCount) {
      ld.aggregateRating = {
        "@type": "AggregateRating",
        ratingValue: cfg.google.rating,
        ratingCount: cfg.google.reviewCount,
      };
    }

    const s = document.createElement("script");
    s.type = "application/ld+json";
    s.innerHTML = JSON.stringify(ld);
    document.head.appendChild(s);
  }

  // ==========================================================================
  // SECTION "COMMANDEZ EN LIVRAISON"
  // ==========================================================================
  function applyDeliverySection() {
    const section = $("#delivery");
    if (!section) return;

    const grid = $("#delivery-grid", section);
    if (!grid) return;

    if (!Array.isArray(cfg.platforms) || !cfg.platforms.length) {
      section.style.display = "none";
      return;
    }

    grid.innerHTML = "";

    cfg.platforms.forEach((p) => {
      grid.appendChild(createPlatformCardMain(p));
    });
  }

  // ==========================================================================
  // TICKET BUILDER – panier multi-produits
  // ==========================================================================
  var ticketLines = [];
  var activeLine = null;
  var ticketPanel = null;
  var ticketToggle = null;

  function asArray(value) {
    return Array.isArray(value) ? value : [];
  }

  function ensureTicketShell() {
    if (ticketPanel && ticketToggle) return;

    ticketToggle = document.createElement("button");
    ticketToggle.id = "ticket-toggle";
    ticketToggle.type = "button";
    ticketToggle.className =
      "fixed left-4 bottom-4 z-40 flex items-center gap-2 px-3 py-2 rounded-full bg-brand text-white shadow-lg text-sm font-semibold";
    ticketToggle.innerHTML = `<span class="text-lg">🎟️</span><span>Ticket</span>`;

    ticketPanel = document.createElement("aside");
    ticketPanel.id = "ticket-panel";
    ticketPanel.className =
      "fixed inset-x-0 bottom-0 z-[9999] md:left-4 md:right-auto md:bottom-20 md:w-80 max-h-[95vh] bg-white rounded-t-3xl md:rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden hidden";

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

        <button id="ticket-share" type="button"
                class="w-full btn-brand rounded-full py-2 text-sm font-semibold flex items-center justify-center gap-2">
          <span>Envoyer / partager le ticket</span>
        </button>

        <button id="ticket-share-restaurant" type="button"
                class="w-full rounded-full py-2 text-sm font-semibold flex items-center justify-center gap-2 border border-slate-300 text-slate-700 bg-white">
          <span>Envoyer directement au resto</span>
        </button>
      </div>
    `;

    document.body.appendChild(ticketToggle);
    document.body.appendChild(ticketPanel);

    ticketToggle.addEventListener("click", () => {
      ticketPanel.classList.toggle("hidden");
    });

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

      if (action === "set-kids-plate") {
        const plateId = actionEl.dataset.plateId;
        if (activeLine && activeLine.categoryId === "menu-enfant") {
          activeLine.kidsChoice = plateId;
          renderTicketPanel();
        }
      }

      if (action === "set-drink") {
        const drinkId = actionEl.dataset.drinkId;
        if (activeLine) {
          activeLine.drinkChoice = drinkId;
          renderTicketPanel();
        }
      }

      if (action === "set-tacos-base") {
        const baseId = actionEl.dataset.baseId;
        setTacosBase(baseId);
      }

      if (action === "toggle-tacos-meat") {
        const meat = actionEl.dataset.meatName;
        toggleTacosMeat(meat);
      }

      if (action === "toggle-tacos-sauce") {
        const sauce = actionEl.dataset.sauceName;
        toggleTacosSauce(sauce);
      }

      if (action === "toggle-tacos-veg") {
        const veg = actionEl.dataset.veggie;
        toggleTacosVeg(veg);
      }

      // 🆕 KAPSALOON ACTIONS
      if (action === "set-kapsaloon-base") {
        const baseId = actionEl.dataset.baseId;
        setKapsaloonBase(baseId);
      }

      if (action === "toggle-kapsaloon-meat") {
        const meat = actionEl.dataset.meatName;
        toggleKapsaloonMeat(meat);
      }

      if (action === "toggle-kapsaloon-sauce") {
        const sauce = actionEl.dataset.sauceName;
        toggleKapsaloonSauce(sauce);
      }

      if (action === "inc-qty") {
        const id = actionEl.dataset.lineId;
        incrementLineQuantity(id);
      }

      if (action === "dec-qty") {
        const id = actionEl.dataset.lineId;
        decrementLineQuantity(id);
      }

      if (action === "set-main-sauce") {
        const sauce = actionEl.dataset.sauceName;
        if (sauce) {
          setMainSauce(sauce);
        }
      }
    });

    const shareBtn = ticketPanel.querySelector("#ticket-share");
    if (shareBtn) {
      shareBtn.addEventListener("click", shareTicket);
    }

    const shareRestaurantBtn = ticketPanel.querySelector(
      "#ticket-share-restaurant"
    );
    if (shareRestaurantBtn) {
      shareRestaurantBtn.addEventListener("click", shareTicketToRestaurant);
    }
  }

  function findProductById(productId) {
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

  // ==========================================================================
  // FONCTIONS TACOS
  // ==========================================================================

  function getTacosBaseForLine(line) {
    if (!line || !line.productId) return null;

    const product = findProductById(line.productId)?.item;
    if (
      !product ||
      !product.tacosConfig ||
      !Array.isArray(product.tacosConfig.bases)
    ) {
      return null;
    }

    return (
      product.tacosConfig.bases.find((base) => base.id === line.tacosBaseId) ||
      null
    );
  }

  function getTacosMaxMeatsForLine(line) {
    const base = getTacosBaseForLine(line);
    if (!base || typeof base.meats !== "number") return 1;
    return base.meats;
  }

  function setTacosBase(baseId) {
    if (!activeLine) return;

    const product = findProductById(activeLine.productId)?.item;
    if (!product?.tacosConfig) return;

    const base = product.tacosConfig.bases.find((b) => b.id === baseId);
    if (!base) return;

    const menuUpcharge = (cfg.tacos && cfg.tacos.menuUpcharge) || 2;

    const basePrice =
      activeLine.variant === "menu"
        ? base.price + menuUpcharge
        : base.price;

    activeLine.tacosBaseId = baseId;
    activeLine.basePrice = basePrice;
    activeLine.lineTotal = basePrice + calculateTacosExtras(activeLine);

    renderTicketPanel();
  }

  function toggleTacosMeat(meat) {
    if (!activeLine || activeLine.categoryId !== "tacos") return;

    const maxMeats = getTacosMaxMeatsForLine(activeLine);
    let meats = Array.isArray(activeLine.tacosMeats)
      ? activeLine.tacosMeats.slice()
      : [];

    const idx = meats.indexOf(meat);

    if (idx >= 0) {
      meats.splice(idx, 1);
    } else {
      if (meats.length >= maxMeats) return;
      meats.push(meat);
    }

    activeLine.tacosMeats = meats;
    activeLine.lineTotal = activeLine.basePrice + calculateTacosExtras(activeLine);

    renderTicketPanel();
  }

  function toggleTacosSauce(sauce) {
    if (!activeLine || activeLine.categoryId !== "tacos") return;

    let sauces = Array.isArray(activeLine.tacosSauces)
      ? activeLine.tacosSauces.slice()
      : [];

    const idx = sauces.indexOf(sauce);

    if (idx >= 0) {
      sauces.splice(idx, 1);
    } else {
      if (sauces.length >= 2) return;
      sauces.push(sauce);
    }

    activeLine.tacosSauces = sauces;
    renderTicketPanel();
  }

  function toggleTacosVeg(veg) {
    if (!activeLine) return;

    let veggies = Array.isArray(activeLine.tacosVeggies)
      ? activeLine.tacosVeggies.slice()
      : [];

    const idx = veggies.indexOf(veg);

    if (idx >= 0) {
      veggies.splice(idx, 1);
    } else {
      veggies.push(veg);
    }

    activeLine.tacosVeggies = veggies;
    renderTicketPanel();
  }

  function calculateTacosExtras(line) {
    let total = 0;
    const supps = asArray(line.supplements);

    for (const sid of supps) {
      const def = cfg.supplements?.catalog?.[sid];
      if (def && typeof def.price === "number") {
        total += def.price;
      }
    }
    return total;
  }

  // ==========================================================================
  // 🆕 FONCTIONS KAPSALOON (copie tacos sans crudités)
  // ==========================================================================

  function getKapsaloonBaseForLine(line) {
    if (!line || !line.productId) return null;

    const product = findProductById(line.productId)?.item;
    if (
      !product ||
      !product.kapsaloonConfig ||
      !Array.isArray(product.kapsaloonConfig.bases)
    ) {
      return null;
    }

    return (
      product.kapsaloonConfig.bases.find((base) => base.id === line.kapsaloonBaseId) ||
      null
    );
  }

  function getKapsaloonMaxMeatsForLine(line) {
    const base = getKapsaloonBaseForLine(line);
    if (!base || typeof base.meats !== "number") return 1;
    return base.meats;
  }

  function setKapsaloonBase(baseId) {
    if (!activeLine) return;

    const product = findProductById(activeLine.productId)?.item;
    if (!product?.kapsaloonConfig) return;

    const base = product.kapsaloonConfig.bases.find((b) => b.id === baseId);
    if (!base) return;

    activeLine.kapsaloonBaseId = baseId;
    activeLine.basePrice = base.price;
    activeLine.lineTotal = base.price + calculateKapsaloonExtras(activeLine);

    renderTicketPanel();
  }

  function toggleKapsaloonMeat(meat) {
    if (!activeLine || activeLine.categoryId !== "kapsaloon") return;

    const maxMeats = getKapsaloonMaxMeatsForLine(activeLine);
    let meats = Array.isArray(activeLine.kapsaloonMeats)
      ? activeLine.kapsaloonMeats.slice()
      : [];

    const idx = meats.indexOf(meat);

    if (idx >= 0) {
      meats.splice(idx, 1);
    } else {
      if (meats.length >= maxMeats) return;
      meats.push(meat);
    }

    activeLine.kapsaloonMeats = meats;
    activeLine.lineTotal = activeLine.basePrice + calculateKapsaloonExtras(activeLine);

    renderTicketPanel();
  }

  function toggleKapsaloonSauce(sauce) {
    if (!activeLine || activeLine.categoryId !== "kapsaloon") return;

    let sauces = Array.isArray(activeLine.kapsaloonSauces)
      ? activeLine.kapsaloonSauces.slice()
      : [];

    const idx = sauces.indexOf(sauce);

    if (idx >= 0) {
      sauces.splice(idx, 1);
    } else {
      if (sauces.length >= 2) return;
      sauces.push(sauce);
    }

    activeLine.kapsaloonSauces = sauces;
    renderTicketPanel();
  }

  function calculateKapsaloonExtras(line) {
    let total = 0;
    const supps = asArray(line.supplements);

    for (const sid of supps) {
      const def = cfg.supplements?.catalog?.[sid];
      if (def && typeof def.price === "number") {
        total += def.price;
      }
    }
    return total;
  }

  // ==========================================================================
  // FONCTION SAUCE PRINCIPALE
  // ==========================================================================

  function setMainSauce(sauceName) {
    if (!activeLine) return;

    const sauceCategories = [
      "burgers",
      "sandwichs",
      "paninis",
      "signatures",
      "galettes",
    ];

    if (!sauceCategories.includes(activeLine.categoryId)) return;
    if (!sauceName) return;

    let sauces = Array.isArray(activeLine.mainSauce)
      ? [...activeLine.mainSauce]
      : activeLine.mainSauce
      ? [activeLine.mainSauce]
      : [];

    const idx = sauces.indexOf(sauceName);

    if (idx >= 0) {
      sauces.splice(idx, 1);
    } else {
      if (sauces.length >= 2) return;
      sauces.push(sauceName);
    }

    activeLine.mainSauce = sauces.length === 1 ? sauces[0] : sauces;

    renderTicketPanel();
  }

  // ==========================================================================
  // HELPERS SUPPLÉMENTS & INGRÉDIENTS
  // ==========================================================================

  function getDefaultSuppForCategory(categoryId) {
    if (categoryId && categoryId.toLowerCase().includes("menu-enfant")) {
      return [];
    }

    const sup = cfg.supplements;
    if (!sup || !sup.catalog) return [];

    const ids = sup.defaultForCategories?.[categoryId];
    if (!Array.isArray(ids) || !ids.length) {
      return [];
    }

    return ids.map((id) => sup.catalog[id]).filter(Boolean);
  }

  function getMenuDrinks() {
    if (!cfg.menu?.categories) return [];

    const drinkCat = cfg.menu.categories.find((cat) => {
      const id = (cat.id || "").toLowerCase();
      const name = (cat.name || "").toLowerCase();
      return id === "boissons" || name.includes("boisson");
    });

    return drinkCat?.items || [];
  }

  // ==========================================================================
  // OUVERTURE DU TICKET BUILDER
  // ==========================================================================

  /*
  ⚠️ FONCTION DÉSACTIVÉE - Le système de modal dans script.js gère maintenant l'ouverture

  function openTicketBuilder(productId, variant) {
    ensureTicketShell();

    const found = findProductById(productId);
    if (!found) return;
    const { categoryId, item } = found;

    const isMenu = variant === "menu";

    const drinksCat =
      cfg.menu &&
      cfg.menu.categories &&
      cfg.menu.categories.find(
        (c) =>
          c.id === "boissons" ||
          (c.name || "").toLowerCase().includes("boisson")
      );
    const drinkItems = drinksCat ? drinksCat.items || [] : [];

    const sauceCategories = [
      "burgers",
      "sandwichs",
      "paninis",
      "signatures",
      "galettes",
    ];
    const globalSauces = Array.isArray(cfg.sauces) ? cfg.sauces : [];

    let basePrice;

    // 🆕 CAS KAPSALOON (avant tacos pour vérifier en priorité)
    if (
      categoryId === "kapsaloon" &&
      item.kapsaloonConfig &&
      Array.isArray(item.kapsaloonConfig.bases) &&
      item.kapsaloonConfig.bases.length
    ) {
      const bases = item.kapsaloonConfig.bases;
      const firstBase = bases[0];

      basePrice = firstBase.price;

      activeLine = {
        id: "line_" + Date.now() + "_" + Math.random().toString(16).slice(2),
        productId: item.id,
        productName: item.name,
        categoryId,
        variant: "solo",
        basePrice,
        quantity: 1,
        supplements: [],
        removedIngredients: [],
        baseIngredients: [],
        drinkChoice: null,
        forbiddenSupp: [],
        availableDrinks: [],
        lineTotal: basePrice,
        kapsaloonBaseId: firstBase.id,
        kapsaloonMeats: [],
        kapsaloonSauces: []
      };

      ticketPanel.classList.remove("hidden");
      renderTicketPanel();
      return;
    }

    // CAS TACOS
    if (
      categoryId === "tacos" &&
      item.tacosConfig &&
      Array.isArray(item.tacosConfig.bases) &&
      item.tacosConfig.bases.length
    ) {
      const bases = item.tacosConfig.bases;
      const firstBase = bases[0];

      const menuUpcharge =
        (window.SNACK_CONFIG &&
          window.SNACK_CONFIG.tacos &&
          window.SNACK_CONFIG.tacos.menuUpcharge) ||
        2;

      basePrice = isMenu ? firstBase.price + menuUpcharge : firstBase.price;

      activeLine = {
        id: "line_" + Date.now() + "_" + Math.random().toString(16).slice(2),
        productId: item.id,
        productName: item.name,
        categoryId,
        variant,
        basePrice,
        quantity: 1,
        supplements: [],
        removedIngredients: [],
        baseIngredients: asArray(item.baseIngredients),
        drinkChoice: null,
        forbiddenSupp: isMenu ? [] : ["cheddar_frites", "boisson_menu"],
        availableDrinks: isMenu ? drinkItems : [],
        lineTotal: basePrice,
        tacosBaseId: firstBase.id,
        tacosMeats: [],
        tacosSauces: [],
        tacosVeggies: [],
      };
    } else {
      // CAS STANDARD
      basePrice =
        variant === "menu" ? item.priceMenu : item.priceSolo ?? item.price;

      if (basePrice == null) return;

      const canChooseSauce = sauceCategories.includes(categoryId);

      activeLine = {
        id: "line_" + Date.now() + "_" + Math.random().toString(16).slice(2),
        productId: item.id,
        productName: item.name,
        categoryId,
        variant,
        basePrice,
        quantity: 1,
        supplements: [],
        removedIngredients: [],
        baseIngredients: asArray(item.baseIngredients),
        drinkChoice: null,
        lineTotal: basePrice,
        forbiddenSupp: isMenu ? [] : ["cheddar_frites", "boisson_menu"],
        availableDrinks: isMenu ? drinkItems : [],
        mainSauce: null,
        availableSauces: canChooseSauce ? globalSauces : [],
      };
    }

    ticketPanel.classList.remove("hidden");
    renderTicketPanel();
  }
  */

  // ==========================================================================
  // GESTION DES LIGNES DU TICKET
  // ==========================================================================

  function isSameLineConfig(a, b) {
    if (!a || !b) return false;
    if (a.productId !== b.productId) return false;
    if (a.variant !== b.variant) return false;
    if ((a.basePrice || 0) !== (b.basePrice || 0)) return false;

    const norm = (arr) => asArray(arr).slice().sort();
    const aSupp = norm(a.supplements);
    const bSupp = norm(b.supplements);
    const aRem = norm(a.removedIngredients);
    const bRem = norm(b.removedIngredients);

    if (aSupp.length !== bSupp.length) return false;
    for (let i = 0; i < aSupp.length; i++) {
      if (aSupp[i] !== bSupp[i]) return false;
    }

    if (aRem.length !== bRem.length) return false;
    for (let i = 0; i < aRem.length; i++) {
      if (aRem[i] !== bRem[i]) return false;
    }

    return true;
  }

  function addActiveLineToTicket() {
    if (!activeLine) return;
    if (!Array.isArray(ticketLines)) ticketLines = [];

    const unitPrice = activeLine.lineTotal || activeLine.basePrice || 0;

    const newLine = {
      ...activeLine,
      quantity: activeLine.quantity > 0 ? activeLine.quantity : 1,
      lineTotal: unitPrice,
    };

    const existing = ticketLines.find((l) => isSameLineConfig(l, newLine));

    if (existing) {
      const prevQty = existing.quantity > 0 ? existing.quantity : 1;
      const prevUnit = prevQty > 0 ? existing.lineTotal / prevQty : unitPrice;
      const newQty = prevQty + newLine.quantity;

      existing.quantity = newQty;
      existing.lineTotal = prevUnit * newQty;
    } else {
      ticketLines.push(newLine);
    }

    activeLine = null;
    renderTicketPanel();
  }

  function removeTicketLine(lineId) {
    ticketLines = ticketLines.filter((l) => l.id !== lineId);
    renderTicketPanel();
  }

  function incrementLineQuantity(lineId) {
    const line = ticketLines.find((l) => l.id === lineId);
    if (!line) return;

    const currentQty = line.quantity > 0 ? line.quantity : 1;
    const unitPrice =
      currentQty > 0 ? line.lineTotal / currentQty : line.basePrice;

    const newQty = currentQty + 1;
    line.quantity = newQty;
    line.lineTotal = unitPrice * newQty;

    renderTicketPanel();
  }

  function decrementLineQuantity(lineId) {
    const line = ticketLines.find((l) => l.id === lineId);
    if (!line) return;

    const currentQty = line.quantity > 0 ? line.quantity : 1;

    if (currentQty <= 1) {
      removeTicketLine(lineId);
      return;
    }

    const unitPrice = line.lineTotal / currentQty;
    const newQty = currentQty - 1;

    line.quantity = newQty;
    line.lineTotal = unitPrice * newQty;

    renderTicketPanel();
  }

  function toggleSupplementOnActive(suppId) {
    if (!activeLine) return;

    const supCfg = cfg.supplements?.catalog?.[suppId];
    if (!supCfg) return;

    const allowed = getDefaultSuppForCategory(activeLine.categoryId).map(
      (s) => s.id
    );
    if (!allowed.includes(suppId)) return;

    if (activeLine && activeLine.productId === "menu-enfant") {
      return;
    }

    activeLine.supplements = asArray(activeLine.supplements);

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

    activeLine.removedIngredients = asArray(activeLine.removedIngredients);

    const idx = activeLine.removedIngredients.indexOf(ingredient);

    if (idx === -1) {
      activeLine.removedIngredients.push(ingredient);
    } else {
      activeLine.removedIngredients.splice(idx, 1);
    }

    renderTicketPanel();
  }

  // ==========================================================================
  // RENDU DU TICKET
  // ==========================================================================

  function renderTicketPanel() {
    if (!ticketPanel) return;
    const body = ticketPanel.querySelector("#ticket-body");
    const totalEl = ticketPanel.querySelector("#ticket-total");
    if (!body || !totalEl) return;

    body.innerHTML = "";

    const safeLines = asArray(ticketLines);

    if (safeLines.length) {
      const blockList = document.createElement("div");
      blockList.innerHTML = `
        <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Produits du ticket</p>
        <ul class="space-y-2" id="ticket-lines-list"></ul>
      `;
      body.appendChild(blockList);

      const ul = blockList.querySelector("#ticket-lines-list");

      safeLines.forEach((line) => {
        const li = document.createElement("li");
        li.className =
          "flex items-start justify-between gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs";

        const variantLabel = line.variant === "menu" ? "menu" : "";

        const supplements = asArray(line.supplements);
        const removedIngredients = asArray(line.removedIngredients);
        const details = [];

        const qty = line.quantity && line.quantity > 0 ? line.quantity : 1;

        // 🆕 CAS KAPSALOON
        if (line.categoryId === "kapsaloon") {
          const base =
            typeof getKapsaloonBaseForLine === "function"
              ? getKapsaloonBaseForLine(line)
              : null;
          if (base && base.label) {
            details.push(`<strong>Taille :</strong> ${base.label}`);
          }

          const meats = asArray(line.kapsaloonMeats);
          if (meats.length) {
            details.push(`<strong>Viandes :</strong> ${meats.join(", ")}`);
          }

          const sauces = asArray(line.kapsaloonSauces);
          if (sauces.length) {
            details.push(`<strong>Sauces :</strong> ${sauces.join(", ")}`);
          }

          if (supplements.length && cfg.supplements?.catalog) {
            const names = supplements
              .map((id) => cfg.supplements.catalog[id]?.name)
              .filter(Boolean);
            if (names.length) {
              details.push(`<strong>Suppléments :</strong> ${names.join(", ")}`);
            }
          }
        } else if (line.categoryId === "tacos") {
          const base =
            typeof getTacosBaseForLine === "function"
              ? getTacosBaseForLine(line)
              : null;
          if (base && base.label) {
            details.push(`<strong>Taille :</strong> ${base.label}`);
          }

          const meats = asArray(line.tacosMeats);
          if (meats.length) {
            details.push(`<strong>Viandes :</strong> ${meats.join(", ")}`);
          }

          const sauces = asArray(line.tacosSauces);
          if (sauces.length) {
            details.push(`<strong>Sauces :</strong> ${sauces.join(", ")}`);
          }

          const veggies = asArray(line.tacosVeggies);
          if (veggies.length) {
            details.push(`<strong>Crudités :</strong> ${veggies.join(", ")}`);
          }

          if (supplements.length && cfg.supplements?.catalog) {
            const names = supplements
              .map((id) => cfg.supplements.catalog[id]?.name)
              .filter(Boolean);
            if (names.length) {
              details.push(`<strong>Suppléments :</strong> ${names.join(", ")}`);
            }
          }

          if (removedIngredients.length) {
            details.push(
              `<strong>Sans :</strong> ${removedIngredients.join(", ")}`
            );
          }
        } else {
          if (line.categoryId === "menu-enfant" && line.kidsChoice) {
            const found = findProductById(line.productId);
            const kidsOpts = found?.item?.kidsOptions || [];
            const opt = kidsOpts.find((o) => o.id === line.kidsChoice);
            if (opt) {
              details.push(`<strong>Plat enfant :</strong> ${opt.name}`);
            }
          }

          if (line.variant === "menu" && line.drinkChoice) {
            const drinkCat = (cfg.menu.categories || []).find(
              (c) => c.id && c.id.toLowerCase().includes("boisson")
            );
            let drinkName = line.drinkChoice;
            if (drinkCat && Array.isArray(drinkCat.items)) {
              const foundDrink = drinkCat.items.find(
                (d) => d.id === line.drinkChoice
              );
              if (foundDrink) drinkName = foundDrink.name;
            }
            details.push(`<strong>Boisson :</strong> ${drinkName}`);
          }

          if (line.mainSauce) {
            let sauces = Array.isArray(line.mainSauce)
              ? line.mainSauce
              : [line.mainSauce];

            if (sauces.length === 1) {
              details.push(`<strong>Sauce :</strong> ${sauces[0]}`);
            } else if (sauces.length > 1) {
              details.push(`<strong>Sauces :</strong> ${sauces.join(", ")}`);
            }
          }

          if (supplements.length && cfg.supplements?.catalog) {
            const names = supplements
              .map((id) => cfg.supplements.catalog[id]?.name)
              .filter(Boolean);
            if (names.length) {
              details.push(`<strong>Suppléments :</strong> ${names.join(", ")}`);
            }
          }

          if (removedIngredients.length) {
            details.push(
              `<strong>Sans :</strong> ${removedIngredients.join(", ")}`
            );
          }
        }

        li.innerHTML = `
          <div>
            <p class="font-semibold text-[13px]">
              ${line.productName}
              <span class="text-slate-500">(${variantLabel})</span>
            </p>
            ${
              details.length
                ? `<p class="text-[11px] text-slate-500 mt-1">${details.join(
                    " · "
                  )}</p>`
                : ""
            }
          </div>

          <div class="flex flex-col items-end gap-1">
            <div class="flex items-center gap-2 text-[11px]">
              <button type="button"
                      class="px-2 py-0.5 rounded-full border border-slate-300"
                      data-ticket-action="dec-qty"
                      data-line-id="${line.id}">
                -
              </button>
              <span>x${qty}</span>
              <button type="button"
                      class="px-2 py-0.5 rounded-full border border-slate-300"
                      data-ticket-action="inc-qty"
                      data-line-id="${line.id}">
                +
              </button>
            </div>
            <span class="text-[13px] font-semibold">${(
              line.lineTotal || 0
            ).toFixed(2)} €</span>
            <button type="button"
                    class="text-[11px] text-red-500"
                    data-ticket-action="remove-line"
                    data-line-id="${line.id}">
              Retirer
            </button>
          </div>
        `;

        ul.appendChild(li);
      });
    }

    if (activeLine) {
      const supList = asArray(
        getDefaultSuppForCategory(activeLine.categoryId)
      );
      const baseIngr =
        activeLine.categoryId === "tacos" || activeLine.categoryId === "kapsaloon"
          ? []
          : asArray(activeLine.baseIngredients).filter(isRemovableIngredient);
      const activeSupps = asArray(activeLine.supplements);
      const activeRemoved = asArray(activeLine.removedIngredients);

      const block = document.createElement("div");
      block.className = "border rounded-2xl p-3 bg-slate-50 space-y-3 text-xs";

      const variantLabel = activeLine.variant === "menu" ? "menu" : "";

      // 🆕 BLOC KAPSALOON HTML
      let kapsaloonHtml = "";
      if (activeLine.categoryId === "kapsaloon") {
        const found = findProductById(activeLine.productId);
        const kapsaloonCfg = found?.item?.kapsaloonConfig || {};
        const bases = Array.isArray(kapsaloonCfg.bases) ? kapsaloonCfg.bases : [];
        const meats = Array.isArray(kapsaloonCfg.meats) ? kapsaloonCfg.meats : [];
        const sauces = Array.isArray(kapsaloonCfg.sauces) ? kapsaloonCfg.sauces : [];

        const selectedBaseId = activeLine.kapsaloonBaseId;
        const selectedMeats = asArray(activeLine.kapsaloonMeats);
        const selectedSauces = asArray(activeLine.kapsaloonSauces);

        kapsaloonHtml = `
          <div class="space-y-3">
            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Taille :</p>
              <div class="flex flex-wrap gap-1">
                ${bases
                  .map((b) => {
                    const isOn = b.id === selectedBaseId;
                    return `
                      <button type="button"
                              data-ticket-action="set-kapsaloon-base"
                              data-base-id="${b.id}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-brand text-white border-brand"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${b.label}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Viandes :</p>
              <div class="flex flex-wrap gap-1">
                ${meats
                  .map((m) => {
                    const isOn = selectedMeats.includes(m);
                    return `
                      <button type="button"
                              data-ticket-action="toggle-kapsaloon-meat"
                              data-meat-name="${m}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-slate-900 text-white border-slate-900"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${m}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Sauces (max. 2) :</p>
              <div class="flex flex-wrap gap-1">
                ${sauces
                  .map((sName) => {
                    const isOn = selectedSauces.includes(sName);
                    return `
                      <button type="button"
                              data-ticket-action="toggle-kapsaloon-sauce"
                              data-sauce-name="${sName}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-brand text-white border-brand"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${sName}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>
          </div>
        `;
      }

      let tacosHtml = "";
      if (activeLine.categoryId === "tacos") {
        const found = findProductById(activeLine.productId);
        const tacosCfg = found?.item?.tacosConfig || {};
        const bases = Array.isArray(tacosCfg.bases) ? tacosCfg.bases : [];
        const meats = Array.isArray(tacosCfg.meats) ? tacosCfg.meats : [];
        const sauces = Array.isArray(tacosCfg.sauces) ? tacosCfg.sauces : [];

        const selectedBaseId = activeLine.tacosBaseId;
        const selectedMeats = asArray(activeLine.tacosMeats);
        const selectedSauces = asArray(activeLine.tacosSauces);

        tacosHtml = `
          <div class="space-y-3">
            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Taille :</p>
              <div class="flex flex-wrap gap-1">
                ${bases
                  .map((b) => {
                    const isOn = b.id === selectedBaseId;
                    return `
                      <button type="button"
                              data-ticket-action="set-tacos-base"
                              data-base-id="${b.id}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-brand text-white border-brand"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${b.label}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Viandes :</p>
              <div class="flex flex-wrap gap-1">
                ${meats
                  .map((m) => {
                    const isOn = selectedMeats.includes(m);
                    return `
                      <button type="button"
                              data-ticket-action="toggle-tacos-meat"
                              data-meat-name="${m}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-slate-900 text-white border-slate-900"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${m}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Sauces (max. 2) :</p>
              <div class="flex flex-wrap gap-1">
                ${sauces
                  .map((sName) => {
                    const isOn = selectedSauces.includes(sName);
                    return `
                      <button type="button"
                              data-ticket-action="toggle-tacos-sauce"
                              data-sauce-name="${sName}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-brand text-white border-brand"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${sName}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>
          </div>
        `;
      }

      let mainSauceHtml = "";
      const sauceCategories = [
        "burgers",
        "sandwichs",
        "paninis",
        "signatures",
        "galettes",
      ];

      if (
        sauceCategories.includes(activeLine.categoryId) &&
        Array.isArray(activeLine.availableSauces) &&
        activeLine.availableSauces.length
      ) {
        const selectedSauces = Array.isArray(activeLine.mainSauce)
          ? activeLine.mainSauce
          : activeLine.mainSauce
          ? [activeLine.mainSauce]
          : [];

        mainSauceHtml = `
          <div class="space-y-2">
            <p class="text-[11px] text-slate-500 font-semibold">Sauce au choix :</p>
            <div class="flex flex-wrap gap-1">
              ${activeLine.availableSauces
                .map((sName) => {
                  const isOn = selectedSauces.includes(sName);
                  return `
                    <button type="button"
                            data-ticket-action="set-main-sauce"
                            data-sauce-name="${sName}"
                            class="px-2 py-1 rounded-full border text-[11px] ${
                              isOn
                                ? "bg-brand text-white border-brand"
                                : "bg-white text-slate-700 border-slate-200"
                            }">
                      ${sName}
                    </button>
                  `;
                })
                .join("")}
            </div>
          </div>
        `;
      }

      let supplHtml = "";
      if (supList.length) {
        supplHtml = `
          <div class="space-y-2">
            <p class="text-[11px] text-slate-500">Suppléments :</p>
            <div class="flex flex-wrap gap-1">
              ${supList
                .map((s) => {
                  const isOn = activeSupps.includes(s.id);
                  return `
                    <button type="button"
                            data-ticket-action="toggle-supp"
                            data-supp-id="${s.id}"
                            class="px-2 py-1 rounded-full border text-[11px] ${
                              isOn
                                ? "bg-brand text-white border-brand"
                                : "bg-white text-slate-700 border-slate-200"
                            }">
                      ${s.name} <span class="opacity-70">+${s.price.toFixed(
                        2
                      )}€</span>
                    </button>
                  `;
                })
                .join("")}
            </div>
          </div>
        `;
      }

      let ingrHtml = "";
      if (baseIngr.length) {
        ingrHtml = `
          <div class="space-y-2">
            <p class="text-[11px] text-slate-500">Ingrédients à enlever :</p>
            <div class="flex flex-wrap gap-1">
              ${baseIngr
                .map((ing) => {
                  const isOff = activeRemoved.includes(ing);
                  return `
                    <button type="button"
                            data-ticket-action="toggle-ingredient"
                            data-ingredient="${ing}"
                            class="px-2 py-1 rounded-full border text-[11px] ${
                              isOff
                                ? "bg-slate-900 text-white border-slate-900"
                                : "bg-white text-slate-700 border-slate-200"
                            }">
                      Sans ${ing}
                    </button>
                  `;
                })
                .join("")}
            </div>
          </div>
        `;
      }

      let drinkHtml = "";

      if (
        activeLine.variant === "menu" &&
        activeLine.categoryId !== "menu-enfant"
      ) {
        const drinkCat = (cfg.menu.categories || []).find(
          (c) => c.id && c.id.toLowerCase().includes("boisson")
        );

        if (drinkCat && Array.isArray(drinkCat.items)) {
          drinkHtml = `
            <div class="space-y-2">
              <p class="text-[11px] text-slate-500">Boisson incluse :</p>
              <div class="flex flex-wrap gap-1">
                ${drinkCat.items
                  .map((d) => {
                    const isOn = activeLine.drinkChoice === d.id;
                    return `
                      <button type="button"
                              data-ticket-action="set-drink"
                              data-drink-id="${d.id}"
                              class="px-2 py-1 rounded-full border text-[11px] ${
                                isOn
                                  ? "bg-brand text-white border-brand"
                                  : "bg-white text-slate-700 border-slate-200"
                              }">
                        ${d.name}
                      </button>
                    `;
                  })
                  .join("")}
              </div>
            </div>
          `;
        }
      }

      if (activeLine.categoryId === "menu-enfant") {
        const found = findProductById(activeLine.productId);
        const kidsOpts = found?.item?.kidsOptions || [];

        if (kidsOpts.length) {
          const kidsBlock = document.createElement("div");
          kidsBlock.className = "space-y-2";

          kidsBlock.innerHTML = `
            <p class="text-[11px] text-slate-500">Plat du menu :</p>
            <div class="flex flex-wrap gap-1">
              ${kidsOpts
                .map((opt) => {
                  const selected = activeLine.kidsChoice === opt.id;
                  return `
                    <button type="button"
                      data-ticket-action="set-kids-plate"
                      data-plate-id="${opt.id}"
                      class="px-2 py-1 rounded-full border text-[11px] ${
                        selected
                          ? "bg-brand text-white border-brand"
                          : "bg-white text-slate-700 border-slate-200"
                      }">
                      ${opt.name}
                    </button>
                  `;
                })
                .join("")}
            </div>
          `;

          body.appendChild(kidsBlock);
        }
      }

      block.innerHTML = `
        <p class="text-[11px] uppercase tracking-wide text-slate-500">En cours de personnalisation</p>

        <p class="font-semibold text-sm">
          ${activeLine.productName}
          <span class="text-slate-500">(${variantLabel})</span>
        </p>

        ${kapsaloonHtml}
        ${tacosHtml}
        ${mainSauceHtml}
        ${supplHtml}
        ${ingrHtml}
        ${drinkHtml}

        <div class="flex items-center justify-between pt-1">
          <span class="font-semibold text-sm">Sous-total : ${activeLine.lineTotal.toFixed(
            2
          )} €</span>

          <button type="button"
                  class="px-3 py-1.5 rounded-full bg-brand text-white text-xs font-semibold"
                  data-ticket-action="add-active-line">
            Ajouter au ticket
          </button>
        </div>
      `;

      body.appendChild(block);
    }

    if (!safeLines.length && !activeLine) {
      const empty = document.createElement("p");
      empty.className = "text-xs text-slate-500";
      empty.textContent =
        "Votre ticket est vide. Ajoutez un produit avec le bouton +.";
      body.appendChild(empty);
    }

    const total = safeLines.reduce(
      (sum, line) => sum + (line.lineTotal || 0),
      0
    );
    totalEl.textContent = total.toFixed(2) + " €";
  }

  // ==========================================================================
  // PARTAGE DU TICKET
  // ==========================================================================

  function prepareTicketMessage() {
    if (!ticketPanel) return null;

    const nameInput = ticketPanel.querySelector("#ticket-name");
    const phoneInput = ticketPanel.querySelector("#ticket-phone");
    const msgInput = ticketPanel.querySelector("#ticket-message");

    if (!nameInput || !phoneInput || !msgInput) {
      return null;
    }

    const name = (nameInput.value || "").trim();
    const phone = (phoneInput.value || "").trim();

    nameInput.classList.remove("ring-2", "ring-red-400");
    phoneInput.classList.remove("ring-2", "ring-red-400");

    if (!name || !phone) {
      if (!name) {
        nameInput.classList.add("ring-2", "ring-red-400");
      }
      if (!phone) {
        phoneInput.classList.add("ring-2", "ring-red-400");
      }
      return null;
    }

    const safeLines = asArray(ticketLines);

    if (!safeLines.length) {
      alert("Ajoutez au moins un produit dans le ticket.");
      return null;
    }

    const total = safeLines.reduce(
      (sum, line) => sum + (line.lineTotal || 0),
      0
    );

    const linesText = safeLines
      .map((line) => {
        const variantLabel = line.variant === "menu" ? "menu" : "";
        const supplements = asArray(line.supplements);
        const removedIngredients = asArray(line.removedIngredients);

        const qty = line.quantity && line.quantity > 0 ? line.quantity : 1;

        const categoryLabel = line.categoryId
          ? line.categoryId.charAt(0).toUpperCase() + line.categoryId.slice(1)
          : "Produit";

        const parts = [
          qty > 1
            ? `- [${categoryLabel}] ${line.productName} (${variantLabel}) x${qty}`
            : `- [${categoryLabel}] ${line.productName} (${variantLabel})`,
        ];

        const sauceCategories = [
          "burgers",
          "sandwichs",
          "paninis",
          "signatures",
          "galettes",
        ];

        if (sauceCategories.includes(line.categoryId) && line.mainSauce) {
          const sauces = Array.isArray(line.mainSauce)
            ? line.mainSauce
            : [line.mainSauce];

          if (sauces.length === 1) {
            parts.push(`*SAUCE :* ${sauces[0]}`);
          } else if (sauces.length > 1) {
            parts.push(`*SAUCES :* ${sauces.join(", ")}`);
          }
        }

        if (supplements.length && cfg.supplements?.catalog) {
          const names = supplements
            .map((id) => cfg.supplements.catalog[id]?.name)
            .filter(Boolean);
          if (names.length) {
            parts.push(`*SUPPLÉMENTS :* ${names.join(", ")}`);
          }
        }

        if (removedIngredients.length) {
          parts.push(`*SANS :* ${removedIngredients.join(", ")}`);
        }

        if (line.productId === "menu-enfant" && line.kidsChoice) {
          parts.push(`*PLAT ENFANT :* ${line.kidsChoice}`);
        }

        if (line.variant === "menu" && line.drinkChoice) {
          parts.push(`*BOISSON :* ${line.drinkChoice} (incluse)`);
        }

        parts.push(`= ${(line.lineTotal || 0).toFixed(2)} €`);

        return parts.join(" | ");
      })
      .join("\n");

    const extra = (msgInput.value || "").trim();
    const txt =
      `Commande de ${name} (${phone})\n\n` +
      `${linesText}\n\n` +
      `Total : ${total.toFixed(2)} €` +
      (extra ? `\n\nMessage : ${extra}` : "");

    const encoded = encodeURIComponent(txt);

    return { txt, encoded };
  }

  function shareTicket() {
    const payload = prepareTicketMessage();
    if (!payload) return;

    const { txt, encoded } = payload;

    if (navigator.share) {
      navigator
        .share({
          title: "Ticket commande",
          text: txt,
        })
        .catch(() => {
          window.open(`https://wa.me/?text=${encoded}`, "_blank");
        });
    } else {
      window.open(`https://wa.me/?text=${encoded}`, "_blank");
    }
  }

  async function shareTicketToRestaurant() {
    // Récupérer les données du formulaire
    const nameInput = ticketPanel?.querySelector("#ticket-name");
    const phoneInput = ticketPanel?.querySelector("#ticket-phone");
    const msgInput = ticketPanel?.querySelector("#ticket-message");

    if (!nameInput || !phoneInput) {
      alert("Erreur: formulaire incomplet");
      return;
    }

    const name = (nameInput.value || "").trim();
    const phone = (phoneInput.value || "").trim();
    const notes = (msgInput?.value || "").trim();

    // Validation
    nameInput.classList.remove("ring-2", "ring-red-400");
    phoneInput.classList.remove("ring-2", "ring-red-400");

    if (!name || !phone) {
      if (!name) nameInput.classList.add("ring-2", "ring-red-400");
      if (!phone) phoneInput.classList.add("ring-2", "ring-red-400");
      alert("Veuillez remplir votre nom et téléphone");
      return;
    }

    const safeLines = Array.isArray(ticketLines) ? ticketLines : [];
    if (!safeLines.length) {
      alert("Ajoutez au moins un produit dans le ticket.");
      return;
    }

    const total = safeLines.reduce((sum, line) => sum + (line.lineTotal || 0), 0);

    // Formater les items pour l'API
    const items = safeLines.map(line => ({
      product_id: line.productId,
      name: line.productName,
      variant: line.variant || "solo",
      quantity: line.quantity || 1,
      unit_price: (line.lineTotal || 0) / (line.quantity || 1),
      supplements: line.supplements || [],
      removed_ingredients: line.removedIngredients || [],
      sauce: line.mainSauce || null,
      drink: line.drinkChoice || null
    }));

    const orderData = {
      customer_name: name,
      customer_phone: phone,
      items: items,
      subtotal: total,
      total: total,
      notes: notes
    };

    // Désactiver le bouton pendant l'envoi
    const submitBtn = ticketPanel?.querySelector("#ticket-submit-btn, .btn-brand");
    const originalBtnText = submitBtn?.textContent || "Envoyer";
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Envoi en cours...";
    }

    try {
      // Envoyer la commande à l'API
      const result = await window.SNACK_API.apiCall("order", "POST", orderData);

      if (result.success) {
        console.log("✅ [ORDER] Commande envoyée:", result.order_id);

        // Afficher confirmation
        showOrderConfirmation(result.order_id, name, total);

        // Vider le panier
        ticketLines = [];
        if (nameInput) nameInput.value = "";
        if (phoneInput) phoneInput.value = "";
        if (msgInput) msgInput.value = "";

        // Fermer le panneau ticket
        closeTicketPanel();

      } else {
        throw new Error(result.message || "Erreur lors de l'envoi");
      }
    } catch (err) {
      console.error("❌ [ORDER] Erreur:", err);
      alert("Erreur lors de l'envoi de la commande. Veuillez réessayer.");

      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  }

  // Afficher la confirmation de commande
  function showOrderConfirmation(orderId, customerName, total) {
    const modal = document.createElement('div');
    modal.id = 'order-confirmation-modal';
    modal.style.cssText = `
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.9);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    `;

    modal.innerHTML = `
      <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px; border-radius: 24px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">
        <div style="font-size: 80px; margin-bottom: 20px;">✅</div>
        <h2 style="color: #fff; font-size: 28px; margin: 0 0 10px;">Commande envoyée !</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin: 0 0 20px;">Merci ${customerName}</p>
        <div style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 15px; margin-bottom: 20px;">
          <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0 0 5px;">N° de commande</p>
          <p style="color: #fff; font-size: 24px; font-weight: bold; margin: 0;">${orderId}</p>
        </div>
        <p style="color: #fff; font-size: 32px; font-weight: bold; margin: 0 0 25px;">${total.toFixed(2)} €</p>
        <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0 0 25px;">Votre commande est en cours de préparation.<br>Vous serez notifié quand elle sera prête !</p>
        <button onclick="this.closest('#order-confirmation-modal').remove()"
          style="padding: 15px 40px; border-radius: 50px; background: #fff; color: #059669; border: none; font-size: 18px; font-weight: bold; cursor: pointer;">
          OK
        </button>
      </div>
    `;

    document.body.appendChild(modal);

    // Auto-close après 10 secondes
    setTimeout(() => {
      modal.remove();
    }, 10000);
  }

  // Fermer le panneau ticket
  function closeTicketPanel() {
    if (ticketPanel) {
      ticketPanel.remove();
      ticketPanel = null;
    }
    // Réactiver le scroll
    document.body.style.overflow = '';
  }

  window.openTicketBuilder = openTicketBuilder;

  // ==========================================================================
  // UTILITAIRES
  // ==========================================================================

  function isRemovableIngredient(name = "") {
    const k = (name || "").toLowerCase();

    const protectedKeywords = [
      "viande",
      "steak",
      "kebab",
      "tenders",
      "nugget",
      "cordon",
      "escalope",
      "merguez",
      "fish",
      "poisson",
      "burger",
      "chicken",
      "kefta",
      "mexicanos",
      "brochette",
      "wings",
    ];

    return !protectedKeywords.some((word) => k.includes(word));
  }

  function pickIcon(id = "", label = "") {
    const k = (id || label || "").toLowerCase();

    if (k.includes("burger")) return "🍔";
    if (k.includes("tacos")) return "🌯";
    if (k.includes("kapsaloon") || k.includes("kapsalon")) return "🍟";
    if (k.includes("wrap")) return "🌯";
    if (k.includes("galette")) return "🫓";
    if (k.includes("pita") || k.includes("panini")) return "🥙";
    if (k.includes("assiette") || k.includes("plate")) return "🍽️";
    if (k.includes("menu-enfant") || k.includes("kid")) return "🍭";
    if (k.includes("dessert") || k.includes("sucré")) return "🍰";
    if (k.includes("boisson") || k.includes("drink") || k.includes("soda"))
      return "🥤";
    if (k.includes("texmex") || k.includes("tex-mex")) return "🍟";
    if (k.includes("frites") || k.includes("frite")) return "🍟";
    if (k.includes("salade") || k.includes("salad")) return "🥗";
    if (k.includes("pizza")) return "🍕";
    if (k.includes("poulet") || k.includes("chicken") || k.includes("wings"))
      return "🍗";
    if (k.includes("hot dog") || k.includes("hot-dog")) return "🌭";
    if (k.includes("snack") || k.includes("snacking")) return "🍽️";

    return "🍽️";
  }

  function fillSocialIcons(container, socialCfg, variant) {
    if (!container || !socialCfg) return;

    const map = [
      ["instagram", socialCfg.instagram],
      ["facebook", socialCfg.facebook],
      ["tiktok", socialCfg.tiktok],
      ["snapchat", socialCfg.snapchat || socialCfg.snap],
    ];

    map.forEach(([type, href]) => {
      if (!href) return;
      container.appendChild(createSocialIcon(type, href, variant));
    });
  }

  function createSocialIcon(type, href, variant) {
    const a = document.createElement("a");
    a.href = href;
    a.target = "_blank";
    a.rel = "noopener";

    if (variant === "drawer") {
      a.className =
        "p-2 rounded-lg bg-white text-slate-900 inline-flex items-center justify-center";
    } else {
      a.className =
        "inline-flex items-center justify-center w-9 h-9 rounded-full bg-white text-slate-900 shadow";
    }

    let svg = "";
    if (type === "instagram") {
      svg = `
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M7 2C4.2 2 2 4.2 2 7v10c0 2.8 2.2 5 5 5h10c2.8 0 5-2.2 5-5V7c0-2.8-2.2-5-5-5H7zm10 2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h10zm-5 3a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm4.5-3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
        </svg>
      `;
    } else if (type === "facebook") {
      svg = `
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.6v-3h2.6V9.5c0-2.6 1.6-4.1 4-4.1 1.2 0 2.4.2 2.4.2v2.7h-1.4c-1.3 0-1.7.8-1.7 1.6V12h3l-.5 3h-2.5v7A10 10 0 0 0 22 12z"/>
        </svg>
      `;
    } else if (type === "tiktok") {
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M13 2h3c.2 1.9 1.6 3.4 3.5 3.7V9c-1.4.1-2.8-.3-3.9-1V15a5.5 5.5 0 1 1-5.5-5.5c.3 0 .6 0 .9.1V7.2A8 8 0 0 0 9.5 7 5.5 5.5 0 0 0 4 12.5 5.5 5.5 0 0 0 9.5 18 5.5 5.5 0 0 0 15 12.5V2z"/>
        </svg>
      `;
    } else if (type === "snapchat") {
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 2c2.5 0 4.5 1.8 4.7 4.2.1 1 .2 2 .4 3 0 0 .2 1.2 1.6 1.7.4.2.9.3 1.2.4.3.1.5.3.5.6 0 .3-.2.6-.5.8-.7.5-1.5.9-2.4 1 0 0 .3 1 .3 1.8 0 .4-.3.7-.7.7-.9 0-1.7-.4-2.5-.8-.8-.4-1.5-.8-2.3-.8s-1.5.4-2.3.8c-.8.4-1.6.8-2.5.8-.4 0-.7-.3-.7-.7 0-.8.3-1.8.3-1.8-.9-.1-1.7-.5-2.4-1-.3-.2-.5-.5-.5-.8 0-.3.2-.5.5-.6.4-.1.8-.2 1.2-.4 1.4-.5 1.6-1.7 1.6-1.7.2-1 .3-2 .4-3C7.5 3.8 9.5 2 12 2z"/>
        </svg>
      `;
    } else {
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <circle cx="12" cy="12" r="10" />
        </svg>
      `;
    }

    a.innerHTML = svg;
    return a;
  }

  function getPlatformMeta(p) {
    const id = (p.id || p.name || "").toLowerCase();

    let accent = p.accentColor || "#111827";
    let text = p.textColor || "#ffffff";
    let label = p.name || "";
    let svg = "";

    if (id.includes("uber")) {
      accent = p.accentColor || "#000000";
      text = p.textColor || "#22c55e";
      label = p.name || "Uber Eats";
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
        </svg>
      `;
    } else if (id.includes("deliveroo")) {
      accent = p.accentColor || "#00CCBC";
      text = p.textColor || "#ffffff";
      label = p.name || "Deliveroo";
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <rect x="5" y="5" width="14" height="14" rx="3"></rect>
        </svg>
      `;
    } else if (id.includes("justeat") || id.includes("just-eat")) {
      accent = p.accentColor || "#ff5a1f";
      text = p.textColor || "#ffffff";
      label = p.name || "Just Eat";
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M4 20L10 4h4l6 16z"></path>
        </svg>
      `;
    } else if (id.includes("deliver") && !id.includes("deliveroo")) {
      accent = p.accentColor || "#0f766e";
      text = p.textColor || "#ffffff";
      label = p.name || "Livraison";
      svg = `
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M3 7h13l5 5-5 5H3z"></path>
        </svg>
      `;
    }

    return { accent, text, label, svg };
  }

  function createPlatformCardDrawer(p) {
    const meta = getPlatformMeta(p);
    const a = document.createElement("a");
    a.href = p.url;
    a.target = "_blank";
    a.rel = "noopener";
    a.className =
      "flex items-center justify-center gap-2 px-3 py-2 rounded-lg font-semibold text-sm";
    a.style.backgroundColor = meta.accent;
    a.style.color = meta.text;

    a.innerHTML = `
      ${meta.svg}
      <span>${meta.label}</span>
    `;
    return a;
  }

  function createPlatformItemFooter(p) {
    const meta = getPlatformMeta(p);
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = p.url;
    a.target = "_blank";
    a.rel = "noopener";
    a.className = "hover:text-slate-900 flex items-center gap-1";

    a.innerHTML = `
      ${meta.svg}
      <span>${meta.label}</span>
    `;

    li.appendChild(a);
    return li;
  }

  function createPlatformCardMain(p) {
    const meta = getPlatformMeta(p);

    const a = document.createElement("a");
    a.href = p.url;
    a.target = "_blank";
    a.rel = "noopener";
    a.className = "block rounded-3xl overflow-hidden elev bg-white";

    a.innerHTML = `
      <div class="px-6 py-6 flex items-center justify-between"
           style="background:${meta.accent};color:${meta.text}">
        <div class="flex items-center gap-3 text-xl font-semibold">
          ${meta.svg}
          <span>${meta.label}</span>
        </div>
      </div>
      <div class="px-6 py-4 text-sm text-slate-600">
        Commandez via ${meta.label}
      </div>
    `;

    return a;
  }

  function capitalize(str = "") {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function hexToRgba(hex, alpha = 1) {
    let h = (hex || "").replace("#", "");
    if (!h) return `rgba(0,0,0,${alpha})`;
    if (h.length === 3) h = h.split("").map((x) => x + x).join("");
    const n = parseInt(h, 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
  }

  // ==========================================================================
  // ONBOARDING POP-UP (PREMIÈRE VISITE)
  // ==========================================================================

  function openOnboardingPopup() {
    const popup = document.getElementById("onboarding-popup");
    const overlay = document.getElementById("onboarding-overlay");
    const modal = document.getElementById("onboarding-modal");

    if (!popup || !overlay || !modal) return;

    // Afficher la pop-up
    popup.classList.remove("hidden");
    popup.classList.add("flex");

    // Empêcher le scroll
    document.body.style.overflow = "hidden";

    // Remplacer le nom de la marque dynamiquement
    const brandNameElement = document.getElementById("onboarding-brand-name");
    if (brandNameElement && cfg.name) {
      brandNameElement.textContent = cfg.name;
    }

    // Animation fade in
    requestAnimationFrame(() => {
      overlay.classList.remove("opacity-0");
      overlay.classList.add("opacity-100");

      modal.classList.remove("opacity-0", "scale-95");
      modal.classList.add("opacity-100", "scale-100");
    });
  }

  function closeOnboardingPopup() {
    const popup = document.getElementById("onboarding-popup");
    const overlay = document.getElementById("onboarding-overlay");
    const modal = document.getElementById("onboarding-modal");

    if (!popup || !overlay || !modal) return;

    // Animation fade out
    overlay.classList.remove("opacity-100");
    overlay.classList.add("opacity-0");

    modal.classList.remove("opacity-100", "scale-100");
    modal.classList.add("opacity-0", "scale-95");

    // Attendre la fin de l'animation avant de masquer
    setTimeout(() => {
      popup.classList.remove("flex");
      popup.classList.add("hidden");
      document.body.style.overflow = "";
    }, 300);
  }

  // Initialiser la pop-up au chargement
  function initOnboarding() {
    const STORAGE_KEY = "snackapp_onboarding_seen";

    // Afficher le popup à chaque visite (pas seulement la première fois)
    setTimeout(() => {
      openOnboardingPopup();
    }, 500);

    // Bouton "OK, j'ai compris"
    const okBtn = document.getElementById("onboarding-ok");
    if (okBtn) {
      okBtn.addEventListener("click", () => {
        localStorage.setItem(STORAGE_KEY, "true");
        closeOnboardingPopup();
      });
    }

    // Bouton "Ne plus afficher"
    const neverBtn = document.getElementById("onboarding-never");
    if (neverBtn) {
      neverBtn.addEventListener("click", () => {
        localStorage.setItem(STORAGE_KEY, "true");
        closeOnboardingPopup();
      });
    }

    // Fermer en cliquant sur l'overlay
    const overlay = document.getElementById("onboarding-overlay");
    if (overlay) {
      overlay.addEventListener("click", () => {
        localStorage.setItem(STORAGE_KEY, "true");
        closeOnboardingPopup();
      });
    }
  }

  // ==========================================================================
  // SYSTÈME DE TOASTS / NOTIFICATIONS
  // ==========================================================================

  function showToast(options) {
    const {
      title = "",
      message = "",
      icon = "🎉",
      type = "info", // success, info, warning, error
      duration = 4000
    } = options;

    const container = document.getElementById("toast-container");
    if (!container) return;

    // Créer le toast
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div class="toast-icon">${icon}</div>
      <div class="toast-content">
        ${title ? `<div class="toast-title">${title}</div>` : ""}
        <div class="toast-message">${message}</div>
      </div>
      <button class="toast-close" aria-label="Fermer">✕</button>
    `;

    // Ajouter au conteneur
    container.appendChild(toast);

    // Bouton fermer
    const closeBtn = toast.querySelector(".toast-close");
    closeBtn.addEventListener("click", () => {
      removeToast(toast);
    });

    // Auto-fermer après duration
    if (duration > 0) {
      setTimeout(() => {
        removeToast(toast);
      }, duration);
    }

    return toast;
  }

  function removeToast(toast) {
    toast.classList.add("toast-exit");
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }

  // ==========================================================================
  // BANDEAU DYNAMIQUE
  // ==========================================================================

  function updateBanner(state = "default") {
    const banner = document.getElementById("info-banner");
    const bannerText = document.getElementById("banner-text");

    if (!banner || !bannerText) return;

    const messages = {
      default: "Cliquez sur un produit pour le personnaliser et créer votre ticket.",
      added: "✅ Continuez vos choix ou envoyez votre ticket.",
      empty: "🛒 Votre ticket est vide, ajoutez des produits !"
    };

    bannerText.textContent = messages[state] || messages.default;

    // Animation subtile de changement
    banner.style.transform = "scale(1.02)";
    setTimeout(() => {
      banner.style.transform = "scale(1)";
    }, 200);
  }

  // ==========================================================================
  // DÉTECTION PREMIER AJOUT AU PANIER
  // ==========================================================================

  function initFirstAddDetection() {
    const FIRST_ADD_KEY = "snackapp_first_add_seen";

    // Observer les ajouts au panier (via mutation observer ou événements personnalisés)
    // Pour l'instant, on va hooker la fonction openTicketBuilder si elle existe

    if (typeof window.openTicketBuilder === "function") {
      const originalFunction = window.openTicketBuilder;

      window.openTicketBuilder = function(...args) {
        // Appeler la fonction originale
        const result = originalFunction.apply(this, args);

        // Vérifier si c'est le premier ajout
        if (!localStorage.getItem(FIRST_ADD_KEY)) {
          setTimeout(() => {
            showToast({
              title: "Produit ajouté !",
              message: "Votre ticket se construit automatiquement sans erreur.",
              icon: "🎉",
              type: "success",
              duration: 5000
            });

            localStorage.setItem(FIRST_ADD_KEY, "true");
            updateBanner("added");
          }, 500);
        } else {
          // Si ce n'est pas le premier, on met quand même à jour le bandeau
          updateBanner("added");
        }

        return result;
      };
    }
  }

  // ==========================================================================
  // DÉTECTION ENVOI WHATSAPP
  // ==========================================================================

  function initWhatsAppDetection() {
    // Observer les clics sur les boutons WhatsApp
    document.addEventListener("click", (e) => {
      const target = e.target.closest('a[href*="wa.me"], a[href*="whatsapp"]');

      if (target) {
        setTimeout(() => {
          showToast({
            title: "Ticket envoyé !",
            message: "Merci pour votre commande. Nous la préparerons avec soin.",
            icon: "📲",
            type: "success",
            duration: 6000
          });
        }, 500);
      }
    });
  }

  // ==========================================================================
  // INITIALISATION COMPLÈTE
  // ==========================================================================

  function initEnhancedFeatures() {
    // Initialiser toutes les fonctionnalités améliorées
    initFirstAddDetection();
    initWhatsAppDetection();

    // Vérifier périodiquement si le panier est vide
    setInterval(() => {
      // Cette logique dépend de votre implémentation du panier
      // Pour l'instant, on la laisse comme exemple
      const ticketItems = document.querySelectorAll("[data-ticket-item]");
      if (ticketItems.length === 0) {
        const banner = document.getElementById("info-banner");
        const bannerText = document.getElementById("banner-text");
        if (banner && bannerText && bannerText.textContent.includes("Continuez")) {
          updateBanner("empty");
        }
      }
    }, 3000);
  }

  // Appeler initOnboarding après le chargement du DOM
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initOnboarding();
      initEnhancedFeatures();
    });
  } else {
    initOnboarding();
    initEnhancedFeatures();
  }
})();

})();
