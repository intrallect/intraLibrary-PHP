<?php

namespace IntraLibrary\IMS;

use \IntraLibrary\Debug;

/**
 * IntraLibrary IMS Content Package class
 */
class Package
{

	/**
	 * @var Manifest
	 */
	private $manifest;
	private $filepath;

	/**
	 * Createa a content package based on a manifest
	 *
	 * @param Manifest $manifest
	 */
	public function __construct(Manifest $manifest)
	{
		$this->manifest = $manifest;
	}

	/**
	 * Set a file that's to be included in this content package
	 *
	 * @param string $filepath
	 */
	public function setFile($filepath)
	{
		if (!is_readable($filepath))
		{
			throw new Exception("Unable to add $filepath to IMS Content Package");
		}
		$this->filepath = $filepath;
	}

	/**
	 * Get the manifest
	 *
	 * @return Manifest
	 */
	public function getManifest()
	{
		return $this->manifest;
	}

	/**
	 * Create a IMS content package
	 *
	 * @return string the filename
	 * @throws Exception
	 */
	public function create()
	{
		// setup paths
		$uniqid 		= str_replace('.', '', uniqid('', TRUE));
		$tmpDir 		= sys_get_temp_dir();
		$packagePath	= $tmpDir . DIRECTORY_SEPARATOR . "intralibrary_upload_$uniqid.zip";
		$manifestPath	= $tmpDir . DIRECTORY_SEPARATOR . "imsmanifest_$uniqid.xml";

		try
		{
			// save the manifest
			$this->manifest->save($manifestPath);
		}
		catch (Exception $ex)
		{
			// log any exceptions
			Debug::log($ex->getMessage());
		}

		// ensure it was saved properly
		if (!file_exists($manifestPath))
		{
			throw new Exception('Unable to create the upload package manifest.');
		}

		// create a zip archive with the manifest
		$zip = new \ZipArchive();
		$zip->open($packagePath, ZIPARCHIVE::CREATE);
		$zip->addFile($manifestPath, 'imsmanifest.xml');

		// add a file if it's been set
		if ($this->filepath)
		{
			$filename = $this->manifest->getFileName();
			if (empty($filename))
			{
				throw new Exception('Unable to create the upload package: imsmanifest did not contain a file name');
			}
			$zip->addFile($this->filepath, $filename);
		}

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
