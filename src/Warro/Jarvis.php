<?php

/*
$webhookMessage = new Message();
$webhookMessage->setContent('');
$embed = new Embed();
$embed->setTitle('Anti-Cheat (Practice)');
$embed->setColor(0xFF8000);
$embed->setDescription('Player: **`' . $damager->getName() . ' (' . $damager->getPing() . 'ms)`**' . TextFormat::EOL . 'Detection: **`Reach`**' . TextFormat::EOL . 'Details: **`' . round($distance, 2) . ' blocks`**' . TextFormat::EOL . 'Violations: **`' . $damager->getReachFlags() . 'x`**');
$webhookMessage->addEmbed($embed);
$webhook = new Webhook(Variables::ANTICHEAT_WEBHOOK, $webhookMessage);
$webhook->send();
*/

declare(strict_types=1);

namespace Warro;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use Exception;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Jarvis implements Listener
{

	public static Jarvis $instance;

	public const REACH = 0;
	public const CPS = 1;
	public const TIMER = 2;
	public const VELOCITY = 3;

	public static function getInstance(): Jarvis
	{
		return self::$instance;
	}

	public function __construct(private Base $plugin)
	{
		self::$instance = $this;
	}

	public function sendViolationAlert(int $violationType, string $player, int $ping, int $violations, float|int $details = 0.0): void
	{
		$message = $this->plugin->sutils->formatViolationMessage($violationType, $player, $ping, $violations, $details);

		if (is_null($message)) {
			return;
		}

		foreach ($this->plugin->utils->onlineStaff as $st) {
			$staff = Server::getInstance()->getPlayerExact($st);
			if ($staff instanceof User and $staff->isConnected()) {
				$session = Base::getInstance()->sessionManager->getSession($staff);
				if ($session instanceof Session) {
					$staff->sendMessage($message);
				}
			}
		}
	}

	public function verifyTimer(Player $player): void
	{
		if ($player instanceof User and $player->isConnected()) {
			$session = $this->plugin->sessionManager->getSession($player);

			if (!$session instanceof Session) {
				return;
			}

			$ping = $player->getNetworkSession()->getPing();

			if (!$player->isAlive()) {
				$session->timerLastTimestamp = -1.0;
				return;
			}

			$timestamp = microtime(true);

			if ($session->timerLastTimestamp === -1.0) {
				$session->timerLastTimestamp = $timestamp;
				return;
			}

			$diff = $timestamp - $session->timerLastTimestamp;

			$session->timerBalance += 0.05;
			$session->timerBalance -= $diff;

			if ($session->timerBalance >= 0.25) {
				$session->timerBalance = 0.0;

				if (time() - $session->timerWait < 1) {
					return;
				}
				$session->timerWait = time();

				$session->timerViolations++;

				$this->sendViolationAlert(self::TIMER, $player->getName(), $ping, $session->timerViolations, round($diff, 3));
			}

			$session->timerLastTimestamp = $timestamp;
		}
	}

	/**
	 * @priority HIGHEST
	 * @throws Exception
	 */
	public function cps(EntityDamageByEntityEvent $event)
	{
		if ($event->isCancelled()) {
			return;
		}
		$player = $event->getEntity();
		$cause = $event->getCause();
		if (!$event instanceof EntityDamageByChildEntityEvent) {
			if ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
				$damager = $event->getDamager();
				if ($player instanceof User and $damager instanceof User and $damager->isSurvival()) {
					$session = $this->plugin->sessionManager->getSession($damager);

					if (!$session instanceof Session) {
						return;
					}

					$ping = $damager->getNetworkSession()->getPing();

					if ($this->plugin->cpsManager->doesPlayerExist($damager)) {
						$cps = $this->plugin->cpsManager->getCps($damager);
						if ($cps > 19) {
							if ($cps > 25) {
								$damager->sendMessage(TextFormat::RED . 'Please reduce your CPS.');
								$event->cancel();
							}

							if (time() - $session->cpsWait < 1) {
								return;
							}
							$session->cpsWait = time();

							$webhookMessage = new Message();
							$webhookMessage->setContent('');
							$embed = new Embed();
							$embed->setTitle('Jarvis on Practice');
							$embed->setColor(0xFF8000);
							$embed->setDescription('Suspect: **`' . $damager->getName() . ' (' . $ping . 'ms)`**' . TextFormat::EOL . 'Detection: **`High CPS`**' . TextFormat::EOL . 'Details: **`' . $cps . ' cps`**' . TextFormat::EOL . 'Violations: **`' . $session->cpsViolations . 'x`**');
							$webhookMessage->addEmbed($embed);
							$webhook = new Webhook(Variables::JARVIS_WEBHOOK);
							$webhook->send($webhookMessage);

							if ($session->cpsViolations >= 3) {
								$this->sendViolationAlert(self::CPS, $damager->getName(), $ping, $session->cpsViolations, $cps);
							}

							$session->addCpsViolation();
						}
					}
				}
			}
		}
	}

	/**
	 * @priority HIGHEST
	 * @throws Exception
	 */
	public function reach(EntityDamageByEntityEvent $event)
	{
		if ($event->isCancelled()) {
			return;
		}
		$player = $event->getEntity();
		$cause = $event->getCause();
		if (!$event instanceof EntityDamageByChildEntityEvent) {
			if ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
				$damager = $event->getDamager();
				if ($player instanceof User and $damager instanceof User and $damager->isSurvival()) {
					$session = $this->plugin->sessionManager->getSession($damager);

					if (!$session instanceof Session) {
						return;
					}

					$damagerPing = $damager->getNetworkSession()->getPing();
					$playerPing = $player->getNetworkSession()->getPing();

					$distance = $player->getEyePos()->distance(new Vector3($damager->getEyePos()->getX(), $player->getEyePos()->getY(), $damager->getEyePos()->getZ()));
					$distance -= $damagerPing * 0.0041;
					$distance -= $playerPing * 0.0051;

					if ($distance < 1) {
						return;
					}

					if ($player->isSprinting()) {
						$distance -= 0.97;
					} elseif (!$player->isSprinting()) {
						$distance -= 0.87;
					}

					if ($damager->isSprinting()) {
						$distance -= 0.77;
					} elseif (!$damager->isSprinting()) {
						$distance -= 0.67;
					}

					if ($distance > 5) {
						$event->cancel();
						return;
					}

					if ($distance > 3.0) {
						if (time() - $session->reachWait < 1) {
							return;
						}
						$session->reachWait = time();

						$detail = round($distance, 3);

						$webhookMessage = new Message();
						$webhookMessage->setContent('');
						$embed = new Embed();
						$embed->setTitle('Jarvis on Practice');
						$embed->setColor(0xFF8000);
						$embed->setDescription('Suspect: **`' . $damager->getName() . ' (' . $damagerPing . 'ms)`**' . TextFormat::EOL . 'Detection: **`Reach`**' . TextFormat::EOL . 'Details: **`about ' . $detail . ' blocks`**' . TextFormat::EOL . 'Violations: **`' . $session->reachViolations . 'x`**');
						$webhookMessage->addEmbed($embed);
						$webhook = new Webhook(Variables::JARVIS_WEBHOOK);
						$webhook->send($webhookMessage);

						if ($session->reachViolations >= 3) {
							$this->sendViolationAlert(self::REACH, $damager->getName(), $damagerPing, $session->reachViolations, $detail);
						}

						$session->addReachViolation();
					}
				}
			}
		}
	}

	/*public function timer(DataPacketReceiveEvent $event): void
	{
		$player = $event->getOrigin()->getPlayer();
		$packet = $event->getPacket();
		if ($player instanceof User and $player->isConnected() and $packet instanceof PlayerAuthInputPacket) {
			if (!$player->spawned or !$player->isSurvival()) {
				return;
			}
			$this->verifyTimer($player);
		}
	}

	public function velocity(DataPacketReceiveEvent $event): void
	{
		$player = $event->getOrigin()->getPlayer();
		$packet = $event->getPacket();
		if ($player instanceof User and $player->isConnected() and $packet instanceof PlayerAuthInputPacket) {
			if (!$player->spawned or !$player->isSurvival()) {
				return;
			}

			$session = $this->plugin->sessionManager->getSession($player);

			if (!$session instanceof Session) {
				return;
			}

			$ping = $player->getNetworkSession()->getPing();

			if (!is_int($ping)) {
				return;
			}

			if ($session->getRecentKit() === 0) {
				return;
			}

			$blockAbove = $player->getWorld()->getBlockAt($player->getPosition()->getFloorX(), $player->getPosition()->getFloorY() + 2, $player->getPosition()->getFloorZ(), true, true);
			if ($blockAbove instanceof Block and !$blockAbove instanceof Air) {
				return;
			}

			if ($player->isUnderwater()) {
				return;
			}

			if (is_null($session->lastLocation)) {
				$session->lastLocation = $packet->getPosition()->subtract(0, 1.62, 0);
				$session->currentMotion = null;
				return;
			}

			if (($motion = ($session->currentMotion ?? null)) !== null) {
				$movementY = $packet->getPosition()->subtract(0, 1.62, 0)->y - $session->lastLocation->y;
				if (!$player->isAlive() or $player->isImmobile()) {
					$motion = 0;
					return;
				}
				if ($motion > 0.005) {
					$percentage = ($movementY / $motion);
					if ($percentage < 0.9999 and $percentage > 0.01) {
						if (time() - $session->velocityWait < 1) {
							return;
						}
						$session->velocityWait = time();

						$session->velocityViolations++;

						$this->sendViolationAlert(self::VELOCITY, $player->getName(), $ping, $session->velocityViolations, round($percentage, 3));
					}
					$session->currentMotion -= 0.08;
					$session->currentMotion *= 0.98;
				} else {
					$session->currentMotion = null;
				}
			}
			$session->lastLocation = $packet->getPosition()->subtract(0, 1.62, 0);
		} elseif ($packet instanceof NetworkStackLatencyPacket) {
			NetworkStackLatencyManager::getInstance()->execute($player, $packet->timestamp);
		}
	}

	public function onReceive(DataPacketReceiveEvent $event): void
	{
		$origin = $event->getOrigin();
		$player = $origin->getPlayer();
		if ($player instanceof User and $player->isConnected()) {
			$packet = $event->getPacket();
			if ($packet instanceof PlayerAuthInputPacket and !is_null($origin->getHandler())) {
				$event->cancel();
				$pkPos = $packet->getPosition();
				foreach ([$pkPos->x, $pkPos->y, $pkPos->z, $packet->getYaw(), $packet->getHeadYaw(), $packet->getPitch()] as $float) {
					if (is_infinite($float) || is_nan($float)) {
						Base::getInstance()->getLogger()->debug('Invalid movement received, contains NAN/INF components');
						return;
					}
				}

				$pos = $player->getLocation();
				$distanceSquared = $pkPos->round(4)->subtract(0, 1.62, 0)->distanceSquared($player->getPosition());
				// The packet is sent every tick so only handle movement if the player has moved
				if ($packet->getYaw() - $pos->getYaw() !== 0.0 || $packet->getPitch() - $pos->getPitch() !== 0.0 || $distanceSquared !== 0.0) {
					$origin->getHandler()->handleMovePlayer(MovePlayerPacket::simple($player->getId(), $pkPos, $packet->getPitch(), $packet->getYaw(), $packet->getHeadYaw(), MovePlayerPacket::MODE_NORMAL, false, 0, $packet->getTick()));
				}

				if ($packet->getItemInteractionData() !== null) {
					$data = $packet->getItemInteractionData();
					$origin->getHandler()->handleInventoryTransaction(InventoryTransactionPacket::create($data->getRequestId(), $data->getRequestChangedSlots(), $data->getTransactionData()));
				}
				if ($packet->getBlockActions() !== null) {
					foreach ($packet->getBlockActions() as $blockAction) {
						$actionType = match ($blockAction->getActionType()) {
							PlayerAction::CONTINUE_DESTROY_BLOCK => PlayerAction::START_BREAK,
							PlayerAction::PREDICT_DESTROY_BLOCK => PlayerAction::STOP_BREAK,
							default => $blockAction->getActionType()
						};
						if ($blockAction instanceof PlayerBlockActionWithBlockInfo) {
							$origin->getHandler()->handlePlayerAction(PlayerActionPacket::create($player->getId(), $actionType, $blockAction->getBlockPosition(), $blockAction->getBlockPosition(), $blockAction->getFace())); //TODO: Find out what $resultPosition is ($blockAction->getBlockPosition())
						}
					}
				}

				if ($packet->hasFlag(PlayerAuthInputFlags::START_SPRINTING)) {
					if (!$player->toggleSprint(true)) {
						$player->sendData([$player]);
					}
				}
				if ($packet->hasFlag(PlayerAuthInputFlags::STOP_SPRINTING)) {
					if (!$player->toggleSprint(false)) {
						$player->sendData([$player]);
					}
				}
				if ($packet->hasFlag(PlayerAuthInputFlags::START_SNEAKING)) {
					if (!$player->toggleSneak(true)) {
						$player->sendData([$player]);
					}
				}
				if ($packet->hasFlag(PlayerAuthInputFlags::STOP_SNEAKING)) {
					if (!$player->toggleSneak(false)) {
						$player->sendData([$player]);
					}
				}
			}
		}
	}

	public function onSend(DataPacketSendEvent $event): void
	{
		foreach ($event->getTargets() as $targets) {
			if ($targets instanceof User and $targets->isConnected()) {
				$session = $this->plugin->sessionManager->getSession($targets);

				if (!$session instanceof Session) {
					return;
				}

				foreach ($event->getPackets() as $packet) {
					if ($packet instanceof StartGamePacket) {
						$packet->playerMovementSettings = new PlayerMovementSettings(PlayerMovementType::SERVER_AUTHORITATIVE_V2_REWIND, 20, false);
					}
					$packet = PacketPool::getInstance()->getPacket($packet->getName());
					if ($packet instanceof SetActorMotionPacket) {
						if ($packet->actorRuntimeId === $targets->getId()) {
							$motion = $packet->motion->y;
							NetworkStackLatencyManager::getInstance()->send($targets, function () use ($session, $targets, $motion): void {
								$session->currentMotion = $motion;
							});
						}
					}
				}
			}
		}
	}*/
}