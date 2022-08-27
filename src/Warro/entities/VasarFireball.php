<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\{EntityDamageByChildEntityEvent,
	EntityDamageEvent,
	ProjectileHitBlockEvent,
	ProjectileHitEntityEvent
};
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\BlockForceFieldParticle;
use pocketmine\world\particle\EntityFlameParticle;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\GhastShootSound;
use Warro\Base;
use Warro\Session;
use Warro\User;
use function is_null;

class VasarFireball extends Throwable
{

	public float $height = 0.25;
	public float $width = 0.25;

	protected $gravity = 0.0;

	protected $damage = 8;

	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
	{
		parent::__construct($location, $shootingEntity, $nbt);
		$this->setScale(1);
		if ($shootingEntity instanceof User) {
			$this->setOwningEntity($shootingEntity);
		}
	}

	protected function getInitialSizeInfo(): EntitySizeInfo
	{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string
	{
		return EntityIds::FIREBALL;
	}

	public function canCollideWith(Entity $entity): bool
	{
		$owner = $this->getOwningEntity();
		if ($entity instanceof User and $owner instanceof User and $entity->getId() === $owner->getId()) {
			return false;
		}
		return parent::canCollideWith($entity);
	}

	protected function onHit(ProjectileHitEvent $event): void
	{
		$this->getWorld()->addParticle($this->getPosition()->asVector3(), new HugeExplodeParticle());
		$this->getWorld()->addParticle($this->getPosition()->asVector3()->add(0, 0.7, 0), new LavaParticle());
		$this->getWorld()->addParticle($this->getPosition()->asVector3()->add(0, 0.7, 0), new LavaParticle());
		$this->getWorld()->addParticle($this->getPosition()->asVector3()->add(0, 0.7, 0), new LavaParticle());

		$this->getWorld()->addSound($this->getPosition()->asVector3(), new ExplodeSound());

		$this->isCollided = true;

		$damage = $this->getResultDamage();
		$owner = $this->getOwningEntity();

		if (!is_null($owner) and $damage >= 0) {
			$hKnockBack = 1.235;
			$vKnockBack = 0.745;
			if ($event instanceof ProjectileHitEntityEvent) {
				$entityHit = $event->getEntityHit();
				if (!$entityHit instanceof User) {
					return;
				}

				$entityHit->attack($event = new EntityDamageByChildEntityEvent($owner, $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage));

				if (!$event->isCancelled()) {
					$deltaX = $entityHit->location->x - $this->location->x;
					$deltaZ = $entityHit->location->z - $this->location->z;

					$entityHit->knockBack($deltaX, $deltaZ, $hKnockBack, $vKnockBack);
				}
			} elseif ($event instanceof ProjectileHitBlockEvent) {
				$this->getWorld()->addParticle($this->getPosition()->asVector3(), new BlockBreakParticle($event->getBlockHit()));
				foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(6, 6, 6)) as $nearby) {
					if ($nearby instanceof User and $nearby->isAlive()) {
						$session = Base::getInstance()->sessionManager->getSession($nearby);
						if ($session instanceof Session and !$session->isAfk() and $session->canTakeDamage()) {
							$nearby->attack($event = new EntityDamageByChildEntityEvent($owner, $this, $nearby, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage / 2));

							if (!$event->isCancelled()) {
								$deltaX = $nearby->location->x - $this->location->x;
								$deltaZ = $nearby->location->z - $this->location->z;

								$distance = $nearby->location->distance($this->location);

								$hKnockBack -= ($distance / 15);
								$vKnockBack -= ($distance / 16);

								if ($hKnockBack > 0 and $vKnockBack > 0) {
									$nearby->knockBack($deltaX, $deltaZ, $hKnockBack, $vKnockBack);
								}
							}
						}
					}
				}
			}
		}
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$owner = $this->getOwningEntity();
		if ($this->isCollided or $owner === null or $this->ticksLived >= 20 * 10) {
			$this->flagForDespawn();
		}
		if ($this->ticksLived === 5) {
			$this->getWorld()->addParticle($this->getPosition()->asVector3(), new ExplodeParticle());
			$this->getWorld()->addSound($this->getPosition()->asVector3(), new GhastShootSound());
		}
		if (!is_null($owner) and $this->ticksLived >= 5) {
			$this->motion->x = $owner->getDirectionVector()->x * 6;
			$this->motion->y = $owner->getDirectionVector()->y * 6;
			$this->motion->z = $owner->getDirectionVector()->z * 6;
		}
		return parent::entityBaseTick($tickDiff);
	}
}