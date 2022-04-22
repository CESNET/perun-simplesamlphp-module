<?php declare(strict_types=1);

use SimpleSAML\Module;

$this->data['u2fAvailable'] = !empty($this->data['u2fSignRequest']);
$this->data['webauthnAvailable'] = !empty($this->data['webAuthnSignRequest']);
$this->data['pushAvailable'] = $this->data['pushAvailable'] ?? false;

$this->data['mode'] = ($this->data['mode'] ?? null) ?: 'otp';
$this->data['noAlternatives'] = true;
foreach (['webauthn', 'otp', 'push', 'u2f'] as $mode) {
    if ($mode !== $this->data['mode'] && $this->data[$mode . 'Available']) {
        $this->data['noAlternatives'] = false;
        break;
    }
}

// Set default scenario if isn't set
if (!empty($this->data['authProcFilterScenario'])) {
    if (empty($this->data['username'])) {
        $this->data['username'] = '';
    }
} else {
    $this->data['authProcFilterScenario'] = 0;
}

// Set the right text shown in otp/pass field(s)
$otpHint = $this->data['otpFieldHint'] ?? $this->t('{privacyidea:privacyidea:otp}');
$passHint = $this->data['passFieldHint'] ?? $this->t('{privacyidea:privacyidea:password}');

$this->data['header'] = $this->t('{privacyidea:privacyidea:login_title_challenge}');

// Prepare next settings
if (!empty($this->data['username'])) {
    $this->data['autofocus'] = 'password';
} else {
    $this->data['autofocus'] = 'username';
}

$this->data['head'] .= '<link rel="stylesheet" href="'
    . htmlspecialchars(Module::getModuleUrl('privacyidea/css/loginform.css'), ENT_QUOTES)
    . '" media="screen" />';

$this->includeAtTemplateBase('includes/header.php');

// Prepare error case to show it in UI if needed
if (null !== $this->data['errorCode']) {
    ?>
    <div class="alert alert-dismissable alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h2 class="alert-heading"><?php echo $this->t('{login:error_header}'); ?></h2>
        <p>
            <?php
            echo htmlspecialchars(
        sprintf('%s%s: %s', $this->t(
            '{privacyidea:privacyidea:error}'
        ), $this->data['errorCode'] ? (' ' . $this->data['errorCode']) : '', $this->data['errorMessage'])
    ); ?>
        </p>
    </div>

    <?php
}  // end of errorcode
?>
<p><?php echo $this->t('{perun:privacyidea:info_text}'); ?></p>
<form action="FormReceiver.php" method="POST" id="piLoginForm" name="piLoginForm" class="loginForm">
    <div class="row">
        <?php if ($this->data['webauthnAvailable']) { ?>
            <div class="<?php echo (!$this->data['noAlternatives']) ? 'col-md-6' : 'col-sm-12'; ?>">
            <h2><?php echo $this->t('{perun:privacyidea:webauthn}'); ?></h2>
            <div>
                <button id="useWebAuthnButton" name="useWebAuthnButton" class="btn btn-primary btn-block text-nowrap" type="button"><?php echo $this->t('{perun:privacyidea:webauthn_btn}'); ?></button>
            </div>
            <div id="message" role="alert">
            <?php
                $messageOverride = $this->data['messageOverride'] ?? null;
                if (null === $messageOverride || is_string($messageOverride)) {
                    echo htmlspecialchars($messageOverride ?? $this->data['message'] ?? '', ENT_QUOTES);
                } elseif (is_callable($messageOverride)) {
                    echo call_user_func($messageOverride, $this->data['message'] ?? '');
                }
            ?>
            </div>
        </div>
        <?php } ?>

        <?php if ($this->data['otpAvailable'] ?? true) { ?>
        <div class="<?php echo (!$this->data['noAlternatives']) ? 'col-md-6' : 'col-sm-12'; ?>">
            <h2><?php echo $this->t('{privacyidea:privacyidea:otp}'); ?></h2>
            <p><?php echo $this->t('{perun:privacyidea:otp_help}'); ?></p>
            <div class="form-row">
                <div class="form-group col-sm-12 <?php echo (!$this->data['noAlternatives']) ? '' : 'col-md-6'; ?>">
                    <label for="otp" class="sr-only"><?php echo $this->t('{perun:privacyidea:otp}'); ?></label>
                    <input id="otp" name="otp" tabindex="1" value="" class="form-control" autocomplete="one-time-code" type="text" inputmode="numeric" pattern="[0-9]{6,}" required placeholder="<?php echo htmlspecialchars($otpHint, ENT_QUOTES); ?>"/>
                </div>
                <div class="form-group col-sm-12 <?php echo (!$this->data['noAlternatives']) ? '' : 'col-md-6'; ?>">
                    <button id="submitButton" tabindex="1" class="btn btn-primary btn-block text-nowrap" type="submit" name="Submit"><?php echo htmlspecialchars($this->t('{perun:privacyidea:otp_submit_btn}'), ENT_QUOTES); ?></button>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Undefined index is suppressed and the default is used for these values -->
    <input id="mode" type="hidden" name="mode" value="otp" data-preferred="<?php echo htmlspecialchars($this->data['mode'], ENT_QUOTES); ?>"/>
    <input id="pushAvailable" type="hidden" name="pushAvailable" value="<?php echo ($this->data['pushAvailable'] ?? false) ? 'true' : ''; ?>"/>
    <input id="otpAvailable" type="hidden" name="otpAvailable" value="<?php echo ($this->data['otpAvailable'] ?? true) ? 'true' : ''; ?>"/>
    <input id="webAuthnSignRequest" type="hidden" name="webAuthnSignRequest" value='<?php echo htmlspecialchars($this->data['webAuthnSignRequest'] ?? '', ENT_QUOTES); ?>'/>
    <input id="u2fSignRequest" type="hidden" name="u2fSignRequest" value='<?php echo htmlspecialchars($this->data['u2fSignRequest'] ?? '', ENT_QUOTES); ?>'/>
    <input id="modeChanged" type="hidden" name="modeChanged" value=""/>
    <input id="step" type="hidden" name="step" value="<?php echo htmlspecialchars(strval(($this->data['step'] ?? null) ?: 2), ENT_QUOTES); ?>"/>
    <input id="webAuthnSignResponse" type="hidden" name="webAuthnSignResponse" value=""/>
    <input id="u2fSignResponse" type="hidden" name="u2fSignResponse" value=""/>
    <input id="origin" type="hidden" name="origin" value=""/>
    <input id="loadCounter" type="hidden" name="loadCounter" value="<?php echo htmlspecialchars(strval(($this->data['loadCounter'] ?? null) ?: 1), ENT_QUOTES); ?>"/>

    <!-- Additional input to persist the message -->
    <input type="hidden" name="message" value="<?php echo htmlspecialchars($this->data['message'] ?? '', ENT_QUOTES); ?>"/>
</form>
<script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/pi-webauthn.js'), ENT_QUOTES); ?>">
</script>

<script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/u2f-api.js'), ENT_QUOTES); ?>">
</script>

<meta id="privacyidea-step" name="privacyidea-step" content="<?php echo $this->data['step']; ?>">

<meta id="privacyidea-translations" name="privacyidea-translations" content="<?php
$translations = [];
$translation_keys = [
    'webauthn_insecure_context',
    'webauthn_library_unavailable',
    'webauthn_AbortError',
    'webauthn_InvalidStateError',
    'webauthn_NotAllowedError',
    'webauthn_NotSupportedError',
    'webauthn_TypeError',
    'webauthn_other_error',
    'webauthn_in_progress',
    'webauthn_success',
    'u2f_insecure_context',
    'u2f_unavailable',
    'u2f_sign_request_error',
    'try_again',
];
foreach ($translation_keys as $translation_key) {
    $translations[$translation_key] = $this->t(sprintf('{privacyidea:privacyidea:%s}', $translation_key));
}
echo htmlspecialchars(json_encode($translations));
?>">

<script src="<?php echo htmlspecialchars(Module::getModuleUrl('privacyidea/js/loginform.js'), ENT_QUOTES); ?>"></script>
<script src="<?php echo htmlspecialchars(Module::getModuleUrl('perun/js/privacy-idea-loginform.js'), ENT_QUOTES); ?>"></script>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
