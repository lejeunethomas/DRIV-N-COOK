<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function get_user_name() {
    return isset($_SESSION['nom']) ? $_SESSION['nom'] : null;
}

function require_role($role) {
    if (!is_logged_in()) {
        header('Location: ../login.html');
        exit;
    }
    
    if ($_SESSION['role'] !== $role) {
        header('Location: ../login.html');
        exit;
    }
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ../login.html');
        exit;
    }
}

function require_franchise_validated() {
    require_login();
    
    if ($_SESSION['role'] !== 'franchise') {
        header('Location: ../login.html');
        exit;
    }
    
    // Vérifier le statut seulement si défini
    if (isset($_SESSION['statut'])) {
        switch($_SESSION['statut']) {
            case 'en_attente':
                // Éviter la redirection si on est déjà sur la page d'attente
                if (!strpos($_SERVER['REQUEST_URI'], 'attente.html')) {
                    header('Location: attente.html');
                    exit;
                }
                break;
            case 'refuse':
                header('Location: ../login.html?error=compte_refuse');
                exit;
                break;
            // Si 'valide' ou autre, continuer normalement
        }
    }
}
?>