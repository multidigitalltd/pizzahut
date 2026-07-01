<?php
/**
 * תבנית עמוד נחיתה מלאה – ללא header/footer של התבנית הפעילה.
 *
 * נבחרת בעורך העמודים תחת "Pizza Hut – דף נחיתה מלא".
 * מציגה את תוכן העמוד (כולל השורטקוד) על רקע ממותג במסך מלא.
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
		/* רקע נחיתה ממותג – קראפט כהה עם מרכוז הבמה. */
		body.phsg-landing-body {
			margin: 0;
			min-height: 100vh;
			background-color: #2D2A26;
			background-image:
				radial-gradient(circle at 20% 10%, rgba(243, 39, 53, 0.18), transparent 45%),
				radial-gradient(circle at 80% 90%, rgba(181, 137, 103, 0.22), transparent 45%);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px 12px;
			box-sizing: border-box;
		}
		body.phsg-landing-body .phsg-landing-content {
			width: 100%;
			max-width: 760px;
		}
		/* הסתרת אלמנטים גלובליים של תבניות/תוספים אחרים אם הוזרקו. */
		body.phsg-landing-body #wpadminbar { display: none; }
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
