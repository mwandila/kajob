<?php
/**
 * Search results — jobs first, then articles.
 *
 * On a job board a search is almost always about a role, so listings lead and
 * the article matches follow rather than the other way round.
 *
 * @package Kajob
 */

get_header();

global $wp_query;

$kajob_term = get_search_query();
$kajob_jobs = kajob_is_job_manager_active() && '' !== $kajob_term
	? kajob_get_job_cards(
		array(
			's'            => $kajob_term,
			'post_status'  => 'publish',
			'has_password' => false,
		),
		array( 'per_page' => 6, 'variant' => 'list' )
	)
	: array( 'html' => '', 'query' => null );

$kajob_job_found  = $kajob_jobs['query'] ? (int) $kajob_jobs['query']->found_posts : 0;
$kajob_post_found = (int) $wp_query->found_posts;
$kajob_has_posts  = have_posts();
?>

<div class="board search-board">

	<section class="board-hero kajob-grain">
		<div class="container board-hero__inner">
			<span class="section__eyebrow board-hero__eyebrow"><?php esc_html_e( 'Search', 'kajob' ); ?></span>
			<h1 class="board-hero__title">
				<?php
				/* translators: %s: the search term. */
				printf( esc_html__( 'Results for “%s”', 'kajob' ), esc_html( $kajob_term ) );
				?>
			</h1>
			<p class="board-hero__sub"><?php esc_html_e( 'Open roles are listed first, followed by anything we have written on the same subject.', 'kajob' ); ?></p>

			<form role="search" method="get" class="board-hero__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="filter-input">
					<?php kajob_icon( 'search', array( 'size' => 18 ) ); ?>
					<label class="screen-reader-text" for="kajob-search-again"><?php esc_html_e( 'Search again', 'kajob' ); ?></label>
					<input type="search" id="kajob-search-again" name="s" value="<?php echo esc_attr( $kajob_term ); ?>" placeholder="<?php esc_attr_e( 'Role, skill, company or topic', 'kajob' ); ?>" />
				</div>
				<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Search', 'kajob' ); ?></button>
			</form>

			<div class="board-hero__meta">
				<span class="chip chip--mint"><?php kajob_icon( 'briefcase', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( _n( '%s job', '%s jobs', $kajob_job_found, 'kajob' ), number_format_i18n( $kajob_job_found ) ) ); ?></span>
				<span class="chip chip--violet"><?php kajob_icon( 'file', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( _n( '%s article', '%s articles', $kajob_post_found, 'kajob' ), number_format_i18n( $kajob_post_found ) ) ); ?></span>
			</div>
		</div>
	</section>

	<div class="container search-body">

		<?php if ( $kajob_job_found ) : ?>
			<section class="search-section">
				<header class="search-section__head">
					<h2 class="search-section__title"><?php kajob_icon( 'briefcase', array( 'size' => 19 ) ); ?><?php esc_html_e( 'Open roles', 'kajob' ); ?></h2>
					<a class="section__link" href="<?php echo esc_url( add_query_arg( 'search_keywords', $kajob_term, kajob_get_jobs_page_url() ) ); ?>">
						<?php
						/* translators: %s: number of matching jobs. */
						printf( esc_html__( 'Filter all %s on the board', 'kajob' ), esc_html( number_format_i18n( $kajob_job_found ) ) );
						?>
						<?php kajob_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
				</header>
				<div class="job-list"><?php echo $kajob_jobs['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in card helper. ?></div>
			</section>
		<?php endif; ?>

		<?php if ( $kajob_has_posts ) : ?>
			<section class="search-section">
				<header class="search-section__head">
					<h2 class="search-section__title"><?php kajob_icon( 'file', array( 'size' => 19 ) ); ?><?php esc_html_e( 'Career advice', 'kajob' ); ?></h2>
					<a class="section__link" href="<?php echo esc_url( kajob_get_blog_url() ); ?>">
						<?php esc_html_e( 'All articles', 'kajob' ); ?>
						<?php kajob_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
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
					'prev_text' => kajob_get_icon( 'arrow-left', array( 'size' => 16 ) ) . '<span>' . esc_html__( 'Previous', 'kajob' ) . '</span>',
					'next_text' => '<span>' . esc_html__( 'Next', 'kajob' ) . '</span>' . kajob_get_icon( 'arrow-right', array( 'size' => 16 ) ),
				) );
				?>
			</section>
		<?php endif; ?>

		<?php if ( ! $kajob_job_found && ! $kajob_has_posts ) : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
