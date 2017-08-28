<div id="de-bam-pass-enter-code-popin" class="de-bam-pass-popin">
	<?php include("elements/close.php"); ?>
	
	<div class="popin-content">
		<p class="title"><?php _e("Activate my BookandMoove code", "debampass"); ?></p>
		
		<div class="content">
			<form type="post" action="">
				<input type="text" class="pass-code" name="enter-code-pass-code" maxlength="9" placeholder="<?php _e("Enter the code of your card", "debampass"); ?>" />
				
				<p class="description">
					<?php _e("Enter your code number printed on the back of your BookandMoove card. This code must be 9 digits long.", "debampass"); ?>
				</p>
				
				<div class="button-container">
					<input type="submit" class="de-bam-pass-button" name="enter-code-submit" value="<?php _e("Validate my code", "debampass"); ?>" />
				</div>
			</form>
		</div>
	</div>
</div>