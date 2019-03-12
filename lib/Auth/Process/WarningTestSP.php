<?php

/**
 * Class sspmod_perun_Auth_Process_WarningTestSP
 *
 * Warns user that he/she is accessing to the testing SP
 */
class sspmod_perun_Auth_Process_WarningTestSP extends SimpleSAML_Auth_ProcessingFilter
{

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
    }

    public function process(&$request)
    {
        if (isset($request["SPMetadata"]["test.sp"]) && $request["SPMetadata"]["test.sp"] === true) {
            $id = SimpleSAML_Auth_State::saveState($request, 'perun:warningTestSP');
            $url = SimpleSAML\Module::getModuleURL('perun/warning_test_sp_page.php');
            \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
        }
    }
}
