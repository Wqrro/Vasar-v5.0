<?php

declare(strict_types=1);

namespace Warro\games;

use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use Warro\Base;
use Warro\User;

class FreeForAll
{

	private array $players = array();

	public function __construct(Base $plugin, private string $name, private string $world, private int $maxPlayers = 15, private string $texture = '', private ?Item $item = null)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getWorld(): string
	{
		return $this->world;
	}

	public function getMaxPlayers(): int
	{
		return $this->maxPlayers;
	}

	public function isFull(): bool
	{
		return $this->getPlayers(true) >= $this->getMaxPlayers();
	}

	public function getTexture(): string
	{
		return $this->texture;
	}

	public function getItem(): ?Item
	{
		return $this->item;
	}

	public function getPlayers(bool $asCount = false, bool $asPlayer = false): array|int
	{
		if ($asPlayer) {
			$array = array();
			foreach ($this->players as $p) {
				if (is_string($p)) {
					$player = Server::getInstance()->getPlayerExact($p);
					if ($player instanceof Player and $player->isConnected()) {
						$array[$player->getName()] = $player;
					}
				}
			}
		} else {
			$array = $this->players;
		}


		if ($asCount) {
			return count($array);
		}
		return $array;
	}

	public function hasPlayer(Player $player): bool
	{
		return isset($this->players[$player->getName()]);
	}

	public function addPlayer(Player $player): bool
	{
		if ($this->hasPlayer($player)) {
			return false;
		}

		if ($this->isFull()) {
			return false;
		}

		$this->players[$player->getName()] = $player->getName();
		return true;
	}

	public function removePlayer(Player $player): bool
	{
		if (!$this->hasPlayer($player)) {
			return false;
		}

		unset($this->players[$player->getName()]);
		return true;
	}
}