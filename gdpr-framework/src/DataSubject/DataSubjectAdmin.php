<?php

namespace Codelight\GDPR\DataSubject;

class DataSubjectAdmin
{
    public function __construct()
    {
        add_filter('gdpr/admin/tabs', [$this, 'registerTab'], 30);
    }

    public function registerTab($tabs)
    {
        global $gdpr;

        $tabs['data-subject'] = new AdminTabDataSubject($gdpr->DataSubject);

        return $tabs;
    }
}