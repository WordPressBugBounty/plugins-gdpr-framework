<?php if (count($consentData) or $consentInfo): ?>
    <hr>
    <h2><?= __('Consent', 'gdpr-framework'); ?></h2>
    <?php if (count($consentData)): ?>
        <form method="post">
            <p><?= __('Here you can withdraw any consents you have given.', 'gdpr-framework'); ?></p>
            <table class="gdpr-consent gdpr-consent-user">
                <th colspan="3"><?= __('Consents', 'gdpr-framework'); ?></th>
                <?php foreach ($consentData as $item): ?>
                    <tr>
                        <td>
                            &#10004;
                        </td>
                        <td class="gdpr-consent-user-title">
                          <?php // Security fix (SECURITY-AUDIT.md Finding 2 / PR review Finding 4): wp_kses() with the narrow allow-list keeps built-in Privacy Policy/Terms links clickable while stripping scripts and unsafe URLs; custom titles are sanitized to plain text on write. ?>
                          <?= wp_kses($item['title'], gdpr_allowed_consent_title_html()); ?>
                        </td>
                        <td class="gdpr-consent-user-desc">
                          <?= esc_html($item['description']); ?>
                        </td>
                        <td>
                            <?php if ('privacy-policy' !== $item['slug']): ?>
                                <a href="<?= esc_url($item['withdraw_url']); ?>" class="button button-primary">
                                    <?= __('Withdraw', 'gdpr-framework'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </form>
    <?php endif; ?>

    <?php if ($consentInfo): ?>
        <p class="gdpr-consent-disclaimer">
            <em><?= do_shortcode($consentInfo); ?></em>
        </p>
    <?php endif; ?>

<?php endif; ?>
