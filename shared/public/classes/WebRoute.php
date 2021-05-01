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

    function getIdentityList(){
        $slt = "SELECT * FROM tblIdentity ORDER BY className ASC";
        return $this->getArray($slt);
    }

    function updateHit(){
        $id = $_REQUEST["id"];
        $slt = "SELECT `hit` FROM tblBoard WHERE `id` = '{$id}'";
        $hitVal = $this->getValue($slt, "hit") + 1;
        $upt = "UPDATE tblBoard SET `hit` = '{$hitVal}' WHERE `id` = '{$id}'";
        $this->update($upt);
    }

    function getCharacterList(){
        return $this->getArray("SELECT * FROM tblCharacter");
    }

    function getRecomUser(){
        $id = $_REQUEST["id"];

        $ins = "
            SELECT *
            FROM tblUser U JOIN (
                SELECT
                    id,
                    (SELECT COUNT(*) FROM tblCharMap WHERE userId = id AND characterId IN (SELECT characterId FROM tblCharMap WHERE userId = '39')) AS matchCnt,(
                        SELECT GROUP_CONCAT(description separator ',') 
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id 
                        WHERE CM.userId = IU.id AND characterId IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS matchDesc,(
                        SELECT GROUP_CONCAT(description separator ',') 
                        FROM tblCharMap CM JOIN tblCharacter C on characterId = C.id 
                        WHERE CM.userId = IU.id AND characterId NOT IN (SELECT characterId FROM tblCharMap WHERE userId = '{$id}')
                    ) AS nonMatchDesc
                FROM tblUser IU
                ORDER BY RAND()
            ) as tmp
            ON U.id = tmp.id
            WHERE U.id != '{$id}'
            ORDER BY matchCnt DESC
            LIMIT 10;
        ";
        return Routable::response(1, "succ", $this->getArray($ins));
    }

}
