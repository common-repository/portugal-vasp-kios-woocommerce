=== Portugal VASP Expresso Kios network for WooCommerce ===
Contributors: webdados, ptwooplugins
Tags: woocommerce, shipping, vasp, pickup, ecommerce, e-commerce, delivery, webdados
Author: PT Woo Plugins (by Webdados)
Author URI: https://ptwooplugins.com
Requires at least: 5.0
Tested up to: 6.2
Requires PHP: 7.0
Stable tag: 3.0

Lets you deliver on the VASP Expresso Kios network of partners in Portugal. This is not a shipping method. This is an add-on for any WooCommerce shipping method you activate it on.

== Description ==

Lets you deliver on the [VASP Expresso](http://www.vaspexpresso.pt) Kios network of partners. This is not a shipping method but rather an add-on for any WooCommerce shipping method you activate it on.

This is not an official VASP Expresso plugin, but their support was obtained during its development. The VASP logo and brand is copyrighted, belongs to them and is used with their permission.

= Features: =

* Lets the store client choose a VASP Expresso Kios point for the shipping delivery;
* The VASP Expresso Kios points option can be associated to any zone/method by the store owner;
* This plugin does not create a new WooCommerce Shipping Method and is compatible with methods that can be associated with a zone (WooCommerce 2.6 and above);
* All WooCommerce built-in shipping methods are compatible;

= 3rd Party Integration: =
* [Flexible Shipping for WooCommerce](https://wordpress.org/plugins/flexible-shipping/);
* [Table Rate Shipping for WooCommerce](https://bolderelements.net/plugins/table-rate-shipping-woocommerce/) (by BolderElements);
* [WooCommerce Advanced Shipping](https://codecanyon.net/item/woocommerce-advanced-shipping/8634573);
* [Table Rate Shipping](https://woocommerce.com/products/table-rate-shipping/) (by WooCommerce);
* Additional compatibility with other plugins can be implemented with costs to be budgeted under contact;

== Installation ==

* Use the included automatic install feature on your WordPress admin panel and search for “Portugal VASP Expresso Kios network for WooCommerce”.
* Go to WooCoomerce > Settings > Shipping > Shipping zones and for each zone/method you want the VASP Expresso Kios points selection to be activated, set "Yes" on the "VASP Expresso Kios in Portugal" option.
* Mandatory if you want to show the point on a map (using Mapbox - recommended): go to the [Mapbox Acess tokens page](https://www.mapbox.com/account/access-tokens) and get either your default public token or generate a new one, then add it to WooCommerce > Settings > Shipping > Shipping options > VASP Expresso Kios network in Portugal > Mapbox public token.
* Mandatory if you want to show the point on a map (using Google Maps): go to the [Google APIs Console](https://console.developers.google.com/cloud-resource-manager) and create a project, then go to the [Maps Static API](https://developers.google.com/maps/documentation/maps-static/get-api-key) documentation website and click on "Get started", choose "Maps", select your project and generate a new key, finally add it to WooCommerce > Settings > Shipping > Shipping options > VASP Expresso Kios network in Portugal > Google Maps API Key.

== Frequently Asked Questions ==

= Is this a shipping method? =

No! This is an add-on for any method that supports "shipping zones" (WooCommerce >= 2.6).
You need to set the shipping fees using built-in or plugin installed methods and then set "Yes" on the "VASP Expresso Kios in Portugal" option on each zone/method that applies.

= Can I change the number of total and near points shown on the website? =

Yes! Go to WooCommerce > Settings > Shipping > Shipping options and tweak the settings as you like.
Always set the total points to a bigger number than the near points, or you're going to end up with the just the near points.

= I need to use this plugin with a shipping method that it's not compatible. Is it possible? =

Maybe. We have the `pvkw_get_shipping_methods` filter that allows you to add other shipping methods besides the ones that are compatible. Use at your own risk.
For example, if you want to use VASP Expresso Kios points with [Flat Rate per State/Country/Region for WooCommerce](https://wordpress.org/plugins/flat-rate-per-countryregion-for-woocommerce/) you would do it [like this](https://gist.github.com/webdados/b461b54c84b798f6549243428bdd906c).

= Why isn't the Mapbox / Google Maps showing up? =

You need to get a Mapbox Public Token or a Google Maps API Key, as explained on the installation instructions.

= Can I change the map image size? =

Yes. Use the `pvkw_map_width` and `pvkw_map_height` filters [like this](https://gist.github.com/webdados/b862500de9acccb89fd622fc3bcc5a47).
You can also change the zoom by using the `pvkw_map_zoom` filter and scale using the `pvkw_map_scale` filter (1 for regular and 2 for retina).

= Is this plugin compatible with the new WooCommerce High-Performance order storage (COT)? =

Yes.

= I need technical support. Who should I contact, VASP or Webdados? =

The development and support is [Webdados](https://www.webdados.pt) responsibility.
For free/standard support you should use the support forums at WordPress.org but no answer is guaranteed.
For premium/urgent support or custom developments you should contact [Webdados](https://www.webdados.pt/contactos/) directly. Charges may (and most certainly will) apply.

= Where do I report security bugs found in this plugin? =  
 
You can report any security bugs found in the source code of the site-reviews plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/portugal-vasp-kios-woocommerce). The Patchstack team will assist you with verification, CVE assignment and take care of notifying the developers of this plugin.

== Changelog ==

= 3.0 - 2023-01-17 =
* Tested and confirmed WooCommerce HPOS compatibility
* Removed legacy code
* Removed Webdados backup API endpoint
* Tested with WordPress 6.2-alpha-55080 and WooCommerce 7.3

= 2.3.0 - 2022-09-13 =
* Fix when country fields are removed from checkout
* Tested with WordPress 6.1-alpha-54043 and WooCommerce 6.9.0-rc.1

= 2.2.0 - 2022-06-29 =
* New brand: PT Woo Plugins 🥳
* Requires WordPress 5.0, WooCommerce 3.0 and PHP 7.0
* Tested with WordPress 6.1-alpha-53556 and WooCommerce 6.7.0-beta.2

= 2.1.0 - 2022-04-21 =
* Fix the pickup points list when the user does not enter the "-" on the postcode
* Use the WordPress `wp_doing_ajax()` function instead of the WooCommerce `is_ajax()` which was deprecated on WooCommerce 6.1.0
* Tested with WordPress 6.0-beta2-53236 and WooCommerce 6.5.0-beta.1

= 2.0.0 - 2020-03-10 =
* New option to not pre-select a point in the VASP Expresso Kios field and force the client to choose it, thus reducing situations in which the client doesn't even notice that he needed to select a point - sponsored by [Evolt](https://evolt.pt/)
* Requires WooCommerce 3.0
* Tested with WordPress 5.8-alpha-50516 and WooCommerce 5.1.0

= 1.9.1 - 2020-03-07 =
* Fix Flexible Shipping integration - Field not showing
* Tested with WordPress 5.7-RC3-50503 and WooCommerce 5.1.0-rc.1

= 1.9.0 - 2020-02-23 =
* Show DDP pickup point number on emails
* Tested with WordPress 5.7-beta3-50388 and WooCommerce 5.1.0-beta.1

= 1.8.0 =
* Bugfix when saving the pickup point for Table Rate Shipping for WooCommerce (by BolderElements)
* Tested with WordPress 5.6-alpha-48937 and WooCommerce 4.5.0-rc.3

= 1.6.7 =
* New `pvkw_available_points` to allow developers to filter the pickup points list before they're shown to the customer on the checkout - Sponsored by [mindthetrash.pt](https://mindthetrash.pt)
* Tested with WordPress 5.3.3-alpha-47290 and WooCommerce 4.0.0-beta.1

= 1.6.6 =
* Bugfix when loading the Checkout page and the active shipping method has VASP enabled
* Tested with WordPress 5.3.3-alpha-46995 and WooCommerce 3.9.0-beta.2

= 1.6.5 =
* Tested with WordPress 5.2.5-alpha and WooCommerce 3.8.0

= 1.6.4 =
* Better cron job logging 
* Tested with WooCommerce 3.6.4
* Tested with WordPress 5.2.3-alpha

= 1.6.3 =
* Fix compatibility with [WooCommerce Advanced Shipping](https://codecanyon.net/item/woocommerce-advanced-shipping/8634573) (Thanks Evolt)
* Tested with WooCommerce 3.6.3
* Tested with WordPress 5.2.1

= 1.6.2 =
* CSS compatibility with Flatsome 3.7

= 1.6.1 =
* Tested with WooCommerce 3.5
* Bumped `WC tested up` tag

= 1.6 =
* Because of the new Google Maps pricing policy, it' now possible to use Mapbox static maps (the link on the map image remains to Google Maps)
* New `pvkw_map_scale` and `pvkw_map_zoom` filters to allow overriding of the map image scale (default is 2, for retina displays) and zoom (default is 11 for Google Maps and 10 for Mapbox)

= 1.5 =
* [Table Rate Shipping](https://woocommerce.com/products/table-rate-shipping/) compatibility - sponsored by [Dreamsbaby](http://www.dreamsbaby.pt/)
* Fix: fatal error when enqueueing CSS and JS

= 1.4 =
* [WooCommerce Advanced Shipping](https://codecanyon.net/item/woocommerce-advanced-shipping/8634573) compatibility - sponsored by [STIVIKpro](https://stivikpro.com/)

= 1.3.2 =
* Fix: when using [Flexible Shipping for WooCommerce](https://wordpress.org/plugins/flexible-shipping/) the point was not saved with the order (thanks @alvesjc)
* Bumped `WC tested up` tag

= 1.3.1 =
* Fix: on newer WooCommerce versions the point was not saved with the order
* Bumped `WC tested up` tag

= 1.3 =
* Removed our fallback Google Maps API Key due to the [changes on the Google Maps Plaform usage policy](https://mapsplatform.googleblog.com/2018/05/introducing-google-maps-platform.html)

= 1.2.1 =
* [Table Rate Shipping for WooCommerce](https://bolderelements.net/plugins/table-rate-shipping-woocommerce/) compatibility - sponsored by [Moreleads](https://moreleads.pt/)
* Small fixes

= 1.0.1 =
* readme.txt enhancements

= 1.0 =
* The VASP Expresso Kios point information is also shown on the order details on the "My Account" page and on the order preview on the admin orders list table
* Code enhancements

= 0.1 =
* Initial release sponsored by [Barbudos.pt](https://barbudos.pt/)
