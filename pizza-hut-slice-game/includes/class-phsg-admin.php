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
	 * מיפוי מקור (utm_source) לתווית קמפיין קריאה.
	 *
	 * @param string $source utm_source.
	 * @return string תווית מקור בעברית.
	 */
	public static function source_label( $source ) {
		$s = strtolower( trim( (string) $source ) );

		$map = array(
			'jdn'   => __( 'אתר JDN', 'pizza-hut-slice-game' ),
			'prog'  => __( 'אתר פרוג', 'pizza-hut-slice-game' ),
			'kikar' => __( 'כיכר השבת', 'pizza-hut-slice-game' ),
		);

		if ( isset( $map[ $s ] ) ) {
			return $map[ $s ];
		}
		if ( '' === $s ) {
			return __( 'ישיר / לא ידוע', 'pizza-hut-slice-game' );
		}
		return $source;
	}

	/**
	 * רישום ה-hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_phsg_export_csv', array( $this, 'export_csv' ) );
		add_action( 'admin_post_phsg_export_winners', array( $this, 'export_winners' ) );
	}

	/**
	 * דירוג המשתתפים – השיא הטוב ביותר לכל משתתף (dedup לפי אימייל), בסדר הזכייה.
	 *
	 * @param int $limit מספר משתתפים מרבי להחזרה.
	 * @return array מערך שורות ARRAY_A מדורגות.
	 */
	public static function get_ranked_participants( $limit = 100 ) {
		global $wpdb;
		$table = PHSG_DB::table_name();
		$limit = max( 1, min( 1000, (int) $limit ) );
		$fetch = min( 10000, $limit * 50 );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, full_name, phone, email, score, clicks, duration, avg_reaction, utm_source, created_at
				 FROM {$table}
				 ORDER BY score DESC, avg_reaction ASC, created_at ASC, id ASC
				 LIMIT %d", // phpcs:ignore WordPress.DB
				$fetch
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$seen = array();
		$out  = array();
		foreach ( $rows as $r ) {
			$key = strtolower( (string) $r['email'] );
			if ( '' === $key ) {
				$key = 'id:' . $r['id']; // ללא אימייל – כל רשומה ייחודית.
			}
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $r;
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
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

		// סינון לפי מקור (utm_source) – מהקישור בדשבורד.
		$source_filter = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// פילוח לפי מקור – ספירת לידים לכל utm_source.
		$breakdown = $wpdb->get_results(
			"SELECT utm_source AS src, COUNT(*) AS cnt
			 FROM {$table}
			 GROUP BY utm_source
			 ORDER BY cnt DESC", // phpcs:ignore WordPress.DB
			ARRAY_A
		);

		$total = PHSG_DB::total_players();

		// עימוד בסיסי.
		$per_page = 30;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$offset   = ( $paged - 1 ) * $per_page;

		// WHERE לפי מקור (אם נבחר).
		$where     = '';
		$where_cnt = $total;
		if ( '' !== $source_filter ) {
			$where     = $wpdb->prepare( ' WHERE utm_source = %s', $source_filter );
			$where_cnt = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" . $where ); // phpcs:ignore WordPress.DB
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, full_name, phone, email, score, clicks, duration, avg_reaction,
						utm_source, utm_medium, utm_campaign, created_at
				 FROM {$table}{$where}
				 ORDER BY score DESC, avg_reaction ASC, created_at ASC
				 LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB
				$per_page,
				$offset
			)
		);

		$base_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Pizza Hut – תוצאות המשחק', 'pizza-hut-slice-game' ) . '</h1>';
		echo '<p>' . sprintf(
			/* translators: %d: total participants. */
			esc_html__( 'סה"כ משתתפים: %d', 'pizza-hut-slice-game' ),
			(int) $total
		) . '</p>';

		// ===== דירוג משתתפים / זוכים =====
		$ranked        = self::get_ranked_participants( 100 );
		$winners_url   = wp_nonce_url(
			admin_url( 'admin-post.php?action=phsg_export_winners' ),
			'phsg_export_winners'
		);

		echo '<h2>' . esc_html__( 'דירוג משתתפים (רשימת זוכים)', 'pizza-hut-slice-game' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'דירוג לפי הניקוד הטוב ביותר של כל משתתף. שובר-שוויון: זמן תגובה ממוצע קצר יותר ואז ההגשה המוקדמת. 3 המקומות הראשונים מודגשים.', 'pizza-hut-slice-game' ) . '</p>';
		echo '<p><a href="' . esc_url( $winners_url ) . '" class="button button-primary">' .
			esc_html__( 'ייצוא דירוג/זוכים ל-CSV', 'pizza-hut-slice-game' ) . '</a></p>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'מקום', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'שם', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'טלפון', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'אימייל', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'מקור', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'ניקוד', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'תגובה ממוצעת (מ"ש)', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'תאריך', 'pizza-hut-slice-game' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $ranked ) ) {
			echo '<tr><td colspan="8">' . esc_html__( 'אין עדיין משתתפים.', 'pizza-hut-slice-game' ) . '</td></tr>';
		} else {
			$rank = 0;
			foreach ( $ranked as $r ) {
				$rank++;
				$medal = 1 === $rank ? '🥇 ' : ( 2 === $rank ? '🥈 ' : ( 3 === $rank ? '🥉 ' : '' ) );
				$style = $rank <= 3 ? ' style="background:#fff6d5;font-weight:bold"' : '';
				echo '<tr' . $style . '>';
				echo '<td>' . $medal . (int) $rank . '</td>';
				echo '<td>' . esc_html( $r['full_name'] ) . '</td>';
				echo '<td>' . esc_html( $r['phone'] ) . '</td>';
				echo '<td>' . esc_html( $r['email'] ) . '</td>';
				echo '<td>' . esc_html( self::source_label( $r['utm_source'] ) ) . '</td>';
				echo '<td>' . esc_html( $r['score'] ) . '</td>';
				echo '<td>' . esc_html( $r['avg_reaction'] ) . '</td>';
				echo '<td>' . esc_html( $r['created_at'] ) . '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody></table>';

		// ===== פילוח לידים לפי מקור =====
		echo '<h2>' . esc_html__( 'פילוח לפי מקור', 'pizza-hut-slice-game' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:640px">';
		echo '<thead><tr><th>' . esc_html__( 'מקור', 'pizza-hut-slice-game' ) . '</th><th>utm_source</th><th>' .
			esc_html__( 'כמות לידים', 'pizza-hut-slice-game' ) . '</th><th>' . esc_html__( 'פעולה', 'pizza-hut-slice-game' ) . '</th></tr></thead><tbody>';

		$all_active = '' === $source_filter ? ' style="font-weight:bold"' : '';
		echo '<tr' . $all_active . '><td>' . esc_html__( 'כל המקורות', 'pizza-hut-slice-game' ) . '</td><td>—</td><td>' .
			(int) $total . '</td><td><a href="' . esc_url( $base_url ) . '">' . esc_html__( 'הצג הכל', 'pizza-hut-slice-game' ) . '</a></td></tr>';

		if ( ! empty( $breakdown ) ) {
			foreach ( $breakdown as $b ) {
				$src        = (string) $b['src'];
				$is_active  = ( $src === $source_filter ) ? ' style="font-weight:bold"' : '';
				$filter_url = add_query_arg( 'source', rawurlencode( $src ), $base_url );
				$export_src = wp_nonce_url(
					admin_url( 'admin-post.php?action=phsg_export_csv&source=' . rawurlencode( $src ) ),
					'phsg_export_csv'
				);
				echo '<tr' . $is_active . '>';
				echo '<td>' . esc_html( self::source_label( $src ) ) . '</td>';
				echo '<td>' . ( '' === $src ? '—' : esc_html( $src ) ) . '</td>';
				echo '<td>' . (int) $b['cnt'] . '</td>';
				echo '<td><a href="' . esc_url( $filter_url ) . '">' . esc_html__( 'סנן', 'pizza-hut-slice-game' ) . '</a> · ' .
					'<a href="' . esc_url( $export_src ) . '">' . esc_html__( 'ייצוא CSV', 'pizza-hut-slice-game' ) . '</a></td>';
				echo '</tr>';
			}
		}
		echo '</tbody></table>';

		// ===== רשימת הלידים =====
		$export_all = wp_nonce_url(
			admin_url( 'admin-post.php?action=phsg_export_csv' ),
			'phsg_export_csv'
		);
		$export_filtered = '' === $source_filter ? $export_all : wp_nonce_url(
			admin_url( 'admin-post.php?action=phsg_export_csv&source=' . rawurlencode( $source_filter ) ),
			'phsg_export_csv'
		);

		echo '<h2 style="margin-top:24px">' . esc_html__( 'לידים', 'pizza-hut-slice-game' );
		if ( '' !== $source_filter ) {
			echo ' — ' . esc_html( self::source_label( $source_filter ) ) . ' (' . (int) $where_cnt . ')';
		}
		echo '</h2>';
		echo '<p><a href="' . esc_url( $export_filtered ) . '" class="button button-primary">' .
			esc_html__( 'ייצוא CSV', 'pizza-hut-slice-game' ) . ( '' !== $source_filter ? ' (' . esc_html( self::source_label( $source_filter ) ) . ')' : '' ) . '</a></p>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>#</th><th>' . esc_html__( 'שם', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'טלפון', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'אימייל', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'מקור', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'ניקוד', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'לחיצות', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'משך', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>' . esc_html__( 'תגובה ממוצעת (מ"ש)', 'pizza-hut-slice-game' ) . '</th>';
		echo '<th>UTM</th>';
		echo '<th>' . esc_html__( 'תאריך', 'pizza-hut-slice-game' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="11">' . esc_html__( 'אין עדיין תוצאות.', 'pizza-hut-slice-game' ) . '</td></tr>';
		} else {
			foreach ( $rows as $r ) {
				$utm = array_filter( array( $r->utm_source, $r->utm_medium, $r->utm_campaign ) );
				echo '<tr>';
				echo '<td>' . esc_html( $r->id ) . '</td>';
				echo '<td>' . esc_html( $r->full_name ) . '</td>';
				echo '<td>' . esc_html( $r->phone ) . '</td>';
				echo '<td>' . esc_html( $r->email ) . '</td>';
				echo '<td><strong>' . esc_html( self::source_label( $r->utm_source ) ) . '</strong></td>';
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
		$total_pages = (int) ceil( $where_cnt / $per_page );
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

		// סינון אופציונלי לפי מקור (utm_source).
		$source_filter = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
		$where         = '';
		if ( '' !== $source_filter ) {
			$where = $wpdb->prepare( ' WHERE utm_source = %s', $source_filter );
		}

		$rows = $wpdb->get_results(
			"SELECT id, full_name, phone, email, consent, score, clicks, duration, avg_reaction,
					utm_source, utm_medium, utm_campaign, utm_term, utm_content, created_at
			 FROM {$table}{$where}
			 ORDER BY score DESC, avg_reaction ASC, created_at ASC", // phpcs:ignore WordPress.DB
			ARRAY_A
		);

		$suffix = '' !== $source_filter ? '-' . sanitize_file_name( $source_filter ) : '';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pizza-hut-leads' . $suffix . '-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		// BOM לתמיכה בעברית ב-Excel.
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			array(
				'ID', 'Full Name', 'Phone', 'Email', 'Consent', 'Source', 'Score', 'Clicks', 'Duration',
				'Avg Reaction (ms)', 'UTM Source', 'UTM Medium', 'UTM Campaign',
				'UTM Term', 'UTM Content', 'Created At',
			)
		);

		if ( $rows ) {
			foreach ( $rows as $r ) {
				// הוספת תווית מקור קריאה מיד לאחר Consent.
				$out = array(
					$r['id'], $r['full_name'], $r['phone'], $r['email'], $r['consent'],
					self::source_label( $r['utm_source'] ),
					$r['score'], $r['clicks'], $r['duration'], $r['avg_reaction'],
					$r['utm_source'], $r['utm_medium'], $r['utm_campaign'],
					$r['utm_term'], $r['utm_content'], $r['created_at'],
				);
				fputcsv( $output, array_map( array( $this, 'neutralize_csv_value' ), $out ) );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * ייצוא דירוג המשתתפים (זוכים) ל-CSV – שיא לכל משתתף, ממוספר לפי מקום.
	 *
	 * @return void
	 */
	public function export_winners() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'אין לך הרשאה.', 'pizza-hut-slice-game' ) );
		}
		check_admin_referer( 'phsg_export_winners' );

		$ranked = self::get_ranked_participants( 1000 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pizza-hut-winners-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			array(
				'Rank', 'Full Name', 'Phone', 'Email', 'Source', 'Score',
				'Avg Reaction (ms)', 'Duration', 'UTM Source', 'Created At',
			)
		);

		$rank = 0;
		foreach ( $ranked as $r ) {
			$rank++;
			$row = array(
				$rank, $r['full_name'], $r['phone'], $r['email'],
				self::source_label( $r['utm_source'] ), $r['score'],
				$r['avg_reaction'], $r['duration'], $r['utm_source'], $r['created_at'],
			);
			fputcsv( $output, array_map( array( $this, 'neutralize_csv_value' ), $row ) );
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
