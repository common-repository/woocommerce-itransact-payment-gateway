=== WooCommerce iTransact Payment Gateway ===
Contributors: outsourcewordpress
Tags: woocommerce, payment gateway, itransact
Donate Link: https://www.outsource-wordpress.com/
Requires at least: 3.0
Tested up to: 4.9.4
Stable tag: 2.0.3
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The iTransact WooCommerce Payment Gateway Plugin allows you to accept payments on your WooCommerce Store via iTransact.

== Description ==

You can use this Plugin to integrate iTransact Payment Gateway with WooCoommerce. You just need to enter the parameters depending on Payment Method you choose.

== Installation ==

= Automatic Installation =

1. Login to your WordPress Site
2. Under the 'Plugins' sidebar tab, click 'Add New'
3. Search for 'WooCommerce iTransact Payment Gateway'
4. Install and Activate the Plugin
5. Navigate to 'WooCommerce > Settings', click on 'Checkout' tab and then 'iTransact' link
6. Enter the required parameters as per Payment Method you choose (please see FAQ section below for details)

= Manual Installation =

1. Download the latest version of the plugin from 'WooCommerce iTransact Payment Gateway' WordPress Plugin page
2. Uncompress the file
3. Upload the uncompressed directory to '/wp-content/plugins/' via FTP
4. Active the plugin from your WordPress backend 'Plugins > Installed Plugins'
5. Navigate to 'WooCommerce > Settings', click on 'Checkout' tab and then 'iTransact' link
6. Enter the required parameters as per Payment Method you choose (please see FAQ section below for details)

== Frequently Asked Questions ==

= What is the difference between API & Redirect Payment Methods offered by the Plugin? =

If you choose 'API' Payment Method (recommended), Credit Card details will be entered and processed in the site itself and customer will not be redirected to iTransact site. Please note that both SSL (HTTPS) & cURL should be installed on your site. if you choose 'Redirect' Payment Method, customer will be redirected to iTransact site to enter the Credit Card details and process the payment.

= How to obtain 'API Username' & 'API Key' from iTransact Payment Gateway? =

Login into iTransact Gateway using your credentials. Navigate through 'Control Panel' and then 'Merchant Settings'. Click on 'Integration' tab on left. Copy 'API Username' as well as 'API Key' values from 'API Access' section.

= How to obtain 'Vendor ID' from iTransact Payment Gateway? =

Login into iTransact Gateway using your credentials. Navigate through 'Control Panel' and then 'Merchant Settings'. Click on 'Integration' tab on left. Copy 'Order Form Unique ID (UID)' value from 'Standard Form/Split Form' section.

= Do I need SSL and cURL installed in my Server? =

If you choose 'API' Payment Method, you definitely need SSL (HTTPS) as well as cURL. Choose 'Redirect' in the Payment Method option if you didn't have these.

= How do I enable Test mode in iTransact? =

Login into iTransact Gateway using your credentials. Navigate through 'Control Panel' and then 'Merchant Settings'. Click on 'Advanced' tab on left. Check 'Test Mode' in 'Testing' section. Please note that you should provide the same one you entered in 'Test First Name' field as customer first name.

== Changelog ==

= 1.0.0 =
* Initial Commit

= 2.0.0 =
* Amount mismatch bug fix
* Added API Payment Method
* Enhancements to support latest WooCommerce version

= 2.0.1 =
* Expiry month fix
* Wrong modal window in My account page

= 2.0.2 =
* Added Customer last name to the API transaction

= 2.0.3 =
* Fixed Shipping Tax

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png