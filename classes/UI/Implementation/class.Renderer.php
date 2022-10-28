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
			\ILIAS\Plugin\LocalRegistration\UI\Component\LoginForm::class
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

		$component = $this->setSignals($component);

		switch (true) {
			case ($component instanceof \ILIAS\Plugin\LocalRegistration\UI\Component\LoginForm):
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

	private function renderLoginForm(\ILIAS\Plugin\LocalRegistration\UI\Component\LoginForm $component, \ILIAS\UI\Renderer $default_renderer)
	{
		return "";
	}
}