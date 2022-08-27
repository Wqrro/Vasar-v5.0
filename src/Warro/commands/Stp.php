<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\User;
use Warro\Variables;

class Stp extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('stp', TextFormat::DARK_AQUA . 'Teleport to any player.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.stp');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}

		$session = $this->plugin->sessionManager->getSession($sender);

		if (!$session->isVanished()) {
			$sender->sendMessage(TextFormat::RED . 'You can\'t execute this command unless you\'re in vanish.');
			return;
		}

		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a player.');
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);

		if (!$target instanceof Player) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		}

		if ($target->getName() === $sender->getName()) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a player other than yourself.');
			return;
		}

		$sender->teleport($target->getLocation());
		$sender->sendMessage(TextFormat::GREEN . 'You teleported to ' . $target->getName() . '.');
	}
}