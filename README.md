CronCommand - automatized cron tasks
====================================

If you are familiar with [Nette Framework][1] and [Symfony Console][2] (especially [implementation by Kdyby][3]), 
you probably had idea to used it for cron operations. This tool will help you with maintenance of cron tasks.


Requirements
------------

This library requires PHP 5.5 or higher. CronCommand library is designed for [Nette Framework][1]; however, it can 
be also used with other frameworks or pure PHP.


Installation
------------

The best way to install this library is using  [Composer](http://getcomposer.org/):

```sh
$ composer require sw2eu/cron-command
```


Documentation
-------------

Firstly, register extension `Sw2\CronCommand\Bridges\Nette\DI\CronCommandExtension`. For more information about
configuration see the class definition. Some features would not work in version 0.8.x (simply - they are not yet been
implemented), so please be patient and [stay tuned][4].

```yaml
extension:
    cron: Sw2\CronCommand\Bridges\Nette\DI\CronCommandExtension
```

Now define your first task. Here is full source code of my AwesomeTask:

```php
<?php

namespace App\Commands;

use Sw2\CronCommand\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AwesomeTask
 * @package App\Commands
 *
 * @cron 0 0 * * 0
 */
class AwesomeTask extends Task
{
	/** @var MySuperAwesomeService @inject */
	public $youCanInjectYourServices;

	protected function configure()
	{
		$this->setName('cron:be-awesome');
		$this->setDescription('Totally awesome task that would totally change your life');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// do something awesome
		$output->writeln("<info>I am awesome task!</info>");
	}

}
```

Annotation `@cron` use the same syntax as you know from crontab. For the parsing of the cron expression is used 
library [mtdowling/cron-expression][5] where you can find more information about syntax. Syntax with `@` is not yet
supported (maybe in the future).

So now register your task to the cron runner:

```yaml
cron:
	tasks:
		- App\Commands\AwesomeTask
```

This way you can create service and register it right to the runner. Tasks in `CronCommandExtension` works in the same
way like services in Nette Framework. You can define just class name, or define the whole service with arguments,
setup, etc. The other way how to register your task is much more automatic: Just create service and tag it with 
defined tag (default tag is `sw2.cron.task` but can be also configured) like this:

```yaml
services:
	- class: App\Commands\AwesomeTask
	  tags: [sw2.cron.task]
```

You need not define `kdyby.console.command`, it will be added automatically. So this mean, that every cron task is 
automatically also console command -- can be started manually if needed.

Now you can run your first cron task using console command `cron:runner`.

```sh
$ php www/index.php cron:runner
```

This library implements locking system. Every task can run only once at a time. If the task is running, you cannot 
start it manually and vice versa.

Advanced documentation will be added in the future. But if you look on my source codes, you will understand what you 
can do with this small (but powerful) library.


[1]: https://github.com/nette/nette
[2]: https://github.com/symfony/Console
[3]: https://github.com/Kdyby/Console
[4]: https://github.com/sw2eu/cron-command/stargazers
[5]: https://github.com/mtdowling/cron-expression
