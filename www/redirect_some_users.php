<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;
use SimpleSAML\Locale\Language;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:redirectSomeUsers');

$config = Configuration::getInstance();

$language = (new Language($config))->getLanguage();

$t = new Template($config, 'perun:redirect_some_users-tpl.php');
$t->data['allowedContinue'] = $_REQUEST['allowedContinue'];
$t->data['redirectURL'] = $_REQUEST['redirectURL'];
$t->data['language'] = $language;

if (isset($_REQUEST['pageText'][$language])) {
    $t->data['pageText'] = $_REQUEST['pageText'][$language];
} else {
    $t->data['pageText'] = $_REQUEST['pageText']['en'];
}



$t->show();
