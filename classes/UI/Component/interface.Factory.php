<?php
namespace ILIAS\Plugin\LocalRegistration\UI\Component;

use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\UI\Component\Link\Standard as Link;

/**
 * This is how a factory for forms looks like.
 */
interface Factory
{

    /**
     *
     * @param string $post_url
     * @param    array<mixed,FormInput>    $inputs
	 * @param	 array<Link>	$links
     *
     * @return    RegisterForm
     */
    public function registerForm(string $post_url, array $inputs, array $links = []): RegisterForm;
}
