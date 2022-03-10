<?php declare(strict_types=1);

// Set default scenario if isn't set
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;

function getTranslation($config, $key, $fallback, $t)
{
    foreach ($config as $dictionary) {
        if (!str_contains($t->t('{' . $dictionary . ':' . $key . '}'), 'not translated')) {
            return $t->t('{' . $dictionary . ':' . $key . '}');
        }
    }

    return $fallback;
}

$config = null;
try {
    $config = Configuration::getConfig('module_perun.php')->getArray('privacyidea_dictionaries', []);
} catch (\Exception $ex) {
    Logger::error(
        "perun:loginform: missing or invalid 'module_perun.php[privacyidea]' configuration file. " .
        'Default configuration will be used.'
    );
}

if (!empty($this->data['authProcFilterScenario'])) {
    if (empty($this->data['username'])) {
        $this->data['username'] = null;
    }
} else {
    $this->data['authProcFilterScenario'] = 0;
}

// Set the right text shown in otp/pass field(s)
$otpHint = $this->data['otpFieldHint'] ?? $this->t('{privacyidea:privacyidea:otp}');
$passHint = $this->data['passFieldHint'] ?? $this->t('{privacyidea:privacyidea:password}');

$this->data['header'] = $this->t('{privacyidea:privacyidea:header}');

// Prepare next settings
$this->data['autofocus'] = strlen($this->data['username']) > 0 ? 'password' : 'username';

$this->data['head'] = '<link rel="stylesheet" href="'
    . htmlspecialchars(Module::getModuleUrl('perun/res/css/privacyidea.css'), ENT_QUOTES)
    . '" media="screen" />' . PHP_EOL;

$this->includeAtTemplateBase('includes/header.php');

// Prepare error case to show it in UI if needed
if (null !== $this->data['errorCode']) {
    ?>

    <div class="alert alert-danger">
        <strong>Error </strong>
        <?php echo htmlspecialchars($this->data['errorCode'] . ': ' . $this->data['errorMessage']); ?>
    </div>

    <?php
}  // end of errorcode
?>

    <div>
        <?php echo getTranslation($config, 'introduction_notice', '', $this); ?>
    </div>
    <div class="login">
        <div class="loginlogo"></div>

        <?php
        if ($this->data['authProcFilterScenario']) {
            echo '<h3 class="text-center">' . htmlspecialchars(
                getTranslation($config, 'login_title_challenge', 'One Time Password', $this)
            ) . '</h3>';
        } else {
            if ($this->data['step'] < 2) {
                echo '<h3>' . htmlspecialchars($this->t('{privacyidea:privacyidea:login_title}')) . '</h3>';
            }
        }
        ?>

        <form action="FormReceiver.php" method="POST" id="piLoginForm" name="piLoginForm" class="loginForm">
            <div class="form-panel first valid" id="gaia_firstform">
                <div class="slide-out ">
                    <div class="input-wrapper focused">
                        <div class="identifier-shown">
                            <?php
                            if ($this->data['forceUsername']) {
                                ?>
                                <input type="hidden" id="username" name="username"
                                       value="<?php echo htmlspecialchars($this->data['username'], ENT_QUOTES); ?>"/>
                                <?php
                            } else {
                                ?>
                                <label for="username" class="sr-only">
                                    <?php echo $this->t('{login:username}'); ?>
                                </label>
                                <input type="text" id="username" tabindex="1" name="username" autofocus
                                       value="<?php echo htmlspecialchars($this->data['username'], ENT_QUOTES); ?>"
                                       placeholder="<?php echo htmlspecialchars($this->t('{login:username}'), ENT_QUOTES); ?>"
                                />
                                <br>
                                <?php
                            }

                            // Remember username in authproc
                            if (!$this->data['authProcFilterScenario']) {
                                if ($this->data['rememberUsernameEnabled'] || $this->data['rememberMeEnabled']) {
                                    $rowspan = 1;
                                } elseif (array_key_exists('organizations', $this->data)) {
                                    $rowspan = 3;
                                } else {
                                    $rowspan = 2;
                                }
                                if ($this->data['rememberUsernameEnabled'] || $this->data['rememberMeEnabled']) {
                                    if ($this->data['rememberUsernameEnabled']) {
                                        echo str_repeat("\t", 4);
                                        echo '<input type="checkbox" id="rememberUsername" tabindex="4" name="rememberUsername"
                                     value="Yes" ';
                                        echo $this->data['rememberUsernameChecked'] ? 'checked="Yes" /> ' : '/> ';
                                        echo htmlspecialchars($this->t('{login:remember_username}'));
                                    }
                                    if ($this->data['rememberMeEnabled']) {
                                        echo str_repeat("\t", 4);
                                        echo '<input type="checkbox" id="rememberMe" tabindex="4" name="rememberMe" value="Yes" ';
                                        echo $this->data['rememberMeChecked'] ? 'checked="Yes" /> ' : '/> ';
                                        echo htmlspecialchars($this->t('{login:remember_me}'));
                                    }
                                }
                            } ?>

                            <!-- Pass and OTP fields -->
                            <label for="password" class="sr-only">
                                <?php echo $this->t('{privacyidea:privacyidea:password}'); ?>
                            </label>
                            <input id="password" name="password" tabindex="1" type="password" value="" class="text"
                                   placeholder="<?php echo htmlspecialchars($passHint, ENT_QUOTES); ?>"/>

                            <label for="otp" class="sr-only">
                                <?php echo $this->t('{privacyidea:privacyidea:otp}'); ?>
                            </label>

                            <div class="row text-center">
                                <div class="col-xs-12">
                                    <label for="otp"></label>
                                    <input id="otp" name="otp" tabindex="1" value="" class="col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0"
                                           autocomplete="one-time-code" type="text" inputmode="numeric" pattern="[0-9]{6,}"
                                           placeholder="<?php echo htmlspecialchars($otpHint, ENT_QUOTES); ?>"/>
                                </div>
                                <div class="col-xs-12">
                                    <button id="submitButton" tabindex="1" class="btn btn-primary col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0" type="submit" name="Submit">
                                        <span><?php echo htmlspecialchars(getTranslation($config, 'login_button', 'Login', $this), ENT_QUOTES); ?></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Undefined index is suppressed and the default is used for these values -->
                            <input id="mode" type="hidden" name="mode"
                                   value="<?php echo @$this->data['mode'] ?: 'otp'; ?>"/>

                            <input id="pushAvailable" type="hidden" name="pushAvailable"
                                   value="<?php echo @$this->data['pushAvailable'] ?: false; ?>"/>

                            <input id="otpAvailable" type="hidden" name="otpAvailable"
                                   value="<?php echo @$this->data['otpAvailable'] ?: true; ?>"/>

                            <input id="webAuthnSignRequest" type="hidden" name="webAuthnSignRequest"
                                   value='<?php echo @$this->data['webAuthnSignRequest'] ?: ''; ?>'/>

                            <input id="u2fSignRequest" type="hidden" name="u2fSignRequest"
                                   value='<?php echo @$this->data['u2fSignRequest'] ?: ''; ?>'/>

                            <input id="modeChanged" type="hidden" name="modeChanged" value="0"/>
                            <input id="step" type="hidden" name="step" value="<?php echo @$this->data['step'] ?: 2; ?>"/>

                            <input id="webAuthnSignResponse" type="hidden" name="webAuthnSignResponse" value=""/>
                            <input id="u2fSignResponse" type="hidden" name="u2fSignResponse" value=""/>
                            <input id="origin" type="hidden" name="origin" value=""/>
                            <input id="loadCounter" type="hidden" name="loadCounter"
                                   value="<?php echo @$this->data['loadCounter'] ?: 1; ?>"/>

                            <!-- Additional input to persist the message -->
                            <input type="hidden" name="message" value="<?php echo @$this->data['message'] ?: ''; ?>"/>

                            <?php
                            // If enrollToken load QR Code
                            if (isset($this->data['tokenQR'])) {
                                echo htmlspecialchars($this->t('{privacyidea:privacyidea:scanTokenQR}')); ?>
                                <div class="tokenQR">
                                    <?php echo '<img src="' . $this->data['tokenQR'] . '" />'; ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <?php
                        // Organizations
                        if (array_key_exists('organizations', $this->data)) {
                            ?>
                            <div class="identifier-shown">
                                <label for="organization"><?php echo htmlspecialchars($this->t('{login:organization}')); ?></label>
                                <select id="organization" name="organization" tabindex="3">
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

                                echo '<option ' . $selected . ' value="' . htmlspecialchars(
                                    $orgId,
                                    ENT_QUOTES
                                ) . '">' . htmlspecialchars($orgDesc) . '</option>';
                            } ?>
                                </select>
                            </div>
                        <?php
                        } ?>
                    </div> <!-- focused -->
                </div> <!-- slide-out-->
            </div> <!-- form-panel -->


            <div id="AlternateLoginOptions" class="groupMargin">
                <h3 class="text-center"><?php echo getTranslation($config, 'alternate_login_options', 'Alternate login options', $this); ?></h3>
                <!-- Alternate Login Options-->
                <div class="row text-center">
                    <div class="col-xs-12">
                        <button id="useWebAuthnButton" name="useWebAuthnButton" class="alternate-btn btn btn-primary col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0" type="button">
                            <span><?php echo getTranslation($config, 'webauthn', 'WebAuthn', $this); ?></span>
                        </button>
                    </div>
                    <div class="col-xs-12">
                        <button id="usePushButton" name="usePushButton" class="alternate-btn btn btn-primary col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0" type="button">
                            <span><?php echo getTranslation($config, 'push', 'Push', $this); ?></span>
                        </button>
                    </div>
                    <div class="col-xs-12">
                        <button id="useOTPButton" name="useOTPButton" class="alternate-btn btn btn-primary col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0" type="button">
                            <span><?php echo getTranslation($config, 'otp', 'OTP', $this); ?></span>
                        </button>
                    </div>
                    <div class="col-xs-12">
                        <button id="useU2FButton" name="useU2FButton" class="alternate-btn btn btn-primary col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-8 col-xs-offset-2 col-12 col-offset-0" type="button">
                            <span><?php echo getTranslation($config, 'u2f', 'U2F', $this); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php
        // Logout
        if (isset($this->data['LogoutURL'])) { ?>
            <p>
                <a href="<?php echo htmlspecialchars($this->data['LogoutURL']); ?>"><?php echo $this->t('{status:logout}'); ?></a>
            </p>
        <?php } ?>
    </div>  <!-- End of login -->

<?php
if (!empty($this->data['links'])) {
            echo '<ul class="links">';
            foreach ($this->data['links'] as $l) {
                echo '<li><a href="' . htmlspecialchars($l['href'], ENT_QUOTES) . '">' . htmlspecialchars(
                    $this->t($l['text'])
                ) . '</a></li>';
            }
            echo '</ul>';
        }
?>

    <script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/webauthn-client/pi-webauthn.js'), ENT_QUOTES); ?>">
    </script>

    <script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/u2f-api.js'), ENT_QUOTES); ?>">
    </script>

    <meta id="privacyidea-step" name="privacyidea-step" content="<?php echo $this->data['step']; ?>">
    <meta id="privacyidea-hide-alternate" name="privacyidea-hide-alternate" content="<?php echo (
        !$this->data['pushAvailable']
        && (!isset($this->data['u2fSignRequest']) || ($this->data['u2fSignRequest']) === '')
        && (!isset($this->data['webAuthnSignRequest']) || ($this->data['webAuthnSignRequest']) === '')
    ) ? 'true' : 'false'; ?>">

    <meta id="privacyidea-translations" name="privacyidea-translations" content="<?php
    $translations = [];
    $translation_keys = [
        'alert_webauthn_insecure_context', 'alert_webauthn_unavailable', 'alert_webAuthnSignRequest_error',
        'alert_u2f_insecure_context', 'alert_u2f_unavailable', 'alert_U2FSignRequest_error',
    ];
    foreach ($translation_keys as $translation_key) {
        $translations[$translation_key] = $this->t(sprintf('{privacyidea:privacyidea:%s}', $translation_key));
    }
    echo htmlspecialchars(json_encode($translations));
    ?>">

    <script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/loginform.js'), ENT_QUOTES); ?>">
    </script>

<?php
$this->includeAtTemplateBase('includes/footer.php');
