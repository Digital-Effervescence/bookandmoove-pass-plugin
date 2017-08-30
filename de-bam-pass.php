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

if (!class_exists('DEBamPass'))
{
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
			// add_action('wp_loaded', array($this, 'wpLoaded')); // TMP
			
			add_action('wp_enqueue_scripts', array($this, 'loadStylesScripts'));
			
			// add_action('woocommerce_checkout_fields', array($this, 'woocommerCheckoutForm'));
			add_action('woocommerce_checkout_process', array($this, 'woocommerCheckoutFormFieldProcess'));
			
			// Ajax popin inscription/connexion
			add_action('wp_ajax_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
			add_action('wp_ajax_nopriv_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
			
			// Ajax popin 'entrer code'
			add_action('wp_ajax_enterCodePopin', array($this, 'enterCodePopin'));
			add_action('wp_ajax_nopriv_enterCodePopin', array($this, 'enterCodePopin'));
			
			// Ajax validation popin 'entrer code'
			add_action('wp_ajax_enterCodeValidation', array($this, 'enterCodeValidation'));
			add_action('wp_ajax_nopriv_enterCodeValidation', array($this, 'enterCodeValidation'));
			
			// Ajax popin 'form registration'
			add_action('wp_ajax_formRegistrationPopin', array($this, 'formRegistrationPopin'));
			add_action('wp_ajax_nopriv_formRegistrationPopin', array($this, 'formRegistrationPopin'));
			
			
			add_filter('wp_footer', array($this, 'deBamPassHtmlContainer'));
		}
		
		// Installation du plugin
		public function install()
		{
			global $wpdb;
			
			// On veut créer une table en BDD
			$tableName = $wpdb->prefix ."debampass";
			
			$charsetCollate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $tableName (
			  id bigint(20) NOT NULL AUTO_INCREMENT,
			  membership_plan bigint(20) NOT NULL,
			  user_id bigint(20),
			  code int(11) NOT NULL,
			  date_end_code_active datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  PRIMARY KEY  (id)
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
			
			$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
			
			$meta = get_post_meta(12166, '_product_ids');
			
			// $membershipPlan = wc_memberships_get_membership_plan(12166);
			// $membershipPlan = wc_memberships_get_membership_plans();
			echo "<pre>";
			print_r($meta);
			echo "</pre>";
			
			// $woocommerce->cart->add_to_cart(12566);
			// echo "yo123";
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
				$queryGetPass .= "AND code = %d";
				
				$result = $wpdb->query($wpdb->prepare($this->getCodePassEntryQuery(), $_POST['enter-code-pass-code']));
				
				$isOk = 0;
				$message = __("The entered code is incorrect", "debampass");
				if ($result == 1) { // On a 1 pass non activé
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
			$queryGetPass .= "AND code = %d";
			
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
				$woocommerce->cart->empty_cart(); // On vide le panier de l'utilisateur
				
				$metaMembershipPlan = get_post_meta($resultPass[0]->membership_plan, '_product_ids');
				// $woocommerce->cart->add_to_cart(12566);
				
				if (isset($metaMembershipPlan[0]) && isset($metaMembershipPlan[0][0])) {
					$woocommerce->cart->add_to_cart($metaMembershipPlan[0][0]);
					
					echo json_encode(
						array(
							'status' => 'success',
							'data' => $this->templates->get_template_part('popin', 'form-enter-code', true, true),
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
		}
		
		public function loginRedirect($redirect_to, $request, $user)
		{
			return home_url() ."?de-bam=ec";
		}
		
		
		// Gestion du formulaire de commande d'un Pass
		public function woocommerCheckoutForm($fields)
		{
			$fields['order']['de_bam_pass']['type'] = "text";
			$fields['order']['de_bam_pass']['label'] = __("Activate my BookandMoove code", "debampass");
			$fields['order']['de_bam_pass']['placeholder'] = __("Enter your card code", "debampass");
			
			return $fields;
		}
		
		public function woocommerCheckoutFormFieldProcess()
		{
			// global $woocommerce;
			
			// $checkout_url = $woocommerce->cart->get_checkout_url();
			
			// echo "url : ". $checkout_url;
			// wc_add_notice(__('Please enter something into this new shiny field.'), 'error');
			// echo "test ma gueule : ". $_POST['de_bam_pass'];
			// die();
		}
	}
}

new DEBamPass();
