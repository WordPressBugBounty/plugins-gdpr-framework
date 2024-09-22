<p>
    <?= esc_html_x('The GDPR Framework has not been set up yet. Would you like to do that?', '(Admin)', 'gdpr-framework'); ?> <br>
    <?= esc_html_x('Our setup wizard will guide you through the process.', '(Admin)', 'gdpr-framework'); ?> <br>
    <?= esc_html_x('You can also configure the plugin manually by going to', '(Admin)', 'gdpr-framework'); ?> 
            <a href="<?= gdpr('helpers')->getAdminUrl(); ?>"> <?= esc_html_x('Tools', '(Admin)', 'gdpr-framework'); ?> > 
                                                                                <?= esc_html_x('Data443 GDPR', '(Admin)', 'gdpr-framework'); ?> </a>.
</p>

<a class="button button-primary" href="<?= $installerUrl; ?>">
    <?= esc_html_x('Run the setup wizard', '(Admin)', 'gdpr-framework'); ?>
</a>

<a class="button button-secondary" href="<?= $autoInstallUrl; ?>">
    <?= esc_html_x('Auto-install pages', '(Admin)', 'gdpr-framework'); ?>
</a>

<a class="button button-secondary" href="<?= $skipUrl; ?>">
    <?= esc_html_x('Skip and install manually', '(Admin)', 'gdpr-framework'); ?>
</a>
