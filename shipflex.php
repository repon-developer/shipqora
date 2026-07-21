<?php

/**
 * Plugin Name: ShipFlex
 * Description: 
 * Version: 1.0.0
 * Author: ShipFlex
 * Author URI: https://shipflexpro.com
 * Text Domain: shipflex
 * 
 * Requires Plugins: woocommerce
 * Requires at least: 6.8
 * Tested up to: 7.0
 * Requires PHP: 8.1
 * 
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ShipFlex_FILE', __FILE__);
define('ShipFlex_BASENAME', plugin_basename(__FILE__));
define('ShipFlex_URI', trailingslashit(plugins_url('/', __FILE__)));
define('ShipFlex_PATH', trailingslashit(plugin_dir_path(__FILE__)));

/**
 * Declare HPOS compatibility
 * 
 * @since 1.0.0
 */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

require_once ShipFlex_PATH . 'inc/class-utils.php';
require_once ShipFlex_PATH . 'inc/class-main.php';




