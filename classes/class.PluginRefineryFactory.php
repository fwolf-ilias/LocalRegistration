<?php

use ILIAS\Refinery\Factory;
use ILIAS\Refinery\Transformation;

class PluginRefineryFactory
{
	protected Factory $refinery;
	private ilLanguage $lng;

	/**
	 * @param Factory $refinery
	 */
	public function __construct(Factory $refinery, ilLanguage $lng) {
		$this->refinery = $refinery;
		$this->lng =$lng;
	}


	/**
	 * @return Transformation
	 */
	public function isLoginConstraint(): Transformation
	{
		return $this->refinery->custom()->constraint(
			function ($var) {
				return ilUtil::isLogin($var);
			},
			$this->lng->txt("login_invalid")
		);
	}

	/**
	 * @return Transformation
	 */
	public function loginExistsConstraint(): Transformation
	{
		return $this->refinery->custom()->constraint(
			function ($var) {
				return !ilObjUser::_loginExists($var);
			},
			$this->lng->txt("login_exists")
		);
	}

	/**
	 * @return Transformation
	 */
	public function isPasswordConstraint(): Transformation
	{
		$custom_error = "";
		return $this->refinery->custom()->constraint(
			function ($var) use ($custom_error) {
				return ilUtil::isPassword($var->toString(), $custom_error);
			},
			function () use($custom_error) {return $custom_error != '' ? $custom_error : $this->lng->txt("passwd_invalid");}
		);
	}

	/**
	 * @param string $msg
	 * @return Transformation
	 */
	public function sameAsConstraint(string $msg): Transformation
	{
		$data = new stdClass();
		$data->pref_val = null;

		return $this->refinery->custom()->constraint(
			function ($var) use ($data){
				if($var instanceof \ILIAS\Data\Password){
					$var = $var->toString();
				}
				if($data->pref_val == null){
					$data->pref_val = $var;
					return true;
				}else{
					return $data->pref_val === $var;
				}
			},
			$msg
		);
	}


	/**
	 *
	 * @return Transformation
	 */
	public function isEmailConstraint(): Transformation
	{
		return $this->refinery->custom()->constraint(
			function ($var) {
				return ilUtil::is_email($var);
			},
			$this->lng->txt("email_not_valid")
		);
	}

	/**
	 *
	 * @param string $msg error message, to included faulty roles add %s to error message
	 * @param bool $allow_blank true if empty string is also allowed
	 * @return Transformation
	 */
	public function isLokalRoleIDConstraint(string $msg, $allow_blank = true): Transformation
	{
		$invalid_roles = [];
		return $this->refinery->custom()->constraint(
			// Function which validates all roles. Roles can either be single or strictly seperated by ', '
			function ($var) use ($msg, $allow_blank, &$invalid_roles) {
				if($allow_blank && in_array($var, ["", null]))
					return true;
				$c = 0;
				foreach(explode(", ", $var) as $role){
					$id = ilUtil::__extractId($role, IL_INST_ID);
					$c++;
					if( !( is_int($id) && $id > 0 && ilObject::_lookupType($id) == "role") )
					{
						$invalid_roles[] =  "$role";
					}
				}
				return empty($invalid_roles) && $c > 0;
			},
			// Function to print the error message which includes all as wrong detected roles
			function () use ($msg, &$invalid_roles) {
				return sprintf($msg, implode(", ", array_map(fn ($v) => "<b>$v</b>", $invalid_roles)));
			}
		);
	}

	/**
	 * Transformation which splits a string of role ids by semicolon, comma or whitespace character cleans them
	 * and returns a string with strict ", " seperated roles
	 * @return Transformation
	 */
	public function normalizeLocalRolesTransformation(): Transformation{
		return $this->refinery->custom()->transformation(

			function (string $roles): string {
				$role_array = [];
				foreach(preg_split( "/[;,\s]/", $roles) as $role)
				{
					$role = trim($role);
					if(!empty($role)) {
						$role_array[] = $role;
					}
				}

				return implode(", ", array_unique($role_array));
			}
		);
	}
}