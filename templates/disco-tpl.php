<?php

declare(strict_types=1);

use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Module\perun\DiscoTemplate;

/**
 * This is simple example of template for perun Discovery service
 *
 * Allow type hinting in IDE
 *
 * @var DiscoTemplate $this
 */

$this->includeAtTemplateBase('includes/header.php');

if (! empty($this->getPreferredIdp())) {
    echo '<h4>' . $this->t('{perun:perun:disco-tpl_previous-selection}') . '</h4>' .
        '<div class="metalist list-group">' .
        Disco::showEntry($this, $this->getPreferredIdp(), true) .
        '</div>' .
        '<p style="text-align: center"> - ' . $this->t('{perun:perun:disco-tpl_or}') . ' - </p>';
}

echo '<h4>' . $this->t('{perun:perun:disco-tpl_institutional-account}') . '</h4>';

foreach ($this->getTaggedIdps() as $tag => $idplist) {
    echo $tag;
    echo '<div class="metalist list-group" id="list">';
    foreach ($idplist as $idpentry) {
        echo Disco::showEntry($this, $idpentry);
    }
    echo '</div>';
}

$this->includeAtTemplateBase('includes/footer.php');
