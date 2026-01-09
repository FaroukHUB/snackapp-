#!/bin/bash
# Fix activation audio notifications

cd ~/Marvelous.mon-agenceweb.fr

echo "Correction du système de notifications audio..."

# Backup
cp admin-panel-v2/notification-sound.js admin-panel-v2/notification-sound.js.backup.$(date +%s)

# Fix: Ajouter un bouton visible pour activer l'audio
cat > /tmp/fix-audio.patch << 'PATCH'
--- a/admin-panel-v2/index.php
+++ b/admin-panel-v2/index.php
@@ -2549,6 +2549,29 @@
     <script src="notification-sound.js"></script>
     <script>
+        // Afficher bouton activation audio si nécessaire
+        function showAudioActivationPrompt() {
+            const existing = document.getElementById('audio-activation-prompt');
+            if (existing) return;
+
+            const prompt = document.createElement('div');
+            prompt.id = 'audio-activation-prompt';
+            prompt.style.cssText = 'position:fixed;top:20px;right:20px;background:#ff6f61;color:#fff;padding:15px 20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:99999;cursor:pointer;font-weight:600;animation:pulse 2s infinite;';
+            prompt.innerHTML = '🔔 Cliquez pour activer les notifications sonores';
+
+            prompt.onclick = async () => {
+                try {
+                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
+                    const ctx = new AudioCtx();
+                    await ctx.resume();
+                    window.orderNotificationSystem.audioEnabled = true;
+                    prompt.remove();
+                    console.log('[Audio] ✅ Activé manuellement');
+                } catch(e) { console.error(e); }
+            };
+
+            document.body.appendChild(prompt);
+        }
+
         // PIN Protection - demande le PIN à chaque accès
         const PROTECTED_SECTIONS = ['stats', 'archives'];
         let pendingSection = null;
@@ -2869,6 +2892,9 @@

         // Notifications (polling toutes les 10 secondes pour être réactif)
         if (window.orderNotificationSystem) orderNotificationSystem.start(10);
+
+        // Afficher prompt activation audio après 2 secondes
+        setTimeout(showAudioActivationPrompt, 2000);

 // === GESTION CLIENTS ===
PATCH

echo "Appliquer le patch manuellement..."
echo ""
echo "IMPORTANT: Le fichier DIAGNOSTIC-NOTIFICATIONS.sh n'existe pas sur votre serveur"
echo "car il est dans mon environnement local."
echo ""
echo "À la place, SOLUTION SIMPLE:"
echo ""
echo "Dans votre navigateur admin, APRÈS vous être connecté:"
echo "1. Cliquez N'IMPORTE OÙ dans la page (un simple clic)"
echo "2. Regardez la console - le warning devrait disparaître"
echo "3. Créez une commande test depuis le site"
echo "4. La popup + son devraient s'afficher"
echo ""
echo "Si ça ne marche toujours pas, ajoutez ce code dans admin-panel-v2/index.php"
echo "juste après la ligne 2871 (orderNotificationSystem.start(10)):"
echo ""
cat << 'JSFIX'

// Forcer activation audio au premier clic
document.addEventListener('click', function activateAudio() {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (AudioCtx) {
        const ctx = new AudioCtx();
        ctx.resume().then(() => {
            console.log('[Audio] ✅ Activé par clic utilisateur');
            window.orderNotificationSystem.audioEnabled = true;
        });
    }
    document.removeEventListener('click', activateAudio);
}, { once: true });

JSFIX

echo ""
echo "Voulez-vous que je vous guide pour ajouter ce code?"
