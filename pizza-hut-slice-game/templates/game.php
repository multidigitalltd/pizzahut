<?php
/**
 * תבנית המשחק – מסכי פתיחה, טופס, משחק וסיום.
 *
 * משתנים זמינים מהשורטקוד:
 *
 * @var array $atts        תכונות השורטקוד (title, subtitle).
 * @var array $leaderboard טבלת מובילים ראשונית.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="phsg-app" dir="rtl" lang="he" role="application" aria-label="<?php echo esc_attr__( 'משחק פיצה האט', 'pizza-hut-slice-game' ); ?>">

	<?php // רקע תבנית טיפוגרפיה (חוזר) – חלק מהמיתוג. ?>
	<div class="phsg-pattern" aria-hidden="true">
		<?php
		$phrases = array( 'hothutpizza', 'Mozzarella', 'since 1990', "I'M HUT AND I KNOW IT" );
		$line    = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$line .= esc_html( $phrases[ $i % count( $phrases ) ] ) . ' &bull; ';
		}
		for ( $row = 0; $row < 14; $row++ ) {
			echo '<span class="phsg-pattern__row">' . $line . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>

	<div class="phsg-stage">

		<?php // ===== לוגו המותג – מוצג בכל המסכים ===== ?>
		<div class="phsg-brand">
			<?php if ( ! empty( $atts['logo'] ) ) : ?>
				<img class="phsg-brand__img" src="<?php echo esc_url( $atts['logo'] ); ?>" alt="Pizza Hut">
			<?php else : ?>
				<?php echo phsg_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</div>

		<?php // ===== מסך פתיחה ===== ?>
		<section class="phsg-screen phsg-screen--intro is-active" data-screen="intro">
			<div class="phsg-card">
				<div class="phsg-logo" aria-hidden="true">
					<?php echo phsg_slice_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<h1 class="phsg-title"><?php echo esc_html( $atts['title'] ); ?></h1>
				<p class="phsg-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
				<p class="phsg-lead">
					<?php esc_html_e( 'תפסו כמה שיותר משולשי פיצה תוך 60 שניות. המשולש בורח מהר – ורק נהיה קטן ומהיר יותר! זהב = 3 נקודות, ירוק = מלכודת שמורידה נקודה.', 'pizza-hut-slice-game' ); ?>
				</p>
				<?php if ( ! empty( $atts['daily_prize'] ) ) : ?>
					<p class="phsg-prize-banner">🏆 <?php echo esc_html( $atts['daily_prize'] ); ?></p>
				<?php endif; ?>
				<button type="button" class="phsg-btn phsg-btn--primary" data-action="go-form">
					<?php esc_html_e( 'יאללה, מתחילים', 'pizza-hut-slice-game' ); ?>
				</button>
			</div>
		</section>

		<?php // ===== טופס משתתף ===== ?>
		<section class="phsg-screen phsg-screen--form" data-screen="form" hidden>
			<div class="phsg-card">
				<h2 class="phsg-title phsg-title--sm"><?php esc_html_e( 'רגע לפני שמתחילים', 'pizza-hut-slice-game' ); ?></h2>
				<p class="phsg-lead"><?php esc_html_e( 'מלאו פרטים כדי להיכנס לטבלת המובילים.', 'pizza-hut-slice-game' ); ?></p>

				<form class="phsg-form" novalidate>
					<div class="phsg-field">
						<label for="phsg-name"><?php esc_html_e( 'שם מלא', 'pizza-hut-slice-game' ); ?></label>
						<input type="text" id="phsg-name" name="full_name" autocomplete="name" required maxlength="120">
						<span class="phsg-field__error" data-error-for="full_name"></span>
					</div>

					<div class="phsg-field">
						<label for="phsg-phone"><?php esc_html_e( 'טלפון', 'pizza-hut-slice-game' ); ?></label>
						<input type="tel" id="phsg-phone" name="phone" autocomplete="tel" inputmode="numeric" required maxlength="15">
						<span class="phsg-field__error" data-error-for="phone"></span>
					</div>

					<div class="phsg-field">
						<label for="phsg-email"><?php esc_html_e( 'אימייל', 'pizza-hut-slice-game' ); ?></label>
						<input type="email" id="phsg-email" name="email" autocomplete="email" required maxlength="190">
						<span class="phsg-field__error" data-error-for="email"></span>
					</div>

					<div class="phsg-field phsg-field--check">
						<label>
							<input type="checkbox" id="phsg-consent" name="consent" value="1" required>
							<span><?php esc_html_e( 'אני מאשר/ת את תנאי ההשתתפות וקבלת דיוור שיווקי מפיצה האט.', 'pizza-hut-slice-game' ); ?></span>
						</label>
						<span class="phsg-field__error" data-error-for="consent"></span>
					</div>

					<button type="submit" class="phsg-btn phsg-btn--primary" data-action="start-game">
						<?php esc_html_e( 'שחקו עכשיו', 'pizza-hut-slice-game' ); ?>
					</button>
				</form>
			</div>
		</section>

		<?php // ===== מסך משחק ===== ?>
		<section class="phsg-screen phsg-screen--game" data-screen="game" hidden>
			<div class="phsg-hud">
				<div class="phsg-hud__item">
					<span class="phsg-hud__label"><?php esc_html_e( 'ניקוד', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-hud__value" data-hud="score">0</span>
				</div>
				<div class="phsg-hud__item phsg-hud__item--time">
					<span class="phsg-hud__label"><?php esc_html_e( 'זמן', 'pizza-hut-slice-game' ); ?></span>
					<span class="phsg-hud__value" data-hud="time">60</span>
				</div>
				<button type="button" class="phsg-sound" data-action="toggle-sound" aria-pressed="false" aria-label="<?php echo esc_attr__( 'הפעלה/השתקה של צלילים', 'pizza-hut-slice-game' ); ?>">🔊</button>
			</div>
			<?php // בר זמן מלחיץ – מתרוקן לאורך המשחק. ?>
			<div class="phsg-timebar" aria-hidden="true">
				<div class="phsg-timebar__fill" data-timebar></div>
			</div>
			<div class="phsg-arena" data-arena tabindex="0" aria-label="<?php echo esc_attr__( 'אזור משחק – לחצו על משולש הפיצה', 'pizza-hut-slice-game' ); ?>">
				<button type="button" class="phsg-slice" data-slice data-type="normal" hidden aria-label="<?php echo esc_attr__( 'משולש פיצה – לחצו!', 'pizza-hut-slice-game' ); ?>">
					<span class="phsg-slice__skin phsg-slice__skin--normal"><?php echo phsg_slice_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="phsg-slice__skin phsg-slice__skin--gold"><?php echo phsg_slice_svg( 'gold' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="phsg-slice__skin phsg-slice__skin--trap"><?php echo phsg_slice_svg( 'trap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</button>
				<?php // שכבת אפקטים (חלקיקים, גלי הדף, טקסט מרחף) – ללא אינטראקציה. ?>
				<div class="phsg-fx" data-fx aria-hidden="true"></div>
				<?php // תג קומבו ?>
				<div class="phsg-combo" data-combo hidden aria-hidden="true"></div>
				<?php // ספירה לאחור לפני תחילת סיבוב ?>
				<div class="phsg-countdown" data-countdown hidden aria-hidden="true">
					<span class="phsg-countdown__num" data-countdown-num>3</span>
				</div>
			</div>
		</section>

		<?php // ===== מסך סיום ===== ?>
		<section class="phsg-screen phsg-screen--end" data-screen="end" hidden>
			<?php // קונפטי חגיגי – מתמלא ב-JS. ?>
			<div class="phsg-confetti" data-confetti aria-hidden="true"></div>
			<div class="phsg-card">
				<div class="phsg-logo phsg-logo--sm" aria-hidden="true">
					<?php echo phsg_slice_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<h2 class="phsg-title phsg-title--sm"><?php esc_html_e( 'איזה כיף!', 'pizza-hut-slice-game' ); ?></h2>

				<div class="phsg-result">
					<div class="phsg-result__item">
						<span class="phsg-result__value" data-result="score">0</span>
						<span class="phsg-result__label"><?php esc_html_e( 'ניקוד', 'pizza-hut-slice-game' ); ?></span>
					</div>
					<div class="phsg-result__item">
						<span class="phsg-result__value" data-result="time">60</span>
						<span class="phsg-result__label"><?php esc_html_e( 'שניות', 'pizza-hut-slice-game' ); ?></span>
					</div>
					<div class="phsg-result__item">
						<span class="phsg-result__value" data-result="rank">–</span>
						<span class="phsg-result__label"><?php esc_html_e( 'דירוג', 'pizza-hut-slice-game' ); ?></span>
					</div>
				</div>

				<p class="phsg-result__msg" data-result="msg" role="status"></p>

				<?php // באנר שיאן היום – מוצג ב-JS כשהשחקן ראשון בלוח היומי. ?>
				<p class="phsg-daily-champ" data-daily-champ hidden></p>

				<?php // קופון – מוצג ב-JS כשעוברים את רף הניקוד. ?>
				<?php if ( ! empty( $atts['coupon_code'] ) ) : ?>
					<div class="phsg-coupon" data-coupon data-coupon-code="<?php echo esc_attr( $atts['coupon_code'] ); ?>" data-coupon-min="<?php echo esc_attr( (int) $atts['coupon_min'] ); ?>" hidden>
						<span class="phsg-coupon__label"><?php esc_html_e( 'מגיע לך! קוד הטבה:', 'pizza-hut-slice-game' ); ?></span>
						<button type="button" class="phsg-coupon__code" data-action="copy-coupon" title="<?php echo esc_attr__( 'לחצו להעתקה', 'pizza-hut-slice-game' ); ?>">
							<?php echo esc_html( $atts['coupon_code'] ); ?> 📋
						</button>
					</div>
				<?php endif; ?>

				<div class="phsg-actions">
					<button type="button" class="phsg-btn phsg-btn--primary" data-action="play-again">
						<?php esc_html_e( 'עוד סיבוב', 'pizza-hut-slice-game' ); ?>
					</button>
					<button type="button" class="phsg-btn phsg-btn--whatsapp" data-action="share-whatsapp">
						<?php esc_html_e( 'שתפו בוואטסאפ', 'pizza-hut-slice-game' ); ?> 💬
					</button>
				</div>
			</div>

			<?php // טבלת מובילים – יומי / כל הזמנים ?>
			<div class="phsg-card phsg-card--board">
				<h3 class="phsg-board__title"><?php esc_html_e( 'טבלת המובילים', 'pizza-hut-slice-game' ); ?></h3>
				<div class="phsg-board-tabs" role="tablist">
					<button type="button" class="phsg-board-tab is-active" data-action="board-daily" role="tab" aria-selected="true"><?php esc_html_e( 'היום', 'pizza-hut-slice-game' ); ?></button>
					<button type="button" class="phsg-board-tab" data-action="board-alltime" role="tab" aria-selected="false"><?php esc_html_e( 'כל הזמנים', 'pizza-hut-slice-game' ); ?></button>
				</div>
				<div class="phsg-board" data-leaderboard>
					<?php echo phsg_render_leaderboard_rows( $leaderboard ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</section>

		<?php // אינדיקטור טעינה ?>
		<div class="phsg-loader" data-loader hidden aria-hidden="true">
			<div class="phsg-loader__spinner"></div>
		</div>

	</div>
</div>
