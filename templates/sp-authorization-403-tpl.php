<?php declare(strict_types=1);

use SimpleSAML\Module\perun\Auth\Process\SpAuthorization;
use SimpleSAML\Module\perun\PerunConstants;

header('HTTP/1.0 403 Forbidden');

$this->data['header'] = '';

$spMetadata = $this->data[SpAuthorization::PARAM_SP_METADATA];
$serviceName = $this->t($spMetadata[PerunConstants::SP_NAME]);
$administrationContact = $spMetadata[PerunConstants::SP_ADMINISTRATION_CONTACT];
$mailto = 'mailto:' . $administrationContact . '?subject=' . $this->t('{perun:perun:sp_authorize_403_subject}');
$informationUrl = empty($spMetadata[PerunConstants::SP_INFORMATION_URL]) ?
    '' : $this->t($spMetadata[PerunConstants::SP_INFORMATION_URL]);
$this->includeAtTemplateBase('includes/header.php');

?>
<div class="error_message">
    <h1><?php echo $this->t('{perun:perun:sp_authorize_403_header}'); ?></h1>
    <p>
        <?php
        echo $this->t('{perun:perun:sp_authorize_403_text}') . '<b>' . $serviceName . '</b>.';
if (!empty($informationUrl)) {
    echo ' (' . $this->t('{perun:perun:sp_authorize_403_information_page}') .
        '<a target="_blank" href="' . $informationUrl . '">'
        . $this->t('{perun:perun:sp_authorize_403_information_page_link_text}') . '</a>.)';
} ?>
    </p>
    <p>
        <?php echo $this->t('{perun:perun:sp_authorize_403_contact_support}'); ?>
        <a href="<?php echo $mailto; ?>"><?php echo $administrationContact; ?></a>.
    </p>
</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
