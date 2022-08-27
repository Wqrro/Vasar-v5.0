<?php

declare(strict_types=1);

namespace Warro\tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\games\clash\Clash;

class ClashTask extends Task
{

	private ?Clash $clash = null;

	private int $seconds = 0;

	public function __construct()
	{
	}

	public function onRun(): void
	{
		if (!$this->hasClash()) {
			$this->getHandler()->cancel();
			return;
		} else {
			if ($this->getClash()->hasEnded()) {
				$this->getHandler()->cancel();
				return;
			}
		}

		if ($this->seconds + 10 === Clash::STARTING_PERIOD) {
			Server::getInstance()->broadcastMessage(TextFormat::YELLOW . '10 seconds ' . TextFormat::AQUA . 'until Clash!' . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC . $this->clash->getPlayers(true));
		}

		$this->seconds++;
	}

	public function hasClash(): bool
	{
		return !is_null($this->clash);
	}

	public function setClash(Clash $clash): void
	{
		$this->clash = $clash;
	}

	public function getClash(): ?Clash
	{
		return $this->clash;
	}
}