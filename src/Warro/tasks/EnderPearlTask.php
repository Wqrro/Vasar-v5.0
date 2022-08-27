<?php

declare(strict_types=1);

namespace Warro\tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use Warro\Base;
use Warro\User;
use Warro\Variables;

class EnderPearlTask extends Task
{

	public function __construct(private Base $plugin)
	{
	}

	public function onRun(): void
	{
		if (!empty($this->plugin->utils->pearlPlayer)) {
			foreach ($this->plugin->utils->pearlPlayer as $name => $time) {
				$player = Server::getInstance()->getPlayerExact($name);
				if ($player instanceof User) {
					if ($time <= 0) {
						if ($this->plugin->utils->isInPearlCooldown($player)) {
							$this->plugin->utils->setPearlCooldown($player, false, true);
						}
						$player->getXpManager()->setXpAndProgress(0, 0);
					} else {
						$percent = floatval($time / Variables::PEARL_COOLDOWN);
						$player->getXpManager()->setXpAndProgress(intval($time / 20), $percent);
						$this->plugin->utils->pearlPlayer[$name]--;
					}
				}
			}
		}
	}
}