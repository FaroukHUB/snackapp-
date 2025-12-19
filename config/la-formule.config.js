// La Formule – configuration SnackApp
// À placer dans: ./config/la-formule.config.js

window.SNACK_CONFIG = {
  id: "la-formule",
  name: "La Formule",
  slug: "la-formule",
  brandTagline: "Tacos, burgers, sandwiches et spécialités maison",

  location: {
    addressLine1: "2 Rue Hector Berlioz",
    postalCode: "59700",
    city: "Marcq-en-Baroeul",
    countryCode: "FR",
    latitude: 50.6650182,
    longitude: 3.0855894
  },

  contact: {
    phone: "+33982456975",
    phoneDisplay: "09 82 45 69 75",
    allowWhatsAppOrders: true,
    whatsappOrdersNumber: "33782121904"
  },

  urls: {
    website: "",
    googleMaps:
      "https://www.google.com/maps/place/2+Rue+Hector+Berlioz,+59700+Marcq-en-Baroeul,+France",
    googleMapsEmbed:
      "https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d2528.9591928352884!2d3.0855894!3d50.6650182!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sfr!2sdz!4v1764582908571!5m2!1sfr!2sdz"
  },

  social: {
    facebook: "https://web.facebook.com/p/La-formule-100064280400137",
    instagram: "",
    tiktok: "",
    snapchat: ""
  },

  google: {
    placeId: null,
    rating: 3.7,
    reviewCount: 136,
    url: "https://maps.app.goo.gl/",
    cuisine: ["Tacos", "Kebab", "Kapsalon", "Burgers", "Restauration rapide"]
  },

  // Avis Uber Eats (pour le hero + FAQ)
  uber: {
    rating: 4.3,
    reviewCount: 500, // "500+" => 500
    url: "https://www.ubereats.com/fr/store/la-formule/tL54YddwRdWouPGOcRLjOA?ps=1"
  },

  // ========== PRÉFÉRÉS CLIENTS ==========
  featured: {
    enabled: true,
    title: "🔥 Les préférés de nos clients",
    subtitle: "Nos best-sellers qui font toujours mouche",
    items: [
      "burger-mega",        // Le Mega
      "tacos-galette",      // Tacos
      "kaps-2v",            // Kapsaloon 2 viandes
      "tiramisu-maison"     // Tiramisu maison
    ]
  },

  reviews: [
    {
      author: "Vanessa B.",
      isLocalGuide: false,
      localGuideLevel: null,
      rating: 5,
      relativeTime: null,
      context: "Uber Eats",
      text: "Qualité des produits",
      aspects: {
        cuisine: 5,
        service: 5,
        ambiance: 5,
      }
    }
  ],

  openingHours: [
    { day: "lundi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "22:30" },
    { day: "mardi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "22:30" },
    { day: "mercredi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "22:30" },
    { day: "jeudi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "22:30" },
    { day: "vendredi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "23:00" },
    { day: "samedi",opens: "11:00", closes: "14:30" },
    { day: "",opens: "18:00", closes: "23:00" },
    { day: "dimanche",opens: "18:00", closes: "22:30" }
  ],

  // Liste globale de sauces (on l'utilisera dans le runtime plus tard)
  sauces: [
    "algérienne",
    "américaine",
    "mayonnaise",
    "ketchup",
    "hannibal",
    "poivre",
    "curry",
    "chili thaï",
    "samouraï",
    "biggy",
    "blanche",
    "andalouse",
    "harissa"
  ],

  // ========== BASE D'INGRÉDIENTS PIZZA ==========
  // Base réutilisable pour toutes les pizzerias
  pizzaIngredients: {
    // Bases de sauce
    bases: [
      "sauce tomate",
      "crème fraîche"
    ],

    // Fromages
    fromages: [
      "mozzarella",
      "chèvre",
      "bleu",
      "emmental",
      "cheddar",
      "boursin",
      "raclette"
    ],

    // Viandes
    viandes: [
      "boeuf haché",
      "filet de poulet",
      "merguez",
      "chorizo",
      "jambon de dinde",
      "jambon",
      "thon",
      "saumon fumé",
      "lardons fumés"
    ],

    // Légumes
    legumes: [
      "olives noires",
      "olives",
      "oignons rouges",
      "oignons frais",
      "oignon frits",
      "tomates cerises",
      "champignons",
      "poivrons",
      "pomme de terre",
      "piments jalapeños"
    ],

    // Sauces et assaisonnements
    saucesEpices: [
      "origan",
      "sauce au poivre",
      "sauce barbecue",
      "sauce algérienne",
      "sauce thaï",
      "sauce gruyère",
      "sauce burger",
      "sauce curry",
      "miel"
    ]
  },

  // ========== SUPPLÉMENTS PIZZA ==========
  // Tarifs par défaut (chaque pizzeria peut les adapter)
  pizzaSupplements: {
    legumes: {
      senior: 1.60,
      xxl: 2.60
    },
    viande: {
      senior: 2.60,
      xxl: 3.60
    }
  },

  theme: {
    colors: {
      // Palette La Formule : rouge / noir / blanc
      brand: "#e11b22",
      brandSoft: "#fee2e2",
      brandTextOn: "#ffffff",

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
      heading:
        "system-ui, -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif",
      body:
        "system-ui, -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif"
    }
  },

  assets: {
    hero: {
      image: "/images/heroformule.webp",
      alt: "La Formule - tacos, burgers et snacking à Marcq-en-Baroeul"
    },
    logo: {
      light: "/images/logoformule.webp",
      dark: "/images/logoformule.webp",
      alt: "La Formule"
    },
    gallery: [
      "/images/la-formule-1.webp",
      "/images/la-formule-2.webp",
      "/images/la-formule-3.webp"
    ]
  },

  priceRange: "€",

  platforms: [
    {
      id: "uber-eats",
      name: "Uber Eats",
      url: "https://www.ubereats.com/fr/store/la-formule/tL54YddwRdWouPGOcRLjOA"
    },
    {
      id: "deliveroo",
      name: "Deliveroo",
      url: "https://deliveroo.fr/fr/menu/lille/lille-marcq-en-baroeul/la-formule"
    }
  ],

  // --------- MENU ---------
  menu: {
    categories: [
      // TACOS – builder à venir dans le runtime
      {
        id: "tacos",
        name: "Tacos",
        description:
          "Compose ton tacos : choisis la taille, les viandes, la sauce et les extras.",
        items: [
          {
            id: "tacos-galette",
            name: "Tacos",
            description:
              "Base galette garnie de frites et sauce fromagère. Tailles M, L, XL, XXL.",
            image: "/images/tacos.webp",
            baseIngredients: [],
            tacosConfig: {
              bases: [
                { id: "m-1v", label: "M · 1 viande", meats: 1, price: 6.5 },
                { id: "l-2v", label: "L · 2 viandes", meats: 2, price: 8.0 },
                {
                  id: "xl-3v",
                  label: "XL · 3 viandes",
                  meats: 3,
                  price: 9.5
                },
                {
                  id: "xxl-4v",
                  label: "XXL · 4 viandes",
                  meats: 4,
                  price: 14.0
                }
              ],
              meats: [
                "viande hachée",
                "escalope poulet",
                "kebab",
                "tenders",
                "nuggets",
                "merguez",
                "cordon bleu"
              ],
              sauces: [
                "algérienne",
                "américaine",
                "mayonnaise",
                "ketchup",
                "hannibal",
                "poivre",
                "curry",
                "chili thaï",
                "samouraï",
                "biggy",
                "blanche",
                "andalouse",
                "harissa"
              ],
              freeCrudites: ["salade", "tomates", "oignons", "olives"],
              extrasGroupId: "tacos-extras"
            },
            price: 6.5
          },
          {
            id: "tacos-bowl",
            name: "Tacos bowl",
            description:
              "Base bowl garnie de frites, viande et sauce fromagère.",
            image: "/images/bowl.webp",
            baseIngredients: [],
            tacosConfig: {
              bases: [
                {
                  id: "bowl-m-1v",
                  label: "Bowl M · 1 viande",
                  meats: 1,
                  price: 7.5
                },
                {
                  id: "bowl-l-2v",
                  label: "Bowl L · 2 viandes",
                  meats: 2,
                  price: 9.0
                },
                {
                  id: "bowl-xl-3v",
                  label: "Bowl XL · 3 viandes",
                  meats: 3,
                  price: 11.0
                }
              ],
              meats: [
                "viande hachée",
                "escalope poulet",
                "kebab",
                "tenders",
                "nuggets",
                "merguez",
                "cordon bleu"
              ],
              sauces: [
                "algérienne",
                "américaine",
                "mayonnaise",
                "ketchup",
                "hannibal",
                "poivre",
                "curry",
                "chili thaï",
                "samouraï",
                "biggy",
                "blanche",
                "andalouse",
                "harissa"
              ],
              freeCrudites: ["salade", "tomates", "oignons", "olives"],
              extrasGroupId: "tacos-extras"
            },
            price: 7.5
          }
        ]
      },

      // BURGERS
     {
        id: "burgers",
        name: "Burgers",
        items: [
          {
            id: "burger-classic",
            name: "Le Classic",
            description: "1 steak, salade, tomates, oignons, sauce au choix.",
            priceSolo: 4.5,
            priceMenu: 6.5,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-chicken",
            name: "Le Chicken",
            description: "1 filet de poulet, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-double-chicken",
            name: "Double Chicken",
            description: "2 filets de poulet, salade, tomates, oignons, sauce au choix.",
            priceSolo: 6.5,
            priceMenu: 8.5,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-big",
            name: "Le Big",
            description: "2 steaks, salade, tomates, oignons, sauce au choix.",
            priceSolo: 6.0,
            priceMenu: 8.0,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-chicken-beef",
            name: "Chicken Beef",
            description: "1 steak + 1 filet de poulet, salade, tomates, oignons, sauce au choix.",
            priceSolo: 7.0,
            priceMenu: 9.0,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "burger-mega",
            name: "Le Mega",
            description:
              "3 steaks 90g, salade, tomates, oignons, sauce au choix.",
            priceSolo: 7.5,
            priceMenu: 9.5,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"],
            isSignature: true
          },
          {
            id: "burger-vege",
            name: "Le Végé",
            description: "Galette de légumes, salade, tomates, oignons, sauce au choix.",
            priceSolo: 4.5,
            priceMenu: 6.5,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"],
            isVeggie: true
          },
          {
            id: "burger-fish",
            name: "Le Fish",
            description: "Steak de poisson, salade, tomates, oignons, sauce au choix.",
            priceSolo: 4.5,
            priceMenu: 6.5,
            image: "/images/burger.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          }
        ]
      },

      // SIGNATURES (baguettes)
      {
        id: "signatures",
        name: "Signatures",
        items: [
          {
            id: "royal",
            name: "Le Royal",
            description:
              "Cordon bleu, steak 90g, cheddar, œuf, crudités, sauce au choix.",
            priceSolo: 7.0,
            priceMenu: 9.0,
            image: "/images/sandwich.webp"
          },
          {
            id: "escalope",
            name: "L'Escalope",
            description:
              "Escalope de poulet, cheddar, œuf, crudités, sauce au choix.",
            priceSolo: 7.0,
            priceMenu: 9.0,
            image: "/images/sandwich.webp"
          },
          {
            id: "buffalo",
            name: "Le Buffalo",
            description:
              "Escalope de poulet, crème fraîche, 3 cheddars, crudités, sauce au choix.",
            priceSolo: 7.0,
            priceMenu: 9.0,
            image: "/images/sandwich.webp"
          },
          {
            id: "chicken-beef-baguette",
            name: "Le Chicken Beef",
            description:
              "1 steak 90g, escalope de poulet, œuf, 2 cheddars, crudités, sauce au choix.",
            priceSolo: 7.5,
            priceMenu: 9.5,
            image: "/images/sandwich.webp"
          }
        ]
      },

      // GALETTES
      {
        id: "galettes",
        name: "Nos Galettes",
        items: [
          {
            id: "galette-kebab",
            name: "Kebab",
            description: "Galette, kebab, salade, tomates, oignons, sauce au choix.",
            priceSolo: 6.0,
            priceMenu: 8.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-kefta",
            name: "Kefta (viande hachée)",
            description: "Galette, kefta, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-merguez",
            name: "Merguez",
            description: "Galette, merguez, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-mexicanos",
            name: "Mexicanos",
            description: "Galette, mexicanos, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-brochette",
            name: "Brochette poulet",
            description: "Galette, brochette poulet, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.5,
            priceMenu: 7.5,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-mixte-2v",
            name: "Mixte 2 viandes",
            description: "Galette, 2 viandes au choix, salade, tomates, oignons, sauce au choix.",
            priceSolo: 7.0,
            priceMenu: 9.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-cordon-bleu",
            name: "Cordon bleu",
            description: "Galette, cordon bleu, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-nuggets",
            name: "Nuggets",
            description: "Galette, nuggets, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "galette-tenders",
            name: "Tenders",
            description: "Galette, tenders, salade, tomates, oignons, sauce au choix.",
            priceSolo: 5.0,
            priceMenu: 7.0,
            image: "/images/galette.webp",
            baseIngredients: ["salade", "tomates", "oignons"]
          }
        ]
      },

      // SANDWICHS
      {
        id: "sandwichs",
        name: "Sandwichs",
        items: [
          {
            id: "sand-kebab",
            name: "Kebab",
            description: "Pain, kebab, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 6.0,
            priceMenu: 8.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-kefta",
            name: "Kefta (viande hachée)",
            description: "Pain, kefta, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 5.0,
            priceMenu: 7.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-merguez",
            name: "Merguez",
            description: "Pain, merguez, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 5.0,
            priceMenu: 7.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-mexicanos",
            name: "Mexicanos",
            description: "Pain, mexicanos, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 5.0,
            priceMenu: 7.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-brochette",
            name: "Brochette poulet",
            description: "Pain, brochette poulet, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-mixte-2v",
            name: "Mixte 2 viandes",
            description: "Pain, 2 viandes au choix, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 7.0,
            priceMenu: 9.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-cordon-bleu",
            name: "Cordon bleu",
            description: "Pain, cordon bleu, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 5.0,
            priceMenu: 6.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-nuggets",
            name: "Nuggets",
            description: "Pain, nuggets, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 4.5,
            priceMenu: 6.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "sand-tenders",
            name: "Tenders",
            description: "Pain, tenders, salade, tomates, oignons, sauce au choix.",
            image: "/images/sand.webp",
            priceSolo: 4.5,
            priceMenu: 6.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          }
        ]
      },

      // PANINIS
      {
        id: "paninis",
        name: "Paninis",
        items: [
          {
            id: "panini-kebab",
            name: "Kebab",
            description: "Panini, kebab, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 6.5,
            priceMenu: 8.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-kefta",
            name: "Kefta (viande hachée)",
            description: "Panini, kefta, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-merguez",
            name: "Merguez",
            description: "Panini, merguez, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-mexicanos",
            name: "Mexicanos",
            description: "Panini, mexicanos, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-brochette",
            name: "Brochette poulet",
            description: "Panini, brochette poulet, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 6.0,
            priceMenu: 8.0,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-mixte-2v",
            name: "Mixte 2 viandes",
            description: "Panini, 2 viandes au choix, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 7.5,
            priceMenu: 9.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-cordon-bleu",
            name: "Cordon bleu",
            description: "Panini, cordon bleu, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-nuggets",
            name: "Nuggets",
            description: "Panini, nuggets, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-tenders",
            name: "Tenders",
            description: "Panini, tenders, salade, tomates, oignons, sauce au choix.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          },
          {
            id: "panini-3fromages",
            name: "3 fromages",
            description: "Panini, 3 fromages, salade, tomates, oignons.",
            image: "/images/paninis.webp",
            priceSolo: 5.5,
            priceMenu: 7.5,
            baseIngredients: ["salade", "tomates", "oignons"]
          }
        ]
      },

      // TEX MEX
      {
        id: "texmex",
        name: "Tex Mex",
        items: [
          { id: "wings-x5", name: "Wings x5", image: "/images/mozza.webp", price: 5.8 },
          { id: "nuggets-x7", name: "Nuggets x7", image: "/images/mozza.webp", price: 5.8 },
          { id: "tenders-x5", name: "Tenders x5", image: "/images/mozza.webp", price: 5.8 },
          { id: "mozza-x5", name: "Stick mozza x5", image: "/images/mozza.webp", price: 4.0 },
          { id: "onion-rings-x6", name: "Onion rings x6", image: "/images/mozza.webp", price: 4.0 }
        ]
      },

      // SALADES
      {
        id: "salades",
        name: "Salades",
        items: [
          { id: "salade-poulet", name: "Salade poulet", image: "/images/salade.webp", price: 7.0 },
          { id: "salade-thon", name: "Salade thon", image: "/images/salade.webp", price: 6.0 }
        ]
      },

      // KIDS
      {
        id: "menu-enfant",
        name: "Menu enfant",
        items: [
          {
            id: "kids-menu",
            name: "Menu Kids",
            description: "4 nuggets, frites, boisson Capri-Sun, jouet.",
            image: "/images/kids.webp",
            priceMenu: 6.0,
            isKids: true,
            kidsOptions: [
              { id: "kid-nuggets", name: "Nuggets" },
              { id: "kid-tenders", name: "Tenders" }
            ]
          }
        ]
      },

      // KAPSALOON
      {
        id: "kapsaloon",
        name: "Kapsaloon",
        description:
          "Compose ton Kapsaloon : choisis la taille et les viandes. Frites, fromage et sauces incluses.",
        items: [
          {
            id: "kaps-1v",
            name: "Kapsaloon 1 viande",
            description:
              "Frites, fromage, 1 viande au choix, sauces (max. 2).",
            image: "/images/kapsaloon.webp",
            baseIngredients: [],
            kapsaloonConfig: {
              bases: [
                { id: "kaps-1v", label: "1 viande", meats: 1, price: 7.0 }
              ],
              meats: [
                "viande hachée",
                "escalope poulet",
                "kebab",
                "tenders",
                "nuggets",
                "merguez",
                "cordon bleu"
              ],
              sauces: [
                "algérienne",
                "américaine",
                "mayonnaise",
                "ketchup",
                "hannibal",
                "poivre",
                "curry",
                "chili thaï",
                "samouraï",
                "biggy",
                "blanche",
                "andalouse",
                "harissa"
              ],
              extrasGroupId: "kapsaloon"
            },
            price: 7.0
          },
          {
            id: "kaps-2v",
            name: "Kapsaloon 2 viandes",
            description:
              "Frites, fromage, 2 viandes au choix, sauces (max. 2).",
            image: "/images/kapsaloon.webp",
            baseIngredients: [],
            kapsaloonConfig: {
              bases: [
                { id: "kaps-2v", label: "2 viandes", meats: 2, price: 8.0 }
              ],
              meats: [
                "viande hachée",
                "escalope poulet",
                "kebab",
                "tenders",
                "nuggets",
                "merguez",
                "cordon bleu"
              ],
              sauces: [
                "algérienne",
                "américaine",
                "mayonnaise",
                "ketchup",
                "hannibal",
                "poivre",
                "curry",
                "chili thaï",
                "samouraï",
                "biggy",
                "blanche",
                "andalouse",
                "harissa"
              ],
              extrasGroupId: "kapsaloon"
            },
            price: 8.0
          }
        ]
      },

      // DESSERTS
      {
        id: "desserts",
        name: "Desserts",
        items: [
          {
            id: "tarte-daims",
            name: "Tarte aux Daims",
            description: "Tarte maison aux Daims.",
            image: "/images/tarte.png",
            price: 3.0
          },
          {
            id: "tiramisu-maison",
            name: "Tiramisu maison",
            description: "Tiramisu fait maison.",
            image: "/images/tira.png",
            price: 3.0
          }
        ]
      },

      // BOISSONS
      {
        id: "boissons",
        name: "Boissons",
        items: [
          { id: "eau", name: "Eau", price: 2.0 },
          { id: "perrier", name: "Perrier", price: 2.0 },
          { id: "ice-tea", name: "Ice Tea", price: 2.0 },
          { id: "oasis-tropical", name: "Oasis Tropical", price: 2.0 },
          {
            id: "oasis-pcf",
            name: "Oasis pomme cassis framboise",
            price: 2.0
          },
          {
            id: "schweppes-agrumes",
            name: "Schweppes Agrumes",
            price: 2.0
          },
          {
            id: "sevenup-mojito",
            name: "Seven Up Mojito",
            price: 2.0
          },
          {
            id: "tropico-exotique",
            name: "Tropico Exotique",
            price: 2.0
          },
          { id: "fanta", name: "Fanta", price: 2.0 },
          { id: "coca", name: "Coca", price: 2.0 },
          { id: "coca-zero", name: "Coca Zéro", price: 2.0 },
          { id: "coca-cherry", name: "Coca Cherry", price: 2.0 }
        ]
      }
    ]
  },

  // --------- SUPPLÉMENTS ---------
  supplements: {
    catalog: {
      // Frites & dérivés
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
      "sup-frite-fromagere": {
        id: "sup-frite-fromagere",
        name: "Frite fromagère",
        price: 5.0
      },
      "sup-frite-cheddar": {
        id: "sup-frite-cheddar",
        name: "Frite cheddar",
        price: 5.0
      },

      "sup-galette-pdt": {
        id: "sup-galette-pdt",
        name: "Galette pomme de terre",
        price: 1.5
      },

      // Fromages
      "sup-chevre": {
        id: "sup-chevre",
        name: "Fromage de chèvre",
        price: 1.0
      },
      "sup-raclette": {
        id: "sup-raclette",
        name: "Raclette",
        price: 1.0
      },
      "sup-boursin": {
        id: "sup-boursin",
        name: "Boursin",
        price: 1.0
      },
      "sup-mozzarella": {
        id: "sup-mozzarella",
        name: "Mozzarella",
        price: 1.0
      },
      "sup-Vache-qui-rit": {
        id: "sup-Vache-qui-rit",
        name: "Vache qui rit",
        price: 1.0
      },
      "sup-oeuf": {
        id: "sup-oeuf",
        name: "Oeuf",
        price: 1.0
      },

      // Garnitures chaudes
      "sup-bacon": {
        id: "sup-bacon",
        name: "Bacon",
        price: 1.5
      },
      "sup-lardon": {
        id: "sup-lardon",
        name: "Lardons",
        price: 1.5
      },
      "sup-champignons": {
        id: "sup-champignons",
        name: "Champignons",
        price: 1.5
      },
      "sup-poivron": {
        id: "sup-poivron",
        name: "Poivron",
        price: 1.5
      },
      "sup-dinde": {
        id: "sup-dinde",
        name: "Dinde",
        price: 1.5
      },
      "sup-jambon": {
        id: "sup-jambon",
        name: "Jambon poulet",
        price: 1.5
      },

      // Gratinage
      "sup-gratinage": {
        id: "sup-gratinage",
        name: "Gratinage",
        price: 1.5
      }
    },

    defaultForCategories: {
      burgers: ["sup-cheddar", "sup-chevre", "sup-bacon"],
      signatures: ["sup-cheddar", "sup-chevre", "sup-bacon"],
      galettes: ["sup-cheddar", "sup-chevre", "sup-galette-pdt"],
      sandwichs: ["sup-cheddar", "sup-chevre", "sup-galette-pdt"],
      paninis: ["sup-cheddar", "sup-chevre"],
      tacos: [
        "sup-cheddar",
        "sup-chevre",
        "sup-bacon",
        "sup-lardon",
        "sup-champignons",
        "sup-poivron",
        "sup-gratinage"
      ],
      kapsaloon: [
        "sup-cheddar",
        "sup-chevre",
        "sup-bacon",
        "sup-lardon",
        "sup-champignons",
        "sup-poivron"
      ],
      texmex: [],
      salades: [],
      "menu-enfant": []
    }
  },

  // --------- FAQ ---------
  faq: {
    enabled: true,
    items: [
      {
        question:
          "Avez-vous votre propre système de livraison ou vous ne travaillez qu'avec les plateformes ?",
        answer:
          "Nous avons notre propre système de livraison ET nous travaillons également avec Uber Eats et Deliveroo."
      },
      {
        question: "Quels sont les frais de livraison ?",
        answer:
          "Les frais de livraison commencent à partir de 12 € de commande."
      },
      {
        question: "Quelles villes livrez-vous ?",
        answer:
          "Nous livrons Marcq-en-Barœul, Marquette, La Madeleine, Bondues, Wambrechies et Saint-André (si vous êtes ailleurs, n'hésitez pas à nous appeler et à demander quand même)."
      },
      {
        question: "Quels sont les délais d'attente en livraison ?",
        answer:
          "Entre 30 et 60 minutes, selon votre secteur et l'affluence."
      },
      {
        question: "Quels modes de paiement acceptez-vous ?",
        answer:
          "Nous acceptons les paiements par carte bancaire, espèces et cartes / tickets restaurant."
      },
      {
        question: "Est-il possible de manger sur place ?",
        answer:
          "Oui, nous avons des mange-debout et quelques places assises."
      },
      {
        question: "Quel est le temps d'attente à emporter ?",
        answer:
          "En général entre 10 et 25 minutes, selon l'affluence."
      },
      {
        question: "Peut-on commander à l'avance ?",
        answer:
          "Oui, et pour vous faciliter la vie, vous avez sur notre site un système de ticket qui vous permet de préparer votre commande, soit pour nous l'envoyer, soit pour l'avoir sous les yeux au moment où vous appelez."
      }
    ]
  },

  seo: {
    title: "La Formule · Tacos, burgers et snacking à Marcq-en-Barœul",
    description:
      "Compose ton tacos, découvre nos burgers, sandwiches, paninis, tex-mex et menus enfants chez La Formule à Marcq-en-Barœul. Sur place, à emporter ou en livraison."
  }
};
