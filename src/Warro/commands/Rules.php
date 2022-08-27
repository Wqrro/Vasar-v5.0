<?php

declare(strict_types=1);

namespace Warro\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;

class Rules extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('rules', TextFormat::DARK_AQUA . 'Learn more about Vasar\'s in-game rules.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$plugin->utils->allowedFrozenCommands[$this->getName()] = $this->getName();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if ($sender instanceof Player) {
			$this->plugin->forms->rules($sender);
		}
	}
}