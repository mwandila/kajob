<?php
/**
 * The main template file (blog home when no static front page is set).
 *
 * @package Kajob
 */

get_header();
?>

<div class="container page-layout">

	<div id="primary" class="page-layout__main">
		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title">
					<?php
					if ( is_home() && ! is_front_page() ) {
						single_post_title();
					} elseif ( is_archive() ) {
						the_archive_title();
					} elseif ( is_search() ) {
						/* translators: %s: search query. */
						printf( esc_html__( 'Search Results for: %s', 'kajob' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
					} else {
						esc_html_e( 'Latest Posts', 'kajob' );
					}
					?>
				</h1>
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
