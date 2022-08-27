<?php

declare(strict_types=1);

namespace Warro\games\clash;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use Warro\tasks\ClashTask;
use Warro\User;

class Clash
{

	public const INTERMISSION_PERIOD = 60;

	public const STARTING_PERIOD = 30;

	private int $started = 0;

	private array $players = array();

	private ?string $winner = null;

	public function __construct()
	{
	}

	public function getPlayers(bool $formatted = false): string|array
	{
		if ($formatted) {
			return implode(' vs ', $this->players);
		}
		return $this->players;
	}

	public function needPlayer(): bool
	{
		$count = count($this->players);
		return $count <= 1 and $count < 2;
	}

	public function arePlayersReady(): bool
	{
		if ($this->needPlayer()) {
			return false;
		}

		foreach ($this->players as $players) {
			if ($players instanceof User) {
				$session = Base::getInstance()->sessionManager->getSession($players);
				if (!$players->isConnected() or $players->isImmobile() or $session?->getCurrentWarp() !== Session::WARP_SPAWN) {
					return false;
				}
			}
		}

		return true;
	}

	public function addPlayer(User $player): bool
	{
		if (!$this->isIdle()) {
			return false;
		}

		$result = false;

		if (!$this->hasPlayerVolunteered($player) and $this->needPlayer()) {
			$this->players[$player->getName()] = $player->getName();
			$result = true;
		}

		if ($this->canStart()) {
			$this->started = 1;

			Server::getInstance()->broadcastMessage(TextFormat::AQUA . 'Clash will begin soon, the contestants will be announced.');

			$instance = Base::getInstance();

			$task = new ClashTask();
			$task->setClash($this);

			$instance->getScheduler()->scheduleRepeatingTask($task, 20);
		}

		return $result;
	}

	public function removePlayer(User $player): void
	{
		if (!$this->isIdle()) {
			return;
		}

		if ($this->hasPlayerVolunteered($player)) {
			unset($this->players[$player->getName()]);
		}

		if (!$this->arePlayersReady()) {
			$this->end();
		}
	}

	public function hasPlayerVolunteered(User $player): bool
	{
		return isset($this->players[$player->getName()]);
	}

	public function isIdle(): bool
	{
		return $this->started === 0;
	}

	public function isStarting(): bool
	{
		return $this->started === 1;
	}

	public function hasStarted(): bool
	{
		return $this->started === 2;
	}

	public function hasEnded(): bool
	{
		return $this->started === 3;
	}

	public function canStart(): bool
	{
		return !$this->isStarting() and !$this->hasStarted() and $this->arePlayersReady();
	}

	public function start(): void
	{
		if ($this->hasStarted()) {
			return;
		}

		$this->started = 2;

		Server::getInstance()->broadcastMessage(TextFormat::AQUA . 'Clash has begun!' . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC . 'Spectate now using /spectate');
	}

	public function end(): void
	{
		if (!$this->hasStarted()) {
			return;
		}

		$this->started = 3;

		Base::getInstance()->utils->clash = time();

		Server::getInstance()->broadcastMessage(TextFormat::AQUA . 'Clash has ended. An intermission period of' . TextFormat::YELLOW . self::INTERMISSION_PERIOD . ' seconds ' . TextFormat::AQUA . 'will begin.');
	}
}