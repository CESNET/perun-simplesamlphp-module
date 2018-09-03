<?php

/**
 * Template for inform user that he/she will be redirected to registration
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 */


$this->data['header'] = $this->t('{perun:perun:choose-vo-and-group-tpl_header}');
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

if(isset($_POST['continueToRegistration'])) {
	\SimpleSAML\Utils\HTTP::redirectTrustedURL($_REQUEST['registerUrL'], $params);
}

$this->includeAtTemplateBase('includes/header.php');


echo '<p>' . $this->t('{perun:perun:choose-vo-and-group-tpl_text}') . '<a href="' . $informationURL . '">' .$serviceName . '</a> </p>';
echo '<p>' . $this->t('{perun:perun:choose-vo-and-group-tpl_message}') . '</p>'
?>




    <form method="post">
        </hr>
        </br>
        <h4> <?php echo $this->t('{perun:perun:unauthorized-access_redirect_to_registration}')?> </h4>

            <input type="submit" name="continueToRegistration" value="<?php echo $this->t('{perun:perun:continue}')?>"  class="btn btn-lg btn-primary btn-block">
        <div class="form-group">
		</div>
	</form>



<?php

$this->includeAtTemplateBase('includes/footer.php');

