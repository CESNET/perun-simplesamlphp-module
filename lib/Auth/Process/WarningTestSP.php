<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

/**
 * Class sspmod_perun_Auth_Process_WarningTestSP
 *
 * Warns user that he/she is accessing to the testing SP
 */
class WarningTestSP extends ProcessingFilter
{
    public const CONFIG_FILE_NAME = 'module_perun.php';

    public const TEST_SP_CONFIG = 'warning_test_sp_config';

    public const TEST_SP_CONFIG_TEXT = 'text';

    public const TEST_SP_CONFIG_HEADER = 'header';

    public const CUSTOM_HEADER_ENABLED = 'custom_header_enabled';

    public const CUSTOM_TEXT_ENABLED = 'custom_text_enabled';

    public const CUSTOM_HEADER_KEY = '{perun:warning_test_sp:custom_header}';

    public const CUSTOM_TEXT_KEY = '{perun:warning_test_sp:custom_text}';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
    }

    public function process(&$request)
    {
        if (isset($request['SPMetadata']['test.sp']) && $request['SPMetadata']['test.sp'] === true) {
            $id = State::saveState($request, 'perun:warningTestSP');
            $url = Module::getModuleURL('perun/warning_test_sp_page.php');
            HTTP::redirectTrustedURL($url, [
                'StateId' => $id,
            ]);
        }
    }
}
