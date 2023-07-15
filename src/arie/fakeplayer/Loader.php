<?php
/**
 * This file is part of FakePlayer
 *
 * @author Arie
 * @link   https://github.com/Arie
 * @license https://opensource.org/licenses/MIT MIT License
 *
 * •.,¸,.•*`•.,¸¸,.•*¯ ╭━━━━━━━━━━━━╮
 * •.,¸,.•*¯`•.,¸,.•*¯.|:::::::/\___/\
 * •.,¸,.•*¯`•.,¸,.•* <|:::::::(｡ ●ω●｡)
 * •.,¸,.•¯•.,¸,.•╰ *  し------し---Ｊ
 *
 */
declare(strict_types=1);

namespace arie\fakeplayer;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Loader extends PluginBase{
    use SingletonTrait;

    /** @var array|int[] */
    private array $inventoryBorderSlots = [
         0,  1,  2,  3,  4,  5,  6,  7,  8,
        45, 46, 47, 48, 49, 50, 51, 52, 53
    ];

    public function onLoad() : void{
        self::setInstance($this);
        foreach ($this->getResources() as $resource) {
            $this->saveResource($resource->getFilename());
        }
    }

    public function onEnable() : void{
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        EntityFactory::getInstance()->register(FakePlayer::class, function(World $world, CompoundTag $nbt) : FakePlayer{
            return new FakePlayer(Helper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['FakePlayer']);
        $manager = $this->getServer()->getPluginManager();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() === "entity") {
            if (!$sender instanceof Player) {
                $this->getLogger()->notice("Please run this command in-game.");
                return false;
            }
            $location = $sender->getLocation();
            $skin = $sender->getSkin();
            (new FakePlayer($location, $skin))->spawnTo($sender);
        }
        return true;
    }

    public function getInventoryBorderSlots() : array{
        return $this->inventoryBorderSlots;
    }
}