<?php
/**
 * Plugin Name:       Pizza Hut Slice Game
 * Plugin URI:        https://multidigital.co.il/
 * Description:        משחק קמפיין ממותג של פיצה האט – "תפוס את המשולש". כולל מסך פתיחה, טופס משתתף, משחק, טבלת מובילים ואיסוף נתונים בטבלת DB ייעודית. שימוש: [pizza_hut_slice_game].
 * Version:           12.6.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Multi Digital
 * Author URI:        https://multidigital.co.il/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pizza-hut-slice-game
 * Domain Path:       /languages
 *
 * @package PizzaHutSliceGame
 */

// חסימת גישה ישירה.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * קבועי הפלאגין.
 */
define( 'PHSG_VERSION', '12.6.1' );
define( 'PHSG_PLUGIN_FILE', __FILE__ );
define( 'PHSG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHSG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PHSG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// שם טבלת הניקוד (ללא ה-prefix של WordPress).
define( 'PHSG_TABLE_SCORES', 'phsg_scores' );

/**
 * טעינת מחלקות הליבה.
 */
require_once PHSG_PLUGIN_DIR . 'includes/phsg-helpers.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-db.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-anti-cheat.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-leaderboard.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-ajax.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-shortcode.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-template.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-admin.php';
require_once PHSG_PLUGIN_DIR . 'includes/class-phsg-plugin.php';

/**
 * הפעלת הפלאגין – יצירת טבלת ה-DB.
 */
function phsg_activate() {
	PHSG_DB::create_table();
	// שמירת גרסת הסכימה לצורך שדרוגים עתידיים.
	update_option( 'phsg_db_version', PHSG_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'phsg_activate' );

/**
 * כיבוי הפלאגין.
 */
function phsg_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'phsg_deactivate' );

/**
 * אתחול הפלאגין לאחר טעינת כל התוספים.
 */
function phsg_init() {
	// בדיקת שדרוג סכימה (אם הפלאגין עודכן ללא re-activation).
	if ( get_option( 'phsg_db_version' ) !== PHSG_VERSION ) {
		PHSG_DB::create_table();
		update_option( 'phsg_db_version', PHSG_VERSION );
	}

	$plugin = new PHSG_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'phsg_init' );
