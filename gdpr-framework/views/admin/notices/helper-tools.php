<p>
    <?php echo esc_html_x('The contents of this page should contain the [gdpr_privacy_tools] shortcode.', '(Admin)', 'gdpr-framework'); ?>
</p>
<p>
    <?php
        echo sprintf(
            esc_html_x('Read more %shere%s', '(Admin)', 'gdpr-framework'), 
            "<a href='". esc_url_raw($helpUrl) . "' target=\"_blank\">", "</a>"
        );
    ?>
</p>
