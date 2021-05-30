<?php /** @noinspection PhpUnused */

include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/Routable.php";

class WebRoute extends Routable {

    function getProvince(){
        $slt = "SELECT * FROM tblZipSido ORDER BY `orderBy` ASC";
        $ret = $this->getArray($slt);

        return self::response(1, "", $ret);
    }

    function getFaqList(){
        return $this->getArray("SELECT * FROM tblFaq ORDER BY `title` ASC");
    }

//    function getIdentityList(){
//        $slt = "SELECT * FROM tblIdentity ORDER BY className ASC";
//        return $this->getArray($slt);
//    }

    function updateHit(){
        $id = $_REQUEST["id"];
        $slt = "SELECT `hit` FROM tblBoard WHERE `id` = '{$id}'";
        $hitVal = $this->getValue($slt, "hit") + 1;
        $upt = "UPDATE tblBoard SET `hit` = '{$hitVal}' WHERE `id` = '{$id}'";
        $this->update($upt);
    }

    function getCharTypeList(){
        return Routable::response(1, "succ", $this->getArray(
            "SELECT * FROM tblCharType WHERE status=1 ORDER BY id"
        ));
    }

    function getCharacterList(){
        return Routable::response(1, "succ", $this->getArray("SELECT id, description, regDate FROM tblCharacter"));
    }

    function getCharacters(){
        $typeList = $this->getArray("SELECT id, `desc` FROM tblCharType WHERE status = 1");
        $res = Array();
        foreach($typeList as $item){
            $list = $this->getArray("SELECT * FROM tblCharacter WHERE typeId = '{$item["id"]}' order by id");
            $tmp = Array(
                "id"=> $item["id"],
                "desc"=> $item["desc"],
                "list"=> $list
            );
            array_push($res, $tmp);
        }
        return self::response(1, "succ", $res);

    }

    function getRecomUser(){
        $id = $_REQUEST["id"];

        $ins = "
            SELECT 
                   *,
                   (SELECT `shortPath` FROM tblFile F WHERE F.id = U.profileId) AS profilePath,
                   (SELECT COUNT(*) FROM tblFollow WHERE followedId = U.id) AS followers,
                   (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT id FROM tblBoard WHERE userKey = U.id)) AS likes,
                   (SELECT COUNT(*) FROM tblBoard B WHERE B.userKey = U.id) AS boards,
                   (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.id) AS followingYou
            FROM tblUser U JOIN (
                SELECT
                    id,
                    (SELECT COUNT(*) FROM tblCharMap WHERE userId = id AND characterId IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS matchCnt, (
                        SELECT GROUP_CONCAT(description separator ',') 
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id 
                        WHERE CM.userId = IU.id AND characterId IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS matchDesc, (
                        SELECT GROUP_CONCAT(characterId separator  ',')
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id    
                        WHERE CM.userId = IU.id AND characterId IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS matchIds, (
                        SELECT GROUP_CONCAT(description separator ',') 
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id 
                        WHERE CM.userId = IU.id AND characterId NOT IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS nonMatchDesc, (
                        SELECT GROUP_CONCAT(characterId separator ',') 
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id 
                        WHERE CM.userId = IU.id AND characterId NOT IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS nonMatchIds
                FROM tblUser IU
                ORDER BY RAND()
            ) as tmp
            ON U.id = tmp.id
            WHERE U.id != '{$id}' AND U.status = 1
            ORDER BY matchCnt DESC
            LIMIT 5;
        ";
        $list = $this->getArray($ins);
        shuffle($list);
        return Routable::response(1, "succ", $list[0]);
    }

    function sendChatPush(){
        $id = $_REQUEST["id"];
        $title = $_REQUEST["title"];
        $message = $_REQUEST["message"];
        $token = $this->getValue("SELECT pushToken From tblUser WHERE id = '{$id}' LIMIT 1", "pushToken");
        return $this->sendPush($title, $message, "", $token);
    }

    function requestMatch(){
        $myId = $_REQUEST["myId"];
        $opponentId = $_REQUEST["opponentId"];

        $chk = $this->getValue(
            "SELECT COUNT(*) AS cnt 
                FROM tblMatch 
                WHERE (requestUserId = '{$myId}' AND receiverUserId = '{$opponentId}') OR (requestUserId = '{$opponentId}' AND receiverUserId = '{$myId}')",
            "cnt"
        );
        if($chk != 0) return Routable::response(-1, "이미 신청된 매칭이 있습니다.");

        $ins = "INSERT INTO tblMatch(`requestUserId`, `receiverUserId`) VALUES('{$myId}', '{$opponentId}')";
        $this->update($ins);
        $token = $this->getValue("SELECT pushToken FROM tblUser WHERE id = '{$opponentId}' LIMIT 1", "pushToken");
        $nick = $this->getValue("SELECT nickname FROM tblUser WHERE id = '{$myId}' LIMIT 1", "nickname");
        $res = $this->sendPush("매칭신청", "{$nick} 님으로부터 대화신청이 도착했습니다.", "", $token);
        return Routable::response(1, "신청되었습니다.");
    }

    function updateMatchStat(){
        $myId = $_REQUEST["myId"];
        $opponentId = $_REQUEST["opponentId"];
        $flag = $_REQUEST["flag"];

        $user = $this->getRow("SELECT * FROM tblUser WHERE id = '{$myId}' LIMIT 1");
        $token = $this->getValue("SELECT pushToken FROM tblUser WHERE id = '{$opponentId}' LIMIT 1", "pushToken");

        $title = "";
        $message = "";
        if($flag == "3"){
            $tmp = $myId;
            $myId = $opponentId;
            $opponentId = $tmp;
        }

        $this->update(
            "UPDATE tblMatch SET status = '{$flag}' WHERE requestUserId = '{$opponentId}' AND receiverUserId = '{$myId}'"
        );

        if($flag == 1){
            $me = $this->getRow("SELECT id, nickname FROM tblUser WHERE id = '{$myId}'");
            $other = $this->getRow(("SELECT id, nickname FROM tblUser WHERE id = '{$opponentId}'"));

            $ins = "
                INSERT INTO tblChatRoom(name) 
                VALUES('{$me["nickname"]}, {$other["nickname"]}')
            ";
            $this->update($ins);
            $roomId = $this->mysql_insert_id();

            $token = $this->getValue("SELECT pushToken FROM tblUser WHERE id = '{$opponentId}' LIMIT 1", "pushToken");
            $nick = $this->getValue("SELECT nickname FROM tblUser WHERE id = '{$myId}' LIMIT 1", "nickname");
            $res = $this->sendPush("매칭신청", "{$nick} 님이 매칭을 수락했습니다. 대화방이 생성됩니다.", "", $token);


            $this->update("INSERT INTO tblChatMember(userId, roomId) VALUES('{$me["id"]}', '{$roomId}')");
            $this->update("INSERT INTO tblChatMember(userId, roomId) VALUES('{$other["id"]}', '{$roomId}')");
        }


        return Routable::response(1, "succ");
    }

    function checkMatchStat(){
        $myId = $_REQUEST["myId"];
        $opponentId = $_REQUEST["opponentId"];
        $ins = "SELECT * FROM tblMatch WHERE (requestUserId = '{$myId}' AND receiverUserId = '{$opponentId}') OR (requestUserId = '{$opponentId}' AND receiverUserId = '{$myId}') LIMIT 1";
        $res = $this->getRow($ins);
        $ret = $res == "" ? -1 : $res["status"];
        return Routable::response($ret);
    }

    function matchCount(){
        $myId = $_REQUEST["myId"];
        $ins = "
            SELECT COUNT(*) AS cnt
            FROM tblUser U JOIN tblMatch M ON U.id = M.requestUserId
            WHERE M.receiverUserId = '{$myId}' AND M.status != 1;
        ";
        $recvCnt = $this->getValue($ins, "cnt");
        $ins = "
            SELECT COUNT(*) AS cnt
            FROM tblUser U JOIN tblMatch M ON U.id = M.receiverUserId
            WHERE M.requestUserId = '{$myId}' AND M.status != 1 AND M.status != 2;
        ";
        $reqCnt = $this->getValue($ins, "cnt");

        return self::response(1, "", array("req" => $reqCnt, "recv" => $recvCnt));
    }

    function myMatchStat(){
        $myId = $_REQUEST['myId'];
        $flag = $_REQUEST['flag'];
        $columns = array("M.requestUserId", "M.receiverUserId");
        $options = array("AND M.status != 2", "");
        if($flag == "req"){
            $columns = array_reverse($columns);
            $options = array_reverse($options);
        }
        $ins = "
            SELECT
                U.*,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = U.id) AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = U.id)) AS characteristicStr
            FROM tblUser U JOIN tblMatch M ON U.id = {$columns[0]} 
            WHERE {$columns[1]} = '{$myId}' AND M.status != 1 {$options[1]};
        ";
        return Routable::response(1, "succ", $this->getArray($ins));
    }


    function getChatRoom(){
        $id = $_REQUEST["myId"];
        $ins = "
        SELECT 
            U.*, M.roomId as point,
            (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = U.id) AS characteristics,
            (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = U.id)) AS characteristicStr,
            (SELECT content FROM tblChatMessage WHERE roomId = M.roomId ORDER BY regDate DESC LIMIT 1) AS latestMsg
        FROM tblUser U JOIN tblChatMember M ON U.id = M.userId
        WHERE roomId IN (
            SELECT roomId
            FROM tblChatRoom R JOIN tblChatMember M ON R.id = M.roomId
            WHERE M.userid = '{$id}'
        ) AND userId != '{$id}'
        ";

        return Routable::response(1, "", $this->getArray($ins));
    }

    function chatMessageList(){
        $rId = $_REQUEST["roomId"];
        $page = $_REQUEST["page"];
        $unit = $_REQUEST["unit"] == "" ? 100 : $_REQUEST["unit"];
        $start = $page * $unit;

        $slt = "SELECT COUNT(*) AS cnt FROM tblChatMessage WHERE roomId = '{$rId}' AND `status`=1";
        $total = $this->getValue($slt, "cnt");
        $totalPage = ceil($total / $unit);

        $ins = "
            SELECT * 
            FROM (
                 SELECT M.*, U.nickname 
                FROM tblChatMessage  M JOIN tblUser U ON M.userId = U.id
                WHERE roomId = '{$rId}'
                ORDER BY `id` DESC LIMIT {$start}, {$unit}
            ) tmp
            ORDER BY tmp.id;
        ";
        $arr = $this->getArray($ins);

        $retVal["list"] = json_encode($arr);
        $retVal["totalPage"] = $totalPage."";
        $retVal["total"] = $total."";
        $retVal["page"] = $page."";
        return self::response(1, "", $retVal);
    }

    function releaseMatch(){
        $roomId = $_REQUEST["roomId"];
        $users = $this->getArray("SELECT userId from tblChatMember WHERE roomId = '{$roomId}'");

        $ins = "
            DELETE FROM tblMatch
            WHERE (requestUserId = '{$users[0]["userId"]}' AND receiverUserId = '{$users[1]["userId"]}')
            OR (requestUserId = '{$users[1]["userId"]}' AND receiverUserId ] '{$users[1]["userId"]}')
        ";
        $this->update($ins);
        $this->update("UPDATE tblChatRoom SET status = 0 WHERE id = '{$roomId}'");

        return self::response(1, "");
    }
    //TODO 채팅방 나갈 때 tblMatch ROW 반드시 제거 필요
}
