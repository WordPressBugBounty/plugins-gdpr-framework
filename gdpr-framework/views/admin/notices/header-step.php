<?php global $gdpr; ?>
<div class="notice notice-gdpr">
    <img class="gdpr-badge" src="<?= $gdpr->PluginUrl; ?>assets/gdpr-rhino.svg" />
    <div class="gdpr-content">
        <form method="POST">
            <input type="hidden" name="gdpr-installer" value="next">