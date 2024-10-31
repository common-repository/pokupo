=== Pokupo Woocommerce gateway ===
Tags: pokupo, woocommerce
Requires at least: 4.6
Tested up to: 5.3.2
Requires PHP: 5.2.4 or later

== Description ==

Pokupo is the ultimate all-in-one solution to build your online store with more than 30+ payment methods.

This module provides simple payment gateway for WordPress + WooCommerce to Pokupo.

== Installation ==

1. In the Wordpress admin area, go to `Plugins-> Add New`, click the `Download Plugin` button
2. Select the downloaded archive with the module and click `Install`
 2.1 In case of installation problems, copy the contents of the archive to the `wp-content / plugins` folder in your site
3. Go to `Plugins-> Installed`, find the` Pokupo WooCommerce Gateway` and click `Activate`
4. Go to `WooCommerce-> Settings-> Payments`
5. Choose and enable Pokupo, then click `Manage` button
6. In the `Shop ID` field, enter the 'Store Identifier for CMS' from the Store Settings in Pokupo dashboard
7. In the `Notification Password` field, enter the 'Notification Password' from the Store Settings in the Pokupo dashboard personal account menu
8. In the Store Settings in the personal account of Pokupo, in the field of notification URLs, display the address from the line `Notification URL`


== Changelog ==

= 1.0.2 =
* Small changes in localization
* Added check for paid order
* Merchant url depending on locale


= 1.0.1 =
* Minor fixes of deprecated calls
* Changed module logo.

= 1.0 =
* Initial release

