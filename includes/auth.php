<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function require_role($role) {
    if (!is_logged_in() || $_SESSION['role'] !== $role) {
        header('Location: ../login.html');
        exit;
    }
}
?>