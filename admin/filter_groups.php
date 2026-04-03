<?php
require_once '../includes/db.php';
include __DIR__ . '/layout/header.php';

$stmt = $pdo->query("
    SELECT fg.*, c.name as category_name
    FROM filter_groups fg
    LEFT JOIN categories c ON fg.category_id = c.id
    ORDER BY fg.category_id ASC, fg.sort_order ASC, fg.name ASC
");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page wrapper (optional) -->
<div class="max-w-5xl mx-auto p-6">

  <!-- Page-specific styles -->
  <style>
    /* Page Title */
    .admin-title {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #1f2937;
    }

    /* Add button */
    .btn-add {
      display: inline-block;
      background: #6366f1;
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      margin-bottom: 18px;
    }
    .btn-add:hover {
      background: #4f46e5;
    }

    /* Table styling */
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 6px 16px rgba(0,0,0,0.05);
    }

    .admin-table th {
      background: #f3f4f6;
      color: #374151;
      padding: 12px;
      font-size: 13px;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: .06em;
    }

    .admin-table td {
      padding: 12px;
      font-size: 14px;
      border-top: 1px solid #e5e7eb;
      vertical-align: middle;
    }

    .admin-table tr:hover {
      background: #f9fafb;
    }

    /* Active status badge */
    .badge-active {
      background: #dcfce7;
      color: #166534;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .badge-inactive {
      background: #fee2e2;
      color: #991b1b;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    /* Action buttons */
    .action-btn {
      text-decoration: none;
      font-size: 13px;
      padding: 6px 10px;
      border-radius: 5px;
      margin-right: 6px;
      display: inline-block;
      white-space: nowrap;
    }

    .btn-edit {
      background: #e0f2fe;
      color: #0369a1;
    }

    .btn-manage {
      background: #fef3c7;
      color: #92400e;
    }

    .btn-delete {
      background: #fee2e2;
      color: #b91c1c;
      border: none;
      cursor: pointer;
    }

    .btn-edit:hover,
    .btn-manage:hover,
    .btn-delete:hover {
      filter: brightness(0.95);
    }

    /* Small responsive tweak */
    @media (max-width: 640px) {
      .admin-table th,
      .admin-table td {
        padding: 8px;
        font-size: 12px;
      }
      .admin-title {
        font-size: 18px;
      }
    }
  </style>

  <!-- Page title + Add button -->
  <h1 class="admin-title">Product Filter Groups</h1>
  <a href="filter_group_add.php" class="btn-add">+ Add Filter Group</a>

  <!-- Table -->
  <table class="admin-table">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Param Key</th>
      <th>Column</th>
      <th>Applies To</th>
      <th>Sort</th>
      <th>Status</th>
      <th>Options</th>
      <th>Actions</th>
    </tr>

    <?php if (!empty($groups)): ?>
      <?php foreach ($groups as $g): ?>
        <tr>
          <td><?php echo (int)$g['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($g['name']); ?></strong></td>
          <td><?php echo htmlspecialchars($g['param_key']); ?></td>
          <td>
              <?php if (!empty($g['category_name'])): ?>
                  <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs font-semibold"><?= htmlspecialchars($g['category_name']) ?></span>
              <?php else: ?>
                  <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">Common</span>
              <?php endif; ?>
          </td>
          <td><?php echo (int)$g['sort_order']; ?></td>
          <td>
            <?php if (!empty($g['is_active'])): ?>
              <span class="badge-active">Active</span>
            <?php else: ?>
              <span class="badge-inactive">Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <a
              class="action-btn btn-manage"
              href="filter_options.php?group_id=<?php echo (int)$g['id']; ?>"
            >
              Manage Options
            </a>
          </td>
          <td>
            <a
              class="action-btn btn-edit"
              href="filter_group_edit.php?id=<?php echo (int)$g['id']; ?>"
            >
              Edit
            </a>

            <!-- Delete form -->
            <form
              action="filter_group_delete.php"
              method="post"
              style="display:inline"
              onsubmit="return confirm('Delete this filter group and all its options?');"
            >
              <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
              <button type="submit" class="action-btn btn-delete">
                Delete
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="9" style="text-align:center; padding:20px; color:#6b7280; font-size:14px;">
          No filter groups found. Click “Add Filter Group” to create one.
        </td>
      </tr>
    <?php endif; ?>
  </table>

</div>