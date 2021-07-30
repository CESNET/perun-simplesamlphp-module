<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Utils\Random;

/**
 * Class Consent
 *
 * @package SimpleSAML\Module\perun
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Consent
{
    public static function perunPresentAttributes($t, $attributes, $nameParent, $labelCol = 5)
    {
        $translator = $t->getTranslator();

        if (strlen($nameParent) > 0) {
            $parentStr = strtolower($nameParent) . '_';
            $str = '<ul class="perun-attributes">';
        } else {
            $parentStr = '';
            $str = '<ul id="perun-table_with_attributes" class="perun-attributes">';
        }

        foreach ($attributes as $name => $value) {
            $nameraw = $name;
            $name = $translator->getAttributeTranslation($parentStr . $nameraw);

            if (preg_match('/^child_/', $nameraw)) {
                // insert child table
                throw new Exception('Unsupported');
            }
            // insert values directly
            $str .= "\n" . '<li>'
                        . '<div class="row"><div class="col-sm-' . $labelCol
                        . '"><h2 class="perun-attrname h4">'
                        . htmlspecialchars(str_replace('domovksé', 'domovské', $name)) . '</h2></div>';

            $str .= '<div class="perun-attrcontainer col-sm-' . (12 - $labelCol) . '">';
            $isHidden = in_array($nameraw, $t->data['hiddenAttributes'], true);
            if ($isHidden) {
                $hiddenId = Random::generateID();
                $str .= '<span class="perun-attrvalue hidden" id="hidden_' . $hiddenId . '">';
            } else {
                $str .= '<span class="perun-attrvalue">';
            }

            if (count($value) > 0) {
                $str .= '<ul class="perun-attrlist">';
                foreach ($value as $listitem) {
                    $str .= '<li>' . self::presentAttributesPhotoOrValue($nameraw, $listitem) . '</li>';
                }
                $str .= '</ul>';
            }
            $str .= '</span>';

            if ($isHidden) {
                $str .= '<div class="perun-attrvalue consent_showattribute" id="visible_' . $hiddenId . '">';
                $str .= '&#8230; ';
                $str .= '<a class="consent_showattributelink" href="javascript:SimpleSAML_show(\'hidden_';
                $str .= $hiddenId;
                $str .= '\'); SimpleSAML_hide(\'visible_' . $hiddenId . '\');">';
                $str .= $t->t('{consent:consent:show_attribute}');
                $str .= '</a>';
                $str .= '</div>';
            }

            $str .= '</div><!-- .perun-attrcontainer --></div><!-- .row --></li>';
            // end else: not child table
        }   // end foreach
        $str .= isset($attributes) ? '</ul>' : '';
        return $str;
    }

    public static function presentAttributesPhotoOrValue($nameraw, $listitem)
    {
        if ($nameraw === 'jpegPhoto') {
            return '<img src="data:image/jpeg;base64,' . htmlspecialchars($listitem) . '" alt="User photo" />';
        }
        return htmlspecialchars($listitem);
    }
}
