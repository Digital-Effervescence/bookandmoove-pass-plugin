<div id="debampass-generated-page" class="wrap">
	<h1><?php _e("Passes generated", "debampass"); ?></h1>
	
	<?php include "elements/admin-notices.php"; ?>
	
	<form id="form-generated-passes" method="get">
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		
		<?php if (isset($_GET['orderby'])): ?>
			<input type="hidden" name="orderby" value="<?php echo $_GET['orderby']; ?>" />
		<?php endif; ?>
		
		<?php if (isset($_GET['order'])): ?>
			<input type="hidden" name="order" value="<?php echo $_GET['order']; ?>" />
		<?php endif; ?>
		
		<input type="hidden" name="paged" value="<?php echo isset($_GET['paged']) ? $_GET['paged'] : 1; ?>" />
		
		<div class="search-box">
			<!-- État du pass -->
			<fieldset class="search-field-container">
				<legend><?php _e("Passes status", "debampass"); ?></legend>
				
				<div class="radio-container">
					<input type="radio" name="search-pass-status" id="search-pass-status-0" required="required" value="-1" <?php echo ((isset($_GET['search-pass-status']) && trim($_GET['search-pass-status']) == -1) || (!isset($_GET['search-pass-status']) || trim($_GET['search-pass-status']) == "")) ? 'checked="checked"' : ''; ?> />
					<label for="search-pass-status-0"><?php _e("Not specified", "debampass"); ?></label>
					
					<input type="radio" name="search-pass-status" id="search-pass-status-1" required="required" value="1" <?php echo (isset($_GET['search-pass-status']) && trim($_GET['search-pass-status']) == 1) ? 'checked="checked"' : ''; ?> />
					<label for="search-pass-status-1"><?php _e("Passes activated", "debampass"); ?></label>
					
					<input type="radio" name="search-pass-status" id="search-pass-status-2" required="required" value="0" <?php echo (isset($_GET['search-pass-status']) && trim($_GET['search-pass-status']) == 0) ? 'checked="checked"' : ''; ?> />
					<label for="search-pass-status-2"><?php _e("Passes not activated", "debampass"); ?></label>
				</div>
			</fieldset>
			
			<!-- Membership Plan -->
			<span class="search-field-container">
				<label for="search-plan-type"><?php _e("Plan type", "debampass"); ?></label>
				<select name="search-plan-type" id="search-plan-type" class="short">
					<option value="" <?php echo (!isset($_GET['search-plan-type']) || trim($_GET['search-plan-type']) == "") ? 'selected="selected"' : ''; ?>><?php _e("Select", "debampass"); ?></option>
					<?php foreach ($membershipPlans as $aMembershipPlan): ?>
						<option value="<?php echo $aMembershipPlan->membership_plan; ?>" <?php echo (isset($_GET['search-plan-type']) && trim($_GET['search-plan-type']) == $aMembershipPlan->membership_plan) ? 'selected="selected"' : ''; ?>><?php echo $aMembershipPlan->post_title; ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			
			<!-- Date expiration -->
			<fieldset class="search-field-container">
				<legend><?php _e("Expiration date", "debampass"); ?></legend>
				
				<!-- Début -->
				<span class="search-field-container-sub">
					<label for="search-expiration-date-start"><?php _e("Start", "debampass"); ?></label>
					<input type="text" name="search-expiration-date-start" id="search-expiration-date-start" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-expiration-date-start']) ? $_GET['search-expiration-date-start'] : ''; ?>" />
				</span>
				
				<!-- Fin -->
				<span class="search-field-container-sub">
					<label for="search-expiration-date-end"><?php _e("End", "debampass"); ?></label>
					<input type="text" name="search-expiration-date-end" id="search-expiration-date-end" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-expiration-date-end']) ? $_GET['search-expiration-date-end'] : ''; ?>" />
				</span>
			</fieldset>
			
			<!-- Date activation (début) -->
			<fieldset class="search-field-container">
				<legend><?php _e("Activation date", "debampass"); ?></legend>
				
				<!-- Début -->
				<span class="search-field-container-sub">
					<label for="search-updated-at-start"><?php _e("Start", "debampass"); ?></label>
					<input type="text" name="search-updated-at-start" id="search-updated-at-start" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-updated-at-start']) ? $_GET['search-updated-at-start'] : ''; ?>" />
				</span>
				
				<!-- Fin -->
				<span class="search-field-container-sub">
					<label for="search-updated-at-end"><?php _e("End", "debampass"); ?></label>
					<input type="text" name="search-updated-at-end" id="search-updated-at-end" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-updated-at-end']) ? $_GET['search-updated-at-end'] : ''; ?>" />
				</span>
			</fieldset>
			
			<!-- Date création -->
			<fieldset class="search-field-container">
				<legend><?php _e("Creation date", "debampass"); ?></legend>
				
				<!-- Début -->
				<span class="search-field-container-sub">
					<label for="search-created-at-start"><?php _e("Start", "debampass"); ?></label>
					<input type="text" name="search-created-at-start" id="search-created-at-start" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-created-at-start']) ? $_GET['search-created-at-start'] : ''; ?>" />
				</span>
				
				<!-- Fin -->
				<span class="search-field-container-sub">
					<label for="search-created-at-end"><?php _e("End", "debampass"); ?></label>
					<input type="text" name="search-created-at-end" id="search-created-at-end" class="jquery-datepicker" placeholder="YYYY-MM-DD" value="<?php echo isset($_GET['search-created-at-end']) ? $_GET['search-created-at-end'] : ''; ?>" />
				</span>
			</fieldset>
			
			<input type="submit" id="search-submit" class="button" value="<?php _e("Passes search", "debampass"); ?>" />
		</div>
		
		<?php $generatedPassListTable->display(); ?>
		
		<?php if ($generatedPassListTable->getNbPass() > 0): ?>
			<?php
				$url = wp_unslash($_SERVER['REQUEST_URI']);
				$query = parse_url($url, PHP_URL_QUERY);
				
				if ($query) {
					$url .= '&action=download-csv';
				} else {
					$url .= '?action=download-csv';
				}
			?>
			
			<a href="<?php echo $url; ?>" class="button"><?php _e("Download data in CSV format", "debampass"); ?></a>
		<?php endif; ?>
	</form>
</div>