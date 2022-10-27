<?php

declare(strict_types=1);

namespace TERAMI\AdvancedSpeed;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Attribute;
use pocketmine\player\Player;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\DefaultPermissionNames;

use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\types\UpdateAbilitiesPacketLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;

class Main extends PluginBase implements Listener
{
	public function onEnable() : void 
	{
		
	}
	
	/**
	* @param CommandSender $sender
	* @param Command       $command
	* @param string        $label
	* @param string[]      $args
	*
	* @return bool
	*/
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
	{
		switch (strtolower($command->getName())) {
			case "speed":
				if (isset($args[0]) && isset($args[1])) {
					if (($amplifier = floatval($args[1])) == 0) {
						$this->sendUsage($sender);
						return false;
					}
					switch ($args[0]) {
						case "ground":
							$this->setGroundSpeed($sender, $amplifier);
							$sender->sendMessage(C::GREEN . "Successfully set your ground speed to " . $amplifier);
							return true;
						case "flight":
							$session = $sender->getNetworkSession();
							$isOp = $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR);
							
							// Not sure if these are necessary. Copied off from PM source.
							// See https://github.com/pmmp/PocketMine-MP/blob/stable/src/network/mcpe/NetworkSession.php#L829
							// See https://github.com/pmmp/BedrockProtocol/blob/master/src/UpdateAbilitiesPacket.php
							// See https://github.com/pmmp/BedrockProtocol/blob/master/src/types/UpdateAbilitiesPacketLayer.php
							$boolAbilities = [
								UpdateAbilitiesPacketLayer::ABILITY_ALLOW_FLIGHT => $sender->getAllowFlight(),
								UpdateAbilitiesPacketLayer::ABILITY_FLYING => $sender->isFlying(),
								UpdateAbilitiesPacketLayer::ABILITY_NO_CLIP => !$sender->hasBlockCollision(),
								UpdateAbilitiesPacketLayer::ABILITY_OPERATOR => $isOp,
								UpdateAbilitiesPacketLayer::ABILITY_TELEPORT => $sender->hasPermission(DefaultPermissionNames::COMMAND_TELEPORT),
								UpdateAbilitiesPacketLayer::ABILITY_INVULNERABLE => $sender->isCreative(),
								UpdateAbilitiesPacketLayer::ABILITY_MUTED => false,
								UpdateAbilitiesPacketLayer::ABILITY_WORLD_BUILDER => false,
								UpdateAbilitiesPacketLayer::ABILITY_INFINITE_RESOURCES => !$sender->hasFiniteResources(),
								UpdateAbilitiesPacketLayer::ABILITY_LIGHTNING => false,
								UpdateAbilitiesPacketLayer::ABILITY_BUILD => !$sender->isSpectator(),
								UpdateAbilitiesPacketLayer::ABILITY_MINE => !$sender->isSpectator(),
								UpdateAbilitiesPacketLayer::ABILITY_DOORS_AND_SWITCHES => !$sender->isSpectator(),
								UpdateAbilitiesPacketLayer::ABILITY_OPEN_CONTAINERS => !$sender->isSpectator(),
								UpdateAbilitiesPacketLayer::ABILITY_ATTACK_PLAYERS => !$sender->isSpectator(),
								UpdateAbilitiesPacketLayer::ABILITY_ATTACK_MOBS => !$sender->isSpectator(),
							];
							
							$session->sendDataPacket(UpdateAbilitiesPacket::create(
								$isOp ? CommandPermissions::OPERATOR : CommandPermissions::NORMAL,
								$isOp ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER,
								$sender->getId(),
								[new UpdateAbilitiesPacketLayer(UpdateAbilitiesPacketLayer::LAYER_BASE, $boolAbilities, $amplifier, 0.1)]
							));
							$sender->sendMessage(C::GREEN . "Successfully set your flight speed to " . $amplifier);
							return true;
						default:
							$this->sendUsage($sender);
					}
				}
				$this->sendUsage($sender);
				return true;
		}
		return true;
	}
	
	/**
	* Gets player's movement speed value. For testing purposes
	*
	* @return int
	**/
	public function getGroundSpeed(Player $player) : int
	{
		$movement = $player->getAttributeMap()->get(Attribute::MOVEMENT_SPEED);
		return $movement->getValue();
	}
	
	/**
	* Sets player's movement speed value
	*
	* @param float $value
	**/
	public function setGroundSpeed(Player $player, float $value) : void
	{
		$movement = $player->getAttributeMap()->get(Attribute::MOVEMENT_SPEED);
		$movement->setValue($value);
	}
	
	public function sendUsage(Player $player)
	{
		$player->sendMessage(C::RED . "You must provide a valid argument: /speed [ground:flight | string] [amplifier | float]");
		$player->sendMessage(C::RED . "Example: /speed ground 2.0");
		$player->sendMessage(C::RED . "Default Values: Ground(0.1), Flight(0.05)");
	}
}
