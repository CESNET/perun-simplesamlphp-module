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

const TRANSLATE_MODULE = 'disco.translate_module';

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
$translate_module = '';


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

    $translate_module = $config->getString(TRANSLATE_MODULE, 'ceitec'); //TODO: fixme
}

// IF WARNING ERROR IS ENABLED, DISPLAY IT AND STOP THE USER
if ($warningIsOn && $warningType === Disco::WARNING_TYPE_ERROR) {
    $this->data['header'] = $this->t('{perun:disco:warning}');
    $this->includeAtTemplateBase('includes/header.php');
    echo Disco::showWarning($warningType, $warningTitle, $warningText);
    $this->includeAtTemplateBase('includes/footer.php');
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
    $this->data['header'] = Disco::getTranslate($this, $translate_module, 'disco', 'header');
}

$this->includeAtTemplateBase('includes/header.php');

# IF WE HAVE A WARNING, DISPLAY IT TO THE USER
if ($warningIsOn) {
    echo Disco::showWarning($warningType, $warningTitle, $warningText);
}
###
# LETS START DISPLAYING LOGIN OPTIONS
###

# CHECK IF WE HAVE PREVIOUS SELECTION, IF YES, DISPLAY IT
# Last selection is not null => Firstly show last selection
if (!empty($this->getPreferredIdp())) {
    # ENTRY FOR PREVIOUS SELECTION
    echo '<p class="discoDescription-left" id="last-used-idp-desc">' . $this->t('{perun:disco:previous_selection}') . '</p>' ;
    echo '<div id="last-used-idp" class="metalist list-group">';
    echo Disco::showEntry($this, $this->getPreferredIdp(), true);
    echo '</div>';

    # OR TEXT
    echo Disco::getOr("last-used-idp-or");

    # BUTTON TO DISPLAY ALL OTHER ENTRIES
    echo '<a id="showEntries" class="metaentry btn btn-block btn-default btn-lg" href="#">' .
         $this->t('{perun:disco:sign_with_other_institution}') . '</a>' ;
}

echo '<div id="entries">';
echo '<p class="discoDescription-left">';
echo $this->t('{perun:disco:disco_select_institution}');
echo '</p>';
# LETS GO THROUGH THE BLOCKS IN CONFIG

//TODO: refactor and move to Disco.php
function showInlineSearch($textOn, $hintTranslateKey, $t, $isAddInstitutionApp, $addInstitutionEmail, $addInstitutionUrl, $allIdps): string
{
    $result = '';
    if ($textOn) {
        $result .= '<p class="login-option-category-hint">'. $t->t($hintTranslateKey) .'</p>' . PHP_EOL;
    }
    $result .= '<div class="inlinesearch">' . PHP_EOL;
    $result .= '    <form id="idpselectform" action="?" method="get">' . PHP_EOL;
    $result .= '        <input class="inlinesearchf form-control input-lg" type="text" value="" name="query" id="query"
                               autofocus oninput="$(\'#list\').show(); placeholder="'
        . $t->t('{perun:disco:type_name_institution}') . '"/>' . PHP_EOL;
    $result .= '    </form>';
    # ENTRIES
    $result .= '    <div class="metalist list-group" id="list" style="display: none">' . PHP_EOL;
    foreach ($allIdps as $idpentry) {
        $result .= Disco::showEntry($t, $idpentry, false) . PHP_EOL;
    }
    $result .= '    </div>' . PHP_EOL;
    # TOO MUCH ENTRIES BLOCK
    $result .= '    <div id="warning-entries" class="alert alert-info entries-warning-block">' . PHP_EOL;
    $result .= '        ' . $t->t('{perun:disco:warning_entries_header}',
            [ '<COUNT_HTML>' => '<span id="results-cnt">0</span>' ]) . '</h4>' . PHP_EOL;
    $result .= '        <div class="col">' . PHP_EOL;
    $result .= '            <button class="btn btn-block btn-info" id="warning-entries-btn-force-show">';
    $result .= $t->t('{perun:disco:warning_entries_btn}') . '</button>' . PHP_EOL;
    $result .= '        </div>' . PHP_EOL;
    $result .= '    </div>' . PHP_EOL;
    # NO ENTRIES BLOCK
    $result .= '    <div id="no-entries" class="no-idp-found alert alert-info entries-warning-block">' . PHP_EOL;
    if ($isAddInstitutionApp) {
        $result .= '        ' . $t->t('{perun:disco:find_institution_contact}') . ' <a href="mailto:'
            . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
            $addInstitutionEmail . '</a>' . PHP_EOL;
    } else {
        $result .= '       ' . $t->t('{perun:disco:find_institution_extended}') . '<br/>' . PHP_EOL;
        $result .= '       ' . $t->t('{perun:disco:find_institution_extended}') . '<a class="btn btn-block btn-secondary" href="' . $addInstitutionUrl . '">' .
            $t->t('{perun:disco:add_institution}') . '</a>' . PHP_EOL;
    }
    $result .= '    </div>' . PHP_EOL;
    $result .= '</div>';

    return $result;
}

//TODO constants
$wayfConfig = $config->getArray('wayf');
foreach ($wayfConfig['blocks'] as $block) {
    $type = $block['type'];
    $textOn = $block['textOn'];
    $hintTextKey = $wayfConfig['text_key'];
    echo '<div class="login-option-category">' . PHP_EOL;
    if ($type === 'inlinesearch') {
        //TODO: fix JS scripts
        echo showInlineSearch($textOn, $hintTextKey, $this, $this->isAddInstitutionApp(), $addInstitutionEmail, $addInstitutionUrl, $this->getAllIdps());
    } else if ($type === 'tagged') {
        //TODO: implement this block and possibly others
        echo '';
    }

}
$this->includeAtTemplateBase('includes/footer.php');
die; //TODO delete this line and following


//   Show all entries
echo '<div class="metalist list-group" id="list" style="display: none">';
foreach ($this->getAllIdps() as $idpentry) {
    echo Disco::showEntry($this, $idpentry, false);
}
echo '</div>';

//    Show warning about more
echo '<div id="warning-entries" class="alert alert-secondary">';
echo '    <h4>' . $this->t('{perun:disco:warning_entries_header}', [ '<COUNT_HTML>' => '<span id="results-cnt">0</span>' ]) . '</h4>';
//    echo '    <h4>' . $this->t('{perun:disco:warning_entries_header_part1}') . '(<span id="results-cnt">0</span>) ' . $this->t('{perun:disco:warning_entries_header_part2}') . '</h4>';
echo '    <br/>';
?>
    <div class="col">
        <button class="btn btn-block btn-secondary"
                style="padding: 5px;"
                onClick="console.log('forceshow');
                         forceShow=true;
                         $('#query').trigger('keyup');
                         showWarningTooMuchEntries(false);">
                    <?php echo $this->t('{perun:disco:warning_entries_btn}');?>
        </button>
    </div>
</div>

<!--    Show warning abuut none entries -->
<div id="no-entries" class="no-idp-found alert alert-secondary">
    <?php
        if ($this->isAddInstitutionApp()) {
            echo $this->t('{perun:disco:find_institution_contact}') . ' ' .
                '<a href="mailto:' . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
                $addInstitutionEmail .
                '</a>';
        } else {
            echo '<h4>' . $this->t('{perun:disco:find_institution_extended}') . '</h4>';
            echo '<div class="col">';
            echo '<a class="btn btn-block btn-secondary" href="' . $addInstitutionUrl . '">' .
                $this->t('{perun:disco:add_institution}') .
                '</a>';
            echo '</div>';
        }
    ?>
</div>
<?php

//    Show tagged IdPs if not on addInstitutionApp
if (!$this->isAddInstitutionApp()) {
    echo Disco::showAllTaggedIdPs($this, $translate_module);
}

echo '<br>';
echo '<br>';

if (!empty($this->getPreferredIdp())) {
    echo '</div>';
}

echo '</div>';

$this->includeAtTemplateBase('includes/footer.php');
