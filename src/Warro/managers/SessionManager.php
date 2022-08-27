<?php

declare(strict_types=1);

namespace Warro\managers;

use pocketmine\player\Player;
use Warro\Base;
use Warro\Session;

class SessionManager
{

	public array $sessions = [];
	public static SessionManager $instance;

	public const VOTE_ACCESS = false;

	public const CAPE_SELECTED = 'none';
	public const SPLASH_COLOR_SELECTED = 'default';
	public const SCOREBOARD = true;
	public const LIGHTNING = true;
	public const PARTICLE_SPLASHES = true;
	public const AUTO_SPRINT = false;
	public const AUTO_REKIT = true;
	public const CPS_COUNTER = true;
	public const PRIVATE_MESSAGES = true;
	public const DUEL_REQUESTS = true;
	public const ANTI_INTERFERENCE = false;
	public const ANTI_CLUTTER = false;
	public const INSTANT_RESPAWN = false;
	public const SHOW_MY_STATS = true;
	public const PING_RANGE = 'unrestricted';
	public const MATCH_AGAINST_TOUCH = true;
	public const MATCH_AGAINST_MOUSE = true;
	public const MATCH_AGAINST_CONTROLLER = true;

	public function __construct(private Base $plugin)
	{
		self::$instance = $this;
	}

	public static function getInstance(): self
	{
		return self::$instance;
	}

	public function getSession(Player $player): ?Session
	{
		return $this->sessions[$player->getName()] ?? null;
	}

	public function createSession(Player $player): Session
	{
		$session = new Session($player, $this->plugin);
		$this->sessions[$player->getName()] = $session;
		return $session;
	}
}