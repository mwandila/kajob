<?php
/**
 * WP Job Manager integration.
 *
 * Kajob uses WP Job Manager for listings, front-end submissions and the
 * employer dashboard. This file wires the plugin to the theme: template
 * overrides live in /job_manager, and the extra numeric salary fields power
 * the archive's salary range filter.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether WP Job Manager is installed and active.
 */
function kajob_is_job_manager_active() {
	return class_exists( 'WP_Job_Manager' ) || defined( 'JOB_MANAGER_VERSION' );
}

/**
 * The plugin gates public taxonomies, rewrites and the job archive on this
 * exact support slug — 'job-manager' alone leaves them all disabled.
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'job-manager-templates' );
} );

/**
 * The setup wizard is redundant: the theme creates its own pages on activation.
 */
add_filter( 'job_manager_show_admin_notice_core_setup', '__return_false' );

/**
 * Templates resolve from /job_manager via the plugin's own locate_template
 * call, so no job_manager_locate_template filter is needed.
 */

/**
 * Card class. WP Job Manager has no job_listing_class filter; it builds the
 * attribute from get_post_class(), so the hook is post_class.
 */
add_filter( 'post_class', function ( $classes, $class, $post_id ) {
	if ( 'job_listing' === get_post_type( $post_id ) && ! is_singular( 'job_listing' ) ) {
		$classes[] = 'job-card';

		if ( kajob_get_salary_min( $post_id ) ) {
			$classes[] = 'job-card--has-salary';
		}
	}

	return $classes;
}, 11, 3 );

/**
 * Numeric salary bounds. Core stores _job_salary as free text, which cannot be
 * range-queried, so the submit form collects two integers alongside it.
 */
add_filter( 'submit_job_form_fields', function ( $fields ) {
	$fields['job']['kajob_salary_min'] = array(
		'label'       => __( 'Minimum salary', 'kajob' ),
		'type'        => 'text',
		'required'    => false,
		'placeholder' => __( 'e.g. 90000', 'kajob' ),
		'priority'    => 8.1,
		'description' => __( 'Annual gross amount, numbers only. Used by the salary filter.', 'kajob' ),
		'sanitizer'   => 'absint',
	);

	$fields['job']['kajob_salary_max'] = array(
		'label'       => __( 'Maximum salary', 'kajob' ),
		'type'        => 'text',
		'required'    => false,
		'placeholder' => __( 'e.g. 130000', 'kajob' ),
		'priority'    => 8.2,
		'description' => __( 'Leave empty for "up to" ranges.', 'kajob' ),
		'sanitizer'   => 'absint',
	);

	return $fields;
} );

/**
 * Keep the human-readable core salary string in sync with the numeric bounds
 * so the plugin's own templates, emails and structured data stay correct.
 */
add_action( 'job_manager_update_job_data', function ( $job_id, $values ) {
	$min = isset( $values['job']['kajob_salary_min'] ) ? absint( $values['job']['kajob_salary_min'] ) : 0;
	$max = isset( $values['job']['kajob_salary_max'] ) ? absint( $values['job']['kajob_salary_max'] ) : 0;

	if ( ! $min && ! $max ) {
		return;
	}

	if ( $min && $max && $max < $min ) {
		list( $min, $max ) = array( $max, $min );
		update_post_meta( $job_id, '_kajob_salary_min', $min );
		update_post_meta( $job_id, '_kajob_salary_max', $max );
	}

	update_post_meta( $job_id, '_job_salary', kajob_format_salary_range( $min, $max, false ) );
}, 10, 2 );

/**
 * Archive filters the plugin does not ship: salary floor and posting recency.
 * The board passes these as query args; a plain GET link supplies them in the
 * query string. Both paths land here so results never diverge.
 */
add_filter( 'get_job_listings_query_args', function ( $query_args, $args ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filtering.
	$salary_min = isset( $args['kajob_salary_min'] )
		? absint( $args['kajob_salary_min'] )
		: ( isset( $_REQUEST['kajob_salary_min'] ) ? absint( wp_unslash( $_REQUEST['kajob_salary_min'] ) ) : 0 );

	$posted = isset( $args['kajob_date_posted'] )
		? sanitize_key( $args['kajob_date_posted'] )
		: ( isset( $_REQUEST['kajob_date_posted'] ) ? sanitize_key( wp_unslash( $_REQUEST['kajob_date_posted'] ) ) : '' );
	// phpcs:enable

	if ( $salary_min > 0 ) {
		$meta_query   = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_kajob_salary_max',
				'value'   => $salary_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => '_kajob_salary_min',
				'value'   => $salary_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			),
		);

		$query_args['meta_query'] = $meta_query;
	}

	$windows = array(
		'24h' => '-1 day',
		'3d'  => '-3 days',
		'7d'  => '-7 days',
		'30d' => '-30 days',
	);

	if ( isset( $windows[ $posted ] ) ) {
		$query_args['date_query'] = array(
			array(
				'after'     => gmdate( 'Y-m-d H:i:s', strtotime( $windows[ $posted ] ) ),
				'inclusive' => true,
			),
		);
	}

	return $query_args;
}, 10, 2 );

/**
 * Salary sorting needs a numeric meta clause; core passes orderby straight to
 * WP_Query, so translate our sentinel into a real meta order.
 */
add_filter( 'get_job_listings_query_args', function ( $query_args, $args ) {
	if ( empty( $args['orderby'] ) || 'salary' !== $args['orderby'] ) {
		return $query_args;
	}

	$query_args['meta_key'] = '_kajob_salary_max';
	$query_args['orderby']  = array( 'meta_value_num' => $query_args['order'] ?? 'DESC' );

	$meta_query   = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
	$meta_query[] = array(
		'key'     => '_kajob_salary_max',
		'value'   => 0,
		'compare' => '>',
		'type'    => 'NUMERIC',
	);

	$query_args['meta_query'] = $meta_query;

	return $query_args;
}, 20, 2 );

/**
 * Report the active filter count so the UI can badge the mobile filter button.
 */
add_filter( 'job_manager_get_listings_result', function ( $result, $jobs ) {
	$result['total_jobs'] = (int) $jobs->found_posts;

	return $result;
}, 10, 2 );

/**
 * Job type terms carry a schema.org employment_type; expose a matching accent
 * so badges are colour-coded consistently across cards, filters and singles.
 */
function kajob_job_type_accent( $slug ) {
	$map = array(
		'full-time'  => 'mint',
		'part-time'  => 'magenta',
		'freelance'  => 'violet',
		'temporary'  => 'amber',
		'internship' => 'sky',
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : 'sky';
}

/**
 * Google for Jobs expects a numeric baseSalary. Core writes the free-text
 * string into value.value, which validators reject, so replace it with the
 * numeric bounds the submit form collects.
 */
add_filter( 'wpjm_get_job_listing_structured_data', function ( $data, $post ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$min = kajob_get_salary_min( $post->ID );
	$max = kajob_get_salary_max( $post->ID );

	if ( ! $min && ! $max ) {
		return $data;
	}

	$value = array(
		'@type'    => 'QuantitativeValue',
		'unitText' => get_post_meta( $post->ID, '_job_salary_unit', true ) ? get_post_meta( $post->ID, '_job_salary_unit', true ) : 'YEAR',
	);

	if ( $min && $max && $min !== $max ) {
		$value['minValue'] = $min;
		$value['maxValue'] = $max;
	} else {
		$value['value'] = $max ? $max : $min;
	}

	$data['baseSalary'] = array(
		'@type'    => 'MonetaryAmount',
		'currency' => get_post_meta( $post->ID, '_job_salary_currency', true ) ? get_post_meta( $post->ID, '_job_salary_currency', true ) : 'USD',
		'value'    => $value,
	);

	return $data;
}, 10, 2 );

/**
 * The theme draws its own meta and company panels, so the plugin's default
 * callbacks would print a second copy above the description.
 */
add_action( 'wp', function () {
	remove_action( 'single_job_listing_start', 'job_listing_meta_display', 20 );
	remove_action( 'single_job_listing_start', 'job_listing_company_display', 30 );
} );

/**
 * Normalised application method for the apply card.
 *
 * @param int $post_id Job ID.
 * @return array{type:string,href:string,label:string,detail:string,open:bool,reason:string}
 */
function kajob_job_apply( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$out = array(
		'type'   => 'none',
		'href'   => '',
		'label'  => __( 'Apply now', 'kajob' ),
		'detail' => '',
		'copy'   => '',
		'recipient' => '',
		'open'   => false,
		'reason' => '',
	);

	if ( '1' === (string) get_post_meta( $post_id, '_filled', true ) ) {
		$out['reason'] = __( 'This position has been filled.', 'kajob' );

		return $out;
	}

	if ( function_exists( 'candidates_can_apply' ) && ! candidates_can_apply( $post_id ) ) {
		$out['reason'] = 'expired' === get_post_status( $post_id )
			? __( 'This listing has expired.', 'kajob' )
			: __( 'Applications are closed for this role.', 'kajob' );

		return $out;
	}

	$method = function_exists( 'get_the_job_application_method' ) ? get_the_job_application_method( $post_id ) : false;

	if ( ! $method || empty( $method->type ) ) {
		$out['reason'] = __( 'The employer has not published an application route yet.', 'kajob' );

		return $out;
	}

	$out['open'] = true;
	$out['type'] = $method->type;

	if ( 'email' === $method->type ) {
		$out['href']   = 'mailto:' . $method->email . '?subject=' . rawurlencode( wp_specialchars_decode( $method->subject, ENT_QUOTES ) );
		$out['label']  = __( 'Apply by email', 'kajob' );
		$out['detail'] = __( 'Send your CV and a short note to', 'kajob' );
		$out['copy']   = $method->raw_email;
		$out['recipient'] = sanitize_email( $method->email );
	} else {
		$out['href']   = $method->url;
		$out['label']  = __( 'Apply on company site', 'kajob' );
		$out['detail'] = __( 'Applications are handled on', 'kajob' );
		$out['copy']   = wp_parse_url( $method->url, PHP_URL_HOST );
	}

	return $out;
}

/**
 * Closing date, if the employer set one.
 */
function kajob_job_deadline( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$raw     = get_post_meta( $post_id, '_job_expires', true );

	if ( ! $raw ) {
		return '';
	}

	$timestamp = strtotime( $raw );

	return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : '';
}

/**
 * "At a glance" rows for the single job. Only rows with a real value are kept.
 */
function kajob_job_facts( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$facts   = array();

	$salary = kajob_get_salary_label( $post_id, false );

	if ( $salary ) {
		$facts[] = array( 'cash', __( 'Salary', 'kajob' ), $salary . ' ' . kajob_salary_unit_label( $post_id ) );
	}

	$location = get_post_meta( $post_id, '_job_location', true );

	if ( $location ) {
		$facts[] = array( 'pin', __( 'Location', 'kajob' ), $location );
	}

	$facts[] = array( 'globe', __( 'Workplace', 'kajob' ), kajob_is_remote( $post_id ) ? __( 'Remote-friendly', 'kajob' ) : __( 'On site', 'kajob' ) );

	if ( function_exists( 'wpjm_get_the_job_types' ) ) {
		$types = wpjm_get_the_job_types( $post_id );

		if ( $types && ! is_wp_error( $types ) ) {
			$facts[] = array( 'briefcase', __( 'Employment', 'kajob' ), implode( ', ', wp_list_pluck( $types, 'name' ) ) );
		}
	}

	$facts[] = array( 'calendar', __( 'Posted', 'kajob' ), date_i18n( get_option( 'date_format' ), get_post_time( 'U', true, $post_id ) ) );

	$deadline = kajob_job_deadline( $post_id );

	if ( $deadline ) {
		$facts[] = array( 'clock', __( 'Apply before', 'kajob' ), $deadline );
	}

	$terms = get_the_terms( $post_id, 'job_listing_category' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$facts[] = array( 'layers', __( 'Category', 'kajob' ), implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
	}

	return $facts;
}

/**
 * Other open roles at the same company, excluding the one being viewed.
 */
function kajob_company_role_count( $company, $exclude = 0 ) {
	if ( '' === trim( (string) $company ) ) {
		return 0;
	}

	$query = new WP_Query( array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'post__not_in'   => array( absint( $exclude ) ),
		'meta_query'     => array(
			array( 'key' => '_company_name', 'value' => $company ),
		),
	) );

	return (int) $query->found_posts;
}

/**
 * Roles worth showing under the one being read: same category first, then the
 * newest listings, always excluding the current job.
 */
function kajob_related_jobs( $post_id = null, $limit = 3 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$slugs   = array();
	$terms   = get_the_terms( $post_id, 'job_listing_category' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$slugs = wp_list_pluck( $terms, 'slug' );
	}

	$args = array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'post__not_in'   => array( $post_id ),
		'has_password'   => false,
		'no_found_rows'  => true,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array( 'key' => '_filled', 'value' => '1', 'compare' => '!=' ),
				array( 'key' => '_filled', 'compare' => 'NOT EXISTS' ),
			),
		),
	);

	if ( $slugs ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'job_listing_category',
				'field'    => 'slug',
				'terms'    => $slugs,
			),
		);
	}

	$query = new WP_Query( $args );

	if ( count( $query->posts ) < $limit ) {
		$exclude = array_merge( array( $post_id ), wp_list_pluck( $query->posts, 'ID' ) );

		$fill = new WP_Query( array_merge( $args, array(
			'posts_per_page' => $limit - count( $query->posts ),
			'post__not_in'   => $exclude,
			'tax_query'      => array(),
		) ) );

		$query->posts = array_merge( $query->posts, $fill->posts );
	}

	return $query->posts;
}

/* ==========================================================================
   Employer side
   ========================================================================== */

/**
 * Status tallies for the signed-in employer's own listings.
 */
function kajob_employer_stats( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	$stats = array(
		'publish' => 0,
		'pending' => 0,
		'filled'  => 0,
		'draft'   => 0,
		'expired' => 0,
		'total'   => 0,
	);

	if ( ! $user_id ) {
		return $stats;
	}

	global $wpdb;

	// count_many_users_posts() returns one total per author, not a per-status
	// breakdown, and its public-only mode hides the drafts this dashboard needs.
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT post_status, COUNT(*) AS total FROM {$wpdb->posts} WHERE post_type = 'job_listing' AND post_author = %d GROUP BY post_status",
		$user_id
	) );

	foreach ( (array) $rows as $row ) {
		$count  = absint( $row->total );
		$status = 'preview' === $row->post_status ? 'draft' : $row->post_status;

		$stats['total'] += $count;

		if ( isset( $stats[ $status ] ) ) {
			$stats[ $status ] += $count;
		}
	}

	$filled = get_posts( array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'author'         => $user_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_key'       => '_filled',
		'meta_value'     => '1',
	) );

	$stats['filled'] = count( $filled );

	return $stats;
}

/**
 * Human label for a listing's dashboard state.
 */
function kajob_listing_state( $post_id ) {
	$status = get_post_status( $post_id );
	$filled = '1' === (string) get_post_meta( $post_id, '_filled', true );

	if ( $filled ) {
		return array( 'filled', __( 'Filled', 'kajob' ), 'sky' );
	}

	$map = array(
		'publish' => array( 'live', __( 'Live', 'kajob' ), 'mint' ),
		'pending' => array( 'review', __( 'In review', 'kajob' ), 'amber' ),
		'draft'   => array( 'draft', __( 'Draft', 'kajob' ), 'violet' ),
		'expired' => array( 'expired', __( 'Expired', 'kajob' ), 'coral' ),
		'preview' => array( 'draft', __( 'Draft', 'kajob' ), 'violet' ),
	);

	return isset( $map[ $status ] ) ? $map[ $status ] : array( 'draft', ucfirst( $status ), 'violet' );
}
