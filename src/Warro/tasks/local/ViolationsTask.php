<?php

declare(strict_types=1);

namespace Warro\tasks\local;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use Warro\Session;
use Warro\User;

class ViolationsTask extends Task
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
		if ($this->session->cpsViolations > 0) {
			$this->session->cpsViolations = 0;
		}
		if ($this->session->reachViolations > 0) {
			$this->session->reachViolations = 0;
		}
		if ($this->session->timerViolations > 0) {
			$this->session->timerViolations = 0;
		}
		if ($this->session->velocityViolations > 0) {
			$this->session->velocityViolations = 0;
		}
	}
}