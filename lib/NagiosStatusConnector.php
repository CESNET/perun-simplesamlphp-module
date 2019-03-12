<?php

/**
 * Class sspmod_perun_NagiosStatusConnector
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_NagiosStatusConnector extends sspmod_perun_StatusConnector
{
    const NAGIOS_URL = "status.nagios.url";
    const NAGIOS_CERT_PATH = "status.nagios.certificate_path";
    const NAGIOS_CERT_PASSWORD = "status.nagios.certificate_password";
    const NAGIOS_CA_PATH = "status.nagios.ca_path";
    const NAGIOS_PEER_VERIFY = "status.nagios.peer_verification";

    private $url;
    private $certPath;
    private $certPassword;
    private $caPath;
    private $peerVerification;

    /**
     * NagiosStatusConnector constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->url = $this->configuration->getString(self::NAGIOS_URL, "");
        $this->certPath = $this->configuration->getString(self::NAGIOS_CERT_PATH, "");
        $this->certPassword = $this->configuration->getString(self::NAGIOS_CERT_PASSWORD, "");
        $this->caPath = $this->configuration->getString(self::NAGIOS_CA_PATH, "");
        $this->peerVerification = $this->configuration->getBoolean(self::NAGIOS_PEER_VERIFY, false);

        if (empty($this->url)) {
            throw new Exception("Required option '" . self::NAGIOS_URL . "' is empty!");
        } elseif (empty($this->certPath)) {
            throw new Exception("Required option '" . self::NAGIOS_CERT_PATH . "' is empty!");
        } elseif (empty($this->caPath)) {
            throw new Exception("Required option '" . self::NAGIOS_CA_PATH . "' is empty!");
        }
    }


    public function getStatus()
    {
        $result = array();
        $serviceStatuses = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->peerVerification);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($ch, CURLOPT_CAPATH, $this->caPath);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->certPassword);

        $response = curl_exec($ch);

        if ($response === false) {
            \SimpleSAML\Logger::error(curl_error($ch));
        }

        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        if (isset($jsonResponse['status']['service_status'])) {
            $serviceStatuses = $jsonResponse['status']['service_status'];
        }

        foreach ($serviceStatuses as $serviceStatus) {
            $status = array();
            $status['name'] = $serviceStatus['service_display_name'];
            $status['status'] = $serviceStatus['status'];
            array_push($result, $status);
        }

        return $result;
    }
}
