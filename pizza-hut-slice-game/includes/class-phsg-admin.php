<?php
/**
 * מסך ניהול – צפייה בתוצאות ובלידים וייצוא CSV.
 *
 * @package PizzaHutSliceGame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * מחלקת PHSG_Admin – ממשק ניהול למנהלי הקמפיין.
 */
class PHSG_Admin {

	const CAPABILITY = 'manage_options';
	const MENU_SLUG  = 'phsg-scores';

	/**
	 * רישום ה-hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_phsg_export_csv', array( $this, 'export_csv' ) );
	}

	/**
	 * הוספת פריט תפריט.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Pizza Hut – תוצאות המשחק', 'pizza-hut-slice-game' ),
			__( 'משחק פיצה האט', 'pizza-hut-slice-game' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-star-filled',
			26
		);
	}

	/**
	 * רינדור מסך התוצאות.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'אין לך הרשאה לצפות בעמוד זה.', 'pizza-hut-slice-game' ) );
		}

		global $wpdb;
		$table = PHSG_DB::table_name();

		// עימוד בסיסי.
		$per_page = 30;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$offset   = ( $paged - 1 ) * $per_page;

		$total = PHSG_DB::total_players();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, full_name, phone, email, score, clicks, duration, avg_reaction,
						utm_source, utm_medium, utm_campaign, created_at
				 FROM {$table}
				 ORDER BY score DESC, avg_reaction ASC, created_at ASC
				 LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB
				$per_page,
				$offset
			)
		);

		$export_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=phsg_export_csv' ),
			'phsg_export_csv'
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Pizza Hut – תוצאות המשחק', 'pizza-hut-slice-game' ) . '</h1>';
		echo '<p>' . sprintf(
			/* translators: %d: total participants. */
			esc_html__( 'סה"כ משתתפים: %d', 'pizza-hut-slice-game' ),
			(int) $total
		) . '</p>';
		echo '<p><a href="' . esc_url( $export_url ) . '" class="button button-primary">' .
			esc_html__( 'ייצוא CSV', 'pizza-hut-slice-game' ) . '</a></p>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>#</th><th>' . esc_html__( 'שם', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'טלפון', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'אימייל', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'ניקוד', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'לחיצות', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'משך', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'תגובה ממוצעת (מ"ש)', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>UTM</th>';
		echo '<th>' . esc_html__( 'תאריך', 'pizza-hut-slice-game' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="10">' . esc_html__( 'אין עדיין תוצאות.', 'pizza-hut-slice-game' ) . '</td></tr>';
		} else {
			foreach ( $rows as $r ) {
				$utm = array_filter( array( $r->utm_source, $r->utm_medium, $r->utm_campaign ) );
				echo '<tr>';
				echo '<td>' . esc_html( $r->id ) . '</td>';
				echo '<td>' . esc_html( $r->full_name ) . '</td>';
				echo '<td>' . esc_html( $r->phone ) . '</td>';
				echo '<td>' . esc_html( $r->email ) . '</td>';
				echo '<td>' . esc_html( $r->score ) . '</td>';
				echo '<td>' . esc_html( $r->clicks ) . '</td>';
				echo '<td>' . esc_html( $r->duration ) . '</td>';
				echo '<td>' . esc_html( $r->avg_reaction ) . '</td>';
				echo '<td>' . esc_html( implode( ' / ', $utm ) ) . '</td>';
				echo '<td>' . esc_html( $r->created_at ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';

		// עימוד.
		$total_pages = (int) ceil( $total / $per_page );
		if ( $total_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $total_pages,
					)
				)
			);
			echo '</div></div>';
		}

		echo '</div>';
	}

	/**
	 * ייצוא כל התוצאות ל-CSV.
	 *
	 * @return void
	 */
	public function export_csv() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'אין לך הרשאה.', 'pizza-hut-slice-game' ) );
		}
		check_admin_referer( 'phsg_export_csv' );

		global $wpdb;
		$table = PHSG_DB::table_name();

		$rows = $wpdb->get_results(
			"SELECT id, full_name, phone, email, consent, score, clicks, duration, avg_reaction,
					utm_source, utm_medium, utm_campaign, utm_term, utm_content, created_at
			 FROM {$table}
			 ORDER BY score DESC, avg_reaction ASC, created_at ASC", // phpcs:ignore WordPress.DB
			ARRAY_A
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pizza-hut-scores-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		// BOM לתמיכה בעברית ב-Excel.
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			array(
				'ID', 'Full Name', 'Phone', 'Email', 'Consent', 'Score', 'Clicks', 'Duration',
				'Avg Reaction (ms)', 'UTM Source', 'UTM Medium', 'UTM Campaign',
				'UTM Term', 'UTM Content', 'Created At',
			)
		);

		if ( $rows ) {
			foreach ( $rows as $r ) {
				fputcsv( $output, array_map( array( $this, 'neutralize_csv_value' ), $r ) );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * נטרול הזרקת נוסחאות ב-CSV (CSV Formula Injection).
	 *
	 * ערכים בשליטת משתמש (שם, UTM) שמתחילים ב-=, +, -, @ או תווי בקרה
	 * עלולים להתפרש כנוסחה ב-Excel/Sheets. מוסיפים גרש מוביל לנטרול.
	 *
	 * @param mixed $value ערך התא.
	 * @return mixed
	 */
	private function neutralize_csv_value( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		if ( preg_match( '/^[=+\-@\t\r]/', $value ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
