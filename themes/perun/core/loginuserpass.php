<?php declare(strict_types=1);

$this->data['header'] = $this->t('{login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
    $this->data['autofocus'] = 'password';
} else {
    $this->data['autofocus'] = 'username';
}

$this->includeAtTemplateBase('includes/header.php');

if ($this->data['errorcode'] !== null) {
    ?>
    <div class="alert alert-danger">
        <span class="glyphicon glyphicon-exclamation-sign"
              style="float:left; font-size: 38px; margin-right: 10px;"></span>

        <strong>
            <?php
            echo htmlspecialchars($this->t(
        '{errors:title_' . $this->data['errorcode'] . '}',
        $this->data['errorparams']
    )); ?>
        </strong>

        <?php
        echo htmlspecialchars($this->t(
        '{errors:descr_' . $this->data['errorcode'] . '}',
        $this->data['errorparams']
    )); ?>
    </div>

    <?php
}
?>

    <p><?php echo $this->t('{login:user_pass_text}'); ?></p>

    <br>

    <form action="?" method="post" name="f" class="form-horizontal">

        <div class="form-group">
            <label for="username" class="col-sm-2 control-label"><?php echo $this->t('{login:username}'); ?></label>
            <div class="col-sm-10">
                <input id="username" <?php echo ($this->data['forceUsername']) ? 'disabled="disabled"' : ''; ?>
                       type="text" name="username" class="form-control"
                    <?php
                    if (! $this->data['forceUsername']) {
                        echo 'tabindex="1"';
                    }
                    ?> value="<?php echo htmlspecialchars($this->data['username']); ?>"/>
            </div>
        </div>

        <div class="form-group">
            <label for="password" class="col-sm-2 control-label"><?php echo $this->t('{login:password}'); ?></label>
            <div class="col-sm-10">
                <input id="password" type="password" tabindex="2" name="password" class="form-control"/>
            </div>
        </div>

        <?php
        if ($this->data['rememberUsernameEnabled'] && ! $this->data['forceUsername']) {
            // display the "remember my username" checkbox?>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="remember_username" tabindex="4"
                                <?php echo ($this->data['rememberUsernameChecked']) ? 'checked="checked"' : ''; ?>
                                   name="remember_username" value="Yes"/>
                            <?php echo $this->t('{login:remember_username}'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <?php
        if ($this->data['rememberMeEnabled']) {
            // display the remember me checkbox (keep me logged in)?>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="remember_me" tabindex="5"
                                <?php echo ($this->data['rememberMeChecked']) ? 'checked="checked"' : ''; ?>
                                   name="remember_me" value="Yes"/>
                            <?php echo $this->t('{login:remember_me}'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <?php
        if (array_key_exists('organizations', $this->data)) {
            ?>
            <div class="form-group">
                <label for="organization"
                       class="col-sm-2 control-label"><?php echo $this->t('{login:organization}'); ?></label>
                <div class="col-sm-10">
                    <select name="organization" tabindex="3" class="form-control">
                        <?php
                        if (array_key_exists('selectedOrg', $this->data)) {
                            $selectedOrg = $this->data['selectedOrg'];
                        } else {
                            $selectedOrg = null;
                        }

            foreach ($this->data['organizations'] as $orgId => $orgDesc) {
                if (is_array($orgDesc)) {
                    $orgDesc = $this->t($orgDesc);
                }

                if ($orgId === $selectedOrg) {
                    $selected = 'selected="selected" ';
                } else {
                    $selected = '';
                }

                echo '<option ' .
                                $selected . 'value="' . htmlspecialchars($orgId) . '">' . htmlspecialchars($orgDesc) .
                                '</option>';
            } ?>
                    </select>
                </div>
            </div>
            <?php
        }
        ?>

        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button class="btn btn-success"
                        onclick="this.value='<?php echo $this->t('{login:processing}'); ?>';
                            this.disabled=true; this.form.submit(); return true;" tabindex="6">
                    <?php echo $this->t('{login:login_button}'); ?>
                </button>
            </div>
        </div>

        <?php
        foreach ($this->data['stateparams'] as $name => $value) {
            echo '<input type="hidden" name="' . htmlspecialchars($name) .
                '" value="' . htmlspecialchars($value) . '" />'
            ;
        }
        ?>
    </form>

<?php

if (! empty($this->data['links'])) {
    echo '<ul class="links" style="margin-top: 2em">';
    foreach ($this->data['links'] as $l) {
        echo '<li>' .
            '<a href="' . htmlspecialchars($l['href']) . '">' . htmlspecialchars($this->t($l['text'])) . '</a>' .
            '</li>';
    }
    echo '</ul>';
}

$this->includeAtTemplateBase('includes/footer.php');
