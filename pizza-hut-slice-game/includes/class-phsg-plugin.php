<?php
/**
 * מנצח הפלאגין – חיווט הרכיבים.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Plugin – נקודת הכניסה המרכזית.
 */
class PHSG_Plugin {

	/**
	 * הפעלת הרכיבים.
	 *
	 * @return void
	 */
	public function run() {
		// טעינת תרגומים.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// שורטקוד + נכסים.
		$shortcode = new PHSG_Shortcode();
		$shortcode->register();

		// AJAX.
		$ajax = new PHSG_Ajax();
		$ajax->register();

		// ממשק ניהול (בגב האתר בלבד).
		if ( is_admin() ) {
			$admin = new PHSG_Admin();
			$admin->register();
		}
	}

	/**
	 * טעינת קובצי שפה.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'pizza-hut-slice-game',
			false,
			dirname( PHSG_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
