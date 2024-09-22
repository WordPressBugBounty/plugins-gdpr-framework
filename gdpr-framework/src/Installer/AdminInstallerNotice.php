<?php

namespace Codelight\GDPR\Installer;

use Codelight\GDPR\Admin\AdminNotice;

class AdminInstallerNotice extends AdminNotice
{
    public function render()
    {
        if (!$this->template) {
            return;
        }

        echo gdpr('view')->render('admin/notices/header-step');
        echo gdpr('view')->render($this->template, $this->data);
        echo gdpr('view')->render('admin/notices/footer-step');
    }
}