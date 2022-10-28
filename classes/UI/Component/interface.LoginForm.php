<?php

/* Copyright (c) 2017 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LocalRegistration\UI\Component;


use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Container\Form\Standard;

/**
 * This describes a standard form.
 */
interface LoginForm extends Standard
{

    /**
     * Get the URL this form posts its result to.
     *
     * @return    string
     */
    public function getPostURL();

	/**
	 * Get the links this form posts its result to.
	 *
	 * @return    array
	 */
	public function getLinks(): array;

	/**
	 * Get a login form like this where links are attached.
	 *
	 * @param    array $links
	 *
	 * @return    LoginForm
	 */
	public function withLinks(array $links): LoginForm;

	/**
	 * Evaluates form and shows errors
	 *
	 * @return void
	 */
	public function evaluate();
}
