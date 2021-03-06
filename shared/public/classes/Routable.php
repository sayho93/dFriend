<?php
include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/bases/Databases.php";
include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/bases/utils/AuthUtil.php";
include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/bases/modules/email/EmailSender.php";

class Routable extends Databases {

    public static function response($returnCode, $returnMessage = "", $data = "", $isDevise = true){
        $retVal = array("returnCode" => $returnCode, "returnMessage" => $returnMessage, "data" => $data);
        if($isDevise) return json_encode($retVal);
        else return $retVal;
    }

    function sendPushReq(){
        $msg = $_REQUEST["msg"];
        $data = $_REQUEST["data"];
        $id = $_REQUEST["token"];
        $title = $_REQUEST["title"];

        return $this->sendPush($title, $msg, $data, $id);
    }

    function sendPush($title, $message, $data, $id) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array (
            'Authorization: key=' . GOOGLE_SERVER_KEY,
            'Content-Type: application/json'
        );

        $fields = array (
            'data' => array ("message" => $data),
            'notification' => array ("body" => $message, "title" => $title)
        );

        if(is_array($id)) {
            $fields['registration_ids'] = $id;
        } else {
            $fields['to'] = $id;
        }

        $fields['priority'] = "high";

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_URL, $url);
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

        $result = curl_exec ( $ch );
        if ($result === FALSE) {
            //die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close ( $ch );
        return $result;
    }

    function uploadFile(){
        $target_path = dirname(__FILE__).'/uploads/';

        if (isset($_FILES['image']['name'])) {
            $target_path = $target_path . basename($_FILES['image']['name']);

            try {
                // Throws exception incase file is not being moved
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // make error flag true
                    echo json_encode(array('status'=>'fail', 'message'=>'could not move file'));
                }

                // File successfully uploaded
                echo json_encode(array('status'=>'success', 'message'=>'File Uploaded'));
            } catch (Exception $e) {
                // Exception occurred. Make error flag true
                echo json_encode(array('status'=>'fail', 'message'=>$e->getMessage()));
            }
        } else {
            // File parameter is missing
            echo json_encode(array('status'=>'fail', 'message'=>'Not received any file'));
        }
    }

    function test(){
        $sql = "SELECT 1 FROM DUAL";
        return $this->getRow($sql);
    }

    function getProperty($name){
        $sql = "SELECT `value` FROM tblProperty WHERE propertyName='{$name}';";
        return $this->getValue($sql, "value");
    }

    function getProperties($prefix, $loc){
        $sql = "SELECT * FROM tblProperty WHERE lang = '{$loc}' AND propertyName LIKE '{$prefix}%';";
        return $this->getArray($sql);
    }

    function getPropertyLoc($name, $loc){
        $sql = "SELECT `value` FROM tblProperty WHERE propertyName='{$name}' AND lang='{$loc}'";
        return $this->getValue($sql, "value");
    }

    function getPropertyLocAjax(){
        return $this->getPropertyLoc($_REQUEST["name"], $_REQUEST["lang"]);
    }

    function setPropertyAjax(){
        return $this->setProperty($_REQUEST["name"], $_REQUEST["value"]);
    }

    function setPropertyLocAjax(){
        return $this->setPropertyLoc($_REQUEST["name"], $_REQUEST["lang"], $_REQUEST["value"]);
    }

    function setPropertyLoc($name, $loc, $value){
        $sql = "
            INSERT INTO tblProperty(propertyName, `desc`, `lang`, `value`) VALUES('{$name}', '', '{$loc}', '{$value}')
            ON DUPLICATE KEY UPDATE `value` = '{$value}'
            ";
        $this->update($sql);
        return Routable::response(1, "succ");
    }

    function getRecommendation($key, $table, $col, $count = 10){
        $slt = "SELECT `{$col}` FROM `{$table}` WHERE `{$col}` LIKE '%{$key}%' ORDER BY `{$col}` DESC LIMIT {$count}";
        $arr = $this->getArray($slt);

        if(sizeof($arr) == 0) return array();

        $retVal = array();
        $cursor = 0;
        foreach ($arr as $unit){
            $retVal[$cursor++] = $unit[$col];
        }
        return $retVal;
    }

    function getData($actionUrl, $request=array()){
        $url = $actionUrl . "?" . http_build_query($request, '', '&');
        $curl_obj = curl_init();
        curl_setopt($curl_obj, CURLOPT_URL, $url);
        curl_setopt($curl_obj, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl_obj, CURLOPT_RETURNTRANSFER, true);
        return  (curl_exec($curl_obj));
    }

    function postData($actionUrl, $postData){
        $curl_obj = curl_init();
        curl_setopt($curl_obj, CURLOPT_URL, $actionUrl);
        curl_setopt($curl_obj, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl_obj, CURLOPT_POST, true);
        curl_setopt($curl_obj, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_obj, CURLOPT_POSTFIELDS, $postData);
        return  (curl_exec($curl_obj));
    }

    function encryptAES256($str){
        $res = openssl_encrypt($str, "AES-256-CBC", AES_KEY_256, 0, AES_KEY_256);
        return $res;
    }

    function decryptAES256($str){
        $res = openssl_decrypt($str, "AES-256-CBC", AES_KEY_256, 0, AES_KEY_256);
        return $res;
    }

    function makeFileName(){
        srand((double)microtime()*1000000);
        $Rnd = rand(1000000,2000000);
        $Temp = date("Ymdhis");
        return $Temp.$Rnd;
    }

}

?>
