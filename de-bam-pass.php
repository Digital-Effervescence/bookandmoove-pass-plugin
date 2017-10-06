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
	exit();
}

if (!defined('DEBAMPASS')) {
	define('DEBAMPASS', __FILE__);
}

if (!defined('DEBAMPASSPATH')) {
	define('DEBAMPASSPATH', basename(dirname(__FILE__)) .'/'. basename(__FILE__));
}


require dirname(DEBAMPASS) .'/inc/Gamajo_Template_Loader.php';
require dirname(DEBAMPASS) .'/inc/PW_Template_Loader.php';

require dirname(DEBAMPASS) .'/inc/class-de-list-table.php';
require dirname(DEBAMPASS) .'/inc/class-de-csv-generator.php';

require dirname(DEBAMPASS) .'/inc/Tools.php';


// Seulement si les plugins 'WooCommerce' et 'WooCommerce Membership' sont actifs
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && in_array('woocommerce-memberships/woocommerce-memberships.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if (!class_exists('DEBamPass')) {
		
		class DEBamPass
		{
			private $templates;
			
			private $codeValidationNbTryMax = 5;
			
			private $timeSpentDeleteOldExport = 86400; // Durée depuis laquelle on supprime les anciens exports CSV (en secondes) (1 jour)
			
			
			public function __construct()
			{
				date_default_timezone_set(ini_get('date.timezone'));
				
				register_activation_hook(__FILE__, array($this, 'install'));
				
				
				$this->templates = new PW_Template_Loader(plugin_dir_path(__FILE__));
				
				
				// add_filter('show_admin_bar', '__return_false'); // TMP
				
				$this->loadTranslations();
				
				add_action('init', array($this, 'init'));
				
				add_filter('wp_footer', array($this, 'deBamPassHtmlContainer'));
				
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
				
				
				add_filter('woocommerce_checkout_fields', array($this, 'customOverrideCheckoutFields'));
				
				
				// Page 'Mon compte'
				// add_filter('wc_memberships_my_memberships_column_names', array($this, 'myAccountOrders'));
				// add_action('wc_memberships_my_memberships_column_code', array($this, 'myAccountCodeColumn'));
				add_action('wc_memberships_before_my_memberships', array($this, 'showPassCode'));
				
				add_filter('woocommerce_locate_template', array($this, 'woocommerceLocateTemplates'), 20, 3);

				
				
				// Admin
				add_action('admin_menu', array($this, 'deBamPassAdminMenu'));
				
				
				add_action('woocommerce_order_status_changed', array($this, 'orderStatusChange'), 99, 3);
				
				add_filter('woocommerce_email_classes', array($this, 'addSendPassCodeWoocommerceEmail'));
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
				  order_id BIGINT(20),
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
				
				// (pas sûr que ce soit utile)
				if (function_exists('wc_memberships')) { 
					remove_action('woocommerce_order_status_processing', array(wc_memberships(), 'grant_membership_access'), 11);
				}
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
				// Sécurité, on définit un certain nombre d'essais
				if (!session_id()) {
					session_start();
				}
				
				$_SESSION['codeValidationNbTry'] = 0;
				
				$this->templates->get_template_part('popin', 'enter-code');
				die();
			}
			
			// Méthode appelée en ajax, lorsque l'on entre son code pour son pass
			public function enterCodeValidation()
			{
				global $wpdb;
				
				if (!session_id()) {
					session_start();
				}
				
				if (isset($_POST['enter-code-pass-code']) && isset($_SESSION['codeValidationNbTry'])) {
					$_SESSION['codeValidationNbTry'] = $_SESSION['codeValidationNbTry'] + 1;
					
					if ($_SESSION['codeValidationNbTry'] <= $this->codeValidationNbTryMax) {
						$tableName = $wpdb->prefix ."debampass";
						
						$result = $wpdb->get_results($wpdb->prepare($this->getCodePassEntryQuery(), $_POST['enter-code-pass-code']));
						
						$orderId = "null";
						$isOk = 0;
						$message = __("The entered code is incorrect or is no longer active", "debampass");
						if (count($result) == 1) { // On a bien 1 pass non activé
							$isOk = 1;
							$message = "";
							
							if ($result[0]->order_id) {
								$orderId = $result[0]->order_id;
							}
						}
						
						echo json_encode(
							array(
								'status' => 'success',
								'passExists' => $isOk,
								'orderId' => $orderId,
								'message' => $message,
								'loggued' => is_user_logged_in(),
							)
						);
					} else { // Trop d'essais -> on bloque
						unset($_SESSION['codeValidationNbTry']);
						
						echo json_encode(
							array(
								'status' => 'error',
								'message' => __("Maximum number of attempts reached", "debampass"),
								'log' => "Nombre d'essais dépassés.",
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
				
				exit();
			}
			
			private function getCodePassEntryQuery()
			{
				global $wpdb;
				
				$tableName = $wpdb->prefix ."debampass";
				
				$queryGetPass = "";
				$queryGetPass .= "SELECT id, membership_plan, user_id, order_id, code, date_end_code_active, created_at, updated_at ";
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
				
				if (count($resultPass) == 1) {
					// On enregistre l'état du panier de l'utilisateur
					if (!session_id()) {
						session_start();
					}
					
					$_SESSION['cart_state'] = $woocommerce->cart->get_cart();
					
					$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
					
					$metaMembershipPlan = get_post_meta($resultPass[0]->membership_plan, '_product_ids');
					
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
								'log' => "Pas de produit trouvé dans les metas du Membership Plan.",
							)
						);
					}
					
					exit();
				} else {
					echo json_encode(
						array(
							'status' => 'error',
							'message' => __("An error has occurred", "debampass"),
							'log' => "Aucun ou plus de 1 résultat.",
						)
					);
					exit();
				}
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
				
				exit();
			}
			
			// Finalisation de l'activation du pass
			public function finalizeOrder()
			{
				global $woocommerce;
				global $wpdb;
				
				if (isset($_POST['idOrder']) && $_POST['deBamPassCode']) { // Si on a bien les données $_POST
					if (get_current_user_id() != 0) { // Si on a bien un utilisateur connecté
						$order = new WC_Order($_POST['idOrder']); // On récupère la commande
						
						// On met à jour dans la BDD l'entrée du pass de la table 'debampass' pour indiquer que le pass est activé
						$tableName = $wpdb->prefix ."debampass";
						
						$queryActivatePass = "";
						$queryActivatePass .= "UPDATE $tableName ";
						$queryActivatePass .= "SET user_id = %d, ";
						$queryActivatePass .= "order_id = %d, ";
						$queryActivatePass .= "updated_at = NOW() ";
						$queryActivatePass .= "WHERE user_id IS NULL ";
						$queryActivatePass .= "AND date_end_code_active >= DATE(NOW()) ";
						$queryActivatePass .= "AND code = %s";
						
						$resultUpdatePass = $wpdb->query($wpdb->prepare($queryActivatePass, get_current_user_id(), $_POST['idOrder'], $_POST['deBamPassCode']));
						
						if (false === $resultUpdatePass || $resultUpdatePass != 1) {
							$order->update_status('processing', __("Pass activation error", "debampass")); // On repasse le statut de la commande à 'En cours'
							
							echo json_encode(
								array(
									'status' => 'error',
									'message' => __("An error has occurred", "debampass"),
									'log' => "Erreur lors de l'activation du pass en BDD.",
								)
							);
						} else {
							if ($order->get_status() != "completed") {
								$order->update_status('completed', __("Pass activated", "debampass")); // On met à jour le statut de la commande, pour la finaliser
							}
						
							// On veut récupérer les données du Membership Plan
							$resultPass = $wpdb->get_results($wpdb->prepare($this->getCodePassEntryActivatedQuery(), $_POST['deBamPassCode']));
							if (count($resultPass) == 1) {
								$membershipPlan = wc_memberships_get_membership_plan($resultPass[0]->membership_plan);
								
								$membership = wc_memberships_get_user_membership(get_current_user_id(), $membershipPlan->id);
								$membership->activate_membership();
								
								// On réapplique le contenu du panier qu'il y avait avant l'activation du pass
								$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
								
								if (!session_id()) {
									session_start();
								}
								
								if (isset($_SESSION['cart_state'])) {
									foreach ($_SESSION['cart_state'] as $cartItemKey => $cartItemValues) {
										$id = $cartItemValues['product_id'];
										$quantity = $cartItemValues['quantity'];
										
										$woocommerce->cart->add_to_cart($id, $quantity);
									}
									
									unset($_SESSION['cart_state']);
								}

								
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
										'log' => "Erreur lors de la récupération du Membership Plan.",
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
				
				exit();
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
				wp_localize_script('de_bam_script', 'loginUrl', wp_login_url());
			}
			
			// Chargement des css et js dans l'admin
			public function loadStylesScriptsAdmin()
			{
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('de_bam_script_admin', plugin_dir_url(__FILE__) .'js/script.js', array('jquery'), '1.0', true);
				
				wp_enqueue_style('de_bam_style_admin', plugin_dir_url(__FILE__) .'css/style.css', array(), false, 'screen');
				
				wp_register_style('jquery-ui', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css');
				wp_enqueue_style('jquery-ui');
			}
			
			public function loginRedirect($redirect_to, $request, $user)
			{
				$redirectUrl = home_url() ."?de-bam=ec&code=". $_GET['code'];
				
				if ($_GET['orderid']) {
					$redirectUrl .= "&orderid=". $_GET['orderid'];
				}
				
				return $redirectUrl;
			}
			
			
			public function customOverrideCheckoutFields($fields)
			{
				// Suppression des champs 'Entreprise' et 'État/Comté' du formulaire de commande
				unset($fields['billing']['billing_company']);
				unset($fields['billing']['billing_state']);
				
				unset($fields['shipping']['shipping_company']);
				unset($fields['shipping']['shipping_state']);
				
				return $fields;
			}
			
			
			
			// Ajout d'une colonne 'Code' sur la page 'Mon compte' (pas utilisé finalement
			/*public function myAccountOrders($columns)
			{
				$columns = array_splice($columns, 0, count($columns) - 1, true) + array('code' => __("Code", "debampass")) + array_splice($columns, count($columns) - 1, count($columns), true);
				
				return $columns;
			}
			// Gestion du contenu de la colonne 'Code' de la page 'Mon compte' (pas utilisé finalement
			public function myAccountCodeColumn($userMembership)
			{
				global $wpdb;
				
				$tableName = $wpdb->prefix ."debampass";
				
				$queryGetPass = "";
				$queryGetPass .= "SELECT code ";
				$queryGetPass .= "FROM $tableName ";
				$queryGetPass .= "WHERE membership_plan = %d ";
				$queryGetPass .= "AND user_id = %d";
				
				$results = $wpdb->get_results($wpdb->prepare($queryGetPass, $userMembership->plan_id, $userMembership->user_id));
				
				if (isset($results[0]) && isset($results[0]->code)) {
					echo $results[0]->code;
				} else {
					echo "";
				}
			}*/
			
			/*public function myAccountStatusColumn($userMembership)
			{
				echo "yo";
				echo "<pre>";
				print_r($userMembership);
				echo "</pre>";
			}*/
			
			/*public function myAccountTable($columns)
			{
				// echo "<pre>";
				// print_r($columns);
				// echo "</pre>";
				
				return $columns;
			}*/
			
			// Affichage d'un bloc avec le numéro du code du pass courant
			public function showPassCode()
			{
				$activeMemberships = wc_memberships_get_user_active_memberships();
				$code = "";
				
				if ($activeMemberships) {
					foreach ($activeMemberships as $aMembership) {
						// On vérifie que la date de fin n'est pas dépassée
						$dateOk = true;
						if (trim($aMembership->get_end_date()) != "") {
							$currentDate = new DateTime();
							$memberShipEndDate = DateTime::createFromFormat('Y-m-d H:i:s', $aMembership->get_end_date());
							
							if ($currentDate > $memberShipEndDate) {
								$dateOk = false;
							}
						}
						
						if ($aMembership->status == "wcm-active" && $dateOk) {
							global $wpdb;
					
							$tableName = $wpdb->prefix ."debampass";
							
							$queryGetPass = "";
							$queryGetPass .= "SELECT code ";
							$queryGetPass .= "FROM $tableName ";
							$queryGetPass .= "WHERE membership_plan = %d ";
							$queryGetPass .= "AND user_id = %d";
							
							$code = $wpdb->get_var($wpdb->prepare($queryGetPass, $aMembership->plan_id, $aMembership->user_id));
							
							if ($code != "") {
								/*// echo "id : ". get_post_type(12166);
								
								// $rules = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules(28403);
								// $rules = wc_memberships()->get_rules_instance()->get_rules(array('object_id' => 12166, 'content_type' => 'wc_membership_plan'));
								
								// if ($rules) {
									// echo "yes";
									echo "<pre>";
									// print_r($aMembership);
									print_r($aMembership->plan);
									// print_r($rules);
									echo "</pre>";
								// } else {
									// echo "no";
								// }*/
								
								
								
								break;
							}
						}
					}
				}
				
				if ($code != "") {
					include 'templates/elements/my-account-card.php';
				}
			}
			
			// Surcharge des template Woocommerce
			public function woocommerceLocateTemplates($template, $templateName, $templatePath)
			{
				if ($templateName == "myaccount/my-memberships.php") {
					$template = dirname(DEBAMPASS) .'/templates/'. $templateName;
				}
				
				return $template;
			}
			
			
			
			public function deBamPassAdminMenu()
			{
				add_submenu_page("woocommerce", __("Pass generator", "debampass"), __("Pass generator", "debampass"), "manage_options", "woocommerce-debampass-generator", array($this, "passGenerator"));
				
				add_submenu_page("woocommerce", __("Pass viewer", "debampass"), __("Pass viewer", "debampass"), "manage_options", "woocommerce-debampass-viewer", array($this, "passesGenerated"));
			}
			
			// Page admin de génération de pass
			public function passGenerator()
			{
				if (!current_user_can('manage_options')) {
					wp_die(__("You are not allowed to access this page."));
				}
				
				
				// Messages de notification
				$adminNotices = array();
				
				// Création du dossier qui contiendra les CSV
				$directoryName = "/exports/";
				$uploadExportsPath = realpath(dirname(__FILE__)) . $directoryName;
				if (!$this->exportDirectoryCheck($uploadExportsPath)) {
					array_push(
						$adminNotices,
						array(
							'type' => 'error',
							'isDismissible' => false,
							'message' =>  __("Unable to create the directory containing the CSV exports on the server. Please temporarily grant sufficient rights to the 'de-bam-pass' directory in 'wp-content/plugins/' and reload the page.", "debampass"),
						)
					);
				} else {
					// On supprime les anciens fichiers d'export
					$filesExport = array_diff(scandir($uploadExportsPath, SCANDIR_SORT_ASCENDING), array('.', '..'));
					foreach ($filesExport as $aFileExport) {
						if (filectime($uploadExportsPath . $aFileExport) < time() - $this->timeSpentDeleteOldExport) {
							unlink($uploadExportsPath . $aFileExport);
						}
					}
				}
				
				$nbMinPass = 1;
				$nbMaxPass = 5000;
				$membershipPlansTmp = wc_memberships_get_membership_plans();
				$membershipPlans = array();
				
				foreach ($membershipPlansTmp as $aMembershipPlan) {
					/*$metaMembershipPlan = get_post_meta($aMembershipPlan->id, '_product_ids');
					if (isset($metaMembershipPlan[0]) && isset($metaMembershipPlan[0][0])) {*/
					if ($aMembershipPlan->has_products()) {
						array_push(
							$membershipPlans,
							array(
								'id' => $aMembershipPlan->id,
								'name' => $aMembershipPlan->name,
							)
						);
					}
				}
				
				
				// Validation du formulaire de génération de pass
				if (isset($_POST['pass-generation-submit'])) {
					$errors = array();
					
					$membershipPlan;
					
					// Membership Plan
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
								if ($_POST['pass-generation-pass-number'] < $nbMinPass) { // Trop petit
									$errors['pass-generation-pass-number'] = array(
										'message' => sprintf(__("Must be greater than or equal to %d", "debampass"), $nbMinPass),
									);
								}
							}
						}
					}
					
					
					// Formulaire valide
					if (empty($errors)) {
						/*global $wpdb;
						
						// Variables de l'algo
						$step = 40005683; // La valeur que l'on ajoute au code courant pour créer un nouveau code
						
						$max = 999999999; // Nombre maximum de codes possibles
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
							
							if ($queryInsertPass != "") {
								$queryString = $queryInsertPassTitle . $queryInsertPass;
								$resultInsertPass = $wpdb->query($queryString);
								$nbInsertedRows += $resultInsertPass;
							}
							
							array_push(
								$adminNotices,
								array(
									'type' => 'success',
									'isDismissible' => true,
									'message' =>  sprintf(__("%d pass inserted successfully", "debampass"), $nbInsertedRows),
								)
							);
							
							array_push(
								$adminNotices,
								array(
									'type' => 'success',
									'isDismissible' => false,
									'message' =>  __("CSV file :", "debampass") .' <a href="'. plugins_url($directoryName . $fileName, __FILE__) .'" target="_blank">'. $fileName .'</a>',
								)
							);
						}*/
						
						// $generationErrors = array();
						
						$generationReturnedData = $this->generatePasses($_POST['pass-generation-pass-number'], $_POST['pass-generation-codes-expiration-date'], $membershipPlan, null, array(), $adminNotices, $directoryName, $uploadExportsPath);
						$generationErrors = $generationReturnedData['generationErrors'];
						$adminNotices = $generationReturnedData['adminNotices'];
					}
				}
				
				include "templates/admin/page-passes-generator.php";
			}
			
			private function generatePasses($nbPassesToGenerate, $expirationDate, $membershipPlan, $orderId = null, $generationErrors = array(), $adminNotices = array(), $directoryName = null, $uploadExportsPath = null)
			{
				global $wpdb;
				
				// Variables de l'algo
				$step = 40005683; // La valeur que l'on ajoute au code courant pour créer un nouveau code
				
				$max = 999999999; // Nombre maximum de codes possibles
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
				if (($max - $resultNbPass) < $nbPassesToGenerate) {
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
					if ($uploadExportsPath !== null) {
						$fileName = date('Y') ."_". date('m') ."_". date('d') ."-". date('H') ."_". date('i') ."_". date('s') ."-pass_generation.csv";
						$filePath = $uploadExportsPath . $fileName;
						
						$csvTitle = array(__("Code", "debampass"), __("Expiration date", "debampass"), __("Membership Plan", "debampass"));
						
						$handle = fopen($filePath, 'w');
						fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
						fputcsv($handle, $csvTitle, ';'); // Titre
					}
					
					
					$queryInsertPassTitle = "";
					$queryInsertPassTitle .= "INSERT INTO $tableName (order_id, membership_plan, code, date_end_code_active, created_at) ";
					$queryInsertPassTitle .= "VALUES ";
					
					
					if ($orderId == null) {
						$orderId = "NULL";
					}
					
					
					// On va générer les pass (autant que défini dans $_POST['pass-generation-pass-number'])
					$codeString;
					$queryInsertPass = "";
					$nbInsertedRows = 0;
					for ($i = 0; $i < $nbPassesToGenerate; $i++) {
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
						
						$queryInsertPass .= "(". $orderId .", ". $membershipPlan->id .", '". $codeString ."', '". $expirationDate ."', NOW())";
						
						if ($uploadExportsPath !== null) {
							// fputcsv($handle, array($codeString, $expirationDate, $membershipPlan->name), ';');
							fputcsv($handle, array('"'. $codeString .'"', $expirationDate, $membershipPlan->name), ';');
						}
						
						if ($i % $batchSize == 0) {
							$queryString = $queryInsertPassTitle . $queryInsertPass;
							$resultInsertPass = $wpdb->query($queryString);
							$nbInsertedRows += $resultInsertPass;
							
							$queryInsertPass = "";
						}
					}
					
					if ($uploadExportsPath !== null) {
						fclose($handle);
					}
					
					if ($queryInsertPass != "") {
						$queryString = $queryInsertPassTitle . $queryInsertPass;
						$resultInsertPass = $wpdb->query($queryString);
						$nbInsertedRows += $resultInsertPass;
					}
					
					array_push(
						$adminNotices,
						array(
							'type' => 'success',
							'isDismissible' => true,
							'message' =>  sprintf(__("%d pass inserted successfully", "debampass"), $nbInsertedRows),
						)
					);
					
					if ($uploadExportsPath !== null) {
						array_push(
							$adminNotices,
							array(
								'type' => 'success',
								'isDismissible' => false,
								'message' =>  __("CSV file :", "debampass") .' <a href="'. plugins_url($directoryName . $fileName, __FILE__) .'" target="_blank">'. $fileName .'</a>',
							)
						);
					}
					
					return array(
						'nbInsertedRows' => $nbInsertedRows,
						'adminNotices' => $adminNotices,
						'generationErrors' => $generationErrors,
						'lastGeneratedCode' => $codeString,
					);
				}
			}
			
			// Page admin de visualisation des pass générés
			public function passesGenerated()
			{
				$generatedPassListTable = new DE_List_Table_Pass_Generated();
				
				$doAction = $generatedPassListTable->current_action();
				
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
				
				if ($doAction) {
					if ($doAction == 'download-csv') { // Génération d'un CSV
						$generatedPassListTable->initializeQueries(); // On initialise les requêtes permettant de retourner les résultats (avec les paramètres courants) et leur nombre
						
						// On récupère la requête retournant les résultats ainsi que ses paramètres
						$querySelectGeneratedPasses = $generatedPassListTable->getQuerySelectGeneratedPasses();
						$queryParams = $generatedPassListTable->getQueryParams();
						
						// On veut maintenant générer un CSV contenant ces résultats
						$directoryName = "/exports/";
						$uploadExportsPath = realpath(dirname(__FILE__)) . $directoryName;
						
						$fileName = date('Y') ."_". date('m') ."_". date('d') ."-". date('H') ."_". date('i') ."_". date('s') ."-pass_export.csv";
						$filePath = $uploadExportsPath . $fileName;
						
						$csvGenerator = new DE_CSV_Generator($querySelectGeneratedPasses, $queryParams);
						$csvGenerator->setBatchSize(500);
						
						if (!session_id()) {
							session_start();
						}
						if (!isset($_SESSION['adminNotices'])) {
							$_SESSION['adminNotices'] = array();
						}
						
						if ($csvGenerator->generateCSV($filePath)) {
							array_push(
								$_SESSION['adminNotices'],
								array(
									'type' => 'success',
									'isDismissible' => false,
									'message' => __("CSV file :", "debampass") .' <a href="'. plugins_url($directoryName . $fileName, __FILE__) .'" target="_blank">'. $fileName .'</a>',
								)
							);
						} else {
							array_push(
								$_SESSION['adminNotices'],
								array(
									'type' => 'success',
									'isDismissible' => false,
									'message' => __("An error occured while generating the CSV file", "debampass"),
								)
							);
						}
					} elseif ($doAction == "delete") { // Suppression de pass de la BDD
						if (isset($_GET['pass'])) {
							global $wpdb;
							
							$tableName = $wpdb->prefix ."debampass";
							
							$queryDelete = "";
							$queryDelete .= "DELETE FROM $tableName ";
							$queryDelete .= "WHERE user_id IS NULL ";
							$queryDelete .= "AND updated_at IS NULL ";
							$queryDelete .= "AND date_end_code_active < DATE(NOW()) ";
							$queryDelete .= "AND id = %d";
							
							$nbPassDeleted = 0;
							foreach ($_GET['pass'] as $aPassId) {
								$resultDelete = $wpdb->query($wpdb->prepare($queryDelete, $aPassId));
								$nbPassDeleted += $resultDelete;
							}
							
							if (!session_id()) {
								session_start();
							}
							if (!isset($_SESSION['adminNotices'])) {
								$_SESSION['adminNotices'] = array();
							}
							
							array_push(
								$_SESSION['adminNotices'],
								array(
									'type' => 'success',
									'isDismissible' => true,
									'message' => sprintf(__("%d passes successfully deleted.", "debampass"), $nbPassDeleted),
								)
							);
						}
					}
					
					wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce', 'action', 'action2', 'pass'), wp_unslash($_SERVER['REQUEST_URI'])));
					exit();
				} elseif (!empty($_REQUEST['_wp_http_referer'])) {
					wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI'])));
					exit();
				}
				
				$generatedPassListTable->prepare_items();
				
				// On ne récupère que les Membership Plans qui ont été associés à des pass
				global $wpdb;
				
				$tableDeBamPass = $wpdb->prefix ."debampass";
				$tablePosts = $wpdb->prefix ."posts";
				
				$queryDistinctMembershipPlan = "";
				$queryDistinctMembershipPlan .= "SELECT dbp.membership_plan, p.post_title ";
				$queryDistinctMembershipPlan .= "FROM $tableDeBamPass dbp ";
				$queryDistinctMembershipPlan .= "LEFT JOIN $tablePosts p ON dbp.membership_plan = p.ID ";
				$queryDistinctMembershipPlan .= "GROUP BY dbp.membership_plan ";
				$queryDistinctMembershipPlan .= "ORDER BY p.post_title";
				
				$membershipPlans = $wpdb->get_results($queryDistinctMembershipPlan);
				
				// Messages de notification
				$adminNotices = array();
				if (isset($_SESSION['adminNotices'])) {
					$adminNotices = $_SESSION['adminNotices'];
					unset($_SESSION['adminNotices']);
				}
				
				include "templates/admin/page-passes-generated.php";
			}
			
			
			public function orderStatusChange($orderId, $oldStatus, $newStatus)
			{
				$order = new WC_Order($orderId); // On récupère la commande
				$items = $order->get_items();
				$userId = $order->get_user_id();
				
				// On a bien un utilisateur lié à la commande
				if ($userId != 0) {
					$membershipPlans = wc_memberships_get_membership_plans(); // On récupère tous les MembershipPlans existants
					
					foreach ($items as $anItem) {
						foreach ($membershipPlans as $aMembershipPlan) {
							if ($aMembershipPlan->has_products()) {
								// Ce MembershipPlan est associé au produit courant
								if ($aMembershipPlan->has_product($anItem['product_id'])) {
									$membership = wc_memberships_get_user_membership($userId, $aMembershipPlan->id);
									
									// Si on ne vient pas de faire la commande
									if ($membership && !($oldStatus == "pending" && $newStatus == "on-hold")) {
										$membership->pause_membership(); // On met en 'pause' le membership de l'utilisateur qui a fait la commande
										// Tools::WriteLog($membership);
										
										// Commande terminée
										if ($newStatus == 'completed' && $oldStatus != 'completed') {
											global $wpdb;
					
											$tableDeBamPass = $wpdb->prefix ."debampass";
											
											// On vérifie que l'on n'a pas déjà inséré un nouveau pass en BDD
											$queryCheckOrder = "";
											$queryCheckOrder .= "SELECT id ";
											$queryCheckOrder .= "FROM $tableDeBamPass ";
											$queryCheckOrder .= "WHERE order_id = %d";
											
											$result = $wpdb->query($wpdb->prepare($queryCheckOrder, $orderId));
						
											if ($result == 0) { // On n'en a pas déjà
												// On génère un nouveau pass
												$expirationDate = new DateTime();
												$expirationDate->modify('+1 month');
												
												$generationReturnedData = $this->generatePasses(1, $expirationDate->format('Y-m-d'), $membership->plan, $orderId); // On génère un pass
												
												do_action('debampass_order_completed', $orderId, $userId, $generationReturnedData['lastGeneratedCode']);
											}
										}
									}
								}
							}
						}
					}
				}
			}
			
			
			public function addSendPassCodeWoocommerceEmail($emailClasses)
			{
				require('inc/class-de-send-pass-code.php');
				
				$emailClasses['DE_Send_Pass_Code_Email'] = new DE_Send_Pass_Code_Email();
				
				return $emailClasses;
			}
			
			
			
			private function exportDirectoryCheck($path)
			{
				$isDirectoryCreated = true;
				
				// Dossier d'export des CSV
				if (!file_exists($path)) {
					$isDirectoryCreated = mkdir($path, 0775, true);
					
					// (Pas besoin en fait)
					// $handle = fopen($path .'index.php', 'w');
					// fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
					// fwrite($handle, "<?php // Munen");
					// fclose($handle);
				}
				
				return $isDirectoryCreated;
			}
		}
		
		
		new DEBamPass();
	}
}
