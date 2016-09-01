<?php

namespace IntraLibrary\Service;

use \IntraLibrary\SWORD\SWORDClient;
use \IntraLibrary\SWORD\SWORDEntry;
use \IntraLibrary\SWORD\SWORDErrorDocument;
use \IntraLibrary\Debug;
use \IntraLibrary\Configuration;

/**
 * A simple wrapper for swordapp-php-library
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class SWORDService
{
    /**
     * Get the id from a deposit response
     *
     * @param \IntraLibrary\SWORD\SWORDEntry $response
     * @return the id, or null if it was unavailable
     */
    public static function get_lo_id(SWORDEntry $response)
    {
        if ($response instanceof SWORDErrorDocument) {
            return null;
        }

        preg_match('/^.*\:(\d+)$/', (string) $response->sac_id, $matches);

        return isset($matches[1]) ? $matches[1] : null;
    }

    /**
     * @var \IntraLibrary\SWORD\SWORDClient
     */
    private $client;

    /**
     * Create a new SWORDService object
     *
     * @param string $username the username
     * @param string $password the password
     */
    public function __construct($username, $password)
    {
        $this->client = new SWORDClient();
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get a list of all available deposit urls
     *
     * @return array
     */
    public function getDepositDetails()
    {
        $url = Configuration::get('hostname') . '/IntraLibrary-Deposit/service';
        Debug::log("SWORD requesting deposit details from $url");
        $service = $this->client->servicedocument($url, $this->username, $this->password, '');

        $deposits = array();

        foreach ($service->sac_workspaces as $workspace) {
            $workspace_title = (string) $workspace->sac_workspacetitle;

            if (empty($deposits[$workspace_title])) {
                $deposits[$workspace_title] = array();
            }

            foreach ($workspace->sac_collections as $collection) {
                $collection_title = (string) $collection->sac_colltitle;
                $deposits[$workspace_title][$collection_title] = (string) $collection->sac_href;
            }
        }

        return $deposits;
    }

    /**
     * Deposit a file to a URL
     *
     * @param string  $url       The deposit URL
     * @param string  $filename  The file to deposit
     * @param boolean $MD5_check Whether to perform an MD5 check
     * @return \IntraLibrary\SWORD\SWORDEntry
     */
    public function deposit($url, $filename, $MD5_check = false)
    {
        Debug::log("SWORD depositing $filename to $url");
        return $this->client->deposit(
            $url,
            $this->username,
            $this->password,
            '',
            $filename,
            '',
            'application/zip',
            false,
            true,
            $MD5_check
        );
    }
}

