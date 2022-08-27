<?php

declare(strict_types=1);

namespace Warro;

interface Variables
{

	public const NAME = 'Vasar';
	public const DOMAIN = 'vasar.land';
	public const DISCORD = 'discord.gg/vasar';
	public const STORE = 'https://store.vasar.land';
	public const VOTE = 'https://vote.vasar.land';

	public const JARVIS_WEBHOOK = 'https://discord.com/api/webhooks/x';
	public const PUNISHMENT_WEBHOOK = 'https://discord.com/api/webhooks/x';
	public const NICK_WEBHOOK = 'https://discord.com/api/webhooks/x';
	public const DISGUISE_WEBHOOK = 'https://discord.com/api/webhooks/x';

	public const BAN_HEADER = '§l§cYou are banned from Vasar§r';
	public const BAN_ALT_HEADER = '§l§cAn account linked to you is banned from Vasar§r';
	public const BLACKLIST_HEADER = '§l§cYou are blacklisted from Vasar§r';
	public const BLACKLIST_ALT_HEADER = '§l§cAn account linked to you is blacklisted from Vasar§r';

	public const SPAWN = 'Vasar_Greek_Style_Lobby'; // https://www.mediafire.com/file/lhifzblm1ofvbg0/Vasar_Greek_Style_Lobby.zip/file
	public const NODEBUFF_FFA_ARENA_TETRIS = 'vasar_nodebuff_ffa_tetris'; // https://www.mediafire.com/file/kc3ivmrzjd8a115/vasar_nodebuff_ffa_tetris.zip/file
	public const NODEBUFF_FFA_ARENA_PLAINS = 'vasar_nodebuff_ffa_plains'; // https://www.mediafire.com/file/kot6q11rqfoi1gj/vasar_nodebuff_ffa_plains.zip/file
	public const SUMO_FFA_ARENA = 'vasar_sumo_ffa'; // https://www.mediafire.com/file/7c3u316blbv0qaf/vasar_sumo_ffa.zip/file
	public const HIVE_FFA_ARENA = 'vasar_hive_ffa'; // https://www.mediafire.com/file/1k6cjp98r89xmc7/vasar_hive_ffa.zip/file
	public const BATTLEFIELD_FFA_ARENA = 'vasar_battlefield_ffa'; // https://www.mediafire.com/file/026ke9wnwfyax91/vasar_battlefield_ffa.zip/file

	public const COMBAT_TAG = 20;
	public const COMBAT_TAG_KILL = 3;
	public const PEARL_COOLDOWN = 300;
	public const RESPAWN_TIMER = 2;
	public const AGRO_TICKS = 20;

	public const LOBBY_ITEM_CLASH = '§r§bComing Soon';
	public const LOBBY_ITEM_FREE_FOR_ALL = '§r§eFree For All';
	public const LOBBY_ITEM_SETTINGS = '§r§dSettings';

	public const BATTLEFIELD_NUKE = 30;

	public const BASIC_SETTINGS_CHOICES_ARRAY = ['§aEnabled', '§cDisabled'];

	public const DAY = 86400;
	public const HOUR = 3600;
	public const MINUTE = 60;

}