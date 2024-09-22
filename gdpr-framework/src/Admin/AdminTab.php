<?php

namespace Codelight\GDPR\Admin;

/**
 * Base class for admin tabs. Extend this.
 *
 * Class AdminTab
 *
 * @package Codelight\GDPR\Admin
 */
abstract class AdminTab implements AdminTabInterface
{
    /* @var string */
    protected $slug;

    /* @var string */
    protected $title;

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getOptionsGroupName()
    {
        return 'gdpr_' . $this->getSlug();
    }

    /**
     * Register a setting on the admin page
     *
     * @param        $optionName
     * @param string $args
     */
    public function registerSetting($optionName, $args = [])
    {
        register_setting($this->getOptionsGroupName(), $optionName, $args);
    }

    /**
     * Register a section on the admin page
     *
     * @param $name
     * @param $callback
     */
    public function registerSettingSection($id, $title, $callback = null)
    {
        add_settings_section(
            $id,
            $title,
            $callback,
            $this->getOptionsGroupName()
        );
    }

    /**
     * Register a setting field on the admin page
     *
     * @param $id
     * @param $title
     * @param $callback
     */
    public function registerSettingField($id, $title, $callback = null, $section = '', $args = [])
    {
        add_settings_field(
            $id,
            $title,
            $callback,
            $this->getOptionsGroupName(),
            $section,
            $args
        );
    }

    /**
     * Render the contents including settings fields, sections and submit button.
     * Trigger hooks for rendering content before and after the settings fields.
     *
     * @return string
     */
    public function renderContents()
    {
        global $gdpr;
        $tabs = array("general", "cookie-popup", "consent", "privacy-policy", "do-not-sell", "support");
        ob_start();

        if (in_array($this->slug, $tabs)) {
            ?>
            <style>
                .column {
                    float: left;
                }
                .left {
                    width: 75%;
                }

                .right {
                    width: 25%;
                }

                /* Clear floats after the columns */
                .row:after {
                    content: "";
                    display: table;
                    clear: both;
                }
                @media screen and (max-width: 600px) {
                    .column {
                        width: 100%;
                    }
                }
            </style>
            <div class="row">
                <div class="column left">
            <?php
        }

        do_action("gdpr/tabs/{$this->getSlug()}/before", $this);
        settings_fields($this->getOptionsGroupName());
        do_settings_sections($this->getOptionsGroupName());
        do_action("gdpr/tabs/{$this->getSlug()}/after", $this);

        $this->renderSubmitButton();

        if (in_array($this->slug, $tabs)) {
            ?>
                </div>
                <div class="column right">
                    <div style="float:left;max-width:250px;margin:9px;margin-left:20px;margin-top:50px;">
                        <b>How to contact us?</b>
                        <ul style="list-style:circle;margin-left:20px;">
                            <li>
                                <a href="<?=gdpr('helpers')->data443()?>" target="_blank">Data443 (homepage)</a>
                            </li>
                            <li>
                                <a href="<?=gdpr('helpers')->supportRequest()?>" target="_blank">Contact Support</a>
                            </li>
                            <li>
                                <a href="https://www.facebook.com/data443/" target="_blank">Like us on Facebook</a>
                            </li>
                            <li>
                                <a href="https://twitter.com/data443risk" target="_blank">Follow us on Twitter</a>
                            </li>
                            <li>
                                <a href="<?=gdpr('helpers')->wordpressReview()?>" target="_blank">Rate us on WordPress</a>
                            </li>
                        </ul>
                        <div style="border: solid 1px black;border-radius: 5px;background: #f2efe6;padding:10px;padding-bottom:30px;">
                            <p style="margin-top:0px;">Do you like this plugin?</p>
                            <p>With the <b>PREMIUM</b> version you can have other awesome features:</p>

                            <div style="clear:both; margin-top: 2px;"></div>
                            <div>
                                <div style="float:left; vertical-align:middle; height:24px; margin-right:5px; margin-top:-3px;">
                                    <img src="<?=$gdpr->PluginUrl?>/assets/images/tick.png" />
                                </div>
                                <p style="margin-top:0px;">Custom Consent Text</p>
                            </div>

                            <div style="clear:both; margin-top: 2px;"></div>
                            <div>
                                <div style="float:left; vertical-align:middle; height:24px; margin-right:5px; margin-top:-3px;">
                                    <img src="<?=$gdpr->PluginUrl?>/assets/images/tick.png" />
                                </div>
                                <p style="margin-top:0px;">Data Rectification</p>
                            </div>

                            <div style="clear:both; margin-top: 2px;"></div>
                            <div>
                                <div style="float:left; vertical-align:middle; height:24px; margin-right:5px; margin-top:-3px;">
                                    <img src="<?=$gdpr->PluginUrl?>/assets/images/tick.png" />
                                </div>
                                <p style="margin-top:0px;">Custom relative URLs for the Privacy Policy and other files</p>
                            </div>

                            <div style="clear:both;"></div>
                            <div style="height:10px;"></div>
                            <div style="float:right;"><a href="<?=gdpr('helpers')->premiumStore()?>" target="_blank">Get PREMIUM now >></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    /**
     * Render WP's default submit button
     */
    public function renderSubmitButton()
    {
        submit_button(_x('Save', '(Admin)', 'gdpr-framework'));
    }

    /**
     * Enqueue scripts, run the child class init function, trigger action for adding custom stuff
     */
    public function setup()
    {
        // Automatically run the 'enqueue' method if it exists
        if (method_exists($this, 'enqueue')) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        }

        $this->init();

        // This hook can be used for registering custom settings
        do_action("gdpr/tabs/{$this->getSlug()}/init", $this);

        // Render the admin notices
        add_action('admin_notices', [$this, 'renderAdminNotices']);
    }

    /**
     * Render success notices via admin_notice action
     */
    public function renderAdminNotices()
    {
        if ('tools_page_privacy' !== get_current_screen()->base) {
            return;
        }

        if (!isset($_REQUEST['gdpr_notice'])) {
            return;
        }

        if ('policy_generated' === $_REQUEST['gdpr_notice']) {
            $message = _x('Policy generated!', '(Admin)', 'gdpr-framework');
            $class = 'notice notice-success';
        }

        echo gdpr('view')->render('admin/notice', compact('message', 'class'));
    }
}
