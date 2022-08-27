<?php

declare(strict_types=1);

namespace Warro\store;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use Warro\Variables;

class Plus extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('storePlus', TextFormat::AQUA . '[' . Variables::DISCORD . ']');
		$this->setPermission('vasar.store.plus');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof ConsoleCommandSender) {
			return;
		}

		if (!isset($args[0]) or !isset($args[1]) or !isset($args[2])) {
			return;
		}

		$target = Server::getInstance()->getPlayerExact($args[0]);

		$isPerm = $args[2] == -1;

		$durationSave = ($isPerm) ? (0) : (intval($args[2]) * Variables::DAY);

		if (is_null($target)) {
			// Add support for stacking durations, right now this will just duplicate the rank if it's bought while one or more exists.
			//$this->plugin->network->executeGeneric('vasar.add.ranks', ['player' => $args[0], 'ranks' => ',' . $rank]);

			$this->plugin->network->executeSelect('vasar.get.ranks', ['player' => $args[0]], function ($rows) use ($args, $isPerm, $durationSave) {
				$rankAsString = $this->plugin->rankManager->getRankAsString(intval($args[1]));

				if (empty($rows)) {
					$this->plugin->network->executeGeneric('vasar.register.player.ranks', ['player' => $args[0], 'ranks' => $rankAsString . ':' . $durationSave += time()]);
					return;
				}

				foreach ($rows as $row) {
					foreach (explode(',', $row['ranks']) as $rankArray) {
						$rankDetails = explode(':', $rankArray);

						$key = $this->plugin->rankManager->getRankFromString($rankDetails[0]);
						if (!is_null($key)) {
							if ($this->plugin->rankManager->doesRankExist($key)) {
								$saveArray[$key] = [$rankDetails[0], intval($rankDetails[1])];
							}
						}

						if ($rankDetails[0] === 'Plus') {
							$newDuration = (intval($rankDetails[1]) > 0) ? (intval($rankDetails[1]) + $durationSave) : (0);
						}
					}
				}

				if (!isset($newDuration)) {
					$this->plugin->network->executeGeneric('vasar.add.ranks', ['player' => $args[0], 'ranks' => ',' . $rankAsString . ':' . $durationSave += time()]);
					return;
				}

				$newDuration = $isPerm ? 0 : $newDuration;

				if (isset($saveArray)) {
					foreach ($saveArray as $key => $value) {
						if (is_array($value)) {
							if ($value[0] === 'Plus') {
								$saveArray[$key] = $value[0] . ':' . $newDuration;
							} else {
								$saveArray[$key] = $value[0] . ':' . $value[1];
							}
						}
					}
					$this->plugin->network->executeGeneric('vasar.set.ranks', ['player' => $args[0], 'ranks' => implode(',', $saveArray)]);
				}
			});
		} elseif ($target instanceof Player) {
			$session = $this->plugin->sessionManager->getSession($target);

			if ($session instanceof Session) {
				$session->addRank(intval($args[1]), $durationSave);
			}
		}
	}
}