<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$is_active = !empty($_POST['is_active']) ? 1 : 0;

if ($name === '') {
    $_SESSION['tag_error'] = 'Tag name is required.';
    header('Location: add_tag.php');
    exit;
}

// simple slug if empty
if ($slug === '') {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');
}

$stmt = $pdo->prepare("
    INSERT INTO tags (name, slug, is_active)
    VALUES (:name, :slug, :is_active)
");
$stmt->execute([
    ':name'      => $name,
    ':slug'      => $slug,
    ':is_active' => $is_active,
]);

$_SESSION['tag_success'] = 'Tag created successfully.';
header('Location: tags.php');
exit;