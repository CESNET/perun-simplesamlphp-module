<?php

declare(strict_types=1);

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\IsEligible;
use SimpleSAML\XHTML\Template;

if (empty($_REQUEST[IsEligible::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . IsEligible::PARAM_STATE_ID . '\' query parameter.');
}

$state = State::loadState($_REQUEST[IsEligible::PARAM_STATE_ID], IsEligible::STAGE);

$config = Configuration::getInstance();
$t = new Template($config, IsEligible::TEMPLATE);

$restartUrl = $state[State::RESTART] ?: null;

$headerKey = '{perun:perun:403_is_eligible_default_header}';
$textKey = '{perun:perun:403_is_eligible_default_text}';
$buttonKey = '{perun:perun:403_is_eligible_default_button}';
$contactKey = '{perun:perun:403_is_eligible_default_contact}';

$translations = $state[IsEligible::TRANSLATIONS] ?: [];
if (!empty($translations)) {
    $translator = $t->getTranslator();
    $headerKey = IsEligible::loadLocalTranslation(
        $translator,
        IsEligible::HEADER_TRANSLATION,
        $translations,
        $headerKey
    );
    $textKey = IsEligible::loadLocalTranslation($translator, IsEligible::TEXT_TRANSLATION, $translations, $textKey);
    $buttonKey = IsEligible::loadLocalTranslation(
        $translator,
        IsEligible::BUTTON_TRANSLATION,
        $translations,
        $buttonKey
    );
    $contactKey = IsEligible::loadLocalTranslation(
        $translator,
        IsEligible::CONTACT_TRANSLATION,
        $translations,
        $contactKey
    );
}

$t->data[IsEligible::PARAM_RESTART_URL] = $restartUrl;
$t->data[IsEligible::HEADER_TRANSLATION] = $headerKey;
$t->data[IsEligible::TEXT_TRANSLATION] = $textKey;
$t->data[IsEligible::BUTTON_TRANSLATION] = $buttonKey;
$t->data[IsEligible::CONTACT_TRANSLATION] = $contactKey;

$t->show();
