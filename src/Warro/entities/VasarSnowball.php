<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use Warro\Base;
use Warro\User;

class VasarSnowball extends Throwable
{

	public float $height = 0.25;
	public float $width = 0.25;

	protected $gravity = 0.03;

	protected $damage = 0;

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
		return EntityIds::SNOWBALL;
	}

	public function canCollideWith(Entity $entity): bool
	{
		$owner = $this->getOwningEntity();
		if ($entity instanceof User and $owner instanceof User and $entity->getId() === $owner->getId()) {
			return false;
		}
		return parent::canCollideWith($entity);
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
	{
		$owner = $this->getOwningEntity();
		if ($owner instanceof Player) {
			Base::getInstance()->utils->doSoundPacket($owner, 'note.pling');
		}
		parent::onHitEntity($entityHit, $hitResult);
	}

	public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
	{
		$owner = $this->getOwningEntity();
		if ($owner instanceof Player) {
			Base::getInstance()->utils->doSoundPacket($owner, 'note.bassattack');
		}
		parent::onHitBlock($blockHit, $hitResult);
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$owner = $this->getOwningEntity();
		if ($this->isCollided or $owner === null or $this->ticksLived >= 20 * 10) {
			$this->flagForDespawn();
		}
		return parent::entityBaseTick($tickDiff);
	}
}