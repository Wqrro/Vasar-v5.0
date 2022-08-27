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
use function array_shift;
use function count;
use function is_null;

class Message extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('message', TextFormat::DARK_AQUA . 'Private message a player.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($sender);

		if (!$session->isPrivateMessages() and !$session->isStaff()) {
			$sender->sendMessage(TextFormat::RED . 'You have private messages disabled.');
			return;
		}
		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a player.');
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix(array_shift($args));

		if (is_null($target)) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		}
		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a message.');
			return;
		}

		if ($target instanceof User) {
			$session->doPrivateMessage($target, implode(' ', $args));
		}
	}
}