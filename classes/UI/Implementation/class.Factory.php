<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LocalRegistration\UI\Implementation;

use ILIAS\Plugin\LocalRegistration\UI;
use ILIAS\Plugin\LocalRegistration\UI\Component\LoginForm;
use ILIAS\Plugin\LongEssayTask\UI\Implementation\Numeric;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;


/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayTask\UI\Implementation
 */
class Factory implements UI\Component\Factory
{
	/**
	 * @var    Data\Factory
	 */
	protected $data_factory;

	/**
	 * @var SignalGeneratorInterface
	 */
	protected $signal_generator;

	/**
	 * @var \ILIAS\Refinery\Factory
	 */
	private $refinery;

	/**
	 * @var	\ilLanguage
	 */
	protected $lng;

	/**
	 * Factory constructor.
	 *
	 * @param SignalGeneratorInterface $signal_generator
	 * @param Data\Factory $data_factory
	 * @param \ILIAS\Refinery\Factory $refinery
	 */
	public function __construct(
		SignalGeneratorInterface $signal_generator,
		Data\Factory $data_factory,
		\ILIAS\Refinery\Factory $refinery,
		\ilLanguage $lng
	) {
		$this->signal_generator = $signal_generator;
		$this->data_factory = $data_factory;
		$this->refinery = $refinery;
		$this->lng = $lng;
	}

	public function loginForm(string $post_url, array $inputs, array $links): LoginForm
	{

	}
}
