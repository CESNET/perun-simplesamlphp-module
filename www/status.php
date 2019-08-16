<?php

use SimpleSAML\Module\perun\StatusConnector;

const OK = "OK";
const WARNING = "WARNING";
const CRITICAL = "CRITICAL";
const UNKNOWN = "UNKNOWN";

const CONFIG_FILE_NAME = "module_perun.php";
const SHOWN_SERVICES = "status.shown_services";

$services = [];

$config = SimpleSAML_Configuration::getInstance();
$perunConfig = SimpleSAML_Configuration::getConfig(CONFIG_FILE_NAME);

$shownServicesList = $perunConfig->getArray(SHOWN_SERVICES, []);

$statusConnector = sspmod_perun_StatusConnector::getInstance();
$services = $statusConnector->getStatus();

$shownServices = [];


if (empty($shownServicesList)) {
    $shownServices = $services;
} else {
    foreach ($services as $service) {
        $serviceName = $service['name'];
        if (in_array($serviceName, array_keys($shownServicesList))) {
            $service['name'] = $shownServicesList[$serviceName]['name'];
            $service['description'] = $shownServicesList[$serviceName]['description'];
            array_push($shownServices, $service);
        }
    }
}

if (isset($_GET['output']) && $_GET['output'] === 'json') {
    header('Content-type: application/json');
    echo json_encode($shownServices);
    exit;
}

$t = new SimpleSAML_XHTML_Template($config, 'perun:status-tpl.php');
$t->data['services'] = $shownServices;
$t->show();


/**
 * Returns the HTML code with correct class
 * @param $status String Status of services
 *
 * @return string
 */
function getBadgeByStatus($status)
{
    if ($status == OK) {
        return '<span class="status label label-success">OK</span>';
    } elseif ($status == WARNING) {
        return '<span class="status label label-warning">WARNING</span>';
    } elseif ($status == CRITICAL || $status == UNKNOWN) {
        return '<span class="status label label-danger">CRITICAL</span>';
    }
}

