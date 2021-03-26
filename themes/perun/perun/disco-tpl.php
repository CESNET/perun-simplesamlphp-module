<?php

use SimpleSAML\Module;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Module\perun\DiscoTemplate;

/**
 * This is simple example of template for perun Discovery service
 *
 * Allow type hinting in IDE
 * @var DiscoTemplate $this
 */

$this->data['jquery'] = ['core' => true, 'ui' => true, 'css' => true];

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('discopower/assets/css/disco.css') . '" />';

$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/disco.css') . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' .
    Module::getModuleUrl('perun/res/js/jquery.livesearch.js') . '"></script>';

$this->data['head'] .= '<script type="text/javascript" src="' .
    Module::getModuleUrl('discopower/assets/js/suggest.js') . '"></script>';

$this->data['head'] .= Disco::searchScript();
$this->data['head'] .= Disco::showEntriesScript();
$this->data['head'] .= Disco::setFocus();

const CONFIG_FILE_NAME = 'module_perun.php';

const ADD_INSTITUTION_URL = 'disco.addInstitution.URL';
const ADD_INSTITUTION_EMAIL = 'disco.addInstitution.email';

const URN_CESNET_PROXYIDP_IDPENTITYID = 'urn:cesnet:proxyidp:idpentityid:';

$warningIsOn = $this->data['warningIsOn'];
$warningType = $this->data['warningType'];
$warningTitle = $this->data['warningTitle'];
$warningText = $this->data['warningText'];

$authContextClassRef = null;
$idpEntityId = null;

$config = null;

$addInstitutionUrl = '';
$addInstitutionEmail = '';

try {
    $config = Configuration::getConfig(CONFIG_FILE_NAME);
} catch (\Exception $ex) {
    Logger::warning('perun:disco-tpl: missing or invalid module_perun.php config file');
}

if ($config !== null) {
    try {
        $addInstitutionUrl = $config->getString(ADD_INSTITUTION_URL);
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing or invalid addInstitution.URL parameter in module_perun.php file');
    }
}

if ($config !== null) {
    try {
        $addInstitutionEmail = $config->getString(ADD_INSTITUTION_EMAIL);
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing or invalid addInstitution.email parameter in module_perun.php file');
    }
}

if ($warningIsOn && $warningType === Disco::WARNING_TYPE_ERROR) {
    $this->data['header'] = $this->t('{perun:disco:warning}');
}

if (isset($this->data['AuthnContextClassRef'])) {
    $authContextClassRef = $this->data['AuthnContextClassRef'];
}

# Do not show social IdPs when using addInstitutionApp, show just header Add Institution
if ($this->isAddInstitutionApp()) {
    // Translate title in header
    $this->data['header'] = $this->t('{perun:disco:add_institution}');
}


    $this->includeAtTemplateBase('includes/header.php');
    if ($authContextClassRef !== null) {
        # Check authnContextClassRef and select IdP directly if the correct value is set
        foreach ($authContextClassRef as $value) {
            if (substr($value, 0, strlen(URN_CESNET_PROXYIDP_IDPENTITYID))
                === URN_CESNET_PROXYIDP_IDPENTITYID) {
                $idpEntityId = substr($value, strlen(URN_CESNET_PROXYIDP_IDPENTITYID), strlen($value));
                Logger::info('Redirecting to ' . $idpEntityId);
                $url = $this->getContinueUrl($idpEntityId);
                HTTP::redirectTrustedURL($url);
                exit;
            }
        }
    }

    if ($warningIsOn) {
        echo Disco::showWarning($warningType, $warningTitle, $warningText);
    }

    if (!$warningIsOn && $warningType !== Disco::WARNING_TYPE_ERROR) {
        if (!empty($this->getPreferredIdp())) {
            echo '<p class="descriptionp" id="last-used-idp-desc">' . $this->t('{perun:disco:previous_selection}') . '</p>';
            echo '<div id="last-used-idp" class="metalist list-group">';
            echo Disco::showEntry($this, $this->getPreferredIdp(), true);
            echo '</div>';

            echo Disco::getOr("last-used-idp-or");

            echo '<a id="showEntries" class="metaentry btn btn-block btn-default btn-lg" href="#">' .
                 $this->t('{perun:disco:sign_with_other_institution}') . '</a>' ;
            echo '<div id="entries" style="display: none">';
        }

        echo '<p class="descriptionp">';
        echo $this->t('{perun:disco:institutional_account}');
        echo '</p>';
    }






if (!$warningIsOn && $warningType !== Disco::WARNING_TYPE_ERROR) {
    echo '<div class="inlinesearch">';
    echo '	<form id="idpselectform" action="?" method="get">
			<input class="inlinesearchf form-control input-lg" placeholder="' .
        $this->t('{perun:disco:type_name_institution}') . '"
			type="text" value="" name="query" id="query" autofocus ' .
        'oninput="document.getElementById(\'list\').style.display=\'block\';"/>
		</form>';
    echo '</div>';


    echo '<div class="metalist list-group" id="list" style="display: none">';
    foreach ($this->getIdps() as $idpentry) {
        echo Disco::showEntry($this, $idpentry, false);
    }
    echo '</div>';

    ?>

    <div id="warning-entries" class="alert alert-info">
				  <h4>Too many (<span id="results-cnt">0</span>) entries satisfy your search. Try to specify your query a bit more.</h4>
				  <br/>
				  <div class="col">
					<button class="btn btn-block btn-info"
							style="padding: 5px;"
							onClick="console.log('forceshow');
									 forceShow=true;
									 $('#query').trigger('keyup');
									 showWarningTooMuchEntries(false);">
                                Display results anyway
                    </button>
				   </div>
				</div>
    <div id="no-entries" class="no-idp-found alert alert-info">
        <?php
            if ($this->isAddInstitutionApp()) {
                echo $this->t('{perun:disco:find_institution_contact}') . ' ' .
                    '<a href="mailto:' . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
                    $addInstitutionEmail .
                    '</a>';
            } else {
                echo $this->t('{perun:disco:find_institution_extended}') . ' ' .
                    '<a class="btn btn-primary" href="' . $addInstitutionUrl . '">' .
                    $this->t('{perun:disco:add_institution}') .
                    '</a>';
            }
        ?>
    </div>
    <?php
    echo Disco::getOr();
    echo Disco::showAllTaggedIdPs($this);

    echo '<br>';
    echo '<br>';

    if (!empty($this->getPreferredIdp())) {
        echo '</div>';
    }

    echo '</div>';
}

$this->includeAtTemplateBase('includes/footer.php');
