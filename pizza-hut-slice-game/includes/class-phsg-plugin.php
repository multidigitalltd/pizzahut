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

		// הפניית עמוד הבית לעמוד המשחק (/game11/).
		add_action( 'template_redirect', array( $this, 'redirect_front_to_game' ) );

		// שורטקוד + נכסים.
		$shortcode = new PHSG_Shortcode();
		$shortcode->register();

		// AJAX.
		$ajax = new PHSG_Ajax();
		$ajax->register();

		// תבנית עמוד נחיתה מלאה.
		$template = new PHSG_Template();
		$template->register();

		// ממשק ניהול (בגב האתר בלבד).
		if ( is_admin() ) {
			$admin = new PHSG_Admin();
			$admin->register();
		}
	}

	/**
	 * הפניית עמוד הבית לעמוד המשחק.
	 *
	 * ברירת המחדל: /game11/. ניתן לשנות דרך הפילטר 'phsg_front_redirect_url'.
	 * מוגן מפני לולאת הפניה ומדלג על בקשות ניהול/AJAX/REST.
	 *
	 * @return void
	 */
	public function redirect_front_to_game() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_front_page() ) {
			return;
		}

		$target = apply_filters( 'phsg_front_redirect_url', home_url( '/game11/' ) );
		if ( empty( $target ) ) {
			return;
		}

		// הימנעות מלולאה אם עמוד הבית עצמו הוא כבר עמוד היעד.
		$current_path = untrailingslashit( wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ) );
		$target_path  = untrailingslashit( wp_parse_url( $target, PHP_URL_PATH ) );
		if ( $current_path === $target_path ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
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
