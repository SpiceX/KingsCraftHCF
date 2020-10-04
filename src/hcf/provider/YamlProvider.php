<?php


namespace hcf\provider;


use hcf\HCF;

class YamlProvider
{
    /**@var HCF */
    private $plugin;

    /**
     * YamlProvider constructor.
     * @param HCF $plugin
     */
    public function __construct(HCF $plugin)
    {
        $this->plugin = $plugin;
        $this->init();
    }

    public function init(): void
    {
        if (!is_dir($this->plugin->getDataFolder() . 'kits')){
            mkdir($this->plugin->getDataFolder() . 'kits');
        }
        if (!is_dir($this->plugin->getDataFolder() . 'crates')){
            mkdir($this->plugin->getDataFolder() . 'crates');
        }
    }
}