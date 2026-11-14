<?php
/**
 * Comments template.
 *
 * @package Kajob
 */

if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-area__title">
			<?php
			$count = get_comments_number();
			/* translators: %s: comment count. */
			printf( esc_html( _n( '%s Comment', '%s Comments', $count, 'kajob' ) ), number_format_i18n( $count ) );
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => true,
				'avatar_size' => 48,
			) );
			?>
		</ol>

		<?php
		the_comments_pagination( array(
			'prev_text' => __( '&larr; Older comments', 'kajob' ),
			'next_text' => __( 'Newer comments &rarr;', 'kajob' ),
		) );
		?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'kajob' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply' => __( 'Leave a Reply', 'kajob' ),
	) );
	?>

</section>
