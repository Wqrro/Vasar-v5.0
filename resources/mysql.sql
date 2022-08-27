-- #!mysql
-- #{ vasar

-- #    { init.mutes
CREATE TABLE IF NOT EXISTS Mutes(Player VARCHAR(16) PRIMARY KEY, occurrence VARCHAR(20), expires INT, staff VARCHAR(20), reason VARCHAR(255));
-- #    }

-- #    { init.bans
CREATE TABLE IF NOT EXISTS Bans(player VARCHAR(16) PRIMARY KEY, occurrence VARCHAR(20), expires INT, staff VARCHAR(20), reason VARCHAR(255));
-- #    }

-- #    { init.blacklists
CREATE TABLE IF NOT EXISTS Blacklists(player VARCHAR(16) PRIMARY KEY, occurrence VARCHAR(20), staff VARCHAR(20), reason VARCHAR(255));
-- #    }

-- #    { init.ranks
CREATE TABLE IF NOT EXISTS RankNames(player VARCHAR(16) PRIMARY KEY, ranks VARCHAR(255) NOT NULL DEFAULT 'Default:0');
-- #    }

-- #    { init.voters
CREATE TABLE IF NOT EXISTS Voters(player VARCHAR(16) PRIMARY KEY, expires INT);
-- #    }

-- #    { get.mutes
SELECT * FROM Mutes;
-- #    }

-- #    { register.mute
-- #    :player string
-- #    :occurrence string
-- #    :expires int
-- #    :staff string
-- #    :reason string
REPLACE INTO Mutes (player, occurrence, expires, staff, reason) VALUES (:player, :occurrence, :expires, :staff, :reason);
-- #    }

-- #    { remove.mute
-- #    :player string
DELETE FROM Mutes where player=:player;
-- #    }

-- #    { get.bans
SELECT * FROM Bans;
-- #    }

-- #    { register.ban
-- #    :player string
-- #    :occurrence string
-- #    :expires int
-- #    :staff string
-- #    :reason string
REPLACE INTO Bans (player, occurrence, expires, staff, reason) VALUES (:player, :occurrence, :expires, :staff, :reason);
-- #    }

-- #    { remove.ban
-- #    :player string
DELETE FROM Bans where player=:player;
-- #    }

-- #    { get.blacklists
SELECT * FROM Blacklists;
-- #    }

-- #    { register.blacklist
-- #    :player string
-- #    :occurrence string
-- #    :staff string
-- #    :reason string
REPLACE INTO Blacklists (player, occurrence, staff, reason) VALUES (:player, :occurrence, :staff, :reason);
-- #    }

-- #    { remove.blacklist
-- #    :player string
DELETE FROM Blacklists where player=:player;
-- #    }









-- #    { register.player.ranks
-- #    :player string
-- #    :ranks string
REPLACE INTO RankNames (player, ranks) VALUES (:player, :ranks);
-- #    }

-- #    { set.ranks
-- #    :player string
-- #    :ranks string
UPDATE RankNames SET ranks=:ranks WHERE player=:player;
-- #    }

-- #    { add.ranks
-- #    :player string
-- #    :ranks string
UPDATE RankNames SET ranks=CONCAT(ranks, :ranks) WHERE player=:player;
-- #    }

-- #    { add.rank
-- #    :player string
-- #    :ranks string
UPDATE RankNames SET ranks=:ranks WHERE player=:player;
-- #    }

-- #    { get.ranks
-- #    :player string
SELECT * FROM RankNames WHERE player=:player;
-- #    }

-- #    { check.ranks
-- #    :player string
SELECT player FROM RankNames WHERE player=:player;
-- #    }

-- #}