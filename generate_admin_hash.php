<?php
// Generate proper password hash for admin account
// Run this file once to get the correct hash

$password = 'admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password Hash for 'admin@123':\n";
echo $hash . "\n\n";

echo "Copy this hash and use it in the SQL INSERT statement:\n\n";

echo "INSERT INTO users (name, email, password, role, is_verified, created_at) \n";
echo "VALUES (\n";
echo "  'Admin',\n";
echo "  'admin@develixirs.com',\n";
echo "  '$hash',\n";
echo "  'admin',\n";
echo "  1,\n";
echo "  NOW()\n";
echo ");\n";
?>
