<?php

declare(strict_types=1);

namespace Warro\listeners;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use Warro\Base;
use Warro\Session;
use Warro\User;

class WorldListener implements Listener
{

	/**
	 * @priority HIGHEST
	 */
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		if ($player instanceof User) {
			$session = Base::getInstance()->sessionManager->getSession($player);
			if ($session instanceof Session) {
				if ($session->getCurrentWarp() === Session::WARP_SPAWN and !$player->isCreative()) {
					$event->cancel();
				}
			}
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		if ($player->getName() !== 'Wqrro') {
			$event->cancel();
			return;
		}
		if (!$player->isCreative()) {
			$event->cancel();
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if ($player->getName() !== 'Wqrro') {
			$event->cancel();
			return;
		}
		if (!$player->isCreative()) {
			$event->cancel();
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onBucketFill(PlayerBucketFillEvent $event): void
	{
		$player = $event->getPlayer();
		if ($player->getName() !== 'Wqrro') {
			$event->cancel();
			return;
		}
		if (!$player->isCreative()) {
			$event->cancel();
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onBucketEmpty(PlayerBucketEmptyEvent $event): void
	{
		$player = $event->getPlayer();
		if ($player->getName() !== 'Wqrro') {
			$event->cancel();
			return;
		}
		if (!$player->isCreative()) {
			$event->cancel();
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onCraft(CraftItemEvent $event)
	{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onExplode(ExplosionPrimeEvent $event)
	{
		$event->setBlockBreaking(false);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onLeaveDecay(LeavesDecayEvent $event)
	{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBurn(BlockBurnEvent $event)
	{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onUpdate(BlockUpdateEvent $event)
	{
		$event->cancel();
	}
}