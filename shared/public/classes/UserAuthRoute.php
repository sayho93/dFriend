<?php /** @noinspection PhpUnused */

include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/FileRoute.php";

class UserAuthRoute extends FileRoute {

    public function requestLogin(){
        $email = $_REQUEST["email"];
        $pwd = $this->encryptAES256($_REQUEST["pwd"]);

        $val = $this->getRow("SELECT * FROM tblUser WHERE email='{$email}' AND email != 'Unknown' AND `password`='{$pwd}' LIMIT 1");
        if($val != null){
            if($val["status"] == "2"){
                return Routable::response(3, "인증 대기중인 계정입니다.\n인증 후 이용해주세요.");
            }else{
                AuthUtil::requestLogin($val);
                $upt = "UPDATE tblUser SET accessDate=NOW() WHERE `id`='{$val["id"]}'";
                $this->update($upt);
                return Routable::response(1, "정상적으로 로그인되었습니다.", $this->getUserWithId($val["id"]));
            }
        }else{
            return Routable::response(2, "일치하는 회원 정보를 찾을 수 없습니다.");
        }
    }

    public function authMail(): array{
        $email = $this->decryptAES256($_REQUEST["authCode"]);
        $val = $this->getRow("SELECT * FROM tblUser WHERE email='{$email}' LIMIT 1");
        if($val != null){
            $upt = "UPDATE tblUser SET `status`=1 WHERE `id`='{$val["id"]}'";
            $this->update($upt);
            $retVal = array(
                "redirect" => true,
                "url" => "http://".$_SERVER["HTTP_HOST"]."/midnight/index.php?msg=인증이%20완료되었습니다."
            );
        }else{
            $retVal = array(
                "redirect" => true,
                "url" => "http://".$_SERVER["HTTP_HOST"]."/midnight/index.php?msg=유효하지%20않은%20요청입니다."
            );
        }

        return $retVal;
    }

    public function getUserByReq(){
        return $this->getUser($_REQUEST["id"]);
    }

    function joinUser(){
        $email = $_REQUEST["email"];
        $pwd = $this->encryptAES256($_REQUEST["pwd"]);
        $nickname = $_REQUEST["nickname"];

        $univ = explode("@", $email)[1];
        switch($univ){
            case "dongguk.edu":
            case "dgu.ac.kr":
                break;
            default:
                return Routable::response(3, "대학교 이메일을 사용해 주세요");
        }

        $val = $this->getRow("SELECT * FROM tblUser WHERE email='{$email}' AND status != 0 LIMIT 1");
        if($val != null){
            return Routable::response(2, "이미 존재하는 이메일 계정입니다.");
        }else{
            $ins = "INSERT INTO tblUser(email, `account`, `password`, `nickname`, `status`, regDate)
                    VALUES ('{$email}', '{$email}', '{$pwd}', '{$nickname}', 2, NOW())";
            $this->update($ins);
            $link = "http://".$_SERVER["HTTP_HOST"]."/midnight/shared/public/route.php?F=UserAuthRoute.authMail&authCode=".urlencode($this->encryptAES256($email));
            $sender = new EmailSender();
            $sender->sendMailTo(
                "피클코드 인증 메일입니다.",
                "아래 링크를 클릭하여 인증을 완료해주세요.<br/><a href='$link'>인증 링크</a><br/>본 서비스를 신청하지 않으셨다면 즉시 본 이메일로 회신바랍니다.",
                $email, $nickname
                );
            $id = $this->mysql_insert_id();
            return Routable::response(1, "입력하신 이메일로 인증 링크가 발송되었습니다.", $this->getUserWithId($id));
        }
    }

    public function checkNick(){
        $nick = $_REQUEST["nick"];
        $cnt = $this->getValue("SELECT COUNT(*) as cnt FROM tblUser WHERE nickname = '{$nick}' LIMIT 1", "cnt");
        if($cnt > 0) return Routable::response(-1, "이미 해당 닉네임을 사용하는 유저가 있습니다.");
        else return Routable::response(1, "사용가능한 닉네임입니다.");
    }

    function test(){
        $email = "fishcreek@naver.com";
        $link = "http://".$_SERVER["HTTP_HOST"]."/midnight/shared/public/route.php?F=UserAuthRoute.authMail&authCode=".urlencode($this->encryptAES256($email));
        $sender = new EmailSender();
        $sender->sendMailTo(
            "피클코드 인증 메일입니다.",
            "아래 링크를 클릭하여 인증을 완료해주세요.<br/><a href='$link'>인증 링크</a><br/>본 서비스를 신청하지 않으셨다면 즉시 본 이메일로 회신바랍니다.",
            $email, "asdf"
        );
    }

    function setUserDetails(){
        $userId = $_REQUEST['userId'];
        $sex = $_REQUEST["sex"];
        $age = $_REQUEST["age"];
        $desc = $_REQUEST["desc"];
        $profileId = $_REQUEST["profileId"];
        if($userId == "")
            return Routable::response(-2, "에러 발생! 관리자에게 문의하세요");

        if($sex === "" || $age === "" || $desc === "" || $profileId === "")
            return Routable::response(-1, "정보를 모두 기입해 주세요");

        $ins = "
            UPDATE tblUser
            SET 
                `identity` = '{$sex}',
                `age` = '{$age}',
                `desc` = '{$desc}',
                `profileId` = '{$profileId}'
            WHERE id = '{$userId}'
        ";
        return Routable::response(1, "succ", $this->getUserWithId($userId));
    }

    /**
     * @throws Exception
     */
    function setUserCharacter(){
        $userId = $_REQUEST['userId'];
        $charList = $_REQUEST['charList'];
        $charList = json_decode($charList);

        if(count($charList) < 3) return Routable::response(2, "관심사를 3개 이상 선택해 주세요");
        if(count($charList) > 6) return Routable::response(3, "관심사는 최대 6개까지 선택할 수 있습니다.");

        $ins = "DELETE FROM tblCharMap WHERE userId = '{$userId}'";
        $this->update($ins);
        foreach($charList as $item){
            $ins = "INSERT INTO tblCharMap(`userId`, `characterId`) VALUES ({$userId}, {$item})";
            $this->update($ins);
        }
        return Routable::response(1, "가입되었습니다", $this->getUserWithId($userId));
    }

    function getUserCharacter(){
        $userId = $_REQUEST["userId"];
        $ins = "
            SELECT * 
            FROM tblCharMap CM JOIN tblCharacter C ON CM.characterId = C.id
            WHERE userId = '{$userId}'
            ORDER BY id
        ";
        return Routable::response(1, "", $this->getArray($ins));
    }

    function revertJoin(){
        $id = $_REQUEST["id"];
        $cnt = $this->getValue("SELECT COUNT(*) as cnt FROM tblUser WHERE id = '{$id}'", "cnt");
        if($cnt > 0) return Routable::response(-1, "없는 유저입니다.");
        $ins = "DELETE FROM tblUser WHERE id = '{$id}'";
        return Routable::response(1, "succ");
    }

    function setLocation(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"] == "" ? 1 : $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `location` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        if($flag == 0 || $flag == "0"){
            $upt = "UPDATE tblUser SET `lat` = 0, `lng`=0 WHERE `id` = '{$id}'";
            $this->update($upt);
        }

        return self::response(1, "");
    }

    function setPush(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"] == "" ? 1 : $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `push` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function updateToken(){
        $id = $_REQUEST["id"];
        $pushToken = $_REQUEST["pushToken"];
        if($pushToken != "" && $pushToken != null){
            $upt = "UPDATE tblUser SET `pushToken` = '{$pushToken}' WHERE `id` = '{$id}'";
            $this->update($upt);
        }

        return self::response(1, "");
    }

    function updateLatLng(){
        $id = $_REQUEST["id"];
        $lat = $_REQUEST["lat"];
        $lng = $_REQUEST["lng"];
        if($lat != "" && $lat != null && $lng != "" && $lng != null){
            $upt = "UPDATE tblUser SET `lat` = '{$lat}', `lng`='{$lng}' WHERE `id` = '{$id}'";
            $this->update($upt);
        }

        return self::response(1, $id.":".$lat."/".$lng);
    }

    function setBio(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `bio` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function setSido(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `sido` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function follow(){
        $uid = $_REQUEST["userId"];
        $bid = $_REQUEST["myId"];


        $upt = "INSERT IGNORE INTO tblFollow(userId, followedId) VALUES('{$bid}','{$uid}')";
        $this->update($upt);

        $slt = "SELECT COUNT(*) AS cnt FROM tblFollow WHERE followedId='{$uid}'";
        $cnt = $this->getValue($slt, "cnt");

        return self::response($cnt, "");
    }

    function unfollow(){
        $uid = $_REQUEST["userId"];
        $bid = $_REQUEST["myId"];


        $upt = "DELETE FROM tblFollow WHERE userId='{$bid}' AND followedId='{$uid}'";
        $this->update($upt);

        $slt = "SELECT COUNT(*) AS cnt FROM tblFollow WHERE followedId='{$uid}'";
        $cnt = $this->getValue($slt, "cnt");

        return self::response($cnt, "");
    }

    function registerFile($id){
        $fileDesc = $this->req("fileDesc");
        $file = $_REQUEST["file"];

        $bid = 0;

        if($file != ""){
            $ins = "INSERT INTO `app_midnight`.`tblFile` 
                    (`path`, `boardId`, `userKey`, `desc`, `regDate`)
                    VALUES ('{$file}', 0, '{$id}', '{$fileDesc}', NOW());";
            $this->update($ins);

            $bid = $this->mysql_insert_id();
        }

        return $bid;
    }

    function setProfileId(){
        $id = $_REQUEST["id"];

        $bid = $this->registerFile($id);

        $upt = "UPDATE tblUser SET `profileId` = '{$bid}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function setBgId(){
        $id = $_REQUEST["id"];

        $flag = $this->registerFile($id);

        $upt = "UPDATE tblUser SET `bgId` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function setPhone(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `phone` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function setBirth(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `birth` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

    function setIdentity(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];
        $upt = "UPDATE tblUser SET `identity` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "");
    }

//    function getIdentities(){
//        $slt = "SELECT * FROM tblIdentity ORDER BY `desc` ASC";
//        $ret = $this->getArray($slt);
//
//        return self::response(1, "", $ret);
//    }

    function setNickname(){
        $id = $_REQUEST["id"];
        $flag = $_REQUEST["value"];

        $slt = "SELECT COUNT(*) AS cnt FROM tblUser WHERE `nickname`='{$flag}'";
        $cnt = $this->getValue($slt, "cnt");

        if($cnt > 0){
            return self::response(2, "이미 존재하는 닉네임입니다.");
        }

        $upt = "UPDATE tblUser SET `nickname` = '{$flag}' WHERE `id` = '{$id}'";
        $this->update($upt);

        return self::response(1, "닉네임이 설정되었습니다.");
    }

    function intro(){
        $id = $_REQUEST["id"];
        $account = $_REQUEST["account"];
        $joinedVia = $_REQUEST["joinedVia"];
        $pushType = $_REQUEST["pushType"];
        $pushToken = $_REQUEST["pushToken"];

        $slt = "SELECT COUNT(*) AS cnt FROM tblUser WHERE `account`='{$account}'";
        $cnt = $this->getValue($slt, "cnt");

        if($cnt > 0){
            // Already existing
        }else{
            $ins = "INSERT INTO tblUser(`account`, `joinedVia`, `pushType`, `regDate`) 
                        VALUES ('{$account}', '{$joinedVia}', '{$pushType}', NOW())";
            $this->update($ins);
        }

        if($pushToken != "" && $pushToken != null){
            $upt = "UPDATE tblUser SET `pushToken` = '{$pushToken}' WHERE `account` = '{$account}'";
            $this->update($upt);
        }

        $slt = "SELECT `id`, `nickname` FROM tblUser WHERE `account`='{$account}'";

        $userId = $this->getValue($slt, "id");
        $nickname = $this->getValue($slt, "nickname");

        if($nickname == "" || $nickname == null){
            $upt = "UPDATE tblUser SET `nickname` = '사용자{$userId}' WHERE `account` = '{$account}'";
            $this->update($upt);
        }

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U WHERE U.`account`='{$account}' LIMIT 1";
        $ret = $this->getRow($slt);

        // Access date set need to be below the load info process
        $upt = "UPDATE tblUser SET `accessDate` = NOW() WHERE `account` = '{$account}'";
        $this->update($upt);

        return self::response(1, "", $ret);
    }

    function getUser(){
        $myId = $_REQUEST["myId"];
        $id = $_REQUEST["id"];

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$myId}' AND followedId=U.`id`) AS followingYou,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U WHERE U.`id`='{$id}' LIMIT 1";
        $ret = $this->getRow($slt);

        return self::response(1, "", $ret);
    }

    function renewUser(){
        $id = $_REQUEST["id"];
        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U WHERE U.`id`='{$id}' LIMIT 1";
        $ret = $this->getRow($slt);

        return self::response(1, "", $ret);
    }

    function getUserWithId($id){
        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U WHERE U.`id`='{$id}' LIMIT 1";
        $ret = $this->getRow($slt);
        return $ret;
    }

    function requestLogout(){
        AuthUtil::requestLogout();
        return Routable::response(1, "정상적으로 로그아웃되었습니다.");
    }

    function getRandomUser(){
        $id = $_REQUEST["id"];

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                IFNULL(ROUND((TO_DAYS(NOW()) - (TO_DAYS(birth))) / 365), 0) AS age
                FROM tblUser U WHERE U.`id`!='{$id}' ORDER BY RAND() LIMIT 1";
        $ret = $this->getRow($slt);

        return self::response(1, "", $ret);
    }

    function getUserWithDistance(){
        $id = $_REQUEST["id"] == "" ? 0 : $_REQUEST["id"];
        $lat = $_REQUEST["lat"];
        $lng = $_REQUEST["lng"];
        $dis = $_REQUEST["distance"] == "" ? 5 : $_REQUEST["distance"];

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                IFNULL(ROUND((TO_DAYS(NOW()) - (TO_DAYS(birth))) / 365), 0) AS age,
                (6371*ACOS(COS(RADIANS({$lat}))*COS(RADIANS(lat))*COS(RADIANS(lng)
                -RADIANS({$lng}))+SIN(RADIANS({$lat}))*SIN(RADIANS(lat)))) AS distance,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U WHERE `lat`!=0 AND `lng`!=0 AND `location`=1 AND U.`id`!='{$id}' HAVING distance <= '{$dis}'
                ORDER BY distance ASC;";

        $ret = $this->getArray($slt);

        return self::response(1, "", $ret);
    }

    function getUserOrderLikes(){
        $id = $_REQUEST["id"] == "" ? 0 : $_REQUEST["id"];
        $lat = $_REQUEST["lat"] == "" ? 0 : $_REQUEST["lat"];
        $lng = $_REQUEST["lng"] == "" ? 0 : $_REQUEST["lng"];
        $limit = $_REQUEST["limit"] == "" ? 10 : $_REQUEST["limit"];

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                IFNULL(ROUND((TO_DAYS(NOW()) - (TO_DAYS(birth))) / 365), 0) AS age,
                (6371*ACOS(COS(RADIANS({$lat}))*COS(RADIANS(lat))*COS(RADIANS(lng)
                -RADIANS({$lng}))+SIN(RADIANS({$lat}))*SIN(RADIANS(lat)))) AS distance,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U
                ORDER BY likes DESC LIMIT {$limit};";

        $ret = $this->getArray($slt);

        return self::response(1, "", $ret);
    }

    function getUserOrderFollowers(){
        $id = $_REQUEST["id"] == "" ? 0 : $_REQUEST["id"];
        $lat = $_REQUEST["lat"] == "" ? 0 : $_REQUEST["lat"];
        $lng = $_REQUEST["lng"] == "" ? 0 : $_REQUEST["lng"];
        $limit = $_REQUEST["limit"] == "" ? 10 : $_REQUEST["limit"];

        $slt = "SELECT *,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.profileId) AS profilePath,
                (SELECT `shortPath` FROM tblFile F WHERE F.`id`=U.bgid) AS bgPath,
                (SELECT `abbreviation` FROM tblZipSido Z WHERE Z.sidoID=U.sido) AS strSido,
                (SELECT COUNT(*) FROM tblFollow WHERE followedId=U.`id`) AS followers,
                (SELECT COUNT(*) FROM tblLike L WHERE L.boardId IN (SELECT `id` FROM tblBoard WHERE userKey=U.`id`)) AS likes,
                (SELECT COUNT(*) FROM tblBoard B WHERE B.`userKey` = U.`id`) AS boards,
                (SELECT COUNT(*) > 0 FROM tblFollow WHERE userId='{$id}' AND followedId=U.`id`) AS followingYou,
                IFNULL(ROUND((TO_DAYS(NOW()) - (TO_DAYS(birth))) / 365), 0) AS age,
                (6371*ACOS(COS(RADIANS({$lat}))*COS(RADIANS(lat))*COS(RADIANS(lng)
                -RADIANS({$lng}))+SIN(RADIANS({$lat}))*SIN(RADIANS(lat)))) AS distance,
                (SELECT GROUP_CONCAT(characterId) FROM tblCharMap WHERE userId = '{$id}') AS characteristics,
                (SELECT GROUP_CONCAT(description) FROM tblCharacter WHERE id IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')) AS characteristicStr
                FROM tblUser U
                ORDER BY followers DESC LIMIT {$limit};";

        $ret = $this->getArray($slt);

        return self::response(1, "", $ret);
    }

}
