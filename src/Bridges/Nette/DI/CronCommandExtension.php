<?php

namespace Sw2\CronCommand\Bridges\Nette\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
use Nette\Utils\Validators;
use Sw2\CronCommand\Runner;
use Sw2\CronCommand\Storage\FileStorage;

/**
 * Class CronCommandExtension
 * @package Sw2\CronCommand\Bridges\Nette\DI
 */
class CronCommandExtension extends CompilerExtension
{
	/** @var array */
	public $defaults = [
		'debugger' => FALSE,
		'tasksTag' => 'sw2.cron.task',
		'tasks' => [],
		'locksDir' => '%tempDir%/cron-locks',
		'storage' => NULL,
		'runner' => NULL,
	];

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'debugger', 'boolean');
		Validators::assertField($config, 'tasksTag', 'string');
		Validators::assertField($config, 'tasks', 'array');
		Validators::assertField($config, 'locksDir', 'string');
		Validators::assertField($config, 'storage', 'string|Nette\DI\Statement|null');
		Validators::assertField($config, 'runner', 'string|Nette\DI\Statement|null');
	}

	public function beforeCompile()
	{
		$this->prepareLocksDir();
		$this->setupStorage($this->config['storage']);
		$this->setupRunner($this->config['runner'], $this->prepareTasks());
	}

	/**
	 * @param string|Statement|null $config
	 */
	private function setupStorage($config)
	{
		$builder = $this->getContainerBuilder();
		if ($config === NULL) {
			$tempDir = $builder->parameters['tempDir'];
			$config = new Statement(FileStorage::class, ["$tempDir/cron-storage"]);
		}
		$definition = $builder->addDefinition($this->prefix('storage'));
		Compiler::loadDefinition($definition, $config);
		$definition->setAutowired(FALSE);
	}

	private function prepareLocksDir()
	{
		$parameters = $this->getContainerBuilder()->parameters;
		$this->config['locksDir'] = $locksDir = Helpers::expand($this->config['locksDir'], $parameters);
		@mkdir($locksDir, 0777, TRUE);
		if (!is_writable($locksDir)) {
			throw new Nette\IOException("Directory '$locksDir' is not writable.");
		}
	}

	/** @return array */
	private function prepareTasks()
	{
		$builder = $this->getContainerBuilder();
		$tasks = [];
		$i = 0;
		foreach ($this->config['tasks'] as $task) {
			Validators::assert($task, 'string|Nette\DI\Statement');
			$tasks[] = $name = $this->prefix('task.' . ++$i);
			$definition = $builder->addDefinition($name);
			Compiler::loadDefinition($definition, $task);
			$definition->setAutowired(FALSE);
			$definition->setInject(TRUE);
		}
		foreach ($builder->findByTag($this->config['tasksTag']) as $name => $allowed) {
			if (!$allowed) continue;
			$tasks[] = $name;
		}
		return $tasks;
	}

	/**
	 * @param string|Statement|null $config
	 * @param array $tasks
	 */
	private function setupRunner($config, array $tasks)
	{
		$builder = $this->getContainerBuilder();
		if ($config === NULL) {
			$config = new Statement(Runner::class, ['cron:runner', $this->prefix('@storage')]);
		}
		$definition = $builder->addDefinition($this->prefix('runner'));
		Compiler::loadDefinition($definition, $config);
		$definition->setAutowired(FALSE);
		$definition->addTag(ConsoleExtension::TAG_COMMAND);

		foreach ($tasks as $task) {
			$definition->addSetup('addTask', ["@$task"]);
			$builder->getDefinition($task)
				->addTag(ConsoleExtension::TAG_COMMAND)
				->addSetup('setDebugMode', [$builder->parameters['debugMode']])
				->addSetup('setLocksDir', [$this->config['locksDir']])
				->addSetup('setStorage', [$this->prefix('@storage')]);
		}
	}

}
