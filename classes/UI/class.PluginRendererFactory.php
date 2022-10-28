<?php

namespace ILIAS\Plugin\LocalRegistration\UI;

use ILIAS\Plugin\LocalRegistration\UI\Implementation\Renderer;
use ILIAS\UI\Implementation\Render;
use ILIAS\UI\Component;

class PluginRendererFactory extends Render\DefaultRendererFactory
{
	public function getRendererInContext(Component\Component $component, array $contexts)
	{
		switch(true){
			default:
				return new Renderer($this->ui_factory,
					$this->tpl_factory,
					$this->lng,
					$this->js_binding,
					$this->refinery,
					$this->image_path_resolver);
		}
	}
}

