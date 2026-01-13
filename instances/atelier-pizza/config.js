// L'Atelier Pizza Roubaix – Configuration SnackApp
// Pizzas & pâtes 100% halal – Frais & fait maison · Roubaix, France

window.SNACK_CONFIG = {
  id: "atelier-pizza-roubaix",
  name: "L'Atelier Pizza",
  slug: "atelier-pizza-roubaix",
  legalName: "L'Atelier Pizza Roubaix",
  brandTagline: "Pizzas & pâtes 100% halal – Frais & fait maison",
  priceRange: "EUR",
  currency: "EUR",  // Devise (EUR pour France)
  isHalal: true,
  hideFooterAddress: false,

  // Couleurs de la marque
  branding: {
    primaryColor: "#c10000",  // Rouge
    secondaryColor: "#000000", // Noir
    accentColor: "#ffffff"     // Blanc
  },

  location: {
    addressLine1: "70 Boulevard de la République",
    addressLine2: "",
    postalCode: "59100",
    city: "Roubaix",
    countryCode: "FR",
    latitude: 50.6927,
    longitude: 3.1746
  },

  contact: {
    phone: "+33320363948",
    phoneDisplay: "03 20 36 39 48",
    email: "atelierpizzaroubaix@gmail.com",
    allowWhatsAppOrders: false,  // Pas de WhatsApp (France)
    whatsappOrdersNumber: null
  },

  urls: {
    website: "https://atelierpizza.mon-agenceweb.fr/",
    googleMaps: null,  // À ajouter si disponible
    googleMapsEmbed: null
  },

  social: {
    facebook: "",
    instagram: "",
    tiktok: "",
    snapchat: ""
  },

  google: {
    placeId: null,
    rating: 0,
    reviewCount: 0,
    url: "",
    cuisine: ["Pizzeria", "Pâtes", "Halal"]
  },

  // Horaires d'ouverture
  hours: {
    monday: {
      lunch: {open: "11:30", close: "14:00"},
      dinner: {open: "18:00", close: "23:00"}
    },
    tuesday: {
      lunch: {open: "11:30", close: "14:00"},
      dinner: {open: "18:00", close: "23:00"}
    },
    wednesday: {
      lunch: {open: "11:30", close: "14:00"},
      dinner: {open: "18:00", close: "23:00"}
    },
    thursday: {
      lunch: {open: "11:30", close: "14:00"},
      dinner: {open: "18:00", close: "23:00"}
    },
    friday: {
      lunch: null,  // Fermé le midi
      dinner: {open: "18:00", close: "23:00"}
    },
    saturday: {
      lunch: null,  // Fermé le midi
      dinner: {open: "18:00", close: "23:00"}
    },
    sunday: {
      lunch: null,  // Fermé le midi
      dinner: {open: "18:00", close: "23:00"}
    }
  },

  // Moyens de paiement acceptés
  paymentMethods: {
    cash: true,              // Espèces
    cardTerminal: true,      // CB au TPE (sur place/livraison)
    cardOnline: true,        // CB en ligne (Stripe)
    ticketResto: true        // Ticket Restaurant (mentionner uniquement)
  },

  // Services disponibles
  features: {
    delivery: true,          // Livraison (via Uber Eats/Deliveroo)
    takeout: true,           // À emporter
    dineIn: true,            // Sur place
    preorder: true,          // Précommande
    loyalty: false,          // Programme fidélité (désactivé pour le moment)
    uberEats: true,
    deliveroo: true
  },

  // Liens plateformes de livraison
  deliveryPlatforms: {
    uberEats: "https://www.ubereats.com/fr/store/latelier-pizza-roubaix/4CQok2ZbXOyUrkueXqipxA",
    deliveroo: "https://deliveroo.fr/menu/lille/roubaix-nord-est/latelier-pizza-roubaix"
  },

  // Offres spéciales
  specialOffers: {
    lunch: {
      enabled: true,
      description: "Offre Midi : Pizza ou Plat + Boisson offerte",
      items: [
        {name: "Solo 26cm + Boisson", price: 7.50},
        {name: "Duo 31cm + Boisson", price: 9.00},
        {name: "Pasta/Gratin + Boisson", price: 9.00}
      ]
    },
    takeaway: {
      enabled: true,
      items: [
        {name: "2 Pizzas Duo (31cm)", price: 15.00},
        {name: "2 Pizzas Solo (26cm)", price: 13.00}
      ]
    }
  }
};
