=== YITH WooCommerce Subscription  ===

Contributors: yithemes
Tags: recurring billing, subscription billing, subscription box, Subscription Management, subscriptions
Requires PHP: 7.4
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 4.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

It allows you to manage recurring payments for product subscription that grant you constant periodical income

== Description ==

YITH WooCommerce Subscription is the complete solution to **sell products or services with a subscription plan** in your e-commerce, manage recurring payments and loyalize your customers.

Selling products on a subscription basis lets you get regular payments and monitor your income growth month by month.

This has become the most popular business model in recent years: users pay for a subscription – typically weekly, monthly, or annually – to use software, to access training courses, to read a magazine, to get unlimited access to an archive of useful resources (like the audio file library offered by Amazon Audible or sites with photos stocks) or to benefit from a hosting service. But this applies also to gyms, video games, libraries, consulting or coaching services, legal advisory, etc… It’s a winning business model that suits most of the products or services available for sale.

With this plugin you can easily create **subscription products and get recurring payments** in your WooCommerce shops.

==YITH WooCommerce Subscription Features==

= FREE VERSION =
* Create a subscription plan for simple, virtual or downloadable products
* Choose the recurring payment type (daily, weekly, monthly, yearly, etc.)
* Set an optional end time for the subscription
* Enable variations as subscription based (to set different subscriptions plans and pricing using variations)
* User subscriptions can be charged automatically only when selecting PayPal payment method
* Users can find the subscription information on "My Account" page
* Customize the "Add to Cart" button label
* Customize the “Place order” button label at checkout
* NEW: Integration with WooCommerce PayPal Payments to let your customers easily pay through PayPal

See it in action here:

[Check the Live Demo of the Free Version >](https://plugins.yithemes.com/yith-woocommerce-subscription-free/)

= NEED MORE? CHECK THE PREMIUM VERSION! =

* Offer a trial period to users before subscribing to a plan
* Ask for a sign-up fee when purchasing a subscription plan
* Synchronize all subscription payments to a specific day of the week, month or year (Example: all payments will be taken the 1st of each month) New
* Cancel a subscription automatically if the associated order is canceled
* Suspend a subscription automatically in case the periodical payment fails
* After three failed payment attempts with Stripe or PayPal, the subscription is automatically set to "Canceled"
* Postpone the automatic status switch (for "active", "overdue" and "suspended" status) by a specific number of hours
* Let users force the payment after the first attempt is failed (through a “Renew Now” button)
* Users can pause a subscription for a certain number of times (decided by the administrator) and resume it later: the expiration date will update accordingly
* Allow users access their subscription contents even after the expiring date and while you’re waiting for their payment
* Suspend access to content on the expiry date, without deleting the subscription
* Allow users to resubscribe a plan from "My Account" page and being granted the same conditions of the expired or canceled plan, without having to pay the sign-up fee once again or to benefit of the trial period once again
* Users can upgrade or downgrade a subscription plan (configurable in variable products only)
* Show users the total duration and the total amount of a subscription
* Limit users to only one subscription per product
* Choose if a user can add only one or more subscription products to cart New
* Edit and load billing and shipping address in the subscription details
* Let users edit the address for only one or for all subscriptions right from My Account page
* Create subscriptions manually from the backend
* Recap the subscription information on a page (Start and expiration dates, details of the subscribed product, billing and shipping information, orders linked to the subscription)
* Edit subscription details like renewal date, expiry date, amount, billing cycle if used (not available with PayPal standard)
* Dashboard with sale reports (net sales, renewals, trials, MRR, ARR, etc.)
* Export all your subscriptions to a CSV file
* Create coupons for the sign-up fee or for the recurring fee (you can now specify the number of recurring payments to which the coupon will apply)
* Receive automatic emails on the following conditions: a subscription plan is going to expire/has been canceled/has been paused/has been resumed
* Receive automatic emails when a payment has been made
* Automatically enable the staging mode when you clone the site to prevent double charges
* Choose to show or not custom messages in the product page about the Signup Fee, the Trial and the recurring payment scheduling
* Use the advanced Gutenberg block to show subscription plans with a modern columns design
* Organize and plan your subscription-based product deliveries and decide if you want to synchronize all the deliveries on a specific day (every Monday, every 1st of the month etc.)
* Print a PDF list of shipping labels with all the addresses of subscription-based deliveries
* Make users pay shipping fees only once in the subscription period with one-time shipping
* NEW: The “Subscription Box” module lets your customers sign up for a box and customize it by choosing the quantity and types of products they want to include

See it in action here:

[Check the Live Demo of the Premium Version >](https://plugins.yithemes.com/yith-woocommerce-subscription/)

== Installation ==
Important: First of all, you have to download and activate WooCommerce plugin, which is essential for YITH WooCommerce Subscription to work.

1. Unzip the downloaded zip file.
2. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.
3. Activate `YITH WooCommerce Subscription` from Plugins page.

= Configuration =
YITH WooCommerce Subscription will add a new tab called "Subscription" in "YITH" menu item.
There, you will find all YITH plugins with quick access to plugin setting page.


== Frequently Asked Questions ==

= Is it compatible with all WordPress themes? =

Compatibility with all themes is impossible, because they are too many, but generally if themes are developed according to WordPress and WooCommerce guidelines, YITH plugins are compatible with them.
Yet, we can grant compatibility with themes developed by YITH, because they are constantly updated and tested with our plugins. Sometimes, especially when new versions are released, it might only require some time for them to be all updated, but you can be sure that they will be tested and will be working in a few days.

= How can I report security bugs? =
You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/yith-woocommerce-subscription)

== Screenshots ==
1. List of subscriptions
2. Plugin options
3. Plugin customization options
4. View subscriptions in your WooCommerce shop page
5. View subscriptions in WooCommerce single product page
6. Cart page
7. Checkout page
8. Subscription list on "My Account" page
9. Subscription detail on "My Account" page


== Changelog ==

= 4.5.0 - Released on 03 Mar 2025 =
 * New: support for WooCommerce 9.7
 * Update: YITH plugin framework
 * Fix: undefined method for callback

= 4.4.0 - Released on 04 Feb 2025 =
 * New: support for WooCommerce 9.6
 * Update: YITH plugin framework

= 4.3.0 - Released on 20 Dec 2024 =
 * New: option to allow customer to pay the renewal order on My Account page if the payment gateway does not support automatic payments

= 4.2.0 - Released on 09 Dec 2024 =
 * New: support for WooCommerce 9.5
 * New: support for WordPress 6.7
 * Update: YITH plugin framework

= 4.1.2 - Released on 03 Oct 2024 =
 * Fix: missing subscription_info order item meta

= 4.1.1 - Released on 26 Sep 2024 =
 * New: support for WooCommerce PayPal Payments 2.9.1

= 4.1.0 - Released on 13 Sep 2024 =
 * New: support for WooCommerce 9.3
 * Update: YITH plugin framework

= 4.0.1 - Released on 6 Sep 2024 =
 * Fix: improved subscription renew query to process only expiring subscription

= 4.0.0 - Released on 4 Sep 2024 =
 * New: register wp cron to handle subscription renew
 * Update: YITH plugin framework
 * Tweak: improved support to WooCommerce PayPal Payments

= 3.9.0 - Released on 20 Aug 2024 =
 * New: support for WooCommerce 9.2
 * New: support for WordPress 6.6
 * Update: YITH plugin framework

= 3.8.0 - Released on 9 Jul 2024 =
 * New: support for WooCommerce 9.1
 * Update: YITH plugin framework
 * Fix: Double check payment method in "paypal payments" module on subscription's payment completed

= 3.7.1 - Released on 1 Jul 2024 =
 * Fix: Removed all polyfill.io references

= 3.7.0 - Released on 27 Jun 2024 =
 * New: support for WooCommerce 9.0
 * Update: YITH plugin framework
 * Tweak: improved support for PHP 8.2
 * Tweak: improved support for WooCommerce checkout block

= 3.6.0 - Released on 23 May 2024 =
 * New: support for WooCommerce 8.9
 * Update: YITH plugin framework

= 3.5.0 - Released on 29 April 2024 =
 * New: support for WooCommerce 8.8
 * New: support for WordPress 6.5
 * Update: YITH plugin framework

= 3.4.0 - Released on 21 March 2024 =
 * New: support for WooCommerce 8.7
 * Update: YITH plugin framework
 * Fix: subscription renew using WooCommerce PayPal Payments

= 3.3.0 - Released on 20 February 2024 =
 * New: support for WooCommerce 8.6
 * New: reset filters button for subscriptions list table
 * Update: YITH plugin framework
 * Dev: new filter 'ywsbs_pay_order_check'

= 3.2.0 - Released on 11 January 2024 =
 * New: support for WooCommerce 8.5
 * New: support for WooCommerce PayPal Payments 2.4.3
 * Update: YITH plugin framework

= 3.1.0 - Released on 15 December 2023 =
 * New: support for WooCommerce 8.4
 * Update: YITH plugin framework
 * Fix: undefined functions in PayPal IPN handler

= 3.0.1 - Released on 23 November 2023 =
 * Fix: Javascript error for product variation edit

= 3.0.0 - Released on 15 November 2023 =
 * New: support for WooCommerce 8.3
 * New: support for WordPress 6.4
 * New: support for new cart and checkout WooCommerce blocks
 * New: support for WooCommerce PayPal Payments
 * New: plugin admin settings panel
 * Update: YITH plugin framework

= 2.27.0 - Released on 10 October 2023 =
 * New: support for WooCommerce 8.2
 * Update: YITH plugin framework

= 2.26.0 - Released on 11 September 2023 =
 * New: support for WooCommerce 8.1
 * Update: YITH plugin framework

= 2.25.0 - Released on 8 August 2023 =
 * New: support for WordPress 6.3
 * New: support for WooCommerce 8.0
 * Update: YITH plugin framework

= 2.24.0 - Released on 10 July 2023 =
 * New: support for WooCommerce 7.9
 * Update: YITH plugin framework

= 2.23.0 - Released on 13 June 2023 =
 * New: support for WooCommerce 7.8
 * Update: YITH plugin framework

= 2.22.0 - Released on 8 May 2023 =
 * New: support for WooCommerce 7.7
 * Update: YITH plugin framework

= 2.21.0 - Released on 13 April 2023 =
 * New: support for WooCommerce 7.6
 * New: support for PHP 8.1
 * Update: YITH plugin framework

= 2.20.0 - Released on 10 March 2023 =
 * New: support for WordPress 6.2
 * New: support for WooCommerce 7.5
 * Update: YITH plugin framework

= 2.19.0 - Released on 9 February 2023 =
* New: support for WooCommerce 7.4
* Update: YITH plugin framework

= 2.18.0 - Released on 9 January 2023 =
* New: support for WooCommerce 7.3
* Update: YITH plugin framework

= 2.17.0 - Released on 11 December 2022 =
* New: support for WooCommerce 7.2
* Update: YITH plugin framework

= 2.16.1 - Released on 11 November 2022 =
 * Fix: patched security vulnerability
 
= 2.16.0 - Released on 3 November 2022 =
* New: support for WordPress 6.1
* New: support for WooCommerce 7.1
* Update: YITH plugin framework

= 2.15.0 - Released on 3 October 2022 =
* New: support for WooCommerce 7.0
* Update: YITH plugin framework

= 2.14.0 - Released on 6 September 2022 =
* New: support for WooCommerce 6.9
* Update: YITH plugin framework

= 2.13.0 - Released on 4 August 2022 =
* New: support for WooCommerce 6.8
* Update: YITH plugin framework

= 2.12.0 - Released on 12 July 2022 =
* New: support for WooCommerce 6.7
* Update: YITH plugin framework

= 2.11.0 - Released on 13 June 2022 =
* New: support for WooCommerce 6.6
* Update: YITH plugin framework

= 2.10.0 - Released on 9 May 2022 =
* New: support for WordPress 6.0
* New: support for WooCommerce 6.5
* Update: YITH plugin framework

= 2.9.0 - Released on 11 April 2022 =
* New: support for WooCommerce 6.4
* Update: YITH plugin framework

= 2.8.0 - Released on 24 February 2022 =
* New: support for WooCommerce 6.3

= 2.7.0 - Released on 9 February 2022 =
* New: support for WooCommerce 6.2
* Update: YITH plugin framework
* Fix: fixed potential php notice

= 2.6.0 - Released on 25 January 2022 =
* New: support for WordPress 5.9
* Update: YITH plugin framework

= 2.5.0 - Released on 12 January 2022 =
* New: support for WooCommerce 6.1
* Update: YITH plugin framework

= 2.4.0 - Released on 2 December 2021 =
* New: support for WooCommerce 6.0
* Update: YITH plugin framework

= 2.3.0 - Released on 9 November 2021 =
* New: support for WooCommerce 5.9
* Update: YITH plugin framework

= 2.2.0 - Released on 13 October 2021 =
* New: support for WooCommerce 5.8
* Update: YITH plugin framework

= 2.1.1 - Released on 27 September 2021 =
* Update: YITH plugin framework
* Fix: debug info feature removed for all logged in users

= 2.1.0 - Released on 7 September 2021 =
* New: support for WooCommerce 5.7
* Update: YITH plugin framework
* Fix: issue with WPML plugin during the translation of product

= 2.0.2 - Released on 6 August 2021 =
* New: support for WooCommerce 5.6
* Update: YITH plugin framework

= 2.0.1 - Released on 1 July 2021 =
* Tweak: Wp List style

= 2.0.0 - Released on 28 June, 2021 =
* New: support for WordPress 5.8
* New: support for WooCommerce 5.5
* New: Subscription table list
* New: Recap the subscription information on a page of administrator panel
* New: Users can find the subscription information on "My Account" page
* New: Limit users to only one subscription per product
* New: Stock management with recurring payments option
* New: Cancel a subscription automatically if the associated order is canceled
* New: Option to allow the shop manager to access and edit the plugin options
* New: Customize the "Add to Cart" button label
* New: Customize the “Place order” button label at checkout
* Update: YITH plugin framework

= 1.5.7 - Released on 27 May, 2021 =
* New: support for WooCommerce 5.4
* Update: YITH plugin framework

= 1.5.6 - Released on 11 May, 2021 =
* Fix: fixed issue on product page editor

= 1.5.5 - Released on 6 May, 2021 =
* Update: YITH plugin framework
* Fix: issue with PayPal Payments

= 1.5.4 - Released on 6 May, 2021 =
* New: support for WooCommerce 5.3
* Update: YITH plugin framework

= 1.5.3 - Released on 7 April, 2021 =
* New: support for WooCommerce 5.2
* Update: YITH plugin framework

= 1.5.2 - Released on 24 February, 2021 =
* New: support for WordPress 5.7
* New: support for WooCommerce 5.1
* Update: YITH plugin framework

= 1.5.1 - Released on 3 February, 2021 =
* New: support for WooCommerce 5.0
* Update: YITH plugin framework

= 1.5.0 - Released on 8 Jan, 2021 =
* New: Support for WooCommerce 4.9
* Update: Plugin framework

= 1.4.10 - Released on 4 Dec, 2020 =
* New: Support for WooCommerce 4.9
* Update: Plugin framework

= 1.4.9 - Released on 3 Dec, 2020 =
* New: Support for WooCommerce 4.8
* Update: Plugin framework

= 1.4.8 - Released on 29 Oct, 2020 =
* New: Support for WordPress 5.6
* New: Support for WooCommerce 4.7
* Update: Plugin framework

= 1.4.7 - Released on 7 Oct, 2020 =
* New: Support for WooCommerce 4.6
* Update: Plugin framework

= 1.4.6 - Released on 18 Sep, 2020 =
* New: Support for WooCommerce 4.5
* Update: Plugin framework

= 1.4.5 - Released on 10 Aug, 2020 =
* New: Support for WooCommerce 4.4
* Update: Plugin framework

= 1.4.4 - Released on 9 Jul, 2020 =
* New: Support for WooCommerce 4.3
* New: Support for WordPress 5.5
* Update: Plugin framework
* Update: Premium tab
* Fix: Fixed PDT Payment Issue with PayPal Standard

= 1.4.3 - Released on 26 May, 2020 =
* New: Support for WooCommerce 4.2
* Update: Plugin framework

= 1.4.2 - Released on 30 April, 2020 =
* New: Support for WooCommerce 4.1
* Update: Plugin framework

= 1.4.1 - Released on 09 March, 2019 =
* New: Support for WordPress 5.4
* New: Support for WooCommerce 4.0
* Update: Plugin framework

= 1.4.0 - Released on 13 January, 2019 =
* Update: Plugin framework
* Fix: Fixed recurring period on order item price

= 1.3.9 - Released on 23 December 2019 =

* New: Support for WooCommerce 3.9
* Update: Plugin framework

= 1.3.8 - Released on 29 November 2019 =

* Update: Plugin framework


= 1.3.6 - Released on 30 October 2019 =

* Update: Plugin framework

= 1.3.5 - Released on 29 October 2019 =

* New: Support for WordPress 5.3
* New: Support for WooCommerce 3.8
* Update: Plugin framework

= 1.3.4 - Released on 26 July 2019 =

* New: Support for WooCommerce 3.7
* Update: Plugin framework
* Update: Setting panel style

= 1.3.3 - Released on 12 June 2019 =

* Update: Plugin framework

= 1.3.2 - Released on 23 April 2019 =

* Update: Plugin framework

= 1.3.1 - Released on 09 April 2019 =

* New: Support for WooCommerce 3.6
* Update: Plugin framework

= 1.3.0 - Released on 28 January 2019 =

* Update: Plugin framework

= 1.2.9 - Released on 06 December 2018 =

* New: Support for WordPress 5.0
* Update: Plugin framework

= 1.2.8 - Released on 29 November 2018 =

* Update: Plugin framework
* Fix: Fix a possible fatal error during the payment

= 1.2.7 - Released on 23 October 2018 =

* Update: Plugin framework

= 1.2.6 - Released on 16 October 2018 =

* New: Support for WooCommerce 3.5
* Update: Plugin framework

= 1.2.5 - Released on 26 September 2018 =

* Update: Plugin framework

= 1.2.4 - Released on 20 September 2018 =

* Update: Readme
* Fix: Fixed minor issue

= 1.2.3 - Released on 03 September 2018 =

* Update: Plugin framework
* Fix: Issue when a coupon is added to an order with subscription product.

= 1.2.2 - Released on 15 May 2018 =

* New: Support for WordPress 4.9.6 RC1
* New: Support for WooCommerce 3.4.0 RC1
* New: Privacy settings option
* New: Retain pending subscriptions option
* New: Retain cancelled subscriptions option
* Update: Update Core Framework 3.0.15
* Update: Localization files
* Fix: Expiring Date format

= 1.2.1 - Released on 31 January 2018 =

* New: Support for WooCommerce 3.3
* Update: Plugin framework

= 1.2.0 - Released on 16 October 2017 =

* New: Support for WooCommerce 3.2
* New: Added taxes on subscription recurring amount
* New: Added shipping on subscription recurring amount
* Update: Plugin framework
* Dev: Hard changes on class YWSBS_Subscription
* Dev: Added class YWSBS_Subscription_Helper

= 1.1.1 - Released on 27 August 2017 =

* New: Support for PHP 7.1
* Update: Plugin framework
* Fix: Localization price on cart

= 1.1.0 - Released on 04 April 2017 =

* New: Support for WooCommerce 3.0
* Update: Plugin framework

= 1.0.5 - Released on 23 November 2016 =

* Fix: round prices in PayPal payments

= 1.0.4 - Released on 04 October 2016 =

* Fix: singular/plural names in html price
* Fix: string translation on backend
* Update: Plugin framework

= 1.0.3 - Released on 26 August 2016 =

* New: Infinite recurring payment
* Update: Plugin framework

= 1.0.2 - Released on 24 March 2016 =

* New: Sidebar in Administrator settings
* Update: Width of subscription's list table
* Update: Name of Author

= 1.0.1 - Released on 24 November 2016 =

* New: Compatibility with Wordpress 4.4
* Fix: Notice if the debug in inactive
* Fix: Javascript error in administrator panel

= 1.0.0 - Released on 17 December 2016 =

* Initial release

== Suggestions ==
If you have any suggestions concerning how to improve YITH WooCommerce Subscription, you can [write to us](mailto:plugins@yithemes.com "Your Inspiration Themes"), so that we can improve YITH WooCommerce Subscription.

