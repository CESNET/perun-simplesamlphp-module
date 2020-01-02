<?php


namespace SimpleSAML\Module\perun\Auth\Process;


use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

class RedirectSomeUsers extends ProcessingFilter
{

    const ATTRIBUTE_IDENTIFIER = 'attributeIdentifier';
    const URL_WITH_LOGINS = 'urlWithLogins';
    const ALLOWED_CONTINUE = 'allowedContinue';
    const REDIRECT_URL = 'redirectURL';
    const PAGE_TEXT = 'pageText';

    private $attributeIdentifier;
    private $URLWtithLogins;
    private $allowedContinue = true;
    private $redirectURL;
    private $pageText;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config[self::ATTRIBUTE_IDENTIFIER])) {
            throw new Exception(
                'perun:RedirectSomeUsers - missing mandatory configuration option \'' .
                self::ATTRIBUTE_IDENTIFIER . '\'.'
            );
        }
        if (!isset($config[self::URL_WITH_LOGINS])) {
            throw new Exception(
                'perun:RedirectSomeUsers - missing mandatory configuration option \'' . self::URL_WITH_LOGINS . '\'.'
            );
        }
        if (!isset($config[self::REDIRECT_URL])) {
            throw new Exception(
                'perun:RedirectSomeUsers - missing mandatory configuration option \'' . self::REDIRECT_URL . '\'.'
            );
        }
        if (!isset($config[self::PAGE_TEXT]['en'])) {
            throw new Exception(
                'perun:RedirectSomeUsers - missing mandatory configuration option \'' . self::REDIRECT_URL . '\'.'
            );
        }

        $this->attributeIdentifier = (string)$config[self::ATTRIBUTE_IDENTIFIER];
        $this->URLWtithLogins = (string)$config[self::URL_WITH_LOGINS];
        if (isset($config[self::ALLOWED_CONTINUE])) {
            $this->allowedContinue = (boolean)$config[self::ALLOWED_CONTINUE];
        }
        $this->redirectURL = (string)$config[self::REDIRECT_URL];
        $this->pageText = $config[self::PAGE_TEXT];
    }

    public function process(&$request)
    {
        $listOfLoginsToRedirect = file_get_contents($this->URLWtithLogins);
        if (empty($listOfLoginsToRedirect)) {
            Logger::debug('perun:RedirectSomeUsers - List of logins is empty!');
        }

        $listOfLoginsToRedirect = explode("\n", $listOfLoginsToRedirect);

        if (!isset($request['Attributes'][$this->attributeIdentifier])) {
            Logger::debug('perun:RedirectSomeUsers - User has not an attribute with identifier \''.
                          $this->attributeIdentifier . ' \'!');
        }
        $userLogins = $request['Attributes'][$this->attributeIdentifier];

        $redirectUser = false;

        foreach ($userLogins as $userLogin) {
            if (in_array($userLogin, $listOfLoginsToRedirect)) {
                $redirectUser = true;
                continue;
            }
        }

        if (!$redirectUser) {
            Logger::debug('perun:RedirectSomeUsers - Redirect is not required. Skipping to another process filter.');
            return;
        }


        $id = State::saveState($request, 'perun:redirectSomeUsers');
        $url = Module::getModuleURL('perun/redirect_some_users.php');
        $attributes = [
            'StateId' => $id,
            'allowedContinue' => $this->allowedContinue,
            'redirectURL' => $this->redirectURL,
            'pageText' => $this->pageText
        ];
        HTTP::redirectTrustedURL($url, $attributes);
    }
}
