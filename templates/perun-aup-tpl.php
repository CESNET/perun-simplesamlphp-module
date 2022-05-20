<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Module\perun\Auth\Process\PerunAup;

$this->data['header'] = $this->t('{perun:perun:aup_header}');
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/perun_aup.css') . '" />';
$this->includeAtTemplateBase('includes/header.php');

?>

<div class="row">
    <div>
        <p><?php echo $this->t('{perun:perun:aup_text}'); ?></p>
        <a class="btn btn-lg btn-block btn-primary" href="<?php echo $this->data[PerunAup::PARAM_APPROVAL_URL]; ?>">
            <?php echo $this->t('{perun:perun:aup_button}'); ?>
        </a>
    </div>
</div>


<?php

$this->includeAtTemplateBase('includes/footer.php');
