<?php

namespace hcf\provider;

use hcf\HCF;
use mysqli;

class MySQLProvider
{

    /** @var HCF */
    private $core;

    /** @var mysqli */
    private $database;

    /**
     * MySQLProvider constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $this->core->saveDefaultConfig();
        $this->database = new mysqli("na02-db.cus.mc-panel.net", "db_104947", "dcdce90156", "db_104947");
        $this->init();
    }

    public function init(): void
    {
        $this->database->query("CREATE TABLE IF NOT EXISTS players(uuid VARCHAR(36) PRIMARY KEY, username VARCHAR(16), faction VARCHAR(16) DEFAULT NULL, factionRole TINYINT DEFAULT NULL, balance BIGINT DEFAULT 0, groupId TINYINT DEFAULT 0, permissions VARCHAR(255) DEFAULT '', tags VARCHAR(255) DEFAULT '', currentTag VARCHAR(150) DEFAULT '', invincibilityTime INT DEFAULT 3600, lives INT DEFAULT 0, deathBanTime INT DEFAULT NULL, reclaim TINYINT DEFAULT 0, kills SMALLINT DEFAULT 0);");
        $this->database->query("CREATE TABLE IF NOT EXISTS shops(x SMALLINT, y SMALLINT, z SMALLINT, level VARCHAR(30), item BLOB, type TINYINT, price INT);");
        $this->database->query("CREATE TABLE IF NOT EXISTS ipAddress(uuid VARCHAR(36), username VARCHAR(16), ipAddress VARCHAR(20), riskLevel TINYINT);");
        $this->database->query("CREATE TABLE IF NOT EXISTS bans(uuid VARCHAR(36) PRIMARY KEY, username VARCHAR(16), effector VARCHAR(16) NOT NULL, reason VARCHAR(200) NOT NULL, expiration INT DEFAULT NULL);");
        $this->database->query("CREATE TABLE IF NOT EXISTS mutes(uuid VARCHAR(36) PRIMARY KEY, username VARCHAR(16), effector VARCHAR(16) NOT NULL, reason VARCHAR(200) NOT NULL, expiration INT NOT NULL);");
        $this->database->query("CREATE TABLE IF NOT EXISTS kitCooldowns(uuid VARCHAR(36) PRIMARY KEY NOT NULL, username VARCHAR(16) NOT NULL, food INT DEFAULT 0 NOT NULL, starter INT DEFAULT 0 NOT NULL, archer INT DEFAULT 0 NOT NULL, bard INT DEFAULT 0 NOT NULL, builder INT DEFAULT 0 NOT NULL, miner INT DEFAULT 0 NOT NULL, astro INT DEFAULT 0 NOT NULL, kinghero INT DEFAULT 0 NOT NULL, legend INT DEFAULT 0 NOT NULL, revenant INT DEFAULT 0 NOT NULL, rogue INT DEFAULT 0 NOT NULL, diamond INT DEFAULT 0 NOT NULL);");
        $this->database->query("CREATE TABLE IF NOT EXISTS factions(name VARCHAR(30) NOT NULL, x SMALLINT DEFAULT NULL, y SMALLINT DEFAULT NULL, z SMALLINT DEFAULT NULL, minX SMALLINT DEFAULT NULL, minZ SMALLINT DEFAULT NULL, maxX SMALLINT DEFAULT NULL, maxZ SMALLINT DEFAULT NULL, level VARCHAR(30) DEFAULT NULL, members VARCHAR(200) NOT NULL, allies VARCHAR(200) DEFAULT NULL, balance BIGINT DEFAULT 0 NOT NULL, dtr DOUBLE DEFAULT 1 NOT NULL);");
        $this->database->query("CREATE TABLE IF NOT EXISTS wayPoints(uuid VARCHAR(36) NOT NULL, username VARCHAR(16) NOT NULL, name VARCHAR(16) NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, z SMALLINT NOT NULL, level VARCHAR(30) NOT NULL);");
    }

    /**
     * @return mysqli
     */
    public function getDatabase(): mysqli
    {
        return $this->database;
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF
    {
        return $this->core;
    }
}