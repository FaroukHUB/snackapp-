/**
 * Notification sonore et visuelle pour nouvelles commandes
 * Version 2 - Activation automatique UNE SEULE FOIS
 */

class OrderNotificationSystem {
    constructor() {
        this.isPlaying = false;
        this.lastOrderId = null;
        this.lastOrderCount = 0; // 🔧 Tracker le nombre de commandes
        this.checkInterval = null;
        this.audioEnabled = false;
        this.activationShown = localStorage.getItem('audio_activated') === 'true';

        // MP3 notification
        this.audioFile = new Audio('assets/sounds/ateliersounds.mp3');
        this.audioFile.loop = true;
        this.audioFile.volume = 0.9;
        this.audioFile.load();
    }

    /* =======================
       BIP HARMONIEUX (WebAudio)
       ======================= */
    generateBeep() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioCtx();

            const now = ctx.currentTime;
            const gain = ctx.createGain();
            gain.connect(ctx.destination);

            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.linearRampToValueAtTime(0.9, now + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);

            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();

            osc1.type = 'triangle';
            osc2.type = 'triangle';

            osc1.frequency.value = 659.25; // E5
            osc2.frequency.value = 880.0;  // A5

            osc1.connect(gain);
            osc2.connect(gain);

            osc1.start(now);
            osc2.start(now);
            osc1.stop(now + 0.45);
            osc2.stop(now + 0.45);
        } catch(e) {
            console.warn('[Audio] Beep non disponible:', e);
        }
    }

    /* =======================
       LECTURE / ARRÊT
       ======================= */
    async playSound() {
        if (this.isPlaying) return;

        // Activer l'audio automatiquement si pas encore fait
        if (!this.audioEnabled) {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioCtx();
                await ctx.resume();
                this.audioEnabled = true;
                console.log('[Audio] ✅ Activé automatiquement lors nouvelle commande');
            } catch (e) {
                console.warn('[Audio] Activation auto échouée:', e);
            }
        }

        this.isPlaying = true;

        try {
            this.audioFile.currentTime = 0;
            this.audioFile.play().catch(() => {});
        } catch (_) {}

        const loop = () => {
            if (!this.isPlaying) return;
            this.generateBeep();
            setTimeout(loop, 1500);
        };

        loop();
    }

    stopSound() {
        this.isPlaying = false;
        try {
            this.audioFile.pause();
            this.audioFile.currentTime = 0;
        } catch (_) {}
    }

    /* =======================
       DÉTECTION COMMANDES
       ======================= */
    async checkNewOrders() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            const res = await fetch('api/orders.php?action=list&limit=1', {
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!res.ok) return;

            const data = await res.json();

            if (!data.success || !data.orders || !data.orders.length) {
                // Aucune commande active
                this.lastOrderCount = 0;
                this.lastOrderId = null;
                return;
            }

            const latest = data.orders[0];
            const currentCount = data.pagination?.total || data.orders.length;

            // 🔧 PREMIÈRE VISITE: Initialiser sans notifier
            if (this.lastOrderId === null) {
                this.lastOrderId = latest.id;
                this.lastOrderCount = currentCount;
                console.log('[Notifications] Initialisé: ' + currentCount + ' commandes');
                return;
            }

            // 🔧 DÉTECTION: Nouvelle commande UNIQUEMENT si le count augmente ET l'ID est différent
            if (currentCount > this.lastOrderCount && latest.id !== this.lastOrderId) {
                console.log('[Notifications] ✅ Nouvelle commande détectée: ' + latest.id);
                this.lastOrderId = latest.id;
                this.lastOrderCount = currentCount;
                this.onNewOrder(latest);
            } else if (currentCount !== this.lastOrderCount) {
                // Le count a changé (archive/suppression) mais pas de nouvelle commande
                console.log('[Notifications] Count changé: ' + this.lastOrderCount + ' → ' + currentCount + ' (pas de notification)');
                this.lastOrderId = latest.id;
                this.lastOrderCount = currentCount;
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.warn('[Notifications] Erreur silencieuse:', e.message);
            }
        }
    }

    onNewOrder(order) {
        this.showNotification(order);
        this.playSound();
    }

    /* =======================
       POPUP COMMANDE
       ======================= */
    showNotification(order) {
        const modal = document.createElement('div');
        modal.id = 'order-notification-modal';
        modal.style.cssText = `
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.9);
            z-index:9999;
            display:flex;
            align-items:center;
            justify-content:center;
        `;

        const customerName = order.customer_name || 'Nouveau client';
        const total = parseFloat(order.total) || 0;

        modal.innerHTML = `
            <div style="background:#2a2a3e;padding:40px;border-radius:20px;text-align:center;max-width:400px;" onclick="event.stopPropagation()">
                <div style="font-size:80px">🔔</div>
                <h2 style="color:#fff;margin:15px 0;">NOUVELLE COMMANDE</h2>
                <p style="font-size:24px;color:#fff;font-weight:bold;margin:10px 0;">${customerName}</p>
                <p style="color:#9ca3af;font-size:12px;margin:5px 0;">#${order.id}</p>
                <p style="font-size:36px;color:#10b981;font-weight:bold;margin:20px 0;">${total.toLocaleString('fr-FR')} ${window.CURRENCY || 'EUR'}</p>
                <button onclick="orderNotificationSystem.acceptOrder()"
                    style="padding:15px 40px;border-radius:50px;background:#10b981;color:white;border:none;font-size:18px;cursor:pointer;">
                    ✅ ACCEPTER
                </button>
            </div>
        `;

        modal.addEventListener('click', () => this.acceptOrder());

        document.body.appendChild(modal);
    }

    acceptOrder() {
        this.stopSound();
        document.getElementById('order-notification-modal')?.remove();
        window.location.reload();
    }

    /* =======================
       BANNER ACTIVATION AUDIO - AFFICHAGE UNE SEULE FOIS
       ======================= */
    showActivationBanner() {
        // Si déjà activé, ne rien afficher
        if (this.activationShown) {
            this.tryActivateAudio();
            return;
        }

        const banner = document.createElement('div');
        banner.id = 'audio-activation-banner';
        banner.style.cssText = `
            position:fixed;
            top:20px;
            left:50%;
            transform:translateX(-50%);
            background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color:#fff;
            padding:20px 30px;
            border-radius:16px;
            box-shadow:0 8px 24px rgba(102,126,234,0.4);
            z-index:99999;
            display:flex;
            align-items:center;
            gap:20px;
            font-weight:600;
            font-size:16px;
            animation:slideDown 0.5s ease;
        `;

        banner.innerHTML = `
            <div style="font-size:32px;">🔔</div>
            <div style="flex:1;">
                <div style="font-size:18px;margin-bottom:5px;">Activer les notifications sonores</div>
                <div style="font-size:13px;opacity:0.9;">Pour être alerté des nouvelles commandes</div>
            </div>
            <button id="activate-audio-btn" style="padding:12px 24px;background:#fff;color:#667eea;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;">
                ✓ Activer
            </button>
        `;

        // Ajouter animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from { transform:translateX(-50%) translateY(-100px); opacity:0; }
                to { transform:translateX(-50%) translateY(0); opacity:1; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(banner);

        // Clic sur le bouton
        document.getElementById('activate-audio-btn').onclick = async () => {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioCtx();
                await ctx.resume();

                // Preload audio
                this.audioFile.play().then(() => {
                    this.audioFile.pause();
                    this.audioFile.currentTime = 0;
                }).catch(() => {});

                this.audioEnabled = true;
                localStorage.setItem('audio_activated', 'true');
                this.activationShown = true;

                // Feedback visuel
                banner.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                banner.innerHTML = `
                    <div style="font-size:32px;">✅</div>
                    <div style="font-size:18px;">Notifications activées !</div>
                `;

                setTimeout(() => banner.remove(), 2000);

                console.log('[Audio] ✅ Activé et mémorisé');
            } catch (e) {
                console.error('[Audio] Erreur activation:', e);
            }
        };
    }

    /* =======================
       ACTIVATION AUTO SI DÉJÀ MÉMORISÉ
       ======================= */
    async tryActivateAudio() {
        if (!this.activationShown) return;

        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioCtx();
            await ctx.resume();

            this.audioFile.play().then(() => {
                this.audioFile.pause();
                this.audioFile.currentTime = 0;
            }).catch(() => {});

            this.audioEnabled = true;
            console.log('[Audio] ✅ Réactivé automatiquement');
        } catch (e) {
            console.warn('[Audio] Auto-activation échouée, attente interaction:', e);
            // Fallback: attendre premier clic
            document.addEventListener('click', () => this.tryActivateAudio(), { once: true });
        }
    }

    /* =======================
       DÉMARRAGE
       ======================= */
    async start(intervalSeconds = 10) {
        // Afficher banner si première fois, sinon activer auto
        this.showActivationBanner();

        // Démarrer le polling
        this.checkNewOrders();
        this.checkInterval = setInterval(
            () => this.checkNewOrders(),
            intervalSeconds * 1000
        );
    }

    stop() {
        clearInterval(this.checkInterval);
        this.stopSound();
    }
}

/* =======================
   INSTANCE GLOBALE
   ======================= */
window.orderNotificationSystem = new OrderNotificationSystem();
