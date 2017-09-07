<div class="admin-notices-container">
	<?php foreach ($adminNotices as $anAdminNotice): ?>
		<div class="notice notice-<?php echo $anAdminNotice['type']; ?> <?php echo $anAdminNotice['isDismissible'] ? "is-dismissible" : ""; ?>">
			<p><?php echo $anAdminNotice['message']; ?></p>
		</div>
	<?php endforeach; ?>
</div>