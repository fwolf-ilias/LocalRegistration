<?php

/* Copyright (c) 2017 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LocalRegistration\UI\Implementation;

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component\Input;

/**
 * This implements a standard form.
 */
class RegisterForm extends Input\Container\Form\Standard implements \ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm
{
	protected array $links;
	protected string $info_text;
	protected bool $disabled = false;

    public function __construct(Input\Field\Factory $input_factory, $post_url, array $inputs, array $links)
    {
        parent::__construct($input_factory, $post_url, $inputs);
		$this->links = $links;
		$this->info_text = "";
    }

	public function getLinks(): array
	{
		return $this->links;
	}

	public function withLinks(array $links): \ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm
	{
		$clone = clone $this;
		$clone->links = $links;

		return $clone;
	}

	public function evaluate()
	{
		$content = $this->getInputGroup()->getContent();
		if (!$content->isok()) {
			$this->setError($content->error());
			return null;
		}

		return $content->value();
	}

	public function getInfoText(): string
	{
		return $this->info_text;
	}

	public function withInfoText(string $text): \ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm
	{
		$clone = clone $this;
		$clone->info_text = $text;

		return $clone;
	}

	public function isDisabled():bool
	{
		return $this->disabled;
	}

	public function withDisabled($is_disabled): \ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm
	{
		$clone = clone $this;
		$clone->disabled = $is_disabled;

		return $clone;
	}
}
