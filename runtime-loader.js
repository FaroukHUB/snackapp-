// runtime-loader.js
// Charge les overrides runtime (admin) et les fusionne dans window.SNACK_CONFIG
// Objectif: ne JAMAIS écrire dans fabrik-burger.config.js (source de vérité front, read-only)

(function () {
  const RUNTIME_URL = "config/menu.runtime.json";

  // Permet au runtime principal (snack-runtime.js) d'attendre le merge
  let __resolveReady;
  window.__SNACK_RUNTIME__ = window.__SNACK_RUNTIME__ || {};
  window.__SNACK_RUNTIME__.ready = new Promise((res) => { __resolveReady = res; });

  function safeObject(v) {
    return v && typeof v === "object" ? v : {};
  }

  function deepMerge(target, patch) {
    const t = safeObject(target);
    const p = safeObject(patch);
    for (const k of Object.keys(p)) {
      const pv = p[k];
      if (Array.isArray(pv)) {
        t[k] = pv.slice();
      } else if (pv && typeof pv === "object") {
        t[k] = deepMerge(t[k], pv);
      } else {
        t[k] = pv;
      }
    }
    return t;
  }

  function ensureArray(v) {
    return Array.isArray(v) ? v : [];
  }

  function applyRuntimeMenuOverrides(config, runtime) {
    const cfg = safeObject(config);
    const rt = safeObject(runtime);

    cfg.menu = safeObject(cfg.menu);
    cfg.menu.categories = ensureArray(cfg.menu.categories);

    const deletedProducts = new Set(ensureArray(rt.deletedProducts));
    const deletedCategories = new Set(ensureArray(rt.deletedCategories));

    // Index catégories
    const catsById = new Map();
    cfg.menu.categories.forEach((c, idx) => {
      if (!c || typeof c !== "object") return;
      if (!c.id) return;
      // normaliser items
      if (!Array.isArray(c.items)) c.items = [];
      catsById.set(c.id, idx);
    });

    // Ajouter catégories custom (runtime)
    const customCats = safeObject(rt.customCategories);
    for (const cid of Object.keys(customCats)) {
      const cat = safeObject(customCats[cid]);
      const id = cat.id || cid;
      if (!id) continue;
      if (deletedCategories.has(id)) continue;

      if (!catsById.has(id)) {
        cfg.menu.categories.push({
          id,
          name: cat.name || id,
          description: cat.description || "",
          items: ensureArray(cat.items),
        });
        catsById.set(id, cfg.menu.categories.length - 1);
      }
    }

    // Appliquer patch catégories
    const catPatches = safeObject(rt.categories);
    for (const cid of Object.keys(catPatches)) {
      if (!catsById.has(cid)) continue;
      if (deletedCategories.has(cid)) continue;
      const idx = catsById.get(cid);
      cfg.menu.categories[idx] = deepMerge(cfg.menu.categories[idx], catPatches[cid]);
      if (!Array.isArray(cfg.menu.categories[idx].items)) cfg.menu.categories[idx].items = [];
    }

    // Produits custom
    const customProducts = safeObject(rt.customProducts);
    for (const pid of Object.keys(customProducts)) {
      if (deletedProducts.has(pid)) continue;
      const p = safeObject(customProducts[pid]);
      const categoryId = p.categoryId;
      if (!categoryId || !catsById.has(categoryId)) continue;
      if (deletedCategories.has(categoryId)) continue;

      const catIdx = catsById.get(categoryId);
      const items = cfg.menu.categories[catIdx].items;
      const exists = items.some((it) => it && it.id === pid);
      if (!exists) {
        items.push(Object.assign({ id: pid }, p));
      }
    }

    // Patches produits (sur existants + custom)
    const productPatches = safeObject(rt.products);
    cfg.menu.categories.forEach((cat) => {
      if (!cat || typeof cat !== "object") return;
      if (deletedCategories.has(cat.id)) return;

      cat.items = ensureArray(cat.items)
        .filter((it) => it && !deletedProducts.has(it.id))
        .map((it) => {
          const pid = it.id;
          if (pid && productPatches[pid]) {
            return deepMerge(it, productPatches[pid]);
          }
          return it;
        });
    });

    // Supprimer catégories marquées supprimées
    cfg.menu.categories = cfg.menu.categories.filter((c) => c && c.id && !deletedCategories.has(c.id));

    // Optionnel: tri par "order" si présent
    cfg.menu.categories.sort((a, b) => {
      const ao = typeof a.order === "number" ? a.order : 0;
      const bo = typeof b.order === "number" ? b.order : 0;
      return ao - bo;
    });

    return cfg;
  }

  async function loadRuntime() {
    try {
      const res = await fetch(RUNTIME_URL, { cache: "no-store" });
      if (!res.ok) return {};
      return await res.json();
    } catch {
      return {};
    }
  }

  // Expose pour debug
  window.applyRuntimeMenuOverrides = applyRuntimeMenuOverrides;

  // Charge runtime puis fusionne dans SNACK_CONFIG
  (async function boot() {
    const runtime = await loadRuntime();
    if (window.SNACK_CONFIG) {
      window.SNACK_CONFIG = applyRuntimeMenuOverrides(window.SNACK_CONFIG, runtime);
    }
    window.SNACK_MENU_RUNTIME = runtime;
    window.dispatchEvent(new CustomEvent("snack:runtime:ready", { detail: runtime }));
    if (typeof __resolveReady === "function") __resolveReady(runtime);
  })();
})();
