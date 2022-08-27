<?php

declare(strict_types=1);

namespace Warro\items;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Snowball as ItemSnowball;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;
use Warro\Base;
use Warro\entities\VasarSnowball;
use Warro\Session;

class VasarItemSnowBall extends ItemSnowball
{

	public function getThrowForce(): float
	{
		return 1.85;
	}

	public function getCooldownTicks(): int
	{
		return 20 * 10;
	}

	protected function createEntity(Location $location, Player $thrower): Throwable
	{
		return new VasarSnowball($location, $thrower);
	}

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		if (!$session instanceof Session) {
			return ItemUseResult::FAIL();
		}

		$location = $player->getLocation();

		$projectile = $this->createEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
		$projectile->setMotion($directionVector->multiply($this->getThrowForce()));

		$projectileEv = new ProjectileLaunchEvent($projectile);
		$projectileEv->call();
		if ($projectileEv->isCancelled()) {
			$projectile->flagForDespawn();
			return ItemUseResult::FAIL();
		}

		$projectile->spawnToAll();

		$location->getWorld()->addSound($location, new ThrowSound());

		if ($session->getCurrentWarp() !== Session::WARP_HIVE) {
			$this->pop();
		}

		return ItemUseResult::SUCCESS();
	}
}