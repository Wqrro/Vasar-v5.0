<?php

declare(strict_types=1);

namespace Warro\commands;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Variables;

class Nick extends Command
{

	public function __construct(private Base $plugin)
	{
		parent::__construct('nick', TextFormat::DARK_AQUA . 'Change your nickname.' . TextFormat::RESET . TextFormat::AQUA . ' [' . Variables::DISCORD . ']');
		$this->setPermission('vasar.command.nick');
		$this->setPermissionMessage(TextFormat::RED . 'Insufficient access: You require Vasar Plus to access this command, please refer to ' . Variables::STORE . '.');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage($this->getPermissionMessage());
			return;
		}

		if (!$sender instanceof Player or !$this->plugin->utils->executeCommand($sender, 10, $this->getName())) {
			return;
		}

		if (!Server::getInstance()->isOp($sender->getName())) {
			if ($this->plugin->utils->isTagged($sender)) {
				$sender->sendMessage(TextFormat::RED . 'Please wait until you\'re out of combat.');
				return;
			}
		}
		if (!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . 'You must provide a nickname.');
			return;
		}
		$nick = str_replace(' ', '', $args[0]);
		$nickLowercase = strtolower($nick);
		foreach (array_merge($this->plugin->utils->getIllegalWords(), $this->plugin->utils->getCommonPlayers()) as $illegalWord) {
			if (str_contains($nickLowercase, $illegalWord)) {
				$sender->sendMessage(TextFormat::RED . 'You must provide an appropriate nickname, otherwise your account could be banned and you may lose your rank.');
				return;
			}
		}
		foreach (Server::getInstance()->getOnlinePlayers() as $online) {
			if ($nickLowercase === strtolower($online->getDisplayName()) or $nickLowercase === strtolower($online->getName())) {
				$sender->sendMessage(TextFormat::RED . 'You can\'t use that as a nickname.');
				return;
			}
		}
		if (!ctype_alnum($nick)) {
			$sender->sendMessage(TextFormat::RED . 'Your nickname can only contain letters and numbers.');
			return;
		}
		if (strlen($nick) < 3) {
			$sender->sendMessage(TextFormat::RED . 'Your nickname can\'t have less than 3 characters.');
			return;
		}
		if (strlen($nick) > 13) {
			$sender->sendMessage(TextFormat::RED . 'Your nickname can\'t have more than 13 characters.');
			return;
		}
		switch ($nickLowercase) {
			case 'off':
			case 'stop':
			case 'cancel':
			case 'close':
			case 'null':
			case 'reset':
			case 'disable':
			case 'remove':
			case 'empty':
				if ($sender->getName() === $sender->getDisplayName()) {
					$sender->sendMessage(TextFormat::RED . 'You aren\'t nicked.');
					return;
				}
				$sender->setDisplayName($sender->getName());
				$sender->sendMessage(TextFormat::GREEN . 'You cleared your nickname.');
				break;
			default:
				$sender->setDisplayName($args[0]);
				$sender->sendMessage(TextFormat::GREEN . 'You set your nickname to ' . TextFormat::YELLOW . $sender->getDisplayName() . TextFormat::GREEN . '.');

				$webhookMessage = new Message();
				$webhookMessage->setContent('');
				$embed = new Embed();
				$embed->setTitle('Player Nick');
				$embed->setColor(0x4a79b5);
				$embed->setDescription('Player: **`' . $sender->getName() . '`**' . TextFormat::EOL . 'Nick: **`' . $args[0] . '`**');
				$webhookMessage->addEmbed($embed);
				$webhook = new Webhook(Variables::NICK_WEBHOOK);
				$webhook->send($webhookMessage);
				break;
		}
		$sender->setNameTag($this->plugin->utils->getTagFormat($sender));
		$sender->respawnToAll();
	}
}