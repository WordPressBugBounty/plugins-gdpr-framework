<?php


namespace Codelight\GDPR\Admin;

class AdminError extends AdminNotice
{
    public function render()
    {
        if (!$this->template) {
            return;
        }

        echo gdpr('view')->render($this->template, $this->data);
    }
}