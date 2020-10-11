<?php

namespace hcf\watchdog\task;

use hcf\HCF;
use hcf\HCFPlayer;
use JsonException;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class ProxyCheckTask extends AsyncTask
{

    public const URL = "http://v2.api.iphub.info/ip/{ADDRESS}";

    /** @var string */
    private $player;

    /** @var string */
    private $address;

    /** @var string */
    private $key;

    /**
     * ProxyCheckTask constructor.
     *
     * @param string $player
     * @param string $address
     * @param string $key
     */
    public function __construct(string $player, string $address, string $key)
    {
        $this->player = $player;
        $this->address = $address;
        $this->key = $key;
        HCF::getInstance()->getLogger()->notice("Unknown ip detected in $player, checking for a vpn or proxy now.");
    }

    /**
     * @throws JsonException
     */
    public function onRun(): void
    {
        $url = str_replace("{ADDRESS}", $this->address, self::URL);
        $get = Internet::getURL($url, 10, ["X-Key: $this->key"]);
        if ($get === false) {
            $this->setResult($get);
            return;
        }
        $get = json_decode($get, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($get)) {
            $this->setResult(false);
            return;
        }
        $result = $get["block"];
        $this->setResult($result);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server): void
    {
        $player = $server->getPlayer($this->player);
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $result = $this->getResult();
        switch ($result) {
            case 0:
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(:uuid, :username, :ipAddress, :riskLevel);");
                $stmt->bindParam("uuid", $uuid);
                $stmt->bindParam("username", $this->player);
                $stmt->bindParam("ipAddress", $this->address);
                $stmt->bindParam("riskLevel", $result);
                $stmt->execute();
                $stmt->closeCursor();
                break;
            case 1:
                $server->getLogger()->warning("A malicious ip swapper was detected in {$this->player}.");
                $uuid = $player->getUniqueId()->toString();
                $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(:uuid, :username, :ipAddress, :riskLevel);");
                $stmt->bindParam("uuid", $uuid);
                $stmt->bindParam("username", $this->player);
                $stmt->bindParam("ipAddress", $this->address);
                $stmt->bindParam("riskLevel", $result);
                $stmt->execute();
                $stmt->closeCursor();
                if (!$player instanceof HCFPlayer) {
                    return;
                }
                $player->close(null, TextFormat::RED . "A malicious ip swapper was detected!");
                break;
            case 2:
                $uuid = $player->getUniqueId()->toString();
                $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO ipAddress(uuid, username, ipAddress, riskLevel) VALUES(:uuid, :username, :ipAddress, :riskLevel);");
                $stmt->bindParam("uuid", $uuid);
                $stmt->bindParam("username", $this->player);
                $stmt->bindParam("ipAddress", $this->address);
                $stmt->bindParam("riskLevel", $result);
                $stmt->execute();
                $stmt->closeCursor();
                $server->getLogger()->info("No malicious ip swapper was detected in {$this->player} but could potentially be using one.");
                break;
            default:
                $server->getLogger()->warning("Error in checking {$this->player}'s proxy.");
                if (!$player instanceof HCFPlayer) {
                    return;
                }
                $player->close(null, TextFormat::RED . "An ip check was conducted and had failed. Please rejoin to complete this check.");
        }
    }
}