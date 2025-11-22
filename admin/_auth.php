<?php
// admin/_auth.php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    // redirect to login (with return url optional)
    header('Location: login.php');
    exit;
}