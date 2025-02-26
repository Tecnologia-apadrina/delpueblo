=== YITH WooCommerce Quick Export Premium ===

Contributors: yithemes
Tags: export, data export, order export, customer export, coupon export, scheduled export, order backup, csv, customer backup, dropbox, dropbox backup, export dropbox
Requires at least: 5.4
Tested up to: 5.8
Stable tag: 1.3.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Documentation: https://docs.yithemes.com/yith-woocommerce-quick-export/

== Changelog ==

= 1.3.10 - Released on 28 June 2021 =

* New: support for WordPress 5.8
* New: support for WooCommerce 5.5
* Update: YITH plugin framework

= 1.3.9 - Released on 04 June 2021 =

* New: support for WooCommerce 5.4
* Update: YITH plugin framework
* Update: language files

= 1.3.8 - Released on 11 May 2021 =

* New: support for WooCommerce 5.3
* Update: YITH plugin framework

= 1.3.7 - Released on 07 April 2021 =

* New: support for WooCommerce 5.2
* Update: YITH plugin framework

= 1.3.6 - Released on 05 March 2021 =

* New: support for WordPress 5.7
* New: support for WooCommerce 5.1
* Update: YITH plugin framework

= 1.3.5 - Released on 11 February 2021 =

* New: support for WooCommerce 5.0
* Update: YITH plugin framework
* Fix: fixed the start and end date filters
* Dev: removed the PCLZIP class from the plugin, now it uses the one from WordPress

= 1.3.4 - Released on 31 December 2020 =

* New: Support for WooCommerce 4.9
* Update: plugin framework
* Fix: order export not working

= 1.3.3 - Released on 27 November 2020 =

* New: Support for WooCommerce 4.8
* Tweak: included the quantity of purchased products in the orders export
* Update: plugin framework

= 1.3.2 - Released on 04 November 2020 =

* New: Support for WooCommerce 4.7
* New: Support for WordPress 5.6
* Update: update plugin fw
* Dev: Auto select plugin enabled
* Dev: removed the .ready method from jQuery

= 1.3.1 - Released on 01 October 2020 =

* New: Support for WooCommerce 4.6
* Update: plugin framework

= 1.3.0 - Released on 17 September 2020 =

* New: Support for WooCommerce 4.5
* New: added a new column with the user funds if the YITH Account Funds plugin is enabled
* Tweak: added the date interval for the gift cards
* Update: plugin-fw
* Dev: improved the export interval

= 1.2.16 - Released on 11 August 2020 =

* New: Support for WooCommerce 4.4
* New: Support for WordPress 5.5
* Update: plugin-fw

= 1.2.15 - Released on 03 July 2020 =

* New: Support for WooCommerce 4.3
* Tweak: added the order notes in the export file
* Tweak: added payment_method_title in the order export file
* Tweak: added the applied gift cards to the orders export
* Update: plugin-fw
* Update: language files
* Dev: added a new condition to not show the order status change notes in the order notes

= 1.2.14 - Released on 29 May 2020 =

* New: Support for WooCommerce 4.2
* Update: plugin-fw
* Update: Italian language files
* Fix: fixed the non displayed vendors names in the export file

= 1.2.13 - Released on 04 May 2020 =

* New: Support for WooCommerce 4.1
* Update: plugin-fw
* Update: Italian language files
* Update: Spanish language files
* Update: Dutch language files
* Fix: fixed the non existing values

= 1.2.12 - Released on 05 March 2020 =

* New: Support for WordPress 5.4
* New: Support for WooCommerce 4.0
* New: added a column with the vendors names is available
* Update: plugin fw
* Fix: fixed a warning on the order export
* Dev: added new filter yith_wcqe_get_order_purchased_products_names
* Dev: added new filter ywqe_customers_export_interval and ywqe_display_pruchased_products_column_for_customers_export
* Dev: added an offset in the customer render to avoid unexpected timeouts
* Dev: all string escaped

= Version 1.2.11 - Released: Dic 27, 2019 =

* New: Support to WooCommerce 3.9
* New: Added column 'products purchased' in order csv and customer csv
* New: Added a new column with the purchased gift cards codes if available
* New: Added a new column with the order ID in the gift card export
* Update: Plugin core
* Fix: fixed radio buttons in the settings
* Dev filter yith_wcqe_get_customer_csv_value
* Dev: added an offset to the gift card csv to avoid timeouts
* Dev: added an offset to the coupons csv to avoid timeouts

= Version 1.2.10 - Released: Nov 07, 2019 =

* New: Support to WooCommerce 3.8
* Tweak: added condition for deprecated method get_used_coupons
* Tweak Add payment method title for order exporter
* Update: Italian language
* Update: Spanish language
* Update: Plugin core
* Fix: fixed an issue with the export of the selected order status
* Fix: fixed an issue with the RAQ order status

= Version 1.2.9 - Released: Jun 31, 2019 =

* New - support to WooCommerce 3.7
* New: integration with YITH Gift Cards, now you can export the gift cards data in a CSV
* Update: Italian language
* Update: Plugin core
* Update: .pot file
* Fix: Option description

= Version 1.2.8 - Released: May 29, 2019 =

* New: support to WordPress 5.2
* Update: plugin core to version 3.2.1

= Version 1.2.7 - Released: Apr 09, 2019 =

* New: Support to WordPress 5.1.1
* New: Support to WooCommerce 3.6.0 RC1
* Update: Plugin Framework
* Update: Spanish language
* Dev: check if WPML is installed to avoid the PCLZIP load

= Version 1.2.6 - Released: Feb 19, 2019 =

* Update: Updated Plugin Framework
* Dev: disabled the pclzip class inside the plugin, now is used the WooCommerce one

= Version 1.2.5 - Released: Dec 07, 2018 =

* New: support to WordPress 5.0
* Update: plugin core to version 3.1.6

= Version 1.2.4 - Released: Oct 23, 2018 =

* Update: Updated Plugin Framework


= Version 1.2.3 - Released: Oct 17, 2018 =

* New: Support to WooCommerce 3.5.0
* Tweak: new action links and plugin row meta in admin manage plugins page
* Update: Italian language
* Update: Spanish language
* Update: Dutch language
* Update: Updated Plugin Framework
* Update: updated the official documentation url of the plugin
* Dev: new filter yith_ywqe_settings_panel_capability

= Version 1.2.2 - Released: Feb 26, 2018 =

* Tweak: Create cvs for applied coupons and another one for all coupons

= Version 1.2.1 - Released: Jan 31, 2018 =

* Update: plugin framework 3.0.11
* New: support to WooCommerce 3.0.0


= Version 1.2.0 - Released: Dec 22, 2017 =

* Update: plugin framework 3.0
* Fix: cart discount not showing
* New: ducth translation

= Version 1.1.0 - Released: Nov 30, 2017 =

* New: support to WordPress 4.9.1
* New: support to WooCommerce 3.2.5
* New: support to Dropbox API v2 support

= Version 1.0.10 - Released: Aug 01, 2017 =

* Tweak: support to PHP 7

= Version 1.0.9 - Released: Jul 10, 2017 =

* Tweak: order export


= Version 1.0.8 - Released: Jul 06, 2017 =

* New: support for WooCommerce 3.1.
* New: tested up to WordPress 4.8.
* Update: YITH Plugin Framework.

= Version 1.0.7 - Released: May 19, 2017 =

* Update: Dropbox app keys.
* Fix: Dropbox conflicts with third party plugin.

= Version 1.0.6 - Released: Mar 24, 2017 =

* New:  Support to WooCommerce 3.0
* Update: YITH Plugin Framework
* Fix: YITH Plugin Framework initialization

= Version 1.0.5 - Released: Feb 02, 2017 =

* New: filter the order statuses to export

= Version 1.0.4 - Released: Dec 07, 2016 =

* New: ready for WordPress 4.7

= Version 1.0.3 - Released: Oct 19, 2016 =

* Fix: conflict with Gravity Forms Dropbox Add-On

= Version 1.0.2 - Released: Jan 28, 2016 =

* Updated: change text-domain from ywqe to yith-woocommerce-quick-export
* Updated: YITH plugin FW to latest release
* Updated: WP up to 4.4.1
* Fix: datepicker conflict with the CSS class selector
* Fix: plugin pot file in /languages folder

= Version 1.0.1 - Released: Aug 12, 2015 =

* Tweak: update YITH Plugin framework.

= Version 1.0.0 - Released: May 27 , 2015 =

Initial release
