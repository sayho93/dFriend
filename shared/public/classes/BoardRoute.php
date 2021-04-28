<?php

include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/Routable.php";

class BoardRoute extends Routable {

    function likeBoard(){
        $uid = $_REQUEST["userId"];
        $bid = $_REQUEST["boardId"];


        $upt = "INSERT IGNORE INTO tblLike(boardId, userId) VALUES('{$bid}','{$uid}')";
        $this->update($upt);

        $slt = "SELECT COUNT(*) AS cnt FROM tblLike WHERE boardId='{$bid}'";
        $cnt = $this->getValue($slt, "cnt");

        return self::response($cnt, "");
    }

    function unlikeBoard(){
        $uid = $_REQUEST["userId"];
        $bid = $_REQUEST["boardId"];


        $upt = "DELETE FROM tblLike WHERE boardId='{$bid}' AND userId='{$uid}'";
        $this->update($upt);

        $slt = "SELECT COUNT(*) AS cnt FROM tblLike WHERE boardId='{$bid}'";
        $cnt = $this->getValue($slt, "cnt");

        return self::response($cnt, "");
    }

    function getBoard(){
        $id = $_REQUEST["id"];
        $slt = "SELECT * FROM tblBoard WHERE `id`={$id}";
        $ret = $this->getRow($slt);

        $slt = "SELECT * FROM tblFile WHERE `boardId`={$id} ORDER BY `order` ASC";
        $ret["files"] = $this->getArray($slt);

        $retVal = self::response(1, "", $ret);
        return $retVal;
    }

    function getBoardExtra(){
        $id = $_REQUEST["id"];
        $userId = $_REQUEST["userId"] == "" ? 0 : $_REQUEST["userId"];

        $slt = "SELECT 
                B.*, 
                U.id AS authorId, 
                (SELECT path FROM tblFile F WHERE F.`id`=U.profileId LIMIT 1) AS authorProfile, 
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId=B.id LIMIT 1) AS likes,
                (SELECT COUNT(*) > 0 FROM tblLike L WHERE L.boardId=B.id AND L.userId='{$userId}' LIMIT 1) AS liked,
                (SELECT COUNT(*) FROM tblComment C WHERE C.boardId=B.id LIMIT 1) AS comments,
                U.nickname AS authorName
                FROM tblBoard B LEFT JOIN tblUser U ON B.userKey=U.`id` WHERE B.`id`='{$id}'";
        $ret = $this->getRow($slt);

        $slt = "SELECT * FROM tblFile WHERE `boardId`={$id} ORDER BY `order` ASC";
        $ret["files"] = $this->getArray($slt);

        $retVal = self::response(1, "", $ret);
        return $retVal;
    }

    function getBoardExtraList(){
        $type = $_REQUEST["type"];
        $page = $_REQUEST["page"];
        $unit = $_REQUEST["unit"] == "" ? 5 : $_REQUEST["unit"];
        $start = $page * $unit;

        $userId = $_REQUEST["userId"] == "" ? 0 : $_REQUEST["userId"];

        $slt = "SELECT COUNT(*) AS cnt FROM tblBoard WHERE `type`={$type} AND `status`=1";
        $total = $this->getValue($slt, "cnt");
        $totalPage = ceil($total / $unit);

        $slt = "SELECT 
                B.*, 
                U.id AS authorId, 
                (SELECT path FROM tblFile F WHERE F.`id`=U.profileId LIMIT 1) AS authorProfile, 
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId=B.id LIMIT 1) AS likes,
                (SELECT COUNT(*) > 0 FROM tblLike L WHERE L.boardId=B.id AND L.userId='{$userId}' LIMIT 1) AS liked,
                (SELECT COUNT(*) FROM tblComment C WHERE C.boardId=B.id LIMIT 1) AS comments,
                U.nickname AS authorName
                FROM tblBoard B LEFT JOIN tblUser U ON B.userKey=U.`id` WHERE B.`status`=1 AND B.`type`={$type}
                ORDER BY `id` DESC LIMIT {$start}, {$unit}";
        $arr = $this->getArray($slt);

        for ($i = 0; $i < sizeof($arr); $i++){
            $slt = "SELECT * FROM tblFile WHERE `boardId`={$arr[$i]["id"]} ORDER BY `order` DESC";
            $arr[$i]["files"] = $this->getArray($slt);
        }

        $retVal["list"] = json_encode($arr);
        $retVal["totalPage"] = $totalPage."";
        $retVal["total"] = $total."";
        $retVal["page"] = $page."";

        return self::response(1, "", $retVal);
    }

    function getBoardExtraListForFeed(){
        $page = $_REQUEST["page"];
        $unit = $_REQUEST["unit"] == "" ? 5 : $_REQUEST["unit"];
        $start = $page * $unit;

        $userId = $_REQUEST["userId"] == "" ? 0 : $_REQUEST["userId"];

        $slt = "SELECT COUNT(*) AS cnt FROM tblBoard WHERE `status`=1 AND (`type`=0 OR `type`=1)";
        $total = $this->getValue($slt, "cnt");
        $totalPage = ceil($total / $unit);

        $slt = "SELECT 
                B.*, 
                U.id AS authorId, 
                (SELECT path FROM tblFile F WHERE F.`id`=U.profileId LIMIT 1) AS authorProfile, 
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId=B.id LIMIT 1) AS likes,
                (SELECT COUNT(*) > 0 FROM tblLike L WHERE L.boardId=B.id AND L.userId='{$userId}' LIMIT 1) AS liked,
                (SELECT COUNT(*) FROM tblComment C WHERE C.boardId=B.id LIMIT 1) AS comments,
                U.nickname AS authorName
                FROM tblBoard B LEFT JOIN tblUser U ON B.userKey=U.`id` WHERE B.`status`=1 AND (B.`type`=0 OR B.`type`=1)
                ORDER BY `id` DESC LIMIT {$start}, {$unit}";
        $arr = $this->getArray($slt);

        for ($i = 0; $i < sizeof($arr); $i++){
            $slt = "SELECT * FROM tblFile WHERE `boardId`={$arr[$i]["id"]} ORDER BY `order` DESC";
            $arr[$i]["files"] = $this->getArray($slt);
        }

        $retVal["list"] = json_encode($arr);
        $retVal["totalPage"] = $totalPage."";
        $retVal["total"] = $total."";
        $retVal["page"] = $page."";

        return self::response(1, "", $retVal);
    }

    function getBoardList(){
        $type = $_REQUEST["type"];
        $page = $_REQUEST["page"];
        $unit = $_REQUEST["unit"] == "" ? 5 : $_REQUEST["unit"];
        $start = $page * $unit;

        $slt = "SELECT COUNT(*) AS cnt FROM tblBoard WHERE `type`={$type} AND `status`=1";
        $total = $this->getValue($slt, "cnt");
        $totalPage = ceil($total / $unit);

        $slt = "SELECT * FROM tblBoard WHERE `type`={$type} AND `status`=1 ORDER BY `id` DESC LIMIT {$start}, {$unit}";
        $arr = $this->getArray($slt);

        for ($i = 0; $i < sizeof($arr); $i++){
            $slt = "SELECT * FROM tblFile WHERE `boardId`={$arr[$i]["id"]} ORDER BY `order` DESC";
            $arr[$i]["files"] = $this->getArray($slt);
        }

        $retVal["list"] = json_encode($arr);
        $retVal["totalPage"] = $totalPage."";
        $retVal["total"] = $total."";
        $retVal["page"] = $page."";

        return self::response(1, "", $retVal);
    }

    function getMarketList(){
        $type = $_REQUEST["type"];
        $page = $_REQUEST["page"];
        $unit = $_REQUEST["unit"] == "" ? 5 : $_REQUEST["unit"];
        $start = $page * $unit;
        $lat = $_REQUEST["lat"] == "" ? 0 : $_REQUEST["lat"];
        $lng = $_REQUEST["lng"] == "" ? 0 : $_REQUEST["lng"];

        $slt = "SELECT COUNT(*) AS cnt FROM tblBoard WHERE `type`={$type} AND `status`=1";
        $total = $this->getValue($slt, "cnt");
        $totalPage = ceil($total / $unit);

        $orderByStmt = "ORDER BY distance";
        if($lat == 0 || $lng == 0) $orderByStmt = "ORDER BY `title` DESC";

        $slt = "
        SELECT *,
        (6371*ACOS(COS(RADIANS({$lat}))*COS(RADIANS(lat))*COS(RADIANS(lng)
                        -RADIANS({$lng}))+SIN(RADIANS({$lat}))*SIN(RADIANS(lat)))) AS distance
        FROM tblBoard WHERE `type`={$type} AND `status`=1 {$orderByStmt} ASC
        LIMIT {$start}, {$unit}
        ";
        $arr = $this->getArray($slt);

        for ($i = 0; $i < sizeof($arr); $i++){
            $slt = "SELECT * FROM tblFile WHERE `boardId`={$arr[$i]["id"]} ORDER BY `order` DESC";
            $arr[$i]["files"] = $this->getArray($slt);
        }

        $retVal["list"] = json_encode($arr);
        $retVal["totalPage"] = $totalPage."";
        $retVal["total"] = $total."";
        $retVal["page"] = $page."";

        return self::response(1, "", $retVal);
    }

    function getFirstNotice(){
        $slt = "SELECT * FROM tblBoard WHERE `type`=3 AND `status`=1 ORDER BY `id` DESC LIMIT 1";
        $ret = $this->getRow($slt);

        if($ret == "") $retVal = self::response(0, "");
        else $retVal = self::response(1, "", $ret);

        return $retVal;
    }

    function deleteBoard(){
        $id = $_REQUEST["id"];
        $upt = "UPDATE tblBoard SET `status` = 0 WHERE `id` = '{$id}'";
        $this->update($upt);

        $upt = "DELETE FROM tblLike WHERE boardId='{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function uploadBoard(){
        $title = $this->req("title");
        $content = $this->req("content");
        $extra = $this->req("extra");
        $subtitle = $this->req("subtitle");
        $sidoId = $_REQUEST["sidoId"] == "" ? 0 : $_REQUEST["sidoId"];
        $userKey = $_REQUEST["userKey"];
        $url = $this->req("url");
        $type = $_REQUEST["type"];
        $exp = "'".$this->req("expireDate")."'";
        $lat = $_REQUEST["lat"] == "" ? 0 : $_REQUEST["lat"];
        $lng = $_REQUEST["lng"] == "" ? 0 : $_REQUEST["lng"];
        $phone = $this->req("phone");
        $geo = $this->req("geo");

        $alert = $_REQUEST["alert"];

        if($exp == "''") $exp = "NULL";

        $query = "INSERT INTO `app_midnight`.`tblBoard` 
                ( 
                `title`, `content`, `extra`, `subtitle`, `sidoId`, `userKey`, `url`, `type`, 
                `expireDate`, `lat`, `lng`, `phone`, `regDate`, `geo`
                )
                VALUES
                ( 
                '{$title}','{$content}', '{$extra}', '{$subtitle}', '{$sidoId}', 
                '{$userKey}', '{$url}', '{$type}', {$exp}, '{$lat}', '{$lng}', '{$phone}', NOW(), '{$geo}'
                );
            ";

        $this->update($query);

        $fileDesc = $this->req("fileDesc");
        $file = $_REQUEST["file"];
        $bid = $this->mysql_insert_id();

        if($file != ""){
            $ins = "INSERT INTO `app_midnight`.`tblFile` 
                    (`path`, `boardId`, `userKey`, `desc`, `regDate`)
                    VALUES ('{$file}', '{$bid}', '{$userKey}', '{$fileDesc}', NOW());";
            $this->update($ins);
        }

        if($alert > 0){
            $region = "전체";
            if($sidoId > 0){
                $slt = "SELECT `abbreviation` FROM tblZipSido WHERE sidoID = '{$sidoId}'";
                $region = $this->getValue($slt, "abbreviation");
            }else{}

            if(mb_strlen($title, "UTF-8") > 20) $str = mb_substr($title, 0, 10, "UTF-8")." ...";
            else $str = $title;

            if($type == 1 || $type == "1"){
                $this->sendPushToRegion("미드나잇 ".$region." 지역 행사 등록 알림", $str, $sidoId);
            }else{
                $this->sendPushToRegion("미드나잇 ".$region." 지역 게시글 알림", $str, $sidoId);
            }

        }

        return self::response(1, "");
    }

    function sendPushToRegion($title, $message, $sido){
        if($sido > 0) $slt = "SELECT pushToken FROM tblUser WHERE `sido`='{$sido}' AND `push`=1 AND `status`=1";
        else $slt = "SELECT pushToken FROM tblUser WHERE `push`=1 AND `status`=1";

        $arr = $this->getArray($slt);

        for($i = 0; $i < sizeof($arr); $i++){
            $pureTokens[$i] = $arr[$i]["pushToken"];
        }

        return $this->sendPush($title, $message, "", $pureTokens);
    }

}
