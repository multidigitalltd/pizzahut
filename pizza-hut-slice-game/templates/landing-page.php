<?php
/**
 * תבנית עמוד נחיתה מלאה – ללא header/footer של התבנית הפעילה.
 *
 * נבחרת בעורך העמודים תחת "Pizza Hut – דף נחיתה מלא".
 * המשחק תופס את כל המסך, מקצה לקצה, בלי שום אלמנט של התבנית.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		/* עמוד נקי במסך מלא – המשחק ממלא את הכול. */
		html, body.phsg-landing-body {
			margin: 0 !important;
			padding: 0 !important;
			width: 100%;
			min-height: 100vh;
			background: #B58967;
		}
		body.phsg-landing-body .phsg-landing-content {
			width: 100%;
			margin: 0;
			padding: 0;
		}
		/* ביטול מגבלות רוחב/ריווח שהתבנית עלולה להזריק לתוכן. */
		body.phsg-landing-body .phsg-landing-content > * {
			max-width: none !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		body.phsg-landing-body .phsg-landing-content .phsg-app {
			min-height: 100vh;
			border-radius: 0;
		}
		/* הסתרת אלמנטים גלובליים של תבניות/תוספים אם הוזרקו. */
		body.phsg-landing-body #wpadminbar,
		body.phsg-landing-body header:not(.phsg-header),
		body.phsg-landing-body footer:not(.phsg-footer) { display: none !important; }
		html { margin-top: 0 !important; }
	</style>
</head>
<body <?php body_class( 'phsg-landing-body' ); ?>>
<?php wp_body_open(); ?>

<main class="phsg-landing-content" role="main">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>

<?php wp_footer(); ?>
</body>
</html>
