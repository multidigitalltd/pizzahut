<?php
/**
 * תבנית המשחק – שחזור נאמן של אב-הטיפוס המאושר (design handoff v2).
 *
 * מסכים: אינטרו ← טופס ← ספירה/משחק ← סיום + טבלת שיאים.
 *
 * @var array $atts        תכונות השורטקוד (title, logo).
 * @var array $leaderboard טבלת שיאים ראשונית.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$phsg_logo_url = ! empty( $atts['logo'] ) ? $atts['logo'] : PHSG_PLUGIN_URL . 'assets/img/pizza-hut-logo.png';

// שורות תבנית הטיפוגרפיה – צבע לסירוגין + היסט התחלתי, כמו באב-הטיפוס.
$phsg_marquee_rows = array(
	array( '#F32735', 32, 0 ),
	array( '#2D2A26', 44, -160 ),
	array( '#F0EFDD', 36, -80 ),
	array( '#F32735', 48, -200 ),
	array( '#2D2A26', 34, 0 ),
	array( '#F0EFDD', 42, -120 ),
	array( '#F32735', 38, -180 ),
	array( '#2D2A26', 46, -40 ),
);
$phsg_marquee_txt  = 'פיצה חמה · מוצרלה · עוד ביס · SINCE 1990 · חם מהתנור · ';
?>
<div class="phsg-app" dir="rtl" lang="he" role="application" data-fullscreen="<?php echo esc_attr( ! empty( $atts['fullscreen'] ) && '0' !== $atts['fullscreen'] ? '1' : '0' ); ?>" aria-label="<?php echo esc_attr__( 'משחק פיצה האט – תפוס ת\'משולש', 'pizza-hut-slice-game' ); ?>">

	<?php // רקע טיפוגרפי נע – שורות במחזוריות שמכסות את כל גובה המסך ?>
	<div class="phsg-marquee" aria-hidden="true">
		<?php for ( $phsg_i = 0; $phsg_i < 32; $phsg_i++ ) : ?>
			<?php $phsg_row = $phsg_marquee_rows[ $phsg_i % count( $phsg_marquee_rows ) ]; ?>
			<div class="phsg-marquee__row" style="color:<?php echo esc_attr( $phsg_row[0] ); ?>; animation-duration:<?php echo esc_attr( $phsg_row[1] ); ?>s; margin-right:<?php echo esc_attr( $phsg_row[2] - ( $phsg_i * 7 ) % 200 ); ?>px;">
				<?php echo esc_html( str_repeat( $phsg_marquee_txt, 5 ) ); ?>
			</div>
		<?php endfor; ?>
	</div>

	<?php // עומק – ויניטה ?>
	<div class="phsg-vignette" aria-hidden="true"></div>

	<div class="phsg-col">

		<?php // ===== כותרת עליונה ===== ?>
		<header class="phsg-header">
			<div class="phsg-header__brand">
				<div class="phsg-logo-card">
					<img src="<?php echo esc_url( $phsg_logo_url ); ?>" alt="Pizza Hut">
				</div>
				<div class="phsg-header__pills">
					<span class="phsg-pill phsg-pill--title"><?php echo esc_html( $atts['title'] ); ?></span>
				</div>
			</div>
			<button type="button" class="phsg-sound" data-action="toggle-sound" aria-pressed="false">
				<span class="phsg-sound__icon phsg-sound__icon--on"><?php echo phsg_svg_sound( true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="phsg-sound__icon phsg-sound__icon--off"><?php echo phsg_svg_sound( false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span data-sound-label><?php esc_html_e( 'צליל: פועל', 'pizza-hut-slice-game' ); ?></span>
			</button>
		</header>

		<?php // ===== אינטרו ===== ?>
		<section class="phsg-screen phsg-intro is-active" data-screen="intro">
			<div class="phsg-hero">
				<div class="phsg-hero__glow" aria-hidden="true"></div>
				<div class="phsg-hero__float">
					<?php echo phsg_svg_hero_slice(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<h1 class="phsg-h1"><?php esc_html_e( "תפוס ת'משולש!", 'pizza-hut-slice-game' ); ?></h1>

			<?php // מסר המותג ?>
			<div class="phsg-promo">
				<p class="phsg-promo__text">
					<?php esc_html_e( 'את פיצה האט לא צריך לחפש —', 'pizza-hut-slice-game' ); ?><br>
					<?php esc_html_e( 'לכל מקום בו תצאו לטייל ולבלות תמצאו', 'pizza-hut-slice-game' ); ?>
					<strong class="phsg-promo__hot"><?php esc_html_e( 'פיצה חמה, טרייה וטעימה.', 'pizza-hut-slice-game' ); ?></strong>
				</p>
				<button type="button" class="phsg-promo__here" data-action="promo-scroll"><?php esc_html_e( 'גם ממש כאן — על המסך 🍕', 'pizza-hut-slice-game' ); ?></button>
			</div>

			<div class="phsg-howto">
				<h2 class="phsg-howto__title"><?php esc_html_e( 'איך משחקים?', 'pizza-hut-slice-game' ); ?></h2>
				<p class="phsg-howto__text"><?php esc_html_e( 'משולש פיצה חם קופץ על המסך — יש לכם 60 שניות לתפוס כמה שיותר. זהירות מהפטריות, הזיתים, הבצל והעגבניות!', 'pizza-hut-slice-game' ); ?></p>
			</div>

			<?php // מקרא – בונוסים מימין, מכשולים משמאל (שורה אחת) ?>
			<div class="phsg-legend-row">
			<div class="phsg-legend-group phsg-legend-group--good">
				<span class="phsg-legend-title phsg-legend-title--good"><?php esc_html_e( '🏆 שווה נקודות — תתפסו!', 'pizza-hut-slice-game' ); ?></span>
				<div class="phsg-legend">
					<div class="phsg-legend__card">
						<svg viewBox="0 0 100 110" width="44" height="48"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#E8A33D" stroke="#2D2A26" stroke-width="4"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#F2B33C" stroke="#2D2A26" stroke-width="4"/><circle cx="40" cy="52" r="9" fill="#E0453A" stroke="#9E2B22" stroke-width="3"/><circle cx="58" cy="68" r="8" fill="#E0453A" stroke="#9E2B22" stroke-width="3"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'משולש פיצה', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--red">+1</span>
					</div>
					<div class="phsg-legend__card phsg-legend__card--gold">
						<svg viewBox="0 0 100 110" width="44" height="48"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#F2B24E" stroke="#2D2A26" stroke-width="4"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#FFD95C" stroke="#2D2A26" stroke-width="4"/><circle cx="40" cy="52" r="9" fill="#E0453A" stroke="#9E2B22" stroke-width="3"/><circle cx="78" cy="18" r="7" fill="#FFE49A" stroke="#D19A2B" stroke-width="3"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'משולש זהב', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--gold">+3</span>
					</div>
					<div class="phsg-legend__card phsg-legend__card--gold">
						<svg viewBox="0 0 64 56" width="44" height="39"><path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="#FFD95C" stroke="#2D2A26" stroke-width="4"/><circle cx="24" cy="38" r="5" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2.5"/><circle cx="41" cy="41" r="4" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2.5"/><circle cx="33" cy="26" r="3.5" fill="#FFF3B0" stroke="#D19A2B" stroke-width="2.5"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'נתח גבינה', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--gold">+2</span>
					</div>
					<div class="phsg-legend__card phsg-legend__card--gold">
						<svg viewBox="0 0 60 60" width="44" height="44"><circle cx="30" cy="32" r="22" fill="#FFE49A" stroke="#2D2A26" stroke-width="4"/><circle cx="30" cy="32" r="16" fill="#FFFEF6" stroke="#2D2A26" stroke-width="2.5"/><path d="M30 32 L30 21 M30 32 L38 36" stroke="#F32735" stroke-width="4" stroke-linecap="round"/><rect x="25" y="4" width="10" height="6" rx="2" fill="#F32735" stroke="#2D2A26" stroke-width="3"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'שעון בונוס', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--gold"><?php esc_html_e( "+5 שנ'", 'pizza-hut-slice-game' ); ?></span>
					</div>
					<div class="phsg-legend__card phsg-legend__card--gold">
						<svg viewBox="0 0 60 60" width="44" height="44"><path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="#F32735" stroke="#2D2A26" stroke-width="4"/><path d="M46 17 Q50 8 58 8" fill="none" stroke="#4C7B3A" stroke-width="5" stroke-linecap="round"/><path d="M20 42 Q16 32 26 24" fill="none" stroke="#FF8A8F" stroke-width="4" stroke-linecap="round"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'פלפל פרנזי', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--red"><?php esc_html_e( '×2 ל-6 שנ\'', 'pizza-hut-slice-game' ); ?></span>
					</div>
				</div>
			</div>

			<?php // מקרא – קבוצת המכשולים ?>
			<div class="phsg-legend-group phsg-legend-group--bad">
				<span class="phsg-legend-title phsg-legend-title--bad"><?php esc_html_e( '⚠️ מכשולים — אל תלחצו!', 'pizza-hut-slice-game' ); ?></span>
				<div class="phsg-legend phsg-legend--bad">
					<div class="phsg-legend__card phsg-legend__card--bad">
						<div class="phsg-legend__icons">
							<svg viewBox="0 0 60 62" width="26" height="27"><path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="#C9A874" stroke="#2D2A26" stroke-width="4"/><path d="M23 37 q-2 15 2 19 q5 3 10 0 q4 -4 2 -19" fill="#F0EFDD" stroke="#2D2A26" stroke-width="4"/></svg>
							<svg viewBox="0 0 60 60" width="24" height="24"><circle cx="30" cy="30" r="23" fill="#3B3830" stroke="#2D2A26" stroke-width="4"/><ellipse cx="30" cy="30" rx="9" ry="12" fill="#6E6754"/></svg>
							<svg viewBox="0 0 60 60" width="24" height="24"><circle cx="30" cy="30" r="23" fill="#EFE0F0" stroke="#2D2A26" stroke-width="4"/><circle cx="30" cy="30" r="15" fill="none" stroke="#B0729E" stroke-width="5"/></svg>
							<svg viewBox="0 0 60 60" width="24" height="24"><circle cx="30" cy="30" r="23" fill="#E85D3A" stroke="#2D2A26" stroke-width="4"/><circle cx="30" cy="30" r="15" fill="#F49B75"/></svg>
						</div>
						<span class="phsg-legend__name"><?php esc_html_e( 'תוספות על הבמה', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--dark">−1</span>
					</div>
					<div class="phsg-legend__card phsg-legend__card--bad">
						<svg viewBox="0 0 100 110" width="44" height="48"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#6B5138" stroke="#2D2A26" stroke-width="4"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#8A6B45" stroke="#2D2A26" stroke-width="4"/><circle cx="40" cy="52" r="9" fill="#4A3B2A" stroke="#2D2A26" stroke-width="3"/><path d="M35 18 q3 -8 0 -13 M60 16 q3 -8 0 -13" fill="none" stroke="#57534A" stroke-width="4" stroke-linecap="round"/></svg>
						<span class="phsg-legend__name"><?php esc_html_e( 'משולש שרוף', 'pizza-hut-slice-game' ); ?></span>
						<span class="phsg-vpill phsg-vpill--dark">−2</span>
					</div>
				</div>
			</div>
			</div><?php // סוף phsg-legend-row ?>

			<span class="phsg-note-pill"><?php esc_html_e( '⏱ 60 שניות · רצף של 5 תפיסות = בונוס +2', 'pizza-hut-slice-game' ); ?></span>
			<button type="button" class="phsg-cta phsg-cta--xl" data-action="go-form"><?php esc_html_e( 'מתחילים ‹', 'pizza-hut-slice-game' ); ?></button>
			<span class="phsg-legal"><?php esc_html_e( 'ההשתתפות כרוכה במילוי פרטים · בכפוף לתקנון', 'pizza-hut-slice-game' ); ?></span>
		</section>

		<?php // ===== טופס משתתפים ===== ?>
		<section class="phsg-screen phsg-form-card" data-screen="form" hidden>
			<h2 class="phsg-h2"><?php esc_html_e( 'רגע לפני שמתחילים', 'pizza-hut-slice-game' ); ?></h2>
			<p class="phsg-form-card__sub"><?php esc_html_e( 'מלאו פרטים כדי להיכנס לטבלת השיאים', 'pizza-hut-slice-game' ); ?></p>
			<form class="phsg-form" novalidate>
				<label class="phsg-field"><?php esc_html_e( 'שם מלא', 'pizza-hut-slice-game' ); ?>
					<input type="text" name="full_name" placeholder="ישראל ישראלי" autocomplete="name" maxlength="120">
				</label>
				<label class="phsg-field"><?php esc_html_e( 'טלפון', 'pizza-hut-slice-game' ); ?>
					<input type="tel" name="phone" placeholder="050-0000000" inputmode="tel" dir="ltr" autocomplete="tel" maxlength="15">
				</label>
				<label class="phsg-field"><?php esc_html_e( 'אימייל', 'pizza-hut-slice-game' ); ?>
					<input type="email" name="email" placeholder="you@mail.com" inputmode="email" dir="ltr" autocomplete="email" maxlength="190">
				</label>
				<label class="phsg-consent">
					<input type="checkbox" name="consent" value="1">
					<span><?php esc_html_e( 'קראתי ואני מאשר/ת את התקנון ואת קבלת עדכונים שיווקיים. השם שלי יוצג בטבלת השיאים (ללא טלפון או אימייל).', 'pizza-hut-slice-game' ); ?></span>
				</label>
				<div class="phsg-form-error" data-form-error hidden></div>
				<button type="submit" class="phsg-cta"><?php esc_html_e( 'יאללה, למשחק ‹', 'pizza-hut-slice-game' ); ?></button>
			</form>
		</section>

		<?php // ===== משחק (+ ספירה לאחור) ===== ?>
		<section class="phsg-screen phsg-game" data-screen="game" hidden>

			<div class="phsg-hud">
				<div class="phsg-hud__card">
					<span class="phsg-hud__label"><?php esc_html_e( 'ניקוד', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-hud__value phsg-hud__value--score" data-hud="score">0</span>
				</div>
				<div class="phsg-hud__card phsg-hud__card--timer" data-timer-card>
					<span class="phsg-hud__label"><?php esc_html_e( 'זמן שנותר', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-hud__value" data-hud="time">1:00</span>
				</div>
				<div class="phsg-hud__card">
					<span class="phsg-hud__label"><?php esc_html_e( 'שלב', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-hud__value" data-hud="level">1</span>
				</div>
			</div>

			<div class="phsg-progress"><div class="phsg-progress__fill" data-progress></div></div>

			<div class="phsg-stage" data-stage>
				<div class="phsg-combo" data-combo hidden></div>

				<div data-slices></div>
				<div class="phsg-sprite phsg-sprite--slice" data-slice hidden>
					<div class="phsg-sprite__wobble">
						<span class="phsg-sprite__skin" data-skin="normal"><?php echo phsg_svg_game_slice( false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="phsg-sprite__skin" data-skin="gold"><?php echo phsg_svg_game_slice( true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					</div>
					<span class="phsg-sparkle phsg-sparkle--a" data-gold-only hidden></span>
					<span class="phsg-sparkle phsg-sparkle--b" data-gold-only hidden></span>
				</div>

				<div data-obstacles></div>
				<div class="phsg-sprite phsg-sprite--cheese" data-cheese hidden><?php echo phsg_svg_cheese(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="phsg-sprite phsg-sprite--bonus" data-bonus hidden></div>
				<div data-popups></div>

				<div class="phsg-frenzy-badge" data-frenzy hidden>🌶️ <?php esc_html_e( 'פרנזי ×2!', 'pizza-hut-slice-game' ); ?></div>

				<div class="phsg-countdown" data-countdown hidden>
					<span class="phsg-countdown__num" data-countdown-num>3</span>
					<span class="phsg-countdown__sub"><?php esc_html_e( 'תתכוננו…', 'pizza-hut-slice-game' ); ?></span>
				</div>
			</div>

			<div class="phsg-chips">
				<span class="phsg-chip"><svg viewBox="0 0 100 110" width="18" height="20"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#E8A33D" stroke="#2D2A26" stroke-width="5"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#F2B33C" stroke="#2D2A26" stroke-width="5"/></svg> &lrm;+1</span>
				<span class="phsg-chip phsg-chip--gold"><svg viewBox="0 0 100 110" width="18" height="20"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#F2B24E" stroke="#2D2A26" stroke-width="5"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#FFD95C" stroke="#2D2A26" stroke-width="5"/></svg> &lrm;+3</span>
				<span class="phsg-chip"><svg viewBox="0 0 60 62" width="17" height="18"><path d="M6 31 Q30 3 54 31 Q42 38 30 38 Q18 38 6 31 Z" fill="#C9A874" stroke="#2D2A26" stroke-width="5"/><path d="M23 37 q-2 15 2 19 q5 3 10 0 q4 -4 2 -19" fill="#F0EFDD" stroke="#2D2A26" stroke-width="5"/></svg><svg viewBox="0 0 60 60" width="16" height="16"><circle cx="30" cy="30" r="23" fill="#3B3830" stroke="#2D2A26" stroke-width="5"/><ellipse cx="30" cy="30" rx="9" ry="12" fill="#6E6754"/></svg> &lrm;−1</span>
				<span class="phsg-chip"><svg viewBox="0 0 100 110" width="18" height="20"><path d="M7 24 Q50 2 93 24 L88 35 Q50 17 12 35 Z" fill="#6B5138" stroke="#2D2A26" stroke-width="5"/><path d="M12 35 Q50 17 88 35 L55 100 Q50 108 45 100 Z" fill="#8A6B45" stroke="#2D2A26" stroke-width="5"/></svg> &lrm;−2</span>
				<span class="phsg-chip"><svg viewBox="0 0 60 60" width="17" height="17"><circle cx="30" cy="32" r="22" fill="#FFE49A" stroke="#2D2A26" stroke-width="5"/><path d="M30 32 L30 21 M30 32 L38 36" stroke="#F32735" stroke-width="5" stroke-linecap="round"/></svg> &lrm;<?php esc_html_e( "+5 שנ'", 'pizza-hut-slice-game' ); ?></span>
				<span class="phsg-chip"><svg viewBox="0 0 60 60" width="17" height="17"><path d="M14 46 Q10 30 24 20 Q38 10 48 16 Q52 30 40 42 Q28 54 14 46 Z" fill="#F32735" stroke="#2D2A26" stroke-width="5"/><path d="M46 17 Q50 8 58 8" fill="none" stroke="#4C7B3A" stroke-width="6" stroke-linecap="round"/></svg> ×2</span>
				<span class="phsg-chip phsg-chip--gold"><svg viewBox="0 0 64 56" width="18" height="16"><path d="M4 46 L32 6 Q34 3 37 5 L60 46 Q62 50 57 50 L7 50 Q2 50 4 46 Z" fill="#FFD95C" stroke="#2D2A26" stroke-width="5"/><circle cx="24" cy="38" r="5" fill="#FFF3B0"/><circle cx="41" cy="41" r="4" fill="#FFF3B0"/></svg> &lrm;+2</span>
				<span class="phsg-chip"><?php esc_html_e( 'רצף 5 = +2', 'pizza-hut-slice-game' ); ?></span>
			</div>
		</section>

		<?php // ===== מסך סיום + טבלת שיאים ===== ?>
		<section class="phsg-screen phsg-end" data-screen="end" hidden>
			<div class="phsg-confetti" data-confetti aria-hidden="true"></div>

			<div class="phsg-medal-wrap">
				<div class="phsg-medal-rays" aria-hidden="true"></div>
				<div class="phsg-medal" data-medal>
					<span class="phsg-medal__label"><?php esc_html_e( 'מקום', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-medal__rank" data-result="rank">—</span>
				</div>
			</div>

			<h2 class="phsg-h1 phsg-end__title" data-end-title><?php esc_html_e( 'כל הכבוד!', 'pizza-hut-slice-game' ); ?></h2>

			<div class="phsg-stats">
				<div class="phsg-stats__card phsg-stats__card--red">
					<div class="phsg-stats__label"><?php esc_html_e( 'ניקוד', 'pizza-hut-slice-game' ); ?></div>
					<div class="phsg-stats__value" data-result="score">0</div>
				</div>
				<div class="phsg-stats__card">
					<div class="phsg-stats__label"><?php esc_html_e( 'תגובה ממוצעת', 'pizza-hut-slice-game' ); ?></div>
					<div class="phsg-stats__value" data-result="avg">0.0</div>
				</div>
				<div class="phsg-stats__card phsg-stats__card--dark">
					<div class="phsg-stats__label"><?php esc_html_e( 'הרצף הכי ארוך', 'pizza-hut-slice-game' ); ?></div>
					<div class="phsg-stats__value" data-result="streak">0</div>
				</div>
			</div>

			<div class="phsg-board">
				<div class="phsg-board__head">
					<span><?php esc_html_e( 'טבלת השיאים', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-board__head-sub"><?php esc_html_e( 'מתעדכן בזמן אמת', 'pizza-hut-slice-game' ); ?></span>
				</div>
				<div data-leaderboard>
					<?php echo phsg_render_leaderboard_rows( $leaderboard ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>

			<?php // כרטיס קופון – פינוק לכל משתתף ?>
			<?php if ( ! empty( $atts['coupon_code'] ) ) : ?>
				<div class="phsg-coupon-card">
					<h3 class="phsg-coupon-card__title"><?php esc_html_e( 'עוד לא יודעים אם תזכו… 🤞', 'pizza-hut-slice-game' ); ?></h3>
					<p class="phsg-coupon-card__text"><?php esc_html_e( 'אבל בינתיים החלטנו לפנק אתכם בקוד קופון להזמנת פיצה עכשיו:', 'pizza-hut-slice-game' ); ?></p>
					<button type="button" class="phsg-coupon-code" data-action="copy-coupon" data-coupon-code="<?php echo esc_attr( $atts['coupon_code'] ); ?>" title="<?php echo esc_attr__( 'לחצו להעתקה', 'pizza-hut-slice-game' ); ?>">
						<?php echo esc_html( $atts['coupon_code'] ); ?> 📋
					</button>
					<a class="phsg-cta phsg-coupon-card__cta" href="<?php echo esc_url( $atts['coupon_url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'מזמינים פיצה עכשיו ‹', 'pizza-hut-slice-game' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<div class="phsg-end__actions">
				<button type="button" class="phsg-cta" data-action="play-again"><?php esc_html_e( 'עוד סיבוב ‹', 'pizza-hut-slice-game' ); ?></button>
				<button type="button" class="phsg-cta phsg-cta--cream" data-action="go-home"><?php esc_html_e( 'למסך הבית', 'pizza-hut-slice-game' ); ?></button>
			</div>
		</section>

		<?php // אינדיקטור טעינה בזמן שליחת התוצאה ?>
		<div class="phsg-loader" data-loader hidden aria-hidden="true">
			<div class="phsg-loader__spinner"></div>
		</div>

	</div>

	<?php // אבות-טיפוס למכשולים ולבונוסים – JS משכפל מכאן. ?>
	<div data-protos hidden aria-hidden="true">
		<span data-proto="mush"><?php echo phsg_svg_obstacle( 'mush' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="olive"><?php echo phsg_svg_obstacle( 'olive' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="onion"><?php echo phsg_svg_obstacle( 'onion' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="tomato"><?php echo phsg_svg_obstacle( 'tomato' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="burnt"><?php echo phsg_svg_obstacle( 'burnt' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="clock"><?php echo phsg_svg_bonus( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span data-proto="chili"><?php echo phsg_svg_bonus( 'chili' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
	</div>
</div>
