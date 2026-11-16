<?php
/**
 * Finding the posts published on this day in previous years.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * The date On This Day should look up, in the site's timezone.
 *
 * Accepts an `otd_date=MM-DD` query argument so that reviewers can look at a
 * day other than today without backdating posts. That override is a prototype
 * affordance and is gated on the capability needed to see the widget at all.
 *
 * @return array {
 *     @type int $month Month number, 1-12.
 *     @type int $day   Day of the month.
 *     @type int $year  The current year, which results must predate.
 * }
 */
function otd_get_date() {
	$now = current_datetime();

	$date = array(
		'month' => (int) $now->format( 'n' ),
		'day'   => (int) $now->format( 'j' ),
		'year'  => (int) $now->format( 'Y' ),
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display override, no state change.
	$override = isset( $_GET['otd_date'] ) ? sanitize_text_field( wp_unslash( $_GET['otd_date'] ) ) : '';

	if ( $override && current_user_can( 'edit_posts' ) && preg_match( '/^(\d{1,2})-(\d{1,2})$/', $override, $matches ) ) {
		$month = (int) $matches[1];
		$day   = (int) $matches[2];

		if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 ) {
			$date['month'] = $month;
			$date['day']   = $day;
		}
	}

	return $date;
}

/**
 * A readable label for the date being shown, e.g. "November 16th".
 *
 * @return string
 */
function otd_get_date_label() {
	$date = otd_get_date();

	$timestamp = mktime( 0, 0, 0, $date['month'], $date['day'], $date['year'] );

	// "November 16th" rather than "16 November": the widget's copy reads as a
	// sentence, and the inverted form trips over the words that follow it.
	return wp_date( _x( 'F jS', 'On This Day widget date format', 'on-this-day' ), $timestamp );
}

/**
 * Whether the switch between my posts and everyone's is worth offering.
 *
 * Comment:83 asks for it on sites with more than one user, but the useful
 * test is narrower: whether *this day* has posts by more than one person.
 * A newsroom of ten where only one person published on 15 July has nothing
 * to switch between, so most sites will rarely see the control — which is
 * the right outcome for a dashboard that should not grow chrome.
 *
 * @return bool
 */
function otd_should_show_scope_switch() {
	$authors = array();

	foreach ( otd_get_posts( 'all' ) as $post ) {
		$authors[ (int) $post->post_author ] = true;
	}

	/**
	 * Filters whether the my/everyone switch is offered.
	 *
	 * @param bool $show Whether more than one person published on this day.
	 */
	return (bool) apply_filters( 'otd_show_scope_switch', count( $authors ) > 1 );
}

/**
 * The stored viewing scope: 'mine' or 'all'.
 *
 * @return string
 */
function otd_get_scope() {
	return 'all' === otd_get_pref( 'scope' ) ? 'all' : 'mine';
}

/**
 * Posts published on this month and day in any previous year.
 *
 * @param string|null $scope Optional. 'mine' or 'all'. Default: the stored scope.
 * @return WP_Post[] Posts, newest first.
 */
function otd_get_posts( $scope = null ) {
	if ( null === $scope ) {
		$scope = otd_get_scope();
	}

	static $cache = array();

	if ( isset( $cache[ $scope ] ) ) {
		return $cache[ $scope ];
	}

	$date = otd_get_date();

	/**
	 * Filters how many posts On This Day shows.
	 *
	 * Defaults to -1, meaning every matching post. A site with a very long
	 * archive can cap it without turning the feature off.
	 *
	 * @param int $number Number of posts. -1 for all.
	 */
	$number = (int) apply_filters( 'otd_number_of_posts', -1 );

	$args = array(
		'post_type'           => apply_filters( 'otd_post_types', array( 'post' ) ),
		'post_status'         => 'publish',
		'posts_per_page'      => $number,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'post__not_in'        => otd_get_hidden_posts(),
		'date_query'          => array(
			'relation' => 'AND',
			array(
				'month' => $date['month'],
				'day'   => $date['day'],
			),
			array(
				'before' => array(
					'year'  => $date['year'],
					'month' => 1,
					'day'   => 1,
				),
			),
		),
	);

	if ( 'mine' === $scope ) {
		$args['author'] = get_current_user_id();
	}

	/**
	 * Filters the query arguments used to find On This Day posts.
	 *
	 * @param array $args Query arguments.
	 */
	$args = apply_filters( 'otd_query_args', $args );

	$query = new WP_Query( $args );

	$cache[ $scope ] = $query->posts;

	return $cache[ $scope ];
}

/**
 * Groups posts by the year they were published in.
 *
 * @param WP_Post[] $posts Posts to group.
 * @return array Year (int) => WP_Post[], newest year first.
 */
function otd_group_by_year( $posts ) {
	$grouped = array();

	foreach ( $posts as $post ) {
		$year = (int) get_the_date( 'Y', $post );

		if ( ! isset( $grouped[ $year ] ) ) {
			$grouped[ $year ] = array();
		}

		$grouped[ $year ][] = $post;
	}

	krsort( $grouped, SORT_NUMERIC );

	return $grouped;
}

/**
 * A relative label for a year, e.g. "10 years ago".
 *
 * This is the one piece of warmth the plain listing keeps: "10 years ago"
 * reads as a memory where a bare "2015" reads as an archive query.
 *
 * @param int $year Year the posts were published in.
 * @return string
 */
function otd_years_ago_label( $year ) {
	$date     = otd_get_date();
	$distance = max( 1, $date['year'] - (int) $year );

	return sprintf(
		/* translators: %s: Number of years. */
		_n( '%s year ago', '%s years ago', $distance, 'on-this-day' ),
		number_format_i18n( $distance )
	);
}
