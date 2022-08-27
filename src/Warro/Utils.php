<?php

declare(strict_types=1);

namespace Warro;

use Exception;
use JsonException;
use pocketmine\color\Color;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\{ItemFactory, ItemIds, PotionType};
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\TotemUseSound;
use pocketmine\world\World;
use Warro\games\clash\Clash;
use Warro\games\FreeForAll;
use Warro\managers\RankManager;

class Utils
{

	public const IMAGE_TYPE_SKIN = 0;
	public const IMAGE_TYPE_CAPE = 1;

	private array $illegalWords;
	private array $commonPlayers;

	public Skin $vasarSkin;

	public Vector3 $battlefieldStronghold;

	public array $disguises = ['Space', 'JumpingRat193847', 'TurdBiscuit88716', 'FartingChair18902', 'AppleAndroid10833',
		'DisturbedFrog78592', 'Alchemist01873454', 'SurprisedFox35589', 'WillTheWise', 'InnocentMan1349', 'Eldo12Amende',
		'Perukeger955', 'HypoblastAstrut', 'Naiadswann79', 'FuzzymetrSkink', 'KaboomEsker', 'ConchrilPrest', 'Iglookers31',
		'Pilosetor2005', 'PeriwArefy', 'Taxislaay78', 'Ipitihan96zzz', 'Dewlapli200105', 'MaxfielDewlap', 'PennyweigCrotal',
		'NoobCartel', 'SinkingCounter3477', 'RunningChairt1033', 'Comoseyman510', 'Creatic', 'Gelation', 'DreamyKicker9247',
		'LousyIndri', 'UpsetCuscus', 'MeanBear', 'NiceSheep', 'Jacko72Equine', 'BozblonSeven', 'FtpsagrrenDhole', 'FtpsagrrenDhole',
		'MysteryshadGulix', 'Labileke599', 'Sylvante1996', 'Canophilia', 'Irreligion', 'GunmindFizgig', 'CpsIsMax', 'rqndom', 'xTeqqq',
		'noJumper', 'YABBA', 'Zurna', 'zelatrix', 'Yappp', 'rexeeet', 'Anatomy', 'attxmpt', 'Tuwar', 'meenum', 'vartis', 'xQwwrooo', 'ZeqX',
		'sprintToggled', 'nicky99', 'NOX_', 'whyquickdrop', 'Trapzies', 'ghxsty', 'LuckyXTapz', 'obeseGamerGirl', 'UnknownXzzz',
		'zAnthonyyy', 'Rustic', 'Vatitelc', 'StudSport', 'Keepuphulk8181', 'LittleComfy', 'Decdarle', 'Vzeem', 'yurra', 'BASIC x VIBES',
		'Evacor', 'hutteric', 'BiggerCobra1181', 'Lextech817717', 'Chnixxor', 'AloneShun', 'AddictedToYou', 'noPqt', 'REYESOOKIE',
		'Asaurus Rex', 'Popperrr', 'xDqpe', 'Regrexxx', '22Jump', 'NotCqnadian', 'egirllove', 'ShaniquaLOL', 'dannydaniels', 'GamingNut69',
		'im disguised', 'udkA77161', 'GangGangGg', 'CoolKid888', 'AcornChaser78109', 'anon171717', 'AnonymousYT', 'Sintress Balline',
		'Daviecrusha', 'HeatedBot46', 'CobraKiller2828', 'KingPVPYT', 'TempestG', 'ThePVPGod', 'McProGangYT', 'ImHqcking', 'undercoverbot',
		'reswoownss199q', 'diego91881', 'CindyPlayz', 'HeyItzMe', 'iTzSkittlesMC', 'NOHACKJUSTPRO', 'Bigumslol', 'Skilumsszz', 'SuperGamer756',
		'ProPVPer2k20', 'N0S3_P1CK3R84', 'PhoenixXD', 'EnderProYT81919', 'Ft MePro', 'NotHaqing', 'aababaha', 'serumxxx', 'bigdogoo_',
		'william18187', 'ZeroLxck', 'Gamer dan', 'SuperSAIN', 'DefNoHax', 'GoldFox', 'ClxpKxng', 'AdamIsPro', 'XXXPRO655', 'proshtGGxD',
		'GamerKid9000', 'SphericalAxeum', 'ImABot', 'god bless', 'TessieSkep', 'Vasar', 'Velvet', 'AntiCheat', 'Holding Left Click',
		'Macro', 'Nitr0', 'Chrome', 'Explorer', 'Notch', 'Mojang', 'Microsoft', 'Windows', 'eBay', 'Kijiji', 'UberEats', 'DoorDash',
		'SkipTheDishes', 'Keystroke', 'Keyboard', 'SprintToggled', 'Toggled', 'Pot', 'Google', 'Clash of Clans', 'Call of Duty',
		'M0DIFIER', 'Board', 'Marcel', 'Altviser', 'Swamp', 'Stimpy', 'Adviser', 'bcz', 'Racks', 'Skeppy', 'Sekrum',
		'xTqrget', 'Reformed', 'W1LDCARD', 'zZax', 'El Chapo', 'L0NGARM', 'xRqnger', 'Reese', 'Javail',
		'Captain Price', 'Kraken', 'el Client', 'Resting', 'Return', 'eboy', 'egirl', 'CHAOSXIII', 'PackPort',
		'Someone I', 'Someone II', 'Someone III', 'Someone IV', 'Someone V'];

	public array $links = ['.xyz', '.me', '.club', 'www.', '.com', '.net', '.gg', '.cc', '.net', '.co', '.co.uk', '.ddns',
		'.ddns.net', '.cf', '.live', '.ml', '.gov', 'http://', 'https://', ',club', 'www,', ',com', ',cc', ',net', ',gg',
		',co', ',couk', ',ddns', ',ddns.net', ',cf', ',live', ',ml', ',gov', ',xyz', 'http://', 'https://', 'gg/'];

	public array $debuffs = ['mundane', 'long_mundane', 'slowness', 'long_slowness', 'harming', 'strong_harming',
		'poison', 'long_poison', 'strong_poison', 'weakness', 'long_weakness', 'wither'];

	public array $notes = [
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Feel free to join our Discord with ' . TextFormat::MINECOIN_GOLD . Variables::DISCORD . TextFormat::YELLOW . '. See you there?' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'We want to know your thoughts on Vasar, please feel free to leave us suggestions and feedback.' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Looking for access to exclusive extras, features, and cosmetics? Check out our store at ' . TextFormat::MINECOIN_GOLD . Variables::STORE . TextFormat::YELLOW . '!' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Did you know you can run ' . TextFormat::MINECOIN_GOLD . '/settings' . TextFormat::YELLOW . ' at any time to customize your experience with many options, features, and cosmetics!' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Try out our cool chat emojis, type ' . TextFormat::MINECOIN_GOLD . ':100:' . TextFormat::YELLOW . ' in chat!' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'While we may be in a tough spot at the moment, you can continue to expect improvements and new features.' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Did someone say ' . TextFormat::MINECOIN_GOLD . 'Vasar Kit' . TextFormat::YELLOW . '?' . TextFormat::EOL . TextFormat::EOL,
		TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Catch up on our in-game rules here at Vasar. Use ' . TextFormat::MINECOIN_GOLD . '/rules' . TextFormat::YELLOW . ' to learn more!' . TextFormat::EOL . TextFormat::EOL,
		//TextFormat::EOL.TextFormat::DARK_AQUA.'['.TextFormat::AQUA.'Note'.TextFormat::RESET.TextFormat::DARK_AQUA.'] '.TextFormat::RESET.TextFormat::YELLOW.'Did you know voting for the server gives you a free rank? Try it out at '.Variables::STORE.'!'.TextFormat::EOL.TextFormat::EOL,
		//TextFormat::EOL . TextFormat::DARK_AQUA . '[' . TextFormat::AQUA . 'Note' . TextFormat::RESET . TextFormat::DARK_AQUA . '] ' . TextFormat::RESET . TextFormat::YELLOW . 'Try dueling a friend in any mode you want, run /duel [player] to fight!' . TextFormat::EOL . TextFormat::EOL,
	];

	public array $comingSoonUse = [
		'It clearly says coming soon',
		'Maybe it won\'t come out soon',
		'Can you read',
		'Wendy\'s',
		'It\'s not ready yet move on',
		'You\'re probably wondering what this is',
	];

	public array $operatingSystems = [
		DeviceOS::UNKNOWN => 'Unknown',
		DeviceOS::IOS => 'iOS',
		DeviceOS::ANDROID => 'Android',
		DeviceOS::OSX => 'macOS',
		DeviceOS::TVOS => 'Apple TV',
		DeviceOS::AMAZON => 'Amazon Fire',
		DeviceOS::GEAR_VR => 'Gear VR',
		DeviceOS::HOLOLENS => 'HoloLens',
		DeviceOS::WINDOWS_10 => 'Windows',
		DeviceOS::WIN32 => 'Windows',
		DeviceOS::WINDOWS_PHONE => 'Windows Phone',
		DeviceOS::DEDICATED => 'Dedicated',
		DeviceOS::PLAYSTATION => 'PlayStation',
		DeviceOS::XBOX => 'Xbox',
		DeviceOS::NINTENDO => 'Nintendo Switch',
	];

	public array $inputModes = ['Unknown', 'Mouse', 'Touch', 'Controller'];

	public ?Location $spawnLocation = null;
	public ?Location $nodebuffFreeForAllTetris = null;
	public ?Location $nodebuffFreeForAllPlains = null;
	public ?Location $sumoFreeForAll = null;
	public ?Location $hiveFreeForAll = null;
	public ?Location $battlefieldFreeForAll = null;

	public Clash|int|null $clash = null;
	public ?string $recentClashStarter = null;
	public ?string $currentClashChampion = null;

	public array $allowedFrozenCommands = array();

	public array $freeForAllArenas = array();

	public array $taggedPlayer = array();
	public array $pearlPlayer = array();
	public array $vanishPlayer = array();
	public array $onlineStaff = array();

	/**
	 * @throws JsonException
	 */
	public function __construct()
	{
		$this->illegalWords = explode(',', file_get_contents(Base::getInstance()->getFile() . 'resources/words.txt', true));
		$this->commonPlayers = explode(',', file_get_contents(Base::getInstance()->getFile() . 'resources/players.txt', true));

		$this->vasarSkin = new Skin('Standard_Custom', $this->createImage('vasar steve', Utils::IMAGE_TYPE_SKIN));

		$this->battlefieldStronghold = new Vector3(26.5, 76, 74.5);
	}

	public function addOnlinePlayer(Player $player): void
	{
		foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
			$pl->getNetworkSession()->onPlayerAdded($player);
		}

		Server::getInstance()->getOnlinePlayers()[$player->getUniqueId()->getBytes()] = $player;
	}

	public function removeOnlinePlayer(Player $player, Player $from = null): void
	{
		$rawUUID = $player->getUniqueId()->getBytes();

		if (isset(Server::getInstance()->getOnlinePlayers()[$rawUUID])) {
			if ($from instanceof Player) {
				$from->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($player->getUniqueId())]));
			} else {
				foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
					$pl->getNetworkSession()->onPlayerRemoved($player);
				}
			}
			unset(Server::getInstance()->getOnlinePlayers()[$rawUUID]);
		}
	}

	public function getDisguiseNames(): array
	{
		return $this->disguises;
	}

	public function getPlayingFreeForAll(): int
	{
		$count = 0;
		foreach ($this->freeForAllArenas as $arena) {
			if ($arena instanceof FreeForAll) {
				$count += $arena->getPlayers(true);
			}
		}

		return $count;
	}

	public function getIllegalWords(): array
	{
		return $this->illegalWords;
	}

	public function getCommonPlayers(): array
	{
		return $this->commonPlayers;
	}

	public function executeCommand(CommandSender $player, int $wait, string $command): bool
	{
		if ($player instanceof ConsoleCommandSender) {
			return true;
		}

		if (!$player instanceof User) {
			return false;
		}

		if (Server::getInstance()->isOp($player->getName())) {
			return true;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if (isset($this->allowedFrozenCommands[$command])) {
			if ($session->isFrozen()) {
				$player->sendMessage(TextFormat::RED . 'You can\'t execute this command while frozen.');
				return false;
			}
		}

		if (isset($session->commandWait[$command])) {
			$time = time() - $session->commandWait[$command];
			//if ($time + $wait < time()) {
			if ($time < $wait) {
				$player->sendMessage(TextFormat::RED . 'You can\'t execute this command for another ' . abs($time - $wait) . ' seconds.');
				return false;
			}
		}

		$session->commandWait[$command] = time();
		return true;
	}

	public function handleChat(PlayerChatEvent $event, bool $hide = true): void
	{
		$clash = $this->clash;
		$player = $event->getPlayer();
		if ($clash instanceof Clash and $player instanceof User) {
			if ($clash->isStarting() and $clash->hasPlayerVolunteered($player)) {
				$event->setFormat(TextFormat::GREEN . '(Clash Volunteer) ' . $player->getDisplayName() . ': ' . $event->getMessage());
				if ($hide) {
					$event->setRecipients([$player]);
				}
			}
		}
	}

	public function getChatFormat(Player $player, PlayerChatEvent $event): string
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		$rank = $session->isDisguised() ? $session->getDisguiseRank() : $session->getHighestRank();

		return match ($rank) {
			RankManager::MEDIA => '§r§7[§bMedia§7]§b ' . $player->getDisplayName() . '§f: ' . $event->getMessage(),
			RankManager::FAMOUS => '§r§7[§dFamous§7]§d ' . $player->getDisplayName() . '§f: ' . $event->getMessage(),
			RankManager::NITRO => '§r§7[§6Nitro§7]§6 ' . $player->getDisplayName() . '§f: ' . $event->getMessage(),
			RankManager::PLUS => '§r§7[§1+§7]§1 ' . $player->getDisplayName() . '§f: ' . $event->getMessage(),
			RankManager::PARTNER => '§r§7[§9Partner§7] §9' . $player->getDisplayName() . '§f: ' . $event->getMessage(),

			RankManager::TRIAL => '§r§7[§gTrial§7]§g ' . $player->getDisplayName() . '§f: §o§g' . $event->getMessage(),
			RankManager::MODERATOR => '§r§7[§2Mod§7]§2 ' . $player->getDisplayName() . '§f: §o§2' . $event->getMessage(),
			RankManager::ADMINISTRATOR => '§r§7[§3Admin§7]§3 ' . $player->getDisplayName() . '§f: §o§3' . $event->getMessage(),
			RankManager::MANAGER => '§r§7[§4Manager§7]§4 ' . $player->getDisplayName() . '§f: §o§4' . $event->getMessage(),
			RankManager::OWNER => '§r§7[§5Owner§7]§5 ' . $player->getDisplayName() . '§f: §o§5' . $event->getMessage(),

			default => '§r§7' . $player->getDisplayName() . '§f: ' . $event->getMessage(),
		};
	}

	public function getTagFormat(Player $player): string
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		$rank = $session->isDisguised() ? $session->getDisguiseRank() : $session->getHighestRank();

		return match ($rank) {
			RankManager::MEDIA => '§r§b' . $player->getDisplayName(),
			RankManager::FAMOUS => '§r§d' . $player->getDisplayName(),
			RankManager::NITRO => '§r§6' . $player->getDisplayName(),
			RankManager::PLUS => '§r§1' . $player->getDisplayName(),
			RankManager::PARTNER => '§r§9' . $player->getDisplayName(),

			RankManager::TRIAL => '§r§o§g' . $player->getDisplayName(),
			RankManager::MODERATOR => '§r§o§2' . $player->getDisplayName(),
			RankManager::ADMINISTRATOR => '§r§o§3' . $player->getDisplayName(),
			RankManager::MANAGER => '§r§o§4' . $player->getDisplayName(),
			RankManager::OWNER => '§r§o§5' . $player->getDisplayName(),

			default => '§r§7' . $player->getDisplayName(),
		};
	}

	public function getColorFormat(Player $player): string
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		$rank = $session->isDisguised() ? $session->getDisguiseRank() : $session->getHighestRank();

		return match ($rank) {
			RankManager::MEDIA => '§r§b',
			RankManager::FAMOUS => '§r§d',
			RankManager::NITRO => '§r§6',
			RankManager::PLUS => '§r§1',
			RankManager::PARTNER => '§r§9',

			RankManager::TRIAL => '§r§o§g',
			RankManager::MODERATOR => '§r§o§2',
			RankManager::ADMINISTRATOR => '§r§o§3',
			RankManager::MANAGER => '§r§o§4',
			RankManager::OWNER => '§r§o§5',

			default => '§r§7',
		};
	}

	public function getPermissions(string $string): array
	{
		return match ($string) {
			'Voter' => [
				'vasar.chat.global',

				'vasar.command.fly'
			],

			'Media', 'Famous', 'Nitro' => [
				'vasar.chat.global',

				'vasar.command.disguise',
				'vasar.command.fly'
			],

			'Plus' => [
				'vasar.chat.global',

				'vasar.command.nick',
				'vasar.command.disguise',
				'vasar.command.fly'
			],

			'Trial' => [
				'pocketmine.command.kick',

				'vasar.chat.global',
				'vasar.chat.staff',

				'vasar.command.stp',
				'vasar.command.disguise',
				'vasar.command.fly',
				'vasar.command.jarvis',
				'vasar.command.cps',
				'vasar.command.staff',
				'vasar.command.disguise',
				'vasar.command.vanish',
				'vasar.command.mute',
				'vasar.command.freeze',
				'vasar.mute.see',
				'vasar.mute.manage',
				'vasar.staff.basicnotifications',
				'vasar.staff.cheatalerts',
			],

			'Moderator' => [
				'pocketmine.command.kick',

				'vasar.chat.global',
				'vasar.chat.staff',

				'vasar.command.stp',
				'vasar.command.disguise',
				'vasar.command.nick',
				'vasar.command.fly',
				'vasar.command.jarvis',
				'vasar.command.cps',
				'vasar.command.staff',
				'vasar.command.disguise',
				'vasar.command.vanish',
				'vasar.command.mute',
				'vasar.command.ban',
				'vasar.command.freeze',
				'vasar.command.online',

				'vasar.mute.see',
				'vasar.mute.manage',
				'vasar.ban.see',
				'vasar.staff.basicnotifications',
				'vasar.staff.cheatalerts',
				'vasar.bypass.vanishsee',
				'vasar.bypass.chatcooldown',
				'vasar.bypass.chatsilence',
			],

			'Administrator' => [
				'pocketmine.command.kick',
				'pocketmine.command.teleport',

				'vasar.chat.global',
				'vasar.chat.staff',

				'vasar.command.stp',
				'vasar.command.disguise',
				'vasar.command.nick',
				'vasar.command.fly',
				'vasar.command.jarvis',
				'vasar.command.cps',
				'vasar.command.staff',
				'vasar.command.disguise',
				'vasar.command.vanish',
				'vasar.command.mute',
				'vasar.command.ban',
				'vasar.command.freeze',
				'vasar.command.who',
				'vasar.command.alias',
				'vasar.command.online',

				'vasar.mute.see',
				'vasar.mute.manage',
				'vasar.ban.see',
				'vasar.ban.manage',
				'vasar.staff.basicnotifications',
				'vasar.staff.notifications',
				'vasar.staff.cheatalerts',
				'vasar.bypass.vanishsee',
				'vasar.bypass.chatcooldown',
				'vasar.bypass.chatsilence',
			],

			'Manager' => [
				'pocketmine.command.kick',
				'pocketmine.command.teleport',

				'vasar.chat.global',
				'vasar.chat.staff',

				'vasar.command.stp',
				'vasar.command.disguise',
				'vasar.command.nick',
				'vasar.command.fly',
				'vasar.command.jarvis',
				'vasar.command.cps',
				'vasar.command.staff',
				'vasar.command.disguise',
				'vasar.command.vanish',
				'vasar.command.mute',
				'vasar.command.ban',
				'vasar.command.blacklist',
				'vasar.command.freeze',
				'vasar.command.who',
				'vasar.command.alias',
				'vasar.command.online',
				'vasar.command.rank',

				'vasar.mute.see',
				'vasar.mute.manage',
				'vasar.ban.see',
				'vasar.ban.manage',
				'vasar.blacklist.see',
				'vasar.blacklist.manage',
				'vasar.staff.basicnotifications',
				'vasar.staff.notifications',
				'vasar.staff.cheatalerts',
				'vasar.bypass.vanishsee',
				'vasar.bypass.chatcooldown',
				'vasar.bypass.chatsilence',
				'vasar.bypass.combatcommand'
			],

			default => ['vasar.chat.global']
		};
	}

	public function clearPermissions(Player $player): void
	{
		if (!$player->isConnected()) {
			return;
		}

		foreach ($player->getEffectivePermissions() as $permissions) {
			$attachment = $permissions->getAttachment();
			if (!is_null($attachment)) {
				$player->removeAttachment($attachment);
			}
		}
	}

	public function updatePermissions(Player $player): void
	{
		if (!$player->isConnected()) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		$this->clearPermissions($player);
		foreach ($session->getRanks() as $rank) {
			if (!empty($this->getPermissions($rank))) {
				foreach ($this->getPermissions($rank) as $permissions) {
					$player->addAttachment(Base::getInstance(), $permissions, true);
				}
			}
		}
	}

	public function teleport(Player $player, string|int $where, bool $doKit = false): void
	{
		if (!$player->isConnected()) {
			return;
		}

		foreach ($this->freeForAllArenas as $arena) {
			if ($arena instanceof FreeForAll) {
				if ($arena->hasPlayer($player)) {
					$arena->removePlayer($player);
				}
			}
		}

		if (is_string($where)) {
			$arena = $this->freeForAllArenas[$where];
			if ($arena instanceof FreeForAll) {
				if (!$arena->addPlayer($player)) {
					return;
				}
			}

			$where = strtolower($where);
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		switch ($where) {
			default:
				$where = 0;

				$session->hKnockBack = 0.0;
				$session->vKnockBack = 0.0;

				//$session->attackCooldown = .470;

				$session->maxDistanceKnockBack = 0.0;

				$player->teleport($this->spawnLocation);

				$player->getNetworkSession()->sendDataPacket(PlayerFogPacket::create(['minecraft:fog_default']));
				break;
			case 'nodebuff tetris':
			case 1:
				$where = 1;

				$session->hKnockBack = 0.394; // Vasar's original horizontal knockback: 0.394
				$session->vKnockBack = 0.395; // Vasar's original vertical knockback: 0.394

				//$session->attackCooldown = .470; // .470

				$session->maxDistanceKnockBack = 3.0;

				$player->teleport($this->nodebuffFreeForAllTetris);

				$player->noDamageTicks = 20 * 1;

				break;
			case 'sumo':
			case 2:
				$where = 2;
				$session->hKnockBack = 0.38;
				$session->vKnockBack = 0.39;

				//$session->attackCooldown = .490;

				$session->maxDistanceKnockBack = 2.3;

				$player->teleport($this->sumoFreeForAll);

				$player->noDamageTicks = 20 * 1;

				break;
			case 'nodebuff plains':
			case 3:
				$where = 1;

				$session->hKnockBack = 0.394; // Vasar's original horizontal knockback: 0.394
				$session->vKnockBack = 0.395; // Vasar's original vertical knockback: 0.394

				//$session->attackCooldown = .470; // .470

				$session->maxDistanceKnockBack = 3.0;

				$player->teleport($this->nodebuffFreeForAllPlains);

				$player->noDamageTicks = 20 * 1;

				break;
			case 'hive':
			case 4:
				$where = 4;
				$session->hKnockBack = 0.4;
				$session->vKnockBack = 0.4;

				//$session->attackCooldown = .490;

				$session->maxDistanceKnockBack = 3.0;

				$player->teleport($this->hiveFreeForAll);

				$player->noDamageTicks = 20 * 1;

				break;
			case 'battlefield':
			case 5:
				$where = 5;
				$session->hKnockBack = 0.36;
				$session->vKnockBack = 0.397;

				//$session->attackCooldown = .490;

				$session->maxDistanceKnockBack = 3.0;

				$session->resetBattlefieldKillstreak();

				$player->teleport($this->battlefieldFreeForAll);

				$player->noDamageTicks = 20 * 5;

				$player->getNetworkSession()->sendDataPacket(PlayerFogPacket::create(['minecraft:fog_hell']));
				break;
		}

		foreach (Base::getInstance()->scoreboardManager->getMainPlayers() as $mainPlayer) {
			if ($mainPlayer instanceof User) {
				Base::getInstance()->scoreboardManager->updateMainPlaying($mainPlayer);
			}
		}

		$this->setTagged($player, false, false, true, -1);
		$this->setPearlCooldown($player, false);
		$session->setLastMove();

		if ($doKit) {
			$this->kit($player, $where);
		}

		$session->setCurrentWarp($where);

		if ($where === Session::WARP_SPAWN) {
			Base::getInstance()->scoreboardManager->addMain($player);
		} else {
			Base::getInstance()->scoreboardManager->addFfa($player);
		}
	}

	public function kit(Player $player, string|int $kit, bool $strict = false): void
	{
		if (!$player->isConnected()) {
			return;
		}

		if (!$player instanceof User) {
			return;
		}

		if (is_string($kit)) {
			$kit = strtolower($kit);
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		$session->setLastDamagePosition();

		$player->setHealth(20);
		$player->getEffects()->clear();
		$player->extinguish();
		$player->setAbsorption(0.0);
		$player->getInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->setFlying(false);
		$player->setAllowFlight(false);
		if ($player->getGamemode() !== GameMode::ADVENTURE()) {
			$player->setGamemode(GameMode::ADVENTURE());
		}
		$player->getXpManager()->setXpAndProgress(0, 0.0);
		$player->getInventory()->setHeldItemIndex(0);

		if ($strict) {
			return;
		}

		$player->setInvisible(false);

		if ($player->isImmobile() and !$session->isFrozen()) {
			$player->setImmobile(false);
		}

		switch ($kit) {
			case 'spawn':
			case 'lobby':
			case 0:
				$kit = 0;

				$clash = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);
				$clash->setCustomName(Variables::LOBBY_ITEM_CLASH);

				$ffa = ItemFactory::getInstance()->get(ItemIds::STONE_SWORD);
				$ffa->setCustomName(Variables::LOBBY_ITEM_FREE_FOR_ALL);

				$settings = ItemFactory::getInstance()->get(ItemIds::DRAGON_BREATH);
				$settings->setCustomName(Variables::LOBBY_ITEM_SETTINGS);

				$player->getInventory()->setItem(0, $clash->setUnbreakable(true));
				$player->getInventory()->setItem(1, $ffa->setUnbreakable(true));
				$player->getInventory()->setItem(8, $settings);
				break;
			case 'nodebuff':
			case 1:
				$kit = 1;

				$helmet = ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
				$helmet->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$helmet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setHelmet($helmet->setUnbreakable(true));
				$chestplate = ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
				$chestplate->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$chestplate->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setChestplate($chestplate->setUnbreakable(true));
				$leggings = ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
				$leggings->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$leggings->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setLeggings($leggings->setUnbreakable(true));
				$boots = ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
				$boots->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$boots->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setBoots($boots->setUnbreakable(true));
				$sword = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);
				$sword->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$sword->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));

				$pearls = ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16);
				$pearls->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);

				$pots = ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 22, 36);
				$pots->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);

				$player->getInventory()->setItem(0, $sword->setUnbreakable(true));
				$player->getInventory()->setItem(1, $pearls);
				$player->getInventory()->addItem($pots);

				$player->getInventory()->setHeldItemIndex(0);

				$player->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::SPEED), 9999 * 9999, 0, false));
				break;
			case 2:
				$kit = 2;
				break;
			case 3:
				$kit = 1;
				break;
			case 4:
				$kit = 4;

				$snowballs = ItemFactory::getInstance()->get(ItemIds::SNOWBALL);
				$snowballs->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Snowball');

				$player->getInventory()->addItem($snowballs);
				break;
			case 'battlefield':
			case 5:
				$kit = 5;

				$helmet = ItemFactory::getInstance()->get(ItemIds::IRON_HELMET);
				$helmet->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$helmet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::PROTECTION), 2));
				$helmet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setHelmet($helmet->setUnbreakable(true));
				$chestplate = ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE);
				$chestplate->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$chestplate->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::PROTECTION), 3));
				$chestplate->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::BLAST_PROTECTION), 1));
				$chestplate->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setChestplate($chestplate->setUnbreakable(true));
				$leggings = ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS);
				$leggings->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$leggings->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::PROTECTION), 3));
				$leggings->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setLeggings($leggings->setUnbreakable(true));
				$boots = ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS);
				$boots->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$boots->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::PROTECTION), 3));
				$boots->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::FEATHER_FALLING), 1));
				$boots->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));
				$player->getArmorInventory()->setBoots($boots->setUnbreakable(true));
				$sword = ItemFactory::getInstance()->get(ItemIds::GOLD_SWORD);
				$sword->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);
				$sword->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::SHARPNESS), 1));
				$sword->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::UNBREAKING), 10));

				$fireballs = ItemFactory::getInstance()->get(ItemIds::FIREBALL, 0, 16);
				$fireballs->setCustomName(TextFormat::RESET . TextFormat::RED . 'Fireball');

				$pots = ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 22, 36);
				$pots->setCustomName(TextFormat::RESET . TextFormat::AQUA . Variables::NAME);

				$player->getInventory()->setItem(0, $sword->setUnbreakable(true));
				$player->getInventory()->setItem(1, $fireballs);
				$player->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::ENCHANTED_GOLDEN_APPLE, 0, 1));
				$player->getInventory()->addItem($pots);

				$player->getInventory()->setHeldItemIndex(0);

				$player->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::SPEED), 9999 * 9999, 0, false));
				break;
		}

		$session->setRecentKit($kit);
	}

	public function doDamageCheck(Player $player, EntityDamageEvent $event): void
	{
		if (!$player instanceof User) {
			return;
		}

		/*$session = Base::getInstance()->sessionManager->getSession($player);

		$damager = $session->getDamager();
		if (!$event->isCancelled() and $player->isSurvival() and $player->isAlive() and $session->canTakeDamage()) {
			$final = $event->getFinalDamage();
			$health = $player->getHealth();
			if ($final > $health) {
				$event->cancel();
				$damager = is_null($damager) ? null : Server::getInstance()->getPlayerExact($damager);
				$this->onDeath($player, $damager);
			}
		}*/
	}

	public function onDeath(Player $player, Player|null $killer = null, bool $animation = true, bool $actuallyDied = false): void
	{

		if ($killer instanceof User and $player->getName() === $killer->getName()) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if ($session->isAfk()) {
			$session->setAfk();
		}
		$session->addDeath();
		$session->resetKillstreak();
		//$session->resetAgroTimer();

		Base::getInstance()->scoreboardManager->removeScoreboard($player);

		if ($killer instanceof User and $this->isInFfa($killer)) {
			$sessionKiller = Base::getInstance()->sessionManager->getSession($killer);

			$sessionKiller->addKill();
			$sessionKiller->addToKillstreak();

			$isBattlefield = $sessionKiller->getCurrentWarp() === Session::WARP_BATTLEFIELD;

			if ($sessionKiller->getCurrentWarp() === Session::WARP_NODEBUFF_TETRIS or $sessionKiller->getCurrentWarp() === Session::WARP_NODEBUFF_PLAINS or $isBattlefield) {
				Server::getInstance()->broadcastMessage(TextFormat::GREEN . $killer->getDisplayName() . TextFormat::DARK_GREEN . '[' . count($killer->getInventory()->all(ItemFactory::getInstance()->get(438, 22))) . ']' . TextFormat::GRAY . ' killed ' . TextFormat::RED . $player->getDisplayName() . TextFormat::DARK_RED . '[' . count($player->getInventory()->all(ItemFactory::getInstance()->get(438, 22))) . ']');
			} else {
				Server::getInstance()->broadcastMessage(TextFormat::GREEN . $killer->getDisplayName() . TextFormat::GRAY . ' killed ' . TextFormat::RED . $player->getDisplayName());
			}

			$this->setTagged($killer, true, true, false, Variables::COMBAT_TAG_KILL);

			if ($isBattlefield) {
				$sessionKiller->addToBattlefieldKillstreak();

				$killer->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::ENCHANTED_GOLDEN_APPLE, 0, 1));
				$killer->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::FIREBALL, 0, mt_rand(3, 6))->setCustomName(TextFormat::RESET . TextFormat::RED . 'Fireball'));
				$killer->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 22, 36));
				$killer->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::STRENGTH), 20 * 8, 1, true));
				$killer->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::RESISTANCE), 20 * 12, 0, true, true));
				$killer->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::REGENERATION), 20 * 6, 30, true, true));
			} else {
				if ($sessionKiller->isAutoRekit()) {
					$this->kit($killer, $sessionKiller->getRecentKit());
				}
			}

			$this->doLightning($player);

			if ($isBattlefield and mt_rand(1, 500) < 30) {
				$arena = Base::getInstance()->utils->freeForAllArenas['Battlefield'];
				if ($arena instanceof FreeForAll) {
					foreach ($arena->getPlayers(false, true) as $players) {
						if ($players instanceof Player) {
							$players->sendMessage(TextFormat::GOLD . 'An ' . TextFormat::LIGHT_PURPLE . 'Enchanted Apple' . TextFormat::GOLD . ' has spawned on top of the mountain as a tribute to ' . TextFormat::AQUA . $player->getDisplayName() . TextFormat::GOLD . '.');
						}
					}
				}
				$world = Server::getInstance()->getWorldManager()->getWorldByName(Variables::BATTLEFIELD_FFA_ARENA);
				if ($world instanceof World and $world->isLoaded()) {
					$world->dropItem($this->battlefieldStronghold, ItemFactory::getInstance()->get(ItemIds::ENCHANTED_GOLDEN_APPLE, 0, 1));
					$world->addSound($this->battlefieldStronghold, new TotemUseSound());
				}
			}
		}

		$this->setTagged($player, false, true, true, -1);
		$this->setPearlCooldown($player, false);

		/*if ($session->isInstantRespawn()) {
			$session->doRespawn();
		} else {
			$time = $actuallyDied ? 0 : Variables::RESPAWN_TIMER;
			$session->startRespawnTimer($time);
		}

		$player->setFlying(false);
		$player->setAllowFlight(false);

		$this->doLightning($player);
		$player->knockBack($player->getPosition()->getX() - $killer->getPosition()->getX(), $player->getPosition()->getZ() - $killer->getPosition()->getZ(), 0.59, 0.32);
		$player->broadcastAnimation(new DeathAnimation($player), $player->getViewers());
		$player->setHealth(20);
		$player->getEffects()->clear();
		$player->setAbsorption(0.0);
		$player->getInventory()->clearAll();
		$player->setSprinting(false);
		if ($player->getGamemode() !== GameMode::ADVENTURE()) {
			$player->setGamemode(GameMode::ADVENTURE());
		}
		$player->getXpManager()->setXpAndProgress(0, 0.0);
		$session->setTakeDamage(false);*/
	}

	public function doLightning(Player $player): void
	{
		$location = $player->getLocation();

		$lightning = new AddActorPacket();

		$lightning->actorUniqueId = Entity::nextRuntimeId();
		$lightning->actorRuntimeId = $lightning->actorUniqueId;
		$lightning->type = 'minecraft:lightning_bolt';
		$lightning->position = $location->asVector3();
		$lightning->motion = null;
		$lightning->pitch = $location->getPitch();
		$lightning->yaw = $location->getYaw();
		$lightning->headYaw = 0.0;
		$lightning->attributes = [];
		$lightning->metadata = [];
		$lightning->links = [];

		$thunder = new PlaySoundPacket();
		$thunder->soundName = 'ambient.weather.thunder';
		$thunder->x = $location->getX();
		$thunder->y = $location->getY();
		$thunder->z = $location->getZ();
		$thunder->volume = 1;
		$thunder->pitch = 1;

		$session = Base::getInstance()->sessionManager->getSession($player);
		if ($session->isLightning()) {
			$player->getNetworkSession()->sendDataPacket($lightning);
			$player->getNetworkSession()->sendDataPacket($thunder);
		}

		foreach ($player->getViewers() as $viewer) {
			if ($viewer instanceof User) {
				$session = Base::getInstance()->sessionManager->getSession($viewer);
				if ($session->isLightning()) {
					$viewer->getNetworkSession()->sendDataPacket($lightning);
					$viewer->getNetworkSession()->sendDataPacket($thunder);
				}
			}
		}
	}

	public function doSoundPacket(Entity $player, $choice = 'random.orb', $pitch = 1, $volume = 1, bool $world = false, bool $online = false)
	{
		$sound = new PlaySoundPacket();
		$sound->soundName = $choice;
		$sound->x = $player->getPosition()->getX();
		$sound->y = $player->getPosition()->getY();
		$sound->z = $player->getPosition()->getZ();
		$sound->volume = $volume;
		$sound->pitch = $pitch;

		if ($world) {
			Server::getInstance()->broadcastPackets($player->getWorld()->getPlayers(), [$sound]);
		} elseif ($online) {
			Server::getInstance()->broadcastPackets(Server::getInstance()->getOnlinePlayers(), [$sound]);
		} else {
			$player->getNetworkSession()->sendDataPacket($sound);
		}
	}

	public function setPearlCooldown(Player $player, bool $value = true, bool $notify = false, int $time = Variables::PEARL_COOLDOWN): void
	{
		if (!$player instanceof User) {
			return;
		}

		if (!$player->isSurvival()) {
			return;
		}

		if ($value) {
			if (!$this->isInPearlCooldown($player)) {
				if ($notify) {
					$player->sendActionBarMessage(TextFormat::RED . 'Pearl-Cooldown Started');
				}
			}
			$this->pearlPlayer[$player->getName()] = $time;
		} else {
			if ($this->isInPearlCooldown($player)) {
				unset($this->pearlPlayer[$player->getName()]);
				if ($notify) {
					$player->sendActionBarMessage(TextFormat::GREEN . 'Pearl-Cooldown Expired');
				}
			}
		}
	}

	public function isInPearlCooldown(Player $player): bool
	{
		return isset($this->pearlPlayer[$player->getName()]);
	}

	public function setTagged(Player $player, bool $value = true, bool $notify = false, bool $clearDamager = true, int $time = Variables::COMBAT_TAG): void
	{
		if (!$player instanceof User) {
			return;
		}

		if (!$player->isSurvival()) {
			return;
		}

		$session = Base::getInstance()->sessionManager->getSession($player);

		if ($value) {
			if (!$this->isTagged($player)) {
				if ($notify) {
					$player->sendActionBarMessage(TextFormat::RED . 'Combat-Tag Started');
				}
			} else {
				$originalTime = $this->taggedPlayer[$player->getName()];
				if ($originalTime >= Variables::COMBAT_TAG_KILL and $time === Variables::COMBAT_TAG_KILL) {
					$player->sendActionBarMessage(TextFormat::GOLD . 'Combat-Tag Reduced');
				}
			}

			$this->taggedPlayer[$player->getName()] = $time;
		} else {
			if ($this->isTagged($player)) {
				unset($this->taggedPlayer[$player->getName()]);
				if ($notify) {
					$player->sendActionBarMessage(TextFormat::GREEN . ($time === -1 ? 'Combat-Tag Removed' : 'Combat-Tag Expired'));
				}
				if ($clearDamager) {
					$session->setDamager();
				}

				$player->clearHidden();
			}
		}
	}

	public function isTagged(Player $player): bool
	{
		return isset($this->taggedPlayer[$player->getName()]);
	}

	public function getVanishPlayers(): array
	{
		return $this->vanishPlayer;
	}

	public function setVanished(Player $player, $value = true)
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		$session->setVanished($value);
	}

	public function isInVanish(Player $player): bool
	{
		return isset($this->vanishPlayer[$player->getName()]);
	}

	public function isInSpawn(Player $player): bool
	{
		return $player->getWorld()->getFolderName() === Variables::SPAWN;
	}

	public function isInNoDebuffFfa(Player $player): bool
	{
		return $player->getWorld()->getFolderName() === Variables::NODEBUFF_FFA_ARENA_TETRIS or $player->getWorld()->getFolderName() === Variables::NODEBUFF_FFA_ARENA_PLAINS;
	}

	public function isInSumoFfa(Player $player): bool
	{
		return $player->getWorld()->getFolderName() === Variables::SUMO_FFA_ARENA;
	}

	public function isInHiveFfa(Player $player): bool
	{
		return $player->getWorld()->getFolderName() === Variables::HIVE_FFA_ARENA;
	}

	public function isInBattlefieldFfa(Player $player): bool
	{
		return $player->getWorld()->getFolderName() === Variables::BATTLEFIELD_FFA_ARENA;
	}

	public function isInFfa(Player $player): bool
	{
		return $this->isInNoDebuffFfa($player) or $this->isInSumoFfa($player) or $this->isInHiveFfa($player) or $this->isInBattlefieldFfa($player);
	}

	public function setPlayerData(Player $player, string $key, $value): void
	{
		$path = Base::getInstance()->getDataFolder() . 'players/' . $player->getName() . '.yml';
		if (file_exists($path)) {
			$data = yaml_parse_file($path);
			if (is_array($data) and isset($data[$key])) {
				$data[$key] = $value;
			}
			yaml_emit_file($path, $data);
		}
	}

	public function getPlayerData(Player $player): array
	{
		$path = Base::getInstance()->getDataFolder() . 'players/' . $player->getName() . '.yml';
		if (file_exists($path)) {
			$data = yaml_parse_file($path);
			if (is_array($data)) {
				return $data;
			}
		}
		return array();
	}

	public function isDebuffPotion(PotionType $potionType): bool
	{

		if (in_array($potionType->name(), $this->debuffs)) {
			return true;
		}
		return false;
	}

	public function createImage($file, int $type = self::IMAGE_TYPE_SKIN): ?string
	{
		$path = match ($type) {
			default => Base::getInstance()->getFile() . 'resources/skins/' . $file . '.png',
			self::IMAGE_TYPE_CAPE => Base::getInstance()->getFile() . 'resources/capes/' . $file . '.png',
		};

		if (is_null($path)) {
			return null;
		}

		$img = @imagecreatefrompng($path);
		$bytes = '';
		$l = (int)@getimagesize($path)[1];
		for ($y = 0; $y < $l; $y++) {
			for ($x = 0; $x < 64; $x++) {
				$rgba = @imagecolorat($img, $x, $y);
				$a = ((~(($rgba >> 24))) << 1) & 0xff;
				$r = ($rgba >> 16) & 0xff;
				$g = ($rgba >> 8) & 0xff;
				$b = $rgba & 0xff;
				$bytes .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		if (is_bool($img)) {
			return null;
		}
		@imagedestroy($img);
		return $bytes;
	}

	public function getColor(string $string): Color
	{
		return match (strtolower($string)) {
			'red', => new Color(255, 0, 0),
			'orange', => new Color(255, 155, 0),
			'yellow', => new Color(255, 255, 0),
			'green', => new Color(0, 255, 0),
			'aqua', => new Color(0, 255, 255),
			'blue', => new Color(70, 0, 255),
			'pink', => new Color(255, 0, 255),
			'white', => new Color(255, 255, 255),
			'gray', => new Color(155, 155, 155),
			'black', => new Color(0, 0, 0),
			default => new Color(0x38, 0x5d, 0xc6)
		};
	}

	/**
	 * @throws Exception
	 */
	public function getTime(): string
	{
		$timezone = 'America/Chicago';
		$timestamp = time();
		$date = new \DateTime('now', new \DateTimeZone($timezone));
		$date->setTimestamp($timestamp);
		return $date->format('m/d/Y');
	}
}