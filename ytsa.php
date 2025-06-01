<?php
/**
 * Plugin Name:       YTSA
 * Plugin URI:        https://github.com/benjaminbelaga
 * Description:       Allows users to follow music labels and artists and receive notifications for new products.
 * Version:           1.0.0
 * Author:            Benjamin Belaga
 * Author URI:        https://github.com/benjaminbelaga
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ytsa
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'YTSA_VERSION', '1.0.0' );
define( 'YTSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YTSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YTSA_TAX_MUSICLABEL', 'musiclabel' );
define( 'YTSA_TAX_MUSICARTIST', 'musicartist' );

function activate_ytsa_plugin() {
        require_once YTSA_PLUGIN_DIR . 'includes/class-ytsa-activator.php';
        YTSA_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_ytsa_plugin' );

require YTSA_PLUGIN_DIR . 'includes/class-ytsa-db.php';
require YTSA_PLUGIN_DIR . 'includes/class-ytsa-assets.php'; // Added
require YTSA_PLUGIN_DIR . 'includes/class-ytsa-frontend.php'; // Added
require YTSA_PLUGIN_DIR . 'includes/class-ytsa-ajax.php';   // Added
// require YTSA_PLUGIN_DIR . 'includes/class-ytsa-notifications.php'; // For later
// require YTSA_PLUGIN_DIR . 'includes/class-ytsa-my-account.php';    // For later

class YTSA_Plugin {

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
            $this->db_handler = new YTSA_DB();
            $this->assets = new YTSA_Assets(); // Instantiated
            $this->frontend = new YTSA_Frontend( $this->db_handler ); // Instantiated
            $this->ajax = new YTSA_Ajax( $this->db_handler );       // Instantiated
            // $this->notifications = new YTSA_Notifications( $this->db_handler );
            // $this->my_account = new YTSA_My_Account( $this->db_handler );
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
                        'ytsa',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}
}

function run_ytsa() {
	if ( class_exists( 'WooCommerce' ) ) {
                $GLOBALS['ytsa'] = new YTSA_Plugin();
	} else {
                add_action( 'admin_notices', 'ytsa_woocommerce_missing_notice' );
	}
}

function ytsa_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
            <p><?php esc_html_e( 'YTSA requires WooCommerce to be activated.', 'ytsa' ); ?></p>
	</div>
	<?php
}
add_action( 'plugins_loaded', 'run_ytsa', 20 ); // Ensure it runs after WooCommerce might be loaded by theme.
