<?php
require_once __DIR__ . '/bootstrap.php';
$restaurantName = RESTAURANT_NAME ?? 'Restaurant';
$currency = CURRENCY ?? 'EUR';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Clients - <?= htmlspecialchars($restaurantName) ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            padding: 24px;
        }

        .max-w-7xl {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Glass effects */
        .glass-strong {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        /* Utility classes */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-between { justify-content: space-between; }
        .flex-1 { flex: 1; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mr-1 { margin-right: 0.25rem; }
        .mr-2 { margin-right: 0.5rem; }
        .ml-2 { margin-left: 0.5rem; }
        .p-6 { padding: 1.5rem; }
        .pt-3 { padding-top: 0.75rem; }
        .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .py-0\.5 { padding-top: 0.125rem; padding-bottom: 0.125rem; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-full { border-radius: 9999px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-white { color: white; }
        .text-gray-400 { color: #9ca3af; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-800 { color: #1f2937; }
        .text-lg { font-size: 1.125rem; }
        .text-xl { font-size: 1.25rem; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-2xl { font-size: 1.5rem; }
        .text-3xl { font-size: 1.875rem; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .border-t { border-top: 1px solid; }
        .border-white\/10 { border-color: rgba(255, 255, 255, 0.1); }
        .cursor-pointer { cursor: pointer; }
        .transition-all { transition: all 0.3s; }
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }

        /* Customer cards grid */
        #customers-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        #customers-list > div {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        #customers-list > div:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
            background: rgba(30, 41, 59, 0.95) !important;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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

        .bg-gray-200 {
            background: #e5e7eb;
        }

        .text-gray-800 {
            color: #1f2937;
        }

        /* Stats grid */
        #customer-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card i {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }

        /* Filters */
        #tag-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Modal */
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
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
            padding: 24px;
        }

        /* Toast */
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

        /* Form inputs */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #1f2937;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Color variables */
        :root {
            --primary-color: #f59e0b;
        }

        /* Badge colors */
        .bg-yellow-500\/20 { background-color: rgba(245, 158, 11, 0.2); }
        .text-yellow-400 { color: #fbbf24; }
        .bg-red-500\/20 { background-color: rgba(239, 68, 68, 0.2); }
        .text-red-400 { color: #f87171; }
        .bg-gray-500\/20 { background-color: rgba(107, 114, 128, 0.2); }
        .text-purple-600 { color: #9333ea; }
        .text-gray-600 { color: #4b5563; }
        .p-4 { padding: 1rem; }

        /* Loading */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Spacing utilities */
        .space-y-2 > * + * { margin-top: 0.5rem; }
        .space-y-3 > * + * { margin-top: 0.75rem; }
        .space-y-4 > * + * { margin-top: 1rem; }
        .space-y-6 > * + * { margin-top: 1.5rem; }
        .hidden { display: none; }
        .w-full { width: 100%; }

        /* Additional colors */
        .bg-gray-800 { background: #1f2937; }
        .bg-gray-800\/50 { background: rgba(31, 41, 59, 0.5); }
        .border-gray-700 { border-color: #374151; }
        .bg-green-500 { background: #10b981; }
        .text-green-400 { color: #34d399; }
        .bg-green-500\/20 { background: rgba(16, 185, 129, 0.2); }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }

            #customers-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="max-w-7xl">
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
        <div id="customer-stats">
            <!-- Stats will be populated by JS -->
        </div>

        <!-- Filtres Tags -->
        <div class="glass-card p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-filter"></i> Filtrer par tag
            </h3>
            <div id="tag-filters" class="flex flex-wrap gap-2">
                <button onclick="loadCustomers(null)" class="btn bg-gray-200 text-gray-800">
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
            <div id="customers-list">
                <!-- Customers will be populated by JS -->
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                    <p style="color: #6b7280;">Chargement des clients...</p>
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
    <script>
        // Global config for customers.js
        window.CURRENCY = '<?= $currency ?>';
        window.APP_CONFIG = {
            restaurantName: '<?= addslashes($restaurantName) ?>',
            currency: '<?= $currency ?>'
        };
    </script>
    <script src="assets/js/customers.js"></script>
    <script>
        // Close customer modal
        function closeCustomerModal() {
            const modal = document.getElementById('customer-modal');
            if (modal) {
                modal.classList.remove('active');
                modal.innerHTML = '';
            }
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
            toast.style.color = 'white';
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle mr-2"></i>${message}`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const now = new Date();
            const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

            if (diffDays === 0) return "Aujourd'hui";
            if (diffDays === 1) return "Hier";
            if (diffDays < 7) return `Il y a ${diffDays} jours`;
            if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} semaines`;
            if (diffDays < 365) return `Il y a ${Math.floor(diffDays / 30)} mois`;
            return `Il y a ${Math.floor(diffDays / 365)} ans`;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            await loadAvailableTags();
            await loadCustomers();
        });

        // Close modal on click outside
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('customer-modal');
            if (modal && e.target === modal) {
                closeCustomerModal();
            }
        });
    </script>
</body>
</html>
