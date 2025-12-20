// Fabrik Burger – configuration SnackApp
// À placer dans: ./config/fabrik-burger.config.js

window.SNACK_CONFIG = {
  id: "fabrik-burger",
  name: "Fabrik Burger",
  slug: "fabrik-burger",
  legalName: "Fabrik Burger",
  brandTagline: "Burgers gourmets à Lille",
  priceRange: "€€",
  isHalal: true,
  hideFooterAddress: true,

  location: {
    addressLine1: "110 Rue des Postes",
    addressLine2: "",
    postalCode: "59000",
    city: "Lille",
    countryCode: "FR",
    latitude: 50.624939,
    longitude: 3.0551876
  },

  contact: {
    phone: "+33374455427",
    phoneDisplay: "03 74 45 54 27",
    allowWhatsAppOrders: true,
    whatsappOrdersNumber: "33757831883"
  },

  urls: {
    website: "https://fabrik-burger.example/",
    googleMaps: "https://www.google.com/maps/place/Fabrik+Burger/@50.624939,3.0551876,17z",
    googleMapsEmbed: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2531.117231022879!2d3.0551876!3d50.624939!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c2d59c0c42f3d5%3A0xbac4c68ab2f0848d!2sFabrik%20Burger!5e0!3m2!1sfr!2sdz!4v1764346967757!5m2!1sfr!2sdz"
  },

  social: {
    facebook: "https://www.facebook.com/Fabrik-Burger-Page",
    instagram: "https://www.instagram.com/fabrik.burger/?hl=fr",
    tiktok: "",
    snapchat: ""
  },

  google: {
    placeId: null,
    rating: 4.6,
    reviewCount: 376,
    url: "https://www.google.com/search?sca_esv=5a21cf7e0cf15b69&rlz=1C5CHFA_enDZ1142DZ1143&sxsrf=AE3TifPsd7_C6_oeBSdXrRd__2Dqx2Ksmg:1765273056280&si=AMgyJEtREmoPL4P1I5IDCfuA8gybfVI2d5Uj7QMwYCZHKDZ-EztU5r8iaO-i9hAwHzzXVG82vR2w-lyYmjdWN36dCkZAFalThWbc-71EKWNNZW-eyxJ3hbWGmMFC1VQw4Kji0L_G6rRi&q=Fabrik+Burger+Avis&sa=X&ved=2ahUKEwiT0s3LmrCRAxUw0wIHHdxIAoAQ0bkNegQIMhAE&biw=1589&bih=843&dpr=2",
    cuisine: ["Burger", "Restaurant"]
  },

  // Avis Uber Eats (pour le hero + FAQ)
  uber: {
    rating: 4.5,
    reviewCount: 375,
    url: "https://www.ubereats.com/fr-en/store/fabrik-burger/dMUlWAS8TGiOKDMY5ZCinA"
  },

  // ========== PRÉFÉRÉS CLIENTS ==========
  featured: {
    enabled: true,
    title: "🔥 Les préférés de nos clients",
    subtitle: "Nos burgers gourmets les plus populaires",
    items: [
      "le-fabrik",      // Le Fabrik (signature)
      "le-seguin",      // Le Seguin (fromage de chèvre)
      "le-spicy",       // Le Spicy
      "tiramisu"        // Tiramisu
    ]
  },

  reviews: [
    {
      author: "Nasse Passe",
      isLocalGuide: true,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 3 mois",
      context: "À emporter | Dîner | 10–20 €",
      text: "Vraiment très bon burger je ne regrette pas, le responsable ou gérant est très pro et agréable, excellente expérience.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    },
    {
      author: "liberté aka",
      isLocalGuide: true,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 2 ans",
      context: "Dîner | 10–20 €",
      text: "Professionnels, bon accueil et bon service. Les burgers gourmets sont excellents. La décoration est sympa. Steak boucher bien cuit, garniture, légumes et frites parfaites.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    },
    {
      author: "E GROSPAS",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 1 an",
      context: "Repas sur place | Dîner | 10–20 €",
      text: "Super découverte. Endroit spacieux, burgers bien présentés et excellents. Ils se démarquent par la qualité du pain et des sauces. Frites très bonnes et bien assaisonnées.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    },
    {
      author: "Juliette Pruvost",
      isLocalGuide: true,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 1 an",
      context: "Repas sur place | Dîner",
      text: "Très bon, copieux et efficace. Service top. On s'est régalé. Prix raisonnables (30€ pour 2 en menu). Frites très bonnes. Option végé validée.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    }
  ],

  openingHours: [
    { day: "lundi", opens: "18:30", closes: "23:30" },
    { day: "mardi", opens: "18:30", closes: "23:30" },
    { day: "mercredi", opens: "18:30", closes: "23:30" },
    { day: "jeudi", opens: "18:30", closes: "23:30" },
    { day: "vendredi", opens: "18:30", closes: "01:00" },
    { day: "samedi", opens: "18:30", closes: "01:00" },
    { day: "dimanche", opens: "18:30", closes: "00:00" }
  ],

  theme: {
    colors: {
      // Palette Fabrik Burger : bois + olive + accent doré
      brand: "#c58a3a",
      brandSoft: "rgba(197, 138, 58, 0.12)",
      brandTextOn: "#fdfbf7",

      background: "#f9f4ec",
      surface: "#3e3023",
      surfaceAlt: "#5b4330",
      cardBorder: "#3e3023",

      headerBackground: "#3e3023",
      headerText: "#fdfbf7",

      categoryPillBg: "#c58a3a",
      categoryPillText: "#ffffff",

      drawerBackground: "#3e3023",
      drawerText: "#fdfbf7",

      callButtonBg: "#c58a3a",
      callButtonText: "#ffffff",

      // Olive doux pour search, prix, petits détails
      olive: "#7e8450",
      // Accent façon bois doré (boutons, séparateurs, badges)
      accent: "#c58a3a",
      // Texte sur fond clair
      text: "#111111",
      // Texte sur fond foncé
      textOnDark: "#fdfbf7",
      // Rouge éventuel (erreurs / badge Halal / CTA secondaire)
      danger: "#b91c1c"
    },
    fonts: {
      heading: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
      body: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    },
    // Couleur de fond de la plaque derrière le logo du footer
    footerLogoBackground: "#c58a3a"
  },

  assets: {
    logo: {
      light: "images/fabriklogo.svg",
      dark: "images/fabriklogo.svg",
      alt: "Logo Fabrik Burger"
    },
    hero: {
      image: "images/herofabrik.jpeg",
      alt: "Comptoir et salle de Fabrik Burger à Lille"
    },
    gallery: [
      "images/fabrik/interior-1.jpg",
      "images/fabrik/counter-1.jpg",
      "images/fabrik/menu-board-1.jpg"
    ]
  },

  platforms: [
    {
      id: "uber-eats",
      name: "Uber Eats",
      url: "https://www.ubereats.com/fr-en/store/fabrik-burger/dMUlWAS8TGiOKDMY5ZCinA"
    },
    {
      id: "deliveroo",
      name: "Deliveroo",
      url: "https://deliveroo.fr/fr/menu/lille/lille-wazemmes/fabrik-burger"
    }
  ],

  // --------- MENU ---------
  menu: {
    categories: [
      // BURGERS
      {
        id: "burgers",
        name: "Burgers",
        description: "Nos burgers gourmets avec pain artisanal et viandes de qualité",
        items: [
          {
            id: "le-fabrik",
            name: "Le Fabrik",
            description: "Pain artisanal, steak 180g, sauce Fabrik, cheddar, oignons, tomates, salade.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/moyenfabrik_ivtf74 (1).webp",
            baseIngredients: ["salade", "tomates", "oignons"],
            isSignature: true
          },
          {
            id: "le-seguin",
            name: "Le Seguin",
            description: "Burger au fromage de chèvre avec sauce miel, salade, tomates, oignons.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/seguin_vvlwyb.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "le-spicy",
            name: "Le Spicy",
            description: "Poulet épicé, sauce relevée, salade, tomates, cheddar.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/moyenspicy_cktzjt.webp",
            baseIngredients: ["salade", "tomates"]
          },
          {
            id: "le-classic",
            name: "Le Classic",
            description: "Steak, cheddar, salade, tomates, sauce burger.",
            priceSolo: 10,
            priceMenu: 13,
            image: "images/moyenclassic_psv9ne (2) (1).webp",
            baseIngredients: ["salade", "tomates"]
          },
          {
            id: "le-supreme",
            name: "Le Suprême",
            description: "Burger généreux avec double viande et fromage.",
            priceSolo: 12,
            priceMenu: 15,
            image: "images/moyensupreme_jma1xu.webp",
            baseIngredients: []
          },
          {
            id: "le-mexicain",
            name: "Le Mexican",
            description: "Steak, cheddar, sauce relevée, poivrons, oignons, salade.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/moyenmexicainfrites_zz4odh.webp",
            baseIngredients: ["salade", "poivrons", "oignons"]
          },
          {
            id: "l-indien",
            name: "L'Indien",
            description: "Poulet mariné, sauce curry, salade, tomates, oignons.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/moyenindien_jm0xvn.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "le-vege",
            name: "Le Végé",
            description: "Galette végétarienne, légumes frais, sauce légère.",
            priceSolo: 10,
            priceMenu: 13,
            image: "images/moyenvege_wpvszf.webp",
            baseIngredients: ["légumes frais"],
            isVeggie: true
          },
          {
            id: "le-chti",
            name: "Le Ch'ti",
            description: "Burger inspiration du Nord, fromage local, sauce maison.",
            priceSolo: 11,
            priceMenu: 14,
            image: "images/moyenchti_s4rgvc.webp",
            baseIngredients: []
          },
          {
            id: "le-cheese",
            name: "Le Cheese",
            description: "Steak, cheddar, salade, oignons, sauce burger.",
            priceSolo: 9,
            priceMenu: 12,
            image: "images/cheese.jpg",
            baseIngredients: ["salade", "oignons"]
          }
        ]
      },

      // MENU ENFANT
      {
        id: "menu-enfant",
        name: "Menu Enfant",
        items: [
          {
            id: "menu-enfant",
            name: "Menu Enfant",
            description: "Petit burger ou tenders + frites + boisson.",
            priceMenu: 9,
            image: "images/kid_s0wggz.webp",
            isKids: true,
            kidsOptions: [
              { id: "mini-burger", name: "Mini Burger" },
              { id: "tenders-kid", name: "3 Tenders" }
            ]
          }
        ]
      },

      // EXTRAS
      {
        id: "extras",
        name: "Extras",
        items: [
          { id: "tenders", name: "Tenders poulet", price: 4.5, image: "images/mozzatenders_qli7au (1).webp" },
          { id: "nems-xxl", name: "Nems poulet XXL", price: 5 },
          { id: "frites-fraiches", name: "Frites fraîches", price: 3 },
          { id: "frites-cheddar", name: "Frites cheddar", price: 4, image: "images/cheddar_t36vwm.webp" },
          { id: "mozza-sticks", name: "Mozza sticks", price: 4.5, image: "images/mozzatenders_qli7au (1).webp" }
        ]
      },

      // DESSERTS
      {
        id: "desserts",
        name: "Desserts",
        items: [
          { id: "tiramisu", name: "Tiramisu", price: 3.5, image: "images/minitiramisu_roc8ov.webp" },
          { id: "tarte-daim", name: "Tarte au Daim", price: 3.5, image: "images/la-tarte-au-daim_jmkzbe.webp" }
        ]
      },

      // BOISSONS
      {
        id: "boissons",
        name: "Boissons",
        items: [
          { id: "eau", name: "Eau", price: 1.5 },
          { id: "san-pellegrino", name: "San Pellegrino", price: 1.5 },
          { id: "ice-tea", name: "Ice Tea", price: 1.5 },
          { id: "oasis", name: "Oasis Tropical", price: 1.5 },
          { id: "schweppes-agrumes", name: "Schweppes agrumes", price: 1.5 },
          { id: "schweppes-mojito", name: "Schweppes mojito", price: 1.5 },
          { id: "tropico", name: "Tropico exotique", price: 1.5 },
          { id: "fanta", name: "Fanta", price: 1.5 },
          { id: "coca", name: "Coca-Cola", price: 1.5 },
          { id: "coca-zero", name: "Coca-Cola Zero", price: 1.5 },
          { id: "coca-cherry", name: "Coca-Cola Cherry", price: 1.5 }
        ]
      }
    ]
  },

  // --------- LA FORMULE (MENU COMBO) ---------
  formula: {
    enabled: true,
    name: "La Formule",
    description: "Burger + Frites + Boisson au choix",
    includes: {
      burger: true,
      fries: true,
      drink: true
    },
    // Catégories de boissons incluses dans la formule
    drinkCategories: ["boissons"],
    // Options de frites disponibles
    friesOptions: [
      { id: "frites-fraiches", name: "Frites fraîches", extraCost: 0 },
      { id: "frites-cheddar", name: "Frites cheddar", extraCost: 1 }
    ],
    // Message affiché au client
    displayText: "🍔 + 🍟 + 🥤",
    displayTextLong: "Burger + Frites fraîches + Boisson au choix"
  },

  // --------- SUPPLÉMENTS ---------
  supplements: {
    catalog: {
      "sup-fromage": {
        id: "sup-fromage",
        name: "Fromage",
        price: 1
      },
      "sup-galette": {
        id: "sup-galette",
        name: "Galette de pomme de terre",
        price: 1
      },
      "sup-bacon": {
        id: "sup-bacon",
        name: "Bacon",
        price: 1
      },
      "sup-cheddar-frites": {
        id: "sup-cheddar-frites",
        name: "Cheddar sur les frites",
        price: 1
      }
    },

    defaultForCategories: {
      burgers: ["sup-fromage", "sup-galette", "sup-bacon", "sup-cheddar-frites"],
      "menu-enfant": [],
      extras: [],
      desserts: [],
      boissons: []
    }
  },

  // --------- FAQ ---------
  faq: {
    enabled: true,
    items: [
      {
        question: "Vos burgers sont-ils halal ?",
        answer: "Oui, tous nos burgers sont 100% halal."
      },
      {
        question: "Proposez-vous la livraison ?",
        answer: "Oui, nous livrons via Uber Eats et Deliveroo."
      },
      {
        question: "Peut-on manger sur place ?",
        answer: "Oui, nous avons une salle spacieuse avec tables et banquettes."
      },
      {
        question: "Avez-vous des options végétariennes ?",
        answer: "Oui, nous proposons Le Végé avec galette végétarienne et légumes frais."
      },
      {
        question: "Peut-on commander à l'avance ?",
        answer: "Oui, vous pouvez préparer votre commande avec notre système de ticket et nous l'envoyer par WhatsApp ou nous appeler."
      }
    ]
  },

  seo: {
    title: "Fabrik Burger · Burgers gourmets halal à Lille (Wazemmes)",
    description: "Fabrik Burger à Lille Wazemmes : burgers gourmets, frites maison et desserts, sur place, à emporter ou en livraison via Uber Eats et Deliveroo."
  }
};
