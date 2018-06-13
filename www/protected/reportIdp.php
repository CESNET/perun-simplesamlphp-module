<?php

/**
 * endpoint which report (send email) given idp defined by idpEntityId param.
 * Also consumes other parameters such as 'isOk'
 * After successfull report it redirects user back to URL defined in 'redirectUri'
 *
 * Request has to be POST
 *
 * example URL (params are POST):
 * https://login.example.org/proxy/module.php/perun/protected/reportIdp.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo 'ERROR request has to be POST';
	die;
}
if (!isset($_POST['idpEntityId'])) {
	http_response_code(400);
	echo 'ERROR parametr "idpEntityId" is missing';
	die;
}
if (!isset($_POST['isOk'])) {
	http_response_code(400);
	echo 'ERROR parametr "isOk" is missing';
	die;
}
if (!isset($_POST['redirectUri'])) {
	http_response_code(400);
	echo 'ERROR parametr "redirectUri" is missing';
	die;
}


$config = SimpleSAML_Configuration::getInstance();
$auth_config = SimpleSAML_Configuration::getConfig("authsources.php");
//name of institution that provides the list of IDPS (elixir, cesnet etc...)
$name = array_values($auth_config->getArray("default-sp")['name'])[0];

$message = <<<EOD

Dear administrator of Identity Provider,

this email has been sent to you as this IdP has been requested to be added to the list of IdPs for {$name}.
Below you can find the result of configuration inspection. If you wish to add the IdP and the result 
shows incorrect configuration, please change it.

User message: {$_POST['body']}

IdP name displayed to user: {$_POST['idpDisplayName']}
IdP entityId: {$_POST['idpEntityId']}

Released all attributes: {$_POST['isOk']}
 - user's identifier: {$_POST['hasUid']}
 - user's affiliation: {$_POST['hasAffiliation']}
 - user's organization: {$_POST['hasOrganization']}

Time of the check: {$_POST['time']}

Result were saved on machine: {$_POST['resultInFile']}
IdP were whitelisted automatically: {$_POST['resultOnProxy']}

EOD;

$toAddress = $config->getString('technicalcontact_email', 'N/A');
if ($toAddress !== 'N/A') {
	$email = new SimpleSAML_XHTML_EMail($toAddress, 'Report: '.$_POST['title'], $_POST['from']);
	$email->setBody($message);
	$email->send();
}

echo '<h1>Unssuported redirection</h1>';

echo "Go back to <a href='{$_POST['redirectUri']}'>{$_POST['redirectUri']}</a>";

// redirect the user back
\SimpleSAML\Utils\HTTP::redirectTrustedURL($_POST['redirectUri'], array('mailSended' => true));





?>
