<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\XHTML\EMail;

/**
 * endpoint which report (send email) given idp defined by idpEntityId param. Also consumes other parameters such as
 * 'isOk' After successfull report it redirects user back to URL defined in 'redirectUri'
 *
 * Request has to be POST
 *
 * example URL (params are POST): https://login.example.org/proxy/module.php/perun/protected/reportIdp.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'ERROR request has to be POST';
    die;
}

if (! isset($_POST['idpEntityId'])) {
    http_response_code(400);
    echo 'ERROR parametr "idpEntityId" is missing';
    die;
}

if (! isset($_POST['isOk'])) {
    http_response_code(400);
    echo 'ERROR parametr "isOk" is missing';
    die;
}

if (! isset($_POST['redirectUri'])) {
    http_response_code(400);
    echo 'ERROR parametr "redirectUri" is missing';
    die;
}

$config = Configuration::getInstance();

$message = <<<CODE_SAMPLE

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

CODE_SAMPLE;

$toAddress = $config->getString('technicalcontact_email', 'N/A');
if ($toAddress !== 'N/A') {
    $email = new EMail($toAddress, 'Report: ' . $_POST['title'], $_POST['from']);
    $email->setBody($message);
    $email->send();
}

echo '<h1>' . $this->t('{perun:perun:unsupported_redirection}') . '</h1>';

echo $this->t('{perun:perun:go_back}') . "<a href='{$_POST['redirectUri']}'>{$_POST['redirectUri']}</a>";

// redirect the user back
HTTP::redirectTrustedURL($_POST['redirectUri'], [
    'mailSended' => true,
]);
