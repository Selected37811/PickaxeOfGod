<?php

namespace Pickaxe;

use Pickaxe\Commands\GiveGodPickCommand;
use Pickaxe\Listener\EventListener;
use pocketmine\plugin\PluginBase;
use mysqli;

class Main extends PluginBase {

    public mysqli $db;

    private array $dbCreds = [
        'host' => 'DB_HOST',
        'user' => 'DB_USER',
        'password' => 'DB_PASSWORD',
        'database' => 'DB',
        'port' => DB_PORT,
    ];

    public function onEnable(): void {
        $this->db = new mysqli(
            $this->dbCreds['host'],
            $this->dbCreds['user'],
            $this->dbCreds['password'],
            $this->dbCreds['database'],
            $this->dbCreds['port']
        );

        if ($this->db->connect_error) {
            $this->getLogger()->error("Erreur MySQL : " . $this->db->connect_error);
            $this->getServer()->shutdown();
            return;
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS god_pickaxes (
                uuid VARCHAR(36) PRIMARY KEY,
                blocks_mined INT NOT NULL DEFAULT 0,
                efficiency_level INT NOT NULL DEFAULT 0
            )
        ");

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("pickaxeofgod", new GiveGodPickCommand($this));
        $this->getLogger()->info("§aPickaxeOfGod connecté à MySQL !");
    }
}