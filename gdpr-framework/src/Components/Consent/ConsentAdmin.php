<?php

namespace Codelight\GDPR\Components\Consent;

class ConsentAdmin
{
    public function __construct()
    {
        add_filter('gdpr/admin/tabs', [$this, 'registerAdminTab'], 20);
    }

    public function registerAdminTab($tabs)
    {
        global $gdpr;
        $tabs['consent'] = new AdminTabConsent($gdpr->Consent);

        return $tabs;
    }
}