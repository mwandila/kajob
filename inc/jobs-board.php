<?php
/**
 * The jobs board: one renderer, one query path, one AJAX endpoint.
 *
 * The board replaces the plugin's `[jobs]` output so the archive, the taxonomy
 * views and any embedded shortcode all share the same filter rail, toolbar and
 * card markup. Filtering runs through get_job_listings() so the plugin's own
 * capability, expiry and visibility rules still apply.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sort choices offered in the toolbar.
 */
function kajob_board_sort_options() {
	return array(
		'date-desc'   => array( __( 'Newest first', 'kajob' ), 'date', 'DESC' ),
		'date-asc'    => array( __( 'Oldest first', 'kajob' ), 'date', 'ASC' ),
		'salary-desc' => array( __( 'Highest salary', 'kajob' ), 'salary', 'DESC' ),
		'salary-asc'  => array( __( 'Lowest salary', 'kajob' ), 'salary', 'ASC' ),
		'featured'    => array( __( 'Featured first', 'kajob' ), 'featured', 'DESC' ),
		'title-asc'   => array( __( 'Title A–Z', 'kajob' ), 'title', 'ASC' ),
	);
}

/**
 * Posting-recency windows.
 */
function kajob_board_date_options() {
	return array(
		''    => __( 'Any time', 'kajob' ),
		'24h' => __( 'Last 24 hours', 'kajob' ),
		'3d'  => __( 'Last 3 days', 'kajob' ),
		'7d'  => __( 'Last week', 'kajob' ),
		'30d' => __( 'Last month', 'kajob' ),
	);
}

/**
 * Normalise the request (or a shortcode's attributes) into board state.
 *
 * @param array $overrides Values that win over the query string.
 * @param array $source    Raw input; defaults to the request.
 */
function kajob_board_state( $overrides = array(), $source = null ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filtering.
	$raw = null === $source ? $_REQUEST : $source;
	// phpcs:enable

	$get = function ( $key, $default = '' ) use ( $raw, $overrides ) {
		if ( array_key_exists( $key, $overrides ) ) {
			return $overrides[ $key ];
		}

		return isset( $raw[ $key ] ) ? wp_unslash( $raw[ $key ] ) : $default;
	};

	$slugs = function ( $value ) {
		if ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = explode( ',', (string) $value );
		}

		$parts = array_map( 'sanitize_title', array_map( 'trim', $parts ) );

		return array_values( array_unique( array_filter( $parts ) ) );
	};

	$sorts   = kajob_board_sort_options();
	$orderby = sanitize_key( $get( 'orderby', 'date-desc' ) );

	// Accept the plugin's own orderby values so its links keep working.
	$aliases = array( 'date' => 'date-desc', 'salary' => 'salary-desc', 'title' => 'title-asc' );

	if ( isset( $aliases[ $orderby ] ) ) {
		$orderby = $aliases[ $orderby ];
	}

	if ( ! isset( $sorts[ $orderby ] ) ) {
		$orderby = 'date-desc';
	}

	$dates  = kajob_board_date_options();
	$posted = sanitize_key( $get( 'kajob_date_posted', '' ) );

	if ( ! isset( $dates[ $posted ] ) ) {
		$posted = '';
	}

	$view = sanitize_key( $get( 'view', 'list' ) );

	if ( ! in_array( $view, array( 'list', 'grid' ), true ) ) {
		$view = 'list';
	}

	$per_page = absint( $get( 'per_page', 10 ) );
	$per_page = max( 3, min( 50, $per_page ) );

	$ceiling = kajob_get_salary_ceiling();

	return array(
		'keywords'   => sanitize_text_field( $get( 'search_keywords', '' ) ),
		'location'   => sanitize_text_field( $get( 'search_location', '' ) ),
		'categories' => $slugs( $get( 'search_categories', array() ) ),
		'types'      => $slugs( $get( 'job_types', array() ) ),
		'remote'     => (bool) absint( $get( 'remote_position', 0 ) ),
		'featured'   => (bool) absint( $get( 'featured', 0 ) ),
		'salary_min' => min( $ceiling, absint( $get( 'kajob_salary_min', 0 ) ) ),
		'posted'     => $posted,
		'orderby'    => $orderby,
		'view'       => $view,
		'paged'      => max( 1, absint( $get( 'paged', 1 ) ) ),
		'per_page'   => $per_page,
		'ceiling'    => $ceiling,
	);
}

/**
 * Seed values (shortcode attributes, a term archive) sit *under* the query
 * string, so an explicit filter URL always wins over the context the board
 * happens to be embedded in.
 *
 * @param array $defaults Context defaults.
 * @return array
 */
function kajob_board_source( $defaults ) {
	$defaults = array_filter( $defaults, function ( $value ) {
		return '' !== $value && null !== $value && array() !== $value;
	} );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only filtering.
	return array_merge( $defaults, $_REQUEST );
}

/**
 * How many filters are currently narrowing the board.
 */
function kajob_board_active_count( $state ) {
	return count( $state['categories'] )
		+ count( $state['types'] )
		+ ( $state['remote'] ? 1 : 0 )
		+ ( $state['featured'] ? 1 : 0 )
		+ ( $state['salary_min'] > 0 ? 1 : 0 )
		+ ( '' !== $state['posted'] ? 1 : 0 )
		+ ( '' !== $state['keywords'] ? 1 : 0 )
		+ ( '' !== $state['location'] ? 1 : 0 );
}

/**
 * Run the board query.
 *
 * @param array $state Normalised board state.
 * @return WP_Query
 */
function kajob_board_query( $state ) {
	if ( ! kajob_is_job_manager_active() || ! function_exists( 'get_job_listings' ) ) {
		return new WP_Query( array( 'post__in' => array( 0 ), 'post_type' => 'job_listing' ) );
	}

	list( , $orderby, $order ) = kajob_board_sort_options()[ $state['orderby'] ];

	$args = array(
		'search_keywords'   => $state['keywords'],
		'search_location'   => $state['location'],
		'search_categories' => $state['categories'],
		'job_types'         => $state['types'],
		'remote_position'   => $state['remote'] ? true : null,
		'featured'          => $state['featured'] ? true : null,
		'orderby'           => $orderby,
		'order'             => $order,
		'posts_per_page'    => $state['per_page'],
		'offset'            => ( $state['paged'] - 1 ) * $state['per_page'],
		'kajob_salary_min'  => $state['salary_min'],
		'kajob_date_posted' => $state['posted'],
	);

	return get_job_listings( $args );
}

/**
 * Cards for one page of results.
 *
 * @param WP_Query $query Results.
 * @param string   $variant list|grid.
 */
function kajob_board_cards( $query, $variant = 'list' ) {
	$html = '';

	foreach ( $query->posts as $job ) {
		if ( function_exists( 'job_manager_user_can_view_job_listing' ) && ! job_manager_user_can_view_job_listing( $job->ID ) ) {
			continue;
		}

		$html .= kajob_job_card( $job->ID, array( 'variant' => $variant ) );
	}

	return $html;
}

/**
 * Removable chips describing the active filters.
 */
function kajob_board_chips( $state ) {
	$chips = array();

	if ( '' !== $state['keywords'] ) {
		$chips[] = array( 'search_keywords', '', sprintf( __( '“%s”', 'kajob' ), $state['keywords'] ), 'violet' );
	}

	if ( '' !== $state['location'] ) {
		$chips[] = array( 'search_location', '', $state['location'], 'sky' );
	}

	foreach ( $state['categories'] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'job_listing_category' );
		$chips[] = array( 'search_categories', $slug, $term ? $term->name : $slug, 'violet' );
	}

	foreach ( $state['types'] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'job_listing_type' );
		$chips[] = array( 'job_types', $slug, $term ? $term->name : $slug, kajob_job_type_accent( $slug ) );
	}

	if ( $state['remote'] ) {
		$chips[] = array( 'remote_position', '', __( 'Remote only', 'kajob' ), 'sky' );
	}

	if ( $state['featured'] ) {
		$chips[] = array( 'featured', '', __( 'Featured', 'kajob' ), 'amber' );
	}

	if ( $state['salary_min'] > 0 ) {
		$chips[] = array( 'kajob_salary_min', '', kajob_currency_symbol() . kajob_compact_money( $state['salary_min'] ) . '+', 'mint' );
	}

	if ( '' !== $state['posted'] ) {
		$chips[] = array( 'kajob_date_posted', '', kajob_board_date_options()[ $state['posted'] ], 'magenta' );
	}

	if ( ! $chips ) {
		return '';
	}

	$html = '<span class="active-filters__label">' . esc_html__( 'Filtering by', 'kajob' ) . '</span>';

	foreach ( $chips as $chip ) {
		$html .= sprintf(
			'<span class="chip chip--%1$s chip--removable">%2$s<button type="button" class="chip__remove" data-filter-remove="%3$s" data-filter-value="%4$s" aria-label="%5$s">&times;</button></span>',
			esc_attr( $chip[3] ),
			esc_html( $chip[2] ),
			esc_attr( $chip[0] ),
			esc_attr( $chip[1] ),
			/* translators: %s: filter label. */
			esc_attr( sprintf( __( 'Remove filter: %s', 'kajob' ), $chip[2] ) )
		);
	}

	$html .= '<button type="button" class="btn btn--ghost btn--sm" data-filter-reset>' . esc_html__( 'Clear all', 'kajob' ) . '</button>';

	return $html;
}

/**
 * The empty state shown when a filter combination matches nothing.
 */
function kajob_board_empty() {
	ob_start();
	?>
	<div class="empty-state">
		<div class="empty-state__icon"><?php kajob_icon( 'target', array( 'size' => 30 ) ); ?></div>
		<h3 class="empty-state__title"><?php esc_html_e( 'No role matches all of that', 'kajob' ); ?></h3>
		<p class="empty-state__text"><?php esc_html_e( 'Try widening the salary floor, clearing a category, or allowing on-site roles. You can also save this search and we will email you when something appears.', 'kajob' ); ?></p>
		<div class="empty-state__actions">
			<button type="button" class="btn btn--primary" data-filter-reset>
				<?php kajob_icon( 'refresh', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Clear all filters', 'kajob' ); ?>
			</button>
			<a class="btn btn--outline" href="#job-alerts">
				<?php kajob_icon( 'mail', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Email me new matches', 'kajob' ); ?>
			</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Sibling-term navigation shown under a taxonomy board so visitors can hop
 * between categories or employment types without going back to the archive.
 *
 * @param string $taxonomy     Taxonomy being viewed.
 * @param string $current_slug Term being viewed.
 */
function kajob_board_term_nav( $taxonomy, $current_slug ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 12,
	) );

	if ( is_wp_error( $terms ) || count( $terms ) < 2 ) {
		return '';
	}

	$out = '<nav class="term-nav container" aria-label="' . esc_attr__( 'Related job groups', 'kajob' ) . '">';
	$out .= '<span class="term-nav__label">' . esc_html__( 'Jump to', 'kajob' ) . '</span><div class="term-nav__list">';
	$out .= '<a class="chip" href="' . esc_url( kajob_get_jobs_page_url() ) . '">' . kajob_get_icon( 'compass', array( 'size' => 13 ) ) . esc_html__( 'All jobs', 'kajob' ) . '</a>';

	foreach ( $terms as $term ) {
		if ( $term->slug === $current_slug ) {
			continue;
		}

		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$out .= sprintf(
			'<a class="chip" href="%1$s">%2$s<span class="term-nav__count">%3$s</span></a>',
			esc_url( $link ),
			esc_html( $term->name ),
			esc_html( number_format_i18n( $term->count ) )
		);
	}

	return $out . '</div></nav>';
}

/**
 * Full board markup. Used by the shortcode, the archive and the taxonomies.
 *
 * @param array $overrides State overrides (from a term archive).
 * @param array $context   title, description, eyebrow.
 * @param array $source    Raw input; defaults to the request.
 */
function kajob_render_board( $overrides = array(), $context = array(), $source = null ) {
	$state       = kajob_board_state( $overrides, $source );
	$query       = kajob_board_query( $state );
	$found       = (int) $query->found_posts;
	$cards       = kajob_board_cards( $query, $state['view'] );
	$active      = kajob_board_active_count( $state );
	$jobs_url    = kajob_get_jobs_page_url();
	$title       = isset( $context['title'] ) ? $context['title'] : __( 'Find your next role', 'kajob' );
	$description = isset( $context['description'] ) ? $context['description'] : __( 'Filter by pay, location, category and how fresh the listing is. Everything updates without a page reload.', 'kajob' );
	$eyebrow     = isset( $context['eyebrow'] ) ? $context['eyebrow'] : __( 'Job board', 'kajob' );

	ob_start();
	?>
	<div class="board" data-board data-jobs-url="<?php echo esc_url( $jobs_url ); ?>" data-per-page="<?php echo esc_attr( $state['per_page'] ); ?>">

		<section class="board-hero kajob-grain">
			<div class="container board-hero__inner">
				<span class="section__eyebrow board-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<h1 class="board-hero__title"><?php echo esc_html( $title ); ?></h1>
				<p class="board-hero__sub"><?php echo esc_html( $description ); ?></p>

				<div class="board-hero__meta">
					<span class="chip chip--mint"><span class="badge-dot badge-dot--live" aria-hidden="true"></span>
						<?php echo esc_html( sprintf( _n( '%s live listing', '%s live listings', kajob_get_job_count(), 'kajob' ), number_format_i18n( kajob_get_job_count() ) ) ); ?>
					</span>
					<span class="chip"><?php kajob_icon( 'globe', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( __( '%s remote', 'kajob' ), number_format_i18n( kajob_get_board_stats()['remote'] ) ) ); ?></span>
					<span class="chip"><?php kajob_icon( 'building', array( 'size' => 13 ) ); ?><?php echo esc_html( sprintf( __( '%s companies', 'kajob' ), number_format_i18n( kajob_get_board_stats()['companies'] ) ) ); ?></span>
				</div>
			</div>
		</section>

		<div class="container board-body">
			<div class="jobs-layout">

				<form class="filters-rail" data-filters aria-label="<?php esc_attr_e( 'Job filters', 'kajob' ); ?>">
					<div class="filters-rail__head">
						<h2 class="filters-rail__title"><?php kajob_icon( 'sliders', array( 'size' => 17 ) ); ?><?php esc_html_e( 'Filters', 'kajob' ); ?></h2>
						<div class="u-flex u-items-center u-gap-2">
							<button type="button" class="filters-rail__reset" data-filter-reset><?php esc_html_e( 'Reset', 'kajob' ); ?></button>
							<button type="button" class="icon-btn filters-sheet__close" data-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'kajob' ); ?>"><?php kajob_icon( 'x', array( 'size' => 19 ) ); ?></button>
						</div>
					</div>

					<div class="filters-rail__body">
						<div class="filter-group">
							<h3 class="filter-group__title"><?php esc_html_e( 'Keyword', 'kajob' ); ?></h3>
							<div class="filter-input">
								<?php kajob_icon( 'search', array( 'size' => 16 ) ); ?>
								<label class="screen-reader-text" for="kajob-filter-keywords"><?php esc_html_e( 'Job title, skill or company', 'kajob' ); ?></label>
								<input type="text" id="kajob-filter-keywords" name="search_keywords" value="<?php echo esc_attr( $state['keywords'] ); ?>" placeholder="<?php esc_attr_e( 'Title, skill, company', 'kajob' ); ?>" autocomplete="off" data-filter-debounce>
							</div>
						</div>

						<div class="filter-group">
							<h3 class="filter-group__title"><?php esc_html_e( 'Location', 'kajob' ); ?></h3>
							<div class="filter-input">
								<?php kajob_icon( 'pin', array( 'size' => 16 ) ); ?>
								<label class="screen-reader-text" for="kajob-filter-location"><?php esc_html_e( 'City or region', 'kajob' ); ?></label>
								<input type="text" id="kajob-filter-location" name="search_location" value="<?php echo esc_attr( $state['location'] ); ?>" placeholder="<?php esc_attr_e( 'City or region', 'kajob' ); ?>" autocomplete="off" data-filter-debounce>
							</div>
							<label class="switch u-mt-5">
								<input type="checkbox" name="remote_position" value="1" <?php checked( $state['remote'] ); ?>>
								<span class="switch__track" aria-hidden="true"></span>
								<span class="switch__label"><?php esc_html_e( 'Remote only', 'kajob' ); ?></span>
							</label>
						</div>

						<?php
						$board_taxonomies = array(
							array(
								'key'      => 'search_categories',
								'taxonomy' => 'job_listing_category',
								'title'    => __( 'Category', 'kajob' ),
								'selected' => $state['categories'],
								'enabled'  => (bool) get_option( 'job_manager_enable_categories' ),
							),
							array(
								'key'      => 'job_types',
								'taxonomy' => 'job_listing_type',
								'title'    => __( 'Employment type', 'kajob' ),
								'selected' => $state['types'],
								'enabled'  => (bool) get_option( 'job_manager_enable_types' ),
							),
						);

						foreach ( $board_taxonomies as $group ) :
							if ( ! $group['enabled'] || ! taxonomy_exists( $group['taxonomy'] ) ) {
								continue;
							}

							$terms = get_terms( array( 'taxonomy' => $group['taxonomy'], 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );

							if ( is_wp_error( $terms ) || ! $terms ) {
								continue;
							}
							?>
							<div class="filter-group">
								<h3 class="filter-group__title"><?php echo esc_html( $group['title'] ); ?></h3>
								<div class="filter-group__list">
									<?php foreach ( $terms as $index => $term ) : ?>
										<label class="check">
											<input
												type="checkbox"
												name="<?php echo esc_attr( $group['key'] ); ?>[]"
												value="<?php echo esc_attr( $term->slug ); ?>"
												<?php checked( in_array( $term->slug, $group['selected'], true ) ); ?>>
											<span><?php echo esc_html( $term->name ); ?></span>
											<span class="check__count"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>

						<div class="filter-group">
							<h3 class="filter-group__title"><?php esc_html_e( 'Minimum salary', 'kajob' ); ?></h3>
							<div class="salary-output">
								<span><?php esc_html_e( 'At least', 'kajob' ); ?></span>
								<strong data-salary-output><?php echo esc_html( $state['salary_min'] ? kajob_currency_symbol() . kajob_compact_money( $state['salary_min'] ) : __( 'Any', 'kajob' ) ); ?></strong>
							</div>
							<label class="screen-reader-text" for="kajob-filter-salary"><?php esc_html_e( 'Minimum annual salary', 'kajob' ); ?></label>
							<input
								type="range"
								class="range"
								id="kajob-filter-salary"
								name="kajob_salary_min"
								min="0"
								max="<?php echo esc_attr( $state['ceiling'] ); ?>"
								step="5000"
								value="<?php echo esc_attr( $state['salary_min'] ); ?>"
								data-salary-range>
							<div class="salary-scale" aria-hidden="true">
								<span><?php esc_html_e( 'Any', 'kajob' ); ?></span>
								<span><?php echo esc_html( kajob_currency_symbol() . kajob_compact_money( $state['ceiling'] ) ); ?></span>
							</div>
						</div>

						<div class="filter-group">
							<h3 class="filter-group__title"><?php esc_html_e( 'Date posted', 'kajob' ); ?></h3>
							<label class="screen-reader-text" for="kajob-filter-posted"><?php esc_html_e( 'Date posted', 'kajob' ); ?></label>
							<select id="kajob-filter-posted" name="kajob_date_posted">
								<?php foreach ( kajob_board_date_options() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['posted'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="filter-group">
							<h3 class="filter-group__title"><?php esc_html_e( 'Promoted', 'kajob' ); ?></h3>
							<label class="switch">
								<input type="checkbox" name="featured" value="1" <?php checked( $state['featured'] ); ?>>
								<span class="switch__track" aria-hidden="true"></span>
								<span class="switch__label"><?php esc_html_e( 'Featured only', 'kajob' ); ?></span>
							</label>
						</div>
					</div>

					<div class="filters-rail__foot">
						<button type="button" class="btn btn--primary btn--block" data-filters-close>
							<?php
							printf(
								/* translators: %s: number of matching jobs. */
								esc_html__( 'Show %s jobs', 'kajob' ),
								esc_html( number_format_i18n( $found ) )
							);
							?>
						</button>
					</div>
				</form>

				<div class="jobs-main">
					<div class="jobs-toolbar">
						<p class="jobs-toolbar__count" data-results-count aria-live="polite">
							<?php
							printf(
								/* translators: 1: number of matches, 2: total listings. */
								esc_html__( '%1$s of %2$s jobs', 'kajob' ),
								'<strong>' . esc_html( number_format_i18n( $found ) ) . '</strong>',
								esc_html( number_format_i18n( kajob_get_job_count() ) )
							);
							?>
						</p>

						<div class="jobs-toolbar__controls">
							<button type="button" class="btn btn--outline btn--sm filters-fab" data-filters-open>
								<?php kajob_icon( 'filter', array( 'size' => 16 ) ); ?>
								<?php esc_html_e( 'Filters', 'kajob' ); ?>
								<span class="filters-fab__count" data-filter-count><?php echo esc_html( $active ); ?></span>
							</button>

							<label class="screen-reader-text" for="kajob-sort"><?php esc_html_e( 'Sort results', 'kajob' ); ?></label>
							<select id="kajob-sort" name="orderby" data-sort>
								<?php foreach ( kajob_board_sort_options() as $value => $option ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['orderby'], $value ); ?>><?php echo esc_html( $option[0] ); ?></option>
								<?php endforeach; ?>
							</select>

							<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'Result layout', 'kajob' ); ?>">
								<button type="button" data-view="list" class="<?php echo 'list' === $state['view'] ? 'is-active' : ''; ?>" aria-pressed="<?php echo 'list' === $state['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'List view', 'kajob' ); ?>"><?php kajob_icon( 'list', array( 'size' => 16 ) ); ?></button>
								<button type="button" data-view="grid" class="<?php echo 'grid' === $state['view'] ? 'is-active' : ''; ?>" aria-pressed="<?php echo 'grid' === $state['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Grid view', 'kajob' ); ?>"><?php kajob_icon( 'grid', array( 'size' => 16 ) ); ?></button>
							</div>
						</div>
					</div>

					<div class="active-filters" data-active-filters><?php echo kajob_board_chips( $state ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<div class="jobs-results" data-results aria-busy="false">
						<div class="job-list <?php echo 'grid' === $state['view'] ? 'job-list--grid' : ''; ?>" data-results-list>
							<?php echo $cards ? $cards : kajob_board_empty(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</div>

					<div class="jobs-results__foot" data-results-foot>
						<?php if ( $found > $state['per_page'] * $state['paged'] ) : ?>
							<button type="button" class="btn btn--outline btn--lg" data-load-more data-page="<?php echo esc_attr( $state['paged'] ); ?>" data-pages="<?php echo esc_attr( $query->max_num_pages ); ?>">
								<span class="btn__spinner" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Load more jobs', 'kajob' ); ?></span>
							</button>
						<?php elseif ( $found ) : ?>
							<p class="u-muted u-mb-0"><?php esc_html_e( 'You have seen every matching job.', 'kajob' ); ?></p>
						<?php endif; ?>
					</div>

					<p class="board-alerts">
						<?php kajob_icon( 'mail', array( 'size' => 16 ) ); ?>
						<?php esc_html_e( 'Nothing quite right?', 'kajob' ); ?>
						<a href="#job-alerts"><?php esc_html_e( 'Save this search as an email alert', 'kajob' ); ?></a>
					</p>
				</div>

			</div>
		</div>

		<div class="filters-scrim" data-filters-scrim hidden></div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Board shortcode handler.
 *
 * @param array $atts Shortcode attributes.
 */
function kajob_jobs_shortcode( $atts ) {
	if ( ! kajob_is_job_manager_active() ) {
		return '<div class="notice notice--info"><p>' . esc_html__( 'Activate WP Job Manager to display listings.', 'kajob' ) . '</p></div>';
	}

	if ( function_exists( 'job_manager_user_can_browse_job_listings' ) && ! job_manager_user_can_browse_job_listings() ) {
		return '<div class="notice notice--warning"><p>' . esc_html__( 'You do not have permission to browse job listings.', 'kajob' ) . '</p></div>';
	}

	$atts = shortcode_atts( array(
		'per_page'        => 10,
		'orderby'         => 'date-desc',
		'categories'      => '',
		'job_types'       => '',
		'featured'        => '',
		'remote_position' => '',
		'keywords'        => '',
		'location'        => '',
		'view'            => '',
		'title'           => '',
	), $atts, 'jobs' );

	// Shortcode attributes seed the board; an explicit query string still wins,
	// so a shared filter URL behaves the same wherever the board is embedded.
	$defaults = array(
		'per_page'          => absint( $atts['per_page'] ),
		'orderby'           => $atts['orderby'],
		'search_keywords'   => $atts['keywords'],
		'search_location'   => $atts['location'],
		'search_categories' => $atts['categories'],
		'job_types'         => $atts['job_types'],
		'view'              => $atts['view'],
	);

	if ( '' !== $atts['featured'] ) {
		$defaults['featured'] = filter_var( $atts['featured'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
	}

	if ( '' !== $atts['remote_position'] ) {
		$defaults['remote_position'] = filter_var( $atts['remote_position'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only filtering.
	$source = kajob_board_source( $defaults );

	$context = array();

	if ( '' !== $atts['title'] ) {
		$context['title'] = $atts['title'];
	}

	return kajob_render_board( array(), $context, $source );
}

/**
 * AJAX: one page of results for the current filter state.
 */
function kajob_ajax_filter_jobs() {
	kajob_ajax_guard();

	$state = kajob_board_state();
	$query = kajob_board_query( $state );
	$found = (int) $query->found_posts;
	$cards = kajob_board_cards( $query, $state['view'] );

	wp_send_json_success( array(
		'html'    => $cards ? $cards : kajob_board_empty(),
		'chips'   => kajob_board_chips( $state ),
		'found'   => $found,
		'pages'   => (int) $query->max_num_pages,
		'paged'   => $state['paged'],
		'active'  => kajob_board_active_count( $state ),
		'view'    => $state['view'],
		'hasMore' => $found > $state['per_page'] * $state['paged'],
		'empty'   => '' === $cards,
		'count'   => sprintf(
			/* translators: 1: matches, 2: total. */
			__( '%1$s of %2$s jobs', 'kajob' ),
			'<strong>' . esc_html( number_format_i18n( $found ) ) . '</strong>',
			esc_html( number_format_i18n( kajob_get_job_count() ) )
		),
		'showBtn' => sprintf(
			/* translators: %s: number of matches. */
			__( 'Show %s jobs', 'kajob' ),
			number_format_i18n( $found )
		),
	) );
}
add_action( 'wp_ajax_kajob_filter_jobs', 'kajob_ajax_filter_jobs' );
add_action( 'wp_ajax_nopriv_kajob_filter_jobs', 'kajob_ajax_filter_jobs' );
