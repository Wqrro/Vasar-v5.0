<?php

declare(strict_types=1);

namespace Warro\tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use Warro\Base;
use Warro\User;

class CombatTask extends Task
{

	public function __construct(private Base $plugin)
	{
	}

	public function onRun(): void
	{
		if (!empty($this->plugin->utils->taggedPlayer)) {
			foreach ($this->plugin->utils->taggedPlayer as $name => $time) {
				$player = Server::getInstance()->getPlayerExact($name);
				if ($player instanceof User) {
					if ($time <= 0) {
						if ($this->plugin->utils->isTagged($player)) {
							$this->plugin->utils->setTagged($player, false, true);
						}
					} else {
						$this->plugin->utils->taggedPlayer[$name]--;
					}
				}
			}
		}
	}
}