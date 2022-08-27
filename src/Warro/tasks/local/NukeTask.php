<?php

declare(strict_types=1);

namespace Warro\tasks\local;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\ExplodeSound;
use Warro\Base;
use Warro\Session;
use Warro\User;

class NukeTask extends Task
{

	private int $seconds = 6;

	public function __construct(private Session $session, private Player|User $player, private array $players)
	{
	}

	public function onRun(): void
	{
		if (!$this->player->isConnected()) {
			$this->getHandler()->cancel();
			return;
		}

		if ($this->session->isAfk() or !$this->player->isAlive()) {
			Server::getInstance()->broadcastMessage(TextFormat::RED . $this->player->getDisplayName() . ' has forfeit their Nuke.');
			$this->getHandler()->cancel();
			return;
		}

		$this->seconds--;

		if ($this->seconds > 0) {
			Server::getInstance()->broadcastMessage(TextFormat::RED . $this->player->getDisplayName() . '\'s Nuke will activate in ' . TextFormat::GOLD . $this->seconds . TextFormat::RED . ' seconds!');
		}

		if ($this->seconds === 1) {
			foreach ($this->players as $player) {
				if ($player instanceof Player) {
					if ($player->getName() !== $this->player->getName()) {
						if ($player->isConnected() and $player->isAlive()) {
							$session = Base::getInstance()->sessionManager->getSession($player);
							if ($session instanceof Session) {
								if ($session->getCurrentWarp() === Session::WARP_BATTLEFIELD and !$session->isAfk() and $player->noDamageTicks === 0 and $player->isSurvival()) {
									$player->noDamageTicks = 40;

									$playerLocation = $player->getLocation();
									$killerLocation = $this->player->getLocation();

									$player->knockBack($playerLocation->x - $killerLocation->x, $playerLocation->z - $killerLocation->z, 4.0, 2.5);
								}
							}
						}
					}
				}
			}
		} elseif ($this->seconds === 0) {
			foreach ($this->players as $player) {
				if ($player instanceof Player) {
					if ($player->getName() !== $this->player->getName()) {
						if ($player->isConnected() and $player->isAlive()) {
							$session = Base::getInstance()->sessionManager->getSession($player);
							if ($session instanceof Session) {
								if ($session->getCurrentWarp() === Session::WARP_BATTLEFIELD and !$session->isAfk() and $player->noDamageTicks === 0 and $player->isSurvival()) {
									if ($this->seconds === 0) {
										$session->setDamager($this->player->getName());

										$player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
										$player->getWorld()->addSound($player->getPosition(), new ExplodeSound());

										$player->kill();
									}
								}
							}
						}
					}
				}
			}
			$this->getHandler()->cancel();
		}
	}
}