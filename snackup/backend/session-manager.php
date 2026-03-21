<?php
/**
 * Demo Session Manager
 * Handles multiple concurrent demo sessions for testing
 * Each session gets isolated temporary data
 */

class DemoSessionManager {
    private $sessionsDir;
    private $sessionDuration = 86400; // 24 hours

    public function __construct() {
        $this->sessionsDir = __DIR__ . '/../../database/demo-sessions/';
        $this->ensureSessionsDirectory();
        $this->cleanupExpiredSessions();
    }

    /**
     * Ensure sessions directory exists
     */
    private function ensureSessionsDirectory() {
        if (!file_exists($this->sessionsDir)) {
            mkdir($this->sessionsDir, 0755, true);
        }
    }

    /**
     * Validate session ID format
     */
    public function isValidSessionId($sessionId) {
        // Format: demo_timestamp_randomstring
        return preg_match('/^demo_\d+_[a-z0-9]+$/', $sessionId);
    }

    /**
     * Create new demo session
     */
    public function createSession($sessionId, $config = []) {
        if (!$this->isValidSessionId($sessionId)) {
            return false;
        }

        $sessionFile = $this->getSessionFile($sessionId);

        $sessionData = [
            'session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->sessionDuration),
            'config' => $config,
            'cuisines' => [],
            'restaurant_data' => $this->getDefaultRestaurantData(),
            'last_activity' => date('Y-m-d H:i:s')
        ];

        return file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Get session data
     */
    public function getSession($sessionId) {
        if (!$this->isValidSessionId($sessionId)) {
            return null;
        }

        $sessionFile = $this->getSessionFile($sessionId);

        if (!file_exists($sessionFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($sessionFile), true);

        // Check if expired
        if (strtotime($data['expires_at']) < time()) {
            $this->deleteSession($sessionId);
            return null;
        }

        // Update last activity
        $data['last_activity'] = date('Y-m-d H:i:s');
        file_put_contents($sessionFile, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

    /**
     * Update session data
     */
    public function updateSession($sessionId, $updates) {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return false;
        }

        $session = array_merge($session, $updates);
        $session['last_activity'] = date('Y-m-d H:i:s');

        $sessionFile = $this->getSessionFile($sessionId);
        return file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Save cuisine selections for session
     */
    public function saveCuisineSelection($sessionId, $cuisines) {
        return $this->updateSession($sessionId, ['cuisines' => $cuisines]);
    }

    /**
     * Delete session
     */
    public function deleteSession($sessionId) {
        $sessionFile = $this->getSessionFile($sessionId);

        if (file_exists($sessionFile)) {
            return unlink($sessionFile);
        }

        return true;
    }

    /**
     * Cleanup expired sessions
     */
    private function cleanupExpiredSessions() {
        $files = glob($this->sessionsDir . 'demo_*.json');
        $now = time();

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (isset($data['expires_at']) && strtotime($data['expires_at']) < $now) {
                unlink($file);
            }
        }
    }

    /**
     * Get session file path
     */
    private function getSessionFile($sessionId) {
        return $this->sessionsDir . $sessionId . '.json';
    }

    /**
     * Get default restaurant data for demo
     */
    private function getDefaultRestaurantData() {
        return [
            'name' => 'Restaurant Démo',
            'slug' => 'restaurant-demo',
            'tagline' => 'Votre restaurant de test',
            'is_demo' => true,
            'theme' => [
                'primary' => '#667eea',
                'secondary' => '#764ba2',
                'accent' => '#ff6fae'
            ],
            'contact' => [
                'phone' => '+33 1 23 45 67 89',
                'email' => 'demo@snackup.com'
            ],
            'location' => [
                'address' => '123 Rue de la Démo',
                'city' => 'Paris',
                'postalCode' => '75001'
            ]
        ];
    }

    /**
     * Get active sessions count
     */
    public function getActiveSessionsCount() {
        $files = glob($this->sessionsDir . 'demo_*.json');
        $count = 0;
        $now = time();

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (isset($data['expires_at']) && strtotime($data['expires_at']) >= $now) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if session exists
     */
    public function sessionExists($sessionId) {
        return $this->getSession($sessionId) !== null;
    }
}

// Helper function to get session manager instance
function getDemoSessionManager() {
    static $instance = null;

    if ($instance === null) {
        $instance = new DemoSessionManager();
    }

    return $instance;
}

// Check if current request is a demo session
function isDemoSession() {
    return isset($_GET['demo']) && isset($_GET['session']);
}

// Get current demo session ID
function getCurrentDemoSessionId() {
    if (isDemoSession()) {
        return $_GET['session'] ?? null;
    }
    return null;
}
