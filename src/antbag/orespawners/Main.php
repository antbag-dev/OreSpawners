<?php

declare(strict_types=1);

class Main extends PluginBase {
  
  public const VERSION = 5;
  
  private $cfg;
  
  /**
   *Disables the plugin if config version is below const VERSION.
   *
   * @return void
   */
  
  public function onEnable(): void {
    $this->cfg = $this->getConfig()->getAll();
    
    if($this->cfg["version"] < self::VERSION) {
      
    }
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
  }
  
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
      if($conmand->getName() === "orespawner") {
        if(!$sender->hasPermission("orespawner.command.give")) {
          $sender->sendMessage(TF::RED . "You do not have permission to use this command");
          return false;
        }
      }
      
      if(!isset($args[0])) {
        $sender->sendMessage(TF::RED . "You must provide some arguements!");
    } else if (isset($args[2]) && !$this->getServer()->getPlayer($args[2])) {
        $sender->sendMessage(TF::RED . "You must provide a valid player!");
        return false;
    } else if (!isset($args[0]) || !in_array(strtolower($args[0]), $typesArray)) {
        $sender->sendMessage(TF::RED . "You must enter a valid ore type!");
        return false;
    } else if (isset($args[1]) && !is_numeric($args[1])) {
        $sender->sendMessage(TF::RED . "You must provide a valid amount!");
        return false;
    }
    $player = isset($args[2]) ? $this->getServer()->getPlayer($args[2]) : $sender;
    if (!$player instanceof Player) return false;
    $ore = strtolower($args[0]);
    $amount = isset($args[1]) ? intval($args[1]) : 1;
    $orespa = $this->createOreSpawner($ore, $amount);
    $player->getInventory()->addItem($orespa);
    return true;
  }
}

public function createOreSpawner(string $ore, int  $amount) {
  
}