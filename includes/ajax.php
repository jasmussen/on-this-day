<?php
/**
 * Hiding a post without a page reload.
 *
 * Hiding is the one interaction that needs to feel immediate and offer an
 * undo in place, so it goes over Ajax. Everything else in the widget is a
 * plain link.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hides a post from the current user's On This Day.
 */
function otd_ajax_hide_post() {
	check_ajax_referer( 'otd', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'on-this-day' ) ), 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

	if ( ! otd_hide_post( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'That post could not be hidden.', 'on-this-day' ) ), 400 );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_otd_hide_post', 'otd_ajax_hide_post' );

/**
 * Restores a single hidden post.
 */
function otd_ajax_restore_post() {
	check_ajax_referer( 'otd', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'on-this-day' ) ), 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

	otd_restore_posts( $post_id );

	wp_send_json_success();
}
add_action( 'wp_ajax_otd_restore_post', 'otd_ajax_restore_post' );
