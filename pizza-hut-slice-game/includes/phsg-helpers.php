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
	 * @return string
	 */
	function phsg_slice_svg() {
		return '<svg class="phsg-slice-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
			<path d="M50 6 L92 88 Q50 100 8 88 Z" fill="#F0EFDD" stroke="#2D2A26" stroke-width="4" stroke-linejoin="round"/>
			<path d="M50 6 L84 72 Q50 82 16 72 Z" fill="#F32735" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
			<circle cx="42" cy="40" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
			<circle cx="62" cy="46" r="6" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
			<circle cx="50" cy="62" r="5.5" fill="#B58967" stroke="#2D2A26" stroke-width="2.5"/>
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
