<?php
/**
 * Full single post content.
 *
 * @package Kajob
 */

?>
<article <?php post_class( 'single-post' ); ?>>

	<header class="single-post__header">
		<div class="single-post__meta">
			<?php kajob_posted_on(); ?>
			<?php kajob_posted_by(); ?>
		</div>

		<h1 class="single-post__title"><?php the_title(); ?></h1>
	</header>

	<figure class="single-post__thumbnail<?php echo kajob_has_article_photo_thumbnail( get_the_ID() ) ? '' : ' single-post__thumbnail--fallback'; ?>">
		<?php if ( kajob_has_article_photo_thumbnail( get_the_ID() ) ) : ?>
			<?php the_post_thumbnail( 'full' ); ?>
		<?php else : ?>
			<img src="<?php echo esc_url( kajob_get_article_cover_image( get_the_ID() ) ); ?>" alt="" fetchpriority="high" decoding="async">
		<?php endif; ?>
	</figure>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages( array(
			'before' => '<nav class="page-links">' . __( 'Pages:', 'kajob' ),
			'after'  => '</nav>',
		) );
		?>
	</div>

	<footer class="single-post__footer">
		<?php the_tags( '<div class="single-post__tags"><span>' . __( 'Tags:', 'kajob' ) . '</span> ', ', ', '</div>' ); ?>
	</footer>

</article>
