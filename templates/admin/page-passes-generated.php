<div id="debampass-generated-page" class="wrap">
	<?php include "elements/tabs-pass-viewer.php"; ?>
	
	<h1><?php _e("Passes generated", "debampass"); ?></h1>
	
	<form id="form-generated-passes" method="get">
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		
		<?php $generatedPassListTable->display(); ?>
	</form>
</div>