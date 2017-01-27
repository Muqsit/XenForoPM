<?php
namespace Muqsit\XenForo;

use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class Database {

    private $database;

    public function __construct($db_ip, string $db_username, string $db_password, $db_database = 'forum', $db_port = 3306)
    {
        Server::getInstance()->getLogger()->info("Connecting to XenForo DB @ ".$db_ip);

        $microtime = microtime(true);
        $this->database = mysqli_connect($db_ip, $db_username, $db_password, $db_database, $db_port);
        if (!$this->database) Server::getInstance()->getLogger()->alert("Failed connecting to XenForo.");
        else {
            $this->query("ALTER TABLE xf_user ADD COLUMN xf_pe VARCHAR(50) NOT NULL DEFAULT ''");
            $microtime = microtime(true) - $microtime;
            Server::getInstance()->getLogger()->notice("Successfully to XenForo as ".$db_username.". (Took ".$microtime."s).");
        }
    }

    /**
    * Close connection.
    */
    public function disconnect()
    {
        mysqli_close($this->database);
    }

    /**
    * Execute/query something.
    */
    public function query($stmt)
    {
        return $this->database->query($stmt);
    }

    /**
    * Return a user's XenForo-unhashed
    * and salted password.
    * @return string
    */
    public function getPasswordHash($userId) : string
    {
        $password = $this->database->query("SELECT data FROM xf_user_authenticate WHERE user_id=$userId");
        $password = $password->fetch_assoc();
        return $password["data"];
    }

    /**
    * Get a user's ID with the help of their
    * email address.
    * Returns -1 if email wasn't found, or
    * the user's id if the email was found.
    * @return int
    */
    public function getIdByEmail(string $email) : int
    {
        $details = $this->database->query("SELECT user_id FROM xf_user WHERE email='$email'");
        $details = $details->fetch_assoc();
        if ($details === null) return -1;
        return $details["user_id"];
    }

    /**
    * Sends $player information (of their
    * XenForo account). /myinfo.
    */
    public function feedUserData(int $userid, $player)
    {
        $details = $this->database->query("SELECT * FROM xf_user WHERE user_id=$userid");
        $details = $details->fetch_assoc();
        $details2 = $this->database->query("SELECT * FROM xf_user_profile WHERE user_id=$userid");
        $details2 = $details2->fetch_assoc();

        if ($details === null) return -1;
        $picked = [$details["username"], $details["email"], $details["gender"], $details["conversations_unread"], $details["like_count"], ($details2["dob_day"].'/'.$details2["dob_month"].'/'.$details2["dob_year"])];
        $cherrypicked = array_combine(["Username", "E-mail", "Gender", "Unread Messages", "Likes Received", "Date Of Birth (DD/MM/YYYY)"], $picked);

        foreach ($cherrypicked as $k => $v) {
            $player->sendMessage(TF::GREEN.$k.TF::AQUA.': '.TF::GREEN.$v);
        }
    }

    /**
    * Get a user's ID with the help of their
    * in-game username.
    * Returns -1 if user wasn't found, or the
    * user's id if the user was found.
    * @return int
    */
    public function getIdByIGN(string $username)
    {
        $username = strtolower($username);
        $details = $this->database->query("SELECT user_id FROM xf_user WHERE xf_pe='$username'");
        $details = $details->fetch_assoc();
        if ($details === null) return -1;
        return $details["user_id"];
    }

    /**
    * Returns user's username with the
    * help of their email address.
    * @return string
    */
    public function getUsernameByEmail(string $email)
    {
        $details = $this->database->query("SELECT username FROM xf_user WHERE email='$email'");
        $details = $details->fetch_assoc();
        return $details["username"];
    }

    /**
    * Returns user's email with the help of
    * their user id.
    * @return string
    */
    public function getEmailById(int $id) : string
    {
        $details = $this->database->query("SELECT email FROM xf_user WHERE user_id=$id");
        $details = $details->fetch_assoc();
        return $details["email"];
    }

    /**
    * Authenticate a user using their XenForo
    * email and password. Returns true only
    * if the correct combination of email and
    * password was provided.
    * @return bool
    */
    public function authenticate(string $email, string $password) : bool
    {
        $id = $this->getIdByEmail($email);
        if ($id === -1) return false;
        $passwordhash = unserialize($this->getPasswordHash($id))["hash"];
        return (password_verify($password, $passwordhash));
    }

    /**
    * Returns true if player has not synced
    * already. Returns false if a player had
    * already synced the account.
    * @return bool
    */
    public function setUserOnForum(string $email, string $username) : bool
    {
        if ($this->isAlreadyAuthenticated($email)) return false;
        else {
            $this->database->query("UPDATE xf_user SET xf_,pe = '$username' WHERE email='$email'");
            return true;
        }
    }

    public function getUserOnForum(string $email)
    {
        if ($this->isAlreadyAuthenticated($email)) {
            $data = $this->database->query("SELECT username FROM xf_user WHERE email='$email'");
            $data = $data->fetch_assoc();
            return $data["username"];
        } else {
            $data = $this->database->query("SELECT xf_pe FROM xf_user WHERE email='$email'");
            $data = $data->fetch_assoc();
            return $data["xf_pe"];
        }
    }

    /**
    * Chage the "sync-er" of an account having
    * email as $email to $to...
    * where $to is the strtolower(username) of
    * "sync-er" you want the account changed
    * to.
    */
    public function changeUserOnForum(string $email, string $to)
    {
        $this->database->query("UPDATE xf_user SET xf_pe = '$to' WHERE email='$email'");
    }

    /**
    * Removes the sync-er of an account.
    * This can be done in-game using
    * /unsync.
    */
    public function unsetUserOnForum(string $email)
    {
        $this->changeUserOnForum($email, "");
    }

    /**
    * Check if user has already synced their
    * information using /sync.
    * @return bool
    */
    public function isAlreadyAuthenticated(string $email) : bool
    {
        $details = $this->database->query("SELECT xf_pe FROM xf_user WHERE email='$email'");
        $details = $details->fetch_assoc();
        return $details["xf_pe"] !== "";
    }
}
