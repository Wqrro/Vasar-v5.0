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

class Staff extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('staff', TextFormat::DARK_AQUA . 'Ever wonder what Staff are online?' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$this->plugin->utils->executeCommand($sender, 10, $this->getName())) {
			return;
		}

		$message = TextFormat::EOL . TextFormat::DARK_PURPLE . '--- Online Staff ---';
		$count = 0;

		foreach ($this->plugin->utils->onlineStaff as $pl) {
			if (is_string($pl)) {
				$player = Server::getInstance()->getPlayerExact($pl);
				if ($player instanceof User and $player->isConnected()) {
					$session = $this->plugin->sessionManager->getSession($player);
					if ($session instanceof Session) {
						if (!$session->isDisguised() and !$session->isVanished()) {
							$message .= TextFormat::EOL . TextFormat::WHITE . '- ' . $this->plugin->utils->getTagFormat($player) . TextFormat::RESET;
							$count++;
						}
					}
				}
			}
		}

		$message .= TextFormat::EOL . TextFormat::EOL;
		$count > 0 ? $sender->sendMessage($message) : $sender->sendMessage(TextFormat::RED . 'Jarvis never sleeps.');
	}
}