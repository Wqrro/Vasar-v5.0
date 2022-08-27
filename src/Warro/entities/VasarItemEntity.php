<?php

declare(strict_types=1);

namespace Warro\entities;

use pocketmine\entity\object\ItemEntity;

class VasarItemEntity extends ItemEntity
{

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		if ($this->ticksLived >= 20 * 60) {
			$this->flagForDespawn();
		}
		return parent::entityBaseTick($tickDiff);
	}
}