<?php
/**
 * מטפל בבקשות AJAX של המשחק.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Ajax – טיפול בהגשת ניקוד ובשליפת טבלת מובילים.
 */
class PHSG_Ajax {

	const NONCE_ACTION = 'phsg_game_nonce';

	/**
	 * רישום ה-hooks.
	 *
	 * @return void
	 */
	public function register() {
		// הגשת ניקוד – למחוברים ולאורחים.
		add_action( 'wp_ajax_phsg_submit_score', array( $this, 'submit_score' ) );
		add_action( 'wp_ajax_nopriv_phsg_submit_score', array( $this, 'submit_score' ) );

		// שליפת טבלת מובילים.
		add_action( 'wp_ajax_phsg_get_leaderboard', array( $this, 'get_leaderboard' ) );
		add_action( 'wp_ajax_nopriv_phsg_get_leaderboard', array( $this, 'get_leaderboard' ) );
	}

	/**
	 * אימות ה-nonce לכל בקשה.
	 *
	 * @return void נעצר עם שגיאה אם ה-nonce אינו תקין.
	 */
	private function verify_nonce() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				array( 'message' => __( 'בקשה לא מאומתת. רעננו את העמוד ונסו שוב.', 'pizza-hut-slice-game' ) ),
				403
			);
		}
	}

	/**
	 * טיפול בהגשת ניקוד.
	 *
	 * @return void
	 */
	public function submit_score() {
		$this->verify_nonce();

		// --- סניטציה של קלט ---.
		$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? $this->sanitize_phone( wp_unslash( $_POST['phone'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$consent   = isset( $_POST['consent'] ) && in_array( wp_unslash( $_POST['consent'] ), array( '1', 'true', 'on', 'yes' ), true ) ? 1 : 0;

		$score        = isset( $_POST['score'] ) ? absint( wp_unslash( $_POST['score'] ) ) : 0;
		$clicks       = isset( $_POST['clicks'] ) ? absint( wp_unslash( $_POST['clicks'] ) ) : 0;
		$duration     = isset( $_POST['duration'] ) ? (float) wp_unslash( $_POST['duration'] ) : 0;
		$avg_reaction = isset( $_POST['avg_reaction'] ) ? (float) wp_unslash( $_POST['avg_reaction'] ) : 0;

		$utm_source   = isset( $_POST['utm_source'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_source'] ) ) : '';
		$utm_medium   = isset( $_POST['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_medium'] ) ) : '';
		$utm_campaign = isset( $_POST['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_campaign'] ) ) : '';
		$utm_term     = isset( $_POST['utm_term'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_term'] ) ) : '';
		$utm_content  = isset( $_POST['utm_content'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_content'] ) ) : '';

		// --- ולידציה של שדות חובה ---.
		$errors = array();

		if ( '' === $full_name || ( function_exists( 'mb_strlen' ) ? mb_strlen( $full_name ) : strlen( $full_name ) ) < 2 ) {
			$errors[] = __( 'יש להזין שם מלא.', 'pizza-hut-slice-game' );
		}
		if ( '' === $phone || ! preg_match( '/^[0-9]{9,15}$/', $phone ) ) {
			$errors[] = __( 'יש להזין מספר טלפון תקין.', 'pizza-hut-slice-game' );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			$errors[] = __( 'יש להזין כתובת אימייל תקינה.', 'pizza-hut-slice-game' );
		}
		if ( ! $consent ) {
			$errors[] = __( 'יש לאשר את תנאי ההשתתפות.', 'pizza-hut-slice-game' );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				array(
					'message' => implode( ' ', $errors ),
					'fields'  => $errors,
				),
				422
			);
		}

		// --- אנטי-רמייה ---.
		$check = PHSG_Anti_Cheat::validate(
			array(
				'score'        => $score,
				'clicks'       => $clicks,
				'duration'     => $duration,
				'avg_reaction' => $avg_reaction,
			)
		);

		if ( is_wp_error( $check ) ) {
			wp_send_json_error( array( 'message' => $check->get_error_message() ), 422 );
		}

		// --- Hash של IP ו-User Agent (לא שומרים ערכים גולמיים) ---.
		$ip_hash = $this->hash_value( $this->get_client_ip() );
		$ua_hash = $this->hash_value(
			isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''
		);

		// --- שמירה ---.
		$display_name = PHSG_Leaderboard::make_display_name( $full_name );

		$row_id = PHSG_DB::insert_score(
			array(
				'full_name'    => $full_name,
				'display_name' => $display_name,
				'phone'        => $phone,
				'email'        => $email,
				'consent'      => $consent,
				'score'        => $score,
				'clicks'       => $clicks,
				'duration'     => round( $duration, 2 ),
				'avg_reaction' => round( $avg_reaction, 2 ),
				'utm_source'   => $utm_source,
				'utm_medium'   => $utm_medium,
				'utm_campaign' => $utm_campaign,
				'utm_term'     => $utm_term,
				'utm_content'  => $utm_content,
				'ip_hash'      => $ip_hash,
				'ua_hash'      => $ua_hash,
				'created_at'   => current_time( 'mysql' ),
			)
		);

		if ( ! $row_id ) {
			wp_send_json_error( array( 'message' => __( 'שמירת התוצאה נכשלה. נסו שוב.', 'pizza-hut-slice-game' ) ), 500 );
		}

		$rank       = PHSG_DB::get_rank( $row_id );
		$daily_rank = PHSG_DB::get_daily_rank( $row_id );
		$total      = PHSG_DB::total_players();

		wp_send_json_success(
			array(
				'rank'              => $rank,
				'daily_rank'        => $daily_rank,
				'total'             => $total,
				'display_name'      => $display_name,
				'score'             => $score,
				'duration'          => round( $duration, 2 ),
				'leaderboard'       => PHSG_Leaderboard::get_public( 10 ),
				'daily_leaderboard' => PHSG_Leaderboard::get_public( 10, true ),
			)
		);
	}

	/**
	 * שליפת טבלת מובילים (ציבורי, ללא פרטים אישיים).
	 *
	 * @return void
	 */
	public function get_leaderboard() {
		$this->verify_nonce();

		$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 10;

		wp_send_json_success(
			array(
				'leaderboard' => PHSG_Leaderboard::get_public( $limit ),
				'total'       => PHSG_DB::total_players(),
			)
		);
	}

	/**
	 * סניטציה של מספר טלפון – שמירת ספרות בלבד.
	 *
	 * @param string $phone קלט גולמי.
	 * @return string
	 */
	private function sanitize_phone( $phone ) {
		return preg_replace( '/[^0-9]/', '', (string) $phone );
	}

	/**
	 * Hash חד-כיווני עם מלח מבוסס AUTH_SALT של האתר.
	 *
	 * @param string $value ערך לגיבוב.
	 * @return string
	 */
	private function hash_value( $value ) {
		if ( '' === $value ) {
			return '';
		}
		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'phsg_static_salt';
		return hash( 'sha256', $salt . '|' . $value );
	}

	/**
	 * קבלת כתובת ה-IP של הלקוח (בזהירות מול פרוקסי).
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
