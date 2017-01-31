<?php
namespace Muqsit\XenForo;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as TF;

class XenForo extends PluginBase {

    private $config, $database;
    public function onEnable()
    {
        if(!file_exists($this->getDataFolder() . 'config.yml')) {
            @mkdir($this->getDataFolder());
            file_put_contents($this->getDataFolder() . 'config.yml', $this->getResource('config.yml'));
        }
        $this->config = yaml_parse_file($this->getDataFolder().'config.yml');

        $db = $this->config["database"];
        $this->database = new Database($db["ip"], $db["user"], $db["password"], $db["database"], $db["port"]);
    }

    public function onCommand(CommandSender $issuer, Command $cmd, $label, array $args)
    {
        switch (strtolower($cmd->getName())) {
            case 'unsync':
                if ($this->database["sync-groups"]) $this->unsyncRankWithForum($issuer);
                else $issuer->sendMessage(TF::RED.'This command has been disabled.');
                break;
            case 'myinfo':
                if ($this->database["self-info"]) {
                    $id = $this->database->getIdByIGN($issuer->getName());
                    if ($id !== -1) $this->database->feedUserData($id, $issuer);
                    else $issuer->sendMessage(TF::YELLOW.'/syncaccount '.TF::GRAY.'[forum e-mail] [forum password]'.PHP_EOL.TF::GRAY.'Link your minecraft character to your ' . $this->config["link"] . ' account.');
                } else $issuer->sendMessage(TF::RED.'This command has been disabled.');
                break;
            case 'sync':
                if (!isset($args[0], $args[1])) {
                    $issuer->sendMessage(TF::RED.'/syncaccount '.TF::GRAY.'[forum e-mail] [forum password]'.PHP_EOL.TF::GRAY.'Link your in-game account with our forum.');
                } else {
                    $issuer->sendMessage(TF::AQUA.'We are attempting to sync your account...');
                    if ($this->database->authenticate($args[0], $args[1])) {
                        if ($this->database->setUserOnForum($args[0], strtolower($issuer->getName()))) {
                            $username = $this->database->getUsernameByEmail($args[0]);
                            $issuer->sendMessage(TF::GREEN.'Your account '.TF::AQUA.$issuer->getName().TF::GREEN.' has been synced to your forum account ('.TF::AQUA.$username.TF::GREEN.')!');
                            $issuer->sendMessage(TF::GRAY.'You can use '.TF::YELLOW.'/myinfo '.TF::GRAY.'to access your forum information and '.TF::YELLOW.'/resync '.TF::GRAY.'to re-synchronize your title on the forum.');
                        } else {
                            $realname = $this->database->getUserOnForum($args[0]);
                            $issuer->sendMessage(TF::RED.'Your account is already synced to the website account..'.PHP_EOL.TF::GRAY.$realname);
                            $issuer->sendMessage(TF::GRAY.'Use '.TF::RED.'/unsync '.TF::GRAY.'to unlink your account from '.$realname.' before trying to link a new account.');
                        }
                    } else {
                        $issuer->sendMessage(TF::RED."You entered an incorrect username/password. If you don't have a forum account create one today at".PHP_EOL.TF::GOLD.$this->config["link"]);
                        break;
                    }
                }
                break;
        }
    }
}
