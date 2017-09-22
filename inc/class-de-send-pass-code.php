<?php
/**
 * Factory Completed Order Email
 *
 * An email sent to the factory when a new order is completed.
 *
 * @class 		WC_Process_Order_Email
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 		WC_Email
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('DE_Send_Pass_Code_Email')) {
	
	class DE_Send_Pass_Code_Email extends WC_Email
	{
		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->id = 'de_send_pass_code';
			
			$this->title = __("Send pass code", "debampass");

			$this->description = __("Send an email with a code for customers who have purshased a pass.", "debampass");

			$this->heading = __("Your code", "debampass");
			$this->subject = __("Your code", "debampass");

			$this->template_html = 'emails/send-pass-code.php';
			$this->template_plain = 'emails/plain/send-pass-code.php';

			// add_action('woocommerce_order_status_pending_to_processing_notification', array($this, 'trigger'));
			// add_action('woocommerce_order_status_on-hold_to_processing_notification', array($this, 'trigger'));
			// add_action('woocommerce_order_status_changed', array($this, 'triggerMail'), 99, 3);
			add_action('debampass_order_completed', array($this, 'triggerMail'), 99, 3);

			parent::__construct();
			
			
			$this->form_fields['email_content'] = array(
				"title" => __("Email content", "debampass"),
				"type" => "textarea",
				"description" => __("The textual content of the email. Need to contain the string ###code###.", "debampass"),
				"default" => "",
			);
			
			// echo "yo : ". $this->get_option('email_content');
		}
		
		/*public function setRecipient($recipient)
		{
			$this->recipient = $recipient;
		}*/

		/**
		 * trigger function.
		 *
		 * @access public
		 * @return void
		 */
		public function triggerMail($orderId, $customerId, $code)
		{
			$user = get_user_by('id', $customerId);
			
			if ($user) {
				$this->recipient = $user->data->user_email;
				// mail("fredericl@digital-effervescence.com", "order : ". $orderId, "triggered : ". $test);
				
				$this->passCode = $code;
				
				$this->send($this->recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
			}
		}

		/**
		 * Get content html.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_html()
		{
			$emailContent = $this->get_option('email_content');
			$emailContent = str_replace("###code###", $this->passCode, $emailContent);
			
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading' => $this->get_heading(),
					'email_content' => $emailContent,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'			=> $this
				),
				'',
				untrailingslashit(dirname(DEBAMPASS)) .'/templates/'
			);
		}

		/**
		 * Get content plain.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain()
		{
			$emailContent = $this->get_option('email_content');
			$emailContent = str_replace("###code###", $this->passCode, $emailContent);
			
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading' => $this->get_heading(),
					'email_content' => $emailContent,
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'			=> $this
				),
				'',
				untrailingslashit(dirname(DEBAMPASS)) .'/templates/'
			);
		}
	}
}

// return new DE_Send_Pass_Code_Email();
