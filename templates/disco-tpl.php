<?php

/**
 * This is simple example of template for perun Discovery service
 *
 * Allow type hinting in IDE
 * @var sspmod_perun_DiscoTemplate $this
 */

$this->includeAtTemplateBase('includes/header.php');



if (!empty($this->getPreferredIdp())) {

	echo'<h4>your previous selection</h4>' .
			'<div class="metalist list-group">' .
				buildEntry($this, $this->getPreferredIdp(), true) .
			'</div>' .
		'<p style="text-align: center"> - or - </p>';
}


echo '<h4>your institutional account</h4>';


foreach ($this->getTaggedIdps() AS $tag => $idplist) {
	echo $tag;
	echo '<div class="metalist list-group" id="list">';
	foreach ($idplist AS $idpentry) {
		echo buildEntry($this, $idpentry);
	}
	echo '</div>';
}



$this->includeAtTemplateBase('includes/footer.php');




function buildEntry(sspmod_perun_DiscoTemplate $t, $idp, $favourite = false) {

	$extra = ($favourite ? 'favourite' : '');
	$html = '<a class="metaentry ' . $extra . ' list-group-item" ' .
			' href="' . $t->getContinueUrl($idp['entityid']) . '">';

	$html .= '<strong>' . htmlspecialchars($t->getTranslatedEntityName($idp)) . '</strong>';
	$html .= '</a>';

	return $html;
}

