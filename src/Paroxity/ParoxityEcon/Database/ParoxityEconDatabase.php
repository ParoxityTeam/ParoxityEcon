<?php
declare(strict_types = 1);

namespace Paroxity\ParoxityEcon\Database;

use Paroxity\ParoxityEcon\ParoxityEcon;
use Paroxity\ParoxityEcon\Utils\ParoxityEconQuery;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use function is_null;

final class ParoxityEconDatabase{

	/** @var ParoxityEcon */
	private $engine;
	/** @var libasynql|DataConnector */
	private $connector;

	public function __construct(ParoxityEcon $engine){
		$this->engine = $engine;

		$this->initDatabase();
	}

	private function initDatabase(): void{
		$this->connector = libasynql::create(
			$this->engine,
			$this->engine->getConfig()->get("database"),
			[
				"sqlite" => "stmts/sqlite.sql",
				"mysql"  => "stmts/mysql.sql"
			]
		);

		$this->connector->executeGeneric(ParoxityEconQuery::INIT, [], function(): void{
			$this->engine->getLogger()->debug("Database Initialized.");
		});
	}

	public function getConnector(): DataConnector{
		return $this->connector;
	}

	public function close(): void{
		$this->connector->close();
	}

	/**
	 * @internal External plugins shouldn't be using this!
	 *
	 * @param string $uuid
	 * @param string $username
	 */
	public function register(string $uuid, string $username): void{
		$this->connector->executeInsert(ParoxityEconQuery::REGISTER,
			[
				"uuid"     => $uuid,
				"username" => $username,
				"money"    => ParoxityEcon::$DEFAULT_MONEY,
			],

			function() use ($uuid, $username): void{
				$this->engine->getLogger()->debug("Player: $username with UUID: $uuid successfully registered.");
			},

			// not sure if this ever get thrown but you never know :/
			function(SqlError $error) use ($uuid, $username): void{
				$engine = $this->engine;
				$player = $engine->getServer()->getPlayerExact($username);

				// display warning ig..
				$engine->getLogger()->warning("Player: $username with UUID: $uuid was kicked since he was found online at two places.");

				if(is_null($player) || !$player->isOnline()){
					return;
				}

				$player->kick("You can't be online at two places at once.", false);
			}
		);
	}

	/*
	 * Note:
	 *
	 * $string is either the username or the uuid of the user.
	 * Use uuid when player is online and username when player is offline.
	 */

	public function addMoney(string $string, float $money, bool $isUUID, ?callable $callable = null): void{
		if($isUUID){
			$this->connector->executeChange(ParoxityEconQuery::ADD_BY_UUID,
				[
					"uuid"  => $string,
					"money" => $money,
					"max"   => ParoxityEcon::$MAX_MONEY
				],

				$callable
			);
		}else{
			$this->connector->executeChange(ParoxityEconQuery::ADD_BY_USERNAME,
				[
					"username" => $string,
					"money"    => $money,
					"max"      => ParoxityEcon::$MAX_MONEY
				],

				$callable
			);
		}
	}

	public function deductMoney(string $string, float $money, bool $isUUID, ?callable $callable = null): void{
		if($isUUID){
			$this->connector->executeChange(ParoxityEconQuery::DEDUCT_BY_UUID,
				[
					"uuid"  => $string,
					"money" => $money
				],

				$callable
			);
		}else{
			$this->connector->executeChange(ParoxityEconQuery::DEDUCT_BY_USERNAME,
				[
					"username" => $string,
					"money"    => $money
				],

				$callable
			);
		}
	}

	public function setMoney(string $string, float $money, bool $isUUID, ?callable $callable = null): void{
		if($isUUID){
			$this->connector->executeChange(ParoxityEconQuery::SET_BY_UUID,
				[
					"uuid"  => $string,
					"money" => $money,
					"max"   => ParoxityEcon::$MAX_MONEY
				],

				$callable
			);
		}else{
			$this->connector->executeChange(ParoxityEconQuery::SET_BY_USERNAME,
				[
					"username" => $string,
					"money"    => $money,
					"max"      => ParoxityEcon::$MAX_MONEY
				],

				$callable
			);
		}
	}

	public function getMoney(string $string, bool $isUUID, callable $callable){
		if($isUUID){
			$this->connector->executeSelect(ParoxityEconQuery::GET_BY_UUID, ["uuid" => $string], $callable);
		}else{
			$this->connector->executeSelect(ParoxityEconQuery::GET_BY_USERNAME, ["username" => $string], $callable);
		}
	}

	/**
	 * @see ParoxityEconQuery::GET_TOP_PLAYERS
	 * @see ParoxityEconQuery::GET_TOP_10_PLAYERS
	 *
	 * @param string   $query
	 * @param callable $callable
	 */
	public function getTopPlayers(string $query, callable $callable){
		$this->connector->executeSelect($query, [], $callable);
	}
}