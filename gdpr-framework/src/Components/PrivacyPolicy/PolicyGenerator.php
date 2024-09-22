<?php

namespace Codelight\GDPR\Components\PrivacyPolicy;

class PolicyGenerator
{
    public function generate()
    {
        return gdpr('view')->render(
            'policy/policy',
            $this->getData()
        );
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