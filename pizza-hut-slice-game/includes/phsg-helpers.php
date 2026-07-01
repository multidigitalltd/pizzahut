<?php
/**
 * פונקציות עזר לרינדור (נטענות פעם אחת).
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'phsg_slice_svg' ) ) {
	/**
	 * SVG של משולש פיצה – פשוט ושובב, בצבעי המותג.
	 *
	 * @param string $variant normal | gold | trap.
	 * @return string
	 */
	function phsg_slice_svg( $variant = 'normal' ) {
		if ( 'gold' === $variant ) {
			// משולש זהב נדיר – שווה 3 נקודות.
			return '<svg class="phsg-slice-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
				<path d="M50 6 L92 88 Q50 100 8 88 Z" fill="#FFE9A8" stroke="#2D2A26" stroke-width="4" stroke-linejoin="round"/>
				<path d="M50 6 L84 72 Q50 82 16 72 Z" fill="#F5B301" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
				<circle cx="42" cy="40" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
				<circle cx="62" cy="46" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
				<circle cx="50" cy="62" r="5.5" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
				<path d="M76 14 l2.6 6 6 2.6 -6 2.6 -2.6 6 -2.6 -6 -6 -2.6 6 -2.6 Z" fill="#FFFFFF" stroke="#2D2A26" stroke-width="1.6"/>
				<path d="M20 22 l1.8 4.2 4.2 1.8 -4.2 1.8 -1.8 4.2 -1.8 -4.2 -4.2 -1.8 4.2 -1.8 Z" fill="#FFFFFF" stroke="#2D2A26" stroke-width="1.4"/>
			</svg>';
		}

		if ( 'trap' === $variant ) {
			// משולש "גלוטן-פרי" מלכודת – ירוק המותג (מותר בהקשר גלוטן), מוריד נקודה.
			return '<svg class="phsg-slice-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
				<path d="M50 6 L92 88 Q50 100 8 88 Z" fill="#F0EFDD" stroke="#2D2A26" stroke-width="4" stroke-linejoin="round"/>
				<path d="M50 6 L84 72 Q50 82 16 72 Z" fill="#6BA43A" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
				<circle cx="42" cy="40" r="6" fill="#4C7A28" stroke="#2D2A26" stroke-width="2.5"/>
				<circle cx="62" cy="46" r="6" fill="#4C7A28" stroke="#2D2A26" stroke-width="2.5"/>
				<circle cx="50" cy="62" r="5.5" fill="#4C7A28" stroke="#2D2A26" stroke-width="2.5"/>
				<path d="M42 26 l16 8 M58 26 l-16 8" stroke="#2D2A26" stroke-width="3" stroke-linecap="round"/>
			</svg>';
		}

		return '<svg class="phsg-slice-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
			<path d="M50 6 L92 88 Q50 100 8 88 Z" fill="#F0EFDD" stroke="#2D2A26" stroke-width="4" stroke-linejoin="round"/>
			<path d="M50 6 L84 72 Q50 82 16 72 Z" fill="#F32735" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
			<circle cx="42" cy="40" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
			<circle cx="62" cy="46" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
			<circle cx="50" cy="62" r="5.5" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_logo_svg' ) ) {
	/**
	 * לוגו ברירת מחדל בהשראת לוגו המותג: סלוגן, אייקון כובע/גג ו-"Pizza Hut".
	 *
	 * ניתן להחליף בלוגו המקורי דרך פרמטר השורטקוד logo="URL".
	 *
	 * @return string
	 */
	function phsg_logo_svg() {
		return '<svg class="phsg-logo-svg" viewBox="0 0 300 110" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Pizza Hut">
			<text x="150" y="18" text-anchor="middle" font-family="Rubik, Assistant, sans-serif" font-size="12" font-weight="800" letter-spacing="3" fill="#F32735">I\'M HUT AND I KNOW IT</text>
			<ellipse cx="150" cy="40" rx="24" ry="13" fill="#F32735"/>
			<path d="M132 42 Q141 30 150 33 Q159 30 168 42 Q159 38 150 39 Q141 38 132 42 Z" fill="#F0EFDD"/>
			<text x="150" y="92" text-anchor="middle" font-family="\'Brush Script MT\', \'Segoe Script\', \'Comic Sans MS\', cursive" font-size="46" font-style="italic" font-weight="700" fill="#F32735">Pizza Hut</text>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_render_leaderboard_rows' ) ) {
	/**
	 * רינדור שורות טבלת מובילים (משמש לרינדור ראשוני בצד השרת).
	 *
	 * @param array $rows שורות הטבלה (כל אחת: rank, display_name, score, duration).
	 * @return string HTML בטוח.
	 */
	function phsg_render_leaderboard_rows( $rows ) {
		if ( empty( $rows ) ) {
			return '<p class="phsg-board__empty">' . esc_html__( 'עדיין אין תוצאות – היו הראשונים!', 'pizza-hut-slice-game' ) . '</p>';
		}

		$html  = '<div class="phsg-board__head">';
		$html .= '<span>' . esc_html__( 'דירוג', 'pizza-hut-slice-game' ) . '</span>';
		$html .= '<span>' . esc_html__( 'שחקן/ית', 'pizza-hut-slice-game' ) . '</span>';
		$html .= '<span>' . esc_html__( 'ניקוד', 'pizza-hut-slice-game' ) . '</span>';
		$html .= '<span>' . esc_html__( 'זמן', 'pizza-hut-slice-game' ) . '</span>';
		$html .= '</div>';

		foreach ( $rows as $row ) {
			$rank     = isset( $row['rank'] ) ? (int) $row['rank'] : 0;
			$name     = isset( $row['display_name'] ) ? $row['display_name'] : '';
			$score    = isset( $row['score'] ) ? (int) $row['score'] : 0;
			$duration = isset( $row['duration'] ) ? (float) $row['duration'] : 0;

			$medal = '';
			if ( 1 === $rank ) {
				$medal = ' phsg-board__row--gold';
			} elseif ( 2 === $rank ) {
				$medal = ' phsg-board__row--silver';
			} elseif ( 3 === $rank ) {
				$medal = ' phsg-board__row--bronze';
			}

			$html .= '<div class="phsg-board__row' . esc_attr( $medal ) . '">';
			$html .= '<span class="phsg-board__rank">' . esc_html( $rank ) . '</span>';
			$html .= '<span class="phsg-board__name">' . esc_html( $name ) . '</span>';
			$html .= '<span class="phsg-board__score">' . esc_html( $score ) . '</span>';
			$html .= '<span class="phsg-board__time">' . esc_html( number_format_i18n( $duration, 0 ) ) . '"</span>';
			$html .= '</div>';
		}

		return $html;
	}
}
