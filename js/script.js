(function ($) {
	$(document).ready(function () {
		initDEBamPass();
	});
	

	function initDEBamPass()
	{
		var popinLoading = false;
		
		$("#page ul.primary-menu").on("click", ".menu-item.save-code", function () {
			if (!popinLoading) {
				popinLoading = true;
				showLoader();
				
				$.ajax({
					url: ajax_object.ajaxurl,
					data: {action: "loadRegistrationPopin"},
					type: "POST",
					success: function (data) {
						$("#de-bam-pass-popin-container").append(data);
						
						closePopinManager();
					},
					error: function (qXHR, textStatus, errorThrown) {
						console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
					},
					complete: function () {
						popinLoading = false;
						hideLoader();
					}
				});
			}
		});
	}
	
	function showLoader()
	{
		$("#de-bam-pass-popin-container").addClass("open").find(".de-bam-pass-overlay, .de-bam-pass-loader").addClass("open");
	}
	
	function hideLoader()
	{
		$("#de-bam-pass-popin-container").find(".de-bam-pass-loader").removeClass("open");
	}
	
	function closePopinManager()
	{
		$(".de-bam-pass-popin").one("click", ".close", function () {
			$(this).closest(".de-bam-pass-popin").remove();
			
			$("#de-bam-pass-popin-container").removeClass("open").find(".de-bam-pass-overlay, .de-bam-pass-loader").removeClass("open");
		});
	}
})(jQuery);
