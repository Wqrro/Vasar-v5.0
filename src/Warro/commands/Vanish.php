<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\User;
use Warro\Variables;

class Vanish extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('vanish', TextFormat::DARK_AQUA . 'Become invisible to all.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.vanish');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 10, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}

		$this->plugin->utils->setVanished($sender, !$this->plugin->utils->isInVanish($sender));
	}
}