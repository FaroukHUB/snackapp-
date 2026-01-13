<?php
/**
 * Page de gestion des utilisateurs admin
 */

require_once __DIR__ . '/bootstrap.php';

// Vérifier la connexion
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Seul un owner peut accéder à cette page
if (($_SESSION['admin_role'] ?? 'staff') !== 'owner') {
    header('Location: index.php');
    exit;
}

// Vérifier que le système MySQL est actif
if (SNACK_USE_JSON || defined('SNACK_DB_ERROR')) {
    die('Cette fonctionnalité nécessite MySQL. Le mode JSON ne supporte pas la gestion multi-utilisateurs.');
}

$restaurant = getCurrentRestaurant();
$primaryColor = $restaurant['primary_color'] ?? '#c58a3a';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Admins - <?php echo htmlspecialchars($restaurant['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #0f1419;
            color: white;
            padding: 20px;
            padding-bottom: 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 28px;
            color: white;
        }

        .header h1 i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .btn-secondary {
            background: #374151;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .admins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 20px;
        }

        .admin-card {
            background: #1e293b;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #374151;
            transition: all 0.3s;
        }

        .admin-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.5);
        }

        .admin-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #374151;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .admin-info h3 {
            font-size: 18px;
            color: white;
            margin-bottom: 4px;
        }

        .admin-role {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .role-owner {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid #dc2626;
        }

        .role-manager {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid #f59e0b;
        }

        .role-staff {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid #3b82f6;
        }

        .admin-details {
            margin-bottom: 15px;
        }

        .admin-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .admin-detail i {
            color: var(--primary-color);
            width: 16px;
        }

        .admin-actions {
            display: flex;
            gap: 8px;
        }

        .admin-actions button {
            flex: 1;
            padding: 10px;
            font-size: 13px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e293b;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            border: 1px solid #374151;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 22px;
            color: white;
        }

        .close-modal {
            background: #374151;
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #374151;
            border-radius: 10px;
            color: white;
            font-size: 15px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #6ee7b7;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #dc2626;
            color: #fca5a5;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #374151;
        }

        .empty-state i {
            font-size: 48px;
            color: #374151;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #9ca3af;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .admins-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-shield"></i> Gestion des Administrateurs</h1>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Nouvel Admin
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <div id="admins-list" class="admins-grid">
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Chargement...</p>
        </div>
    </div>

    <!-- Modal Créer Admin -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Créer un Admin</h2>
                <button class="close-modal" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="createForm" onsubmit="createAdmin(event)">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nom d'utilisateur</label>
                    <input type="text" name="username" required minlength="3" placeholder="admin">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimum 6 caractères">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Rôle</label>
                    <select name="role" required>
                        <option value="staff">Staff (Employé)</option>
                        <option value="manager">Manager (Gestionnaire)</option>
                        <option value="owner">Owner (Propriétaire)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Créer
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Modifier Admin -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Modifier l'Admin</h2>
                <button class="close-modal" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="editForm" onsubmit="updateAdmin(event)">
                <input type="hidden" name="admin_id" id="edit-admin-id">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nom d'utilisateur</label>
                    <input type="text" name="username" id="edit-username" minlength="3" placeholder="Laisser vide pour ne pas modifier">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                    <input type="password" name="password" id="edit-password" minlength="6" placeholder="Laisser vide pour ne pas modifier">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Rôle</label>
                    <select name="role" id="edit-role" required>
                        <option value="staff">Staff (Employé)</option>
                        <option value="manager">Manager (Gestionnaire)</option>
                        <option value="owner">Owner (Propriétaire)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <script>
        let admins = [];

        // Charger les admins
        async function loadAdmins() {
            try {
                const response = await fetch('api/admin-users.php?action=list');
                const data = await response.json();

                if (data.success) {
                    admins = data.admins;
                    renderAdmins();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur lors du chargement', 'error');
            }
        }

        // Afficher les admins
        function renderAdmins() {
            const container = document.getElementById('admins-list');

            if (admins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>Aucun administrateur</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = admins.map(admin => `
                <div class="admin-card">
                    <div class="admin-header">
                        <div class="admin-avatar">${admin.username.charAt(0).toUpperCase()}</div>
                        <div class="admin-info">
                            <h3>${admin.username}</h3>
                            <span class="admin-role role-${admin.role}">${getRoleLabel(admin.role)}</span>
                        </div>
                    </div>
                    <div class="admin-details">
                        <div class="admin-detail">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Créé le ${formatDate(admin.created_at)}</span>
                        </div>
                        <div class="admin-detail">
                            <i class="fas fa-clock"></i>
                            <span>${admin.last_login ? 'Dernière connexion: ' + formatDate(admin.last_login) : 'Jamais connecté'}</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <button class="btn btn-secondary" onclick="openEditModal(${admin.id})">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button class="btn btn-danger" onclick="confirmDelete(${admin.id}, '${admin.username}')">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Créer un admin
        async function createAdmin(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const data = {
                action: 'create',
                username: formData.get('username'),
                password: formData.get('password'),
                role: formData.get('role')
            };

            try {
                const response = await fetch('api/admin-users.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Admin créé avec succès', 'success');
                    closeCreateModal();
                    loadAdmins();
                    event.target.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur lors de la création', 'error');
            }
        }

        // Modifier un admin
        async function updateAdmin(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const data = {
                action: 'update',
                admin_id: parseInt(formData.get('admin_id')),
                username: formData.get('username') || undefined,
                password: formData.get('password') || undefined,
                role: formData.get('role')
            };

            try {
                const response = await fetch('api/admin-users.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Admin mis à jour avec succès', 'success');
                    closeEditModal();
                    loadAdmins();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur lors de la mise à jour', 'error');
            }
        }

        // Supprimer un admin
        async function deleteAdmin(adminId) {
            try {
                const response = await fetch('api/admin-users.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'delete',
                        admin_id: adminId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Admin supprimé avec succès', 'success');
                    loadAdmins();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur lors de la suppression', 'error');
            }
        }

        // Confirmer suppression
        function confirmDelete(adminId, username) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'admin "${username}" ?\n\nCette action est irréversible.`)) {
                deleteAdmin(adminId);
            }
        }

        // Modals
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
            document.getElementById('createForm').reset();
        }

        function openEditModal(adminId) {
            const admin = admins.find(a => a.id === adminId);
            if (!admin) return;

            document.getElementById('edit-admin-id').value = admin.id;
            document.getElementById('edit-username').value = '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-role').value = admin.role;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.getElementById('editForm').reset();
        }

        // Helpers
        function getRoleLabel(role) {
            const labels = {
                'owner': 'Propriétaire',
                'manager': 'Gestionnaire',
                'staff': 'Employé'
            };
            return labels[role] || role;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            container.appendChild(alert);

            setTimeout(() => alert.remove(), 5000);
        }

        // Fermer modals avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
            }
        });

        // Charger au démarrage
        loadAdmins();
    </script>
</body>
</html>
