=== ZimRate ===
Contributors: tygalive
Donate link: https://tyganeutronics.com
Tags: zimbabwe, zimrate, currency, rate, tyganeutronics
Requires at least: 4.0.0
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

All Zimbabwean exchange rates from multiple sites in one plugin. No need to scrounge the internet for the current days rate.

== Description ==

Add automatic Zimbabwean currency conversion to your site. 
This plugin modifies the result from listed plugins api calls before they are submitted to plugin.

This plugin directly supports these plugins:

*   [Multi Currency for WooCommerce](https://wordpress.org/plugins/woo-multi-currency "Multi Currency for WooCommerce")
*   [Multi Currency for WooCommerce](https://wordpress.org/plugins/wc-multi-currency "Multi Currency for WooCommerce")
*   [CurrencyConverter](https://wordpress.org/plugins/currencyconverter "CurrencyConverter")
*   [Currency Switcher for WooCommerce](https://wordpress.org/plugins/currency-switcher-woocommerce "Currency Switcher for WooCommerce")
*   [Currency Exchange for WooCommerce](https://wordpress.org/plugins/currency-exchange-for-woocommerce "Currency Exchange for WooCommerce")

All Zimbabwean rates are obtained from [ZimRate](http://zimrate.tyganeutronics.com "Zimrate") and caching is provided in plugin to avoid overloading the server though you are free to disable caching.

Note: This plugin is not directly a currency switcher (as that would be redundant considering the number of options on wordpress.org).

== Installation ==

1. Upload `/zimrate/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What's this? =

A currency injector for wordpress plugins. When said plugin requests for latest currency rates using wordpress' functions, this plugin modifes the result before it is submitted to the requesting plugin.

= Where's my favourate plugin? =

Though have tried to cover as many plugins as possible, there is a limitation on the plugins that can be directly supported.
This plugin relies on a plugin using wordpress' internal http_request feature which has hooks to modify the result or if said plugin has hooks to modify result before use.
If you have a plugin that you want added, you are free to contact.

= What if i need feature X? =

You are free to contact and will happily add feature X as long as it is in the scope of the plugin.

= Easy Digital Downloads is not supported? =

Though would have wanted to supported Easy Digital Downloads, could not get hold of a currency convertor for it.

== Screenshots ==

1. Zimrate Dashboard.
2. Zimrate options screen

== Changelog ==

= 1.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0 =
Initial Release.
