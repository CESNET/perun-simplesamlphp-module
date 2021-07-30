<?php

declare(strict_types=1);

/**
 * Template of page, which showing status of used components
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */

use SimpleSAML\Module\perun\StatusConnector;

$config = SimpleSAML_Configuration::getInstance();
$instanceName = $config->getString('instance_name', '');

$this->data['header'] = $instanceName . ' ' . $this->t('{perun:status:aai}') . ' ' . $this->t('{perun:status:header}');
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
                      SimpleSAML\Module::getModuleUrl('perun/res/css/status.css') . '" />';

$services = $this->data['services'];

$this->includeAtTemplateBase('includes/header.php');

echo '<p>' . $this->t('{perun:status:text}') . ' ' . $instanceName . ' ' . $this->t('{perun:status:aai}') . '.</p>';

echo '<div class="services">';

foreach ($services as $service) {
    echo '<div class="row service">';
    echo '<h3>' . $service['name'] . StatusConnector::getBadgeByStatus($service['status']) . ' </h3>';
    if (isset($service['description']) && ! empty($service['description'])) {
        echo '<p><span class="glyphicon glyphicon-info-sign"></span> ' . $service['description'] . '</p>';
    }
    echo '</div>';
}

echo '</div>';

echo '<h4>' . $this->t('{perun:status:legend}') . '</h4>';
echo '<div class="row legend">';
echo '<div class="col-md-4">';
echo '<p><span class="label label-success">' . $this->t('{perun:status:status_ok}') .
     '</span> - ' . $this->t('{perun:status:status_ok_legend}') . '</p>';
echo '</div class="col-md-4">';
echo '<div class="col-md-4">';
echo '<p><span class="label label-warning">' . $this->t('{perun:status:status_warning}') .
     '</span> - ' . $this->t('{perun:status:status_warning_legend}') . '</p>';
echo '</div class="col-md-4">';
echo '<div class="col-md-4">';
echo '<p><span class="label label-danger">' . $this->t('{perun:status:status_critical}') .
     '</span> - ' . $this->t('{perun:status:status_critical_legend}') . '</p>';
echo '</div class="col-md-4">';
echo '</div>';

$this->includeAtTemplateBase('includes/footer.php');
