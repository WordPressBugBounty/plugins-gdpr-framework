<?php

namespace Codelight\GDPR\Admin;

class AdminHelper
{
    public function __construct()
    {
        $this->toolsHelper();
        $this->autoinstallHelper();
        $this->policyHelper();
        $this->settingsHelper();
    }

    protected function toolsHelper()
    {
        global $gdpr;
        $toolsPage = $gdpr->Options->get('tools_page');

        // Display the notice only on Tools page
        if (!$toolsPage || !isset($_GET['post']) || $_GET['post'] !== $toolsPage) {
            return;
        }

        $post = get_post($toolsPage);
        $helpUrl = gdpr('helpers')->siteOwnersGuide();

        if (!stristr($post->post_content, '<!-- wp:shortcode -->[gdpr_privacy_tools]<!-- /wp:shortcode -->')) {
            gdpr('admin-notice')->add('admin/notices/helper-tools', compact('helpUrl'));
        }
    }

    protected function autoinstallHelper()
    {
        if (!isset($_GET['gdpr-notice']) || empty($_GET['gdpr-notice']) || 'autoinstall' !== $_GET['gdpr-notice']) {
            return;
        }

        $helpUrl = gdpr('helpers')->PrivacyPolicy();

        gdpr('admin-notice')->add('admin/notices/helper-autoinstall', compact('helpUrl'));
    }

    protected function policyHelper()
    {
        global $gdpr;
        $policyPage = $gdpr->Options->get('policy_page');

        // Display the notice only on Policy page
        if (!$policyPage || !isset($_GET['post']) || $_GET['post'] !== $policyPage) {
            return;
        }

        $post = get_post($policyPage);
        $helpUrl = gdpr('helpers')->docs();

        if (stristr($post->post_content, '[TODO]')) {
            gdpr('admin-notice')->add('admin/notices/helper-policy', compact('helpUrl'));
        }
    }

    protected function settingsHelper()
    {
        global $gdpr;
        if ($gdpr->Options->get('is_installed') &&
            ((!$gdpr->Options->get('tools_page') || is_null(get_post($gdpr->Options->get('tools_page'))))) && !$gdpr->Options->get('custom_tools_page')) {
            $this->renderSettingsHelperNotice();
        }

        if ('download_and_notify' === $gdpr->Options->get('export_action') || 'notify' === $gdpr->Options->get('export_action')) {
            if (!$gdpr->Options->get('export_action_email')) {
                $this->renderSettingsHelperNotice();
            }
        }

        if ('anonymize_and_notify' === $gdpr->Options->get('delete_action') ||
            'delete_and_notify' === $gdpr->Options->get('delete_action') ||
            'notify' === $gdpr->Options->get('delete_action')
        ) {
            if (!$gdpr->Options->get('delete_action_email')) {
                $this->renderSettingsHelperNotice();
            }
        }
    }

    protected function renderSettingsHelperNotice()
    {
        gdpr('admin-notice')->add('admin/notices/helper-settings');
    }
}
