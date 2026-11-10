<?php
    $categories      = $args['categories'];
    $cookie_category = $args['cookie_category'];
    $cookie_list     = $args['cookie_list'];

    if ( isset( $categories[ $cookie_category ] ) ) {
        $category_data = $categories[ $cookie_category ];
    }
    if ( isset( $cookie_list[ $cookie_category ] ) ) {
        $cat_cookie_list = $cookie_list[ $cookie_category ];
    }
?>

<p>
    <label class="block-label" for="cat_cookie_title">
        <?php esc_html_e( 'Title', 'thim-core' ); ?>
    </label>
    <input type="text" name="cat_cookie_title" id="cat_cookie_title" placeholder="" value="<?php echo esc_attr( $category_data['title'] ); ?>">
</p>
<p>
    <label class="block-label" for="cat_cookie_desc">
        <?php esc_html_e( 'Description', 'thim-core' ); ?>
    </label>
    <textarea name="cat_cookie_desc" id="cat_cookie_desc" rows="3" cols="70" placeholder=""><?php echo esc_textarea( $category_data['desc'] ); ?></textarea>
</p>
<div class="cookie-list-settings">
    <label class="block-label" for="cat_cookie_list">
        <?php esc_html_e( 'Cookie List', 'thim-core' ); ?>
        <?php echo sprintf( ' <a href="#thimcookie-scanner">%s</a>', esc_html__('( You can refer here )','thim-core') );?>
    </label>
    <div class="data-ck-list">
        <?php if( !empty( $cat_cookie_list) ) : ?>
            <table class="cookie-list-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Cookie', 'thim-core' ); ?></th>
                        <th><?php esc_html_e( 'Domain', 'thim-core' ); ?></th>
                        <th><?php esc_html_e( 'Duration', 'thim-core' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'thim-core' ); ?></th>
                        <?php if( $cookie_category !== 'necessary' ) { ?>
                        <th><?php esc_html_e( 'Script Source', 'thim-core' ); ?></th>
                        <?php } ?>
                        <th><?php esc_html_e( 'Action', 'thim-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $cat_cookie_list as $cookie ) : ?>
                        <tr>
                            <td><?php echo esc_html( $cookie['id'] ); ?></td>
                            <td><?php echo esc_html( $cookie['domain'] ); ?></td>
                            <td><?php echo esc_html( $cookie['duration'] ); ?></td>
                            <td><?php echo esc_html( $cookie['desc'] ); ?></td>
                            <?php if( $cookie_category !== 'necessary' ) { ?>
                            <td><?php echo esc_html( $cookie['src'] ); ?></td>
                            <?php } ?>
                            <td>
                            <button 
                                class="delete-cookie-button" 
                                data-cookie-id="<?php echo esc_attr( $cookie['id'] ); ?>" 
                                data-cookie-category="<?php echo esc_attr( $cookie_category ); ?>" 
                                data-nonce="<?php echo esc_attr( wp_create_nonce( 'delete_cookie_nonce' ) ); ?>" 
                                type="button">
                                <?php esc_html_e( 'Delete', 'thim-core' ); ?>
                            </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else:?>
            <div class="empty-data" style="font-size: 14px;">
                <?php esc_html_e( 'Cookie List is Empty -> Add new Cookie', 'thim-core' ); ?>
            </div>
        <?php endif;  ?>
    </div>
</div>

<!-- Additional fields for Cookie List -->
<div id="cookie-list-container">
    <h4 style="margin-top:0;">
        <?php esc_html_e( 'Add new Cookie', 'thim-core' ); ?>
    </h4>
    <div class="row">
        <div class="col-md-6 col-xs-12">
            <p>
                <label class="block-label" for="ck_list_id">
                    <?php esc_html_e( 'Cookie ID *', 'thim-core' ); ?>
                </label>
                <input type="text" name="ck_list_id[]" id="ck_list_id" placeholder="" value="" required>
            </p>
            <p>
                <label class="block-label" for="ck_list_domain">
                    <?php esc_html_e( 'Cookie Domain *', 'thim-core' ); ?>
                </label>
                <input type="text" name="ck_list_domain[]" id="ck_list_domain" placeholder="" value="" required>
            </p>
        </div>
        <div class="col-md-6 col-xs-12">
            <p>
                <label class="block-label" for="ck_list_duration">
                    <?php esc_html_e( 'Cookie Duration *', 'thim-core' ); ?>
                </label>
                <input type="text" name="ck_list_duration[]" id="ck_list_duration" placeholder="" value="" required>
            </p>
            <p>
                <label class="block-label" for="ck_list_desc">
                    <?php esc_html_e( 'Cookie Description *', 'thim-core' ); ?>
                </label>
                <textarea name="ck_list_desc[]" id="ck_list_desc" rows="3" cols="30" placeholder="" required></textarea>
            </p>
        </div>
        <?php if( $cookie_category !== 'necessary' ) { ?>
            <div class="col-md-12 col-xs-12">
                <p>
                    <label class="block-label" for="ck_list_src">
                        <?php esc_html_e( 'Script Source URL', 'thim-core' ); ?>
                        <?php esc_html_e( '( for blocking the third-party script settings of this cookie )', 'thim-core' ); ?>
                    </label>
                    <input type="text" name="ck_list_src[]" id="ck_list_src" placeholder="https://example.com/script.js" value="">
                </p>
            </div>
        <?php } ?>
    </div>
</div>

<?php wp_nonce_field( 'cookie_consent_settings_nonce', 'cookie_consent_$nonce' ); ?>
<input type="hidden" name="cookie_category" value="<?php echo esc_attr($cookie_category);?>">

<button class="button button-primary tc-button" type="submit">
    <?php esc_html_e( 'Save Changes', 'thim-core' ); ?>
</button>