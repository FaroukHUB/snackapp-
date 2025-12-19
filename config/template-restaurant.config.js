// Template Restaurant – Configuration SnackApp
// À dupliquer et personnaliser pour chaque nouveau restaurant

window.SNACK_CONFIG = {
  // ========== IDENTITÉ DU RESTAURANT ==========
  id: "nom-du-restaurant",  // Slug unique (ex: "le-bon-snack")
  name: "Nom du Restaurant",
  slug: "nom-du-restaurant",
  brandTagline: "Slogan accrocheur ici",

  // ========== LOCALISATION ==========
  location: {
    addressLine1: "123 Rue Exemple",
    postalCode: "59000",
    city: "Ville",
    countryCode: "FR",
    latitude: 50.6292,   // Pour Google Maps
    longitude: 3.0573
  },

  // ========== CONTACT ==========
  contact: {
    phone: "+33123456789",
    phoneDisplay: "01 23 45 67 89",
    allowWhatsAppOrders: true,
    whatsappOrdersNumber: "33123456789"  // Format international sans +
  },

  // ========== URLS & LIENS ==========
  urls: {
    website: "https://monrestaurant.fr",
    googleMaps: "https://www.google.com/maps/place/...",
    googleMapsEmbed: "https://www.google.com/maps/embed?pb=..."
  },

  // ========== RÉSEAUX SOCIAUX ==========
  social: {
    facebook: "https://facebook.com/monrestaurant",
    instagram: "https://instagram.com/monrestaurant",
    tiktok: "https://tiktok.com/@monrestaurant",
    snapchat: ""
  },

  // ========== AVIS & NOTES ==========
  google: {
    placeId: null,
    rating: 4.5,
    reviewCount: 250,
    url: "https://maps.app.goo.gl/...",
    cuisine: ["Fast Food", "Burgers", "Tacos"]
  },

  uber: {
    rating: 4.3,
    reviewCount: 500,
    url: "https://www.ubereats.com/fr/store/..."
  },

  // ========== HORAIRES D'OUVERTURE ==========
  openingHours: [
    { day: "lundi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "22:30" },
    { day: "mardi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "22:30" },
    { day: "mercredi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "22:30" },
    { day: "jeudi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "22:30" },
    { day: "vendredi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "23:00" },
    { day: "samedi", opens: "11:00", closes: "14:30" },
    { day: "", opens: "18:00", closes: "23:00" },
    { day: "dimanche", opens: "18:00", closes: "22:30" }
  ],

  // ========== SAUCES DISPONIBLES ==========
  sauces: [
    "algérienne",
    "américaine",
    "mayonnaise",
    "ketchup",
    "barbecue",
    "curry",
    "samouraï",
    "blanche",
    "andalouse",
    "harissa"
  ],

  // ========== THÈME & COULEURS ==========
  theme: {
    colors: {
      brand: "#e11b22",           // Couleur principale (rouge)
      brandSoft: "#fee2e2",        // Couleur claire pour fonds
      brandTextOn: "#ffffff",      // Texte sur couleur principale

      background: "#ffffff",
      surface: "#ffffff",
      surfaceAlt: "#f9fafb",
      cardBorder: "#111827",

      headerBackground: "#ffffff",
      headerText: "#111827",

      categoryPillBg: "#111827",
      categoryPillText: "#ffffff",

      drawerBackground: "#111827",
      drawerText: "#f9fafb",

      callButtonBg: "#e11b22",
      callButtonText: "#ffffff"
    },
    fonts: {
      heading: "system-ui, -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif",
      body: "system-ui, -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif"
    }
  },

  // ========== IMAGES & ASSETS ==========
  assets: {
    hero: {
      image: "/images/hero.webp",
      alt: "Photo du restaurant"
    },
    logo: {
      light: "/images/logo-light.png",
      dark: "/images/logo-dark.png",
      alt: "Logo du restaurant"
    },
    gallery: [
      "/images/gallery-1.webp",
      "/images/gallery-2.webp",
      "/images/gallery-3.webp"
    ]
  },

  // ========== GAMME DE PRIX ==========
  priceRange: "€",  // € ou €€ ou €€€

  // ========== PLATEFORMES DE LIVRAISON ==========
  platforms: [
    {
      id: "uber-eats",
      name: "Uber Eats",
      url: "https://www.ubereats.com/fr/store/..."
    },
    {
      id: "deliveroo",
      name: "Deliveroo",
      url: "https://deliveroo.fr/fr/menu/..."
    }
  ],

  // ========== MENU ==========
  menu: {
    categories: [

      // ========== EXEMPLE : BURGERS ==========
      {
        id: "burgers",
        name: "Burgers",
        description: "Nos délicieux burgers faits maison",
        items: [
          {
            id: "burger-classic",
            name: "Le Classic",
            description: "Steak, salade, tomates, oignons, sauce au choix.",
            image: "/images/burger-classic.webp",
            priceSolo: 5.0,
            priceMenu: 7.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-cheese",
            name: "Le Cheese",
            description: "Steak, double cheddar, sauce burger.",
            image: "/images/burger-cheese.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: []
          }
        ]
      },

      // ========== EXEMPLE : TACOS (avec builder) ==========
      {
        id: "tacos",
        name: "Tacos",
        description: "Compose ton tacos : taille, viandes, sauces.",
        items: [
          {
            id: "tacos-galette",
            name: "Tacos galette",
            description: "Base galette avec frites et sauce fromagère.",
            image: "/images/tacos-galette.webp",
            baseIngredients: [],
            tacosConfig: {
              bases: [
                { id: "m-1v", label: "M · 1 viande", meats: 1, price: 6.5 },
                { id: "l-2v", label: "L · 2 viandes", meats: 2, price: 8.0 },
                { id: "xl-3v", label: "XL · 3 viandes", meats: 3, price: 9.5 }
              ],
              meats: [
                "viande hachée",
                "escalope poulet",
                "kebab",
                "nuggets",
                "merguez"
              ],
              sauces: [
                "algérienne",
                "américaine",
                "mayonnaise",
                "ketchup",
                "curry",
                "samouraï",
                "blanche",
                "harissa"
              ],
              freeCrudites: ["salade", "tomates", "oignons"],
              extrasGroupId: "tacos-extras"
            },
            price: 6.5
          }
        ]
      },

      // ========== EXEMPLE : DESSERTS ==========
      {
        id: "desserts",
        name: "Desserts",
        items: [
          {
            id: "tiramisu",
            name: "Tiramisu maison",
            description: "Tiramisu fait maison.",
            image: "/images/tiramisu.webp",
            price: 3.5
          },
          {
            id: "brownie",
            name: "Brownie chocolat",
            description: "Brownie fondant au chocolat.",
            image: "/images/brownie.webp",
            price: 3.0
          }
        ]
      },

      // ========== BOISSONS (sans images) ==========
      {
        id: "boissons",
        name: "Boissons",
        items: [
          { id: "coca", name: "Coca-Cola", price: 2.0 },
          { id: "coca-zero", name: "Coca Zéro", price: 2.0 },
          { id: "fanta", name: "Fanta", price: 2.0 },
          { id: "sprite", name: "Sprite", price: 2.0 },
          { id: "ice-tea", name: "Ice Tea", price: 2.0 },
          { id: "eau", name: "Eau", price: 1.5 }
        ]
      }
    ]
  },

  // ========== SUPPLÉMENTS ==========
  supplements: {
    catalog: {
      // Frites
      "sup-frite-petite": {
        id: "sup-frite-petite",
        name: "Petite frite",
        price: 3.0
      },
      "sup-frite-grande": {
        id: "sup-frite-grande",
        name: "Grande frite",
        price: 3.5
      },

      // Fromages
      "sup-cheddar": {
        id: "sup-cheddar",
        name: "Cheddar",
        price: 1.0
      },
      "sup-chevre": {
        id: "sup-chevre",
        name: "Fromage de chèvre",
        price: 1.0
      },

      // Garnitures
      "sup-bacon": {
        id: "sup-bacon",
        name: "Bacon",
        price: 1.5
      },
      "sup-oeuf": {
        id: "sup-oeuf",
        name: "Œuf",
        price: 1.0
      }
    },

    // Suppléments par catégorie
    defaultForCategories: {
      burgers: ["sup-cheddar", "sup-bacon", "sup-oeuf"],
      tacos: ["sup-cheddar", "sup-chevre", "sup-bacon"],
      desserts: [],
      boissons: []
    }
  },

  // ========== FAQ ==========
  faq: {
    enabled: true,
    items: [
      {
        question: "Quels sont vos horaires ?",
        answer: "Nous sommes ouverts du lundi au dimanche. Consultez notre section horaires pour plus de détails."
      },
      {
        question: "Livrez-vous ?",
        answer: "Oui, nous livrons via Uber Eats et Deliveroo dans un rayon de 5km."
      },
      {
        question: "Peut-on manger sur place ?",
        answer: "Oui, nous avons une salle avec 20 places assises."
      },
      {
        question: "Acceptez-vous les tickets restaurant ?",
        answer: "Oui, nous acceptons les tickets restaurant et les cartes bancaires."
      }
    ]
  },

  // ========== AVIS CLIENTS ==========
  reviews: [
    {
      author: "Marie D.",
      isLocalGuide: true,
      localGuideLevel: 5,
      rating: 5,
      relativeTime: "il y a 2 semaines",
      context: "Sur place | Dîner | 10-20€",
      text: "Excellent restaurant, produits frais et service rapide. Je recommande !",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 4
      }
    },
    {
      author: "Thomas L.",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 4,
      relativeTime: "il y a 1 mois",
      context: "À emporter | Déjeuner",
      text: "Très bon rapport qualité-prix, portions généreuses.",
      aspects: {
        cuisine: 4,
        service: 4,
        ambiance: null
      }
    }
  ],

  // ========== SEO ==========
  seo: {
    title: "Nom du Restaurant · Spécialité à Ville",
    description: "Découvrez nos burgers, tacos et spécialités maison. Sur place, à emporter ou en livraison à Ville."
  }
};
