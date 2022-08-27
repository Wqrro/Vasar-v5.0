<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\games\clash\Clash;
use Warro\Variables;

class Spectate extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('spectate', TextFormat::DARK_AQUA . 'Spectate Clash when it\'s active, or soon, Duels.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player or !$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}

		$clash = $this->plugin->utils->clash;
		if (!$clash instanceof Clash) {
			if (is_null($clash)) {
				$sender->sendMessage(TextFormat::RED . 'Clash is not active at the moment, please try again later.');
			} elseif (is_int($clash)) {
				$sender->sendMessage(TextFormat::RED . 'Clash is in intermission for another ' . abs(time() - $clash) . ' seconds.');
			}
			return;
		}
		//$clash->addSpectator($sender); // TODO
	}
}