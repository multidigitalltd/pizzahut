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
	const GAME_DURATION      = 60;   // משך המשחק בשניות.
	const SLICE_TIMEOUT      = 10;   // זמן שהות מקסימלי של המשולש (שניות).
	const MIN_REACTION_MS    = 120;  // זמן תגובה אנושי מינימלי סביר (מ"ש).
	const DURATION_TOLERANCE = 3;    // סטייה מותרת ממשך המשחק (שניות).

	/**
	 * בדיקת סבירות התוצאה.
	 *
	 * @param array $payload נתונים מסונָנים: score, duration, avg_reaction.
	 * @return true|WP_Error true אם תקין, אחרת WP_Error עם הסבר.
	 */
	public static function validate( array $payload ) {
		$score        = isset( $payload['score'] ) ? (int) $payload['score'] : 0;
		$duration     = isset( $payload['duration'] ) ? (float) $payload['duration'] : 0;
		$avg_reaction = isset( $payload['avg_reaction'] ) ? (float) $payload['avg_reaction'] : 0;

		// 1. ניקוד לא שלילי ובגבול הגיוני.
		if ( $score < 0 ) {
			return new WP_Error( 'phsg_invalid_score', __( 'ניקוד לא תקין.', 'pizza-hut-slice-game' ) );
		}

		// 2. משך המשחק חייב להיות קרוב ל-60 שניות.
		$min_duration = self::GAME_DURATION - self::DURATION_TOLERANCE;
		$max_duration = self::GAME_DURATION + self::DURATION_TOLERANCE;
		if ( $duration < $min_duration || $duration > $max_duration ) {
			return new WP_Error( 'phsg_invalid_duration', __( 'משך משחק לא תקין.', 'pizza-hut-slice-game' ) );
		}

		// 3. חסם עליון תיאורטי על הניקוד:
		//    כל לחיצה דורשת לפחות MIN_REACTION_MS. מספר הלחיצות המרבי במשך המשחק
		//    לא יכול לעבור duration / MIN_REACTION_MS. מוסיפים באפר קטן להשהיית רינדור.
		$max_possible = (int) floor( ( self::GAME_DURATION * 1000 ) / self::MIN_REACTION_MS ) + 5;
		if ( $score > $max_possible ) {
			return new WP_Error( 'phsg_score_too_high', __( 'הניקוד גבוה מהאפשרי במשחק.', 'pizza-hut-slice-game' ) );
		}

		// 4. אם יש ניקוד – זמן התגובה הממוצע חייב להיות אנושי (לא 0 ולא מהיר מדי).
		if ( $score > 0 ) {
			if ( $avg_reaction < self::MIN_REACTION_MS ) {
				return new WP_Error( 'phsg_reaction_too_fast', __( 'זמן תגובה מהיר מהאפשרי.', 'pizza-hut-slice-game' ) );
			}
			// זמן תגובה לא יכול לעבור את חלון הזמן של המשולש.
			if ( $avg_reaction > ( self::SLICE_TIMEOUT * 1000 ) ) {
				return new WP_Error( 'phsg_reaction_too_slow', __( 'זמן תגובה לא תקין.', 'pizza-hut-slice-game' ) );
			}
			// בדיקת עקביות: score * avg_reaction לא יכול לעבור את משך המשחק בפועל.
			$spent_ms = $score * $avg_reaction;
			if ( $spent_ms > ( $duration * 1000 ) + 500 ) {
				return new WP_Error( 'phsg_inconsistent', __( 'הנתונים אינם עקביים.', 'pizza-hut-slice-game' ) );
			}
		} elseif ( $avg_reaction > 0 ) {
			// אין ניקוד אבל יש זמן תגובה – לא עקבי.
			return new WP_Error( 'phsg_inconsistent', __( 'הנתונים אינם עקביים.', 'pizza-hut-slice-game' ) );
		}

		return true;
	}
}
