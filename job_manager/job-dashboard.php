<?php
/**
 * Employer dashboard.
 *
 * Override of wp-job-manager/templates/job-dashboard.php. Actions are plain
 * links carrying the plugin's own nonces, so management works without the
 * plugin's UI bundle.
 *
 * @package Kajob
 * @var array  $job_dashboard_columns
 * @var int    $max_num_pages
 * @var array  $jobs
 * @var array  $job_actions
 * @var string $search_input
 * @var string $status_input
 * @var int    $paged
 * @var int    $found
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kajob_stats   = kajob_employer_stats();
$kajob_can_post = job_manager_user_can_submit_job_listing();
$kajob_message  = class_exists( '\WP_Job_Manager\UI\Redirect_Message' ) ? \WP_Job_Manager\UI\Redirect_Message::get_message( 'updated' ) : '';
$kajob_base     = remove_query_arg( array( 'action', 'job_id', '_wpnonce', 'updated', 'paged' ), get_permalink() );
?>
<div id="job-manager-job-dashboard" class="jm-dashboard">

	<?php if ( $kajob_message ) : ?>
		<div class="notice notice--success"><?php echo wp_kses_post( $kajob_message ); ?></div>
	<?php endif; ?>

	<div class="jm-dashboard-stats">
		<?php
		$kajob_tiles = array(
			array( 'eye', __( 'Live', 'kajob' ), $kajob_stats['publish'], 'mint', 'publish' ),
			array( 'clock', __( 'In review', 'kajob' ), $kajob_stats['pending'], 'amber', 'pending' ),
			array( 'check-circle', __( 'Filled', 'kajob' ), $kajob_stats['filled'], 'sky', 'filled' ),
			array( 'file', __( 'Drafts', 'kajob' ), $kajob_stats['draft'], 'violet', 'draft' ),
		);

		foreach ( $kajob_tiles as $kajob_tile ) :
			$kajob_on = $status_input === $kajob_tile[4];
			?>
			<a class="jm-stat jm-stat--<?php echo esc_attr( $kajob_tile[3] ); ?><?php echo $kajob_on ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $kajob_on ? remove_query_arg( 'status', $kajob_base ) : add_query_arg( 'status', $kajob_tile[4], $kajob_base ) ); ?>">
				<span class="jm-stat__icon" aria-hidden="true"><?php kajob_icon( $kajob_tile[0], array( 'size' => 18 ) ); ?></span>
				<span class="jm-stat__value"><?php echo esc_html( number_format_i18n( $kajob_tile[2] ) ); ?></span>
				<span class="jm-stat__label"><?php echo esc_html( $kajob_tile[1] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="jm-dashboard__bar">
		<form method="get" class="jm-dashboard__filters" role="search">
			<label class="screen-reader-text" for="kajob-dashboard-search"><?php esc_html_e( 'Search your listings', 'kajob' ); ?></label>
			<div class="filter-input">
				<?php kajob_icon( 'search', array( 'size' => 16 ) ); ?>
				<input type="search" id="kajob-dashboard-search" name="search" value="<?php echo esc_attr( $search_input ); ?>" placeholder="<?php esc_attr_e( 'Search your listings', 'kajob' ); ?>" />
			</div>
			<?php if ( $status_input ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_input ); ?>" />
			<?php endif; ?>
			<button type="submit" class="btn btn--outline btn--sm"><?php esc_html_e( 'Search', 'kajob' ); ?></button>
			<?php if ( $search_input || $status_input ) : ?>
				<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( $kajob_base ); ?>"><?php esc_html_e( 'Clear', 'kajob' ); ?></a>
			<?php endif; ?>
		</form>

		<?php if ( $kajob_can_post ) : ?>
			<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>">
				<?php kajob_icon( 'plus', array( 'size' => 16 ) ); ?>
				<?php esc_html_e( 'Post a new job', 'kajob' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<div class="job-manager-jobs jm-dashboard-table <?php echo count( $job_dashboard_columns ) > 3 ? 'jm-dashboard-table--wide' : ''; ?>">
		<?php if ( ! $jobs ) : ?>
			<div class="empty-state">
				<div class="empty-state__icon"><?php kajob_icon( 'inbox', array( 'size' => 30 ) ); ?></div>
				<h3 class="empty-state__title">
					<?php echo ( $search_input || $status_input ) ? esc_html__( 'Nothing here yet', 'kajob' ) : esc_html__( 'No listings yet', 'kajob' ); ?>
				</h3>
				<p class="empty-state__text">
					<?php
					echo ( $search_input || $status_input )
						? esc_html__( 'No listing on your account matches this view. Clear the filters to see all of them.', 'kajob' )
						: esc_html__( 'Post your first role and it will appear here with its status, closing date and management actions.', 'kajob' );
					?>
				</p>
				<div class="empty-state__actions">
					<?php if ( $search_input || $status_input ) : ?>
						<a class="btn btn--outline" href="<?php echo esc_url( $kajob_base ); ?>"><?php esc_html_e( 'Show all listings', 'kajob' ); ?></a>
					<?php endif; ?>
					<?php if ( $kajob_can_post ) : ?>
						<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>">
							<?php kajob_icon( 'plus', array( 'size' => 17 ) ); ?>
							<?php esc_html_e( 'Post a job', 'kajob' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php else : ?>
			<div class="jm-dashboard-header">
				<?php foreach ( $job_dashboard_columns as $kajob_key => $kajob_column ) : ?>
					<div class="jm-col jm-col--<?php echo esc_attr( $kajob_key ); ?>"><?php echo esc_html( $kajob_column ); ?></div>
				<?php endforeach; ?>
				<div class="jm-col jm-col--actions"><?php esc_html_e( 'Manage', 'kajob' ); ?></div>
			</div>

			<div class="jm-dashboard-rows">
				<?php foreach ( $jobs as $kajob_job ) : ?>
					<div class="jm-row">
						<?php foreach ( $job_dashboard_columns as $kajob_key => $kajob_column ) : ?>
							<div class="jm-col jm-col--<?php echo esc_attr( $kajob_key ); ?>">
								<span class="jm-col__label"><?php echo esc_html( $kajob_column ); ?></span>
								<div class="jm-col__value">
									<?php do_action( 'job_manager_job_dashboard_column_' . $kajob_key, $kajob_job ); ?>
								</div>
							</div>
						<?php endforeach; ?>

						<div class="jm-col jm-col--actions job-dashboard-job-actions">
							<span class="jm-col__label"><?php esc_html_e( 'Manage', 'kajob' ); ?></span>
							<div class="jm-actions">
								<?php
								$kajob_row_actions = isset( $job_actions[ $kajob_job->ID ] ) ? $job_actions[ $kajob_job->ID ] : array();
								$kajob_delete      = null;

								if ( isset( $kajob_row_actions['delete'] ) ) {
									$kajob_delete = $kajob_row_actions['delete'];
									unset( $kajob_row_actions['delete'] );
								}

								do_action( 'job_manager_job_dashboard_column_actions', $kajob_job, $kajob_row_actions );

								$kajob_icons = array(
									'edit'          => 'edit',
									'duplicate'     => 'copy',
									'mark_filled'   => 'check-circle',
									'mark_not_filled' => 'refresh',
									'relist'        => 'refresh',
									'renew'         => 'refresh',
									'continue'      => 'arrow-right',
								);

								foreach ( $kajob_row_actions as $kajob_action ) {
									printf(
										'<a href="%1$s" class="jm-action job-dashboard-action-%2$s" title="%3$s">%4$s<span>%3$s</span></a>',
										esc_url( $kajob_action['url'] ),
										esc_attr( $kajob_action['name'] ),
										esc_attr( $kajob_action['label'] ),
										isset( $kajob_icons[ $kajob_action['name'] ] ) ? kajob_get_icon( $kajob_icons[ $kajob_action['name'] ], array( 'size' => 15 ) ) : '' // phpcs:ignore WordPress.Security.EscapeOutput -- fixed path map.
									);
								}

								if ( $kajob_delete ) {
									printf(
										'<a href="%1$s" class="jm-action jm-action--danger job-dashboard-action-delete" data-confirm="%2$s" title="%3$s">%4$s<span>%3$s</span></a>',
										esc_url( $kajob_delete['url'] ),
										esc_attr( sprintf( __( 'Delete “%s”? This cannot be undone.', 'kajob' ), get_the_title( $kajob_job->ID ) ) ),
										esc_attr( $kajob_delete['label'] ),
										kajob_get_icon( 'trash', array( 'size' => 15 ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- fixed path map.
									);
								}

								if ( ! $kajob_row_actions && ! $kajob_delete ) {
									echo '<span class="jm-muted">' . esc_html__( 'Read only', 'kajob' ) . '</span>';
								}
								?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $max_num_pages > 1 ) : ?>
		<nav class="jm-pagination" aria-label="<?php esc_attr_e( 'Your listings', 'kajob' ); ?>">
			<?php
			echo wp_kses_post( paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%', $kajob_base ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $max_num_pages,
				'mid_size'  => 1,
				'prev_text' => kajob_get_icon( 'arrow-left', array( 'size' => 16 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Previous', 'kajob' ) . '</span>',
				'next_text' => kajob_get_icon( 'arrow-right', array( 'size' => 16 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Next', 'kajob' ) . '</span>',
			) ) );
			?>
		</nav>
	<?php endif; ?>

	<p class="jm-dashboard__note">
		<?php
		printf(
			/* translators: 1: listings shown, 2: total listings. */
			esc_html__( 'Showing %1$s of %2$s listings on this account.', 'kajob' ),
			esc_html( number_format_i18n( count( $jobs ) ) ),
			esc_html( number_format_i18n( $found ) )
		);
		?>
	</p>
</div>