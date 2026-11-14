<?php
/**
 * Job category archive: the board with this category pre-applied.
 *
 * @package Kajob
 */

get_header();

$kajob_term = get_queried_object();

if ( $kajob_term instanceof WP_Term ) {
	$kajob_context = array(
		'eyebrow'     => __( 'Category', 'kajob' ),
		'title'       => sprintf( /* translators: %s: category name. */ __( '%s jobs', 'kajob' ), $kajob_term->name ),
		'description' => $kajob_term->description
			? $kajob_term->description
			: sprintf(
				/* translators: %s: category name. */
				__( 'Every open %s role on the board. Narrow by pay, location and freshness — results update as you type.', 'kajob' ),
				$kajob_term->name
			),
	);

	echo kajob_render_board( // phpcs:ignore WordPress.Security.EscapeOutput
		array(),
		$kajob_context,
		kajob_board_source( array( 'search_categories' => array( $kajob_term->slug ) ) )
	);

	echo kajob_board_term_nav( 'job_listing_category', $kajob_term->slug ); // phpcs:ignore WordPress.Security.EscapeOutput
} else {
	echo kajob_render_board(); // phpcs:ignore WordPress.Security.EscapeOutput
}

get_footer();