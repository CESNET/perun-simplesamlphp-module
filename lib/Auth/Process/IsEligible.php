<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use DateTime;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Session;
use SimpleSAML\Utils\HTTP;

class IsEligible extends ProcessingFilter
{
    public const STAGE = 'perun:IsEligible';

    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const REDIRECT = 'perun/403_is_eligible.php';

    public const TEMPLATE = 'perun:403-is-eligible-tpl.php';

    public const DEFAULT_VALIDITY_PERIOD_MONTHS = 12;

    public const PARAM_STATE_ID = PerunConstants::STATE_ID;

    public const PARAM_RESTART_URL = 'restart_url';

    public const TRIGGER_ATTRIBUTE = 'trigger_attribute';

    public const ELIGIBLE_LAST_SEEN_TIMESTAMP_ATTRIBUTE = 'eligible_last_seen_timestamp_attribute';

    public const VALIDITY_PERIOD_MONTHS = 'validity_period_months';

    public const TRANSLATIONS = 'translations';

    public const OLD_VALUE_HEADER_TRANSLATION = 'old_value_header';

    public const OLD_VALUE_TEXT_TRANSLATION = 'old_value_text';

    public const OLD_VALUE_BUTTON_TRANSLATION = 'old_value_button';

    public const OLD_VALUE_CONTACT_TRANSLATION = 'old_value_contact';

    public const NO_VALUE_HEADER_TRANSLATION = 'no_value_header';

    public const NO_VALUE_TEXT_TRANSLATION = 'no_value_text';

    public const NO_VALUE_BUTTON_TRANSLATION = 'no_value_button';

    public const NO_VALUE_CONTACT_TRANSLATION = 'no_value_contact';

    public const HEADER_TRANSLATION = 'header_translation';

    public const TEXT_TRANSLATION = 'text_translation';

    public const BUTTON_TRANSLATION = 'button_translation';

    public const CONTACT_TRANSLATION = 'contact_translation';

    private $triggerAttribute;

    private $timestampAttribute;

    private $validityPeriodMonths;

    private $filterConfig;

    private $translations;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->filterConfig = Configuration::loadFromArray($config);

        $this->triggerAttribute = $this->filterConfig->getString(self::TRIGGER_ATTRIBUTE);
        $this->timestampAttribute = $this->filterConfig->getString(self::ELIGIBLE_LAST_SEEN_TIMESTAMP_ATTRIBUTE);

        $this->validityPeriodMonths = $this->filterConfig->getInteger(
            self::VALIDITY_PERIOD_MONTHS,
            self::DEFAULT_VALIDITY_PERIOD_MONTHS
        );

        $this->translations = $this->filterConfig->getArray(self::TRANSLATIONS, []);
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(!empty($request[PerunConstants::DESTINATION]));
        assert(!empty($request[PerunConstants::ATTRIBUTES]));

        $attributesReleasedToSp = [];
        if (!empty($request[PerunConstants::DESTINATION][PerunConstants::DESTINATION_ATTRIBUTES])) {
            $attributesReleasedToSp = $request[PerunConstants::DESTINATION][PerunConstants::DESTINATION_ATTRIBUTES];
        }

        if (!in_array($this->triggerAttribute, $attributesReleasedToSp, true)) {
            Logger::info(
                self::DEBUG_PREFIX . 'SP does not consume the trigger attribute \'' . $this->triggerAttribute . '\'. Terminating execution of this filter.'
            );
            return;
        }

        $lastSeenEligibleTimestampString = null;
        if (!empty($request[PerunConstants::ATTRIBUTES][$this->timestampAttribute])) {
            $lastSeenEligibleTimestampString = $request[PerunConstants::ATTRIBUTES][$this->timestampAttribute][0];
        } else {
            Logger::info(
                self::DEBUG_PREFIX . 'Timestamp of the last seen eligibility is empty, cannot let user go through. Redirecting to unauthorized explanation page.'
            );
            $this->unauthorized($request, false);
        }

        $lastSeenEligibleTimestamp = DateTime::createFromFormat('Y-m-d H:i:s', $lastSeenEligibleTimestampString);
        $lastSeenEligibleTimestamp = $lastSeenEligibleTimestamp->modify('+' . $this->validityPeriodMonths . 'months');
        $now = new DateTime();

        if ($lastSeenEligibleTimestamp < $now) {
            Logger::info(
                self::DEBUG_PREFIX . 'Last seen eligibility timestamp value \'' . $lastSeenEligibleTimestampString . '\' is out of the defined period of ' . $this->validityPeriodMonths . ' months until now. Redirecting to unauthorized explanation page.'
            );
            $this->unauthorized($request, true);
        }
        Logger::info(
            self::DEBUG_PREFIX . 'Last seen eligibility timestamp value \'' . $lastSeenEligibleTimestampString . '\' is inside the defined period of ' . $this->validityPeriodMonths . ' months until now. Continue to next filter.'
        );
    }

    public function unauthorized(&$state, $hasValue)
    {
        $translations = $this->loadLocalTranslations($hasValue);
        $state[self::TRANSLATIONS] = $translations;

        $state = State::saveState($state, self::STAGE);

        try {
            $session = Session::getSessionFromRequest();
            $session->doLogout('default-sp');
        } catch (Exception|\Exception $exception) {
            Logger::warning(self::DEBUG_PREFIX . 'Error when logging user out. Logout has failed!');
            Logger::debug(
                self::DEBUG_PREFIX . 'Details about the logout failure \'' . $exception->getMessage() . '\'.'
            );
        }
        $url = Module::getModuleURL(self::REDIRECT);
        $params = [
            self::PARAM_STATE_ID => $state,
        ];

        HTTP::redirectTrustedURL($url, $params);
    }

    public static function loadLocalTranslation(
        Translate $translator,
        string $key,
        array $translations,
        string $defaultTranslationKey
    ): string {
        if (!empty($translations[$key])) {
            $translation = $translations[$key];
            $translationKey = '{' . self::STAGE . '_' . $key . '}';
            $translator->includeInlineTranslation($translationKey, $translation);
            return $translationKey;
        }
        return $defaultTranslationKey;
    }

    private function loadLocalTranslations($hasValue): array
    {
        if ($hasValue) {
            $header = $this->loadTranslation(self::OLD_VALUE_HEADER_TRANSLATION, $this->translations);
            $text = $this->loadTranslation(self::OLD_VALUE_TEXT_TRANSLATION, $this->translations);
            $button = $this->loadTranslation(self::OLD_VALUE_BUTTON_TRANSLATION, $this->translations);
            $contact = $this->loadTranslation(self::OLD_VALUE_CONTACT_TRANSLATION, $this->translations);
        } else {
            $header = $this->loadTranslation(self::NO_VALUE_HEADER_TRANSLATION, $this->translations);
            $text = $this->loadTranslation(self::NO_VALUE_TEXT_TRANSLATION, $this->translations);
            $button = $this->loadTranslation(self::NO_VALUE_BUTTON_TRANSLATION, $this->translations);
            $contact = $this->loadTranslation(self::NO_VALUE_CONTACT_TRANSLATION, $this->translations);
        }
        return [
            self::HEADER_TRANSLATION => $header,
            self::TEXT_TRANSLATION => $text,
            self::BUTTON_TRANSLATION => $button,
            self::CONTACT_TRANSLATION => $contact,
        ];
    }

    private function loadTranslation(string $key, array $translations): array
    {
        if (array_key_exists($key, $translations)) {
            return $translations[$key];
        }
        return [];
    }
}
