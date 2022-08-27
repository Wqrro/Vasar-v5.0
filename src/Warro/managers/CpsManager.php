<?php

declare(strict_types=1);

namespace Warro\managers;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use function array_filter;
use function array_pop;
use function array_unshift;
use function count;
use function microtime;
use function round;

class CpsManager
{

	private array $clicks = [];

	public function doesPlayerExist(Player $player): bool
	{
		return isset($this->clicks[$player->getName()]);
	}

	public function addPlayer(Player $player)
	{
		if (!$this->doesPlayerExist($player)) {
			$this->clicks[$player->getName()] = [];
		}
	}

	public function removePlayer(Player $player)
	{
		if ($this->doesPlayerExist($player)) {
			unset($this->clicks[$player->getName()]);
		}
	}

	public function addClick(Player $player)
	{
		array_unshift($this->clicks[$player->getName()], microtime(true));
		if (count($this->clicks[$player->getName()]) >= 27) {
			array_pop($this->clicks[$player->getName()]);
		}

		$session = Base::getInstance()->sessionManager->getSession($player);
		if ($session instanceof Session) {
			if ($session->isCpsCounter()) {
				$player->sendTip(TextFormat::WHITE . abs($this->getCps($player)) . ' CPS');
			}
		}
	}

	public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1): float
	{
		if (!$this->doesPlayerExist($player) or empty($this->clicks[$player->getName()])) {
			return 0.0;
		}

		$mt = microtime(true);

		return round(count(array_filter($this->clicks[$player->getName()], static function (float $t) use ($deltaTime, $mt): bool {
				return ($mt - $t) <= $deltaTime;
			})) / $deltaTime, $roundPrecision);
	}
}