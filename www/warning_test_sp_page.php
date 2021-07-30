<?php

declare(strict_types=1);

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Auth\Process\WarningTestSP;
use SimpleSAML\XHTML\Template;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:warningTestSP');

$config = Configuration::getInstance();

$t = new Template($config, 'perun:warning-test-sp-tpl.php');
$t->data[WarningTestSP::CUSTOM_TEXT_ENABLED] = false;


$perunModuleConfig = null;
try {
    $perunModuleConfig = Configuration::getConfig(WarningTestSP::CONFIG_FILE_NAME);
} catch (\Exception $ex) {
    Logger::warning("perun:warning_test_sp_page: missing or invalid '" .
        WarningTestSP::CONFIG_FILE_NAME . "' config file");
}
if ($perunModuleConfig !== null) {
    $testSpWarningConfig = $perunModuleConfig->getConfigItem(WarningTestSP::TEST_SP_CONFIG, null);
    if ($testSpWarningConfig !== null) {
        $header = $testSpWarningConfig->getArray(WarningTestSP::TEST_SP_CONFIG_HEADER, []);
        if (! empty($header)) {
            $t->includeInlineTranslation(WarningTestSP::CUSTOM_HEADER_KEY, $header);
            $t->data[WarningTestSP::CUSTOM_HEADER_ENABLED] = true;
        }
        $text = $testSpWarningConfig->getArray(WarningTestSP::TEST_SP_CONFIG_TEXT, []);
        if (! empty($text)) {
            $t->includeInlineTranslation(WarningTestSP::CUSTOM_TEXT_KEY, $text);
            $t->data[WarningTestSP::CUSTOM_TEXT_ENABLED] = true;
        }
    }
}

$t->show();
