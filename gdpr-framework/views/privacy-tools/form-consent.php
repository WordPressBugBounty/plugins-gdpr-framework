<?php if ($consentData or $consentInfo): ?>
    <h2><?= __('Consent', 'gdpr-framework'); ?></h2>

    <?php if ($consentData): ?>
        <p>
            <?= __('Here you can withdraw any consents you have given.', 'gdpr-framework'); ?>
        </p>
        <table class="gdpr-consent">
            <th colspan="4"><?= __('Consent types', 'gdpr-framework'); ?></th>
            <?php foreach ($consentData as $item): ?>
                <tr>
                    <td>
                        &#10004;
                    </td>
                    <td>
                        <?php /* Security fix (SECURITY-AUDIT.md Finding 2 / PR review Finding 4): custom titles are sanitized to plain text on write; wp_kses() with the narrow allow-list keeps built-in Privacy Policy/Terms links clickable while stripping scripts and unsafe URLs. */ ?>
                        <?= wp_kses($item['title'], gdpr_allowed_consent_title_html()); ?>
                    </td>
                    <td>
                        <em><?= esc_html($item['description']); ?></em>
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
    <?php endif; ?>

    <?php if ($consentInfo): ?>
        <div class="gdpr-consent-disclaimer">
            <?= do_shortcode($consentInfo); ?>
        </div>
    <?php endif; ?>
    <hr>
<?php endif; ?>
