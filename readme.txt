=== TheMoak Virtual Try-on ===
Contributors: themoak
Tags: woocommerce, virtual try-on, eyewear, glasses, augmented reality, face tracking
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A virtual try-on solution for WooCommerce that allows customers to see how eyewear products look on their face using webcam and augmented reality.

== Description ==

TheMoak Virtual Try-on is a WooCommerce extension that enables customers to virtually try on eyewear products before purchasing. Using advanced face tracking technology, the plugin overlays glasses images onto the customer's face in real-time through their webcam.

= Features =
* Real-time virtual try-on using webcam and face tracking
* Responsive popup interface works on desktop, tablet, and mobile
* Modern glassmorphism effects for elegant presentation
* Customizable button text and appearance
* Fully customizable instructional text (with Persian language support)
* Admin panel for managing try-on-enabled products
* Upload transparent PNG images for each product
* Shortcodes for flexible integration

= How It Works =
1. Customers click the virtual try-on button on product pages
2. A popup opens requesting webcam access
3. Using advanced face tracking, glasses are overlaid on the customer's face
4. Glasses automatically adjust to the user's face movements

= Requirements =
* WordPress 5.0 or higher
* WooCommerce 3.0 or higher
* A modern browser with webcam support
* User permission to access their webcam

== Installation ==

1. Upload the `themoak-virtual-tryon` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Virtual Try-on > Settings to configure your preferences
4. Go to Virtual Try-on > Products to enable try-on for specific products and upload glasses images

== Usage ==

= Basic Usage =
Once the plugin is activated and configured, the try-on button will automatically appear on product pages for products that have try-on enabled.

= Using Shortcodes =
You can also use shortcodes to place the try-on button anywhere on your site:

* `[themoak_tryon]` - Displays the try-on button for the current product (on product pages)
* `[themoak_tryon product_id="123"]` - Displays the try-on button for a specific product

== Frequently Asked Questions ==

= Does this work on mobile devices? =
Yes, the virtual try-on works on mobile devices that have a front-facing camera and support the required web technologies.

= How do I create transparent PNG images for my glasses? =
For best results, photograph your glasses against a solid green or blue background, then use image editing software to remove the background and create a transparent PNG.

= Can I customize the text and appearance? =
Yes, all text elements can be customized through the settings page, including support for Persian language.

= How accurate is the face tracking? =
The plugin uses advanced MediaPipe face mesh technology to track facial landmarks with high accuracy. The glasses will adjust to the user's movements in real-time.

= Why does the plugin request camera access? =
Camera access is required for the virtual try-on functionality to work. The plugin does not store or transmit any webcam footage - all processing happens locally in the user's browser.

== Screenshots ==

1. Virtual try-on in action
2. Admin settings page
3. Product management page
4. Try-on button on product page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of TheMoak Virtual Try-on plugin.

== Credits ==

This plugin uses the following open-source libraries:
* MediaPipe FaceMesh - https://github.com/google/mediapipe