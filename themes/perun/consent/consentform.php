<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Module\perun\Consent;
use SimpleSAML\Utils\Arrays;

/**
 * Template form for giving consent.
 *
 * Parameters:
 * - 'srcMetadata': Metadata/configuration for the source.
 * - 'dstMetadata': Metadata/configuration for the destination.
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'yesData': Parameters which should be included in the yes-request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'noData': Parameters which should be included in the no-request.
 * - 'attributes': The attributes which are about to be released.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package SimpleSAMLphp
 */
assert(is_array($this->data['srcMetadata']));
assert(is_array($this->data['dstMetadata']));
assert(is_string($this->data['yesTarget']));
assert(is_array($this->data['yesData']));
assert(is_string($this->data['noTarget']));
assert(is_array($this->data['noData']));
assert(is_array($this->data['attributes']));
assert(is_array($this->data['hiddenAttributes']));
assert($this->data['sppp'] === false || is_string($this->data['sppp']));

if (! isset($this->data['label-col'])) {
    $this->data['label-col'] = 5;
}

// Parse parameters
if (array_key_exists('name', $this->data['srcMetadata'])) {
    $srcName = $this->data['srcMetadata']['name'];
} elseif (array_key_exists('OrganizationDisplayName', $this->data['srcMetadata'])) {
    $srcName = $this->data['srcMetadata']['OrganizationDisplayName'];
} else {
    $srcName = $this->data['srcMetadata']['entityid'];
}

if (is_array($srcName)) {
    $srcName = $this->t($srcName);
}

if (isset($this->data['dstMetadata']['UIInfo']['DisplayName'])) {
    $dstName = $this->data['dstMetadata']['UIInfo']['DisplayName'];
} elseif (array_key_exists('name', $this->data['dstMetadata'])) {
    $dstName = $this->data['dstMetadata']['name'];
} elseif (array_key_exists('OrganizationDisplayName', $this->data['dstMetadata'])) {
    $dstName = $this->data['dstMetadata']['OrganizationDisplayName'];
} else {
    $dstName = $this->data['dstMetadata']['entityid'];
}

if (is_array($dstName)) {
    $dstName = $this->t($dstName);
}

$srcName = htmlspecialchars($srcName);
$dstName = htmlspecialchars($dstName);

$attributes = $this->data['attributes'];

$this->data['header'] = $this->t(
    '{perun:consent:consent_attributes_header}',
    [
        'SPNAME' => $dstName,
        'IDPNAME' => $srcName,
    ]
);

if (! isset($this->data['head'])) {
    $this->data['head'] = '';
}
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('consent/assets/css/consent.css') . '" />';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/consent.css') . '" />';

$this->includeAtTemplateBase('includes/header.php');

if (array_key_exists('descr_purpose', $this->data['dstMetadata'])) {
    echo '</p><p>' . $this->t(
        '{consent:consent:consent_purpose}',
        [
            'SPNAME' => $dstName,
            'SPDESC' => $this->getTranslation(Arrays::arrayize($this->data['dstMetadata']['descr_purpose'], 'en')),
        ]
    );
}

if ($this->data['sppp'] !== false) {
    echo '<p>' . htmlspecialchars($this->t('{perun:consent:consent_privacypolicy}')) . ' ';
    echo "<a target='_blank' href='" . htmlspecialchars($this->data['sppp']) . "'>" . $dstName . '</a>';
    echo '</p>';
}

echo Consent::perunPresentAttributes($this, $attributes, '', $this->data['label-col']);

?>
    <div class="row" id="saveconsentcontainer">
        <div class="col-xs-12">
            <?php
            if ($this->data['usestorage']) {
                $checked = ($this->data['checked'] ? 'checked="checked"' : '');
                echo '<div class="checkbox">
            <input type="checkbox" form="yesform" name="saveconsent" id="saveconsent" value="1" /> '
                . '<label for="saveconsent">' . $this->t('{perun:consent:remember}') . '</label>
            </div>';
            }
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">

            <form action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>" id="yesform">
                <?php
                // Embed hidden fields...
                foreach ($this->data['yesData'] as $name => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($name) .
                        '" value="' . htmlspecialchars($value) . '" />';
                }
                ?>

                <button type="submit" name="yes" class="btn btn-lg btn-primary btn-success btn-block" id="yesbutton">
                    <span><?php echo htmlspecialchars($this->t('{consent:consent:yes}')) ?></span>
                </button>

            </form>

        </div>
        <div class="col-sm-6">

            <form action="<?php echo htmlspecialchars($this->data['noTarget']); ?>">

                <?php
                foreach ($this->data['noData'] as $name => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($name) .
                        '" value="' . htmlspecialchars($value) . '" />';
                }
                ?>
                <button type="submit" class="btn btn-lg btn-default btn-block  btn-no" name="no" id="nobutton">
                    <span><?php echo htmlspecialchars($this->t('{consent:consent:no}')) ?></span>
                </button>

            </form>

        </div>
    </div>

<?php

$this->includeAtTemplateBase('includes/footer.php');
