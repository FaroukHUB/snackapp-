/**
 * SnackApp Security Utilities
 * Fonctions de sécurité pour prévenir XSS et autres vulnérabilités
 */

/**
 * Échappe les caractères HTML pour prévenir XSS
 * @param {string} str - Chaîne à échapper
 * @returns {string} - Chaîne échappée
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

/**
 * Valide et sanitize une URL d'image
 * @param {string} url - URL à valider
 * @param {string} fallback - Image par défaut si invalide
 * @returns {string} - URL sécurisée ou fallback
 */
function sanitizeImageUrl(url, fallback = 'images/placeholder.png') {
    if (!url) return fallback;

    try {
        // Créer un objet URL pour validation
        const urlObj = new URL(url, window.location.origin);

        // Seulement http: et https: autorisés (pas javascript:, data:, etc.)
        if (!['http:', 'https:'].includes(urlObj.protocol)) {
            console.warn('[SECURITY] Protocole URL non autorisé:', urlObj.protocol);
            return fallback;
        }

        // Vérifier l'extension de fichier
        const validExtensions = /\.(jpg|jpeg|png|webp|gif|svg)$/i;
        if (!validExtensions.test(urlObj.pathname)) {
            console.warn('[SECURITY] Extension image invalide:', urlObj.pathname);
            return fallback;
        }

        return url;
    } catch (e) {
        console.error('[SECURITY] URL invalide:', url, e);
        return fallback;
    }
}

/**
 * Crée un attribut data-* sécurisé au lieu de onclick
 * @param {string} action - Nom de l'action
 * @param {string|number} id - ID de l'élément
 * @returns {string} - Attribut data-* sécurisé
 */
function createDataAction(action, id) {
    return `data-action="${escapeHtml(action)}" data-id="${escapeHtml(id)}"`;
}

/**
 * Sanitize du contenu JSON avant insertion dans HTML
 * @param {any} data - Données à sanitizer
 * @returns {string} - JSON échappé pour HTML
 */
function sanitizeJsonForHtml(data) {
    return escapeHtml(JSON.stringify(data));
}

// Export global pour utilisation dans tous les fichiers
window.securityUtils = {
    escapeHtml,
    sanitizeImageUrl,
    createDataAction,
    sanitizeJsonForHtml
};
