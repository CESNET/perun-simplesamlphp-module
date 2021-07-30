<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\discopower\PowerIdPDisco;
use SimpleSAML\Module\perun\model\WarningConfiguration;
use SimpleSAML\Utils\HTTP;

/**
 * This class implements a IdP discovery service.
 *
 * This module extends the DiscoPower IdP disco handler, so it needs to be avaliable and enabled and configured.
 *
 * It adds functionality of whitelisting and greylisting IdPs. for security reasons for blacklisting please manipulate
 * directly with metadata. In case of manual idps comment them out or in case of automated metadata fetching configure
 * blacklist in config-metarefresh.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Disco extends PowerIdPDisco
{
    public const CONFIG_FILE_NAME = 'module_perun.php';

    public const URN_CESNET_PROXYIDP_IDPENTITYID = 'urn:cesnet:proxyidp:idpentityid:';

    public const DEFAULT_THEME = 'perun';

    # ROOT CONFIGURATION ENTRY
    public const WAYF = 'wayf_config';

    # CONFIGURATION ENTRIES
    public const BOXED = 'boxed';

    public const TRANSLATE_MODULE = 'translate_module';

    public const REMOVE_AUTHN_CONTEXT_CLASS_PREFIXES = 'remove_authn_context_class_ref_prefixes';

    public const DISABLE_WHITELISTING = 'disable_whitelisting';

    # CONFIGURATION ENTRIES IDP BLOCKS
    public const IDP_BLOCKS = 'idp_blocks_config';

    public const IDP_BLOCK_TYPE = 'type';

    public const IDP_BLOCK_TYPE_INLINESEARCH = 'inlinesearch';

    public const IDP_BLOCK_TYPE_TAGGED = 'tagged';

    public const IDP_BLOCK_TEXT_ENABLED = 'text_enabled';

    public const IDP_BLOCK_NAME = 'name';

    public const IDP_BLOCK_HINT_TRANSLATION = 'hint_translation';

    public const IDP_BLOCK_NOTE_TRANSLATION = 'note_translation';

    public const IDP_BLOCK_PLACEHOLDER_TRANSLATE = 'placeholder_translation';

    public const IDP_BLOCK_TAGS = 'tags';

    public const IDP_BLOCK_ENTITY_IDS = 'entity_ids';

    # CONFIGURATION ENTRIES ADD INSTITUTION
    public const ADD_INSTITUTION = 'add_institution_config';

    public const ADD_INSTITUTION_URL = 'url';

    public const ADD_INSTITUTION_EMAIL = 'email';

    # PARAMS AND DATA KEYS
    public const ENTITY_ID = 'entityID';

    public const RETURN = 'return';

    public const RETURN_ID_PARAM = 'returnIDParam';

    public const ORIGINAL_SP = 'originalsp';

    public const IDP_LIST = 'idplist';

    public const PREFERRED_IDP = 'preferredidp';

    public const AUTHN_CONTEXT_CLASS_REF = 'AuthnContextClassRef';

    public const WARNING_ATTRIBUTES = 'warningAttributes';

    public const AUTH_ID = 'AuthID';

    # METADATA KEYS
    public const METADATA_DO_NOT_FILTER_IDPS = 'disco.doNotFilterIdps';

    public const METADATA_ADD_INSTITUTION_APP = 'disco.addInstitutionApp';

    public const IDP_ENTITY_ATTRIBUTES = 'EntityAttributes';

    public const IDP_COCO = 'CoCo';

    public const IDP_RAS = 'RaS';

    public const SP_GREYLIST = 'greylist';

    public const IDP_ENTITY_ID = 'entityid';

    public const IDP_COLOR = 'color';

    public const IDP_FULL_DISPLAY_NAME = 'fullDisplayName';

    public const IDP_SHOW_SIGN_IN_WITH = 'showSignInWith';

    # STATE KEYS
    public const STATE_SP_METADATA = 'SPMetadata';

    public const SAML_REQUESTED_AUTHN_CONTEXT = 'saml:RequestedAuthnContext';

    public const STATE_AUTHN_CONTEXT_CLASS_REF = 'AuthnContextClassRef';

    public const SAML_SP_SSO = 'saml:sp:sso';

    private $originalsp;

    private array $originalAuthnContextClassRef = [];

    private $wayfConfiguration;

    private $perunModuleConfiguration;

    private $proxyIdpEntityId;

    public function __construct(array $metadataSets, $instance)
    {
        //LOAD CONFIG FOR MODULE PERUN, WHICH CONTAINS WAYF CONFIGURATION
        try {
            $this->perunModuleConfiguration = Configuration::getConfig(self::CONFIG_FILE_NAME);
            $this->wayfConfiguration = $this->perunModuleConfiguration->getConfigItem(self::WAYF);
        } catch (\Exception $ex) {
            Logger::error("perun:disco-tpl: missing or invalid '" . self::CONFIG_FILE_NAME . "' config file");
            throw $ex;
        }

        if (! array_key_exists(self::RETURN, $_GET)) {
            throw new \Exception('Missing parameter: ' . self::RETURN);
        }
        $returnURL = HTTP::checkURLAllowed($_GET[self::RETURN]);

        parse_str(parse_url($returnURL)['query'], $query);

        if (isset($query[self::AUTH_ID])) {
            $id = explode(':', $query[self::AUTH_ID])[0];
            $state = State::loadState($id, self::SAML_SP_SSO, true);

            if ($state !== null) {
                if (isset($state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::AUTHN_CONTEXT_CLASS_REF])) {
                    $this->originalAuthnContextClassRef = $state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::AUTHN_CONTEXT_CLASS_REF];
                    $this->removeAuthContextClassRefWithPrefixes($state);
                    if (isset($state['IdPMetadata']['entityid'])) {
                        $this->proxyIdpEntityId = $state['IdPMetadata']['entityid'];
                    }
                    State::saveState($state, self::SAML_SP_SSO);
                }
                $e = explode('=', $returnURL)[0];
                $newReturnURL = $e . '=' . urlencode($id);
                $_GET[self::RETURN] = $newReturnURL;
            }
        }

        parent::__construct($metadataSets, $instance);

        if (isset($state) && isset($state[self::STATE_SP_METADATA])) {
            $this->originalsp = $state[self::STATE_SP_METADATA];
        }
    }

    /**
     * Handles a request to this discovery service. It is entry point of Discovery service.
     *
     * The IdP disco parameters should be set before calling this function.
     */
    public function handleRequest()
    {
        // test if user has selected an idp or idp can be determined automatically somehow.
        $this->start();

        // no choice possible. Show discovery service page
        $idpList = $this->getIdPList();
        if (isset($this->originalsp[self::METADATA_ADD_INSTITUTION_APP])
            && $this->originalsp[self::METADATA_ADD_INSTITUTION_APP] === true
        ) {
            $idpList = $this->filterAddInstitutionList($idpList);
        } else {
            $idpList = $this->filterList($idpList);
        }

        if (sizeof($idpList) === 1) {
            $idp = array_keys($idpList)[0];
            $url = self::buildContinueUrl($this->spEntityId, $this->returnURL, $this->returnIdParam, $idp);
            Logger::info('perun.Disco: Only one Idp left. Redirecting automatically. IdP: ' . $idp);
            HTTP::redirectTrustedURL($url);
        }

        $preferredIdP = $this->getRecommendedIdP();
        $preferredIdP = array_key_exists($preferredIdP, $idpList) ? $preferredIdP : null;

        // IF IS SET AUTHN CONTEXT CLASS REF, REDIRECT USER TO THE IDP
        if (isset($this->originalAuthnContextClassRef)) {
            if ($this->originalAuthnContextClassRef !== null) {
                # Check authnContextClassRef and select IdP directly if the correct value is set
                foreach ($this->originalAuthnContextClassRef as $value) {
                    // VERIFY THE PREFIX IS CORRECT AND WE CAN PERFORM THE REDIRECT
                    $acrStartSubstr = substr($value, 0, strlen(self::URN_CESNET_PROXYIDP_IDPENTITYID));
                    if ($acrStartSubstr === self::URN_CESNET_PROXYIDP_IDPENTITYID) {
                        $idpEntityId = substr($value, strlen(self::URN_CESNET_PROXYIDP_IDPENTITYID), strlen($value));
                        if ($idpEntityId === $this->proxyIdpEntityId) {
                            continue;
                        }
                        Logger::info('Redirecting to ' . $idpEntityId);
                        $url = self::buildContinueUrl(
                            $this->spEntityId,
                            $this->returnURL,
                            $this->returnIdParam,
                            $idpEntityId
                        );
                        HTTP::redirectTrustedURL($url);
                    }
                }
            }
        }

        $warningAttributes = null;
        try {
            $warningInstance = WarningConfiguration::getInstance();
            $warningAttributes = $warningInstance->getWarningAttributes();
        } catch (Exception $ex) {
            $warningAttributes = null;
        }

        $t = new DiscoTemplate($this->config);
        $t->data[self::ORIGINAL_SP] = $this->originalsp;
        $t->data[self::IDP_LIST] = $this->idplistStructured($idpList);
        $t->data[self::PREFERRED_IDP] = $preferredIdP;
        $t->data[self::ENTITY_ID] = $this->spEntityId;
        $t->data[self::RETURN] = $this->returnURL;
        $t->data[self::RETURN_ID_PARAM] = $this->returnIdParam;
        $t->data[self::AUTHN_CONTEXT_CLASS_REF] = $this->originalAuthnContextClassRef;
        $t->data[self::WARNING_ATTRIBUTES] = $warningAttributes;
        $t->data[self::WAYF] = $this->wayfConfiguration;
        $t->show();
    }

    /**
     * Filter out IdP which: 1. are not in SAML2 Scoping attribute list (SAML2 feature) 2. are not whitelisted (if
     * whitelisting is allowed) 3. are greylisted
     *
     * @param array $list A map of entities to filter.
     *
     * @return array The list in $list after filtering entities.
     * @throws Exception In case
     */
    public static function doFilter(
        array $list,
        bool $disableWhitelisting = false,
        array $scopedIdPList = []
    ): array {
        $service = IdpListsService::getInstance();
        $whitelist = $service->getWhitelistEntityIds();
        $greylist = $service->getGreylistEntityIds();

        $list = self::scoping($list, $scopedIdPList);
        if (! $disableWhitelisting) {
            $list = self::whitelisting($list, $whitelist);
        }
        $list = self::greylisting($list, $greylist);

        return $list;
    }

    /**
     * @param $entityID
     * @param $return
     * @param $returnIDParam
     * @param $idpEntityId
     * @return string url where user should be redirected when he choose idp
     */
    public static function buildContinueUrl(
        string $entityID,
        string $return,
        string $returnIDParam,
        string $idpEntityId
    ): string {
        return '?' .
            'entityID=' . urlencode($entityID) . '&' .
            'return=' . urlencode($return) . '&' .
            'returnIDParam=' . urlencode($returnIDParam) . '&' .
            'idpentityid=' . urlencode($idpEntityId);
    }

    /**
     * @param $entityID
     * @param $return
     * @param $returnIDParam
     * @return string url where user should be redirected when he choose idp
     */
    public static function buildContinueUrlWithoutIdPEntityId(
        string $entityID,
        string $return,
        string $returnIDParam
    ): string {
        return '?' .
            'entityID=' . urlencode($entityID) . '&' .
            'return=' . urlencode($return) . '&' .
            'returnIDParam=' . urlencode($returnIDParam);
    }

    /**
     * This method remove all AuthnContextClassRef which start with prefix from configuration
     *
     * @param $state
     * @throws \Exception
     */
    public function removeAuthContextClassRefWithPrefixes(&$state)
    {
        $prefixes = $this->wayfConfiguration->getArray(self::REMOVE_AUTHN_CONTEXT_CLASS_PREFIXES, []);

        if (empty($prefixes)) {
            return;
        }
        unset($state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::STATE_AUTHN_CONTEXT_CLASS_REF]);
        $filteredAcrs = [];
        foreach ($this->originalAuthnContextClassRef as $acr) {
            $acr = trim($acr);
            $retain = true;
            foreach ($prefixes as $prefix) {
                if (substr($acr, 0, strlen($prefix)) === $prefix) {
                    $retain = false;
                    break;
                }
            }
            if ($retain) {
                array_push($filteredAcrs, $acr);
            }
        }
        if (! empty($filteredAcrs)) {
            $state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::STATE_AUTHN_CONTEXT_CLASS_REF] = $filteredAcrs;
        }
    }

    /**
     * @param bool $favourite
     * @return string html
     */
    public static function showEntry(DiscoTemplate $t, array $metadata, $favourite = false): string
    {
        $searchData = htmlspecialchars(self::constructSearchData($metadata));
        $extra = ($favourite ? ' favourite' : '');
        $href = $t->getContinueUrl($metadata[self::IDP_ENTITY_ID]);
        $html = '<a class="metaentry' . $extra .
            ' list-group-item" data-search="' . $searchData . '" href="' . $href . '">';
        $html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';
        $html .= '</a>';

        return $html;
    }

    /**
     * @return string html
     */
    public static function showTaggedEntry(DiscoTemplate $t, array $metadata, string $class = ''): string
    {
        if (! array_key_exists('tags', $metadata)) {
            return self::showEntry($t, $metadata);
        }
        $bck = 'white';
        if (! empty($metadata[self::IDP_COLOR])) {
            $bck = $metadata[self::IDP_COLOR];
        }

        $href = $t->getContinueUrl($metadata[self::IDP_ENTITY_ID]);
        $text = '';
        if (isset($metadata[self::IDP_FULL_DISPLAY_NAME])) {
            $text = $metadata[self::IDP_FULL_DISPLAY_NAME];
        } elseif (isset($metadata[self::IDP_SHOW_SIGN_IN_WITH]) && $metadata[self::IDP_SHOW_SIGN_IN_WITH]) {
            $text = $t->t('{perun:disco:sign_in_with}') . $t->getTranslatedEntityName($metadata);
        } else {
            $text .= $t->getTranslatedEntityName($metadata);
        }
        $html = '<div class="' . $class . '">' . PHP_EOL;
        $html .= '    <div class="metalist list-group">' . PHP_EOL;
        $html .= '        <a class="metaentry btn btn-block tagged" href="' . $href .
            '" style="background: ' . $bck . '">';
        $html .= '            <img alt="icon" src="' . $metadata['icon'] . '"><strong>' . $text . '</strong>' . PHP_EOL;
        $html .= '        </a>';
        $html .= '    </div>';
        $html .= '</div>';

        return $html;
    }

    public static function getOr($id = null): string
    {
        $or = '';
        if ($id !== null) {
            $or .= '<div class="hrline" id="' . $id . '">' . PHP_EOL;
        } else {
            $or .= '<div class=" hrline">' . PHP_EOL;
        }
        $or .= '    <span>or</span>' . PHP_EOL;
        $or .= '</div>';
        return $or;
    }

    public static function showTaggedIdPs(DiscoTemplate $t, Configuration $blockConfig): string
    {
        $html = '';
        $idps = [];
        $tags = $blockConfig->getArray(self::IDP_BLOCK_TAGS, []);
        foreach ($tags as $tag) {
            $idps = array_merge($idps, $t->getIdPs($tag));
        }
        $entityIds = $blockConfig->getArray(self::IDP_BLOCK_ENTITY_IDS, []);
        $allIdps = $t->getAllIdps();
        foreach ($entityIds as $entityId) {
            array_push($idps, $allIdps[$entityId]);
        }
        $idpCount = count($idps);
        if ($idpCount === 0) {
            return $html;
        }
        $html .= '<div class="row">' . PHP_EOL;
        $html .= self::addLoginOptionHint($t, $blockConfig);

        $counter = 0;
        $fullRows = floor($idpCount / 3);
        $remainingIdps = $idpCount % 3;
        $class = 'col-xs-12 col-md-6 col-lg-4';
        for ($i = 0; $i < $fullRows; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($remainingIdps === 0 && $counter === ($idpCount - 1)) {
                    $class .= ' col-md-offset-3 col-lg-offset-0';
                }
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
            }
        }

        $html .= self::showRemainingTaggedEntries($t, $idps, $counter, $remainingIdps, $fullRows > 0);
        $html .= self::addLoginOptionNote($t, $blockConfig);
        $html .= '</div>' . PHP_EOL;
        return $html;
    }

    public static function showWarning(DiscoTemplate $t, WarningConfiguration $warningConf): string
    {
        $html = '';
        switch ($warningConf->getType()) {
            case WarningConfiguration::WARNING_TYPE_INFO:
                $html .= '<div class="alert alert-info">' . PHP_EOL;
                break;
            case WarningConfiguration::WARNING_TYPE_WARNING:
                $html .= '<div class="alert alert-warning">' . PHP_EOL;
                break;
            case WarningConfiguration::WARNING_TYPE_ERROR:
                $html .= '<div class="alert alert-danger">' . PHP_EOL;
                break;
        }

        $html .= '<h4><strong>' . $t->t('{perun:disco:warning_title}') . '</strong></h4>' . PHP_EOL;
        $html .= $t->t('{perun:disco:warning_text}');
        $html .= '</div>';
        return $html;
    }

    public static function showInlineSearch(
        DiscoTemplate $t,
        Configuration $blockConfig,
        Configuration $addInstitution = null
    ): string {
        $result = '';
        $allIdps = $t->getAllIdps();
        $isAddInstitutionApp = $t->isAddInstitutionApp();
        $addInstitutionUrl = '';
        $addInstitutionEmail = '';
        if ($addInstitution !== null) {
            $addInstitutionUrl = $addInstitution->getString(self::ADD_INSTITUTION_URL, '');
            $addInstitutionEmail = $addInstitution->getString(self::ADD_INSTITUTION_EMAIL, '');
        }
        $placeholderTranslateKey = self::getPlaceholderTranslation(
            $t,
            $blockConfig,
            '{perun:disco:institution_search_input_placeholder}'
        );

        $result .= self::addLoginOptionNote($t, $blockConfig, '{perun:disco:institution_search_hint}');
        $result .= '<div class="inlinesearch">' . PHP_EOL;
        $result .= '    <form id="idpselectform" action="?" method="get">' . PHP_EOL;
        $result .= '        <input class="inlinesearchf form-control input-lg" type="text" value=""
            name="query" id="query" autofocus oninput="$(\'#list\').show();" placeholder="'
            . $t->t($placeholderTranslateKey) . '"/>' . PHP_EOL;
        $result .= '    </form>';
        # ENTRIES
        $result .= '    <div class="metalist list-group" id="list" style="display: none">' . PHP_EOL;
        foreach ($allIdps as $idpentry) {
            $result .= self::showEntry($t, $idpentry) . PHP_EOL;
        }
        $result .= '    </div>' . PHP_EOL;
        # TOO MUCH ENTRIES BLOCK
        $result .= '    <div id="warning-entries" class="alert alert-info entries-warning-block">' . PHP_EOL;
        $result .= '        ' . $t->t(
            '{perun:disco:institution_search_display_too_much_entries_header}',
            [
                '<COUNT_HTML>' => '<span id="results-cnt">0</span>',
            ]
        ) . PHP_EOL;
        $result .= '        <div class="col">' . PHP_EOL;
        $result .= '            <button class="btn btn-block btn-info" id="warning-entries-btn-force-show">';
        $result .= ' ' . $t->t('{perun:disco:institution_search_display_too_much_entries_btn}') . '</button>' . PHP_EOL;
        $result .= '        </div>' . PHP_EOL;
        $result .= '    </div>' . PHP_EOL;
        # NO ENTRIES BLOCK
        $result .= '    <div id="no-entries" class="no-idp-found alert alert-info entries-warning-block">' . PHP_EOL;
        if ($isAddInstitutionApp && $addInstitutionEmail !== null) {
            $result .= '        ' . $t->t('{perun:disco:add_institution_no_entries_contact_us}') .
                ' <a href="mailto:' . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
                $addInstitutionEmail . '</a>' . PHP_EOL;
        } else {
            $result .= '       ' . $t->t('{perun:disco:institution_search_no_entries_header}');
            if ($addInstitutionUrl !== null) {
                $result .= '<br/><br/>' . PHP_EOL;
                $result .= '       ' .
                    ' <a class="btn btn-info btn-block" href="' . $addInstitutionUrl . '">' .
                    $t->t('{perun:disco:institution_search_no_entries_add_institution_text}') . '</a>' . PHP_EOL;
            }
        }
        $result .= '    </div>' . PHP_EOL;
        $result .= '</div>';
        $result .= self::addLoginOptionNote($t, $blockConfig);

        return $result;
    }

    public static function getScripts(bool $boxed): string
    {
        $html = '<script type="text/javascript" src="' .
            Module::getModuleUrl('discopower/assets/js/suggest.js') . '"></script>' . PHP_EOL;

        $html .= '<script type="text/javascript" src="' .
            Module::getModuleUrl('perun/res/js/jquery.livesearch.js') . '"></script>' . PHP_EOL;

        $html .= '<script type="text/javascript" src="' .
            Module::getModuleUrl('perun/res/js/disco.js') . '"></script>' . PHP_EOL;
        if ($boxed) {
            $html .= self::boxedDesignScript() . PHP_EOL;
        }
        return $html;
    }

    /**
     * Filter a list of entities according to any filters defined in the parent class, plus
     *
     * @param array $list A map of entities to filter.
     * @return array The list in $list after filtering entities.
     * @throws Exception if all IdPs are filtered out and no one left.
     */
    protected function filterList($list): array
    {
        $disableWhitelisting = $this->wayfConfiguration->getBoolean(self::DISABLE_WHITELISTING, false);

        if (! isset($this->originalsp[self::METADATA_DO_NOT_FILTER_IDPS])
            || ! $this->originalsp[self::METADATA_DO_NOT_FILTER_IDPS]
        ) {
            $list = parent::filterList($list);
            $list = self::doFilter($list, $disableWhitelisting, $this->scopedIDPList);
            $list = $this->greylistingPerSP($list, $this->originalsp);
        }

        if (empty($list)) {
            throw new Exception('All IdPs has been filtered out. And no one left.');
        }

        return $list;
    }

    /**
     * Filter a list of entities for addInstitution app according to if entityID is whitelisted or not
     *
     * @param array $list A map of entities to filter.
     * @return array The list in $list after filtering entities.
     * @throws Exception if all IdPs are filtered out and no one left.
     */
    protected function filterAddInstitutionList(array $list): array
    {
        $service = IdpListsService::getInstance();
        $whitelist = $service->getWhitelistEntityIds();
        foreach ($list as $entityId => $idp) {
            if (in_array($entityId, $whitelist, true)) {
                unset($list[$entityId]);
            }
        }

        if (empty($list)) {
            throw new Exception('All IdPs has been filtered out. And no one left.');
        }

        return $list;
    }

    /**
     * Filter out IdP which are not in SAML2 Scoping attribute list (SAML2 feature)
     *
     * @param array $list A map of entities to filter.
     *
     * @return array The list in $list after filtering entities.
     */
    protected static function scoping(array $list, array $scopedIDPList): array
    {
        if (! empty($scopedIDPList)) {
            foreach ($list as $entityId => $idp) {
                if (! in_array($entityId, $scopedIDPList, true)) {
                    unset($list[$entityId]);
                }
            }
        }
        return $list;
    }

    /**
     * Filter out IdP which: 1. are not whitelisted 2. are not supported research and scholarship 3. are not supported
     * code of conduct
     *
     * @param array $list A map of entities to filter.
     * @param array $whitelist The list of whitelisted IdPs
     *
     * @return array The list in $list after filtering entities.
     */
    protected static function whitelisting(array $list, array $whitelist): array
    {
        foreach ($list as $entityId => $idp) {
            $unset = true;

            if (in_array($entityId, $whitelist, true)) {
                $unset = false;
            }
            if (isset($idp[self::IDP_ENTITY_ATTRIBUTES]['http://macedir.org/entity-category-support'])) {
                $entityCategorySupport
                    = $idp[self::IDP_ENTITY_ATTRIBUTES]['http://macedir.org/entity-category-support'];
                if (in_array('http://refeds.org/category/research-and-scholarship', $entityCategorySupport, true)
                ) {
                    $unset = false;
                }
                if (in_array('http://www.geant.net/uri/dataprotection-code-of-conduct/v1', $entityCategorySupport, true)
                ) {
                    $unset = false;
                }
            }
            if (isset($idp[self::IDP_COCO]) and $idp[self::IDP_COCO] === true) {
                $unset = false;
            }
            if (isset($idp[self::IDP_RAS]) and $idp[self::IDP_RAS] === true) {
                $unset = false;
            }

            if ($unset === true) {
                unset($list[$entityId]);
            }
        }
        return $list;
    }

    /**
     * Filter out IdP which are greylisted
     *
     * @param array $list A map of entities to filter.
     * @param array $greylist The list of greylisted IdPs
     *
     * @return array The list in $list after filtering entities.
     */
    protected static function greylisting(array $list, array $greylist): array
    {
        foreach ($list as $entityId => $idp) {
            if (in_array($entityId, $greylist, true)) {
                unset($list[$entityId]);
            }
        }

        return $list;
    }

    protected function greylistingPerSP($list, $sp): array
    {
        foreach ($list as $entityId => $idp) {
            if (isset($sp[self::SP_GREYLIST])
                && in_array($entityId, $sp[self::SP_GREYLIST], true)
            ) {
                unset($list[$entityId]);
            }
        }

        return $list;
    }

    protected static function showRemainingTaggedEntries(
        DiscoTemplate $t,
        array $idps,
        int $counter,
        int $remainingIdps,
        bool $hasFullRows
    ): string {
        $html = '';
        if ($remainingIdps === 0) {
            return $html;
        }

        if ($hasFullRows > 0) {
            if ($remainingIdps === 2) {
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-2';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-0 col-md-offset-3';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
            } elseif ($remainingIdps === 1) {
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-4';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
                $html .= '</div>' . PHP_EOL;
            }
        } elseif ($remainingIdps === 2) {
            $class = 'col-xs-12 col-md-6';
            $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
            $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
        } elseif ($remainingIdps === 1) {
            $class = 'col-lg-12';
            $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
        }
        return $html;
    }

    private static function addLoginOptionHint(
        DiscoTemplate $t,
        Configuration $blockConfig,
        string $defaultTranslateKey = ''
    ): string {
        $textOn = $blockConfig->getBoolean(self::IDP_BLOCK_TEXT_ENABLED, false);
        $name = $blockConfig->getString(self::IDP_BLOCK_NAME, '');
        $hintTranslate = $blockConfig->getArray(self::IDP_BLOCK_HINT_TRANSLATION, []);
        $hintTranslateKey = ! empty($name) ? '{perun:disco:' . $name . '_hint}' : '';
        if ($textOn && ! empty($hintTranslateKey) && ! empty($hintTranslate)) {
            $t->includeInlineTranslation($hintTranslateKey, $hintTranslate);
            return '<p class="login-option-category-hint">' . $t->t($hintTranslateKey) . '</p>' . PHP_EOL;
        } elseif ($textOn && ! empty($defaultTranslateKey)) {
            return '<p class="login-option-category-hint">' . $t->t($defaultTranslateKey) . '</p>' . PHP_EOL;
        }
        return '';
    }

    private static function addLoginOptionNote(
        DiscoTemplate $t,
        Configuration $blockConfig,
        string $defaultTranslateKey = ''
    ): string {
        $textOn = $blockConfig->getBoolean(self::IDP_BLOCK_TEXT_ENABLED, false);
        $name = $blockConfig->getString(self::IDP_BLOCK_NAME, '');
        $noteTranslate = $blockConfig->getArray(self::IDP_BLOCK_NOTE_TRANSLATION, []);
        $noteTranslateKey = ! empty($name) ? '{perun:disco:' . $name . '_note}' : '';
        if ($textOn && ! empty($noteTranslateKey) && ! empty($noteTranslate)) {
            $t->includeInlineTranslation($noteTranslateKey, $noteTranslate);
            return '<p class="login-option-category-note">' . $t->t($noteTranslateKey) . '</p>' . PHP_EOL;
        } elseif ($textOn && ! empty($defaultTranslateKey)) {
            return '<p class="login-option-category-note">' . $t->t($defaultTranslateKey) . '</p>' . PHP_EOL;
        }
        return '';
    }

    private static function getPlaceholderTranslation(
        DiscoTemplate $t,
        Configuration $blockConfig,
        string $translateKey
    ): string {
        $translate = $blockConfig->getArray(self::IDP_BLOCK_PLACEHOLDER_TRANSLATE, []);
        if (! empty($translate)) {
            $t->includeInlineTranslation($translateKey, $translate);
        }
        return $translateKey;
    }

    private static function boxedDesignScript(): string
    {
        $script = '<script>' . PHP_EOL;
        $script .= '   $("#wrap").css("box-shadow", "0 1rem 3rem 0.5rem rgba(0, 0, 0, .15)");' . PHP_EOL;
        $script .= '</script>';
        return $script;
    }

    private static function arrayFlatten($array): array
    {
        $return = [];
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $return = [...$return, ...self::arrayFlatten($value)];
                } else {
                    $return = [...$return, $value];
                }
            }
        } else {
            $return = [$array];
        }
        return $return;
    }

    private static function constructSearchData($idpMetadata): string
    {
        $res = '';
        $dataSearchKeys = [];
        if (! empty($idpMetadata['UIInfo'])) {
            $idpMetadata = array_merge($idpMetadata, $idpMetadata['UIInfo']);
        }

        $keys = ['entityid', 'OrganizationName', 'OrganizationDisplayName',
            'name', 'url', 'OrganizationURL', 'scope', 'DisplayName', ];

        foreach ($keys as $key) {
            if (! empty($idpMetadata[$key])) {
                $dataSearchKeys = [...$dataSearchKeys, ...self::arrayFlatten($idpMetadata[$key])];
            }
        }
        $res .= (' ' . implode(' ', $dataSearchKeys));

        return strtolower(str_replace('"', '', iconv('UTF-8', 'US-ASCII//TRANSLIT', $res)));
    }
}
