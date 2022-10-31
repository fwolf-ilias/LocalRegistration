<?php

/* Copyright (c) 2017 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LocalRegistration\UI\Component;


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

	/**
	 * Get The Info Text
	 *
	 * @return string
	 */
	public function getInfoText(): string;

	/**
	 * Get a login form like this where links are attached.
	 *
	 * @param    string $text
	 *
	 * @return    RegisterForm
	 */
	public function withInfoText(string $text): RegisterForm;

	/**
	 * Is this input disabled?
	 *
	 * @return    bool
	 */
	public function isDisabled(): bool;

	/**
	 * Get an input like this, but set it to a disabled state.
	 *
	 * @param    bool $is_disabled
	 *
	 * @return    RegisterForm
	 */
	public function withDisabled(bool $is_disabled): RegisterForm;
}
