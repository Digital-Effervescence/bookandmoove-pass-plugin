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
			$this->templates = new PW_Template_Loader(plugin_dir_path(__FILE__));
			
			add_filter('show_admin_bar', '__return_false'); // TMP
			
			$this->loadTranslations();
			
			add_action('init', array($this, 'init'));
			
			add_action('wp_enqueue_scripts', array($this, 'loadStylesScripts'));
			
			add_action('woocommerce_checkout_fields', array($this, 'woocommerCheckoutForm'));
			
			// Ajax popin inscription/connexion
			add_action('wp_ajax_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
			add_action('wp_ajax_nopriv_loadRegistrationPopin', array($this, 'loadRegistrationPopin'));
			
			// Ajax popin 'entrer code'
			add_action('wp_ajax_enterCodePopin', array($this, 'enterCodePopin'));
			add_action('wp_ajax_nopriv_enterCodePopin', array($this, 'enterCodePopin'));
			
			add_filter('wp_footer', array($this, 'deBamPassHtmlContainer'));
		}
		
		public function init()
		{
			// On a le paramètre indiquant que l'on est sur la page de login et que l'on souhaite afficher la popin permettant d'entrer le code après l'authentification
			if (isset($_GET['de-bam']) && $_GET['de-bam'] == "lo") {
				add_filter('login_redirect', array($this, 'loginRedirect'), 10, 3);
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
			$this->templates->get_template_part('popin', 'enter-code');
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
			wp_enqueue_style('de_bam_style', plugin_dir_url(__FILE__) .'css/style.css', array(), false, 'screen');
			
			wp_enqueue_script('de_bam_script', plugin_dir_url(__FILE__) .'js/script.js', array('jquery'), '1.0', true);
			wp_localize_script('de_bam_script', 'deBamPassPluginDirUrl', plugin_dir_url(__FILE__));
			wp_localize_script('de_bam_script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
		}
		
		public function loginRedirect($redirect_to, $request, $user)
		{
			return home_url() ."?de-bam=ec";
		}
		
		public function woocommerCheckoutForm($fields)
		{
			$fields['order']['de_bam_pass']['type'] = "text";
			$fields['order']['de_bam_pass']['label'] = __("Activate my BookandMoove code", "debampass");
			$fields['order']['de_bam_pass']['placeholder'] = __("Enter your card code", "debampass");
			
			return $fields;
		}
	}
}

new DEBamPass();
