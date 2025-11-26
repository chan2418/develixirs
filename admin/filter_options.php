<?php
// admin/filter_options.php
require_once '../includes/db.php';
include __DIR__ . '/layout/header.php';

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($groupId <= 0) {
    echo '<div class="max-w-4xl mx-auto px-6 py-8">
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-md">
              Missing or invalid filter group.
            </div>
          </div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// fetch group info
$stmt = $pdo->prepare("
    SELECT *
    FROM filter_groups
    WHERE id = :id
");
$stmt->execute(['id' => $groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    echo '<div class="max-w-4xl mx-auto px-6 py-8">
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-md">
              Filter group not found.
            </div>
          </div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// fetch options for this group
$stmtOpt = $pdo->prepare("
    SELECT *
    FROM filter_options
    WHERE group_id = :gid
    ORDER BY sort_order ASC, label ASC
");
$stmtOpt->execute(['gid' => $groupId]);
$options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-5xl mx-auto px-6 py-8">
  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">
        Filter Options – <?php echo htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8'); ?>
      </h1>
      <p class="text-sm text-slate-500 mt-1">
        Manage the selectable values for this filter group.
      </p>
      <p class="text-xs text-slate-400 mt-1">
        Param key: <code class="bg-slate-100 px-1 rounded"><?php echo htmlspecialchars($group['param_key']); ?></code> ·
        Column: <code class="bg-slate-100 px-1 rounded"><?php echo htmlspecialchars($group['column_name']); ?></code>
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a
        href="filter_groups.php"
        class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50"
      >
        ← Back to Groups
      </a>
      <a
        href="filter_option_add.php?group_id=<?php echo (int)$groupId; ?>"
        class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm"
      >
        + Add Option
      </a>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($options)): ?>
      <div class="p-6 text-sm text-slate-500">
        No options added yet. Click <strong>“+ Add Option”</strong> to create the first one.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">ID</th>
              <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Label</th>
              <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Value</th>
              <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Sort</th>
              <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Active</th>
              <th class="text-right px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($options as $opt): ?>
              <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-2 text-slate-600">
                  <?php echo (int)$opt['id']; ?>
                </td>
                <td class="px-4 py-2 text-slate-800">
                  <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="px-4 py-2 text-slate-600">
                  <code class="bg-slate-100 px-1 rounded">
                    <?php echo htmlspecialchars($opt['value'], ENT_QUOTES, 'UTF-8'); ?>
                  </code>
                </td>
                <td class="px-4 py-2 text-slate-600">
                  <?php echo (int)$opt['sort_order']; ?>
                </td>
                <td class="px-4 py-2">
                  <?php if (!empty($opt['is_active'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">
                      Active
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-500">
                      Inactive
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-right space-x-3">
                    <a
                        href="filter_option_edit.php?id=<?php echo (int)$opt['id']; ?>&group_id=<?php echo (int)$groupId; ?>"
                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                    >
                        Edit
                    </a>

                    <form
                        action="filter_option_delete.php"
                        method="post"
                        style="display:inline"
                        onsubmit="return confirm('Are you sure you want to delete this option?');"
                    >
                        <input type="hidden" name="id" value="<?php echo (int)$opt['id']; ?>">
                        <input type="hidden" name="group_id" value="<?php echo (int)$groupId; ?>">

                        <button
                        type="submit"
                        class="text-red-600 hover:text-red-800 text-xs font-medium"
                        >
                        Delete
                        </button>
                    </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Helper block -->
  <div class="mt-6 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs text-slate-600">
    <div class="font-semibold mb-1">How this is used on frontend:</div>
    <ul class="list-disc pl-5 space-y-1">
      <li>Filter group: <strong><?php echo htmlspecialchars($group['name']); ?></strong></li>
      <li>Each option becomes a checkbox with <code>name="<?php echo htmlspecialchars($group['param_key']); ?>[]”</code></li>
      <li>Value is sent in URL like: <code>?<?php echo htmlspecialchars($group['param_key']); ?>[]=VALUE</code></li>
      <li>In product listing query, these values filter the column <code><?php echo htmlspecialchars($group['column_name']); ?></code></li>
    </ul>
  </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>