<?php

namespace Sw2\CronCommand\Storage;

use Nette\Utils\DateTime;

/**
 * Interface IStorage
 * @package Sw2\CronCommand\Storage
 */
interface IStorage
{

	/**
	 * @param string $taskName
	 * @return DateTime|null
	 */
	public function getLastTime($taskName);

	/**
	 * @param string $taskName
	 * @param DateTime $time
	 */
	public function putLastTime($taskName, DateTime $time);

}
