<?php

declare(strict_types=1);

namespace Warro\tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Warro\Base;

class NoteTask extends Task
{

	private int $recentNote = -1;

	public function __construct(private Base $plugin)
	{
	}

	public function onRun(): void
	{
		$key = array_rand($this->plugin->utils->notes);
		$message = $this->plugin->utils->notes[$key];
		while ($this->recentNote === $key) {
			$key = array_rand($this->plugin->utils->notes);
			$message = $this->plugin->utils->notes[$key];
		}

		$this->recentNote = $key;

		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
			if ($player instanceof Player and $player->spawned and $player->isConnected()) {
				$player->sendMessage($message);
			}
		}
	}
}