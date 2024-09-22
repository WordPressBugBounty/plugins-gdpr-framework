<?php

namespace Codelight\GDPR;

use Codelight\GDPR\Admin\AdminError;
use Codelight\GDPR\Admin\AdminNotice;
use Codelight\GDPR\Admin\Modal;
use Codelight\GDPR\Admin\WordpressAdmin;
use Codelight\GDPR\Admin\WordpressAdminPage;
use Codelight\GDPR\Components\Consent\ConsentAdmin;
use Codelight\GDPR\Components\CookiePopup\CookiePopup;
use Codelight\GDPR\Installer\Installer;
use Codelight\GDPR\Installer\AdminInstallerNotice;
use Codelight\GDPR\Admin\AdminPrivacySafe;
use Codelight\GDPR\DataSubject\DataSubjectAdmin;
use Codelight\GDPR\Components\PrivacyPolicy\PrivacyPolicy;
use Codelight\GDPR\Components\DoNotSell\DoNotSell;
use Codelight\GDPR\Components\Support\Support;
use Codelight\GDPR\Components\AdvancedIntegration\AdvancedIntegration;
use Codelight\GDPR\Components\PrivacySafe\PrivacySafe;

/**
 * Register and set up admin components.
 * This class is instantiated at admin_init priority 0
 *
 * Class SetupAdmin
 *
 * @package Codelight\GDPR
 */
class SetupAdmin
{
    public function __construct()
    {
        $this->registerComponents();
        $this->runComponents();
    }

    /**
     * Register components in the container
     */
    protected function registerComponents()
    {
        global $gdpr;
        $gdpr->AdminNotice = new AdminNotice();
        $gdpr->AdminError = new AdminError();
        $gdpr->AdminInstallerNotice = new AdminInstallerNotice();
        $gdpr->PrivacySafe = new AdminPrivacySafe();
        $gdpr->AdminModal = new Modal();
        $gdpr->AdminPage = new WordpressAdminPage();
        $gdpr->AdminTabGeneral = new Admin\AdminTabGeneral();
    }

    protected function runComponents()
    {
        global $gdpr;
        new WordpressAdmin($gdpr->AdminPage);
        new Installer($gdpr->AdminTabGeneral);
        new CookiePopup();
        new ConsentAdmin();
        new DataSubjectAdmin();
        new PrivacyPolicy();
        new DoNotSell();
        new Support();
        new AdvancedIntegration();
        new PrivacySafe();
    }
}
