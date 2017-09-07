<?php
/*
	Plugin Name: DE Book And Moove Pass
	Description: Plugin de gestion de pass.
	Version:     0.1
	Author:      Digital Effervescence - Frédéric Le Crom
	Author URI:  http://www.digital-effervescence.com/
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

if (!defined('DEBAMPASS')) {
	define('DEBAMPASS', __FILE__);
}

require dirname(DEBAMPASS) .'/inc/Gamajo_Template_Loader.php';
require dirname(DEBAMPASS) .'/inc/PW_Template_Loader.php';

require dirname(DEBAMPASS) .'/inc/class-de-list-table.php';


// Seulement si les plugins 'WooCommerce' et 'WooCommerce Membership' sont actifs
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && in_array('woocommerce-memberships/woocommerce-memberships.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if (!class_exists('DEBamPass')) {
		class DEBamPass
		{
			private $templates;
			
			
			public function __construct()
			{
				register_activation_hook(__FILE__, array($this, 'install'));
				
				
				$this->templates = new PW_Template_Loader(plugin_dir_path(__FILE__));
				
				
				add_filter('show_admin_bar', '__return_false'); // TMP
				
				$this->loadTranslations();
				
				add_action('init', array($this, 'init'));
				
				add_action('wp_enqueue_scripts', array($this, 'loadStylesScripts'));
				add_action('admin_enqueue_scripts', array($this, 'loadStylesScriptsAdmin'));
				
				// Ajax popin 'entrer code'
				add_action('wp_ajax_enterCodePopin', array($this, 'enterCodePopin'));
				add_action('wp_ajax_nopriv_enterCodePopin', array($this, 'enterCodePopin'));
				
				// Ajax validation popin 'entrer code'
				add_action('wp_ajax_enterCodeValidation', array($this, 'enterCodeValidation'));
				add_action('wp_ajax_nopriv_enterCodeValidation', array($this, 'enterCodeValidation'));
				
				// Ajax popin inscription/connexion
				add_action('wp_ajax_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
				add_action('wp_ajax_nopriv_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
				
				// Ajax popin 'form registration'
				add_action('wp_ajax_formRegistrationPopin', array($this, 'formRegistrationPopin'));
				add_action('wp_ajax_nopriv_formRegistrationPopin', array($this, 'formRegistrationPopin'));
				
				// Ajax 'validate registration'
				add_action('wp_ajax_validateCheckoutForm', array($this, 'validateCheckoutForm'));
				add_action('wp_ajax_nopriv_validateCheckoutForm', array($this, 'validateCheckoutForm'));
				
				// Ajax 'finalize order'
				add_action('wp_ajax_finalizeOrder', array($this, 'finalizeOrder'));
				add_action('wp_ajax_nopriv_finalizeOrder', array($this, 'finalizeOrder'));
				
				// Ajax popin 'order message validation'
				add_action('wp_ajax_messageValidationPass', array($this, 'messageValidationPass'));
				add_action('wp_ajax_nopriv_messageValidationPass', array($this, 'messageValidationPass'));
				
				
				// AJAX TMP
				// add_action('wp_ajax_tmpPassGenerator', array($this, 'tmpPassGenerator'));
				// add_action('wp_ajax_nopriv_tmpPassGenerator', array($this, 'tmpPassGenerator'));
				
				
				add_filter('wp_footer', array($this, 'deBamPassHtmlContainer'));
				
				
				
				// Admin
				add_action('admin_menu', array($this, 'deBamPassAdminMenu'));
			}
			
			// Installation du plugin
			public function install()
			{
				global $wpdb;
				
				// On veut créer une table en BDD
				$tableName = $wpdb->prefix ."debampass";
				
				$charsetCollate = $wpdb->get_charset_collate();
				
				$sql = "CREATE TABLE $tableName (
				  id BIGINT(20) NOT NULL AUTO_INCREMENT,
				  membership_plan BIGINT(20) NOT NULL,
				  user_id BIGINT(20),
				  code VARCHAR(9) NOT NULL,
				  date_end_code_active DATE NOT NULL,
				  created_at DATETIME NOT NULL,
				  updated_at DATETIME,
				  PRIMARY KEY  (id),
				  KEY INDEX_CODE (code)
				) $charsetCollate;";

				require_once (ABSPATH .'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			
			public function init()
			{
				// On a le paramètre indiquant que l'on est sur la page de login et que l'on souhaite afficher la popin permettant d'entrer le code après l'authentification
				if (isset($_GET['de-bam']) && $_GET['de-bam'] == "lo") {
					add_filter('login_redirect', array($this, 'loginRedirect'), 10, 3);
				}
			}
			
			public function wpLoaded()
			{
				global $woocommerce;
				
				
				// $orderId = $woocommerce->checkout->create_order();
				
				// echo "yo : ". $orderId;
				
				// echo "<pre>";
				// print_r($meta);
				// echo "</pre>";
			}
			
			public function customPrice($cart)
			{
				global $woocommerce;
				
				// foreach ($woocommerce->cart->get_cart() as $key => $value) {
				foreach ($cart->get_cart() as $aProductInCart) {
					// $value['data']->set_price(0);
					// $aProductInCart['line_total'] = 40;
					$aProductInCart['data']->set_price(0);
					// echo get_class($aProductInCart['data']);
					// $aProductInCart['data']->virtual = "yes";
					
					// echo "<pre>";
					// print_r($aProductInCart);
					// echo "</pre>";
				}
				
				// $cart->add_fee('test_fee', 3);
				
				// $woocommerce->shipping->reset_shipping();
			}
			
			public function addCartFee()
			{
				global $woocommerce;
				
				$woocommerce->cart->add_fee('test_fee', -5);
			}
			
			// Ajout du container HTML du plugin à la page courante
			public function deBamPassHtmlContainer($content)
			{
				$this->templates->get_template_part('popin', 'container');
			}
			
			public function loadRegistrationPopin()
			{
				$this->templates->get_template_part('popin', 'registration');
				die();
			}
			
			public function enterCodePopin()
			{
				$this->templates->get_template_part('popin', 'enter-code');
				die();
			}
			
			// Méthode appelée en ajax, lorsque l'on entre son code pour son pass
			public function enterCodeValidation()
			{
				global $wpdb;
				
				if (isset($_POST['enter-code-pass-code'])) {
					// $tableName = $wpdb->prefix ."debampass";
					
					$queryGetPass = "";
					$queryGetPass .= "SELECT id ";
					$queryGetPass .= "FROM $tableName ";
					$queryGetPass .= "WHERE user_id IS NULL ";
					$queryGetPass .= "AND date_end_code_active >= DATE(NOW()) ";
					$queryGetPass .= "AND code = %s";
					
					$result = $wpdb->query($wpdb->prepare($this->getCodePassEntryQuery(), $_POST['enter-code-pass-code']));
					
					$isOk = 0;
					$message = __("The entered code is incorrect", "debampass");
					if ($result == 1) { // On a bien 1 pass non activé
						$isOk = 1;
						$message = "";
					}
					
					echo json_encode(
						array(
							'status' => 'success',
							'passExists' => $isOk,
							'message' => $message,
							'loggued' => is_user_logged_in(),
						)
					);
				} else {
					echo json_encode(
						array(
							'status' => 'success',
							'message' => __("An error has occurred", "debampass"),
							'log' => "Manque la variable POST contenant le code.",
						)
					);
				}
				
				exit;
			}
			
			private function getCodePassEntryQuery()
			{
				global $wpdb;
				
				$tableName = $wpdb->prefix ."debampass";
				
				$queryGetPass = "";
				$queryGetPass .= "SELECT id, membership_plan, user_id, code, date_end_code_active, created_at, updated_at ";
				$queryGetPass .= "FROM $tableName ";
				$queryGetPass .= "WHERE user_id IS NULL ";
				$queryGetPass .= "AND date_end_code_active >= DATE(NOW()) ";
				$queryGetPass .= "AND code = %s";
				
				return $queryGetPass;
			}
			
			private function getCodePassEntryActivatedQuery()
			{
				global $wpdb;
				
				$tableName = $wpdb->prefix ."debampass";
				
				$queryGetPass = "";
				$queryGetPass .= "SELECT id, membership_plan, user_id, code, date_end_code_active, created_at, updated_at ";
				$queryGetPass .= "FROM $tableName ";
				$queryGetPass .= "WHERE user_id IS NOT NULL ";
				$queryGetPass .= "AND code = %s";
				
				return $queryGetPass;
			}
			
			// Chargement de la popin avec le formulaire d'inscription
			public function formRegistrationPopin()
			{
				global $woocommerce;
				global $wpdb;
				
				
				$resultPass = $wpdb->get_results($wpdb->prepare($this->getCodePassEntryQuery(), $_POST['code-pass'])); // On récupère l'entrée en BDD du pass dont le code est passé en POST
				// $resultPass = $wpdb->get_results($wpdb->prepare($this->getCodePassEntryQuery(), "123654987")); // On récupère l'entrée en BDD du pass dont le code est passé en POST
				// echo "<pre>";
				// print_r($resultPass);
				// echo "</pre>";
				// exit;
				if (count($resultPass) == 1) {
					// On enregistre l'état du panier de l'utilisateur
					if (!session_id()) {
						session_start();
					}
					
					$_SESSION['cart_state'] = $woocommerce->cart->get_cart();
					
					$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
					
					$metaMembershipPlan = get_post_meta($resultPass[0]->membership_plan, '_product_ids');
					// $woocommerce->cart->add_to_cart(12566);
					
					if (isset($metaMembershipPlan[0]) && isset($metaMembershipPlan[0][0])) {
						$woocommerce->cart->add_to_cart($metaMembershipPlan[0][0]);
						
						foreach ($woocommerce->cart->get_cart() as $key => $value) {
							$value['data']->set_price(0);
							$value['data']->virtual = "yes"; // On rend le produit 'virtuel' pour ne pas qu'il y ait de frais de port
						}

						
						echo json_encode(
							array(
								'status' => 'success',
								'data' => $this->templates->get_template_part('popin', 'form-registration', true, true),
							)
						);
					} else {
						echo json_encode(
							array(
								'status' => 'error',
								'message' => __("An error has occurred", "debampass"),
								'log' => "Pas de produit trouvé dans les metas du membership plan.",
							)
						);
					}
					
					exit;
				} else {
					echo json_encode(
						array(
							'status' => 'error',
							'message' => __("An error has occurred", "debampass"),
							'log' => "Aucun ou plus de 1 résultat.",
						)
					);
					exit;
				}
				
				// echo "<pre>";
				// print_r($resultPass);
				// echo "</pre>";
			}
			
			public function validateCheckoutForm()
			{
				global $woocommerce;
				global $wpdb;
				
				// On vérifie (encore) que le code est valide
				if (isset($_POST['debampass-code'])) {
					$result = $wpdb->query($wpdb->prepare($this->getCodePassEntryQuery(), $_POST['debampass-code']));
					
					if ($result == 1) { // On a bien 1 pass non activé
						// On parcourt les produits du panier (il n'y en a en fait qu'un seul)
						foreach ($woocommerce->cart->get_cart() as $aProductInCart) {
							$aProductInCart['data']->set_price(0); // On met le prix à 0
							$aProductInCart['data']->virtual = "yes"; // On rend le produit 'virtuel' pour ne pas qu'il y ait de frais de port
						}
						
						$woocommerce->checkout->must_create_account = true; // Un utilisateur non inscrit sera inscrit à l'issue de la commande
						$checkoutResults = $woocommerce->checkout->process_checkout();
						
						// echo "<pre>";
						// print_r($checkoutResults);
						// echo "</pre>";
					} else {
						echo json_encode(
							array(
								'status' => 'error',
								'message' => __("An error has occurred", "debampass"),
								'log' => "Le code n'est pas bon.",
							)
						);
					}
				} else {
					echo json_encode(
						array(
							'status' => 'error',
							'message' => __("An error has occurred", "debampass"),
							'log' => "Manque la variable POST contenant le code.",
						)
					);
				}
				
				// echo json_encode($checkoutResults);
				// echo "<pre>";
				// print_r($checkoutResults);
				// echo "</pre>";
				exit;
			}
			
			// Finalisation de l'activation du pass
			public function finalizeOrder()
			{
				global $woocommerce;
				global $wpdb;
				
				if (isset($_POST['idOrder']) && $_POST['deBamPassCode']) { // Si on a bien les données $_POST
					if (get_current_user_id() != 0) { // Si on a bien un utilisateur connecté
						$order = new WC_Order($_POST['idOrder']); // On récupère la commande
						$order->update_status('completed', __("Pass activated", "debampass")); // On met à jour le status de la commande, pour la finaliser
						
						// On met à jour dans la BDD l'entrée du pass de la table 'debampass' pour indiquer que le pass est activé
						$tableName = $wpdb->prefix ."debampass";
						
						$queryActivatePass = "";
						$queryActivatePass .= "UPDATE $tableName ";
						$queryActivatePass .= "SET user_id = %d, ";
						$queryActivatePass .= "updated_at = NOW() ";
						$queryActivatePass .= "WHERE user_id IS NULL ";
						$queryActivatePass .= "AND date_end_code_active >= DATE(NOW()) ";
						$queryActivatePass .= "AND code = %s";
						
						$resultUpdatePass = $wpdb->query($wpdb->prepare($queryActivatePass, get_current_user_id(), $_POST['deBamPassCode']));
						
						if (false === $resultUpdatePass || $resultUpdatePass != 1) {
							$order->update_status('processing', __("Pass activation error", "debampass")); // On repasse le status de la commande à 'En cours'
							
							echo json_encode(
								array(
									'status' => 'error',
									'message' => __("An error has occurred", "debampass"),
									'log' => "Erreur lors de l'activation du pass en BDD.",
								)
							);
						} else {
							// On veut récupérer les données du membership plan
							$resultPass = $wpdb->get_results($wpdb->prepare($this->getCodePassEntryActivatedQuery(), $_POST['deBamPassCode']));
							if (count($resultPass) == 1) {
								$membershipPlan = wc_memberships_get_membership_plan($resultPass[0]->membership_plan);
								
								// On réapplique le contenu du panier qu'il y avait avant l'activation du pass
								$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
								
								if (!session_id()) {
									session_start();
								}
								
								foreach ($_SESSION['cart_state'] as $cartItemKey => $cartItemValues) {
									$id = $cartItemValues['product_id'];
									$quantity = $cartItemValues['quantity'];
									
									$woocommerce->cart->add_to_cart($id, $quantity);
								}
								
								unset($_SESSION['cart_state']);

								
								echo json_encode(
									array(
										'status' => 'success',
										'membershipPlan' => $membershipPlan,
									)
								);
							} else {
								echo json_encode(
									array(
										'status' => 'error',
										'message' => __("An error has occurred", "debampass"),
										'log' => "Erreur lors de la récupération du membership plan.",
									)
								);
							}
						}
					} else {
						echo json_encode(
							array(
								'status' => 'error',
								'message' => __("An error has occurred", "debampass"),
								'log' => "Pas d'utilisateur connecté.",
							)
						);
					}
				} else {
					echo json_encode(
						array(
							'status' => 'error',
							'message' => __("An error has occurred", "debampass"),
							'log' => "Manque la ou les variables POST.",
						)
					);
				}
				
				exit;
			}
			
			// Affichage de la popin affichant le message de confirmation de l'activation du pass
			public function messageValidationPass()
			{
				$this->templates->get_template_part('popin', 'message-validation-pass');
				die();
			}
			
			private function loadTranslations()
			{
				$languageDir = dirname(plugin_basename(DEBAMPASS)) .'/languages/';
				
				load_plugin_textdomain('debampass', false, $languageDir);
			}
			
			// Chargement des css et js
			public function loadStylesScripts()
			{
				global $woocommerce;
				
				$checkoutUrl = $woocommerce->cart->get_checkout_url();
				
				wp_enqueue_style('de_bam_style', plugin_dir_url(__FILE__) .'css/style.css', array(), false, 'screen');
				
				wp_enqueue_script('de_bam_script', plugin_dir_url(__FILE__) .'js/script.js', array('jquery'), '1.0', true);
				wp_localize_script('de_bam_script', 'deBamPassPluginDirUrl', plugin_dir_url(__FILE__));
				wp_localize_script('de_bam_script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
				wp_localize_script('de_bam_script', 'checkoutUrl', $checkoutUrl);
				wp_localize_script('de_bam_script', 'checkoutButtonLabel', __("Activate my code", "debampass"));
			}
			
			// Chargement des css et js dans l'admin
			public function loadStylesScriptsAdmin()
			{
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('de_bam_script_admin', plugin_dir_url(__FILE__) .'js/script.js', array('jquery'), '1.0', true);
				
				wp_enqueue_style('de_bam_style_admin', plugin_dir_url(__FILE__) .'css/style.css', array(), false, 'screen');
				
				wp_register_style('jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css');
				wp_enqueue_style('jquery-ui');
			}
			
			public function loginRedirect($redirect_to, $request, $user)
			{
				return home_url() ."?de-bam=ec";
			}
			
			
			// Gestion du formulaire de commande d'un Pass
			/*public function woocommerCheckoutForm($fields)
			{
				echo "<pre>";
				print_r($fields);
				echo "</pre>";
				
				$fields['order']['de_bam_pass']['type'] = "text";
				$fields['order']['de_bam_pass']['label'] = __("Activate my BookandMoove code", "debampass");
				$fields['order']['de_bam_pass']['placeholder'] = __("Enter your card code", "debampass");
				
				return $fields;
			}*/
			
			/*public function woocommerCheckoutFormFieldProcess()
			{
				// global $woocommerce;
				
				// $checkout_url = $woocommerce->cart->get_checkout_url();
				
				// echo "url : ". $checkout_url;
				// wc_add_notice(__('Please enter something into this new shiny field.'), 'error');
				// echo "test ma gueule : ". $_POST['de_bam_pass'];
				// die();
			}*/
			
			
			
			public function deBamPassAdminMenu()
			{
				add_submenu_page("woocommerce", __("Pass generator", "debampass"), __("Pass generator", "debampass"), "manage_options", "woocommerce-debampass-generator", array($this, "passGenerator"));
				
				add_submenu_page("woocommerce", __("Pass viewer", "debampass"), __("Pass viewer", "debampass"), "manage_options", "woocommerce-debampass-viewer", array($this, "passGenerated"));
			}
			
			// Page admin de génération de pass
			public function passGenerator()
			{
				if (!current_user_can('manage_options')) {
					wp_die(__("You are not allowed to access this page."));
				}
				
				
				$generationErrors = array();
				
				// Création du dossier qui contiendra les CSV
				$directoryName = "/exports/";
				$uploadExportsPath = realpath(dirname(__FILE__)) . $directoryName;
				if (!$this->exportDirectoryCheck($uploadExportsPath)) {
					array_push($generationErrors, __("Unable to create the directory containing the CSV exports on the server. Please temporarily grant sufficient rights to the 'de-bam-pass' directory in 'wp-content/plugins/' and reload the page.", "debampass"));
				}
				
				$nbminPass = 1;
				$nbMaxPass = 5000;
				$membershipPlans = wc_memberships_get_membership_plans();
				
				// Validation du formulaire de génération de pass
				if (isset($_POST['pass-generation-submit'])) {
					$errors = array();
					
					$membershipPlan;
					
					// Membership plan
					if (!isset($_POST['pass-generation-plan-type']) || trim($_POST['pass-generation-plan-type']) == "") { // Inexistant ou chaîne vide
						$errors['pass-generation-plan-type'] = array(
							'message' => __("Is required", "debampass"),
						);
					} else {
						$membershipPlan = wc_memberships_get_membership_plan($_POST['pass-generation-plan-type']);
						
						if (empty($membershipPlan)) {
							$errors['pass-generation-plan-type'] = array(
								'message' => __("Is not a valid membership plan", "debampass"),
							);
						}
					}
					
					// Date d'expiration
					if (!isset($_POST['pass-generation-codes-expiration-date']) || trim($_POST['pass-generation-codes-expiration-date']) == "") { // Inexistant ou chaîne vide
						$errors['pass-generation-codes-expiration-date'] = array(
							'message' => __("Is required", "debampass"),
						);
					} else {
						$expireDateExploded = explode('-', $_POST['pass-generation-codes-expiration-date']);
						
						// Mauvais format
						if (!(strlen($_POST['pass-generation-codes-expiration-date']) == 10 && count($expireDateExploded) == 3 && strlen($expireDateExploded[0]) == 4 && is_numeric($expireDateExploded[0]) && strlen($expireDateExploded[1]) == 2 && is_numeric($expireDateExploded[1]) && strlen($expireDateExploded[2]) == 2 && is_numeric($expireDateExploded[2]))) {
							$errors['pass-generation-codes-expiration-date'] = array(
								'message' => __("Must be a valid date", "debampass"),
							);
						}
					}
					
					// Nombre de pass
					if (!isset($_POST['pass-generation-pass-number']) || trim($_POST['pass-generation-pass-number']) == "") { // Inexistant ou chaîne vide
						$errors['pass-generation-pass-number'] = array(
							'message' => __("Is required", "debampass"),
						);
					} else {
						if (!ctype_digit($_POST['pass-generation-pass-number'])) { // N'est pas un nombre valide
							$errors['pass-generation-pass-number'] = array(
								'message' => __("Must be a number", "debampass"),
							);
						} else {
							if ($_POST['pass-generation-pass-number'] > $nbMaxPass) { // Trop grand
								$errors['pass-generation-pass-number'] = array(
									'message' => sprintf(__("Must be less than or equal to %d", "debampass"), $nbMaxPass),
								);
							} else {
								if ($_POST['pass-generation-pass-number'] < $nbminPass) { // Trop petit
									$errors['pass-generation-pass-number'] = array(
										'message' => sprintf(__("Must be greater than or equal to %d", "debampass"), $nbminPass),
									);
								}
							}
						}
					}
					
					
					// Formulaire valide
					if (empty($errors)) {
						global $wpdb;
						
						$validationMessages = array();
						
						// Variables de l'algo
						$step = 40005683; // La valeur que l'on ajoute au code courant pour créer un nouveau code
						
						$max = 999999999; // Nombre maximum des codes
						$maxString = $max ."";
						$maxLength = strlen($maxString);
						
						$b = 1; // L'occurence de la boucle. Sert comme valeur initiale pour le code.
						
						$code; // Le code courant
						$codeTmp = $b;
						
						$batchSize = 1000;
						
						$tableName = $wpdb->prefix ."debampass";
						
						// On récupère le nombre d'enregistrements
						$queryNbPass = "";
						$queryNbPass .= "SELECT COUNT(id) ";
						$queryNbPass .= "FROM $tableName";
						
						$resultNbPass = $wpdb->get_var($queryNbPass);
						
						
						// Si on a moins de codes disponibles que de codes que l'on veut générer
						if (($max - $resultNbPass) < $_POST['pass-generation-pass-number']) {
							array_push($generationErrors, sprintf(__("There are only %d available codes left", "debampass"), ($max - $resultNbPass)));
						} else {
							if ($resultNbPass == 0) { // Première génération
								$code = $b;
							} else {
								// On récupère le dernier enregistrement en BDD pour continuer à partir de son code
								$queryGetLastPass = "";
								$queryGetLastPass .= "SELECT code ";
								$queryGetLastPass .= "FROM $tableName ";
								$queryGetLastPass .= "ORDER BY id DESC ";
								$queryGetLastPass .= "LIMIT 1";
								
								$resultLastPass = $wpdb->get_results($queryGetLastPass);
								
								$code = $resultLastPass[0]->code;
								$code = intval($code);
								
								// On calcule l'occurence ($b) de la boucle
								$i = 0;
								$codeTmp;
								while ($codeTmp != $code) {
									$codeTmp = $codeTmp + $step;
									
									if ($codeTmp > $max) {
										$b++;
										$codeTmp = $b;
									}
									
									$i++;
								}
							}
							
							
							// Création du fichier CSV
							$fileName = date('Y') ."_". date('m') ."_". date('d') ."-". date('H') ."_". date('i') ."_". date('s') ."-pass_generation.csv";
							$filePath = $uploadExportsPath . $fileName;
							
							$csvTitle = array(__("Code", "debampass"), __("Expiration date", "debampass"), __("Membership Plan", "debampass"));
							
							$handle = fopen($filePath, 'w');
							fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
							fputcsv($handle, $csvTitle, ';'); // Titre
							
							
							$queryInsertPassTitle = "";
							$queryInsertPassTitle .= "INSERT INTO $tableName (membership_plan, code, date_end_code_active, created_at) ";
							$queryInsertPassTitle .= "VALUES ";
							
							
							// On va générer les pass (autant que défini dans $_POST['pass-generation-pass-number'])
							$codeString;
							$queryInsertPass = "";
							$nbInsertedRows = 0;
							for ($i = 0; $i < $_POST['pass-generation-pass-number']; $i++) {
								$code = $code + $step;
								
								// Si on dépasse $max (999999999) -> on recommence en ajoutant 1 à la valeur initiale
								if ($code > $max) {
									$b++;
									$code = $b;
								}
								
								$codeString = $code ."";
								$codeStringLength = strlen($codeString);
								for ($j = 0; $j < $maxLength - $codeStringLength; $j++) {
									$codeString = "0". $codeString;
								}
								
								if ($i != 0 && ($i % $batchSize) != 1) {
									$queryInsertPass .= ", ";
								}
								
								$queryInsertPass .= "(". $membershipPlan->id .", '". $codeString ."', '". $_POST['pass-generation-codes-expiration-date'] ."', NOW())";
								
								// fputcsv($handle, array($codeString, $_POST['pass-generation-codes-expiration-date'], $membershipPlan->name), ';');
								fputcsv($handle, array('"'. $codeString .'"', $_POST['pass-generation-codes-expiration-date'], $membershipPlan->name), ';');
								
								if ($i % $batchSize == 0) {
									$queryString = $queryInsertPassTitle . $queryInsertPass;
									$resultInsertPass = $wpdb->query($queryString);
									$nbInsertedRows += $resultInsertPass;
									
									$queryInsertPass = "";
								}
							}
							
							fclose($handle);
							
							$queryString = $queryInsertPassTitle . $queryInsertPass;
							$resultInsertPass = $wpdb->query($queryString);
							$nbInsertedRows += $resultInsertPass;
							
							array_push($validationMessages, sprintf(__("%d pass inserted successfully", "debampass"), $nbInsertedRows));
							array_push($validationMessages, __("CSV file :", "debampass") .' <a href="'. plugins_url($directoryName . $fileName, __FILE__) .'" target="_blank">'. $fileName .'</a>');
						}
					}
				}
				
				include "templates/admin/page-passes-generator.php";
				
				// echo "<pre>";
				// print_r($membershipPlan);
				// echo "</pre>";
			}
			
			// Page admin de visualisation des pass générés
			public function passGenerated($activeTab)
			{
				// header('Content-Type: application/csv');
				// header('Content-Disposition: attachment; filename=example.csv');
				// header('Pragma: no-cache');
				// readfile("exports/2017_09_06-14_47_59-pass_generation.csv");
				// exit;
				
				$generatedPassListTable = new DE_List_Table_Pass_Generated();
				
				$doAction = $generatedPassListTable->current_action();
				
				// echo "action : ";
				// echo "<pre>";
				// print_r($doAction);
				// echo "</pre>";
				
				if ($doAction) {
					
				} elseif (!empty($_REQUEST['_wp_http_referer'])) {
					wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI'])));
					exit;
				}
				
				// Page
				if (isset($_GET['paged'])) {
					$generatedPassListTable->setCurrentNumPage($_GET['paged']);
				}
				
				// Tri
				if (isset($_GET['orderby']) && isset($_GET['order'])) {
					$generatedPassListTable->setOrder($_GET['orderby'], $_GET['order']);
				}
				
				
				// Recherche
				if (isset($_GET['search-pass-status']) && trim($_GET['search-pass-status']) != "" && trim($_GET['search-pass-status']) != "-1") { // Statut des pass
					$generatedPassListTable->setPassStatus($_GET['search-pass-status']);
				}
				
				if (isset($_GET['search-plan-type']) && trim($_GET['search-plan-type']) != "") { // Membership Plan
					$generatedPassListTable->setMembershipPlan($_GET['search-plan-type']);
				}
				
				if (isset($_GET['search-expiration-date-start']) && trim($_GET['search-expiration-date-start']) != "") { // Date d'expiration (début)
					$generatedPassListTable->setExpirationDateStart($_GET['search-expiration-date-start']);
				}
				if (isset($_GET['search-expiration-date-end']) && trim($_GET['search-expiration-date-end']) != "") { // Date d'expiration (fin)
					$generatedPassListTable->setExpirationDateEnd($_GET['search-expiration-date-end']);
				}
				
				if (isset($_GET['search-updated-at-start']) && trim($_GET['search-updated-at-start']) != "") { // Date d'activation (début)
					$generatedPassListTable->setUpdatedAtStart($_GET['search-updated-at-start']);
				}
				if (isset($_GET['search-updated-at-end']) && trim($_GET['search-updated-at-end']) != "") { // Date d'activation (fin)
					$generatedPassListTable->setUpdatedAtEnd($_GET['search-updated-at-end']);
				}
				
				if (isset($_GET['search-created-at-start']) && trim($_GET['search-created-at-start']) != "") { // Date de création (début)
					$generatedPassListTable->setCreatedAtStart($_GET['search-created-at-start']);
				}
				if (isset($_GET['search-created-at-end']) && trim($_GET['search-created-at-end']) != "") { // Date de création (fin)
					$generatedPassListTable->setCreatedAtEnd($_GET['search-created-at-end']);
				}
				
				$generatedPassListTable->prepare_items();
				
				$membershipPlans = wc_memberships_get_membership_plans();
				
				include "templates/admin/page-passes-generated.php";
			}
			
			// Page admin de visualisation des pass activés
			private function passActivated($activeTab)
			{
				include "templates/admin/page-passes-activated.php";
			}
			
			
			private function exportDirectoryCheck($path)
			{
				$isDirectoryCreated = true;
				
				// Dossier d'export des CSV
				if (!file_exists($path)) {
					$isDirectoryCreated = mkdir($path, 0775, true);
					
					// $handle = fopen($path .'index.php', 'w');
					// fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
					// fwrite($handle, "<?php // Munen");
					// fclose($handle);
				}
				
				return $isDirectoryCreated;
			}
			
			// TMP
			/*public function tmpPassGenerator()
			{
				global $wpdb;
				
				$generationErrors = array();
				
				// Variables de l'algo
				$step = 40005683; // La valeur que l'on ajoute au code courant pour créer un nouveau code
				
				$max = 999999999; // Nombre maximum des codes
				$maxString = $max ."";
				$maxLength = strlen($maxString);
				// $max = 4000; // Nombre maximum des codes
				
				$b = 1; // L'occurence de la boucle. Sert comme valeur initiale pour le code.
				
				$code; // Le code courant
				$codeTmp = $b;
				
				
				$batchSize = 1000;
				
				$tableName = $wpdb->prefix ."debampass";
				
				// On récupère le nombre d'enregistrements
				$queryNbPass = "";
				$queryNbPass .= "SELECT COUNT(id) ";
				$queryNbPass .= "FROM $tableName ";
				
				$resultNbPass = $wpdb->get_var($queryNbPass);
				
				
				// Si on a moins de codes disponibles que de codes que l'on veut générer
				if (($max - $resultNbPass) < $_POST['pass-generation-pass-number']) {
					array_push($generationErrors, sprintf(__("There are only %d available codes left", "debampass"), ($max - $resultNbPass)));
				} else {
					if ($resultNbPass == 0) { // Première génération
						$code = $b;
					} else {
						// On récupère le dernier enregistrement en BDD pour continuer à partir de son code
						$queryGetLastPass = "";
						$queryGetLastPass .= "SELECT code ";
						$queryGetLastPass .= "FROM $tableName ";
						$queryGetLastPass .= "ORDER BY id DESC ";
						$queryGetLastPass .= "LIMIT 1";
						
						$resultLastPass = $wpdb->get_results($queryGetLastPass);
						
						$code = $resultLastPass[0]->code;
						$code = intval($code);
						
						// On calcule l'occurence ($b) de la boucle
						$i = 0;
						$codeTmp;
						while ($codeTmp != $code) {
							$codeTmp = $codeTmp + $step;
							
							if ($codeTmp > $max) {
								$b++;
								$codeTmp = $b;
							}
							
							$i++;
						}
					}
					
					
					$queryInsertPassTitle = "";
					$queryInsertPassTitle .= "INSERT INTO $tableName (membership_plan, code, date_end_code_active, created_at) ";
					$queryInsertPassTitle .= "VALUES ";
					
					
					$uploadExportsPath = realpath(dirname(__FILE__)) ."/exports/";
					if (!file_exists($uploadExportsPath)) {
						$returned = mkdir($uploadExportsPath, 0775, true);
					}
					
					// On va générer les pass (autant que défini dans $_POST['pass-generation-pass-number'])
					$codeString;
					
					$queryInsertPass = "";
					// $queryInsertPass .= ", ";
					
					$indexFile = 0;
					for ($i = 0; $i < $_POST['pass-generation-pass-number']; $i++) {
						// $codeTmp = $code + $step;
						$code = $code + $step;
						
						// Si on dépasse $max (999999999) -> on recommence en ajoutant 1 à la valeur initiale
						// if ($codeTmp > $max) {
						if ($code > $max) {
							$b++;
							$code = $b;
						}
						
						$codeString = $code ."";
						$codeStringLength = strlen($codeString);
						for ($j = 0; $j < $maxLength - $codeStringLength; $j++) {
							$codeString = "0". $codeString;
						}
						
						// if ($i > 0) {
						if ($i != 0 && ($i % $batchSize) != 1) {
							$queryInsertPass .= ", ";
						}
						
						$queryInsertPass .= "(12166, '". $codeString ."', '". $_POST['pass-generation-codes-expiration-date'] ."', NOW())";
						
						if ($i % $batchSize == 0) {
							$queryString = $queryInsertPassTitle . $queryInsertPass;
							
							$filePath = $uploadExportsPath . "query_". $indexFile .".sql";
							
							$handle = fopen($filePath, 'w');
							fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
							fwrite($handle, $queryString);
							fclose($handle);
							
							$queryInsertPass = "";
							// $queryInsertPass .= ", ";
							$indexFile++;
						}
					}
					
					
					$queryString = $queryInsertPassTitle . $queryInsertPass;
					$filePath = $uploadExportsPath . "query_". $indexFile .".sql";
					
					$handle = fopen($filePath, 'w');
					fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
					fwrite($handle, $queryString);
					fclose($handle);
					
					exit;
				}
			}*/
		}
		
		new DEBamPass();
	}
}
