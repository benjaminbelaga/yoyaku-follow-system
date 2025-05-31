<?php
/**
 * Plugin Name:       YOYAKU Follow System
 * Plugin URI:        https://www.yoyaku.io/
 * Description:       Allows users to follow music labels and artists and receive notifications for new products.
 * Version:           1.0.0
 * Author:            YOYAKU Development Team
 * Author URI:        https://www.yoyaku.io/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yoyaku-follow-system
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'YFS_VERSION', '1.0.0' );
define( 'YFS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YFS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YFS_TAX_MUSICLABEL', 'musiclabel' );
define( 'YFS_TAX_MUSICARTIST', 'musicartist' );

function activate_yfs_plugin() {
	require_once YFS_PLUGIN_DIR . 'includes/class-yfs-activator.php';
	YFS_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_yfs_plugin' );

require YFS_PLUGIN_DIR . 'includes/class-yfs-db.php';
require YFS_PLUGIN_DIR . 'includes/class-yfs-assets.php'; // Added
require YFS_PLUGIN_DIR . 'includes/class-yfs-frontend.php'; // Added
require YFS_PLUGIN_DIR . 'includes/class-yfs-ajax.php';   // Added
// require YFS_PLUGIN_DIR . 'includes/class-yfs-notifications.php'; // For later
// require YFS_PLUGIN_DIR . 'includes/class-yfs-my-account.php';    // For later

class Yoyaku_Follow_System {

	public $db_handler;
	public $assets;
	public $frontend;
	public $ajax;
	// public $notifications;
	// public $my_account;

	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		$this->db_handler = new YFS_DB();
		$this->assets = new YFS_Assets(); // Instantiated
		$this->frontend = new YFS_Frontend( $this->db_handler ); // Instantiated
		$this->ajax = new YFS_Ajax( $this->db_handler );       // Instantiated
		// $this->notifications = new YFS_Notifications( $this->db_handler );
		// $this->my_account = new YFS_My_Account( $this->db_handler );
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // The init methods of the classes will register their own hooks
		if ( $this->assets ) {
			$this->assets->init();
		}
		if ( $this->frontend ) {
			$this->frontend->init();
		}
		if ( $this->ajax ) {
			$this->ajax->init();
		}
		// Similarly for other classes when added
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'yoyaku-follow-system',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}
}

function run_yoyaku_follow_system() {
	if ( class_exists( 'WooCommerce' ) ) {
		$GLOBALS['yoyaku_follow_system'] = new Yoyaku_Follow_System();
	} else {
		add_action( 'admin_notices', 'yfs_woocommerce_missing_notice' );
	}
}

function yfs_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'YOYAKU Follow System requires WooCommerce to be activated.', 'yoyaku-follow-system' ); ?></p>
	</div>
	<?php
}
add_action( 'plugins_loaded', 'run_yoyaku_follow_system', 20 ); // Ensure it runs after WooCommerce might be loaded by theme.