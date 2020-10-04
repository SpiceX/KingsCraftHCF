<?php

namespace hcf\discord;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Embed;
use pocketmine\event\player\PlayerQuitEvent;

class Logger implements Listener {

    private $core;

    public function __construct(HCF $core){
        $this->core = $core;
    }

    }