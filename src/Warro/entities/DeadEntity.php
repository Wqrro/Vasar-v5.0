<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\world\particle\EntityFlameParticle;
use pocketmine\world\sound\EntityLongFallSound;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;

class DeadEntity extends Human
{

	private int $life = 0;

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$this->life++;
		if ($this->life >= 25) {
			$this->flagForDespawn();
			$this->getWorld()->addParticle($this->getLocation(), new EntityFlameParticle());
			return true;
		}
		return parent::entityBaseTick($tickDiff);
	}

	public function canBeCollidedWith(): bool
	{
		return false;
	}

	public function canBeMovedByCurrents(): bool
	{
		return false;
	}

	public function canBreathe(): bool
	{
		return false;
	}

	public function canClimbWalls(): bool
	{
		return false;
	}

	public function canClimb(): bool
	{
		return false;
	}

	public function canCollideWith(Entity $entity): bool
	{
		return false;
	}

	public function attack(EntityDamageEvent $source): void
	{
	}

	public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void
	{
	}

	public function doAirSupplyTick(int $tickDiff): bool
	{
		return true;
	}

	public function doOnFireTick(int $tickDiff = 1): bool
	{
		return false;
	}

	protected function onHitGround(): ?float
	{
		$fallBlockPos = $this->location->floor();
		$fallBlock = $this->getWorld()->getBlock($fallBlockPos);
		if (count($fallBlock->getCollisionBoxes()) === 0) {
			$fallBlockPos = $fallBlockPos->down();
			$fallBlock = $this->getWorld()->getBlock($fallBlockPos);
		}
		$newVerticalVelocity = $fallBlock->onEntityLand($this);

		$damage = $this->calculateFallDamage($this->fallDistance);
		if ($damage > 0) {
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FALL, $damage);
			$this->attack($ev);
		}

		$this->broadcastSound(new EntityLongFallSound($this));
		return $newVerticalVelocity;
	}

	public function shove(float $x, float $z, float $base = 0.4): void
	{
		$f = sqrt($x * $x + $z * $z);
		if ($f <= 0) {
			return;
		}
		if (mt_rand() / mt_getrandmax() > $this->knockbackResistanceAttr->getValue()) {
			$f = 1 / $f;

			$motion = clone $this->motion;

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += 0.275;
			$motion->z += $z * $f * $base;

			$this->setMotion($motion);
		}
	}
}