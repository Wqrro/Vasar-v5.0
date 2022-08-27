<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\{EntityDamageByChildEntityEvent,
	EntityDamageByEntityEvent,
	EntityDamageEvent,
	ProjectileHitBlockEvent,
	ProjectileHitEntityEvent
};
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\particle\EntityFlameParticle;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\ExplodeSound;
use Warro\Base;
use Warro\Session;
use Warro\User;
use function is_null;

class BattlefieldFireball extends Throwable
{

	public float $height = 0.25;
	public float $width = 0.25;

	protected $gravity = 0.035;
	protected $drag = 0.0004;

	protected $damage = 14;

	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
	{
		parent::__construct($location, $shootingEntity, $nbt);
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
		$vec3 = $this->getPosition()->asVector3();
		$this->getWorld()->addParticle($vec3, new HugeExplodeParticle());
		$this->getWorld()->addSound($vec3, new ExplodeSound());

		$this->isCollided = true;

		$damage = $this->getResultDamage();
		$owner = $this->getOwningEntity();

		if (!is_null($owner)) {
			$hKnockBack = 1.335;
			$vKnockBack = 0.845;
			if ($event instanceof ProjectileHitEntityEvent) {
				if ($damage > 0) {
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
				}
			} elseif ($event instanceof ProjectileHitBlockEvent) {
				if ($damage >= 0) {
					if ($damage >= 0) {
						foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(5.42, 5.42, 5.42)) as $nearby) {
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
		}
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$owner = $this->getOwningEntity();
		if ($this->isCollided or $owner === null or $this->ticksLived >= 20 * 15) {
			$this->flagForDespawn();
		}
		return parent::entityBaseTick($tickDiff);
	}

	public function attack(EntityDamageEvent $source): void
	{
		if ($source instanceof EntityDamageByEntityEvent and $source->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			$damager = $source->getDamager();
			if ($damager instanceof User) {
				$owner = $this->getOwningEntity();
				if ($owner instanceof Player) {
					if ($damager->getName() === $owner->getName()) {
						return;
					}
					$vec3 = $this->getPosition()->asVector3();
					$this->getWorld()->addParticle($vec3, new EntityFlameParticle());
					Base::getInstance()->utils->doSoundPacket($this, 'item.trident.throw', 1, 1, true);
					$this->flagForDespawn();

					$owner->sendMessage(TextFormat::RED . $damager->getDisplayName() . ' destroyed your Fireball!');
				}
			}
		}
	}
}