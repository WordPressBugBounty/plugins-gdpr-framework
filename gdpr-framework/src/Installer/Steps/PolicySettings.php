<?php

namespace Codelight\GDPR\Installer\Steps;

use Codelight\GDPR\Installer\InstallerStep;
use Codelight\GDPR\Installer\InstallerStepInterface;

class PolicySettings extends InstallerStep implements InstallerStepInterface
{
    protected $slug = 'policy-settings';

    protected $type = 'wizard';

    protected $template = 'installer/steps/policy-settings';

    protected $activeSteps = 2;

    protected function renderContent()
    {
        global $gdpr;
        $policyPage         = $gdpr->Options->get('policy_page');
        $policyPageSelector = wp_dropdown_pages([
            'name'              => 'gdpr_policy_page',
            'show_option_none'  => _x('&mdash; Create a new page &mdash;', '(Admin)', 'gdpr-framework'),
            'option_none_value' => 'new',
            'selected'          => $policyPage ? $policyPage : 'new',
            'echo'              => false,
            'class'             => 'gdpr-select js-gdpr-select2',
        ]);

        $hasTermsPage = $gdpr->Options->get('has_terms_page');
        $termsPage    = $gdpr->Options->get('terms_page');
        $policy_page_url = $gdpr->Options->get('custom_policy_page');
        // Woo compatibility
        if (!$termsPage && get_option('woocommerce_terms_page_id')) {
            $hasTermsPage  = 'yes';
            $termsPage     = get_option('woocommerce_terms_page_id');
            $termsPageNote = _x(
                'We have automatically selected your WooCommerce Terms & Conditions page.',
                '(Admin)',
                'gdpr-framework'
            );
        } else {
            $termsPageNote = false;
        }

        $termsPageSelector = wp_dropdown_pages([
            'name'              => 'gdpr_terms_page',
            'show_option_none'  => _x('&mdash; Create a new page &mdash;', '(Admin)', 'gdpr-framework'),
            'option_none_value' => 'new',
            'selected'          => $termsPage ? $termsPage : 'new',
            'echo'              => false,
            'class'             => 'gdpr-select js-gdpr-select2',
        ]);
        $termsPageUrl = $gdpr->Options->get('custom_terms_page');

        $companyName     = $gdpr->Options->get('company_name');
        $companyLocation = $gdpr->Options->get('company_location');
        $countryOptions  = gdpr('helpers')->getCountrySelectOptions($companyLocation);
        $contactEmail    = $gdpr->Options->get('contact_email') ?
            $gdpr->Options->get('contact_email') :
            get_option('admin_email');

        $representativeContactName  = $gdpr->Options->get('representative_contact_name');
        $representativeContactEmail = $gdpr->Options->get('representative_contact_email');
        $representativeContactPhone = $gdpr->Options->get('representative_contact_phone');

        $dpaWebsite  = $gdpr->Options->get('dpa_website');
        $dpaEmail = $gdpr->Options->get('dpa_email');
        $dpaPhone = $gdpr->Options->get('dpa_phone');
        $dpaData = json_encode(gdpr('helpers')->getDataProtectionAuthorities());

        $hasDPO   = $gdpr->Options->get('has_dpo');
        $dpoName  = $gdpr->Options->get('dpo_name');
        $dpoEmail = $gdpr->Options->get('dpo_email');

        echo gdpr('view')->render(
            $this->template,
            compact(
                'policyPage',
                'policyPageSelector',
                'policy_page_url',
                'companyName',
                'companyLocation',
                'contactEmail',
                'countryOptions',
                'hasDPO',
                'dpoEmail',
                'dpoName',
                'representativeContactName',
                'representativeContactEmail',
                'representativeContactPhone',
                'dpaWebsite',
                'dpaEmail',
                'dpaPhone',
                'dpaData',
                'hasTermsPage',
                'termsPage',
                'termsPageSelector',
                'termsPageNote',
                'termsPageUrl'
            )
        );
    }

    /*
    public function validate()
    {
        if (!is_email($_POST['gdpr_contact_email'])) {
            $this->errors = 'Company email is not a valid email!';
            return false;
        }

        return true;

        //filter_var($url, FILTER_VALIDATE_URL) === FALSE
    }
    */

    public function submit()
    {
        global $gdpr;
        /**
         * Policy page
         */
        if (isset($_POST['gdpr_create_policy_page']) && 'yes' === $_POST['gdpr_create_policy_page']) {
            $id = $this->createPolicyPage();
            $gdpr->Options->set('policy_page', $id);
        } else {
            $gdpr->Options->set('policy_page', sanitize_text_field($_POST['gdpr_policy_page']));
        }


        /**
         * Custom Policy page URL
         */
        if (isset($_POST['gdpr_custom_policy_page']) && '' != $_POST['gdpr_custom_policy_page']) {
            $gdpr->Options->set('custom_policy_page',sanitize_text_field($_POST['gdpr_custom_policy_page']));
        } else {
            $gdpr->Options->set('custom_policy_page', "");
        }

        /**
         * 'Generate policy' checkbox
         */
        if (isset($_POST['gdpr_generate_policy']) && 'yes' === $_POST['gdpr_generate_policy']) {
            $this->generatePolicy();
            $gdpr->Options->set('policy_generated', true);
        } else {
            $gdpr->Options->set('policy_generated', false);
        }

        /**
         * Company information
         */
        $gdpr->Options->set('company_name', sanitize_text_field($_POST['gdpr_company_name']));
        $gdpr->Options->set('company_location', sanitize_text_field($_POST['gdpr_company_location']));

        if (is_email($_POST['gdpr_contact_email'])) {
            $gdpr->Options->set('contact_email', sanitize_email($_POST['gdpr_contact_email']));
        }

        /**
         * Data Protection Officer
         */
        if (isset($_POST['gdpr_has_dpo'])) {
            $gdpr->Options->set('has_dpo', sanitize_text_field($_POST['gdpr_has_dpo']));
        }

        $gdpr->Options->set('dpo_name', sanitize_text_field($_POST['gdpr_dpo_name']));

        if (is_email($_POST['gdpr_dpo_email'])) {
            $gdpr->Options->set('dpo_email', sanitize_email($_POST['gdpr_dpo_email']));
        }

        /**
         * Representative contact
         */
        $gdpr->Options->set('representative_contact_name', sanitize_text_field($_POST['gdpr_representative_contact_name']));
        $gdpr->Options->set('representative_contact_phone', sanitize_text_field($_POST['gdpr_representative_contact_phone']));

        if (is_email($_POST['gdpr_representative_contact_email'])) {
            $gdpr->Options->set('representative_contact_email', sanitize_email($_POST['gdpr_representative_contact_email']));
        }

        /**
         * Data protection authority
         */
        $gdpr->Options->set('dpa_website', sanitize_text_field($_POST['gdpr_dpa_website']));
        $gdpr->Options->set('dpa_phone', sanitize_text_field($_POST['gdpr_dpa_phone']));

        if (is_email($_POST['gdpr_dpa_email'])) {
            $gdpr->Options->set('dpa_email', sanitize_email($_POST['gdpr_dpa_email']));
        }


        /**
         * Terms page
         */
        if (isset($_POST['gdpr_has_terms_page'])) {
            $gdpr->Options->set('has_terms_page', sanitize_text_field($_POST['gdpr_has_terms_page']));
        }

        if (isset($_POST['gdpr_has_terms_page']) && 'yes' === $_POST['gdpr_has_terms_page'] && isset($_POST['gdpr_terms_page'])) {
            $gdpr->Options->set('terms_page', sanitize_text_field($_POST['gdpr_terms_page']));
        } else {
            $gdpr->Options->delete('terms_page');
        }
    }

    protected function createPolicyPage()
    {
        $id = wp_insert_post([
            'post_title'   => __('Privacy Policy', 'gdpr-framework'),
            'post_type'    => 'page',
        ]);

        return $id;
    }

    protected function generatePolicy()
    {
        global $gdpr;
        wp_update_post([
            'ID'           => $gdpr->Options->get('policy_page'),
            'post_content' => gdpr('view')->render(
                'policy/policy',
                $this->getData()
            ),
        ]);
    }

    public function getData()
    {
        global $gdpr;
        $location = $gdpr->Options->get('company_location');
        $date = date(get_option('date_format'));

        return [
            'companyName'     => $gdpr->Options->get('company_name'),
            'companyLocation' => $location,
            'contactEmail'    => $gdpr->Options->get('contact_email') ?
                $gdpr->Options->get('contact_email') :
                get_option('admin_email'),

            'hasRepresentative'          => gdpr('helpers')->countryNeedsRepresentative($location),
            'representativeContactName'  => $gdpr->Options->get('representative_contact_name'),
            'representativeContactEmail' => $gdpr->Options->get('representative_contact_email'),
            'representativeContactPhone' => $gdpr->Options->get('representative_contact_phone'),

            'dpaWebsite' => $gdpr->Options->get('dpa_website'),
            'dpaEmail'   => $gdpr->Options->get('dpa_email'),
            'dpaPhone'   => $gdpr->Options->get('dpa_phone'),

            'hasDpo'   => $gdpr->Options->get('has_dpo'),
            'dpoName'  => $gdpr->Options->get('dpo_name'),
            'dpoEmail' => $gdpr->Options->get('dpo_email'),

            'hasTerms' => $gdpr->Options->get('terms_page'),

            'date' => $date,
        ];
    }
}
