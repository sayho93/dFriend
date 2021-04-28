<?php

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


}
