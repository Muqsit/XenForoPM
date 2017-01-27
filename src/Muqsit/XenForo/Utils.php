<?php
namespace Muqsit\XenForo;

class Utils {

    private $database;
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
    * Gets a string list of user's secondary
    * group. Example output:
    * "5,2,6,8"
    */
    public function getSecondaryGroups(int $userId)
    {
        $details = $this->database->query("SELECT secondary_group_ids FROM xf_user WHERE user_id=$userId");
        $details = $details->fetch_assoc();
        return $details["secondary_group_ids"];
    }

    /**
    * Add a user to a secondary group.
    * You need to specify the secondary
    * ID.
    */
    public function addToSecondaryGroup(int $userId, int $groupId)
    {
        $currently = $this->getSecondaryGroups($userId);
        if (isset($currently[0])) {
            $exploded = explode(',',$currently);//No, $currently is a string with commas. Eg: "4,3,8".
            foreach ($exploded as $val) {
                if ($val == $groupId) return;
            }
            $groupId = $currently.','.$groupId;
        }
        $this->database->query("UPDATE xf_user SET secondary_group_ids='$groupId' WHERE user_id=$userId");
    }

    /**
    * Erases user's current secondary groups
    * and overwrites it with $group.
    * $group can be an int or a string of
    * integers imploded with a comma.
    * $group examples:
    *
    * 7
    *
    * "4,8,9,11,2"
    */
    public function setSecondaryGroup(int $userId, $group)
    {
        $this->database->query("UPDATE xf_user SET secondary_group_ids='$group' WHERE user_id=$userId");
    }

   /**
    * Removes user from an int[] of secondary
    * group ids.
    * $groups example:
    * [2, 7, 4, 6, 9];
    */
   public function removeFromGroups(int $userId, array $groups)
    {
        foreach ($groups as $grpId) {
            $this->removeFromSecondaryGroup($userId, $grpId);
        }
    }

    /**
    * Removes a user from a secondary
    * group (id).
    */
    public function removeFromSecondaryGroup(int $userId, int $group)
    {
        $groups = $this->getSecondaryGroups($userId);
        $groups = explode(',',$groups);
        if (!empty($groups)) {
            foreach ($groups as $k => $val) {
                if ($val == $group) {
                    unset($groups[$k]);
                    break;
                }
            }
        }
        $groups = implode(',',$groups);
        $this->setSecondaryGroup($userId, $groups);
    }

    /**
    * Check if user has his birthday today.
    * @return bool
    */
    public function hasBirthdayToday(int $userId) : bool
    {
        $details = $this->database->query("SELECT dob_day, dob_month, dob_year FROM xf_user_profile WHERE user_id=$userId");
        $details = $details
->fetch_assoc();
        return date('Y') === $details["dob_year"] && date('m') === $details["dob_month"] && date('d') === $details["dob_day"];
    }
}
