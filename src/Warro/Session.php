<?php

declare(strict_types=1);

namespace Warro;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use Exception;
use JsonException;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Warro\games\FreeForAll;
use Warro\managers\RankManager;
use Warro\managers\SessionManager;
use Warro\tasks\async\InitializePlayerDataTask;
use Warro\tasks\local\BaseTask;
use Warro\tasks\local\NukeTask;
use Warro\tasks\local\ViolationsTask;

class Session
{

	public const WARP_SPAWN = 0;
	public const WARP_NODEBUFF_TETRIS = 1;
	public const WARP_SUMO = 2;
	public const WARP_NODEBUFF_PLAINS = 3;
	public const WARP_HIVE = 4;
	public const WARP_BATTLEFIELD = 5;

	public bool $useMenusOverForms = false;

	public ?string $banned = null;
	public bool $muted = false;

	public ?float $currentMotion = null;

	public Vector3|float|null $lastLocation = null;

	public float $timerLastTimestamp = -1.0;

	public float $lastBoostTimestamp = -1.0;

	public float $timerBalance = 0.0;

	public int $chatWait = -1;
	public array $commandWait = array();
	public float $boostWait = 0.0;

	public int $cpsWait = -1;
	public int $reachWait = -1;
	public int $timerWait = -1;
	public int $velocityWait = -1;

	public int $cpsViolations = 0;
	public int $reachViolations = 0;
	public int $timerViolations = 0;
	public int $velocityViolations = 0;

	public array $ranks = array();

	public string $chatRoom = 'public';

	private int $inputMode = DeviceOS::UNKNOWN;
	private int $operatingSystem = DeviceOS::UNKNOWN;
	private string $deviceModel = '';
	private string $deviceId = '';
	private int $clientRandomId = 0;
	private string $selfSignedId = '';
	private string $serverAddress = '';
	private string $gameVersion = '';

	private bool $afk = false;
	private bool|array $disguise = false;
	private bool $vanished = false;
	private bool $staffMode = false;
	private bool $frozen = false;

	private int $respawnTimer = -1;
	private int $agroTicks = -1;
	private ?string $messenger = null;
	private ?string $damager = null;
	private ?Position $lastDamagePosition = null;
	private bool $takeDamage = true;

	private ?int $lastMove = 0;

	private int $recentKit = 0;
	private int $currentWarp = 0;

	public float $hKnockBack = 0.0;
	public float $vKnockBack = 0.0;
	public float $maxDistanceKnockBack = 0.0;
	public float $attackCooldown = 0.0;

	public int $kills = 0;
	public int $deaths = 0;
	public int $killstreak = 0;
	public int $battlefieldKillstreak = 0;
	public int $bestKillstreak = 0;

	public int $playtimeTotal = 0;
	public int $playtimeSession = 0;

	/*
	 * User options
	 */
	public string $capeSelected = SessionManager::CAPE_SELECTED;
	public string $splashColorSelected = SessionManager::CAPE_SELECTED;
	public bool $scoreboard = SessionManager::SCOREBOARD;
	public bool $lightning = SessionManager::LIGHTNING;
	public bool $particleSplashes = SessionManager::PARTICLE_SPLASHES;
	public bool $autoSprint = SessionManager::AUTO_SPRINT;
	public bool $autoRekit = SessionManager::AUTO_REKIT;
	public bool $cpsCounter = SessionManager::CPS_COUNTER;
	public bool $privateMessages = SessionManager::PRIVATE_MESSAGES;
	public bool $duelRequests = SessionManager::DUEL_REQUESTS;
	public bool $antiInterfere = SessionManager::ANTI_INTERFERENCE;
	public bool $antiClutter = SessionManager::ANTI_CLUTTER;
	public bool $instantRespawn = SessionManager::INSTANT_RESPAWN;
	public bool $showMyStats = SessionManager::SHOW_MY_STATS;
	public int|string $pingRange = SessionManager::PING_RANGE;
	public bool $matchAgainstTouch = SessionManager::MATCH_AGAINST_TOUCH;
	public bool $matchAgainstMouse = SessionManager::MATCH_AGAINST_MOUSE;
	public bool $matchAgainstController = SessionManager::MATCH_AGAINST_CONTROLLER;

	public function __construct(public Player $player, private Base $plugin)
	{
		$this->isRegisteredRanks(function (array $rows): void {
			if (count($rows) < 1) {
				$this->registerRanks();
				return;
			}
			$this->loadRanks();
		});
		$this->isRegisteredStats(function (array $rows): void {
			if (count($rows) < 1) {
				$this->registerStats();
				return;
			}
			$this->loadStats();
		});

		$this->initializeData();

		$this->setPlaytimeSession(time());
	}

	/**
	 * @throws JsonException
	 */
	public function onJoin(): void
	{
		$this->plugin->getScheduler()->scheduleDelayedRepeatingTask(new BaseTask($this, $this->player), 5, 20);

		if (!Server::getInstance()->isOp($this->player->getName())) {
			if (isset($this->plugin->sutils->mutes[strtolower($this->player->getName())])) {
				$muteInfo = $this->plugin->sutils->mutes[strtolower($this->player->getName())];

				if ($muteInfo['expires'] < time()) {
					$this->plugin->sutils->unmutePlayer($this->player->getName(), 'Automatic');
					return;
				}

				$this->muted = true;
			}
		}

		$this->plugin->utils->teleport($this->player, 0, true);

		$this->plugin->getScheduler()->scheduleRepeatingTask(new ViolationsTask($this, $this->player), 20 * 60);

		$this->plugin->scoreboardManager->addMain($this->player);

		$this->player->setNameTag($this->plugin->utils->getTagFormat($this->player));

		$this->loadData();

		$this->plugin->utils->updatePermissions($this->player);

		$this->plugin->cpsManager->addPlayer($this->player);

		$this->player->getHungerManager()->setEnabled(false);

		$this->player->sendMessage(TextFormat::EOL . TextFormat::GRAY . '-#-' .
			TextFormat::EOL . TextFormat::AQUA . 'Welcome to ' . Variables::NAME .
			TextFormat::EOL . TextFormat::WHITE . TextFormat::ITALIC . 'The go to destination for PvP on Minecraft Bedrock Edition.' .
			TextFormat::RESET . TextFormat::EOL . TextFormat::EOL . TextFormat::DARK_GRAY . Variables::DISCORD .
			TextFormat::EOL . TextFormat::DARK_GRAY . Variables::STORE .
			TextFormat::EOL . TextFormat::DARK_GRAY . Variables::VOTE .
			TextFormat::EOL . TextFormat::GRAY . '-#-' .
			TextFormat::EOL . TextFormat::EOL
		);

		if (!$this->player->hasPlayedBefore()) {
			$this->plugin->addRegisteredPlayer($this->player);
		}

		if (is_string($this->getCapeSelected())) {
			$cape = Base::getInstance()->utils->createImage($this->getCapeSelected(), Utils::IMAGE_TYPE_CAPE);
			if (is_string($cape)) {
				$this->setCape($cape);
			}
		}

		if ($this->isStaff()) {
			$this->plugin->utils->onlineStaff[$this->player->getName()] = $this->player->getName();
		}

		foreach ($this->plugin->scoreboardManager->getMainPlayers() as $mainPlayer) {
			if ($mainPlayer instanceof User) {
				$this->plugin->scoreboardManager->updateMainOnline($mainPlayer);
			}
		}

		foreach ($this->plugin->utils->getVanishPlayers() as $pl) {
			$players = Server::getInstance()->getPlayerExact($pl);
			if ($players instanceof User) {
				if (!$players->hasPermission('vasar.bypass.vanishsee')) {
					$players->hidePlayer($this->player);
				}
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event): void
	{
		if ($this->hasDamager() and $event->getQuitReason() === 'client disconnect') {
			$this->player->attack(new EntityDamageEvent($this->player, EntityDamageEvent::CAUSE_SUICIDE, 1000));
		}

		if (isset($this->plugin->utils->taggedPlayer[$this->player->getName()])) {
			unset($this->plugin->utils->taggedPlayer[$this->player->getName()]);
		}
		if (isset($this->plugin->utils->pearlPlayer[$this->player->getName()])) {
			unset($this->plugin->utils->pearlPlayer[$this->player->getName()]);
		}
		if (isset($this->plugin->utils->vanishPlayer[$this->player->getName()])) {
			unset($this->plugin->utils->vanishPlayer[$this->player->getName()]);
		}
		if ($this->plugin->cpsManager->doesPlayerExist($this->player)) {
			$this->plugin->cpsManager->removePlayer($this->player);
		}
		if ($this->isStaff() and isset($this->plugin->utils->onlineStaff[$this->player->getName()])) {
			unset($this->plugin->utils->onlineStaff[$this->player->getName()]);
		}

		foreach ($this->plugin->utils->freeForAllArenas as $arena) {
			if ($arena instanceof FreeForAll) {
				if ($arena->hasPlayer($this->player)) {
					$arena->removePlayer($this->player);
				}
			}
		}

		$this->saveAll(function (): void {
			unset(SessionManager::getInstance()->sessions[$this->player->getName()]);
		});

		Server::getInstance()->removeOnlinePlayer($this->player);

		foreach ($this->plugin->scoreboardManager->getMainPlayers() as $mainPlayer) {
			if ($mainPlayer instanceof User) {
				$this->plugin->scoreboardManager->updateMainOnline($mainPlayer);
			}
		}
	}

	public function setInputMode(int $variable): void
	{
		$this->inputMode = $variable;
	}

	public function setOperatingSystem(int $variable): void
	{
		$this->operatingSystem = $variable;
	}

	public function setDeviceModel(string $variable): void
	{
		$this->deviceModel = $variable;
	}

	public function setDeviceId(string $variable): void
	{
		$this->deviceId = $variable;
	}

	public function setClientRandomId(int $variable): void
	{
		$this->clientRandomId = $variable;
	}

	public function setSelfSignedId(string $variable): void
	{
		$this->selfSignedId = $variable;
	}

	public function setServerAddress(string $variable): void
	{
		$this->serverAddress = $variable;
	}

	public function setGameVersion(string $variable): void
	{
		$this->gameVersion = $variable;
	}

	public function getInputMode(bool $asString = false): string|int
	{
		if ($asString) {
			return $this->plugin->utils->inputModes[$this->inputMode];
		}
		return $this->inputMode;
	}

	public function getOperatingSystem(bool $asString = false): string|int
	{
		if ($asString) {
			return $this->plugin->utils->operatingSystems[$this->operatingSystem];
		}
		return $this->operatingSystem;
	}

	public function getDeviceModel(): string
	{
		return $this->deviceModel;
	}

	public function getDeviceId(): string
	{
		return $this->deviceId;
	}

	public function getClientRandomId(): int
	{
		return $this->clientRandomId;
	}

	public function getSelfSignedId(): string
	{
		return $this->selfSignedId;
	}

	public function getServerAddress(): string
	{
		return $this->serverAddress;
	}

	public function getGameVersion(): string
	{
		return $this->gameVersion;
	}

	public function isRegisteredRanks(callable $callable): void
	{
		$this->plugin->network->executeSelect('vasar.check.ranks', ['player' => $this->player->getName()], function (array $rows) use ($callable): void {
			$callable($rows);
		});
	}

	public function registerRanks(): void
	{
		$rank = $this->plugin->rankManager->getRankAsString(RankManager::DEFAULT);
		$this->plugin->network->executeGeneric('vasar.register.player.ranks', ['player' => $this->player->getName(), 'ranks' => $rank . ':0']);
		$this->ranks[RankManager::DEFAULT] = [$rank, 0];
	}

	public function loadRanks(): void
	{
		$this->plugin->network->executeSelect('vasar.get.ranks', ['player' => $this->player->getName()], function ($rows) {
			foreach ($rows as $row) {
				if (empty($row['ranks'])) {
					$this->registerRanks();
					return;
				}

				foreach (explode(',', $row['ranks']) as $rankArray) {
					$rankDetails = explode(':', $rankArray);

					/*foreach ($rankDetails as $string) {
						if (!is_string($string)) {
							$this->player->kick(TextFormat::RED . 'There was a problem with your session, please try re-connecting.');
							return;
						}
					}*/

					$rank = $rankDetails[0];
					$duration = intval($rankDetails[1]);

					$isPlus = $rank === 'Plus';

					if ($duration === 0 or $duration > time()) {
						$key = $this->plugin->rankManager->getRankFromString($rank);
						if (!is_null($key)) {
							if ($this->plugin->rankManager->doesRankExist($key)) {
								$this->ranks[$key] = [$rank, $duration];
							}
						}
					} else {
						$message = $isPlus ? 'Your Vasar Plus subscription has ended. If you\'d like to renew it, head over to our store at ' . Variables::STORE . '!' : 'The ' . $rank . ' rank on your account has expired.';
						$this->player->sendMessage(TextFormat::RED . $message);
					}

					if ($duration + Variables::DAY > time()) {
						$remainingTime = $duration - time();

						$day = floor($remainingTime / 86400);

						$hourSeconds = $remainingTime % 86400;

						$hour = floor($hourSeconds / 3600);

						$minuteSec = $hourSeconds % 3600;

						$minute = floor($minuteSec / 60);

						$remainingSec = $minuteSec % 60;

						$second = ceil($remainingSec);

						$time = $day;
						$str = $time > 1 ? 'days' : 'day';
						if ($time <= 0) {
							$time = $hour;
							$str = $time > 1 ? 'hours' : 'hour';
						}
						if ($time <= 0) {
							$time = $minute;
							$str = $time > 1 ? 'minutes' : 'minute';
						}
						if ($time <= 0) {
							$time = $second;
							$str = $time > 1 ? 'seconds' : 'second';
						}
						if ($time <= 0) {
							return;
						}

						$message = $isPlus ? 'Your Vasar Plus subscription ends in ' . $time . ' ' . $str . '. If you\'d like to renew it, head over to our store at ' . Variables::STORE . '!' : 'The ' . $rank . ' rank on your account is expiring in ' . $time . ' ' . $str . '.';
						$this->player->sendMessage(TextFormat::RED . $message);
					}
				}
			}
		});
	}

	public function saveRanks(?callable $callable): void
	{
		$this->plugin->network->executeGeneric('vasar.set.ranks', ['player' => $this->player->getName(), 'ranks' => $this->getRanksForSave()], $callable);
	}

	public function isRegisteredStats(callable $callable): void
	{
		$this->plugin->database->executeSelect('vasar.check.stats', ['player' => $this->player->getName()], function (array $rows) use ($callable): void {
			$callable($rows);
		});
	}

	public function registerStats(): void
	{
		$this->plugin->database->executeGeneric('vasar.register.player.stats', ['player' => $this->player->getName(), 'playtime' => 0, 'kills' => $this->kills, 'deaths' => $this->deaths, 'killstreak' => $this->killstreak, 'bestkillstreak' => $this->bestKillstreak]);
	}

	public function loadStats(): void
	{
		$this->plugin->database->executeSelect('vasar.get.stats', ['player' => $this->player->getName()], function ($rows) {
			foreach ($rows as $row) {
				$this->playtimeTotal = $row['playtime'];
				$this->kills = $row['kills'];
				$this->deaths = $row['deaths'];
				$this->killstreak = $row['killstreak'];
				$this->bestKillstreak = $row['bestkillstreak'];
			}
		});
	}

	public function saveStats(?callable $callable): void
	{
		$this->plugin->database->executeGeneric('vasar.set.stats', ['player' => $this->player->getName(), 'playtime' => $this->playtimeTotal + time() - $this->playtimeSession, 'kills' => $this->kills, 'deaths' => $this->deaths, 'killstreak' => $this->killstreak, 'bestkillstreak' => $this->bestKillstreak], $callable);
	}

	public function initializeData(): void
	{
		Server::getInstance()->getAsyncPool()->submitTask(new InitializePlayerDataTask($this->plugin->getDataFolder() . 'players/' . $this->player->getName() . '.yml'));
	}

	public function verifyData(): void
	{
		if ($this->getInputMode() === 'touch') {
			if (!$this->isMatchAgainstTouch()) {
				$this->setMatchAgainstTouch(true);
			}
		} elseif ($this->getInputMode() === 'controller') {
			if (!$this->isMatchAgainstController()) {
				$this->setMatchAgainstController(true);
			}
		} elseif ($this->getInputMode() === 'mouse') {
			if (!$this->isMatchAgainstMouse()) {
				$this->setMatchAgainstMouse(true);
			}
		}

		if ($this->isInstantRespawn()) {
			$packet = new GameRulesChangedPacket();
			$packet->gameRules['doImmediateRespawn'] = new BoolGameRule(true, false);
			$this->player->getNetworkSession()->sendDataPacket($packet);
		}

		if (!$this->hasRank(RankManager::PLUS)) {
			if ($this->getCapeSelected() !== SessionManager::CAPE_SELECTED or $this->getCapeSelected() !== 'vasar series 1' or $this->getCapeSelected() !== 'vasar series 2') {
				$this->setCapeSelected(SessionManager::CAPE_SELECTED);
			}
			if ($this->getSplashColorSelected() !== SessionManager::SPLASH_COLOR_SELECTED) {
				$this->setSplashColorSelected(SessionManager::SPLASH_COLOR_SELECTED);
			}
			if ($this->getPingRange() !== SessionManager::PING_RANGE) {
				$this->setPingRange(SessionManager::PING_RANGE);
			}
			if ($this->getMatchAgainstTouch() !== SessionManager::MATCH_AGAINST_TOUCH) {
				$this->setMatchAgainstTouch(SessionManager::MATCH_AGAINST_TOUCH);
			}
			if ($this->getMatchAgainstController() !== SessionManager::MATCH_AGAINST_CONTROLLER) {
				$this->setMatchAgainstController(SessionManager::MATCH_AGAINST_CONTROLLER);
			}
			if ($this->getMatchAgainstMouse() !== SessionManager::MATCH_AGAINST_MOUSE) {
				$this->setMatchAgainstMouse(SessionManager::MATCH_AGAINST_MOUSE);
			}
		}
	}

	public function loadData(): void
	{
		$data = $this->plugin->utils->getPlayerData($this->player);

		$this->capeSelected = $data['cape-selected'];
		$this->splashColorSelected = $data['splash-color-selected'];
		$this->scoreboard = $data['scoreboard'];
		$this->lightning = $data['lightning'];
		$this->particleSplashes = $data['particle-splashes'];
		$this->autoSprint = $data['auto-sprint'];
		$this->autoRekit = $data['auto-rekit'];
		$this->cpsCounter = $data['cps-counter'];
		$this->privateMessages = $data['dms'];
		$this->duelRequests = $data['duel-requests'];
		$this->antiInterfere = $data['anti-interfere'];
		$this->antiClutter = $data['anti-clutter'];
		$this->instantRespawn = $data['instant-respawn'];
		$this->showMyStats = $data['show-my-stats'];
		$this->pingRange = $data['ping-range'];
		$this->matchAgainstTouch = $data['match-against-touch'];
		$this->matchAgainstMouse = $data['match-against-mouse'];
		$this->matchAgainstController = $data['match-against-controller'];

		$this->verifyData();
	}

	public function saveData(): void
	{
		$this->plugin->utils->setPlayerData($this->player, 'cape-selected', $this->capeSelected);
		$this->plugin->utils->setPlayerData($this->player, 'splash-color-selected', $this->splashColorSelected);
		$this->plugin->utils->setPlayerData($this->player, 'scoreboard', $this->scoreboard);
		$this->plugin->utils->setPlayerData($this->player, 'lightning', $this->lightning);
		$this->plugin->utils->setPlayerData($this->player, 'particle-splashes', $this->particleSplashes);
		$this->plugin->utils->setPlayerData($this->player, 'auto-sprint', $this->autoSprint);
		$this->plugin->utils->setPlayerData($this->player, 'auto-rekit', $this->autoRekit);
		$this->plugin->utils->setPlayerData($this->player, 'cps-counter', $this->cpsCounter);
		$this->plugin->utils->setPlayerData($this->player, 'dms', $this->privateMessages);
		$this->plugin->utils->setPlayerData($this->player, 'duel-requests', $this->duelRequests);
		$this->plugin->utils->setPlayerData($this->player, 'anti-interfere', $this->antiInterfere);
		$this->plugin->utils->setPlayerData($this->player, 'anti-clutter', $this->antiClutter);
		$this->plugin->utils->setPlayerData($this->player, 'instant-respawn', $this->instantRespawn);
		$this->plugin->utils->setPlayerData($this->player, 'show-my-stats', $this->showMyStats);
		$this->plugin->utils->setPlayerData($this->player, 'ping-range', $this->pingRange);
		$this->plugin->utils->setPlayerData($this->player, 'match-against-touch', $this->matchAgainstTouch);
		$this->plugin->utils->setPlayerData($this->player, 'match-against-mouse', $this->matchAgainstMouse);
		$this->plugin->utils->setPlayerData($this->player, 'match-against-controller', $this->matchAgainstController);
	}

	public function saveAll(?callable $callable): void
	{
		$this->saveRanks($callable);
		$this->saveStats($callable);
		$this->saveData();
	}

	public function getPlayer(): Player
	{
		return $this->player;
	}

	public function addRank(int $int = RankManager::DEFAULT, int $duration = 0): void
	{
		$rank = $this->plugin->rankManager->getRankAsString($int);
		if (!is_null($rank)) {
			$this->ranks[$int] = [$rank, $duration];
			if ($int == $this->getHighestRank()) {
				$this->player->setNameTag($this->plugin->utils->getTagFormat($this->player));
			}
			$this->player->sendMessage(TextFormat::GREEN . 'The ' . TextFormat::YELLOW . $rank . TextFormat::GREEN . ' rank has been added to your account.');
			$this->plugin->utils->updatePermissions($this->player);
		}

		if ($this->isStaff() and !isset($this->plugin->utils->onlineStaff[$this->player->getName()])) {
			$this->plugin->utils->onlineStaff[$this->player->getName()] = $this->player->getName();
		}
	}

	public function removeRank(int $int): void
	{
		$rank = $this->plugin->rankManager->getRankAsString($int);
		if (!is_null($rank) and $this->plugin->rankManager->doesRankExist($int) and $this->hasRank($int)) {
			unset($this->ranks[$int]);
			if ($int == $this->getHighestRank()) {
				$this->player->setNameTag($this->plugin->utils->getTagFormat($this->player));
			}
			$this->player->sendMessage(TextFormat::GREEN . 'The ' . TextFormat::YELLOW . $rank . TextFormat::GREEN . ' rank has been removed from your account.');
			$this->plugin->utils->updatePermissions($this->player);
		}

		if (!$this->isStaff() and isset($this->plugin->utils->onlineStaff[$this->player->getName()])) {
			unset($this->plugin->utils->onlineStaff[$this->player->getName()]);
		}
	}

	private function getRanksForSave(): string
	{
		$ranks = $this->ranks;
		ksort($ranks);

		$newArray = array();

		foreach ($ranks as $key => $array) {
			if (is_array($array)) {
				$newArray[$key] = $array[0] . ':' . $array[1];
			}
		}

		return implode(',', $newArray);
	}

	public function getRanks(bool $asString = false, bool $cleanString = false, bool $sort = false): array|string
	{
		$array = $this->ranks;
		if ($sort) {
			ksort($array);
		}

		$arr = array();
		foreach ($array as $key => $value) {
			$arr[$key] = $value[0];
		}

		if ($asString) {
			return $cleanString ? implode(', ', $arr) : implode(',', $arr);
		}

		return $arr;
	}

	public function getHighestRank(bool $asString = false): int|string|null
	{
		$arr = $this->ranks;
		ksort($arr);

		if (empty($arr)) {
			$this->player->kick(TextFormat::RED . 'aThere was a problem with your session, please try re-connecting.');
			return null;
		}

		$highest = reset($arr)[0];

		if (!is_string($highest)) {
			$this->player->kick(TextFormat::RED . 'bThere was a problem with your session, please try re-connecting.');
			return null;
		}

		if ($asString) {
			return $highest;
		}

		return array_key_first($arr);
	}

	public function hasRank(int $int = RankManager::DEFAULT): bool
	{
		return isset($this->ranks[$int]);
	}

	public function isStaff(bool $strict = false): bool
	{
		if ($strict) {
			return $this->hasRank(RankManager::TRIAL) or $this->hasRank(RankManager::MODERATOR) or $this->hasRank(RankManager::ADMINISTRATOR) or $this->hasRank(RankManager::MANAGER) or $this->hasRank(RankManager::OWNER);
		}
		return Server::getInstance()->isOp($this->player->getName()) or $this->hasRank(RankManager::TRIAL) or $this->hasRank(RankManager::MODERATOR) or $this->hasRank(RankManager::ADMINISTRATOR) or $this->hasRank(RankManager::MANAGER) or $this->hasRank(RankManager::OWNER);
	}

	public function setChat(string $variable = 'public'): void
	{
		$this->chatRoom = $variable;
	}

	public function getChat(): string
	{
		return strtolower($this->chatRoom);
	}

	/**
	 * @throws Exception
	 */
	public function addCpsViolation(): void
	{
		$this->cpsViolations++;
		if ($this->cpsViolations > 10) {
			$this->plugin->sutils->banPlayer($this->player->getName(), 'Jarvis', 'Unfair Advantage', true);
		}
	}

	/**
	 * @throws Exception
	 */
	public function addReachViolation(): void
	{
		$this->reachViolations++;
		if ($this->reachViolations > 15) {
			$this->plugin->sutils->banPlayer($this->player->getName(), 'Jarvis', 'Unfair Advantage', true);
		}
	}

	/**
	 * @throws Exception
	 */
	public function addTimerViolation(): void
	{
		$this->timerViolations++;
		if ($this->timerViolations > 15) {
			$this->plugin->sutils->banPlayer($this->player->getName(), 'Jarvis', 'Unfair Advantage', true);
		}
	}

	/**
	 * @throws Exception
	 */
	public function addVelocityViolation(): void
	{
		$this->velocityViolations++;
		if ($this->velocityViolations > 15) {
			$this->plugin->sutils->banPlayer($this->player->getName(), 'Jarvis', 'Unfair Advantage', true);
		}
	}

	public function setAfk(bool $variable = false): void
	{
		if (!$this->player->isOnGround()) {
			return;
		}
		$this->afk = $variable;
		$this->player->clearHidden();
		$this->player->sendMessage(($variable ? TextFormat::ITALIC . TextFormat::GRAY . 'You\'re now away, other players and objects can\'t interact with you.' : TextFormat::ITALIC . TextFormat::GRAY . 'You\'re no longer away.'));
		$this->player->setScoreTag(($variable ? TextFormat::ITALIC . TextFormat::GRAY . 'Away' : ''));
	}

	public function getAfk(): bool
	{
		return $this->afk;
	}

	public function isAfk(): bool
	{
		return $this->afk;
	}

	public function setDisguise(string $disguise = null, int $rank = null): void
	{
		if (is_string($disguise) and is_int($rank)) {
			$this->disguise = ['disguise' => $disguise, 'rank' => $rank];

			$this->player->setDisplayName($disguise);
			$this->plugin->utils->removeOnlinePlayer($this->player);

			$this->player->setSkin($this->plugin->utils->vasarSkin);
			$this->player->sendSkin();
			$this->player->spawnToAll();

			$this->player->sendMessage(TextFormat::GREEN . 'You\'re now disguised as ' . TextFormat::YELLOW . $disguise . TextFormat::GREEN . '.' . TextFormat::EOL .
				TextFormat::ITALIC . TextFormat::GRAY . 'We set your skin to our \'Vasar Steve\' skin, and removed you from the server, you\'re a ghost!');

			$webhookMessage = new Message();
			$webhookMessage->setContent('');
			$embed = new Embed();
			$embed->setTitle('Player Disguise');
			$embed->setColor(0xa5645a);
			$embed->setDescription('Player: **`' . $this->player->getName() . '`**' . TextFormat::EOL . 'Disguise: **`' . $disguise . '`**');
			$webhookMessage->addEmbed($embed);
			$webhook = new Webhook(Variables::DISGUISE_WEBHOOK);
			$webhook->send($webhookMessage);
		} else {
			$this->disguise = false;

			$this->player->setDisplayName($this->player->getName());
			$this->plugin->utils->addOnlinePlayer($this->player);

			$this->player->sendMessage(TextFormat::GREEN . 'You left your disguise.');
		}

		$this->player->setNameTag($this->plugin->utils->getTagFormat($this->player));
	}

	public function isDisguised(): bool
	{
		return !is_bool($this->disguise);
	}

	public function getDisguiseRank(): ?int
	{
		return $this->disguise['rank'];
	}

	public function setVanished(bool $variable = false): void
	{
		$this->vanished = $variable;
		$this->setLastMove();
		if ($variable) {
			$this->plugin->utils->vanishPlayer[$this->player->getName()] = $this->player->getName();

			foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
				$onlinePlayer->hidePlayer($this->player);
			}
			$this->plugin->utils->removeOnlinePlayer($this->player);

			$this->player->setSilent();
			$this->player->setScoreTag(TextFormat::AQUA . 'In-Vanish');
			$this->player->sendMessage(TextFormat::GREEN . 'You are now in vanish.');
			$this->player->setFlying(true);
			$this->player->setAllowFlight(true);
			$this->setLastMove();
			$this->setLastDamagePosition();
			Base::getInstance()->utils->setTagged($this->player, false);
			if (!$this->player->isCreative(true)) {
				$this->player->setGamemode(GameMode::CREATIVE());
			}
		} else {
			unset($this->plugin->utils->vanishPlayer[$this->player->getName()]);
			foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
				$onlinePlayer->showPlayer($this->player);
			}
			$this->plugin->utils->addOnlinePlayer($this->player);

			$this->player->setSilent(false);
			$this->player->setScoreTag('');
			$this->player->sendMessage(TextFormat::GREEN . 'You are no longer in vanish.');
			if (!$this->player->isSurvival()) {
				$this->player->setGamemode(GameMode::ADVENTURE());
			}

		}
	}

	public function isVanished(): bool
	{
		return $this->vanished;
	}

	public function setStaffMode(bool $variable = false): void
	{
		$this->staffMode = $variable;
	}

	public function isStaffMode(): bool
	{
		return $this->staffMode;
	}

	public function setFrozen(bool $variable = false): void
	{
		$this->frozen = $variable;

		$this->player->setImmobile($variable);
	}

	public function isFrozen(): bool
	{
		return $this->frozen;
	}

	public function startRespawnTimer(int $time = Variables::RESPAWN_TIMER): void
	{
		$this->respawnTimer = $time;
	}

	public function decreaseRespawnTimer(): void
	{
		if ($this->hasRespawnTimerStarted()) {
			$this->respawnTimer--;
			if ($this->respawnTimer <= 0) {
				$this->doRespawn();
			}
		}
	}

	public function doRespawn(): void
	{
		$this->resetRespawnTimer();
		$this->setTakeDamage();
		$this->setDamager();
		$this->plugin->utils->teleport($this->player, 0, true);
	}

	public function resetRespawnTimer(): void
	{
		$this->respawnTimer = -1;
	}

	public function hasRespawnTimerStarted(): bool
	{
		return $this->respawnTimer !== -1;
	}

	public function startAgroTimer(int $time = Variables::AGRO_TICKS): void
	{
		$this->agroTicks = $time;
	}

	public function decreaseAgroTimer(): void
	{
		if ($this->hasAgroTimerStarted()) {
			$this->agroTicks--;
			if ($this->agroTicks <= 0) {
				$this->resetAgroTimer();
			}
		}
	}

	public function resetAgroTimer(): void
	{
		$this->agroTicks = -1;
	}

	public function hasAgroTimerStarted(): bool
	{
		return $this->agroTicks !== -1;
	}

	public function setMessenger(?string $variable = null): void
	{
		$this->messenger = $variable;
	}

	public function getMessenger(): ?string
	{
		return $this->messenger;
	}

	public function hasMessenger(): bool
	{
		return !is_null($this->messenger);
	}

	public function doPrivateMessage(Player $player, string $message): bool
	{
		$playerSession = $this->plugin->sessionManager->getSession($player);

		$targetHR = $playerSession->getHighestRank();
		$senderHR = $this->getHighestRank();

		$toColor = $targetHR === RankManager::DEFAULT ? TextFormat::WHITE : $this->plugin->utils->getColorFormat($player);
		$fromColor = $senderHR === RankManager::DEFAULT ? TextFormat::WHITE : $this->plugin->utils->getColorFormat($this->player);

		if (!$playerSession->isPrivateMessages() and !$this->isStaff()) {
			$this->player->sendMessage(TextFormat::RED . 'This player has private messages disabled.');
			return false;
		}

		$this->player->sendMessage(TextFormat::GRAY . '(To ' . $toColor . $player->getDisplayName() . TextFormat::RESET . TextFormat::GRAY . ') ' . $toColor . $message);
		$player->sendMessage(TextFormat::GRAY . '(From ' . $fromColor . $this->player->getDisplayName() . TextFormat::RESET . TextFormat::GRAY . ') ' . $fromColor . $message);

		$playerSession->setMessenger($this->player->getName());

		$this->plugin->utils->doSoundPacket($player);
		return true;
	}

	public function setDamager(?string $variable = null): void
	{
		/*if (is_string($this->damager)) {
			$currentDamager = Server::getInstance()->getPlayerExact($this->damager);
			if ($currentDamager instanceof User) {
				$currentDamager->sendData(
					[$this->player],
					[EntityMetadataProperties::NAMETAG =>
						new StringMetadataProperty($currentDamager->getNameTag())]);
			}
		}*/

		$this->damager = $variable;

		/*if (is_string($variable)) {
			$newDamager = Server::getInstance()->getPlayerExact($variable);
			if ($newDamager instanceof User) {
				$newDamager->sendData(
					[$this->player],
					[EntityMetadataProperties::NAMETAG =>
						new StringMetadataProperty(TextFormat::DARK_RED . $newDamager->getDisplayName())]);
			}
		}*/
	}

	public function getDamager(): ?string
	{
		return $this->damager;
	}

	public function hasDamager(): bool
	{
		return !is_null($this->damager);
	}

	public function setLastDamagePosition(?Position $variable = null): void
	{
		$this->lastDamagePosition = $variable;
	}

	public function getLastDamagePosition(): ?Position
	{
		return $this->lastDamagePosition;
	}

	public function hasLastDamagePosition(): bool
	{
		return $this->lastDamagePosition !== null;
	}

	public function setTakeDamage(bool $variable = true): void
	{
		$this->takeDamage = $variable;
	}

	public function canTakeDamage(bool $strict = false): bool
	{
		if ($strict) {
			return $this->takeDamage;
		}
		return $this->takeDamage and !$this->isAfk() and !$this->isFrozen() and !$this->isVanished();
	}

	public function setLastMove(?int $variable = null): void
	{
		$this->lastMove = $variable;
		if ($this->isAfk()) {
			$this->setAfk();
		}
	}

	public function getLastMove(): ?int
	{
		return $this->lastMove;
	}

	public function setRecentKit(int $variable = 0): void
	{
		$this->recentKit = $variable;
	}

	public function getRecentKit(): int
	{
		return $this->recentKit;
	}

	public function setCurrentWarp(int $variable = 0): void
	{
		$this->currentWarp = $variable;
	}

	public function getCurrentWarp(): int
	{
		return $this->currentWarp;
	}

	public function getKills(): int
	{
		return $this->kills;
	}

	public function addKill(): void
	{
		$this->kills++;
		$this->plugin->scoreboardManager->updateFfaKills($this->player, $this->kills);
		if ($this->getCurrentWarp() === self::WARP_BATTLEFIELD) {
			$this->plugin->scoreboardManager->updateFfaKdr($this->player, $this->kills, $this->deaths);
		}
	}

	public function getDeaths(): int
	{
		return $this->deaths;
	}

	public function addDeath(): void
	{
		$this->deaths++;
		if ($this->player->isAlive()) {
			$this->plugin->scoreboardManager->updateFfaDeaths($this->player, $this->deaths);
			if ($this->getCurrentWarp() === self::WARP_BATTLEFIELD) {
				$this->plugin->scoreboardManager->updateFfaKdr($this->player, $this->kills, $this->deaths);
			}
		}
	}

	public function getKillstreak(): int
	{
		return $this->killstreak;
	}

	public function getBattlefieldKillstreak(bool $till = false): int
	{
		if ($till) {
			$int = $this->battlefieldKillstreak;
			return Variables::BATTLEFIELD_NUKE - $int;
		}
		return $this->battlefieldKillstreak;
	}

	public function getBestKillstreak(): int
	{
		return $this->bestKillstreak;
	}

	public function addToKillstreak(): void
	{
		$this->killstreak++;
		$this->plugin->scoreboardManager->updateFfaKillstreak($this->player, $this->killstreak);
		if ($this->killstreak > $this->bestKillstreak) {
			$this->bestKillstreak = $this->killstreak;
		}
	}

	public function resetKillstreak(): void
	{
		$this->killstreak = 0;
		$this->plugin->scoreboardManager->updateFfaKillstreak($this->player, $this->killstreak);
	}

	public function addToBattlefieldKillstreak(): void
	{
		$this->battlefieldKillstreak++;

		$till = $this->getBattlefieldKillstreak(true);

		$this->plugin->scoreboardManager->updateFfaBattlefieldKillstreak($this->player, $till);
		if ($this->battlefieldKillstreak >= Variables::BATTLEFIELD_NUKE) {
			$this->resetBattlefieldKillstreak();
			$arena = $this->plugin->utils->freeForAllArenas['Battlefield'];
			if ($arena instanceof FreeForAll) {
				$players = $arena->getPlayers(false, true);
				$this->plugin->getScheduler()->scheduleRepeatingTask(new NukeTask($this, $this->player, $players), 20);
				Server::getInstance()->broadcastMessage(TextFormat::RED . 'Nuke incoming...', $players);
			}
		} elseif ($this->battlefieldKillstreak % 5 === 0) {
			$arena = $this->plugin->utils->freeForAllArenas['Battlefield'];
			if ($arena instanceof FreeForAll) {
				Server::getInstance()->broadcastMessage(TextFormat::RED . $this->player->getDisplayName() . TextFormat::YELLOW . ' is ' . TextFormat::DARK_RED . $till . TextFormat::YELLOW . ' kills away from a Nuke!', $arena->getPlayers(false, true));
			}
		}
	}

	public function resetBattlefieldKillstreak(): void
	{
		$this->battlefieldKillstreak = 0;
		$this->plugin->scoreboardManager->updateFfaBattlefieldKillstreak($this->player, $this->getBattlefieldKillstreak(true));
	}

	public function setPlaytimeSession(int $int)
	{
		$this->playtimeSession = $int;
	}

	public function getFormattedPlaytimeTotal(): string
	{
		$t = (time() - $this->playtimeSession);
		$time = ($t + $this->playtimeTotal);
		return ($time < 0 ? '-' : '') . sprintf('%2d%s%2d%s%2d', floor(abs($time) / 3600), ':', (abs($time) / 60) % 60, ':', abs($time) % 60);
	}

	public function getFormattedPlaytimeSession(): string
	{
		$time = time() - $this->playtimeSession;
		return ($time < 0 ? '-' : '') . sprintf('%2d%s%2d%s%2d', floor(abs($time) / 3600), ':', (abs($time) / 60) % 60, ':', abs($time) % 60);
	}

	/**
	 * @throws JsonException
	 */
	public function setCape(string $cape): void
	{
		$oldSkin = $this->player->getSkin();
		$skin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
		$this->player->setSkin($skin);
		$this->player->sendSkin();
	}

	/**
	 * @throws JsonException
	 */
	public function removeCape(): void
	{
		$oldSkin = $this->player->getSkin();
		$skin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), '', $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
		$this->player->setSkin($skin);
		$this->player->sendSkin();
	}

	/*
	 * User options
	 */
	public function getCapeSelected(): string
	{
		return $this->capeSelected;
	}

	public function getSplashColorSelected(): string
	{
		return $this->splashColorSelected;
	}

	public function getPingRange(): int|string
	{
		return $this->pingRange;
	}

	public function isPingRangeUnrestricted(): bool
	{
		return $this->pingRange === 'unrestricted';
	}

	public function isMatchAgainstTouch(): bool
	{
		return $this->matchAgainstTouch;
	}

	public function isMatchAgainstMouse(): bool
	{
		return $this->matchAgainstMouse;
	}

	public function isMatchAgainstController(): bool
	{
		return $this->matchAgainstController;
	}

	public function getMatchAgainstTouch(): bool
	{
		return $this->matchAgainstTouch;
	}

	public function getMatchAgainstMouse(): bool
	{
		return $this->matchAgainstMouse;
	}

	public function getMatchAgainstController(): bool
	{
		return $this->matchAgainstController;
	}

	public function isScoreboard(): bool
	{
		return $this->scoreboard;
	}

	public function isLightning(): bool
	{
		return $this->lightning;
	}

	public function isParticleSplashes(): bool
	{
		return $this->particleSplashes;
	}

	public function isAutoSprint(): bool
	{
		return $this->autoSprint;
	}

	public function isAutoRekit(): bool
	{
		return $this->autoRekit;
	}

	public function isCpsCounter(): bool
	{
		return $this->cpsCounter;
	}

	public function isPrivateMessages(): bool
	{
		return $this->privateMessages;
	}

	public function isDuelRequests(): bool
	{
		return $this->duelRequests;
	}

	public function isAntiInterfere(): bool
	{
		return $this->antiInterfere;
	}

	public function isAntiClutter(): bool
	{
		return $this->antiClutter;
	}

	public function isInstantRespawn(): bool
	{
		return $this->instantRespawn;
	}

	public function isShowMyStats(): bool
	{
		return $this->showMyStats;
	}

	public function setCapeSelected(string $string): void
	{
		$this->capeSelected = $string;
	}

	public function setSplashColorSelected(string $string): void
	{
		$this->splashColorSelected = $string;
	}

	public function setScoreboard(bool $bool): void
	{
		$this->scoreboard = $bool;
	}

	public function setLightning(bool $bool): void
	{
		$this->lightning = $bool;
	}

	public function setParticleSplashes(bool $bool): void
	{
		$this->particleSplashes = $bool;
	}

	public function setAutoSprint(bool $bool): void
	{
		$this->autoSprint = $bool;
	}

	public function setAutoRekit(bool $bool): void
	{
		$this->autoRekit = $bool;
	}

	public function setCpsCounter(bool $bool): void
	{
		$this->cpsCounter = $bool;
	}

	public function setPrivateMessages(bool $bool): void
	{
		$this->privateMessages = $bool;
	}

	public function setDuelRequests(bool $bool): void
	{
		$this->duelRequests = $bool;
	}

	public function setAntiInterfere(bool $bool): void
	{
		$this->antiInterfere = $bool;
	}

	public function setAntiClutter(bool $bool): void
	{
		$this->antiClutter = $bool;
	}

	public function setInstantRespawn(bool $bool): void
	{
		$this->instantRespawn = $bool;
	}

	public function setShowMyStats(bool $bool): void
	{
		$this->showMyStats = $bool;
	}

	public function setPingRange(int|string $value): void
	{
		$this->pingRange = $value;
	}

	public function setMatchAgainstTouch(bool $bool): void
	{
		$this->matchAgainstTouch = $bool;
	}

	public function setMatchAgainstMouse(bool $bool): void
	{
		$this->matchAgainstMouse = $bool;
	}

	public function setMatchAgainstController(bool $bool): void
	{
		$this->matchAgainstController = $bool;
	}
}