<?php

declare(strict_types=1);

use SimpleSAML\Module;

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

$dstName = htmlspecialchars($dstName);

$this->data['header'] = $this->t('{consent:consent:noconsent_title}');

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" ' .
    'href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" ' .
    'href="' . Module::getModuleUrl('perun/res/css/consent.css') . '" />';

$this->includeAtTemplateBase('includes/header.php');

echo '<p>' . $this->t('{consent:consent:noconsent_text}', [
    'SPNAME' => $dstName,
]) . '</p>';

if ($this->data['resumeFrom']) {
    echo '<p><a class="btn btn-default" href="' . htmlspecialchars($this->data['resumeFrom']) . '">';
    echo '<i class="glyphicon glyphicon-chevron-left"></i> ';
    echo $this->t('{consent:consent:noconsent_return}');
    echo '</a></p>';
}

if ($this->data['aboutService']) {
    echo '<p><a href="' . htmlspecialchars($this->data['aboutService']) . '">';
    echo '<i class="glyphicon glyphicon-info-sign"></i> ';
    echo $this->t('{consent:consent:noconsent_goto_about}');
    echo '</a></p>';
}

echo '<p><a class="btn btn-default" href="' . htmlspecialchars($this->data['logoutLink']) . '">';
echo '<i class="glyphicon glyphicon-ban-circle"></i> ';
echo $this->t('{consent:consent:abort}', [
    'SPNAME' => $dstName,
]);
echo '</a></p>';

$this->includeAtTemplateBase('includes/footer.php');
