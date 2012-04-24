<?php

call_user_func(function() {
	$swordapppath = dirname(__FILE__) . '/swordapp-php-library/';
	require_once $swordapppath . 'swordappclient.php';
	require_once $swordapppath . 'swordappentry.php';
});

class IntraLibrarySWORD
{
	private $client;
	
	public function __construct($username, $password)
	{
		$this->client = new SWORDAPPClient();
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Get a list of all available deposit urls
	 *
	 * @return array
	 */
	public function get_deposit_details()
	{
		$service = $this->client->servicedocument(
				IntraLibraryConfiguration::get('hostname') . '/IntraLibrary-Deposit/service',
				$this->username,
				$this->password,
				'');
	
		$deposits = array();
	
		foreach ($service->sac_workspaces as $workspace)
		{
			$workspace_title = (string) $workspace->sac_workspacetitle;
				
			if (empty($deposits[$workspace_title]))
				$deposits[$workspace_title] = array();
				
			foreach ($workspace->sac_collections as $collection)
			{
				$collection_title = (string) $collection->sac_colltitle;
				$deposits[$workspace_title][$collection_title] = (string) $collection->sac_href;
			}
		}
	
		return $deposits;
	}
	
	/**
	 * Deposit a file to a URL
	 *
	 * @param string $url      The deposit URL
	 * @param string $filename The file to deposit
	 */
	public function deposit($url, $filename)
	{
		return $this->client->deposit(
				$url,
				$this->username,
				$this->password,
				'',
				$filename,
				'http://www.imsglobal.org/xsd/imscp_v1p1',
				'application/zip',
				FALSE,
				TRUE);
	}
	
	/**
	 * 
	 * @param IntraLibraryIMSManifest $manifest A configured manifest
	 * @param string                  $filepath A path to the file that should be attached
	 */
	public function create_package(IntraLibraryIMSManifest $manifest, $filepath)
	{
		// setup paths
		$uniqid 		= str_replace('.', '', uniqid('', TRUE));
		$tmpDir 		= sys_get_temp_dir();
		$packagePath	= $tmpDir . DIRECTORY_SEPARATOR . "intralibrary_upload_$uniqid.zip";
		$manifestPath	= $tmpDir . DIRECTORY_SEPARATOR . "imsmanifest_$uniqid.xml";
		
		try
		{
			// save the manifest
			$manifest->save($manifestPath);
		}
		catch (Exception $ex)
		{
			// log any exceptions
			IntraLibraryDebug::log($ex->getMessage());
		}
		
		// ensure it was saved properly
		if (!file_exists($manifestPath))
		{
			throw new Exception('Unable to create the upload package manifest.');
		}
		
		$filename = $manifest->getFileName();
		if (empty($filename))
		{
			throw new Exception('Unable to create the upload package: imsmanifest did not contain a file name');
		}
		
		// create a zip archive with the manifest and the uploaded file
		$zip = new ZipArchive();
		$zip->open($packagePath, ZIPARCHIVE::CREATE);
		$zip->addFile($manifestPath, 'imsmanifest.xml');
		$zip->addFile($filepath, $filename);
		$zip->close();
		
		// remove the manifest file..
		unlink($manifestPath);
		
		// ensure it was saved properly
		if (!file_exists($packagePath))
		{
			throw new Exception('Unable to create the upload package.');
		}
		
		return $packagePath;
	}
}