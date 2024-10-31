<?php
/**
* Plugin Name: NMI for WooCommerce
* Plugin URI: http://www.wpclubz.com
* Description: Accept payments seamlessly with NMI for WooCommerce plugin (Network Merchants Inc Gateway).
* Version: 1.0.1
* Author: NMI for WooCommerce
* Author URI: https://profiles.wordpress.org/wpclubz/
* Domain Path: /languages
* WC requires at least: 3.3
* WC tested up to: 9.0
* Text Domain: nmi-for-woocommerce
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Requires Plugins: woocommerce
 */



 // Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'nfw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function nfw_fs() {
        global $nfw_fs;
    if ( ! isset( $nfw_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $nfw_fs = fs_dynamic_init( array(
                'id'                  => '16385',
                'slug'                => 'nmi-for-woocommerce',
                'type'                => 'plugin',
                'public_key'          => 'pk_6bf865000fedec0f1573f84ab7f51',
                'is_premium'          => false,
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'menu'                => array(
                    'slug'           => 'nmi-settings',
                    'first-path'     => 'admin.php?page=wc-settings&tab=checkout',
                    'support'        => false,
                    'parent'         => array(
                        'slug' => 'woocommerce',
                    ),
					 'position' => 99, // Puts it at the end of the WooCommerce submenu.
                ),
            ) );
        }

        return $nfw_fs;
    }

    // Init Freemius.
    nfw_fs();
    // Signal that SDK was initiated.
    do_action( 'nfw_fs_loaded' );
}



// Hook into plugins_loaded action to initialize the WooCommerce NMI payment gateway
add_action( 'plugins_loaded', 'woonswpclubz_woonmi_init', 0 );

function woonswpclubz_woonmi_init() {
	// Check if WooCommerce payment gateway classes exist
	if ( ! class_exists( 'WC_Payment_Gateway_CC' ) || ! class_exists( 'WC_Payment_Gateway_eCheck' ) ) {
		return;
	}

	// Load plugin text domain for localization
	load_plugin_textdomain( 'nmi-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Include necessary payment gateway files
	include 'includes/creditcard.php';
	// This "if" block will be auto removed from the Free version.
if ( nfw_fs()->is__premium_only() ) {

}
	
	
	/**
	 * Register NMI payment gateways with WooCommerce.
	 **/
	function add_woonmi_gateway( $methods ) {
		$methods[] = 'woonswpclubz_woonmi';
		$methods[] = 'woonswpclubz_woonmi_echeck';

		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_woonmi_gateway' );
}






// Add NMI submenu to WooCommerce and make sure it appears at the end.
add_action( 'admin_menu', 'nmi_add_woocommerce_submenu', 99 ); // High priority to make sure it comes last.

function nmi_add_woocommerce_submenu() {
    // Add submenu under WooCommerce.
    add_submenu_page(
        'woocommerce',      
        'NMI Settings',        
        'NMI Settings',       
        'manage_woocommerce',   
        'nmi-settings',         
        'nmi_render_settings_page', 
        99                      // Position: 99 ensures it appears at the end.
    );
}

// Callback to render the NMI settings page content.
function nmi_render_settings_page() {
    global $nfw_fs; // Ensure the Freemius object is available

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'NMI for WooCommerce Settings', 'nmi-for-woocommerce' ); ?></h1>

        <!-- Promotion content starts here -->
         <div class="nmi-promotion">
            <h2><?php esc_html_e( 'Unlock the Full Power of NMI for WooCommerce!', 'nmi-for-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'NMI provides everything your business needs to accept a wide range of payments online, making it the ideal solution for processing credit, debit, and electronic payments. With our NMI for WooCommerce plugin, you can seamlessly integrate the NMI payment gateway to accept payments via credit cards or ACH (eCheck) directly on your WooCommerce store — without redirecting customers offsite.', 'nmi-for-woocommerce' ); ?></p>

            <h3><?php esc_html_e( 'Why Choose NMI for WooCommerce?', 'nmi-for-woocommerce' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'Quick and Easy Setup: Get started with a few simple steps.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Secure Payments: We use Collect.js tokenization library for secure payment data transmission.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Capture Payments on Your Terms: Choose to authorize and capture payments as needed.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Customize Card Options: Restrict certain card types with ease.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Refunds Made Simple: Process refunds directly from your WooCommerce dashboard.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Email Receipts: Optionally send receipts directly from your NMI merchant account.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Detailed Logging: Enable logging for easy troubleshooting.', 'nmi-for-woocommerce' ); ?></li>
            </ul>

            <h3><?php esc_html_e( 'Pro Features for Even More Power!', 'nmi-for-woocommerce' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'Manage Subscriptions: Handle recurring payments with WooCommerce Subscriptions.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Pre-Order Support: Let customers pre-order products and charge their card automatically.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( '3D Secure 2: Ensure compliance with SCA regulations using 3D Secure 2 verification.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'ACH Payments: Accept ACH (eCheck) payments for smoother transactions.', 'nmi-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Pay via Saved Cards: Allow customers to save card details for quicker checkouts.', 'nmi-for-woocommerce' ); ?></li>
            </ul>

            <p><?php esc_html_e( 'Ready to Upgrade? Get all these powerful features with the Pro Version today and boost your WooCommerce store’s potential.', 'nmi-for-woocommerce' ); ?></p>

            <!-- Upgrade button with dynamic Freemius upgrade URL -->
            <a href="<?php echo esc_url( $nfw_fs->get_upgrade_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'nmi-for-woocommerce' ); ?></a>
        </div>
    </div>
    <?php
}

//enqueue nmi admin styles
function nmi_enqueue_admin_styles( $hook ) {
        wp_enqueue_style( 'nmi-admin-style', plugins_url( 'includes/assets/css/nmi-admin-style.css', __FILE__ ), array(), '1.0.0', 'all' );
}
add_action( 'admin_enqueue_scripts', 'nmi_enqueue_admin_styles' );