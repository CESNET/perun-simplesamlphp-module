<?php

use SimpleSAML\Module;
use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Module\perun\model\WarningConfiguration;
use SimpleSAML\XHTML\Template;

/**
 * Template for warn user
 *
 * Allow type hinting in IDE
 * @var Template $this
 */


$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

$warningAttributes = $this->data[Disco::WARNING_ATTRIBUTES];
$this->includeInlineTranslation('{perun:disco:warning_title}', $warningAttributes->getTitle());
$this->includeInlineTranslation('{perun:disco:warning_text}', $warningAttributes->getText());

echo Disco::showWarning($this, $warningAttributes);

if ($warningAttributes->getType() !== WarningConfiguration::WARNING_TYPE_ERROR) {
    ?>
    <form method="post" action="<?php echo Module::getModuleURL('perun/warning_continue.php'); ?>">
        <input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>">
        <div class="form-group">
            <input type="submit" value="<?php echo $this->t('{perun:perun:continue}') ?>"
                   class="btn btn-lg btn-primary btn-block">
        </div>
    </form>

    <?php
}

$this->includeAtTemplateBase('includes/footer.php');


