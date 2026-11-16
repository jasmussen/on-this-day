<?php
/**
 * The scope switch and the restore link.
 *
 * There is no options menu. Comment:83's scope switch is a product control,
 * not a setting, so it sits in the widget where it can be seen; everything
 * else that was behind the clock icon has been removed rather than hidden.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds a URL that changes the viewing scope.
 *
 * @param string $scope 'mine' or 'all'.
 * @return string Nonced URL.
 */
function otd_scope_url( $scope ) {
	$url = add_query_arg( 'otd_scope', rawurlencode( $scope ), admin_url( 'index.php' ) );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display override.
	if ( isset( $_GET['otd_date'] ) ) {
		$url = add_query_arg( 'otd_date', rawurlencode( sanitize_text_field( wp_unslash( $_GET['otd_date'] ) ) ), $url );
	}

	if ( otd_has_opted_in() ) {
		$url = add_query_arg( 'otd_optin', '1', $url );
	}

	return wp_nonce_url( $url, 'otd_scope' );
}

/**
 * Builds a URL that restores every hidden post.
 *
 * @return string Nonced URL.
 */
function otd_restore_url() {
	$url = add_query_arg( 'otd_restore', '1', admin_url( 'index.php' ) );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display override.
	if ( isset( $_GET['otd_date'] ) ) {
		$url = add_query_arg( 'otd_date', rawurlencode( sanitize_text_field( wp_unslash( $_GET['otd_date'] ) ) ), $url );
	}

	if ( otd_has_opted_in() ) {
		$url = add_query_arg( 'otd_optin', '1', $url );
	}

	return wp_nonce_url( $url, 'otd_restore' );
}

/**
 * Applies a scope change or a restore requested from the widget.
 */
function otd_handle_requests() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$redirect = false;

	if ( isset( $_GET['otd_scope'] ) ) {
		check_admin_referer( 'otd_scope' );
		otd_set_pref( 'scope', sanitize_key( wp_unslash( $_GET['otd_scope'] ) ) );
		$redirect = true;
	} elseif ( isset( $_GET['otd_restore'] ) ) {
		check_admin_referer( 'otd_restore' );
		otd_restore_posts();
		$redirect = true;
	}

	if ( ! $redirect ) {
		return;
	}

	$url = admin_url( 'index.php' );

	if ( isset( $_GET['otd_date'] ) ) {
		$url = add_query_arg( 'otd_date', rawurlencode( sanitize_text_field( wp_unslash( $_GET['otd_date'] ) ) ), $url );
	}

	if ( otd_has_opted_in() ) {
		$url = add_query_arg( 'otd_optin', '1', $url );
	}

	wp_safe_redirect( $url );
	exit;
}
add_action( 'load-index.php', 'otd_handle_requests' );

/**
 * Renders the my/everyone switch.
 *
 * Uses core's own `.button-group` at `.button-small`, which is a 24px
 * segmented control that needs no new styles and reads as WordPress. Only
 * shown where it means something: a day more than one person published on.
 */
function otd_render_scope_switch() {
	if ( ! otd_should_show_scope_switch() ) {
		return;
	}

	$scope = otd_get_scope();

	$choices = array(
		'mine' => __( 'My posts', 'on-this-day' ),
		'all'  => __( 'Everyone', 'on-this-day' ),
	);

	echo '<div class="otd-scope">';
	echo '<div class="button-group button-small">';

	foreach ( $choices as $value => $label ) {
		$is_current = ( $value === $scope );

		printf(
			'<a class="button%1$s" href="%2$s" aria-pressed="%3$s">%4$s</a>',
			$is_current ? ' otd-scope-current' : '',
			esc_url( otd_scope_url( $value ) ),
			$is_current ? 'true' : 'false',
			esc_html( $label )
		);
	}

	echo '</div>';
	echo '</div>';
}

/**
 * Renders the restore link, when the current user has hidden anything.
 *
 * Deliberately without a count. Putting a number on the posts someone chose
 * not to see is its own small unkindness.
 */
function otd_render_restore_link() {
	if ( ! otd_get_hidden_posts() ) {
		return;
	}

	printf(
		'<p class="otd-restore-row"><a href="%1$s">%2$s</a></p>',
		esc_url( otd_restore_url() ),
		esc_html__( 'Restore hidden posts', 'on-this-day' )
	);
}
