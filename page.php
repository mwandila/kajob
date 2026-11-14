<?php
/**
 * Static page template.
 *
 * @package Kajob
 */

get_header();
?>

<div class="container page-layout">

	<div id="primary" class="page-layout__main">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'single-page' ); ?>>
				<?php if ( ! is_front_page() ) : ?>
					<header class="page-header">
						<h1 class="page-title"><?php the_title(); ?></h1>
					</header>
				<?php endif; ?>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="single-page__thumbnail">
						<?php the_post_thumbnail( 'kajob-card' ); ?>
					</figure>
				<?php endif; ?>

				<div class="entry-content">
					<?php
					the_content();

					wp_link_pages( array(
						'before' => '<nav class="page-links">' . __( 'Pages:', 'kajob' ),
						'after'  => '</nav>',
					) );
					?>
				</div>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
