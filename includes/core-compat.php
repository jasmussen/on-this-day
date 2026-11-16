<?php
/**
 * Stands the prototype next to the right version of core.
 *
 * WordPress 7.2 shipped an On This Day section inside the Activity widget,
 * which Trac #65116 comment:83 proposes to revert in favour of a dedicated
 * widget. While that revert is pending, both would render at once and the
 * dashboard would show the feature twice.
 *
 * This empties core's query so its section renders nothing, which is what a
 * reviewer needs to see: the proposed dedicated widget on its own, in the
 * dashboard the revert would leave behind.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Suppresses core's On This Day section inside the Activity widget.
 *
 * @param array $args Query arguments used by core.
 * @return array Arguments that match no posts.
 */
function otd_suppress_core_section( $args ) {
	/**
	 * Filters whether core's own On This Day section is suppressed.
	 *
	 * @param bool $suppress Whether to suppress it.
	 */
	if ( ! apply_filters( 'otd_suppress_core_section', true ) ) {
		return $args;
	}

	$args['post__in'] = array( 0 );

	return $args;
}
add_filter( 'wp_dashboard_on_this_day_query_args', 'otd_suppress_core_section' );
