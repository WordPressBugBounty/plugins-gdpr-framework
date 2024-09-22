<?php

namespace Codelight\GDPR\Installer\Steps;

use Codelight\GDPR\Installer\InstallerStep;
use Codelight\GDPR\Installer\InstallerStepInterface;

class ConfigurationSettings extends InstallerStep implements InstallerStepInterface
{
    protected $slug = 'configuration-settings';

    protected $type = 'wizard';

    protected $template = 'installer/steps/configuration-settings';

    protected $activeSteps = 1;

    protected function renderContent()
    {
        global $gdpr;
        $privacyToolsPageUrl = get_permalink($gdpr->Options->get('tools_page'));
        $deleteAction      = $gdpr->Options->get('delete_action');
        $deleteActionEmail = $gdpr->Options->get('delete_action_email');

        $exportAction      = $gdpr->Options->get('export_action');
        $exportActionEmail = $gdpr->Options->get('export_action_email');

        $reassign = $gdpr->Options->get('delete_action_reassign');
        $reassignUser = $gdpr->Options->get('delete_action_reassign_user');

        echo gdpr('view')->render(
            $this->template,
            compact(
                'deleteAction',
                'deleteActionEmail',
                'exportAction',
                'exportActionEmail',
                'privacyToolsPageUrl',
                'reassign',
                'reassignUser'
            )
        );
    }

    public function submit()
    {
        global $gdpr;
        if (isset($_POST['gdpr_export_action'])) {
            $gdpr->Options->set('export_action', sanitize_text_field($_POST['gdpr_export_action']));
        }

        if (isset($_POST['gdpr_export_action_email'])) {
            $gdpr->Options->set('export_action_email', sanitize_email($_POST['gdpr_export_action_email']));
        }

        if (isset($_POST['gdpr_delete_action'])) {
            $gdpr->Options->set('delete_action', sanitize_text_field($_POST['gdpr_delete_action']));
        }

        if (isset($_POST['gdpr_delete_action_email'])) {
            $gdpr->Options->set('delete_action_email', sanitize_email($_POST['gdpr_delete_action_email']));
        }

        if (isset($_POST['gdpr_delete_action_reassign'])) {
            $gdpr->Options->set('delete_action_reassign', sanitize_text_field($_POST['gdpr_delete_action_reassign']));
        }

        if (isset($_POST['gdpr_delete_action_reassign_user'])) {
            $gdpr->Options->set('delete_action_reassign_user', sanitize_text_field($_POST['gdpr_delete_action_reassign_user']));
        }
    }
}
