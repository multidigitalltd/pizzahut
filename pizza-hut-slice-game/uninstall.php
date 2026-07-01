<?php
/**
 * הסרת הפלאגין – ניקוי נתונים.
 *
 * מופעל אוטומטית ע"י WordPress בעת מחיקת הפלאגין.
 *
 * @package PizzaHutSliceGame
 */

// אבטחה: מופעל רק דרך תהליך ההסרה של WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// שם הטבלה (חייב להיות תואם לקבוע בקובץ הראשי).
$phsg_table = $wpdb->prefix . 'phsg_scores';

// מחיקת הטבלה. הערה: מכיוון שהנתונים כוללים פרטי לידים,
// שיקול דעת נדרש – כאן אנו מנקים לחלוטין בעת הסרה מלאה.
$wpdb->query( "DROP TABLE IF EXISTS {$phsg_table}" ); // phpcs:ignore WordPress.DB

// מחיקת אופציות.
delete_option( 'phsg_db_version' );
