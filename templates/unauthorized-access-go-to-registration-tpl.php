<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\XHTML\Template;

/**
 * Template for inform user that he/she will be redirected to registration
 *
 * Allow type hinting in IDE
 *
 * @var Template $this
 */

$this->data['header'] = '';
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/perun_identity_go_to_registration.css') . '" />';

$spMetadata = $this->data['SPMetadata'];
$serviceName = '';
$informationURL = '';
$params = $this->data['params'];
if ($spMetadata['name']['en']) {
    $serviceName = $spMetadata['name']['en'];
}

if ($spMetadata['InformationURL']['en']) {
    $informationURL = $spMetadata['InformationURL']['en'];
}

if (isset($_POST['continueToRegistration'])) {
    HTTP::redirectTrustedURL($_REQUEST['registerUrL'], $params);
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
        </hr>
        </br>
        <input type="submit" name="continueToRegistration"
               value="<?php echo $this->t('{perun:perun:go-to-registration_continue}') ?>"
               class="btn btn-lg btn-primary btn-block">
        <div class="form-group">
        </div>
    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
