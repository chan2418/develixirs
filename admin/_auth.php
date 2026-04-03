<?php
// admin/_auth.php
session_start();
date_default_timezone_set('Asia/Kolkata');
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    // redirect to login (with return url optional)
    header('Location: login.php');
    exit;
}