(function ($) {
	$(document).ready(function () {
		initDEBamPass();
	});
	

	function initDEBamPass()
	{
		var popinLoading = false;
		
		// Clics sur 'Enregistrer mon code' -> ouverture de la popin de connexion/inscription
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
						
						loginManager();
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
		
		var deBamParam = getVar("de-bam");
		
		// On veut afficher la popin permettant d'activer son pass
		if (deBamParam && deBamParam == "ec") {
			popinLoading = true;
			showLoader();
			
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {action: "enterCodePopin"},
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
			closePopin($(this).closest(".de-bam-pass-popin"));
		});
	}
	
	function closePopin($popin)
	{
		$popin.remove();
		
		$("#de-bam-pass-popin-container").removeClass("open").find(".de-bam-pass-overlay, .de-bam-pass-loader").removeClass("open");
	}
	
	// Gestion de la connexion depuis la popin de connexion/inscription
	function loginManager()
	{
		$('#de-bam-pass-registration-popin .login').on("click", "a.de-bam-pass-button", function (event) {
			// Ouverture de la popin avec le formulaire de connexion (si possible)
			if ($(".lwa .lwa-links-modal").length == 1 && $(".lwa .lwa-modal").length == 1) {
				event.preventDefault();
				
				closePopin($("#de-bam-pass-popin-container .de-bam-pass-popin")); // On ferme la popin courante (connexion/inscription)
				
				$(".lwa .lwa-links-modal").trigger("click"); // On ouvre la popin avec le formulaire de connexion
				
				// On veut ajouter un paramètre dans le formulaire de connexion pour rediriger l'utilisateur lorsqu'il se connecte
				$(".lwa-modal .lwa-submit-wrapper").append('<input type="hidden" name="redirect_to" value="'+ urlAddParameter(document.location, 'de-bam', 'ec') +'" />');
			}
		});
	}
	
	
	
	function urlAddParameter(url, key, value)
	{
		key = encodeURI(key);
		value = encodeURI(value);

		// var kvp = document.location.search.substr(1).split('&');
		var kvp = url.search.substr(1).split('&');

		var i = kvp.length;
		var x;
		while (i--) {
			x = kvp[i].split('=');
			
			if (x[0] == key) {
				x[1] = value;
				kvp[i] = x.join('=');
				break;
			}
		}

		if (i < 0) {
			kvp[kvp.length] = [key,value].join('=');
		}
		
		// console.log(kvp);
		// console.log(kvp.join('&'));
		
		//this will reload the page, it's likely better to store this until finished
		// document.location.search = kvp.join('&');
		
		return url.origin + url.pathname +"?"+ kvp.join('&');
	}
	
	function getVar(nomVariable)
	{
		var infos = location.href.substring(location.href.indexOf("?")+1, location.href.length)+"&"
		if (infos.indexOf("#")!=-1)
		infos = infos.substring(0,infos.indexOf("#"))+"&"
		var variable=0
		{
		nomVariable = nomVariable + "="
		var taille = nomVariable.length
		if (infos.indexOf(nomVariable)!=-1)
		variable = infos.substring(infos.indexOf(nomVariable)+taille,infos.length).substring(0,infos.substring(infos.indexOf(nomVariable)+taille,infos.length).indexOf("&"))
		}
		return variable
	}
})(jQuery);
