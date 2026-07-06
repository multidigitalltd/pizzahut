<?php
/**
 * ולידציית אנטי-רמייה בסיסית לתוצאות המשחק.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Anti_Cheat – בדיקות סבירות על תוצאה שהוגשה.
 */
class PHSG_Anti_Cheat {

	// חוקי המשחק (חייבים להיות תואמים ל-JS).
	const GAME_DURATION      = 60;   // מגבלת הזמן של שלב 1 (שניות).
	const SLICE_TIMEOUT      = 5;    // חסם עליון לזמן שהות המשולש (שניות, שלב 1).
	const MIN_REACTION_MS    = 250;  // זמן תגובה אנושי מינימלי סביר (מ"ש).
	const MIN_DURATION       = 3;    // מינימום: הפסד מהיר ב-5 פסילות.
	const MAX_DURATION       = 245;  // מקסימום: 60+45+35+30+25 + הארכות שעון + סבילות.
	const MAX_POINTS_PER_HIT = 13;   // זהב (3) × פרנזי (2) + רצף (2) + מהירות (1) + שלב (4).
	const FLAT_SCORE_BUFFER  = 60;   // בונוסי השלמת שלבים (3+6+9+12+15) + מרווח.

	/**
	 * בדיקת סבירות התוצאה.
	 *
	 * @param array $payload נתונים מסונָנים: score, clicks, duration, avg_reaction.
	 * @return true|WP_Error true אם תקין, אחרת WP_Error עם הסבר.
	 */
	public static function validate( array $payload ) {
		$score        = isset( $payload['score'] ) ? (int) $payload['score'] : 0;
		$clicks       = isset( $payload['clicks'] ) ? (int) $payload['clicks'] : 0;
		$duration     = isset( $payload['duration'] ) ? (float) $payload['duration'] : 0;
		$avg_reaction = isset( $payload['avg_reaction'] ) ? (float) $payload['avg_reaction'] : 0;

		// 1. ניקוד ולחיצות לא שליליים.
		if ( $score < 0 || $clicks < 0 ) {
			return new WP_Error( 'phsg_invalid_score', __( 'ניקוד לא תקין.', 'pizza-hut-slice-game' ) );
		}

		// 2. משך המשחק משתנה (שלבים לפי תפיסות): בין הפסד מהיר לניצחון מלא עם הארכות.
		if ( $duration < self::MIN_DURATION || $duration > self::MAX_DURATION ) {
			return new WP_Error( 'phsg_invalid_duration', __( 'משך משחק לא תקין.', 'pizza-hut-slice-game' ) );
		}

		// 3. חסם עליון תיאורטי על מספר הלחיצות:
		//    כל לחיצה דורשת לפחות MIN_REACTION_MS. מוסיפים באפר קטן להשהיית רינדור.
		$max_clicks = (int) floor( ( $duration * 1000 ) / self::MIN_REACTION_MS ) + 5;
		if ( $clicks > $max_clicks ) {
			return new WP_Error( 'phsg_score_too_high', __( 'הניקוד גבוה מהאפשרי במשחק.', 'pizza-hut-slice-game' ) );
		}

		// 4. הניקוד חסום על ידי התפיסות: כל תפיסה שווה לכל היותר MAX_POINTS_PER_HIT
		//    (זהב בפרנזי + בונוס רצף), ומכשולים מורידים – לכן אין חסם תחתון מעבר ל-0.
		if ( $score > $clicks * self::MAX_POINTS_PER_HIT + self::FLAT_SCORE_BUFFER ) {
			return new WP_Error( 'phsg_score_too_high', __( 'הניקוד גבוה מהאפשרי במשחק.', 'pizza-hut-slice-game' ) );
		}

		// 5. אם היו לחיצות – זמן התגובה הממוצע חייב להיות אנושי (לא 0 ולא מהיר מדי).
		if ( $clicks > 0 ) {
			if ( $avg_reaction < self::MIN_REACTION_MS ) {
				return new WP_Error( 'phsg_reaction_too_fast', __( 'זמן תגובה מהיר מהאפשרי.', 'pizza-hut-slice-game' ) );
			}
			// זמן תגובה לא יכול לעבור את חלון הזמן המקסימלי של המשולש.
			if ( $avg_reaction > ( self::SLICE_TIMEOUT * 1000 ) ) {
				return new WP_Error( 'phsg_reaction_too_slow', __( 'זמן תגובה לא תקין.', 'pizza-hut-slice-game' ) );
			}
			// בדיקת עקביות רכה: זמן התגובה המצטבר של תפיסות המשולש חסום במשך
			// המשחק, אבל clicks כולל גם תפיסות גבינה (שנתפסות במקביל לחלון
			// המשולש) – לכן מרווח של 40% ומרווח קבוע, כדי לא לפסול משחק כשר.
			$spent_ms = $clicks * $avg_reaction;
			if ( $spent_ms > ( $duration * 1000 ) * 1.4 + 1000 ) {
				return new WP_Error( 'phsg_inconsistent', __( 'הנתונים אינם עקביים.', 'pizza-hut-slice-game' ) );
			}
		} elseif ( $score > 0 || $avg_reaction > 0 ) {
			// אין לחיצות אבל יש ניקוד/זמן תגובה – לא עקבי.
			return new WP_Error( 'phsg_inconsistent', __( 'הנתונים אינם עקביים.', 'pizza-hut-slice-game' ) );
		}

		return true;
	}
}
