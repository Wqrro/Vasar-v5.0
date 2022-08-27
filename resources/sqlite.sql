-- #!sqlite
-- #{ vasar

-- #    { init.stats
CREATE TABLE IF NOT EXISTS Stats(
    player VARCHAR(16) NOT NULL,
    playtime INT UNSIGNED DEFAULT 0,
    kills INT UNSIGNED DEFAULT 0,
    deaths INT UNSIGNED DEFAULT 0,
    killstreak INT UNSIGNED DEFAULT 0,
    bestkillstreak INT UNSIGNED DEFAULT 0,
    PRIMARY KEY(Player));
-- #    }

-- #    { register.player.stats
-- #    :player string
-- #    :playtime int
-- #    :kills int
-- #    :deaths int
-- #    :killstreak int
-- #    :bestkillstreak int
INSERT OR REPLACE INTO Stats(player, playtime, kills, deaths, killstreak, bestkillstreak) VALUES (:player, :playtime, :kills, :deaths, :killstreak, :bestkillstreak);
-- #    }

-- #    { check.stats
-- #    :player string
SELECT player FROM Stats WHERE player=:player;
-- #    }

-- #    { set.stats
-- #    :player string
-- #    :playtime int
-- #    :kills int
-- #    :deaths int
-- #    :killstreak int
-- #    :bestkillstreak int
INSERT OR REPLACE INTO Stats(player, playtime, kills, deaths, killstreak, bestkillstreak) VALUES (:player, :playtime, :kills, :deaths, :killstreak, :bestkillstreak);
-- #    }

-- #    { get.stats
-- #    :player string
SELECT * FROM Stats WHERE player=:player;
-- #    }

-- #    { top.kills
-- #    :desc int
SELECT player, kills FROM Stats ORDER BY kills DESC LIMIT :desc;
-- #    }

-- #    { top.bestkillstreak
-- #    :desc int
SELECT player, bestkillstreak FROM Stats ORDER BY bestkillstreak DESC LIMIT :desc;
-- #    }

-- #}