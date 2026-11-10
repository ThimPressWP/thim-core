<?php $mobile_popup = '';
if ( $args['options']['enable_mobile_popup'] !== 'on' ) {
	$mobile_popup = 'mobile-hide-modal';
}
$customise_consent_mess = $args['options']['customise_consent_mess'];
?>

<div class="md-overlay thim-hide"></div>

<div
	class="tc-modal <?php echo esc_attr( $args['options']['popup_position'] ); ?> <?php echo esc_attr( $mobile_popup ); ?>"
	id="<?php echo esc_attr( $args['id'] ); ?>"
	data-template="<?php echo esc_attr( $args['id'] ); ?>"
>
	<!-- Banner hidden by default; JS shows it when no consent cookie exists -->
	<div class="thimcookie-banner thim-hide" id="thimcookie-banner">
			<div class="message">
				<?php echo $args['options']['consent_message']; ?>
			</div>

			<div class="cookie-action">
				<?php if ( $customise_consent_mess ) { ?>
					<button class="btn-outline" onclick="thimCustomise()">
						<?php echo esc_html__( 'Customise', 'thim-core' ); ?>
					</button>
				<?php } ?>

				<button class="btn-outline" onclick="thimCookieRejectAll()">
					<?php echo esc_html__( 'Reject All', 'thim-core' ); ?>
				</button>

				<button onclick="thimCookieAcceptAll()">
					<?php echo esc_html__( 'Accept All', 'thim-core' ); ?>
				</button>
			</div>
		</div>


	<?php
	if ( ! $customise_consent_mess ) {
		return;
	}

	// Customise cookie consent
	// Checkbox states are set by JS from document.cookie — works with page cache.
	$cookie_value = [];
	$analytics    = $ads = $functional = '';

	// Generate HTML for cookie categories
	$cookie_categories_html = [];
	foreach ( $args['options']['cookie_categories'] as $category_key => $category_data ) {
		ob_start();
		?>
		<div class="thimcookie-cat cat-<?php echo esc_attr( $category_key ); ?>">
			<span style="font-size: 17px;" class="icon-toggle">+</span>

			<div class="header-cat">
				<?php echo esc_html( $category_data['title'] ); ?>
				<?php if ( $category_key === 'necessary' ) : ?>
					<span class="note"><?php esc_html_e( 'Always Active', 'thim-core' ); ?></span>
				<?php else : ?>
					<label><input type="checkbox"
									id="consent-<?php echo esc_attr( $category_key ); ?>" <?php echo ( isset( $cookie_value[ $category_key ] ) && $cookie_value[ $category_key ] === 'yes' ) ? 'checked' : ''; ?>></label>
				<?php endif; ?>
			</div>
			<p class="desc-for-cat">
				<?php echo esc_html( $category_data['desc'] ); ?>
			</p>
			<div class="cklist-cat">
				<?php
				$cat_cookie_list = isset( $args['options']['cookie_list'][ $category_key ] ) ? $args['options']['cookie_list'][ $category_key ] : [];

				if ( ! empty( $cat_cookie_list ) ) :
					foreach ( $cat_cookie_list as $cookie ) :
						?>
						<ul class="cookie-info">
							<li>
								<label><?php echo esc_html__( 'Cookie', 'thim-core' ); ?></label>
								<span><?php echo esc_html( $cookie['id'] ); ?></span>
							</li>
							<li>
								<label><?php echo esc_html__( 'Duration', 'thim-core' ); ?></label>
								<span><?php echo esc_html( $cookie['duration'] ); ?></span>
							</li>
							<li>
								<label><?php echo esc_html__( 'Description', 'thim-core' ); ?></label>
								<span><?php echo esc_html( $cookie['desc'] ); ?></span>
							</li>
						</ul>
						<?php
					endforeach;
				else :
					?>
					<div class="empty-data" style="font-size: 14px;">
						<?php echo esc_html__( 'No Cookie to display', 'thim-core' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$cookie_categories_html[ $category_key ] = ob_get_clean();
	}

	// Replace placeholders in the customise consent message
	foreach ( $cookie_categories_html as $category_key => $category_html ) {
		$customise_consent_mess = str_replace( '{{' . $category_key . '}}', $category_html, $customise_consent_mess );
	}
	?>

	<div class="thimcookie-customise thim-hide" id="thimcookie-customise">
		<button class="thim-close-modal" title="<?php echo esc_attr( 'Close', 'thim-core' ); ?>"
				onclick="thimCloseModal()">
			<svg xmlns="http://www.w3.org/2000/svg" width="22"
				viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2"
				stroke-linecap="round" stroke-linejoin="round">
				<line x1="18" y1="6" x2="6" y2="18"/>
				<line x1="6" y1="6" x2="18" y2="18"/>
			</svg>
		</button>

		<div class="customise-content">
			<div class="message">
				<?php echo $customise_consent_mess; ?>
			</div>
		</div>

		<div class="cookie-action">
			<button class="btn-outline" onclick="thimCookieRejectAll()">
				<?php echo esc_html__( 'Reject All', 'thim-core' ); ?>
			</button>

			<button class="btn-outline" onclick="saveThimConsent()">
				<?php echo esc_html__( 'Save My Preferences', 'thim-core' ); ?>
			</button>

			<button onclick="thimCookieAcceptAll()">
				<?php echo esc_html__( 'Accept All', 'thim-core' ); ?>
			</button>
		</div>
	</div>
</div>
