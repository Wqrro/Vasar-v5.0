<?php


namespace Warro\handlers;

use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\player\Player;
use function mt_rand;
use function spl_object_id;

final class NetworkStackLatencyManager
{

	private array $list = [];

	private static ?NetworkStackLatencyManager $instance = null;

	public static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function send(Player $player, callable $onReceive): void
	{
		$pk = new NetworkStackLatencyPacket();
		$pk->needResponse = true;
		$pk->timestamp = mt_rand(1, 10000000000) * 1000; // when the client sends this back, the timestamp is always divisible evenly with 1000
		$this->list[spl_object_id($player)][$pk->timestamp] = $onReceive;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function execute(Player $player, int $timestamp): void
	{
		$callable = $this->list[spl_object_id($player)][$timestamp] ?? null;
		if ($callable !== null) {
			$callable($timestamp);
			unset($this->list[spl_object_id($player)][$timestamp]);
		}
	}

	public function remove(Player $player): void
	{
		unset($this->list[spl_object_id($player)]);
	}

}