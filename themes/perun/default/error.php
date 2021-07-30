<?php declare(strict_types=1);

use SimpleSAML\Module;

$this->data['header'] = '<i class="glyphicon glyphicon-exclamation-sign text-danger"></i> ' .
    $this->t($this->data['dictTitle']);

$this->data['head'] = <<<CODE_SAMPLE
<meta name="robots" content="noindex, nofollow" />
<meta name="googlebot" content="noarchive, nofollow" />
CODE_SAMPLE;

$this->data['head'] .= '<script src="' . Module::getModuleUrl('perun/res/js/jquery.js') . '" ></script>';
$this->data['head'] .= '<script src="' . Module::getModuleUrl('perun/res/bootstrap/js/bootstrap.min.js') .
    '" ></script>';

$this->includeAtTemplateBase('includes/header.php');

?>

    <p>
        <?php
        echo htmlspecialchars($this->t($this->data['dictDescr'], $this->data['parameters']));
        ?>
        <a href="#moreInfo" data-toggle="collapse"><?php echo $this->t('{perun:error:more}'); ?><span
                class="caret"></span></a>
    </p>

<?php

// include optional information for error
if (isset($this->data['includeTemplate'])) {
    $this->includeAtTemplateBase($this->data['includeTemplate']);
}

?>

    <div id="moreInfo" class="collapse">
        <p id="trackid" class="input-left">
            <?php
            echo $this->t('{perun:error:error_number}');
            echo $this->data['error']['trackId'];
            ?>
        </p>
        <?php
        // print out exception only if the exception is available
        if ($this->data['showerrors']) {
            ?>
            <p style="margin: 1px"><?php echo htmlspecialchars($this->data['error']['exceptionMsg']); ?></p>
            <pre style="padding: 1em; font-family: monospace;">
                <?php echo htmlspecialchars($this->data['error']['exceptionTrace']); ?>
            </pre>
            <?php
        }
        ?>
    </div>

<?php

/* Add error report submit section if we have a valid technical contact. 'errorreportaddress' will only be set if
 * the technical contact email address has been set.
 */
if (isset($this->data['errorReportAddress'])) {
    ?>
    <br>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $this->t('report_header'); ?></h3>
        </div>
        <div class="panel-body">

            <form action="<?php echo htmlspecialchars($this->data['errorReportAddress']); ?>" method="post"
                  class="form-horizontal">

                <div class="form-group">
                    <label class="col-sm-2 control-label"
                           for="reportId"><?php echo $this->t('{perun:error:error_id}'); ?></label>
                    <div class="col-sm-10">
                        <input name="reportId" type="text" class="form-control" id="reportId"
                               value="<?php echo $this->data['error']['reportId']; ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="email"><?php echo $this->t('report_email'); ?></label>
                    <div class="col-sm-10">
                        <input name="email" type="email" class="form-control" id="email" placeholder="Email" required>
                        <span class="help-block">
                            <?php echo $this->t('{perun:error:error_report_email_message}'); ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-12">
                        <textarea name="text" class="form-control" rows="3"
                                  placeholder="<?php echo $this->t('report_explain'); ?>"></textarea>
                    </div>
                </div>

                <button type="submit" name="send"
                        class="btn btn-primary"><?php echo $this->t('report_submit'); ?></button>

            </form>

        </div>
    </div>
    <?php
}

$this->includeAtTemplateBase('includes/footer.php');
