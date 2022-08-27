<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\block\Block;
use pocketmine\color\Color;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\PotionType;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\sound\PotionSplashSound;
use Warro\Base;
use Warro\managers\SessionManager;
use Warro\User;

class VasarPotion extends SplashPotion
{

	public const MAX_HIT = 1.0515;
	public const MAX_MISS = 0.9215;

	protected $gravity = 0.06;
	protected $drag = 0.0025;

	private bool $hasEffects = true;

	private array $colors = array();

	public function __construct(Location $location, ?Entity $shootingEntity, PotionType $potionType, ?CompoundTag $nbt = null)
	{
		parent::__construct($location, $shootingEntity, $potionType, $nbt);
		$this->setScale(0.6);
		if ($shootingEntity instanceof User) {
			$session = Base::getInstance()->sessionManager->getSession($shootingEntity);
			if ($session->getSplashColorSelected() === SessionManager::SPLASH_COLOR_SELECTED) {
				$effects = $this->getPotionEffects();
				if (count($effects) === 0) {
					$this->colors = [new Color(0x38, 0x5d, 0xc6)];
					$this->hasEffects = false;
				} else {
					foreach ($effects as $effect) {
						$level = $effect->getEffectLevel();
						for ($j = 0; $j < $level; ++$j) {
							$this->colors[] = $effect->getColor();
						}
					}
				}
			} else {
				$this->colors = [Base::getInstance()->utils->getColor($session->getSplashColorSelected())];
			}
		}
	}

	protected function onHit(ProjectileHitEvent $event): void
	{
		$owner = $this->getOwningEntity();
		if (!$owner instanceof User) {
			$this->flagForDespawn();
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($owner);

		$packet = new LevelEventPacket();
		$packet->eventId = LevelEvent::PARTICLE_SPLASH;
		$packet->eventData = Color::mix(...$this->colors)->toARGB();
		$packet->position = $this->getPosition()->asVector3();

		foreach ($owner->getViewers() as $viewer) {
			if ($viewer instanceof User) {
				$viewerSession = Base::getInstance()->sessionManager->getSession($owner);
				if ($viewerSession->isParticleSplashes()) {
					$viewer->getNetworkSession()->sendDataPacket($packet);
				}
			}
		}
		if ($session->isParticleSplashes()) {
			$owner->getNetworkSession()->sendDataPacket($packet);
		}

		$this->broadcastSound(new PotionSplashSound());

		if ($this->hasEffects) {
			if ($event instanceof ProjectileHitEntityEvent) {
				$entityHit = $event->getEntityHit();
				if ($entityHit instanceof User) {
					$entityHit->heal(new EntityRegainHealthEvent($entityHit, 1.45, EntityRegainHealthEvent::CAUSE_CUSTOM));
				}
			}
			foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expand(1.85, 2.65, 1.85)) as $nearby) {
				if ($nearby instanceof User) {
					$nearbySession = Base::getInstance()->sessionManager->getSession($nearby);
					if ($nearby->isAlive() and !$nearby->isImmobile() and $nearbySession->canTakeDamage() and !$nearbySession->isAfk()) {
						foreach ($this->getPotionEffects() as $effect) {
							if (!$effect->getType() instanceof InstantEffect) {
								$newDuration = (int)round($effect->getDuration() * 0.75 * self::MAX_HIT);
								if ($newDuration < 20) {
									continue;
								}
								$effect->setDuration($newDuration);
								$nearby->getEffects()->add($effect);
							} else {
								$effect->getType()->applyEffect($nearby, $effect, self::MAX_HIT, $this);
							}
						}
					}
				}
			}
		}
	}

	public function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end): ?RayTraceResult
	{
		if ($block->getId() === 95) {
			return null;
		}
		return parent::calculateInterceptWithBlock($block, $start, $end);
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		if ($this->isCollided) {
			$this->flagForDespawn();
		}
		return parent::entityBaseTick($tickDiff);
	}
}