<?php

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

?>

    <h3><?php echo $this->t('{perun:perun:block-user-tpl_title}'); ?></h3>

    <div class="alert alert-warning" role="alert">
        <?php echo $this->t('{perun:perun:block-user-tpl_text}'); ?>
    </div>

<?php

$this->includeAtTemplateBase('includes/footer.php');
