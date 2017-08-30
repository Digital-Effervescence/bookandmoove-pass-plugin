<div id="de-bam-pass-registration-popin" class="de-bam-pass-popin">
	<?php include("elements/close.php"); ?>
	
	<div class="popin-content">
		<p class="title"><?php _e("I activate my BookandMoove code", "debampass"); ?></p>
		
		<div class="content">
			<div class="block login">
				<p class="block-title"><?php _e("Already customer", "debampass"); ?></p>
				
				<p class="description">
					<?php _e("You already have a customer account and want to activate a new BookandMoove code.", "debampass"); ?><br />
					<?php _e("You must first log in.", "debampass"); ?>
				</p>
				
				<div class="button-container">
					<a href="<?php echo wp_login_url(); ?>?de-bam=lo" class="de-bam-pass-button"><?php _e("Login", "debampass"); ?></a>
				</div>
			</div>
			
			<div class="block register">
				<p class="block-title"><?php _e("New customer", "debampass"); ?></p>
				
				<p class="description">
					<?php _e("You have not yet created a BookandMoove account.", "debampass"); ?><br />
					<?php _e("You must first create one to activate your code.", "debampass"); ?>
				</p>
				
				<div class="button-container">
					<button class="button-register de-bam-pass-button empty"><?php _e("Create an acount", "debampass"); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>