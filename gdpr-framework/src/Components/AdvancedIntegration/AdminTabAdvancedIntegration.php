<?php

namespace Codelight\GDPR\Components\AdvancedIntegration;

use Codelight\GDPR\Admin\AdminTab;

class AdminTabAdvancedIntegration extends AdminTab
{
    /* @var string */
    protected $slug = 'advanced-integration';

    /* @var PolicyGenerator */
    protected $policyGenerator;

    public function __construct()
    {
        $this->title = _x('Data Hound', '(Admin)', 'gdpr-framework');

        add_action('gdpr/admin/action/AdvancedIntegration/generate', [$this, 'generateAdvancedIntegration']);
    }

    public function init()
    {
        $this->registerSettingSection(
            'gdpr_section_privacy_policy',
            _x('Data Hound', '(Admin)', 'gdpr-framework'),
            [$this, 'renderHeader']
        );
    }

    public function renderHeader()
    {
        echo gdpr('view')->render('admin/advanced-integration/header');
    }

    public function renderSubmitButton()
    {
        // leave an empty method to prevent the placement of the default save button
    }
}
