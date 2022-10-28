<?php

namespace ILIAS\Plugin\LocalRegistration\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;

class Renderer extends AbstractComponentRenderer
{

	/**
	 * @inheritDoc
	 */
	protected function getComponentInterfaceName()
	{
		return [
			\ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm::class
		];
	}

	/**
	 * @inheritDoc
	 */
	public function render(Component $component, \ILIAS\UI\Renderer $default_renderer)
	{
		/**
		 * @var $component Input
		 */
		$this->checkComponent($component);

		switch (true) {
			case ($component instanceof \ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm):
				return $this->renderLoginForm($component, $default_renderer);
			default:
				throw new \LogicException("Cannot render '" . get_class($component) . "'");
		}
	}


	/**
	 * @param $name
	 * @return mixed|string
	 */
	protected function getTemplatePath($name)
	{
		return $name;
	}

	private function renderLoginForm(\ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm $component, \ILIAS\UI\Renderer $default_renderer)
	{
		$tpl = $this->getTemplate("tpl.register_form.html", true, true);

		if($component->getInfoText() != ""){
			$tpl->setCurrentBlock("info_text");
			$tpl->setVariable("INFO_TEXT", $component->getInfoText());
			$tpl->parseCurrentBlock();
		}

		if ($component->getPostURL() != "") {
			$tpl->setCurrentBlock("action");
			$tpl->setVariable("URL", $component->getPostURL());
			$tpl->parseCurrentBlock();
		}

		$f = $this->getUIFactory();
		$submit_button = $f->button()->standard($this->txt("register"), "");

		$tpl->setVariable("BUTTONS_TOP", $default_renderer->render($submit_button));
		$tpl->setVariable("BUTTONS_BOTTOM", $default_renderer->render($submit_button));

		$tpl->setVariable("INPUTS", $default_renderer->render($component->getInputGroup()));

		$error = $component->getError();
		if (!is_null($error)) {
			$tpl->setVariable("ERROR", $error);
		}
		if(count($component->getLinks()) > 0 ){
			$tpl->setCurrentBlock("links");

			foreach ($component->getLinks() as $link){
				$tpl->setCurrentBlock("link");
				$tpl->setVariable("LINK_ACTION", $link->getAction());
				$tpl->setVariable("LINK_TEXT", $link->getLabel());
				$tpl->parseCurrentBlock();
			}
		}

		return $tpl->get();
	}
}