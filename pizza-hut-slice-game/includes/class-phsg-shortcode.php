<?php
/**
 * רישום השורטקוד וטעינת נכסים על פי הצורך.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Shortcode – השורטקוד [pizza_hut_slice_game].
 */
class PHSG_Shortcode {

	/**
	 * דגל – האם השורטקוד קיים בעמוד הנוכחי (לטעינת נכסים בלבד בעת הצורך).
	 *
	 * @var bool
	 */
	private $assets_needed = false;

	/**
	 * רישום ה-hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'pizza_hut_slice_game', array( $this, 'render' ) );

		// זיהוי מוקדם של השורטקוד כדי לרשום את הנכסים רק בעמוד הרלוונטי.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_register_assets' ) );
	}

	/**
	 * רישום ה-assets (בלי טעינה בפועל) והחלטה אם צריך לטעון אותם.
	 *
	 * @return void
	 */
	public function maybe_register_assets() {
		wp_register_style(
			'phsg-game',
			PHSG_PLUGIN_URL . 'assets/css/game.css',
			array(),
			PHSG_VERSION
		);

		wp_register_script(
			'phsg-game',
			PHSG_PLUGIN_URL . 'assets/js/game.js',
			array(),
			PHSG_VERSION,
			true
		);

		// אם העמוד הנוכחי מכיל את השורטקוד – סמן שנצטרך את הנכסים.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( $post->post_content, 'pizza_hut_slice_game' ) ) {
				$this->assets_needed = true;
				$this->enqueue_assets();
			}
		}
	}

	/**
	 * טעינת ה-assets בפועל והעברת נתונים ל-JS.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'phsg-game' );
		wp_enqueue_script( 'phsg-game' );

		wp_localize_script(
			'phsg-game',
			'PHSG_DATA',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( PHSG_Ajax::NONCE_ACTION ),
				'gameDuration' => PHSG_Anti_Cheat::GAME_DURATION,
				'sliceTimeout' => PHSG_Anti_Cheat::SLICE_TIMEOUT,
				'i18n'         => array(
					'required'      => __( 'שדה חובה', 'pizza-hut-slice-game' ),
					'invalidEmail'  => __( 'אימייל לא תקין', 'pizza-hut-slice-game' ),
					'invalidPhone'  => __( 'טלפון לא תקין', 'pizza-hut-slice-game' ),
					'consentNeeded' => __( 'יש לאשר את תנאי ההשתתפות', 'pizza-hut-slice-game' ),
					'saveError'     => __( 'אירעה שגיאה בשמירה. נסו שוב.', 'pizza-hut-slice-game' ),
					'rankOf'        => __( 'מתוך', 'pizza-hut-slice-game' ),
					'go'            => __( 'GO!', 'pizza-hut-slice-game' ),
					'combo'         => __( 'קומבו', 'pizza-hut-slice-game' ),
					'comboBroken'   => __( 'הקומבו נשבר!', 'pizza-hut-slice-game' ),
					'dailyChamp'    => __( 'שיאן/ית היום! 🏆', 'pizza-hut-slice-game' ),
					'dailyRank'     => __( 'דירוג יומי', 'pizza-hut-slice-game' ),
					'shareText'     => __( 'תפסתי %s משולשי פיצה ב-60 שניות במשחק של פיצה האט! 🍕 נסו לעבור אותי:', 'pizza-hut-slice-game' ),
					'copied'        => __( 'הועתק!', 'pizza-hut-slice-game' ),
				),
			)
		);
	}

	/**
	 * רינדור השורטקוד.
	 *
	 * @param array $atts תכונות השורטקוד.
	 * @return string HTML.
	 */
	public function render( $atts = array() ) {
		// גיבוי: אם הזיהוי המוקדם החמיץ (למשל שורטקוד בתוך ווידג'ט), נטען כאן.
		if ( ! $this->assets_needed ) {
			$this->assets_needed = true;
			// ודא שהנכסים רשומים.
			if ( ! wp_style_is( 'phsg-game', 'registered' ) ) {
				$this->maybe_register_assets();
			}
			$this->enqueue_assets();
		}

		$atts = shortcode_atts(
			array(
				'title'       => __( 'תפוס את המשולש', 'pizza-hut-slice-game' ),
				'subtitle'    => __( 'האט אנד יו נואו איט', 'pizza-hut-slice-game' ),
				'logo'        => '', // URL ללוגו (מומלץ SVG/PNG שקוף). ריק = לוגו ברירת מחדל.
				'coupon_code' => '', // קוד קופון למסך הסיום. ריק = ללא קופון.
				'coupon_min'  => 15, // רף ניקוד מינימלי לקבלת הקופון.
				'daily_prize' => __( 'שיאן/ית היום זוכה לפיצה משפחתית! 🍕', 'pizza-hut-slice-game' ), // ריק = הסתרת באנר הפרס.
			),
			$atts,
			'pizza_hut_slice_game'
		);

		ob_start();
		// רינדור ראשוני = הלוח היומי (הטאב הפעיל כברירת מחדל).
		$leaderboard = PHSG_Leaderboard::get_public( 10, true );
		include PHSG_PLUGIN_DIR . 'templates/game.php';
		return ob_get_clean();
	}
}
