<?php

require_once(dirname(__FILE__).'/src/Singleton.php');

function gdpr($name)
{
    global $gdpr;
    if ($name === 'admin-notice') {
        return $gdpr->AdminNotice;
    } elseif ($name === 'themes') {
        return $gdpr->Themes;
    } elseif ($name === 'view') {
        return $gdpr->View;
    } elseif ($name === 'helpers') {
        return $gdpr->Helpers;
    } elseif ($name === 'admin-error') {
        return $gdpr->AdminError;
    } elseif ($name === 'options') {
        return $gdpr->Options;
    } elseif ($name === 'consent') {
        return $gdpr->Consent;
    } elseif ($name === 'data-subject') {
        return $gdpr->DataSubject;
    } elseif ($name === 'controller') {
        return $gdpr->Controller;
    }
    die("Unknown name in gdpr: " . $name);
}

add_action('init', function() {

    if (!is_admin()) {
        return;
    }

    new \Codelight\GDPR\SetupAdmin();
}, 0);

include_once(dirname(__FILE__).'/src/Updater/Updater.php');

add_action('plugins_loaded', function () use($gdpr_error) {
    new \Codelight\GDPR\Updater\Updater();

    global $gdpr;
    $gdpr = new \Codelight\GDPR\Singleton();
    $gdpr->init(__FILE__);
}, 0);
