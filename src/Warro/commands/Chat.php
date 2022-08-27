<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;
use function strtolower;
use function ucfirst;

class Chat extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('chat', TextFormat::DARK_AQUA . 'Handle your current chat room.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($sender);

		$chats['public'] = 'Public';
		if ($session->isStaff()) {
			$chats['staff'] = 'Staff';
		}

		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::GREEN . 'Chat Rooms: ' . implode(', ', $chats));
			return;
		}

		$lc = strtolower($args[0]);
		if (!isset($chats[$lc])) {
			$sender->sendMessage(TextFormat::RED . 'That chat room doesn\'t exist.');
			return;
		}

		if ($session->getChat() === $lc) {
			$sender->sendMessage(TextFormat::RED . 'Your chat room is already set to ' . ucfirst($args[0]) . '.');
			return;
		}

		$uc = ucfirst($args[0]);

		//$message = str_repeat(TextFormat::EOL, 100);
		//$message .= TextFormat::GRAY . TextFormat::ITALIC . 'Chat Room: ' . $uc . TextFormat::EOL;
		$message = TextFormat::RESET . TextFormat::GREEN . 'You set your chat room to ' . $uc . '.';

		$sender->sendMessage($message);

		$session->setChat(strtolower($args[0]));
	}
}