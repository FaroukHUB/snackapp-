/**
 * Notification sonore et visuelle pour nouvelles commandes
 * - Popup "Commande reçue"
 * - Bip harmonieux + MP3 en boucle
 * - Option C : aucun son au clic d’activation
 */

class OrderNotificationSystem {
    constructor() {
        this.isPlaying = false;
        this.lastOrderId = null;
        this.checkInterval = null;

        // Son activé par défaut
        this.audioEnabled = true;

        // MP3 notification - use relative path for admin panel
        this.audioFile = new Audio('assets/sounds/commande.mp3');
        this.audioFile.loop = true;
        this.audioFile.volume = 0.9;

        // Preload the audio file
        this.audioFile.load();
    }

    /* =======================
       BIP HARMONIEUX (WebAudio)
       ======================= */
    generateBeep() {
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
    }

    /* =======================
       LECTURE / ARRÊT
       ======================= */
    playSound() {
        if (this.isPlaying || !this.audioEnabled) return;

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
            const res = await fetch('api/orders.php?action=list&limit=1');
            const data = await res.json();

            if (!data.success || !data.orders || !data.orders.length) return;

            const latest = data.orders[0];

            if (this.lastOrderId === null) {
                this.lastOrderId = latest.id;
                return;
            }

            if (latest.id !== this.lastOrderId) {
                this.lastOrderId = latest.id;
                this.onNewOrder(latest);
            }
        } catch (e) {
            console.error('Erreur check commandes', e);
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
                <p style="font-size:36px;color:#10b981;font-weight:bold;margin:20px 0;">${total.toLocaleString('fr-FR')} DA</p>
                <button onclick="orderNotificationSystem.acceptOrder()"
                    style="padding:15px 40px;border-radius:50px;background:#10b981;color:white;border:none;font-size:18px;cursor:pointer;">
                    ✅ ACCEPTER
                </button>
            </div>
        `;

        // Clic n'importe où sur le fond arrête le son
        modal.addEventListener('click', () => this.acceptOrder());

        document.body.appendChild(modal);
    }

    acceptOrder() {
        this.stopSound();
        document.getElementById('order-notification-modal')?.remove();
        window.location.reload();
    }

    /* =======================
       PERMISSION AUDIO - Activé automatiquement
       ======================= */
    requestAudioPermission() {
        return new Promise(resolve => {
            try {
                // Active le contexte audio automatiquement
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioCtx();
                ctx.resume();

                this.audioEnabled = true;
                resolve(true);
            } catch (e) {
                console.warn('Audio context non disponible:', e);
                this.audioEnabled = true; // Activé quand même
                resolve(true);
            }
        });
    }

    /* =======================
       DÉMARRAGE
       ======================= */
    async start(intervalSeconds = 10) {
        await this.requestAudioPermission();
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
