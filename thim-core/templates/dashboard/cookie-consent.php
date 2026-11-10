<div class="tc-cookie-consent-wrapper wrap">
	<?php // Registration box
        do_action( 'thim_dashboard_registration_box' );
	?>

    <!-- Notice: Link to documentation -->
    <div class="tc-notice tc-info">
        <div class="content">
            <?php 
                echo sprintf(
                    esc_html__( 'You can read the documentation %s to understand how to manage Cookie Consent on your site', 'thim-core' ),
                    '<a href="https://help.physcode.com/hc/articles/1/2/8/how-to-use-gdpr-feature-cookie-consent" target="__blank">' . esc_html__( 'here', 'thim-core' ). '</a>'
                ); 
            ?>
        </div>
    </div>

    <div class="row">
        <!-- Cookie Banner Settings -->
        <div class="col-md-6 col-xs-12">
            <div class="tc-box">
                <div class="tc-box-header">
                    <h2 class="box-title">
                        <?php esc_html_e( 'Cookie Banner', 'thim-core' ); ?>
                    </h2>
                </div>
                <div class="tc-box-body">
                    <form class="cookie-consent-form" id="cookie-banner-form" action="" method="post">
                        <div class="box-field">
                            <label class="" for="enable_popup">
                                <input type="checkbox" name="enable_popup" id="enable_popup" <?php if( $args['enable_popup'] == 'on' ) echo 'checked';?>>
                                <?php esc_html_e( 'Enable Cookie Consent Popup', 'thim-core' ); ?>
                            </label>
                        </div>
                        <div class="box-field">
                            <label class="block-label" for="popup_position">
                                <?php esc_html_e( 'Popup Position ( Desktop )', 'thim-core' ); ?>
                            </label>
                            <select name="popup_position" id="popup_position">
                                <?php
                                $positions = array(
                                    'top-left'     => esc_html__( 'Top Left', 'thim-core' ),
                                    'top-right'    => esc_html__( 'Top Right', 'thim-core' ),
                                    'bottom-left'  => esc_html__( 'Bottom Left', 'thim-core' ),
                                    'bottom-right' => esc_html__( 'Bottom Right', 'thim-core' ),
                                    'md-center'    => esc_html__( 'Center', 'thim-core' ),
                                );

                                foreach ( $positions as $value => $label ) {
                                    $selected = ( $args['popup_position'] === $value ) ? 'selected' : '';
                                    echo "<option value='" . esc_attr( $value ) . "' $selected>$label</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="box-field">
                            <label class="" for="enable_mobile_popup">
                                <input type="checkbox" name="enable_mobile_popup" id="enable_mobile_popup" <?php if( $args['enable_mobile_popup'] == 'on' ) echo 'checked';?>>
                                <?php esc_html_e( 'Show Popup on Mobile', 'thim-core' ); ?>
                            </label>
                        </div>
                        <div class="box-field">
                            <label class="" for="enable_revisit_button">
                                <input type="checkbox" name="enable_revisit_button" id="enable_revisit_button" <?php if( $args['enable_revisit_button'] == 'on' ) echo 'checked';?>>
                                <?php esc_html_e( 'Show Revisit Consent Button', 'thim-core' ); ?>
                            </label>
                        </div>
                        <div class="box-field">
                            <label class="block-label" for="consent_message">
                                <?php esc_html_e( 'Consent Message', 'thim-core' ); ?>
                            </label>
                            <?php
                                wp_editor($args['consent_message'], 'consent_message', [
                                    'textarea_name' => 'consent_message',
                                    'media_buttons' => false,
                                    'textarea_rows' => 4,
                                    'tinymce' => true,
                                    'quicktags' => true
                                ]);
                            ?>
                        </div>
                        <div class="box-field">
                            <label class="block-label" for="customise_consent_mess">
                                <?php esc_html_e( 'Customise Consent Message', 'thim-core' ); ?>
                            </label>
                            <?php
                                wp_editor($args['customise_consent_mess'], 'customise_consent_mess', [
                                    'textarea_name' => 'customise_consent_mess',
                                    'media_buttons' => false,
                                    'textarea_rows' => 5,
                                    'tinymce' => true,
                                    'quicktags' => true
                                ]);
                            ?>
                            <small style="margin-top: 8px; display: block;">
                                <?php esc_html_e( 'Show Categories:', 'thim-core' ); ?>
                                <code>{{necessary}}</code>, 
                                <code>{{analytics}}</code>, 
                                <code>{{ads}}</code>, 
                                <code>{{functional}}</code>
                            </small>
                        </div>

                        <?php wp_nonce_field( 'cookie_consent_settings_nonce', 'cookie_consent_nonce' ); ?>
                        <button class="button button-primary tc-button" type="submit">
                            <?php esc_html_e( 'Save Changes', 'thim-core' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Cookie Manager: Categories -->
        <div class="col-md-6 col-xs-12">
            <div class="tc-box">
                <div class="tc-box-header">
                    <h2 class="box-title">
                        <?php esc_html_e( 'Cookie Manager', 'thim-core' ); ?>
                    </h2>
                </div>

                <?php
                    $cookie_category = isset($_GET['cookie-category']) ? sanitize_text_field($_GET['cookie-category']) : 'necessary';
                    $categories      = isset($args['cookie_categories']) ? $args['cookie_categories'] : [];
                    $cookie_list     = isset($args['cookie_list']) ? $args['cookie_list'] : [];
                ?>
                <div class="tc-box-body">
                    <div class="box-field"> 
                        <label style="display: inline-block; margin-right: 20px;" for="cookie_category">
                            <?php esc_html_e( 'Select Categories', 'thim-core' ); ?>
                        </label>
                        <select name="cookie_category" id="cookie_category">
                            <?php foreach ( $categories as $category_key => $category_data ) {
                                $selected = ( $cookie_category === $category_key ) ? 'selected' : '';
                                echo "<option value='" . esc_attr( $category_key ) . "' $selected>" . esc_html( $category_data['title'] ) . "</option>";
                            } ?>
                        </select>
                        <p class="cookie-category-note" style="margin-top: 8px; font-size: 14px;">
                            <em>
                                <?php esc_html_e( 'Please select a category to manage cookies', 'thim-core' ); ?>
                            </em>
                        </p>
                    </div>

                    <form class="cookie-consent-form" id="cookie-manager-form" action="" method="post">
                        <?php
                            $data = array(
                                'cookie_category' => $cookie_category, 
                                'categories'      => $categories,
                                'cookie_list'     => $cookie_list
                            );
                            Thim_Template_Helper::template( 'cookie-category-fields.php', $data, true );
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scan for get List Cookie Used On Your Site -->
    <div class="row" id="thimcookie-scanner">
        <div class="col-md-12 col-xs-12">
            <div class="tc-box">
                <div class="tc-box-header">
                    <h2 class="box-title">
                        <?php esc_html_e( 'All Cookie List', 'thim-core' ); ?>
                    </h2>
                    <p>
                        <?php esc_html_e( 'For more specific details about a cookie, you can search by cookie name at ', 'thim-core' ); ?>
                        <a href="https://cookiedatabase.org/" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'CookieDatabase.org', 'thim-core' ); ?>
                        </a>
                    </p>
                </div>
                <div class="tc-box-body">
                    <div class="table-wrapper">
                        <table id="cookie-scan-list-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Cookie Name (ID)', 'thim-core' ); ?></th>
                                    <th><?php esc_html_e( 'Domain', 'thim-core' ); ?></th>
                                    <th><?php esc_html_e( 'Type', 'thim-core' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Cookies will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
