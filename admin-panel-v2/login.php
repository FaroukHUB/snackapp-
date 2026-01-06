<?php
/**
 * SnackApp v1 - Login
 * Support MySQL avec fallback mot de passe simple
 */

require_once __DIR__ . '/bootstrap.php';

// Si déjà connecté, rediriger
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

// Rate limiting: bloquer après 5 tentatives échouées
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset après 15 minutes d'inactivité
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

// Traitement du formulaire
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier rate limit
    if ($_SESSION['login_attempts'] >= 5) {
        $waitTime = 900 - (time() - $_SESSION['last_attempt_time']);
        if ($waitTime > 0) {
            $error = 'Trop de tentatives. Réessayez dans ' . ceil($waitTime/60) . ' minute(s).';
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    if (!$error) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($useMySQL) {
            // Mode MySQL: authentification par username/password
            if (empty($username)) {
                $error = 'Nom d\'utilisateur requis';
            } else {
                $admin = RestaurantRepository::verifyAdmin(SNACK_RESTAURANT_ID, $username, $password);

                if ($admin) {
                    // ✅ Régénérer session ID (protection session fixation)
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['restaurant_id'] = SNACK_RESTAURANT_ID;
                    // Reset tentatives après succès
                    $_SESSION['login_attempts'] = 0;
                    // 🔒 SÉCURITÉ: Régénérer token CSRF après connexion réussie
                    regenerateCsrfToken();
                    header('Location: index.php');
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    $error = 'Identifiants incorrects';
                }
            }
        } else {
            // Mode JSON: authentification par mot de passe haché
            require_once __DIR__ . '/config.php';

            // 🔒 SÉCURITÉ: Vérification par hash Argon2id uniquement
            $passwordValid = false;
            if (defined('ADMIN_PASSWORD_HASH')) {
                $passwordValid = password_verify($password, ADMIN_PASSWORD_HASH);
            } else {
                // Configuration incorrecte - refuser la connexion
                error_log('[SÉCURITÉ] ADMIN_PASSWORD_HASH non défini - connexion refusée');
                $error = 'Configuration incorrecte du système';
                $passwordValid = false;
            }

            if ($passwordValid) {
                // ✅ Régénérer session ID (protection session fixation)
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                // Reset tentatives après succès
                $_SESSION['login_attempts'] = 0;
                // 🔒 SÉCURITÉ: Régénérer token CSRF après connexion réussie
                regenerateCsrfToken();
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $error = 'Mot de passe incorrect';
            }
        }
    }
}

// Charger les infos du restaurant
if ($useMySQL) {
    $restaurant = getCurrentRestaurant();
    $restaurantName = $restaurant['name'] ?? 'Restaurant';
    $primaryColor = $restaurant['primary_color'] ?? '#c58a3a';
} else {
    require_once __DIR__ . '/config.php';
    $config = loadConfig();
    $primaryColor = $config['branding']['primaryColor'] ?? $config['theme']['colors']['brand'] ?? '#c58a3a';
    $restaurantName = $config['restaurant']['name'] ?? $config['name'] ?? 'Restaurant';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Admin <?php echo htmlspecialchars($restaurantName); ?></title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            margin-bottom: 16px;
        }

        .logo-icon i {
            font-size: 32px;
            color: white;
        }

        .logo-container h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .logo-container p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .error-box {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fca5a5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .hint {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        .mode-badge {
            text-align: center;
            margin-top: 16px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <h1><?php echo htmlspecialchars($restaurantName); ?></h1>
            <p>Panneau d'administration</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($useMySQL): ?>
            <!-- Mode MySQL: username + password -->
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nom d'utilisateur</label>
                <input
                    type="text"
                    name="username"
                    required
                    placeholder="admin"
                    autocomplete="username"
                    autofocus
                >
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Mot de passe</label>
                <input
                    type="password"
                    name="password"
                    required
                    placeholder="Entrez votre mot de passe"
                    autocomplete="current-password"
                    <?php if (!$useMySQL): ?>autofocus<?php endif; ?>
                >
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

        <div class="hint">
            <p>Connectez-vous avec vos identifiants</p>
        </div>

        <div class="mode-badge">
            <i class="fas fa-database"></i>
            Mode: <?php echo $useMySQL ? 'MySQL' : 'JSON (fallback)'; ?>
        </div>
    </div>
</body>
</html>
