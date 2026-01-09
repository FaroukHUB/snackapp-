<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Clients - Le Marvelous</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
        }

        .tag {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin: 2px;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="glass-card p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-users text-purple-600"></i> Gestion Clients
                    </h1>
                    <p class="text-gray-600">Interface CRM complète pour gérer vos clients</p>
                </div>
                <div class="flex gap-3">
                    <a href="index.php" class="btn bg-gray-200 text-gray-800">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <button onclick="loadCustomers()" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div id="customer-stats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Stats will be populated by JS -->
        </div>

        <!-- Filtres Tags -->
        <div class="glass-card p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-filter"></i> Filtrer par tag
            </h3>
            <div id="tag-filters" class="flex flex-wrap gap-2">
                <button onclick="loadCustomers(null)" class="btn bg-gray-200 text-gray-800 text-sm">
                    <i class="fas fa-users"></i> Tous
                </button>
                <!-- Tag filters will be populated by JS -->
            </div>
        </div>

        <!-- Liste Clients -->
        <div class="glass-card p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-list"></i> Liste des clients
            </h3>
            <div id="customers-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Customers will be populated by JS -->
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Chargement des clients...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails Client (will be created by JS) -->
    <div id="customer-modal" class="modal"></div>

    <!-- Modal Adresse (will be created by JS) -->
    <div id="address-modal" class="modal"></div>

    <!-- Modal WhatsApp (will be created by JS) -->
    <div id="whatsapp-modal" class="modal"></div>

    <!-- Load JavaScript -->
    <script src="assets/js/customers.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            await loadAvailableTags();
            await loadCustomers();
        });
    </script>
</body>
</html>
