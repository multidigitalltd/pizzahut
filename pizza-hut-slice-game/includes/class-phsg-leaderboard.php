<?php
/**
 * עזרי טבלת המובילים והצגתה.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Leaderboard – בניית מבנה נתונים ציבורי לטבלת המובילים.
 */
class PHSG_Leaderboard {

	/**
	 * החזרת טבלת המובילים כמבנה מוכן להצגה/JSON (ללא פרטים אישיים).
	 *
	 * @param int  $limit מספר שורות.
	 * @param bool $daily true = לוח יומי (לפרס היומי).
	 * @return array
	 */
	public static function get_public( $limit = 10, $daily = false ) {
		$rows   = PHSG_DB::get_leaderboard( $limit, $daily );
		$output = array();
		$rank   = 0;

		foreach ( $rows as $row ) {
			$rank++;
			$output[] = array(
				'rank'         => $rank,
				'display_name' => $row->display_name,
				'score'        => (int) $row->score,
				'duration'     => (float) $row->duration,
				'avg_reaction' => (float) $row->avg_reaction,
			);
		}

		return $output;
	}

	/**
	 * יצירת שם תצוגה בטוח משם מלא (בלי לחשוף שם משפחה מלא).
	 * לדוגמה: "ישראל ישראלי" => "ישראל י׳".
	 *
	 * @param string $full_name שם מלא.
	 * @return string
	 */
	public static function make_display_name( $full_name ) {
		$full_name = trim( preg_replace( '/\s+/u', ' ', $full_name ) );

		if ( '' === $full_name ) {
			return __( 'שחקן/ית', 'pizza-hut-slice-game' );
		}

		$parts = explode( ' ', $full_name );
		$first = $parts[0];

		if ( count( $parts ) > 1 ) {
			$last_initial = function_exists( 'mb_substr' )
				? mb_substr( end( $parts ), 0, 1 )
				: substr( end( $parts ), 0, 1 );
			return $first . ' ' . $last_initial . '׳';
		}

		return $first;
	}
}
