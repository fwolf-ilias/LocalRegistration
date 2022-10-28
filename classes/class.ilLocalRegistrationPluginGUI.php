<?php

include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");

use ILIAS\Plugin\LocalRegistration\UI\Implementation\RegisterForm;
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


	private ilCtrl $ctrl;
	private ilGlobalTemplateInterface $tpl;
	private Factory $factory;
	private Renderer $renderer;
	private \Psr\Http\Message\RequestInterface $request;
	private ilRbacReview $rbacreview;
	private ilObjUser $user;
	private \ILIAS\Refinery\Factory $refinery;
	private ilTabsGUI $tabs;


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
		$this->rbacreview = $DIC->rbac()->review();
		$this->user = $DIC->user();
		$this->refinery = $DIC->refinery();
		$this->tabs = $DIC->tabs();
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

		$DIC["plugin"] = function (\ILIAS\DI\Container $dic ) {return $this->getPlugin();};

		$DIC["custom_renderer_loader"] =  function (\ILIAS\DI\Container $dic ) {
			return new PluginLoader($dic["ui.component_renderer_loader"],
				new PluginRendererFactory(
					$dic["ui.factory"],
					new PluginTemplateFactory($dic["ui.template_factory"], $dic["plugin"], $dic["tpl"]),
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
				"local_role" => $data["sec"]["local_role"]
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
				"local_role" => $data["sec"]["local_role"]
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

		$welcome = $this->factory->input()->field()->textarea("Welcome Message");
		$global_role = $this->factory->input()->field()->select("Global Role", $user_gui->selectable_roles)
			->withRequired(true);
		$local_role = $this->factory->input()->field()->text("Local Role", "Local Role ID String here.");
		$title = "Create Local Registration Form";
		$cmd = "create";

		if (!$a_create)
		{
			$prop = $this->getProperties();
			$welcome = $welcome->withValue($prop["welcome"]);
			$global_role = $global_role->withValue((string)$prop["global_role"]);
			$local_role = $local_role->withValue((string)$prop["local_role"]);
			$title = "Edit Local Registration Form";
			$cmd = "update";
		}

		$form = $this->factory->input()->container()->form()->standard(
			$this->ctrl->getFormAction($this, $cmd), [
				"sec" => $this->factory->input()->field()->section([
				"welcome" => $welcome,
				"global_role" => $global_role,
				"local_role" => $local_role
						], $title)
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
		$links = [$this->factory->link()->standard("Already have an Account?",
			'./login.php?cmd=force_login&lang=' . $this->lng->getUserLanguage())];

		if($this->getPlugin()->userCanCreate()){
			$this->ctrl->setParameterByClass("ilObjCategoryGUI", "ref_id", $this->getPlugin()->getParentCategoryRefID());
			$links[] = $this->factory->link()->standard("Administrate Accounts",
				$this->ctrl->getLinkTargetByClass("ilObjCategoryGUI", "listUsers")
			);
		}elseif ($this->getPlugin()->userCanRegister()){
			$disabled = false;
		}else{
			return isset($a_properties["welcome"]) && $a_properties["welcome"] != ""
				? str_replace("&#13;", "<br />", $a_properties["welcome"])
				: "Welcome!";
		}

		if(in_array($a_mode, [self::MODE_PRINT, self::MODE_EDIT, self::MODE_OFFLINE, self::MODE_PREVIEW])){
			$disabled = true;
		}
		$form = $this->registrationForm($disabled);
		if($this->request->getMethod() === "POST"){
			$form = $form->withRequest($this->request);
			$success = $this->register($form, $a_properties);
			if($success){
				return $this->renderer->render(
					$this->factory->link()->standard($this->lng->txt('login_to_ilias'),
						'./login.php?cmd=force_login&lang=' . $this->lng->getUserLanguage())
				);
			}
		}
		$form = $form->withLinks($links);

		return $this->renderer()->render($form);
	}

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
			$importParser->setXMLContent($this->buildUserImportXML($data, $properties));

			$importParser->startParsing();

			switch ($importParser->getErrorLevel()) {
				case IL_IMPORT_SUCCESS:
					ilUtil::sendSuccess(
						$this->lng->txt("welcome")."<br />".$this->lng->txt('txt_registered'),
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

	private function registrationForm($a_disabled = false): RegisterForm
	{
		$this->lng->loadLanguageModule("form");
		$this->ctrl->saveParameter($this, "ref_id");
		$action = $this->ctrl->getFormActionByClass($this->ctrl->getCurrentClassPath(), $this->ctrl->getCmd());
		$string = $this->refinery->string();

		$username = $this->factory->input()->field()->text($this->lng->txt("login"))
			->withAdditionalTransformation($string->hasMaxLength(80))
			->withAdditionalTransformation($this->isLoginRefinery())
			->withAdditionalTransformation($this->loginExistsRefinery())
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

		$same_password = $this->sameAsRefinery($this->lng->txt("passwd_not_match"));
		$password = $this->factory->input()->field()->password($this->lng->txt("passwd"), ilUtil::getPasswordRequirementsInfo())
			->withAdditionalTransformation($this->isPasswordRefinery())
			->withAdditionalTransformation($same_password)
			->withRequired(true)
			->withDisabled($a_disabled);
		$password_repeat = $this->factory->input()->field()->password("", $this->lng->txt("form_retype_password"))
			->withAdditionalTransformation($same_password)
			->withDisabled($a_disabled);

		$same_email = $this->sameAsRefinery($this->lng->txt("email_not_match"));
		$email = $this->factory->input()->field()->text($this->lng->txt("email"))
			->withAdditionalTransformation($string->hasMaxLength(80))
			->withAdditionalTransformation($this->isEmailRefinery())
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
				], "Login Data"
			),
			"pd" => $this->factory->input()->field()->section(
				[
					"firstname" => $firstname,
					"lastname" => $lastname,
					"gender" => $gender,
					"email" => $email,
					"email_repeat" => $email_repeat,
					"matriculation" => $matriculation
				], "Personal Data"
			),
		];

		return $this->custom_factory()->registerForm($action, $inputs);
	}

	private function buildUserImportXML(array $data, array $properties): string
	{
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
		$tpl->setVariable("ROLE_ID", $this->buildRoleImportID($properties["global_role"]));
		$tpl->setVariable("ROLE_TYPE", "Global");
		$tpl->setVariable("ROLE_NAME", ilObjRole::_lookupTitle($properties["global_role"]));
		$tpl->parseCurrentBlock();

		if($properties["local_role"] !== "" && ilObjRole::_getIdForImportId($properties["local_role"]) !== 0){
			$ilias_id = $this->buildRoleImportID($properties["local_role"]);
			$tpl->setVariable("ROLE_ID", $ilias_id);
			$tpl->setVariable("ROLE_TYPE", "Local");
			$tpl->setVariable("ROLE_NAME", $ilias_id);
		}

		$tpl->parseCurrentBlock();

		return $tpl->get();
	}

	private function isLoginRefinery()
	{
		return $this->refinery->custom()->constraint(
			function ($var) {
				return ilUtil::isLogin($var);
			},
			$this->lng->txt("login_invalid")
		);
	}
	private function loginExistsRefinery()
	{
		return $this->refinery->custom()->constraint(
			function ($var) {
				return !ilObjUser::_loginExists($var);
			},
			$this->lng->txt("login_exists")
		);
	}

	private function isPasswordRefinery(){
		$custom_error = "";
		return $this->refinery->custom()->constraint(
			function ($var) use ($custom_error) {
				return ilUtil::isPassword($var->toString(), $custom_error);
			},
			function () use($custom_error) {return $custom_error != '' ? $custom_error : $this->lng->txt("passwd_invalid");}
		);
	}

	private function sameAsRefinery(string $msg)
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
	 * @return Renderer
	 */
	private function renderer(): Renderer
	{
		global $DIC;
		return  $DIC["custom_renderer"];
	}

	/**
	 * @return PluginUIFactory
	 */
	private function custom_factory(): PluginUIFactory
	{
		global $DIC;
		return $DIC["custom_factory"];
	}

	private function isEmailRefinery(){
		return $this->refinery->custom()->constraint(
			function ($var) {
				return ilUtil::is_email($var);
			},
			$this->lng->txt("email_not_valid")
		);
	}

	private function buildRoleImportID($rol_id){
		return 'il_' . IL_INST_ID . '_' . ilObject::_lookupType($rol_id) . '_' . $rol_id;
	}

	/**
	 * Set tabs
	 *
	 * @param
	 * @return
	 */
	function setTabs($a_active)
	{
		$this->tabs->addTab("edit", $this->lng->txt("settings"),
			$this->ctrl->getLinkTarget($this, "edit"));
		$this->tabs->setForcePresentationOfSingleTab(true);
		$this->tabs->setBack2Target($this->lng->txt("cancel"), $this->ctrl->getLinkTarget($this, "cancel"));
		$this->tabs->activateTab($a_active);
	}

	/**
	 * @return ilLocalRegistrationPlugin
	 /
	public function getPlugin(): ilLocalRegistrationPlugin
	{
		return parent::getPlugin();
	}*/

	public function links(){
		return '<p class="">
		
<a class="il_ContainerItemCommand" href="../../../../../../../../login.php?lang=en&amp;client_id=edutiek">Already have an account?</a>


<a class="il_ContainerItemCommand" href="../../../../../../../../index.php?client_id=edutiek&amp;lang=en">Administrate Accounts</a>

<a class="il_ContainerItemCommand" href="../../../../../../../../index.php?client_id=edutiek&amp;lang=en">Settings</a>

</p>';
	}
}