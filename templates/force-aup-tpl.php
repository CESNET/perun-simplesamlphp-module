<?php

/**
 * This is simple example of template where user has to accept usage policy
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 */
$aupUrl = $this->data['aupUrl'];
$aupVersion = $this->data['aupVersion'];

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

?>



	<form method="post" action="<?php echo SimpleSAML_Module::getModuleURL('perun/force_aup_continue.php'); ?>" >

		<input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>" >

		<div class="form-group">
			<input type="submit" value="<?php echo $this->t('{perun:perun:force-aup-tpl_agree}'); ?>" class="btn btn-lg btn-primary btn-block">
		</div>
		<p>
			See the <a href="<?php echo $aupUrl; ?>" target="_blank"> <?php echo $this->t('{perun:perun:force-aup-tpl_aup}'); ?> <i class="glyphicon glyphicon-new-window"></i></a>.
		</p>
	</form>



<?php

$this->includeAtTemplateBase('includes/footer.php');
