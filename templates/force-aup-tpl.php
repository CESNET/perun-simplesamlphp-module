<?php

/**
 * This is simple example of template where user has to accept usage policy
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 */
$newAups = $this->data['newAups'];

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

?>

    <h3><?php echo $this->t('{perun:perun:force-aup-tpl_aup_accept}'); ?></h3>
    <form method="post" action="<?php echo SimpleSAML\Module::getModuleURL('perun/force_aup_continue.php'); ?>">

        <?php
        foreach ($newAups as $key => $aup) {
            echo '<div>';
            echo '<p style="font-size: 16px; padding: 0; margin: 0;">' . $this->t('{perun:perun:organization}') .
                '<strong>' . $key . '</strong></p>';
            echo '<p>' . $this->t('{perun:perun:force-aup-tpl_aup_redirect}') . $aup->version . ' <a href="' .
                $aup->link . '">' . $this->t('{perun:perun:here}') . '</a></p>';
            echo '</div>';
        }
        ?>

        <input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>">

        <div class="form-group">
            <input type="submit" value="<?php echo $this->t('{perun:perun:force-aup-tpl_agree}'); ?>"
                   class="btn btn-lg btn-primary btn-block">
        </div>
    </form>


<?php


$this->includeAtTemplateBase('includes/footer.php');
