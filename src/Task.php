<?php

namespace Sw2\CronCommand;

use Nette\SmartObject;
use Nette\Utils\DateTime;
use Sw2\CronCommand\Storage\IStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Task
 * @package Sw2\CronCommand
 *
 * @property-read bool $debugMode
 */
abstract class Task extends Command
{
	use SmartObject;

	/** @var bool */
	private $debugMode;

	/** @var string */
	private $locksDir;

	/** @var IStorage */
	private $storage;

	/**
	 * @param bool $debugMode
	 * @internal
	 */
	public function setDebugMode($debugMode)
	{
		$this->debugMode = $debugMode;
	}

	/**
	 * @param string $locksDir
	 * @internal
	 */
	public function setLocksDir($locksDir)
	{
		$this->locksDir = $locksDir;
	}

	/**
	 * @param IStorage $storage
	 * @internal
	 */
	public function setStorage(IStorage $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * @inheritdoc
	 */
	public final function run(InputInterface $input, OutputInterface $output, DateTime $execTime = NULL)
	{
		$execTime = $execTime ?: DateTime::from('- 3 seconds');
		$statusCode = 0;
		if ($this->lock()) {
			$output->writeln(sprintf('<info>Task %s started</info>', $this->getName()));
			if (($statusCode = parent::run($input, $output)) === 0) {
				$this->storage->putLastTime($this->getName(), $execTime);
			}
		}

		return $statusCode;
	}

	/**
	 * @return bool
	 */
	private function lock()
	{
		static $lock; // static for lock until the process end
		@mkdir($this->locksDir);
		$path = sprintf('%s/cron-%s.lock', $this->locksDir, md5($this->getName()));
		$lock = fopen($path, 'w+b');

		return $lock !== FALSE && flock($lock, LOCK_EX | LOCK_NB);
	}

	/**
	 * @return boolean
	 */
	public function isDebugMode()
	{
		return $this->debugMode;
	}

}
