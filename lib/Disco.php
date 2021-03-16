<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Module\discopower\PowerIdPDisco;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Error\Exception;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;

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
    const PROPNAME_DISABLE_WHITELISTING = 'disco.disableWhitelisting';
    const PROPNAME_PREFIX = 'disco.removeAuthnContextClassRefPrefix';

    const DEFAULT_THEME = 'perun';

    const WARNING_TYPE_INFO = 'INFO';
    const WARNING_TYPE_WARNING = 'WARNING';
    const WARNING_TYPE_ERROR = 'ERROR';
    const C_HINT_TRANSLATION_KEY = 'hintTranslationKey';
    const C_NOTE_TRANSLATION_KEY = 'noteTranslationKey';
    const C_PLACEHOLDER_TRANSLATION_KEY = 'placeholderTranslationKey';
    const C_TEXT_ON = 'textOn';
    const C_TAGS = 'tags';
    const C_ENTITY_IDS = 'entityIds';

    private $originalsp;
    private $whitelist;
    private $greylist;
    private $service;
    private $authnContextClassRef = [];

    public function __construct(array $metadataSets, $instance)
    {
        if (!array_key_exists('return', $_GET)) {
            throw new \Exception('Missing parameter: return');
        } else {
            $returnURL = HTTP::checkURLAllowed($_GET['return']);
        }

        parse_str(parse_url($returnURL)['query'], $query);

        if (isset($query['AuthID'])) {
            $id = explode(":", $query['AuthID'])[0];
            $state = State::loadState($id, 'saml:sp:sso', true);

            if ($state !== null) {
                if (isset($state['saml:RequestedAuthnContext']['AuthnContextClassRef'])) {
                    $this->authnContextClassRef = $state['saml:RequestedAuthnContext']['AuthnContextClassRef'];
                    $this->removeAuthContextClassRefWithPrefix($state);
                }

                $id = State::saveState($state, 'saml:sp:sso');

                $e = explode("=", $returnURL)[0];
                $newReturnURL = $e . "=" . urlencode($id);
                $_GET['return'] = $newReturnURL;
            }
        }

        parent::__construct($metadataSets, $instance);

        if (isset($state) && isset($state['SPMetadata'])) {
            $this->originalsp = $state['SPMetadata'];
        }
    }

    protected static function boxedDesignScript(): string
    {
        $script = '<script>' . PHP_EOL;
        $script .= '   $("#wrap").css("box-shadow", "0 1rem 3rem 0.5rem rgba(0, 0, 0, .15)");' . PHP_EOL;
        $script .= '</script>';
        return $script;
    }

    public static function getScripts(bool $boxed)
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

    /**
     * Handles a request to this discovery service. It is enry point of Discovery service.
     *
     * The IdP disco parameters should be set before calling this function.
     */
    public function handleRequest()
    {
        // test if user has selected an idp or idp can be determined automatically somehow.
        $this->start();

        // no choice possible. Show discovery service page
        $idpList = $this->getIdPList();
        if (isset($this->originalsp['disco.addInstitutionApp'])
            && $this->originalsp['disco.addInstitutionApp'] === true
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

        try {
            $warningInstance = WarningConfiguration::getInstance();
            $warningAttributes = $warningInstance->getWarningAttributes();
        } catch (Exception $ex) {
            $warningAttributes = [
                'warningIsOn' => false,
                'warningType' => '',
                'warningTitle' => '',
                'warningText' => ''
            ];
        }

        $t = new DiscoTemplate($this->config);
        $t->data['originalsp'] = $this->originalsp;
        $t->data['idplist'] = $this->idplistStructured($idpList);
        $t->data['preferredidp'] = $preferredIdP;
        $t->data['entityID'] = $this->spEntityId;
        $t->data['return'] = $this->returnURL;
        $t->data['returnIDParam'] = $this->returnIdParam;
        $t->data['AuthnContextClassRef'] = $this->authnContextClassRef;
        $t->data['warningIsOn'] = $warningAttributes['warningIsOn'];
        $t->data['warningType'] = $warningAttributes['warningType'];
        $t->data['warningTitle'] = $warningAttributes['warningTitle'];
        $t->data['warningText'] = $warningAttributes['warningText'];
        $t->show();
    }

    /**
     * Filter a list of entities according to any filters defined in the parent class, plus
     *
     * @param array $list A map of entities to filter.
     * @return array The list in $list after filtering entities.
     * @throws Exception if all IdPs are filtered out and no one left.
     */
    protected function filterList($list)
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $disableWhitelisting
            = $conf->getBoolean(self::PROPNAME_DISABLE_WHITELISTING, false);

        if (!isset($this->originalsp['disco.doNotFilterIdps'])
            || !$this->originalsp['disco.doNotFilterIdps']
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
    public static function doFilter($list, $disableWhitelisting = false, $scopedIdPList = [])
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
    protected function filterAddInstitutionList($list)
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
    protected static function scoping($list, $scopedIDPList)
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
    protected static function whitelisting($list, $whitelist)
    {
        foreach ($list as $entityId => $idp) {
            $unset = true;

            if (in_array($entityId, $whitelist)) {
                $unset = false;
            }
            if (isset($idp['EntityAttributes']['http://macedir.org/entity-category-support'])) {
                $entityCategorySupport
                    = $idp['EntityAttributes']['http://macedir.org/entity-category-support'];
                if (in_array('http://refeds.org/category/research-and-scholarship', $entityCategorySupport)
                ) {
                    $unset = false;
                }
                if (in_array('http://www.geant.net/uri/dataprotection-code-of-conduct/v1', $entityCategorySupport)
                ) {
                    $unset = false;
                }
            }
            if (isset($idp['CoCo']) and $idp['CoCo'] === true) {
                $unset = false;
            }
            if (isset($idp['RaS']) and $idp['RaS'] === true) {
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
    protected static function greylisting($list, $greylist)
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
            if (isset($sp['greylist'])
                && in_array($entityId, $sp['greylist'])
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
     */
    public function removeAuthContextClassRefWithPrefix(&$state)
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $prefix = $conf->getString(self::PROPNAME_PREFIX, null);

        if ($prefix === null) {
            return;
        }
        unset($state['saml:RequestedAuthnContext']['AuthnContextClassRef']);
        $array = [];
        foreach ($this->authnContextClassRef as $value) {
            if (!(substr($value, 0, strlen($prefix)) === $prefix)) {
                array_push($array, $value);
            }
        }
        if (!empty($array)) {
            $state['saml:RequestedAuthnContext']['AuthnContextClassRef']
                = $array;
        }
    }

    /**
     * @param DiscoTemplate $t
     * @param array $metadata
     * @param bool $favourite
     * @return string html
     */
    public static function showEntry(DiscoTemplate $t, $metadata, $favourite = false): string
    {
        $extra = ($favourite ? ' favourite' : '');
        $href = $t->getContinueUrl($metadata['entityid']);
        $html = '<a class="metaentry' . $extra . ' list-group-item" href="' . $href. '">';
        $html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';
        $html .= '</a>';

        return $html;
    }

    /**
     * @param DiscoTemplate $t
     * @param array $metadata
     * @param bool $showSignInWith
     *
     * @return string html
     */
    public static function showTaggedEntry(DiscoTemplate $t, $metadata, $showSignInWith = false): string
    {
        if (!array_key_exists('tags', $metadata)) {
            return Disco::showEntry($t, $metadata);
        }
        $bck = 'white';
        if (!empty($metadata['color'])) {
            $bck = $metadata['color'];
        }

        $href = $t->getContinueUrl($metadata['entityid']);
        $text = '';
        if (isset($metadata['fullDisplayName'])) {
            $text = $metadata['fullDisplayName'];
        } elseif ($showSignInWith) {
            $text = $t->t('{perun:disco:sign_in_with}') . $t->getTranslatedEntityName($metadata);
        } else {
            $text .= $t->getTranslatedEntityName($metadata);
        }
        $html = '<a class="metaentry btn btn-block tagged" href="' . $href . '" style="background: ' . $bck . '">';
        $html .= '<img src="' . $metadata['icon'] . '">' . PHP_EOL;
        $html .= '<strong>' . $text . '</strong></a>';

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
        $tags = $blockConfig[self::C_TAGS];
        foreach ($tags as $tag) {
            $idps = array_merge($idps, $t->getIdPs($tag));
        }
        $entityIds = $blockConfig[self::C_ENTITY_IDS];
        $allIdps = $t->getAllIdps();
        foreach ($entityIds as $entityId) {
            array_push($idps, $allIdps[$entityId]);
        }
        $idpCount = count($idps);
        $textOn = $blockConfig[self::C_TEXT_ON];
        $hintTranslateKey = array_key_exists(self::C_HINT_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::C_HINT_TRANSLATION_KEY] : '';
        $noteTranslateKey = array_key_exists(self::C_NOTE_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::C_NOTE_TRANSLATION_KEY] : '';
        if ($textOn && strlen(trim($hintTranslateKey)) > 0) {
            $html .= '<p class="login-option-category-hint">'. $t->t('{' . $hintTranslateKey . '}') . '</p>' . PHP_EOL;
        }
        $html .= '<div class="row">' . PHP_EOL;

        for ($i = 0; $i < $idpCount; $i++) {
            $html .= '    <div class="col-md-12">' . PHP_EOL;
            $html .= '        <div class="metalist list-group">' . PHP_EOL;
            $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$i]]);
            $html .= '        </div>' . PHP_EOL;
            $html .= '    </div>' . PHP_EOL;
        }

        $html .= '</div>' . PHP_EOL;
        if ($textOn && strlen(trim($noteTranslateKey)) > 0) {
            $html .= '<p class="login-option-category-note">'. $t->t('{' . $noteTranslateKey . '}') . '</p>' . PHP_EOL;
        }

        return $html;
    }

    public static function showWarning($warningType, $warningTitle, $warningText): string
    {
        $html = '';
        if ($warningType === Disco::WARNING_TYPE_INFO) {
            $html .= '<div class="alert alert-info">';
        } elseif ($warningType === Disco::WARNING_TYPE_WARNING) {
            $html .= '<div class="alert alert-warning">';
        } elseif ($warningType === Disco::WARNING_TYPE_ERROR) {
            $html .='<div class="alert alert-danger">';
        }
        $html .= '<h4> <strong>' . $warningTitle . '</strong> </h4>';
        $html .= $warningText;
        $html .= '</div>';

        return $html;
    }

    public static function showInlineSearch(DiscoTemplate $t, $blockConfig, $addInstitutionEmail,
                                            $addInstitutionUrl): string
    {
        $result = '';
        $allIdps = $t->getAllIdps();
        $isAddInstitutionApp = $t->isAddInstitutionApp();
        $textOn = $blockConfig[self::C_TEXT_ON];
        $hintTranslateKey = array_key_exists(self::C_TEXT_ON, $blockConfig) ?
            $blockConfig[self::C_HINT_TRANSLATION_KEY] : 'perun:disco:institution_search_hint';
        $placeholderTranslateKey = array_key_exists(self::C_PLACEHOLDER_TRANSLATION_KEY, $blockConfig) ?
            $blockConfig[self::C_PLACEHOLDER_TRANSLATION_KEY] : 'perun:disco:institution_search_input_placeholder';

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
            $result .= '        ' . $t->t('{perun:disco:institution_search_no_entries_contact_us}') .
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

    public static function getTranslate($t, $module, $file, $key)
    {
        $translate = $t->t('{' . $module . ':' . $file . ':' . $key . '}');
        if (str_starts_with(trim($translate), 'not translated')) {
            $translate = $t->t('{' . self::DEFAULT_THEME . ':' . $file . ':' . $key . '}');
        }
        return $translate;
    }

}
