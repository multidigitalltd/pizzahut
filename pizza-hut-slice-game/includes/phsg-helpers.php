<?php
/**
 * פונקציות עזר לרינדור – כל גרפיקת המשחק כ-SVG inline (מתוך אב-הטיפוס המאושר).
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'phsg_svg_hero_slice' ) ) {
	/**
	 * משולש הפיצה הגדול של מסך הפתיחה (210×225).
	 *
	 * @return string
	 */
	function phsg_svg_hero_slice() {
		return '<svg viewBox="0 0 100 110" width="100%" height="100%">
			<defs>
				<linearGradient id="phsgCrustA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F2B24E"/><stop offset="1" stop-color="#BE7526"/></linearGradient>
				<linearGradient id="phsgCheeseA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFE49A"/><stop offset="1" stop-color="#EFAC2F"/></linearGradient>
			</defs>
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phsgCrustA)" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phsgCheeseA)" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<path d="M24 40 q4 12 8 1 M64 42 q3 11 7 0 M44 44 q3 9 6 0" fill="none" stroke="#FFE49A" stroke-width="6" stroke-linecap="round"/>
			<circle cx="37" cy="52" r="8.5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="62" cy="55" r="8.5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="49" cy="73" r="8" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="50" cy="90" r="5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="34.5" cy="49" r="2.4" fill="#F7A69E"/>
			<circle cx="59.5" cy="52" r="2.4" fill="#F7A69E"/>
			<circle cx="46.5" cy="70" r="2.2" fill="#F7A69E"/>
			<circle cx="30" cy="63" r="2.6" fill="#FFF3C9" opacity=".9"/>
			<circle cx="68" cy="66" r="2.2" fill="#FFF3C9" opacity=".9"/>
			<path d="M20 38 Q35 31 50 30" fill="none" stroke="#FFF3C9" stroke-width="3.5" stroke-linecap="round" opacity=".85"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_game_slice' ) ) {
	/**
	 * ספרייט המשולש במשחק – רגיל או זהב (גבינה בהירה יותר).
	 *
	 * @param bool $gold וריאנט זהב.
	 * @return string
	 */
	function phsg_svg_game_slice( $gold = false ) {
		$id_suffix  = $gold ? 'G' : 'N';
		$cheese_top = $gold ? '#FFF3B0' : '#FFE49A';
		$cheese_bot = $gold ? '#FFC93C' : '#EFAC2F';
		return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none; overflow:visible;">
			<defs>
				<linearGradient id="phsgCrustB' . $id_suffix . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F2B24E"/><stop offset="1" stop-color="#BE7526"/></linearGradient>
				<linearGradient id="phsgCheeseB' . $id_suffix . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $cheese_top . '"/><stop offset="1" stop-color="' . $cheese_bot . '"/></linearGradient>
			</defs>
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phsgCrustB' . $id_suffix . ')" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phsgCheeseB' . $id_suffix . ')" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<path d="M24 40 q4 12 8 1 M64 42 q3 11 7 0" fill="none" stroke="' . $cheese_top . '" stroke-width="6" stroke-linecap="round"/>
			<circle cx="37" cy="52" r="8.5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="62" cy="55" r="8.5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="49" cy="73" r="8" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="50" cy="90" r="5" fill="#E0453A" stroke="#9E2B22" stroke-width="2.5"/>
			<circle cx="34.5" cy="49" r="2.4" fill="#F7A69E"/>
			<circle cx="59.5" cy="52" r="2.4" fill="#F7A69E"/>
			<circle cx="30" cy="63" r="2.6" fill="#FFF3C9" opacity=".9"/>
			<path d="M20 38 Q35 31 50 30" fill="none" stroke="#FFF3C9" stroke-width="3.5" stroke-linecap="round" opacity=".85"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_obstacle' ) ) {
	/**
	 * SVG של מכשול לפי סוג – פטרייה, זית, בצל, עגבנייה, משולש שרוף.
	 *
	 * @param string $type mush | olive | onion | tomato | burnt.
	 * @return string
	 */
	function phsg_svg_obstacle( $type ) {
		switch ( $type ) {
			case 'mush':
				return '<svg viewBox="0 0 60 62" width="100%" height="100%" style="pointer-events:none;">
					<path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="#C9A874" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
					<path d="M23 37 q-2 15 2 19 q5 3 10 0 q4 -4 2 -19" fill="#F0EFDD" stroke="#2D2A26" stroke-width="3" stroke-linejoin="round"/>
					<circle cx="20" cy="24" r="3.5" fill="#F0EFDD" opacity=".85"/>
					<circle cx="34" cy="17" r="3" fill="#F0EFDD" opacity=".85"/>
					<circle cx="43" cy="26" r="3.5" fill="#F0EFDD" opacity=".85"/>
				</svg>';
			case 'olive':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<circle cx="30" cy="30" r="23" fill="#3B3830" stroke="#2D2A26" stroke-width="3"/>
					<ellipse cx="30" cy="30" rx="9" ry="12" fill="#6E6754"/>
					<ellipse cx="30" cy="30" rx="4" ry="6.5" fill="#8A8168"/>
					<circle cx="21" cy="20" r="5" fill="#57534A"/>
				</svg>';
			case 'onion':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<circle cx="30" cy="30" r="23" fill="#EFE0F0" stroke="#2D2A26" stroke-width="3"/>
					<circle cx="30" cy="30" r="16.5" fill="none" stroke="#B0729E" stroke-width="4" opacity=".8"/>
					<circle cx="30" cy="30" r="10" fill="none" stroke="#B0729E" stroke-width="3.5" opacity=".7"/>
					<circle cx="30" cy="30" r="4" fill="#B0729E" opacity=".8"/>
					<path d="M14 18 Q22 12 30 11" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" opacity=".8"/>
				</svg>';
			case 'tomato':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<circle cx="30" cy="30" r="23" fill="#E85D3A" stroke="#2D2A26" stroke-width="3"/>
					<circle cx="30" cy="30" r="17" fill="#F49B75"/>
					<circle cx="30" cy="30" r="5" fill="#E85D3A"/>
					<ellipse cx="30" cy="17.5" rx="3" ry="5" fill="#F7C59B"/>
					<ellipse cx="30" cy="42.5" rx="3" ry="5" fill="#F7C59B"/>
					<ellipse cx="17.5" cy="30" rx="5" ry="3" fill="#F7C59B"/>
					<ellipse cx="42.5" cy="30" rx="5" ry="3" fill="#F7C59B"/>
					<path d="M15 17 Q22 11 30 10" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" opacity=".6"/>
				</svg>';
			case 'burnt':
				return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none;">
					<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#6B5138" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
					<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#8A6B45" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
					<circle cx="37" cy="52" r="8" fill="#4A3B2A" stroke="#2D2A26" stroke-width="2.5"/>
					<circle cx="62" cy="55" r="8" fill="#4A3B2A" stroke="#2D2A26" stroke-width="2.5"/>
					<circle cx="49" cy="73" r="7.5" fill="#4A3B2A" stroke="#2D2A26" stroke-width="2.5"/>
					<path d="M30 20 q3 -8 0 -14 M50 16 q3 -8 0 -14 M70 20 q3 -8 0 -14" fill="none" stroke="#57534A" stroke-width="3" stroke-linecap="round" opacity=".7"/>
				</svg>';
		}
		return '';
	}
}

if ( ! function_exists( 'phsg_svg_cheese' ) ) {
	/**
	 * נתח גבינה צהובה – פריט נקודות מהיר (+2).
	 *
	 * @return string
	 */
	function phsg_svg_cheese() {
		return '<svg viewBox="0 0 64 56" width="100%" height="100%" style="pointer-events:none;">
			<defs>
				<linearGradient id="phsgCheeseC" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFE566"/><stop offset="1" stop-color="#F2B33C"/></linearGradient>
			</defs>
			<path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="url(#phsgCheeseC)" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<circle cx="24" cy="38" r="5" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2"/>
			<circle cx="41" cy="41" r="4" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2"/>
			<circle cx="33" cy="26" r="3.5" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2"/>
			<path d="M14 44 Q20 40 26 44" fill="none" stroke="#FFF3B0" stroke-width="2.5" stroke-linecap="round" opacity=".8"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_bonus' ) ) {
	/**
	 * SVG של פריט בונוס – שעון (+5 שנ') או פלפל (פרנזי ×2).
	 *
	 * @param string $type clock | chili.
	 * @return string
	 */
	function phsg_svg_bonus( $type ) {
		if ( 'clock' === $type ) {
			return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
				<circle cx="30" cy="32" r="22" fill="#FFE49A" stroke="#2D2A26" stroke-width="3.5"/>
				<circle cx="30" cy="32" r="16" fill="#FFFEF6" stroke="#2D2A26" stroke-width="2"/>
				<path d="M30 32 L30 21 M30 32 L38 36" stroke="#F32735" stroke-width="3.5" stroke-linecap="round"/>
				<rect x="25" y="4" width="10" height="6" rx="2" fill="#F32735" stroke="#2D2A26" stroke-width="2.5"/>
			</svg>';
		}
		return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
			<path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="#F32735" stroke="#2D2A26" stroke-width="3.5" stroke-linejoin="round"/>
			<path d="M46 17 Q50 8 58 8" fill="none" stroke="#4C7B3A" stroke-width="5" stroke-linecap="round"/>
			<path d="M20 42 Q16 32 26 24" fill="none" stroke="#FF8A8F" stroke-width="4" stroke-linecap="round" opacity=".8"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_sound' ) ) {
	/**
	 * אייקון רמקול – פועל / כבוי.
	 *
	 * @param bool $on מצב הצליל.
	 * @return string
	 */
	function phsg_svg_sound( $on = true ) {
		if ( $on ) {
			return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="#F32735" stroke="#2D2A26" stroke-width="1.8" stroke-linejoin="round"/><path d="M16 9 Q18 12 16 15 M18.5 6.5 Q22 12 18.5 17.5" fill="none" stroke="#2D2A26" stroke-width="2" stroke-linecap="round"/></svg>';
		}
		return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="#B5B2A4" stroke="#2D2A26" stroke-width="1.8" stroke-linejoin="round"/><path d="M16.5 9.5 L21.5 14.5 M21.5 9.5 L16.5 14.5" fill="none" stroke="#F32735" stroke-width="2.4" stroke-linecap="round"/></svg>';
	}
}

if ( ! function_exists( 'phsg_render_leaderboard_rows' ) ) {
	/**
	 * רינדור שורות טבלת השיאים בצד השרת (לשורטקוד הלוח העצמאי).
	 *
	 * @param array $rows שורות (rank, display_name, score, avg_reaction).
	 * @return string HTML בטוח.
	 */
	function phsg_render_leaderboard_rows( $rows ) {
		if ( empty( $rows ) ) {
			return '<div class="phsg-board__row"><span class="phsg-board__empty">' . esc_html__( 'עדיין אין תוצאות – היו הראשונים!', 'pizza-hut-slice-game' ) . '</span></div>';
		}

		$medals = array( '#FFC93C', '#D6D4C8', '#D19A6A' );
		$html   = '';
		$i      = 0;

		foreach ( $rows as $row ) {
			$rank    = isset( $row['rank'] ) ? (int) $row['rank'] : $i + 1;
			$name    = isset( $row['display_name'] ) ? $row['display_name'] : '';
			$score   = isset( $row['score'] ) ? (int) $row['score'] : 0;
			$avg     = isset( $row['avg_reaction'] ) ? (float) $row['avg_reaction'] : 0;
			$rank_bg = $rank <= 3 ? $medals[ $rank - 1 ] : '#FBFAEE';
			$row_bg  = ( $i % 2 ) ? '#E9E7D2' : '#F0EFDD';

			$html .= '<div class="phsg-board__row" style="background:' . esc_attr( $row_bg ) . ';">';
			$html .= '<span class="phsg-board__rank" style="background:' . esc_attr( $rank_bg ) . ';">' . esc_html( $rank ) . '</span>';
			$html .= '<span class="phsg-board__name">' . esc_html( $name ) . '</span>';
			$html .= '<span class="phsg-board__score">' . esc_html( $score ) . '</span>';
			$html .= '<span class="phsg-board__avg">' . esc_html( number_format_i18n( $avg / 1000, 1 ) ) . ' ' . esc_html__( "שנ'", 'pizza-hut-slice-game' ) . '</span>';
			$html .= '</div>';
			$i++;
		}

		return $html;
	}
}
