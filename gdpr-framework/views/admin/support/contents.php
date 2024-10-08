<hr>
<?php global $gdpr; ?>
<section class="section">
    <h3 class="align-center">
        <?= esc_html_x('Need more info?', '(Admin)', 'gdpr-framework'); ?>
    </h3>
    <div class="row">
        <div class="col">
          <div class="col_image" style="background-image:url('<?= $gdpr->PluginUrl; ?>/assets/1.png');"></div>
            <a class="button button-primary" href="<?= gdpr('helpers')->siteOwnersGuide(); ?>" target="_blank">
                <?= esc_html_x('Site Owner\'s guide to GDPR', '(Admin)', 'gdpr-framework'); ?>
            </a>
            <p>
                <?= esc_html_x('Read the full guide on GDPR compliance.', '(Admin)', 'gdpr-framework'); ?>
            </p>
        </div>
        <div class="col">
          <div class="col_image" style="background-image:url('<?= $gdpr->PluginUrl; ?>/assets/2.png');"></div>
            <a class="button button-primary" href="<?= gdpr('helpers')->knowledgeBase(); ?>" target="_blank">
                <?= esc_html_x('Knowledge base', '(Admin)', 'gdpr-framework'); ?>
            </a>
            <p>
                <?= esc_html_x('Check out the knowledge base for common questions and answers.', '(Admin)', 'gdpr-framework'); ?>
            </p>
        </div>
        <div class="col">
          <div class="col_image" style="background-image:url('<?= $gdpr->PluginUrl; ?>/assets/3.png');"></div>
            <a class="button button-primary" href="<?= gdpr('helpers')->developerDocs(); ?>" target="_blank">
                <?= esc_html_x('Developer\'s guide to GDPR', '(Admin)', 'gdpr-framework'); ?>
            </a>
            <p>
                <?= esc_html_x('We have a thorough guide to help making custom sites compliant.', '(Admin)', 'gdpr-framework'); ?>
            </p>
        </div>
    </div>
</section>

<section class="section">
    <h3 class="align-center">
        <?= esc_html_x('Need help?', '(Admin)', 'gdpr-framework'); ?>
    </h3>
    <div class="row">
        <div class="col">
          <div class="col_image" style="background-image:url('<?= $gdpr->PluginUrl; ?>/assets/4.png');"></div>
            <a class="button button-primary" href="<?=gdpr('helpers')->supportRequest()?>" target="_blank">
                <?= esc_html_x('Submit a support request', '(Admin)', 'gdpr-framework'); ?>
            </a>
            <p>
                <?= esc_html_x('Found a bug or have a question about the plugin? Submit a support request and we’ll get right on it!', '(Admin)', 'gdpr-framework'); ?>
            </p>
        </div>
        <div class="col">
          <div class="col_image" style="background-image:url('<?= $gdpr->PluginUrl; ?>/assets/5.png');"></div>
            <a class="button button-primary" href="<?= gdpr('helpers')->docs('contact/'); ?>" target="_blank">
                <?= esc_html_x('Request a consultation', '(Admin)', 'gdpr-framework'); ?>
            </a>
            <p>
                <?= esc_html_x('Need assistance in making your site compliant? We can help!', '(Admin)', 'gdpr-framework'); ?>
            </p>
        </div>
    </div>
</section>
