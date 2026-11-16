<?php
/**
 * The opt-in gate.
 *
 * Trac #65116 comment:75 made avoiding algorithmic cruelty a blocker, and a
 * widget that shows you ten years of your own posts before asking is the
 * scenario that was called one. So nothing is queried or rendered until the
 * invitation has been accepted.
 *
 * The invitation is only offered on a day that has something behind it.
 * Asking, being told yes, and then showing nothing would be a worse first
 * impression than staying quiet. Counting posts is not showing them, so
 * this does not give away anything before the question is answered.
 *
 * In this prototype the answer is deliberately NOT remembered: the state
 * lives in the URL and is gone on the next plain load of the dashboard, so
 * the gate can be seen and felt on every visit. In the real feature the
 * choice would of course persist, and only the storage changes.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current view has accepted the invitation.
 *
 * @return bool
 */
function otd_has_opted_in() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display state only, nothing is stored.
	return isset( $_GET['otd_optin'] ) && '1' === $_GET['otd_optin'];
}

/**
 * Whether the current view has declined the invitation.
 *
 * @return bool
 */
function otd_has_opted_out() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display state only, nothing is stored.
	return isset( $_GET['otd_optin'] ) && '0' === $_GET['otd_optin'];
}

/**
 * A dashboard URL carrying an opt-in answer.
 *
 * No nonce: these change nothing, they only pick which state to render.
 *
 * @param string $answer '1', '0', or '' to return to the invitation.
 * @return string
 */
function otd_optin_url( $answer ) {
	$args = array();

	if ( '' !== $answer ) {
		$args['otd_optin'] = $answer;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display override.
	if ( isset( $_GET['otd_date'] ) ) {
		$args['otd_date'] = rawurlencode( sanitize_text_field( wp_unslash( $_GET['otd_date'] ) ) );
	}

	return add_query_arg( $args, admin_url( 'index.php' ) );
}

/**
 * Renders the invitation.
 *
 * Wording and buttons follow the opt-in mock already shared on the ticket,
 * which drew no objection. Both buttons are secondary: the question is a
 * genuine one, so neither answer is presented as the expected one.
 */
function otd_render_optin_gate() {
	echo '<div class="otd-optin">';

	printf(
		'<p class="otd-optin-text">%s</p>',
		esc_html__( 'Would you like to see posts from previous years here? You can change this later in the Screen Options tab.', 'on-this-day' )
	);

	echo '<p class="otd-optin-actions">';

	printf(
		'<a class="button button-compact" href="%1$s">%2$s</a> <a class="button button-compact" href="%3$s">%4$s</a>',
		esc_url( otd_optin_url( '1' ) ),
		esc_html__( 'Keep it', 'on-this-day' ),
		esc_url( otd_optin_url( '0' ) ),
		esc_html__( 'Turn off', 'on-this-day' )
	);

	echo '</p>';
	echo '</div>';
}

/**
 * Renders the state after the invitation has been declined.
 */
function otd_render_declined() {
	echo '<div class="otd-optin otd-optin-declined">';

	printf(
		'<p class="otd-optin-text">%s</p>',
		esc_html__( 'On This Day is off. You can turn it back on in Screen Options.', 'on-this-day' )
	);

	printf(
		'<p class="otd-controls-note"><a href="%1$s">%2$s</a></p>',
		esc_url( otd_optin_url( '' ) ),
		esc_html__( 'Show the invitation again', 'on-this-day' )
	);

	echo '</div>';
}
