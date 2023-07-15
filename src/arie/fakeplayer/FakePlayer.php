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

use muqsit\invmenu\InvMenu;

use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class FakePlayer extends Human{
    /** @var ?InvMenu */
    private ?InvMenu $invMenu = null;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null) {
        parent::__construct($location, $skin, $nbt);

        $this->setCanSaveWithChunk(true);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        $invMenu = $this->initInvMenu();
        $this->invMenu = $invMenu;
    }

    protected function initInvMenu() : InvMenu{
        $invMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $invMenu->setName("Fake Player");
        foreach (Loader::getInstance()->getInventoryBorderSlots() as $borderSlot) {
            $namedtag = CompoundTag::create()->setInt("border_item", 1);
            $borderItem = VanillaBlocks::IRON_BARS()->asItem()->setNamedTag($namedtag)->setCustomName(" ");
            $invMenu->getInventory()->setItem($borderSlot, $borderItem);
        }
        $contents = $this->getInventory()->getContents(true);
        foreach($contents as $i => $item) {
            // 9 for the first 9 slots... I barely have any ideas else... any built-in array-functions would help a lot
            $invMenu->getInventory()->setItem($i + 9, $item);
        }
        return $invMenu;
    }

    public function saveNBT() : CompoundTag {
        $nbt = parent::saveNBT();
        $test = array_slice($this->invMenu->getInventory()->getContents(true), 9, 36);
        $this->getInventory()->setContents($test);
        return $nbt;
    }

    private function processInteract(Player $player) : void{
        $invMenu = $this->invMenu ?? $this->initInvMenu();

        $invMenu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
            $slot = $transaction->getAction()->getSlot();
            if (in_array($slot, Loader::getInstance()->getInventoryBorderSlots(), true)) {
                return $transaction->discard();
            }
            return $transaction->continue();
        });
        $invMenu->setInventoryCloseListener(function(Player $player, Inventory $inventory) use ($invMenu): void{
            $this->getInventory()->setContents(array_slice($this->invMenu->getInventory()->getContents(true), 9, 36));
        });
        $this->invMenu = $invMenu;
        $invMenu->send($player);
    }

    public function attack(EntityDamageEvent $source) : void{
        if ($source instanceof EntityDamageByEntityEvent) {
            $attacker = $source->getDamager();

            if ($attacker instanceof Player && !($attacker->getInventory()->getItemInHand()->getTypeId() === ItemTypeIds::NETHERITE_SWORD)) {
                $this->processInteract($attacker);
            }
        }
    }

    public function onInteract(Player $player, Vector3 $clickPos) : bool{
        if ($player->getInventory()->getItemInHand()->getTypeId() === ItemTypeIds::NETHERITE_SWORD) {
            $this->kill();
            return false;
        }
        $this->processInteract($player);
        return false;
    }

    private function getInvMenu() : InvMenu{
        return $this->invMenu;
    }
}
