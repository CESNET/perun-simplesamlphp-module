<?php declare(strict_types=1);

use SimpleSAML\Module\perun\Auth\Process\PerunUser;

$this->data['header'] = $this->t('{perun:perun:register_header}');

$this->includeAtTemplateBase('includes/header.php');

?>

<div class="row">
    <div>
        <p><?php echo $this->t('{perun:perun:register_text}'); ?></p>
        <a class="btn btn-block btn-primary" href="<?php echo $this->data[PerunUser::PARAM_REGISTRATION_URL]; ?>">
            <?php echo $this->t('{perun:perun:register_button}'); ?>
        </a>
    </div>
</div>


<?php

$this->includeAtTemplateBase('includes/footer.php');
