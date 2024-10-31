=== WP NMI Payment Gateway for WooCommerce ===
Contributors: wpclubz  
Tags: nmi woocommerce, payment gateway, nmi, network merchants, ach, pci
Plugin URI: https://wpclubz.com  
Author URI: https://wpclubz.com
Requires at least: 5.0 
Tested up to: 6.6.2  
Requires PHP: 5.6  
Stable tag: 1.0.1  
License: GPLv3  
License URI: [GPLv3 License](https://www.gnu.org/licenses/gpl-3.0.html)

Integrate NMI with WooCommerce for secure, PCI-compliant payments. Accept credit cards and ACH for smooth WooCommerce transactions.

== Description ==

**NMI ACH Gateway for WooCommerce** is a powerful payment gateway plugin that allows you to securely accept credit cards and ACH payments directly on your WooCommerce store. Built with PCI compliance in mind, this plugin ensures that every transaction is handled safely and efficiently through the NMI (Network Merchants, Inc.) gateway, one of the most reliable payment processors in the market that offers robust payment solutions for merchants to accept a variety of payment methods online, including ACH eChecks.

#### Key Features of WordPress NMI ACH Gateway for WooCommerce
* **Easy Installation**: Streamlined setup process with minimal configuration. Once installed, browse WooCommerce Settings - Payments and fill out a few fields, and you’ll be set up to accept credit card payments on your store.
* **Secure ACH Processing**: Leverages NMI's tokenization library for secure ACH payments, ensuring PCI compliance.
* **Secure Payment Processing**: Leverages the Collect.js tokenization library to securely transmit payment data directly to NMI, eliminating the need for PCI-DSS certification concerns.
* **Card Verification**: The plugin integrates card processing and verification to process credit card payments synchronized with card verification.
* **Refund Management**: Process full or partial refunds directly from the WooCommerce dashboard, including one-time orders.
* **Restrict Card Types**: Optionally choose to restrict certain card types, hiding their icons and providing proper error messages on checkout.
* **Authorize and Capture**: Option to authorize transactions initially and capture funds later.
* **Payment Method Restriction**: Restrict certain card types during checkout with appropriate error messaging.
* **Gateway Receipts**: Option to send payment receipts directly from your NMI account.
* **Detailed Logging**: Enable logging to troubleshoot and debug any issues, providing valuable insights when needed.
* **Manage Subscriptions [Enterprise Version]**: Utilize the WooCommerce Subscriptions extension to create and manage recurring payment products, offering residual revenue that you can monitor.
* **No Sensitive Data Stored On-Site**: Uses NMI's "customer vault" to charge future payments without storing sensitive data on your site.
* **Customizable Payment Experience**: Tailor the payment experience to match your brand's look and feel by adjusting settings like displayed payment methods and transaction descriptions.
* **Advanced Security**: Uses encryption and tokenization to protect sensitive customer data, minimizing data breach risks and ensuring PCI compliance.
* **Extensive Reporting & Analytics**: Access transaction history, manage refunds, and analyze gateway performance to optimize sales strategies.

Integrating the NMI Gateway into your WooCommerce store not only streamlines the payment process but also enhances customer trust. By providing a secure environment for transactions, you can reassure shoppers that their sensitive information is protected. This confidence often leads to higher conversion rates and repeat business.

The plugin supports a variety of payment options, catering to diverse customer preferences. Whether your clientele prefers using credit cards or opting for ACH payments, the NMI Gateway ensures that their needs are met. This flexibility can significantly broaden your customer base and improve overall sales.

NMI Gateway for WooCommerce Platform is an essential tool for any online merchant looking to enhance their payment processing capabilities. With its robust security features, user-friendly interface, and extensive customization options, it stands out as a leading choice for businesses of all sizes.

https://www.youtube.com/watch?v=b_il2F8cTu0

### Requirements
* Active [NMI](https://www.nmi.com/) account.
* [WooCommerce](https://woocommerce.com/) version 3.3 or later.
* Valid SSL certificate for secure transactions and PCI DSS compliance. This plugin does not store sensitive payment data on your site.


### Disclaimer
This plugin is not affiliated with or endorsed by NMI, WooCommerce, or Automattic. All trademarks are the property of their respective owners.

== Installation ==

1. Upload the `wp-nmi-ach-gateway` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the WooCommerce settings page, select the 'Payments' tab, and configure the 'NMI ACH Gateway' settings.
4. Enable the payment method and enter your NMI API keys (Public Key and Private Key).
5. Save changes and start accepting ACH payments on your WooCommerce store.

== Frequently Asked Questions ==

= Is SSL Required? =
Yes, a valid SSL certificate is required to ensure secure transactions and PCI DSS compliance.

= How do I process refunds? =
Refunds can be processed directly from your WooCommerce dashboard.

= Can I restrict certain payment methods? =
Yes, you can restrict specific payment methods and display custom error messages during checkout.



== Third-Party Services ==

This plugin integrates with several third-party services to process payments and transactions. The data sent to these services may include sensitive information such as payment details. Below is a list of the third-party services used, including their URLs, terms of use, and privacy policies:

**SkyBank Gateway**
* **API URL**: [https://secure.skybankgateway.com/api/transact.php](https://secure.skybankgateway.com/api/transact.php)

**Durango Direct**
* **API URL**: [https://secure.durango-direct.com/api/transact.php](https://secure.durango-direct.com/api/transact.php)

**Network Merchants Inc. (NMI) **
* **API URL**: [https://secure.networkmerchants.com/api/transact.php](https://secure.networkmerchants.com/api/transact.php)

**PlanetAuthorize Gateway**
* **API URL**: [https://secure.planetauthorizegateway.com/api/transact.php](https://secure.planetauthorizegateway.com/api/transact.php)

**MerchantGuy Gateway**
* **API URL**: [https://secure.merchantguygateway.com/api/transact.php](https://secure.merchantguygateway.com/api/transact.php)

**Payline Data Gateway**
* **API URL**: [https://secure.paylinedatagateway.com/api/transact.php](https://secure.paylinedatagateway.com/api/transact.php)

*Note*: For detailed information on how these services handle your data, please review their respective terms of use and privacy policies. We do not control these services and are not responsible for their data handling practices.


== Changelog ==



= 1.0.1 = 
* Integrated Freemius SDK

= 1.0.0 = 
* Initial release version