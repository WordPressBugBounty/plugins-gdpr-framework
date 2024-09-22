<?php

namespace Codelight\GDPR\Components\Support;

class Support
{
    public function __construct()
    {
        add_filter('gdpr/admin/tabs', [$this, 'registerTab'], 40);
    }

    public function registerTab($tabs)
    {
        $tabs['support'] = new AdminTabSupport();

        return $tabs;
    }
}
