<?php

namespace Codelight\GDPR\Admin;

class Modal
{
    protected $id;

    protected $template;

    protected $data;

    /**
     * AdminNotice constructor.
     */
    public function __construct()
    {
        add_action('admin_footer', [$this, 'render']);
    }

    public function add($id, $template, $data = [])
    {
        $this->id = $id;
        $this->template = $template;
        $this->data = $data;
        $this->data['id'] = $this->id;
    }

    public function render()
    {
        if (!$this->template) {
            return;
        }

        echo gdpr('view')->render('admin/modals/header', $this->data);
        echo gdpr('view')->render($this->template, $this->data);
        echo gdpr('view')->render('admin/modals/footer', $this->data);
    }
}
