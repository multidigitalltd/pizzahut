<?php
/**
 * פונקציות עזר לרינדור – גרפיקת המשחק כ-SVG תלת-ממדי מבריק (סגנון 2026).
 *
 * כל ספרייט בנוי מגרדיאנטים, הברקות ספקולריות ותאורת שפה – בלי קווי מתאר
 * שחורים קשים. הצלליות זהות לגרסאות הקודמות כדי לא לשנות אזורי פגיעה.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'phsg_svg_hero_slice' ) ) {
	/**
	 * משולש הפיצה הגדול של מסך הפתיחה – מבריק, עסיסי, עם אדים.
	 *
	 * @return string
	 */
	function phsg_svg_hero_slice() {
		return '<svg viewBox="0 0 100 110" width="100%" height="100%">
			<defs>
				<linearGradient id="phCrustH" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFDE9C"/><stop offset="0.5" stop-color="#EFA94A"/><stop offset="1" stop-color="#B06B24"/></linearGradient>
				<radialGradient id="phCheeseH" cx="0.5" cy="0.3" r="0.9"><stop offset="0" stop-color="#FFF8CE"/><stop offset="0.55" stop-color="#FFD968"/><stop offset="1" stop-color="#EFA22B"/></radialGradient>
				<radialGradient id="phPepH" cx="0.35" cy="0.3" r="0.9"><stop offset="0" stop-color="#FF9078"/><stop offset="0.55" stop-color="#E8503C"/><stop offset="1" stop-color="#A81F14"/></radialGradient>
			</defs>
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phCrustH)" stroke="#96601F" stroke-width="2" stroke-linejoin="round"/>
			<path d="M9 25 Q50 6 91 25" fill="none" stroke="#FFF0C8" stroke-width="3.5" stroke-linecap="round" opacity="0.75"/>
			<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phCheeseH)" stroke="#D08E2A" stroke-width="1.8" stroke-linejoin="round"/>
			<path d="M24 40 q4 12 8 1 M64 42 q3 11 7 0 M44 44 q3 9 6 0" fill="none" stroke="#FFF3C0" stroke-width="6" stroke-linecap="round" opacity="0.9"/>
			<circle cx="37" cy="52" r="8.5" fill="url(#phPepH)"/>
			<circle cx="62" cy="55" r="8.5" fill="url(#phPepH)"/>
			<circle cx="49" cy="73" r="8" fill="url(#phPepH)"/>
			<circle cx="50" cy="90" r="5" fill="url(#phPepH)"/>
			<circle cx="34" cy="49" r="2.6" fill="#FFC9B8" opacity="0.95"/>
			<circle cx="59" cy="52" r="2.6" fill="#FFC9B8" opacity="0.95"/>
			<circle cx="46.5" cy="70" r="2.3" fill="#FFC9B8" opacity="0.95"/>
			<circle cx="30" cy="63" r="2.8" fill="#FFFBE2" opacity="0.9"/>
			<circle cx="68" cy="66" r="2.3" fill="#FFFBE2" opacity="0.9"/>
			<path d="M20 40 Q35 32 52 31" fill="none" stroke="#FFFBE2" stroke-width="3.5" stroke-linecap="round" opacity="0.8"/>
			<path d="M40 46 Q50 42 62 44" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" opacity="0.55"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_game_slice' ) ) {
	/**
	 * ספרייט המשולש במשחק – רגיל או זהב, מבריק ותלת-ממדי.
	 *
	 * @param bool $gold וריאנט זהב.
	 * @return string
	 */
	function phsg_svg_game_slice( $gold = false ) {
		$sfx        = $gold ? 'G' : 'N';
		$cheese_in  = $gold ? '#FFFBD6' : '#FFF8CE';
		$cheese_mid = $gold ? '#FFE47A' : '#FFD968';
		$cheese_out = $gold ? '#F5B301' : '#EFA22B';
		return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none; overflow:visible;">
			<defs>
				<linearGradient id="phCrust' . $sfx . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFDE9C"/><stop offset="0.5" stop-color="#EFA94A"/><stop offset="1" stop-color="#B06B24"/></linearGradient>
				<radialGradient id="phCheese' . $sfx . '" cx="0.5" cy="0.3" r="0.9"><stop offset="0" stop-color="' . $cheese_in . '"/><stop offset="0.55" stop-color="' . $cheese_mid . '"/><stop offset="1" stop-color="' . $cheese_out . '"/></radialGradient>
				<radialGradient id="phPep' . $sfx . '" cx="0.35" cy="0.3" r="0.9"><stop offset="0" stop-color="#FF9078"/><stop offset="0.55" stop-color="#E8503C"/><stop offset="1" stop-color="#A81F14"/></radialGradient>
			</defs>
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phCrust' . $sfx . ')" stroke="#96601F" stroke-width="2" stroke-linejoin="round"/>
			<path d="M9 25 Q50 6 91 25" fill="none" stroke="#FFF0C8" stroke-width="3.5" stroke-linecap="round" opacity="0.75"/>
			<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phCheese' . $sfx . ')" stroke="#D08E2A" stroke-width="1.8" stroke-linejoin="round"/>
			<path d="M24 40 q4 12 8 1 M64 42 q3 11 7 0" fill="none" stroke="#FFF3C0" stroke-width="6" stroke-linecap="round" opacity="0.9"/>
			<circle cx="37" cy="52" r="8.5" fill="url(#phPep' . $sfx . ')"/>
			<circle cx="62" cy="55" r="8.5" fill="url(#phPep' . $sfx . ')"/>
			<circle cx="49" cy="73" r="8" fill="url(#phPep' . $sfx . ')"/>
			<circle cx="50" cy="90" r="5" fill="url(#phPep' . $sfx . ')"/>
			<circle cx="34" cy="49" r="2.6" fill="#FFC9B8" opacity="0.95"/>
			<circle cx="59" cy="52" r="2.6" fill="#FFC9B8" opacity="0.95"/>
			<circle cx="30" cy="63" r="2.8" fill="#FFFBE2" opacity="0.9"/>
			<path d="M20 40 Q35 32 52 31" fill="none" stroke="#FFFBE2" stroke-width="3.5" stroke-linecap="round" opacity="0.8"/>
			<path d="M40 46 Q50 42 62 44" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" opacity="0.55"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_obstacle' ) ) {
	/**
	 * SVG של מכשול – גרסאות מבריקות תלת-ממדיות.
	 *
	 * @param string $type mush | olive | onion | tomato | burnt.
	 * @return string
	 */
	function phsg_svg_obstacle( $type ) {
		switch ( $type ) {
			case 'mush':
				return '<svg viewBox="0 0 60 62" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phMushCap" cx="0.35" cy="0.25" r="1"><stop offset="0" stop-color="#F2DCBB"/><stop offset="0.55" stop-color="#CDa276"/><stop offset="1" stop-color="#96683C"/></radialGradient>
						<linearGradient id="phMushStem" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFDF2"/><stop offset="1" stop-color="#DBD1B4"/></linearGradient>
					</defs>
					<path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="url(#phMushCap)" stroke="#7C5530" stroke-width="1.6" stroke-linejoin="round"/>
					<path d="M23 37 q-2 15 2 19 q5 3 10 0 q4 -4 2 -19" fill="url(#phMushStem)" stroke="#B8A984" stroke-width="1.4" stroke-linejoin="round"/>
					<ellipse cx="21" cy="17" rx="7" ry="4" fill="#FFFFFF" opacity="0.5" transform="rotate(-18 21 17)"/>
					<circle cx="20" cy="25" r="3.2" fill="#FFF6E3" opacity="0.9"/>
					<circle cx="34" cy="16" r="2.8" fill="#FFF6E3" opacity="0.9"/>
					<circle cx="43" cy="26" r="3.2" fill="#FFF6E3" opacity="0.9"/>
				</svg>';
			case 'olive':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phOlive" cx="0.35" cy="0.28" r="1"><stop offset="0" stop-color="#6E6650"/><stop offset="0.55" stop-color="#403A2E"/><stop offset="1" stop-color="#221E17"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phOlive)"/>
					<ellipse cx="30" cy="30" rx="9" ry="12" fill="#7E7357"/>
					<ellipse cx="30" cy="30" rx="4" ry="6.5" fill="#A89A73"/>
					<ellipse cx="21" cy="19" rx="7" ry="4.5" fill="#FFFFFF" opacity="0.45" transform="rotate(-24 21 19)"/>
				</svg>';
			case 'onion':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phOnion" cx="0.4" cy="0.3" r="1"><stop offset="0" stop-color="#FDF4FD"/><stop offset="0.6" stop-color="#EBD6EC"/><stop offset="1" stop-color="#C9A4C8"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phOnion)"/>
					<circle cx="30" cy="30" r="16.5" fill="none" stroke="#B478B0" stroke-width="3.5" opacity="0.75"/>
					<circle cx="30" cy="30" r="10" fill="none" stroke="#B478B0" stroke-width="3" opacity="0.65"/>
					<circle cx="30" cy="30" r="4" fill="#B478B0" opacity="0.75"/>
					<ellipse cx="21" cy="18" rx="8" ry="4.5" fill="#FFFFFF" opacity="0.7" transform="rotate(-24 21 18)"/>
				</svg>';
			case 'tomato':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phTomato" cx="0.35" cy="0.28" r="1"><stop offset="0" stop-color="#FF8E68"/><stop offset="0.5" stop-color="#F05336"/><stop offset="1" stop-color="#C22B18"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phTomato)"/>
					<circle cx="30" cy="30" r="17" fill="#F99C77" opacity="0.85"/>
					<circle cx="30" cy="30" r="5" fill="#E85D3A"/>
					<ellipse cx="30" cy="17.5" rx="3" ry="5" fill="#FBC9A6" opacity="0.9"/>
					<ellipse cx="30" cy="42.5" rx="3" ry="5" fill="#FBC9A6" opacity="0.9"/>
					<ellipse cx="17.5" cy="30" rx="5" ry="3" fill="#FBC9A6" opacity="0.9"/>
					<ellipse cx="42.5" cy="30" rx="5" ry="3" fill="#FBC9A6" opacity="0.9"/>
					<ellipse cx="21" cy="17" rx="8" ry="4.5" fill="#FFFFFF" opacity="0.6" transform="rotate(-24 21 17)"/>
				</svg>';
			case 'burnt':
				return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<linearGradient id="phBurntCrust" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7E6142"/><stop offset="1" stop-color="#4A3520"/></linearGradient>
						<radialGradient id="phBurntCheese" cx="0.5" cy="0.3" r="0.9"><stop offset="0" stop-color="#9A7A4E"/><stop offset="1" stop-color="#5E4327"/></radialGradient>
					</defs>
					<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phBurntCrust)" stroke="#3A2A16" stroke-width="1.8" stroke-linejoin="round"/>
					<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phBurntCheese)" stroke="#3A2A16" stroke-width="1.6" stroke-linejoin="round"/>
					<circle cx="37" cy="52" r="8" fill="#2F2314"/>
					<circle cx="62" cy="55" r="8" fill="#2F2314"/>
					<circle cx="49" cy="73" r="7.5" fill="#2F2314"/>
					<path d="M30 20 q3 -8 0 -14 M50 16 q3 -8 0 -14 M70 20 q3 -8 0 -14" fill="none" stroke="#A9A29A" stroke-width="3" stroke-linecap="round" opacity="0.75"/>
					<path d="M20 38 Q35 31 50 30" fill="none" stroke="#B79A6C" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
				</svg>';
		}
		return '';
	}
}

if ( ! function_exists( 'phsg_svg_cheese' ) ) {
	/**
	 * נתח גבינה צהובה מבריק – פריט נקודות מהיר (+2).
	 *
	 * @return string
	 */
	function phsg_svg_cheese() {
		return '<svg viewBox="0 0 64 56" width="100%" height="100%" style="pointer-events:none;">
			<defs>
				<linearGradient id="phCheeseC" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFF3A6"/><stop offset="0.55" stop-color="#FFD84E"/><stop offset="1" stop-color="#EFA22B"/></linearGradient>
			</defs>
			<path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="url(#phCheeseC)" stroke="#D08E2A" stroke-width="1.8" stroke-linejoin="round"/>
			<circle cx="24" cy="38" r="5" fill="#FFEFA0"/><path d="M21 36 a5 5 0 0 1 6 -1" fill="none" stroke="#D19A2B" stroke-width="1.6" opacity="0.7"/>
			<circle cx="41" cy="41" r="4" fill="#FFEFA0"/><path d="M38.6 39.4 a4 4 0 0 1 4.8 -0.8" fill="none" stroke="#D19A2B" stroke-width="1.4" opacity="0.7"/>
			<circle cx="33" cy="26" r="3.5" fill="#FFEFA0"/><path d="M30.9 24.6 a3.5 3.5 0 0 1 4.2 -0.7" fill="none" stroke="#D19A2B" stroke-width="1.3" opacity="0.7"/>
			<path d="M30 9 L36 9" stroke="#FFFBE2" stroke-width="3" stroke-linecap="round" opacity="0.9"/>
			<path d="M14 44 Q20 40 26 44" fill="none" stroke="#FFFBE2" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_bonus' ) ) {
	/**
	 * SVG של פריט בונוס מבריק – שעון (+5 שנ') או פלפל (פרנזי ×2).
	 *
	 * @param string $type clock | chili.
	 * @return string
	 */
	function phsg_svg_bonus( $type ) {
		if ( 'clock' === $type ) {
			return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
				<defs>
					<radialGradient id="phClock" cx="0.35" cy="0.28" r="1"><stop offset="0" stop-color="#FFF3C2"/><stop offset="0.6" stop-color="#FFD968"/><stop offset="1" stop-color="#E8A62B"/></radialGradient>
				</defs>
				<circle cx="30" cy="32" r="22" fill="url(#phClock)" stroke="#C98A22" stroke-width="1.8"/>
				<circle cx="30" cy="32" r="16" fill="#FFFEF8" stroke="#E3C173" stroke-width="1.4"/>
				<path d="M30 32 L30 21 M30 32 L38 36" stroke="#F03A2E" stroke-width="3.5" stroke-linecap="round"/>
				<circle cx="30" cy="32" r="2" fill="#F03A2E"/>
				<rect x="25" y="4" width="10" height="6" rx="3" fill="#F03A2E" stroke="#C22B1F" stroke-width="1.4"/>
				<ellipse cx="22" cy="21" rx="7" ry="4" fill="#FFFFFF" opacity="0.65" transform="rotate(-24 22 21)"/>
			</svg>';
		}
		return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
			<defs>
				<radialGradient id="phChili" cx="0.35" cy="0.3" r="1.1"><stop offset="0" stop-color="#FF7A62"/><stop offset="0.5" stop-color="#F03A2E"/><stop offset="1" stop-color="#B01A12"/></radialGradient>
			</defs>
			<path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="url(#phChili)" stroke="#8E1710" stroke-width="1.6" stroke-linejoin="round"/>
			<path d="M46 17 Q50 8 58 8" fill="none" stroke="#4C8A3A" stroke-width="5" stroke-linecap="round"/>
			<path d="M46 17 Q49 11 55 9" fill="none" stroke="#7DBF63" stroke-width="2" stroke-linecap="round" opacity="0.8"/>
			<path d="M20 42 Q16 32 26 24" fill="none" stroke="#FFB0A0" stroke-width="4" stroke-linecap="round" opacity="0.9"/>
			<ellipse cx="34" cy="21" rx="7" ry="3.5" fill="#FFFFFF" opacity="0.5" transform="rotate(-28 34 21)"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_israel_map' ) ) {
	/**
	 * מפת ארץ ישראל מסוגננת – רקע הבמה. ים בהיר, יבשה בגוון חול,
	 * הכנרת וים המלח, נקודות ערים ומסלול מקווקו.
	 *
	 * @return string
	 */
	function phsg_svg_israel_map() {
		return '<svg class="phsg-map-svg" viewBox="6 0 74 262" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<g transform="translate(40 0) scale(1.6 1) translate(-40 0)">
				<path d="M38 6 C34 20 30 40 31 55 C29 70 26 80 25 90 C23 105 24 118 27 128 L22 138 C24 146 28 150 32 152 L47 247 L54 240 C58 200 60 170 55 155 C58 140 58 125 54 115 C57 105 57 95 54 88 C56 70 55 55 52 45 C55 35 56 25 52 18 C50 12 44 8 38 6 Z"
					fill="rgba(240,69,58,0.05)" stroke="#E8443B" stroke-width="1.1" stroke-linejoin="round" opacity="0.8"/>
			</g>
			<g fill="#F0453A">
				<circle cx="25.6" cy="55" r="3.4" opacity="0.22"/><circle cx="25.6" cy="55" r="1.9"/>
				<circle cx="17.6" cy="86" r="3.4" opacity="0.22"/><circle cx="17.6" cy="86" r="1.9"/>
				<circle cx="46.4" cy="102" r="3.4" opacity="0.22"/><circle cx="46.4" cy="102" r="1.9"/>
				<circle cx="32" cy="130" r="3.4" opacity="0.22"/><circle cx="32" cy="130" r="1.9"/>
				<circle cx="51.2" cy="245" r="3.4" opacity="0.22"/><circle cx="51.2" cy="245" r="1.9"/>
			</g>
			<g font-family="Almoni, Rubik, sans-serif" font-size="6" font-weight="500" fill="#E4574C" opacity="0.9" text-anchor="middle">
				<text x="36" y="57">חיפה</text>
				<text x="28" y="88">ת״א</text>
				<text x="57" y="104">י-ם</text>
				<text x="42" y="132">ב״ש</text>
				<text x="51.2" y="256">אילת</text>
			</g>
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
			return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="#F32735"/><path d="M16 9 Q18 12 16 15 M18.5 6.5 Q22 12 18.5 17.5" fill="none" stroke="#2D2A26" stroke-width="2" stroke-linecap="round"/></svg>';
		}
		return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="#B5B2A4"/><path d="M16.5 9.5 L21.5 14.5 M21.5 9.5 L16.5 14.5" fill="none" stroke="#F32735" stroke-width="2.4" stroke-linecap="round"/></svg>';
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
			$row_bg  = ( $i % 2 ) ? '#231C22' : '#1D171C';

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
