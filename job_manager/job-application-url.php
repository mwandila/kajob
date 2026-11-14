<?php
/**
 * Apply-on-company-site route.
 *
 * Override of wp-job-manager/templates/job-application-url.php.
 *
 * @package Kajob
 * @var stdClass $apply
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p>
	<?php esc_html_e( 'Applications are handled on', 'kajob' ); ?>
	<a href="<?php echo esc_url( $apply->url ); ?>" target="_blank" rel="nofollow noopener">
		<?php echo esc_html( wp_parse_url( $apply->url, PHP_URL_HOST ) ); ?>
	</a>
</p>