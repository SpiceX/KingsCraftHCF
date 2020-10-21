<?php


namespace hcf\enchant;


use hcf\enchant\task\TickEnchantmentsTask;
use hcf\enchant\types\boots\SpeedEnchant;
use hcf\HCF;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\utils\Config;
use ReflectionProperty;
use SplFixedArray;

class CustomEnchantManager
{
    /** @var CustomEnchant[] */
    private static $enchants;
    /**@var HCF */
    private $plugin;
    /** @var array */
    private $enchantmentData;

    /**
     * CustomEnchantManager constructor.
     * @param HCF $plugin
     */
    public function __construct(HCF $plugin)
    {
        $this->plugin = $plugin;
        $this->init();
    }

    public function init()
    {
        $vanillaEnchantments = new SplFixedArray(1024);

        $property = new ReflectionProperty(Enchantment::class, "enchantments");
        $property->setAccessible(true);
        foreach ($property->getValue() as $key => $value) {
            $vanillaEnchantments[$key] = $value;
        }
        $property->setValue($vanillaEnchantments);
        $this->plugin->getScheduler()->scheduleRepeatingTask(new TickEnchantmentsTask($this->plugin), 1);

        self::registerEnchantment(new SpeedEnchant($this->plugin, CustomEnchantmentIds::SPEED));
    }

    public static function registerEnchantment(CustomEnchant $enchant): void
    {
        Enchantment::registerEnchantment($enchant);
        /** @var CustomEnchant $enchant */
        $enchant = Enchantment::getEnchantment($enchant->getId());
        self::$enchants[$enchant->getId()] = $enchant;

        HCF::getInstance()->getLogger()->debug("Custom Enchantment '" . $enchant->getName() . "' registered with id " . $enchant->getId());
    }

    /**
     * @param string $enchant
     * @param string $data
     * @param int|string|array $default
     * @return mixed
     * @internal
     */
    public function getEnchantmentData(string $enchant, string $data, $default = "")
    {
        if (!isset($this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data])) $this->setEnchantmentData($enchant, $data, $default);
        return $this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data];
    }

    /**
     * @param string $enchant
     * @param string $data
     * @param int|string|array $value
     */
    public function setEnchantmentData(string $enchant, string $data, $value): void
    {
        $this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data] = $value;
        $config = new Config($this->plugin->getDataFolder() . $data . ".json");
        $config->set(str_replace(" ", "", strtolower($enchant)), $value);
        $config->save();
    }

}