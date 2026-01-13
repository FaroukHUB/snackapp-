// Le Marvelous – configuration SnackApp
// Diner 50's · Crêpes, Gaufres & Coffee · Ouled Moussa, Algérie

window.SNACK_CONFIG = {
  id: "le-marvelous",
  name: "Le Marvelous",
  slug: "le-marvelous",
  legalName: "Le Marvelous",
  brandTagline: "Diner 50's · Crêpes, Gaufres & Coffee",
  priceRange: "DA",
  isHalal: true,
  hideFooterAddress: false,

  location: {
    addressLine1: "Riad City, Cité OMS 562 logements",
    addressLine2: "",
    postalCode: "35520",
    city: "Ouled Moussa",
    countryCode: "DZ",
    latitude: 36.7053937,
    longitude: 3.3693709
  },

  contact: {
    phone: "+213556782194",
    phoneDisplay: "0556 78 21 94",
    allowWhatsAppOrders: true,
    whatsappOrdersNumber: "213556782194"
  },

  urls: {
    website: "https://marvelous.mon-agenceweb.fr/",
    googleMaps: "https://maps.app.goo.gl/yCGHBfEXKbhBqsaSA",
    googleMapsEmbed: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3198.7146774777384!2d3.3667959758340036!3d36.70539367227425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128e5df9ea007565%3A0x61e9a9aae68c8648!2sLe%20MARVELOUS!5e0!3m2!1sfr!2sdz!4v1768232143312!5m2!1sfr!2sdz"
  },

  social: {
    facebook: "",
    instagram: "https://www.instagram.com/lemarvelous.50/",
    tiktok: "",
    snapchat: ""
  },

  google: {
    placeId: null,
    rating: 5.0,
    reviewCount: 87,
    url: "https://www.google.com/search?sca_esv=b243d552b696475b&rlz=1C5CHFA_enDZ1142DZ1143&sxsrf=ANbL-n7MnzELJzxRRAuob8C08vW8BOmpGw:1768232073160&q=marvelous+ouled+moussa&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOR7_uxVy-zhzsx0dTVqPMUUKZRSWYDZl1XaHwxiwgJkvZ_TqgPMTm9n81Gw7rU9-44pz8vEwedj_WqguvxavQLeqSMY2&sa=X&ved=2ahUKEwiatefkqYaSAxWmTqQEHTV4B_gQrrQLegQIHBAA&biw=1584&bih=877&dpr=2&aic=0",
    cuisine: ["Crêperie", "Coffee Shop", "Diner 50's"]
  },

  uber: {
    rating: null,
    reviewCount: null,
    url: ""
  },

  featured: {
    enabled: true,
    title: "Les préférés de nos clients",
    subtitle: "Nos créations les plus populaires",
    items: [
      "la-marvelous",
      "la-mexicana",
      "nutella-banane",
      "gaufre-complete"
    ]
  },

  reviews: [
    {
      author: "HA HD",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 4 mois",
      context: "Sur place",
      text: "Très bon et beau restaurant, décoration style américaine faite avec goût. Petit déjeuner sur place ou à emporter, sandwichs et gâteaux. Croissants très bons, jus d'orange frais.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    },
    {
      author: "fay Bel",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a un mois",
      context: "Sur place",
      text: "Très bon restaurant style fast food à Ouled Moussa. Agréable surprise ! Tout était très bon : crêpes salées, riz au poulet crousty, tortillas.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    },
    {
      author: "Rtc Ivry",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 5,
      relativeTime: "il y a 4 mois",
      context: "En famille",
      text: "Très très bon restaurant. Les enfants étaient ravis. Crêpes sucrées et salées excellentes. Aucun regret.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    }
  ],

  openingHours: [
    { day: "lundi", opens: "07:00", closes: "21:30" },
    { day: "mardi", opens: "07:00", closes: "21:30" },
    { day: "mercredi", opens: "07:00", closes: "21:30" },
    { day: "jeudi", opens: "07:00", closes: "21:30" },
    { day: "vendredi", opens: "07:00", closes: "11:30" },
    { day: "samedi", opens: "07:00", closes: "21:30" },
    { day: "dimanche", opens: "07:00", closes: "21:30" }
  ],

  theme: {
    colors: {
      brand: "#2ec4b6",
      brandSoft: "rgba(46, 196, 182, 0.12)",
      brandTextOn: "#ffffff",

      background: "#f5f5f5",
      surface: "#1a1a2e",
      surfaceAlt: "#16213e",
      cardBorder: "#1a1a2e",

      headerBackground: "#1a1a2e",
      headerText: "#ffffff",

      categoryPillBg: "#2ec4b6",
      categoryPillText: "#ffffff",

      drawerBackground: "#1a1a2e",
      drawerText: "#ffffff",

      callButtonBg: "#2ec4b6",
      callButtonText: "#ffffff",

      olive: "#2ec4b6",
      accent: "#ff6fae",
      text: "#111111",
      textOnDark: "#ffffff",
      danger: "#b91c1c"
    },
    fonts: {
      heading: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
      body: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    },
    footerLogoBackground: "#2ec4b6"
  },

  assets: {
    logo: {
      light: "images/marvel-logo.png",
      dark: "images/marvel-logo.png",
      alt: "Logo Le Marvelous"
    },
    hero: {
      image: "images/marvel-hero.jpg",
      alt: "Le Marvelous - Diner 50's à Ouled Moussa"
    },
    gallery: []
  },

  platforms: [],

  // --------- MENU ---------
  menu: {
    categories: [
      // CRÊPES SALÉES SIGNATURE
      {
        id: "crepes-salees-signature",
        name: "Crêpes Salées Signature",
        description: "Nos créations maison",
        items: [
          {
            id: "la-marvelous",
            name: "La Marvelous",
            description: "Viande hachée, fromage, oignons, poivrons, sauce maison.",
            priceSolo: 550,
            image: "",
            baseIngredients: ["oignons", "poivrons"],
            isSignature: true
          },
          {
            id: "la-mexicana",
            name: "La Mexicana",
            description: "Poulet épicé, guacamole, cheddar, jalapeños.",
            priceSolo: 580,
            image: "",
            baseIngredients: ["jalapeños"]
          },
          {
            id: "la-forestiere",
            name: "La Forestière",
            description: "Champignons, jambon de dinde, gruyère, crème fraîche.",
            priceSolo: 520,
            image: "",
            baseIngredients: ["champignons"]
          },
          {
            id: "chicken-bbq",
            name: "La Chicken BBQ",
            description: "Poulet grillé, sauce BBQ, oignons caramélisés, cheddar.",
            priceSolo: 560,
            image: "",
            baseIngredients: ["oignons"]
          }
        ]
      },

      // CRÊPES SALÉES CLASSIQUES
      {
        id: "crepes-salees-classiques",
        name: "Crêpes Salées Classiques",
        description: "Les incontournables",
        items: [
          {
            id: "jambon-fromage",
            name: "Crêpe Jambon Fromage",
            description: "Jambon de dinde, fromage fondu.",
            priceSolo: 380,
            image: "",
            baseIngredients: []
          },
          {
            id: "crepe-thon",
            name: "Crêpe Thon",
            description: "Thon, mayonnaise, fromage.",
            priceSolo: 400,
            image: "",
            baseIngredients: []
          },
          {
            id: "viande-hachee",
            name: "Crêpe Viande Hachée",
            description: "Viande hachée épicée, fromage.",
            priceSolo: 420,
            image: "",
            baseIngredients: []
          },
          {
            id: "crepe-poulet",
            name: "Crêpe Poulet",
            description: "Poulet grillé, sauce blanche, fromage.",
            priceSolo: 430,
            image: "",
            baseIngredients: []
          }
        ]
      },

      // CRÊPES SUCRÉES
      {
        id: "crepes-sucrees",
        name: "Crêpes Sucrées",
        description: "Pour les gourmands",
        items: [
          {
            id: "crepe-nutella",
            name: "Crêpe Nutella",
            description: "Nutella généreux.",
            priceSolo: 300,
            image: "",
            baseIngredients: []
          },
          {
            id: "nutella-banane",
            name: "Crêpe Nutella Banane",
            description: "Nutella, bananes fraîches.",
            priceSolo: 350,
            image: "",
            baseIngredients: ["banane"]
          },
          {
            id: "crepe-confiture",
            name: "Crêpe Confiture",
            description: "Confiture au choix.",
            priceSolo: 250,
            image: "",
            baseIngredients: []
          },
          {
            id: "miel-amandes",
            name: "Crêpe Miel Amandes",
            description: "Miel, amandes effilées.",
            priceSolo: 320,
            image: "",
            baseIngredients: ["amandes"]
          },
          {
            id: "caramel-beurre-sale",
            name: "Crêpe Caramel Beurre Salé",
            description: "Sauce caramel maison.",
            priceSolo: 340,
            image: "",
            baseIngredients: []
          },
          {
            id: "fruits-rouges",
            name: "Crêpe Fruits Rouges",
            description: "Coulis fruits rouges, chantilly.",
            priceSolo: 380,
            image: "",
            baseIngredients: ["chantilly"]
          }
        ]
      },

      // GAUFRES
      {
        id: "gaufres",
        name: "Gaufres",
        description: "Gaufres maison",
        items: [
          {
            id: "gaufre-nature",
            name: "Gaufre Nature",
            description: "Sucre glace.",
            priceSolo: 200,
            image: "",
            baseIngredients: []
          },
          {
            id: "gaufre-nutella",
            name: "Gaufre Nutella",
            description: "Nutella généreux.",
            priceSolo: 300,
            image: "",
            baseIngredients: []
          },
          {
            id: "gaufre-chantilly",
            name: "Gaufre Chantilly",
            description: "Chantilly maison.",
            priceSolo: 280,
            image: "",
            baseIngredients: []
          },
          {
            id: "gaufre-complete",
            name: "Gaufre Complète",
            description: "Nutella, banane, chantilly, amandes.",
            priceSolo: 400,
            image: "",
            baseIngredients: ["banane", "chantilly", "amandes"]
          }
        ]
      },

      // BOISSONS CHAUDES
      {
        id: "boissons-chaudes",
        name: "Boissons Chaudes",
        description: "Café, thé et plus",
        items: [
          { id: "cafe-express", name: "Café Express", description: "Café court.", priceSolo: 100, image: "", baseIngredients: [] },
          { id: "cafe-creme", name: "Café Crème", description: "Café avec lait.", priceSolo: 150, image: "", baseIngredients: [] },
          { id: "cappuccino", name: "Cappuccino", description: "Café, lait mousseux, cacao.", priceSolo: 200, image: "", baseIngredients: [] },
          { id: "chocolat-chaud", name: "Chocolat Chaud", description: "Chocolat onctueux.", priceSolo: 200, image: "", baseIngredients: [] },
          { id: "the-menthe", name: "Thé à la Menthe", description: "Thé vert, menthe fraîche.", priceSolo: 150, image: "", baseIngredients: [] }
        ]
      },

      // BOISSONS FRAÎCHES
      {
        id: "boissons-fraiches",
        name: "Boissons Fraîches",
        description: "Jus et smoothies",
        items: [
          { id: "jus-orange", name: "Jus d'Orange Frais", description: "Oranges pressées.", priceSolo: 200, image: "", baseIngredients: [] },
          { id: "citronnade", name: "Citronnade", description: "Citron, sucre, eau fraîche.", priceSolo: 150, image: "", baseIngredients: [] },
          { id: "smoothie-banane", name: "Smoothie Banane", description: "Banane, lait, miel.", priceSolo: 250, image: "", baseIngredients: [] },
          { id: "smoothie-fruits-rouges", name: "Smoothie Fruits Rouges", description: "Mix fruits rouges.", priceSolo: 280, image: "", baseIngredients: [] },
          { id: "eau-minerale", name: "Eau Minérale", description: "50cl.", priceSolo: 50, image: "", baseIngredients: [] },
          { id: "soda", name: "Soda", description: "Coca, Fanta, Sprite.", priceSolo: 100, image: "", baseIngredients: [] }
        ]
      },

      // MENU ENFANT
      {
        id: "menu-enfant",
        name: "Menu Enfant",
        description: "Pour les petits",
        items: [
          {
            id: "menu-petit-marvel",
            name: "Menu Petit Marvel",
            description: "Mini crêpe salée + jus + surprise.",
            priceSolo: 400,
            image: "",
            baseIngredients: [],
            isKids: true
          },
          {
            id: "menu-petit-gourmand",
            name: "Menu Petit Gourmand",
            description: "Mini crêpe sucrée + jus + surprise.",
            priceSolo: 380,
            image: "",
            baseIngredients: [],
            isKids: true
          }
        ]
      }
    ]
  },

  // Pas de formule combo pour Le Marvelous
  formula: {
    enabled: false
  },

  // --------- SUPPLÉMENTS ---------
  supplements: {
    catalog: {
      "sup-fromage": {
        id: "sup-fromage",
        name: "Fromage",
        price: 50
      },
      "sup-oeuf": {
        id: "sup-oeuf",
        name: "Oeuf",
        price: 50
      },
      "sup-champignons": {
        id: "sup-champignons",
        name: "Champignons",
        price: 80
      },
      "sup-poulet": {
        id: "sup-poulet",
        name: "Poulet",
        price: 100
      },
      "sup-viande": {
        id: "sup-viande",
        name: "Viande hachée",
        price: 100
      },
      "sup-nutella": {
        id: "sup-nutella",
        name: "Nutella",
        price: 80
      },
      "sup-banane": {
        id: "sup-banane",
        name: "Banane",
        price: 50
      },
      "sup-chantilly": {
        id: "sup-chantilly",
        name: "Chantilly",
        price: 50
      },
      "sup-amandes": {
        id: "sup-amandes",
        name: "Amandes",
        price: 60
      },
      "sup-fruits-rouges": {
        id: "sup-fruits-rouges",
        name: "Fruits rouges",
        price: 80
      }
    },

    defaultForCategories: {
      "crepes-salees-signature": ["sup-fromage", "sup-oeuf", "sup-champignons", "sup-poulet", "sup-viande"],
      "crepes-salees-classiques": ["sup-fromage", "sup-oeuf", "sup-champignons", "sup-poulet", "sup-viande"],
      "crepes-sucrees": ["sup-nutella", "sup-banane", "sup-chantilly", "sup-amandes", "sup-fruits-rouges"],
      "gaufres": ["sup-nutella", "sup-banane", "sup-chantilly", "sup-amandes", "sup-fruits-rouges"],
      "boissons-chaudes": [],
      "boissons-fraiches": [],
      "menu-enfant": []
    }
  },

  // --------- FAQ ---------
  faq: {
    enabled: true,
    items: [
      {
        question: "Quels moyens de paiement acceptez-vous ?",
        answer: "Argent liquide uniquement."
      },
      {
        question: "Livrez-vous ?",
        answer: "Oui, livraison en commande directe via WhatsApp ou téléphone."
      },
      {
        question: "Y a-t-il un espace pour les femmes ?",
        answer: "Oui, nous disposons d'une salle réservée aux femmes."
      },
      {
        question: "Convient-il pour les familles ?",
        answer: "Oui, nous avons une salle dédiée aux familles et un menu enfant."
      },
      {
        question: "Peut-on regarder du sport sur place ?",
        answer: "Oui, écrans disponibles pour regarder du sport."
      }
    ]
  },

  seo: {
    title: "Le Marvelous · Diner 50's à Ouled Moussa · Crêpes, Gaufres & Coffee",
    description: "Le Marvelous à Ouled Moussa : crêpes salées signature, crêpes sucrées, gaufres, boissons et menu enfant. Salle familles et salle femmes disponibles."
  }
};
