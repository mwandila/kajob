<?php
/**
 * Articles index — the page set as "Posts page" in Settings → Reading.
 *
 * @package Kajob
 */

get_header();

global $wp_query;

$kajob_topics = get_terms( array(
	'taxonomy'   => 'category',
	'hide_empty' => true,
	'exclude'    => array( 1 ),
	'orderby'    => 'count',
	'order'      => 'DESC',
) );

$kajob_total = (int) $wp_query->found_posts;
$kajob_first = 1 === max( 1, (int) get_query_var( 'paged' ) );
?>

<div class="board advice-board">

	<section class="board-hero kajob-grain">
		<div class="aurora" aria-hidden="true"><span class="aurora__blob aurora__blob--1"></span><span class="aurora__blob aurora__blob--2"></span></div>
		<div class="container board-hero__inner">
			<span class="section__eyebrow board-hero__eyebrow"><?php esc_html_e( 'Career advice', 'kajob' ); ?></span>
			<h1 class="board-hero__title"><?php single_post_title(); ?></h1>
			<p class="board-hero__sub"><?php esc_html_e( 'Practical guidance for the parts of hiring nobody explains: reading a salary band, negotiating an offer, and judging a remote team from its job description.', 'kajob' ); ?></p>
			<div class="board-hero__meta">
				<span class="chip chip--violet"><?php kajob_icon( 'file', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( _n( '%s article', '%s articles', $kajob_total, 'kajob' ), number_format_i18n( $kajob_total ) ) ); ?></span>
				<?php if ( ! is_wp_error( $kajob_topics ) && $kajob_topics ) : ?>
					<span class="chip"><?php kajob_icon( 'layers', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( _n( '%s topic', '%s topics', count( $kajob_topics ), 'kajob' ), number_format_i18n( count( $kajob_topics ) ) ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( ! is_wp_error( $kajob_topics ) && count( $kajob_topics ) > 1 ) : ?>
		<nav class="term-nav container" aria-label="<?php esc_attr_e( 'Article topics', 'kajob' ); ?>">
			<span class="term-nav__label"><?php esc_html_e( 'Topics', 'kajob' ); ?></span>
			<div class="term-nav__list">
				<a class="chip is-active" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php kajob_icon( 'compass', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Everything', 'kajob' ); ?></a>
				<?php foreach ( $kajob_topics as $kajob_topic ) : ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $kajob_topic ) ); ?>">
						<?php echo esc_html( $kajob_topic->name ); ?>
						<span class="term-nav__count"><?php echo esc_html( number_format_i18n( $kajob_topic->count ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</nav>
	<?php endif; ?>

	<div class="container advice-body">
		<?php if ( have_posts() ) : ?>

			<?php
			if ( $kajob_first ) :
				the_post();
				$kajob_lead_cats = get_the_category();
				?>
				<article <?php post_class( 'lead-article' ); ?> data-reveal>
					<a class="lead-article__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( kajob_has_article_photo_thumbnail( get_the_ID() ) ) : ?>
							<?php the_post_thumbnail( 'large' ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( kajob_get_article_cover_image( get_the_ID() ) ); ?>" alt="" loading="eager" decoding="async">
						<?php endif; ?>
					</a>

					<div class="lead-article__body">
						<div class="lead-article__meta">
							<span class="chip chip--amber"><?php kajob_icon( 'sparkle', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Latest', 'kajob' ); ?></span>
							<?php if ( $kajob_lead_cats ) : ?>
								<span class="chip chip--violet"><?php echo esc_html( $kajob_lead_cats[0]->name ); ?></span>
							<?php endif; ?>
							<span class="lead-article__time"><?php kajob_icon( 'clock', array( 'size' => 14 ) ); ?><?php echo esc_html( kajob_reading_time() ); ?></span>
						</div>

						<h2 class="lead-article__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="lead-article__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

						<div class="lead-article__foot">
							<span class="lead-article__byline">
								<?php echo get_avatar( get_the_author_meta( 'ID' ), 34, '', get_the_author(), array( 'class' => 'lead-article__avatar' ) ); ?>
								<span>
									<strong><?php the_author(); ?></strong>
									<small><?php echo esc_html( get_the_date() ); ?></small>
								</span>
							</span>
							<a class="btn btn--primary" href="<?php the_permalink(); ?>">
								<?php esc_html_e( 'Read article', 'kajob' ); ?>
								<?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
							</a>
						</div>
					</div>
				</article>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'card' );
					endwhile;
					?>
				</div>
			<?php elseif ( $kajob_first ) : ?>
				<p class="u-muted"><?php esc_html_e( 'More articles are on the way.', 'kajob' ); ?></p>
			<?php endif; ?>

			<?php
			the_posts_pagination( array(
				'mid_size'  => 1,
				'prev_text' => kajob_get_icon( 'arrow-left', array( 'size' => 16 ) ) . '<span>' . esc_html__( 'Previous', 'kajob' ) . '</span>',
				'next_text' => '<span>' . esc_html__( 'Next', 'kajob' ) . '</span>' . kajob_get_icon( 'arrow-right', array( 'size' => 16 ) ),
			) );
			?>

		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>

		<aside class="advice-cta" data-reveal>
			<div>
				<h2 class="advice-cta__title"><?php esc_html_e( 'Ready to put it into practice?', 'kajob' ); ?></h2>
				<p class="advice-cta__text">
					<?php
					printf(
						/* translators: %s: number of live listings. */
						esc_html__( '%s open roles, every one with the salary shown.', 'kajob' ),
						esc_html( number_format_i18n( kajob_get_job_count() ) )
					);
					?>
				</p>
			</div>
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>">
				<?php kajob_icon( 'search', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Browse jobs', 'kajob' ); ?>
			</a>
		</aside>
	</div>
</div>

<?php
get_footer();
