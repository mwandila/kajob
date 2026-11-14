<?php
/**
 * Employer dashboard.
 *
 * The plugin's [job_dashboard] renders a JS actions menu and a modal overlay
 * that need its own UI bundle. Kajob takes over the shortcode and renders the
 * same data as plain links, so every management action works without that
 * bundle. The plugin's own action handler still runs on `wp`, which is what
 * verifies the nonces and performs edit / fill / duplicate / delete.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the signed-in employer's listings.
 */
function kajob_dashboard_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'posts_per_page' => 12 ), $atts, 'job_dashboard' );

	ob_start();

	if ( ! is_user_logged_in() ) {
		get_job_manager_template( 'job-dashboard-login.php' );

		return ob_get_clean();
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing and search.
	$action = isset( $_REQUEST['action'] ) ? sanitize_title( wp_unslash( $_REQUEST['action'] ) ) : '';
	$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
	$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	// phpcs:enable

	if ( $action && has_action( 'job_manager_job_dashboard_content_' . $action ) ) {
		do_action( 'job_manager_job_dashboard_content_' . $action, $atts );

		return ob_get_clean();
	}

	$per_page = max( 1, absint( $atts['posts_per_page'] ) );
	$paged    = max( 1, absint( get_query_var( 'paged' ) ), isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1 );

	$statuses = array( 'publish', 'expired', 'pending', 'draft', 'preview' );

	if ( get_option( 'job_manager_enable_scheduled_listings' ) ) {
		$statuses[] = 'future';
	}

	$args = array(
		'post_type'           => 'job_listing',
		'post_status'         => $statuses,
		'author'              => get_current_user_id(),
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'posts_per_page'      => $per_page,
		'paged'               => $paged,
		's'                   => $search,
	);

	$dashboard = \WP_Job_Manager\Job_Dashboard_Shortcode::instance();

	if ( 'filled' === $status ) {
		$args['meta_query'] = array( array( 'key' => '_filled', 'value' => '1' ) );
	} elseif ( in_array( $status, $statuses, true ) ) {
		$args['post_status'] = array( $status );
		$args['meta_query']  = array(
			'relation' => 'OR',
			array( 'key' => '_filled', 'value' => '1', 'compare' => '!=' ),
			array( 'key' => '_filled', 'compare' => 'NOT EXISTS' ),
		);
	}

	$query       = new WP_Query( apply_filters( 'job_manager_get_dashboard_jobs_args', $args ) );
	$job_actions = array();

	foreach ( $query->posts as $job ) {
		$job_actions[ $job->ID ] = $dashboard->get_job_actions( $job );
	}

	$columns = apply_filters( 'job_manager_job_dashboard_columns', array(
		'job_title' => __( 'Listing', 'kajob' ),
		'location'  => __( 'Location', 'kajob' ),
		'date'      => __( 'Posted', 'kajob' ),
	) );

	do_action( 'job_manager_job_dashboard_before', $query );

	get_job_manager_template( 'job-dashboard.php', array(
		'jobs'                  => $query->posts,
		'job_actions'           => $job_actions,
		'max_num_pages'         => (int) $query->max_num_pages,
		'job_dashboard_columns' => $columns,
		'search_input'          => $search,
		'status_input'          => $status,
		'paged'                 => $paged,
		'found'                 => (int) $query->found_posts,
	) );

	do_action( 'job_manager_job_dashboard_after', $query );

	return ob_get_clean();
}

/* ==========================================================================
   Dashboard columns — theme renderers replace the plugin's jm-ui markup.
   ========================================================================== */

add_action( 'wp', function () {
	if ( ! kajob_is_job_manager_active() || ! class_exists( '\WP_Job_Manager\Job_Dashboard_Shortcode' ) ) {
		return;
	}

	$plugin = '\WP_Job_Manager\Job_Dashboard_Shortcode';

	remove_action( 'job_manager_job_dashboard_column_job_title', array( $plugin, 'the_job_title' ), 10 );
	remove_action( 'job_manager_job_dashboard_column_job_title', array( $plugin, 'the_status' ), 12 );
	remove_action( 'job_manager_job_dashboard_column_date', array( $plugin, 'the_date' ), 10 );
	remove_action( 'job_manager_job_dashboard_column_date', array( $plugin, 'the_expiration_date' ), 10 );
	remove_action( 'job_manager_job_dashboard_column_company', array( $plugin, 'the_company' ), 10 );
	remove_action( 'job_manager_job_dashboard_column_actions', array( $plugin, 'the_primary_action' ), 10 );
	remove_action( 'job_manager_job_dashboard_columns', array( \WP_Job_Manager\Job_Dashboard_Shortcode::instance(), 'maybe_display_company_column' ), 8 );

	add_action( 'job_manager_job_dashboard_column_job_title', 'kajob_dashboard_column_title', 10 );
	add_action( 'job_manager_job_dashboard_column_location', 'kajob_dashboard_column_location', 10 );
	add_action( 'job_manager_job_dashboard_column_date', 'kajob_dashboard_column_date', 10 );
	add_action( 'job_manager_job_dashboard_column_company', 'kajob_dashboard_column_company', 10 );
} );

/**
 * Title, state chip and the company line.
 */
function kajob_dashboard_column_title( $job ) {
	list( $slug, $label, $tone ) = kajob_listing_state( $job->ID );
	$company                     = get_post_meta( $job->ID, '_company_name', true );
	?>
	<div class="jm-listing">
		<?php echo kajob_get_company_logo( $job->ID, 'jm-listing__logo' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		<div class="jm-listing__body">
			<a class="jm-listing__title" href="<?php echo esc_url( get_permalink( $job->ID ) ); ?>"><?php echo esc_html( get_the_title( $job ) ); ?></a>
			<div class="jm-listing__meta">
				<span class="chip chip--<?php echo esc_attr( $tone ); ?> chip--sm"><?php echo esc_html( $label ); ?></span>
				<?php if ( is_position_featured( $job ) && 'publish' === $job->post_status ) : ?>
					<span class="chip chip--amber chip--sm"><?php kajob_icon( 'star', array( 'size' => 12 ) ); ?><?php esc_html_e( 'Featured', 'kajob' ); ?></span>
				<?php endif; ?>
				<?php if ( $company ) : ?>
					<span class="jm-listing__company"><?php echo esc_html( $company ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Location plus the remote flag.
 */
function kajob_dashboard_column_location( $job ) {
	$location = get_post_meta( $job->ID, '_job_location', true );
	$remote   = kajob_is_remote( $job->ID );

	if ( ! $location && ! $remote ) {
		echo '<span class="jm-muted">' . esc_html__( 'Not set', 'kajob' ) . '</span>';

		return;
	}

	echo '<span class="jm-cell">';

	if ( $location ) {
		kajob_icon( 'pin', array( 'size' => 15 ) );
		echo esc_html( $location );
	} elseif ( $remote ) {
		kajob_icon( 'globe', array( 'size' => 15 ) );
		esc_html_e( 'Remote', 'kajob' );
	}

	echo '</span>';

	if ( $location && $remote ) {
		echo '<span class="jm-cell jm-muted">' . esc_html__( 'Remote friendly', 'kajob' ) . '</span>';
	}
}

/**
 * Posted date and the expiry countdown.
 */
function kajob_dashboard_column_date( $job ) {
	echo '<span class="jm-cell">' . esc_html( date_i18n( get_option( 'date_format' ), get_post_time( 'U', true, $job ) ) ) . '</span>';

	$expires = get_post_meta( $job->ID, '_job_expires', true );

	if ( 'publish' !== $job->post_status || ! $expires ) {
		return;
	}

	$timestamp = strtotime( $expires );

	if ( ! $timestamp ) {
		return;
	}

	$days  = (int) ceil( ( $timestamp - time() ) / DAY_IN_SECONDS );
	$tone  = $days <= 7 ? 'coral' : 'muted';
	/* translators: %d: number of days. */
	$label = $days > 0 ? sprintf( _n( 'Closes in %d day', 'Closes in %d days', $days, 'kajob' ), $days ) : __( 'Closing today', 'kajob' );

	echo '<span class="jm-cell jm-' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Company logo and name, for accounts that hire under several brands.
 */
function kajob_dashboard_column_company( $job ) {
	echo '<span class="jm-cell">' . esc_html( get_post_meta( $job->ID, '_company_name', true ) ) . '</span>';
}

/* ==========================================================================
   Plugin assets that would fight the theme's own components.
   ========================================================================== */

/**
 * The theme replaces every plugin template, so the plugin's frontend and
 * dashboard stylesheets are dead weight and its overlay script would hijack
 * the dashboard rows. Select2 stays: the submit form's category picker uses it.
 */
add_filter( 'job_manager_enqueue_frontend_style', '__return_false' );

add_action( 'wp_enqueue_scripts', function () {
	foreach ( array( 'wp-job-manager-frontend', 'wp-job-manager-job-listings', 'wp-job-manager-job-dashboard', 'wp-job-manager-ui', 'jquery-ui' ) as $handle ) {
		wp_deregister_style( $handle );
	}

}, 20 );
