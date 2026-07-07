<?php
/**
 * רישום השורטקודים וטעינת נכסים על פי הצורך.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Shortcode – [pizza_hut_slice_game] + [pizza_slice_leaderboard].
 */
class PHSG_Shortcode {

	/**
	 * דגל – האם הנכסים כבר נטענו בעמוד הנוכחי.
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
		add_shortcode( 'pizza_slice_leaderboard', array( $this, 'render_leaderboard' ) );

		// זיהוי מוקדם של השורטקוד כדי לרשום את הנכסים רק בעמוד הרלוונטי.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_register_assets' ) );
	}

	/**
	 * רישום ה-assets (בלי טעינה בפועל) והחלטה אם צריך לטעון אותם.
	 *
	 * @return void
	 */
	public function maybe_register_assets() {
		// גופני המותג (מכמורת + אלמוני) נטענים מקומית מ-assets/fonts דרך ה-CSS.
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

		// אם העמוד הנוכחי מכיל שורטקוד – טוענים כאן.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ( has_shortcode( $post->post_content, 'pizza_hut_slice_game' ) || has_shortcode( $post->post_content, 'pizza_slice_leaderboard' ) ) ) {
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
				'i18n'         => array(
					'errName'    => __( 'נא להזין שם מלא', 'pizza-hut-slice-game' ),
					'errPhone'   => __( 'מספר טלפון לא תקין', 'pizza-hut-slice-game' ),
					'errEmail'   => __( 'כתובת אימייל לא תקינה', 'pizza-hut-slice-game' ),
					'errConsent' => __( 'יש לאשר את התקנון כדי להשתתף', 'pizza-hut-slice-game' ),
					'saveError'  => __( 'אירעה שגיאה בשמירה. נסו שוב.', 'pizza-hut-slice-game' ),
					'soundOn'    => __( 'צליל: פועל', 'pizza-hut-slice-game' ),
					'soundOff'   => __( 'צליל: כבוי', 'pizza-hut-slice-game' ),
					'streak'     => __( 'רצף', 'pizza-hut-slice-game' ),
					'sec'        => __( "שנ'", 'pizza-hut-slice-game' ),
					'titleChamp' => __( 'אלוף/ת הפיצה!', 'pizza-hut-slice-game' ),
					'titleGood'  => __( 'כל הכבוד!', 'pizza-hut-slice-game' ),
					'titleMeh'   => __( 'לא רע… עוד סיבוב?', 'pizza-hut-slice-game' ),
					'youSuffix'  => __( '(את/ה!)', 'pizza-hut-slice-game' ),
					'copied'     => __( 'הועתק!', 'pizza-hut-slice-game' ),
					'level'      => __( 'שלב', 'pizza-hut-slice-game' ),
					'levelUpSub' => __( 'מהר יותר… קשה יותר!', 'pizza-hut-slice-game' ),
					'levelBonus' => __( 'בונוס שלב', 'pizza-hut-slice-game' ),
					'levelGoal'  => __( 'תפיסות ב-', 'pizza-hut-slice-game' ),
					'strikes'    => __( 'מכשולים', 'pizza-hut-slice-game' ),
					'titleTime'  => __( 'הזמן נגמר!', 'pizza-hut-slice-game' ),
					'titleStrikes' => __( 'יותר מדי מכשולים…', 'pizza-hut-slice-game' ),
				),
			)
		);
	}

	/**
	 * טעינת נכסים במקרה שהזיהוי המוקדם החמיץ (שורטקוד בווידג'ט וכו').
	 *
	 * @return void
	 */
	private function ensure_assets() {
		if ( ! $this->assets_needed ) {
			$this->assets_needed = true;
			if ( ! wp_style_is( 'phsg-game', 'registered' ) ) {
				$this->maybe_register_assets();
			}
			$this->enqueue_assets();
		}
	}

	/**
	 * רינדור שורטקוד המשחק.
	 *
	 * @param array $atts תכונות השורטקוד.
	 * @return string HTML.
	 */
	public function render( $atts = array() ) {
		$this->ensure_assets();

		$atts = shortcode_atts(
			array(
				'title'       => __( "תפוס ת'משולש", 'pizza-hut-slice-game' ),
				'logo'        => '', // URL ללוגו. ריק = הלוגו הרשמי המצורף לתוסף.
				'fullscreen'  => '1', // 1 = השתלטות על כל העמוד (הסתרת התבנית). 0 = הטמעה רגילה.
				'coupon_code' => 'HUTGAME', // קוד הפינוק במסך הסיום. ריק = הסתרת הכרטיס.
				'coupon_url'  => 'https://www.pizzahut.co.il/?utm_source=slice_game&utm_medium=game&utm_campaign=coupon', // יעד כפתור ההזמנה.
				'bg'          => '', // URL לתמונת רקע אמיתית (צילום המותג ממדיה). ריק = רקע אדום מובנה.
				'slice'       => '', // URL לתמונת משולש PNG שקופה – מחליפה את איור המשולש במשחק.
			),
			$atts,
			'pizza_hut_slice_game'
		);

		ob_start();
		$leaderboard = PHSG_Leaderboard::get_public( 8 );
		include PHSG_PLUGIN_DIR . 'templates/game.php';
		return ob_get_clean();
	}

	/**
	 * רינדור שורטקוד טבלת השיאים העצמאי.
	 *
	 * @param array $atts תכונות השורטקוד.
	 * @return string HTML.
	 */
	public function render_leaderboard( $atts = array() ) {
		$this->ensure_assets();

		$atts = shortcode_atts(
			array( 'limit' => 8 ),
			$atts,
			'pizza_slice_leaderboard'
		);

		$rows = PHSG_Leaderboard::get_public( (int) $atts['limit'] );

		$html  = '<div class="phsg-app phsg-app--board-only" dir="rtl" lang="he">';
		$html .= '<div class="phsg-board">';
		$html .= '<div class="phsg-board__head"><span>' . esc_html__( 'טבלת השיאים', 'pizza-hut-slice-game' ) . '</span>';
		$html .= '<span class="phsg-board__head-sub">' . esc_html__( 'מתעדכן בזמן אמת', 'pizza-hut-slice-game' ) . '</span></div>';
		$html .= phsg_render_leaderboard_rows( $rows );
		$html .= '</div></div>';

		return $html;
	}
}
