<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test Authentification Admin</h2>";

echo "<h3>1. Session PHP</h3>";
echo "Session status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";

echo "<h3>2. Variables session</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>3. Test isAdminLoggedIn()</h3>";
$isLoggedIn = isAdminLoggedIn();
echo $isLoggedIn ? "✅ Admin connecté" : "❌ Admin NON connecté";
echo "<br>";

echo "<h3>4. Token CSRF</h3>";
echo "Token: " . (getCsrfToken() ?? 'NONE') . "<br>";

echo "<h3>5. Fix temporaire</h3>";
echo "<p>Si vous n'êtes pas connecté, cliquez ici :</p>";
echo '<a href="login.php" style="padding:10px 20px;background:#10b981;color:white;text-decoration:none;border-radius:5px;">Se connecter</a>';
