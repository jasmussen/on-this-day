<?php
/**
 * Per-user preferences and hidden-post storage.
 *
 * Everything here is deliberately per-user: hiding a post from On This Day
 * is a personal act, and on a multi-author site it must not affect what
 * anybody else sees.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default preferences.
 *
 * Only the viewing scope is stored. The prototype's layout and empty-day
 * switches were removed once they had served their purpose.
 *
 * @return array
 */
function otd_default_prefs() {
	return array(
		'scope' => 'mine', // 'mine' | 'all'
	);
}

/**
 * Reads the current user's preferences, merged over the defaults.
 *
 * @return array
 */
function otd_get_prefs() {
	$stored = get_user_meta( get_current_user_id(), 'otd_prefs', true );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return wp_parse_args( $stored, otd_default_prefs() );
}

/**
 * Reads a single preference.
 *
 * @param string $key Preference name.
 * @return string Preference value, or the default when unknown.
 */
function otd_get_pref( $key ) {
	$prefs = otd_get_prefs();

	return isset( $prefs[ $key ] ) ? $prefs[ $key ] : '';
}

/**
 * Stores a single preference for the current user.
 *
 * Values are checked against a fixed allowlist, so an unexpected value from
 * the request can never reach the database.
 *
 * @param string $key   Preference name.
 * @param string $value Preference value.
 * @return bool True when the value was accepted and saved.
 */
function otd_set_pref( $key, $value ) {
	$allowed = array(
		'scope' => array( 'mine', 'all' ),
	);

	if ( ! isset( $allowed[ $key ] ) || ! in_array( $value, $allowed[ $key ], true ) ) {
		return false;
	}

	$prefs         = otd_get_prefs();
	$prefs[ $key ] = $value;

	update_user_meta( get_current_user_id(), 'otd_prefs', $prefs );

	return true;
}

/**
 * Post IDs the current user has hidden from their own On This Day.
 *
 * @return int[]
 */
function otd_get_hidden_posts() {
	$hidden = get_user_meta( get_current_user_id(), 'otd_hidden_posts', true );

	if ( ! is_array( $hidden ) ) {
		return array();
	}

	return array_values( array_unique( array_map( 'absint', $hidden ) ) );
}

/**
 * Hides a post from the current user's On This Day.
 *
 * @param int $post_id Post ID.
 * @return bool True when the post is now hidden.
 */
function otd_hide_post( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id || ! get_post( $post_id ) ) {
		return false;
	}

	$hidden = otd_get_hidden_posts();

	if ( ! in_array( $post_id, $hidden, true ) ) {
		$hidden[] = $post_id;
		update_user_meta( get_current_user_id(), 'otd_hidden_posts', $hidden );
	}

	return true;
}

/**
 * Restores a single hidden post, or all of them when no ID is given.
 *
 * @param int $post_id Optional. Post ID to restore. Default 0, meaning all.
 * @return bool True on success.
 */
function otd_restore_posts( $post_id = 0 ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		delete_user_meta( get_current_user_id(), 'otd_hidden_posts' );
		return true;
	}

	$hidden = array_diff( otd_get_hidden_posts(), array( $post_id ) );

	update_user_meta( get_current_user_id(), 'otd_hidden_posts', array_values( $hidden ) );

	return true;
}
