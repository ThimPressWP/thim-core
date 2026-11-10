<?php $mobile_popup = '';
    if( $args['options']['enable_mobile_popup'] !== 'on') {
        $mobile_popup = 'mobile-hide-modal';
    }
    if ( get_theme_mod( 'navbar_mobile_show', false ) ) {
        $mobile_popup .= ' high-bottom';
    }
?>

<button class="thimcookie-btn-revisit thim-hide <?php echo esc_attr( $mobile_popup ); ?>"
    title="<?php echo esc_attr('Consent Preferences','thim-core');?>" 
    onclick="thimCustomise()"
>
    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="27" fill="none" stroke="#50575E" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M12 2a10 10 0 0 0-1 19.95 10 10 0 0 0 9-14.82A2.5 2.5 0 0 1 15.5 5a2.5 2.5 0 0 1-2.45-3A10 10 0 0 0 12 2Z"/>
        <circle cx="10.5" cy="9" r="1.5"/>
        <circle cx="15.5" cy="13" r="1.5"/>
        <circle cx="7.5" cy="16" r="1.5"/>
    </svg>
</button>