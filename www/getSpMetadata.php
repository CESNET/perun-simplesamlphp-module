<?php

declare(strict_types=1);

use SimpleSAML\Module\perun\MetadataFromPerun;

$fetch = new MetadataFromPerun();
$content = $fetch->getAllMetadataAsFlatfile();
$fetch->saveAndDownload($content);
