<?php
/**
 * Public AJAX endpoints: search suggestions, saved jobs, job alerts.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guard shared by the public endpoints.
 */
function kajob_ajax_guard() {
	$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'kajob_public' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page.', 'kajob' ) ), 403 );
	}
}

/**
 * Live search suggestions for the hero field and command palette.
 */
function kajob_ajax_suggest() {
	kajob_ajax_guard();

	$term = isset( $_REQUEST['term'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term'] ) ) : '';

	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	if ( ! kajob_is_job_manager_active() ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	$query = new WP_Query( array(
		'post_type'           => 'job_listing',
		'post_status'         => 'publish',
		'posts_per_page'      => 6,
		's'                   => $term,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'has_password'        => false,
	) );

	$items = array();

	foreach ( $query->posts as $job ) {
		if ( function_exists( 'job_manager_user_can_view_job_listing' ) && ! job_manager_user_can_view_job_listing( $job->ID ) ) {
			continue;
		}

		$company  = get_post_meta( $job->ID, '_company_name', true );
		$location = get_post_meta( $job->ID, '_job_location', true );
		$salary   = kajob_get_salary_label( $job->ID );

		$meta_parts = array_filter( array( $company, $location ) );

		$items[] = array(
			'id'       => $job->ID,
			'title'    => get_the_title( $job ),
			'company'  => $company,
			'meta'     => implode( ' · ', $meta_parts ),
			'salary'   => $salary,
			'remote'   => kajob_is_remote( $job->ID ),
			'url'      => get_permalink( $job->ID ),
			'initials' => kajob_company_initials( $company ? $company : get_the_title( $job ) ),
			'gradient' => kajob_company_gradient( $company ? $company : get_the_title( $job ) ),
			'logo'     => get_the_post_thumbnail_url( $job->ID, 'thumbnail' ),
		);
	}

	$categories = array();

	if ( taxonomy_exists( 'job_listing_category' ) ) {
		$terms = get_terms( array(
			'taxonomy'   => 'job_listing_category',
			'hide_empty' => true,
			'number'     => 3,
			'name__like' => $term,
		) );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$categories[] = array(
					'title' => $t->name,
					'count' => (int) $t->count,
					'url'   => get_term_link( $t ),
				);
			}
		}
	}

	wp_send_json_success( array(
		'items'      => $items,
		'categories' => $categories,
	) );
}
add_action( 'wp_ajax_kajob_suggest', 'kajob_ajax_suggest' );
add_action( 'wp_ajax_nopriv_kajob_suggest', 'kajob_ajax_suggest' );

/**
 * Toggle a saved job for the logged-in user.
 */
function kajob_ajax_toggle_save() {
	kajob_ajax_guard();

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Sign in to sync saved jobs across devices.', 'kajob' ) ), 401 );
	}

	$job_id = isset( $_REQUEST['job_id'] ) ? absint( wp_unslash( $_REQUEST['job_id'] ) ) : 0;

	if ( ! $job_id || 'job_listing' !== get_post_type( $job_id ) || 'publish' !== get_post_status( $job_id ) ) {
		wp_send_json_error( array( 'message' => __( 'That job is no longer available.', 'kajob' ) ), 404 );
	}

	$user_id = get_current_user_id();
	$saved   = kajob_get_saved_job_ids();
	$is_saved = in_array( $job_id, $saved, true );

	if ( $is_saved ) {
		$saved = array_values( array_diff( $saved, array( $job_id ) ) );
	} else {
		array_unshift( $saved, $job_id );
		$saved = array_slice( array_unique( $saved ), 0, 200 );
	}

	update_user_meta( $user_id, 'kajob_saved_jobs', $saved );

	wp_send_json_success( array(
		'saved'  => ! $is_saved,
		'ids'    => $saved,
		'count'  => count( $saved ),
	) );
}
add_action( 'wp_ajax_kajob_toggle_save', 'kajob_ajax_toggle_save' );

/**
 * Replace a guest's local list with the server list once they sign in.
 */
function kajob_ajax_sync_saved() {
	kajob_ajax_guard();

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Not signed in.', 'kajob' ) ), 401 );
	}

	$incoming = isset( $_REQUEST['ids'] ) ? (array) wp_unslash( $_REQUEST['ids'] ) : array();
	$incoming = array_values( array_filter( array_map( 'absint', $incoming ) ) );
	$existing = kajob_get_saved_job_ids();
	$merged   = array_slice( array_unique( array_merge( $incoming, $existing ) ), 0, 200 );

	$valid = array();

	foreach ( $merged as $job_id ) {
		if ( 'job_listing' === get_post_type( $job_id ) && 'publish' === get_post_status( $job_id ) ) {
			$valid[] = $job_id;
		}
	}

	update_user_meta( get_current_user_id(), 'kajob_saved_jobs', $valid );

	wp_send_json_success( array(
		'ids'   => $valid,
		'count' => count( $valid ),
	) );
}
add_action( 'wp_ajax_kajob_sync_saved', 'kajob_ajax_sync_saved' );

/**
 * Render cards for an arbitrary set of job IDs (saved-jobs page).
 */
function kajob_ajax_saved_cards() {
	kajob_ajax_guard();

	$ids = isset( $_REQUEST['ids'] ) ? (array) wp_unslash( $_REQUEST['ids'] ) : array();
	$ids = array_slice( array_values( array_filter( array_map( 'absint', $ids ) ) ), 0, 60 );

	if ( empty( $ids ) ) {
		wp_send_json_success( array( 'html' => '', 'count' => 0 ) );
	}

	$query = new WP_Query( array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'post__in'       => $ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $ids ),
		'no_found_rows'  => true,
		'has_password'   => false,
	) );

	$html = '';

	foreach ( $query->posts as $job ) {
		if ( function_exists( 'job_manager_user_can_view_job_listing' ) && ! job_manager_user_can_view_job_listing( $job->ID ) ) {
			continue;
		}

		$html .= kajob_job_card( $job->ID );
	}

	wp_send_json_success( array(
		'html'  => $html,
		'count' => $query->post_count,
	) );
}
add_action( 'wp_ajax_kajob_saved_cards', 'kajob_ajax_saved_cards' );
add_action( 'wp_ajax_nopriv_kajob_saved_cards', 'kajob_ajax_saved_cards' );

/**
 * Job alert subscription.
 */
function kajob_ajax_subscribe() {
	kajob_ajax_guard();

	$email = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : '';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Enter a valid email address.', 'kajob' ) ), 400 );
	}

	// Rate limit per IP so the endpoint cannot be used to flood the table.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'kajob_sub_' . md5( $ip );

	if ( (int) get_transient( $key ) >= 5 ) {
		wp_send_json_error( array( 'message' => __( 'Too many attempts. Please try again later.', 'kajob' ) ), 429 );
	}

	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );

	$existing = get_posts( array(
		'post_type'      => 'kajob_subscriber',
		'post_status'    => 'any',
		'title'          => $email,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		wp_send_json_success( array(
			'message'  => __( 'You are already subscribed.', 'kajob' ),
			'existing' => true,
		) );
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'kajob_subscriber',
		'post_status' => 'publish',
		'post_title'  => $email,
	), true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not save your subscription. Please try again.', 'kajob' ) ), 500 );
	}

	$criteria = isset( $_REQUEST['criteria'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['criteria'] ) ) : '';

	if ( $criteria ) {
		update_post_meta( $post_id, '_kajob_alert_criteria', $criteria );
	}

	wp_send_json_success( array( 'message' => __( 'You are on the list. Watch your inbox.', 'kajob' ) ) );
}
add_action( 'wp_ajax_kajob_subscribe', 'kajob_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_kajob_subscribe', 'kajob_ajax_subscribe' );
