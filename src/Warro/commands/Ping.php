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

class Ping extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('ping', TextFormat::DARK_AQUA . 'View the latency of a player.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			if ($sender instanceof Player) {
				$sender->sendMessage(TextFormat::GREEN . 'You have a latency of ' . TextFormat::YELLOW . $sender->getNetworkSession()->getPing() . 'ms' . TextFormat::GREEN . '.');
			}
		} else {
			$target = Server::getInstance()->getPlayerByPrefix($args[0]);
			if (is_null($target)) {
				$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
				return;
			} elseif ($target instanceof Player) {
				$sender->sendMessage(TextFormat::GREEN . $target->getDisplayName() . ' has a latency of ' . TextFormat::YELLOW . $target->getNetworkSession()->getPing() . 'ms' . TextFormat::GREEN . '.');
			}
		}
	}
}