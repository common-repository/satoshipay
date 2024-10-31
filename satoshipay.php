<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @wordpress-plugin
 * Plugin Name:       SatoshiPay
 * Plugin URI:        https://wordpress.org/plugins/satoshipay/
 * Description:       Integrates SatoshiPay's micropayment system into WordPress.
 * Version:           1.11
 * Author:            SatoshiPay
 * Author URI:        https://satoshipay.io
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       satoshipay
 * Domain Path:       /languages
 */

namespace SatoshiPay;

// Abort if this file is called directly.
if (!defined('WPINC')) {
    die("This file can not be executed as a stand-alone script.\n");
}

// Plugin version, used in user-agent string for API calls; keep in sync with
// version in plugin description above!
define('SATOSHIPAY_VERSION', '1.11');

// Plugin root file
define('SATOSHIPAY_PLUGIN_ROOT_FILE', plugin_basename(__FILE__));

// Load configuration, silently ignore missing config.php
@include_once __DIR__ . '/config.php';

// Read environment variables, will override config
if (!defined('SATOSHIPAY_PRODUCT_SERVICE_URL') && getenv('SATOSHIPAY_PRODUCT_SERVICE_URL')) {
    define('SATOSHIPAY_PRODUCT_SERVICE_URL', getenv('SATOSHIPAY_PRODUCT_SERVICE_URL'));
}
if (!defined('SATOSHIPAY_PUBLISHER_SERVICE_URL') && getenv('SATOSHIPAY_PUBLISHER_SERVICE_URL')) {
    define('SATOSHIPAY_PUBLISHER_SERVICE_URL', getenv('SATOSHIPAY_PUBLISHER_SERVICE_URL'));
}
if (!defined('SATOSHIPAY_CLIENT_URL') && getenv('SATOSHIPAY_CLIENT_URL')) {
    define('SATOSHIPAY_CLIENT_URL', getenv('SATOSHIPAY_CLIENT_URL'));
}
if (!defined('SATOSHIPAY_USE_BROWSER_DETECTION') && getenv('SATOSHIPAY_USE_BROWSER_DETECTION')) {
    define('SATOSHIPAY_USE_BROWSER_DETECTION', getenv('SATOSHIPAY_USE_BROWSER_DETECTION') === 'true' ? true : false);
}
if (!defined('SATOSHIPAY_USE_AD_BLOCKER_DETECTION') && getenv('SATOSHIPAY_USE_AD_BLOCKER_DETECTION')) {
    define('SATOSHIPAY_USE_AD_BLOCKER_DETECTION', getenv('SATOSHIPAY_USE_AD_BLOCKER_DETECTION') === 'true' ? true : false);
}
if (!defined('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE') && getenv('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE')) {
    define('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE', getenv('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE'));
}

// Use defaults if no environment or config variables were set
if (!defined('SATOSHIPAY_STYLE_ADMIN')) {
    define('SATOSHIPAY_STYLE_ADMIN', plugins_url('assets/css/style_admin.css', __FILE__));
}
if (!defined('SATOSHIPAY_SCRIPT_ADMIN')) {
    define('SATOSHIPAY_SCRIPT_ADMIN', plugins_url('assets/js/script_admin.js', __FILE__));
}
if (!defined('SATOSHIPAY_SCRIPT_ADMIN_MIGRATOR')) {
    define('SATOSHIPAY_SCRIPT_ADMIN_MIGRATOR', plugins_url('assets/js/script_admin_migrator.js', __FILE__));
}
if (!defined('SATOSHIPAY_SCRIPT_POST')) {
    define('SATOSHIPAY_SCRIPT_POST', plugins_url('assets/js/script_post.js', __FILE__));
}
if (!defined('SATOSHIPAY_PRODUCT_SERVICE_URL')) {
    define('SATOSHIPAY_PRODUCT_SERVICE_URL', 'https://api.satoshipay.io/v2');
}
if (!defined('SATOSHIPAY_PUBLISHER_SERVICE_URL')) {
    define('SATOSHIPAY_PUBLISHER_SERVICE_URL', 'https://api.satoshipay.io/mainnet/publisher');
}
if (!defined('SATOSHIPAY_CLIENT_URL')) {
    define('SATOSHIPAY_CLIENT_URL', 'https://wallet.satoshipay.io/satoshipay.js');
}
if (!defined('SATOSHIPAY_USE_BROWSER_DETECTION')) {
    define('SATOSHIPAY_USE_BROWSER_DETECTION', true);
}
if (!defined('SATOSHIPAY_USE_AD_BLOCKER_DETECTION')) {
    define('SATOSHIPAY_USE_AD_BLOCKER_DETECTION', true);
}
if (!defined('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE')) {
    // Default max product price for tier 2 publishers: 3 XLM = 3 * 10^7 = 30.000.000.
    define('SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE', 30000000);
}

require_once __DIR__ . '/src/SatoshiPay/SatoshiPayPlugin.php';
require_once __DIR__ . '/src/SatoshiPay/SatoshiPayAdminPlugin.php';
require_once __DIR__ . '/src/SatoshiPay/Gutenberg/init.php';

use SatoshiPay\SatoshiPayPlugin;
use SatoshiPay\SatoshiPayAdminPlugin;
use SatoshiPay\GutenbergEditor;

if (is_admin()) {
    add_action('plugins_loaded', array(SatoshiPayAdminPlugin::getInstance(__FILE__), 'init'));
} else {
    add_action('plugins_loaded', array(SatoshiPayPlugin::getInstance(__FILE__), 'init'));
}

// Initialize Gutenberg Satoshipay blocks
GutenbergEditor\init();

// installation procedure
include_once __DIR__ . '/src/SatoshiPay/SatoshiPayInstall.php';
register_activation_hook(__FILE__, array( 'SatoshiPay\SatoshiPayInstall', 'install' ) );
