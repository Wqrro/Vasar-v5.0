<?php

declare(strict_types=1);

namespace Warro\tasks\local;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Warro\Session;
use Warro\User;

class BaseTask extends Task
{

	public function __construct(private Session $session, private Player|User $player)
	{
	}

	public function onRun(): void
	{
		if (!$this->player->isConnected()) {
			$this->getHandler()->cancel();
			return;
		}

		if (is_string($this->session->banned)) {
			$this->player->kick($this->session->banned);
			$this->getHandler()->cancel();
			return;
		}

		if (!$this->session->isAfk()) {
			$lastMove = $this->session->getLastMove();
			if (is_int($lastMove)) {
				if (time() - $lastMove >= 10) {
					$this->session->setAfk(true);
				}
			}
		} else {
			return;
		}

		if ($this->session->hasRespawnTimerStarted()) {
			$this->session->decreaseRespawnTimer();
		}

		foreach ($this->player->getHidden() as $key => $value) {
			if (is_string($key) and is_int($value)) {
				if (time() - $value >= 10) {
					unset($this->player->getHidden()[$key]);
					$player = Server::getInstance()->getPlayerExact($key);
					if (!is_null($player)) {
						$this->player->showThem($player);
					}
				}
			}
		}
	}
}