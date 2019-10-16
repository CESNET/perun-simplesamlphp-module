<?php

use SimpleSAML\Module;
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
assert('is_array($this->data["srcMetadata"])');
assert('is_array($this->data["dstMetadata"])');
assert('is_string($this->data["yesTarget"])');
assert('is_array($this->data["yesData"])');
assert('is_string($this->data["noTarget"])');
assert('is_array($this->data["noData"])');
assert('is_array($this->data["attributes"])');
assert('is_array($this->data["hiddenAttributes"])');
assert('$this->data["sppp"] === false || is_string($this->data["sppp"])');

function present_attributes_photo_or_value($nameraw, $listitem) {
    if ($nameraw === 'jpegPhoto') {
        return '<img src="data:image/jpeg;base64,' . htmlspecialchars($listitem) . '" alt="User photo" />';
    } else {
        return htmlspecialchars($listitem);
    }
}

function perun_present_attributes($t, $attributes, $nameParent)
{
    $translator = $t->getTranslator();

    if (strlen($nameParent) > 0) {
        $parentStr = strtolower($nameParent).'_';
        $str = '<ul class="perun-attributes">';
    } else {
        $parentStr = '';
        $str .= '<ul id="perun-table_with_attributes" class="perun-attributes">';
    }

    foreach ($attributes as $name => $value) {
        $nameraw = $name;
        $name = $translator->getAttributeTranslation($parentStr.$nameraw);

        if (preg_match('/^child_/', $nameraw)) {
            // insert child table
            throw new Exception('Unsupported');
        } else {
            // insert values directly
            $liClasses = [];
            if (count($value) <= 1) {
                $liClasses[] = 'perun-attr-singlevalue';
            }
            $str .= "\n".'<li class="' . implode(' ', $liClasses) . '">'
              . '<div class="row"><div class="col-sm-6"><h2 class="perun-attrname h4">'.htmlspecialchars(str_replace("domovksé", "domovské", $name)).'</h2></div>';

            $str .= '<div class="perun-attrcontainer col-sm-6">';
            $isHidden = in_array($nameraw, $t->data['hiddenAttributes'], true);
            if ($isHidden) {
                $hiddenId = \SimpleSAML\Utils\Random::generateID();
                $str .= '<span class="perun-attrvalue hidden" id="hidden_'.$hiddenId.'">';
            } else {
                $str .= '<span class="perun-attrvalue">';
            }

            if (count($value) > 0) {
                $str .= '<ul class="perun-attrlist">';
                foreach ($value as $listitem) {
                    $str .= '<li>' . present_attributes_photo_or_value($nameraw, $listitem) . '</li>';
                }
                $str .= '</ul>';
            }
            $str .= '</span>';

            if ($isHidden) {
                $str .= '<div class="perun-attrvalue consent_showattribute" id="visible_'.$hiddenId.'">';
                $str .= '&#8230; ';
                $str .= '<a class="consent_showattributelink" href="javascript:SimpleSAML_show(\'hidden_'.$hiddenId;
                $str .= '\'); SimpleSAML_hide(\'visible_'.$hiddenId.'\');">';
                $str .= $t->t('{consent:consent:show_attribute}');
                $str .= '</a>';
                $str .= '</div>';
            }

            $str .= '</div><!-- .perun-attrcontainer --></div><!-- .row --></li>';
        }       // end else: not child table
    }   // end foreach
    $str .= isset($attributes) ? '</ul>' : '';
    return $str;
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

if (array_key_exists('name', $this->data['dstMetadata'])) {
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

$this->data['header'] = $this->t('{consent:consent:consent_header}');

if (!isset($this->data['head'])) {
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
            'SPDESC' => $this->getTranslation(
                Arrays::arrayize(
                    $this->data['dstMetadata']['descr_purpose'],
                    'en'
                )
            ),
        ]
    );
}

if ($this->data['sppp'] !== false) {
    echo "<p>" . htmlspecialchars($this->t('{consent:consent:consent_privacypolicy}')) . " ";
    echo "<a target='_blank' href='" . htmlspecialchars($this->data['sppp']) . "'>" . $dstName . "</a>";
    echo "</p>";
}

echo '<h1 id="attributeheader">' .
    $this->t(
        '{perun:consent:consent_attributes_header}',
        ['SPNAME' => $dstName, 'IDPNAME' => $srcName]
    ) .
    '</h1>';

echo perun_present_attributes($this, $attributes, '');

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
                    echo('<input type="hidden" name="' . htmlspecialchars($name) .
                        '" value="' . htmlspecialchars($value) . '" />');
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
