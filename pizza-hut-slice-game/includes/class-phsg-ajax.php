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

		// הנפקת טוקן משחק חד-פעמי (אנטי-רמייה).
		add_action( 'wp_ajax_phsg_start_game', array( $this, 'start_game' ) );
		add_action( 'wp_ajax_nopriv_phsg_start_game', array( $this, 'start_game' ) );

		// רענון nonce – פותר nonce שפג בגלל קאש עמודים (דף נחיתה מקושש).
		add_action( 'wp_ajax_phsg_refresh_nonce', array( $this, 'refresh_nonce' ) );
		add_action( 'wp_ajax_nopriv_phsg_refresh_nonce', array( $this, 'refresh_nonce' ) );

		// הרשמה לרשימת התפוצה מיד עם שליחת הטופס (לפני תחילת המשחק).
		add_action( 'wp_ajax_phsg_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_nopriv_phsg_subscribe', array( $this, 'subscribe' ) );
	}

	/**
	 * הרשמה לרשימת התפוצה (InforU) מיד עם שליחת טופס ההשתתפות.
	 *
	 * ההסכמה לדיוור ניתנת ברגע אישור התקנון, ולכן ההרשמה מתבצעת כאן ולא
	 * מחכה לסיום המשחק. מחזיר תמיד הצלחה – זהו ערוץ צדדי שלא אמור לחסום
	 * את תחילת המשחק בשום מצב.
	 *
	 * @return void
	 */
	public function subscribe() {
		$this->verify_nonce();

		$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? $this->sanitize_phone( wp_unslash( $_POST['phone'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$consent   = isset( $_POST['consent'] ) && in_array( wp_unslash( $_POST['consent'] ), array( '1', 'true', 'on', 'yes' ), true ) ? 1 : 0;

		// בלי הסכמה מפורשת – לא נרשמים לדיוור.
		if ( ! $consent ) {
			wp_send_json_success( array( 'subscribed' => false ) );
		}

		$ip_hash = $this->hash_value( $this->get_client_ip() );
		if ( ! $this->check_rate_limit( 'subscribe_' . $ip_hash, 60 ) ) {
			wp_send_json_success( array( 'subscribed' => false ) );
		}

		$this->push_to_inforu( $full_name, $phone, $email );

		wp_send_json_success( array( 'subscribed' => true ) );
	}

	/**
	 * הנפקת nonce טרי.
	 *
	 * דפי נחיתה מוגשים לרוב מקאש, ולכן ה-nonce שמוטמע ב-HTML עלול להיות בן
	 * יותר מ-24 שעות ופג תוקף ("בקשה לא מאומתת"). ה-endpoint הזה עוקף את
	 * הקאש (admin-ajax אף פעם לא מקושש) ומחזיר nonce עדכני. אין בכך חשיפה:
	 * ה-nonce ממילא מוטמע בעמוד ציבורי לכל גולש אנונימי, וההגנות האמיתיות הן
	 * הטוקן החד-פעמי, הגבלת הקצב והאנטי-רמייה.
	 *
	 * @return void
	 */
	public function refresh_nonce() {
		nocache_headers();
		wp_send_json_success( array( 'nonce' => wp_create_nonce( self::NONCE_ACTION ) ) );
	}

	/**
	 * הנפקת טוקן חד-פעמי בתחילת משחק. נדרש בהגשת התוצאה.
	 *
	 * @return void
	 */
	public function start_game() {
		$this->verify_nonce();

		$ip_hash = $this->hash_value( $this->get_client_ip() );

		// הגבלת קצב הנפקה: עד 60 טוקנים לשעה לכל IP.
		if ( ! $this->check_rate_limit( 'start_' . $ip_hash, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'יותר מדי ניסיונות. נסו שוב מאוחר יותר.', 'pizza-hut-slice-game' ) ), 429 );
		}

		$token = wp_generate_password( 32, false, false );
		// טוקן חד-פעמי, תקף לשעתיים (מכסה גם משחקים ארוכים בשלבים אינסופיים).
		// לא נקשר ל-IP: במובייל ה-IP מתחלף באמצע המשחק (WiFi/סלולר/NAT) והיה
		// גורם ל"המשחק לא אומת" לשחקנים כשרים. ההגנה נשמרת: הטוקן מונפק רק מול
		// nonce תקין, חד-פעמי, מוגבל בזמן, ובנוסף יש הגבלת קצב ובדיקות אנטי-רמייה.
		set_transient( 'phsg_tok_' . $token, '1', 2 * HOUR_IN_SECONDS );

		wp_send_json_success( array( 'token' => $token ) );
	}

	/**
	 * מונה קצב פשוט מבוסס transient.
	 *
	 * @param string $key   מפתח ייחודי (פעולה + IP).
	 * @param int    $limit מקסימום פעולות בחלון של שעה.
	 * @return bool האם מותר להמשיך.
	 */
	private function check_rate_limit( $key, $limit ) {
		$tkey  = 'phsg_rl_' . md5( $key );
		$count = (int) get_transient( $tkey );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $tkey, $count + 1, HOUR_IN_SECONDS );
		return true;
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
		if ( '' === $phone || ! preg_match( '/^(?:0\d{8,9}|\+\d{7,15})$/', $phone ) ) {
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

		// --- טוקן חד-פעמי + הגבלת קצב ---.
		$ip_hash = $this->hash_value( $this->get_client_ip() );
		$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( '' === $token || false === get_transient( 'phsg_tok_' . $token ) ) {
			wp_send_json_error( array( 'message' => __( 'המשחק לא אומת. רעננו את העמוד ונסו שוב.', 'pizza-hut-slice-game' ) ), 403 );
		}
		delete_transient( 'phsg_tok_' . $token ); // חד-פעמי.

		if ( ! $this->check_rate_limit( 'submit_' . $ip_hash, 30 ) ) {
			wp_send_json_error( array( 'message' => __( 'יותר מדי הגשות. נסו שוב מאוחר יותר.', 'pizza-hut-slice-game' ) ), 429 );
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

		// --- Hash של User Agent (לא שומרים ערכים גולמיים) ---.
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

		// רשת ביטחון: ההרשמה לדיוור מתבצעת כבר בשליחת הטופס (phsg_subscribe).
		// שולחים כאן שוב רק אם ההרשמה המוקדמת לא הצליחה (כשל רשת רגעי).
		// InforU מבצע Create-or-Update לפי אימייל/טלפון – ולכן אין כפילויות.
		$already_subscribed = isset( $_POST['subscribed'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['subscribed'] ) );
		if ( ! $already_subscribed && $consent ) {
			$this->push_to_inforu( $full_name, $phone, $email );
		}

		$rank  = PHSG_DB::get_rank( $row_id );
		$total = PHSG_DB::total_players();

		wp_send_json_success(
			array(
				'rank'         => $rank,
				'total'        => $total,
				'display_name' => $display_name,
				'score'        => $score,
				'duration'     => round( $duration, 2 ),
				'leaderboard'  => PHSG_Leaderboard::get_public( 8 ),
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
	 * סניטציה ונרמול של מספר טלפון.
	 *
	 * שומר ספרות ו-+ מוביל; קידומת ישראלית (+972/972) מומרת למספר מקומי.
	 *
	 * @param string $phone קלט גולמי.
	 * @return string
	 */
	private function sanitize_phone( $phone ) {
		$phone = preg_replace( '/[^0-9+]/', '', (string) $phone );
		// + מותר רק בתחילת המספר.
		$phone = preg_replace( '/(?!^)\+/', '', $phone );

		if ( preg_match( '/^\+972\d{8,9}$/', $phone ) ) {
			return '0' . substr( $phone, 4 );
		}
		if ( preg_match( '/^972\d{8,9}$/', $phone ) ) {
			return '0' . substr( $phone, 3 );
		}

		return $phone;
	}

	/**
	 * Hash חד-כיווני עם מלח מבוסס AUTH_SALT של האתר.
	 *
	 * @param string $value ערך לגיבוב.
	 * @return string
	 */
	/**
	 * הוספת המשתתף לרשימת התפוצה ב-InforU (Create or Update Contact).
	 *
	 * שליחה לא חוסמת (blocking=false): גם אם ה-API איטי או נופל, השחקן ממשיך
	 * כרגיל. פועל רק אם הוגדרו שם משתמש וטוקן בהגדרות.
	 *
	 * הגדרות (wp_options): phsg_inforu_user, phsg_inforu_token, phsg_inforu_group.
	 * אפשר גם דרך wp-config.php: PHSG_INFORU_USER / PHSG_INFORU_TOKEN / PHSG_INFORU_GROUP.
	 *
	 * @param string $full_name שם מלא.
	 * @param string $phone     טלפון.
	 * @param string $email     אימייל.
	 * @return void
	 */
	public function push_to_inforu( $full_name, $phone, $email ) {
		// trim – רווחים נסתרים בהדבקה הם גורם נפוץ לכשל אימות.
		$user  = trim( defined( 'PHSG_INFORU_USER' ) ? PHSG_INFORU_USER : get_option( 'phsg_inforu_user', '' ) );
		$token = trim( defined( 'PHSG_INFORU_TOKEN' ) ? PHSG_INFORU_TOKEN : get_option( 'phsg_inforu_token', '' ) );
		$group = trim( defined( 'PHSG_INFORU_GROUP' ) ? PHSG_INFORU_GROUP : get_option( 'phsg_inforu_group', '' ) );

		// ללא פרטי התחברות – מדלגים בשקט (התוסף עובד רגיל).
		if ( '' === $user || '' === $token ) {
			return;
		}

		// חייב אימייל או טלפון (דרישת ה-API).
		if ( '' === $email && '' === $phone ) {
			return;
		}

		// פיצול השם המלא לשם פרטי ושם משפחה.
		$clean = trim( preg_replace( '/\s+/u', ' ', (string) $full_name ) );
		$parts = '' === $clean ? array() : explode( ' ', $clean );
		$first = isset( $parts[0] ) ? $parts[0] : '';
		$last  = count( $parts ) > 1 ? implode( ' ', array_slice( $parts, 1 ) ) : '';

		$contact = array(
			'FirstName'   => $first,
			'LastName'    => $last,
			'PhoneNumber' => $phone,
			'Email'       => $email,
		);

		if ( '' !== $group ) {
			// אם הקבוצה לא קיימת – InforU יוצר אותה אוטומטית לפי השם.
			$contact['AddToGroupName'] = $group;
		}

		$body = wp_json_encode( array( 'Data' => array( 'List' => array( $contact ) ) ) );

		// שליחה חוסמת (8 שנ') – כדי שנוכל לקרוא את התשובה ולתעד תקלות.
		// אינה מעכבת את השחקן: המשחק מתחיל בצד הדפדפן בלי להמתין לתשובה.
		$res = wp_remote_post(
			'https://capi.inforu.co.il/api/v2/Contact/CreateOrUpdateContacts',
			array(
				'timeout' => 8,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $token ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				),
				'body'    => $body,
			)
		);

		$log = array(
			'time'  => current_time( 'mysql' ),
			'email' => $email,
			'group' => $group,
		);

		if ( is_wp_error( $res ) ) {
			$log['ok']      = false;
			$log['message'] = $res->get_error_message();
			update_option( 'phsg_inforu_last', $log, false );
			return $log;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$json = json_decode( $raw, true );

		$status_id = isset( $json['StatusId'] ) ? (int) $json['StatusId'] : 0;
		$log['ok']        = ( 200 === $code && 1 === $status_id );
		$log['http']      = $code;
		$log['status_id'] = $status_id;
		$log['message']   = isset( $json['StatusDescription'] ) ? (string) $json['StatusDescription'] : substr( (string) $raw, 0, 300 );

		if ( isset( $json['Data'] ) && is_array( $json['Data'] ) ) {
			$log['new']      = isset( $json['Data']['ContactsNew'] ) ? (int) $json['Data']['ContactsNew'] : null;
			$log['existing'] = isset( $json['Data']['ContactsExisting'] ) ? (int) $json['Data']['ContactsExisting'] : null;
			$log['failed']   = isset( $json['Data']['RowsFailed'] ) ? (int) $json['Data']['RowsFailed'] : null;
			if ( ! empty( $json['Data']['Errors'] ) ) {
				$log['errors'] = wp_json_encode( $json['Data']['Errors'] );
			}
		}

		// פירוש קודי השגיאה של InforU להסבר מעשי בעברית.
		if ( ! $log['ok'] ) {
			if ( -2 === $status_id ) {
				$log['hint'] = __( 'אימות נכשל או כתובת IP לא מורשית. שתי אפשרויות: (1) שם המשתמש/הטוקן שגויים; (2) כתובת ה-IP של השרת אינה ברשימת ההיתר ב-InforU — יש לפנות לתמיכת InforU ולבקש להתיר את ה-IP המוצג למטה.', 'pizza-hut-slice-game' );
			} elseif ( -1 === $status_id ) {
				$log['hint'] = __( 'כותרת ההרשאה לא התקבלה או לא פוענחה. בדקו שאין רווחים או תווים חריגים בשם המשתמש/טוקן.', 'pizza-hut-slice-game' );
			}
		}

		update_option( 'phsg_inforu_last', $log, false );

		return $log;
	}

	/**
	 * כתובת ה-IP היוצאת של השרת (כפי ש-InforU רואה אותה) – לצורך רשימת היתר.
	 *
	 * @return string
	 */
	public static function outbound_ip() {
		$cached = get_transient( 'phsg_outbound_ip' );
		if ( $cached ) {
			return $cached;
		}

		$res = wp_remote_get( 'https://api.ipify.org', array( 'timeout' => 6 ) );
		if ( is_wp_error( $res ) ) {
			return '';
		}

		$ip = trim( wp_remote_retrieve_body( $res ) );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		set_transient( 'phsg_outbound_ip', $ip, DAY_IN_SECONDS );
		return $ip;
	}

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
