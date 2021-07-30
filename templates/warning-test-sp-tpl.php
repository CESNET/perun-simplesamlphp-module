<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

/**
 * Template for warn user that he/she is accessing test SP
 *
 * Allow type hinting in IDE
 *
 * @var Template $this
 */

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/warning_test_sp.css') . '" />';

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');
$customHeaderEnabled = isset($this->data[Module\perun\Auth\Process\WarningTestSP::CUSTOM_HEADER_ENABLED])
    && $this->data[Module\perun\Auth\Process\WarningTestSP::CUSTOM_HEADER_ENABLED];
$customTextEnabled = isset($this->data[Module\perun\Auth\Process\WarningTestSP::CUSTOM_TEXT_ENABLED])
    && $this->data[Module\perun\Auth\Process\WarningTestSP::CUSTOM_TEXT_ENABLED];
?>

    <form method="post" action="<?php echo Module::getModuleURL('perun/warning_test_sp_continue.php'); ?>">

        <input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>">
        <h3><?php echo $customTextEnabled ?
                $this->t(Module\perun\Auth\Process\WarningTestSP::CUSTOM_HEADER_KEY) :
                $this->t('{perun:perun:warning-test-sp-tpl_text}')
        ?>
        </h3>
        <?php
        if ($customTextEnabled) {
            echo '<div>' . $this->t(Module\perun\Auth\Process\WarningTestSP::CUSTOM_TEXT_KEY) . '</div>' . PHP_EOL;
        }
        ?>
        <br/>
        <div class="form-group">
            <input type="submit" value="<?php echo $this->t('{perun:perun:continue}') ?>"
                   class="btn btn-lg btn-primary btn-block">
        </div>
    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
