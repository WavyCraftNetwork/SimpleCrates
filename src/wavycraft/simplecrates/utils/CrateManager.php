<?php

declare(strict_types=1);

namespace wavycraft\simplecrates\utils;

use pocketmine\player\Player;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TextColor;
use pocketmine\world\Position;
use wavycraft\simplecrates\Loader;
use wavycraft\core\utils\SoundUtils;

final class CrateManager {
    use SingletonTrait;

    private $creatingCrate = [];

    public function isCrateBlock(Block $block) : bool {
        $crateConfig = new Config(Loader::getInstance()->getDataFolder() . "crate_locations.json", Config::JSON);

        foreach ($crateConfig->getAll() as $coordinates) {
            if (
                isset($coordinates["x"], $coordinates["y"], $coordinates["z"], $coordinates["world"]) &&
                $coordinates["x"] === $block->getPosition()->getX() &&
                $coordinates["y"] === $block->getPosition()->getY() &&
                $coordinates["z"] === $block->getPosition()->getZ() &&
                $coordinates["world"] === $block->getPosition()->getWorld()->getFolderName()
            ) {
                return true;
            }
        }
        
        return false;
    }

    public function getCrateTypeByPosition(Position $position): ?string {
        $crateConfig = new Config(Loader::getInstance()->getDataFolder() . "crate_locations.json", Config::JSON);
    
        foreach ($crateConfig->getAll() as $crateType => $coordinates) {
            if (
                isset($coordinates["x"], $coordinates["y"], $coordinates["z"], $coordinates["world"]) &&
                $coordinates["x"] === $position->getX() &&
                $coordinates["y"] === $position->getY() &&
                $coordinates["z"] === $position->getZ() &&
                $coordinates["world"] === $position->getWorld()->getFolderName()
            ) {
                return $coordinates["type"] ?? null;
            }
        }

        return null;
    }

    public function openCrate(Player $player, string $crateType): void {
        $inventory = $player->getInventory();
        $crateKeyFound = false;

        foreach ($inventory->getContents() as $slot => $item) {
            $nbt = $item->getNamedTag();

            if (($nbt->getTag("Key") !== null) && $nbt->getString("Key") === $crateType) {
                if ($item->getCount() > 1) {
                    $item->setCount($item->getCount() - 1);
                    $inventory->setItem($slot, $item);
                } else {
                    $inventory->clear($slot);
                }
                $crateKeyFound = true;
                break;
            }
        }

        if ($crateKeyFound) {
            RewardManager::getInstance()->givePrize($player, $crateType);
            $player->sendMessage("You opened a $crateType crate!");
            SoundUtils::getInstance()->playSound($player, "random.levelup");
        } else {
            $player->sendMessage("You need a $crateType crate key to open this crate!");
            SoundUtils::getInstance()->playSound($player, "note.bass");
        }
    }

    public function startCrateCreation(Player $player, string $type) {
        $crateConfig = Loader::getInstance()->getConfig();
        $crateKeys = $crateConfig->get("crates", []);

        if (!isset($crateKeys[$type])) {
            $player->sendMessage(TextColor::RED . "Crate type '$type' does not exist!");
            return;
        }

        $this->creatingCrate[$player->getName()] = $type;
        $player->sendMessage(TextColor::GREEN . "Right-click or left-click a chest block to create a " . ucfirst($type) . " crate.");
    }

    public function isCreatingCrate(Player $player): bool {
        return isset($this->creatingCrate[$player->getName()]);
    }

    public function finishCrateCreation(Player $player, Position $position) {
        $playerName = $player->getName();
        if (!isset($this->creatingCrate[$playerName])) return;

        $crateType = $this->creatingCrate[$playerName];
        unset($this->creatingCrate[$playerName]);

        $crateConfig = Loader::getInstance()->getConfig();
        $crateKeys = $crateConfig->get("crates", []);
        $floatingText = $crateKeys[$crateType]["crate_floating_text"] ?? "§l§e" . ucfirst($crateType) . " Crate\nYou need a key to open!";

        $crateLocations = new Config(Loader::getInstance()->getDataFolder() . "crate_locations.json", Config::JSON);
        $crateLocations->set($crateType . "_crate", [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "world" => $position->getWorld()->getFolderName(),
            "type" => $crateType
        ]);
        $crateLocations->save();

        FloatingText::create(
            new Position($position->getX() + 0.5, $position->getY() + 1, $position->getZ() + 0.5, $position->getWorld()),
            "{$crateType}_crate_floating_text",
            $floatingText
        );

        $player->sendMessage(TextColor::GREEN . ucfirst($crateType) . " crate created successfully!");
    }
}