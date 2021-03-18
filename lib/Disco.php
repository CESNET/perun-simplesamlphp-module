<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Module\discopower\PowerIdPDisco;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Error\Exception;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\model\WarningConfiguration;

/**
 * This class implements a IdP discovery service.
 *
 * This module extends the DiscoPower IdP disco handler, so it needs to be avaliable and enabled and configured.
 *
 * It adds functionality of whitelisting and greylisting IdPs.
 * for security reasons for blacklisting please manipulate directly with metadata. In case of manual idps
 * comment them out or in case of automated metadata fetching configure blacklist in config-metarefresh.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Disco extends PowerIdPDisco
{
    const CONFIG_FILE_NAME = 'module_perun.php';
    const URN_CESNET_PROXYIDP_IDPENTITYID = 'urn:cesnet:proxyidp:idpentityid:';

    const DEFAULT_THEME = 'perun';

    # ROOT CONFIGURATION ENTRY
    const WAYF = 'wayf';
    # CONFIGURATION ENTRIES
    const BOXED = 'boxed';
    # CONFIGURATION ENTRIES IDP BLOCKS
    const BLOCKS = 'blocks';
    const BLOCK_TYPE = 'type';
    const BLOCK_TYPE_INLINESEARCH = "inlinesearch";
    const BLOCK_TYPE_TAGGED = "tagged";
    const BLOCK_TEXT_ON = 'text_enabled';
    const BLOCK_HINT_TRANSLATION_KEY = 'hint_translation_key';
    const BLOCK_NOTE_TRANSLATION_KEY = 'note_translation_key';
    const BLOCK_PLACEHOLDER_TRANSLATION_KEY = 'placeholder_translation_key';
    const BLOCK_TAGS = 'tags';
    const BLOCK_ENTITY_IDS = 'entity_ids';
    # CONFIGURATION ENTRIES ADD INSTITUTION
    const ADD_INSTITUTION = 'add_institution';
    const ADD_INSTITUTION_URL = 'url';
    const ADD_INSTITUTION_EMAIL = 'email';
    const TRANSLATE_MODULE = 'translate_module';
    const REMOVE_AUTHN_CONTEXT_CLASS_PREFIX = 'remove_authn_context_class_ref_prefix';
    const DISABLE_WHITELISTING = 'disable_whitelisting';

    # PARAMS AND DATA KEYS
    const ENTITY_ID = "entityID";
    const RETURN = "return";
    const RETURN_ID_PARAM = "returnIDParam";
    const ORIGINAL_SP = "originalsp";
    const IDP_LIST = "idplist";
    const PREFERRED_IDP = "preferredidp";
    const AUTHN_CONTEXT_CLASS_REF = 'AuthnContextClassRef';
    const WARNING_ATTRIBUTES = 'warningAttributes';
    const AUTH_ID = 'AuthID';

    # METADATA KEYS
    const METADATA_DO_NOT_FILTER_IDPS = 'disco.doNotFilterIdps';
    const METADATA_ADD_INSTITUTION_APP = 'disco.addInstitutionApp';
    const IDP_ENTITY_ATTRIBUTES = 'EntityAttributes';
    const IDP_COCO = 'CoCo';
    const IDP_RAS = 'RaS';
    const SP_GREYLIST = 'greylist';
    const IDP_ENTITY_ID = 'entityid';
    const IDP_COLOR = 'color';
    const IDP_FULL_DISPLAY_NAME = 'fullDisplayName';
    const IDP_SHOW_SIGN_IN_WITH = 'showSignInWith';

    # STATE KEYS
    const STATE_SP_METADATA = 'SPMetadata';
    const SAML_REQUESTED_AUTHN_CONTEXT = 'saml:RequestedAuthnContext';
    const STATE_AUTHN_CONTEXT_CLASS_REF = 'AuthnContextClassRef';
    const SAML_SP_SSO = 'saml:sp:sso';

    private $originalsp;
    private array $authnContextClassRef = [];

    public function __construct(array $metadataSets, $instance)
    {
        if (!array_key_exists(self::RETURN, $_GET)) {
            throw new \Exception('Missing parameter: ' . self::RETURN);
        } else {
            $returnURL = HTTP::checkURLAllowed($_GET[self::RETURN]);
        }

        parse_str(parse_url($returnURL)['query'], $query);

        if (isset($query[self::AUTH_ID])) {
            $id = explode(":", $query[self::AUTH_ID])[0];
            $state = State::loadState($id, self::SAML_SP_SSO, true);

            if ($state !== null) {
                if (isset($state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::AUTHN_CONTEXT_CLASS_REF])) {
                    $this->authnContextClassRef = $state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::AUTHN_CONTEXT_CLASS_REF];
                    $this->removeAuthContextClassRefWithPrefix($state);
                }

                $id = State::saveState($state, self::SAML_SP_SSO);

                $e = explode("=", $returnURL)[0];
                $newReturnURL = $e . "=" . urlencode($id);
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
        if (isset($this->originalsp[Disco::METADATA_ADD_INSTITUTION_APP])
            && $this->originalsp[Disco::METADATA_ADD_INSTITUTION_APP] === true
        ) {
            $idpList = $this->filterAddInstitutionList($idpList);
        } else {
            $idpList = $this->filterList($idpList);
        }
        $preferredIdP = $this->getRecommendedIdP();
        $preferredIdP = array_key_exists($preferredIdP, $idpList)
            ? $preferredIdP : null;

        if (sizeof($idpList) === 1) {
            $idp = array_keys($idpList)[0];
            $url = Disco::buildContinueUrl(
                $this->spEntityId,
                $this->returnURL,
                $this->returnIdParam,
                $idp
            );
            Logger::info('perun.Disco: Only one Idp left. Redirecting automatically. IdP: '
                . $idp);
            HTTP::redirectTrustedURL($url);
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
        $t->data[self::AUTHN_CONTEXT_CLASS_REF] = $this->authnContextClassRef;
        $t->data[self::WARNING_ATTRIBUTES] = $warningAttributes;
        $t->show();
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
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $disableWhitelisting = $conf->getBoolean(self::DISABLE_WHITELISTING, false);

        if (!isset($this->originalsp[Disco::METADATA_DO_NOT_FILTER_IDPS])
            || !$this->originalsp[Disco::METADATA_DO_NOT_FILTER_IDPS]
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
     * Filter out IdP which:
     *  1. are not in SAML2 Scoping attribute list (SAML2 feature)
     *  2. are not whitelisted (if whitelisting is allowed)
     *  3. are greylisted
     *
     * @param array $list A map of entities to filter.
     * @param bool $disableWhitelisting
     * @param array $scopedIdPList
     *
     * @return array The list in $list after filtering entities.
     * @throws Exception In case
     */
    public static function doFilter(array $list, $disableWhitelisting = false, $scopedIdPList = []): array
    {
        $service = IdpListsService::getInstance();
        $whitelist = $service->getWhitelistEntityIds();
        $greylist = $service->getGreylistEntityIds();

        $list = self::scoping($list, $scopedIdPList);
        if (!$disableWhitelisting) {
            $list = self::whitelisting($list, $whitelist);
        }
        $list = self::greylisting($list, $greylist);

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
            if (in_array($entityId, $whitelist)) {
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
     * @param array $scopedIDPList
     *
     * @return array The list in $list after filtering entities.
     */
    protected static function scoping(array $list, array $scopedIDPList): array
    {
        if (!empty($scopedIDPList)) {
            foreach ($list as $entityId => $idp) {
                if (!in_array($entityId, $scopedIDPList)) {
                    unset($list[$entityId]);
                }
            }
        }
        return $list;
    }

    /**
     * Filter out IdP which:
     *  1. are not whitelisted
     *  2. are not supported research and scholarship
     *  3. are not supported code of conduct
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

            if (in_array($entityId, $whitelist)) {
                $unset = false;
            }
            if (isset($idp[self::IDP_ENTITY_ATTRIBUTES]['http://macedir.org/entity-category-support'])) {
                $entityCategorySupport
                    = $idp[self::IDP_ENTITY_ATTRIBUTES]['http://macedir.org/entity-category-support'];
                if (in_array('http://refeds.org/category/research-and-scholarship', $entityCategorySupport)
                ) {
                    $unset = false;
                }
                if (in_array('http://www.geant.net/uri/dataprotection-code-of-conduct/v1', $entityCategorySupport)
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
            if (in_array($entityId, $greylist)) {
                unset($list[$entityId]);
            }
        }

        return $list;
    }

    protected function greylistingPerSP($list, $sp)
    {
        foreach ($list as $entityId => $idp) {
            if (isset($sp[self::SP_GREYLIST])
                && in_array($entityId, $sp[self::SP_GREYLIST])
            ) {
                unset($list[$entityId]);
            }
        }

        return $list;
    }

    /**
     * @param $entityID
     * @param $return
     * @param $returnIDParam
     * @param $idpEntityId
     * @return string url where user should be redirected when he choose idp
     */
    public static function buildContinueUrl($entityID, $return, $returnIDParam, $idpEntityId): string
    {
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
    public static function buildContinueUrlWithoutIdPEntityId($entityID, $return, $returnIDParam): string
    {
        return '?' .
            'entityID=' . urlencode($entityID) . '&' .
            'return=' . urlencode($return) . '&' .
            'returnIDParam=' . urlencode($returnIDParam);
    }

    /**
     * This method remove all AuthnContextClassRef which start with prefix from configuration
     * @param $state
     * @throws \Exception
     */
    public function removeAuthContextClassRefWithPrefix(&$state)
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $prefix = $conf->getString(self::REMOVE_AUTHN_CONTEXT_CLASS_PREFIX, null);

        if ($prefix === null) {
            return;
        }
        unset($state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::STATE_AUTHN_CONTEXT_CLASS_REF]);
        $array = [];
        foreach ($this->authnContextClassRef as $value) {
            if (!(substr($value, 0, strlen($prefix)) === $prefix)) {
                array_push($array, $value);
            }
        }
        if (!empty($array)) {
            $state[self::SAML_REQUESTED_AUTHN_CONTEXT][self::STATE_AUTHN_CONTEXT_CLASS_REF]
                = $array;
        }
    }

    /**
     * @param DiscoTemplate $t
     * @param array $metadata
     * @param bool $favourite
     * @return string html
     */
    public static function showEntry(DiscoTemplate $t, array $metadata, $favourite = false): string
    {
        $extra = ($favourite ? ' favourite' : '');
        $href = $t->getContinueUrl($metadata[self::IDP_ENTITY_ID]);
        $html = '<a class="metaentry' . $extra . ' list-group-item" href="' . $href. '">';
        $html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';
        $html .= '</a>';

        return $html;
    }

    /**
     * @param DiscoTemplate $t
     * @param array $metadata
     * @param string $class
     *
     * @return string html
     */
    public static function showTaggedEntry(DiscoTemplate $t, array $metadata, $class = ''): string
    {
        if (!array_key_exists('tags', $metadata)) {
            return Disco::showEntry($t, $metadata);
        }
        $bck = 'white';
        if (!empty($metadata[self::IDP_COLOR])) {
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

    public static function getOr($id = NULL): string
    {
        $or = '';
        if (!is_null($id)) {
            $or .= '<div class="hrline" id="' . $id . '">'  . PHP_EOL;
        } else {
            $or .= '<div class=" hrline">'  . PHP_EOL;
        }
        $or .= '    <span>or</span>' . PHP_EOL;
        $or .= '</div>';
        return $or;
    }

    public static function showTaggedIdPs(DiscoTemplate $t, $blockConfig): string
    {
        $html = '';
        $idps = [];
        $tags = $blockConfig[self::BLOCK_TAGS];
        foreach ($tags as $tag) {
            $idps = array_merge($idps, $t->getIdPs($tag));
        }
        $entityIds = $blockConfig[self::BLOCK_ENTITY_IDS];
        $allIdps = $t->getAllIdps();
        foreach ($entityIds as $entityId) {
            array_push($idps, $allIdps[$entityId]);
        }
        $idpCount = count($idps);
        $textOn = $blockConfig[self::BLOCK_TEXT_ON];
        $hintTranslateKey = array_key_exists(self::BLOCK_HINT_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::BLOCK_HINT_TRANSLATION_KEY] : '';
        $noteTranslateKey = array_key_exists(self::BLOCK_NOTE_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::BLOCK_NOTE_TRANSLATION_KEY] : '';
        if ($textOn && strlen(trim($hintTranslateKey)) > 0) {
            $html .= '<p class="login-option-category-hint">'. $t->t('{' . $hintTranslateKey . '}') . '</p>' . PHP_EOL;
        }
        $html .= '<div class="row">' . PHP_EOL;

        $counter = 0;
        $fullRows = floor($idpCount / 3);
        $remainingIdps = $idpCount % 3;

        $class = 'col-xs-12 col-md-6 col-lg-4';
        for ($i = 0; $i < $fullRows; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($remainingIdps === 0 && $counter === ($idpCount-1)) {
                    $class .= ' col-md-offset-3 col-lg-offset-0';
                }
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
            }
        }

        $html .= Disco::showRemainingTaggedEntries($t, $idps, $counter, $remainingIdps, $fullRows > 0);

        $html .= '</div>' . PHP_EOL;
        if ($textOn && strlen(trim($noteTranslateKey)) > 0) {
            $html .= '<p class="login-option-category-note">'. $t->t('{' . $noteTranslateKey . '}') . '</p>' . PHP_EOL;
        }

        return $html;
    }

    protected static function showRemainingTaggedEntries($t, $idps, $counter, $remainingIdps, $hasFullRows): string
    {
        $html = '';
        if ($remainingIdps == 0) {
            return $html;
        }

        if ($hasFullRows > 0) {
            if ($remainingIdps == 2) {
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-2';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-0 col-md-offset-3';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
            } else if ($remainingIdps == 1) {
                $class = 'col-xs-12 col-md-6 col-lg-4 col-lg-offset-4';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
                $html .= '</div>' . PHP_EOL;
            }
        } else {
            if ($remainingIdps == 2) {
                $class = 'col-xs-12 col-md-6';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter++]], $class);
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
            } else if ($remainingIdps == 1) {
                $class = 'col-lg-12';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $class);
            }
        }
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

        $html .= '<h4><strong>' .  $t->t('{perun:disco:warning_title}') . '</strong></h4>' . PHP_EOL;
        $html .= $t->t('{perun:disco:warning_text}');
        $html .= '</div>';
        return $html;
    }

    public static function showInlineSearch(DiscoTemplate $t, $blockConfig, $addInstitutionEmail,
                                            $addInstitutionUrl): string
    {
        $result = '';
        $allIdps = $t->getAllIdps();
        $isAddInstitutionApp = $t->isAddInstitutionApp();
        $textOn = $blockConfig[self::BLOCK_TEXT_ON];
        $hintTranslateKey = array_key_exists(self::BLOCK_TEXT_ON, $blockConfig) ?
            $blockConfig[self::BLOCK_HINT_TRANSLATION_KEY] : 'perun:disco:institution_search_hint';
        $placeholderTranslateKey = array_key_exists(self::BLOCK_PLACEHOLDER_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::BLOCK_PLACEHOLDER_TRANSLATION_KEY] : 'perun:disco:institution_search_input_placeholder';

        if ($textOn) {
            $result .= '<p class="login-option-category-hint">'. $t->t('{' . $hintTranslateKey . '}') .'</p>' . PHP_EOL;
        }
        $result .= '<div class="inlinesearch">' . PHP_EOL;
        $result .= '    <form id="idpselectform" action="?" method="get">' . PHP_EOL;
        $result .= '        <input class="inlinesearchf form-control input-lg" type="text" value="" name="query" id="query"
                               autofocus oninput="$(\'#list\').show();" placeholder="'
            . $t->t('{' . $placeholderTranslateKey . '}') . '"/>' . PHP_EOL;
        $result .= '    </form>';
        # ENTRIES
        $result .= '    <div class="metalist list-group" id="list" style="display: none">' . PHP_EOL;
        foreach ($allIdps as $idpentry) {
            $result .= Disco::showEntry($t, $idpentry) . PHP_EOL;
        }
        $result .= '    </div>' . PHP_EOL;
        # TOO MUCH ENTRIES BLOCK
        $result .= '    <div id="warning-entries" class="alert alert-info entries-warning-block">' . PHP_EOL;
        $result .= '        ' . $t->t('{perun:disco:institution_search_display_too_much_entries_header}',
                [ '<COUNT_HTML>' => '<span id="results-cnt">0</span>' ]) . PHP_EOL;
        $result .= '        <div class="col">' . PHP_EOL;
        $result .= '            <button class="btn btn-block btn-info" id="warning-entries-btn-force-show">';
        $result .= ' ' . $t->t('{perun:disco:institution_search_display_too_much_entries_btn}') . '</button>' . PHP_EOL;
        $result .= '        </div>' . PHP_EOL;
        $result .= '    </div>' . PHP_EOL;
        # NO ENTRIES BLOCK
        $result .= '    <div id="no-entries" class="no-idp-found alert alert-info entries-warning-block">' . PHP_EOL;
        if ($isAddInstitutionApp) {
            $result .= '        ' . $t->t('{perun:disco:add_institution_no_entries_contact_us}') .
                ' <a href="mailto:' . $addInstitutionEmail . '?subject=Request%20for%20adding%20new%20IdP">' .
                $addInstitutionEmail . '</a>' . PHP_EOL;
        } else {
            $result .= '       ' . $t->t('{perun:disco:institution_search_no_entries_header}') . '<br/>' . PHP_EOL;
            $result .= '       ' . $t->t('{perun:disco:institution_search_no_entries_add_institution_text}') .
                ' <a class="btn btn-info" href="' . $addInstitutionUrl . '">' .
                $t->t('{perun:disco:institution_search_no_entries_add_institution_btn}') . '</a>.' . PHP_EOL;
        }
        $result .= '    </div>' . PHP_EOL;
        $result .= '</div>';

        return $result;
    }


    private static function boxedDesignScript(): string
    {
        $script = '<script>' . PHP_EOL;
        $script .= '   $("#wrap").css("box-shadow", "0 1rem 3rem 0.5rem rgba(0, 0, 0, .15)");' . PHP_EOL;
        $script .= '</script>';
        return $script;
    }

    public static function getScripts(bool $boxed): string
    {
        $html = '<script type="text/javascript" src="' .
            Module::getModuleUrl('discopower/assets/js/suggest.js') . '"></script>' . PHP_EOL;
        $html .= '<script type="text/javascript" src="' .
            Module::getModuleUrl('perun/res/js/disco.js') . '"></script>' . PHP_EOL;
        if ($boxed) {
            $html .= Disco::boxedDesignScript() . PHP_EOL;
        }
        return $html;
    }

    public static function getTranslate($t, $module, $file, $key)
    {
        $translate = $t->t('{' . $module . ':' . $file . ':' . $key . '}');
        if (str_starts_with(trim($translate), 'not translated')) {
            $translate = $t->t('{' . self::DEFAULT_THEME . ':' . $file . ':' . $key . '}');
        }
        return $translate;
    }

}
