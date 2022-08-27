<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use Warro\Variables;

class Fly extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('fly', TextFormat::DARK_AQUA . 'Manage your flight in spawn.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.fly');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access: You require Vasar Plus to access this command, please refer to ' . Variables::STORE . '.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$sender instanceof Player or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($sender);

		if ($session->getCurrentWarp() !== Session::WARP_SPAWN) {
			$sender->sendMessage(TextFormat::RED . 'You can\'t use that here.');
			return;
		}

		if (!$sender->getAllowFlight()) {
			$sender->setAllowFlight(true);
			$sender->setFlying(true);

			$sender->sendMessage(TextFormat::GREEN . 'You enabled flying.');
		} else {
			$sender->setAllowFlight(false);
			$sender->setFlying(false);

			$sender->sendMessage(TextFormat::GREEN . 'You disabled flying.');
		}
	}
}