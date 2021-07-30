<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\AdapterRpc;
use SimpleSAML\Module\perun\Auth\Process\PerunIdentity;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\XHTML\Template;

/**
 * This page let user select one group and redirect him to a url where he can register to group.
 *
 * It prepares model data for Template.
 *
 * See PerunIdentity for mor information.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

$adapter = Adapter::getInstance($_REQUEST[PerunIdentity::INTERFACE_PROPNAME]);
$rpcAdapter = new AdapterRpc();
$spEntityId = $_REQUEST['spEntityId'];
$vosIdForRegistration = $_REQUEST['vosIdForRegistration'];
$stateId = $_REQUEST['stateId'];
$spGroups = $adapter->getSpGroups($spEntityId);
$registerUrlBase = $_REQUEST[PerunIdentity::REGISTER_URL_BASE];
$vosForRegistration = [];
$groupsForRegistration = [];

foreach ($spGroups as $group) {
    if (in_array($group->getVoId(), $vosIdForRegistration, true)) {
        if ($group->getName() === 'members' || $rpcAdapter->hasRegistrationForm($group->getId(), 'group')) {
            $vo = $adapter->getVoById($group->getVoId());
            if (! isset($vosForRegistration[$vo->getShortName()])) {
                $vosForRegistration[$vo->getShortName()] = $vo;
            }
            array_push($groupsForRegistration, $group);
        }
    }
}

if (empty($groupsForRegistration)) {
    PerunIdentity::unauthorized($_REQUEST);
} elseif (count($groupsForRegistration) === 1) {
    $params = [];
    $vo = explode(':', $groupsForRegistration[0]->getUniqueName(), 2)[0];
    $group = $groupsForRegistration[0]->getName()[0];
    $callback = Module::getModuleURL('perun/perun_identity_callback.php', [
        'stateId' => $stateId,
    ]);

    $params['vo'] = $vo;

    if ($group !== 'members') {
        $params['group'] = $group;
    }

    $params[PerunIdentity::TARGET_NEW] = $callback;
    $params[PerunIdentity::TARGET_EXISTING] = $callback;
    $params[PerunIdentity::TARGET_EXTENDED] = $callback;

    $url = Module::getModuleURL('perun/unauthorized_access_go_to_registration.php');
    HTTP::redirectTrustedURL($url, [
        'StateId' => $stateId,
        'SPMetadata' => $_REQUEST['SPMetadata'],
        'registerUrL' => $registerUrlBase,
        'params' => $params,
    ]);
}

$config = Configuration::getInstance();

$t = new Template($config, 'perun:choose-vo-and-group-tpl.php');
$t->data['registerUrlBase'] = $registerUrlBase;
$t->data['callbackUrl'] = $_REQUEST['callbackUrl'];
$t->data['vos'] = $vosForRegistration;
$t->data['groups'] = $groupsForRegistration;
$t->data['SPMetadata'] = $_REQUEST['SPMetadata'];
$t->data['stateId'] = $_REQUEST['stateId'];
$t->show();
