<?php

declare(strict_types=1);

namespace AndreasHGK\EasyKits\importer;

use AndreasHGK\EasyKits\DataManager;
use AndreasHGK\EasyKits\EasyKits;
use AndreasHGK\EasyKits\Kit;
use AndreasHGK\EasyKits\KitManager;
use Infernus101\KitUI\Main;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Server;

class KitUIImporter{

    public static $kitPlugin;

    public static function ImportAll(): array
    {
        $kp = self::getKitPlugin();
        if(!self::isPluginLoaded()) return [];
        $return = [];
        foreach($kp->kits as $name => $kit){
            $return[$name] = self::Import($kit);
        }
        KitManager::saveAll();
        return $return;
    }

    //a big thanks to AdvancedKits for no $kit->getItems() method
    public static function Import(\Infernus101\KitUI\Kit $akit): bool
    {
        $name = $akit->name;
        if(KitManager::exists($name)) return false;
        $data = $akit->data;

        $price = $akit->cost;
        $cooldown = $akit->timer * 60;

        $items = [];
        foreach($data["items"] as $key => $itemString){
            $expl = explode(":", $itemString);
            $items[$key] = self::loadItem((int)array_shift($expl), (int)array_shift($expl), (int)array_shift($expl), array_shift($expl) ?? "default", ...$expl);
        }

        $armor = [];

        if(isset($data["helmet"])) {
            $expl = explode(":", $data["helmet"]);
            $armor[3] = self::loadItem((int)array_shift($expl), (int)array_shift($expl), (int)array_shift($expl), array_shift($expl) ?? "default", ...$expl);
        }
        if(isset($data["chestplate"])) {
            $expl = explode(":", $data["chestplate"]);
            $armor[2] = self::loadItem((int)array_shift($expl), (int)array_shift($expl), (int)array_shift($expl), array_shift($expl) ?? "default", ...$expl);
        }
        if(isset($data["leggings"])) {
            $expl = explode(":", $data["leggings"]);
            $armor[1] = self::loadItem((int)array_shift($expl), (int)array_shift($expl), (int)array_shift($expl), array_shift($expl) ?? "default", ...$expl);
        }
        if(isset($data["boots"])) {
            $expl = explode(":", $data["boots"]);
            $armor[0] = self::loadItem((int)array_shift($expl), (int)array_shift($expl), (int)array_shift($expl), array_shift($expl) ?? "default", ...$expl);
        }

        $kit = new Kit($name, $price, $cooldown, $items, $armor);

        $default = DataManager::getKey(DataManager::CONFIG, "default-flags");
        $kit->setLocked($default["locked"]);
        $kit->setEmptyOnClaim($default["emptyOnClaim"]);
        $kit->setDoOverride($default["doOverride"]);
        $kit->setDoOverrideArmor($default["doOverrideArmor"]);
        $kit->setAlwaysClaim($default["alwaysClaim"]);

        KitManager::add($kit, true);
        return true;
    }

    public static function loadItem(int $id = 0, int $damage = 0, int $count = 1, string $name = "default", ...$enchantments): Item{
        $item = Item::get($id, $damage, $count);
        if(strtolower($name) !== "default"){
            $item->setCustomName($name);
        }
        $enchantment = null;
        foreach($enchantments as $key => $name_level){
            if($key % 2 === 0){ //Name expected
                $enchantment = Enchantment::getEnchantmentByName((string)$name_level);

            }elseif($enchantment !== null){

                $item->addEnchantment(new EnchantmentInstance($enchantment, (int)$name_level));
            }
        }

        return $item;
    }


    public static function getKitPlugin(): ?Main
    {
        if(!isset(self::$kitPlugin)) self::$kitPlugin = Server::getInstance()->getPluginManager()->getPlugin("KitUI");
        $pl = self::$kitPlugin;
        return $pl instanceof Main ? $pl : null;
    }

    public static function isPluginLoaded() : bool {
        return self::getKitPlugin() !== null;
    }

    private function __construct()
    {
    }

}