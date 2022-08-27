<?php

declare(strict_types=1);

namespace Warro;

use FilesystemIterator;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\{EntityDataHelper, EntityFactory, Location};
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use Warro\commands\{Alias,
	Ban,
	Chat,
	Disguise,
	Fly,
	Kill,
	Message,
	Mute,
	Nick,
	Ping,
	Rank,
	Rekit,
	Reply,
	Rules,
	Settings,
	Spawn,
	Spectate,
	Staff,
	Stats,
	Stp,
	Vanish,
	Who
};
use Warro\entities\{DeadEntity, BattlefieldFireball, VasarItemEntity, VasarPearl, VasarPotion, VasarSnowball};
use Warro\games\FreeForAll;
use Warro\items\VasarItemEnderPearl;
use Warro\items\VasarItemFireball;
use Warro\items\VasarItemSnowBall;
use Warro\items\VasarItemSplashPotion;
use Warro\listeners\{PlayerListener, WorldListener};
use Warro\managers\CpsManager;
use Warro\managers\RankManager;
use Warro\managers\ScoreboardManager;
use Warro\managers\SessionManager;
use Warro\tasks\{CombatTask, EnderPearlTask, NoteTask};
use Warro\store\Broadcast;
use Warro\store\Plus;
use Warro\store\Unban;
use Warro\store\Unblacklist;
use Warro\utilities\SkinAdapterPersona;

class Base extends PluginBase
{

	private static Base $instance;

	public ?DataConnector $network = null;
	public ?DataConnector $database = null;

	public ?Utils $utils = null;
	public ?SUtils $sutils = null;
	public ?Forms $forms = null;
	public ?SessionManager $sessionManager = null;
	public ?RankManager $rankManager = null;
	public ?CpsManager $cpsManager = null;
	public ?ScoreboardManager $scoreboardManager = null;

	private int $registeredPlayers = 0;

	public static function getInstance(): Base
	{
		return self::$instance;
	}

	public function onLoad(): void
	{
		self::$instance = $this;

		$this->registeredPlayers = iterator_count(new FilesystemIterator($this->getServer()->getDataPath() . 'players', FilesystemIterator::SKIP_DOTS));

		@mkdir($this->getDataFolder() . 'aliases/');
		@mkdir($this->getDataFolder() . 'players/');

		$this->doOverride();

		$this->getServer()->getWorldManager()->loadWorld(Variables::SPAWN, true);
		$this->getServer()->getWorldManager()->loadWorld(Variables::NODEBUFF_FFA_ARENA_TETRIS, true);
		$this->getServer()->getWorldManager()->loadWorld(Variables::NODEBUFF_FFA_ARENA_PLAINS, true);
		$this->getServer()->getWorldManager()->loadWorld(Variables::SUMO_FFA_ARENA, true);
		$this->getServer()->getWorldManager()->loadWorld(Variables::HIVE_FFA_ARENA, true);
		$this->getServer()->getWorldManager()->loadWorld(Variables::BATTLEFIELD_FFA_ARENA, true);

		$this->getServer()->getNetwork()->setName(TextFormat::DARK_AQUA . Variables::NAME . TextFormat::GRAY);

		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('kill'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('me'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('defaultgamemode'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('difficulty'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('spawnpoint'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('setworldspawn'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('title'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('seed'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('particle'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('clear'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('tell'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('ban'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('pardon'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('list'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('banlist'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('ban-ip'));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('pardon-ip'));

		$this->network = libasynql::create($this, ['type' => 'mysql', 'mysql' => ['host' => 'Target Machine IPv4 or local, 127.0.0.1', 'username' => 'YourUsername', 'password' => 'YourPassword', 'schema' => 'YourDatabaseName']], ['mysql' => 'mysql.sql']);
		$this->network->executeGeneric('vasar.init.mutes', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->network->waitAll();
		$this->network->executeGeneric('vasar.init.bans', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->network->waitAll();
		$this->network->executeGeneric('vasar.init.blacklists', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->network->waitAll();
		$this->network->executeGeneric('vasar.init.ranks', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->network->waitAll();
		$this->network->executeGeneric('vasar.init.voters', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->network->waitAll();

		$this->database = libasynql::create($this, ['type' => 'sqlite', 'sqlite' => ['file' => $this->getDataFolder() . 'sqlite.db']], ['sqlite' => 'sqlite.sql']);
		$this->database->executeGeneric('vasar.init.stats', [], null, function (SqlError $error_) use (&$error) {
			$error = $error_;
		});
		$this->database->waitAll();
	}

	public function onEnable(): void
	{
		if (!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($this);
		}

		$this->utils = new Utils();
		$this->sutils = new SUtils();
		$this->forms = new Forms();
		$this->sessionManager = new SessionManager($this);
		$this->rankManager = new RankManager();
		$this->cpsManager = new CpsManager();
		$this->scoreboardManager = new ScoreboardManager($this);

		SkinAdapterSingleton::set(new SkinAdapterPersona($this));

		$this->getServer()->getPluginManager()->registerEvents(new Jarvis($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new WorldListener(), $this);

		$this->getScheduler()->scheduleDelayedRepeatingTask(new NoteTask($this), 60, 20 * 60 * 5);
		$this->getScheduler()->scheduleRepeatingTask(new EnderPearlTask($this), 1);
		$this->getScheduler()->scheduleRepeatingTask(new CombatTask($this), 20);

		foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
			$world->setAutoSave(false);
			$world->setTime(World::TIME_DAY);
			$world->stopTime();
			foreach ($world->getEntities() as $entity) {
				$entity->flagForDespawn();
			}
			if ($world->getFolderName() === Variables::SPAWN) {
				$this->utils->spawnLocation = new Location(-6.5, 57, 53.5, $world, 180, 0);
			} elseif ($world->getFolderName() === Variables::NODEBUFF_FFA_ARENA_TETRIS) {
				$arena = new FreeForAll($this, 'NoDebuff Tetris', Variables::NODEBUFF_FFA_ARENA_TETRIS, 25, 'textures/items/potion_bottle_splash_heal', ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 22));
				$this->utils->freeForAllArenas[$arena->getName()] = $arena;

				$this->utils->nodebuffFreeForAllTetris = new Location(5073.5, 51, 5182.5, $world, 0, 0);
			} elseif ($world->getFolderName() === Variables::NODEBUFF_FFA_ARENA_PLAINS) {
				$arena = new FreeForAll($this, 'NoDebuff Plains', Variables::NODEBUFF_FFA_ARENA_PLAINS, 25, 'textures/items/potion_bottle_splash_poison', ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 25));
				$this->utils->freeForAllArenas[$arena->getName()] = $arena;

				$this->utils->nodebuffFreeForAllPlains = new Location(100.5, 51, 100.5, $world, 0, 0);
			} elseif ($world->getFolderName() === Variables::SUMO_FFA_ARENA) {
				$arena = new FreeForAll($this, 'Sumo', Variables::SUMO_FFA_ARENA, 20, 'textures/items/lead', ItemFactory::getInstance()->get(ItemIds::LEAD));
				$this->utils->freeForAllArenas[$arena->getName()] = $arena;

				$this->utils->sumoFreeForAll = new Location(202.5, 171, 222.5, $world, 0, 0);
			} elseif ($world->getFolderName() === Variables::HIVE_FFA_ARENA) {
				$arena = new FreeForAll($this, 'Hive', Variables::HIVE_FFA_ARENA, 15, 'textures/items/heartofthesea_closed', ItemFactory::getInstance()->get(ItemIds::HEART_OF_THE_SEA));
				$this->utils->freeForAllArenas[$arena->getName()] = $arena;

				$this->utils->hiveFreeForAll = new Location(5000.5, 51, 5000.5, $world, 0, 0);
			} elseif ($world->getFolderName() === Variables::BATTLEFIELD_FFA_ARENA) {
				$arena = new FreeForAll($this, 'Battlefield', Variables::BATTLEFIELD_FFA_ARENA, 20, 'textures/items/fireball', ItemFactory::getInstance()->get(ItemIds::FIREBALL));
				$this->utils->freeForAllArenas[$arena->getName()] = $arena;

				$this->utils->battlefieldFreeForAll = new Location(100.5, 51, 100.5, $world, 0, 0);
			}
		}

		$this->getServer()->getCommandMap()->registerAll(Variables::NAME, [
			new Rules($this),
			new Stp($this),
			new Mute($this),
			new Ban($this),
			new Disguise($this),
			new Vanish($this),
			new Reply($this),
			new Message($this),
			new Stats($this),
			new Settings($this),
			new Chat($this),
			new Fly($this),
			new Alias($this),
			new Spectate($this),
			new Staff($this),
			new Who($this),
			new Nick($this),
			new Ping($this),
			new Nick($this),
			new Rank($this),
			new Kill($this),
			new Rekit($this),
			new Spawn($this),

			//Store commands
			new Plus($this),
			new Unban($this),
			new Unblacklist($this),
			new Broadcast(),
		]);

		$this->network->executeSelect('vasar.get.bans', [], function ($rows) {
			foreach ($rows as $row) {
				if (is_array($row)) {
					if (isset($row['player'])) {
						$this->sutils->bans[strtolower($row['player'])] = $row;
					}
				}
			}
		});

		$this->network->executeSelect('vasar.get.mutes', [], function ($rows) {
			foreach ($rows as $row) {
				if (is_array($row)) {
					if (isset($row['player'])) {
						$this->sutils->mutes[strtolower($row['player'])] = $row;
					}
				}
			}
		});

		$this->getLogger()->notice(TextFormat::MINECOIN_GOLD . 'Created by Warro#7777');
	}

	public function onDisable(): void
	{
		foreach ($this->getServer()->getOnlinePlayers() as $players) {
			$players->kick(TextFormat::AQUA . 'Vasar is restarting, feel free to re-connect.');
		}

		foreach ($this->getServer()->getWorldManager()->getWorlds() as $level) {
			foreach ($level->getEntities() as $entity) {
				if (!$entity instanceof User) {
					$entity->close();
				}
			}
		}
	}

	private function doOverride(): void
	{
		ItemFactory::getInstance()->register(new VasarItemEnderPearl(new ItemIdentifier(ItemIds::ENDER_PEARL, 0), 'Ender Pearl'), true);

		ItemFactory::getInstance()->register(new VasarItemFireball(new ItemIdentifier(ItemIds::FIREBALL, 0), 'Fireball'), true);

		ItemFactory::getInstance()->register(new VasarItemSnowBall(new ItemIdentifier(ItemIds::SNOWBALL, 0), 'Snowball'), true);

		foreach (PotionType::getAll() as $type) {
			$typeId = PotionTypeIdMap::getInstance()->toId($type);
			ItemFactory::getInstance()->register(new VasarItemSplashPotion(new ItemIdentifier(ItemIds::SPLASH_POTION, $typeId), $type->getDisplayName() . ' Splash Potion', $type), true);
		}

		EntityFactory::getInstance()->register(VasarPearl::class, function (World $world, CompoundTag $nbt): VasarPearl {
			return new VasarPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl'], EntityLegacyIds::ENDER_PEARL);

		EntityFactory::getInstance()->register(VasarPotion::class, function (World $world, CompoundTag $nbt): VasarPotion {
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort('PotionId', PotionTypeIds::WATER));
			if ($potionType === null) {
				throw new SavedDataLoadingException();
			}
			return new VasarPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);

		}, ['ThrownPotion', 'minecraft:potion', 'thrownpotion'], EntityLegacyIds::SPLASH_POTION);

		EntityFactory::getInstance()->register(VasarItemEntity::class, function (World $world, CompoundTag $nbt): VasarItemEntity {
			$itemTag = $nbt->getCompoundTag('Item');
			if ($itemTag !== null) {
				throw new SavedDataLoadingException();
			}

			$item = Item::nbtDeserialize($itemTag);
			if ($item->isNull()) {
				throw new SavedDataLoadingException();
			}
			return new VasarItemEntity(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
		}, ['Item', 'minecraft:item'], EntityLegacyIds::ITEM);

		EntityFactory::getInstance()->register(BattlefieldFireball::class, function (World $world, CompoundTag $nbt): BattlefieldFireball {
			return new BattlefieldFireball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['VasarFireball', 'vasar:fire_ball'], EntityLegacyIds::FIREBALL);

		EntityFactory::getInstance()->register(VasarSnowball::class, function (World $world, CompoundTag $nbt): VasarSnowball {
			return new VasarSnowball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['VasarSnowball', 'vasar:snow_ball'], EntityLegacyIds::SNOWBALL);

		EntityFactory::getInstance()->register(DeadEntity::class, function (World $world, CompoundTag $nbt): DeadEntity {
			return new DeadEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['DeadEntity', 'vasar:dead_entity']);
	}

	public function getFile(): string
	{
		return parent::getFile();
	}

	public function addRegisteredPlayer(Player $player): void
	{
		$this->registeredPlayers += 1;
		Server::getInstance()->broadcastMessage(TextFormat::YELLOW . 'Welcome ' . TextFormat::ITALIC . TextFormat::GOLD . $player->getName() . TextFormat::RESET . TextFormat::YELLOW . ' as our ' . TextFormat::MINECOIN_GOLD . $this->registeredPlayers . 'th ' . TextFormat::YELLOW . 'player.');
		$player->sendMessage(TextFormat::DARK_GREEN . 'Please consider reading Vasar\'s in-game rules, you can view them at any time using /rules.');
	}
}