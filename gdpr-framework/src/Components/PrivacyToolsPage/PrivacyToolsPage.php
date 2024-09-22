<?php

namespace Codelight\GDPR\Components\PrivacyToolsPage;

class PrivacyToolsPage
{
    public function __construct()
    {
        global $gdpr;
        $controller = new PrivacyToolsPageController(
            $gdpr->DataSubjectAuthenticator
            , $gdpr->DataSubjectIdentificator
            , $gdpr->DataSubject
            , $gdpr->DataExporter
            , $gdpr->UserConsentModel
            );
        $gdpr->Controller = $controller;
        new PrivacyToolsPageShortcode($controller, $gdpr->Consent);
    }
}