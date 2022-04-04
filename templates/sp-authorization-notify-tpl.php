<?php declare(strict_types=1);

use SimpleSAML\Module\perun\Auth\Process\SpAuthorization;
use SimpleSAML\Module\perun\PerunConstants;

$this->data['header'] = '';

$spMetadata = $this->data[SpAuthorization::PARAM_SP_METADATA];
$serviceName = $this->t($spMetadata[PerunConstants::SP_NAME]);
$administrationContact = $spMetadata[PerunConstants::SP_ADMINISTRATION_CONTACT];
$mailto = 'mailto:' . $administrationContact . '?subject=' . $this->t('{perun:perun:sp_authorize_403_subject}');
$informationUrl = empty($spMetadata[PerunConstants::SP_INFORMATION_URL]) ?
    '' : $this->t($spMetadata[PerunConstants::SP_INFORMATION_URL]);
$redirectUrl = $this->data[SpAuthorization::PARAM_REGISTRATION_URL];

$this->includeAtTemplateBase('includes/header.php');
?>
    <p>
        <?php
        echo $this->t('{perun:perun:sp_authorize_notify_text}') . '<b>' . $serviceName . '</b>.';
        if (!empty($informationUrl)) {
            echo ' (' . $this->t('{perun:perun:sp_authorize_notify_information_page}') .
                '<a target="_blank" href="' . $informationUrl . '">'
                . $this->t('{perun:perun:sp_authorize_notify_information_page_link_text}') . '</a>). ';
        }
        echo $this->t('{perun:perun:sp_authorize_notify_text2}');
        ?>
    </p>
    <div>
        <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn btn-lg btn-primary btn-block">
            <?php echo $this->t('{perun:perun:sp_authorize_notify_button}'); ?>
        </a>
    </div>

<?php

$this->includeAtTemplateBase('includes/footer.php');
