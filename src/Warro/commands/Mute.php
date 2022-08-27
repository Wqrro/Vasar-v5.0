<?php

declare(strict_types=1);

namespace Warro\commands;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\User;
use Warro\Variables;

class Mute extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('mute', TextFormat::DARK_AQUA . 'All in one mute command.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.mute');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access.');
	}

	/**
	 * @throws Exception
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$this->plugin->utils->executeCommand($sender, 10, $this->getName())) {
			return;
		}

		if (!isset($args[0])) {
			if ($sender instanceof Player) {
				$sender->sendMessage('i didnt get to that yet sorry sir');
				//$this->plugin->sutils->muteForm($sender);
			} else {
				$sender->sendMessage(TextFormat::RED . 'You must provide an argument.');
			}
			return;
		}

		switch ($args[0]) {
			default:
				$lc = strtolower($args[0]);

				if (isset($this->plugin->sutils->mutes[$lc])) {
					$sender->sendMessage(TextFormat::RED . 'That account is already muted.');
					return;
				}

				$target = Server::getInstance()->getPlayerByPrefix($args[0]);

				if ($target instanceof Player) {
					if ($target->getName() == $sender->getName()) {
						$sender->sendMessage(TextFormat::RED . 'You must provide a player other than yourself.');
						return;
					}
				}

				if (Server::getInstance()->isOp($args[0])) {
					$sender->sendMessage(TextFormat::RED . 'That account can\'t be muted.');
					return;
				}

				if (!isset($args[1])) {
					$sender->sendMessage(TextFormat::RED . 'You must provide a reason.');
					return;
				}

				$name = $target instanceof User ? $target->getName() : $args[0];

				$reason = match (strtolower($args[1])) {
					'spam', 'spamming', 'spammer', 'annoying', 'fast message', 'fast' => 'Spam',
					'toxic', 'mean', 'rude', 'toxicity', 'racism', 'racist', 'discrimination' => 'Toxicity',
					'advertisement', 'advertising', 'ads', 'ad' => 'Advertisement',
					default => null,
				};

				if (is_null($reason)) {
					$sender->sendMessage(TextFormat::RED . 'You must provide a valid reason.');
					return;
				}

				$this->plugin->sutils->mutePlayer($name, $sender, $reason);
				break;
			case 'list':
				if (!$sender->hasPermission('vasar.mute.see')) {
					$sender->sendMessage($this->getPermissionMessage());
					return;
				}

				if (empty($this->plugin->sutils->mutes)) {
					$sender->sendMessage(TextFormat::RED . 'There are no accounts muted.');
					return;
				}

				$players = array();
				foreach ($this->plugin->sutils->mutes as $punished) {
					if (isset($punished['player'])) {
						if ($punished['expires'] < time()) {
							$this->plugin->sutils->unmutePlayer($punished['player'], 'Automatic');
						} else {
							$players[] = TextFormat::YELLOW . $punished['player'];
						}
					}
				}

				$sender->sendMessage(TextFormat::AQUA . 'Muted Losers (' . count($players) . ')');
				$sender->sendMessage(implode(', ', $players));
				break;
			case 'info':
				if (!isset($args[1])) {
					$sender->sendMessage(TextFormat::RED . 'You must provide a muted account.');
					return;
				}

				$lc = strtolower($args[1]);

				if (!isset($this->plugin->sutils->mutes[$lc])) {
					$sender->sendMessage(TextFormat::RED . 'That account isn\'t muted.');
					return;
				}

				$info = $this->plugin->sutils->mutes[$lc];
				if (isset($info['expires'])) {
					$remainingTime = $info['expires'] - time();
					$day = floor($remainingTime / 86400);
					$hourSeconds = $remainingTime % 86400;
					$hour = floor($hourSeconds / 3600);
					$minuteSec = $hourSeconds % 3600;
					$minute = floor($minuteSec / 60);
					$remainingSec = $minuteSec % 60;
					$second = ceil($remainingSec);
					$sender->sendMessage(TextFormat::AQUA . 'Mute Info For ' . $info['player']);
					$sender->sendMessage(TextFormat::YELLOW . 'Reason: ' . $info['reason']);
					$sender->sendMessage(TextFormat::YELLOW . 'Remaining: ' . $day . ' days, ' . $hour . ' hours, ' . $minute . ' minutes, ' . $second . ' seconds');
					$sender->sendMessage(TextFormat::YELLOW . 'Staff: ' . $info['staff']);
					$sender->sendMessage(TextFormat::YELLOW . 'Date: ' . $info['occurrence']);
				}
				break;
			case 'lift':
				if (!$sender->hasPermission('vasar.mute.manage')) {
					$sender->sendMessage($this->getPermissionMessage());
					return;
				}

				if (!isset($args[1])) {
					$sender->sendMessage(TextFormat::RED . 'You must provide a muted account.');
					return;
				}

				$lc = strtolower($args[1]);

				if (!isset($this->plugin->sutils->mutes[$lc])) {
					$sender->sendMessage(TextFormat::RED . 'That account isn\'t muted.');
					return;
				}

				$this->plugin->sutils->unmutePlayer($lc, $sender);
				break;
		}
	}
}