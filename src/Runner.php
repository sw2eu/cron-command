<?php

namespace Sw2\CronCommand;

use Cron\CronExpression;
use Nette\Reflection\ClassType;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Sw2\CronCommand\Storage\IStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Runner
 * @package Sw2\CronCommand
 */
class Runner extends Command
{
	/** @var IStorage */
	protected $storage;

	/** @var Task[] */
	protected $tasks = [];

	/**
	 * @param string $name
	 * @param IStorage $storage
	 */
	public function __construct($name, IStorage $storage)
	{
		parent::__construct($name);
		$this->storage = $storage;
	}

	public function addTask(Task $task)
	{
		$this->tasks[] = $task;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$execTime = new DateTime;
		$output->writeln(sprintf('<info>Cron runner started at %s</info>', $execTime->format('H:i:s')));

		foreach ($this->tasks as $task) {
			try {
				if ($this->shouldStart($execTime, $task)) {
					$statusCode = $task->run($input, $output, $execTime);
					if ($statusCode !== 0) {
						$output->writeln(sprintf('<error>Status code %d</error> in task %s', $statusCode, $task->getName()));
						return $statusCode;
					}
				}
			}
			catch (\Exception $e) {
				$output->writeln(sprintf('<error>Error in task %s</error> - %s', $task->getName(), $e->getMessage()));
			}
		}

		$output->writeln(sprintf('<info>Cron runner finished at %s</info>', (new DateTime)->format('H:i:s')));
		return 0;
	}

	/**
	 * @param DateTime $time
	 * @param Task $task
	 * @return bool
	 */
	private function shouldStart(DateTime $time, Task $task)
	{
		$execTime = $time->modifyClone('+ 3 seconds');
		$lastTime = $this->storage->getLastTime($task->getName());
		$annotations = ClassType::from($task)->getAnnotations();
		if ($lastTime === NULL) return isset($annotations['cron']);

		$annotations = ClassType::from($task)->getAnnotations();
		foreach ($annotations['cron'] as $expression) {
			$expression = CronExpression::factory(Strings::replace($expression, '~\\\\~', '/'));
			$next = $expression->getNextRunDate($lastTime)->getTimestamp();

			if ($execTime->getTimestamp() > $next) {
				return TRUE;
			}
		}

		return FALSE;
	}

}
