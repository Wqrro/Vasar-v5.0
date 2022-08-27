# **Vasar v5.0**

_**Incomplete and entirely hardcoded. For PocketMine 4.X.X.**_

Many thanks to Prim for plenty of help over the years which basically formed this plugin. Check out
his [GitHub page](https://github.com/Prim69) or find him on Discord at prim#0419.

My last contribution to Vasar, the final PocketMine plugin. I wrote this plugin in a very specific way, for a very
specific server, as 1 person. Meaning everything exists for a reason, and everything is placed or done a certain way for
a reason. Even the smallest changes can break this plugin or void all the small details. I planned to have duels, and an
automated 1v1 tournament (aka Clash), before each could be completed I decided to leave Vasar.

### **Core features:**

- Anti-cheat (Jarvis)
	- High CPS detection
	- Bare minimum reach detection
- Ranks
	- Can be set as permanent or temporary
	- Support for players to have more than 1 rank
- Cooldowns
	- Smart combat-tag
	- Ender Pearl cooldown, in addition to other item/entity cooldowns
- Custom Knockback
	- Height limiter
	- Horizontal knockback and vertical knockback
- Support for multiple FFA arenas
	- Features, gameplay, and PvP can be unique to each
- SQLite support for basic server specific statistics
	- i.e., kills, deaths
- YAML support for extensive and very dynamic server specific data
	- i.e., selected cape, selected potion splash color
- MYSQL support for network wide ranks, mutes and bans (blacklist is incomplete)
- Custom Ender Pearls and Splash Potions, in addition to other items/entities
- Various options and few cosmetics for players to choose from

### **Other features:**

- Various commands
	- Most notably, `/nick`, `/disguise`, `/vanish`, `/who`, `/alias`, `/rank`, `/mute`, `/ban`
- Automated announcements, "Note"
	- Will broadcast a message to all players online at a set interval
- Nick (command)
	- Alter your displayed username
- Disguise (command)
	- Hide your identity by using Vasar's default skin and altering your displayed username, due to PM4 limitations
	  there are ways to be compromised
- Players will be set to AFK if movement hasn't occurred for longer than a set amount of seconds in FFA
	- Will change their score-tag to "Away", also prevents any entity collisions with this player
- Clash, an automated 1v1 tournament activated by player activity (incomplete)

### **Requirements:**

- Required plugins
	- **DEVirion** by **poggit** (https://poggit.pmmp.io/p/devirion)
- Required virions
	- **DiscordWebhookAPI** by **CortexPE** (https://poggit.pmmp.io/ci/CortexPE/DiscordWebhookAPI/DiscordWebhookAPI)
	- **InvMenu** by **Muqsit** (https://poggit.pmmp.io/ci/Muqsit/InvMenu/InvMenu)
	- **libasynql** by **poggit** (https://poggit.pmmp.io/ci/poggit/libasynql/libasynql)
- PHP 8+
- A brain

### **Extras:**

These are the maps I used for Vasar, each map was created by me apart from the spawn.

Spawn: https://www.mediafire.com/file/lhifzblm1ofvbg0/Vasar_Greek_Style_Lobby.zip/file

NoDebuff Tetris FFA: https://www.mediafire.com/file/kc3ivmrzjd8a115/vasar_nodebuff_ffa_tetris.zip/file

NoDebuff Plains FFA: https://www.mediafire.com/file/kot6q11rqfoi1gj/vasar_nodebuff_ffa_plains.zip/file

Sumo FFA: https://www.mediafire.com/file/7c3u316blbv0qaf/vasar_sumo_ffa.zip/file

Hive FFA: https://www.mediafire.com/file/1k6cjp98r89xmc7/vasar_hive_ffa.zip/file

Battlefield FFA: https://www.mediafire.com/file/026ke9wnwfyax91/vasar_battlefield_ffa.zip/file

###### **Developed by Warro#7777**
