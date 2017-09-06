<div id="debampass-generator-page" class="wrap">
	<h1><?php _e("Pass generation", "debampass"); ?></h1>

	<?php if (isset($generationErrors) && !empty($generationErrors)): ?>
		<div class="global-errors">
			<?php foreach ($generationErrors as $aGenerationError): ?>
				<p class="errors"><?php echo $aGenerationError; ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<?php if (isset($validationMessages) && !empty($validationMessages)): ?>
		<div class="global-success">
			<?php foreach ($validationMessages as $aValidationMessage): ?>
				<p class="message"><?php echo $aValidationMessage; ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form id="form-pass-generation" method="post" action="">
		<p class="form-field">
			<label for="pass-generation-plan-type"><?php _e("Plan type", "debampass"); ?></label>
			<select name="pass-generation-plan-type" id="pass-generation-plan-type" class="short">
				<?php foreach ($membershipPlans as $aMembershipPlan): ?>
					<option value="<?php echo $aMembershipPlan->id; ?>"><?php echo $aMembershipPlan->name; ?></option>
				<?php endforeach; ?>
			</select>
			
			<?php if (isset($errors['pass-generation-plan-type'])): ?>
				<span class="errors"><?php echo $errors['pass-generation-plan-type']['message']; ?></span>
			<?php endif; ?>
		</p>
		
		<p class="form-field">
			<label for="pass-generation-codes-expiration-date"><?php _e("Codes expiration date", "debampass"); ?></label>
			<input type="text" name="pass-generation-codes-expiration-date" id="pass-generation-codes-expiration-date" class="short jquery-datepicker" required="required" placeholder="YYYY-MM-JJ" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
			
			<?php if (isset($errors['pass-generation-codes-expiration-date'])): ?>
				<span class="errors"><?php echo $errors['pass-generation-codes-expiration-date']['message']; ?></span>
			<?php endif; ?>
		</p>
		
		<p class="form-field">
			<label for="pass-generation-pass-number"><?php _e("Pass number", "debampass"); ?></label>
			<input type="number" name="pass-generation-pass-number" id="pass-generation-pass-number" class="short" required="required" step="1" min="<?php echo $nbMinPass; ?>" max="<?php echo $nbMaxPass; ?>" placeholder="<?php printf(__("Max %d", "debampass"), $nbMaxPass); ?>" />
			
			<?php if (isset($errors['pass-generation-pass-number'])): ?>
				<span class="errors"><?php echo $errors['pass-generation-pass-number']['message']; ?></span>
			<?php endif; ?>
		</p>
		
		<p class="submit-container">
			<input type="submit" name="pass-generation-submit" id="pass-generation-submit" class="de-bam-pass-button" value="<?php _e("Generate", "debampass"); ?>" />
		</p>
	</form>
</div>

<?php
	// echo "<pre>";
	// print_r($errors);
	// echo "</pre>";
	
	// $codeString = "236";
	// $codeStringLength = strlen($codeString);
	// for ($j = 0; $j < (9 - $codeStringLength); $j++) {
		// $codeString = "0". $codeString;
	// }
	
	// echo "code string : ". $codeString;
?>