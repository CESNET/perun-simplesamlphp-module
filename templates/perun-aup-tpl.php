<?php declare(strict_types=1);

use SimpleSAML\Module\perun\Auth\Process\PerunAup;

$this->includeAtTemplateBase('includes/header.php');

?>

<div class="row">
    <div class="offset-1 col-10 offset-sm-1 col-sm-10 offset-md-2 col-md-8 offset-lg-3 col-lg-6 offset-xl-3 col-xl-6">
        <p><?php echo $this->t('{perun:perun:aup_text}'); ?></p>
        <a class="btn btn-block" href="<?php echo $this->data[PerunAup::PARAM_APPROVAL_URL]; ?>">
            <?php echo $this->t('{perun:perun:aup_button}'); ?>
        </a>
    </div>
</div>


<?php

$this->includeAtTemplateBase('includes/footer.php');
