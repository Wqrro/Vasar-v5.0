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
use function count;
use function is_null;

class Reply extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('reply', TextFormat::DARK_AQUA . 'Reply to your most recent private message.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($sender);

		if (!$session->hasMessenger()) {
			$sender->sendMessage(TextFormat::RED . 'You don\'t have any recent private messages to reply to.');
			return;
		}
		if (!$session->isPrivateMessages() and !$session->isStaff()) {
			$sender->sendMessage(TextFormat::RED . 'You have private messages disabled.');
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($session->getMessenger());

		if (is_null($target)) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			$session->setMessenger();
			return;
		}
		if (count($args) < 0) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a message.');
			return;
		}

		if ($target instanceof User) {
			$session->doPrivateMessage($target, implode(' ', $args));
		}
	}
}