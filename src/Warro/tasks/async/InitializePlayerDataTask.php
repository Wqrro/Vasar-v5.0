<?php

declare(strict_types=1);

namespace Warro\tasks\async;

use pocketmine\scheduler\AsyncTask;
use Warro\managers\SessionManager;

class InitializePlayerDataTask extends AsyncTask
{

	public function __construct(private string $path)
	{
	}

	public function onRun(): void
	{
		if (!file_exists($this->path)) {
			$data = array(
				'cape-selected' => SessionManager::CAPE_SELECTED,
				'splash-color-selected' => SessionManager::SPLASH_COLOR_SELECTED,
				'scoreboard' => SessionManager::SCOREBOARD,
				'lightning' => SessionManager::LIGHTNING,
				'particle-splashes' => SessionManager::PARTICLE_SPLASHES,
				'auto-sprint' => SessionManager::AUTO_SPRINT,
				'auto-rekit' => SessionManager::AUTO_REKIT,
				'cps-counter' => SessionManager::CPS_COUNTER,
				'dms' => SessionManager::PRIVATE_MESSAGES,
				'duel-requests' => SessionManager::DUEL_REQUESTS,
				'anti-interfere' => SessionManager::ANTI_INTERFERENCE,
				'anti-clutter' => SessionManager::ANTI_CLUTTER,
				'instant-respawn' => SessionManager::INSTANT_RESPAWN,
				'show-my-stats' => SessionManager::SHOW_MY_STATS,
				'ping-range' => SessionManager::PING_RANGE,
				'match-against-touch' => SessionManager::MATCH_AGAINST_TOUCH,
				'match-against-mouse' => SessionManager::MATCH_AGAINST_MOUSE,
				'match-against-controller' => SessionManager::MATCH_AGAINST_CONTROLLER);
			yaml_emit_file($this->path, $data);
		} else {
			$data = yaml_parse_file($this->path);
			$edit = false;
			if (!isset($data['cape-selected'])) {
				$data['cape-selected'] = SessionManager::CAPE_SELECTED;
				$edit = true;
			}
			if (!isset($data['splash-color-selected'])) {
				$data['splash-color-selected'] = SessionManager::SPLASH_COLOR_SELECTED;
				$edit = true;
			}
			if (!isset($data['scoreboard'])) {
				$data['scoreboard'] = SessionManager::SCOREBOARD;
				$edit = true;
			}
			if (!isset($data['lightning'])) {
				$data['lightning'] = SessionManager::LIGHTNING;
				$edit = true;
			}
			if (!isset($data['particle-splashes'])) {
				$data['particle-splashes'] = SessionManager::PARTICLE_SPLASHES;
				$edit = true;
			}
			if (!isset($data['auto-sprint'])) {
				$data['auto-sprint'] = SessionManager::AUTO_SPRINT;
				$edit = true;
			}
			if (!isset($data['auto-rekit'])) {
				$data['auto-rekit'] = SessionManager::AUTO_REKIT;
				$edit = true;
			}
			if (!isset($data['cps-counter'])) {
				$data['cps-counter'] = SessionManager::CPS_COUNTER;
				$edit = true;
			}
			if (!isset($data['dms'])) {
				$data['dms'] = SessionManager::PRIVATE_MESSAGES;
				$edit = true;
			}
			if (!isset($data['duel-requests'])) {
				$data['duel-requests'] = SessionManager::DUEL_REQUESTS;
				$edit = true;
			}
			if (!isset($data['anti-interfere'])) {
				$data['anti-interfere'] = SessionManager::ANTI_INTERFERENCE;
				$edit = true;
			}
			if (!isset($data['anti-clutter'])) {
				$data['anti-clutter'] = SessionManager::ANTI_CLUTTER;
				$edit = true;
			}
			if (!isset($data['instant-respawn'])) {
				$data['instant-respawn'] = SessionManager::INSTANT_RESPAWN;
				$edit = true;
			}
			if (!isset($data['show-my-stats'])) {
				$data['show-my-stats'] = SessionManager::SHOW_MY_STATS;
				$edit = true;
			}
			if (!isset($data['ping-range'])) {
				$data['ping-range'] = SessionManager::PING_RANGE;
				$edit = true;
			}
			if (!isset($data['match-against-touch'])) {
				$data['match-against-touch'] = SessionManager::MATCH_AGAINST_TOUCH;
				$edit = true;
			}
			if (!isset($data['match-against-mouse'])) {
				$data['match-against-mouse'] = SessionManager::MATCH_AGAINST_MOUSE;
				$edit = true;
			}
			if (!isset($data['match-against-controller'])) {
				$data['match-against-controller'] = SessionManager::MATCH_AGAINST_CONTROLLER;
				$edit = true;
			}
			if ($edit === true) {
				yaml_emit_file($this->path, $data);
			}
		}
	}
}