<?php


namespace hcf\util;


use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class Utils
{
    public const THIN_TAG = TextFormat::ESCAPE . "　";

    public static function toThin(string $string): string
    {
        return preg_replace("/%*(([a-z0-9_]+\.)+[a-z0-9_]+)/i", "%$1", $string) . self::THIN_TAG;
    }

    public static function getRandomVector() : Vector3
    {
        $x = mt_rand()/mt_getrandmax() * 2 - 1;
        $y = mt_rand()/mt_getrandmax() * 2 - 1;
        $z = mt_rand()/mt_getrandmax() * 2 - 1;
        $v = new Vector3($x, $y, $z);
        return $v->normalize();
    }
}