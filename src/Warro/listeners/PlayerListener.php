<?php

declare(strict_types=1);

namespace Warro\listeners;

use JsonException;
use muqsit\invmenu\InvMenu;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\{EntityDamageByBlockEvent,
	EntityDamageByChildEntityEvent,
	EntityDamageByEntityEvent,
	EntityDamageEvent,
	EntityItemPickupEvent,
	ProjectileLaunchEvent
};
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\BlazeShootSound;
use Warro\Base;
use Warro\managers\RankManager;
use Warro\Session;
use Warro\tasks\async\BanVerificationTask;
use Warro\tasks\async\InitializePlayerAliasTask;
use Warro\User;
use Warro\Variables;

class PlayerListener implements Listener
{

	public function __construct(private Base $plugin)
	{
	}

	/**
	 * @priority HIGH
	 */
	public function onCreation(PlayerCreationEvent $event): void
	{
		$event->setPlayerClass(User::class);
	}

	/**
	 * @priority HIGH
	 */
	public function onLogin(PlayerLoginEvent $event): void
	{
		$player = $event->getPlayer();

		$player->setNameTag(TextFormat::ITALIC . TextFormat::GRAY . 'Logging In...');

		$session = $this->plugin->sessionManager->createSession($player);

		$info = $player->getNetworkSession()->getPlayerInfo();

		if ($info instanceof PlayerInfo) {
			$session->setInputMode($info->getExtraData()['CurrentInputMode'] ?? 'Unknown');
			$session->setOperatingSystem($info->getExtraData()['DeviceOS'] ?? 'Unknown');
			$session->setDeviceModel($info->getExtraData()['DeviceModel'] ?? 'Unknown');
			$session->setDeviceId($info->getExtraData()['DeviceId'] ?? 'Unknown');
			$session->setClientRandomId($info->getExtraData()['ClientRandomId'] ?? 'Unknown');
			$session->setSelfSignedId($info->getExtraData()['SelfSignedId'] ?? 'Unknown');
			$session->setServerAddress($info->getExtraData()['ServerAddress'] ?? 'Unknown');
			$session->setGameVersion($info->getExtraData()['GameVersion'] ?? 'Unknown');

			if ($session->getInputMode(true) === 'Mouse') {
				$session->useMenusOverForms = true;
			}

			if (!Server::getInstance()->isOp($player->getName())) {
				Server::getInstance()->getAsyncPool()->submitTask(new BanVerificationTask(
					$player->getName(),
					$this->plugin->sutils->bans,
					$this->plugin->getDataFolder() . 'aliases/' . md5($player->getNetworkSession()->getIp()),
					$this->plugin->getDataFolder() . 'aliases/' . md5($info->getExtraData()['DeviceId'] ?? 'Unknown'),
					$this->plugin->getDataFolder() . 'aliases/' . md5(strval($info->getExtraData()['ClientRandomId']) ?? 'Unknown'),
					$this->plugin->getDataFolder() . 'aliases/' . md5($info->getExtraData()['SelfSignedId'] ?? 'Unknown'),
				));
			}
		} else {
			$player->kick(TextFormat::RED . 'There was a problem handling your client data, please try re-connecting.');
			return;
		}

		Server::getInstance()->getAsyncPool()->submitTask(new InitializePlayerAliasTask($this->plugin->getDataFolder() . 'aliases/' . md5($player->getNetworkSession()->getIp()), $player->getName()));
		Server::getInstance()->getAsyncPool()->submitTask(new InitializePlayerAliasTask($this->plugin->getDataFolder() . 'aliases/' . md5($session->getDeviceId()), $player->getName()));
		Server::getInstance()->getAsyncPool()->submitTask(new InitializePlayerAliasTask($this->plugin->getDataFolder() . 'aliases/' . md5(strval($session->getClientRandomId())), $player->getName()));
		Server::getInstance()->getAsyncPool()->submitTask(new InitializePlayerAliasTask($this->plugin->getDataFolder() . 'aliases/' . md5($session->getSelfSignedId()), $player->getName()));

		$player->setImmobile(true);

		$this->plugin->utils->teleport($player, 0);
		$this->plugin->utils->kit($player, 0, true);
	}

	/**
	 * @priority HIGH
	 * @throws JsonException
	 */
	public function onJoin(PlayerJoinEvent $event): void
	{
		$player = $event->getPlayer();
		$event->setJoinMessage('');

		if (!$player instanceof User) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($player);
		if (!$session instanceof Session) {
			$player->kick(TextFormat::RED . 'There was a problem creating your session, please try re-connecting.');
			return;
		}

		$session->onJoin();

		foreach ($this->plugin->utils->getVanishPlayers() as $vp) {
			$vanishPlayer = Server::getInstance()->getPlayerExact($vp);
			if ($vanishPlayer instanceof Player) {
				$player->hidePlayer($vanishPlayer);
				$this->plugin->utils->removeOnlinePlayer($vanishPlayer, $player);
			} else {
				unset($this->plugin->utils->getVanishPlayers()[$vp]);
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onQuit(PlayerQuitEvent $event): void
	{
		$player = $event->getPlayer();
		$event->setQuitMessage('');

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (is_null($session)) {
			return;
		}

		$session->onQuit($event);
	}

	/**
	 * @priority HIGH
	 */
	public function onChat(PlayerChatEvent $event): void
	{
		$player = $event->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (is_string($session->banned)) {
			$event->cancel();
			return;
		}

		$event->setMessage(str_replace([':skull:', ':L:', ':fire:', ':eyes:', ':clown:', ':100:', ':heart:'], ['', '', '', '', '', '', ''], $event->getMessage()));
		$event->setFormat($this->plugin->utils->getChatFormat($player, $event));

		if (!Server::getInstance()->isOp($player->getName())) {
			if (time() - $session->chatWait < 3) {
				$this->plugin->utils->handleChat($event, false);
				$event->setRecipients([$player]);
				return;
			}
			$session->chatWait = time();

			$lc = str_replace(' ', '', strtolower($event->getMessage()));

			foreach ($this->plugin->utils->links as $links) {
				if (str_contains($lc, $links)) {
					$event->setRecipients([$player]);
					break;
				}
			}

			/*foreach ($this->plugin->utils->getIllegalWords() as $illegalWord) {
				if (str_contains($lc, $illegalWord)) {
					$event->cancel();
					return;
				}
			}*/

			$this->plugin->utils->handleChat($event);

			if ($session->muted) {
				$event->setRecipients([$player]);

				if (isset($this->plugin->sutils->mutes[strtolower($player->getName())])) {
					$muteInfo = $this->plugin->sutils->mutes[strtolower($player->getName())];
					if ($muteInfo['expires'] < time()) {
						$this->plugin->sutils->unmutePlayer($player->getName(), 'Automatic');
					}
				}
			}
		}

		$message = $event->getMessage();
		if ($message[0] === '!' or $session->getChat() === 'staff') {
			if ($player->hasPermission('vasar.chat.staff')) {
				foreach ($this->plugin->utils->onlineStaff as $st) {
					if (is_string($st)) {
						$staff = Server::getInstance()->getPlayerExact($st);
						if ($staff instanceof Player and $staff->isConnected()) {
							if ($staff->hasPermission('vasar.chat.staff')) {
								$staff->sendMessage(TextFormat::DARK_GRAY . '[S] ' . TextFormat::DARK_RED . '[' . $session->getHighestRank(true) . '] ' . TextFormat::RED . $player->getName() . ': ' . TextFormat::GRAY . str_replace('!', '', $message));
							}
						}
					}
				}
				$event->cancel();
			} else {
				$session->setChat();
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onUseItem(PlayerItemUseEvent $event): void
	{
		$player = $event->getPlayer();
		$item = $event->getItem();

		if (!$player instanceof User) {
			return;
		}

		$name = $item->getCustomName();

		if ($name === Variables::LOBBY_ITEM_CLASH) {
			//Base::getInstance()->forms->clash($player);
			$player->sendJukeboxPopup(TextFormat::DARK_GRAY . $this->plugin->utils->comingSoonUse[array_rand($this->plugin->utils->comingSoonUse)], []);
		} elseif ($name === Variables::LOBBY_ITEM_FREE_FOR_ALL) {
			Base::getInstance()->forms->freeForAll($player);
		} elseif ($name === Variables::LOBBY_ITEM_SETTINGS) {
			Base::getInstance()->forms->settings($player);
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onTransaction(InventoryTransactionEvent $event)
	{
		$transaction = $event->getTransaction();
		$actions = $transaction->getActions();
		foreach ($actions as $action) {
			if ($action instanceof SlotChangeAction) {
				$inventory = $action->getInventory();
				$player = $inventory->getHolder();
				if ($player instanceof User) {
					if ($player->isCreative()) {
						return;
					}
					$session = Base::getInstance()->sessionManager->getSession($player);
					if ($session->getCurrentWarp() === Session::WARP_SPAWN) {
						$event->cancel();
					} elseif (/*$this->matchHandler->isASpectator($player) or */ !$session->canTakeDamage()) {
						$event->cancel();
						return;
					}
				}
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onInventoryPickup(EntityItemPickupEvent $event)
	{
		$player = $event->getOrigin();
		if ($player instanceof User) {
			$session = Base::getInstance()->sessionManager->getSession($player);
			if (/*$this->matchHandler->isASpectator($player) or */ !$session->canTakeDamage()) {
				$event->cancel();
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onMove(PlayerMoveEvent $event): void
	{
		$player = $event->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (is_null($session)) {
			return;
		}

		$from = $event->getFrom();
		$to = $event->getTo();

		if ($session->isFrozen()) {
			if ($from->getX() != $to->getX() and $from->getZ() != $to->getZ()) {
				$event->cancel();
				return;
			}
		}

		/*if ($session->isAutoSprint()) {
			if ($from->getX() != $to->getX() and $from->getZ() != $to->getZ()) {
				if (!$player->isFlying()) {
					$player->toggleSprint(true);
				}
			}
		}*/

		$position = $player->getPosition();

		if ($session->getCurrentWarp() === Session::WARP_SPAWN) {
			if ($position->getY() <= 0) {
				$this->plugin->utils->teleport($player, 0);
			}
			if ($session->hasRank(RankManager::PLUS) or $session->isStaff()) {
				if (!$player->isFlying() and $player->getInAirTicks() >= 18 and $player->getInAirTicks() <= 60) {
					$player->getWorld()->addParticle($position->asVector3()->add(0, 1.8, 0), new FlameParticle());
				}
			}

			if (!is_null($session->getLastMove())) {
				$session->setLastMove();
			}
		} else {
			$Y = match ($session->getCurrentWarp()) {
				1 => $this->plugin->utils->nodebuffFreeForAllTetris->subtract(0, 10, 0),
				2 => $this->plugin->utils->sumoFreeForAll->subtract(0, 10, 0),
				3 => $this->plugin->utils->nodebuffFreeForAllPlains->subtract(0, 10, 0),
				4 => $this->plugin->utils->hiveFreeForAll->subtract(0, 10, 0),
				5 => $this->plugin->utils->battlefieldFreeForAll->subtract(0, 10, 0),
			};
			if ($position->getY() <= $Y->getY()) {
				$player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_SUICIDE, 1000));
			}

			$session->setLastMove(time());
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onDeath(PlayerDeathEvent $event): void
	{
		$player = $event->getPlayer();
		$event->setDeathMessage('');
		$event->setDrops([]);
		$event->setXpDropAmount(0);

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (!$session instanceof Session) {
			return;
		}

		if ($session->hasDamager()) {
			$damager = Server::getInstance()->getPlayerExact($session->getDamager());
			if ($damager instanceof User) {
				Base::getInstance()->utils->onDeath($player, $damager);
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onRespawn(PlayerRespawnEvent $event): void
	{
		$player = $event->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$event->setRespawnPosition($this->plugin->utils->spawnLocation);

		$this->plugin->utils->teleport($player, 0, true);
		/*$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
			$this->plugin->utils->teleport($player, 0, true);
		}), 1);*/
	}

	/**
	 * @priority HIGH
	 */
	public function onEntityDamage(EntityDamageEvent $event): void
	{
		$player = $event->getEntity();
		$cause = $event->getCause();

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (is_null($session)) {
			return;
		}

		if ($session->getCurrentWarp() === Session::WARP_SPAWN or !$session->canTakeDamage()) {
			$event->cancel();
			return;
		}

		if ($cause === EntityDamageEvent::CAUSE_FALL and $session->getCurrentWarp() !== Session::WARP_BATTLEFIELD) {
			$event->cancel();
			return;
		}

		if ($event instanceof EntityDamageByEntityEvent and !$event instanceof EntityDamageByChildEntityEvent) {
			$damager = $event->getDamager();

			if (!$damager instanceof User) {
				return;
			}

			$sessionDamager = Base::getInstance()->sessionManager->getSession($damager);

			if ($sessionDamager->isVanished()) {
				$event->cancel();
				return;
			}

			$warp = $sessionDamager->getCurrentWarp();
			if ($warp === Session::WARP_SUMO or $warp === Session::WARP_HIVE) {
				$event->setBaseDamage(0.0);
				if ($warp === 4) {
					if ($event->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
						$event->setModifier(0.0, EntityDamageEvent::MODIFIER_CRITICAL);
					}
				}
			}

			if ($warp !== Session::WARP_BATTLEFIELD) {
				if ($sessionDamager->isAntiInterfere() and $this->plugin->utils->isTagged($damager)) {
					if ($sessionDamager->getDamager() !== $player->getName()) {
						if ($sessionDamager->isAntiClutter()) {
							$damager->hideThem($player);
						}
						$damager->sendMessage(TextFormat::RED . 'You can\'t hit other players while you have anti-interference enabled.');
						$event->cancel();
						return;
					}
				}
				if ($session->isAntiInterfere() and $this->plugin->utils->isTagged($player)) {
					if ($session->getDamager() !== $damager->getName()) {
						if ($session->isAntiClutter()) {
							$player->hideThem($damager);
						}
						$damager->sendMessage(TextFormat::RED . 'This player has anti-interference enabled.');
						$event->cancel();
						return;
					}
				}
			}

			if (!$event->isCancelled()) {
				foreach ([$player, $damager] as $players) {
					$this->plugin->utils->setTagged($players, true, true);
				}

				$sessionDamager->setLastMove(time());
				$sessionDamager->setDamager($player->getName());

				$session->setLastMove(time());
				$session->setDamager($damager->getName());
				$session->setLastDamagePosition($damager->getPosition());
			}

			Base::getInstance()->utils->doDamageCheck($player, $event);
		} elseif ($event instanceof EntityDamageByChildEntityEvent) {
			$damager = $event->getChild();
			$owner = $damager->getOwningEntity();
			if ($owner instanceof User) {
				if ($damager instanceof Projectile) {
					if ($player->getName() === $owner->getName()) {
						$event->cancel();
						return;
					}
				}

				$sessionOwner = Base::getInstance()->sessionManager->getSession($owner);

				if ($sessionOwner->isVanished()) {
					$event->cancel();
					return;
				}

				if ($sessionOwner->getCurrentWarp() !== Session::WARP_BATTLEFIELD) {
					if ($sessionOwner->isAntiInterfere() and $this->plugin->utils->isTagged($owner)) {
						if ($sessionOwner->getDamager() !== $player->getName()) {
							if ($sessionOwner->isAntiClutter()) {
								$owner->hideThem($player);
							}
							$owner->sendMessage(TextFormat::RED . 'You can\'t hit other players while you have anti-interference enabled.');
							$event->cancel();
							return;
						}
					}
					if ($session->isAntiInterfere() and $this->plugin->utils->isTagged($player)) {
						if ($session->getDamager() !== $owner->getName()) {
							if ($session->isAntiClutter()) {
								$player->hideThem($owner);
							}
							$owner->sendMessage(TextFormat::RED . 'This player has anti-interference enabled.');
							$event->cancel();
							return;
						}
					}
				}

				if (!$event->isCancelled()) {
					foreach ([$player, $owner] as $players) {
						Base::getInstance()->utils->setTagged($players, true, true);
					}
					$session->setDamager($owner->getName());
					$sessionOwner->setDamager($player->getName());
				}
			}

			Base::getInstance()->utils->doDamageCheck($player, $event);
		} elseif ($event instanceof EntityDamageByBlockEvent) {
			$event->cancel();
		} else {
			Base::getInstance()->utils->doDamageCheck($player, $event);
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onExhaust(PlayerExhaustEvent $event): void
	{
		$event->cancel();
	}

	/**
	 * @priority HIGH
	 */
	public function onDropItem(PlayerDropItemEvent $event): void
	{
		$player = $event->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if ($session->isVanished()) {
			$event->cancel();
			return;
		}

		if (!$player->isCreative()) {
			$event->cancel();
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onProjectileLaunch(ProjectileLaunchEvent $event): void
	{
		$player = $event->getEntity();

		if (!$player instanceof User) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if ($session->isVanished()) {
			$event->cancel();
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onDataReceive(DataPacketReceiveEvent $event): void
	{
		$packet = $event->getPacket();

		$player = $event->getOrigin()->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$session = $this->plugin->sessionManager->getSession($player);

		if ($packet::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID and $packet instanceof LevelSoundEventPacket) {
			if ($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE) {
				$player->broadcastAnimation(new ArmSwingAnimation($player), $player->getViewers());
			}

			if ($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE or $packet->sound === LevelSoundEvent::ATTACK_STRONG) {
				if ($this->plugin->cpsManager->doesPlayerExist($player)) {
					$this->plugin->cpsManager->addClick($player);
				}

				if ($session->getCurrentWarp() === Session::WARP_SPAWN) {
					if (!$player->isFlying() and !$player->isOnGround() and $player->isSurvival()) {
						if ($session->boostWait <= microtime(true)) {
							$session->boostWait = microtime(true) + 1.5;

							$player->toggleSprint(false);

							$player->setMotion(new Vector3($player->getDirectionVector()->getX() * 1.75, 0.95, $player->getDirectionVector()->getZ() * 1.75));
							$this->plugin->utils->doSoundPacket($player, 'mob.vex.hurt', 0.5, 1, true);
						} else {
							if ($session->hasRank(RankManager::VOTER) or $session->hasRank(RankManager::NITRO) or $session->hasRank(RankManager::PLUS) or $session->isStaff()) {
								if ($player->getFallDistance() > 0.3) {
									if ($session->lastBoostTimestamp <= microtime(true)) {
										$session->lastBoostTimestamp = microtime(true) + 6.0;

										$player->setMotion(new Vector3($player->getDirectionVector()->getX() * 3.75, $player->getDirectionVector()->getY() * 2, $player->getDirectionVector()->getZ() * 3.75));

										$player->getWorld()->addParticle($player->getPosition()->asVector3(), new HugeExplodeParticle());

										$player->getWorld()->addSound($player->getPosition()->asVector3(), new BlazeShootSound());
									}
								}
							}
						}
					}
				}
			}
		} elseif ($packet::NETWORK_ID === PlayerAuthInputPacket::NETWORK_ID and $packet instanceof PlayerAuthInputPacket) {
			if ($session->isAutoSprint()) {
				if ($packet->hasFlag(PlayerAuthInputFlags::UP) and !$player->isSprinting()) {
					$player->setSprinting(true);
				} elseif ($packet->hasFlag(PlayerAuthInputFlags::DOWN) and $player->isSprinting()) {
					$player->setSprinting(false);
				}
			}
		} elseif ($packet::NETWORK_ID === EmotePacket::NETWORK_ID and $packet instanceof EmotePacket) {
			$event->cancel();
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onDataSend(DataPacketSendEvent $event): void
	{
		$packets = $event->getPackets();
		foreach ($packets as $packet) {
			if ($packet::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID and $packet instanceof LevelSoundEventPacket) {
				if ($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE or $packet->sound === LevelSoundEvent::ATTACK_STRONG) {
					$event->cancel();
				}
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onSkinChange(PlayerChangeSkinEvent $event): void
	{
		$player = $event->getPlayer();

		if (!$player instanceof User) {
			return;
		}

		$event->cancel();
		$player->sendMessage(TextFormat::RED . 'For safety reasons, you can\'t change your skin in-game.');
	}
}