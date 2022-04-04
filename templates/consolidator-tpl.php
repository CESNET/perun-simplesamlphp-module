<?php

declare(strict_types=1);

/**
 * Template of page, which showing status of used components.
 */

use SimpleSAML\Configuration;

$config = Configuration::getInstance();
$this->data['header'] = 'ELIXIR AAI account repair for Life Science Login';

$this->includeAtTemplateBase('includes/header.php');

$idpEntityId = $_GET['idpEntityId'];

//Check if value is allowed
if (!filter_var($idpEntityId, FILTER_VALIDATE_URL)) {
    throw new Exception('ERROR');
}

$consolidatorUrl = 'https://perun.elixir-czech.cz/lsaai-ic/ic/?targetIdP=' . urlencode($idpEntityId);
$acrParam = 'urn:cesnet:proxyidp:lsidpentityid:' . $idpEntityId;
//$acrParam = 'urn:cesnet:proxyidp:idpentityid:' . $idpEntityId;
$url = 'https://perun.elixir-czech.cz/Consolidator.sso/Login?target=' . urlencode(
    $consolidatorUrl
) . '&authnContextClassRef=' . urlencode($acrParam);

echo '
<div>
    <p class="text-justify">You have been provided a special link, which will help us to prepare your account to be usable in the Life Science Login, to which your account will be migrated. You will now be asked to log in twice with your home organization. After that, your account should be ready to be used in Life Science Login.</p>
    <p class="text-justify">If you do not wish to use your home organization for login, let us know using the address below. In case of running into any issues, do not hesitate to contact us at <a href="mailto:aai-contact@elixir-europe.org">aai-contact@elixir-europe.org</a>.</p>
    <p>Proceed by clicking the button below.</p>
    <a href="' . $url . '">
        <div class="btn btn-primary btn-block">Continue</div>
    </a>
</div>
';

$this->includeAtTemplateBase('includes/footer.php');
