<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LocalRegistration\UI\Implementation;

use ILIAS\Plugin\LocalRegistration\UI;
use ILIAS\Plugin\LocalRegistration\UI\Component\RegisterForm;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Standard;
use ILIAS\UI\Implementation\Component\Input;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayTask\UI\Implementation
 */
class Factory implements UI\Component\Factory
{
	/**
	 * @var Input\Field\Factory
	 */
	protected Input\Field\Factory $field_factory;

	public function __construct(Input\Field\Factory $field_factory) {
		$this->field_factory = $field_factory;
	}

	public function registerForm(string $post_url, array $inputs, array $links = []): RegisterForm
	{
		return new \ILIAS\Plugin\LocalRegistration\UI\Implementation\RegisterForm($this->field_factory, $post_url, $inputs, $links);
	}
}
