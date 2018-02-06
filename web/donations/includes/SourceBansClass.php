<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}
require_once ABSDIR . "includes/GroupsClass.php";
require_once ABSDIR . 'scripts/rcon_code.php';

class sb extends groups {

    public $sdb;
    public $ddb;

    public function __construct() {

        try {
            $this->sdb = new PDO('mysql:host=' . SB_HOST . ';dbname=' . SOURCEBANS_DB . ';charset=utf8', SB_USER, SB_PASS);
            $this->sdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->sdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $ex) {
            throw new Exception("Unable to connect to Sourcebans " . $ex->getMessage());
        }

        try {
            $this->ddb = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DONATIONS_DB . ';charset=utf8', DB_USER, DB_PASS);
            $this->ddb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //$this->ddb->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $this->ddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $ex) {
            throw new Exception("Unable to connect to database " . $ex->getMessage());
        }
    }

    public function queryServers($query) {
        if (DEBUG) {
            return true;
        }
        $result = $this->sdb->query("SELECT * FROM sb_servers");
        while ($server = $result->fetch(PDO::FETCH_ASSOC)) {
            $srcds_rcon = new srcds_rcon();
            $OUTPUT = $srcds_rcon->rcon_command($server['ip'], $server['port'], $server['rcon'], $query);
        }
        return true;
    }

    /**
     *
     * @param type $steam_id
     * @return type
     * @throws Exception
     */
    public function getUserGroup($steam_id) {// code works good
        try {
            $stmt = $this->sdb->prepare("SELECT * FROM " . SB_PREFIX . "_admins WHERE `authid` =?;");
            $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                throw new Exception(sprintf("%s, is not in any valid groups.", $steam_id));
            }
            $aid = $rows[0]['aid'];
            $srv_group = $rows[0]['srv_group'];
        } catch (Exception $ex) {
            throw new Exception(sprintf('There was a problem fetching infomation from MySQL server: %s', $ex->getMessage()));
        }

        unset($stmt);
        unset($rows);
        try {
            $stmt = $this->sdb->prepare("SELECT * FROM " . SB_PREFIX . "_admins_servers_groups WHERE `admin_id` =?;");
            $stmt->bindParam(1, $aid, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);
            $group_id = $rows['group_id'];
            $srv_group_id = $rows['srv_group_id'];
            $server_id = $rows['server_id'];
        } catch (Exception $ex) {
            throw new Exception(printf('There was a problem fetching infomation from MySQL server: %s', $ex->getMessage()));
        }
        $this->userGroup = array('srv_group' => $srv_group, 'group_id' => $group_id, 'srv_group_id' => $srv_group_id, 'server_id' => $server_id, 'aid' => $aid);
        return $this->userGroup;
    }

    /**
     *
     * @param type $steam_id
     * @param type $username
     * @param type $tier
     * @return boolean
     * @throws Exception
     */
    public function addDonor($steam_id, $username, $tier) {
//check sourcebans database to see if user is already in there

        $steam_id = str_replace(array("/", "'"), "", $steam_id);


        try {
            $stmt = $this->sdb->prepare("SELECT `aid` FROM " . SB_PREFIX . "_admins WHERE authid=?;");
            $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            throw new Exception(printf('There was a problem fetching infomation from MySQL server: %s', $ex->getMessage()));
        }
        unset($stmt);

        if (isset($rows['aid']) && !empty($rows['aid'])) {

            throw new Exception("user is already in the Sourcebans database, Aborting.");
        } else {
//if not, PUT EM IN!


            $group = $this->getGroupInfo($tier);
            try {
                $stmt = $this->sdb->prepare("INSERT INTO `" . SOURCEBANS_DB . "` . `" . SB_PREFIX . "_admins` (user,authid,password,gid,extraflags,immunity,srv_group) VALUES (:username, :steam_id, :sb_pw , '-1' , '0' , '0', :group);");
                $vals = array(':username' => $username, ':steam_id' => $steam_id, ':sb_pw' => '1fcc1a43dfb4a474abb925f54e65f426e932b59e', ':group' => $group['name']);
                $stmt->execute($vals);
                $admin_id = $this->sdb->lastInsertId();
            } catch (Exception $ex) {
                throw new Exception(printf("<div class='alert alert-danger' role='alert'>There was a problem inserting into the Sourcebans admins database: %s</div>", $ex->getMessage()));
            }

            try {
                $stmt = $this->sdb->prepare("INSERT INTO `" . SOURCEBANS_DB . "` . `" . SB_PREFIX . "_admins_servers_groups` (admin_id,group_id,srv_group_id,server_id) VALUES(:admin_id,:group_id,:srv_group_id,:server_id);");
                $stmt->execute(array(':admin_id' => $admin_id, ':group_id' => $group['group_id'], ':srv_group_id' => $group['srv_group_id'], ':server_id' => $group['server_id']));
            } catch (Exception $ex) {
                throw new Exception(printf("<div class='alert alert-danger' role='alert'>There was a problem inserting into the Sourcebans admins server groups database: %s</div>", $ex->getMessage()));
            }
        }

        $this->queryServers('sm_reloadadmins');

        return TRUE;
    }

    /**
     *
     * @param type $steam_id
     * @param type $tier
     * @return boolean
     * @throws Exception
     */
    public function removeDonor($steam_id) { //snaggle code
        $userGroup = $this->getUserGroup($steam_id);

        // $groupList = $this->listGroups();
        // $grpLen = count($groupList);
        // $i = 1;
        // foreach ($groupList as $groups) {
        //     if ($userGroup['group_id'] == $groups['group_id']) {
        //         $i++;
        //     }
        // }
        // if ($i != $grpLen) {
        //     throw new Exception($steam_id . " is in a different sourcebans group.");
        // }
        try {
            $stmt = $this->sdb->prepare("DELETE FROM `" . SOURCEBANS_DB . "`.`" . SB_PREFIX . "_admins` WHERE authid =:steam_id");
            $stmt->bindValue(':steam_id', $steam_id, PDO::PARAM_STR);
            $stmt->execute();
            $affected_rows = $stmt->rowCount();

            if ($affected_rows == 1) {
                unset($affected_rows);

                $stmt = $this->ddb->prepare("DELETE FROM `" . SOURCEBANS_DB . "`.`" . SB_PREFIX . "_admins_servers_groups` WHERE admin_id =:aid");
                $stmt->bindValue(':aid', $userGroup['aid'], PDO::PARAM_STR);
                $stmt->execute();
                $affected_rows = $stmt->rowCount();
            } else {
                throw new Exception('Failed deleting from donor from database. ');
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }

        $this->queryServers('sm_reloadadmins');

        return TRUE;
    }

}
