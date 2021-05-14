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

    function getCharacterList(){
        return Routable::response(1, "succ", $this->getArray("SELECT * FROM tblCharacter ORDER BY id"));
    }

    function getRecomUser(){
        $id = $_REQUEST["id"];

        $ins = "
            SELECT 
                   *,
                   (SELECT `path` FROM tblFile F WHERE F.id = U.profileId) AS profilePath,
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
            LIMIT 10;
        ";
        return Routable::response(1, "succ", $this->getArray($ins));
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
        return $this->sendPush("매칭신청", "{$nick} 님으로부터 대화신청이 도착했습니다.", "", $token);
    }

    function updateMatchStat(){
        $myId = $_REQUEST["myId"];
        $opponentId = $_REQUEST["opponentId"];
        $flag = $_REQUEST["flag"];
        if($flag == "3"){
            $tmp = $myId;
            $myId = $opponentId;
            $opponentId = $tmp;
        }
        $this->update(
            "UPDATE tblMatch SET status = '{$flag}' WHERE requestUserId = '{$opponentId}' AND receiverUserId = '{$myId}'"
        );
        return Routable::response(1, "succ");
    }

    function myMatchStat(){
        $myId = $_REQUEST['myId'];
        $flag = $_REQUEST['flag'];
        $columns = array("M.requestUserId", "M.receiverUserId");
        if($flag == "req") $columns = array_reverse($columns);
        $ins = "
            SELECT
                *,
                U.id as id,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = U.id) AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = U.id)) AS characteristicStr
            FROM tblUser U JOIN tblMatch M ON U.id = {$columns[0]} 
            WHERE {$columns[1]} = '{$myId}' AND status != 1;
        ";
        return Routable::response(1, "succ", $this->getArray($ins));
    }


}
