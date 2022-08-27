<?php

declare(strict_types=1);

namespace Warro\managers;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\Base;
use Warro\Session;
use Warro\User;
use Warro\Variables;
use function count;

class ScoreboardManager
{

	private array $scoreboard = [];
	private array $main = [];
	private array $ffa = [];

	public function __construct(private Base $plugin)
	{
	}

	public function getMainPlayers(): array
	{
		$array = array();
		foreach ($this->main as $pl) {
			if (is_string($pl)) {
				$player = Server::getInstance()->getPlayerExact($pl);
				if ($player instanceof User and $player->isConnected()) {
					$array[$player->getName()] = $player;
				}
			}

		}
		return $array;
	}

	public function getFfaPlayers(): array
	{
		$array = array();
		foreach ($this->ffa as $pl) {
			if (is_string($pl)) {
				$player = Server::getInstance()->getPlayerExact($pl);
				if ($player instanceof User and $player->isConnected()) {
					$array[$player->getName()] = $player;
				}
			}
		}
		return $array;
	}

	public function addMain(Player $player): void
	{
		if ($this->isPlayerSetScoreboard($player)) {
			$this->removeScoreboard($player);
		}

		$session = $this->plugin->sessionManager->getSession($player);
		if (!$session instanceof Session) {
			return;
		}

		$this->main[$player->getName()] = $player->getName();

		if ($session->isScoreboard()) {
			$this->lineTitle($player);
			$this->lineCreate($player, 0, '§8-<                     >-', false); // 
			$this->lineCreate($player, 1, TextFormat::WHITE . 'Online: ' . TextFormat::AQUA . count(Server::getInstance()->getOnlinePlayers()));
			$this->lineCreate($player, 2, TextFormat::WHITE . 'Playing: ' . TextFormat::AQUA . Base::getInstance()->utils->getPlayingFreeForAll());
			$this->lineCreate($player, 3, ' ');
			$this->lineCreate($player, 4, TextFormat::ITALIC . TextFormat::GRAY . Variables::DOMAIN);
			$this->lineCreate($player, 5, '§8-<                     >-§', false); // 

			$this->scoreboard[$player->getName()] = $player->getName();
		}
	}

	public function updateMainOnline(Player $player): void
	{
		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetMain($player)) {
				$this->lineRemove($player, 1);
				$this->lineCreate($player, 1, TextFormat::WHITE . 'Online: ' . TextFormat::AQUA . count(Server::getInstance()->getOnlinePlayers()));
			}
		}
	}

	public function updateMainPlaying(Player $player): void
	{
		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetMain($player)) {
				$this->lineRemove($player, 2);
				$this->lineCreate($player, 2, TextFormat::WHITE . 'Playing: ' . TextFormat::AQUA . Base::getInstance()->utils->getPlayingFreeForAll());
			}
		}
	}

	public function addFfa(Player $player): void
	{
		if ($this->isPlayerSetScoreboard($player)) {
			$this->removeScoreboard($player);
		}

		$session = $this->plugin->sessionManager->getSession($player);
		if (!$session instanceof Session) {
			return;
		}

		$this->ffa[$player->getName()] = $player->getName();

		if ($session->isScoreboard()) {
			$this->lineTitle($player);
			$this->lineCreate($player, 0, '§8-<                     >-', false); // 
			$this->lineCreate($player, 1, TextFormat::AQUA . 'Kills: ' . TextFormat::WHITE . $session->getKills());
			$this->lineCreate($player, 2, TextFormat::AQUA . 'Killstreak: ' . TextFormat::WHITE . $session->getKillstreak());
			$this->lineCreate($player, 3, TextFormat::AQUA . 'Deaths: ' . TextFormat::WHITE . $session->getDeaths());
			if ($session->getCurrentWarp() === Session::WARP_BATTLEFIELD) {
				$kdr = $session->getDeaths() > 0 ? round($session->getKills() / $session->getDeaths(), 2) : $session->getKills() . '.00';
				$this->lineCreate($player, 4, TextFormat::AQUA . 'K/D Ratio: ' . TextFormat::WHITE . $kdr);
				$this->lineCreate($player, 5, TextFormat::RED . 'Kills Till Nuke: ' . TextFormat::WHITE . $session->getBattlefieldKillstreak(true));
			}
			$this->lineCreate($player, 7, '');
			$this->lineCreate($player, 8, TextFormat::ITALIC . TextFormat::GRAY . Variables::DOMAIN);
			$this->lineCreate($player, 9, '§8-<                     >-§', false); // 

			$this->scoreboard[$player->getName()] = $player->getName();
		}
	}

	public function updateFfaKills(Player $player, ?int $provided = null): void
	{
		if (!is_int($provided)) {
			$session = $this->plugin->sessionManager->getSession($player);
			if (!$session instanceof Session) {
				return;
			}

			$variable = $session->getKills();
		} else {
			$variable = $provided;
		}

		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetFfa($player)) {
				$this->lineRemove($player, 1);
				$this->lineCreate($player, 1, TextFormat::AQUA . 'Kills: ' . TextFormat::WHITE . $variable);
			}
		}
	}

	public function updateFfaKillstreak(Player $player, ?int $provided = null): void
	{
		if (!is_int($provided)) {
			$session = $this->plugin->sessionManager->getSession($player);
			if (!$session instanceof Session) {
				return;
			}

			$variable = $session->getKillstreak();
		} else {
			$variable = $provided;
		}

		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetFfa($player)) {
				$this->lineRemove($player, 2);
				$this->lineCreate($player, 2, TextFormat::AQUA . 'Killstreak: ' . TextFormat::WHITE . $variable);
			}
		}
	}

	public function updateFfaDeaths(Player $player, ?int $provided = null): void
	{
		if (!is_int($provided)) {
			$session = $this->plugin->sessionManager->getSession($player);
			if (!$session instanceof Session) {
				return;
			}

			$variable = $session->getDeaths();
		} else {
			$variable = $provided;
		}

		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetFfa($player)) {
				$this->lineRemove($player, 3);
				$this->lineCreate($player, 3, TextFormat::AQUA . 'Deaths: ' . TextFormat::WHITE . $variable);
			}
		}
	}

	public function updateFfaKdr(Player $player, ?int $killsProvided = null, ?int $deathsProvided = null): void
	{
		if (!is_int($killsProvided) and !is_int($deathsProvided)) {
			$session = $this->plugin->sessionManager->getSession($player);
			if (!$session instanceof Session) {
				return;
			}

			if ($session->getCurrentWarp() !== Session::WARP_BATTLEFIELD) {
				return;
			}

			$variable = $session->getDeaths() > 0 ? round($session->getKills() / $session->getDeaths(), 2) : $session->getKills() . '.00';
		} else {
			$variable = $deathsProvided > 0 ? round($killsProvided / $deathsProvided, 2) : $killsProvided . '.00';
		}

		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetFfa($player)) {
				$this->lineRemove($player, 4);
				$this->lineCreate($player, 4, TextFormat::AQUA . 'K/D Ratio: ' . TextFormat::WHITE . $variable);
			}
		}
	}

	public function updateFfaBattlefieldKillstreak(Player $player, ?int $provided = null): void
	{
		if (!is_int($provided)) {
			$session = $this->plugin->sessionManager->getSession($player);
			if (!$session instanceof Session) {
				return;
			}

			$variable = $session->getBattlefieldKillstreak(true);
		} else {
			$variable = $provided;
		}

		if ($this->isPlayerSetScoreboard($player)) {
			if ($this->isPlayerSetFfa($player)) {
				$this->lineRemove($player, 5);
				$this->lineCreate($player, 5, TextFormat::RED . 'Kills Till Nuke: ' . TextFormat::WHITE . $variable);
			}
		}
	}

	public function isPlayerSetScoreboard(Player $player): bool
	{
		return isset($this->scoreboard[$player->getName()]);
	}

	public function isPlayerSetMain(Player $player): bool
	{
		return isset($this->main[$player->getName()]);
	}

	public function isPlayerSetFfa($player): bool
	{
		return isset($this->ffa[$player->getName()]);
	}

	public function getCorrectScoreboard($player): void
	{
		if ($this->isPlayerSetMain($player)) {
			$this->addMain($player);
		} elseif ($this->isPlayerSetFfa($player)) {
			$this->addFfa($player);
		} else {
			$player->sendMessage(TextFormat::RED . 'Your scoreboard will be displayed once you\'re sent to spawn.');
		}
	}

	public function lineTitle(Player $player, string $title = TextFormat::BOLD . TextFormat::DARK_AQUA . 'VASAR'): void
	{
		$session = $this->plugin->sessionManager->getSession($player);

		if (!$session->isScoreboard()) {
			return;
		}

		$packet = new SetDisplayObjectivePacket();
		$packet->displaySlot = 'sidebar';
		$packet->objectiveName = 'objective';
		$packet->displayName = $title;
		$packet->criteriaName = 'dummy';
		$packet->sortOrder = 0;
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public function removeScoreboard(Player $player, bool $clear = true): void
	{

		$packet = new RemoveObjectivePacket();
		$packet->objectiveName = 'objective';
		$player->getNetworkSession()->sendDataPacket($packet);

		if ($clear) {
			if ($this->isPlayerSetScoreboard($player)) {
				unset($this->scoreboard[$player->getName()]);
			}
			if ($this->isPlayerSetMain($player)) {
				unset($this->main[$player->getName()]);
			}
			if ($this->isPlayerSetFfa($player)) {
				unset($this->ffa[$player->getName()]);
			}
		}
	}

	public function lineCreate(Player $player, int $line, string $string, bool $space = true): void
	{
		$session = $this->plugin->sessionManager->getSession($player);

		if (!$session->isScoreboard()) {
			return;
		}

		$packetLine = new ScorePacketEntry();
		$packetLine->objectiveName = 'objective';
		$packetLine->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
		$packetLine->customName = $space ? '  ' . $string . '  ' : $string;
		$packetLine->score = $line;
		$packetLine->scoreboardId = $line;
		$packet = new SetScorePacket();
		$packet->type = SetScorePacket::TYPE_CHANGE;
		$packet->entries[] = $packetLine;

		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public function lineRemove(Player $player, int $line): void
	{
		$entry = new ScorePacketEntry();
		$entry->objectiveName = 'objective';
		$entry->score = $line;
		$entry->scoreboardId = $line;
		$packet = new SetScorePacket();
		$packet->type = SetScorePacket::TYPE_REMOVE;
		$packet->entries[] = $entry;

		$player->getNetworkSession()->sendDataPacket($packet);
	}
}