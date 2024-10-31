<?php


 // Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('woocommerce_payment_token_deleted', 'woonswpclubz_woonmi_echeck_payment_token_deleted', 10, 2);

/**
 * Delete token from NMI.
 */
function woonswpclubz_woonmi_echeck_payment_token_deleted($token_id, $token)
{
    $gateway = new woonswpclubz_woonmi_echeck();

    if ('woonmi_echeck' === $token->get_gateway_id()) {
        $provider = $gateway->provider;

        $nmi_adr = $gateway->$provider.'?';

        $woonmi_echeck_args['security_key'] = $gateway->security_key;
        $woonmi_echeck_args['customer_vault'] = 'delete_customer';
        $woonmi_echeck_args['customer_vault_id'] = $token->get_token();

        $name_value_pairs = array();
        foreach ($woonmi_echeck_args as $key => $value) {
            $name_value_pairs[] = $key.'='.urlencode($value);
        }
        $gateway_values = implode('&', $name_value_pairs);

        $response = wp_remote_post($nmi_adr, array('body' => $gateway_values, 'sslverify' => false, 'timeout' => 60));

        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
            parse_str($response['body'], $response);
            if ($response['response'] == '1') {
            } else {
                if (strpos($response['responsetext'], 'Invalid Customer Vault Id') !== false) {
                } else {
                    wc_add_notice(sprintf(__('Deleting card failed. %1$s-%2$s', 'nmi-for-woocommerce'), $response['response_code'], $response['responsetext']), $notice_type = 'error');

                    return;
                }
            }
        } else {
          wc_add_notice(
			sprintf(
			__( 'There was an error processing your request. %s', 'nmi-for-woocommerce' ),
			print_r($response, true)
			),
			'error'
			);

            return;
        }
    }
}

class woonswpclubz_woonmi_echeck extends WC_Payment_Gateway_eCheck
{
    public function __construct()
    {
        global $woocommerce;
        $this->id = 'woonmi_echeck';
        $this->method_title = __('Network Merchants Inc - eCheck', 'nmi-for-woocommerce');
		$this->method_description = __( 'Integrates eCheck payment fields directly into the checkout page, ensuring that payment details are securely transmitted to the gateway for transaction processing.' , 'nmi-for-woocommerce' );
        $this->icon = apply_filters('woonswpclubz_woonmi_echeck_icon', '');
        $this->has_fields = true;

        $this->nmi = 'https://secure.networkmerchants.com/api/transact.php';
        $this->alphacard = 'https://secure.alphacardgateway.com/api/transact.php';
        $this->llms = 'https://secure.llmsgateway.com/api/transact.php';
        $this->powerpay = 'https://verifi.powerpay.biz/api/transact.php';
        $this->sbg = 'https://secure.skybankgateway.com/api/transact.php';
        $this->mgg = 'https://secure.merchantguygateway.com/api/transact.php';
        $this->durango = 'https://secure.durango-direct.com/api/transact.php';
        $this->tnbci = 'https://secure.tnbcigateway.com/api/transact.php';
        $this->payscape = 'https://secure.payscapegateway.com/api/transact.php';
        $this->paylinedata = 'https://secure.paylinedatagateway.com/api/transact.php';
        $this->inspire = 'https://secure.inspiregateway.net/api/transact.php';
        $this->safesave = 'https://secure.safesavegateway.com/api/transact.php';
        $this->tranzcrypt = 'https://secure.tranzcrypt.com/api/transact.php';
        $this->planetauth = 'https://secure.planetauthorizegateway.com/api/transact.php';
        $this->cyogate = 'https://secure.cyogate.net/api/transact.php';
        $this->fluidpay = 'https://app.fluidpay.com/api/transact.php';
		$this->glacierpay   = 'https://secure.glacierpaygateway.com/api/transact.php';
        $this->supports = array(
      'products',
      'refunds',
      'tokenization',
      'subscriptions',
      'subscription_cancellation',
      'subscription_suspension',
      'subscription_reactivation',
      'subscription_payment_method_change',
    );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->security_key = $this->get_option('security_key');
        $this->descriptor = $this->get_option('descriptor');
        $this->descriptor_url = $this->get_option('descriptor_url');
        $this->processor_id = $this->get_option('processor_id');
        $this->dup_seconds = $this->get_option('dup_seconds');
        $this->provider = $this->get_option('provider');
        $this->transtype = $this->get_option('transtype');
        $this->saved_cards = 'yes' === $this->get_option('saved_cards');
        $this->receipt = 'yes' === $this->get_option('receipt');
        $this->send_details = 'yes' === $this->get_option('send_details');
        $this->debug = $this->get_option('debug');

        if ($this->provider == '') {
            $this->provider = 'nmi';
        }

        if ($this->transtype == '') {
            $this->transtype = 'sale';
        }

        // Logs
        if ($this->debug == 'yes') {
            $this->log = new WC_Logger();
        }

        // Actions
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_subscription_cancelled_'.$this->id, array($this, 'cancelled_subscription'), 10, 2);
        }

        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }
    }

    /**
     * cancelled_subscription function.
     *
     * @param float    $amount_to_charge the amount to charge
     * @param WC_Order $renewal_order    a WC_Order object created to record the renewal payment
     */
    public function cancelled_subscription($order)
    {
        //$profile_id = self::get_subscriptions_woonmi_id( $order );

        $profile_id = get_post_meta($order->parent_id, 'NMI Subscriber ID', true);

        // Make sure a subscriptions status is active with NMI
        $woonmi_echeck_args['security_key'] = $this->security_key;
        $woonmi_echeck_args['recurring'] = 'delete_subscription';
        $woonmi_echeck_args['subscription_id'] = $profile_id;

        $name_value_pairs = array();
        foreach ($woonmi_echeck_args as $key => $value) {
            $name_value_pairs[] = $key.'='.urlencode($value);
        }
        $gateway_values = implode('&', $name_value_pairs);

        $provider = $this->provider;

        $nmi_adr = $this->$provider.'?';

        $response = wp_remote_post($nmi_adr, array('body' => $gateway_values, 'sslverify' => false, 'timeout' => 60));

        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
            parse_str($response['body'], $response);

            if ($response['response'] == '1') {
                $order->add_order_note(__('Subscription cancelled with NMI', 'nmi-for-woocommerce'));
            } else {
                $order->add_order_note(__('There was error cancelling the Subscription with NMI', 'nmi-for-woocommerce'));
            }
        }
    }

    /**
     * Returns a NMI Subscription ID/Recurring Payment Profile ID based on a user ID and subscription key.
     *
     * @since 1.1
     */
    public static function get_subscriptions_woonmi_echeck_id($order, $product_id)
    {
        $profile_id = get_post_meta($order->id, 'NMI Subscriber ID', true);

        return $profile_id;
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     */
    public function is_valid_for_use()
    {
        if (!in_array(get_option('woocommerce_currency'), array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP'))) {
            return false;
        }

        return true;
    }

    private function force_ssl($url)
    {
        if ('yes' == get_option('woocommerce_force_ssl_checkout')) {
            $url = str_replace('http:', 'https:', $url);
        }

        return $url;
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis.
     *
     * @since 1.0.0
     */
    public function admin_options()
    {
        ?>
    <h3><?php esc_html_e('Network Merchants Inc - eCheck', 'nmi-for-woocommerce'); ?></h3>
    <p><?php esc_html_e('Network Merchants Inc - eCheck works by processing the ACH Account Payments on your site.', 'nmi-for-woocommerce'); ?></p>
    <table class="form-table">
    <?php
      if ($this->is_valid_for_use()) :

        // Generate the HTML For the settings form.
        $this->generate_settings_html(); else :

        ?>
              <div class="inline error"><p><strong><?php esc_html_e('Gateway Disabled', 'nmi-for-woocommerce'); ?></strong>: <?php esc_html_e('NMI does not support your store currency.', 'nmi-for-woocommerce'); ?></p></div>
          <?php

      endif; ?>
    </table><!--/.form-table-->
    <?php
    }

    // End admin_options()

    /**
     *  Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
      'enabled' => array(
        'title' => __('Enable/Disable', 'nmi-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable Network Merchants Inc - eCheck', 'nmi-for-woocommerce'),
        'default' => 'yes',
        'desc_tip' => true,
      ),
      'title' => array(
        'title' => __('Title', 'nmi-for-woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'nmi-for-woocommerce'),
        'default' => __('eCheck', 'nmi-for-woocommerce'),
        'desc_tip' => true,
      ),
      'description' => array(
        'title' => __('Description', 'nmi-for-woocommerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'nmi-for-woocommerce'),
        'default' => __('Pay via NMI; you can pay with your ACH Account.', 'nmi-for-woocommerce'),
        'desc_tip' => true,
      ),
      'security_key' => array(
        'title' => __('Private Security Key', 'nmi-for-woocommerce'),
        'type' => 'password',
        'description' => __('Please enter your Private Security Key; this is needed in order to take payment.', 'nmi-for-woocommerce'),
        'default' => '',
        'desc_tip' => true,
      ),
      'descriptor' => array(
        'title' => __('Descriptor', 'nmi-for-woocommerce'),
        'type' => 'text',
        'description' => __('Set payment descriptor on supported processors.', 'nmi-for-woocommerce'),
        'default' => '',
        'desc_tip' => true,
      ),
      'descriptor_url' => array(
        'title' => __('Descriptor URL', 'nmi-for-woocommerce'),
        'type' => 'text',
        'description' => __('Set payment descriptor url on supported processors.', 'nmi-for-woocommerce'),
        'default' => '',
        'desc_tip' => true,
      ),
      'dup_seconds' => array(
        'title' => __('Duplicate Seconds Check', 'nmi-for-woocommerce'),
        'type' => 'text',
        'description' => __('Sets the time in seconds for duplicate transaction checking on supported processors. Set to 0 to disable duplicate checking.', 'nmi-for-woocommerce'),
        'default' => '0',
        'desc_tip' => true,
      ),
      'processor_id' => array(
        'title' => __('Processor ID', 'nmi-for-woocommerce'),
        'type' => 'text',
        'description' => __('If using Multiple MIDs, route to this processor (processor_id is obtained under Settings->Transaction Routing in the Control Panel).', 'nmi-for-woocommerce'),
        'default' => '',
        'desc_tip' => true,
      ),
      'receipt' => array(
        'title' => __('Enable/Disable Customer Receipt', 'nmi-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable Customer Receipt', 'nmi-for-woocommerce'),
        'description' => __('If enabled, when the customer is charged, they will be sent a transaction receipt.', 'nmi-for-woocommerce'),
        'default' => 'yes',
        'desc_tip' => true,
      ),
      'saved_cards' => array(
        'title' => __('Save Account', 'nmi-for-woocommerce'),
        'label' => __('Enable Payment via Saved Account', 'nmi-for-woocommerce'),
        'type' => 'checkbox',
        'description' => __('If enabled, users will be able to pay with a saved account during checkout. Account details are saved on NMI servers, not on your store.', 'nmi-for-woocommerce'),
        'default' => 'no',
        'desc_tip' => true,
      ),
      'send_details' => array(
        'title' => __('Send Cart Details', 'nmi-for-woocommerce'),
        'label' => __('Enable Send Cart Details', 'nmi-for-woocommerce'),
        'type' => 'checkbox',
        'description' => __('If enabled, plugin will send the product details to NMI.', 'nmi-for-woocommerce'),
        'default' => 'yes',
        'desc_tip' => true,
      ),
      'transtype' => array(
        'title' => __('Transaction Type', 'nmi-for-woocommerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'options' => array(
          'sale' => 'Sale (Authorize and Capture)',
          'auth' => 'Authorize Only',
        ),
        'description' => __('Select your Transaction Type.', 'nmi-for-woocommerce'),
        'default' => 'sale',
        'desc_tip' => true,
      ),
      'provider' => array(
        'title' => __('Payment Gateway', 'nmi-for-woocommerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'options' => array(
          'nmi' => 'Network Merchants Inc',
          'alphacard' => 'Alpha Card Services',
          'llms' => 'LL Merchant Solutions',
          'powerpay' => 'PowerPay',
          'sbg' => 'SkyBank',
          'mgg' => 'MerchantGuy',
          'durango' => 'Durango Merchant Services',
          'tnbci' => 'TransNational Bankcard Inc',
          'payscape' => 'Payscape',
          'paylinedata' => 'Payline Data',
          'inspire' => 'Inspire Commerce',
          'safesave' => 'SafeSave Payments',
          'tranzcrypt' => 'Tranzcrypt',
          'planetauth' => 'PlanetAuthorize',
          'cyogate' => 'CyoGate',
          'fluidpay' => 'Fluid Pay',
          'glacierpay' => 'Glacier Payments',
        ),
        'description' => __('Select your Merchant Account Provider.', 'nmi-for-woocommerce'),
        'default' => 'nmi',
        'desc_tip' => true,
      ),
      'debug' => array(
        'title' => __('Debug log', 'nmi-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'nmi-for-woocommerce'),
        'default' => 'no',
        'description' => sprintf(__('Log NMI requests, inside %s', 'nmi-for-woocommerce'), '<code>'.WC_Log_Handler_File::get_log_file_path('woonmi_echeck').'</code>'),
      ),
    );
    }

    // End init_form_fields()

    /**
     * There are no payment fields for nmi, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        $user = wp_get_current_user();
        $display_tokenization = $this->supports('tokenization') && is_checkout() && $this->saved_cards /*&& $user->ID*/;

        if ($user->ID) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }

        if (is_add_payment_method_page()) {
            $pay_button_text = __('Add Card', 'nmi-for-woocommerce');
        } else {
            $pay_button_text = 'Test';
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



        if ($display_tokenization) {
            $this->tokenization_script();
            $this->saved_payment_methods();
        }
        wp_enqueue_script('wc-credit-card-form');
        //$this->form();
        echo '
    <fieldset id="woonmi-cc-check" class="wc-echeck-form wc-payment-form" >
      <p class="form-row form-row-wide">
        <label for="woonmi-account-name">'.esc_html__('Account Name', 'nmi-for-woocommerce').' <span class="required">*</span></label>
        <input id="woonmi-account-name" class="input-text" style="font-size: 1.5em;padding: 8px;background-repeat: no-repeat;background-position: right .618em center;background-size: 32px 20px;" type="text" maxlength="50" autocomplete="off" name="woonmi-account-name" />
      </p>
      <p class="form-row form-row-first">
        <label for="woonmi-account-type">'.esc_html__('Account Type', 'nmi-for-woocommerce').' <span class="required">*</span></label>
        <select name="woonmi-account-type" class="input-text" style="width:100%; font-size: 1.5em;padding: 8px;background-repeat: no-repeat;background-position: right .618em center;background-size: 32px 20px;">
          <option value="checking">Checking</option>
          <option value="saving">Saving</option>
        </select>
      </p>
      <p class="form-row form-row-last">
        <label for="woonmi-holder-type">'.esc_html__('Account Holder Type', 'nmi-for-woocommerce').' <span class="required">*</span></label>
        <select name="woonmi-holder-type" class="input-text" style="width:100%; font-size: 1.5em;padding: 8px;background-repeat: no-repeat;background-position: right .618em center;background-size: 32px 20px;">
          <option value="personal">Personal</option>
          <option value="business">Business</option>
        </select>
      </p>
      <p class="form-row form-row-first">
        <label for="woonmi-routing-number">'.esc_html__('Routing number', 'nmi-for-woocommerce').' <span class="required">*</span></label>
        <input id="woonmi-routing-number" style="font-size: 1.5em;padding: 8px;background-repeat: no-repeat;background-position: right .618em center;background-size: 32px 20px;" class="input-text"  type="text" maxlength="9" autocomplete="off" name="woonmi-routing-number" />
      </p>
      <p class="form-row form-row-last">
        <label for="woonmi-account-number">'.esc_html__('Account number', 'nmi-for-woocommerce').' <span class="required">*</span></label>
        <input id="woonmi-account-number" class="input-text" style="font-size: 1.5em;padding: 8px;background-repeat: no-repeat;background-position: right .618em center;background-size: 32px 20px;" type="text" autocomplete="off" name="woonmi-account-number" maxlength="17" />
      </p>
      <div class="clear"></div>
    </fieldset>';

        if ($display_tokenization) {
            $this->save_payment_method_checkbox();
        }
    }

    public function validate_fields()
    {
        global $woocommerce;

        if (isset($_POST['wc-woonmi_echeck-payment-token']) && 'new' !== $_POST['wc-woonmi_echeck-payment-token']) {
            $token_id = wc_clean($_POST['wc-woonmi_echeck-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            if ($token->get_user_id() !== get_current_user_id()) {
                // Optionally display a notice with `wc_add_notice`
                wc_add_notice(__('There was error processing payment using token please use the card details to continue the checkout.', 'nmi-for-woocommerce'), $notice_type = 'error');
            }
        } else {
            if (empty($_POST['woonmi-account-name'])) {
                wc_add_notice('<strong>Account Name</strong> '.__('is a required field.', 'nmi-for-woocommerce'), 'error');
            }

            if (empty($_POST['woonmi-routing-number'])) {
                wc_add_notice('<strong>Routing Number</strong> '.__('is a required field.', 'nmi-for-woocommerce'), 'error');
            }

            if (empty($_POST['woonmi-account-number'])) {
                wc_add_notice('<strong>Account Number</strong> '.__('is a required field.', 'nmi-for-woocommerce'), 'error');
            }
        }
    }

    /**
     * Process the payment and return the result.
     **/
    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $provider = $this->provider;

		$checkname = isset($_POST['woonmi-account-name']) ? sanitize_text_field($_POST['woonmi-account-name']) : '';
		$checkaba = isset($_POST['woonmi-routing-number']) ? sanitize_text_field($_POST['woonmi-routing-number']) : '';
		$checkaccount = isset($_POST['woonmi-account-number']) ? sanitize_text_field($_POST['woonmi-account-number']) : '';
		$account_holder_type = isset($_POST['woonmi-holder-type']) ? sanitize_text_field($_POST['woonmi-holder-type']) : '';
		$account_type = isset($_POST['woonmi-account-type']) ? sanitize_text_field($_POST['woonmi-account-type']) : '';


        $nmi_adr = $this->$provider.'?';

        $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), 'woonmi_echeck');

        if ($this->debug == 'yes') {
			$order_id_sanitized = sanitize_text_field($order_id);
			$this->log->add('woonmi_echeck', esc_html($order_id_sanitized) . ' - eCheck Processing Started');
		}


        if (isset($_POST['wc-woonmi_echeck-new-payment-method']) && count($tokens) == 0) {
            $woonmi_echeck_args['customer_vault'] = 'add_customer';

            $woonmi_echeck_args['checkname'] = $checkname;
            $woonmi_echeck_args['checkaba'] = $checkaba;
            $woonmi_echeck_args['checkaccount'] = $checkaccount;
            $woonmi_echeck_args['account_holder_type'] = $account_holder_type;
            $woonmi_echeck_args['account_type'] = $account_type;

            $last_four_digits = substr($checkaccount, -4);

            if ($this->debug == 'yes') {
				$order_id_sanitized = sanitize_text_field($order_id);
				$token_count_sanitized = intval(count($tokens)); // Ensures token count is an integer
				$payment_method_param = isset($_POST['wc-woonmi_echeck-new-payment-method']) ? sanitize_text_field($_POST['wc-woonmi_echeck-new-payment-method']) : '';

				$this->log->add('woonmi_echeck', esc_html($order_id_sanitized) . ' - Token Count: ' . esc_html($token_count_sanitized) . ' Token Param: ' . esc_html($payment_method_param));
			}

        } elseif (isset($_POST['wc-woonmi_echeck-new-payment-method']) && isset($_POST['wc-woonmi_echeck-payment-token']) && 'new' == $_POST['wc-woonmi_echeck-payment-token'] && count($tokens) > 0) {
            $token = WC_Payment_Tokens::get(get_user_meta(get_current_user_id(), 'nmi_check_token_id', true));

            $woonmi_echeck_args['customer_vault'] = 'update_customer';
            $woonmi_echeck_args['customer_vault_id'] = $token->get_token();
            $woonmi_echeck_args['checkname'] = $checkname;
            $woonmi_echeck_args['checkaba'] = $checkaba;
            $woonmi_echeck_args['checkaccount'] = $checkaccount;
            $woonmi_echeck_args['account_holder_type'] = $account_holder_type;
            $woonmi_echeck_args['account_type'] = $account_type;

            $last_four_digits = substr($checkaccount, -4);

            if ($this->debug == 'yes') {
			$order_id_sanitized = sanitize_text_field($order_id);
			$token_id_sanitized = sanitize_text_field($token->get_id());
			$payment_method_param = isset($_POST['wc-woonmi_echeck-new-payment-method']) ? sanitize_text_field($_POST['wc-woonmi_echeck-new-payment-method']) : '';

			$this->log->add('woonmi_echeck', esc_html($order_id_sanitized) . ' - Custom Saved Token : ' . esc_html($token_id_sanitized) . ' Token Param: ' . esc_html($payment_method_param));
		}

        } elseif (isset($_POST['wc-woonmi_echeck-payment-token']) && 'new' !== $_POST['wc-woonmi_echeck-payment-token']) {
            $token_id = wc_clean($_POST['wc-woonmi_echeck-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $woonmi_echeck_args['customer_vault'] = 'update_customer';
            $woonmi_echeck_args['customer_vault_id'] = $token->get_token();

            if ($this->debug == 'yes') {
				$order_id_sanitized = sanitize_text_field($order_id);
				$token_id_sanitized = sanitize_text_field($token_id);
				$payment_method_param = isset($_POST['wc-woonmi_echeck-new-payment-method']) ? sanitize_text_field($_POST['wc-woonmi_echeck-new-payment-method']) : '';

				$this->log->add('woonmi_echeck', esc_html($order_id_sanitized) . ' - Token : ' . esc_html($token_id_sanitized) . ' Token Param: ' . esc_html($payment_method_param));
			}

        } else {
            $woonmi_echeck_args['checkname'] = $checkname;
            $woonmi_echeck_args['checkaba'] = $checkaba;
            $woonmi_echeck_args['checkaccount'] = $checkaccount;
            $woonmi_echeck_args['account_holder_type'] = $account_holder_type;
            $woonmi_echeck_args['account_type'] = $account_type;

            $last_four_digits = substr($checkaccount, -4);

            if ($this->debug == 'yes') {
				$order_id_sanitized = sanitize_text_field($order_id);
				$message = esc_html__('New ACH Account with no Tokenisation', 'nmi-for-woocommerce');
				
				$this->log->add('woonmi_echeck', esc_html($order_id_sanitized) . ' - ' . $message);
			}

        }

        if ($this->receipt) {
            $woonmi_echeck_args['customer_receipt'] = $this->receipt;
        }

        // Processing subscription
        if (function_exists('wcs_order_contains_subscription') || function_exists('wcs_is_subscription')) {
            if (wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id)) {
                $woonmi_echeck_args['type'] = $this->transtype;
                $woonmi_echeck_args['payment'] = 'check';
				$woonmi_echeck_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
                $woonmi_echeck_args['security_key'] = $this->security_key;
                $woonmi_echeck_args['currency'] = get_woocommerce_currency();

                $woonmi_echeck_args['orderid'] = $order->get_order_number().'-'.time();

                $woonmi_echeck_args = $this->set_billing_and_shipping_address($woonmi_echeck_args, $order);

                $woonmi_echeck_args['email'] = $order->get_billing_email();

                $woonmi_echeck_args['invoice'] = $order->order_key;

                $AmountInput = number_format($order->order_total, 2, '.', '');

                $woonmi_echeck_args['amount'] = $AmountInput;

                if (in_array($order->billing_country, array('US', 'CA'))) {
                    $order->billing_phone = str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->get_billing_phone());
                    $woonmi_echeck_args['phone'] = $order->billing_phone;
                } else {
                    $woonmi_echeck_args['phone'] = $order->get_billing_phone();
                }
                //var_dump($order->get_total_tax());die;
                // Tax
                $woonmi_echeck_args['tax'] = $order->get_total_tax();

                if ($this->send_details) {
                    // Cart Contents
                    $item_loop = 0;
                    if (sizeof($order->get_items()) > 0) {
                        foreach ($order->get_items() as $item) {
                            if ($item->get_quantity() > 0) {
                                ++$item_loop;

                                $item_name = $item->get_name();
                                $item_meta = wp_strip_all_tags(wc_display_item_meta($item, array(
                          'before' => '',
                          'separator' => ', ',
                          'after' => '',
                          'echo' => false,
                          'autop' => false,
                      )));
                                if ($item_meta) {
                                    $item_name .= ' ('.$item_meta.')';
                                }

                                $product = $item->get_product();

                                $sku = $product->get_sku();
                                if (empty($product->get_sku()) || $product->get_sku() == '') {
                                    $sku = 'N/A';
                                }

                                $woonmi_echeck_args['item_product_code_'.$item_loop] = $sku;
                                $woonmi_echeck_args['item_tax_amount_'.$item_loop] = $item->get_total_tax();
                                $woonmi_echeck_args['item_description_'.$item_loop] = $item_name;
                                $woonmi_echeck_args['item_unit_cost_'.$item_loop] = $order->get_item_subtotal($item, false);
                                $woonmi_echeck_args['item_quantity_'.$item_loop] = $item->get_quantity();
                                $item_total_amount = $order->get_item_subtotal($item, false) * $item->get_quantity();
                                $woonmi_echeck_args['item_total_amount_'.$item_loop] = $item_total_amount;
                            }
                        }
                    }
                }

                // Discount
                if ($order->get_total_discount() > 0) {
                    $woonmi_echeck_args['discount_amount'] = number_format($order->get_total_discount(), 2, '.', '');
                }

                // Shipping Cost item - woonmi_echeck only allows shipping per item, we want to send shipping for the order
                if ($order->get_total_shipping() > 0) {
                    $woonmi_echeck_args['shipping'] = number_format($order->get_total_shipping(), 2, '.', '');
                }

                $subscriptions = wcs_get_subscriptions_for_order($order);

                $subscription = array_pop($subscriptions);

                if (!empty($subscription)) {
                    $order_items = $order->get_items();

                    $unconverted_periods = array(
                  'billing_period' => $subscription->billing_period,
                  'trial_period' => $subscription->trial_period,
                );

                    $converted_periods = array();

                    // Convert period strings into PayPay's format
                    foreach ($unconverted_periods as $key => $period) {
                        switch (strtolower($period)) {
              case 'day':
                $converted_periods[$key] = 'day';
                break;
              case 'week':
                $converted_periods[$key] = 'week';
                break;
              case 'year':
                $converted_periods[$key] = 'year';
                break;
              case 'month':
              default:
                $converted_periods[$key] = 'month';
                break;
            }
                    }

                    $sign_up_fee = $subscription->get_sign_up_fee();
                    $price_per_period = $subscription->get_total();
                    $subscription_interval = $subscription->billing_interval;
                    $start_timestamp = $subscription->get_time('start');
                    $trial_end_timestamp = $subscription->get_time('trial_end');
                    $next_payment_timestamp = $subscription->get_time('next_payment');

                    $is_synced_subscription = WC_Subscriptions_Synchroniser::subscription_contains_synced_product($subscription->id);

                    if ($is_synced_subscription) {
                        $length_from_timestamp = $next_payment_timestamp;
                    } elseif ($trial_end_timestamp > 0) {
                        $length_from_timestamp = $trial_end_timestamp;
                    } else {
                        $length_from_timestamp = $start_timestamp;
                    }

                    $subscription_length = wcs_estimate_periods_between($length_from_timestamp, $subscription->get_time('end'), $subscription->billing_period);

                    $subscription_installments = $subscription_length / $subscription_interval;

                    $initial_payment = $order->get_total();

                    $initial_payment = 0;

                    if ($initial_payment == '0.00') {
                        $initial_payment = '0.01';
                    }

                    if ($subscription_trial_length > 0) {
                        $trial_until = wcs_calculate_paypal_trial_periods_until($next_payment_timestamp);

                        $subscription_trial_length = $trial_until['first_trial_length'];
                        $converted_periods['trial_period'] = $trial_until['first_trial_period'];

                        $dateformat = 'Ymd';
                        $todayDate = gmdate($dateformat);
                        $startdate = gmdate($dateformat, strtotime(gmdate($dateformat, strtotime($todayDate)).' +'.$subscription_trial_length.' '.$converted_periods['trial_period']));

                        $woonmi_echeck_args['plan_payments'] = $subscription_installments;

                        if ($subscription_installments > 1) {
                            $woonmi_echeck_args['plan_payments'] = $subscription_installments - 1;
                        }

                        $woonmi_echeck_args['amount'] = $initial_payment;

                        $woonmi_echeck_args['plan_amount'] = $price_per_period;

                        if ($converted_periods['billing_period'] == 'day') {
                            $woonmi_echeck_args['day_frequency'] = $subscription_interval;
                        } elseif ($converted_periods['billing_period'] == 'week') {
                            $woonmi_echeck_args['day_frequency'] = $subscription_interval * 7;
                        } elseif ($converted_periods['billing_period'] == 'year') {
                            $woonmi_echeck_args['month_frequency'] = $subscription_interval * 12;
                            $timestamp = strtotime($startdate);
                            $day = gmdate('d', $timestamp);
                            $woonmi_echeck_args['day_of_month'] = $day;
                        } else {
                            $woonmi_echeck_args['month_frequency'] = $subscription_interval;
                            $timestamp = strtotime($startdate);
                            $day = gmdate('d', $timestamp);
                            $woonmi_echeck_args['day_of_month'] = $day;
                        }
                    } else {
                        $dateformat = 'Ymd';
                        $startdate = gmdate($dateformat);

                        $woonmi_echeck_args['plan_payments'] = $subscription_installments;

                        $woonmi_echeck_args['amount'] = $initial_payment;

                        $woonmi_echeck_args['plan_amount'] = $price_per_period;

                        if ($converted_periods['billing_period'] == 'day') {
                            $woonmi_echeck_args['day_frequency'] = $subscription_interval;
                            $startdate = gmdate($dateformat, strtotime(gmdate($dateformat, strtotime($startdate)).' +1 day'));
                        } elseif ($converted_periods['billing_period'] == 'week') {
                            $woonmi_echeck_args['day_frequency'] = $subscription_interval * 7;
                            $startdate = gmdate($dateformat, strtotime(gmdate($dateformat, strtotime($startdate)).' +1 week'));
                        } elseif ($converted_periods['billing_period'] == 'year') {
                            $woonmi_echeck_args['month_frequency'] = $subscription_interval * 12;
                            $startdate = gmdate($dateformat, strtotime(gmdate($dateformat, strtotime($startdate)).' +1 year'));
                            $timestamp = strtotime($startdate);
                            $day = gmdate('d', $timestamp);
                            $woonmi_echeck_args['day_of_month'] = $day;
                        } else {
                            $woonmi_echeck_args['month_frequency'] = $subscription_interval;
                            $timestamp = strtotime($startdate);
                            $day = gmdate('d', $timestamp);
                            $woonmi_echeck_args['day_of_month'] = $day;
                            $startdate = gmdate($dateformat, strtotime(gmdate($dateformat, strtotime($startdate)).' +1 month'));
                        }
                    }

                    $woonmi_echeck_args['start_date'] = $startdate;

                    $woonmi_echeck_args['recurring'] = 'add_subscription';

                    $woonmi_echeck_args['billing_method'] = 'recurring';
                }
            } else {
                $woonmi_echeck_args['type'] = $this->transtype;
                $woonmi_echeck_args['payment'] = 'check';
				$woonmi_echeck_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
                $woonmi_echeck_args['security_key'] = $this->security_key;
                $woonmi_echeck_args['currency'] = get_woocommerce_currency();

                $woonmi_echeck_args['orderid'] = $order->get_order_number().'-'.time();

                $woonmi_echeck_args = $this->set_billing_and_shipping_address($woonmi_echeck_args, $order);

                $woonmi_echeck_args['email'] = $order->get_billing_email();

                $woonmi_echeck_args['invoice'] = $order->order_key;

                $AmountInput = number_format($order->order_total, 2, '.', '');

                $woonmi_echeck_args['amount'] = $AmountInput;

                if (in_array($order->billing_country, array('US', 'CA'))) {
                    $order->billing_phone = str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->get_billing_phone());
                    $woonmi_echeck_args['phone'] = $order->billing_phone;
                } else {
                    $woonmi_echeck_args['phone'] = $order->get_billing_phone();
                }
                //var_dump($order->get_total_tax());die;
                // Tax
                $woonmi_echeck_args['tax'] = $order->get_total_tax();

                if ($this->send_details) {
                    // Cart Contents
                    $item_loop = 0;
                    if (sizeof($order->get_items()) > 0) {
                        foreach ($order->get_items() as $item) {
                            if ($item->get_quantity() > 0) {
                                ++$item_loop;

                                $item_name = $item->get_name();
                                $item_meta = wp_strip_all_tags(wc_display_item_meta($item, array(
                          'before' => '',
                          'separator' => ', ',
                          'after' => '',
                          'echo' => false,
                          'autop' => false,
                      )));
                                if ($item_meta) {
                                    $item_name .= ' ('.$item_meta.')';
                                }

                                $product = $item->get_product();

                                $sku = $product->get_sku();
                                if (empty($product->get_sku()) || $product->get_sku() == '') {
                                    $sku = 'N/A';
                                }

                                $woonmi_echeck_args['item_product_code_'.$item_loop] = $sku;
                                $woonmi_echeck_args['item_tax_amount_'.$item_loop] = $item->get_total_tax();
                                $woonmi_echeck_args['item_description_'.$item_loop] = $item_name;
                                $woonmi_echeck_args['item_unit_cost_'.$item_loop] = $order->get_item_subtotal($item, false);
                                $woonmi_echeck_args['item_quantity_'.$item_loop] = $item->get_quantity();
                                $item_total_amount = $order->get_item_subtotal($item, false) * $item->get_quantity();
                                $woonmi_echeck_args['item_total_amount_'.$item_loop] = $item_total_amount;
                            }
                        }
                    }
                }

                // Discount
                if ($order->get_total_discount() > 0) {
                    $woonmi_echeck_args['discount_amount'] = number_format($order->get_total_discount(), 2, '.', '');
                }

                // Shipping Cost item - woonmi_echeck only allows shipping per item, we want to send shipping for the order
                if ($order->get_total_shipping() > 0) {
                    $woonmi_echeck_args['shipping'] = number_format($order->get_total_shipping(), 2, '.', '');
                }
            }

            // Processing standard
        } else {
            $woonmi_echeck_args['type'] = $this->transtype;
            $woonmi_echeck_args['payment'] = 'check';
			$woonmi_echeck_args['ipaddress'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('unknown', 'nmi-for-woocommerce');
            $woonmi_echeck_args['security_key'] = $this->security_key;
            $woonmi_echeck_args['currency'] = get_woocommerce_currency();

            $woonmi_echeck_args['orderid'] = $order->get_order_number().'-'.time();

            $woonmi_echeck_args = $this->set_billing_and_shipping_address($woonmi_echeck_args, $order);

            $woonmi_echeck_args['email'] = $order->get_billing_email();

            $woonmi_echeck_args['invoice'] = $order->order_key;

            $AmountInput = number_format($order->order_total, 2, '.', '');

            $woonmi_echeck_args['amount'] = $AmountInput;

            if (in_array($order->billing_country, array('US', 'CA'))) {
                $order->billing_phone = str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->get_billing_phone());
                $woonmi_echeck_args['phone'] = $order->billing_phone;
            } else {
                $woonmi_echeck_args['phone'] = $order->get_billing_phone();
            }
            //var_dump($order->get_total_tax());die;
            // Tax
            $woonmi_echeck_args['tax'] = $order->get_total_tax();

            if ($this->send_details) {
                // Cart Contents
                $item_loop = 0;
                if (sizeof($order->get_items()) > 0) {
                    foreach ($order->get_items() as $item) {
                        if ($item->get_quantity() > 0) {
                            ++$item_loop;

                            $item_name = $item->get_name();
                            $item_meta = wp_strip_all_tags(wc_display_item_meta($item, array(
                        'before' => '',
                        'separator' => ', ',
                        'after' => '',
                        'echo' => false,
                        'autop' => false,
                    )));
                            if ($item_meta) {
                                $item_name .= ' ('.$item_meta.')';
                            }

                            $product = $item->get_product();

                            $sku = $product->get_sku();
                            if (empty($product->get_sku()) || $product->get_sku() == '') {
                                $sku = 'N/A';
                            }

                            $woonmi_echeck_args['item_product_code_'.$item_loop] = $sku;
                            $woonmi_echeck_args['item_tax_amount_'.$item_loop] = $item->get_total_tax();
                            $woonmi_echeck_args['item_description_'.$item_loop] = $item_name;
                            $woonmi_echeck_args['item_unit_cost_'.$item_loop] = $order->get_item_subtotal($item, false);
                            $woonmi_echeck_args['item_quantity_'.$item_loop] = $item->get_quantity();
                            $item_total_amount = $order->get_item_subtotal($item, false) * $item->get_quantity();
                            $woonmi_echeck_args['item_total_amount_'.$item_loop] = $item_total_amount;
                        }
                    }
                }

                if (!empty($woonmi_echeck_args['discount_amount'])) {
                    ++$item_loop;

                    $woonmi_echeck_args['item_product_code_'.$item_loop] = 'N/A';
                    $woonmi_echeck_args['item_tax_amount_'.$item_loop] = 0;
                    $woonmi_echeck_args['item_description_'.$item_loop] = 'Discount';
                    $woonmi_echeck_args['item_unit_cost_'.$item_loop] = -$woonmi_echeck_args['discount_amount'];
                    $woonmi_echeck_args['item_quantity_'.$item_loop] = 1;
                    $woonmi_echeck_args['item_total_amount_'.$item_loop] = -$woonmi_echeck_args['discount_amount'];
                }
            }

            // Discount
            if ($order->get_total_discount() > 0) {
                $woonmi_echeck_args['discount_amount'] = number_format($order->get_total_discount(), 2, '.', '');
            }

            // Shipping Cost item - woonmi_echeck only allows shipping per item, we want to send shipping for the order
            if ($order->get_total_shipping() > 0) {
                $woonmi_echeck_args['shipping'] = number_format($order->get_total_shipping(), 2, '.', '');
            }
        }

        if (!empty($_POST['descriptor']) && isset($_POST['descriptor'])) {
            $woonmi_echeck_args['descriptor'] = $_POST['descriptor'];
        } elseif (!empty($this->descriptor)) {
            $woonmi_echeck_args['descriptor'] = $this->descriptor;
        }

        if ( ! empty( $_POST['descriptor_url'] ) && isset( $_POST['descriptor_url'] ) ) {
			$woonmi_echeck_args['descriptor_url'] = esc_url_raw( sanitize_text_field( $_POST['descriptor_url'] ) );
		} elseif ( ! empty( $this->descriptor_url ) ) {
			$woonmi_echeck_args['descriptor_url'] = esc_url_raw( sanitize_text_field( $this->descriptor_url ) );
		}


        if (!empty($_POST['processor_id']) && isset($_POST['processor_id'])) {
            $woonmi_echeck_args['processor_id'] = $_POST['processor_id'];
        } elseif (!empty($this->processor_id)) {
            $woonmi_echeck_args['processor_id'] = $this->processor_id;
        }

        if (!empty($this->dup_seconds) && (int) $this->dup_seconds > 0) {
            $woonmi_echeck_args['dup_seconds'] = $this->dup_seconds;
        }

        for ($fn = 1; $fn < 21; ++$fn) {
            if (!empty($_POST["merchant_defined_field_$fn"])) {
                $woonmi_echeck_args["merchant_defined_field_$fn"] = $_POST["merchant_defined_field_$fn"];
            }
        }

        if (empty($_POST['merchant_defined_field_1'])) {
            $woonmi_echeck_args['merchant_defined_field_1'] = get_bloginfo('name');
        }

        $name_value_pairs = array();
        foreach ($woonmi_echeck_args as $key => $value) {
            $name_value_pairs[] = $key.'='.urlencode($value);
        }
        $gateway_values = implode('&', $name_value_pairs);

        $response = wp_remote_post($nmi_adr, array('body' => $gateway_values, 'sslverify' => false, 'timeout' => 60));

       if ($this->debug == 'yes') {
			$woonmi_echeck_args['checkaba'] = 'XXXXXXXXX';
			$woonmi_echeck_args['checkaccount'] = 'XXXXX' . sanitize_text_field($last_four_digits);

			// Ensure proper sanitization and escaping of $order_id and $woonmi_echeck_args
			$order_id_sanitized = sanitize_text_field($order_id);
			$args_output = print_r($woonmi_echeck_args, true);
			
			$this->log->add(
				'woonmi_echeck',
				esc_html($order_id_sanitized) . ' - ' . esc_html__('NMI eCheck Order Request: ', 'nmi-for-woocommerce') . esc_html($args_output)
			);
		}


        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
            parse_str($response['body'], $response);

           if ($this->debug == 'yes') {
				$response_output = print_r($response, true);
				$this->log->add(
					'woonmi_echeck',
					esc_html(sanitize_text_field($order_id)) . ' - ' . esc_html__('NMI eCheck Order Response: ', 'nmi-for-woocommerce') . esc_html($response_output)
				);
			}


            if ($response['response'] == '1') {
                // Payment completed
                $order->add_order_note(sprintf(__('The NMI Payment transaction is successful. The Transaction Id is %1$s. Account used is %2$s', 'nmi-for-woocommerce'), $response['transactionid'], $last_four_digits));
                $order->payment_complete($response['transactionid']);

                update_post_meta($order_id, 'NMI Transaction ID', $response['transactionid']);

                if (isset($response['subscription_id'])) {
                    update_post_meta($order_id, 'NMI Subscriber ID', $response['subscription_id']);
                    WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
                }

                if (isset($response['avsresponse'])) {
                    $text = sprintf(__('%1$s => %2$s', 'nmi-for-woocommerce'), $response['avsresponse'], $this->getAVSresponse($response['avsresponse']));
                    update_post_meta($order_id, 'AVS Response', $text);
                }

                if (isset($response['cvvresponse'])) {
                    $text = sprintf(__('%1$s => %2$s', 'nmi-for-woocommerce'), $response['cvvresponse'], $this->getCVVresponse($response['cvvresponse']));
                    update_post_meta($order_id, 'CVV Response', $text);
                }

                if (isset($response['response_code'])) {
                    $text = sprintf(__('%1$s => %2$s', 'nmi-for-woocommerce'), $response['response_code'], $this->getCoderesponse($response['response_code']));
                    update_post_meta($order_id, 'NMI Response', $text);
                }

                if (isset($response['customer_vault_id']) && isset($_POST['wc-woonmi_echeck-new-payment-method']) && count($tokens) == 0) {
                    // Build the token
                    $token = new WC_Payment_Token_eCheck();
                    $token->set_token($response['customer_vault_id']); // Token comes from payment processor
                    $token->set_gateway_id('woonmi_echeck');
                    $token->set_last4($last_four_digits);
                    $token->set_user_id(get_current_user_id());
                    // Save the new token to the database
                    $token->save();

                    update_user_meta(get_current_user_id(), 'nmi_check_token_id', $token->get_id());

                    if ($this->debug == 'yes') {
						$this->log->add(
							'woonmi_echeck',
							esc_html(sanitize_text_field($order_id)) . ' - ' . esc_html__('New Token Saved : ', 'nmi-for-woocommerce') . esc_html(sanitize_text_field($token->get_id()))
						);
					}

                } elseif (isset($response['customer_vault_id']) && isset($_POST['wc-woonmi_echeck-new-payment-method']) && count($tokens) > 0) {
                    // Build the token
                    $token_id = get_user_meta(get_current_user_id(), 'nmi_check_token_id', true);
                    $token = WC_Payment_Tokens::get($token_id);
                    $token->set_token($response['customer_vault_id']); // Token comes from payment processor
                    $token->set_gateway_id('woonmi_echeck');
                    $token->set_last4($last_four_digits);
                    $token->set_user_id(get_current_user_id());
                    // Save the new token to the database
                    $token->save();

                    update_user_meta(get_current_user_id(), 'nmi_check_token_id', $token->get_id());

						if ($this->debug == 'yes') {
							$this->log->add(
								'woonmi_echeck',
								esc_html(sanitize_text_field($order_id)) . ' - ' . esc_html__('Old Token Deleted : ', 'nmi-for-woocommerce') . esc_html(sanitize_text_field($token_id)) . ' - ' . esc_html__('New Token Saved : ', 'nmi-for-woocommerce') . esc_html(sanitize_text_field($token->get_id()))
							);
						}

                }

                return array(
          'result' => 'success',
          'redirect' => $this->get_return_url($order),
        );
            } else {
                if (strpos($response['responsetext'], 'Invalid Customer Vault Id') !== false) {// Build the token
                    $token_id = get_user_meta(get_current_user_id(), 'nmi_check_token_id', true);
                    if (!empty($token_id)) {
                        $token = new WC_Payment_Tokens();
                        $token->delete($token_id);
                    }
                   if ($this->debug == 'yes') {
				$this->log->add(
				'woonmi_echeck',
					esc_html(sanitize_text_field($order_id)) . ' - ' . esc_html__('Token Deleted : ', 'nmi-for-woocommerce') . esc_html(sanitize_text_field($token_id)) . ' - ' . esc_html__('because of error : ', 'nmi-for-woocommerce') . esc_html(sanitize_text_field($response['responsetext']))
				);
			}


                    return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(),
          );
                } else {
                    $order->add_order_note(sprintf(__('Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce'), $response['response_code'], $response['responsetext']));
                    wc_add_notice(sprintf(__('Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce'), $response['response_code'], $response['responsetext']), $notice_type = 'error');
                   if ($this->debug == 'yes') {
					$this->log->add(
						'woonmi_echeck',
						esc_html(sanitize_text_field($order_id)) . ' - ' . esc_html(sprintf(
							__('Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce'),
							sanitize_text_field($response['response_code']),
							sanitize_text_field($response['responsetext'])
						))
					);
				}

                }
            }
        } else {
            $order->add_order_note(sprintf(__('Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce'), print_r($response, true)));
            wc_add_notice(__('Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce'), $notice_type = 'error');
           if ($this->debug == 'yes') {
			$this->log->add(
				'woonmi_echeck',
				sanitize_text_field($order_id) . ' - ' . sprintf(
					__('Gateway Error. Please Notify the Store Owner about this error. %s', 'nmi-for-woocommerce'),
					esc_html(print_r($response, true))
				)
			);
		}

        }
    }

    /**
     * Set Billing and Shipping Address.
     *
     * @param int   $order_id
     * @param array $woonmi_echeck_args
     *
     * @return array $woonmi_echeck_args
     */
    public function set_billing_and_shipping_address($woonmi_echeck_args, $order)
    {
        $woonmi_echeck_args['firstname'] = $order->get_billing_first_name();
        $woonmi_echeck_args['lastname'] = $order->get_billing_last_name();
        $woonmi_echeck_args['company'] = $order->get_billing_company();
        $woonmi_echeck_args['address1'] = $order->get_billing_address_1();
        $woonmi_echeck_args['address2'] = $order->get_billing_address_2();
        $woonmi_echeck_args['city'] = $order->get_billing_city();
        $woonmi_echeck_args['state'] = $order->get_billing_state();
        $woonmi_echeck_args['zip'] = $order->get_billing_postcode();
        $woonmi_echeck_args['country'] = $order->get_billing_country();

        $ship_name = $order->get_shipping_first_name();

        if (empty($ship_name)) {
            $woonmi_echeck_args['shipping_firstname'] = $order->get_billing_first_name();
            $woonmi_echeck_args['shipping_lastname'] = $order->get_billing_last_name();
            $woonmi_echeck_args['shipping_company'] = $order->get_billing_company();
            $woonmi_echeck_args['shipping_address1'] = $order->get_billing_address_1();
            $woonmi_echeck_args['shipping_address2'] = $order->get_billing_address_2();
            $woonmi_echeck_args['shipping_city'] = $order->get_billing_city();
            $woonmi_echeck_args['shipping_state'] = $order->get_billing_state();
            $woonmi_echeck_args['shipping_zip'] = $order->get_billing_postcode();
            $woonmi_echeck_args['shipping_country'] = $order->get_billing_country();
        } else {
            $woonmi_echeck_args['shipping_firstname'] = $order->get_shipping_first_name();
            $woonmi_echeck_args['shipping_lastname'] = $order->get_shipping_last_name();
            $woonmi_echeck_args['shipping_company'] = $order->get_shipping_company();
            $woonmi_echeck_args['shipping_address1'] = $order->get_shipping_address_1();
            $woonmi_echeck_args['shipping_address2'] = $order->get_shipping_address_2();
            $woonmi_echeck_args['shipping_city'] = $order->get_shipping_city();
            $woonmi_echeck_args['shipping_state'] = $order->get_shipping_state();
            $woonmi_echeck_args['shipping_zip'] = $order->get_shipping_postcode();
            $woonmi_echeck_args['shipping_country'] = $order->get_shipping_country();
        }

        return $woonmi_echeck_args;
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
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$order || !$order->get_transaction_id()) {
            return false;
        }

        $provider = $this->provider;

        $nmi_adr = $this->$provider.'?';

        if (!is_null($amount)) {
            $woonmi_echeck_args['type'] = 'refund';
            $woonmi_echeck_args['security_key'] = $this->security_key;
            $woonmi_echeck_args['transactionid'] = $order->get_transaction_id();
            $woonmi_echeck_args['amount'] = number_format($amount, 2, '.', '');
        }

        $name_value_pairs = array();
        foreach ($woonmi_echeck_args as $key => $value) {
            $name_value_pairs[] = $key.'='.urlencode($value);
        }
        $gateway_values = implode('&', $name_value_pairs);

        $response = wp_remote_post($nmi_adr, array('body' => $gateway_values, 'sslverify' => false, 'timeout' => 60));

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['body'])) {
            return new WP_Error('nmi-error', __('Empty NMI response.', 'nmi-for-woocommerce'));
        }

        parse_str($response['body'], $response);

        if ($response['response'] == '1') {
           $order->add_order_note(sprintf(__('Refund %1$s - Refund ID: %2$s', 'nmi-for-woocommerce'), $response['responsetext'], $response['transactionid']));

            return true;
        } elseif ($response['response'] == '2') {
            $order->add_order_note(__('Transaction Declined', 'nmi-for-woocommerce'));

            return true;
        } elseif ($response['response'] == '3') {
            $order->add_order_note(__('Error in transaction data or system error.', 'nmi-for-woocommerce'));

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
   public function add_payment_method()
{
    // Sanitize inputs from $_POST
    $checkname = isset($_POST['woonmi-account-name']) ? sanitize_text_field($_POST['woonmi-account-name']) : '';
    $checkaba = isset($_POST['woonmi-routing-number']) ? sanitize_text_field($_POST['woonmi-routing-number']) : '';
    $checkaccount = isset($_POST['woonmi-account-number']) ? sanitize_text_field($_POST['woonmi-account-number']) : '';
    $account_holder_type = isset($_POST['woonmi-holder-type']) ? sanitize_text_field($_POST['woonmi-holder-type']) : '';
    $account_type = isset($_POST['woonmi-account-type']) ? sanitize_text_field($_POST['woonmi-account-type']) : '';
    $provider = sanitize_text_field($this->provider);

    $nmi_adr = esc_url_raw($this->$provider.'?');

    $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), 'woonmi_echeck');

    if (count($tokens) == 0) {
        $woonmi_echeck_args['customer_vault'] = 'add_customer';
        $woonmi_echeck_args['checkname'] = $checkname;
        $woonmi_echeck_args['checkaba'] = $checkaba;
        $woonmi_echeck_args['checkaccount'] = $checkaccount;
        $woonmi_echeck_args['account_holder_type'] = $account_holder_type;
        $woonmi_echeck_args['account_type'] = $account_type;

        $last_four_digits = substr($checkaccount, -4);
    } elseif (count($tokens) > 0) {
        $token_id = get_user_meta(get_current_user_id(), 'nmi_check_token_id', true);
        $token = WC_Payment_Tokens::get($token_id);

        $woonmi_echeck_args['customer_vault'] = 'update_customer';
        $woonmi_echeck_args['customer_vault_id'] = sanitize_text_field($token->get_token());
        $woonmi_echeck_args['checkname'] = $checkname;
        $woonmi_echeck_args['checkaba'] = $checkaba;
        $woonmi_echeck_args['checkaccount'] = $checkaccount;
        $woonmi_echeck_args['account_holder_type'] = $account_holder_type;
        $woonmi_echeck_args['account_type'] = $account_type;

        $last_four_digits = substr($checkaccount, -4);
    }
    
    $woonmi_echeck_args['security_key'] = sanitize_text_field($this->security_key);

    $name_value_pairs = array();
    foreach ($woonmi_echeck_args as $key => $value) {
        $name_value_pairs[] = $key.'='.urlencode(sanitize_text_field($value));
    }
    $gateway_values = implode('&', $name_value_pairs);

    $response = wp_remote_post($nmi_adr, array('body' => $gateway_values, 'sslverify' => false, 'timeout' => 60));

    if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
        parse_str($response['body'], $response);
        if (isset($response['response']) && $response['response'] == '1') {
            if (count($tokens) == 0) {
                // Build the token
                $token = new WC_Payment_Token_eCheck();
                $token->set_token(sanitize_text_field($response['customer_vault_id'])); // Token comes from payment processor
                $token->set_gateway_id('woonmi_echeck');
                $token->set_last4($last_four_digits);
                $token->set_user_id(get_current_user_id());
                // Save the new token to the database
                $token->save();

                update_user_meta(get_current_user_id(), 'nmi_check_token_id', $token->get_id());
            } elseif (count($tokens) > 0) {
                // Build the token
                $token_id = get_user_meta(get_current_user_id(), 'nmi_check_token_id', true);
                $token = WC_Payment_Tokens::get($token_id);
                $token->set_token(sanitize_text_field($response['customer_vault_id'])); // Token comes from payment processor
                $token->set_gateway_id('woonmi_echeck');
                $token->set_last4($last_four_digits);
                $token->set_user_id(get_current_user_id());
                // Save the new token to the database
                $token->save();

                update_user_meta(get_current_user_id(), 'nmi_check_token_id', $token->get_id());
            }

            return array(
                'result' => 'success',
                'redirect' => wc_get_endpoint_url('payment-methods'),
            );
        } else {
            wc_add_notice(sprintf(__('Transaction Failed. %1$s-%2$s', 'nmi-for-woocommerce'), isset($response['response_code']) ? sanitize_text_field($response['response_code']) : 'N/A', isset($response['responsetext']) ? sanitize_text_field($response['responsetext']) : 'N/A'), 'error');

            return;
        }
    } else {
        $notice_message = __('Please make sure you have entered the Credit Card details.', 'nmi-for-woocommerce') . print_r($response, true);
        wc_add_notice($notice_message, 'error');

        return;
    }
}

    public function getAVSresponse($code)
    {
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

        return $avsresponse[$code];
    }

    public function getCVVresponse($code)
    {
        $cvvresponse = array(
      'M' => 'CVV2/CVC2 match',
      'N' => 'CVV2/CVC2 no match',
      'P' => 'Not processed',
      'S' => 'Merchant has indicated that CVV2/CVC2 is not present on card',
      'U' => 'Issuer is not certified and/or has not provided Visa encryption keys',
    );

        return $cvvresponse[$code];
    }

    public function getCoderesponse($code)
    {
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

        return $response[$code];
    }
}

?>
