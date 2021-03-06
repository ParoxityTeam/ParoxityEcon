<?php
declare(strict_types = 1);

namespace Paroxity\ParoxityEcon;

use CortexPE\Commando\PacketHooker;
use Paroxity\ParoxityEcon\Cache\ParoxityEconCache;
use Paroxity\ParoxityEcon\Command\ParoxityEconCommand;
use Paroxity\ParoxityEcon\Database\ParoxityEconDatabase;
use pocketmine\plugin\PluginBase;
use function floatval;

class ParoxityEcon extends PluginBase{

	/** @var self|null */
	private static $instance = null;

	/** @var string */
	private static $MONETARY_UNIT = "$";
	/** @var float */
	private static $MAX_MONEY = 50000000.0;
	/** @var float */
	private static $DEFAULT_MONEY = 1000.0;

	private ParoxityEconDatabase $database;
	private ParoxityEconAPI $api;

	public static function getMonetaryUnit(): string{
		return self::$MONETARY_UNIT;
	}

	public static function getMaxMoney(): float{
		return self::$MAX_MONEY;
	}

	public static function getDefaultMoney(): float{
		return self::$DEFAULT_MONEY;
	}

	public function onLoad(){
		self::$instance = $this;
	}

	public function onEnable(){
		$this->saveDefaultConfig();

		$this->saveResource("data/dummy.txt");

		self::$MONETARY_UNIT = $this->getConfig()->get("unit", "$");
		self::$MAX_MONEY = floatval($this->getConfig()->get("max-money", 50000000.0));
		self::$DEFAULT_MONEY = floatval($this->getConfig()->get("default-money", 1000.0));

		$this->database = new ParoxityEconDatabase($this);
		$this->api = new ParoxityEconAPI($this, $this->database);

		ParoxityEconCache::init($this);

		if(!PacketHooker::isRegistered()){
			PacketHooker::register($this);
		}

		$this->getServer()->getPluginManager()->registerEvents(new ParoxityEconListener($this, $this->database), $this);
		$this->getServer()->getCommandMap()->register("ParoxityEcon", new ParoxityEconCommand($this));
	}

	public static function getInstance(): ?self{
		return self::$instance;
	}

	public function getDatabase(): ParoxityEconDatabase{
		return $this->database;
	}

	public function getAPI(): ParoxityEconAPI{
		return $this->api;
	}

	public function onDisable(){
		self::$instance = null;
	}
}