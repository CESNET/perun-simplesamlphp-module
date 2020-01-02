<?php

use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

/**
 * Template for warn user that he/she is accessing test SP
 *
 * Allow type hinting in IDE
 * @var Template $this
 */

$this->data['header'] = '';
$allowedContinue = $this->data['allowedContinue'];
$redirectURL = $this->data['redirectURL'];
$pageText = $this->data['pageText'];
$this->includeAtTemplateBase('includes/header.php');

?>

    <form method="post" action="<?php echo Module::getModuleURL('perun/redirect_some_users_continue.php'); ?>">

        <input type="hidden" name="StateId" value="<?php echo $_REQUEST['StateId'] ?>">
        <h3> <?php echo $this->t('{perun:perun:redirect_some_users-header}') ?> </h3>
        </hr>
        </br>

        <div> <?php echo $pageText ?> </div>

        </hr>
        </br>

        <?php
        if ($allowedContinue) {
            echo '<a class="btn btn-lg btn-block btn-primary" style="color:#FFF" target="_blank" href="' .
                 $redirectURL . '">' . $this->t('{perun:perun:continue}')  . '</a>';


            echo "</br>";
            echo '<div class="form-group">'. $this->t('{perun:perun:continue_to_service}') . '
            <input type="submit" value="' . $this->t('{perun:perun:here}') . '"
                   class="btn btn-sm btn-link">
            </div>';
        } else {
            echo '<a class="btn btn-lg btn-block btn-primary "style="color:#FFF"  href="' . $redirectURL . '">' .
                 $this->t('{perun:perun:continue}')  . '</a>';
        }
        ?>

    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
