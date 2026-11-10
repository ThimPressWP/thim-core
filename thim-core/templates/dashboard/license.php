<?php
$token         = Thim_Product_Registration::get_data_theme_register( 'purchase_token' );
$purchase_code = Thim_Product_Registration::get_data_theme_register( 'purchase_code' );
$my_theme_id   = Thim_Free_Theme::get_theme_id();
$user          = wp_get_current_user();

$theme_data = Thim_Theme_Manager::get_metadata();
$theme      = $theme_data['text_domain'];
$version    = $theme_data['version'];
$is_active  = Thim_Product_Registration::is_active();
$site_key   = Thim_Product_Registration::get_site_key();
?>
<div class="tc-box tc-box-theme-license">
	<div class="tc-box-header">
		<h2 class="box-title">Activate your theme license</h2>
	</div>
	<div class="tc-box-body">
		<?php
		if ( $is_active ) :
			if ( $site_key && empty( $token ) ) {
				$link_deregister = Thim_Dashboard::get_link_main_dashboard(
					array(
						'thim-core-deregister' => true,
					)
				);
				?>
				<div class="message-success message">Your theme is activated. Thank you!</div>
				<table class="form-table">
					<tbody>
					<?php if ( ! empty( $site_key ) ) : ?>
						<tr class="license-active">
							<th scope="row">
								<?php esc_html_e( 'Site Key: ', 'thim-core' ); ?>
							</th>
							<td>
								<input type="text"
										value="<?php echo esc_html( str_repeat( '*', strlen( $site_key ) - 7 ) . substr( $site_key, - 4 ) ); ?>"
										disabled>
								<button class="deactivate-btn button button-secondary tc-button tc-button-deregister"
										data-url-deregister="<?php echo esc_url( $link_deregister ); ?>"
										data-confirm_deregister="<?php esc_html_e( 'Are you sure to remove theme activation??', 'thim-core' ); ?>">
									<?php esc_html_e( 'Deactivate', 'thim-core' ); ?>
								</button>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
				<?php
			} else {
				?>
				<div class="wrapper-message">
					<div class="message-success message">Your theme license is activated. Thank you!</div>
				</div>
				<table class="form-table">
					<tbody>
					<?php if ( ! empty( $purchase_code ) ) : ?>
						<tr class="license-active">
							<th scope="row">
								<?php esc_html_e( 'Purchase code: ', 'thim-core' ); ?>
							</th>
							<td>
								<input type="text"
										value="<?php echo esc_html( str_repeat( '*', strlen( $purchase_code ) - 7 ) . substr( $purchase_code, - 4 ) ); ?>"
										disabled>
								<button
									class="thim-deactive button button-secondary tc-button deactivate-btn tc-run-step">
									<?php esc_html_e( 'Deactivate', 'thim-core' ); ?>
								</button>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			<?php } ?>
		<?php else : ?>
			<div class="wrapper-message">
				<div class="message-info message">Activate your purchase code for this domain to turn on install plugin
					required and import data demo
				</div>
			</div>

			<form class="thim-form-license" action="" method="post">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">Purchase code <span class="required">*</span></th>
						<td>
							<input type="text" id="purchase_code" name="purchase_code" value=""
									placeholder="Enter purchase code" autocomplete="off" required>

							<p class="find-license">
								<a href="https://thimpress.com/my-account/"
									target="_blank" rel="noopener">Get my purchase code from ThimPress
								</a>
								<?php if ( ! $my_theme_id ) { ?>
									<a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code"
										target="_blank" rel="noopener">Get my purchase code from Envato Market
									</a>
								<?php } ?>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
				<p>
					<label for="agree_stored" class="agree-label">
						<input type="checkbox" name="agree_stored" id="agree_stored" required>
						I agree that my purchase code and user data will be stored by thimpress.com
					</label>
				</p>

				<input type="hidden" name="domain" value="<?php echo esc_url( site_url() ); ?>">
				<input type="hidden" name="theme" value="<?php echo esc_attr( $theme ); ?>">
				<input type="hidden" name="theme_version" value="<?php echo esc_attr( $version ); ?>">
				<input type="hidden" name="user_email"
						value="<?php echo esc_attr( $user ? $user->user_email : '' ); ?>">

				<button class="button button-primary tc-button activate-btn tc-run-step" type="submit">
					<?php esc_html_e( 'Submit', 'thim-core' ); ?>
				</button>
			</form>
		<?php endif; ?>

	</div>
	<div class="tc-box-footer">
		<p>Note: 1 Regular theme license can only be activated on 1 Domain Name. If <?php echo $theme_data['name']; ?>
			is the selection for your multiple sites, you can purchase the Extended theme license.</p>
	</div>
</div>
