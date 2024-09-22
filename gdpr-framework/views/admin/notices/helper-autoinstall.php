<p>
    <?= esc_html_x('A Privacy Policy page has been created, but it is empty. You can generate a policy template on this page.', '(Admin)', 'gdpr-framework'); ?>
</p>
<p>
    <?= 
        sprintf(esc_html_x('Read more about the %sPrivacy Policy%s', '(Admin)', 'gdpr-framework'), "<a href='{$helpUrl}' target='_blank'>", "</a>");
     ?>
</p>
