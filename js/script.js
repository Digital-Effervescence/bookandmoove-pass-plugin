(function ($) {
	$(document).ready(function () {
		initDEBamPass();
	});
	
	
	var popinLoading = false;
	var deBamPassCode = "";
	
	var htmlOverflow;
	

	function initDEBamPass()
	{
		htmlOverflow = $("html").css("overflow");
		
		// Clics sur 'Enregistrer mon code' -> ouverture de la popin de connexion/inscription
		$("#page ul.primary-menu").on("click", ".menu-item.save-code", function () {
			/*if (!popinLoading) {
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
			}*/
			
			displayActivationPassPopin();
		});
		
		// On récupère les éventuels paramètre dans l'url
		var deBamParam = getVar("de-bam");
		var deBamCode = getVar("code");
		
		// On veut afficher la popin d'inscription (commande)
		if (deBamParam && deBamParam == "ec" && deBamCode) {
			deBamPassCode = deBamCode;
			
			displayRegistrationPopin();
		}
		
		// On veut afficher la popin permettant d'activer son pass
		/*if (deBamParam && deBamParam == "ec") {
			popinLoading = true;
			showLoader();
			
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {action: "enterCodePopin"},
				type: "POST",
				success: function (dataPopin) {
					$("#de-bam-pass-popin-container").append(dataPopin);
					
					closePopinManager();
					
					// Validation du formulaire de saisie d'un code
					$("#de-bam-pass-popin-container").submit("#enter-code-form", function (event) {
						event.preventDefault();
						$("#de-bam-pass-enter-code-popin .errors").text("").removeClass("open");
						
						deBamPassCode = $("[name='enter-code-pass-code']").val();
						
						showLoaderAbove();
						
						$.ajax({
							url: ajax_object.ajaxurl,
							data: {action: "enterCodeValidation", 'enter-code-pass-code': deBamPassCode},
							type: "POST",
							dataType: "json",
							success: function (data) {
								console.log(data);
								if (data.status == "success") {
									if (data.passExists == 1) { // Le code du pass est bon, on passe à la suite
										if (data.loggued) { // Si l'utilisateur est loggué -> on affiche le formulaire
											console.log("if");
										} else { // Si l'utilisateur n'est pas loggué -> On affiche la popin permettant de se logguer ou de s'inscrire
											displayLoginRegistrationPopin();
										}
									} else { // Le code n'existe pas, est déjà pris ou n'est pas actif
										enterCodeValidationShowMessage(data.message);
									}
								} else {
									enterCodeValidationShowMessage(data.message);
									console.log(data.log);
								}
							},
							error: function (qXHR, textStatus, errorThrown) {
								console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
							},
							complete: function (dataCheckout) {
								hideLoaderAbove();
							}
						});
					});
				},
				error: function (qXHR, textStatus, errorThrown) {
					console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
				},
				complete: function () {
					popinLoading = false;
					hideLoader();
				}
			});
		}*/
		
		
		// Datepicker
		$(".jquery-datepicker").datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true
		});
	}
	
	function displayActivationPassPopin()
	{
		popinLoading = true;
		showLoader();
		
		// On veut afficher la popin de saisie du code
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {action: "enterCodePopin"},
			type: "POST",
			success: function (dataPopin) {
				$("#de-bam-pass-popin-container").append(dataPopin);
				
				closePopinManager();
				
				// Validation du formulaire de saisie d'un code
				$("#de-bam-pass-popin-container").on("submit", "#enter-code-form", function (event) {
					event.preventDefault();
					$("#de-bam-pass-enter-code-popin .errors").text("").removeClass("open");
					
					deBamPassCode = $("[name='enter-code-pass-code']").val();
					
					showLoaderAbove();
					
					$.ajax({
						url: ajax_object.ajaxurl,
						data: {action: "enterCodeValidation", 'enter-code-pass-code': deBamPassCode},
						type: "POST",
						dataType: "json",
						success: function (data) {
							// console.log(data);
							if (data.status == "success") {
								if (data.passExists == 1) { // Le code du pass est bon, on passe à la suite
									if (data.loggued) { // Si l'utilisateur est loggué -> on affiche le formulaire
										displayRegistrationPopin();
									} else { // Si l'utilisateur n'est pas loggué -> On affiche la popin permettant de se logguer ou de s'inscrire
										displayLoginRegistrationPopin();
										
										hideLoaderAbove();
									}
								} else { // Le code n'existe pas, est déjà pris ou n'est pas actif
									enterCodeValidationShowMessage(data.message);
									hideLoaderAbove();
								}
							} else {
								enterCodeValidationShowMessage(data.message);
								console.log(data.log);
								
								hideLoaderAbove();
							}
						},
						error: function (qXHR, textStatus, errorThrown) {
							console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
						},
						complete: function (dataCheckout) {
							// hideLoaderAbove();
						}
					});
				});
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
	
	function displayLoginRegistrationPopin()
	{
		if (!popinLoading) {
			popinLoading = true;
			
			$("#de-bam-pass-popin-container .de-bam-pass-popin").remove();
			showLoader();
			
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {action: "loadRegistrationPopin"},
				type: "POST",
				success: function (data) {
					$("#de-bam-pass-popin-container").append(data);
					
					closePopinManager();
					
					loginManager();
					
					$("#de-bam-pass-registration-popin .register").on("click", ".button-register", function () {
						displayRegistrationPopin();
					});
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
	
	function displayRegistrationPopin()
	{
		if (!popinLoading) {
			popinLoading = true;
			
			$("#de-bam-pass-popin-container .de-bam-pass-popin").remove();
			showLoader();
			
			// Chargement de la popin
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {action: "formRegistrationPopin", 'code-pass': deBamPassCode},
				type: "POST",
				dataType: "json",
				success: function (dataPopin) {
					if (dataPopin.status == "success") { // OK
						// Chargement du contenu du formulaire
						$.ajax({
							url: checkoutUrl,
							type: "POST",
							success: function (dataCheckout) {
								$("#de-bam-pass-popin-container").append(dataPopin.data);
								$("#de-bam-pass-popin-container .content").append($(dataCheckout).find("#entry-content-anchor").html());
								
								$("[name='woocommerce_checkout_place_order']").attr("value", checkoutButtonLabel);
								$("#order_review .checkout__billing .place-order").append('<input type="hidden" name="action" value="validateCheckoutForm" />'); // Pour le POST['action'] de l'ajax de validation du formulaire
								$("#order_review .checkout__billing .place-order").append('<input type="hidden" name="debampass-code" value="'+ deBamPassCode +'" />'); // Contient le code du pass pour être envoyé à l'ajax de validation du formulaire
								
								closePopinManager();
								
								checkoutManager();
							},
							error: function (qXHR, textStatus, errorThrown) {
								console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
							},
							complete: function (dataCheckout) {
								popinLoading = false;
								hideLoader();
								hideLoaderAbove();
							}
						});
					} else { // Erreur
						alert(dataPopin.message);
						console.log(dataPopin.log);
						
						popinLoading = false;
						hideLoader();
						hideLoaderAbove();
						
						// hideDeBamPassContainer();
						closePopin();
					}
				},
				error: function (qXHR, textStatus, errorThrown) {
					console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
					
					popinLoading = false;
					hideLoader();
					hideLoaderAbove();
				}
			});
		}
	}
	
	function checkoutManager()
	{
		// On valide le formulaire de commande
		$("#de-bam-pass-form-popin").on("submit", "form#order_review", function (event) {
			event.preventDefault();
			
			var formSerialized = $(this).serialize();
			
			showLoaderAbove();
			
			$.ajax({
				url: ajax_object.ajaxurl,
				data: formSerialized,
				type: "POST",
				dataType: "json",
				success: function (data) {
					// console.log(data);
					
					if (data.result == "success") { // OK
						// On veut finaliser la commande
						finalizeOrder(data.redirect);
					} else if (data.result == "failure") { // Erreur de validation
						// Si on n'a pas le container des messages d'erreurs de créé
						if ($("#de-bam-pass-form-popin form#order_review .form-errors-container").length == 0) {
							$("#de-bam-pass-form-popin form#order_review").prepend('<div class="form-errors-container"></div>');
						}
						
						$("#de-bam-pass-form-popin form#order_review .form-errors-container").html(data.messages);
						
						hideLoaderAbove();
					} else { // Autre erreur
						alert(data.message);
						console.log(data.log);
						
						hideLoaderAbove();
					}
				},
				error: function (qXHR, textStatus, errorThrown) {
					console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
					
					hideLoaderAbove();
				},
				complete: function (dataCheckout) {
					// hideLoaderAbove();
				}
			});
		});
	}
	
	function finalizeOrder(url)
	{
		// On récupère l'ID de la commande dans l'url (un peu de la bidouille, mais bon...)
		var urlSplited = url.split('?');
		var urlSplitedSlash = urlSplited[0].split('/');
		var idOrder = urlSplitedSlash[urlSplitedSlash.length - 1];
		
		// On veut finaliser la commande
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {'action': 'finalizeOrder', 'idOrder': idOrder, 'deBamPassCode': deBamPassCode},
			type: "POST",
			dataType: "json",
			success: function (data) {
				// console.log(data);
				
				if (data.status == "success") {
					popinMessageValidationPassManager(data.membershipPlan);
				} else {
					alert(data.message);
					console.log(data.log);
					
					hideLoaderAbove();
				}
			},
			error: function (qXHR, textStatus, errorThrown) {
				console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
				
				hideLoaderAbove();
			},
			complete: function (dataCheckout) {
				// hideLoaderAbove();
			}
		});
	}
	
	// Affiche la popin avec le message de validation de l'activation du pass
	function popinMessageValidationPassManager(membershipPlan)
	{
		popinLoading = true;
		showLoader();
		
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {action: "messageValidationPass"},
			type: "POST",
			success: function (dataPopin) {
				hideLoaderAbove();
				$("#de-bam-pass-popin-container .de-bam-pass-popin").remove();
				
				dataPopin = dataPopin.replace("###membership_plan_name###", "'"+ membershipPlan.name +"'");
				$("#de-bam-pass-popin-container").append(dataPopin);
				
				// On recharge la page à la fermeture de la popin
				$("#de-bam-pass-message-validation-pass-popin").one("click", ".close", function () {
					var urlSplited = document.location.href.split('?');
					document.location.href = urlSplited[0]; // Sans les éventuels paramètres de l'url
				});
			},
			error: function (qXHR, textStatus, errorThrown) {
				console.log(qXHR +" || "+ textStatus +" || "+ errorThrown);
				
				popinLoading = false;
				hideLoaderAbove();
				closePopin();
			},
			complete: function () {
				popinLoading = false;
				hideLoaderAbove();
				// closePopin();
			}
		});
	}
	
	function enterCodeValidationShowMessage(message)
	{
		$("#de-bam-pass-enter-code-popin .errors").text(message).addClass("open");
	}
	
	function showLoader()
	{
		$("#de-bam-pass-popin-container").addClass("open").find(".de-bam-pass-overlay, .de-bam-pass-loader").addClass("open");
		$("html").css({"overflow": "hidden"});
	}
	
	// function hideDeBamPassContainer()
	// {
		// $("#de-bam-pass-popin-container").removeClass("open");
		// $("html").css({"overflow": htmlOverflow});
	// }
	
	function hideLoader()
	{
		$("#de-bam-pass-popin-container").find(".de-bam-pass-loader").removeClass("open");
	}
	
	function showLoaderAbove()
	{
		$("#de-bam-pass-popin-container").find(".de-bam-pass-overlay-above, .de-bam-pass-loader").addClass("open");
	}
	
	function hideLoaderAbove()
	{
		$("#de-bam-pass-popin-container").find(".de-bam-pass-overlay-above, .de-bam-pass-loader").removeClass("open");
	}
	
	function closePopinManager()
	{
		$(".de-bam-pass-popin").one("click", ".close", function () {
			closePopin($(this).closest(".de-bam-pass-popin"));
		});
	}
	
	function closePopin($popin)
	{
		if (typeof $popin == "undefined") {
			$("#de-bam-pass-popin-container .de-bam-pass-popin").remove();
		} else {
			$popin.remove();
		}
		
		// hideLoaderAbove();
		$("html").css({"overflow": htmlOverflow});
		
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
				var url = urlAddParameter(document.location.href, 'de-bam', 'ec');
				url = urlAddParameter(url, 'code', deBamPassCode);
				$(".lwa-modal .lwa-submit-wrapper").append('<input type="hidden" name="redirect_to" value="'+ url +'" />');
			}
		});
	}
	
	
	
	function urlAddParameter(uri, key, value)
	{
		var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
		var separator = uri.indexOf('?') !== -1 ? "&" : "?";
		if (uri.match(re)) {
			return uri.replace(re, '$1' + key + "=" + value + '$2');
		} else {
			return uri + separator + key + "=" + value;
		}
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
