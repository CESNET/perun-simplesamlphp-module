<?php

use SimpleSAML\Module\perun\MetadataFromPerun;

$fetch = new MetadataFromPerun();
$content = $fetch->getAllMetadataAsFlatfile();
$fetch->saveAndDownload($content);
