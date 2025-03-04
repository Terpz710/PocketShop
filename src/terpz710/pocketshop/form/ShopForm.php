<?php

declare(strict_types=1);

namespace terpz710\pocketshop\form;

use pocketmine\player\Player;

use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;

use pocketmine\utils\Config;

use terpz710\pocketshop\PocketShop;

use terpz710\pocketforms\SimpleForm;
use terpz710\pocketforms\CustomForm;
use terpz710\pocketforms\ModalForm;

use terpz710\mineconomy\Mineconomy;

class ShopForm {

    private Config $config;

    public function __construct(protected PocketShop $plugin) {
        $this->config = new Config($this->plugin->getDataFolder() . "shop.yml", Config::YAML);
    }

    public function openMainShop(Player $player) : void{
        $form = new SimpleForm();
        $form->setTitle("Shop");

        foreach ($this->config->getAll() as $category => $data) {
            $form->addButton($category, 0, $data["image"] ?? "");
        }

        $form->setCallback(function (Player $player, ?int $data) {
            if ($data !== null) {
                $categories = array_keys($this->config->getAll());
                $selectedCategory = $categories[$data] ?? null;
                if ($selectedCategory !== null) {
                    $this->openItemShop($player, $selectedCategory);
                }
            }
        });

        $player->sendForm($form);
    }

    private function openItemShop(Player $player, string $category) : void{
        $items = $this->config->getNested("$category.items", []);
        if (empty($items)) {
            $player->sendMessage("No items available in this category.");
            return;
        }

        $form = new SimpleForm();
        $form->setTitle($category);

        foreach ($items as $item) {
            $form->addButton("{$item["name"]} - ${$item["price"]}", 0, $item["item_image"]);
        }

        $form->setCallback(function (Player $player, ?int $data) use ($category, $items) {
            if ($data !== null) {
                $selectedItem = $items[$data] ?? null;
                if ($selectedItem !== null) {
                    $this->openAmountInputForm($player, $category, $selectedItem);
                }
            }
        });

        $player->sendForm($form);
    }

    private function openAmountInputForm(Player $player, string $category, array $item) : void{
        $form = new CustomForm();
        $form->setTitle("Enter Amount");
        $form->addLabel("Item: {$item["name"]}\nPrice per unit: ${$item["price"]}");
        $form->addInput("Enter quantity", "1", "1");

        $form->setCallback(function (Player $player, ?array $data) use ($category, $item) {
            if ($data !== null && isset($data[1])) {
                $amount = (int) $data[1];
                if ($amount > 0) {
                    $this->openConfirmationForm($player, $category, $item, $amount);
                } else {
                    $player->sendMessage("Invalid amount.");
                }
            }
        });

        $player->sendForm($form);
    }

    private function openConfirmationForm(Player $player, string $category, array $item, int $amount) : void{
        $totalPrice = $item["price"] * $amount;
        $form = new ModalForm();
        $form->setTitle("Confirm Purchase");
        $form->setContent("Are you sure you want to buy {$amount}x {$item["name"]} for ${$totalPrice}?");
        $form->setButton1("Yes");
        $form->setButton2("No");

        $form->setCallback(function (Player $player, ?bool $data) use ($category, $item, $amount, $totalPrice) {
            if ($data) {
                $this->processPurchase($player, $category, $item, $amount, $totalPrice);
            } else {
                $player->sendMessage("Purchase canceled.");
            }
        });

        $player->sendForm($form);
    }

    private function processPurchase(Player $player, string $category, array $item, int $amount, int $totalPrice) : void{
        $economy = Mineconomy::getInstance();
        $balance = $economy->getFunds($player);

        if ($balance === null || $balance < $totalPrice) {
            $player->sendMessage("You don't have enough money.");
            return;
        }

        $economy->removeFunds($player, $totalPrice);

        $itemInstance = StringToItemParser::getInstance()->parse($item["id"]);
        if ($itemInstance instanceof Item) {
            $itemInstance->setCount($amount);
            $player->getInventory()->addItem($itemInstance);
            $player->sendMessage("You bought {$amount}x {$item["name"]} for ${$totalPrice}.");
        } else {
            $player->sendMessage("Failed to process item.");
        }
    }
}