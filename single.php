<?php
/**
 * Single article.
 *
 * @package Kajob
 */

get_header();

while ( have_posts() ) :
	the_post();

	$kajob_cats  = get_the_category();
	$kajob_topic = $kajob_cats ? $kajob_cats[0] : null;
	?>

	<div class="reading-progress" data-reading-progress aria-hidden="true"><span></span></div>

	<article <?php post_class( 'article-single' ); ?>>

		<header class="article-hero kajob-grain">
			<div class="aurora" aria-hidden="true"><span class="aurora__blob aurora__blob--1"></span><span class="aurora__blob aurora__blob--2"></span></div>
			<div class="container article-hero__inner">
				<nav class="breadcrumbs breadcrumbs--light" aria-label="<?php esc_attr_e( 'Breadcrumb', 'kajob' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'kajob' ); ?></a>
					<?php kajob_icon( 'chevron-right', array( 'size' => 13 ) ); ?>
					<a href="<?php echo esc_url( kajob_get_blog_url() ); ?>"><?php esc_html_e( 'Career advice', 'kajob' ); ?></a>
					<?php if ( $kajob_topic ) : ?>
						<?php kajob_icon( 'chevron-right', array( 'size' => 13 ) ); ?>
						<a href="<?php echo esc_url( get_category_link( $kajob_topic ) ); ?>"><?php echo esc_html( $kajob_topic->name ); ?></a>
					<?php endif; ?>
				</nav>

				<h1 class="article-hero__title"><?php the_title(); ?></h1>
				<p class="article-hero__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

				<div class="article-hero__meta">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', get_the_author(), array( 'class' => 'article-hero__avatar' ) ); ?>
					<span class="article-hero__byline">
						<strong><?php the_author(); ?></strong>
						<small><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo esc_html( kajob_reading_time() ); ?></small>
					</span>
					<div class="article-hero__actions">
						<button type="button" class="icon-btn icon-btn--light" data-share data-share-title="<?php the_title_attribute(); ?>" aria-label="<?php esc_attr_e( 'Share this article', 'kajob' ); ?>">
							<?php kajob_icon( 'share', array( 'size' => 18 ) ); ?>
						</button>
						<button type="button" class="icon-btn icon-btn--light" data-copy="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'kajob' ); ?>">
							<?php kajob_icon( 'link', array( 'size' => 18 ) ); ?>
						</button>
					</div>
				</div>
			</div>
		</header>

		<div class="container">
			<div class="article-layout">
				<div class="article-layout__main">
					<figure class="article-cover<?php echo kajob_has_article_photo_thumbnail( get_the_ID() ) ? '' : ' article-cover--fallback'; ?>">
						<?php if ( kajob_has_article_photo_thumbnail( get_the_ID() ) ) : ?>
							<?php the_post_thumbnail( 'large' ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( kajob_get_article_cover_image( get_the_ID() ) ); ?>" alt="" fetchpriority="high" decoding="async">
						<?php endif; ?>
					</figure>

					<div class="job-panel">
						<div class="entry-content" data-article-body>
							<?php
							the_content();

							wp_link_pages( array(
								'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'kajob' ),
								'after'  => '</nav>',
							) );
							?>
						</div>

						<?php if ( get_the_tag_list() ) : ?>
							<div class="entry-footer"><?php the_tags( '<div class="tags-links"><span>' . esc_html__( 'Tagged', 'kajob' ) . '</span> ', '', '</div>' ); ?></div>
						<?php endif; ?>
					</div>

					<section class="author-card" data-reveal>
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 64, '', get_the_author(), array( 'class' => 'author-card__avatar' ) ); ?>
						<div class="author-card__body">
							<span class="section__eyebrow"><?php esc_html_e( 'Written by', 'kajob' ); ?></span>
							<h2 class="author-card__name"><?php the_author(); ?></h2>
							<p class="author-card__bio">
								<?php
								$kajob_bio = get_the_author_meta( 'description' );
								echo esc_html( $kajob_bio ? $kajob_bio : __( 'Writes about the practical side of hiring for the kajob career desk — pay transparency, interviews and the first ninety days.', 'kajob' ) );
								?>
							</p>
							<a class="section__link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
								<?php esc_html_e( 'More from this author', 'kajob' ); ?>
								<?php kajob_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
							</a>
						</div>
					</section>

					<?php
					the_post_navigation( array(
						'prev_text' => '<span class="nav-label">' . kajob_get_icon( 'arrow-left', array( 'size' => 14 ) ) . esc_html__( 'Previous', 'kajob' ) . '</span><span class="nav-title">%title</span>',
						'next_text' => '<span class="nav-label">' . esc_html__( 'Next', 'kajob' ) . kajob_get_icon( 'arrow-right', array( 'size' => 14 ) ) . '</span><span class="nav-title">%title</span>',
					) );

					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
					?>
				</div>

				<aside class="article-layout__aside">
					<?php
					$kajob_related_posts = get_posts( array(
						'post_type'      => 'post',
						'post_status'    => 'publish',
						'posts_per_page' => 3,
						'post__not_in'   => array( get_the_ID() ),
						'category__in'   => $kajob_topic ? array( $kajob_topic->term_id ) : array(),
						'no_found_rows'  => true,
					) );

					if ( count( $kajob_related_posts ) < 3 ) {
						$exclude = array_merge( array( get_the_ID() ), wp_list_pluck( $kajob_related_posts, 'ID' ) );

						$kajob_related_posts = array_merge( $kajob_related_posts, get_posts( array(
							'post_type'      => 'post',
							'post_status'    => 'publish',
							'posts_per_page' => 3 - count( $kajob_related_posts ),
							'post__not_in'   => $exclude,
							'no_found_rows'  => true,
						) ) );
					}

					if ( $kajob_related_posts ) :
						?>
						<section class="job-panel">
							<h2 class="job-panel__title"><?php kajob_icon( 'layers', array( 'size' => 18 ) ); ?><?php esc_html_e( 'Keep reading', 'kajob' ); ?></h2>
							<ul class="widget-jobs">
								<?php foreach ( $kajob_related_posts as $kajob_related ) : ?>
									<li class="widget-job">
										<a href="<?php echo esc_url( get_permalink( $kajob_related ) ); ?>">
											<span class="widget-job__logo widget-job__logo--fallback" style="background:<?php echo esc_attr( kajob_company_gradient( $kajob_related->post_title ) ); ?>" aria-hidden="true"><?php kajob_icon( 'file', array( 'size' => 16 ) ); ?></span>
											<span class="widget-job__body">
												<span class="widget-job__title"><?php echo esc_html( get_the_title( $kajob_related ) ); ?></span>
												<span class="widget-job__meta"><?php echo esc_html( kajob_reading_time( $kajob_related->ID ) ); ?></span>
											</span>
											<span class="widget-job__arrow" aria-hidden="true"><?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php
					$kajob_side_jobs = kajob_get_job_cards(
						array( 'post_status' => 'publish', 'has_password' => false ),
						array( 'per_page' => 4, 'variant' => 'list' )
					);
					?>
					<section class="job-panel">
						<h2 class="job-panel__title"><?php kajob_icon( 'briefcase', array( 'size' => 18 ) ); ?><?php esc_html_e( 'Roles hiring now', 'kajob' ); ?></h2>
						<ul class="widget-jobs">
							<?php foreach ( $kajob_side_jobs['query']->posts as $kajob_side ) : ?>
								<li class="widget-job">
									<a href="<?php echo esc_url( get_permalink( $kajob_side ) ); ?>">
										<?php echo kajob_get_company_logo( $kajob_side->ID, 'widget-job__logo' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
										<span class="widget-job__body">
											<span class="widget-job__title"><?php echo esc_html( get_the_title( $kajob_side ) ); ?></span>
											<span class="widget-job__meta"><?php echo esc_html( kajob_get_salary_label( $kajob_side->ID ) ); ?></span>
										</span>
										<span class="widget-job__arrow" aria-hidden="true"><?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
						<a class="btn btn--outline btn--block u-mt-4" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>"><?php esc_html_e( 'Browse all jobs', 'kajob' ); ?></a>
					</section>
				</aside>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
