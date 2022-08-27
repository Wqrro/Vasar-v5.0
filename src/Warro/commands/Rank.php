<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;

class Rank extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('rank', TextFormat::DARK_AQUA . 'All in one rank command.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.rank');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if ($sender->getName() !== 'CONSOLE') {
			if (!$sender->hasPermission($this->getPermission()) or !Server::getInstance()->isOp($sender->getName())) {
				$sender->sendMessage($this->getPermissionMessage());
				return;
			}

			if (!$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
				return;
			}
		}
		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a player.');
			return;
		}
		if (!isset($args[1])) {
			$sender->sendMessage(TextFormat::RED . 'Argument [add:remove:list] required.');
			return;
		}
		if (strtolower($args[1]) !== 'list' and !isset($args[2])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a rank.');
			return;
		}
		$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		if (!$target instanceof Player) {
			$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
			return;
		}
		$session = $this->plugin->sessionManager->getSession($target);
		if (strtolower($args[1]) === 'list') {
			$sender->sendMessage(TextFormat::GREEN . $target->getDisplayName() . '\'s ranks: ' . TextFormat::YELLOW . $session->getRanks(true, true));
			return;
		}
		$newRank = $this->plugin->rankManager->getRankFromString($args[2]);
		if (is_null($newRank) or !$this->plugin->rankManager->doesRankExist($newRank)) {
			$sender->sendMessage(TextFormat::RED . 'The rank you provided is doesn\'t exist.');
			return;
		}
		if (strtolower($args[1]) === 'add') {
			$duration = 0;
			if (isset($args[3])) {
				$arg3 = strtolower($args[3]);
				if (str_contains($arg3, 'd')) {
					$duration = intval($arg3) * Variables::DAY;
				} elseif (str_contains($arg3, 'h')) {
					$duration = intval($arg3) * Variables::HOUR;
				} elseif (str_contains($arg3, 'm')) {
					$duration = intval($arg3) * Variables::MINUTE;
				} else {
					$duration = 0;
				}
			}

			if ($duration > 0) {
				$duration += time();
			}

			$session->addRank($newRank, $duration);
			$sender->sendMessage(TextFormat::GREEN . 'You added the ' . TextFormat::YELLOW . $this->plugin->rankManager->getRankAsString($newRank) . TextFormat::GREEN . ' rank to ' . TextFormat::YELLOW . $target->getDisplayName() . '\'s ' . TextFormat::GREEN . 'account.');
		} elseif (strtolower($args[1]) === 'remove') {
			$session->removeRank($newRank);
			$sender->sendMessage(TextFormat::GREEN . 'You removed the ' . TextFormat::YELLOW . $this->plugin->rankManager->getRankAsString($newRank) . TextFormat::GREEN . ' rank from ' . TextFormat::YELLOW . $target->getDisplayName() . '\'s ' . TextFormat::GREEN . 'account.');
		}
	}
}