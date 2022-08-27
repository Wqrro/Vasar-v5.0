<?php

declare(strict_types=1);

namespace Warro\items;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\{ProjectileLaunchEvent};
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;
use Warro\entities\BattlefieldFireball;

class VasarItemFireball extends ProjectileItem
{

	public function __construct(ItemIdentifier $identifier, string $name)
	{
		parent::__construct($identifier, $name);
	}

	public function getMaxStackSize(): int
	{
		return 16;
	}

	public function getThrowForce(): float
	{
		return 1.935;
	}

	public function getCooldownTicks(): int
	{
		return 20 * 10;
	}

	protected function createEntity(Location $location, Player $thrower): Throwable
	{
		return new BattlefieldFireball($location, $thrower); // VasarFireball
	}

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult
	{
		$position = $player->getPosition();
		if ($player->getInventory()->getItemInHand()->getId() === ItemIds::FIREBALL and !$player->isCreative()) {
			$count = $player->getInventory()->getItemInHand()->getCount();
			$newItem = $player->getInventory()->getItemInHand()->setCount($count - 1);
			$player->getInventory()->setItemInHand($newItem);
		}
		$projectile = $this->createEntity(Location::fromObject($player->getEyePos(), $player->getWorld()), $player);
		$projectile->setMotion($directionVector->multiply($this->getThrowForce()));

		$event = new ProjectileLaunchEvent($projectile);
		$event->call();
		if ($event->isCancelled()) {
			$projectile->flagForDespawn();
			return ItemUseResult::FAIL();
		}

		$projectile->spawnToAll();
		$player->getWorld()->addSound($position->asVector3(), new ThrowSound());

		return ItemUseResult::SUCCESS();
	}
}