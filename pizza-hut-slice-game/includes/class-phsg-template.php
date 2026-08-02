<?php
/**
 * תבנית עמוד נחיתה מלאה מתוך התוסף.
 *
 * מוסיפה לעורך העמודים תבנית "Pizza Hut – דף נחיתה מלא" ללא header/footer
 * של התבנית הפעילה – מתאים לדפי נחיתה מבאנרים.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Template – רישום תבנית עמוד מהתוסף.
 */
class PHSG_Template {

	const TEMPLATE_SLUG = 'phsg-landing.php';

	/**
	 * רישום ה-hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'theme_page_templates', array( $this, 'add_template' ) );
		add_filter( 'template_include', array( $this, 'load_template' ) );
	}

	/**
	 * הוספת התבנית לרשימת תבניות העמוד בעורך.
	 *
	 * @param array $templates תבניות קיימות.
	 * @return array
	 */
	public function add_template( $templates ) {
		$templates[ self::TEMPLATE_SLUG ] = __( 'Pizza Hut – דף נחיתה מלא', 'pizza-hut-slice-game' );
		return $templates;
	}

	/**
	 * טעינת קובץ התבנית מהתוסף כאשר העמוד משתמש בה.
	 *
	 * @param string $template נתיב התבנית הנוכחי.
	 * @return string
	 */
	public function load_template( $template ) {
		if ( ! is_singular( 'page' ) ) {
			return $template;
		}

		$assigned = get_page_template_slug( get_queried_object_id() );

		if ( self::TEMPLATE_SLUG === $assigned ) {
			$plugin_template = PHSG_PLUGIN_DIR . 'templates/landing-page.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}
}
