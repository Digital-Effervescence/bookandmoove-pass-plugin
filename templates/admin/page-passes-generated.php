<div id="debampass-generated-page" class="wrap">
	<?php include "elements/tabs-pass-viewer.php"; ?>
	
	<h1><?php _e("Passes generated", "debampass"); ?></h1>
	
	<form id="form-generated-passes" method="get">
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		
		<?php if (isset($_GET['orderby'])): ?>
			<input type="hidden" name="orderby" value="<?php echo $_GET['orderby']; ?>" />
		<?php endif; ?>
		
		<?php if (isset($_GET['order'])): ?>
			<input type="hidden" name="order" value="<?php echo $_GET['order']; ?>" />
		<?php endif; ?>
		
		<input type="hidden" name="paged" value="<?php echo isset($_GET['paged']) ? $_GET['paged'] : 1; ?>" />
		
		<?php $generatedPassListTable->display(); ?>
	</form>
</div>