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
	 * @return Transformation
	 */
	public function isLokalRoleIDConstraint($msg, $allow_blank = true): Transformation
	{
		return $this->refinery->custom()->constraint(
			function ($var) use ($msg, $allow_blank) {
				if($allow_blank && in_array($var, ["", null]))
					return true;

				$id = ilUtil::__extractId($var, IL_INST_ID);
				return is_int($id) && $id > 0 && ilObject::_lookupType($id) == "role";
			},
			$msg
		);
	}
}