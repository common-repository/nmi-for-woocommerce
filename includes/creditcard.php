<?php

 // Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'woocommerce_payment_token_deleted', 'woonswpclubz_woonmi_payment_token_deleted', 10, 2 );

/**
 * Delete token from NMI.
 */
function woonswpclubz_woonmi_payment_token_deleted( $token_id, $token ) {
	$gateway = new woonswpclubz_woonmi();

	if ( 'nmi-for-woocommerce' === $token->get_gateway_id() ) {
		$provider = $gateway->provider;

		$nmi_adr = $gateway->$provider . '?';

		$woonmi_args['security_key']          = $gateway->security_key;
		$woonmi_args['customer_vault']    = 'delete_customer';
		$woonmi_args['customer_vault_id'] = $token->get_token();

		$name_value_pairs = array();
		foreach ( $woonmi_args as $key => $value ) {
			$name_value_pairs[] = $key . '=' . urlencode( $value );
		}
		$gateway_values = implode( '&', $name_value_pairs );

		$response = wp_remote_post(
			$nmi_adr,
			array(
				'body'      => $gateway_values,
				'sslverify' => false,
				'timeout'   => 60,
			)
		);

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			parse_str( $response['body'], $response );
			if ( $response['response'] == '1' ) {
				return true;
			} else {
				if ( strpos( $response['responsetext'], __( 'Invalid Customer Vault Id', 'nmi-for-woocommerce' ) ) !== false ) {
					return false;
				} else {
					wc_add_notice( sprintf( __( 'Deleting card failed. %1$s-%2$s', 'nmi-for-woocommerce' ), $response['response_code'], $response['responsetext'] ), $notice_type = 'error' );

					return false;
				}
			}
		} else {
			wc_add_notice( __( 'There was error processing your request.', 'nmi-for-woocommerce' ), $notice_type = 'error' );

			return false;
		}
	}
}

class woonswpclubz_woonmi extends WC_Payment_Gateway_CC {

	public function __construct() {
		 global $woocommerce;
		$this->id           = 'nmi-for-woocommerce';
		$this->method_title = __( 'Network Merchants Inc - NMI CC', 'nmi-for-woocommerce' );
		$this->method_description = __( 'Accept payments seamlessly with eCheck using NMI For WooCommerce.' , 'nmi-for-woocommerce' );
		$this->icon         = apply_filters( 'woonswpclubz_woonmi_icon', '' );
		$this->has_fields   = true;
		$this->notify_url   = add_query_arg( 'wc-api', 'woonswpclubz_woonmi',home_url());

		$this->nmi         = 'https://secure.networkmerchants.com/api/transact.php';
		$this->alphacard   = 'https://secure.alphacardgateway.com/api/transact.php';
		$this->llms        = 'https://secure.llmsgateway.com/api/transact.php';
		$this->powerpay    = 'https://verifi.powerpay.biz/api/transact.php';
		$this->sbg         = 'https://secure.skybankgateway.com/api/transact.php';
		$this->mgg         = 'https://secure.merchantguygateway.com/api/transact.php';
		$this->durango     = 'https://secure.durango-direct.com/api/transact.php';
		$this->tnbci       = 'https://secure.tnbcigateway.com/api/transact.php';
		$this->payscape    = 'https://secure.payscapegateway.com/api/transact.php';
		$this->paylinedata = 'https://secure.paylinedatagateway.com/api/transact.php';
		$this->inspire     = 'https://secure.inspiregateway.net/api/transact.php';
		$this->safesave    = 'https://secure.safesavegateway.com/api/transact.php';
		$this->tranzcrypt  = 'https://secure.tranzcrypt.com/api/transact.php';
		$this->planetauth  = 'https://secure.planetauthorizegateway.com/api/transact.php';
		$this->cyogate     = 'https://secure.cyogate.net/api/transact.php';
		$this->fluidpay    = 'https://app.fluidpay.com/api/transact.php';
		$this->glacierpay   = 'https://secure.glacierpaygateway.com/api/transact.php';
		$this->supports    = array(
			'products',
			'default_credit_card_form',
			'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_payment_method_change',
		);

		$default_card_type_options = array(
			'VISA' => 'VISA',
			'MC'   => 'MasterCard',
			'AMEX' => 'American Express',
			'DISC' => 'Discover',
			'DC'   => 'Diner\'s Club',
			'JCB'  => 'JCB Card',
		);

		$this->card_type_options = apply_filters( 'woonswpclubz_woonmi_card_types', $default_card_type_options );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->title          		= $this->get_option( 'title' );
		$this->description    		= $this->get_option( 'description' );
		$this->security_key       	= $this->get_option( 'security_key' );
		$this->token_public_key     = $this->get_option( 'token_public_key' );
		$this->checkout_public_key	= $this->get_option( 'checkout_public_key' );
		$this->webhook_key			= $this->get_option( 'webhook_key' );
		$this->descriptor     		= $this->get_option( 'descriptor' );
		$this->descriptor_url 		= $this->get_option( 'descriptor_url' );
		$this->processor_id   		= $this->get_option( 'processor_id' );
		$this->dup_seconds    		= $this->get_option( 'dup_seconds' );
		$this->provider       		= $this->get_option( 'provider' );
		$this->transtype      		= $this->get_option( 'transtype' );
		$this->cardtypes      		= $this->get_option( 'cardtypes' );
		$this->saved_cards    		= 'yes' === $this->get_option( 'saved_cards' );
		$this->receipt        		= 'yes' === $this->get_option( 'receipt' );
		$this->inline_challenge     = 'yes' === $this->get_option( 'inline_challenge' );
		$this->receipt        		= 'yes' === $this->get_option( 'receipt' );
		$this->inline_card_fields	= 'yes' === $this->get_option( 'inline_card_fields' );
		$this->send_details   		= 'yes' === $this->get_option( 'send_details' );
		$this->debug          		= $this->get_option( 'debug' );


		if ( $this->provider == '' ) {
			$this->provider = 'nmi';
		}

		if ( $this->transtype == '' ) {
			$this->transtype = 'sale';
		}

		// Logs
		if ( $this->debug == 'yes' ) {
			$this->log = new WC_Logger();
		}

		add_action( 'woocommerce_api_woonswpclubz_woonmi', array( $this, 'successful_request' ) );
		//add_action( 'valid-woonmi-ipn-request', array( $this, 'successful_request' ) );
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		
		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_subscription_cancelled_' . $this->id, array( $this, 'cancelled_subscription' ), 10, 2 );
		}

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
	}

	/**
	 * cancelled_subscription function.
	 *
	 * @param float    $amount_to_charge the amount to charge
	 * @param WC_Order $renewal_order    a WC_Order object created to record the renewal payment
	 */
	public function cancelled_subscription( $order ) {
		// $profile_id = self::get_subscriptions_woonmi_id( $order );

		$profile_id = get_post_meta( $order->parent_id, 'NMI Subscriber ID', true );

		// Make sure a subscriptions status is active with NMI
		$woonmi_args['security_key']        = $this->security_key;
		$woonmi_args['recurring']       = 'delete_subscription';
		$woonmi_args['subscription_id'] = $profile_id;

		$name_value_pairs = array();
		foreach ( $woonmi_args as $key => $value ) {
			$name_value_pairs[] = $key . '=' . urlencode( $value );
		}
		$gateway_values = implode( '&', $name_value_pairs );

		$provider = $this->provider;

		$nmi_adr = $this->$provider . '?';

		$response = wp_remote_post(
			$nmi_adr,
			array(
				'body'      => $gateway_values,
				'sslverify' => false,
				'timeout'   => 60,
			)
		);

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			parse_str( $response['body'], $response );

			if ( $response['response'] == '1' ) {
				$order->add_order_note( __( 'Subscription cancelled with NMI', 'nmi-for-woocommerce' ) );
			} else {
				$order->add_order_note( __( 'There was error cancelling the Subscription with NMI', 'nmi-for-woocommerce' ) );
			}
		}
	}

	/**
	 * Returns a NMI Subscription ID/Recurring Payment Profile ID based on a user ID and subscription key.
	 *
	 * @since 1.1
	 */
	public static function get_subscriptions_woonmi_id( $order ) {
		$profile_id = get_post_meta( $order->id, 'NMI Subscriber ID', true );

		return $profile_id;
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {
		if ( ! in_array( get_option( 'woocommerce_currency' ), array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * get_icon function.
	 *
	 * @return string
	 */
	public function get_icon() {
		global $woocommerce;

		$icon = '';
		if ( $this->icon ) {
			// Default behavior
			$icon = '<img src="' . esc_url( $this->force_ssl( $this->icon ) ) . '" alt="' . esc_attr( $this->title ) . '" />';
		} elseif ( is_array( $this->cardtypes ) && !empty( $this->cardtypes ) ) {
			// Display icons for the selected card types
			$icon = '';
			foreach ( $this->cardtypes as $cardtype ) {
				if ( file_exists( plugin_dir_path( __FILE__ ) . '/assets/images/card-' . strtolower( $cardtype ) . '.png' ) ) {
					$icon .= '<img src="' . esc_url( $this->force_ssl( plugins_url( '/assets/images/card-' . strtolower( $cardtype ) . '.png', __FILE__ ) ) ) . '" alt="' . esc_attr( strtolower( $cardtype ) ) . '" />';
				}
			}
		} else {
			// Display a message if no credit card types are selected
			$message = __( 'Please select a credit card in WooCommerce => Payments.', 'nmi-for-woocommerce' );
			echo '<p>' . esc_html( $message ) . '</p>';
		}



		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	private function force_ssl( $url ) {
		if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		return $url;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {       ?>
		<h3><?php esc_html_e( 'Network Merchants Inc', 'nmi-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Network Merchants Inc - CC works by processing the Credit Card Payments on your site.', 'nmi-for-woocommerce' ); ?>
		</p>
		<table class="form-table">
			<?php
			if ( $this->is_valid_for_use() ) :

				// Generate the HTML For the settings form.
				$this->generate_settings_html(); else :

					?>
			<div class="inline error">
				<p><strong><?php esc_html_e( 'Gateway Disabled', 'nmi-for-woocommerce' ); ?></strong>:
					<?php esc_html_e( 'NMI does not support your store currency.', 'nmi-for-woocommerce' ); ?></p>
			</div>
					<?php

			  endif;
				?>
		</table>
		<!--/.form-table-->
		<?php
	}

	// End admin_options()

	/**
	 *  Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'    => __( 'Enable/Disable', 'nmi-for-woocommerce' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable Network Merchants Inc - CC', 'nmi-for-woocommerce' ),
				'default'  => 'yes',
				'desc_tip' => true,
			),
			'title'          => array(
				'title'       => __( 'Title', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'nmi-for-woocommerce' ),
				'default'     => __( 'Credit Card', 'nmi-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'nmi-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'nmi-for-woocommerce' ),
				'default'     => __( 'Pay via NMI; you can pay with your credit card.', 'nmi-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'security_key'       => array(
				'title'       => __( 'Private Security key', 'nmi-for-woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Please enter your Private Security key; this is needed in order to take payment.', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'token_public_key'       => array(
				'title'       => __( 'Public Tokenization Key', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter your Public Tokenization Key; this is needed in order to take payment.', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'checkout_public_key'       => array(
				'title'       => __( 'Public Checkout Key', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter your Public Checkout Key; this is needed in order to take payment.', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'webhook_key'       => array(
				'title'       => __( 'Webhooks Signing Key', 'nmi-for-woocommerce' ),
				'type'        => 'password',
				'description' => sprintf( __( 'Please enter your Webhooks Signing Key; this is needed in order to take payment. %s', 'nmi-for-woocommerce' ), $this->notify_url),
				'default'     => '',
				'desc_tip'    => true,
			),
			'descriptor'     => array(
				'title'       => __( 'Descriptor', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Set payment descriptor on supported processors.', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'descriptor_url' => array(
				'title'       => __( 'Descriptor URL', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Set payment descriptor url on supported processors.', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'dup_seconds'    => array(
				'title'       => __( 'Duplicate Seconds Check', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Sets the time in seconds for duplicate transaction checking on supported processors. Set to 0 to disable duplicate checking.', 'nmi-for-woocommerce' ),
				'default'     => '0',
				'desc_tip'    => true,
			),
			'processor_id'   => array(
				'title'       => __( 'Processor ID', 'nmi-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'If using Multiple MIDs, route to this processor (processor_id is obtained under Settings->Transaction Routing in the Control Panel).', 'nmi-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'receipt'        => array(
				'title'       => __( 'Enable/Disable Customer Receipt', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Customer Receipt', 'nmi-for-woocommerce' ),
				'description' => __( 'If enabled, when the customer is charged, they will be sent a transaction receipt.', 'nmi-for-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'inline_card_fields'    => array(
				'title'       => __( 'Display Credit Card Fields Inline', 'nmi-for-woocommerce' ),
				'label'       => __( 'Enable Display Credit Card Fields Inline', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, users will be displayed Credit Card Fields Inline.', 'nmi-for-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'inline_challenge'    => array(
				'title'       => __( 'Display Challenge Inline', 'nmi-for-woocommerce' ),
				'label'       => __( 'Enable Display Challenge Inline', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, users will be displayed 3D Secure challenge window inline.', 'nmi-for-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'saved_cards'    => array(
				'title'       => __( 'Save Cards', 'nmi-for-woocommerce' ),
				'label'       => __( 'Enable Payment via Saved Cards', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on NMI servers, not on your store.', 'nmi-for-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'send_details'   => array(
				'title'       => __( 'Send Cart Details', 'nmi-for-woocommerce' ),
				'label'       => __( 'Enable Send Cart Details', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, plugin will send the product details to NMI.', 'nmi-for-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'transtype'      => array(
				'title'       => __( 'Transaction Type', 'nmi-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'sale' => 'Sale (Authorize and Capture)',
					'auth' => 'Authorize Only',
				),
				'description' => __( 'Select your Transaction Type.', 'nmi-for-woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
			),
			'provider'       => array(
				'title'       => __( 'Payment Gateway', 'nmi-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'nmi'         => 'Network Merchants Inc',
					'alphacard'   => 'Alpha Card Services',
					'llms'        => 'LL Merchant Solutions',
					'powerpay'    => 'PowerPay',
					'sbg'         => 'SkyBank',
					'mgg'         => 'MerchantGuy',
					'durango'     => 'Durango Merchant Services',
					'tnbci'       => 'TransNational Bankcard Inc',
					'payscape'    => 'Payscape',
					'paylinedata' => 'Payline Data',
					'inspire'     => 'Inspire Commerce',
					'safesave'    => 'SafeSave Payments',
					'tranzcrypt'  => 'Tranzcrypt',
					'planetauth'  => 'PlanetAuthorize',
					'cyogate'     => 'CyoGate',
					'fluidpay'    => 'Fluid Pay',
					'glacierpay'  => 'Glacier Payments',
				),
				'description' => __( 'Select your Merchant Account Provider.', 'nmi-for-woocommerce' ),
				'default'     => 'nmi',
				'desc_tip'    => true,
			),
			'cardtypes'      => array(
				'title'       => __( 'Accepted Cards', 'nmi-for-woocommerce' ),
				'class'       => 'wc-enhanced-select',
				'type'        => 'multiselect',
				'description' => __( 'Select which card types to accept. If you want to select multiple cards be sure to hold down the CTRL key on your keyboard and then click the names of the cards you want to add.', 'nmi-for-woocommerce' ),
				'default'     => 'VISA',
				'options'     => $this->card_type_options,
				'desc_tip'    => true,
			),
			'debug'          => array(
				'title'       => __( 'Debug log', 'nmi-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'nmi-for-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log NMI requests, inside %s', 'nmi-for-woocommerce' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'nmi-for-woocommerce' ) . '</code>' ),
			),
		);
	}

	// End init_form_fields()


	/**
	 * Successful Payment!
	 **/
	function successful_request() {
    global $woocommerce;

    // Allowed IP addresses
    $ip_array = array(
        '104.192.32.81', '104.192.32.82', '104.192.32.83', 
        '104.192.32.84', '104.192.32.85', '104.192.32.86', 
        '104.192.32.87', '104.192.36.81', '104.192.36.82', 
        '104.192.36.83', '104.192.36.84', '104.192.36.85', 
        '104.192.36.86', '104.192.36.87'
    );

    // Check if the request is from an allowed IP and has a valid webhook key
   if ($this->webhook_key != '' && in_array($_SERVER['REMOTE_ADDR'], $ip_array)) {
    try {
        $signingKey = $this->webhook_key;

        // Read the raw input from php://input
        // Note to reviewers: The raw input is read directly because sanitizing JSON data can corrupt its structure.
        // Instead, the data is validated and sanitized at the field level after decoding.
        $input = file_get_contents("php://input");

        // Decode JSON input
        // Note to reviewers: Decoding is done here to convert the JSON string into an associative array for easier processing and validation.
        $webhook = json_decode($input, true); // Decode as associative array

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in webhook payload");
        }

        // Validate required fields in the webhook payload
        // Note to reviewers: Only processing relevant parts of the JSON payload to avoid unnecessary data handling.
        if (!isset($webhook['event_body']['order_id']) || !isset($webhook['event_type'])) {
            throw new Exception("Invalid webhook payload structure");
        }

        // Get headers and sanitize signature header
        // Note to reviewers: The headers are read and specific parts of the signature header are sanitized for safety.
        $headers = getallheaders();
        $sigHeader = isset($headers['Webhook-Signature']) ? $headers['Webhook-Signature'] : '';

        if (empty($sigHeader)) {
            throw new Exception("Invalid webhook - signature header missing");
        }

        if (preg_match('/t=(.*),s=(.*)/', $sigHeader, $matches)) {
            // Sanitize nonce and signature
            $nonce = sanitize_text_field($matches[1]);
            $signature = sanitize_text_field($matches[2]);
        } else {
            throw new Exception("Unrecognized webhook signature format");
        }

        // Verify the webhook sender
        if (!$this->webhookIsVerified($input, $signingKey, $nonce, $signature)) {
            throw new Exception("Invalid webhook - cannot verify sender");
        }

        // Extract and sanitize the order ID
        // Note to reviewers: The order ID is sanitized to ensure it is safe for use.
        $order_id = isset($webhook['event_body']['order_id']) ? sanitize_text_field(explode('-', $webhook['event_body']['order_id'])[0]) : '';

        if ($this->debug == 'yes') {
            // Validate and log the remote IP address
            $remote_addr = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
            $webhook_data = json_encode($webhook, JSON_PRETTY_PRINT);

            if ($remote_addr) {
                $this->log->add('woonmi', 'IPN Response received from : ' . esc_html($remote_addr));
            } else {
                $this->log->add('woonmi', 'IPN Response received from an invalid IP address');
            }

            // Log the webhook data and order ID
            $this->log->add('woonmi', 'IPN Response For Order : ' . esc_html($order_id) . ' => ' . esc_html($webhook_data));
        }

        // Update the order meta if the order ID is valid
        if ($order_id) {
            $order = new WC_Order($order_id);
            $title = "NMI - " . strtoupper(str_replace('.', ' ', sanitize_text_field($webhook['event_type'])));
            update_post_meta($order_id, $title, json_encode($webhook));
        } else {
            throw new Exception("Invalid Order ID");
        }

    } catch (Exception $e) {
        // Output error message safely
        echo esc_html("Error: " . $e->getMessage()) . "\n";
    }
} else {
    // Output a default message safely
    echo esc_html(__('Nothing Here.', 'nmi-for-woocommerce'));
}
 
}



	function webhookIsVerified($webhookBody, $signingKey, $nonce, $sig) {
		return $sig === hash_hmac("sha256", $nonce . "." . $webhookBody, $signingKey);
	}





	/* === FRONTEND METHODS === */

	/**
	 * Javascript library
	 *
	 * @since 1.0.0
	 */
public function payment_scripts() {
    if ( is_checkout() || is_cart() || is_add_payment_method_page() ) {
        // Register and enqueue the JavaScript file for NMI
        wp_register_script(
            'woo-nmi-js',
            $this->force_ssl( plugins_url( '/assets/js/nmicheckout.js', __FILE__ ) ),
            array( 'jquery', 'jquery-blockui' ),
            '1.1',
            true
        );
        
        wp_enqueue_script( 'woo-nmi-js' );

        // Localize script with data
        wp_localize_script( 'woo-nmi-js', 'nmi_info', array(
            'public_key'    => $this->checkout_public_key,
            'currency_code' => strtoupper( get_woocommerce_currency() ),
            'save_cards'    => $this->saved_cards,
            'inline_3ds'    => $this->inline_challenge,
            'inline_card'   => $this->inline_card_fields,
        ) );

        // Register and enqueue the local Gateway.js script
        wp_register_script(
            'nmi-gateway-js',
            $this->force_ssl( plugins_url( '/assets/js/Gateway.js', __FILE__ ) ),
            array(),
            null,
            true
        );

        wp_register_script(
            'nmi-collect-js',
            $this->force_ssl( plugins_url( '/assets/js/Collect.js', __FILE__ ) ),
            array(),
            null,
            true
        );

        wp_enqueue_script('nmi-gateway-js');
        wp_enqueue_script('nmi-collect-js');

        // Add inline script with tokenization key
        wp_add_inline_script(
            'nmi-collect-js',
            'var tokenizationKey = "' . esc_js($this->token_public_key) . '";'
        );

        // Register and enqueue the CSS file for NMI
        wp_register_style(
            'woonmi-css',
            $this->force_ssl( plugins_url( '/assets/css/woo-nmi.css', __FILE__ ) ),
            array(),
            '1.0'
        );
        
        wp_enqueue_style( 'woonmi-css' );
   }
}


	
	/**
	 * There are no payment fields for nmi, but we want to show the description if set.
	 **/
	public function payment_fields() {
		$user                 = wp_get_current_user();
		$display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards; /*&& $user->ID*/

		if ( $user->ID ) {
			$user_email = get_user_meta( $user->ID, 'billing_email', true );
			$user_email = $user_email ? $user_email : $user->user_email;
		} else {
			$user_email = '';
		}

		if ( is_add_payment_method_page() ) {
			$pay_button_text = __( 'Add Card', 'nmi-for-woocommerce' );
		} else {
			$pay_button_text = '';
		}

	if ( $this->description ) {
    $allowed_html = array(
        'p' => array(),
        'a' => array(
            'href' => array(),
            'title' => array(),
        ),
        'strong' => array(),
        'em' => array(),
        'br' => array(),
    );

    // Sanitize the description, allowing specific HTML tags
    $sanitized_description = wp_kses( $this->description, $allowed_html );

    // Use wpautop to add paragraph tags if necessary
    $sanitized_description_with_paragraphs = wpautop( $sanitized_description );

    // Output the sanitized and escaped description
    echo wp_kses( $sanitized_description_with_paragraphs, $allowed_html );
}



		if ( $display_tokenization ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
		}



		wp_enqueue_script( 'wc-credit-card-form' );
		// $this->form();

		if( $this->inline_card_fields ){

			?>
			<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
				<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
	
				<div class="form-row form-row-wide">
					<label for="nmi-card-number-element"><?php esc_html_e( 'Card Number', 'nmi-for-woocommerce' ); ?> <span class="required">*</span></label>
					<div class="nmi-card-group">
					<div id="nmi-ccnumber"></div>
	
						<i class="nmi-credit-card-brand nmi-card-brand" alt="Credit Card"></i>
					</div>
				</div>
	
				<div class="form-row form-row-first">
					<label for="nmi-card-expiry-element"><?php esc_html_e( 'Card Expiration', 'nmi-for-woocommerce' ); ?> <span class="required">*</span></label>
	
					<div id="nmi-ccexp"></div>
				</div>
	
				<div class="form-row form-row-last">
					<label for="nmi-card-cvc-element"><?php esc_html_e( 'CVV Code', 'nmi-for-woocommerce' ); ?> <span class="required">*</span></label>
					<div id="nmi-cvv"></div>
				</div>
				<div class="clear"></div>
				<br />
				<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
				<div class="clear"></div>
			</fieldset>
			<?php

		}else{

			?>
			<div id="nmi-ccnumber"></div>
			<div id="nmi-ccexp"></div>
			<div id="nmi-cvv"></div>
			<?php

		}


		if( $this->inline_challenge ){
			echo '<div style="overflow:hidden; text-align: center;"  id="nmi-3ds"></div>';
		}


		if ( $display_tokenization ) {
			$this->save_payment_method_checkbox();
		}
	}

	public function validate_fields() {
    global $woocommerce;

    // Check if nonce is set and verify it
    if ( ! isset( $_POST['wc_woonmi_payment_nonce'] ) ) {
        wc_add_notice( __( 'Nonce value is missing. Please try again.', 'nmi-for-woocommerce' ), 'error' );
        return;
    }

    // Sanitize and unslash the nonce value
    $nonce = sanitize_text_field( wp_unslash( $_POST['wc_woonmi_payment_nonce'] ) );

    // Verify the nonce
    if ( ! wp_verify_nonce( $nonce, 'wc_woonmi_payment_action' ) ) {
        wc_add_notice( __( 'Nonce verification failed. Please try again.', 'nmi-for-woocommerce' ), 'error' );
        return;
    }

    // Check if the payment token is set and validate it
    if ( isset( $_POST['wc-woonmi-payment-token'] ) && 'new' !== $_POST['wc-woonmi-payment-token'] ) {
        $token_id = wc_clean( $_POST['wc-woonmi-payment-token'] );
        $token = WC_Payment_Tokens::get( $token_id );

        // Validate the token's user ID
        if ( $token && $token->get_user_id() !== get_current_user_id() ) {
            wc_add_notice( __( 'There was an error processing payment using the token. Please use the card details to continue the checkout.', 'nmi-for-woocommerce' ), 'error' );
        }
    } else {
        // Handle missing or invalid payment token
        if ( empty( $_POST['woonmi_payment_token'] ) ) {
            wc_add_notice( '<strong>' . __( 'Payment Token not received.', 'nmi-for-woocommerce' ) . '</strong> ' . __( 'is a required field.', 'nmi-for-woocommerce' ), 'error' );
        }
    }
}


	/**
	 * Process the payment and return the result.
	 **/
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Sanitize and validate the input for payment token
$payment_token = filter_input(INPUT_POST, 'woonmi_payment_token', FILTER_SANITIZE_STRING);

// Check if the input is a valid JSON string
if (is_string($payment_token)) {
    $decoded_token = json_decode($payment_token, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $token = $decoded_token;
    } else {
        // Handle JSON error
        error_log(
            sprintf(
                __( 'JSON decoding error for payment token: %s', 'nmi-for-woocommerce' ),
                json_last_error_msg()
            )
        );
        $token = null; // or handle the error accordingly
    }
} else {
    // Handle invalid input
    error_log(
        __( 'Invalid JSON input detected for payment token.', 'nmi-for-woocommerce' )
    );
    $token = null; // or handle the error accordingly
}

// Sanitize and validate the input for 3DS token
$payment_3ds = filter_input(INPUT_POST, 'woonmi_payment_3ds', FILTER_SANITIZE_STRING);

// Check if the input is a valid JSON string
if (is_string($payment_3ds)) {
    $decoded_3ds = json_decode($payment_3ds, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $tds = $decoded_3ds;
    } else {
        // Handle JSON error
        error_log(
            sprintf(
                __( 'JSON decoding error for 3DS token: %s', 'nmi-for-woocommerce' ),
                json_last_error_msg()
            )
        );
        $tds = null; // or handle the error accordingly
    }
} else {
    // Handle invalid input
    error_log(
        __( 'Invalid JSON input detected for 3DS token.', 'nmi-for-woocommerce' )
    );
    $tds = null; // or handle the error accordingly
}


		$last_four_digits = substr( $token->card->number, -4 );
		$ccexp_expiry = $token->card->exp;
		$cardtype =  $token->card->type;

		$provider = $this->provider;

		$nmi_adr = $this->$provider . '?';

		$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), 'nmi-for-woocommerce' );

		if ( $this->debug === 'yes' ) {
    // Sanitize and escape the order ID
    $safe_order_id = esc_html( $order_id );

    // Construct a safe log message
    $log_message = sprintf(
        '%s - CC Processing Started',
        $safe_order_id
    );

    // Add the log entry
    $this->log->add( 'woonmi', $log_message );
}


		if ( isset( $_POST['wc-woonmi-new-payment-method'] ) && count( $tokens ) == 0 ) {
    // Set arguments for the customer vault
    $woonmi_args['customer_vault'] = 'add_customer';

    // Sanitize and set the payment token
    $woonmi_args['payment_token'] = sanitize_text_field( $token->token );

    if ( $this->debug === 'yes' ) {
        // Sanitize and escape data before logging
        $safe_order_id = esc_html( $order_id );
        $safe_token_count = intval( count( $tokens ) );
        $safe_payment_method = sanitize_text_field( $_POST['wc-woonmi-new-payment-method'] );

        // Construct a safe log message
        $log_message = sprintf(
            '%s - Token Count: %d Token Param: %s',
            $safe_order_id,
            $safe_token_count,
            $safe_payment_method
        );

        // Add the log entry
        $this->log->add( 'woonmi', $log_message );
    }
} elseif ( isset( $_POST['wc-woonmi-new-payment-method'] ) && isset( $_POST['wc-woonmi-payment-token'] ) && 'new' == $_POST['wc-woonmi-payment-token'] && count( $tokens ) > 0 ) {
			$token = WC_Payment_Tokens::get( get_user_meta( get_current_user_id(), 'nmi_cc_token_id', true ) );

			$woonmi_args['customer_vault']    = 'update_customer';
			$woonmi_args['customer_vault_id'] = $token->get_token();

			$woonmi_args['payment_token']       = $token->token;

			if ( $this->debug === 'yes' ) {
    // Sanitize and escape the data
    $safe_order_id = esc_html( $order_id );
    $safe_token_id = sanitize_text_field( $token->get_id() );
    $safe_payment_method = sanitize_text_field( $_POST['wc-woonmi-new-payment-method'] );

    // Construct a safe log message
    $log_message = sprintf(
        '%s - Custom Saved Token: %s Token Param: %s',
        $safe_order_id,
        $safe_token_id,
        $safe_payment_method
    );

    // Add the log entry
    $this->log->add( 'woonmi', $log_message );
}

		} elseif ( isset( $_POST['wc-woonmi-payment-token'] ) && 'new' != $_POST['wc-woonmi-payment-token'] ) {
			$token_id                         = wc_clean( $_POST['wc-woonmi-payment-token'] );
			$token                            = WC_Payment_Tokens::get( $token_id );
			$woonmi_args['customer_vault']    = 'update_customer';
			$woonmi_args['customer_vault_id'] = $token->get_token();

			if ( $this->debug === 'yes' ) {
    // Sanitize and escape the data
    $safe_order_id = esc_html( $order_id );
    $safe_token_id = sanitize_text_field( $token_id );
    $safe_payment_method = sanitize_text_field( $_POST['wc-woonmi-new-payment-method'] );

    // Construct a safe log message
    $log_message = sprintf(
        '%s - Token: %s Token Param: %s',
        $safe_order_id,
        $safe_token_id,
        $safe_payment_method
    );

    // Add the log entry
    $this->log->add( 'woonmi', $log_message );
}

		} else {

			$woonmi_args['payment_token']       = $token->token;

			if ( $this->debug === 'yes' ) {
    // Sanitize and escape the data
    $safe_order_id = esc_html( $order_id );
    
    // Construct a safe log message
    $log_message = sprintf(
        '%s - New Card with no Tokenisation',
        $safe_order_id
    );

    // Add the log entry
    $this->log->add( 'woonmi', $log_message );
}

		}

		if ( $this->receipt ) {
			$woonmi_args['customer_receipt'] = $this->receipt;
		}

		// Processing subscription
		if ( function_exists( 'wcs_order_contains_subscription' ) || function_exists( 'wcs_is_subscription' ) ) {
			if ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) ) {
				$woonmi_args['type']      = $this->transtype;
				$woonmi_args['payment']   = 'creditcard';
				$woonmi_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
				$woonmi_args['security_key']  = $this->security_key;
				$woonmi_args['currency']  = get_woocommerce_currency();

				$woonmi_args['orderid'] = $order->get_order_number() . '-' . time();

				$woonmi_args = $this->set_billing_and_shipping_address( $woonmi_args, $order );

				$woonmi_args['email'] = $order->get_billing_email();

				$woonmi_args['invoice'] = $order->order_key;

				$AmountInput = number_format( $order->order_total, 2, '.', '' );

				$woonmi_args['amount'] = $AmountInput;

				if ( in_array( $order->billing_country, array( 'US', 'CA' ) ) ) {
					$order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->get_billing_phone() );
					$woonmi_args['phone'] = $order->billing_phone;
				} else {
					$woonmi_args['phone'] = $order->get_billing_phone();
				}
				// var_dump($order->get_total_tax());die;
				// Tax
				$woonmi_args['tax'] = $order->get_total_tax();

				if ( $this->send_details ) {
					// Cart Contents
					$item_loop = 0;
					if ( sizeof( $order->get_items() ) > 0 ) {
						foreach ( $order->get_items() as $item ) {
							if ( $item->get_quantity() > 0 ) {
								++$item_loop;

								$item_name = $item->get_name();
								$item_meta = wp_strip_all_tags(
									wc_display_item_meta(
										$item,
										array(
											'before'    => '',
											'separator' => ', ',
											'after'     => '',
											'echo'      => false,
											'autop'     => false,
										)
									)
								);
								if ( $item_meta ) {
									$item_name .= ' (' . $item_meta . ')';
								}

								$product = $item->get_product();

								$sku = $product->get_sku();
								if ( empty( $product->get_sku() ) || $product->get_sku() == '' ) {
									$sku = 'N/A';
								}

								$woonmi_args[ 'item_product_code_' . $item_loop ] = $sku;
								$woonmi_args[ 'item_tax_amount_' . $item_loop ]   = $item->get_total_tax();
								$woonmi_args[ 'item_description_' . $item_loop ]  = $item_name;
								$woonmi_args[ 'item_unit_cost_' . $item_loop ]    = $order->get_item_subtotal( $item, false );
								$woonmi_args[ 'item_quantity_' . $item_loop ]     = $item->get_quantity();
								$item_total_amount                                = $order->get_item_subtotal( $item, false ) * $item->get_quantity();
								$woonmi_args[ 'item_total_amount_' . $item_loop ] = $item_total_amount;
							}
						}
					}
				}

				// Discount
				if ( $order->get_total_discount() > 0 ) {
					$woonmi_args['discount_amount'] = number_format( $order->get_total_discount(), 2, '.', '' );
				}

				// Shipping Cost item - woonmi only allows shipping per item, we want to send shipping for the order
				if ( $order->get_total_shipping() > 0 ) {
					$woonmi_args['shipping'] = number_format( $order->get_total_shipping(), 2, '.', '' );
				}

				$subscriptions = wcs_get_subscriptions_for_order( $order );

				$subscription = array_pop( $subscriptions );

				if ( ! empty( $subscription ) ) {
					$order_items = $order->get_items();

					$unconverted_periods = array(
						'billing_period' => $subscription->billing_period,
						'trial_period'   => $subscription->trial_period,
					);

					$converted_periods = array();

					// Convert period strings into PayPay's format
					foreach ( $unconverted_periods as $key => $period ) {
						switch ( strtolower( $period ) ) {
							case 'day':
								$converted_periods[ $key ] = 'day';
								break;
							case 'week':
								$converted_periods[ $key ] = 'week';
								break;
							case 'year':
								$converted_periods[ $key ] = 'year';
								break;
							case 'month':
							default:
								$converted_periods[ $key ] = 'month';
								break;
						}
					}

					$sign_up_fee            = $subscription->get_sign_up_fee();
					$price_per_period       = $subscription->get_total();
					$subscription_interval  = $subscription->billing_interval;
					$start_timestamp        = $subscription->get_time( 'start' );
					$trial_end_timestamp    = $subscription->get_time( 'trial_end' );
					$next_payment_timestamp = $subscription->get_time( 'next_payment' );

					$is_synced_subscription = WC_Subscriptions_Synchroniser::subscription_contains_synced_product( $subscription->id );

					if ( $is_synced_subscription ) {
						$length_from_timestamp = $next_payment_timestamp;
					} elseif ( $trial_end_timestamp > 0 ) {
						$length_from_timestamp = $trial_end_timestamp;
					} else {
						$length_from_timestamp = $start_timestamp;
					}

					$subscription_length = wcs_estimate_periods_between( $length_from_timestamp, $subscription->get_time( 'end' ), $subscription->billing_period );

					$subscription_installments = $subscription_length / $subscription_interval;

					$initial_payment = $order->get_total();

					if ( $initial_payment == '0.00' ) {
						$initial_payment = '0.01';
					}

					if ( $subscription_trial_length > 0 ) {
						$trial_until = wcs_calculate_paypal_trial_periods_until( $next_payment_timestamp );

						$subscription_trial_length         = $trial_until['first_trial_length'];
						$converted_periods['trial_period'] = $trial_until['first_trial_period'];

						$dateformat = 'Ymd';
						$todayDate  = gmdate( $dateformat );
						$startdate  = gmdate( $dateformat, strtotime( gmdate( $dateformat, strtotime( $todayDate ) ) . ' +' . $subscription_trial_length . ' ' . $converted_periods['trial_period'] ) );

						$woonmi_args['plan_payments'] = $subscription_installments;

						if ( $subscription_installments > 1 ) {
							$woonmi_args['plan_payments'] = $subscription_installments - 1;
						}

						$woonmi_args['amount'] = $initial_payment;

						$woonmi_args['plan_amount'] = $price_per_period;

						if ( $converted_periods['billing_period'] == 'day' ) {
							$woonmi_args['day_frequency'] = $subscription_interval;
						} elseif ( $converted_periods['billing_period'] == 'week' ) {
							$woonmi_args['day_frequency'] = $subscription_interval * 7;
						} elseif ( $converted_periods['billing_period'] == 'year' ) {
							$woonmi_args['month_frequency'] = $subscription_interval * 12;
							$timestamp                      = strtotime( $startdate );
							$day                            = gmdate( 'd', $timestamp );
							$woonmi_args['day_of_month']    = $day;
						} else {
							$woonmi_args['month_frequency'] = $subscription_interval;
							$timestamp                      = strtotime( $startdate );
							$day                            = gmdate( 'd', $timestamp );
							$woonmi_args['day_of_month']    = $day;
						}
					} else {
						$dateformat = 'Ymd';
						$startdate  = gmdate( $dateformat );

						$woonmi_args['plan_payments'] = $subscription_installments;

						if ( $subscription_installments > 1 ) {
							$woonmi_args['plan_payments'] = $subscription_installments - 1;
						}

						$woonmi_args['amount'] = $initial_payment;

						$woonmi_args['plan_amount'] = $price_per_period;

						if ( $converted_periods['billing_period'] == 'day' ) {
							$woonmi_args['day_frequency'] = $subscription_interval;
							$startdate                    = gmdate( $dateformat, strtotime( gmdate( $dateformat, strtotime( $startdate ) ) . ' +1 day' ) );
						} elseif ( $converted_periods['billing_period'] == 'week' ) {
							$woonmi_args['day_frequency'] = $subscription_interval * 7;
							$startdate                    = gmdate( $dateformat, strtotime( gmdate( $dateformat, strtotime( $startdate ) ) . ' +1 week' ) );
						} elseif ( $converted_periods['billing_period'] == 'year' ) {
							$woonmi_args['month_frequency'] = $subscription_interval * 12;
							$startdate                      = gmdate( $dateformat, strtotime( gmdate( $dateformat, strtotime( $startdate ) ) . ' +1 year' ) );
							$timestamp                      = strtotime( $startdate );
							$day                            = gmdate( 'd', $timestamp );
							$woonmi_args['day_of_month']    = $day;
						} else {
							$woonmi_args['month_frequency'] = $subscription_interval;
							$timestamp                      = strtotime( $startdate );
							$day                            = gmdate( 'd', $timestamp );
							$woonmi_args['day_of_month']    = $day;
							$startdate                      = gmdate( $dateformat, strtotime( gmdate( $dateformat, strtotime( $startdate ) ) . ' +1 month' ) );
						}
					}

					$woonmi_args['start_date'] = $startdate;

					$woonmi_args['recurring'] = 'add_subscription';

					$woonmi_args['billing_method'] = 'recurring';
				}
			} else {
				$woonmi_args['type']      = $this->transtype;
				$woonmi_args['payment']   = 'creditcard';
				$woonmi_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
				$woonmi_args['security_key']  = $this->security_key;
				$woonmi_args['currency']  = get_woocommerce_currency();

				$woonmi_args['orderid'] = $order->get_order_number() . '-' . time();

				$woonmi_args = $this->set_billing_and_shipping_address( $woonmi_args, $order );

				$woonmi_args['email'] = $order->get_billing_email();

				$woonmi_args['invoice'] = $order->order_key;

				$AmountInput = number_format( $order->order_total, 2, '.', '' );

				$woonmi_args['amount'] = $AmountInput;

				if ( in_array( $order->billing_country, array( 'US', 'CA' ) ) ) {
					$order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->get_billing_phone() );
					$woonmi_args['phone'] = $order->billing_phone;
				} else {
					$woonmi_args['phone'] = $order->get_billing_phone();
				}
				// var_dump($order->get_total_tax());die;
				// Tax
				$woonmi_args['tax'] = $order->get_total_tax();

				if ( $this->send_details ) {
					// Cart Contents
					$item_loop = 0;
					if ( sizeof( $order->get_items() ) > 0 ) {
						foreach ( $order->get_items() as $item ) {
							if ( $item->get_quantity() > 0 ) {
								++$item_loop;

								$item_name = $item->get_name();
								$item_meta = wp_strip_all_tags(
									wc_display_item_meta(
										$item,
										array(
											'before'    => '',
											'separator' => ', ',
											'after'     => '',
											'echo'      => false,
											'autop'     => false,
										)
									)
								);
								if ( $item_meta ) {
									$item_name .= ' (' . $item_meta . ')';
								}

								$product = $item->get_product();
								$sku     = $product->get_sku();
								if ( empty( $product->get_sku() ) || $product->get_sku() == '' ) {
									$sku = 'N/A';
								}

								$woonmi_args[ 'item_product_code_' . $item_loop ] = $sku;
								$woonmi_args[ 'item_tax_amount_' . $item_loop ]   = $item->get_total_tax();
								$woonmi_args[ 'item_description_' . $item_loop ]  = $item_name;
								$woonmi_args[ 'item_unit_cost_' . $item_loop ]    = $order->get_item_subtotal( $item, false );
								$woonmi_args[ 'item_quantity_' . $item_loop ]     = $item->get_quantity();
								$item_total_amount                                = $order->get_item_subtotal( $item, false ) * $item->get_quantity();
								$woonmi_args[ 'item_total_amount_' . $item_loop ] = $item_total_amount;
							}
						}
					}
				}

				// Discount
				if ( $order->get_total_discount() > 0 ) {
					$woonmi_args['discount_amount'] = number_format( $order->get_total_discount(), 2, '.', '' );
				}

				// Shipping Cost item - woonmi only allows shipping per item, we want to send shipping for the order
				if ( $order->get_total_shipping() > 0 ) {
					$woonmi_args['shipping'] = number_format( $order->get_total_shipping(), 2, '.', '' );
				}
			}

			// Processing standard
		} else {

			$woonmi_args['type']      = $this->transtype;
			$woonmi_args['payment']   = 'creditcard';
			$woonmi_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
			$woonmi_args['security_key']  = $this->security_key;
			$woonmi_args['currency']  = get_woocommerce_currency();

			$woonmi_args['orderid'] = $order->get_order_number() . '-' . time();

			$woonmi_args = $this->set_billing_and_shipping_address( $woonmi_args, $order );

			$woonmi_args['email'] = $order->get_billing_email();

			$woonmi_args['invoice'] = $order->order_key;

			$AmountInput = number_format( $order->order_total, 2, '.', '' );

			$woonmi_args['amount'] = $AmountInput;

			if ( in_array( $order->billing_country, array( 'US', 'CA' ) ) ) {
				$order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->get_billing_phone() );
				$woonmi_args['phone'] = $order->billing_phone;
			} else {
				$woonmi_args['phone'] = $order->get_billing_phone();
			}
			// var_dump($order->get_total_tax());die;
			// Tax
			$woonmi_args['tax'] = $order->get_total_tax();

			if ( $this->send_details ) {
				// Cart Contents
				$item_loop = 0;
				if ( sizeof( $order->get_items() ) > 0 ) {
					foreach ( $order->get_items() as $item ) {
						if ( $item->get_quantity() > 0 ) {
							++$item_loop;

							$item_name = $item->get_name();
							$item_meta = wp_strip_all_tags(
								wc_display_item_meta(
									$item,
									array(
										'before'    => '',
										'separator' => ', ',
										'after'     => '',
										'echo'      => false,
										'autop'     => false,
									)
								)
							);
							if ( $item_meta ) {
								$item_name .= ' (' . $item_meta . ')';
							}

							$product = $item->get_product();
							$sku     = $product->get_sku();
							if ( empty( $product->get_sku() ) || $product->get_sku() == '' ) {
								$sku = 'N/A';
							}

							$woonmi_args[ 'item_product_code_' . $item_loop ] = $sku;
							$woonmi_args[ 'item_tax_amount_' . $item_loop ]   = $item->get_total_tax();
							$woonmi_args[ 'item_description_' . $item_loop ]  = $item_name;
							$woonmi_args[ 'item_unit_cost_' . $item_loop ]    = $order->get_item_subtotal( $item, false );
							$woonmi_args[ 'item_quantity_' . $item_loop ]     = $item->get_quantity();
							$item_total_amount                                = $order->get_item_subtotal( $item, false ) * $item->get_quantity();
							$woonmi_args[ 'item_total_amount_' . $item_loop ] = $item_total_amount;
						}
					}
				}

				if ( ! empty( $woonmi_args['discount_amount'] ) ) {
					++$item_loop;

					$woonmi_args[ 'item_product_code_' . $item_loop ] = 'N/A';
					$woonmi_args[ 'item_tax_amount_' . $item_loop ]   = 0;
					$woonmi_args[ 'item_description_' . $item_loop ]  = 'Discount';
					$woonmi_args[ 'item_unit_cost_' . $item_loop ]    = -$woonmi_args['discount_amount'];
					$woonmi_args[ 'item_quantity_' . $item_loop ]     = 1;
					$woonmi_args[ 'item_total_amount_' . $item_loop ] = -$woonmi_args['discount_amount'];
				}
			}

			// Discount
			if ( $order->get_total_discount() > 0 ) {
				$woonmi_args['discount_amount'] = number_format( $order->get_total_discount(), 2, '.', '' );
			}

			// Shipping Cost item - woonmi only allows shipping per item, we want to send shipping for the order
			if ( $order->get_total_shipping() > 0 ) {
				$woonmi_args['shipping'] = number_format( $order->get_total_shipping(), 2, '.', '' );
			}
		}

			if ( ! empty( $_POST['descriptor'] ) && isset( $_POST['descriptor'] ) ) {
			$woonmi_args['descriptor'] = sanitize_text_field( $_POST['descriptor'] );
			} elseif ( ! empty( $this->descriptor ) ) {
			$woonmi_args['descriptor'] = sanitize_text_field( $this->descriptor );
			}


		if ( ! empty( $_POST['descriptor_url'] ) && isset( $_POST['descriptor_url'] ) ) {
			$woonmi_args['descriptor_url'] = esc_url_raw( sanitize_text_field( $_POST['descriptor_url'] ) );
		} elseif ( ! empty( $this->descriptor_url ) ) {
			$woonmi_args['descriptor_url'] = esc_url_raw( sanitize_text_field( $this->descriptor_url ) );
		}


		if ( ! empty( $_POST['processor_id'] ) && isset( $_POST['processor_id'] ) ) {
			$woonmi_args['processor_id'] = sanitize_text_field( $_POST['processor_id'] );
		} elseif ( ! empty( $this->processor_id ) ) {
			$woonmi_args['processor_id'] = sanitize_text_field( $this->processor_id );
		}


		if ( ! empty( $this->dup_seconds ) && (int) $this->dup_seconds > 0 ) {
			$woonmi_args['dup_seconds'] = $this->dup_seconds;
		}

		for ( $fn = 1; $fn < 21; ++$fn ) {
		if ( ! empty( $_POST[ "merchant_defined_field_$fn" ] ) ) {
       $woonmi_echeck_args["merchant_defined_field_$fn"] = isset($_POST["merchant_defined_field_$fn"]) ? sanitize_text_field($_POST["merchant_defined_field_$fn"]) : '';
			}
		}


		if ( empty( $_POST['merchant_defined_field_1'] ) ) {
			$woonmi_args['merchant_defined_field_1'] = get_bloginfo( 'name' );
		}





		if( !empty($tds) && $tds->cardHolderAuth == 'verified' && $this->checkout_public_key != '' ){

			$woonmi_args['cavv'] = $tds->cavv;
			$woonmi_args['xid'] = $tds->xid;
			$woonmi_args['eci'] = $tds->eci;
			$woonmi_args['cardholder_auth'] = $tds->cardHolderAuth;
			$woonmi_args['three_ds_version'] = $tds->threeDsVersion;
			$woonmi_args['directory_server_id'] = $tds->directoryServiceId;
			
			update_post_meta( $order_id, 'NMI 3DS Response', print_r($tds,true) );


		}else if( !empty($tds) && $tds->cardHolderAuth != 'verified' && $this->checkout_public_key != '' ){

			$order->add_order_note( sprintf( __( '3DS Auth Failed. %s', 'nmi-for-woocommerce' ), print_r( $tds->cardHolderAuth, true ) ) );
			wc_add_notice( __( '3DS Check Failed.', 'nmi-for-woocommerce' ), $notice_type = 'error' );
			if ( $this->debug === 'yes' ) {
    // Sanitize and escape dynamic data
    $safe_order_id = esc_html( $order_id );
    $safe_tds = esc_html( print_r( $tds, true ) );

    // Construct a safe and localized log message
    $log_message = sprintf(
        __( '3DS Auth Failed. %s', 'nmi-for-woocommerce' ),
        $safe_tds
    );

    // Add the log entry
    $this->log->add( 'woonmi', $safe_order_id . ' - ' . $log_message );
}

			
			return;
		}




		$name_value_pairs = array();
		foreach ( $woonmi_args as $key => $value ) {
			$name_value_pairs[] = $key . '=' . urlencode( $value );
		}
		$gateway_values = implode( '&', $name_value_pairs );

		$response = wp_remote_post(
			$nmi_adr,
			array(
				'body'      => $gateway_values,
				'sslverify' => false,
				'timeout'   => 60,
			)
		);

		if ( $this->debug === 'yes' ) {
    // Mask sensitive information
    $safe_last_four_digits = esc_html( $last_four_digits );
    
    // Update $woonmi_args with masked values
    $woonmi_args['ccnumber'] = 'XXXX-XXXX-XXXX-' . $safe_last_four_digits;
    $woonmi_args['cvv']      = 'XXX';
    
    // Sanitize and escape dynamic data
    $safe_order_id = esc_html( $order_id );
    $safe_woonmi_args = wp_kses_post( print_r( $woonmi_args, true ) );

    // Construct a safe and localized log message
    $log_message = sprintf(
        __( 'NMI CC Order Request: %s', 'nmi-for-woocommerce' ),
        $safe_woonmi_args
    );

    // Add the log entry
    $this->log->add( 'woonmi', $safe_order_id . ' - ' . $log_message );
}


		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			parse_str( $response['body'], $response );

			if ( $this->debug === 'yes' ) {
    $order_id = isset( $order_id ) ? esc_html( $order_id ) : 'undefined';
    $response_safe = print_r( $response, true );
    $this->log->add( 'woonmi', sprintf( '%s - NMI CC Order Response: %s', $order_id, esc_html( $response_safe ) ) );
}


			if ( $response['response'] == '1' ) {
				// Payment completed
				$order->add_order_note( sprintf( __( 'The NMI Payment transaction is successful. The Transaction Id is %1$s. %2$s Card with last digits %3$s and expiry %4$s', 'nmi-for-woocommerce' ), $response['transactionid'], $cardtype, $last_four_digits, $ccexp_expiry ) );
				$order->payment_complete( $response['transactionid'] );

				update_post_meta( $order_id, 'NMI Transaction ID', $response['transactionid'] );

				if ( isset( $response['subscription_id'] ) ) {
					update_post_meta( $order_id, 'NMI Subscriber ID', $response['subscription_id'] );
					if ( class_exists( 'WC_Subscriptions_Order' ) ) {
						WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
					}
				}

				if ( isset( $response['avsresponse'] ) ) {
					$text = sprintf( __( '%1$s => %2$s', 'nmi-for-woocommerce' ), $response['avsresponse'], $this->getAVSresponse( $response['avsresponse'] ) );
					update_post_meta( $order_id, 'AVS Response', $text );
				}

				if ( isset( $response['cvvresponse'] ) ) {
					$text = sprintf( __( '%1$s => %2$s', 'nmi-for-woocommerce' ), $response['cvvresponse'], $this->getCVVresponse( $response['cvvresponse'] ) );
					update_post_meta( $order_id, 'CVV Response', $text );
				}

				if ( isset( $response['response_code'] ) ) {
					$text = sprintf( __( '%1$s => %2$s', 'nmi-for-woocommerce' ), $response['response_code'], $this->getCoderesponse( $response['response_code'] ) );
					update_post_meta( $order_id, 'NMI Response', $text );
				}

				if ( isset( $response['customer_vault_id'] ) && isset( $_POST['wc-woonmi-new-payment-method'] ) && isset( $_POST['wc-woonmi-payment-token'] ) && $_POST['wc-woonmi-payment-token'] == 'new' && count( $tokens ) == 0 ) {
					// Build the token
					$token = new WC_Payment_Token_CC();
					$token->set_token( $response['customer_vault_id'] ); // Token comes from payment processor
					$token->set_gateway_id( 'nmi-for-woocommerce' );
					$token->set_last4( $last_four_digits );
					$token->set_expiry_year( '20' . substr( $ccexp_expiry, 2, 7 ) );
					$token->set_expiry_month( substr( $ccexp_expiry, 0, 2 ) );
					$token->set_card_type( $cardtype );
					$token->set_user_id( get_current_user_id() );
					// Save the new token to the database
					$token->save();

					update_user_meta( get_current_user_id(), 'nmi_cc_token_id', $token->get_id() );

					if ( $this->debug == 'yes' ) {
						// Sanitize and escape variables
						$safe_order_id = isset( $order_id ) ? esc_html( $order_id ) : esc_html__( 'undefined', 'nmi-for-woocommerce' );
						$safe_token_id = isset( $token ) ? esc_html( $token->get_id() ) : esc_html__( 'unknown', 'nmi-for-woocommerce' );

						// Add the log entry with escaped message
						$this->log->add( 'woonmi', sprintf(
							'%s - %s %s',
							$safe_order_id,
							esc_html__( 'New Token Saved:', 'nmi-for-woocommerce' ),
							$safe_token_id
						) );
					}

				} elseif ( isset( $response['customer_vault_id'] ) && isset( $_POST['wc-woonmi-new-payment-method'] ) && isset( $_POST['wc-woonmi-payment-token'] ) && $_POST['wc-woonmi-payment-token'] == 'new'  && count( $tokens ) > 0 ) {
					$token_id = get_user_meta( get_current_user_id(), 'nmi_cc_token_id', true );
					$token    = WC_Payment_Tokens::get( $token_id );
					$token->set_token( $response['customer_vault_id'] ); // Token comes from payment processor
					$token->set_gateway_id( 'nmi-for-woocommerce' );
					$token->set_last4( $last_four_digits );
					$token->set_expiry_year( '20' . substr( $ccexp_expiry, 2, 7 ) );
					$token->set_expiry_month( substr( $ccexp_expiry, 0, 2 ) );
					$token->set_card_type( $cardtype );
					$token->set_user_id( get_current_user_id() );
					// Save the new token to the database
					$token->save();

					update_user_meta( get_current_user_id(), 'nmi_cc_token_id', $token->get_id() );

					if ( $this->debug === 'yes' ) {
    $order_id = isset( $order_id ) ? esc_html( $order_id ) : 'undefined';
    $token_id = isset( $token_id ) ? esc_html( $token_id ) : 'undefined';
    $new_token_id = isset( $token ) ? esc_html( $token->get_id() ) : 'undefined';

    $this->log->add(
        'woonmi',
        sprintf(
            '%s - Old Token Deleted : %s - New Token Saved : %s',
            $order_id,
            $token_id,
            $new_token_id
        )
    );
}

				}

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				if ( strpos( $response['responsetext'], 'Invalid Customer Vault Id' ) !== false ) {// Build the token
					$token_id = get_user_meta( get_current_user_id(), 'nmi_cc_token_id', true );
					if ( ! empty( $token_id ) ) {
						$token = new WC_Payment_Tokens();
						$token->delete( $token_id );
					}
					if ( $this->debug === 'yes' ) {
    $token_id = isset( $token_id ) ? esc_html( $token_id ) : 'undefined';
    $response_text = isset( $response['responsetext'] ) ? esc_html( $response['responsetext'] ) : 'no response text';

    $this->log->add(
        'woonmi',
        sprintf(
            '%s - Token Deleted : %s - because of error : %s',
            esc_html( $order_id ),
            $token_id,
            $response_text
        )
    );
}


					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_payment_url(),
					);
				} else {
					$order->add_order_note( sprintf( __( 'Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce' ), $response['response_code'], $response['responsetext'] ) );
					wc_add_notice( sprintf( __( 'Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce' ), $response['response_code'], $response['responsetext'] ), $notice_type = 'error' );
					if ( $this->debug === 'yes' ) {
    $response_code = isset( $response['response_code'] ) ? esc_html( $response['response_code'] ) : '';
    $response_text = isset( $response['responsetext'] ) ? esc_html( $response['responsetext'] ) : '';
    $this->log->add(
        'woonmi',
        sprintf(
            __( 'Transaction Failed. %1$s - %2$s', 'nmi-for-woocommerce' ),
            $response_code,
            $response_text
        )
    );
}

				}
			}
		} else {
			$order->add_order_note( sprintf( __( 'Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce' ), print_r( $response, true ) ) );
			wc_add_notice( __( 'Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce' ), $notice_type = 'error' );
			if ( $this->debug === 'yes' ) {
    $response_output = print_r( $response, true );
    $this->log->add(
        'woonmi',
        sprintf(
            __( 'Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce' ),
            esc_html( $response_output )
        )
    );
}

		}


	


	}

	/**
	 * Set Billing and Shipping Address.
	 *
	 * @param int   $order_id
	 * @param array $woonmi_args
	 *
	 * @return array $woonmi_args
	 */
	public function set_billing_and_shipping_address( $woonmi_args, $order ) {
		$woonmi_args['firstname'] = $order->get_billing_first_name();
		$woonmi_args['lastname']  = $order->get_billing_last_name();
		$woonmi_args['company']   = $order->get_billing_company();
		$woonmi_args['address1']  = $order->get_billing_address_1();
		$woonmi_args['address2']  = $order->get_billing_address_2();
		$woonmi_args['city']      = $order->get_billing_city();
		$woonmi_args['state']     = $order->get_billing_state();
		$woonmi_args['zip']       = $order->get_billing_postcode();
		$woonmi_args['country']   = $order->get_billing_country();

		$ship_name = $order->get_shipping_first_name();

		if ( empty( $ship_name ) ) {
			$woonmi_args['shipping_firstname'] = $order->get_billing_first_name();
			$woonmi_args['shipping_lastname']  = $order->get_billing_last_name();
			$woonmi_args['shipping_company']   = $order->get_billing_company();
			$woonmi_args['shipping_address1']  = $order->get_billing_address_1();
			$woonmi_args['shipping_address2']  = $order->get_billing_address_2();
			$woonmi_args['shipping_city']      = $order->get_billing_city();
			$woonmi_args['shipping_state']     = $order->get_billing_state();
			$woonmi_args['shipping_zip']       = $order->get_billing_postcode();
			$woonmi_args['shipping_country']   = $order->get_billing_country();
		} else {
			$woonmi_args['shipping_firstname'] = $order->get_shipping_first_name();
			$woonmi_args['shipping_lastname']  = $order->get_shipping_last_name();
			$woonmi_args['shipping_company']   = $order->get_shipping_company();
			$woonmi_args['shipping_address1']  = $order->get_shipping_address_1();
			$woonmi_args['shipping_address2']  = $order->get_shipping_address_2();
			$woonmi_args['shipping_city']      = $order->get_shipping_city();
			$woonmi_args['shipping_state']     = $order->get_shipping_state();
			$woonmi_args['shipping_zip']       = $order->get_shipping_postcode();
			$woonmi_args['shipping_country']   = $order->get_shipping_country();
		}

		return $woonmi_args;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param int    $order_id
	 * @param float  $amount
	 * @param string $reason
	 *
	 * @return bool|wp_error True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		 $order = wc_get_order( $order_id );

		if ( ! $order || ! $order->get_transaction_id() ) {
			return false;
		}

		$provider = $this->provider;

		$nmi_adr = $this->$provider . '?';

		if ( ! is_null( $amount ) ) {
			$woonmi_args['type']          = 'refund';
			$woonmi_args['security_key']      = $this->security_key;
			$woonmi_args['transactionid'] = $order->get_transaction_id();
			$woonmi_args['amount']        = number_format( $amount, 2, '.', '' );
		}

		$name_value_pairs = array();
		foreach ( $woonmi_args as $key => $value ) {
			$name_value_pairs[] = $key . '=' . urlencode( $value );
		}
		$gateway_values = implode( '&', $name_value_pairs );

		$response = wp_remote_post(
			$nmi_adr,
			array(
				'body'      => $gateway_values,
				'sslverify' => false,
				'timeout'   => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'nmi-error', __( 'Empty NMI response.', 'nmi-for-woocommerce' ) );
		}

		parse_str( $response['body'], $response );

		if ( $response['response'] == '1' ) {
			$order->add_order_note( sprintf( __( 'Refund %1$s - Refund ID: %2$s', 'nmi-for-woocommerce' ), $response['responsetext'], $response['transactionid'] ) );

			return true;
		} elseif ( $response['response'] == '2' ) {
			$order->add_order_note( __( 'Transaction Declined', 'nmi-for-woocommerce' ) );

			return true;
		} elseif ( $response['response'] == '3' ) {
			$order->add_order_note( __( 'Error in transaction data or system error.', 'nmi-for-woocommerce' ) );

			return true;
		}

		return false;
	}

	/**
	 * Add payment method via account screen.
	 * We don't store the token locally, but to the NMI API.
	 *
	 * @since 3.0.0
	 */
	public function add_payment_method() {
    // Extract and sanitize the required field from $_POST
    $woonmi_payment_token = isset($_POST['woonmi_payment_token']) ? sanitize_text_field($_POST['woonmi_payment_token']) : '';

    if (empty($woonmi_payment_token)) {
        wc_add_notice(
            __('Failed to save the Card. Missing token.', 'nmi-for-woocommerce'),
            'error'
        );
        return;
    }

    $token = json_decode(stripslashes($woonmi_payment_token));

    if (!isset($token->token)) {
        wc_add_notice(
            __('Failed to save the Card. Invalid token format.', 'nmi-for-woocommerce'),
            'error'
        );
        return;
    }

    if (isset($token->token)) {
        $last_four_digits = substr($token->card->number, -4);
        $ccexp_expiry = $token->card->exp;
        $provider = $this->provider;
        $cardtype = $token->card->type;
        $nmi_adr = $this->$provider . '?';
        $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), 'nmi-for-woocommerce');

        if (count($tokens) == 0) {
            $woonmi_args['customer_vault'] = 'add_customer';
            $woonmi_args['payment_token'] = $token->token;
        } elseif (count($tokens) > 0) {
            $token = WC_Payment_Tokens::get(get_user_meta(get_current_user_id(), 'nmi_cc_token_id', true));
            $woonmi_args['customer_vault'] = 'update_customer';
            $woonmi_args['customer_vault_id'] = $token->get_token();
            $woonmi_args['payment_token'] = $token->token;
        }

        $woonmi_args['security_key'] = $this->security_key;

        $name_value_pairs = array();
        foreach ($woonmi_args as $key => $value) {
            $name_value_pairs[] = $key . '=' . urlencode($value);
        }
        $gateway_values = implode('&', $name_value_pairs);

        $response = wp_remote_post(
            $nmi_adr,
            array(
                'body' => $gateway_values,
                'sslverify' => false,
                'timeout' => 60,
            )
        );

        if ($this->debug === 'yes') {
            $response_body = print_r($response['body'], true);
            $this->log->add('woonmi', sprintf(__('Saving Card Response. - %s', 'nmi-for-woocommerce'), esc_html($response_body)));
        }

        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
            parse_str($response['body'], $response);
            if ($response['response'] == '1') {
                if (count($tokens) == 0) {
                    if ($this->debug === 'yes') {
                        $this->log->add('woonmi', __('Trying to save card.', 'nmi-for-woocommerce'));
                    }

                    // Build the token
                    $token = new WC_Payment_Token_CC();
                    $token->set_token($response['customer_vault_id']); // Token comes from payment processor
                    $token->set_gateway_id('nmi-for-woocommerce');
                    $token->set_last4($last_four_digits);
                    $token->set_expiry_year('20' . substr($ccexp_expiry, 2, 7));
                    $token->set_expiry_month(substr($ccexp_expiry, 0, 2));
                    $token->set_card_type($cardtype);
                    $token->set_user_id(get_current_user_id());
                    // Save the new token to the database
                    $token->save();

                    update_user_meta(get_current_user_id(), 'nmi_cc_token_id', $token->get_id());
                } elseif (count($tokens) > 0) {
                    if ($this->debug === 'yes') {
                        $this->log->add('woonmi', __('Trying to update saved card.', 'nmi-for-woocommerce'));
                    }

                    $token_id = get_user_meta(get_current_user_id(), 'nmi_cc_token_id', true);
                    $token = WC_Payment_Tokens::get($token_id);
                    $token->set_token($response['customer_vault_id']); // Token comes from payment processor
                    $token->set_gateway_id('nmi-for-woocommerce');
                    $token->set_last4($last_four_digits);
                    $token->set_expiry_year('20' . substr($ccexp_expiry, 2, 7));
                    $token->set_expiry_month(substr($ccexp_expiry, 0, 2));
                    $token->set_card_type($cardtype);
                    $token->set_user_id(get_current_user_id());
                    // Save the new token to the database
                    $token->save();

                    update_user_meta(get_current_user_id(), 'nmi_cc_token_id', $token->get_id());
                }

                return array(
                    'result' => 'success',
                    'redirect' => wc_get_endpoint_url('payment-methods'),
                );
            } else {
                wc_add_notice(
                    sprintf(
                        __('Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce'),
                        esc_html($response['response_code']),
                        esc_html($response['responsetext'])
                    ),
                    'error'
                );
                return;
            }
        } else {
            wc_add_notice(
                sprintf(
                    __('Please make sure you have entered the Credit Card details. %s', 'nmi-for-woocommerce'),
                    esc_html(print_r($response, true))
                ),
                'error'
            );
            return;
        }
    } else {
        // Note to reviewers: Extracted and sanitized only the required field from $_POST instead of processing the entire $_POST array.
        wc_add_notice(
            sprintf(
                __('Failed to save the Card. %s', 'nmi-for-woocommerce'),
                esc_html(print_r(array('woonmi_payment_token' => $woonmi_payment_token), true))
            ),
           __('error', 'nmi-for-woocommerce')
        );
        return;
    }
}




	public function getAVSresponse( $code ) {
		$avsresponse = array(
			'X' => 'Exact match, 9-character numeric ZIP',
			'Y' => 'Exact match, 5-character numeric ZIP',
			'D' => 'Exact match, 5-character numeric ZIP',
			'M' => 'Exact match, 5-character numeric ZIP',
			'2' => 'Exact match, 5-character numeric ZIP, customer name',
			'6' => 'Exact match, 5-character numeric ZIP, customer name',
			'A' => 'Address match only',
			'B' => 'Address match only',
			'3' => 'Address, customer name match only',
			'7' => 'Address, customer name match only',
			'W' => '9-character numeric ZIP match only',
			'Z' => '5-character ZIP match only',
			'P' => '5-character ZIP match only',
			'L' => '5-character ZIP match only',
			'1' => '5-character ZIP, customer name match only',
			'5' => '5-character ZIP, customer name match only',
			'N' => 'No address or ZIP match only',
			'C' => 'No address or ZIP match only',
			'4' => 'No address or ZIP or customer name match only',
			'8' => 'No address or ZIP or customer name match only',
			'U' => 'Address unavailable',
			'G' => 'Non-U.S. issuer does not participate',
			'I' => 'Non-U.S. issuer does not participate',
			'R' => 'Issuer system unavailable',
			'E' => 'Not a mail/phone order',
			'S' => 'Service not supported',
			'0' => 'AVS not available',
			'O' => 'AVS not available',
			'B' => 'AVS not available',
		);

		return $avsresponse[ $code ];
	}

	public function getCVVresponse( $code ) {
		$cvvresponse = array(
			'M' => 'CVV2/CVC2 match',
			'N' => 'CVV2/CVC2 no match',
			'P' => 'Not processed',
			'S' => 'Merchant has indicated that CVV2/CVC2 is not present on card',
			'U' => 'Issuer is not certified and/or has not provided Visa encryption keys',
		);

		return $cvvresponse[ $code ];
	}

	public function getCoderesponse( $code ) {
		$response = array(
			'100' => 'Transaction was approved.',
			'200' => 'Transaction was declined by processor.',
			'201' => 'Do not honor.',
			'202' => 'Insufficient funds.',
			'203' => 'Over limit.',
			'204' => 'Transaction not allowed.',
			'220' => 'Incorrect payment information.',
			'221' => 'No such card issuer.',
			'222' => 'No card number on file with issuer.',
			'223' => 'Expired card.',
			'224' => 'Invalid expiration date.',
			'225' => 'Invalid card security code.',
			'226' => 'Invalid PIN.',
			'240' => 'Call issuer for further information.',
			'250' => 'Pick up card.',
			'251' => 'Lost card.',
			'252' => 'Stolen card.',
			'253' => 'Fraudulent card.',
			'260' => 'Declined with further instructions available. (See response text)',
			'261' => 'Declined-Stop all recurring payments.',
			'262' => 'Declined-Stop this recurring program.',
			'263' => 'Declined-Update cardholder data available.',
			'264' => 'Declined-Retry in a few days.',
			'300' => 'Transaction was rejected by gateway.',
			'400' => 'Transaction error returned by processor.',
			'410' => 'Invalid merchant configuration.',
			'411' => 'Merchant account is inactive.',
			'420' => 'Communication error.',
			'421' => 'Communication error with issuer.',
			'430' => 'Duplicate transaction at processor.',
			'440' => 'Processor format error.',
			'441' => 'Invalid transaction information.',
			'460' => 'Processor feature not available.',
			'461' => 'Unsupported card type.',
		);

		return $response[ $code ];
	}
}

?>