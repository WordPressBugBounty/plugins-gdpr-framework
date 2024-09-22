<?php

namespace Codelight\GDPR\Installer\Steps;

use Codelight\GDPR\Installer\InstallerStep;
use Codelight\GDPR\Installer\InstallerStepInterface;

class ConfigurationPages extends InstallerStep implements InstallerStepInterface
{
    protected $slug = 'configuration-pages';

    protected $type = 'wizard';

    protected $template = 'installer/steps/configuration-pages';

    protected $activeSteps = 1;

    protected function renderContent()
    {
        global $gdpr;
        $policyPage         = $gdpr->Options->get('policy_page');
        $policyPageSelector = wp_dropdown_pages([
            'name'              => 'gdpr_policy_page',
            'show_option_none'  => _x('&mdash; Create a new page &mdash;', '(Admin)', 'gdpr-framework'),
            'option_none_value' => 'new',
            'selected'          => $policyPage ? $policyPage : 'new',
            'echo'              => false,
            'class'             => 'gdpr-select js-gdpr-select2',
        ]);
        
        $privacyToolsPage         = $gdpr->Options->get('tools_page');
        $privacyToolsPageSelector = wp_dropdown_pages([
            'name'              => 'gdpr_tools_page',
            'show_option_none'  => _x('&mdash; Create a new page &mdash;', '(Admin)', 'gdpr-framework'),
            'option_none_value' => 'new',
            'selected'          => $privacyToolsPage ? $privacyToolsPage : 'new',
            'echo'              => false,
            'class'             => 'gdpr-select js-gdpr-select2',
        ]);

        echo gdpr('view')->render(
            $this->template,
            compact(
                'policyPage',
                'policyPageSelector',
                'privacyToolsPage',
                'privacyToolsPageSelector'
            )
        );
    }

    public function submit()
    {
        global $gdpr;
        if (isset($_POST['gdpr_create_tools_page']) && 'yes' === $_POST['gdpr_create_tools_page']) {
            $id = $this->createPrivacyToolsPage();
            $gdpr->Options->set('tools_page', $id);
        } else {
            $gdpr->Options->set('tools_page', $_POST['gdpr_tools_page']);
        }
    }

    protected function createPrivacyToolsPage()
    {
        $id = wp_insert_post([
            'post_content' => '<!-- wp:shortcode -->[gdpr_privacy_tools]<!-- /wp:shortcode -->',
            'post_title'   => __('Privacy Tools', 'gdpr-framework'),
            'post_type'    => 'page',
        ]);

        return $id;
    }
}
