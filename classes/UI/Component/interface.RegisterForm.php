<?php

/* Copyright (c) 2017 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LocalRegistration\UI\Component;


use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Link\Standard as Link;

/**
 * This describes a standard form.
 */
interface RegisterForm extends Standard
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
	 * @return    array<Link>
	 */
	public function getLinks(): array;

	/**
	 * Get a login form like this where links are attached.
	 *
	 * @param    array<Link> $links
	 *
	 * @return    RegisterForm
	 */
	public function withLinks(array $links): RegisterForm;

	/**
	 * Evaluates form and shows errors
	 *
	 * @return void
	 */
	public function evaluate();
}
