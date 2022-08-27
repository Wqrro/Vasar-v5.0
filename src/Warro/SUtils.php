<?php


namespace Warro;


use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use Exception;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\forms\CustomForm;

class SUtils
{

	public array $bans = array();
	public array $mutes = array();

	private array $banReasons = ['Unfair Advantage', 'Interference', 'Exploitation', 'Permission Abuse', 'Invalid Skin', 'Advertisement', 'Evasion'];
	private array $muteReasons = ['Spam', 'Toxicity', 'Advertisement'];

	public function banForm(Player $player): void
	{
		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (!$session->isStaff()) {
			return;
		}

		$onlinePlayers = array();
		foreach (Server::getInstance()->getOnlinePlayers() as $online) {
			$onlinePlayers[] = $online->getName();
		}

		$form = new CustomForm(function (Player $player, $data = null) use ($onlinePlayers): void {
			if (!is_null($data)) {
				$onlinePlayer = $onlinePlayers[$data[2]];
				$offlinePlayer = $data[3];
				$target = empty($offlinePlayer) ? $onlinePlayer : $offlinePlayer;
				$reason = $this->banReasons[$data[1]];
				$silent = $data[0] === false;
				if ($target === $player->getName()) {
					$player->sendMessage(TextFormat::RED . 'You must provide a player other than yourself.');
					return;
				}
				if (Server::getInstance()->isOp($target)) {
					$player->sendMessage(TextFormat::RED . 'That account can\'t be banned.');
					return;
				}
				if (isset($this->plugin->sutils->bans[strtolower($target)])) {
					$player->sendMessage(TextFormat::RED . 'That account is already banned.');
					return;
				}

				$this->banPlayer($target, $player, $reason, $silent);
			}
		});
		$form->setTitle('Ban');
		$form->addToggle('Silent', false);
		$form->addDropdown('Reason', $this->banReasons);
		$form->addDropdown('Online Account', $onlinePlayers);
		$form->addInput('Offline Account', 'Steve');
		$player->sendForm($form);
	}

	public function formatViolationMessage(int $violationType, string $player, int $ping, int $violations, float|int $details = 0.0): ?string
	{
		switch ($violationType) {
			case Jarvis::REACH:
				return TextFormat::DARK_GRAY . '[Jarvis] ' . TextFormat::AQUA . $player . ' violated Reach ' . TextFormat::GRAY . '[distance:' . $details . '] [ping:' . $ping . '] [x' . $violations . ']';
			case Jarvis::CPS:
				return TextFormat::DARK_GRAY . '[Jarvis] ' . TextFormat::AQUA . $player . ' violated High CPS ' . TextFormat::GRAY . '[cps:' . $details . '] [ping:' . $ping . '] [x' . $violations . ']';
			case Jarvis::TIMER:
				return TextFormat::DARK_GRAY . '[Jarvis] ' . TextFormat::AQUA . $player . ' violated Timer ' . TextFormat::GRAY . '[difference:' . $details . '] [ping:' . $ping . '] [x' . $violations . ']';
			case Jarvis::VELOCITY:
				return TextFormat::DARK_GRAY . '[Jarvis] ' . TextFormat::AQUA . $player . ' violated Velocity ' . TextFormat::GRAY . '[amount:' . $details . '] [ping:' . $ping . '] [x' . $violations . ']';
		}
		return null;
	}

	public function getBanMessage(array $data = null, string $header = Variables::BAN_HEADER): string
	{
		if (isset($data) and is_array($data)) {
			$message = $header;
			if (!is_null($data['reason'])) $message .= TextFormat::EOL . TextFormat::DARK_AQUA . 'Reason: ' . TextFormat::AQUA . $data['reason'];
			if (!is_null($data['remaining']['time']) and !is_null($data['remaining']['time_indicator'])) $message .= TextFormat::EOL . TextFormat::DARK_GREEN . 'Remaining: ' . TextFormat::GREEN . $data['remaining']['time'] . ' ' . $data['remaining']['time_indicator'];
			$message .= TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC . 'For questions or concerns refer to ' . Variables::DISCORD;
			return $message;
		}
		return Variables::BAN_ALT_HEADER . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC . 'For questions or concerns refer to ' . Variables::DISCORD;
	}

	public function getMaxBetween(int $day, int $hour, int $minute, int $second): string
	{
		if ($day <= 0 and $hour <= 0 and $minute <= 0 and $second >= 0) {
			return $second == 1 ? $second . ':' . 'second' : $second . ':' . 'seconds';
		}
		if ($day <= 0 and $hour <= 0 and $minute >= 0 and $second >= 0) {
			return $minute == 1 ? $minute . ':' . 'minute' : $minute . ':' . 'minutes';
		}
		if ($day <= 0 and $hour >= 0 and $minute >= 0 and $second >= 0) {
			return $hour == 1 ? $hour . ':' . 'hour' : $hour . ':' . 'hours';
		}
		if ($day > 0 and $hour >= 0 and $minute >= 0 and $second >= 0) {
			return $day == 1 ? $day . ':' . 'day' : $day . ':' . 'days';
		}
		return 'empty:data';
	}

	/**
	 * @throws Exception
	 */
	public function banPlayer(string $name, CommandSender|Player|string $staff, string $reason, bool $announce): void
	{
		$stringStaff = !is_string($staff) ? $staff->getName() : $staff;

		[$day, $hour] = match (strtolower($reason)) {
			'interference' => [0 * 86400, 12 * 3600],
			'exploitation' => [9 * 86400, 0 * 3600],
			'invalid skin' => [3 * 86400, 0 * 3600],
			'advertisement' => [6 * 86400, 0 * 3600],
			'punishment evasion' => [120 * 86400, 0 * 3600],
			default => [30 * 86400, 0 * 3600]
		};

		$duration = time() + $day + $hour;

		$infoArr = [
			'player' => $name,
			'occurrence' => Base::getInstance()->utils->getTime(),
			'expires' => $duration,
			'staff' => $stringStaff,
			'reason' => $reason,
		];

		Base::getInstance()->network->executeGeneric('vasar.register.ban', $infoArr);

		$this->bans[strtolower($name)] = $infoArr;

		if ($staff instanceof Player) {
			$staff->sendMessage(TextFormat::GREEN . 'You banned ' . $name . '\'s account.');
		}

		$target = Server::getInstance()->getPlayerExact($name);

		if ($target instanceof Player and $target->isConnected()) {
			$remainingTime = $duration - time();
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);

			$timeData = explode(':', $this->getMaxBetween(intval($day), intval($hour), intval($minute), intval($second)));

			$target->kick($this->getBanMessage(['reason' => $reason, 'remaining' => ['time' => $timeData[0], 'time_indicator' => $timeData[1]]]), false);
		}

		if ($announce) {
			Server::getInstance()->broadcastMessage(TextFormat::EOL . TextFormat::AQUA . $stringStaff . TextFormat::WHITE .
				' has removed ' . TextFormat::DARK_AQUA . $name . TextFormat::WHITE .
				' from Vasar for ' . TextFormat::DARK_RED . $reason . TextFormat::EOL . TextFormat::EOL);
		}

		$webhookMessage = new Message();
		$webhookMessage->setContent('');
		$embed = new Embed();
		$embed->setTitle('Account Ban');
		$embed->setColor(0xFF0000);
		$embed->setDescription('Account: **`' . $name . TextFormat::EOL . '`**Staff: **`' . $stringStaff . TextFormat::EOL . '`**Reason: **`' . $reason . '`**');
		$webhookMessage->addEmbed($embed);
		$webhook = new Webhook(Variables::PUNISHMENT_WEBHOOK);
		$webhook->send($webhookMessage);
	}

	public function unbanPlayer(string $name, CommandSender|Player|string $staff)
	{
		$stringStaff = !is_string($staff) ? $staff->getName() : $staff;

		Base::getInstance()->network->executeGeneric('vasar.remove.ban',
			[
				'player' => $name,
			]);

		unset($this->bans[strtolower($name)]);

		if ($staff instanceof Player) {
			$staff->sendMessage(TextFormat::GREEN . 'You unbanned ' . $name . '\'s account.');
		}

		$webhookMessage = new Message();
		$webhookMessage->setContent('');
		$embed = new Embed();
		$embed->setTitle('Account Unban');
		$embed->setColor(0xFF7000);
		$embed->setDescription('Account: **`' . $name . '`**' . TextFormat::EOL . 'Staff: **`' . $stringStaff . '`**');
		$webhookMessage->addEmbed($embed);
		$webhook = new Webhook(Variables::PUNISHMENT_WEBHOOK);
		$webhook->send($webhookMessage);
	}

	/**
	 * @throws Exception
	 */
	public function mutePlayer(string $name, CommandSender|Player|string $staff, string $reason): void
	{
		$stringStaff = !is_string($staff) ? $staff->getName() : $staff;

		[$day, $hour] = match (strtolower($reason)) {
			'spam' => [0 * 86400, 6 * 3600],
			'toxicity' => [9 * 86400, 24 * 3600],
			'advertisement' => [3 * 86400, 0 * 3600],
			default => [0 * 86400, 12 * 3600]
		};

		$duration = time() + $day + $hour;

		$infoArr = [
			'player' => $name,
			'occurrence' => Base::getInstance()->utils->getTime(),
			'expires' => $duration,
			'staff' => $stringStaff,
			'reason' => $reason,
		];

		Base::getInstance()->network->executeGeneric('vasar.register.mute', $infoArr);

		$this->mutes[strtolower($name)] = $infoArr;

		if ($staff instanceof Player) {
			$staff->sendMessage(TextFormat::GREEN . 'You muted ' . $name . '\'s account.');
		}

		$target = Server::getInstance()->getPlayerExact($name);

		if ($target instanceof Player and $target->isConnected()) {
			$session = Base::getInstance()->sessionManager->getSession($target);
			if ($session instanceof Session) {
				$session->muted = true;
			}
		}

		$webhookMessage = new Message();
		$webhookMessage->setContent('');
		$embed = new Embed();
		$embed->setTitle('Account Mute');
		$embed->setColor(0xFF0000);
		$embed->setDescription('Account: **`' . $name . TextFormat::EOL . '`**Staff: **`' . $stringStaff . TextFormat::EOL . '`**Reason: **`' . $reason . '`**');
		$webhookMessage->addEmbed($embed);
		$webhook = new Webhook(Variables::PUNISHMENT_WEBHOOK);
		$webhook->send($webhookMessage);
	}

	public function unmutePlayer(string $name, CommandSender|Player|string $staff)
	{
		$stringStaff = !is_string($staff) ? $staff->getName() : $staff;

		Base::getInstance()->network->executeGeneric('vasar.remove.mute',
			[
				'player' => $name,
			]);

		unset($this->bans[strtolower($name)]);

		if ($staff instanceof Player) {
			$staff->sendMessage(TextFormat::GREEN . 'You unmuted ' . $name . '\'s account.');
		}

		$target = Server::getInstance()->getPlayerExact($name);

		if ($target instanceof Player and $target->isConnected()) {
			$session = Base::getInstance()->sessionManager->getSession($target);
			if ($session instanceof Session) {
				$session->muted = false;
			}
		}

		$webhookMessage = new Message();
		$webhookMessage->setContent('');
		$embed = new Embed();
		$embed->setTitle('Account Unmute');
		$embed->setColor(0xFF7000);
		$embed->setDescription('Account: **`' . $name . '`**' . TextFormat::EOL . 'Staff: **`' . $stringStaff . '`**');
		$webhookMessage->addEmbed($embed);
		$webhook = new Webhook(Variables::PUNISHMENT_WEBHOOK);
		$webhook->send($webhookMessage);
	}
}