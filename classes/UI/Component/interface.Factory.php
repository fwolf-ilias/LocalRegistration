<?php
namespace ILIAS\Plugin\LocalRegistration\UI\Component;

use ILIAS\UI\Component\Input\Field\FormInput;

/**
 * This is how a factory for forms looks like.
 */
interface Factory
{

    /**
     *
     * @param string $post_url
     * @param    array<mixed,FormInput>    $inputs
	 * @param	 array<mixed, string>	$links
     *
     * @return    LoginForm
     */
    public function loginForm(string $post_url, array $inputs, array $links): LoginForm;
}
