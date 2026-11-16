<?php
/**
 * Plugin Name: On This Day (prototype)
 * Plugin URI: https://core.trac.wordpress.org/ticket/65116
 * Description: Prototype of a dedicated "On This Day" dashboard widget, per the scope in Trac #65116 comment:83. A throwaway proof of concept for design review, not production code.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

define( 'OTD_VERSION', '0.1.0' );
define( 'OTD_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTD_URL', plugin_dir_url( __FILE__ ) );

require_once OTD_DIR . 'includes/core-compat.php';
require_once OTD_DIR . 'includes/prefs.php';
require_once OTD_DIR . 'includes/query.php';
require_once OTD_DIR . 'includes/optin.php';
require_once OTD_DIR . 'includes/controls.php';
require_once OTD_DIR . 'includes/widget.php';
require_once OTD_DIR . 'includes/ajax.php';
