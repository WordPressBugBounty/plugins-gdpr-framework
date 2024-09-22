<?php

namespace Codelight\GDPR\Admin;

use Codelight\GDPR\Admin\AdminNotice;

class AdminPrivacySafe extends AdminNotice
{
    public function render()
    {
        if (!$this->template) {
            return;
        }

        echo gdpr('view')->render('admin/notices/header-privacy-safe');
        echo gdpr('view')->render($this->template, $this->data);
        echo gdpr('view')->render('admin/notices/footer-step');
    }
}