<?php
/**
 * Employment type archive: the board with this type pre-applied.
 *
 * @package Kajob
 */

get_header();

$kajob_term = get_queried_object();

if ( $kajob_term instanceof WP_Term ) {
	$kajob_context = array(
		'eyebrow'     => __( 'Employment type', 'kajob' ),
		'title'       => sprintf( /* translators: %s: employment type. */ __( '%s roles', 'kajob' ), $kajob_term->name ),
		'description' => $kajob_term->description
			? $kajob_term->description
			: sprintf(
				/* translators: %s: employment type. */
				__( 'Open %s positions across every category. Filter by salary floor, location and how recently the role was posted.', 'kajob' ),
				strtolower( $kajob_term->name )
			),
	);

	echo kajob_render_board( // phpcs:ignore WordPress.Security.EscapeOutput
		array(),
		$kajob_context,
		kajob_board_source( array( 'job_types' => array( $kajob_term->slug ) ) )
	);

	echo kajob_board_term_nav( 'job_listing_type', $kajob_term->slug ); // phpcs:ignore WordPress.Security.EscapeOutput
} else {
	echo kajob_render_board(); // phpcs:ignore WordPress.Security.EscapeOutput
}

get_footer();