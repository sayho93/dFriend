<?php

include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/Routable.php";

class WebRoute extends Routable {

    function saveStatistic(){
        $userId = AuthUtil::getLoggedInfo()->id == "" ? "0" : AuthUtil::getLoggedInfo()->id;
        $accessIp = $_SERVER['REMOTE_ADDR'].":".$_SERVER['SERVER_PORT'];
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $fbclid = $_REQUEST["fbclid"];

        $ins = "INSERT INTO tblAccessHistory(`userId`, `accessIp`, `fbclid`, `agent`, `regDate`)
                VALUES ('{$userId}', '{$accessIp}', '{$fbclid}', '{$agent}', NOW())";

        $this->update($ins);
    }

    function getProvince(){
        $slt = "SELECT * FROM tblZipSido ORDER BY `orderBy` ASC";
        $ret = $this->getArray($slt);

        return self::response(1, "", $ret);
    }

    function getFaqList(){
        return $this->getArray("SELECT * FROM tblFaq ORDER BY `title` ASC");
    }

    function getNoticeList(){
        $page = $_REQUEST["page"] == "" ? 1 : $_REQUEST["page"];
        $query = $_REQUEST["query"];
        $whereStmt = "1=1 ";
        if($query != ""){
            $whereStmt .= " AND `title` LIKE '%{$query}%'";
        }

        $startLimit = ($page - 1) * 5;
        $slt = "SELECT `id`, `title`, `madeBy`, `filePath`, `uptDate`, `regDate`, `hit`,
                (SELECT `name` FROM tblCustomer WHERE `id`=`madeBy` LIMIT 1) AS madeName 
                FROM tblNotice WHERE {$whereStmt}
                ORDER BY `regDate` DESC LIMIT {$startLimit}, 5";
        return $this->getArray($slt);
    }

    function getTopNotice($cnt){
        $slt = "SELECT `id`, `title`, DATE(`regDate`) AS dt 
                FROM tblNotice WHERE `status`=1
                ORDER BY `regDate` DESC LIMIT {$cnt}";
        return $this->getArray($slt);
    }

    function getIdentityList(){
        $slt = "SELECT * FROM tblIdentity ORDER BY className ASC";
        return $this->getArray($slt);
    }

    function getNotice(){
        $slt = "SELECT *,
                (SELECT `name` FROM tblCustomer WHERE `id`=`madeBy` LIMIT 1) AS madeName
                FROM tblNotice WHERE `id`='{$_REQUEST["id"]}'";
        return $this->getRow($slt);
    }

    function updateHit(){
        $id = $_REQUEST["id"];
        $slt = "SELECT `hit` FROM tblBoard WHERE `id` = '{$id}'";
        $hitVal = $this->getValue($slt, "hit") + 1;
        $upt = "UPDATE tblBoard SET `hit` = '{$hitVal}' WHERE `id` = '{$id}'";
        $this->update($upt);
    }

}
