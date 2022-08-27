<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;

class Spawn extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('spawn', TextFormat::DARK_AQUA . 'Teleport to the server spawn.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}

		$this->plugin->utils->teleport($sender, 0, true);
	}
}