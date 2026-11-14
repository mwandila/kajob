<?php
/**
 * Reusable template helpers: icons, salary formatting, job cards.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   Icons — inline SVG so there is no icon-font request and no CDN.
   ========================================================================== */

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon key.
 * @param array  $args  size, class, stroke.
 */
function kajob_get_icon( $name, $args = array() ) {
	$paths = array(
		'search'        => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'pin'           => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'briefcase'     => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M2 13h20"/>',
		'bookmark'      => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>',
		'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'          => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
		'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
		'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
		'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
		'arrow-right'   => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
		'arrow-up'      => '<path d="M12 20V4"/><path d="m6 10 6-6 6 6"/>',
		'arrow-left'    => '<path d="M20 12H4"/><path d="m10 18-6-6 6-6"/>',
		'check'         => '<path d="M20 6 9 17l-5-5"/>',
		'check-circle'  => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
		'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'cash'          => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
		'building'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M10 21v-4h4v4"/>',
		'users'         => '<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3.5"/><path d="M22 20v-2a4 4 0 0 0-3-3.9"/><path d="M16.5 3.6a4 4 0 0 1 0 7"/>',
		'globe'         => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18Z"/>',
		'filter'        => '<path d="M3 5h18l-7 8v6l-4-2v-4Z"/>',
		'grid'          => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
		'list'          => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
		'share'         => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.3M8.2 13.2l7.6 4.3"/>',
		'star'          => '<path d="m12 3 2.7 5.7 6.3.9-4.5 4.4 1 6.2-5.5-3-5.5 3 1-6.2L3 9.6l6.3-.9Z"/>',
		'sparkle'       => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/>',
		'mail'          => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'link'          => '<path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/>',
		'calendar'      => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
		'trending'      => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
		'code'          => '<path d="m9 18-6-6 6-6"/><path d="m15 6 6 6-6 6"/>',
		'palette'       => '<path d="M12 21a9 9 0 1 1 9-9c0 2-1.6 3-3.5 3H16a2 2 0 0 0-1.4 3.4A2 2 0 0 1 13 21Z"/><circle cx="7.5" cy="12" r="1"/><circle cx="10" cy="8" r="1"/><circle cx="15" cy="8.5" r="1"/>',
		'megaphone'     => '<path d="m3 11 15-6v14L3 13Z"/><path d="M7 12v6a2 2 0 0 0 4 0v-4"/><path d="M20 9v6"/>',
		'headset'       => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="13" width="5" height="7" rx="2"/><rect x="17" y="13" width="5" height="7" rx="2"/><path d="M19 20a3 3 0 0 1-3 3h-2"/>',
		'cpu'           => '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M10 2v4M14 2v4M10 18v4M14 18v4M2 10h4M2 14h4M18 10h4M18 14h4"/>',
		'pulse'         => '<path d="M2 12h4l2.5-7 4 14L15 12h7"/>',
		'chart'         => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
		'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
		'zap'           => '<path d="M13 2 4 14h7l-1 8 9-12h-7Z"/>',
		'file'          => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/><path d="M9 13h6M9 17h4"/>',
		'plus'          => '<path d="M12 5v14M5 12h14"/>',
		'external'      => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
		'alert'         => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
		'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
		'target'        => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
		'cap'           => '<path d="m2 8 10-4 10 4-10 4Z"/><path d="M6 10v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/><path d="M22 8v6"/>',
		'layers'        => '<path d="m12 2 9 5-9 5-9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
		'handshake'     => '<path d="m11 17 2 2 4-4"/><path d="M3 10 8 5l4 3 4-3 5 5-4 6-5-3-5 3Z"/>',
		'linkedin'      => '<path d="M4.5 9v11M4.5 4.5v.01"/><path d="M10 20V9m0 3.5c0-2 1.5-3.5 3.5-3.5S17 10.5 17 12.5V20"/>',
		'github'        => '<path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 4.5-1.4 4.5-5a4 4 0 0 0-1.1-2.8c.1-.4.4-1.5-.1-2.7 0 0-1.4-.4-4.3 1.6a10 10 0 0 0-5 0C5.6 3.6 4.2 4 4.2 4c-.5 1.2-.2 2.3-.1 2.7A4 4 0 0 0 3 9.5c0 3.6 1.7 4.7 4.5 5-.6.6-.6 1.2-.5 2V21"/>',
		'x-social'      => '<path d="M4 4l7.5 9.5L4.5 20"/><path d="M20 4l-7.6 8.4L20 20h-4L4 4h4l12 16"/>',
		'facebook'      => '<path d="M15 3h-2.5A4.5 4.5 0 0 0 8 7.5V10H5v4h3v7h4v-7h3l1-4h-4V7.5A.5.5 0 0 1 12.5 7H15Z"/>',
		'sliders'       => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
		'refresh'       => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
		'inbox'         => '<path d="M3 12h5l2 3h4l2-3h5"/><path d="M5 5h14l2 7v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6Z"/>',
		'rss'           => '<path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1.5"/>',
		'edit'          => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
		'trash'         => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
		'eye'           => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
		'send'          => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/>',
		'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
		'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
		'copy'          => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
		'home'          => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/>',
		'compass'       => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5Z"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$size   = isset( $args['size'] ) ? absint( $args['size'] ) : 20;
	$class  = isset( $args['class'] ) ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
	$stroke = isset( $args['stroke'] ) ? (float) $args['stroke'] : 1.9;
	$fill   = ! empty( $args['fill'] ) ? 'currentColor' : 'none';

	return sprintf(
		'<svg%1$s width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="%3$s" stroke="currentColor" stroke-width="%4$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%5$s</svg>',
		$class,
		$size,
		esc_attr( $fill ),
		esc_attr( $stroke ),
		$paths[ $name ]
	);
}

/**
 * Echo an inline SVG icon.
 */
function kajob_icon( $name, $args = array() ) {
	echo kajob_get_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from a fixed path map.
}

/**
 * Brand mark used in the header and footer.
 */
function kajob_brand_mark() {
	return '<span class="site-branding__mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19V7l7 6 7-6v12"/></svg></span>';
}

/* ==========================================================================
   Page URLs
   ========================================================================== */

/**
 * URL of the jobs archive. Prefers the plugin's own registered page.
 */
function kajob_get_jobs_page_url() {
	if ( kajob_is_job_manager_active() && function_exists( 'job_manager_get_page_id' ) ) {
		$page_id = job_manager_get_page_id( 'jobs' );

		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			return get_permalink( $page_id );
		}
	}

	$page_id = absint( get_theme_mod( 'kajob_jobs_page', 0 ) );

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	$archive = get_post_type_archive_link( 'job_listing' );

	return $archive ? $archive : home_url( '/' );
}

/**
 * URL of a WP Job Manager page by slug.
 */
function kajob_get_jm_page_url( $page ) {
	if ( kajob_is_job_manager_active() && function_exists( 'job_manager_get_page_id' ) ) {
		$page_id = job_manager_get_page_id( $page );

		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			return get_permalink( $page_id );
		}
	}

	return home_url( '/' );
}

/**
 * URL of the AI Mode page (page using the page-ai-mode.php template).
 */
function kajob_get_ai_mode_url() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'numberposts'    => 1,
		'fields'         => 'ids',
		'posts_per_page' => 1,
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-ai-mode.php',
	) );

	if ( ! empty( $pages ) ) {
		return get_permalink( $pages[0] );
	}

	return '';
}

/**
 * URL of the theme's saved-jobs page.
 */
function kajob_get_saved_jobs_url() {
	$page_id = absint( get_theme_mod( 'kajob_saved_jobs_page', 0 ) );

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	return home_url( '/saved-jobs/' );
}

/**
 * URL of the articles index. Falls back to the posts page, then the seeded slug.
 */
function kajob_get_blog_url() {
	$posts_page = (int) get_option( 'page_for_posts' );

	if ( $posts_page && 'publish' === get_post_status( $posts_page ) ) {
		return get_permalink( $posts_page );
	}

	$advice = get_page_by_path( 'career-advice' );

	if ( $advice && 'publish' === $advice->post_status ) {
		return get_permalink( $advice );
	}

	return home_url( '/' );
}

/**
 * Choose a locally stored cover image for articles without a featured image.
 *
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return string Image URL.
 */
function kajob_get_article_cover_image( $post_id = null ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$images  = array(
		'remote'      => 'remote-scene.svg',
		'interview'   => 'teamwork-scene.svg',
		'negotiat'    => 'teamwork-scene.svg',
		'salary'      => 'teamwork-scene.svg',
		'application' => 'career-scene.svg',
		'resume'      => 'career-scene.svg',
		'portfolio'   => 'career-scene.svg',
		'career'      => 'career-scene.svg',
		'job-search'  => 'career-scene.svg',
	);
	$filename = 'career-scene.svg';
	$terms    = get_the_category( $post_id );

	if ( $terms ) {
		foreach ( $terms as $term ) {
			foreach ( $images as $needle => $candidate ) {
				if ( false !== strpos( strtolower( $term->slug . ' ' . $term->name ), $needle ) ) {
					$filename = $candidate;
					break 2;
				}
			}
		}
	}

	return KAJOB_URI . '/assets/images/home-demo/' . $filename;
}

/**
 * Whether an article has a real featured image rather than a generated demo cover.
 *
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return bool
 */
function kajob_has_article_photo_thumbnail( $post_id = null ) {
	$post_id       = $post_id ? absint( $post_id ) : get_the_ID();
	$thumbnail_id  = get_post_thumbnail_id( $post_id );

	if ( ! $thumbnail_id ) {
		return false;
	}

	$attached_file = (string) get_post_meta( $thumbnail_id, '_wp_attached_file', true );

	return 0 !== strpos( $attached_file, 'kajob-demo/cover-' );
}

/* ==========================================================================
   Counts & stats
   ========================================================================== */

/**
 * Number of published job listings.
 */
function kajob_get_job_count() {
	if ( ! kajob_is_job_manager_active() ) {
		return 0;
	}

	$counts = wp_count_posts( 'job_listing' );

	return isset( $counts->publish ) ? absint( $counts->publish ) : 0;
}

/**
 * Aggregate figures for the hero stat row. Cached because it runs several
 * taxonomy queries and the front page renders on every visit.
 */
function kajob_get_board_stats() {
	$cached = get_transient( 'kajob_board_stats' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$jobs      = kajob_get_job_count();
	$companies = 0;
	$remote    = 0;
	$categories = 0;

	if ( kajob_is_job_manager_active() ) {
		$names = get_posts( array(
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		$unique = array();

		foreach ( $names as $job_id ) {
			$name = get_post_meta( $job_id, '_company_name', true );

			if ( $name ) {
				$unique[ strtolower( trim( $name ) ) ] = true;
			}

			if ( '1' === (string) get_post_meta( $job_id, '_remote_position', true ) ) {
				$remote++;
			}
		}

		$companies = count( $unique );

		if ( taxonomy_exists( 'job_listing_category' ) ) {
			$categories = (int) wp_count_terms( array( 'taxonomy' => 'job_listing_category', 'hide_empty' => true ) );
		}
	}

	$stats = array(
		'jobs'       => $jobs,
		'companies'  => $companies,
		'remote'     => $remote,
		'categories' => $categories,
	);

	set_transient( 'kajob_board_stats', $stats, HOUR_IN_SECONDS );

	return $stats;
}

/**
 * Bust the stats cache whenever a listing changes.
 */
add_action( 'save_post_job_listing', function () {
	delete_transient( 'kajob_board_stats' );
	delete_transient( 'kajob_board_companies' );
	delete_transient( 'kajob_salary_insights' );
} );

add_action( 'deleted_post', function ( $post_id ) {
	if ( 'job_listing' === get_post_type( $post_id ) ) {
		delete_transient( 'kajob_board_stats' );
		delete_transient( 'kajob_board_companies' );
		delete_transient( 'kajob_salary_insights' );
	}
} );

/**
 * Distinct hiring companies, most active first, for the trust strip.
 */
function kajob_get_board_companies( $limit = 14 ) {
	$cached = get_transient( 'kajob_board_companies' );

	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}

	$companies = array();

	if ( kajob_is_job_manager_active() ) {
		$ids = get_posts( array(
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		$tally = array();

		foreach ( $ids as $job_id ) {
			$name = trim( (string) get_post_meta( $job_id, '_company_name', true ) );

			if ( '' === $name ) {
				continue;
			}

			$key = strtolower( $name );

			if ( ! isset( $tally[ $key ] ) ) {
				$tally[ $key ] = array( 'name' => $name, 'count' => 0, 'url' => add_query_arg( 'search_keywords', $name, kajob_get_jobs_page_url() ) );
			}

			$tally[ $key ]['count']++;
		}

		usort( $tally, function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		} );

		$companies = array_values( $tally );
	}

	set_transient( 'kajob_board_companies', $companies, HOUR_IN_SECONDS );

	return array_slice( $companies, 0, $limit );
}

/**
 * Median-ish pay band per category, computed from the numeric salary meta.
 * Powers the front-page transparency chart, so every bar is a real figure.
 */
function kajob_get_salary_insights( $limit = 6 ) {
	$cached = get_transient( 'kajob_salary_insights' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$rows = array();

	if ( kajob_is_job_manager_active() && taxonomy_exists( 'job_listing_category' ) ) {
		$ids = get_posts( array(
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_kajob_salary_max', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ),
			),
		) );

		$buckets = array();

		foreach ( $ids as $job_id ) {
			$min = kajob_get_salary_min( $job_id );
			$max = kajob_get_salary_max( $job_id );

			if ( ! $min ) {
				$min = $max;
			}

			$terms = get_the_terms( $job_id, 'job_listing_category' );

			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( ! isset( $buckets[ $term->slug ] ) ) {
					$buckets[ $term->slug ] = array(
						'name'  => $term->name,
						'slug'  => $term->slug,
						'url'   => get_term_link( $term ),
						'low'   => $min,
						'high'  => $max,
						'sum'   => 0,
						'count' => 0,
					);
				}

				$buckets[ $term->slug ]['low']   = min( $buckets[ $term->slug ]['low'], $min );
				$buckets[ $term->slug ]['high']  = max( $buckets[ $term->slug ]['high'], $max );
				$buckets[ $term->slug ]['sum']  += ( $min + $max ) / 2;
				$buckets[ $term->slug ]['count']++;
			}
		}

		foreach ( $buckets as $bucket ) {
			if ( is_wp_error( $bucket['url'] ) ) {
				continue;
			}

			$bucket['avg'] = (int) round( $bucket['sum'] / max( 1, $bucket['count'] ) );
			unset( $bucket['sum'] );
			$rows[] = $bucket;
		}

		usort( $rows, function ( $a, $b ) {
			return $b['avg'] <=> $a['avg'];
		} );

		$rows = array_slice( $rows, 0, $limit );
	}

	set_transient( 'kajob_salary_insights', $rows, HOUR_IN_SECONDS );

	return $rows;
}

/* ==========================================================================
   Salary
   ========================================================================== */

function kajob_get_salary_min( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return absint( get_post_meta( $post_id, '_kajob_salary_min', true ) );
}

function kajob_get_salary_max( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return absint( get_post_meta( $post_id, '_kajob_salary_max', true ) );
}

/**
 * Highest salary on the board, rounded up, for the filter slider's max.
 */
function kajob_get_salary_ceiling() {
	$cached = get_transient( 'kajob_salary_ceiling' );

	if ( $cached ) {
		return (int) $cached;
	}

	global $wpdb;

	$max = (int) $wpdb->get_var(
		"SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key IN ('_kajob_salary_max','_kajob_salary_min') AND meta_value <> ''"
	);

	$ceiling = $max > 0 ? (int) ( ceil( $max / 10000 ) * 10000 ) : 250000;

	set_transient( 'kajob_salary_ceiling', $ceiling, HOUR_IN_SECONDS );

	return $ceiling;
}

add_action( 'save_post_job_listing', function () {
	delete_transient( 'kajob_salary_ceiling' );
} );

/**
 * Compact money formatting: 120000 -> 120k.
 */
function kajob_compact_money( $amount ) {
	$amount = (float) $amount;

	if ( $amount >= 1000000 ) {
		return rtrim( rtrim( number_format( $amount / 1000000, 1 ), '0' ), '.' ) . 'M';
	}

	if ( $amount >= 1000 ) {
		return rtrim( rtrim( number_format( $amount / 1000, 1 ), '0' ), '.' ) . 'k';
	}

	return number_format( $amount );
}

/**
 * Build a salary range string from numeric bounds.
 *
 * @param int  $min      Lower bound.
 * @param int  $max      Upper bound.
 * @param bool $compact  Use 120k rather than 120,000.
 */
function kajob_format_salary_range( $min, $max, $compact = true ) {
	$min = absint( $min );
	$max = absint( $max );

	if ( ! $min && ! $max ) {
		return '';
	}

	$fmt = $compact ? 'kajob_compact_money' : 'number_format';
	$cur = kajob_currency_symbol();

	if ( $min && $max && $min !== $max ) {
		return $cur . $fmt( $min ) . ' – ' . $cur . $fmt( $max );
	}

	$single = $max ? $max : $min;

	return $cur . $fmt( $single );
}

/**
 * Currency symbol for the configured plugin currency.
 */
function kajob_currency_symbol() {
	$code = get_option( 'job_manager_default_salary_currency', 'USD' );

	$map = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'CAD' => 'CA$',
		'AUD' => 'A$',
		'INR' => '₹',
		'JPY' => '¥',
	);

	return isset( $map[ $code ] ) ? $map[ $code ] : $code . ' ';
}

/**
 * Salary label for a job, falling back to the plugin's free-text field.
 */
function kajob_get_salary_label( $post_id = null, $compact = true ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$range   = kajob_format_salary_range( kajob_get_salary_min( $post_id ), kajob_get_salary_max( $post_id ), $compact );

	if ( $range ) {
		return $range;
	}

	if ( function_exists( 'get_the_job_salary' ) ) {
		$raw = get_the_job_salary( $post_id );

		if ( $raw ) {
			return $raw;
		}
	}

	return '';
}

/**
 * Human label for the salary unit (per year / per hour).
 */
function kajob_salary_unit_label( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$unit    = get_post_meta( $post_id, '_job_salary_unit', true );

	$map = array(
		'YEAR'  => __( 'per year', 'kajob' ),
		'MONTH' => __( 'per month', 'kajob' ),
		'WEEK'  => __( 'per week', 'kajob' ),
		'DAY'   => __( 'per day', 'kajob' ),
		'HOUR'  => __( 'per hour', 'kajob' ),
	);

	return isset( $map[ $unit ] ) ? $map[ $unit ] : __( 'per year', 'kajob' );
}

/* ==========================================================================
   Company presentation
   ========================================================================== */

/**
 * Deterministic accent per company so a fallback logo is stable across pages.
 * Returns a SOLID background color (no gradients per design direction).
 */
function kajob_company_gradient( $name ) {
	$colors = array( '#ffb020', '#0284c7', '#00b483', '#d629ac', '#e63757', '#5a35f0', '#252e56', '#d98800' );

	$index = abs( crc32( strtolower( trim( (string) $name ) ) ) ) % count( $colors );

	return $colors[ $index ];
}

/**
 * One or two initials for a fallback logo.
 */
function kajob_company_initials( $name ) {
	$name = trim( wp_strip_all_tags( (string) $name ) );

	if ( '' === $name ) {
		return '?';
	}

	$words = preg_split( '/\s+/', $name );
	$first = mb_substr( $words[0], 0, 1 );
	$second = count( $words ) > 1 ? mb_substr( $words[1], 0, 1 ) : mb_substr( $words[0], 1, 1 );

	return mb_strtoupper( $first . $second );
}

/**
 * Company logo: real image when present, otherwise a coloured initials tile.
 */
function kajob_get_company_logo( $post_id = null, $class = 'job-card__logo', $size = 'thumbnail' ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$name    = get_post_meta( $post_id, '_company_name', true );

	if ( ! $name ) {
		$name = get_the_title( $post_id );
	}

	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail(
			$post_id,
			$size,
			array(
				'class' => $class,
				'alt'   => esc_attr( $name ),
				'loading' => 'lazy',
			)
		);
	}

	$legacy = get_post_meta( $post_id, '_company_logo', true );

	if ( $legacy ) {
		return sprintf(
			'<img class="%1$s" src="%2$s" alt="%3$s" loading="lazy" />',
			esc_attr( $class ),
			esc_url( $legacy ),
			esc_attr( $name )
		);
	}

	return sprintf(
		'<span class="%1$s %1$s--fallback" style="background:%2$s" aria-hidden="true">%3$s</span>',
		esc_attr( $class ),
		esc_attr( kajob_company_gradient( $name ) ),
		esc_html( kajob_company_initials( $name ) )
	);
}

/**
 * Whether the listing is flagged remote.
 */
function kajob_is_remote( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return '1' === (string) get_post_meta( $post_id, '_remote_position', true );
}

/* ==========================================================================
   Job type / category presentation
   ========================================================================== */

/**
 * Job type badges for a listing.
 */
function kajob_get_job_type_chips( $post_id = null, $limit = 2 ) {
	if ( ! kajob_is_job_manager_active() || ! function_exists( 'wpjm_get_the_job_types' ) ) {
		return '';
	}

	$types = wpjm_get_the_job_types( $post_id );

	if ( empty( $types ) || is_wp_error( $types ) ) {
		return '';
	}

	$out = '';

	foreach ( array_slice( $types, 0, $limit ) as $type ) {
		$out .= sprintf(
			'<span class="chip chip--%1$s"><span class="badge-dot"></span>%2$s</span>',
			esc_attr( kajob_job_type_accent( $type->slug ) ),
			esc_html( $type->name )
		);
	}

	return $out;
}

/**
 * Category icon + accent, keyed off the slug so real terms get stable styling.
 */
function kajob_category_style( $slug ) {
	$map = array(
		'engineering'       => array( 'code', 'var(--violet-600)' ),
		'design'            => array( 'palette', 'var(--magenta-600)' ),
		'data'              => array( 'chart', 'var(--sky-600)' ),
		'marketing'         => array( 'megaphone', 'var(--amber-600)' ),
		'sales'             => array( 'trending', 'var(--mint-600)' ),
		'customer-support'  => array( 'headset', 'var(--coral-600)' ),
		'product'           => array( 'layers', 'var(--violet-600)' ),
		'operations'        => array( 'cpu', 'var(--sky-600)' ),
		'healthcare'        => array( 'pulse', 'var(--coral-600)' ),
		'finance'           => array( 'cash', 'var(--mint-600)' ),
		'education'         => array( 'cap', 'var(--amber-600)' ),
		'security'          => array( 'shield', 'var(--violet-600)' ),
	);

	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	$fallbacks = array(
		array( 'briefcase', 'var(--violet-600)' ),
		array( 'globe', 'var(--sky-600)' ),
		array( 'users', 'var(--mint-600)' ),
		array( 'target', 'var(--magenta-600)' ),
		array( 'zap', 'var(--amber-600)' ),
		array( 'building', 'var(--coral-600)' ),
	);

	return $fallbacks[ abs( crc32( $slug ) ) % count( $fallbacks ) ];
}

/**
 * Category cards for the front page.
 */
function kajob_render_job_categories() {
	$terms = array();

	if ( kajob_is_job_manager_active() && taxonomy_exists( 'job_listing_category' ) ) {
		$terms = get_terms( array(
			'taxonomy'   => 'job_listing_category',
			'hide_empty' => true,
			'number'     => 8,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
	}

	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			list( $icon, $accent ) = kajob_category_style( $term->slug );

			printf(
				'<a class="category-card" href="%1$s" style="--cat-accent:%2$s"><span class="category-card__icon">%3$s</span><span class="category-card__body"><span class="category-card__name">%4$s</span><span class="category-card__count">%5$s</span></span></a>',
				esc_url( get_term_link( $term ) ),
				esc_attr( $accent ),
				kajob_get_icon( $icon, array( 'size' => 22 ) ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $term->name ),
				esc_html( sprintf( _n( '%d open role', '%d open roles', $term->count, 'kajob' ), $term->count ) )
			);
		}

		return;
	}

	$placeholders = array(
		__( 'Engineering', 'kajob' )      => 'engineering',
		__( 'Design', 'kajob' )           => 'design',
		__( 'Marketing', 'kajob' )        => 'marketing',
		__( 'Sales', 'kajob' )            => 'sales',
		__( 'Data', 'kajob' )             => 'data',
		__( 'Customer Support', 'kajob' ) => 'customer-support',
	);

	foreach ( $placeholders as $name => $slug ) {
		list( $icon, $accent ) = kajob_category_style( $slug );

		printf(
			'<span class="category-card category-card--placeholder" style="--cat-accent:%1$s"><span class="category-card__icon">%2$s</span><span class="category-card__body"><span class="category-card__name">%3$s</span><span class="category-card__count">%4$s</span></span></span>',
			esc_attr( $accent ),
			kajob_get_icon( $icon, array( 'size' => 22 ) ), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html( $name ),
			esc_html__( 'Awaiting listings', 'kajob' )
		);
	}
}

/* ==========================================================================
   Job card — theme-native renderer (front page, related, saved jobs)
   ========================================================================== */

/**
 * Render one job card. The /job_manager override handles the plugin loop; this
 * is used wherever the theme builds its own listing query.
 *
 * @param int   $post_id Job ID.
 * @param array $args    variant: list|grid.
 */
function kajob_job_card( $post_id, $args = array() ) {
	$variant  = isset( $args['variant'] ) ? $args['variant'] : 'list';
	$company  = get_post_meta( $post_id, '_company_name', true );
	$location = get_post_meta( $post_id, '_job_location', true );
	$remote   = kajob_is_remote( $post_id );
	$salary   = kajob_get_salary_label( $post_id );
	$featured = '1' === (string) get_post_meta( $post_id, '_featured', true );
	$filled   = '1' === (string) get_post_meta( $post_id, '_filled', true );

	$classes = array( 'job-card' );

	if ( $featured ) {
		$classes[] = 'job-card--featured';
	}

	if ( $filled ) {
		$classes[] = 'job-card--filled';
	}

	$logo = kajob_get_company_logo( $post_id, 'job-card__logo', 'thumbnail' );

	$save_button = sprintf(
		'<button type="button" class="job-save" data-job-save="%1$s" aria-pressed="false" aria-label="%2$s">%3$s</button>',
		esc_attr( $post_id ),
		esc_attr( sprintf( __( 'Save %s', 'kajob' ), get_the_title( $post_id ) ) ),
		kajob_get_icon( 'bookmark', array( 'size' => 17 ) )
	);

	ob_start();
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-job-id="<?php echo esc_attr( $post_id ); ?>">
		<a class="job-card__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<?php if ( 'grid' === $variant ) : ?>
				<div class="job-card__top"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php else : ?>
				<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>

			<div class="job-card__body">
				<div class="job-card__head">
					<h3 class="job-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
					<?php if ( $featured ) : ?>
						<span class="chip chip--amber"><?php kajob_icon( 'star', array( 'size' => 12, 'fill' => true ) ); ?><?php esc_html_e( 'Featured', 'kajob' ); ?></span>
					<?php endif; ?>
					<?php if ( $filled ) : ?>
						<span class="chip chip--coral"><?php esc_html_e( 'Filled', 'kajob' ); ?></span>
					<?php endif; ?>
				</div>

				<div class="job-card__company">
					<?php if ( $company ) : ?><strong><?php echo esc_html( $company ); ?></strong><?php endif; ?>
					<?php if ( $location ) : ?>
						<span class="job-card__sep" aria-hidden="true"></span>
						<span><?php kajob_icon( 'pin', array( 'size' => 13 ) ); ?> <?php echo esc_html( $location ); ?></span>
					<?php endif; ?>
				</div>

				<div class="job-card__meta">
					<?php echo kajob_get_job_type_chips( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( $remote ) : ?>
						<span class="chip chip--sky"><?php kajob_icon( 'globe', array( 'size' => 12 ) ); ?><?php esc_html_e( 'Remote', 'kajob' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="job-card__aside">
				<?php if ( $salary ) : ?>
					<span class="job-card__salary"><?php echo esc_html( $salary ); ?> <small><?php echo esc_html( kajob_salary_unit_label( $post_id ) ); ?></small></span>
				<?php else : ?>
					<span class="job-card__salary u-muted"><?php esc_html_e( 'Salary n/a', 'kajob' ); ?></span>
				<?php endif; ?>
				<span class="job-card__date">
					<?php
					/* translators: %s: human-readable time difference. */
					printf( esc_html__( '%s ago', 'kajob' ), esc_html( human_time_diff( get_post_time( 'U', true, $post_id ), current_time( 'timestamp' ) ) ) );
					?>
				</span>
			</div>
		</a>
		<?php echo $save_button; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped above. ?>
	</article>
	<?php

	return ob_get_clean();
}

/**
 * Render a run of job cards from a listing query. Shared by the front-page
 * rails, the archive's AJAX responses and the saved-jobs page so a card looks
 * identical wherever it appears.
 *
 * @param array $args     WP_Query overrides.
 * @param array $options  per_page, variant.
 * @return array{html:string,query:WP_Query}
 */
function kajob_get_job_cards( $args = array(), $options = array() ) {
	$per_page = isset( $options['per_page'] ) ? (int) $options['per_page'] : 6;
	$variant  = isset( $options['variant'] ) ? $options['variant'] : 'list';

	$query = new WP_Query( array_merge( array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'has_password'   => false,
	), $args ) );

	$html = '';

	foreach ( $query->posts as $job ) {
		if ( function_exists( 'job_manager_user_can_view_job_listing' ) && ! job_manager_user_can_view_job_listing( $job->ID ) ) {
			continue;
		}

		$html .= kajob_job_card( $job->ID, array( 'variant' => $variant ) );
	}

	return array( 'html' => $html, 'query' => $query );
}

/* ==========================================================================
   Post helpers
   ========================================================================== */

function kajob_posted_on() {
	printf(
		'<span class="posted-on">%1$s <a href="%2$s" rel="bookmark"><time datetime="%3$s">%4$s</time></a></span>',
		esc_html_x( 'Posted', 'post date label', 'kajob' ),
		esc_url( get_permalink() ),
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);
}

function kajob_posted_by() {
	printf(
		'<span class="byline">%1$s <a href="%2$s">%3$s</a></span>',
		esc_html_x( 'by', 'post author label', 'kajob' ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);
}

/**
 * Estimated reading time in minutes.
 */
function kajob_reading_time( $post_id = null ) {
	$content = get_post_field( 'post_content', $post_id ? $post_id : get_the_ID() );
	$words   = str_word_count( wp_strip_all_tags( (string) $content ) );
	$minutes = max( 1, (int) ceil( $words / 220 ) );

	/* translators: %d: number of minutes. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'kajob' ), $minutes );
}

/**
 * Breadcrumb trail for the single job header.
 */
function kajob_job_breadcrumbs( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$items = array(
		array( home_url( '/' ), __( 'Home', 'kajob' ) ),
		array( kajob_get_jobs_page_url(), __( 'Jobs', 'kajob' ) ),
	);

	$terms = get_the_terms( $post_id, 'job_listing_category' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$items[] = array( get_term_link( $terms[0] ), $terms[0]->name );
	}

	$out = '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'kajob' ) . '">';

	foreach ( $items as $item ) {
		$out .= sprintf( '<a href="%1$s">%2$s</a><span class="breadcrumbs__sep" aria-hidden="true">/</span>', esc_url( $item[0] ), esc_html( $item[1] ) );
	}

	$out .= '<span aria-current="page">' . esc_html( get_the_title( $post_id ) ) . '</span></nav>';

	return $out;
}
