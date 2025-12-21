// Le Marvelous – configuration SnackApp
// Diner 50's · Crêpes, gaufres & coffee
// Ouled Moussa, Algérie

window.SNACK_CONFIG = {
  id: "le-marvelous",
  name: "Le Marvelous",
  slug: "le-marvelous",
  legalName: "Le Marvelous",
  brandTagline: "Diner 50's · Crêpes, gaufres & coffee",
  priceRange: "DA",
  currency: "DZD",
  currencySymbol: "DA",
  isHalal: true,
  hideFooterAddress: false,

  // Espaces spéciaux
  specialRooms: [
    { name: "Salle Femmes", description: "Espace réservé aux femmes" },
    { name: "Salle Familles", description: "Espace réservé aux familles" }
  ],

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
    googleMaps: "https://www.google.com/maps?q=Le%20MARVELOUS%20Ouled%20Moussa",
    googleMapsEmbed: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3198.7146763237606!2d3.3693709!3d36.705393699999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128e5df9ea007565%3A0x61e9a9aae68c8648!2sLe%20MARVELOUS!5e0!3m2!1sfr!2sdz!4v1765556127293!5m2!1sfr!2sdz"
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
    url: "https://www.google.com/maps?q=Le%20MARVELOUS%20Ouled%20Moussa",
    cuisine: ["Crêperie", "Diner 50's", "Coffee Shop"]
  },

  // Livraison
  delivery: {
    enabled: true,
    platforms: [
      {
        id: "whatsapp-direct",
        name: "Livraison directe",
        url: "https://wa.me/213556782194",
        icon: "whatsapp"
      }
    ]
  },

  // ========== PRÉFÉRÉS CLIENTS ==========
  featured: {
    enabled: true,
    title: "Les préférés de nos clients",
    subtitle: "Nos créations signature les plus populaires",
    items: [
      "la-marvelous",
      "la-mexicana",
      "crepe-nutella-banane",
      "gaufre-complete"
    ]
  },

  // Avis clients
  reviews: [
    {
      author: "HA HD",
      isLocalGuide: false,
      rating: 5,
      relativeTime: "il y a 4 mois",
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
      rating: 5,
      relativeTime: "il y a un mois",
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
      rating: 5,
      relativeTime: "il y a 4 mois",
      text: "Très très bon restaurant. Les enfants étaient ravis. Crêpes sucrées et salées excellentes. Aucun regret.",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5
      }
    }
  ],

  branding: {
    primaryColor: "#2ec4b6",
    primaryDark: "#043b36",
    secondaryColor: "#0b0b0f",
    accentColor: "#ff6fae",
    logo: "marvel.jpeg",
    favicon: "favicon.png",
    ogImage: "marvelhero.png"
  },

  // Horaires d'ouverture
  openingHours: [
    { day: "lundi", opens: "07:00", closes: "21:30" },
    { day: "mardi", opens: "07:00", closes: "21:30" },
    { day: "mercredi", opens: "07:00", closes: "21:30" },
    { day: "jeudi", opens: "07:00", closes: "21:30" },
    { day: "vendredi", slots: [
      { opens: "07:00", closes: "11:30" },
      { opens: "15:30", closes: "21:30" }
    ]},
    { day: "samedi", opens: "07:00", closes: "21:30" },
    { day: "dimanche", opens: "07:00", closes: "21:30" }
  ],

  // ========== MENU ==========
  menu: {
    categories: [
      {
        id: "crepes-salees-signature",
        name: "Crêpes Salées Signature",
        icon: "fa-star",
        description: "Nos créations maison",
        items: [
          {
            id: "la-marvelous",
            name: "La Marvelous",
            description: "Viande hachée, fromage, oignons, poivrons, sauce maison",
            price: 550,
            image: "marvelous.jpg",
            status: "available"
          },
          {
            id: "la-mexicana",
            name: "La Mexicana",
            description: "Poulet épicé, guacamole, cheddar, jalapeños",
            price: 580,
            image: "mexicana.jpg",
            status: "available"
          },
          {
            id: "la-forestiere",
            name: "La Forestière",
            description: "Champignons, jambon de dinde, gruyère, crème fraîche",
            price: 520,
            image: "forestiere.jpg",
            status: "available"
          },
          {
            id: "chicken-bbq",
            name: "La Chicken BBQ",
            description: "Poulet grillé, sauce BBQ, oignons caramélisés, cheddar",
            price: 560,
            image: "chicken-bbq.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "crepes-salees-classiques",
        name: "Crêpes Salées Classiques",
        icon: "fa-utensils",
        description: "Les incontournables",
        items: [
          {
            id: "jambon-fromage",
            name: "Crêpe Jambon Fromage",
            description: "Jambon de dinde, fromage fondu",
            price: 380,
            image: "jambon-fromage.jpg",
            status: "available"
          },
          {
            id: "crepe-thon",
            name: "Crêpe Thon",
            description: "Thon, mayonnaise, fromage",
            price: 400,
            image: "thon.jpg",
            status: "available"
          },
          {
            id: "viande-hachee",
            name: "Crêpe Viande Hachée",
            description: "Viande hachée épicée, fromage",
            price: 420,
            image: "viande-hachee.jpg",
            status: "available"
          },
          {
            id: "crepe-poulet",
            name: "Crêpe Poulet",
            description: "Poulet grillé, sauce blanche, fromage",
            price: 430,
            image: "poulet.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "crepes-sucrees",
        name: "Crêpes Sucrées",
        icon: "fa-cookie",
        description: "Pour les gourmands",
        items: [
          {
            id: "crepe-nutella",
            name: "Crêpe Nutella",
            description: "Nutella généreux",
            price: 300,
            image: "nutella.jpg",
            status: "available"
          },
          {
            id: "crepe-nutella-banane",
            name: "Crêpe Nutella Banane",
            description: "Nutella, bananes fraîches",
            price: 350,
            image: "nutella-banane.jpg",
            status: "available"
          },
          {
            id: "crepe-confiture",
            name: "Crêpe Confiture",
            description: "Confiture au choix",
            price: 250,
            image: "confiture.jpg",
            status: "available"
          },
          {
            id: "miel-amandes",
            name: "Crêpe Miel Amandes",
            description: "Miel, amandes effilées",
            price: 320,
            image: "miel-amandes.jpg",
            status: "available"
          },
          {
            id: "caramel-beurre-sale",
            name: "Crêpe Caramel Beurre Salé",
            description: "Sauce caramel maison",
            price: 340,
            image: "caramel.jpg",
            status: "available"
          },
          {
            id: "fruits-rouges",
            name: "Crêpe Fruits Rouges",
            description: "Coulis fruits rouges, chantilly",
            price: 380,
            image: "fruits-rouges.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "gaufres",
        name: "Gaufres",
        icon: "fa-stroopwafel",
        description: "Gaufres maison",
        items: [
          {
            id: "gaufre-nature",
            name: "Gaufre Nature",
            description: "Sucre glace",
            price: 200,
            image: "gaufre-nature.jpg",
            status: "available"
          },
          {
            id: "gaufre-nutella",
            name: "Gaufre Nutella",
            description: "Nutella généreux",
            price: 300,
            image: "gaufre-nutella.jpg",
            status: "available"
          },
          {
            id: "gaufre-chantilly",
            name: "Gaufre Chantilly",
            description: "Chantilly maison",
            price: 280,
            image: "gaufre-chantilly.jpg",
            status: "available"
          },
          {
            id: "gaufre-complete",
            name: "Gaufre Complète",
            description: "Nutella, banane, chantilly, amandes",
            price: 400,
            image: "gaufre-complete.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "boissons-chaudes",
        name: "Boissons Chaudes",
        icon: "fa-mug-hot",
        description: "Café, thé et plus",
        items: [
          {
            id: "cafe-express",
            name: "Café Express",
            description: "Café court",
            price: 100,
            image: "cafe.jpg",
            status: "available"
          },
          {
            id: "cafe-creme",
            name: "Café Crème",
            description: "Café avec lait",
            price: 150,
            image: "cafe-creme.jpg",
            status: "available"
          },
          {
            id: "cappuccino",
            name: "Cappuccino",
            description: "Café, lait mousseux, cacao",
            price: 200,
            image: "cappuccino.jpg",
            status: "available"
          },
          {
            id: "chocolat-chaud",
            name: "Chocolat Chaud",
            description: "Chocolat onctueux",
            price: 200,
            image: "chocolat-chaud.jpg",
            status: "available"
          },
          {
            id: "the-menthe",
            name: "Thé à la Menthe",
            description: "Thé vert, menthe fraîche",
            price: 150,
            image: "the-menthe.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "boissons-fraiches",
        name: "Boissons Fraîches",
        icon: "fa-glass-water",
        description: "Jus et smoothies",
        items: [
          {
            id: "jus-orange",
            name: "Jus d'Orange Frais",
            description: "Oranges pressées",
            price: 200,
            image: "jus-orange.jpg",
            status: "available"
          },
          {
            id: "citronnade",
            name: "Citronnade",
            description: "Citron, sucre, eau fraîche",
            price: 150,
            image: "citronnade.jpg",
            status: "available"
          },
          {
            id: "smoothie-banane",
            name: "Smoothie Banane",
            description: "Banane, lait, miel",
            price: 250,
            image: "smoothie-banane.jpg",
            status: "available"
          },
          {
            id: "smoothie-fruits-rouges",
            name: "Smoothie Fruits Rouges",
            description: "Mix fruits rouges",
            price: 280,
            image: "smoothie-fruits-rouges.jpg",
            status: "available"
          },
          {
            id: "eau-minerale",
            name: "Eau Minérale",
            description: "50cl",
            price: 50,
            image: "eau.jpg",
            status: "available"
          },
          {
            id: "soda",
            name: "Soda",
            description: "Coca, Fanta, Sprite",
            price: 100,
            image: "soda.jpg",
            status: "available"
          }
        ]
      },
      {
        id: "menu-enfant",
        name: "Menu Enfant",
        icon: "fa-child",
        description: "Pour les petits",
        items: [
          {
            id: "menu-petit-marvel",
            name: "Menu Petit Marvel",
            description: "Mini crêpe salée + jus + surprise",
            price: 400,
            image: "menu-enfant-sale.jpg",
            status: "available"
          },
          {
            id: "menu-petit-gourmand",
            name: "Menu Petit Gourmand",
            description: "Mini crêpe sucrée + jus + surprise",
            price: 380,
            image: "menu-enfant-sucre.jpg",
            status: "available"
          }
        ]
      }
    ]
  },

  // Suppléments
  supplements: {
    catalog: [
      { id: "fromage", name: "Fromage", price: 50 },
      { id: "oeuf", name: "Oeuf", price: 50 },
      { id: "champignons", name: "Champignons", price: 80 },
      { id: "poulet", name: "Poulet", price: 100 },
      { id: "viande-hachee", name: "Viande hachée", price: 100 },
      { id: "nutella", name: "Nutella", price: 80 },
      { id: "banane", name: "Banane", price: 50 },
      { id: "chantilly", name: "Chantilly", price: 50 },
      { id: "amandes", name: "Amandes", price: 60 },
      { id: "fruits-rouges", name: "Fruits rouges", price: 80 }
    ],
    defaultForCategories: {
      "crepes-salees-signature": ["fromage", "oeuf", "champignons", "poulet", "viande-hachee"],
      "crepes-salees-classiques": ["fromage", "oeuf", "champignons", "poulet", "viande-hachee"],
      "crepes-sucrees": ["nutella", "banane", "chantilly", "amandes", "fruits-rouges"],
      "gaufres": ["nutella", "banane", "chantilly", "amandes", "fruits-rouges"]
    }
  },

  // Fidélité
  loyalty: {
    enabled: true,
    pointsPerDinar: 10,
    rewards: [
      { points: 500, reward: "Boisson offerte" },
      { points: 800, reward: "Crêpe sucrée offerte" },
      { points: 1000, reward: "-200 DA sur commande" },
      { points: 1500, reward: "Crêpe salée signature offerte" }
    ]
  },

  // SEO
  seo: {
    title: "Le MARVELOUS · Diner 50's à Ouled Moussa · Crêpes & Coffee",
    description: "Le MARVELOUS à Ouled Moussa (Riad City, Cité OMS 562 logements) : crêpes salées signature, crêpes sucrées, gaufres, boissons & menu enfant. Sur place, à emporter et livraison en commande directe.",
    keywords: ["crêpes ouled moussa", "diner 50s algérie", "le marvelous", "gaufres algérie", "coffee shop boumerdes"]
  },

  // FAQ
  faq: [
    { question: "Quels moyens de paiement acceptez-vous ?", answer: "Argent liquide uniquement." },
    { question: "Livrez-vous ?", answer: "Oui, livraison en commande directe (WhatsApp / téléphone)." },
    { question: "Convient-il pour les familles ?", answer: "Oui, nous avons une salle dédiée aux familles et un menu enfant." },
    { question: "Y a-t-il un espace pour les femmes ?", answer: "Oui, nous disposons d'une salle réservée aux femmes." },
    { question: "Peut-on regarder du sport sur place ?", answer: "Oui, écrans disponibles pour regarder du sport." }
  ]
};
