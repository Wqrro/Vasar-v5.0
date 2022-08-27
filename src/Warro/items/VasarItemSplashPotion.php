<?php

declare(strict_types=1);

namespace Warro\items;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemUseResult;
use pocketmine\item\PotionType;
use pocketmine\item\SplashPotion;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use Warro\Base;
use Warro\entities\VasarPotion;

class VasarItemSplashPotion extends SplashPotion
{

	private PotionType $potionType;

	public function __construct(ItemIdentifier $identifier, string $name, PotionType $potionType)
	{
		parent::__construct($identifier, $name, $potionType);
		$this->potionType = $potionType;
	}

	public function getThrowForce(): float
	{
		if (Base::getInstance()->utils->isDebuffPotion($this->potionType)) {
			return 0.565;
		}
		return 0.5;
	}

	protected function createEntity(Location $location, Player $thrower): Throwable
	{
		return new VasarPotion($location, $thrower, $this->potionType);
	}

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult
	{
		return parent::onClickAir($player, $directionVector);
	}
}