<?php

#include_once("./Services/COPage/classes/class.ilPageComponentPlugin.php");

/**
 * LocalRegistration COPage Plugin
 *
 * @author Fabian Wolf <wolf@ilias.de>
 * @version $Id$
 *
 */
class ilLocalRegistrationPlugin extends ilPageComponentPlugin
{
	protected ilObjUser $user;
	protected ilSetting $settings;
	protected ?ilObjCategory $category = null;
	protected ilTree $tree;
	protected ilAccessHandler $access;


	public function __construct()
	{
		global $DIC;
		parent::__construct();
		$this->user = $DIC->user();
		$this->settings = new ilSetting($this->getId());
		$this->tree = $DIC->repositoryTree();
		$this->access = $DIC->access();
		$parent_ref_id = $this->getParentCategoryRefID();
		$this->category = $parent_ref_id !== 0 ? new ilObjCategory($parent_ref_id, true) : null;
	}


	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	function getPluginName()
	{
		return "LocalRegistration";
	}


	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	function isValidParentType($a_type)
	{
		return $this->category !== null && $this->userCanCreate() && in_array($a_type, array("cat", "copa"));
	}

	/**
	 * Get Javascript files
	 */
	function getJavascriptFiles($a_mode)
	{
		return array();
	}

	/**
	 * Get css files
	 */
	function getCssFiles($a_mode)
	{
		return array();
	}

	/**
	 * Check if user can Create Registration Form and Manage Users
	 * @return bool
	 */
	public function userCanCreate(): bool
	{
		// If user can Access User Administration or Local User Administration
		return $this->access->checkAccess("write", "", 7) || (
				$this->category !== null &&
				$this->access->checkAccess("cat_administrate_users", "", $this->category->getRefId())
			);
	}

	/**
	 * Check if user can Register via Form
	 * @return bool
	 */
	public function userCanRegister(): bool
	{
		return $this->user->isAnonymous();
	}

	/**
	 * Searches for a Parent Category ID
	 *
	 * @return int
	 */
	public function getParentCategoryRefID():int
	{
		if($this->category !== null){
			return $this->category->getRefId();
		}


		$ref_id = (int)$_GET["ref_id"] ?? 0;

		if($ref_id === 0){
			$references = ilObject2::_getAllReferences($this->getParentId());
			$ref_id = $references[0] ?? 0;
		}

		if($ref_id === 0){
			return 0;
		}

		if($this->getParentType() === "cat" ||
			ilObject::_lookupType($ref_id, true) === "cat"){
			return $ref_id;
		}

		if(($this->getParentType() === "copa" || ilObject::_lookupType($ref_id, true) === "copa")
			&& $this->tree->checkForParentType($ref_id, "cat")){
			return $this->tree->getParentId($ref_id);
		}
		return 0;
	}

	/**
	 * @return int
	 */
	public function getLocalUserCount(): int{
		return count(ilLocalUser::_getAllUserIds($this->getParentCategoryRefID()));
	}

	/**
	 * @param int $usr_id
	 * @return bool
	 */
	public function setIsSelfRegisteredYes(int $usr_id): bool
	{
		global $DIC;

		$ilDB = $DIC['ilDB'];

		$query = "UPDATE usr_data SET is_self_registered = 1 " .
			"WHERE usr_id = %s";
		$affected = $ilDB->manipulateF(
			$query,
			array('integer'),
			array($usr_id)
		);
		if ($affected) {
			return true;
		} else {
			return false;
		}
	}
}