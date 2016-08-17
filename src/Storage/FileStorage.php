<?php

namespace Sw2\CronCommand\Storage;

use Nette;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Class FileStorage
 * @package Sw2\CronCommand\Storage
 */
class FileStorage implements IStorage
{
	const TIME_FORMAT = 'Y-m-d H:i:s O';

	/** @var string */
	private $dir;

	/**
	 * @param string $dir
	 */
	public function __construct($dir)
	{
		$this->dir = $dir;

		@mkdir($dir, 0777, TRUE);
		if (!is_writable($dir)) {
			throw new Nette\IOException("Directory '$dir' is not writable.");
		}
	}


	/**
	 * @param string $taskName
	 * @return DateTime|null
	 */
	public function getLastTime($taskName)
	{
		$file = $this->getFilename($taskName);
		if (file_exists($file)) {
			$content = explode("\r\n", file_get_contents($file));
			if (isset($content[1])) {
				return DateTime::createFromFormat(self::TIME_FORMAT, $content[1]);
			}
		}

		return NULL;
	}

	/**
	 * @param string $taskName
	 * @param DateTime $time
	 */
	public function putLastTime($taskName, DateTime $time)
	{
		file_put_contents($this->getFilename($taskName), $taskName . "\r\n" . $time->format(self::TIME_FORMAT));
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function getFilename($name)
	{
		return 'safe://' . $this->dir . '/' . Strings::webalize($name) . '--' . md5($name);
	}

}
