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
		return phsg_slice_svg( 'H', false, true );
	}
}

if ( ! function_exists( 'phsg_slice_svg' ) ) {
	/**
	 * בונה משולש פיצה תלת-ממדי: קראסט אפוי עם שלפוחיות, גבינה מבעבעת
	 * (תאורת-בליטות feDiffuseLighting), טפטופי גבינה ופפרוני עסיסי.
	 *
	 * @param string $sfx  סיומת ייחודית ל-IDs.
	 * @param bool   $gold וריאנט זהב.
	 * @param bool   $hero גרסת הירו (פרט נוסף + אדים).
	 * @return string
	 */
	function phsg_slice_svg( $sfx, $gold = false, $hero = false ) {
		$ch_in  = $gold ? '#FFF6CC' : '#FBF4DF';
		$ch_mid = $gold ? '#FFE18E' : '#F2E2BE';
		$ch_out = $gold ? '#EFB63C' : '#DDBB84';
		$steam  = $hero ? '<path d="M38 -2 q-4 -7 1 -13 q4 -5 1 -11" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" opacity="0.3"/><path d="M60 0 q4 -7 -1 -13 q-4 -5 -1 -11" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" opacity="0.24"/>' : '';
		return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none; overflow:visible;">
			<defs>
				<linearGradient id="phCr' . $sfx . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F7C979"/><stop offset="0.45" stop-color="#E09A3E"/><stop offset="1" stop-color="#A6621C"/></linearGradient>
				<radialGradient id="phCh' . $sfx . '" cx="0.5" cy="0.22" r="1"><stop offset="0" stop-color="' . $ch_in . '"/><stop offset="0.55" stop-color="' . $ch_mid . '"/><stop offset="1" stop-color="' . $ch_out . '"/></radialGradient>
				<filter id="phChT' . $sfx . '" x="-15%" y="-15%" width="130%" height="130%"><feTurbulence type="fractalNoise" baseFrequency="0.06 0.08" numOctaves="2" seed="8" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="2.2" diffuseConstant="1.05" result="l"><feDistantLight azimuth="235" elevation="62"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
				<filter id="phCrT' . $sfx . '" x="-15%" y="-15%" width="130%" height="130%"><feTurbulence type="fractalNoise" baseFrequency="0.14 0.18" numOctaves="2" seed="4" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="1.9" diffuseConstant="1.05" result="l"><feDistantLight azimuth="235" elevation="60"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
			</defs>
			' . $steam . '
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phCr' . $sfx . ')" filter="url(#phCrT' . $sfx . ')"/>
			<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="none" stroke="#8F5A1C" stroke-width="1.5" opacity="0.5" stroke-linejoin="round"/>
			<path d="M10 24.5 Q50 5 90 24.5" fill="none" stroke="#FFE9BE" stroke-width="3" stroke-linecap="round" opacity="0.8"/>
			<ellipse cx="26" cy="26" rx="2.4" ry="1.6" fill="#7E4A14" opacity="0.5"/><ellipse cx="25.5" cy="25.4" rx="0.9" ry="0.6" fill="#FFE9B8" opacity="0.9"/>
			<ellipse cx="58" cy="20" rx="2" ry="1.4" fill="#7E4A14" opacity="0.45"/>
			<ellipse cx="76" cy="27" rx="2.2" ry="1.5" fill="#7E4A14" opacity="0.5"/><ellipse cx="75.5" cy="26.4" rx="0.8" ry="0.55" fill="#FFE9B8" opacity="0.85"/>
			<path d="M12.5 35.5 Q50 18 87.5 35.5" fill="none" stroke="#C4571E" stroke-width="3" opacity="0.45"/>
			<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phCh' . $sfx . ')" filter="url(#phChT' . $sfx . ')"/>
			<ellipse cx="30" cy="46" rx="5" ry="3.4" fill="#C98937" opacity="0.5"/>
			<ellipse cx="66" cy="50" rx="4.4" ry="3" fill="#B7742A" opacity="0.45"/>
			<ellipse cx="46" cy="63" rx="4" ry="2.8" fill="#C98937" opacity="0.4"/>
			<ellipse cx="52" cy="86" rx="3.2" ry="2.2" fill="#B7742A" opacity="0.4"/>
			<ellipse cx="38" cy="76" rx="3" ry="2" fill="#C98937" opacity="0.35"/>
			<g>
				<circle cx="33" cy="46" r="5" fill="#1D1710"/><circle cx="33" cy="46" r="2" fill="' . $ch_mid . '"/><path d="M29.6 43.4 a5 5 0 0 1 4 -1.8" stroke="#5E5240" stroke-width="1.1" fill="none" opacity="0.9"/>
				<circle cx="63" cy="43" r="4.6" fill="#1D1710"/><circle cx="63" cy="43" r="1.8" fill="' . $ch_mid . '"/><path d="M59.9 40.6 a4.6 4.6 0 0 1 3.7 -1.6" stroke="#5E5240" stroke-width="1" fill="none" opacity="0.9"/>
				<circle cx="42" cy="70" r="4.6" fill="#1D1710"/><circle cx="42" cy="70" r="1.8" fill="' . $ch_mid . '"/>
				<circle cx="57" cy="78" r="4.2" fill="#1D1710"/><circle cx="57" cy="78" r="1.6" fill="' . $ch_mid . '"/>
				<circle cx="50" cy="94" r="3.6" fill="#1D1710"/><circle cx="50" cy="94" r="1.4" fill="' . $ch_mid . '"/>
			</g>
			<g fill="none" stroke-linecap="round">
				<path d="M22 52 Q30 58 26 68" stroke="#E8B62E" stroke-width="5"/><path d="M22.5 53 Q29 58 26 66" stroke="#FFDF7E" stroke-width="1.8" opacity="0.9"/>
				<path d="M70 56 Q64 64 68 72" stroke="#E8B62E" stroke-width="4.6"/><path d="M69.5 57 Q64.5 64 67.5 70.5" stroke="#FFDF7E" stroke-width="1.6" opacity="0.9"/>
				<path d="M44 38 Q52 40 56 36" stroke="#E8B62E" stroke-width="4.4"/><path d="M45 38.5 Q52 40 55 37" stroke="#FFDF7E" stroke-width="1.5" opacity="0.9"/>
			</g>
			<g>
				<rect x="47" y="50" width="7" height="7" rx="1.4" fill="#D8321E" transform="rotate(14 50.5 53.5)"/><path d="M47.8 51.2 L53 50.4" stroke="#F27A5A" stroke-width="1.6" stroke-linecap="round" transform="rotate(14 50.5 53.5)"/>
				<rect x="31" y="82" width="6" height="6" rx="1.2" fill="#C42A18" transform="rotate(-18 34 85)"/><path d="M31.8 83 L36 82.4" stroke="#F27A5A" stroke-width="1.4" stroke-linecap="round" transform="rotate(-18 34 85)"/>
				<rect x="60" y="62" width="6" height="6" rx="1.2" fill="#D8321E" transform="rotate(28 63 65)"/>
			</g>
			<g fill="none" stroke-linecap="round">
				<path d="M28 60 Q36 64 42 60" stroke="#A86CA6" stroke-width="3"/><path d="M29 60.5 Q36 63.5 41 60.4" stroke="#E3C4E2" stroke-width="1.2" opacity="0.9"/>
				<path d="M52 68 Q58 74 54 82" stroke="#A86CA6" stroke-width="2.8"/><path d="M52.8 69 Q57.4 74 54.6 80.5" stroke="#E3C4E2" stroke-width="1.1" opacity="0.9"/>
				<path d="M56 46 Q62 50 68 48" stroke="#A86CA6" stroke-width="2.6"/>
			</g>
			<path d="M18 42 Q50 30 82 42" fill="none" stroke="#FFFFFF" stroke-width="3.6" stroke-linecap="round" opacity="0.28"/>
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
		return phsg_slice_svg( $gold ? 'G' : 'N', $gold, false );
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
						<radialGradient id="phMushCap" cx="0.35" cy="0.22" r="1"><stop offset="0" stop-color="#F5E2C4"/><stop offset="0.55" stop-color="#CDA276"/><stop offset="1" stop-color="#8E5F33"/></radialGradient>
						<linearGradient id="phMushStem" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFDF2"/><stop offset="1" stop-color="#D3C7A6"/></linearGradient>
						<filter id="phMushT" x="-15%" y="-15%" width="130%" height="130%"><feTurbulence type="fractalNoise" baseFrequency="0.14 0.18" numOctaves="2" seed="5" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="1.6" diffuseConstant="1.05" result="l"><feDistantLight azimuth="235" elevation="62"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
					</defs>
					<path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="url(#phMushCap)" filter="url(#phMushT)"/>
					<path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="none" stroke="#6E4A28" stroke-width="1.4" opacity="0.5" stroke-linejoin="round"/>
					<path d="M10 31.5 Q30 37 50 31.5" fill="none" stroke="#8A6238" stroke-width="2.2" opacity="0.55"/>
					<path d="M15 33.5 L15.5 36.4 M22 35.4 L22.3 38 M30 36 L30 38.6 M38 35.4 L37.7 38 M45 33.5 L44.5 36.4" stroke="#B99568" stroke-width="1.4" opacity="0.7" stroke-linecap="round"/>
					<path d="M23 37 q-2 15 2 19 q5 3 10 0 q4 -4 2 -19" fill="url(#phMushStem)"/>
					<path d="M25 40 q-1.4 11 1.2 15" fill="none" stroke="#B9AD8C" stroke-width="1.6" opacity="0.6" stroke-linecap="round"/>
					<ellipse cx="20" cy="15" rx="8" ry="4.5" fill="#FFFFFF" opacity="0.55" transform="rotate(-18 20 15)"/>
					<circle cx="20" cy="25" r="3" fill="#FFF6E3" opacity="0.85"/>
					<circle cx="35" cy="15" r="2.6" fill="#FFF6E3" opacity="0.8"/>
					<circle cx="43" cy="25" r="3" fill="#FFF6E3" opacity="0.85"/>
				</svg>';
			case 'olive':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phOlive" cx="0.34" cy="0.26" r="1.05"><stop offset="0" stop-color="#7E7458"/><stop offset="0.5" stop-color="#443E30"/><stop offset="1" stop-color="#1C1913"/></radialGradient>
						<radialGradient id="phOliveIn" cx="0.4" cy="0.35" r="1"><stop offset="0" stop-color="#C4523E"/><stop offset="1" stop-color="#8E2E1E"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phOlive)"/>
					<ellipse cx="30" cy="30" rx="9.5" ry="12.5" fill="#161310"/>
					<ellipse cx="30" cy="30.5" rx="6.5" ry="9" fill="url(#phOliveIn)"/>
					<ellipse cx="28.4" cy="27" rx="2" ry="2.8" fill="#E88A70" opacity="0.8"/>
					<path d="M22.5 24 a9.5 12.5 0 0 1 6 -6" fill="none" stroke="#0E0C09" stroke-width="2" opacity="0.7"/>
					<ellipse cx="20" cy="17.5" rx="8" ry="4.5" fill="#FFFFFF" opacity="0.5" transform="rotate(-24 20 17.5)"/>
					<path d="M14 41 a23 23 0 0 0 25 6" fill="none" stroke="#8E8264" stroke-width="2.4" opacity="0.4" stroke-linecap="round"/>
				</svg>';
			case 'onion':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phOnion" cx="0.38" cy="0.28" r="1"><stop offset="0" stop-color="#FEF8FE"/><stop offset="0.6" stop-color="#EED9EF"/><stop offset="1" stop-color="#BE93BD"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phOnion)"/>
					<circle cx="30" cy="30" r="19.5" fill="none" stroke="#C9A0C8" stroke-width="1.6" opacity="0.6"/>
					<circle cx="30" cy="30" r="16" fill="none" stroke="#B478B0" stroke-width="3.2" opacity="0.7"/>
					<circle cx="30" cy="30" r="12.5" fill="none" stroke="#C9A0C8" stroke-width="1.4" opacity="0.55"/>
					<circle cx="30" cy="30" r="9.5" fill="none" stroke="#B478B0" stroke-width="2.8" opacity="0.6"/>
					<circle cx="30" cy="30" r="4" fill="#B478B0" opacity="0.7"/>
					<circle cx="30" cy="30" r="1.8" fill="#9A5C96" opacity="0.8"/>
					<ellipse cx="20.5" cy="17.5" rx="9" ry="5" fill="#FFFFFF" opacity="0.75" transform="rotate(-24 20.5 17.5)"/>
					<path d="M15 42 a23 23 0 0 0 22 7" fill="none" stroke="#FFFFFF" stroke-width="2.2" opacity="0.5" stroke-linecap="round"/>
				</svg>';
			case 'tomato':
				return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<radialGradient id="phTomato" cx="0.35" cy="0.26" r="1.05"><stop offset="0" stop-color="#FF9670"/><stop offset="0.5" stop-color="#EF4E30"/><stop offset="1" stop-color="#B22412"/></radialGradient>
						<radialGradient id="phTomatoIn" cx="0.42" cy="0.36" r="1"><stop offset="0" stop-color="#FFB68E"/><stop offset="1" stop-color="#F07048"/></radialGradient>
					</defs>
					<circle cx="30" cy="30" r="23" fill="url(#phTomato)"/>
					<circle cx="30" cy="30" r="17.5" fill="url(#phTomatoIn)"/>
					<path d="M30 14.5 L30 45.5 M14.5 30 L45.5 30 M19 19 L41 41 M41 19 L19 41" stroke="#E85D3A" stroke-width="2.2" opacity="0.6"/>
					<circle cx="30" cy="30" r="4.6" fill="#F2B790"/>
					<ellipse cx="26" cy="21.5" rx="1.4" ry="2.4" fill="#F7D34E" transform="rotate(24 26 21.5)"/>
					<ellipse cx="35.5" cy="22.5" rx="1.4" ry="2.4" fill="#F7D34E" transform="rotate(-28 35.5 22.5)"/>
					<ellipse cx="38.5" cy="35.5" rx="1.4" ry="2.4" fill="#F7D34E" transform="rotate(58 38.5 35.5)"/>
					<ellipse cx="24" cy="37.5" rx="1.4" ry="2.4" fill="#F7D34E" transform="rotate(-58 24 37.5)"/>
					<ellipse cx="20.5" cy="17" rx="8.5" ry="4.5" fill="#FFFFFF" opacity="0.6" transform="rotate(-24 20.5 17)"/>
				</svg>';
			case 'burnt':
				return '<svg viewBox="0 0 100 110" width="100%" height="100%" style="pointer-events:none;">
					<defs>
						<linearGradient id="phBurntCrust" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6E5236"/><stop offset="1" stop-color="#382613"/></linearGradient>
						<radialGradient id="phBurntCheese" cx="0.5" cy="0.28" r="0.95"><stop offset="0" stop-color="#8A6A40"/><stop offset="0.6" stop-color="#5E4327"/><stop offset="1" stop-color="#382614"/></radialGradient>
						<filter id="phBurntT" x="-15%" y="-15%" width="130%" height="130%"><feTurbulence type="fractalNoise" baseFrequency="0.09 0.12" numOctaves="3" seed="11" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="2.4" diffuseConstant="0.9" result="l"><feDistantLight azimuth="235" elevation="55"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
					</defs>
					<path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="url(#phBurntCrust)" filter="url(#phBurntT)"/>
					<path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="url(#phBurntCheese)" filter="url(#phBurntT)"/>
					<circle cx="37" cy="52" r="8" fill="#1F1609"/><path d="M31.8 47.5 a8 8 0 0 1 6 -3" stroke="#4A3A22" stroke-width="1.6" fill="none" opacity="0.8"/>
					<circle cx="62" cy="55" r="8" fill="#1F1609"/><path d="M56.8 50.5 a8 8 0 0 1 6 -3" stroke="#4A3A22" stroke-width="1.6" fill="none" opacity="0.8"/>
					<circle cx="49" cy="73" r="7.5" fill="#1F1609"/>
					<circle cx="27" cy="45" r="1.3" fill="#FF6A3C" opacity="0.7"/>
					<circle cx="70" cy="47" r="1.1" fill="#FF8A4C" opacity="0.6"/>
					<circle cx="52" cy="86" r="1.2" fill="#FF6A3C" opacity="0.65"/>
					<path d="M30 20 q3 -8 0 -14 M50 16 q3 -8 0 -14 M70 20 q3 -8 0 -14" fill="none" stroke="#9C948A" stroke-width="3" stroke-linecap="round" opacity="0.7"/>
					<path d="M20 38 Q35 31 50 30" fill="none" stroke="#A98B60" stroke-width="2.4" stroke-linecap="round" opacity="0.45"/>
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
				<linearGradient id="phCheeseC" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFF5AE"/><stop offset="0.55" stop-color="#FFD84E"/><stop offset="1" stop-color="#E8961F"/></linearGradient>
				<filter id="phCheeseCT" x="-15%" y="-15%" width="130%" height="130%"><feTurbulence type="fractalNoise" baseFrequency="0.07 0.09" numOctaves="2" seed="6" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="1.8" diffuseConstant="1.05" result="l"><feDistantLight azimuth="235" elevation="64"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
			</defs>
			<path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="url(#phCheeseC)" filter="url(#phCheeseCT)"/>
			<path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="none" stroke="#C9821E" stroke-width="1.5" opacity="0.55" stroke-linejoin="round"/>
			<circle cx="24" cy="38" r="5" fill="#F2B637"/><path d="M20.4 35.4 a5 5 0 0 1 5.4 -2.2" fill="none" stroke="#B26F14" stroke-width="1.6" opacity="0.7"/><ellipse cx="26" cy="40.4" rx="2.6" ry="1.4" fill="#FFEFA0" opacity="0.85"/>
			<circle cx="41" cy="41" r="4" fill="#F2B637"/><path d="M38.2 38.9 a4 4 0 0 1 4.4 -1.8" fill="none" stroke="#B26F14" stroke-width="1.4" opacity="0.7"/><ellipse cx="42.6" cy="42.6" rx="2" ry="1.1" fill="#FFEFA0" opacity="0.85"/>
			<circle cx="33" cy="26" r="3.5" fill="#F2B637"/><path d="M30.6 24.2 a3.5 3.5 0 0 1 3.8 -1.5" fill="none" stroke="#B26F14" stroke-width="1.2" opacity="0.7"/>
			<path d="M29 10 L35.5 10" stroke="#FFFBE2" stroke-width="3" stroke-linecap="round" opacity="0.9"/>
			<path d="M13 44.5 Q20 40.5 27 44.5" fill="none" stroke="#FFFBE2" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/>
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
					<radialGradient id="phClock" cx="0.35" cy="0.26" r="1"><stop offset="0" stop-color="#FFF6CC"/><stop offset="0.55" stop-color="#FFD35A"/><stop offset="1" stop-color="#D89218"/></radialGradient>
					<radialGradient id="phClockFace" cx="0.42" cy="0.34" r="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#F2E7C9"/></radialGradient>
				</defs>
				<circle cx="30" cy="32" r="22" fill="url(#phClock)"/>
				<path d="M12.5 41 a22 22 0 0 0 30 9" fill="none" stroke="#B27412" stroke-width="2.6" opacity="0.5" stroke-linecap="round"/>
				<circle cx="30" cy="32" r="16" fill="url(#phClockFace)"/>
				<path d="M17.8 24.5 a16 16 0 0 1 10 -7.5" fill="none" stroke="#C9B274" stroke-width="1.6" opacity="0.7"/>
				<path d="M30 18.5 L30 21 M43.5 32 L41 32 M30 45.5 L30 43 M16.5 32 L19 32" stroke="#B79A4E" stroke-width="2" stroke-linecap="round"/>
				<path d="M30 32 L30 21.5 M30 32 L38 36" stroke="#F03A2E" stroke-width="3.5" stroke-linecap="round"/>
				<circle cx="30" cy="32" r="2.2" fill="#F03A2E"/><circle cx="29.3" cy="31.3" r="0.8" fill="#FF9A90"/>
				<rect x="25" y="4" width="10" height="6" rx="3" fill="#F03A2E"/>
				<path d="M26.5 6 a4 3 0 0 1 4 -1" stroke="#FF9A90" stroke-width="1.4" fill="none" stroke-linecap="round"/>
				<ellipse cx="21.5" cy="20" rx="7.5" ry="4" fill="#FFFFFF" opacity="0.7" transform="rotate(-24 21.5 20)"/>
			</svg>';
		}
		return '<svg viewBox="0 0 60 60" width="100%" height="100%" style="pointer-events:none;">
			<defs>
				<radialGradient id="phChili" cx="0.32" cy="0.26" r="1.15"><stop offset="0" stop-color="#FF8A6E"/><stop offset="0.45" stop-color="#F03A2E"/><stop offset="1" stop-color="#9C130C"/></radialGradient>
				<linearGradient id="phChiliStem" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#3E7A2E"/><stop offset="1" stop-color="#7DBF63"/></linearGradient>
			</defs>
			<path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="url(#phChili)"/>
			<path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="none" stroke="#7E100A" stroke-width="1.4" opacity="0.5" stroke-linejoin="round"/>
			<path d="M17 44 Q13.5 31 25 22.5" fill="none" stroke="#FFB0A0" stroke-width="4" stroke-linecap="round" opacity="0.9"/>
			<path d="M22 40 Q19.5 33 26 27" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" opacity="0.6"/>
			<path d="M38 39 Q46 31 47.5 22" fill="none" stroke="#B01A12" stroke-width="3" stroke-linecap="round" opacity="0.6"/>
			<path d="M46 17 Q50 8 58 8" fill="none" stroke="url(#phChiliStem)" stroke-width="5" stroke-linecap="round"/>
			<path d="M46 17 Q49 11 55 9" fill="none" stroke="#A8D98A" stroke-width="1.8" stroke-linecap="round" opacity="0.85"/>
			<ellipse cx="34" cy="20" rx="7" ry="3.2" fill="#FFFFFF" opacity="0.55" transform="rotate(-28 34 20)"/>
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_bg_pizza' ) ) {
	/**
	 * פיצה עגולה חתוכה למשולשים – רקע דקורטיבי בפינת העמוד (בהשראת תמונת המותג).
	 *
	 * @return string
	 */
	function phsg_svg_bg_pizza() {
		$cuts    = '';
		$strands = '';
		foreach ( array( 8, 68, 128, 188, 248, 308 ) as $deg ) {
			$cuts .= '<path d="M200 200 L200 12" stroke="rgba(70,18,6,0.82)" stroke-width="5.5" transform="rotate(' . $deg . ' 200 200)"/>';
		}
		foreach ( array( 68, 188, 308 ) as $deg ) {
			$strands .= '<g transform="rotate(' . $deg . ' 200 200)"><path d="M195 90 q5 6 10 0" fill="none" stroke="#F7E3A0" stroke-width="2" opacity="0.9"/><path d="M194 130 q6 7 12 0" fill="none" stroke="#F2D488" stroke-width="1.6" opacity="0.85"/><path d="M196 62 q4 5 8 0" fill="none" stroke="#F7E3A0" stroke-width="1.6" opacity="0.8"/></g>';
		}
		return '<svg viewBox="0 0 400 400" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<defs>
				<radialGradient id="phBgCrust" cx="0.5" cy="0.5" r="0.5"><stop offset="0.82" stop-color="#D89A44"/><stop offset="0.92" stop-color="#C07E2C"/><stop offset="1" stop-color="#8E5514"/></radialGradient>
				<radialGradient id="phBgCheese" cx="0.42" cy="0.4" r="0.75"><stop offset="0" stop-color="#F2CE6E"/><stop offset="0.6" stop-color="#E0A83E"/><stop offset="1" stop-color="#B87A22"/></radialGradient>
				<filter id="phBgT" x="-10%" y="-10%" width="120%" height="120%"><feTurbulence type="fractalNoise" baseFrequency="0.028 0.032" numOctaves="3" seed="14" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="3.4" diffuseConstant="1" result="l"><feDistantLight azimuth="235" elevation="58"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
				<filter id="phBgCrT" x="-10%" y="-10%" width="120%" height="120%"><feTurbulence type="fractalNoise" baseFrequency="0.06 0.07" numOctaves="2" seed="9" result="n"/><feDiffuseLighting in="n" lighting-color="#ffffff" surfaceScale="2.6" diffuseConstant="1" result="l"><feDistantLight azimuth="235" elevation="58"/></feDiffuseLighting><feComposite in="l" in2="SourceGraphic" operator="arithmetic" k1="1" k2="0" k3="0" k4="0"/></filter>
			</defs>
			<circle cx="200" cy="200" r="188" fill="url(#phBgCrust)" filter="url(#phBgCrT)"/>
			<circle cx="200" cy="200" r="150" fill="url(#phBgCheese)" filter="url(#phBgT)"/>
			<ellipse cx="150" cy="120" rx="34" ry="24" fill="#A85E14" opacity="0.55"/>
			<ellipse cx="262" cy="150" rx="28" ry="20" fill="#9C5410" opacity="0.5"/>
			<ellipse cx="235" cy="265" rx="36" ry="24" fill="#A85E14" opacity="0.55"/>
			<ellipse cx="130" cy="240" rx="26" ry="18" fill="#9C5410" opacity="0.5"/>
			<ellipse cx="196" cy="180" rx="22" ry="16" fill="#C0762A" opacity="0.4"/>
			<ellipse cx="172" cy="145" rx="18" ry="12" fill="#FFEFB0" opacity="0.4"/>
			<ellipse cx="245" cy="205" rx="16" ry="11" fill="#FFEFB0" opacity="0.35"/>
			<ellipse cx="160" cy="272" rx="17" ry="11" fill="#FFEFB0" opacity="0.35"/>
			' . $cuts . $strands . '
		</svg>';
	}
}

if ( ! function_exists( 'phsg_svg_store' ) ) {
	/**
	 * חנות פיצה האט קטנה וזוהרת – מוצבת על מפת הבמה (המשולשים קופצים מחנות לחנות).
	 *
	 * @return string
	 */
	function phsg_svg_store() {
		return '<svg viewBox="0 0 90 78" width="100%" height="100%" style="pointer-events:none;">
			<defs>
				<linearGradient id="phStB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFF6E8"/><stop offset="1" stop-color="#E3D2BC"/></linearGradient>
				<linearGradient id="phStR" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FF5A63"/><stop offset="1" stop-color="#C4101E"/></linearGradient>
			</defs>
			<ellipse cx="45" cy="72" rx="30" ry="5" fill="rgba(0,0,0,0.45)"/>
			<rect x="16" y="34" width="58" height="34" rx="5" fill="url(#phStB)"/>
			<rect x="16" y="34" width="58" height="6" fill="rgba(120,80,30,0.18)"/>
			<rect x="22" y="44" width="14" height="12" rx="2.5" fill="#FFD968" class="phsg-store__win"/>
			<rect x="54" y="44" width="14" height="12" rx="2.5" fill="#FFD968" class="phsg-store__win"/>
			<rect x="39" y="46" width="12" height="22" rx="2.5" fill="#C4101E"/>
			<rect x="40.5" y="47.5" width="4" height="19" rx="1.5" fill="#E8404E" opacity="0.7"/>
			<path d="M6 36 Q45 4 84 36 L73 36 Q45 15 17 36 Z" fill="url(#phStR)"/>
			<path d="M12 33 Q45 9 78 33" fill="none" stroke="#FF9BA0" stroke-width="2.4" stroke-linecap="round" opacity="0.75"/>
			<ellipse cx="45" cy="30" rx="17" ry="8.5" fill="#FFFFFF"/>
			<ellipse cx="45" cy="30" rx="17" ry="8.5" fill="none" stroke="#E3D2BC" stroke-width="1.2"/>
			<text x="45" y="33" text-anchor="middle" font-size="7.5" font-weight="700" font-style="italic" fill="#E8192C" font-family="Almoni, Rubik, sans-serif">Pizza Hut</text>
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
		return '<svg class="phsg-map-svg" viewBox="0 0 200 250" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<g stroke="#E8443B" stroke-width="1.3" opacity="0.5" stroke-linecap="round" fill="none">
				<path d="M100 10 V24 M97 20 L100 12 L103 20"/>
				<path d="M100 226 V240"/>
				<path d="M182 125 H196"/>
				<path d="M4 125 H18"/>
			</g>
			<g font-family="Almoni, Rubik, sans-serif" font-weight="700" fill="#E4574C" opacity="0.9" text-anchor="middle">
				<text x="100" y="8" font-size="12">צפון</text>
				<text x="100" y="250" font-size="12">דרום</text>
				<text x="191" y="129" font-size="11">מזרח</text>
				<text x="9" y="129" font-size="11">מערב</text>
			</g>
			<g transform="translate(60 6) scale(1.05 0.96)">
				<path d="M38 6 C34 20 30 40 31 55 C29 70 26 80 25 90 C23 105 24 118 27 128 L22 138 C24 146 28 150 32 152 L47 247 L54 240 C58 200 60 170 55 155 C58 140 58 125 54 115 C57 105 57 95 54 88 C56 70 55 55 52 45 C55 35 56 25 52 18 C50 12 44 8 38 6 Z"
					fill="rgba(240,69,58,0.06)" stroke="#E8443B" stroke-width="1.5" stroke-linejoin="round" opacity="0.9" vector-effect="non-scaling-stroke"/>
				<ellipse cx="52" cy="23" rx="2.6" ry="5" fill="rgba(140,200,240,0.3)"/>
				<ellipse cx="54" cy="100" rx="2.6" ry="10" fill="rgba(140,200,240,0.24)"/>
				<g fill="#F0453A">
					<circle cx="31" cy="55" r="3.4" opacity="0.22"/><circle cx="31" cy="55" r="1.9"/>
					<circle cx="26" cy="86" r="3.4" opacity="0.22"/><circle cx="26" cy="86" r="1.9"/>
					<circle cx="32" cy="130" r="3.4" opacity="0.22"/><circle cx="32" cy="130" r="1.9"/>
					<circle cx="47" cy="245" r="3.4" opacity="0.22"/><circle cx="47" cy="245" r="1.9"/>
				</g>
				<g transform="translate(46 102)"><path d="M0 -7 L2 -2 L7 -2 L3 1.4 L4.5 6.5 L0 3.4 L-4.5 6.5 L-3 1.4 L-7 -2 L-2 -2 Z" fill="#F0453A"/></g>
				<g font-family="Almoni, Rubik, sans-serif" font-size="7.5" font-weight="500" fill="#E4574C" opacity="0.92" text-anchor="start">
					<text x="36" y="57">חיפה</text>
					<text x="12" y="88" text-anchor="end">ת״א</text>
					<text x="52" y="104">י-ם</text>
					<text x="37" y="132">ב״ש</text>
					<text x="52" y="247">אילת</text>
				</g>
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
			return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="currentColor"/><path d="M16 8.6 A5 5 0 0 1 16 15.4 M18.6 6 A8 8 0 0 1 18.6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
		}
		return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M4 9 L8 9 L13 4.5 L13 19.5 L8 15 L4 15 Z" fill="currentColor"/><path d="M16.5 9.5 L21.5 14.5 M21.5 9.5 L16.5 14.5" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/></svg>';
	}
}

if ( ! function_exists( 'phsg_svg_pin' ) ) {
	/**
	 * אייקון "סניפים" – סיכת מיקום נקייה בקו-מתאר עם נקודה מלאה (currentColor).
	 *
	 * @return string
	 */
	function phsg_svg_pin() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path d="M12 2.4c-3.9 0-7 3-7 6.9 0 4.6 5.4 10.6 6.4 11.7a.8.8 0 0 0 1.2 0C13.6 19.9 19 13.9 19 9.3 19 5.4 15.9 2.4 12 2.4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="9.1" r="2.5" fill="currentColor"/></svg>';
	}
}

if ( ! function_exists( 'phsg_time_ago' ) ) {
	/**
	 * זמן יחסי בעברית: "עכשיו", "לפני 5 דק׳", "לפני 2 שע׳", "לפני 3 ימים".
	 *
	 * @param string $mysql_datetime זמן ב-mysql (current_time('mysql')).
	 * @return string
	 */
	function phsg_time_ago( $mysql_datetime ) {
		if ( empty( $mysql_datetime ) ) {
			return '';
		}
		$then = strtotime( $mysql_datetime );
		$now  = (int) current_time( 'timestamp' );
		$diff = $now - $then;
		if ( $diff < 0 ) {
			$diff = 0;
		}
		if ( $diff < 60 ) {
			return __( 'עכשיו', 'pizza-hut-slice-game' );
		}
		if ( $diff < 3600 ) {
			$m = (int) floor( $diff / 60 );
			/* translators: %d = minutes */
			return sprintf( _n( 'לפני דקה', 'לפני %d דק׳', $m, 'pizza-hut-slice-game' ), $m );
		}
		if ( $diff < 86400 ) {
			$h = (int) floor( $diff / 3600 );
			/* translators: %d = hours */
			return sprintf( _n( 'לפני שעה', 'לפני %d שע׳', $h, 'pizza-hut-slice-game' ), $h );
		}
		if ( $diff < 604800 ) {
			$d = (int) floor( $diff / 86400 );
			/* translators: %d = days */
			return sprintf( _n( 'אתמול', 'לפני %d ימים', $d, 'pizza-hut-slice-game' ), $d );
		}
		$w = (int) floor( $diff / 604800 );
		/* translators: %d = weeks */
		return sprintf( _n( 'לפני שבוע', 'לפני %d שב׳', $w, 'pizza-hut-slice-game' ), $w );
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
			$ago     = isset( $row['ago'] ) ? $row['ago'] : '';
			$rank_bg = $rank <= 3 ? $medals[ $rank - 1 ] : '#FBFAEE';
			$row_bg  = ( $i % 2 ) ? '#231C22' : '#1D171C';

			$html .= '<div class="phsg-board__row" style="background:' . esc_attr( $row_bg ) . ';">';
			$html .= '<span class="phsg-board__rank" style="background:' . esc_attr( $rank_bg ) . ';">' . esc_html( $rank ) . '</span>';
			$html .= '<span class="phsg-board__name">' . esc_html( $name ) . '</span>';
			$html .= '<span class="phsg-board__score">' . esc_html( $score ) . '</span>';
			$html .= '<span class="phsg-board__avg">' . esc_html( $ago ) . '</span>';
			$html .= '</div>';
			$i++;
		}

		return $html;
	}
}

if ( ! function_exists( 'phsg_branches' ) ) {
	/**
	 * סניפי פיצה האט מקובצים לפי אזור (נכון למועד הבנייה).
	 *
	 * @return array מפה: אזור => רשימת סניפים.
	 */
	function phsg_branches() {
		return array(
			'צפון' => array( 'אום אל-פאחם', 'אור עקיבא', 'באקה אל גרביה', 'בית שאן', 'טמרה', 'טבריה', 'ירכא', 'יקנעם', 'כפר מגאר', 'כפר קרע', 'כרמיאל', 'מגדל', 'מעלות', 'נהריה', 'נצרת', 'סכנין', 'עכו', 'עפולה', 'ראש פינה', 'קרית אתא', 'קרית שמונה', 'קרית מוצקין', 'רמת ישי', 'שפרעם', 'טירת הכרמל', 'חיפה – מוריה', 'חיפה – מושבה גרמנית', 'חיפה – טכניון/נשר', 'חדרה', 'חריש', 'זכרון יעקב', 'דאלית אל-כרמל' ),
			'שרון' => array( 'בני דרור', 'הוד השרון', 'הרצליה', 'כפר יונה', 'כפר סבא', 'נתניה – מרכז העיר', 'נתניה פולג', 'פרדס חנה כרכור', 'רעננה', 'רמת השרון' ),
			'מרכז / גוש דן' => array( 'בני ברק', 'בת ים', 'גבעת שמואל', 'גני תקווה / קרית אונו', 'חולון – מערב', 'חולון – מזרח', 'יהוד', 'פתח תקווה – אם המושבות', 'פתח תקווה – כפר גנים', 'פתח תקווה סירקין', 'ראש העין', 'רמת גן – מרום נווה', 'מגדלי תל אביב / גבעתיים', 'תל אביב – קינג ג׳ורג׳', 'תל אביב יד אליהו', 'תל אביב רמת אביב', 'תל אביב – רמת החייל' ),
			'ירושלים והסביבה' => array( 'בית שמש', 'ביתר עלית', 'ירושלים – בן הלל', 'ירושלים – מלחה', 'ירושלים – ניות', 'ירושלים – קרית יובל', 'ירושלים – רמות', 'ירושלים – שועפאט', 'ירושלים – תחנה מרכזית', 'ירושלים – תלפיות', 'ירושלים – פסגת זאב', 'מבשרת ציון', 'מעלה אדומים', 'מודיעין מורשת' ),
			'שפלה' => array( 'אשדוד סיטי', 'באר יעקב', 'באר יעקב-צמרות', 'בילו סנטר / קרית עקרון', 'גדרה', 'יבנה', 'נס ציונה', 'רחובות', 'רמלה לוד', 'רמלה מערב – נאות שמיר', 'ראשון לציון מזרח', 'ראשון לציון מערב', 'שוהם' ),
			'דרום ואילת' => array( 'אופקים', 'אילת – הקניון האדום', 'אילת – טיילת', 'אשקלון', 'באר שבע אביסרור', 'באר שבע MALL-7', 'באר שבע – רמות', 'דימונה', 'מצפה רמון', 'נתיבות', 'ערד', 'קרית גת', 'קרית מלאכי', 'רהט', 'שדרות' ),
		);
	}
}

if ( ! function_exists( 'phsg_render_branches' ) ) {
	/**
	 * רינדור רשימת הסניפים המקובצת לחלון הסניפים.
	 *
	 * @param string $branches_url קישור לרשימה המלאה באתר.
	 * @return string HTML בטוח.
	 */
	function phsg_render_branches( $branches_url ) {
		$html = '';
		foreach ( phsg_branches() as $region => $names ) {
			$html .= '<div class="phsg-branch-group">';
			$html .= '<h4 class="phsg-branch-group__title">' . esc_html( $region ) . '</h4>';
			$html .= '<ul class="phsg-branches__list">';
			foreach ( $names as $name ) {
				$html .= '<li class="phsg-branch">📍 ' . esc_html( $name ) . '</li>';
			}
			$html .= '</ul></div>';
		}
		if ( $branches_url ) {
			$html .= '<a class="phsg-branches__link" href="' . esc_url( $branches_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'לרשימת הסניפים המלאה והכתובות', 'pizza-hut-slice-game' ) . '<span class="phsg-arrow" aria-hidden="true">←</span></a>';
		}
		return $html;
	}
}

if ( ! function_exists( 'phsg_render_terms' ) ) {
	/**
	 * תקנון הפעילות – מוצג בחלון מתוך טופס ההצטרפות.
	 *
	 * @return string HTML.
	 */
	function phsg_render_terms() {
		$sections = array(
			array(
				'title' => '',
				'intro' => __( 'התנאים המפורטים בתקנון זה יחולו על משתתף, כהגדרתו להלן, במסגרת פעילות קידום מכירות ותוכן של פיצה האט ישראל, שבמסגרתה ניתן יהיה לזכות בפרס, כמפורט מטה (להלן: "התקנון").', 'pizza-hut-slice-game' ),
				'items' => array(),
			),
			array(
				'title' => __( 'כללי', 'pizza-hut-slice-game' ),
				'items' => array(
					__( 'הפעילות נערכת על ידי רשת פיצה האט ישראל – המופעלת באמצעות חברת טבסקו החזקות בע"מ, ח.פ. 514179803 (להלן: "החברה" ו/או "עורכת הפעילות").', 'pizza-hut-slice-game' ),
					__( 'תקנון זה יחול על הפעילות שתקיים החברה באמצעי הפרסום הרשמיים שלה, במסגרתה ייבחרו שלושה משתתפים שיעמדו בתנאי התקנון ויהיו זכאים לפרס, כמפורט להלן.', 'pizza-hut-slice-game' ),
					__( 'תקנון הפעילות יפורסם באתר החברה ו/או באמצעי הפרסום הרשמיים של הפעילות.', 'pizza-hut-slice-game' ),
				),
			),
			array(
				'title' => __( 'הגדרות', 'pizza-hut-slice-game' ),
				'items' => array(
					__( '"אתר החברה" – אתר האינטרנט הרשמי של פיצה האט ישראל, כפי שיעודכן מעת לעת.', 'pizza-hut-slice-game' ),
					__( '"עמוד הפעילות" – אמצעי הפרסום הרשמיים של החברה, לרבות כל עמוד, אתר, יישומון, משחק, דף נחיתה או פלטפורמה דיגיטלית אחרת שבמסגרתם תפורסם הפעילות ותתבצע ההשתתפות בה, כפי שתקבע החברה מעת לעת.', 'pizza-hut-slice-game' ),
					__( '"משתתף/ים" – כלל הציבור בישראל אשר ישתתפו בפעילות בהתאם ובכפוף להוראות תקנון זה. ככל שמשתתף הינו קטין, השתתפותו תיחשב כהצהרה כי קיבל את הסכמת הוריו או האפוטרופוס החוקי שלו להשתתפות בפעילות ולמימוש הפרס. מובהר כי עובדים, מנהלים, יועצים ונותני שירות של החברה ו/או של מי מטעמה, וכן בני משפחותיהם הגרים עמם, לא יהיו זכאים להשתתף בפעילות.', 'pizza-hut-slice-game' ),
					__( '"הפעילות" – פעילות קידום מכירות ותוכן שבמסגרתה יתבקשו משתתפים, במהלך תקופת הפעילות, להשתתף במשחק ו/או לבצע את הפעולה המוגדרת בפרסומי הפעילות הרשמיים של החברה, והכל כמפורט ובכפוף לאמור בתקנון זה; שלושה משתתפים שייבחרו בהתאם להוראות התקנון יהיו זכאים לפרס.', 'pizza-hut-slice-game' ),
					__( '"הפרס" – שובר, קוד הטבה ו/או הטבה אחרת מטעם החברה לארוחה משפחתית בשווי 300 ש"ח, המשקפת שווה ערך ל-5 פיצות משפחתיות, כפי שתקבע החברה. "הזוכים" – שלושת המשתתפים שייבחרו בהתאם להוראות תקנון זה.', 'pizza-hut-slice-game' ),
				),
			),
			array(
				'title' => __( 'תקופת הפעילות', 'pizza-hut-slice-game' ),
				'items' => array(
					__( 'הפעילות תחל ביום 24.7.2026 ותסתיים ביום 14.8.2026, או במועד אחר כפי שתקבע החברה, והכל בהתאם לפרסומי הפעילות הרשמיים (להלן: "תקופת הפעילות").', 'pizza-hut-slice-game' ),
					__( 'ההשתתפות בפעילות אינה כרוכה בתשלום ואינה מותנית ברכישה כלשהי.', 'pizza-hut-slice-game' ),
					__( 'למען הסר ספק, הפעילות תתנהל על בסיס עמידה בתנאי התקנון ובחירת הזוכים על ידי החברה בהתאם לשיקולים מקצועיים וסבירים כפי שייקבעו על ידה, ולפיכך אינה מהווה הגרלה, פרס נושא גורל או פעילות המבוססת על מזל, גורל או בחירה אקראית.', 'pizza-hut-slice-game' ),
					__( 'למען הסר ספק, לאחר תום תקופת הפעילות לא ניתן יהיה להשתתף בפעילות ו/או לטעון לזכאות לפרס.', 'pizza-hut-slice-game' ),
					__( 'החברה שומרת לעצמה את הזכות להפסיק ו/או להאריך ו/או לקצר ו/או לשנות את תקופת הפעילות, את תנאיה, את מנגנון הבחירה ו/או את פרטי הפרס, בכל עת, והכל לפי שיקול דעתה הבלעדי ובכפוף להוראות הדין.', 'pizza-hut-slice-game' ),
				),
			),
			array(
				'title' => __( 'תנאי הפעילות והזכאות לפרס', 'pizza-hut-slice-game' ),
				'items' => array(
					__( 'לצורך השתתפות תקפה בפעילות, על המשתתף לבצע במהלך תקופת הפעילות את הפעולה המוגדרת בפרסומי הפעילות הרשמיים, לרבות השתתפות במשחק ככל שהדבר רלוונטי, בהתאם להנחיות החברה. השתתפות שלא תבוצע בהתאם להוראות הפעילות, שתהיה חלקית, פגומה, בלתי ניתנת לאימות, בניגוד לדין או לאחר תום תקופת הפעילות, לא תיחשב כהשתתפות תקפה.', 'pizza-hut-slice-game' ),
					__( 'המשתתף מצהיר ומתחייב כי כל מידע, תוכן, נתון ו/או חומר שיימסר או יועלה על ידו במסגרת הפעילות הינו חוקי, מדויק, מקורי או כזה שהמשתתף מחזיק בכל הזכויות, ההרשאות וההסכמות הדרושות לגביו, וכי אין בו כדי להפר כל דין או זכות של צד שלישי כלשהו. עורכת הפעילות תהא רשאית לפסול, לפי שיקול דעתה הסביר, כל השתתפות שנעשתה בחוסר תום לב, באמצעים פסולים או אוטומטיים, באמצעות מספר זהויות ו/או בניגוד להוראות תקנון זה או הוראות הדין.', 'pizza-hut-slice-game' ),
					__( 'במסגרת הפעילות ייבחרו שלושה משתתפים שיהיו זכאים לפרס. הבחירה תיעשה באופן ישיר ועל פי שיקול דעתה הבלעדי של עורכת הפעילות, בין היתר בהתחשב בעמידה בתנאי הפעילות, בביצועי המשתתף במשחק ככל שרלוונטי, ביצירתיות, בהתאמה לרוח הפעילות, באיכות התוכן ו/או בכל שיקול מקצועי אחר כפי שתמצא החברה לנכון.', 'pizza-hut-slice-game' ),
					__( 'לאחר בדיקת העמידה בתנאי הפעילות, עורכת הפעילות תיצור קשר עם הזוכים באמצעות טלפון, דואר אלקטרוני ו/או בכל אמצעי קשר אחר שיימסר לה. זוכה שלא ישיב לפניית עורכת הפעילות, לא ימסור את הפרטים הנדרשים או לא ישתף פעולה במימוש הפרס בתוך פרק הזמן שייקבע על ידי עורכת הפעילות, יאבד את זכאותו, ועורכת הפעילות תהא רשאית לפנות למשתתף אחר במקומו.', 'pizza-hut-slice-game' ),
					__( 'ככל שבמסגרת הפעילות יועלה, יישלח או יימסר על ידי המשתתף תוכן כלשהו, החברה תהא רשאית לשתף, לפרסם, להעלות מחדש ו/או לעשות בו שימוש בעמוד הפעילות ו/או בכל מדיה אחרת מטעמה, ללא תמורה נוספת, והמשתתף מעניק בכך את הסכמתו לכך.', 'pizza-hut-slice-game' ),
					__( 'כל משתתף רשאי להשתתף בפעילות פעם אחת או יותר, בכפוף לתנאי הפעילות כפי שיפורסמו על ידי החברה, אולם לא תתאפשר זכאות ליותר מפרס אחד לאותו אדם, אלא אם החברה קבעה אחרת במפורש.', 'pizza-hut-slice-game' ),
					__( 'הפרס הינו אישי ואינו ניתן להעברה, להמחאה, לשינוי, להחלפה או להמרה לכסף, זיכוי או לכל תמורה אחרת. הפרס יימסר כפי שהוא, ובכפוף לזמינות, למדיניות החברה ולהוראות כל דין.', 'pizza-hut-slice-game' ),
					__( 'סה"כ כמות הפרסים בפעילות הינה 3, כאשר כל זוכה יהיה זכאי לפרס אחד.', 'pizza-hut-slice-game' ),
				),
			),
			array(
				'title' => __( 'הוראות כלליות ופרשנות', 'pizza-hut-slice-game' ),
				'items' => array(
					__( 'הוראות תקנון זה יחולו על כל המשתתפים בפעילות ויהוו את הבסיס המשפטי לכל דיון בין המשתתפים לבין עורכת הפעילות. יש לקרוא בעיון את התקנון במלואו. תקנון הפעילות ניתן לעיון באתר החברה ו/או באמצעי הפרסום הרשמיים של הפעילות.', 'pizza-hut-slice-game' ),
					__( 'התקנון מנוסח בלשון זכר מטעמי נוחות בלבד, אולם הוא מיועד לשני המינים כאחד.', 'pizza-hut-slice-game' ),
					__( 'התקנון ממצה ומסדיר את היחסים בין המשתתפים לבין עורכת הפעילות, ומובהר בזאת כי השתתפות בפעילות מהווה הסכמה מלאה להוראות התקנון והתחייבות כי, בכפוף להוראות התקנון, לא תהא למשתתף ו/או למי מטעמו כל טענה ו/או דרישה ו/או תביעה כנגד החברה ו/או מי מעובדיה ו/או מי מטעמה בקשר עם הפעילות. לכן, אם אינך מסכים לתנאי התקנון, כולם או חלקם, אינך רשאי להשתתף בפעילות.', 'pizza-hut-slice-game' ),
					__( 'השתתפות בפעילות ו/או קבלת הפרס מהווה הסכמה להוראות מדיניות הפרטיות המופיעות באתר החברה, ככל שהן חלות על מידע אישי שיימסר לחברה בקשר עם הפעילות, לרבות שם מלא, מספר טלפון, כתובת דואר אלקטרוני, כתובת למשלוח ככל שתידרש, וכל פרט נוסף שיידרש לצורך בדיקת הזכאות, יצירת קשר עם הזוכים ומימוש הפרס.', 'pizza-hut-slice-game' ),
					__( 'ההשתתפות בפעילות הינה באחריותו הבלעדית של המשתתף.', 'pizza-hut-slice-game' ),
					__( 'עורכת הפעילות שומרת לעצמה את הזכות לבטל ו/או לשנות ו/או לעדכן ו/או להפסיק את הפעילות ו/או את התקנון בכל עת, לפי שיקול דעתה הבלעדי והמוחלט ובכפוף להוראות הדין, ללא צורך במתן הודעה מוקדמת, וכל שינוי יחייב מרגע פרסומו באתר החברה ו/או בעמוד הפעילות.', 'pizza-hut-slice-game' ),
					__( 'עורכת הפעילות ו/או מי מטעמה לא יישאו באחריות לכל נזק ו/או הפסד ו/או אובדן ו/או הוצאה שייגרמו בכל הקשור להשתתפות או אי השתתפות בפעילות ולתקנון זה.', 'pizza-hut-slice-game' ),
					__( 'מבלי לגרוע מכלליות האמור לעיל, עורכת הפעילות ו/או מי מטעמה לא יישאו באחריות לכל תקלה טכנית ו/או טעות ו/או שיבוש ו/או הפרעה בפעולת רשת האינטרנט, מערכות מחשוב, מערכות דיוור, המשחק, עמוד הפעילות, קווי תקשורת, שרתים, תוכנות או כל מערכת אחרת, אשר ימנעו השתתפות תקפה בפעילות, קליטת נתונים, זיהוי ההשתתפות ו/או מסירת הפרס.', 'pizza-hut-slice-game' ),
					__( 'כל מקרה של מחלוקת בקשר עם הפעילות ו/או הפרס ו/או כלליה ו/או תנאיה ו/או הוראות התקנון, לרבות לעניין עמידה בתנאי התקנון, אופן הבחירה, ניקוד או תוצאות המשחק ככל שרלוונטי, זיהוי המשתתף, מסירת הפרטים הנדרשים או מימוש הפרס, יוכרע על ידי נציג/ה מוסמך/ת מטעם החברה, והכרעתו/ה תהיה סופית ובלתי ניתנת לערעור.', 'pizza-hut-slice-game' ),
					__( 'הצילומים, התמונות, ההמחשות ו/או הפרסומים בקשר עם הפעילות, בכל מדיה, מודעה, דף נחיתה, אתר אינטרנט, הודעה, פוסט או חומר שיווקי אחר, מיועדים להמחשה בלבד ואין לראות בהם התחייבות כלשהי מעבר לאמור במפורש בתקנון זה.', 'pizza-hut-slice-game' ),
					__( 'בכל מקרה של סתירה ו/או אי התאמה כלשהי בין הוראות תקנון זה לבין פרסומים אחרים בדבר הפעילות, לרבות באתרי אינטרנט שונים ו/או בעיתונים ו/או במודעות, תגברנה הוראות תקנון זה לכל דבר ועניין.', 'pizza-hut-slice-game' ),
					__( 'למען הסר ספק, הפעילות כפופה להוראות תקנון זה ולהוראות כל דין.', 'pizza-hut-slice-game' ),
					__( 'על התקנון, פרשנותו ועל כל הכרוך בו יחולו דיני מדינת ישראל בלבד, וכל סכסוך או סוגיה משפטית בקשר עם התקנון יובאו להכרעת בתי המשפט המוסמכים בישראל.', 'pizza-hut-slice-game' ),
					__( 'החלוקה לסעיפים וכותרות הסעיפים הינה לשם נוחיות בלבד ולא תשמש לצרכי פרשנות.', 'pizza-hut-slice-game' ),
				),
			),
		);

		$html = '<div class="phsg-terms">';
		$n = 0;
		foreach ( $sections as $sec ) {
			if ( ! empty( $sec['intro'] ) ) {
				$html .= '<p class="phsg-terms__intro">' . esc_html( $sec['intro'] ) . '</p>';
			}
			if ( '' !== $sec['title'] ) {
				$n++;
				$html .= '<h4 class="phsg-terms__title">' . $n . '. ' . esc_html( $sec['title'] ) . '</h4>';
			}
			if ( ! empty( $sec['items'] ) ) {
				$html .= '<ol class="phsg-terms__list">';
				foreach ( $sec['items'] as $item ) {
					$html .= '<li>' . esc_html( $item ) . '</li>';
				}
				$html .= '</ol>';
			}
		}
		$html .= '</div>';

		return $html;
	}
}
