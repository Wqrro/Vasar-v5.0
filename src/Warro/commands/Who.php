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

class Who extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('who', TextFormat::DARK_AQUA . 'View various details on a player.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.who');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if ($sender->getName() !== 'CONSOLE') {
			if (!Server::getInstance()->isOp($sender->getName())) {
				$sender->sendMessage($this->getPermissionMessage());
				return;
			}
		}

		if (!$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			if ($sender instanceof User) {
				$target = $sender;
			}
		}

		if (!isset($target)) {
			$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		}

		if (is_null($target)) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		} elseif ($target instanceof User) {
			$session = $this->plugin->sessionManager->getSession($target);

			$operatingSystem = $session->getOperatingSystem(true);

			$message = TextFormat::EOL . TextFormat::GREEN . '--- ' . $target->getName() . '\'s collected details ---';
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Nickname: ' . TextFormat::WHITE . ($target->getDisplayName() === $target->getName() ? '?' : $target->getDisplayName());
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Operator: ' . TextFormat::WHITE . (Server::getInstance()->isOp($target->getName()) ? 'Yes' : 'No');
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Invisible: ' . TextFormat::WHITE . ($target->isInvisible() ? 'Yes' : 'No');
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Damager: ' . TextFormat::WHITE . (is_null($session->getDamager()) ? '?' : $session->getDamager());
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Location: ' . TextFormat::WHITE . '[' . round($target->getLocation()->getX(), 2) . ' | ' . round($target->getLocation()->getY(), 2) . ' | ' . round($target->getLocation()->getZ(), 2) . ']' . ' @ \'' . $target->getWorld()?->getFolderName() . '\'';
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Ranks: ' . TextFormat::WHITE . '[' . $session->getRanks(true, true, true) . ']';
			$message .= TextFormat::EOL . TextFormat::DARK_GRAY . '-#-';
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Input Mode ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getInputMode(true);
			$message .= TextFormat::EOL . TextFormat::GRAY . 'Operating System ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $operatingSystem;
			if ($operatingSystem !== 'Windows') {
				$message .= TextFormat::EOL . TextFormat::GRAY . 'Device Model ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getDeviceModel();
			}
			//$message .= TextFormat::EOL . TextFormat::GRAY . 'Server Address ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getServerAddress();
			$message .= TextFormat::EOL . TextFormat::GRAY . 'DID ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getDeviceId();
			$message .= TextFormat::EOL . TextFormat::GRAY . 'CID ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getClientRandomId();
			$message .= TextFormat::EOL . TextFormat::GRAY . 'SSID ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getSelfSignedId() . TextFormat::EOL . TextFormat::EOL;
			//$message .= TextFormat::EOL . TextFormat::GRAY . 'Game Version ' . TextFormat::MINECOIN_GOLD . '~' . TextFormat::GRAY . ': ' . TextFormat::WHITE . $session->getServerAddress();

			$sender->sendMessage($message);
		}
	}
}