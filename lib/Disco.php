<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Module\discopower\PowerIdPDisco;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Error\Exception;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

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

    const WARNING_TYPE_INFO = 'INFO';
    const WARNING_TYPE_WARNING = 'WARNING';
    const WARNING_TYPE_ERROR = 'ERROR';

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

    /**
     * Handles a request to this discovery service. It is enry point of Discovery service.
     *
     * The IdP disco parameters should be set before calling this function.
     */
    public function handleRequest()
    {
        // test if user has selected an idp or idp can be deremine automatically somehow.
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
    public static function buildContinueUrl(
        $entityID,
        $return,
        $returnIDParam,
        $idpEntityId
    ) {
        $url = '?' .
            'entityID=' . urlencode($entityID) . '&' .
            'return=' . urlencode($return) . '&' .
            'returnIDParam=' . urlencode($returnIDParam) . '&' .
            'idpentityid=' . urlencode($idpEntityId);

        return $url;
    }

    /**
     * @param $entityID
     * @param $return
     * @param $returnIDParam
     * @return string url where user should be redirected when he choose idp
     */
    public static function buildContinueUrlWithoutIdPEntityId(
        $entityID,
        $return,
        $returnIDParam
    ) {
        $url = '?' .
            'entityID=' . urlencode($entityID) . '&' .
            'return=' . urlencode($return) . '&' .
            'returnIDParam=' . urlencode($returnIDParam);

        return $url;
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

    public static function buildEntry(DiscoTemplate $t, $idp, $favourite = false)
    {

        $extra = ($favourite ? 'favourite' : '');
        $html = '<a class="metaentry ' . $extra . ' list-group-item" ' .
                ' href="' . $t->getContinueUrl($idp['entityid']) . '">';

        $html .= '<strong>' . htmlspecialchars($t->getTranslatedEntityName($idp)) . '</strong>';
        $html .= '</a>';

        return $html;
    }

    /**
     * @param DiscoTemplate $t
     * @param array $metadata
     * @param bool $favourite
     * @return string html
     */
    public static function showEntry($t, $metadata, $favourite = false)
    {

        if (isset($metadata['tags']) &&
            (in_array('social', $metadata['tags']) || in_array('preferred', $metadata['tags']))) {
            return self::showTaggedEntry($t, $metadata);
        }

        $extra = ($favourite ? ' favourite' : '');
        $html = '<a class="metaentry' . $extra . ' list-group-item" href="' .
                $t->getContinueUrl($metadata['entityid']) . '">';

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
    public static function showTaggedEntry($t, $metadata, $showSignInWith = false)
    {

        $bck = 'white';
        if (!empty($metadata['color'])) {
            $bck = $metadata['color'];
        }

        $html = '<a class="metaentry btn btn-block tagged" href="' . $t->getContinueUrl($metadata['entityid']) .
                '" style="background: ' . $bck . '">';

        $html .= '<img src="' . $metadata['icon'] . '">';

        if (isset($metadata['fullDisplayName'])) {
            $html .= '<strong>' . $metadata['fullDisplayName'] . '</strong>';
        } elseif ($showSignInWith) {
            $html .= '<strong>' . $t->t('{perun:disco:sign_in_with}') . $t->getTranslatedEntityName($metadata) .
                     '</strong>';
        } else {
            $html .= '<strong>' . $t->getTranslatedEntityName($metadata) . '</strong>';
        }

        $html .= '</a>';

        return $html;
    }

    public static function getOr($id = NULL)
    {
        $or = '';
        if (!is_null($id)) {
            $or .= '<div class="hrline" id="' . $id . '">';
        } else {
            $or .= '<div class="hrline">';
        }
        $or .= '	<span>or</span>';
        $or .= '</div>';
        return $or;
    }

    public static function showAllTaggedIdPs($t)
    {
        $html = '';
        $html .= self::showTaggedIdPs($t, 'preferred');
        $html .= self::showTaggedIdPs($t, 'social', true);
        return $html;
    }


    public static function showTaggedIdPs($t, $tag, $showSignInWith = false)
    {
        $html = '';
        $idps = $t->getIdPs($tag);
        $idpCount = count($idps);
        $counter = 0;

        $fullRowCount = floor($idpCount / 3);
        for ($i = 0; $i < $fullRowCount; $i++) {
            $html .= '<div class="row">';
            for ($j = 0; $j < 3; $j++) {
                $html .= '<div class="col-md-4">';
                $html .= '<div class="metalist list-group">';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $showSignInWith);
                $html .= '</div>';
                $html .= '</div>';
                $counter++;
            }
            $html .= '</div>';
        }

        $remainIdpsCount = ($idpCount - ($fullRowCount * 3)) % 3;
        if ($remainIdpsCount !== 0) {
            $html .= '<div class="row">';
            for ($i = 0; $i < $remainIdpsCount; $i++) {
                $html .= '<div class="' . self::getCssClass($remainIdpsCount) . '">';
                $html .= '<div class="metalist list-group">';
                $html .= self::showTaggedEntry($t, $idps[array_keys($idps)[$counter]], $showSignInWith);
                $html .= '</div>';
                $html .= '</div>';
                $counter++;
            }
            $html .= '</div>';
        }

        return $html;
    }

    public static function showWarning($warningType, $warningTitle, $warningText)
    {
        $html = '';
        if ($warningType === WARNING_TYPE_INFO) {
            $html .= '<div class="alert alert-info">';
        } elseif ($warningType === WARNING_TYPE_WARNING) {
            $html .= '<div class="alert alert-warning">';
        } elseif ($warningType === WARNING_TYPE_ERROR) {
            $html .='<div class="alert alert-danger">';
        }
        $html .= '<h4> <strong>' . $warningTitle . '</strong> </h4>';
        $html .= $warningText;
        $html .= '</div>';

        return $html;
    }


    protected static function getCssClass($remainIdpsCount)
    {
        if ($remainIdpsCount === 1) {
            return 'col-md-4 col-md-offset-4';
        }
        return 'col-md-6';
    }

    public static function showEntriesScript()
    {
        $script = '<script type="text/javascript">
         $(document).ready(function() {
             $("#showEntries").click(function() {
                 $("#last-used-idp").hide();
                 $("#last-used-idp-desc").hide();
                 $("#last-used-idp-or").hide();
                 $("#entries").show();
                 $("#showEntries").hide();
             });
         });
        </script>';
        return $script;
    }

    public static function searchScript()
    {

        $script = '<script type="text/javascript">

        $(document).ready(function() { 
            $("#query").liveUpdate("#list");
        });
        
        </script>';

        return $script;
    }

    public static function setFocus()
    {
        $script = '<script type="text/javascript">

        $(document).ready(function() {
            if ($("#last-used-idp")) {
                $("#last-used-idp .metaentry").focus();
            }
        });
        
        </script>';

        return $script;
    }

}
