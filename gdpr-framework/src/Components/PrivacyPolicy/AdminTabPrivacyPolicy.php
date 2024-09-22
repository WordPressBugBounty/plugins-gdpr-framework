<?php

namespace Codelight\GDPR\Components\PrivacyPolicy;

use Codelight\GDPR\Admin\AdminTab;

class AdminTabPrivacyPolicy extends AdminTab
{
    /* @var string */
    protected $slug = 'privacy-policy';

    /* @var PolicyGenerator */
    protected $policyGenerator;

    public function __construct(PolicyGenerator $policyGenerator)
    {
        $this->policyGenerator = $policyGenerator;

        $this->title = _x('Privacy Policy', '(Admin)', 'gdpr-framework');

        $this->registerSetting('gdpr_company_name');
        $this->registerSetting('gdpr_contact_email');
        $this->registerSetting('gdpr_company_location');

        $this->registerSetting('gdpr_representative_contact_name');
        $this->registerSetting('gdpr_representative_contact_email');
        $this->registerSetting('gdpr_representative_contact_phone');

        $this->registerSetting('gdpr_dpa_website');
        $this->registerSetting('gdpr_dpa_email');
        $this->registerSetting('gdpr_dpa_phone');

        $this->registerSetting('gdpr_has_dpo');
        $this->registerSetting('gdpr_dpo_name');
        $this->registerSetting('gdpr_dpo_email');
        $this->registerSetting('gdpr_delete_text');

        /*
        $this->registerSetting('gdpr_pp_data_gathered_1');
        $this->registerSetting('gdpr_pp_data_gathered_2');
        $this->registerSetting('gdpr_pp_data_gathered_3');
        $this->registerSetting('gdpr_pp_data_gathered_4');

        $this->registerSetting('gdpr_pp_data_usage_1');
        $this->registerSetting('gdpr_pp_data_usage_2');
        $this->registerSetting('gdpr_pp_data_usage_3');
        $this->registerSetting('gdpr_pp_data_usage_4');

        $this->registerSetting('gdpr_pp_data_partners');
        */

        add_action('gdpr/admin/action/privacy-policy/generate', [$this, 'generatePolicy']);
    }

    public function init()
    {
        /**
         * General settings
         */
        $this->registerSettingSection(
            'gdpr_section_privacy_policy',
            _x('Privacy Policy', '(Admin)', 'gdpr-framework'),
            [$this, 'renderHeader']
        );

        /**
         * Company info
         */
        $this->registerSettingSection(
            'gdpr_section_privacy_policy_company',
            _x('Company information', '(Admin)', 'gdpr-framework')
        );

        $this->registerSettingField(
            'gdpr_company_name',
            _x('Company Name', '(Admin)', 'gdpr-framework'),
            [$this, 'renderCompanyNameHtml'],
            'gdpr_section_privacy_policy_company'
        );


        $this->registerSettingField(
            'gdpr_company_email',
            _x('Company Email', '(Admin)', 'gdpr-framework'),
            [$this, 'renderCompanyEmailHtml'],
            'gdpr_section_privacy_policy_company'
        );

        $this->registerSettingField(
            'gdpr_company_location',
            _x('Company Location', '(Admin)', 'gdpr-framework'),
            [$this, 'renderCompanyLocationHtml'],
            'gdpr_section_privacy_policy_company'
        );

        /**
         * Representative
         */
        $this->registerSettingSection(
            'gdpr_section_privacy_policy_representative',
            false,
            [$this, 'renderRepresentativeOpen']
        );

        $this->registerSettingField(
            'gdpr_representative_contact_name',
            _x('Representative Contact Name', '(Admin)', 'gdpr-framework'),
            [$this, 'renderRepresentativeContactName'],
            'gdpr_section_privacy_policy_representative'
        );

        $this->registerSettingField(
            'gdpr_representative_contact_email',
            _x('Representative Contact Email', '(Admin)', 'gdpr-framework'),
            [$this, 'renderRepresentativeContactEmail'],
            'gdpr_section_privacy_policy_representative'
        );

        $this->registerSettingField(
            'gdpr_representative_contact_phone',
            _x('Representative Contact Phone', '(Admin)', 'gdpr-framework'),
            [$this, 'renderRepresentativeContactPhone'],
            'gdpr_section_privacy_policy_representative'
        );

        $this->registerSettingSection(
            'gdpr_section_privacy_policy_representative_close',
            false,
            [$this, 'renderRepresentativeClose']
        );

        /**
         * DPA
         */
        $this->registerSettingSection(
            'gdpr_section_privacy_policy_dpa',
            _x('Data Protection Authority', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDpaJS']
        );

        $this->registerSettingField(
            'gdpr_dpa_website',
            _x('Data Protection Authority Website', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDpaWebsite'],
            'gdpr_section_privacy_policy_dpa'
        );

        $this->registerSettingField(
            'gdpr_dpa_email',
            _x('Data Protection Authority Email', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDpaEmail'],
            'gdpr_section_privacy_policy_dpa'
        );

        $this->registerSettingField(
            'gdpr_dpa_phone',
            _x('Data Protection Authority Phone', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDpaPhone'],
            'gdpr_section_privacy_policy_dpa'
        );

        /**
         * DPO
         */
        $this->registerSettingSection(
            'gdpr_section_privacy_policy_dpo',
            _x('Data Protection Officer', '(Admin)', 'gdpr-framework'),
            function() {
                echo "<a href='https://data443.atlassian.net/servicedesk/customer/portal/2/article/28213359' target='_blank'>";
                echo _x('Knowledge base: Do I need to appoint a Data Protection Officer?', '(Admin)', 'gdpr-framework');
                echo "</a>";
            }
        );

        $this->registerSettingField(
            'gdpr_has_dpo',
            _x('Data Protection Officer', '(Admin)', 'gdpr-framework'),
            [$this, 'renderHasDPOHtml'],
            'gdpr_section_privacy_policy_dpo'
        );

        $this->registerSettingField(
            'gdpr_dpo_name',
            _x('Data Protection Officer Name', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDPONameHtml'],
            'gdpr_section_privacy_policy_dpo',
            ['class' => 'gdpr-dpo hidden']
        );

        $this->registerSettingField(
            'gdpr_dpo_email',
            _x('Data Protection Officer Email', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDPOEmailHtml'],
            'gdpr_section_privacy_policy_dpo',
            ['class' => 'gdpr-dpo hidden']
        );

        /**
         * Change Delete Text
         */

        $this->registerSettingField(
            'gdpr_delete_text',
            _x('Delete Text', '(Admin)', 'gdpr-framework'),
            [$this, 'renderDeleteTextHtml'],
            'gdpr_section_privacy_policy_company'
        );
        
    }

    public function renderHeader()
    {
        echo gdpr('view')->render('admin/privacy-policy/header');
    }

    /**
     * Company info
     */

    public function renderCompanyNameHtml()
    {
        global $gdpr;
        $value = $gdpr->Options->get('company_name') ? esc_attr($gdpr->Options->get('company_name')) : '';
        $placeholder = _x('Company Name', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_company_name' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderCompanyEmailHtml()
    {
        global $gdpr;
        $value = $gdpr->Options->get('contact_email') ? esc_attr($gdpr->Options->get('contact_email')) : '';
        $placeholder = _x('Contact Email', '(Admin)', 'gdpr-framework');
        echo "<input type='email' name='gdpr_contact_email' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderCompanyLocationHtml()
    {
        global $gdpr;
        $country = $gdpr->Options->get('company_location') ? $gdpr->Options->get('company_location') : '';
        $countrySelectOptions = gdpr('helpers')->getCountrySelectOptions($country);
        echo gdpr('view')->render('admin/privacy-policy/company-location', compact('countrySelectOptions'));
    }

    /**
     * Representative contact
     */

    public function renderRepresentativeOpen()
    {
        echo "<span class='gdpr-representative hidden'>";
        echo "<h3>";
        echo _x('Representative Contact', '(Admin)', 'gdpr-framework');
        echo "</h3>";
        echo "<a href='https://data443.atlassian.net/servicedesk/customer/portal/2/article/28082218' target='_blank'>";
        echo _x('Knowledge base: Do I need to appoint an EU-based representative?', '(Admin)', 'gdpr-framework');
        echo "</a>";
    }

    public function renderRepresentativeContactName()
    {
        global $gdpr;
        $value = $gdpr->Options->get('representative_contact_name') ? esc_attr($gdpr->Options->get('representative_contact_name')) : '';
        $placeholder = _x('Representative Contact Name', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_representative_contact_name' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderRepresentativeContactEmail()
    {
        global $gdpr;
        $value = $gdpr->Options->get('representative_contact_email') ? esc_attr($gdpr->Options->get('representative_contact_email')) : '';
        $placeholder = _x('Representative Contact Email', '(Admin)', 'gdpr-framework');
        echo "<input type='email' name='gdpr_representative_contact_email' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderRepresentativeContactPhone()
    {
        global $gdpr;
        $value = $gdpr->Options->get('representative_contact_phone') ? esc_attr($gdpr->Options->get('representative_contact_phone')) : '';
        $placeholder = _x('Representative Contact Phone', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_representative_contact_phone' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderRepresentativeClose()
    {
        echo "</span>";
    }

    /**
     * Data Protection Authority
     */
    public function renderDpaJS()
    {
        //echo "<a href='https://data443.com/wordpress-gdpr-framework/knowledge-base/do-i-need-to-appoint-an-eu-based-representative/' target='_blank'>";
        echo sprintf(
          _x('See the %slist of contacts here%s.', '(Admin)', 'gdpr-framework'),
          '<a href="http://ec.europa.eu/justice/data-protection/article-29/structure/data-protection-authorities/index_en.htm" target="_blank">',
          '</a>'
        );
        //echo "</a>";

        $dpaData = json_encode(gdpr('helpers')->getDataProtectionAuthorities());
        echo gdpr('view')->render('admin/privacy-policy/dpa', compact('dpaData'));
    }

    public function renderDpaWebsite()
    {
        global $gdpr;
        $value = $gdpr->Options->get('dpa_website') ? esc_attr($gdpr->Options->get('dpa_website')) : '';
        $placeholder = _x('Data Protection Authority Website', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_dpa_website' id='gdpr_dpa_website' placeholder='{$placeholder}' value='{$value}' data-set='{$value}'>";
    }

    public function renderDpaEmail()
    {
        global $gdpr;
        $value = $gdpr->Options->get('dpa_email') ? esc_attr($gdpr->Options->get('dpa_email')) : '';
        $placeholder = _x('Data Protection Authority Email', '(Admin)', 'gdpr-framework');
        echo "<input type='email' name='gdpr_dpa_email' id='gdpr_dpa_email' placeholder='{$placeholder}' value='{$value}' data-set='{$value}'>";
    }

    public function renderDpaPhone()
    {
        global $gdpr;
        $value = $gdpr->Options->get('dpa_phone') ? esc_attr($gdpr->Options->get('dpa_phone')) : '';
        $placeholder = _x('Data Protection Authority Phone', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_dpa_phone' id='gdpr_dpa_phone' placeholder='{$placeholder}' value='{$value}' data-set='{$value}'>";
    }

    /**
     * Data Protection Officer
     */

    public function renderHasDPOHtml()
    {
        global $gdpr;
        $hasDPO = $gdpr->Options->get('has_dpo');
        echo gdpr('view')->render('admin/privacy-policy/has-dpo', compact('hasDPO'));
    }

    public function renderDPONameHtml()
    {
        global $gdpr;
        $value = $gdpr->Options->get('dpo_name') ? esc_attr($gdpr->Options->get('dpo_name')) : '';
        $placeholder = _x('DPO Name', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_dpo_name' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderDPOEmailHtml()
    {
        global $gdpr;
        $value = $gdpr->Options->get('dpo_email') ? esc_attr($gdpr->Options->get('dpo_email')) : '';
        $placeholder = _x('DPO Email', '(Admin)', 'gdpr-framework');
        echo "<input type='email' name='gdpr_dpo_email' placeholder='{$placeholder}' value='{$value}'>";
    }

    public function renderDeleteTextHtml()
    {
        global $gdpr;
        $value = $gdpr->Options->get('delete_text') ? esc_attr($gdpr->Options->get('delete_text')) : '';
        $placeholder = _x('Delete Text', '(Admin)', 'gdpr-framework');
        echo "<input name='gdpr_delete_text' placeholder='{$placeholder}' value='{$value}'>";
    }
    
    public function generatePolicy()
    {
        global $gdpr;
        $policyPage = $gdpr->Options->get('policy_page');

        if ( ! $policyPage) {
            return;
        }

        $policy = gdpr('view')->render(
            'policy/policy'
        );

        wp_update_post([
            'ID'           => $policyPage,
            'post_content' => $policy,
        ]);

        wp_safe_redirect(gdpr('helpers')->getAdminUrl('&gdpr-tab=privacy-policy&gdpr_notice=policy_generated'));
    }

    /**
     * Render either the settings or the generated policy
     */
    public function renderContents()
    {
        if (isset($_GET['settings-updated']) && 'true' == $_GET['settings-updated']) {
            return $this->renderPolicy();
        } else {
            return parent::renderContents();
        }
    }

    public function renderPolicy()
    {
        global $gdpr;
        $policyPageId = $gdpr->Options->get('policy_page');
        if ($policyPageId) {
            $policyUrl = get_edit_post_link($policyPageId);
        } else {
            $policyUrl = false;
        }

        $editor = $this->getPolicyEditor();
        $backUrl = gdpr('helpers')->getAdminUrl('&gdpr-tab=privacy-policy');

        return gdpr('view')->render('admin/privacy-policy/generated', compact('editor', 'policyUrl', 'backUrl'));
    }

    protected function getPolicyEditor()
    {
        ob_start();

        wp_editor(
            wp_kses_post($this->policyGenerator->generate()),
            'gdpr_policy',
            [
                'media_buttons' => false,
                'editor_height' => 600,
                'teeny' => true,
                'editor_css' => '<style>#mceu_16 { display: none; }</style>'
            ]
        );

        return ob_get_clean();
    }

    /**
     * Render WP's default submit button
     */
    public function renderSubmitButton()
    {
        submit_button(_x('Save & Generate Policy', '(Admin)', 'gdpr-framework'));
    }
}
