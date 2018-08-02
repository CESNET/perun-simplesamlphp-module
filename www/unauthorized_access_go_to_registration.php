<?php

$id = $_REQUEST['StateId'];

$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'perun:unauthorized-access-go-to-registration-tpl.php');
$t->data['SPMetadata'] = $_REQUEST['SPMetadata'];
$t->data['registerUrL'] = $_REQUEST['registerUrL'];
$t->data['params'] = $_REQUEST['params'];

$t->show();
