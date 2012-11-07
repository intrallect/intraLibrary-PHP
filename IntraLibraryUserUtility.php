<?php

/**
 * A helper class to manage intralibrary users
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryUserUtility
{
	/**
	 * Create an IntraLibrary student user
	 *
	 * @param string $username The requested username
	 * @param string $userRole The user role
	 * @param string $groupIds The group ids to associate to this user
	 * @return string the new user's username (may differ from the requested username)
	 */
	public static function createUser($username, $userRole, $groupIds)
	{
		// will use $suffix to attempt to generate a unique username
		$suffix = '';
		while (TRUE)
		{
			try
			{
				return self::_createUser($username . $suffix, $userRole, $groupIds, TRUE);
			}
			catch (IntraLibraryException $ex)
			{
				$code = $ex->getCode();

				if ($code == IntraLibraryException::USER_EXISTS)
				{
					// If the user exists, let's try to generate a unique username
					if ($suffix == '') $suffix = 1;
					else $suffix++;
				}
				else
				{
					// Log all other exceptions and move on
					error_log($ex->getMessage());
					break;
				}
			}
		}

		return NULL;
	}

	/**
	 * Create an IntraLibrary user
	 *
	 * @param string  $username The username
	 * @param string  $userRole The user role
	 * @param integer $groupIds The group
	 * @throws IntraLibraryException
	 * @return string the new user's username
	 */
	private static function _createUser($username, $userRole, $groupIds)
	{
		$req 	= new IntraLibraryRESTRequest();
		$resp 	= $req->adminGet('User/createWithGroup', array(
			'username' => $username,
			'password' => self::generatePassword($username),
			'user_roles' => $userRole,
			'selected_group_ids' => $groupIds
		));

		if ($error = $resp->getError())
		{
			throw new IntraLibraryException($error, -1);
		}

		$data = $resp->getData();

		return $data['user']['username'];
	}

	/**
	 * Generate a username-based hash for use as a password
	 *
	 * @param string $username the username
	 * @return string
	 */
	public static function generatePassword($username)
	{
		return substr(md5($username . 'salty' . $username), 0, 10);
	}

	/**
	 * Delete user
	 *
	 * @param string $username the username
	 * @return void
	 */
	public static function deleteUser($username)
	{
		$request = new IntraLibraryRESTRequest();
		$request->adminGet('User/deleteUser', array('username' => $username));
	}
}
