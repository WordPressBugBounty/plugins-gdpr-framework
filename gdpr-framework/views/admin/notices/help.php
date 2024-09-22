<p>
    <?= sprintf(
         esc_html_x('Need help? Take a look at our %sdocumentation%s.', '(Admin)', 'gdpr-framework'),
         '<a href="' . gdpr('helpers')->siteOwnersGuide() . '" target="_blank">',
         '</a>'
    ); ?>
</p>
