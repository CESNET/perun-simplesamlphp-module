<?php

declare(strict_types=1);

const CONFIG_FILE_NAME = 'module_perun.php';
const STATUS_NAGIOS = 'status_nagios';
const SHOWN_SERVICES = 'status.shown_services';

const HOST = 'host';
const PROBE_IDENTIFIER = 'probe_identifier';
const STATUS = 'status';

$services = [];
$shownServicesList = [];

$config = SimpleSAML_Configuration::getInstance();
$perunConfig = SimpleSAML_Configuration::getConfig(CONFIG_FILE_NAME);

$params = $perunConfig->getArray(STATUS_NAGIOS, []);

if (isset($params[SHOWN_SERVICES]) && is_array($params[SHOWN_SERVICES])) {
    $shownServicesList = $params[SHOWN_SERVICES];
}

$statusConnector = sspmod_perun_StatusConnector::getInstance();
$services = $statusConnector->getStatus();

$shownServices = [];

if (empty($shownServicesList)) {
    $shownServices = $services;
} else {
    foreach ($shownServicesList as $service) {
        if (isset($services[$service[HOST]][$service[PROBE_IDENTIFIER]])) {
            $host = $services[$service[HOST]];
            $service[STATUS] = $host[$service[PROBE_IDENTIFIER]];
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
