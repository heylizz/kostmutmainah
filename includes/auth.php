<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isPemilik() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'pemilik';
}

function isPenghuni() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'penghuni';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /kost_mutmainah/index.php?login=1');
        exit;
    }
}

function requirePemilik() {
    requireLogin();
    if (!isPemilik()) {
        header('Location: /kost_mutmainah/penghuni/dashboard.php');
        exit;
    }
}

function requirePenghuni() {
    requireLogin();
    if (!isPenghuni()) {
        header('Location: /kost_mutmainah/pemilik/dashboard.php');
        exit;
    }
}
?>
