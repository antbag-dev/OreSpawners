<?php

declare(strict_types=1);

namespace antbag\orespawners;

use pocketmine\block\Block;
use pocketmine\tile\Tile;
use pocketmine\level\sound\FizzSound;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat as TF;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;

use antbag\orespawners\Main;

class EventListener implements Listener
{

    /**
     * @var Main
     */
    private $plugin;

    private $cfg;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->cfg = $this->plugin->getConfig()->getAll();
    }

    public function onBlockUpdate(BlockUpdateEvent $event)
    {
        $block = $event->getBlock();
        $bbelow = $block->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];

        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }

        if (in_array($bbelow->getId(), $blocks)) {
        $tile = $event->getBlock()->getWorld()->getTile($bbelow);
        $ore = $this->checkBlock($bbelow);
        $delay = $this->getDelay($bbelow);
        if (!$event->isCancelled()) {
        $event->cancel();
        if ($event->getBlock()->getBlockTypeId() == $ore->getBlockTypeId()) return;
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick)use ($event, $ore): void {
        if ($event->getBlock()->getWorld() !== null) {
        $event->getBlock()->getWorld()->getPosition()->setBlock($event->getBlock()->floor(), $ore, false, true);
                        //if ($this->cfg["fizz-sound"] == true) $event->getBlock()->getLevel()->addSound(new FizzSound($event->getBlock()->asVector3()));
                    }
                }), intval($delay));
            }
        }
    }

    public function checkBlock(Block $bbelow)
    {
        $bbid = $bbelow->getId();
        $coalid = intval($this->cfg["ore-generator-blocks"]["coal"]);
        $ironid = intval($this->cfg["ore-generator-blocks"]["iron"]);
        $goldid = intval($this->cfg["ore-generator-blocks"]["gold"]);
        $diamondid = intval($this->cfg["ore-generator-blocks"]["diamond"]);
        $emeraldid = intval($this->cfg["ore-generator-blocks"]["emerald"]);
        $lapizid = intval($this->cfg["ore-generator-blocks"]["lapis"]);
        $redstoneid = intval($this->cfg["ore-generator-blocks"]["redstone"]);
        switch ($bbid) {
            case $coalid:
                $ore = VanillaBlocks::COAL_ORE()->asItem();
                break;
            case $ironid:
                $ore = VanillaBlocks::IRON_ORE()->asItem();
                break;
            case $goldid:
                $ore = VanillaBlocks::GOLD_ORE()->asItem();
                break;
            case $diamondid:
                $ore = VanillaBlocks::DIAMOND_ORE()->asItem();
                break;
            case $emeraldid:
                $ore = VanillaBlocks::EMERALD_ORE()->asItem();
                break;
            case $lapizid:
                $ore = VanillaBlocks::LAPIZ_ORE()->asItem();
                break;
            case $redstoneid:
                $ore = VanillaBlocks::REDSTONE_ORE()->asItem();
                break;
        }
        if (isset($ore)) {
            return $ore;
        }
        return false;
    }

    public function getDelay(Block $block)
    {
        $tile = $block->getWorld()->getTile($block->asVector3());
        $stacked = $tile->getData("stacked")->getValue();
        $base = intval($this->cfg["base-delay"]);
        return ($base / $stacked) * 20;
    }

    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $item = $event->getItem();
        $blocks = [];

        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }

        if (in_array($block->getId(), $blocks)) {
            if ($item->getNamedTag()->hasTag("orespawner")) {
                $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock()->asVector3());
                
            }
        }
        
        $block = $event->getBlock();
        $bbelow = $block->getWorld()->getPosition()->getBlock($event->getBlock()->floor()->down(1));
        if ($this->checkBlock($bbelow)) {
            $event->cancel();
            $event->getPlayer()->sendMessage(Tf::RED . "You can not place blocks over an OreSpawner!");
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): bool
    {
        if ($this->cfg["stacking"] == false || $event->isCancelled()) return false;
        $item = $event->getItem();
        $player = $event->getPlayer();
        $blocks = [];
        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }
        if (in_array($event->getBlock()->getId(), $blocks)) {
            $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
                if (!$player->getGamemode() == 1) {
                    $stacked = $tile->getData("stacked")->getValue();
                    if (in_array($item->getId(), $blocks) && $item->getNamedTag()->hasTag("orespawner")) {
                        if ($event->getBlock()->getId() == $item->getId()) {
                            if (!($stacked >= intval($this->cfg["max"]))) {
                                $event->cancel();
                                $tile->setData("stacked", $stacked + 1);
                                $item->setCount($item->getCount() - 1);
                                $player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
                                $player->sendMessage(str_replace("&", "§", $this->cfg["gen-added"] ?? "&aSuccessfully stacked a orespawner"));
                                return true;
                            }
                            $player->sendMessage(str_replace("&", "§", $this->cfg["limit-reached"] ?? "&cYou can't stack anymore orespawners, you have reached the limit"));
                            return false;
                        }
                        $player->sendMessage("§cPlease hold the right type of OreSpawner to stack");
                        return false;
                    }
                    $player->sendMessage("§aThere are currently " . TF::YELLOW . $stacked . " §astacked OreSpawners");
                    return false;
                }
                $player->sendMessage(TF::RED . "You can only using stacking system in Survival.");
                return false;
            }
            return false;
        }
        return false;
    }

    public function getTile(Vector3 $pos): ?Tile
    {
        return $this->getTileAt((int)floor($pos->x), (int)floor($pos->y), (int)floor($pos->z));
    }

    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $bbelow = $block->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];
        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }
        if ($event->isCancelled()) return;
        if (in_array($event->getBlock()->getId(), $blocks)) {
            $tile = $event->getBlock()->getLevel()->getTile($block);
            $tile = $player->getLevel()->getTile($block->asVector3());
            $type = $this->checkSpawner($block);
            $count = $tile instanceof SimpleTile ? $tile->getData("stacked")->getValue() : 1;
            $orespawner = $this->plugin->createOreSpawner($type, $count);
            $drops = array();
            $drops[] = $orespawner;
            $event->setDrops($drops);
        } else if (in_array($bbelow->getId(), $blocks)) {
            if ($this->cfg["drop-xp"] == false) {
                $event->setXpDropAmount(0);
            }
        }
    }

    public function checkSpawner(Block $bbelow)
    {
        $bbid = $bbelow->getId();
        $coalid = intval($this->cfg["ore-generator-blocks"]["coal"]);
        $ironid = intval($this->cfg["ore-generator-blocks"]["iron"]);
        $goldid = intval($this->cfg["ore-generator-blocks"]["gold"]);
        $diamondid = intval($this->cfg["ore-generator-blocks"]["diamond"]);
        $emeraldid = intval($this->cfg["ore-generator-blocks"]["emerald"]);
        $lapizid = intval($this->cfg["ore-generator-blocks"]["lapis"]);
        $redstoneid = intval($this->cfg["ore-generator-blocks"]["redstone"]);
        switch ($bbid) {
            case $coalid:
                $ore = "coal";
                break;
            case $ironid:
                $ore = "iron";
                break;
            case $goldid:
                $ore = "gold";
                break;
            case $diamondid:
                $ore = "diamond";
                break;
            case $emeraldid:
                $ore = "emerald";
                break;
            case $lapizid:
                $ore = "lapis";
                break;
            case $redstoneid:
                $ore = "redstone";
                break;
        }
        if (isset($ore)) {
            return $ore;
        }
        return false;
    }
}
