<?php
/**
 * Front page: the job board landing experience.
 *
 * Every figure on this page is read from live listings — the stat row, the
 * company marquee, the tabbed job rails and the pay chart all come from the
 * database, so an empty board degrades to honest empty states.
 *
 * @package Kajob
 */

// A small, shareable preview parameter makes all five homepage demos available
// from one install while leaving the site's configured front page untouched.
$kajob_home_demo = isset( $_GET['home-demo'] ) ? absint( wp_unslash( $_GET['home-demo'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$kajob_home_demo = max( 1, min( 5, $kajob_home_demo ) );

if ( 1 !== $kajob_home_demo ) {
	get_template_part( 'template-parts/home-demo-' . $kajob_home_demo );
	return;
}

get_header();

$kajob_stats     = kajob_get_board_stats();
$kajob_companies = kajob_get_board_companies( 12 );
$kajob_insights  = kajob_get_salary_insights( 6 );
$kajob_jobs_url  = kajob_get_jobs_page_url();
$kajob_job_total = (int) $kajob_stats['jobs'];

/**
 * Fetch one rail of jobs for the tabbed section.
 *
 * @param array $args    Query overrides.
 * @param int   $per_page How many cards.
 */
$kajob_rails = array(
	'latest'   => array(
		'label' => __( 'Latest', 'kajob' ),
		'icon'  => 'sparkle',
		'args'  => array( 'orderby' => 'date', 'order' => 'DESC' ),
		'url'   => $kajob_jobs_url,
	),
	'featured' => array(
		'label' => __( 'Featured', 'kajob' ),
		'icon'  => 'star',
		'args'  => array( 'meta_query' => array( array( 'key' => '_featured', 'value' => '1' ) ), 'orderby' => 'date' ),
		'url'   => add_query_arg( 'featured', '1', $kajob_jobs_url ),
	),
	'remote'   => array(
		'label' => __( 'Remote', 'kajob' ),
		'icon'  => 'globe',
		'args'  => array( 'meta_query' => array( array( 'key' => '_remote_position', 'value' => '1' ) ), 'orderby' => 'date' ),
		'url'   => add_query_arg( 'remote_position', '1', $kajob_jobs_url ),
	),
	'top-pay'  => array(
		'label' => __( 'Top pay', 'kajob' ),
		'icon'  => 'trending',
		'args'  => array(
			'meta_key'   => '_kajob_salary_max',
			'orderby'    => 'meta_value_num',
			'order'      => 'DESC',
			'meta_query' => array( array( 'key' => '_kajob_salary_max', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ) ),
		),
		'url'   => add_query_arg( 'orderby', 'salary', $kajob_jobs_url ),
	),
);

foreach ( $kajob_rails as $key => $rail ) {
	$cards = kajob_get_job_cards( array_merge( $rail['args'], array( 'no_found_rows' => true ) ), array( 'per_page' => absint( get_theme_mod( 'kajob_latest_jobs_count', 6 ) ) ) );
	$kajob_rails[ $key ]['html'] = $cards['html'];
}
?>

<section class="hero kajob-grain">
	<div class="container hero__inner">
		<span class="hero__badge">
			<span class="badge-dot badge-dot--live" aria-hidden="true"></span>
			<?php
			printf(
				/* translators: %s: number of live job listings. */
				esc_html( _n( '%s role live right now', '%s roles live right now', $kajob_job_total, 'kajob' ) ),
				esc_html( number_format_i18n( $kajob_job_total ) )
			);
			?>
		</span>

		<h1 class="hero__title">
			<?php
			$hero_title = get_theme_mod( 'kajob_hero_title', __( 'Find work that actually fits.', 'kajob' ) );
			$hero_words = explode( ' ', $hero_title );
			$hero_tail  = count( $hero_words ) > 2 ? array_pop( $hero_words ) : '';
			echo esc_html( implode( ' ', $hero_words ) );
			if ( $hero_tail ) {
				echo ' <em>' . esc_html( $hero_tail ) . '</em>';
			}
			?>
		</h1>

		<p class="hero__subtitle">
			<?php echo esc_html( get_theme_mod( 'kajob_hero_subtitle', __( 'Every listing shows the salary band and whether it is genuinely remote — before you spend an hour on an application.', 'kajob' ) ) ); ?>
		</p>

		<form class="job-search" role="search" method="get" action="<?php echo esc_url( $kajob_jobs_url ); ?>" data-job-search>
			<div class="job-search__field">
				<?php kajob_icon( 'search', array( 'size' => 18 ) ); ?>
				<label class="screen-reader-text" for="job-search-keyword"><?php esc_html_e( 'Job title, skill or company', 'kajob' ); ?></label>
				<input
					type="text"
					id="job-search-keyword"
					name="search_keywords"
					autocomplete="off"
					role="combobox"
					aria-expanded="false"
					aria-controls="kajob-suggest"
					aria-autocomplete="list"
					data-suggest-input
					placeholder="<?php esc_attr_e( 'Job title, skill or company', 'kajob' ); ?>">
			</div>

			<div class="job-search__field">
				<?php kajob_icon( 'pin', array( 'size' => 18 ) ); ?>
				<label class="screen-reader-text" for="job-search-location"><?php esc_html_e( 'Location', 'kajob' ); ?></label>
				<input type="text" id="job-search-location" name="search_location" placeholder="<?php esc_attr_e( 'City, or leave blank for anywhere', 'kajob' ); ?>">
			</div>

			<button type="submit" class="btn btn--primary job-search__submit">
				<?php kajob_icon( 'search', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Search', 'kajob' ); ?>
			</button>

			<div class="suggest" id="kajob-suggest" data-suggest role="listbox" aria-label="<?php esc_attr_e( 'Job suggestions', 'kajob' ); ?>"></div>
		</form>

		<div class="hero__popular">
			<span class="hero__popular-label"><?php esc_html_e( 'Popular:', 'kajob' ); ?></span>
			<?php
			$popular = array(
				array( add_query_arg( 'remote_position', '1', $kajob_jobs_url ), __( 'Remote', 'kajob' ) ),
				array( add_query_arg( array( 'search_categories' => 'engineering' ), $kajob_jobs_url ), __( 'Engineering', 'kajob' ) ),
				array( add_query_arg( array( 'search_categories' => 'design' ), $kajob_jobs_url ), __( 'Design', 'kajob' ) ),
				array( add_query_arg( array( 'job_types' => 'full-time' ), $kajob_jobs_url ), __( 'Full-time', 'kajob' ) ),
				array( add_query_arg( array( 'kajob_salary_min' => 120000 ), $kajob_jobs_url ), kajob_currency_symbol() . '120k+' ),
				array( add_query_arg( array( 'kajob_date_posted' => '7d' ), $kajob_jobs_url ), __( 'This week', 'kajob' ) ),
			);

			foreach ( $popular as $item ) {
				printf( '<a href="%1$s">%2$s</a>', esc_url( $item[0] ), esc_html( $item[1] ) );
			}
			?>
		</div>

		<div class="hero__stats">
			<?php
			$stat_items = array(
				array( $kajob_job_total, __( 'Open positions', 'kajob' ) ),
				array( (int) $kajob_stats['companies'], __( 'Hiring companies', 'kajob' ) ),
				array( (int) $kajob_stats['remote'], __( 'Remote-friendly', 'kajob' ) ),
				array( (int) $kajob_stats['categories'], __( 'Categories', 'kajob' ) ),
			);

			foreach ( $stat_items as $stat ) :
				?>
				<div class="hero-stat">
					<div class="hero-stat__value" data-count-to="<?php echo esc_attr( $stat[0] ); ?>"><?php echo esc_html( number_format_i18n( $stat[0] ) ); ?></div>
					<div class="hero-stat__label"><?php echo esc_html( $stat[1] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( $kajob_companies ) : ?>
	<div class="trust-strip">
		<div class="container trust-strip__inner">
			<span class="trust-strip__label"><?php esc_html_e( 'Teams hiring here', 'kajob' ); ?></span>
			<div class="trust-strip__track">
				<?php for ( $row = 0; $row < 2; $row++ ) : ?>
					<div class="trust-strip__row" <?php echo $row ? 'aria-hidden="true"' : ''; ?>>
						<?php
						foreach ( $kajob_companies as $company ) {
							printf(
								'<a class="trust-strip__logo" href="%1$s"%2$s><i style="background:%3$s"></i>%4$s</a>',
								esc_url( $company['url'] ),
								$row ? ' tabindex="-1"' : '',
								esc_attr( kajob_company_gradient( $company['name'] ) ),
								esc_html( $company['name'] )
							);
						}
						?>
					</div>
				<?php endfor; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<section class="section section--paper" id="categories">
	<div class="container">
		<div class="section__head" data-reveal>
			<div>
				<span class="section__eyebrow"><?php esc_html_e( 'Browse', 'kajob' ); ?></span>
				<h2 class="section__title"><?php echo esc_html( get_theme_mod( 'kajob_categories_title', __( 'Pick your field.', 'kajob' ) ) ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'Categories are ordered by how many roles are actually open, so the busiest fields come first.', 'kajob' ); ?></p>
			</div>
			<a class="section__link" href="<?php echo esc_url( $kajob_jobs_url ); ?>">
				<?php esc_html_e( 'All jobs', 'kajob' ); ?>
				<?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
			</a>
		</div>

		<div class="category-grid" data-reveal>
			<?php kajob_render_job_categories(); ?>
		</div>
	</div>
</section>

<section class="section" id="jobs">
	<div class="container">
		<div class="section__head" data-reveal>
			<div>
				<span class="section__eyebrow"><?php esc_html_e( 'Fresh listings', 'kajob' ); ?></span>
				<h2 class="section__title"><?php echo esc_html( get_theme_mod( 'kajob_latest_jobs_title', __( 'Roles worth your afternoon.', 'kajob' ) ) ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'Switch the rail to see what is newest, promoted, remote or best paid. Save anything and come back to it later.', 'kajob' ); ?></p>
			</div>
			<a class="section__link" href="<?php echo esc_url( $kajob_jobs_url ); ?>">
				<?php
				printf(
					/* translators: %s: number of jobs. */
					esc_html__( 'View all %s jobs', 'kajob' ),
					esc_html( number_format_i18n( $kajob_job_total ) )
				);
				?>
				<?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
			</a>
		</div>

		<?php if ( $kajob_job_total ) : ?>
			<div data-tabs>
				<div class="tabs u-mb-0" role="tablist" aria-label="<?php esc_attr_e( 'Job rails', 'kajob' ); ?>" style="margin-bottom:var(--sp-5)">
					<?php $first = true; foreach ( $kajob_rails as $key => $rail ) : ?>
						<button
							type="button"
							class="tabs__btn<?php echo $first ? ' is-active' : ''; ?>"
							role="tab"
							id="kajob-tab-<?php echo esc_attr( $key ); ?>"
							aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
							aria-controls="kajob-panel-<?php echo esc_attr( $key ); ?>"
							tabindex="<?php echo $first ? '0' : '-1'; ?>"
							data-tab="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $rail['label'] ); ?>
						</button>
					<?php $first = false; endforeach; ?>
				</div>

				<?php $first = true; foreach ( $kajob_rails as $key => $rail ) : ?>
					<div
						class="tab-panel"
						role="tabpanel"
						id="kajob-panel-<?php echo esc_attr( $key ); ?>"
						aria-labelledby="kajob-tab-<?php echo esc_attr( $key ); ?>"
						data-tab-panel="<?php echo esc_attr( $key ); ?>"
						<?php echo $first ? '' : 'hidden'; ?>>
						<?php if ( $rail['html'] ) : ?>
							<div class="job-list"><?php echo $rail['html']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="u-center u-mt-6">
								<a class="btn btn--outline" href="<?php echo esc_url( $rail['url'] ); ?>">
									<?php
									/* translators: %s: rail label. */
									printf( esc_html__( 'See every %s role', 'kajob' ), esc_html( strtolower( $rail['label'] ) ) );
									?>
									<?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
								</a>
							</div>
						<?php else : ?>
							<div class="empty-state">
								<div class="empty-state__icon"><?php kajob_icon( 'inbox', array( 'size' => 30 ) ); ?></div>
								<h3 class="empty-state__title"><?php esc_html_e( 'Nothing in this rail yet', 'kajob' ); ?></h3>
								<p class="empty-state__text"><?php esc_html_e( 'No listing matches this rail right now. The full board still has plenty to look at.', 'kajob' ); ?></p>
								<div class="empty-state__actions">
									<a class="btn btn--primary" href="<?php echo esc_url( $kajob_jobs_url ); ?>"><?php esc_html_e( 'Browse all jobs', 'kajob' ); ?></a>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php $first = false; endforeach; ?>
			</div>
		<?php else : ?>
			<div class="empty-state">
				<div class="empty-state__icon"><?php kajob_icon( 'briefcase', array( 'size' => 30 ) ); ?></div>
				<h3 class="empty-state__title"><?php esc_html_e( 'The board is empty', 'kajob' ); ?></h3>
				<p class="empty-state__text"><?php esc_html_e( 'No listings have been published yet. Post the first one, or import the theme demo content from Appearance → Demo Content.', 'kajob' ); ?></p>
				<div class="empty-state__actions">
					<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>"><?php esc_html_e( 'Post the first job', 'kajob' ); ?></a>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a class="btn btn--outline" href="<?php echo esc_url( admin_url( 'themes.php?page=kajob-demo-content' ) ); ?>"><?php esc_html_e( 'Import demo content', 'kajob' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $kajob_insights ) : ?>
	<section class="section section--paper" id="salaries">
		<div class="container salary-layout">
			<div data-reveal>
				<span class="section__eyebrow"><?php esc_html_e( 'Pay transparency', 'kajob' ); ?></span>
				<h2 class="section__title"><?php esc_html_e( 'What these fields actually pay.', 'kajob' ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'Built from the salary bands on live listings — not a survey, not an estimate. The bar spans the lowest to the highest advertised number in each category.', 'kajob' ); ?></p>

				<dl class="salary-summary">
					<div>
						<dt><?php esc_html_e( 'Listings with a salary', 'kajob' ); ?></dt>
						<dd>
							<?php
							$with_salary = 0;
							foreach ( $kajob_insights as $row ) {
								$with_salary += $row['count'];
							}
							echo esc_html( number_format_i18n( $with_salary ) );
							?>
						</dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Top advertised', 'kajob' ); ?></dt>
						<dd><?php echo esc_html( kajob_currency_symbol() . kajob_compact_money( kajob_get_salary_ceiling() ) ); ?></dd>
					</div>
				</dl>

				<a class="btn btn--primary u-mt-5" href="<?php echo esc_url( add_query_arg( 'orderby', 'salary', $kajob_jobs_url ) ); ?>">
					<?php kajob_icon( 'trending', array( 'size' => 17 ) ); ?>
					<?php esc_html_e( 'Sort the board by pay', 'kajob' ); ?>
				</a>
			</div>

			<div class="salary-chart card card--pad" data-reveal>
				<?php
				$chart_hues = array( 'var(--violet-600)', 'var(--magenta-600)', 'var(--sky-600)', 'var(--mint-600)', 'var(--amber-600)', 'var(--coral-600)' );
				$chart_max  = 0;

				foreach ( $kajob_insights as $row ) {
					$chart_max = max( $chart_max, $row['high'] );
				}

				$chart_max = max( 1, $chart_max );

				foreach ( $kajob_insights as $index => $row ) :
					$hue   = $chart_hues[ $index % count( $chart_hues ) ];
					$left  = ( $row['low'] / $chart_max ) * 100;
					$width = max( 4, ( ( $row['high'] - $row['low'] ) / $chart_max ) * 100 );
					$row_count = absint( $row['count'] );
					$row_count_text = _n( '%s role', '%s roles', $row_count, 'kajob' );
					?>
					<div class="salary-row" style="--row-hue:<?php echo esc_attr( $hue ); ?>">
						<div class="salary-row__head">
							<a class="salary-row__label" href="<?php echo esc_url( $row['url'] ); ?>">
								<?php kajob_icon( kajob_category_style( $row['slug'] )[0], array( 'size' => 15 ) ); ?>
								<?php echo esc_html( $row['name'] ); ?>
								<span class="salary-row__count"><?php echo esc_html( sprintf( $row_count_text, number_format_i18n( $row_count ) ) ); ?></span>
							</a>
							<span class="salary-row__value"><?php echo esc_html( kajob_format_salary_range( $row['low'], $row['high'] ) ); ?></span>
						</div>
						<div class="salary-row__track">
							<span class="salary-row__bar" style="--bar-left:<?php echo esc_attr( round( $left, 2 ) ); ?>%;--bar-width:<?php echo esc_attr( round( $width, 2 ) ); ?>%"></span>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="salary-chart__axis" aria-hidden="true">
					<span><?php echo esc_html( kajob_currency_symbol() . '0' ); ?></span>
					<span><?php echo esc_html( kajob_currency_symbol() . kajob_compact_money( $chart_max / 2 ) ); ?></span>
					<span><?php echo esc_html( kajob_currency_symbol() . kajob_compact_money( $chart_max ) ); ?></span>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="section" id="how-it-works">
	<div class="container">
		<div class="section__head" data-reveal>
			<div>
				<span class="section__eyebrow"><?php esc_html_e( 'How it works', 'kajob' ); ?></span>
				<h2 class="section__title"><?php esc_html_e( 'Three steps, no account required.', 'kajob' ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'Search and save as a guest. Sign in only when you want your shortlist on another device.', 'kajob' ); ?></p>
			</div>
		</div>

		<div class="steps-grid">
			<?php
			$steps = array(
				array( 'search', 'var(--violet-600)', __( 'Filter down to the real options', 'kajob' ), __( 'Combine keyword, location, category, employment type, a salary floor and how recently it was posted. Results update as you type.', 'kajob' ) ),
				array( 'bookmark', 'var(--magenta-600)', __( 'Shortlist as you go', 'kajob' ), __( 'Save roles with one click. Your shortlist lives in this browser and syncs to your account the moment you sign in.', 'kajob' ) ),
				array( 'send', 'var(--mint-600)', __( 'Apply where it counts', 'kajob' ), __( 'Every listing states exactly how to apply — a direct link or an email address. No middleman, no recruiter forwarding.', 'kajob' ) ),
			);

			foreach ( $steps as $i => $step ) :
				?>
				<article class="step-card" style="--step-accent:<?php echo esc_attr( $step[1] ); ?>" data-reveal data-reveal-delay="<?php echo esc_attr( $i * 90 ); ?>">
					<div class="step-card__num" aria-hidden="true"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></div>
					<div class="step-card__icon"><?php kajob_icon( $step[0], array( 'size' => 23 ) ); ?></div>
					<h3 class="step-card__title"><?php echo esc_html( $step[2] ); ?></h3>
					<p class="step-card__text"><?php echo esc_html( $step[3] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--paper" id="stories">
	<div class="container">
		<div class="section__head" data-reveal>
			<div>
				<span class="section__eyebrow"><?php esc_html_e( 'Why people stay', 'kajob' ); ?></span>
				<h2 class="section__title"><?php esc_html_e( 'Built for the person reading listings at 11pm.', 'kajob' ); ?></h2>
			</div>
		</div>

		<div class="quote-grid">
			<?php
			$quotes = array(
				array( 'var(--violet-600)', __( 'The salary filter is the whole product for me. I stopped opening listings that turned out to be half what I earn now.', 'kajob' ), __( 'Priya Raman', 'kajob' ), __( 'Backend engineer, hired at Meridian', 'kajob' ), 'PR' ),
				array( 'var(--magenta-600)', __( 'I shortlisted eleven roles on my phone on the train, then applied from my laptop that evening. The list was just there.', 'kajob' ), __( 'Tomás Ferreira', 'kajob' ), __( 'Product designer, hired at Lumen', 'kajob' ), 'TF' ),
				array( 'var(--mint-600)', __( 'We filled a senior data role in nine days. Candidates arrived already knowing the band, so nobody wasted a call.', 'kajob' ), __( 'Dana Okonjo', 'kajob' ), __( 'Head of Talent, Northwind Labs', 'kajob' ), 'DO' ),
			);

			foreach ( $quotes as $i => $quote ) :
				?>
				<figure class="quote-card" style="--quote-accent:<?php echo esc_attr( $quote[0] ); ?>" data-reveal data-reveal-delay="<?php echo esc_attr( $i * 90 ); ?>">
					<div class="quote-card__mark" aria-hidden="true">&ldquo;</div>
					<div class="quote-card__stars" aria-label="<?php esc_attr_e( '5 out of 5', 'kajob' ); ?>">
						<?php for ( $s = 0; $s < 5; $s++ ) { kajob_icon( 'star', array( 'size' => 14, 'fill' => true ) ); } ?>
					</div>
					<blockquote class="quote-card__text"><?php echo esc_html( $quote[1] ); ?></blockquote>
					<figcaption class="quote-card__person">
						<span class="quote-card__avatar" aria-hidden="true"><?php echo esc_html( $quote[4] ); ?></span>
						<span>
							<span class="quote-card__name"><?php echo esc_html( $quote[2] ); ?></span>
							<span class="quote-card__role"><?php echo esc_html( $quote[3] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
$kajob_advice = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );

if ( $kajob_advice->have_posts() ) :
	?>
	<section class="section" id="advice">
		<div class="container">
			<div class="section__head" data-reveal>
				<div>
					<span class="section__eyebrow"><?php esc_html_e( 'Career advice', 'kajob' ); ?></span>
					<h2 class="section__title"><?php esc_html_e( 'Read this before you apply.', 'kajob' ); ?></h2>
				</div>
				<a class="section__link" href="<?php echo esc_url( kajob_get_blog_url() ); ?>">
					<?php esc_html_e( 'All articles', 'kajob' ); ?>
					<?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
				</a>
			</div>

			<div class="post-grid">
				<?php
				while ( $kajob_advice->have_posts() ) :
					$kajob_advice->the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="section section--tight">
	<div class="container">
		<div class="cta-banner kajob-grain" data-reveal>
			<div class="cta-banner__inner">
				<div>
					<h2 class="cta-banner__title"><?php echo esc_html( get_theme_mod( 'kajob_cta_title', __( 'Hiring? Put the salary in the listing.', 'kajob' ) ) ); ?></h2>
					<p class="cta-banner__text"><?php echo esc_html( get_theme_mod( 'kajob_cta_text', __( 'Listings with a published band get read first. Write your post, preview it exactly as candidates will see it, and publish in minutes.', 'kajob' ) ) ); ?></p>
					<div class="cta-banner__points">
						<?php
						$points = array(
							__( 'Free to post', 'kajob' ),
							__( 'Live preview before publishing', 'kajob' ),
							__( 'Edit, fill or expire from your dashboard', 'kajob' ),
						);

						foreach ( $points as $point ) {
							printf( '<span class="cta-banner__point">%1$s%2$s</span>', kajob_get_icon( 'check-circle', array( 'size' => 17 ) ), esc_html( $point ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>
				</div>
				<div class="cta-banner__actions">
					<a class="btn btn--mint btn--lg" href="<?php echo esc_url( get_theme_mod( 'kajob_cta_url', kajob_get_jm_page_url( 'submit_job_form' ) ) ); ?>">
						<?php kajob_icon( 'plus', array( 'size' => 18 ) ); ?>
						<?php echo esc_html( get_theme_mod( 'kajob_cta_button', __( 'Post a Job', 'kajob' ) ) ); ?>
					</a>
					<a class="btn btn--on-dark btn--lg" href="<?php echo esc_url( kajob_get_jm_page_url( 'job_dashboard' ) ); ?>">
						<?php esc_html_e( 'Employer dashboard', 'kajob' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
