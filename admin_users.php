<?php
require_once "includes/db.php";

$stmt = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Registered Users</title>
<style>
body{font-family:Poppins, Arial;background:#f5f5f5;padding:30px;}
table{
  width:100%;
  border-collapse:collapse;
  background:#fff;
  box-shadow:0 5px 20px rgba(0,0,0,0.08);
}
th,td{
  padding:12px;
  border-bottom:1px solid #eee;
  text-align:left;
}
th{background:#A41B42;color:#fff;}
tr:hover{background:#fafafa;}
.status-active{color:green;font-weight:bold;}
.status-inactive{color:red;font-weight:bold;}
</style>
</head>
<body>

<h2>Registered Users</h2>

<table>
<tr>
  <th>ID</th>
  <th>Name</th>
  <th>Email</th>
  <th>Role</th>
  <th>Status</th>
  <th>Created</th>
</tr>

<?php foreach($users as $user): ?>
<tr>
  <td><?= $user['id']; ?></td>
  <td><?= htmlspecialchars($user['name']); ?></td>
  <td><?= htmlspecialchars($user['email']); ?></td>
  <td><?= $user['role']; ?></td>
  <td>
    <span class="<?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
      <?= $user['is_active'] ? 'Active' : 'Inactive'; ?>
    </span>
  </td>
  <td><?= $user['created_at']; ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
