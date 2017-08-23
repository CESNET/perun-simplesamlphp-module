<?php

/**
 * Template for warn user that he/she is accessing test SP
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 */

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

?>


        <form method="post" action="<?php echo SimpleSAML_Module::getModuleURL('perun/warning_test_sp_continue.php'); ?>" >

                <input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>" >
                <h3>You are about to access service, which is in testing environment.</h3>
                </hr>
                </br>

                <div class="form-group">
                        <input type="submit" value="Continue" class="btn btn-lg btn-primary btn-block">
                </div>
        </form>



<?php

$this->includeAtTemplateBase('includes/footer.php');
