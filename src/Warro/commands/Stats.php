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
use function is_null;

class Stats extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('stats', TextFormat::DARK_AQUA . 'View player stats' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			$this->plugin->forms->stats($sender);
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		if (isset($args[0]) and is_null($target)) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		}
		if ($target instanceof User) {
			$targetSession = Base::getInstance()->sessionManager->getSession($sender);
			if ($targetSession->isShowMyStats()) {
				$this->plugin->forms->stats($sender, $target);
			} else {
				$sender->sendMessage(TextFormat::RED . 'This player isn\'t allowing others to view their stats.');
			}
		}
	}
}