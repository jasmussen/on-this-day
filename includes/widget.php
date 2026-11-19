<?php
/**
 * Registration and rendering of the On This Day dashboard widget.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the feature is switched on at all.
 *
 * A site that does not want On This Day anywhere can turn it off with one
 * filter, and the widget is then never registered.
 *
 * @return bool
 */
function otd_is_enabled() {
	/**
	 * Filters whether the On This Day widget is registered.
	 *
	 * @param bool $enabled Whether the feature is enabled.
	 */
	return (bool) apply_filters( 'otd_enabled', true );
}

/**
 * Registers the dashboard widget.
 *
 * The widget is registered even on days with nothing to show. That is
 * deliberate: the 7.1 implementation hid itself by not registering, which
 * removed it from Screen Options (so it could not be switched back on) and
 * lost its place in the drag-and-drop order. Registering always and hiding
 * the rendered container keeps both intact.
 */
function otd_register_dashboard_widget() {
	if ( ! otd_is_enabled() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'on_this_day',
		__( 'On This Day', 'on-this-day' ),
		'otd_render_widget'
	);
}
add_action( 'wp_dashboard_setup', 'otd_register_dashboard_widget' );

/**
 * Hides the widget's container on days with no posts.
 *
 * Printed as inline CSS rather than skipping registration, for the reasons
 * in otd_register_dashboard_widget().
 */
function otd_maybe_hide_empty_widget() {
	$screen = get_current_screen();

	if ( ! $screen || 'dashboard' !== $screen->id ) {
		return;
	}

	if ( ! otd_is_enabled() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( otd_get_posts() ) {
		return;
	}

	echo "<style id='otd-hide-empty'>#on_this_day { display: none; }</style>\n";
}
add_action( 'admin_head', 'otd_maybe_hide_empty_widget' );

/**
 * A cache-busting version for an asset, from its modified time.
 *
 * This is a prototype being iterated on with a reviewer watching, so a
 * stylesheet edit has to reach the browser on the next reload rather than
 * after a hard refresh. Production would use the release version.
 *
 * @param string $relative_path Path within the plugin.
 * @return string Version string.
 */
function otd_asset_version( $relative_path ) {
	$file = OTD_DIR . $relative_path;

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	return OTD_VERSION;
}

/**
 * Loads the widget's styles and script on the dashboard only.
 *
 * @param string $hook_suffix Current admin page.
 */
function otd_enqueue_assets( $hook_suffix ) {
	if ( 'index.php' !== $hook_suffix || ! otd_is_enabled() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_enqueue_style( 'on-this-day', OTD_URL . 'assets/on-this-day.css', array( 'dashicons' ), otd_asset_version( 'assets/on-this-day.css' ) );
	wp_enqueue_script( 'on-this-day', OTD_URL . 'assets/on-this-day.js', array(), otd_asset_version( 'assets/on-this-day.js' ), true );

	wp_localize_script(
		'on-this-day',
		'otdSettings',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'otd' ),
			'i18n'    => array(
				'hidden'  => __( 'Hidden from On This Day.', 'on-this-day' ),
				'undo'    => __( 'Undo', 'on-this-day' ),
				'failed'  => __( 'That did not work. Please reload and try again.', 'on-this-day' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'otd_enqueue_assets' );

/**
 * Renders the widget.
 */
function otd_render_widget() {
	echo '<div class="otd">';

	if ( otd_has_opted_out() ) {
		otd_render_declined();
		echo '</div>';
		return;
	}

	if ( 'opt-in' === otd_get_mode() && ! otd_has_opted_in() ) {
		otd_render_optin_gate();
		echo '</div>';
		return;
	}

	$posts = otd_get_posts();

	if ( ! $posts ) {
		otd_render_empty_state();
		echo '</div>';
		return;
	}

	otd_render_scope_switch();

	foreach ( otd_group_by_year( $posts ) as $year => $year_posts ) {
		otd_render_year( $year, $year_posts );
	}

	otd_render_restore_link();

	// In the opt-out prototype the question follows the posts, until it has
	// been answered.
	if ( ! otd_has_opted_in() ) {
		otd_render_optout_notice();
	}

	echo '</div>';
}

/**
 * Renders the empty state shown on days with no past posts.
 *
 * Only reached when the prototype is set to show an empty state rather than
 * hide the widget. Kept to one quiet line: an empty day is not an error.
 */
function otd_render_empty_state() {
	printf(
		'<p class="otd-empty">%s</p>',
		sprintf(
			/* translators: %s: Date, e.g. "16 November". */
			esc_html__( 'Nothing published on %s in previous years.', 'on-this-day' ),
			esc_html( otd_get_date_label() )
		)
	);
}

/**
 * Renders one year group.
 *
 * @param int       $year  Year.
 * @param WP_Post[] $posts Posts published that year.
 */
function otd_render_year( $year, $posts ) {
	$show_author = 'all' === otd_get_scope();

	echo '<div class="otd-year activity-block">';

	printf(
		'<h3>%1$s <span class="otd-year-number">%2$s</span></h3>',
		esc_html( otd_years_ago_label( $year ) ),
		esc_html( (string) $year )
	);

	echo '<ul>';

	foreach ( $posts as $post ) {
		otd_render_post_row( $post, $show_author );
	}

	echo '</ul>';
	echo '</div>';
}

/**
 * Renders a single post row.
 *
 * @param WP_Post $post        Post object.
 * @param bool    $show_author Whether to credit the author.
 */
function otd_render_post_row( $post, $show_author ) {
	$title = get_the_title( $post );

	if ( '' === trim( $title ) ) {
		$title = __( '(no title)', 'on-this-day' );
	}

	$link = current_user_can( 'edit_post', $post->ID ) ? get_edit_post_link( $post->ID ) : get_permalink( $post );

	echo '<li class="otd-post" data-post-id="' . esc_attr( $post->ID ) . '">';

	printf(
		'<a class="otd-post-title" href="%1$s">%2$s</a>',
		esc_url( $link ),
		esc_html( $title )
	);

	if ( $show_author ) {
		printf(
			' <span class="otd-post-author">%s</span>',
			esc_html( get_the_author_meta( 'display_name', $post->post_author ) )
		);
	}

	printf(
		'<button type="button" class="otd-hide" data-post-id="%1$s"><span class="screen-reader-text">%2$s</span><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>',
		esc_attr( $post->ID ),
		esc_attr(
			sprintf(
				/* translators: %s: Post title. */
				__( 'Hide “%s” from On This Day', 'on-this-day' ),
				$title
			)
		)
	);

	echo '</li>';
}
