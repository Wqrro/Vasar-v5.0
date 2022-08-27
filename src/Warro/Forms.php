<?php

declare(strict_types=1);

namespace Warro;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Warro\forms\CustomForm;
use Warro\forms\EmptyForm;
use Warro\forms\SimpleForm;
use Warro\games\clash\Clash;
use Warro\games\FreeForAll;
use Warro\managers\RankManager;

class Forms
{

	private InvMenu $settingsMenu;
	private SimpleForm $settingsForm;

	public function __construct()
	{
		$this->settingsMenu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->settingsMenu->setName(Variables::LOBBY_ITEM_SETTINGS);
		$inventory = $this->settingsMenu->getInventory();

		$item = ItemFactory::getInstance()->get(ItemIds::ITEM_FRAME);
		$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Display');
		$inventory->setItem(11, $item);

		$item = ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL);
		$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Visual');
		$inventory->setItem(12, $item);

		$item = ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE);
		$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Gameplay');
		$inventory->setItem(13, $item);

		$item = ItemFactory::getInstance()->get(ItemIds::MOB_HEAD, 3);
		$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Privacy');
		$inventory->setItem(14, $item);

		$item = ItemFactory::getInstance()->get(ItemIds::DIAMOND);
		$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . 'Advanced');
		$inventory->setItem(15, $item);

		$this->settingsMenu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
			$player = $transaction->getPlayer();
			if (!$player instanceof Player) {
				return $transaction->discard();
			}

			$this->settingsMenu->onClose($player);

			// If this is broken, get the saved phar from the Vasar v5 folder in dedi
			switch ($transaction->getAction()->getSlot()) {
				case 11:
					$this->displaySettings($player);
					break;
				case 12:
					$this->visualSettings($player);
					break;
				case 13:
					$this->gameplaySettings($player);
					break;
				case 14:
					$this->privacySettings($player);
					break;
				case 15:
					$this->cosmetics($player);
					break;
			}

			return $transaction->discard();
		});

		$this->settingsForm = new SimpleForm(function (User|Player $player, $data = null): void {
			if (!is_null($data)) {
				switch ($data) {
					case 'display':
						$this->displaySettings($player);
						break;
					case 'visual':
						$this->visualSettings($player);
						break;
					case 'gameplay':
						$this->gameplaySettings($player);
						break;
					case 'privacy':
						$this->privacySettings($player);
						break;
					case 'advanced':
						$this->cosmetics($player);
						break;
				}
			}
		});
		$this->settingsForm->setTitle('Settings');
		$this->settingsForm->addButton('Display', -1, '', 'display');
		$this->settingsForm->addButton('Visual', -1, '', 'visual');
		$this->settingsForm->addButton('Gameplay', -1, '', 'gameplay');
		$this->settingsForm->addButton('Privacy', -1, '', 'privacy');
		$this->settingsForm->addButton('Advanced', -1, '', 'advanced');
	}

	public function clash(User|Player $player): void
	{
		$instance = Base::getInstance();
		$clash = $instance->utils->clash;
		$form = new SimpleForm(function (User|Player $player, $data = null) use ($instance, $clash): void {
			if (!is_null($data)) {
				switch ($data) {
					case 0:
						if (is_null($clash)) {
							if ($instance->utils->recentClashStarter === $player->getName()) {
								$player->sendMessage(TextFormat::RED . 'You\'re not allowed to activate another Clash, please try again later.');
								return;
							}

							$newClash = new Clash();
							$instance->utils->clash = $newClash;
							$instance->utils->recentClashStarter = $player->getName();

							$newClash->addPlayer($player);
						} elseif ($clash instanceof Clash) {
							if ($clash->isIdle()) {
								if (!$clash->addPlayer($player)) {
									$player->sendMessage(TextFormat::RED . 'Clash is already starting or the challenger has left, please try again later.');
									return;
								}
							}
						}
						break;
					case 1:
						if ($clash instanceof Clash) {
							if (!$clash->hasStarted()) {
								$clash->removePlayer($player);
							}
						}
						break;
					case 2:
						$diff = time() - $clash;
						if ($diff < Clash::INTERMISSION_PERIOD) {
							$player->sendMessage(TextFormat::RED . 'Clash is in intermission for another ' . abs($diff) . ' seconds.');
						}
						break;
				}
			}
		});
		if (is_null($clash)) {
			$form->addButton('Volunteer', -1, '', 0);
		} elseif ($clash instanceof Clash) {
			if (!$clash->hasPlayerVolunteered($player)) {
				$form->addButton('Volunteer', -1, '', 0);
			} else {
				$form->addButton('Leave', -1, '', 1);
			}
		} elseif (is_int($clash)) {
			$form->addButton(strval(abs(time() - $clash)), -1, '', 2);
		}

		$form->setTitle('Clash');
		$player->sendForm($form);
	}

	public function freeForAll(User|Player $player): void
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		if (!$session instanceof Session) {
			return;
		}

		if ($session->useMenusOverForms) {
			$menu = InvMenu::create(InvMenu::TYPE_CHEST);
			$menu->setName(Variables::LOBBY_ITEM_FREE_FOR_ALL);
			$inventory = $menu->getInventory();

			$slot = 10;
			foreach (Base::getInstance()->utils->freeForAllArenas as $arena) {
				if ($arena instanceof FreeForAll) {
					$prompt = $arena->isFull() ? TextFormat::RED . 'Arena is full' : TextFormat::GREEN . 'Click to join';

					$item = clone $arena->getItem();
					$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . $arena->getName());
					$item->setLore([
						' ',
						TextFormat::RESET . TextFormat::GRAY . $arena->getPlayers(true) . '/' . $arena->getMaxPlayers(),
						' ',
						TextFormat::RESET . $prompt,
					]);
					$inventory->setItem($slot += 1, $item);
				}
			}

			$menu->setListener(function (InvMenuTransaction $transaction) use ($menu): InvMenuTransactionResult {
				$player = $transaction->getPlayer();
				if (!$player instanceof Player) {
					return $transaction->discard();
				}

				$itemClicked = $transaction->getItemClicked();
				$cleanName = TextFormat::clean($itemClicked->getCustomName());

				if (isset(Base::getInstance()->utils->freeForAllArenas[$cleanName])) {
					$arena = Base::getInstance()->utils->freeForAllArenas[$cleanName];
					if ($arena instanceof FreeForAll) {
						if (!Server::getInstance()->getWorldManager()->isWorldLoaded($arena->getWorld())) {
							$player->sendMessage(TextFormat::RED . 'This Arena is unavailable at the moment.');
							$menu->onClose($player);
							return $transaction->discard();
						}

						if ($arena->isFull()) {
							$player->sendMessage(TextFormat::RED . 'This Arena is full at the moment.');
						} else {
							Base::getInstance()->utils->teleport($player, $arena->getName(), true);
						}
					}
				}

				return $transaction->discard();
			});

			$menu->send($player);
		} else {
			$form = new SimpleForm(function (User|Player $player, $data = null): void {
				if (!is_null($data)) {
					if ($data === 0) {
						$player->sendMessage(TextFormat::RED . 'This Arena is full at the moment.');
						return;
					} elseif ($data === 1) {
						$player->sendMessage(TextFormat::RED . 'This Arena is unavailable at the moment.');
						return;
					}
					Base::getInstance()->utils->teleport($player, $data, true);
				}
			});

			foreach (Base::getInstance()->utils->freeForAllArenas as $arena) {
				if ($arena instanceof FreeForAll) {

					if ($arena->isFull() and !$session->isStaff()) {
						$data = 0;
					} elseif (!Server::getInstance()->getWorldManager()->isWorldLoaded($arena->getWorld())) {
						$data = 1;
					} else {
						$data = $arena->getName();
					}

					$texture = $arena->getTexture();
					$imageType = empty($texture) ? -1 : 0;

					$form->addButton($arena->getName() . ' ' . $arena->getPlayers(true) . '/' . $arena->getMaxPlayers(), $imageType, $texture, $data);
				}
			}

			$form->setTitle('Free For All');
			$player->sendForm($form);
		}
	}

	public function stats(User|Player $player, $target = null): void
	{
		$target = is_null($target) ? $player : $target;
		if (is_string($target)) $target = Server::getInstance()->getPlayerExact($target);
		if ($target instanceof User and $target->isOnline()) {
			$session = Base::getInstance()->sessionManager->getSession($target);

			if (!$session instanceof Session) {
				return;
			}

			$timeSession = explode(':', $session->getFormattedPlaytimeSession());
			$timeTotal = explode(':', $session->getFormattedPlaytimeTotal());

			$kdr = $session->getDeaths() > 0 ? round($session->getKills() / $session->getDeaths(), 2) : $session->getKills() . '.00';

			$form = new EmptyForm($target->getName() . '\'s Casual Stats',
				TextFormat::AQUA . 'Kills: ' . TextFormat::WHITE . $session->getKills() . TextFormat::EOL .
				TextFormat::AQUA . 'Killstreak: ' . TextFormat::WHITE . $session->getKillstreak() . TextFormat::EOL .
				TextFormat::AQUA . 'Best Killstreak: ' . TextFormat::WHITE . $session->getBestKillstreak() . TextFormat::EOL .
				TextFormat::AQUA . 'Deaths: ' . TextFormat::WHITE . $session->getDeaths() . TextFormat::EOL .
				TextFormat::AQUA . 'K/D Ratio: ' . TextFormat::WHITE . $kdr . TextFormat::EOL . TextFormat::EOL .
				TextFormat::AQUA . 'Playtime (All Time): ' . TextFormat::WHITE . $timeTotal[0] . ' hours' . TextFormat::EOL .
				TextFormat::AQUA . 'Playtime (Session): ' . TextFormat::WHITE . $timeSession[0] . ' hours'
			);
			$form->openForm($player);
		}
	}

	public function rules(User|Player $player): void
	{
		$form = new EmptyForm('Rules',
			TextFormat::AQUA . 'Vasar\'s in-game rules' . TextFormat::EOL .
			TextFormat::GRAY . TextFormat::ITALIC . 'Please read everything carefully. These rules matter as they\'re what can keep you from getting banned, muted, or even blacklisted.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'No unusual clicking methods' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'You are only permitted to butterfly click, jitter click, or single click. Usage of any other clicking methods that may enhance clicks-per-second, promote an unfair advantage and may be considered cheating.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'Double clicks are allowed to an extent' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'Keep your debounce time at 6 milliseconds or higher.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'No teaming or interruptions in Free For All' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'Avoid getting in the way of fights, or attempting to break, end, or disrupt an on-going fight between 1 more players. This rule does not apply if consent was given by all parties involved.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'Rules are subject to common sense' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'These rules are not comprehensive and use of loopholes to violate the spirit of these rules is subject to enforcement.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'Show some respect' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'Try your best to treat everyone with respect. Any excessive amounts of toxicity or hate speech will not be tolerated.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'Use English only' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'Communicate in English, but be considerate of all languages.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'Keep the chat easy to read' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'No spamming, self-promotion or advertisements.' . TextFormat::RESET . TextFormat::EOL . TextFormat::EOL .

			TextFormat::DARK_AQUA . 'No personal information' . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . 'Respect and protect the privacy of yourself and the privacy of others.'
		);
		$form->openForm($player);
	}

	public function settings(User|Player $player): void
	{
		$session = Base::getInstance()->sessionManager->getSession($player);

		if (!$session instanceof Session) {
			return;
		}

		if ($session->useMenusOverForms) {
			$this->settingsMenu->send($player);
		} else {
			$player->sendForm($this->settingsForm);
		}
	}

	public function displaySettings(User|Player $player): void
	{
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				switch ($data[0]) {
					case 0:
						if ($session->isScoreboard() !== true) {
							$session->setScoreboard(true);
							Base::getInstance()->scoreboardManager->getCorrectScoreboard($player);
						}
						break;
					case 1:
						if ($session->isScoreboard() !== false) {
							$session->setScoreboard(false);
							Base::getInstance()->scoreboardManager->removeScoreboard($player, false);
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->isCpsCounter() !== true) {
							$session->setCpsCounter(true);
						}
						break;
					case 1:
						if ($session->isCpsCounter() !== false) {
							$session->setCpsCounter(false);
						}
						break;
				}
				$this->displaySettings($player);
			} else {
				$this->settings($player);
			}
		});
		$form->setTitle('Display Settings');
		switch ($session->isScoreboard()) {
			case false:
				$form->addDropdown('Scoreboard:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Scoreboard:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isCpsCounter()) {
			case false:
				$form->addDropdown('CPS Counter:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('CPS Counter:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		$player->sendForm($form);
	}

	public function visualSettings(User|Player $player): void
	{
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				switch ($data[0]) {
					case 0:
						if ($session->isLightning() !== true) {
							$session->setLightning(true);
						}
						break;
					case 1:
						if ($session->isLightning() !== false) {
							$session->setLightning(false);
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->isParticleSplashes() !== true) {
							$session->setParticleSplashes(true);
						}
						break;
					case 1:
						if ($session->isParticleSplashes() !== false) {
							$session->setParticleSplashes(false);
						}
						break;
				}
				/*switch ($data[2]) {
					case 0:
						if ($session->isPearlAnimation() !== true) {
							$session->setPearlAnimation(true);
						}
						break;
					case 1:
						if ($session->isPearlAnimation() !== false) {
							$session->setPearlAnimation(false);
						}
						break;
				}*/
				$this->visualSettings($player);
			} else {
				$this->settings($player);
			}
		});
		$form->setTitle('Visual Settings');
		switch ($session->isLightning()) {
			case false:
				$form->addDropdown('Lightning:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Lightning:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isParticleSplashes()) {
			case false:
				$form->addDropdown('Potion Splashes:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Potion Splashes:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		/*switch ($session->isPearlAnimation()) {
			case false:
				$form->addDropdown('Pearl Animation:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Pearl Animation:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}*/
		$player->sendForm($form);
	}

	public function gameplaySettings(User|Player $player): void
	{
		if (!$player instanceof User) {
			return;
		}
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				/*switch ($data[0]) {
					case 0:
						if ($session->isAutoSprint() !== true) {
							$session->setAutoSprint(true);
						}
						break;
					case 1:
						if ($session->isAutoSprint() !== false) {
							$session->setAutoSprint(false);
						}
						break;
				}*/
				switch ($data[0]) {
					case 0:
						if ($session->isAutoRekit() !== true) {
							$session->setAutoRekit(true);
						}
						break;
					case 1:
						if ($session->isAutoRekit() !== false) {
							$session->setAutoRekit(false);
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->isAntiInterfere() !== true) {
							$session->setAntiInterfere(true);
						}
						break;
					case 1:
						if ($session->isAntiInterfere() !== false) {
							$session->setAntiInterfere(false);
						}
						break;
				}
				switch ($data[2]) {
					case 0:
						if ($session->isAntiClutter() !== true) {
							$session->setAntiClutter(true);
						}
						break;
					case 1:
						if ($session->isAntiClutter() !== false) {
							$session->setAntiClutter(false);
							$player->clearHidden();
						}
						break;
				}
				switch ($data[3]) {
					case 0:
						if ($session->isInstantRespawn() !== true) {
							$session->setInstantRespawn(true);

							$packet = new GameRulesChangedPacket();
							$packet->gameRules['doImmediateRespawn'] = new BoolGameRule(true, false);
							$player->getNetworkSession()->sendDataPacket($packet);
						}
						break;
					case 1:
						if ($session->isInstantRespawn() !== false) {
							$session->setInstantRespawn(false);

							$packet = new GameRulesChangedPacket();
							$packet->gameRules['doImmediateRespawn'] = new BoolGameRule(false, false);
							$player->getNetworkSession()->sendDataPacket($packet);
						}
						break;
				}
				$this->gameplaySettings($player);
			} else {
				$this->settings($player);
			}
		});
		$form->setTitle('Gameplay Settings');
		/*switch ($session->isAutoSprint()) {
			case false:
				$form->addDropdown('Toggle Sprint:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Toggle Sprint:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}*/
		switch ($session->isAutoRekit()) {
			case false:
				$form->addDropdown('Auto Rekit:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Auto Rekit:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isAntiInterfere()) {
			case false:
				$form->addDropdown('Anti Interference:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Anti Interference:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isAntiClutter()) {
			case false:
				$form->addDropdown('Anti Clutter (Anti Interference required):', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Anti Clutter (Anti Interference required):', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isInstantRespawn()) {
			case false:
				$form->addDropdown('Instant Respawn:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Instant Respawn:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		$player->sendForm($form);
	}

	public function matchmakingSettings(User|Player $player): void
	{
		if (!$player instanceof User) {
			return;
		}
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				if (!$session->hasRank(RankManager::PLUS)) {
					$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this setting, please refer to ' . Variables::STORE . '.');
					return;
				}
				switch ($data[0]) {
					case 0:
						if ($session->getMatchAgainstTouch() !== false) {
							if ($session->getMatchAgainstController() === false and $session->getMatchAgainstMouse() === false) {
								$this->matchmakingSettings($player);
								return;
							} elseif ($session->getInputMode(true) === 'Touch') {
								$player->sendMessage(TextFormat::RED . 'You can\'t opt-out of queueing against players with the same controls as you.');
								$this->matchmakingSettings($player);
								return;
							}
							$session->setMatchAgainstTouch(false);
						}
						break;
					case 1:
						if ($session->getMatchAgainstTouch() !== true) {
							$session->setMatchAgainstTouch(true);
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->getMatchAgainstController() !== false) {
							if ($session->getMatchAgainstTouch() === false and $session->getMatchAgainstMouse() === false) {
								$this->matchmakingSettings($player);
								return;
							} elseif ($session->getInputMode(true) === 'Controller') {
								$player->sendMessage(TextFormat::RED . 'You can\'t opt-out of queueing against players with the same controls as you.');
								$this->matchmakingSettings($player);
								return;
							}
							$session->setMatchAgainstController(false);
						}
						break;
					case 1:
						if ($session->getMatchAgainstController() !== true) {
							$session->setMatchAgainstController(true);
						}
						break;
				}
				switch ($data[2]) {
					case 0:
						if ($session->getMatchAgainstMouse() !== false) {
							if ($session->getMatchAgainstController() === false and $session->getMatchAgainstTouch() === false) {
								$this->matchmakingSettings($player);
								return;
							} elseif ($session->getInputMode(true) === 'Mouse') {
								$player->sendMessage(TextFormat::RED . 'You can\'t opt-out of queueing against players with the same controls as you.');
								$this->matchmakingSettings($player);
								return;
							}
							$session->setMatchAgainstMouse(false);
						}
						break;
					case 1:
						if ($session->getMatchAgainstMouse() !== true) {
							$session->setMatchAgainstMouse(true);
						}
						break;
				}
				switch ($data[3]) {
					case 0:
						$val = 'unrestricted';
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 1:
						$val = 25;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 2:
						$val = 50;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 3:
						$val = 75;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 4:
						$val = 100;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 5:
						$val = 125;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
					case 6:
						$val = 150;
						if ($session->getPingRange() !== $val) {
							$session->setPingRange($val);
						}
						break;
				}
				$this->matchmakingSettings($player);
			} else {
				$this->settings($player);
			}
		});
		$form->setTitle('Matchmaking Settings');
		$form->addToggle('Queue Against Touch Players', $session->getMatchAgainstTouch());
		$form->addToggle('Queue Against Controller Players', $session->getMatchAgainstController());
		$form->addToggle('Queue Against Mouse/Keyboard Players', $session->getMatchAgainstMouse());
		switch ($session->getPingRange()) {
			case '25':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 1);
				break;
			case '50':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 2);
				break;
			case '75':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 3);
				break;
			case '100':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 4);
				break;
			case '125':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 5);
				break;
			case '150':
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 6);
				break;
			default:
				$form->addStepSlider('Ping Range', [TextFormat::RED . 'Unrestricted', '25', '50', '75', '100', '125', '150'], 0);
				break;
		}
		$player->sendForm($form);

	}

	public function privacySettings(User|Player $player): void
	{
		if (!$player instanceof User) {
			return;
		}
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				switch ($data[0]) {
					case 0:
						if ($session->isPrivateMessages() !== true) {
							$session->setPrivateMessages(true);
						}
						break;
					case 1:
						if ($session->isPrivateMessages() !== false) {
							$session->setPrivateMessages(false);
							$session->setMessenger();
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->isShowMyStats() !== true) {
							$session->setShowMyStats(true);
						}
						break;
					case 1:
						if ($session->isShowMyStats() !== false) {
							$session->setShowMyStats(false);
						}
						break;
				}
				switch ($data[2]) {
					case 0:
						if ($session->isDuelRequests() !== true) {
							$session->setDuelRequests(true);
						}
						break;
					case 1:
						if ($session->isDuelRequests() !== false) {
							$session->setDuelRequests(false);
						}
						break;
				}
				$this->privacySettings($player);
			} else {
				$this->settings($player);
			}
		});
		$form->setTitle('Privacy Settings');
		switch ($session->isPrivateMessages()) {
			case false:
				$form->addDropdown('Allow others to private message me:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Allow others to private message me:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isShowMyStats()) {
			case false:
				$form->addDropdown('Allow others to view my stats:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Allow others to view my stats:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		switch ($session->isDuelRequests()) {
			case false:
				$form->addDropdown('Allow others to send me duel requests:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 1);
				break;
			default:
				$form->addDropdown('Allow others to send me duel requests:', Variables::BASIC_SETTINGS_CHOICES_ARRAY, 0);
				break;
		}
		$player->sendForm($form);
	}

	public function cosmetics(User|Player $player): void
	{
		$session = Base::getInstance()->sessionManager->getSession($player);
		$form = new CustomForm(function (User|Player $player, $data = null) use ($session): void {
			if (!is_null($data)) {
				switch ($data[0]) {
					case 0:
						$cape = 'none';
						if ($session->getCapeSelected() !== $cape) {
							$session->setCapeSelected($cape);
							$session->removeCape();
						}
						break;
					case 1:
						$cape = 'vasar series 1';
						if ($session->getCapeSelected() !== $cape) {
							$session->setCapeSelected($cape);
							$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
							$session->setCape($cape);
						}
						break;
					case 2:
						$cape = 'vasar series 2';
						if ($session->getCapeSelected() !== $cape) {
							$session->setCapeSelected($cape);
							$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
							$session->setCape($cape);
						}
						break;
					case 3:
						$cape = 'portal';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 4:
						$cape = 'spicy';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 5:
						$cape = 'happy';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus higher to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 6:
						$cape = 'sad';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 7:
						$cape = 'fold';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 8:
						$cape = 'optifine red';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 9:
						$cape = 'optifine pink';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 10:
						$cape = 'optifine cyan';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 11:
						$cape = 'optifine green';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 12:
						$cape = 'optifine dark';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 13:
						$cape = 'vlone';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 14:
						$cape = 'crow';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 15:
						$cape = 'ak';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 16:
						$cape = 'evil';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 17:
						$cape = 'drag';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 18:
						$cape = 'bland';
						if ($session->getCapeSelected() !== $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 19:
						$cape = 'pumpkin';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 20:
						$cape = 'wave';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
					case 21:
						$cape = 'cba';
						if ($session->getCapeSelected() != $cape) {
							if ($session->hasRank(RankManager::PLUS) or Server::getInstance()->isOp($player->getName())) {
								$session->setCapeSelected($cape);
								$cape = Base::getInstance()->utils->createImage($cape, Utils::IMAGE_TYPE_CAPE);
								$session->setCape($cape);
							} else {
								$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
								return;
							}
						}
						break;
				}
				switch ($data[1]) {
					case 0:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'default';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 1:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'red';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 2:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'orange';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 3:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'yellow';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 4:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'green';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 5:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'aqua';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 6:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'blue';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 7:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'pink';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 8:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'white';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 9:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'gray';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
					case 10:
						if ($session->hasRank(RankManager::PLUS)) {
							$color = 'black';
							if ($session->getSplashColorSelected() != $color) {
								$session->setSplashColorSelected($color);
							}
						} else {
							$player->sendMessage(TextFormat::RED . 'You require Vasar Plus to access this cosmetic, please refer to ' . Variables::STORE . '.');
						}
						break;
				}
				$this->cosmetics($player);
			} else {
				$this->settings($player);
			}
		});
		$capes = ['None', 'Vasar Series 1', 'Vasar Series 2', 'Portal', 'Spicy', 'Happy', 'Sad', 'Fold', 'Optifine Red', 'Optifine Pink', 'Optifine Cyan', 'Optifine Green', 'Optifine Dark', 'Vlone', 'Crow', 'AK', 'Evil', 'Drag', 'Bland', 'Pumpkin', 'Wave', 'CBA'];
		$splashColors = ['Default', TextFormat::RED . 'Red', TextFormat::GOLD . 'Orange', TextFormat::YELLOW . 'Yellow', TextFormat::GREEN . 'Green', TextFormat::AQUA . 'Aqua', TextFormat::BLUE . 'Blue', TextFormat::LIGHT_PURPLE . 'Pink', TextFormat::WHITE . 'White', TextFormat::GRAY . 'Gray', TextFormat::DARK_GRAY . 'Black'];
		$form->setTitle('Advanced');
		switch ($session->getCapeSelected()) {
			case 'vasar series 1':
				$form->addDropdown('Cape:', $capes, 1);
				break;
			case 'vasar series 2':
				$form->addDropdown('Cape:', $capes, 2);
				break;
			case 'portal':
				$form->addDropdown('Cape:', $capes, 3);
				break;
			case 'spicy':
				$form->addDropdown('Cape:', $capes, 4);
				break;
			case 'happy':
				$form->addDropdown('Cape:', $capes, 5);
				break;
			case 'sad':
				$form->addDropdown('Cape:', $capes, 6);
				break;
			case 'fold':
				$form->addDropdown('Cape:', $capes, 7);
				break;
			case 'optifine red':
				$form->addDropdown('Cape:', $capes, 8);
				break;
			case 'optifine pink':
				$form->addDropdown('Cape:', $capes, 9);
				break;
			case 'optifine cyan':
				$form->addDropdown('Cape:', $capes, 10);
				break;
			case 'optifine green':
				$form->addDropdown('Cape:', $capes, 11);
				break;
			case 'optifine dark':
				$form->addDropdown('Cape:', $capes, 12);
				break;
			case 'vlone':
				$form->addDropdown('Cape:', $capes, 13);
				break;
			case 'crow':
				$form->addDropdown('Cape:', $capes, 14);
				break;
			case 'ak':
				$form->addDropdown('Cape:', $capes, 15);
				break;
			case 'evil':
				$form->addDropdown('Cape:', $capes, 16);
				break;
			case 'drag':
				$form->addDropdown('Cape:', $capes, 17);
				break;
			case 'bland':
				$form->addDropdown('Cape:', $capes, 18);
				break;
			case 'pumpkin':
				$form->addDropdown('Cape:', $capes, 19);
				break;
			case 'wave':
				$form->addDropdown('Cape:', $capes, 20);
				break;
			case 'cba':
				$form->addDropdown('Cape:', $capes, 21);
				break;
			default:
				$form->addDropdown('Cape:', $capes, 0);
				break;
		}
		switch ($session->getSplashColorSelected()) {
			case 'red':
				$form->addDropdown('Potion Splash Color:', $splashColors, 1);
				break;
			case 'orange':
				$form->addDropdown('Potion Splash Color:', $splashColors, 2);
				break;
			case 'yellow':
				$form->addDropdown('Potion Splash Color:', $splashColors, 3);
				break;
			case 'green':
				$form->addDropdown('Potion Splash Color:', $splashColors, 4);
				break;
			case 'aqua':
				$form->addDropdown('Potion Splash Color:', $splashColors, 5);
				break;
			case 'blue':
				$form->addDropdown('Potion Splash Color:', $splashColors, 6);
				break;
			case 'pink':
				$form->addDropdown('Potion Splash Color:', $splashColors, 7);
				break;
			case 'white':
				$form->addDropdown('Potion Splash Color:', $splashColors, 8);
				break;
			case 'gray':
				$form->addDropdown('Potion Splash Color:', $splashColors, 9);
				break;
			case 'black':
				$form->addDropdown('Potion Splash Color:', $splashColors, 10);
				break;
			default:
				$form->addDropdown('Potion Splash Color:', $splashColors, 0);
				break;
		}
		$player->sendForm($form);
	}

	public function disguise(User|Player $player, ?Session $session = null): void
	{
		$instance = Base::getInstance();

		if (is_null($session)) {
			$session = $instance->sessionManager->getSession($player);
		}

		$form = new SimpleForm(function (User|Player $player, $data = null) use ($instance, $session): void {
			if (!is_null($data)) {
				switch ($data) {
					case 'disable':
						$session->setDisguise();
						break;
					case 'random':
						$array = $instance->utils->getDisguiseNames();
						$disguise = $array[array_rand($array)];
						$this->disguiseRank($player, $disguise, $session);
						break;
					default:
						$this->disguiseRank($player, $data);
						break;
				}
			}
		});
		$form->setTitle('Disguise');
		if ($session->isDisguised()) {
			$form->addButton(TextFormat::RED . 'Leave Disguise', -1, '', 'disable');
		}
		$form->addButton(TextFormat::LIGHT_PURPLE . 'Random', -1, '', 'random');
		foreach ($instance->utils->getDisguiseNames() as $disguise) {
			$form->addButton($disguise, -1, '', $disguise);
		}
		$player->sendForm($form);
	}

	public function disguiseRank(User|Player $player, string $disguise, ?Session $session = null): void
	{
		$instance = Base::getInstance();

		if (is_null($session)) {
			$session = $instance->sessionManager->getSession($player);
		}

		$form = new SimpleForm(function (User|Player $player, $data = null) use ($disguise, $instance, $session): void {
			if (!is_null($data)) {
				foreach (Server::getInstance()->getOnlinePlayers() as $online) {
					if ($online->getDisplayName() === $disguise or $online->getName() === $disguise) {
						$player->sendMessage(TextFormat::RED . 'You can\'t use that disguise at the moment.');
						return;
					}
				}

				if ($data === 'random') {
					$array = [RankManager::PLUS => RankManager::PLUS, RankManager::NITRO, RankManager::DEFAULT];
					$rand = array_rand($array);
					$rank = $array[$rand];
				} else {
					$rank = $data;
				}

				$session->setDisguise($disguise, $rank);
			}
		});
		$form->setTitle('Choose a Rank');
		$form->addButton('My Rank', -1, '', $session->getHighestRank());
		$form->addButton(TextFormat::LIGHT_PURPLE . 'Random', -1, '', 'random');
		$form->addButton(TextFormat::DARK_BLUE . 'Vasar Plus', -1, '', RankManager::PLUS);
		$form->addButton(TextFormat::GOLD . 'Nitro', -1, '', RankManager::NITRO);
		$form->addButton('Default', -1, '', RankManager::DEFAULT);
		$player->sendForm($form);
	}
}