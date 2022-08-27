<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;

class Kill extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('kill', TextFormat::DARK_AQUA . 'Instantly kill a player.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$this->plugin->utils->executeCommand($sender, 2, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			if ($sender instanceof Player) {
				$sender->attack(new EntityDamageEvent($sender, EntityDamageEvent::CAUSE_SUICIDE, 1000));
			}
		} else {
			if ($sender->getName() !== 'CONSOLE') {
				if (!Server::getInstance()->isOp($sender->getName())) {
					$sender->sendMessage($this->getPermissionMessage());
					return;
				}
			}
			$target = Server::getInstance()->getPlayerByPrefix($args[0]);
			if (is_null($target)) {
				$sender->sendMessage(TextFormat::RED . 'That player wasn\'t found.');
				return;
			} elseif ($target instanceof Player) {
				$target->attack(new EntityDamageEvent($target, EntityDamageEvent::CAUSE_SUICIDE, 1000));
			}
		}
	}
}