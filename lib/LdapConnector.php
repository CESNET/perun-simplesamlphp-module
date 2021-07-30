<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Provides interface to get info from Perun Ldap. Configuration file 'module_perun.php' should be placed in default
 * config folder of SimpleSAMLphp. Example of file is in config-template folder.
 *
 * Example Usage: $user = new LdapConnector(ldapHostname, $ldapUser, $ldapPassword)->searchForEntity( "ou=People,
 * dc=perun, dc=cesnet, dc=cz", "(eduPersonPrincipalNames=$uid)", ["perunUserId", "displayName", "cn", "preferredMail",
 * "mail"] );
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class LdapConnector
{
    private $hostname;

    private $user;

    private $password;

    private $enableTLS;

    /**
     * LdapConnector constructor.
     *
     * @param $hostname
     * @param $user
     * @param $password
     * @param $enableTLS
     */
    public function __construct($hostname, $user, $password, $enableTLS = false)
    {
        $this->hostname = $hostname;
        $this->user = $user;
        $this->password = $password;
        $this->enableTLS = $enableTLS;
    }

    /**
     * @param string $base
     * @param string $filter
     * @param array|null $attrNames attributes to be returned. If null all attrs are returned.
     * @return array associative array where key is attribute name and value is array of values, entity or null
     * @throws Exception if result contains more than one entity
     */
    public function searchForEntity($base, $filter, $attrNames = null)
    {
        $entries = self::search($base, $filter, $attrNames);

        if (empty($entries)) {
            Logger::debug(
                'sspmod_perun_LdapConnector.searchForEntity - No entity found. Returning \'null\'. ' .
                'query base: ' . $base . ', filter: ' . $filter . '"'
            );
            return null;
        }

        if (sizeof($entries) > 1) {
            throw new Exception(
                'sspmod_perun_LdapConnector.searchForEntity - More than one entity found. ' .
                'query base: ' . $base . ', filter: ' . $filter . '.' .
                'Hint: Use method searchForEntities if you expect array of entities.'
            );
        }

        return $entries[0];
    }

    /**
     * @param string $base
     * @param string $filter
     * @param array $attrNames attributes to be returned. If null all attrs are returned.
     * @return array of entities. Each entity is associative array.
     */
    public function searchForEntities($base, $filter, $attrNames = null)
    {
        $entries = self::search($base, $filter, $attrNames);

        if (empty($entries)) {
            Logger::debug(
                'sspmod_perun_LdapConnector.searchForEntity - No entities found. Returning empty array. ' .
                'query base: ' . $base . ', filter: ' . $filter
            );
            return $entries;
        }

        return $entries;
    }

    protected function search($base, $filter, $attributes = null)
    {
        $conn = ldap_connect($this->hostname);
        if ($conn === false) {
            throw new Exception('Unable to connect to the Perun LDAP, ' . $this->hostname);
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Enable TLS, if needed
        if ($this->enableTLS && stripos($this->hostname, 'ldaps:') === false) {
            if (! @ldap_start_tls($conn)) {
                throw new Exception('Unable to force TLS on Perun LDAP');
            }
        }

        if (ldap_bind($conn, $this->user, $this->password) === false) {
            throw new Exception('Unable to bind user to the Perun LDAP, ' . $this->hostname);
        }

        Logger::debug('sspmod_perun_LdapConnector.search - Connection to Perun LDAP established. ' .
            'Ready to perform search query. host: ' . $this->hostname . ', user: ' . $this->user);

        $startTime = microtime(true);
        $result = ldap_search($conn, $base, $filter, $attributes);
        $endTime = microtime(true);

        $responseTime = round(($endTime - $startTime) * 1000, 3);

        // no such entity
        if (ldap_errno($conn) === 2) {
            return [];
        }

        $entries = self::getSimplifiedEntries($conn, $result);

        ldap_close($conn);

        Logger::debug('sspmod_perun_LdapConnector.search - search query proceeded in ' . $responseTime . 'ms. ' .
            'Query base: ' . $base . ', filter: ' . $filter . ', response: ' . json_encode($entries));

        return $entries;
    }

    /**
     * remove unnecessary meta information from entry (e.g. 'count' field) and simplify entry structure
     *
     * @param $conn
     * @return array associative array where key is attr name and value is array of attr values.
     */
    private static function getSimplifiedEntries($conn, $resultId)
    {
        $entries = [];

        $entryId = ldap_first_entry($conn, $resultId);
        while ($entryId) {
            $entry = [];

            $attrName = ldap_first_attribute($conn, $entryId);
            while ($attrName) {
                $values = ldap_get_values($conn, $entryId, $attrName);

                unset($values['count']);

                $entry[$attrName] = $values;
                $attrName = ldap_next_attribute($conn, $entryId);
            }

            array_push($entries, $entry);
            $entryId = ldap_next_entry($conn, $entryId);
        }

        return $entries;
    }
}
