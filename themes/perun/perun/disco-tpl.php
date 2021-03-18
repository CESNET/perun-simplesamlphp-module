<?php

use SimpleSAML\Module;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Module\perun\DiscoTemplate;
use SimpleSAML\Module\perun\model\WarningConfiguration;

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

$warningAttributes = $this->data[Disco::WARNING_ATTRIBUTES];
$this->includeInlineTranslation('{perun:disco:warning_title}', $warningAttributes->getTitle());
$this->includeInlineTranslation('{perun:disco:warning_text}', $warningAttributes->getText());
$authContextClassRef = null;
$idpEntityId = null;

$config = null;

$addInstitutionUrl = '';
$addInstitutionEmail = '';
$translate_module = '';
$wayfConfig = [];


//LOAD CONFIG
try {
    $config = Configuration::getConfig(Disco::CONFIG_FILE_NAME);
} catch (\Exception $ex) {
    Logger::error('perun:disco-tpl: missing or invalid module_perun.php config file');
    throw $ex;
}

if ($config !== null) {
    try {
        $wayfConfig = $config->getConfigItem(Disco::WAYF);
    } catch (\Exception $ex) {
        Logger::error("perun:disco-tpl: missing configuration for param '" . Disco::WAYF . "'");
        throw $ex;
    }
    $translate_module = $wayfConfig->getString(Disco::TRANSLATE_MODULE, 'disco');
    $addInstitution = $wayfConfig->getConfigItem(Disco::ADD_INSTITUTION);
    try {
        $addInstitutionUrl = $addInstitution->getString(Disco::ADD_INSTITUTION_URL);
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing or  parameter in module_perun.php file');
    }

    try {
        $addInstitutionEmail = $addInstitution->getString(Disco::ADD_INSTITUTION_EMAIL);
    } catch (\Exception $ex) {
        Logger::warning('perun:disco-tpl: missing or invalid addInstitution.email parameter in module_perun.php file');
    }
}

// IF WARNING ERROR IS ENABLED, DISPLAY IT AND STOP THE USER
if ($warningAttributes->isEnabled() && $warningAttributes->getType() === WarningConfiguration::WARNING_TYPE_ERROR) {
    $this->data['header'] = $this->t('{perun:disco:warning}');
    $this->includeAtTemplateBase('includes/header.php');
    echo Disco::showWarning($this, $warningAttributes);
    $this->includeAtTemplateBase('includes/footer.php');
    echo Disco::getScripts($wayfConfig[Disco::BOXED]) . PHP_EOL;
    exit;
}

// IF IS SET AUTHN CONTEXT CLASS REF, REDIRECT USER TO THE IDP
if (isset($this->data[Disco::AUTHN_CONTEXT_CLASS_REF])) {
    $authContextClassRef = $this->data[Disco::AUTHN_CONTEXT_CLASS_REF];
    if ($authContextClassRef !== null) {
        # Check authnContextClassRef and select IdP directly if the correct value is set
        foreach ($authContextClassRef as $value) {
            // VERIFY THE PREFIX IS CORRECT AND WE CAN PERFORM THE REDIRECT
            $acrStartSubstr = substr($value, 0, strlen(Disco::URN_CESNET_PROXYIDP_IDPENTITYID));
            if ($acrStartSubstr === Disco::URN_CESNET_PROXYIDP_IDPENTITYID) {
                $idpEntityId = substr($value, strlen(Disco::URN_CESNET_PROXYIDP_IDPENTITYID), strlen($value));
                Logger::info('Redirecting to ' . $idpEntityId);
                $url = $this->getContinueUrl($idpEntityId);
                HTTP::redirectTrustedURL($url);
                exit;
            }
        }
    }
}

// START DISPLAYING REGULAR WAYF (for users or add-institution)
// get header based on type of app (add inst. or wayf)
if ($this->isAddInstitutionApp()) {
    // Change header for Add institution app
    $this->data['header'] = $this->t('{perun:disco:add_institution}');
} else {
    $this->data['header'] = $this->t('{perun:disco:header}');
}

$this->includeAtTemplateBase('includes/header.php');

# IF WE HAVE A WARNING, DISPLAY IT TO THE USER
if ($warningAttributes->isEnabled()) {
    echo Disco::showWarning($this, $warningAttributes);
}
###
# LETS START DISPLAYING LOGIN OPTIONS
###
if ($this->isAddInstitutionApp()) {
    // add institution is suitable only if we display the eduGAIN
    echo '<div id="entries" class="add-institution-entries">';
    foreach ($wayfConfig[Disco::BLOCKS] as $blockConfig) {
        $type = $blockConfig[Disco::BLOCK_TYPE];
        echo '<div class="row login-option-category">' . PHP_EOL;
        if ($type === Disco::BLOCK_TYPE_INLINESEARCH) {
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
    $blocksCount = count($wayfConfig->getArray(Disco::BLOCKS));
    foreach ($wayfConfig->getArray(Disco:: BLOCKS) as $blockConfig) {
        $type = $blockConfig[Disco::BLOCK_TYPE];
        echo '<div class="row login-option-category">' . PHP_EOL;
        if (strtolower($type) === Disco::BLOCK_TYPE_INLINESEARCH) {
            echo Disco::showInlineSearch($this, $blockConfig, $addInstitutionEmail, $addInstitutionUrl) . PHP_EOL;
        } else if (strtolower($type) === Disco::BLOCK_TYPE_TAGGED) {
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
echo Disco::getScripts($wayfConfig->getBoolean(Disco::BOXED, false)) . PHP_EOL;