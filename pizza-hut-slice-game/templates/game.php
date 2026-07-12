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

$phsg_logo_url   = ! empty( $atts['logo'] ) ? $atts['logo'] : PHSG_PLUGIN_URL . 'assets/img/pizza-hut-logo.png';
$phsg_bg_url     = ! empty( $atts['bg'] ) ? $atts['bg'] : '';
// רשימת תמונות הפיצה הקופצות – עוקף/משלים את ברירת המחדל שב-PHSG_DATA.
$phsg_pizza_list = isset( $phsg_pizzas ) && ! empty( $phsg_pizzas ) ? $phsg_pizzas : array(
	PHSG_PLUGIN_URL . 'assets/img/pizza-slice-a.png',
	PHSG_PLUGIN_URL . 'assets/img/pizza-slice-b.png',
	PHSG_PLUGIN_URL . 'assets/img/pizza-tray-a.png',
	PHSG_PLUGIN_URL . 'assets/img/pizza-tray-b.png',
);
$phsg_hero_img   = $phsg_pizza_list[0];
$phsg_map_url    = isset( $atts['map'] ) && '' !== $atts['map'] ? $atts['map'] : '';

// ספרייטים אמיתיים – מכשולים ובונוסים (תמונות מוטמעות בתוסף).
$phsg_sprites = array(
	'onion'    => PHSG_PLUGIN_URL . 'assets/img/obstacle-onion.png',
	'tomato'   => PHSG_PLUGIN_URL . 'assets/img/obstacle-tomato.png',
	'mushroom' => PHSG_PLUGIN_URL . 'assets/img/obstacle-mushroom.png',
	'chili'    => PHSG_PLUGIN_URL . 'assets/img/obstacle-chili.png',
	'pin'      => PHSG_PLUGIN_URL . 'assets/img/bonus-pin.png',
	'box'      => PHSG_PLUGIN_URL . 'assets/img/bonus-box.png',
);
?>
<div class="phsg-app<?php echo $phsg_bg_url ? ' phsg-app--photo' : ''; ?>"<?php echo $phsg_bg_url ? ' style="background-image:url(' . esc_url( $phsg_bg_url ) . ');"' : ''; ?> dir="rtl" lang="he" role="application" data-fullscreen="<?php echo esc_attr( ! empty( $atts['fullscreen'] ) && '0' !== $atts['fullscreen'] ? '1' : '0' ); ?>" data-pizzas="<?php echo esc_attr( wp_json_encode( array_map( 'esc_url_raw', $phsg_pizza_list ) ) ); ?>" data-sprites="<?php echo esc_attr( wp_json_encode( array_map( 'esc_url_raw', $phsg_sprites ) ) ); ?>" aria-label="<?php echo esc_attr__( 'משחק פיצה האט – תפוס ת\'משולש', 'pizza-hut-slice-game' ); ?>">

	<?php // עומק – ויניטה ?>
	<div class="phsg-vignette" aria-hidden="true"></div>

	<div class="phsg-col">

		<?php // ===== כותרת עליונה ===== ?>
		<header class="phsg-header">
			<div class="phsg-header__brand">
				<img class="phsg-logo" src="<?php echo esc_url( $phsg_logo_url ); ?>" alt="Pizza Hut">
			</div>

			<?php // באנר שיווקי לרוחב הכותרת – בין הלוגו לאייקונים ?>
			<a class="phsg-promobanner" href="<?php echo esc_url( $atts['order_url'] ); ?>" target="_blank" rel="noopener">
				<span class="phsg-promobanner__shine" aria-hidden="true"></span>
				<span class="phsg-promobanner__text"><?php esc_html_e( 'פיצה חמה מחכה לכם כעת בסניפים', 'pizza-hut-slice-game' ); ?></span>
				<span class="phsg-promobanner__cta"><?php esc_html_e( 'להזמנה', 'pizza-hut-slice-game' ); ?></span>
			</a>

			<div class="phsg-header__actions">
				<button type="button" class="phsg-iconbtn" data-action="open-branches" aria-label="<?php echo esc_attr__( 'לסניפים שלנו', 'pizza-hut-slice-game' ); ?>" title="<?php echo esc_attr__( 'לסניפים שלנו', 'pizza-hut-slice-game' ); ?>">
					<?php echo phsg_svg_pin(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<button type="button" class="phsg-iconbtn phsg-sound" data-action="toggle-sound" aria-pressed="false" aria-label="<?php echo esc_attr__( 'הפעלה או השתקה של הצליל', 'pizza-hut-slice-game' ); ?>" title="<?php echo esc_attr__( 'צליל', 'pizza-hut-slice-game' ); ?>">
					<span class="phsg-sound__icon phsg-sound__icon--on"><?php echo phsg_svg_sound( true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="phsg-sound__icon phsg-sound__icon--off"><?php echo phsg_svg_sound( false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</button>
			</div>
		</header>

		<?php // ===== מסך טעינה – כמו משחק אמיתי ===== ?>
		<section class="phsg-screen phsg-boot is-active" data-screen="boot">
			<div class="phsg-boot__title phsg-title3d"><?php esc_html_e( "תפוס ת'משולש!", 'pizza-hut-slice-game' ); ?></div>
			<div class="phsg-boot__bar"><div class="phsg-boot__fill" data-boot-fill></div></div>
			<div class="phsg-boot__pct" data-boot-pct>0%</div>
		</section>

		<?php // ===== אינטרו ===== ?>
		<section class="phsg-screen phsg-intro" data-screen="intro" hidden>
			<div class="phsg-hero">
				<div class="phsg-hero__glow" aria-hidden="true"></div>
				<div class="phsg-hero__float">
					<img class="phsg-slice-img phsg-hero-img" src="<?php echo esc_url( $phsg_hero_img ); ?>" alt="">
				</div>
			</div>
			<h1 class="phsg-h1 phsg-title3d"><?php esc_html_e( "תפוס ת'משולש!", 'pizza-hut-slice-game' ); ?></h1>

			<?php // מסר המותג ?>
			<div class="phsg-promo">
				<p class="phsg-promo__text">
					<?php esc_html_e( 'פיצה האט מחכה לכם עם', 'pizza-hut-slice-game' ); ?>
					<strong class="phsg-promo__hot"><?php esc_html_e( 'למעלה מ-100 סניפים', 'pizza-hut-slice-game' ); ?></strong>
					<?php esc_html_e( 'בכל רחבי הארץ', 'pizza-hut-slice-game' ); ?><br>
					<?php esc_html_e( 'אפילו ממש כאן — על המסך 🍕', 'pizza-hut-slice-game' ); ?>
				</p>
			</div>

			<?php // שני כפתורים – הוראות והתחלה ?>
			<div class="phsg-intro__buttons">
				<button type="button" class="phsg-btn phsg-btn--ghost" data-action="toggle-instructions" aria-expanded="false" aria-controls="phsg-instructions">
					<span class="phsg-btn__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="7.7" r="1.05" fill="currentColor"/><path d="M12 11v6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span><?php esc_html_e( 'הוראות המשחק', 'pizza-hut-slice-game' ); ?>
				</button>
				<button type="button" class="phsg-btn phsg-btn--play" data-action="go-form">
					<?php esc_html_e( 'התחל משחק', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span>
				</button>
			</div>

			<?php // איזור ההוראות – מתקפל, נפתח בלחיצה על "הוראות המשחק" ?>
			<div class="phsg-instructions" id="phsg-instructions" data-instructions hidden>
				<div class="phsg-howto">
					<h2 class="phsg-howto__title"><?php esc_html_e( 'איך משחקים?', 'pizza-hut-slice-game' ); ?></h2>
					<p class="phsg-howto__text">
						<?php esc_html_e( 'בכל שלב מופיעים על המסך משולשי פיצות. תפסו אותם לפני שהם יעלמו.', 'pizza-hut-slice-game' ); ?><br>
						<?php esc_html_e( 'ככל שתתפסו אותם מהר יותר — תקבלו יותר נקודות.', 'pizza-hut-slice-game' ); ?><br>
						<?php esc_html_e( 'שימו לב למכשולים בדרך ואל תלחצו עליהם!', 'pizza-hut-slice-game' ); ?><br>
						<strong class="phsg-promo__hot"><?php esc_html_e( 'ניצחתם במשחק? קבלו מאיתנו ארוחה משפחתית ב-300 ₪ במתנה בכל סניף שתבחרו!', 'pizza-hut-slice-game' ); ?></strong>
					</p>
				</div>

				<div class="phsg-legend-row">
				<div class="phsg-legend-group phsg-legend-group--good">
					<span class="phsg-legend-title phsg-legend-title--good"><?php esc_html_e( '🏆 שווה נקודות — תתפסו!', 'pizza-hut-slice-game' ); ?></span>
					<div class="phsg-legend">
						<div class="phsg-legend__card">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_pizza_list[0] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'משולש פיצה', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--red">+1</span>
						</div>
						<div class="phsg-legend__card">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_pizza_list[2] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'מגש פיצה', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--red">+1</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--gold">
							<img class="phsg-legend__img phsg-legend__img--gold" src="<?php echo esc_url( $phsg_pizza_list[1] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'פיצת זהב', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--gold">+3</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--gold">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['box'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'קופסת פיצה', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--gold">+3</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--pin">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['pin'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'נקודת פיצה האט', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--red"><?php esc_html_e( 'בונוס +5!', 'pizza-hut-slice-game' ); ?></span>
						</div>
					</div>
				</div>

				<div class="phsg-legend-group phsg-legend-group--bad">
					<span class="phsg-legend-title phsg-legend-title--bad"><?php esc_html_e( '⚠️ מכשולים — אל תלחצו!', 'pizza-hut-slice-game' ); ?></span>
					<div class="phsg-legend phsg-legend--bad">
						<div class="phsg-legend__card phsg-legend__card--bad">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['onion'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'בצל', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--dark">−1</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--bad">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['tomato'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'עגבנייה', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--dark">−1</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--bad">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['mushroom'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'פטרייה', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--dark">−1</span>
						</div>
						<div class="phsg-legend__card phsg-legend__card--bad">
							<img class="phsg-legend__img" src="<?php echo esc_url( $phsg_sprites['chili'] ); ?>" alt="">
							<span class="phsg-legend__name"><?php esc_html_e( 'פלפל חריף', 'pizza-hut-slice-game' ); ?></span>
							<span class="phsg-vpill phsg-vpill--dark">−2</span>
						</div>
					</div>
				</div>
				</div><?php // סוף phsg-legend-row ?>

			</div><?php // סוף phsg-instructions ?>

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
				<button type="submit" class="phsg-cta"><?php esc_html_e( 'יאללה, למשחק', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span></button>
			</form>
		</section>

		<?php // ===== משחק (+ ספירה לאחור) ===== ?>
		<section class="phsg-screen phsg-game" data-screen="game" hidden>

			<div class="phsg-hud">
				<div class="phsg-hud__card phsg-hud__card--score">
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
					<span class="phsg-hud__target" data-hud="target">0/7</span>
				</div>
			</div>

			<div class="phsg-progress"><div class="phsg-progress__fill" data-progress></div></div>

			<div class="phsg-stage" data-stage>
				<?php // רקע הבמה – מפת ישראל אמיתית (זזה כדי להראות כל פעם אזור אחר) ?>
				<div class="phsg-stage__map" data-map aria-hidden="true"<?php echo $phsg_map_url ? ' style="background-image:url(' . esc_url( $phsg_map_url ) . ');"' : ''; ?>></div>
				<div class="phsg-stage__dots" data-mapdots aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>

				<?php // אבק קסם ?>
				<div class="phsg-stage__dust" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>

				<div class="phsg-stage__brand" aria-hidden="true"><?php esc_html_e( 'פיצה האט · בכל מקום בארץ 🍕', 'pizza-hut-slice-game' ); ?></div>

				<div class="phsg-combo" data-combo hidden></div>

				<?php // כפתור עצירה/השהיה – זמין בכל שלב ?>
				<button type="button" class="phsg-pausebtn" data-action="pause-game" aria-pressed="false" aria-label="<?php echo esc_attr__( 'עצור את המשחק', 'pizza-hut-slice-game' ); ?>" title="<?php echo esc_attr__( 'עצור', 'pizza-hut-slice-game' ); ?>">
					<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><rect x="6.5" y="5" width="4" height="14" rx="1.4" fill="currentColor"/><rect x="13.5" y="5" width="4" height="14" rx="1.4" fill="currentColor"/></svg>
					<span class="phsg-pausebtn__txt"><?php esc_html_e( 'עצור', 'pizza-hut-slice-game' ); ?></span>
				</button>

				<?php // שכבת השהיה ?>
				<div class="phsg-pause" data-pause hidden>
					<div class="phsg-pause__card">
						<span class="phsg-pause__icon" aria-hidden="true">⏸</span>
						<h2 class="phsg-pause__title"><?php esc_html_e( 'המשחק מושהה', 'pizza-hut-slice-game' ); ?></h2>
						<p class="phsg-pause__text"><?php esc_html_e( 'קחו נשימה — הזמן והמשולשים מחכים לכם.', 'pizza-hut-slice-game' ); ?></p>
						<button type="button" class="phsg-cta phsg-pause__resume" data-action="resume-game"><?php esc_html_e( 'המשך משחק', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span></button>
					</div>
				</div>

				<div data-slices></div>
				<div class="phsg-sprite phsg-sprite--slice" data-slice hidden>
					<div class="phsg-sprite__pop">
					<div class="phsg-sprite__wobble">
						<img class="phsg-slice-img" data-pizza-img src="<?php echo esc_url( $phsg_hero_img ); ?>" alt="">
					</div>
					</div>
					<span class="phsg-sparkle phsg-sparkle--a" data-gold-only hidden></span>
					<span class="phsg-sparkle phsg-sparkle--b" data-gold-only hidden></span>
				</div>

				<div data-obstacles></div>
				<div data-bonuses></div>
				<div data-popups></div>
				<div data-bursts aria-hidden="true"></div>

				<?php // תג פסילות – פגיעות במכשולים בשלב ?>
				<div class="phsg-strikes" data-strikes hidden></div>

				<div class="phsg-countdown" data-countdown hidden>
					<span class="phsg-countdown__num" data-countdown-num>3</span>
					<span class="phsg-countdown__sub"><?php esc_html_e( 'תתכוננו…', 'pizza-hut-slice-game' ); ?></span>
				</div>
			</div>

			<div class="phsg-chips">
					<span class="phsg-chip"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_pizza_list[0] ); ?>" alt=""> &lrm;+1</span>
					<span class="phsg-chip phsg-chip--gold"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_pizza_list[1] ); ?>" alt=""> &lrm;+3</span>
					<span class="phsg-chip phsg-chip--gold"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_sprites['box'] ); ?>" alt=""> &lrm;+3</span>
					<span class="phsg-chip phsg-chip--pin"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_sprites['pin'] ); ?>" alt=""> &lrm;+5</span>
					<span class="phsg-chip"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_sprites['onion'] ); ?>" alt=""> &lrm;−1</span>
					<span class="phsg-chip"><img class="phsg-chip__img" src="<?php echo esc_url( $phsg_sprites['chili'] ); ?>" alt=""> &lrm;−2</span>
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
					<span class="phsg-board__head-sub"><?php esc_html_e( 'משחק אחרון', 'pizza-hut-slice-game' ); ?></span>
				</div>
				<div data-leaderboard>
					<?php echo phsg_render_leaderboard_rows( $leaderboard ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>

			<?php // כרטיס קופון – פינוק לכל משתתף ?>
			<?php if ( ! empty( $atts['coupon_code'] ) ) : ?>
				<div class="phsg-coupon-card">
					<h3 class="phsg-coupon-card__title"><?php esc_html_e( 'עוד לא יודעים אם תזכו…', 'pizza-hut-slice-game' ); ?><span class="phsg-coupon-card__ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="19" height="19"><path d="M4 11h16v8.2a.8.8 0 0 1-.8.8H4.8a.8.8 0 0 1-.8-.8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3.2 8h17.6a.8.8 0 0 1 .8.8V11H2.4V8.8A.8.8 0 0 1 3.2 8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 8v12" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 8C11 5.4 9.6 4.6 8.4 5.1c-1.5.6-1 2.9 3.6 2.9" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 8c1-2.6 2.4-3.4 3.6-2.9 1.5.6 1 2.9-3.6 2.9" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span></h3>
					<p class="phsg-coupon-card__text"><?php esc_html_e( 'אבל בינתיים החלטנו לפנק אתכם בקוד קופון להזמנת פיצה עכשיו:', 'pizza-hut-slice-game' ); ?></p>
					<button type="button" class="phsg-coupon-code" data-action="copy-coupon" data-coupon-code="<?php echo esc_attr( $atts['coupon_code'] ); ?>" title="<?php echo esc_attr__( 'לחצו להעתקה', 'pizza-hut-slice-game' ); ?>">
						<?php echo esc_html( $atts['coupon_code'] ); ?> 📋
					</button>
					<p class="phsg-coupon-card__deal"><?php esc_html_e( 'בקניית 2 פיצות משפחתיות מקבלים מקלות שוקולד או בייגל שוקולד ב-10 ש"ח', 'pizza-hut-slice-game' ); ?></p>
					<a class="phsg-cta phsg-coupon-card__cta" href="<?php echo esc_url( $atts['coupon_url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'מזמינים פיצה עכשיו', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span>
					</a>
				</div>
			<?php endif; ?>

			<div class="phsg-end__actions">
				<button type="button" class="phsg-cta" data-action="play-again"><?php esc_html_e( 'עוד סיבוב', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span></button>
				<button type="button" class="phsg-cta phsg-cta--cream" data-action="go-home"><?php esc_html_e( 'למסך הבית', 'pizza-hut-slice-game' ); ?></button>
			</div>
		</section>

		<?php // חיווי שמירת התוצאה ?>
		<div class="phsg-loader" data-loader hidden role="status">
			<div class="phsg-loader__spinner"></div>
			<span class="phsg-loader__text"><?php esc_html_e( 'שומרים את התוצאה…', 'pizza-hut-slice-game' ); ?></span>
		</div>

	</div>

	<?php // חלון רשימת הסניפים ?>
	<div class="phsg-modal" data-branches-modal hidden>
		<div class="phsg-modal__backdrop" data-action="close-branches"></div>
		<div class="phsg-modal__box" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'רשימת החנויות שלנו', 'pizza-hut-slice-game' ); ?>">
			<div class="phsg-modal__head">
				<span class="phsg-modal__title"><span class="phsg-modal__title-ico" aria-hidden="true"><?php echo phsg_svg_pin(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php esc_html_e( 'רשימת החנויות שלנו', 'pizza-hut-slice-game' ); ?></span>
				<button type="button" class="phsg-modal__close" data-action="close-branches" aria-label="<?php echo esc_attr__( 'סגירה', 'pizza-hut-slice-game' ); ?>">✕</button>
			</div>
			<div class="phsg-modal__body">
				<?php echo phsg_render_branches( $atts['branches_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="phsg-modal__foot">
				<a class="phsg-cta phsg-modal__order" href="<?php echo esc_url( $atts['order_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'מזמינים פיצה עכשיו', 'pizza-hut-slice-game' ); ?><span class="phsg-arrow" aria-hidden="true">←</span></a>
			</div>
		</div>
	</div>
</div>
