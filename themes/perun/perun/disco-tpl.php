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

const CONFIG_FILE_NAME = 'module_perun.php';

const ADD_INSTITUTION_URL = 'disco.addInstitution.URL';
const ADD_INSTITUTION_EMAIL = 'disco.addInstitution.email';

const TRANSLATE_MODULE = 'disco.translate_module';

const URN_CESNET_PROXYIDP_IDPENTITYID = 'urn:cesnet:proxyidp:idpentityid:';


$this->data['jquery'] = ['core' => true, 'ui' => true, 'css' => true];

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('discopower/assets/css/disco.css') . '" />';

$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/disco.css') . '" />';

$warningIsOn = $this->data['warningIsOn'];
$warningType = $this->data['warningType'];
$warningTitle = $this->data['warningTitle'];
$warningText = $this->data['warningText'];

$authContextClassRef = null;
$idpEntityId = null;

$config = null;

$addInstitutionUrl = '';
$addInstitutionEmail = '';
$translate_module = '';
$wayfConfig = [];


//LOAD CONFIG
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

    try {
        $addInstitutionEmail = $config->getString(ADD_INSTITUTION_EMAIL);
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing or invalid addInstitution.email parameter in module_perun.php file');
    }

    $translate_module = $config->getString(TRANSLATE_MODULE, 'disco');
    try {
        //TOOD: handle loading of config when we decide the final versiongi
        $wayfConfig = $config->getArray('wayf');
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing configuration for "WAYF"');
        throw $ex;
    }
}

// IF WARNING ERROR IS ENABLED, DISPLAY IT AND STOP THE USER
if ($warningIsOn && $warningType === Disco::WARNING_TYPE_ERROR) {
    $this->data['header'] = $this->t('{perun:disco:warning}');
    $this->includeAtTemplateBase('includes/header.php');
    echo Disco::showWarning($warningType, $warningTitle, $warningText);
    $this->includeAtTemplateBase('includes/footer.php');
    echo Disco::getScripts($wayfConfig['boxed']) . PHP_EOL;
    exit;
}

// IF IS SET AUTHN CONTEXT CLASS REF, REDIRECT USER TO THE IDP
if (isset($this->data['AuthnContextClassRef'])) {
    $authContextClassRef = $this->data['AuthnContextClassRef'];
    if ($authContextClassRef !== null) {
        # Check authnContextClassRef and select IdP directly if the correct value is set
        foreach ($authContextClassRef as $value) {
            // VERIFY THE PREFIX IS CORRECT AND WE CAN PERFORM THE REDIRECT
            $acrStartSubstr = substr($value, 0, strlen(URN_CESNET_PROXYIDP_IDPENTITYID));
            if ($acrStartSubstr === URN_CESNET_PROXYIDP_IDPENTITYID) {
                $idpEntityId = substr($value, strlen(URN_CESNET_PROXYIDP_IDPENTITYID), strlen($value));
                Logger::info('Redirecting to ' . $idpEntityId);
                $url = $this->getContinueUrl($idpEntityId);
                HTTP::redirectTrustedURL($url);
                exit;
            }
        }
    }
}

// START DISPLAYING REGULAR WAYF (for users or add-institution)
// get header based on type
if ($this->isAddInstitutionApp()) {
    // Change header for Add institution app
    $this->data['header'] = $this->t('{perun:disco:add_institution}');
} else {
    $this->data['header'] = $this->t('{perun:disco:header}');
}

$this->includeAtTemplateBase('includes/header.php');

# IF WE HAVE A WARNING, DISPLAY IT TO THE USER
if ($warningIsOn) {
    echo Disco::showWarning($warningType, $warningTitle, $warningText);
}
###
# LETS START DISPLAYING LOGIN OPTIONS
###
if ($this->isAddInstitutionApp()) {
    // add institution is suitable only if we display the eduGAIN
    echo '<div id="entries" class="add-institution-entries">';
    foreach ($wayfConfig['blocks'] as $blockConfig) {
        $type = $blockConfig['type'];
        echo '<div class="row login-option-category">' . PHP_EOL;
        if ($type === 'inlinesearch') {
            echo Disco::showInlineSearch($this, $blockConfig, $addInstitutionEmail, $addInstitutionUrl) . PHP_EOL;
        }
        echo '</div>' . PHP_EOL;
    }
} else {
    # CHECK IF WE HAVE PREVIOUS SELECTION, IF YES, DISPLAY IT
    # Last selection is not null => Firstly show last selection
    if (!empty($this->getPreferredIdp())) {
        # ENTRY FOR PREVIOUS SELECTION
        echo '<div id="last-used-idp-wrap">' . PHP_EOL;
        echo '    <p class="discoDescription-left" id="last-used-idp-desc">'
            . $this->t('{perun:disco:previous_selection}') . '</p>' . PHP_EOL;
        echo '    <div id="last-used-idp" class="metalist list-group">' . PHP_EOL;
        echo Disco::showEntry($this, $this->getPreferredIdp(), true) . PHP_EOL;
        echo '    </div>' . PHP_EOL;

        # OR TEXT
        echo Disco::getOr("last-used-idp-or") . PHP_EOL;

        # BUTTON TO DISPLAY ALL OTHER ENTRIES
        echo '    <div id="show-entries-wrap">' . PHP_EOL;
        echo '        <a id="showEntries" class="metaentry btn btn-block btn-default btn-lg" href="#">' .
            $this->t('{perun:disco:sign_with_other_institution}') . '</a>';
        echo '    </div>' . PHP_EOL;
        echo '</div>' . PHP_EOL;
    }

    // regular wayf contains all entries
    echo '<div id="entries">';
    $cnt = 1;
    $blocksCount = count($wayfConfig['blocks']);
    foreach ($wayfConfig['blocks'] as $blockConfig) {
        $type = $blockConfig['type'];
        echo '<div class="row login-option-category">' . PHP_EOL;
        if ($type === 'inlinesearch') {
            echo Disco::showInlineSearch($this, $blockConfig, $addInstitutionEmail, $addInstitutionUrl) . PHP_EOL;
        } else if ($type === 'tagged') {
            echo Disco::showTaggedIdPs($this, $blockConfig) . PHP_EOL;
        }
        if ($cnt++ < $blocksCount) {
            echo Disco::getOr();
        }
        echo '</div>' . PHP_EOL;
    }
}
echo '</div>' . PHP_EOL;

$this->includeAtTemplateBase('includes/footer.php');
echo Disco::getScripts($wayfConfig['boxed']) . PHP_EOL;