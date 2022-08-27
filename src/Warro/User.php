<?php

declare(strict_types=1);

namespace Warro;

use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\{EntityDamageByChildEntityEvent, EntityDamageByEntityEvent, EntityDamageEvent};
use pocketmine\form\Form;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;

class User extends Player
{

	private ?float $openForm = null;

	private array $hidden = array();

	//private float $lastAttack;

	public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, Location $spawnLocation, ?CompoundTag $namedtag)
	{
		parent::__construct($server, $session, $playerInfo, $authenticated, $spawnLocation, $namedtag);
		//$this->lastAttack = microtime(true);
		$this->xuid = 'vasar.land';
	}

	public function getHidden(): array
	{
		return $this->hidden;
	}

	public function hideThem(Player $player): void
	{
		$this->hidden[$player->getName()] = time();
		$this->hidePlayer($player);
	}

	public function showThem(Player $player): void
	{
		unset($this->hidden[$player->getName()]);
		$this->showPlayer($player);
	}

	public function clearHidden(): void
	{
		foreach ($this->hidden as $key => $value) {
			if (is_string($key) and is_int($value)) {
				$player = Server::getInstance()->getPlayerExact($key);
				if ($player instanceof User) {
					$this->showThem($player);
				}
			}
		}
		$this->hidden = [];
	}

	/*public function setLastAttack(float $float): void
	{
		$this->lastAttack = $float;
	}

	public function getLastAttack(): float
	{
		return $this->lastAttack;
	}*/

	public function canBeAttacked(): bool
	{
		return /*$this->lastAttack <= microtime(true)*/ $this->attackTime === 0;
	}

	public function sendPosition(Vector3 $pos, ?float $yaw = null, ?float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL): void
	{
		parent::sendPosition($pos, $yaw, $pitch, $mode);
	}

	public function canBeCollidedWith(): bool
	{
		$session = Base::getInstance()->sessionManager->getSession($this);

		if (is_null($session)) {
			return false;
		}

		if (!$session->canTakeDamage()) {
			return false;
		}
		return parent::canBeCollidedWith();
	}

	public function sendForm(Form $form): void
	{
		if (is_null($this->openForm) or (is_float($this->openForm) and $this->openForm <= microtime(true))) {
			$this->openForm = microtime(true) + 0.25;
			parent::sendForm($form);
		}
	}

	public function useHeldItem(): bool
	{
		$item = $this->inventory->getItemInHand();

		if ($this->hasItemCooldown($item)) {
			$this->sendMessage(TextFormat::RED . 'Your ' . $item->getVanillaName() . TextFormat::RED . ' is on cooldown for ' . abs(($this->server->getTick() - $this->getItemCooldownExpiry($item)) / 20) . ' seconds.');
			return false;
		}

		return parent::useHeldItem();
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

		return $newVerticalVelocity;
	}

	public function applyDamageModifiers(EntityDamageEvent $source): void
	{
		if (!is_null($this->lastDamageCause) and !$this->canBeAttacked()) {
			if ($this->lastDamageCause->getBaseDamage() >= $source->getBaseDamage()) {
				$source->cancel();
			}
			$source->setModifier(-$this->lastDamageCause->getBaseDamage(), EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN);
		}
		if ($source->canBeReducedByArmor()) {
			$source->setModifier(-$source->getFinalDamage() * $this->getArmorPoints() * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
		}

		$cause = $source->getCause();
		if ((!is_null($resistance = $this->effectManager->get(VanillaEffects::RESISTANCE()))) and $cause !== EntityDamageEvent::CAUSE_VOID and $cause !== EntityDamageEvent::CAUSE_SUICIDE) {
			$source->setModifier(-$source->getFinalDamage() * min(1, 0.2 * $resistance->getEffectLevel()), EntityDamageEvent::MODIFIER_RESISTANCE);
		}

		$totalEpf = 0;
		foreach ($this->armorInventory->getContents() as $item) {
			if ($item instanceof Armor) {
				$totalEpf += $item->getEnchantmentProtectionFactor($source);
			}
		}
		$source->setModifier(-$source->getFinalDamage() * min(ceil(min($totalEpf, 25) * (mt_rand(50, 100) / 100)), 20) * 0.04, EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);

		$source->setModifier(-min($this->getAbsorption(), $source->getFinalDamage()), EntityDamageEvent::MODIFIER_ABSORPTION);
	}

	public function attack(EntityDamageEvent $source): void
	{
		if ($this->noDamageTicks > 0) {
			$source->cancel();
			return;
		}
		if (!$this->canBeAttacked() and !$source instanceof EntityDamageByChildEntityEvent) {
			$source->cancel();
			return;
		}

		if ($this->effectManager->has(VanillaEffects::FIRE_RESISTANCE()) and ($source->getCause() === EntityDamageEvent::CAUSE_FIRE or $source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK or $source->getCause() === EntityDamageEvent::CAUSE_LAVA)) {
			$source->cancel();
		}

		$this->applyDamageModifiers($source);

		if ($source instanceof EntityDamageByEntityEvent and ($source->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION or $source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION)) {
			$base = $source->getKnockBack();
			$source->setKnockBack($base - min($base, $base * $this->getHighestArmorEnchantmentLevel(VanillaEnchantments::BLAST_PROTECTION()) * 0.15));
		}

		if (!$this->isAlive()) {
			return;
		}

		if ($this->isCreative() and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE) {
			$source->cancel();
		} elseif ($this->getAllowFlight() and $source->getCause() === EntityDamageEvent::CAUSE_FALL) {
			$source->cancel();
		}

		$cause = $source->getCause();

		Entity::attack($source);

		if ($source->isCancelled()) {
			return;
		}

		$this->applyPostDamageEffects($source);
		$this->doHitAnimation();

		if ($source instanceof EntityDamageByEntityEvent) {
			$damager = $source->getDamager();

			if (!is_null($damager)) {
				$deltaX = $this->location->x - $damager->location->x;
				$deltaZ = $this->location->z - $damager->location->z;

				$this->knockBack($deltaX, $deltaZ, $source->getKnockBack());
			}
		} elseif ($source instanceof EntityDamageByChildEntityEvent) {
			$damager = $source->getChild();

			if (!is_null($damager)) {
				$motion = $damager->getMotion();

				$this->knockBack($motion->x, $motion->z, $source->getKnockBack());
			}
		}

		$session = Base::getInstance()->sessionManager->getSession($this);

		if (is_null($session)) {
			return;
		}

		if ($cause === EntityDamageEvent::CAUSE_PROJECTILE) {
			//$this->setLastAttack(microtime(true) + 0.270);
			$this->attackTime = 3;
		} else {
			//$this->setLastAttack(microtime(true) + $session->attackCooldown);
			$this->attackTime = 10;
		}
	}

	public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void // using $verticalLimit as vertical knockback!!!
	{
		$session = Base::getInstance()->sessionManager->getSession($this);

		if (is_null($session)) {
			return;
		}

		[$horizontal, $vertical] = $force === 0.4 ? [$session->hKnockBack, $session->vKnockBack] : [$force, $verticalLimit];

		if ($session->hasLastDamagePosition()) {
			$position = $session->getLastDamagePosition();
			if ($position instanceof Vector3) {
				$dist = $this->getPosition()->getY() - $position->getY();

				$addDist = $dist + 0.5;

				if (!$this->isOnGround()) {
					$bool = $addDist > $session->maxDistanceKnockBack;

					$diff = $bool ? 0.026 * 0.75 : 0.026;

					if ($addDist > $session->maxDistanceKnockBack) {
						$vertical -= $dist * $diff;
					}
				}
			}
		}

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
			$motion->x += $x * $f * $horizontal;
			$motion->y += $vertical;
			$motion->z += $z * $f * $horizontal;

			if ($motion->y > $vertical) {
				$motion->y = $vertical;
			}

			$this->setMotion($motion);
		}
	}
}