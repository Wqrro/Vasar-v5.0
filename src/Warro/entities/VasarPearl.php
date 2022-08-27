<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Server;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;
use Warro\Base;
use Warro\User;

class VasarPearl extends EnderPearl
{

	protected $gravity = 0.065;
	protected $drag = 0.0085;

	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
	{
		parent::__construct($location, $shootingEntity, $nbt);
		$this->setScale(0.6);
	}

	protected function onHit(ProjectileHitEvent $event): void
	{
		$owner = $this->getOwningEntity();
		if ($owner instanceof User) {
			$session = Base::getInstance()->sessionManager->getSession($owner);
			if ($owner->isAlive() and $session->canTakeDamage()) {
				if ($owner->getWorld()->getId() === $this->getWorld()->getId()) {
					$this->getWorld()->addParticle($owner->getPosition(), new EndermanTeleportParticle());
					$this->getWorld()->addSound($owner->getPosition(), new EndermanTeleportSound());

					$vector = $event->getRayTraceResult()->getHitVector();

					//$flag = $session->isPearlAnimation() ? MoveActorAbsolutePacket::FLAG_FORCE_MOVE_LOCAL_ENTITY : MoveActorAbsolutePacket::FLAG_GROUND;
					$owner->getNetworkSession()->sendDataPacket(MoveActorAbsolutePacket::create($owner->getId(), $vector, $owner->getLocation()->getPitch(), $owner->getLocation()->getYaw(), 0, MoveActorAbsolutePacket::FLAG_GROUND), true);

					Server::getInstance()->broadcastPackets($owner->getViewers(), [MoveActorAbsolutePacket::create($owner->getId(), $vector->add(0, $owner->getEyeHeight(), 0), $owner->getLocation()->getPitch(), $owner->getLocation()->getYaw(), 0, MoveActorAbsolutePacket::FLAG_FORCE_MOVE_LOCAL_ENTITY)]);

					$owner->sendPosition($vector->asVector3(), $owner->getLocation()->getYaw(), $owner->location->pitch, MovePlayerPacket::MODE_TELEPORT);
					$owner->setPosition($vector);
					$owner->attack(new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_CUSTOM, 0));

					$this->getWorld()->addParticle($owner->getPosition(), new EndermanTeleportParticle());
					$this->getWorld()->addSound($owner->getPosition(), new EndermanTeleportSound());
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