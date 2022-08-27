<?php

declare(strict_types=1);

namespace Warro\tasks\async;

use pocketmine\scheduler\AsyncTask;

class InitializePlayerAliasTask extends AsyncTask
{

	public function __construct(private string $path, private string $name)
	{
	}

	public function onRun(): void
	{
		if (file_exists($this->path)) {
			$file = explode(', ', file_get_contents($this->path, true));
			if (!in_array($this->name, $file)) {
				file_put_contents($this->path, $this->name . ', ', FILE_APPEND);
			}
		} else {
			file_put_contents($this->path, $this->name . ', ');
		}
	}
}