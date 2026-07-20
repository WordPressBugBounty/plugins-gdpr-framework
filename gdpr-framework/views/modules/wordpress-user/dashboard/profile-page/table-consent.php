<hr>
<?php if ($consentData): ?>
	<table class="gdpr-consent">
		<th colspan="2"><?= _x('Consents given', '(Admin)', 'gdpr-framework'); ?></th>
		<?php foreach ($consentData as $item): ?>
			<tr>
				<td>
					&#10004;
				</td>
				<td>
					<?php // Security fix (SECURITY-AUDIT.md Finding 2 / PR review Finding 4): wp_kses() with the narrow allow-list keeps built-in Privacy Policy/Terms links clickable while stripping scripts and unsafe URLs; custom titles are sanitized to plain text on write. ?>
					<?= wp_kses($item['title'], gdpr_allowed_consent_title_html()); ?> Valid until <?= esc_html($item['valid_until']); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php else: ?>
	<p><?= _x('No consents given', '(Admin)', 'gdpr-framework'); ?>.</p>
<?php endif; ?>
