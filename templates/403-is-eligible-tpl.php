<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Auth\Process\IsEligible;

header('HTTP/1.0 403 Forbidden');

$headerKey = $this->data[IsEligible::HEADER_TRANSLATION];
$textKey = $this->data[IsEligible::TEXT_TRANSLATION];
$buttonKey = $this->data[IsEligible::BUTTON_TRANSLATION];
$contactKey = $this->data[IsEligible::CONTACT_TRANSLATION];

$restartUrl = $this->data[IsEligible::PARAM_RESTART_URL] ?: null;
$config = Configuration::getInstance();
$supportAddress = $config->getString('technicalcontact_email', 'N/A');

$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/perun.css') . '" />';

$this->data['header'] = $this->t($headerKey);



$this->includeAtTemplateBase('includes/header.php');

?>

<div class="row">
    <div>
        <p><?php echo $this->t($textKey); ?></p>
<?php if (!empty($restartUrl)): ?>
        <p>
            <a class="btn btn-lg btn-block btn-primary" href="<?php echo $restartUrl ?>">
                <?php echo $this->t($buttonKey); ?>
            </a>
        </p>
<?php endif ?>
        <p><?php echo $this->t($contactKey); ?>
        <a href="mailto:<?php echo $supportAddress; ?>"><?php echo $supportAddress; ?></a>
        </p>
    </div>
</div>


<?php
$this->includeAtTemplateBase('includes/footer.php');
