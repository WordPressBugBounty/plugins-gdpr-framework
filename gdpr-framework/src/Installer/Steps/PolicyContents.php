<?php


namespace Codelight\GDPR\Installer\Steps;


use Codelight\GDPR\Installer\InstallerStep;
use Codelight\GDPR\Installer\InstallerStepInterface;

class PolicyContents extends InstallerStep implements InstallerStepInterface
{
    protected $slug = 'policy-contents';

    protected $type = 'wizard';

    protected $template = 'installer/steps/policy-contents';

    protected $activeSteps = 2;

    protected function renderContent()
    {
        global $gdpr;
        $policyUrl = get_permalink($gdpr->Options->get('policy_page'));
        add_filter( 'gdpr_custom_policy_link', 'gdprfPrivacyPolicyurl' );
        $policyUrl = apply_filters( 'gdpr_custom_policy_link',$policyUrl);
        $editPolicyUrl = get_edit_post_link($gdpr->Options->get('policy_page'));
        $policyGenerated = $gdpr->Options->get('policy_generated');

        echo gdpr('view')->render(
            $this->template,
            compact('policyUrl', 'editPolicyUrl', 'policyGenerated')
        );
    }
}
