<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\XHTML\Template;

/**
 * Template displaying information user that user will be redirected to registration page
 *
 * @var Template $this
 */

$this->data['header'] = '';
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/perun_identity_go_to_registration.css') . '" />';

$params = $this->data['params'];
if (isset($_POST['continueToRegistration'])) {
    HTTP::redirectTrustedURL($_REQUEST['registerUrL'], $params);
}
$spMetadata = $this->data['SPMetadata'];

$serviceName = '';
if (isset($spMetadata['name']['en'])) {
    $serviceName = $spMetadata['name']['en'];
}

$informationURL = '';
if (isset($spMetadata['InformationURL']['en'])) {
    $informationURL = $spMetadata['InformationURL']['en'];
}

$this->includeAtTemplateBase('includes/header.php');

$header = $this->t('{perun:perun:go-to-registration_header1}');
if (! empty($serviceName) && ! empty($informationURL)) {
    $header .= '<a href="' . $informationURL . '">' . $serviceName . '</a>';
} elseif (! empty($serviceName)) {
    $header .= $serviceName;
}
$header .= $this->t('{perun:perun:go-to-registration_header2}');

echo '<div id="head">';
echo '<h1>' . $header . '</h1>';
echo '</div>';

?>

    <form method="post">
        <hr/>
        <br/>
        <input type="submit" name="continueToRegistration"
               value="<?php echo $this->t('{perun:perun:go-to-registration_continue}') ?>"
               class="btn btn-lg btn-primary btn-block">
        <div class="form-group"></div>
    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
