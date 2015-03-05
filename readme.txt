=== WooCommerce Putler Connector ===
Contributors: putler, storeapps
Tags: administration, putler, woocommerce, ecommerce, management, reporting, analysis, sales, products, orders, history, customers, graphs, charts
Requires at least: 3.3
Tested up to: 4.1.1
Stable tag: 2.3
License: GPL 3.0


== Description ==

Track WooCommerce orders on desktop and mobile with [Putler](http://putler.com/) -  Insightful reporting that grows your business.

WooCommerce Putler Connector sends transactions to Putler using Putler's Inbound API. All past orders are sent when you first configure this plugin. Future orders will be sent to Putler automatically. 

You need a Putler account (Free or Paid), and a WooCommerce based store to use this plugin.

= Installation =

1. Ensure you have latest version of [WooCommerce](http://www.woothemes.com/woocommerce/) plugin installed
2. Unzip and upload contents of the plugin to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Click on 'WooCommerce Putler Connector ' option within WordPress admin sidebar menu

= Configuration =

Go to Wordpress > Tools > Putler Connector

This is where you need to enter Putler API Token and Putler Email Address to sync your past WooCommerce transactions to Putler and start tracking WooCommerce transactions with Putler.

1. Enter your Putler Email Address.
2. Enter your Putler API Token which you will get once you add a new account "Putler Inbound API" in Putler
3. Click on "Save & Send Past Orders to Putler" to send all the WooCommerce past orders to Putler.

All past orders will be sent to Putler. New orders will be automatically synced.

= Where to find your Putler API Token? =

1. Sign up for a free account at: [Putler](http://www.putler.com/)
2. Download and install Putler on your desktop
3. Add a new account - select "Putler Inbound API" as the account type
4. Note down the API Token and copy the same API Token in Putler Connector Settings

== Frequently Asked Questions ==

= Can I use this with free version of Putler? =

Yes, you can use this connector with free version of Putler.

= Can I sync data to multiple Putler instances? =

Yes, you can sync data to multiple Putler instances using Putler API tokens separated by comma.

== Screenshots ==

1. WooCommerce Putler Connector Settings Page

2. Putler Sales Dashboard

3. Adding a new account in Putler - Notice API token that needs to be copied to Putler Connector settings

== Changelog ==

= 2.3 =
* Update: Transactions will show updated statuses in Putler.
* Update: Compatibility with new versions of WordPress & WooCommerce (v2.3 or greater)
* Fixed: Issue of product SKU not getting synced
* Fixed: Issue of orders in trash getting synced

= 2.2 =
* Update: Compatibility with new versions of WordPress & WooCommerce (v2.2 or greater)
* Fixed: Issue with syncing of product custom attributes 

= 2.1 =
* Fix: Date & Timezone issue

= 2.0 =
* New: Support for multiple API Tokens
* Fix: Minor Fixes and compatibilty

= 1.1 =
* Fixed: Minor Fixes related to variations data getting posted and Putler API url changes

= 1.0 =
* Initial release 


== Upgrade Notice ==

= 2.3 =
Updates related to transactions will show updated statuses in Putler, compatibility with new versions of WordPress & WooCommerce (v2.3 or greater) along with some important updates and fixes, recommended upgrade.

= 2.2 =
Compatibility with new versions of WordPress & WooCommerce (v2.2 or greater) along with some important updates and fixes, recommended upgrade. 

= 2.1 =
Fixes related to date & timezone issue, recommended upgrade.

= 2.0 =
Support for multiple API Tokens and Minor Fixes and compatibilty, recommended upgrade.

= 1.1 =
Minor Fixes related to variations data getting posted and Putler API url changes, recommended upgrade.

= 1.0 =
Welcome!!