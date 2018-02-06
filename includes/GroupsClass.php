<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}

class groups {

    /**
     *
     * @return multidimensional array with all groups and all their data.
     */
    public function listGroups() {
        $i = 0;

        $stmt = $this->ddb->query("SELECT * FROM `groups` WHERE 1");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groupData[$i] = $row;
            $i++;
        }
        if (!empty($groupData)) {
            return $groupData;
        } else {
            return false;
//throw new Exception("No groups found in database");
        }
    }

    /**
     *
     * @param type $tier (int) id in database of group.
     * @return array(9) id,name,group_id,srv_group_id,server_id,multiplier,ccc_enabled,minimum,active
     * @throws Exception
     */
    public function getGroupInfo($tier) {
        try {
            $stmt = $this->ddb->prepare("SELECT * FROM `groups` where `id` =?;");
            $stmt->bindParam(1, $tier, PDO::PARAM_INT);
            $stmt->execute();
            //$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->groupInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($this->groupInfo)) {
                throw new Exception("nothing returned from database");
                var_dump($stmt);
            }
        } catch (Exception $ex) {
            throw new Exception("Failed get group info.. " . $ex->getMessage());
        }
        //$this->groupInfo = $rows[0];
        return $this->groupInfo;
    }

    /**
     *
     * @param type $name
     * @param type $group_id
     * @param type $srv_group_id
     * @param type $server_id
     * @param type $multiplier
     * @param type $ccc_enabled
     * @param type $minimum
     * @return boolean
     * @throws Exception
     */
    public function addGroup($name, $group_id, $srv_group_id, $server_id, $multiplier, $ccc_enabled, $minimum) {
        try {
            $stmt = $this->ddb->prepare("INSERT INTO `groups` (`name`, `group_id`, `srv_group_id`, `server_id`, `multiplier`, `ccc_enabled`, `active`,`minimum`) VALUES (:name,:group_id,:srv_group_id,:server_id,:multiplier,:ccc_enabled, '1',:minimum);");
            $vals = array(':name' => $name, ':group_id' => $group_id, ':srv_group_id' => $srv_group_id, ':server_id' => $server_id, ':multiplier' => $multiplier, ':ccc_enabled' => $ccc_enabled, ':minimum' => $minimum);
            $stmt->execute($vals);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Unable to insert group into database');
            }
        } catch (Exception $ex) {
            throw new Exception("Failed to create group. " . $ex->getMessage());
        }
        return true;
    }

    /**
     *
     * @param type $name
     * @param type $group_id
     * @param type $srv_group_id
     * @param type $server_id
     * @param type $multiplier
     * @param type $ccc_enabled
     * @param type $active
     * @param type $minimum
     * @return boolean
     * @throws Exception
     */
    public function editGroup($name, $group_id, $srv_group_id, $server_id, $multiplier, $ccc_enabled, $active, $minimum) {
        try {
            $stmt = $this->ddb->prepare("UPDATE `groups` SET `name`=:name, `srv_group_id`=:srv_group_id, `server_id`=:server_id, `multiplier`=:multiplier, `ccc_enabled`=:ccc_enabled, `active`=:active,`minimum`=:minimum WHERE `groups`.`group_id`=:group_id;");
            $vals = array(':name' => $name,
                ':srv_group_id' => $srv_group_id,
                ':server_id' => $server_id,
                ':multiplier' => $multiplier,
                ':ccc_enabled' => $ccc_enabled,
                ':active' => $active,
                ':group_id' => $group_id,
                ':minimum' => $minimum);
            $stmt->execute($vals);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Failed to update group.');
            }
        } catch (Exception $ex) {
            throw new Exception("Failed to update group. " . $ex->getMessage());
        }


        return true;
    }

    /**
     *
     * @param type $id
     * @throws Exception
     */
    public function disableGroup($id) {
        try {
            $stmt = $this->ddb->query("UPDATE `groups` SET `active` = '0' WHERE id = :id;");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $ex) {
            throw new Exception("Failed to disable group. " . $ex->getMessage());
        }
    }

}
