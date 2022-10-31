<?php

include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");

use ILIAS\Plugin\LocalRegistration\UI\Implementation\RegisterForm;
use ILIAS\Refinery\Custom\Custom\Constraint;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LocalRegistration\UI\Implementation\Factory as PluginUIFactory;
use ILIAS\Plugin\LocalRegistration\UI\PluginLoader;
use ILIAS\Plugin\LocalRegistration\UI\PluginRendererFactory;
use ILIAS\Plugin\LocalRegistration\UI\PluginTemplateFactory;

/**
 * LocalRegistration COPage Plugin
 *
 * @author Fabian Wolf <wolf@ilias.de>
 * @version $Id$
 *
 * @ilCtrl_isCalledBy ilLocalRegistrationPluginGUI: ilPCPluggedGUI
 * @ilCtrl_Calls ilObjLongEssayTaskGUI:
 */
class ilLocalRegistrationPluginGUI extends ilPageComponentPluginGUI
{
	const MODE_EDIT = 'edit';
	const MODE_OFFLINE = 'offline';
	const MODE_PRINT = 'print';
	const MODE_PRESENTATION = 'presentation';
	const MODE_PREVIEW = 'preview';

	protected static bool $inited = false;

	private ilCtrl $ctrl;
	private ilGlobalTemplateInterface $tpl;
	private Factory $factory;
	private \Psr\Http\Message\RequestInterface $request;

	private \ILIAS\Refinery\Factory $refinery;
	private ilTabsGUI $tabs;
	private \ILIAS\DI\RBACServices $rbac;


	/** @var ilLocalRegistrationPlugin $plugin */

	public function __construct()
	{
		global $DIC;
		parent::__construct();
		$this->init();

		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->factory = $DIC->ui()->factory();
		$this->request = $DIC->http()->request();
		$this->refinery = $DIC->refinery();
		$this->tabs = $DIC->tabs();
		$this->rbac = $DIC->rbac();
	}


	/**
	 * Execute command
	 *
	 * @param
	 * @return
	 */
	function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('showStartPage');
		switch ($cmd) {
			case 'showStartPage':
			case 'insert':
			case 'edit':
			case 'create':
			case 'update':
			case 'register':
				$this->$cmd();
				break;
			default:
				$this->tpl->setContent('unknown command: ' . $cmd);
		}
	}


	protected function init(){
		global $DIC;

		if(self::$inited){
			return;
		}
		self::$inited = true;
		$DIC["xlrp_plugin"] = function (\ILIAS\DI\Container $dic ) {return $this->getPlugin();};

		$DIC["custom_renderer_loader"] =  function (\ILIAS\DI\Container $dic ) {
			return new PluginLoader($dic["ui.component_renderer_loader"],
				new PluginRendererFactory(
					$dic["ui.factory"],
					new PluginTemplateFactory($dic["ui.template_factory"], $dic["xlrp_plugin"], $dic["tpl"]),
					$dic["lng"],
					$dic["ui.javascript_binding"],
					$dic["refinery"],
					$dic["ui.pathresolver"]
				)
			);
		};

		$DIC["custom_renderer"] = function (\ILIAS\DI\Container $dic) {
			return new \ILIAS\UI\Implementation\DefaultRenderer(
				$dic["custom_renderer_loader"]
			);
		};

		$DIC["custom_factory"] = function (\ILIAS\DI\Container $dic) {
			return new PluginUIFactory($dic["ui.factory.input.field"]);
		};

		$DIC["custom_refinery"] = function (\ILIAS\DI\Container $dic) {
			return new PluginRefineryFactory($dic['refinery'], $dic["lng"]);
		};
	}

	/**
	 * Form for new elements
	 */
	function insert()
	{
		$form = $this->initForm(true);
		$this->tpl->setContent($this->renderer()->render($form));
	}

	/**
	 * Save new pc example element
	 */
	public function create()
	{
		$this->setTabs("edit");
		$form = $this->initForm(true)->withRequest($this->request);
		$data = $form->getData();

		if (isset($data))
		{
			$properties = array(
				"welcome" => $data["sec"]["welcome"],
				"global_role" => $data["sec"]["global_role"],
				"local_role" => $data["sec"]["local_role"],
				"max_user" => $data["sec"]["max_user_enabled"] !== null ? $data["sec"]["max_user_enabled"]["max_user"] : -1
			);

			if ($this->createElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$this->tpl->setContent($this->renderer()->render($form));
	}

	/**
	 * Edit
	 *
	 * @param
	 * @return
	 */
	function edit()
	{
		$this->setTabs("edit");

		$form = $this->initForm();
		$this->tpl->setContent($this->renderer()->render($form));
	}

	/**
	 * Update
	 *
	 * @param
	 * @return
	 */
	function update()
	{
		$this->setTabs("edit");
		$form = $this->initForm(true)->withRequest($this->request);
		$data = $form->getData();
		if (isset($data))
		{
			$properties = array(
				"welcome" => $data["sec"]["welcome"],
				"global_role" => $data["sec"]["global_role"],
				"local_role" => $data["sec"]["local_role"],
				"max_user" => $data["sec"]["max_user_enabled"] !== null ? $data["sec"]["max_user_enabled"]["max_user"] : -1
			);

			if ($this->updateElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$this->tpl->setContent($this->renderer()->render($form));
	}


	/**
	 * Init editing form
	 *
	 * @param bool $a_create
	 * @return Form
	 */
	public function initForm($a_create = false): Form
	{
		$parent_ref_id = $this->plugin->getParentCategoryRefID();
		$user_gui = new ilObjUserGUI("", $parent_ref_id, true, false);
		$user_gui->initCreate();
		$selectable_roles = $user_gui->selectable_roles;
		if(isset($selectable_roles[2]))
			unset($selectable_roles[2]);

		if(!$this->rbac->system()->checkAccessOfUser(ANONYMOUS_USER_ID, "read", $parent_ref_id))
		{
			ilUtil::sendFailure($this->txt("missing_anonymous_access"));
		}

		$welcome = $this->factory->input()->field()->textarea($this->txt("welcome_message"));
		$global_role = $this->factory->input()->field()->select($this->txt("global_role"), $selectable_roles)
			->withRequired(true);
		$local_role = $this->factory->input()->field()->text($this->txt("local_role"), $this->txt("local_role_byline"))
			->withAdditionalTransformation($this->custom_refinery()->isLokalRoleIDConstraint($this->txt("local_role_not_valid")));

		$max_user = $this->factory->input()->field()->numeric($this->txt("max_user"))
			->withAdditionalTransformation($this->refinery->int()->isGreaterThan(-1))
			->withAdditionalTransformation($this->refinery->numeric()->isNumeric());

		$max_user_enabled = $this->factory->input()->field()->optionalGroup([
			"max_user" => $max_user
		], $this->txt("max_user_enable"))->withValue(null);

		$title = $this->txt("cmd_insert");
		$cmd = "create";

		if (!$a_create)
		{
			$prop = $this->getProperties();
			$max_user = $prop["max_user"];
			$welcome = $welcome->withValue((string)$prop["welcome"]);
			$global_role = $global_role->withValue((string)$prop["global_role"]);
			$local_role = $local_role->withValue((string)$prop["local_role"]);
			$max_user_enabled = $max_user_enabled->withValue((is_numeric($max_user) && (int)$max_user > -1) ? ["max_user" => (int)$max_user] : null);

			$title = $this->txt("cmd_edit");
			$cmd = "update";
		}

		$form = $this->factory->input()->container()->form()->standard(
			$this->ctrl->getFormAction($this, $cmd), [
				"sec" => $this->factory->input()->field()->section([
				"welcome" => $welcome,
				"global_role" => $global_role,
				"local_role" => $local_role,
				"max_user_enabled" => $max_user_enabled
						], $title),
			]
		);

		return $form;
	}


	/**
	 * Cancel
	 */
	function cancel()
	{
		$this->returnToParent();
	}

	/**
	 * Get HTML for element
	 *
	 * @param string $a_mode (edit, presentation, preview, offline)s
	 * @return string $html
	 */
	function getElementHTML($a_mode, array $a_properties, $a_plugin_version): string
	{
		$disabled = true;
		$links = [$this->factory->link()->standard($this->txt("already_have_account"),
			'./login.php?cmd=force_login&lang=' . $this->lng->getUserLanguage())];
		$max_user = $a_properties["max_user"] ?? 0;
		$occ = $this->getPlugin()->getLocalUserCount();

		if($this->getPlugin()->userCanCreate()){
			$this->ctrl->setParameterByClass("ilObjCategoryGUI", "ref_id", $this->getPlugin()->getParentCategoryRefID());
			$links[] = $this->factory->link()->standard($this->txt("administrate_accounts"),
				$this->ctrl->getLinkTargetByClass(["ilrepositorygui", "ilObjCategoryGUI"], "listUsers")
			);

			$info_text = ($max_user > 0) ? sprintf($this->txt("occupied_places"), $occ, $max_user): "";
		}elseif ($this->getPlugin()->userCanRegister()){
			$disabled = false;
			if($max_user > 0){
				$free = $max_user - $occ;
				if($free > 0){
					$info_text = sprintf($this->txt("free_places"), $free, $max_user);
				}else{
					$info_text = $this->txt("no_free_places");
					$disabled = true;
				}
			}
		}else{
			$title = (($title=ilObjSystemFolder::_getHeaderTitle()) !== "") ? $title : ilSetting::_lookupValue("common", "short_inst_name");

			return isset($a_properties["welcome"]) && $a_properties["welcome"] != ""
				? str_replace("&#13;", "<br />", $a_properties["welcome"])
				: sprintf($this->txt("welcome"), $title);
		}

		if(in_array($a_mode, [self::MODE_PRINT, self::MODE_EDIT, self::MODE_OFFLINE, self::MODE_PREVIEW])){
			$disabled = true;
		}
		$form = $this->registrationForm($disabled)->withLinks($links)->withInfoText($info_text);
		if($this->request->getMethod() === "POST" && !$disabled){
			if($max_user > 0 && $max_user <= $occ){
				ilUtil::sendFailure($this->txt("no_free_places"));
			}else{
				$form = $form->withRequest($this->request);
				$success = $this->register($form, $a_properties);
				if($success){
					return $this->renderer()->render(
						$this->factory->link()->standard($this->lng->txt('login_to_ilias'),
							'./login.php?cmd=force_login&lang=' . $this->lng->getUserLanguage())
					);
				}
			}
		}

		return $this->renderer()->render($form);
	}

	/**
	 * Register User
	 *
	 * @param RegisterForm $form
	 * @param array $properties PC properties
	 * @return bool
	 */
	private function register(RegisterForm $form, array $properties): bool
	{
		$data = $form->getData();

		if(isset($data)){
			$importParser = new ilUserImportParser(
				'',
				IL_USER_IMPORT,
				IL_FAIL_ON_CONFLICT
			);
			$importParser->setFolderId($this->getPlugin()->getParentCategoryRefID());
			$role_assignment = [(int)$properties["global_role"] => (int)$properties["global_role"]];

			if($properties["local_role"] !== "" && $this->custom_refinery()->isLokalRoleIDConstraint("", false)
					->applyTo(new ILIAS\Data\Result\Ok($properties["local_role"]))->isOK()){
				$id = ilUtil::__extractId($properties["local_role"], IL_INST_ID);
				$role_assignment[$id] = $id;
			}

			$importParser->setRoleAssignment($role_assignment);
			$importParser->setXMLContent($this->buildUserImportXML($data, $properties));
			$importParser->startParsing();

			switch ($importParser->getErrorLevel()) {
				case IL_IMPORT_SUCCESS:
					// Set Self Registered because its true and prevents new Password enforcement after first login
					$user_id = array_keys($importParser->getUserMapping());
					$usr_obj = new ilObjUser($user_id[0]);
					$usr_obj->setLastPasswordChangeToNow();
					$this->getPlugin()->setIsSelfRegisteredYes($usr_obj->getId());

					ilUtil::sendSuccess(
						$this->lng->txt("welcome") . " " . ilObjUser::_lookupFullname($usr_obj->getId()) .
						" (<b>" . $usr_obj->getLogin() . "</b>)!<br />".$this->lng->txt('txt_registered'),
						false
					);
					return true;
					break;
				case IL_IMPORT_WARNING:
				case IL_IMPORT_FAILURE:
					ilUtil::sendFailure($importParser->getProtocolAsHTML($this->lng->txt("import_failure_log")));
					break;
			}
		}
		return false;
	}

	/**
	 * Builds registration Form
	 *
	 * @param bool $a_disabled
	 * @return RegisterForm
	 */
	private function registrationForm(bool $a_disabled = false): RegisterForm
	{
		$this->lng->loadLanguageModule("form");
		$this->ctrl->saveParameter($this, "ref_id");
		$action = $this->ctrl->getFormActionByClass($this->ctrl->getCurrentClassPath(), $this->ctrl->getCmd());
		$string = $this->refinery->string();

		$username = $this->factory->input()->field()->text($this->lng->txt("login"))
			->withAdditionalTransformation($string->hasMaxLength(80))
			->withAdditionalTransformation($this->custom_refinery()->isLoginConstraint())
			->withAdditionalTransformation($this->custom_refinery()->loginExistsConstraint())
			->withRequired(true)
			->withDisabled($a_disabled);
		$firstname = $this->factory->input()->field()->text($this->lng->txt("firstname"))
			->withAdditionalTransformation($string->hasMaxLength(32))
			->withRequired(true)
			->withDisabled($a_disabled);
		$lastname = $this->factory->input()->field()->text($this->lng->txt("lastname"))
			->withAdditionalTransformation($string->hasMaxLength(32))
			->withRequired(true)
			->withDisabled($a_disabled);
		$gender = $this->factory->input()->field()->radio($this->lng->txt("salutation"))
			->withDisabled($a_disabled)
			->withOption("n", $this->lng->txt("salutation_n"))
			->withOption("f", $this->lng->txt("salutation_f"))
			->withOption("m", $this->lng->txt("salutation_m"));

		$same_password = $this->custom_refinery()->sameAsConstraint($this->lng->txt("passwd_not_match"));
		$password = $this->factory->input()->field()->password($this->lng->txt("passwd"), ilUtil::getPasswordRequirementsInfo())
			->withAdditionalTransformation($this->custom_refinery()->isPasswordConstraint())
			->withAdditionalTransformation($same_password)
			->withRequired(true)
			->withDisabled($a_disabled);
		$password_repeat = $this->factory->input()->field()->password("", $this->lng->txt("form_retype_password"))
			->withAdditionalTransformation($same_password)
			->withDisabled($a_disabled);

		$same_email = $this->custom_refinery()->sameAsConstraint($this->lng->txt("email_not_match"));
		$email = $this->factory->input()->field()->text($this->lng->txt("email"))
			->withAdditionalTransformation($string->hasMaxLength(80))
			->withAdditionalTransformation($this->custom_refinery()->isEmailConstraint())
			->withAdditionalTransformation($same_email)
			->withRequired(true)
			->withDisabled($a_disabled);
		$email_repeat = $this->factory->input()->field()->text("", $this->lng->txt('form_retype_email'))
			->withAdditionalTransformation($same_email)
			->withDisabled($a_disabled);

		$matriculation = $this->factory->input()->field()->text($this->lng->txt("matriculation"))
			->withAdditionalTransformation($string->hasMaxLength(40))
			->withRequired(true)
			->withDisabled($a_disabled);

		$inputs = [
			"ld" => $this->factory->input()->field()->section(
				[
					"username" => $username,
					"password" => $password,
					"password_repeat" => $password_repeat,
				], $this->lng->txt("login_data")
			),
			"pd" => $this->factory->input()->field()->section(
				[
					"firstname" => $firstname,
					"lastname" => $lastname,
					"gender" => $gender,
					"email" => $email,
					"email_repeat" => $email_repeat,
					"matriculation" => $matriculation
				], $this->lng->txt("personal_data")
			),
		];

		return $this->custom_factory()->registerForm($action, $inputs)->withDisabled($a_disabled);
	}

	/**
	 * Builds User Import XML of Form Data and PC properties
	 *
	 * @param array $data
	 * @param array $properties
	 * @return string
	 */
	private function buildUserImportXML(array $data, array $properties): string
	{
		// Hard prevent using Admin Role
		if((int)$properties["global_role"] === 2){
			$properties["global_role"] = 3;
		}

		$tpl = $this->getPlugin()->getTemplate("tpl.user_import.xml", true, true);
		$tpl->setVariable("LANG_CODE", $this->lng->getUserLanguage());
		$tpl->setVariable("LOGIN", $data["ld"]["username"]);
		$tpl->setVariable("FIRSTNAME", $data["pd"]["firstname"]);
		$tpl->setVariable("LASTNAME", $data["pd"]["lastname"]);
		$tpl->setVariable("PASSWORD", $data["ld"]["password"]->toString());
		$tpl->setVariable("EMAIL", $data["pd"]["email"]);
		if($data["pd"]["gender"]){
			$tpl->setVariable("GENDER", $data["pd"]["gender"]);
		}
		$tpl->setVariable("MATRICULATION", $data["pd"]["matriculation"]);
		$tpl->setCurrentBlock("role");
		$tpl->setVariable("ROLE_ID", $this->buildRoleImportID((int) $properties["global_role"]));
		$tpl->setVariable("ROLE_TYPE", "Global");
		$tpl->setVariable("ROLE_NAME", ilObjRole::_lookupTitle((int)$properties["global_role"]));
		$tpl->parseCurrentBlock();

		if($properties["local_role"] !== "" && $this->custom_refinery()->isLokalRoleIDConstraint("", false)
				->applyTo(new ILIAS\Data\Result\Ok($properties["local_role"]))->isOK()){
			$tpl->setVariable("ROLE_ID", $properties["local_role"]);
			$tpl->setVariable("ROLE_TYPE", "Local");
			$tpl->setVariable("ROLE_NAME", $properties["local_role"]);
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}

	/**
	 * Custom Renderer to add own UI Components
	 *
	 * @return Renderer
	 */
	private function renderer(): Renderer
	{
		global $DIC;
		return  $DIC["custom_renderer"];
	}

	/**
	 * Custom Factory for own UI Components
	 *
	 * @return PluginUIFactory
	 */
	private function custom_factory(): PluginUIFactory
	{
		global $DIC;
		return $DIC["custom_factory"];
	}

	/**
	 *
	 * Custom Factory for own Refinery Objects
	 * @return PluginRefineryFactory
	 */
	private function custom_refinery(): PluginRefineryFactory
	{
		global $DIC;
		return $DIC["custom_refinery"];
	}

	/**
	 * Build Import ID
	 *
	 * @param int $rol_id
	 * @return string
	 */
	private function buildRoleImportID(int $rol_id): string
	{
		return 'il_' . IL_INST_ID . '_' . ilObject::_lookupType($rol_id) . '_' . $rol_id;
	}

	/**
	 * @param string $variable
	 * @return string
	 */
	private function txt(string $variable): string{
		return $this->getPlugin()->txt($variable);
	}

	/**
	 * PC Configuration Tab
	 *
	 * @param string $a_active Active tab ID
	 * @return void
	 */
	function setTabs(string $a_active)
	{
		$this->tabs->addTab("edit", $this->lng->txt("settings"),
			$this->ctrl->getLinkTarget($this, "edit"));
		$this->tabs->setForcePresentationOfSingleTab(true);
		$this->tabs->setBack2Target($this->lng->txt("cancel"), $this->ctrl->getLinkTarget($this, "cancel"));
		$this->tabs->activateTab($a_active);
	}
}