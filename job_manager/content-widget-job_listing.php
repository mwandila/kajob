<?php
/**
 * Job listing inside the plugin's Recent/Featured Jobs widget.
 *
 * Override of wp-job-manager/templates/content-widget-job_listing.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

if ( ( post_password_required( $post ) && ! is_super_admin() ) || ! job_manager_user_can_view_job_listing( $post->ID ) ) {
	return;
}

$kajob_widget_salary = kajob_get_salary_label( $post->ID );
?>
<li class="widget-job">
	<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
		<?php echo kajob_get_company_logo( $post->ID, 'widget-job__logo', 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="widget-job__body">
			<span class="widget-job__title"><?php echo esc_html( get_the_title( $post->ID ) ); ?></span>
			<span class="widget-job__meta">
				<?php echo esc_html( get_post_meta( $post->ID, '_company_name', true ) ); ?>
				<?php if ( $kajob_widget_salary ) : ?> · <?php echo esc_html( $kajob_widget_salary ); ?><?php endif; ?>
			</span>
		</span>
		<?php kajob_icon( 'chevron-right', array( 'size' => 16, 'class' => 'widget-job__arrow' ) ); ?>
	</a>
</li>