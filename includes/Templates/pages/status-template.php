<?php
defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];
?>

<div class="container-fluid">
	<div class="container">
		<ul class="list-group">
			<!-- PHP Version -->
			<li class="list-group-item">
				<div class="row">
					<div class="col-md-6 border-end">
						<span class="item-key"><?php esc_html_e( 'PHP Version', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-6 text-end">
						<span class="item-value">
							<?php echo esc_html( phpversion() ); ?>
						</span>
					</div>
				</div>
			</li>
			<!-- GD Version -->
			<li class="list-group-item">
				<div class="row">
					<div class="col-md-6 border-end">
						<span class="item-key"><?php esc_html_e( 'GD Version', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-6 text-end">
						<span class="item-value text-w-bold">
							<?php
							$is_gd_enabled = ImageConverter::is_gd_enabled();
							if ( ! $is_gd_enabled ) {
								$template_page::install_and_version_icon( 'red' );
							} else {
								$template_page::install_and_version_icon( 'green', $template_page->get_gd_version() );
							}
							?>
						</span>
					</div>
				</div>
			</li>
			<!-- Imagick Version -->
			<li class="list-group-item">
				<div class="row">
					<div class="col-md-6 border-end">
						<span class="item-key"><?php esc_html_e( 'ImageMagick Version', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-6 text-end">
						<span class="item-value text-w-bold">
							<?php
							$is_imagick_enabled = ImageConverter::is_imagick_enabled();
							if ( $is_imagick_enabled && $template_page->get_imagick_version() ) {
								$template_page::install_and_version_icon( 'green', $template_page->get_imagick_version() );

							} else {
								$template_page::install_and_version_icon( 'red' );
							}
							?>
						</span>
					</div>
				</div>
			</li>
			<!-- AVIF Support -->
			<li class="list-group-item">
				<div class="row">
					<div class="col-md-6 border-end">
						<span class="item-key"><?php esc_html_e( 'AVIF Support', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-6 text-end">
						<span class="item-value">
							<?php
							$is_avif_supported = ImageConverter::is_type_supported( 'avif' );
							$template_page::install_and_version_icon( $is_avif_supported ? 'green' : 'red', ( ! $is_avif_supported ? 'Not ' : '' ) . 'Supported' );
							?>
						</span>
					</div>
				</div>
			</li>
			<!-- WEBP Support -->
			<li class="list-group-item">
				<div class="row">
					<div class="col-md-6 border-end">
						<span class="item-key"><?php esc_html_e( 'WEBP Support', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-6 text-end">
						<span class="item-value">
							<?php
							$is_webp_supported = ImageConverter::is_type_supported( 'webp' );
							$template_page::install_and_version_icon( $is_webp_supported ? 'green' : 'red', ( ! $is_webp_supported ? 'Not ' : '' ) . 'Supported' );
							?>
						</span>
					</div>
				</div>
			</li>
		</ul>
		<?php
		if ( ! ImageConverter::is_type_supported( 'avif' ) ) :
			?>
			<div class="notice notice-error avif-reqs py-2 px-3">

				<span>
				<?php
				/* translators: 1: GD Library name 2: Imagemagick Library name. */
				printf( esc_html__( 'AVIF requires %1$s compiled with AVIF support OR %2$s at least. please contact your hosting support regarding that.', 'image-type-converter' ), '<strong>GD </strong>', '<strong>ImageMagick V 7.0.25</strong>' );
				?>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( ImageConverter::is_type_supported( 'avif' ) && ! ImageConverter::is_avif_allowed() ) : ?>
		<div class="avif-allowed-notice notice notice-warning py-2 px-3">
			<span>
			<?php
			/* translators: %s: AVIF support Plugin name */
			printf( esc_html__( 'AVIF images are not allowed for upload. If you want to upload and use AVIF images, we recommend our plugin %s', 'image-type-converter' ), '<a href="https://wordpress.org/plugins/avif-support/" target="_blank" >AVIF Support</a>' );
			?>
			</span>
		</div>
		<?php endif; ?>
	</div>

	<div class="container optimizers-status-wrapper accordion my-5" id="ImgOptimizersAccordion">
		<h3 class="mb-2 p-3 bg-white border shadow-sm"><?php esc_html_e( 'Optimizers status', 'image-type-converter' ); ?><span><?php $core->new_keyword( 'New', false ); ?></span></h3>
		<?php
		$optimizers = ImageOptimizer::get_optimizers();
		if ( ! $template_page::is_proc_open_available() ) {
			?>
			<div id="message" class="error notice inline">
				<p><strong><?php esc_html_e( 'proc_open() function seems not available, Optimizers rely on this function. please contact your hosting support if it can be enabled or consider upgrading to a higher hosting plan', 'image-type-converter' ); ?></strong></p>
			</div>
			<?php
		}
		?>
		<ul class="list-group p-3">
			<?php foreach ( $optimizers as $optimizer_key => $optimizer_arr ) : ?>
			<li class="list-group-item accordion-item pe-4">
				<h4 class="accordion-header">
					<div class="accordion-header-wrapper p-2 accordion-button collapsed" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $optimizer_key . '-optimizer' ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $optimizer_key . '-optimizer' ); ?>">
						<div class="col-md-6 border-end">
							<span class="item-key"><?php echo esc_html( $optimizer_arr['title'] ); ?></span>
						</div>
						<div class="col-md-6 text-end me-2">
							<span class="item-value text-bold">
								<?php
								$is_optimizer_installed = ImageOptimizer::is_optimizer_installed( $optimizer_key, true );
								if ( $is_optimizer_installed && ! is_wp_error( $is_optimizer_installed ) ) {
									$template_page::install_and_version_icon( 'green', $is_optimizer_installed );

								} else {
									$template_page::install_and_version_icon( 'red' );
								}
								?>
							</span>
						</div>
					</div>
				</h4>
				<div id="<?php echo esc_attr( $optimizer_key . '-optimizer' ); ?>" class="accordion-collapse collapse" data-bs-parent="#ImgOptimizersAccordion">
					<ul class="list-group p-3 my-3">
						<!-- Image Type Target -->
						<li class="list-group-item">
							<div class="row">
								<div class="col-md-6 border-end">
									<span class="item-key"><?php esc_html_e( 'Image type Target', 'image-type-converter' ); ?></span>
								</div>
								<div class="col-md-6 text-end">
									<span class="item-value">
										<?php echo esc_html( $optimizer_arr['target'] ); ?>
									</span>
								</div>
							</div>
						</li>
						<?php
						$optimizter_installed_result = ImageOptimizer::is_optimizer_installed( $optimizer_key, true );
						if ( ! $optimizter_installed_result || is_wp_error( $optimizter_installed_result ) ) :
						?>
						<!-- Install -->
						<li class="list-group-item" style="cursor:pointer;">
							<?php esc_html_e( 'Install commands', 'image-type-converter' ); ?>
							
							<ul class="list-group p-3">
								<?php foreach ( $optimizer_arr['install'] as $os_name => $os_command ) : ?>
								<li class="list-group-item">
									<div class="row">
										<div class="col-md-6 border-end">
											<span class="item-key"><?php echo esc_html( $os_name ); ?></span>
										</div>
										<div class="col-md-6 text-end">
											<span class="item-value">
												<?php echo esc_html( $os_command ); ?>
											</span>
										</div>
									</div>
								</li>
								<?php endforeach; ?>	
							</ul>
						</li>

						<?php endif; ?>
					</ul>
				</div>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div style="margin-top:150px;">
	<?php $core->plugins_sidebar(); ?>
	</div>
</div>
