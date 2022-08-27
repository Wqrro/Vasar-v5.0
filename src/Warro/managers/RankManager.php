<?php

declare(strict_types=1);

namespace Warro\managers;

class RankManager
{

	private array $ranks = [];

	/*
	 * DONT FORGET TO UPDATE THE STORE IF THE ORDER OF THESE RANKS CHANGE
	 */

	public const DEFAULT = 11;
	public const VOTER = 10;
	public const MEDIA = 9;
	public const FAMOUS = 8;
	public const NITRO = 7;
	public const PLUS = 6;
	public const PARTNER = 5;
	public const TRIAL = 4;
	public const MODERATOR = 3;
	public const ADMINISTRATOR = 2;
	public const MANAGER = 1;
	public const OWNER = 0;

	/*
	 * DONT FORGET TO UPDATE THE STORE IF THE ORDER OF THESE RANKS CHANGE
	 */

	public function __construct()
	{
		$this->ranks [self::DEFAULT] = $this->getRankAsString(self::DEFAULT);
		$this->ranks [self::VOTER] = $this->getRankAsString(self::VOTER);
		$this->ranks [self::MEDIA] = $this->getRankAsString(self::MEDIA);
		$this->ranks [self::FAMOUS] = $this->getRankAsString(self::FAMOUS);
		$this->ranks [self::NITRO] = $this->getRankAsString(self::NITRO);
		$this->ranks [self::PLUS] = $this->getRankAsString(self::PLUS);
		$this->ranks [self::PARTNER] = $this->getRankAsString(self::PARTNER);
		$this->ranks [self::TRIAL] = $this->getRankAsString(self::TRIAL);
		$this->ranks [self::MODERATOR] = $this->getRankAsString(self::MODERATOR);
		$this->ranks [self::ADMINISTRATOR] = $this->getRankAsString(self::ADMINISTRATOR);
		$this->ranks [self::MANAGER] = $this->getRankAsString(self::MANAGER);
		$this->ranks [self::OWNER] = $this->getRankAsString(self::OWNER);
	}

	public function getRanks(): array
	{
		return $this->ranks;
	}

	public function doesRankExist(int $rank): bool
	{
		return isset($this->ranks[$rank]);
	}

	public function getRankFromString(string $rank): ?int
	{
		return match (strtolower($rank)) {
			default => null,
			'default' => RankManager::DEFAULT,
			'voter' => RankManager::VOTER,
			'media' => RankManager::MEDIA,
			'famous' => RankManager::FAMOUS,
			'nitro' => RankManager::NITRO,
			'plus' => RankManager::PLUS,
			'partner' => RankManager::PARTNER,
			'trial' => RankManager::TRIAL,
			'moderator' => RankManager::MODERATOR,
			'administrator' => RankManager::ADMINISTRATOR,
			'manager' => RankManager::MANAGER,
			'owner' => RankManager::OWNER,
		};
	}

	public function getRankAsString(int $rank): ?string
	{
		return match ($rank) {
			default => null,
			RankManager::DEFAULT => 'Default',
			RankManager::VOTER => 'Voter',
			RankManager::MEDIA => 'Media',
			RankManager::FAMOUS => 'Famous',
			RankManager::NITRO => 'Nitro',
			RankManager::PLUS => 'Plus',
			RankManager::PARTNER => 'Partner',
			RankManager::TRIAL => 'Trial',
			RankManager::MODERATOR => 'Moderator',
			RankManager::ADMINISTRATOR => 'Administrator',
			RankManager::MANAGER => 'Manager',
			RankManager::OWNER => 'Owner',
		};
	}
}