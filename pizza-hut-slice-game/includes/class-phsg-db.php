<?php
/**
 * שכבת גישה לבסיס הנתונים.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_DB – ניהול טבלת הניקוד.
 */
class PHSG_DB {

	/**
	 * החזרת שם הטבלה המלא (כולל prefix).
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . PHSG_TABLE_SCORES;
	}

	/**
	 * יצירת טבלת הניקוד באמצעות dbDelta.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name VARCHAR(120) NOT NULL DEFAULT '',
			display_name VARCHAR(80) NOT NULL DEFAULT '',
			phone VARCHAR(40) NOT NULL DEFAULT '',
			email VARCHAR(190) NOT NULL DEFAULT '',
			consent TINYINT(1) NOT NULL DEFAULT 0,
			score INT(10) UNSIGNED NOT NULL DEFAULT 0,
			clicks INT(10) UNSIGNED NOT NULL DEFAULT 0,
			duration DECIMAL(6,2) NOT NULL DEFAULT 0,
			avg_reaction DECIMAL(8,2) NOT NULL DEFAULT 0,
			utm_source VARCHAR(120) NOT NULL DEFAULT '',
			utm_medium VARCHAR(120) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(120) NOT NULL DEFAULT '',
			utm_term VARCHAR(120) NOT NULL DEFAULT '',
			utm_content VARCHAR(120) NOT NULL DEFAULT '',
			ip_hash CHAR(64) NOT NULL DEFAULT '',
			ua_hash CHAR(64) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY ranking_idx (score, avg_reaction, created_at),
			KEY created_at_idx (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * הוספת רשומת ניקוד חדשה.
	 *
	 * @param array $data נתונים מסונָנים.
	 * @return int|false מזהה הרשומה או false בכשל.
	 */
	public static function insert_score( array $data ) {
		global $wpdb;

		$defaults = array(
			'full_name'    => '',
			'display_name' => '',
			'phone'        => '',
			'email'        => '',
			'consent'      => 0,
			'score'        => 0,
			'clicks'       => 0,
			'duration'     => 0,
			'avg_reaction' => 0,
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_campaign' => '',
			'utm_term'     => '',
			'utm_content'  => '',
			'ip_hash'      => '',
			'ua_hash'      => '',
			'created_at'   => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		$formats = array(
			'%s', // full_name.
			'%s', // display_name.
			'%s', // phone.
			'%s', // email.
			'%d', // consent.
			'%d', // score.
			'%d', // clicks.
			'%f', // duration.
			'%f', // avg_reaction.
			'%s', // utm_source.
			'%s', // utm_medium.
			'%s', // utm_campaign.
			'%s', // utm_term.
			'%s', // utm_content.
			'%s', // ip_hash.
			'%s', // ua_hash.
			'%s', // created_at.
		);

		$result = $wpdb->insert( self::table_name(), $data, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * חישוב דירוג של רשומה מסוימת לפי כללי הדירוג.
	 *
	 * דירוג = מספר הרשומות שמדורגות מעליה + 1.
	 * סדר עדיפויות: ניקוד גבוה > זמן תגובה ממוצע קצר > הגשה מוקדמת.
	 *
	 * @param int $row_id מזהה הרשומה.
	 * @return int הדירוג (1 = מקום ראשון), 0 אם לא נמצא.
	 */
	public static function get_rank( $row_id ) {
		global $wpdb;

		$table = self::table_name();

		// שליפת הרשומה הנוכחית.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT score, avg_reaction, created_at FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB
				$row_id
			)
		);

		if ( ! $row ) {
			return 0;
		}

		// ספירת כל הרשומות שמדורגות טוב יותר.
		$better = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE
					score > %d
					OR ( score = %d AND avg_reaction < %f )
					OR ( score = %d AND avg_reaction = %f AND created_at < %s )
					OR ( score = %d AND avg_reaction = %f AND created_at = %s AND id < %d )", // phpcs:ignore WordPress.DB
				$row->score,
				$row->score,
				$row->avg_reaction,
				$row->score,
				$row->avg_reaction,
				$row->created_at,
				$row->score,
				$row->avg_reaction,
				$row->created_at,
				$row_id
			)
		);

		return (int) $better + 1;
	}

	/**
	 * שליפת טבלת המובילים (ללא חשיפת טלפון/אימייל).
	 *
	 * @param int  $limit מספר השורות להחזרה.
	 * @param bool $daily true = תוצאות מהיום בלבד (לפרס היומי).
	 * @return array מערך אובייקטים: display_name, score, duration, avg_reaction.
	 */
	public static function get_leaderboard( $limit = 10, $daily = false ) {
		global $wpdb;

		$table = self::table_name();
		$limit = max( 1, min( 100, (int) $limit ) );

		if ( $daily ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT display_name, score, duration, avg_reaction
					 FROM {$table}
					 WHERE DATE(created_at) = %s
					 ORDER BY score DESC, avg_reaction ASC, created_at ASC, id ASC
					 LIMIT %d", // phpcs:ignore WordPress.DB
					current_time( 'Y-m-d' ),
					$limit
				)
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT display_name, score, duration, avg_reaction
					 FROM {$table}
					 ORDER BY score DESC, avg_reaction ASC, created_at ASC, id ASC
					 LIMIT %d", // phpcs:ignore WordPress.DB
					$limit
				)
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * דירוג רשומה בלוח היומי (תוצאות מאותו יום בלבד).
	 *
	 * @param int $row_id מזהה הרשומה.
	 * @return int הדירוג היומי (1 = שיאן היום), 0 אם לא נמצא.
	 */
	public static function get_daily_rank( $row_id ) {
		global $wpdb;

		$table = self::table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT score, avg_reaction, created_at FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB
				$row_id
			)
		);

		if ( ! $row ) {
			return 0;
		}

		$today  = current_time( 'Y-m-d' );
		$better = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s AND (
					score > %d
					OR ( score = %d AND avg_reaction < %f )
					OR ( score = %d AND avg_reaction = %f AND created_at < %s )
					OR ( score = %d AND avg_reaction = %f AND created_at = %s AND id < %d )
				)", // phpcs:ignore WordPress.DB
				$today,
				$row->score,
				$row->score,
				$row->avg_reaction,
				$row->score,
				$row->avg_reaction,
				$row->created_at,
				$row->score,
				$row->avg_reaction,
				$row->created_at,
				$row_id
			)
		);

		return (int) $better + 1;
	}

	/**
	 * סך כל המשתתפים (לשימוש בסטטיסטיקות ובחישוב אחוזון).
	 *
	 * @return int
	 */
	public static function total_players() {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB
	}
}
