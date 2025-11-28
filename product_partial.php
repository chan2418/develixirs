<?php if (empty($products)): ?>
  <p style="font-size:14px;color:#777;">No products found with these filters.</p>
<?php else: ?>
  <div class="products-grid">
    <?php foreach ($products as $p): ?>
      <!-- product card HTML here exactly as before -->
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (isset($totalPages) && $totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="#" class="page-item" data-page="<?php echo $p; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>