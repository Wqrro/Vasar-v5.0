<?php

declare(strict_types=1);

namespace Warro\tasks\async;

use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Warro\Base;
use Warro\Session;
use Warro\Variables;

class BanVerificationTask extends AsyncTask
{

	private const KEY = 'bans';

	public function __construct(private string $player, private array $bans, private string $ipDir, private string $didDir, private string $cidDir, private string $ssidDir)
	{
		$this->storeLocal(self::KEY, $bans);
	}

	public function onRun(): void
	{
		$array = (array)$this->bans;

		if (is_array($array)) {
			$ipPath = file_get_contents($this->ipDir, true);
			if (is_string($ipPath) and !$this->hasResult()) {
				foreach (explode(', ', $ipPath) as $accounts) {
					if (isset($this->bans[strtolower($accounts)])) {
						$this->setResult($accounts);
						break;
					}
				}
			}

			$didPath = file_get_contents($this->didDir, true);
			if (is_string($didPath) and !$this->hasResult()) {
				foreach (explode(', ', $didPath) as $accounts) {
					if (isset($this->bans[strtolower($accounts)])) {
						$this->setResult($accounts);
						break;
					}
				}
			}

			$cidPath = file_get_contents($this->cidDir, true);
			if (is_string($cidPath) and !$this->hasResult()) {
				foreach (explode(', ', $cidPath) as $accounts) {
					if (isset($this->bans[strtolower($accounts)])) {
						$this->setResult($accounts);
						break;
					}
				}
			}

			$ssidPath = file_get_contents($this->ssidDir, true);
			if (is_string($ssidPath) and !$this->hasResult()) {
				foreach (explode(', ', $ssidPath) as $accounts) {
					if (isset($this->bans[strtolower($accounts)])) {
						$this->setResult($accounts);
						break;
					}
				}
			}
		}
	}

	public function onCompletion(): void
	{
		if (!$this->hasResult()) {
			return;
		}
		$result = $this->getResult();

		$header = ($this->player !== $result) ? Variables::BAN_ALT_HEADER : Variables::BAN_HEADER;

		$banInfo = $this->bans[strtolower($result)];
		if ($banInfo['expires'] < time()) {
			Base::getInstance()->sutils->unbanPlayer($result, 'Automatic');
			return;
		}

		$player = Server::getInstance()->getPlayerExact($this->player);
		if (!$player instanceof Player) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);
		if (!$session instanceof Session) {
			return;
		}

		Server::getInstance()->unsubscribeFromAllBroadcastChannels($player);

		$remainingTime = $banInfo['expires'] - time();
		$day = floor($remainingTime / 86400);
		$hourSeconds = $remainingTime % 86400;
		$hour = floor($hourSeconds / 3600);
		$minuteSec = $hourSeconds % 3600;
		$minute = floor($minuteSec / 60);
		$remainingSec = $minuteSec % 60;
		$second = ceil($remainingSec);
		$timeData = explode(':', Base::getInstance()->sutils->getMaxBetween(intval($day), intval($hour), intval($minute), intval($second)));
		$session->banned = Base::getInstance()->sutils->getBanMessage(['reason' => $banInfo['reason'], 'remaining' => ['time' => $timeData[0], 'time_indicator' => $timeData[1]]], $header);
	}
}