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

class Rekit extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('rekit', TextFormat::DARK_AQUA . 'Replenish your current Free For All Kit.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof User or !$this->plugin->utils->executeCommand($sender, 10, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}
		if ($this->plugin->utils->isInFfa($sender)) {
			$session = Base::getInstance()->sessionManager->getSession($sender);

			$this->plugin->utils->kit($sender, $session->getRecentKit());
			$sender->sendMessage(TextFormat::GREEN . 'You replenished your current kit.');
		}
	}
}