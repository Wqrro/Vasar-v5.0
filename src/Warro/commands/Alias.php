<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use Warro\User;
use Warro\Variables;

class Alias extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('alias', TextFormat::DARK_AQUA . 'View any related accounts to a specific user.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.alias');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a player.');
			return;
		}
		if (Server::getInstance()->getPlayerByPrefix($args[0]) === null) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		}
		$target = Server::getInstance()->getPlayerByPrefix($args[0]);

		if ($target instanceof User) {
			$session = $this->plugin->sessionManager->getSession($target);
			if (!$session instanceof Session) {
				return;
			}

			$altAccountsIp = array();
			foreach (explode(', ', file_get_contents($this->plugin->getDataFolder() . 'aliases/' . md5($target->getNetworkSession()->getIp()), true)) as $ipAlternate) {
				if (isset($this->plugin->sutils->bans[strtolower($ipAlternate)])) {
					$altAccountsIp[] = TextFormat::RED . $ipAlternate;
				} else {
					$altAccountsIp[] = TextFormat::GREEN . $ipAlternate;
				}
			}

			$altAccountsDeviceId = array();
			foreach (explode(', ', file_get_contents($this->plugin->getDataFolder() . 'aliases/' . md5($session->getDeviceId()), true)) as $didAlternate) {
				if (isset($this->plugin->sutils->bans[strtolower($didAlternate)])) {
					$altAccountsDeviceId[] = TextFormat::RED . $didAlternate;
				} else {
					$altAccountsDeviceId[] = TextFormat::GREEN . $didAlternate;
				}
			}

			$altAccountsClientRandomId = array();
			foreach (explode(', ', file_get_contents($this->plugin->getDataFolder() . 'aliases/' . md5(strval($session->getClientRandomId())), true)) as $cidAlternate) {
				if (isset($this->plugin->sutils->bans[strtolower($cidAlternate)])) {
					$altAccountsClientRandomId[] = TextFormat::RED . $cidAlternate;
				} else {
					$altAccountsClientRandomId[] = TextFormat::GREEN . $cidAlternate;
				}
			}

			$altAccountsSelfSignedId = array();
			foreach (explode(', ', file_get_contents($this->plugin->getDataFolder() . 'aliases/' . md5($session->getSelfSignedId()), true)) as $ssidAlternate) {
				if (isset($this->plugin->sutils->bans[strtolower($ssidAlternate)])) {
					$altAccountsSelfSignedId[] = TextFormat::RED . $ssidAlternate;
				} else {
					$altAccountsSelfSignedId[] = TextFormat::GREEN . $ssidAlternate;
				}
			}

			$message = TextFormat::EOL . TextFormat::GREEN . '--- Accounts related to ' . $target->getName() . ' ---';
			$message .= TextFormat::EOL . TextFormat::GRAY . $target->getName() . '\'s IP:';
			$message .= TextFormat::EOL . TextFormat::GRAY . implode(' ' . TextFormat::GRAY . '- ', $altAccountsIp);
			$message .= TextFormat::EOL . TextFormat::GRAY . $target->getName() . '\'s DID:';
			$message .= TextFormat::EOL . TextFormat::GRAY . implode(' ' . TextFormat::GRAY . '- ', $altAccountsDeviceId);
			$message .= TextFormat::EOL . TextFormat::GRAY . $target->getName() . '\'s CID:';
			$message .= TextFormat::EOL . TextFormat::GRAY . implode(' ' . TextFormat::GRAY . '- ', $altAccountsClientRandomId);
			$message .= TextFormat::EOL . TextFormat::GRAY . $target->getName() . '\'s SSID:';
			$message .= TextFormat::EOL . TextFormat::GRAY . implode(' ' . TextFormat::GRAY . '- ', $altAccountsSelfSignedId) . TextFormat::EOL . TextFormat::EOL;

			$sender->sendMessage($message);
		}
	}
}