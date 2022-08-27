<?php

declare(strict_types=1);

namespace Warro\store;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Variables;

class Broadcast extends Command
{

	public function __construct()
	{
		parent::__construct('storeBroadcast', TextFormat::AQUA . '[' . Variables::DISCORD . ']');
		$this->setPermission('vasar.store.broadcast');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof ConsoleCommandSender) {
			return;
		}

		if (!isset($args[0]) and !isset($args[1]) and !isset($args[2])) {
			return;
		}

		Server::getInstance()->broadcastMessage($args[0] . ' purchased ' . $args[1] . ' for ' . $args[2] . '!');
	}
}