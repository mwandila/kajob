<?php
/**
 * Archive template (category, tag, date, author, custom post type).
 *
 * @package Kajob
 */

get_header();
?>

<div class="container page-layout">

	<div id="primary" class="page-layout__main">
		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title"><?php the_archive_title(); ?></h1>
				<?php the_archive_description( '<div class="page-description">', '</div>' ); ?>
			</header>

			<div class="post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination( array(
				'mid_size'  => 1,
				'prev_text' => __( '&larr; Previous', 'kajob' ),
				'next_text' => __( 'Next &rarr;', 'kajob' ),
			) );
			?>

		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>

	<?php get_sidebar(); ?>

</div>

<?php
get_footer();
