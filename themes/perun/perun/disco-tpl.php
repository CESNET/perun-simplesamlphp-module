<?php

declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Module\perun\DiscoTemplate;
use SimpleSAML\Module\perun\model\WarningConfiguration;

/**
 * This is simple example of template for perun Discovery service
 *
 * Allow type hinting in IDE
 *
 * @var DiscoTemplate $this
 */


$this->data['jquery'] = [
    'core' => true,
    'ui' => true,
    'css' => true,
];

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('discopower/assets/css/disco.css') . '" />';

$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/disco.css') . '" />';

$wayfConfig = $this->data[Disco::WAYF];

$translateModule = $wayfConfig->getString(Disco::TRANSLATE_MODULE, 'disco');
$addInstitutionConfig = $wayfConfig->getConfigItem(Disco::ADD_INSTITUTION, null);

$warningAttributes = $this->data[Disco::WARNING_ATTRIBUTES];
if ($warningAttributes !== null) {
    $this->includeInlineTranslation('{perun:disco:warning_title}', $warningAttributes->getTitle());
    $this->includeInlineTranslation('{perun:disco:warning_text}', $warningAttributes->getText());
    // IF WARNING ERROR IS ENABLED, DISPLAY IT AND STOP THE USER
    if ($warningAttributes->isEnabled() && $warningAttributes->getType() === WarningConfiguration::WARNING_TYPE_ERROR) {
        $this->data['header'] = $this->t('{perun:disco:warning}');
        $this->includeAtTemplateBase('includes/header.php');
        echo Disco::showWarning($this, $warningAttributes);
        $this->includeAtTemplateBase('includes/footer.php');
        echo Disco::getScripts($wayfConfig->getBoolean(Disco::BOXED, false)) . PHP_EOL;
        exit;
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
if ($warningAttributes !== null && $warningAttributes->isEnabled()) {
    echo Disco::showWarning($this, $warningAttributes);
}
###
# LETS START DISPLAYING LOGIN OPTIONS
###
if ($this->isAddInstitutionApp()) {
    // add institution is suitable only if we display the eduGAIN
    echo '<div id="entries" class="add-institution-entries">';
    $blocksCount = count($wayfConfig->getArray(Disco::IDP_BLOCKS));
    $blocksConfig = $wayfConfig->getConfigItem(Disco::IDP_BLOCKS);
    $blockKeys = $blocksConfig->getOptions();
    foreach ($blockKeys as $key) {
        $blockConfig = $blocksConfig->getConfigItem($key);
        $type = $blockConfig->getString(Disco::IDP_BLOCK_TYPE);
        echo '<div class="row login-option-category">' . PHP_EOL;
        if ($type === Disco::IDP_BLOCK_TYPE_INLINESEARCH) {
            echo Disco::showInlineSearch($this, $blockConfig, $addInstitutionConfig) . PHP_EOL;
        }
        echo '</div>' . PHP_EOL;
    }
} else {
    # CHECK IF WE HAVE PREVIOUS SELECTION, IF YES, DISPLAY IT
    # Last selection is not null => Firstly show last selection
    if (! empty($this->getPreferredIdp())) {
        # ENTRY FOR PREVIOUS SELECTION
        echo '<div id="last-used-idp-wrap">' . PHP_EOL;
        echo '    <p class="discoDescription-left" id="last-used-idp-desc">'
            . $this->t('{perun:disco:previous_selection}') . '</p>' . PHP_EOL;
        echo '    <div id="last-used-idp" class="metalist list-group">' . PHP_EOL;
        echo Disco::showEntry($this, $this->getPreferredIdp(), true) . PHP_EOL;
        echo '    </div>' . PHP_EOL;

        # OR TEXT
        echo Disco::getOr('last-used-idp-or') . PHP_EOL;

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
    $blocksCount = count($wayfConfig->getArray(Disco::IDP_BLOCKS));
    $blocksConfig = $wayfConfig->getConfigItem(Disco::IDP_BLOCKS);
    $blockKeys = $blocksConfig->getOptions();
    foreach ($blockKeys as $key) {
        $blockConfig = $blocksConfig->getConfigItem($key);
        $type = $blockConfig->getString(Disco::IDP_BLOCK_TYPE);
        echo '<div class="row login-option-category">' . PHP_EOL;
        if (strtolower($type) === Disco::IDP_BLOCK_TYPE_INLINESEARCH) {
            echo Disco::showInlineSearch($this, $blockConfig, $addInstitutionConfig) . PHP_EOL;
        } elseif (strtolower($type) === Disco::IDP_BLOCK_TYPE_TAGGED) {
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
