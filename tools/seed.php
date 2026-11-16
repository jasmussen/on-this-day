<?php
/**
 * Seeds posts published on today's month and day in previous years.
 *
 * Run with WP-CLI:
 *
 *     wp eval-file tools/seed.php
 *
 * Or require it from anywhere WordPress is loaded, which is how the
 * Playground blueprint seeds its demo site.
 *
 * The widget is only as convincing as what it has to show, so this creates a
 * spread the prototype can actually be judged on: several years, more than one
 * author, more than one post in a single year, and a couple of featured images
 * for the featured layout.
 *
 * @package OnThisDay
 */

defined( 'ABSPATH' ) || die( 'WordPress must be loaded.' );

/**
 * Reports progress, when there is somewhere to report it.
 *
 * WP-CLI is not available when this runs from a Playground blueprint, so
 * nothing here may assume it.
 *
 * @param string $message Message.
 */
function otd_seed_log( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	}
}

/**
 * Makes a placeholder image and attaches it to a post.
 *
 * @param int    $post_id Post to attach to.
 * @param int    $year    Year, drawn onto the image.
 * @param string $title   Title, used for the file name.
 * @return int Attachment ID, or 0 on failure.
 */
function otd_seed_image( $post_id, $year, $title ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}

	$width  = 1200;
	$height = 800;
	$image  = imagecreatetruecolor( $width, $height );

	// A different hue per year, so the images are told apart at a glance.
	$hue = ( $year * 47 ) % 360;
	list( $r, $g, $b ) = otd_seed_hsl_to_rgb( $hue, 0.35, 0.55 );

	$background = imagecolorallocate( $image, $r, $g, $b );
	imagefilledrectangle( $image, 0, 0, $width, $height, $background );

	list( $r2, $g2, $b2 ) = otd_seed_hsl_to_rgb( ( $hue + 40 ) % 360, 0.4, 0.42 );
	$accent               = imagecolorallocate( $image, $r2, $g2, $b2 );
	imagefilledellipse( $image, (int) ( $width * 0.7 ), (int) ( $height * 0.6 ), 620, 620, $accent );

	$white = imagecolorallocate( $image, 255, 255, 255 );
	imagestring( $image, 5, 60, 60, (string) $year, $white );

	$uploads = wp_upload_dir();
	$file    = trailingslashit( $uploads['path'] ) . sanitize_title( $title . '-' . $year ) . '.jpg';

	imagejpeg( $image, $file, 82 );
	imagedestroy( $image );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => sprintf( 'Seed image %d', $year ),
			'post_status'    => 'inherit',
		),
		$file,
		$post_id
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );

	return (int) $attachment_id;
}

/**
 * Converts HSL to RGB.
 *
 * @param float $h Hue, 0-360.
 * @param float $s Saturation, 0-1.
 * @param float $l Lightness, 0-1.
 * @return int[] Red, green, blue.
 */
function otd_seed_hsl_to_rgb( $h, $s, $l ) {
	$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
	$x = $c * ( 1 - abs( fmod( $h / 60, 2 ) - 1 ) );
	$m = $l - $c / 2;

	if ( $h < 60 ) {
		$rgb = array( $c, $x, 0 );
	} elseif ( $h < 120 ) {
		$rgb = array( $x, $c, 0 );
	} elseif ( $h < 180 ) {
		$rgb = array( 0, $c, $x );
	} elseif ( $h < 240 ) {
		$rgb = array( 0, $x, $c );
	} elseif ( $h < 300 ) {
		$rgb = array( $x, 0, $c );
	} else {
		$rgb = array( $c, 0, $x );
	}

	return array(
		(int) round( ( $rgb[0] + $m ) * 255 ),
		(int) round( ( $rgb[1] + $m ) * 255 ),
		(int) round( ( $rgb[2] + $m ) * 255 ),
	);
}

$now        = current_datetime();
$this_year  = (int) $now->format( 'Y' );
$month      = (int) $now->format( 'n' );
$day        = (int) $now->format( 'j' );

$users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );

/*
 * The switch between your posts and everyone's only appears on days more
 * than one person published, so a demo site needs a second author or that
 * part of the widget is invisible.
 */
if ( count( $users ) < 2 ) {
	$second = wp_insert_user(
		array(
			'user_login'   => 'samira',
			'user_email'   => 'samira@example.com',
			'user_pass'    => wp_generate_password( 24 ),
			'display_name' => 'Samira Ortiz',
			'first_name'   => 'Samira',
			'last_name'    => 'Ortiz',
			'role'         => 'editor',
		)
	);

	if ( is_wp_error( $second ) ) {
		otd_seed_log( 'Could not add a second author: ' . $second->get_error_message() );
	} else {
		otd_seed_log( 'Added a second author.' );
	}

	$users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
}

if ( ! $users ) {
	otd_seed_log( 'No users found.' );
	return;
}

/*
 * years_ago => list of posts. A couple of years carry two posts so that the
 * grouping is visible, and `image` marks the ones worth a featured image.
 */
$plan = array(
	1  => array(
		array(
			'title' => 'Notes from a slow morning',
			'image' => true,
		),
	),
	2  => array(
		array( 'title' => 'What I learned rebuilding the kitchen shelf' ),
		array( 'title' => 'A short list of things that worked' ),
	),
	5  => array(
		array(
			'title' => 'The workshop, five summers in',
			'image' => true,
		),
	),
	9  => array(
		array( 'title' => 'On keeping a notebook' ),
	),
	10 => array(
		array(
			'title' => 'Ten years of writing here',
			'image' => true,
		),
		array( 'title' => 'Everything I got wrong about tools' ),
	),
	14 => array(
		array( 'title' => 'First post from the old desk' ),
	),
);

$created = 0;
$index   = 0;

foreach ( $plan as $years_ago => $entries ) {
	$year = $this_year - $years_ago;

	foreach ( $entries as $entry ) {
		$author = $users[ $index % count( $users ) ];
		++$index;

		$date = sprintf( '%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, 8 + ( $index % 9 ), ( $index * 7 ) % 60 );

		$existing = get_posts(
			array(
				'post_type'      => 'post',
				'title'          => $entry['title'],
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		if ( $existing ) {
			otd_seed_log( sprintf( 'Skipping existing: %s', $entry['title'] ) );
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $entry['title'],
				'post_content' => '<!-- wp:paragraph --><p>Seed content for the On This Day prototype.</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $author->ID,
				'post_date'    => $date,
				'post_type'    => 'post',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
					otd_seed_log( 'Skipped: ' . $post_id->get_error_message() );
			continue;
		}

		if ( ! empty( $entry['image'] ) ) {
			$attachment_id = otd_seed_image( $post_id, $year, $entry['title'] );

			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		++$created;
		otd_seed_log( sprintf( '%d — %s (%s)', $year, $entry['title'], $author->display_name ) );
	}
}

otd_seed_log( sprintf( 'Created %d posts for %02d-%02d.', $created, $month, $day ) );
