<?php

use SimpleSAML\Module;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Module\perun\DiscoTemplate;

/**
 * This is simple example of template for perun Discovery service
 *
 * Allow type hinting in IDE
 * @var DiscoTemplate $this
 */

$this->data['jquery'] = array('core' => true, 'ui' => true, 'css' => true);

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('discopower/assets/css/disco.css') . '" />';

$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/disco.css') . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' .
    Module::getModuleUrl('perun/res/js/jquery.livesearch.js') . '"></script>';

$this->data['head'] .= '<script type="text/javascript" src="' .
    Module::getModuleUrl('discopower/assets/js/suggest.js') . '"></script>';

$this->data['head'] .= searchScript();

const WARNING_CONFIG_FILE_NAME = 'config-warning.php';
const WARNING_IS_ON = 'isOn';
const WARNING_USER_CAN_CONTINUE = 'userCanContinue';
const WARNING_TITLE = 'title';
const WARNING_TEXT = 'text';

const PERUN_CONFIG_FILE_NAME = 'module_perun.php';
const ADD_INSTITUTION_URL = 'disco.addInstitution.URL';
const ADD_INSTITUTION_EMAIL = 'disco.addInstitution.email';

const URN_CESNET_PROXYIDP_IDPENTITYID = "urn:cesnet:proxyidp:idpentityid:";

$authContextClassRef = null;
$idpEntityId = null;

$warningIsOn = false;
$warningUserCanContinue = null;
$warningTitle = null;
$warningText = null;
$configWarning = null;

$configPerun = null;
$addInstitutionUrl = '';
$addInstitutionEmail = '';

try {
    $configWarning = Configuration::getConfig(WARNING_CONFIG_FILE_NAME);
} catch (\Exception $ex) {
    Logger::warning("perun:disco-tpl: missing or invalid config-warning file");
}

try {
    $configPerun = Configuration::getConfig(PERUN_CONFIG_FILE_NAME);
} catch (\Exception $ex) {
    Logger::warning("perun:disco-tpl: invalid module_perun.php file");
}

if (!is_null($configPerun)) {
    try {
        $addInstitutionUrl = $configPerun->getString(ADD_INSTITUTION_URL);
    } catch (\Exception $ex) {
        Logger::warning("perun:disco-tpl: missing or invalid addInstitution.URL parameter in module_perun.php file");
    }
}

if (!is_null($configPerun)) {
    try {
        $addInstitutionEmail = $configPerun->getString(ADD_INSTITUTION_EMAIL);
    } catch (\Exception $ex) {
        Logger::warning("perun:disco-tpl: missing or invalid addInstitution.email parameter in module_perun.php file");
    }
}

if ($configWarning != null) {
    try {
        $warningIsOn = $configWarning->getBoolean(WARNING_IS_ON);
    } catch (\Exception $ex) {
        Logger::warning("perun:disco-tpl: missing or invalid isOn parameter in config-warning file");
        $warningIsOn = false;
    }
}

if ($warningIsOn) {
    try {
        $warningUserCanContinue = $configWarning->getBoolean(WARNING_USER_CAN_CONTINUE);
    } catch (\Exception $ex) {
        Logger::warning(
            "perun:disco-tpl: missing or invalid userCanContinue parameter in config-warning file"
        );
        $warningUserCanContinue = true;
    }
    try {
        $warningTitle = $configWarning->getString(WARNING_TITLE);
        $warningText = $configWarning->getString(WARNING_TEXT);
        if (empty($warningTitle) || empty($warningText)) {
            throw new Exception();
        }
    } catch (Exception $ex) {
        Logger::warning("perun:disco-tpl: missing or invalid title or text in config-warning file");
        $warningIsOn = false;
    }
}

if ($warningIsOn && !$warningUserCanContinue) {
    $this->data['header'] = $this->t('{perun:disco:warning}');
}

if (isset($this->data['AuthnContextClassRef'])) {
    $authContextClassRef = $this->data['AuthnContextClassRef'];
}

# Do not show social IdPs when using addInstitutionApp, show just header Add Institution
if ($this->isAddInstitutionApp()) {
    // Translate title in header
    $this->data['header'] = $this->t('{perun:disco:add_institution}');
    $this->includeAtTemplateBase('includes/header.php');
} else {
    $this->includeAtTemplateBase('includes/header.php');

    if ($authContextClassRef != null) {
        foreach ($authContextClassRef as $value) {
            if (substr($value, 0, strlen(URN_CESNET_PROXYIDP_IDPENTITYID))
                === URN_CESNET_PROXYIDP_IDPENTITYID) {
                $idpEntityId = substr($value, strlen(URN_CESNET_PROXYIDP_IDPENTITYID), strlen($value));
                Logger::info("Redirecting to " . $idpEntityId);
                $url = $this->getContinueUrl($idpEntityId);
                HTTP::redirectTrustedURL($url);
                exit;
            }
        }
    }

    if ($warningIsOn) {
        if ($warningUserCanContinue) {
            echo '<div class="alert alert-warning">';
        } else {
            echo '<div class="alert alert-danger">';
        }
        echo '<h4> <strong>' . $warningTitle . '</strong> </h4>';
        echo $warningText;
        echo '</div>';
    }

    if (!$warningIsOn || $warningUserCanContinue) {
        if (!empty($this->getPreferredIdp())) {
            echo '<p class="descriptionp">' . $this->t('{perun:disco:previous_selection}') . '</p>';
            echo '<div class="metalist list-group">';
            echo showEntry($this, $this->getPreferredIdp(), true);
            echo '</div>';


            echo getOr();
        }

        echo '<div class="row">';
        foreach ($this->getIdps('preferred') as $idpentry) {
            echo '<div class="col-md-4">';
            echo '<div class="metalist list-group">';
            echo showEntry($this, $idpentry, false);
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="row">';
        foreach ($this->getIdps('social') as $idpentry) {
            echo '<div class="col-md-4">';
            echo '<div class="metalist list-group">';
            echo showEntry($this, $idpentry, false);
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo getOr();

        echo '<p class="descriptionp">';
        echo $this->t('{perun:disco:institutional_account}');
        echo '</p>';
    }
}

if (!$warningIsOn || $warningUserCanContinue) {
    echo '<div class="inlinesearch">';
    echo '	<form id="idpselectform" action="?" method="get">
			<input class="inlinesearchf form-control input-lg" placeholder="' .
        $this->t('{perun:disco:type_name_institution}') . '"
			type="text" value="" name="query" id="query" autofocus ' .
        'oninput="document.getElementById(\'list\').style.display=\'block\';"/>
		</form>';
    echo '</div>';

    echo '<div class="metalist list-group" id="list">';
    foreach ($this->getIdps() as $idpentry) {
        echo showEntry($this, $idpentry, false);
    }
    echo '</div>';

    echo '<br>';
    echo '<br>';

    echo '<div class="no-idp-found alert alert-info">';
    if ($this->isAddInstitutionApp()) {
        echo $this->t('{perun:disco:find_institution_contact}') .
            '<a href="mailto:' . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
            $addInstitutionEmail .
            '</a>';
    } else {
        echo $this->t('{perun:disco:find_institution_extended}') .
            '<a class="btn btn-primary" href="' . $addInstitutionUrl . '">' .
            $this->t('{perun:disco:add_institution}') .
            '</a>';
    }
    echo '</div>';
}

$this->includeAtTemplateBase('includes/footer.php');

function searchScript()
{

    $script = '<script type="text/javascript">

	$(document).ready(function() { 
		$("#query").liveUpdate("#list");
	});
	
	</script>';

    return $script;
}

/**
 * @param DiscoTemplate $t
 * @param array $metadata
 * @param bool $favourite
 * @return string html
 */
function showEntry($t, $metadata, $favourite = false)
{

    if (isset($metadata['tags']) && in_array('social', $metadata['tags'])) {
        return showEntrySocial($t, $metadata);
    }

    $extra = ($favourite ? ' favourite' : '');
    $html = '<a class="metaentry' . $extra . ' list-group-item" href="' .
        $t->getContinueUrl($metadata['entityid']) . '">';

    $html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';

    $html .= showIcon($metadata);

    $html .= '</a>';

    return $html;
}

/**
 * @param DiscoTemplate $t
 * @param array $metadata
 * @return string html
 */
function showEntrySocial($t, $metadata)
{

    $bck = 'white';
    if (!empty($metadata['color'])) {
        $bck = $metadata['color'];
    }

    $html = '<a class="btn btn-block social" href="' . $t->getContinueUrl($metadata['entityid']) .
        '" style="background: ' . $bck . '">';

    $html .= '<img src="' . $metadata['icon'] . '">';

    $html .= '<strong>Sign in with ' . $t->getTranslatedEntityName($metadata) . '</strong>';

    $html .= '</a>';

    return $html;
}


function showIcon($metadata)
{
    $html = '';
    // Logos are turned off, because they are loaded via URL from IdP. Some IdPs have bad configuration,
    // so it breaks the WAYF.

    /*if (isset($metadata['UIInfo']['Logo'][0]['url'])) {
        $html .= '<img src="' .
                    htmlspecialchars(\SimpleSAML\Utils\HTTP::resolveURL($metadata['UIInfo']['Logo'][0]['url'])) .
                    '" class="idp-logo">';
    } else if (isset($metadata['icon'])) {
        $html .= '<img src="' . htmlspecialchars(\SimpleSAML\Utils\HTTP::resolveURL($metadata['icon'])) .
                    '" class="idp-logo">';
    }*/

    return $html;
}

function getOr()
{
    $or = '<div class="hrline">';
    $or .= '	<span>or</span>';
    $or .= '</div>';
    return $or;
}
