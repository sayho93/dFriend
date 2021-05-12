<?php

include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/Routable.php";

class FileRoute extends Routable {

    static function createDir($path) {
        if (is_dir($path)) return true;
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1 );
        $return = self::createDir($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path, 0755) : false;
    }

    function procFiles($file, $userKey, $boardId = 0){
        $fileInfo = array();
        $fileIds = array();

        if(!is_array($file["name"])){
            $tmp_name = $file["tmp_name"];
            $rawName = basename($file["name"]);
            $ext = pathinfo($rawName, PATHINFO_EXTENSION);
            $size = $file["size"];

            $res = $this->procDir($file);
            $short = $res["short"];
            $targetPath = $res["targetPath"];
            $tmp_name = $res["tmp_name"];

            $fileId = $this->applyUploadedData($rawName, $tmp_name, $ext, $userKey, $short, $size);

            $mime = mime_content_type($targetPath);
            // Cannot cover the situation with File Name Conflict
            $fileIds = $fileId;
            $fileInfo[$file["name"]]["id"] = $fileId;
            $fileInfo[$file["name"]]["name"] = $rawName;
            $fileInfo[$file["name"]]["size"] = filesize($targetPath);
            $fileInfo[$file["name"]]["path"] = $targetPath;
            $fileInfo["data"] = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($targetPath));
        }else{
            for($e = 0, $eMax = count($file["name"]); $e < $eMax; $e++){
                $tmp_name = $file["tmp_name"][$e];
                $rawName = basename($file["name"][$e]);
                $ext = pathinfo($rawName,PATHINFO_EXTENSION);

                if($tmp_name == ""){
                    break;
                }
                // Ext check
                $size = $file["size"][$e];

                $res = $this->procDir($file);
                $short = $res["short"];
                $targetPath = $res["targetPath"];
                $tmp_name = $res["tmp_name"];

                $fileId = $this->applyUploadedData($rawName, $tmp_name, $ext, $userKey, $short, $size, $e);

                $mime = mime_content_type($targetPath);
                // Cannot cover the situation with File Name Conflict
                $fileIds[$e] = $fileId;
                $fileInfo[$file["name"][$e]]["id"] = $fileId;
                $fileInfo[$file["name"][$e]]["name"] = $rawName;
                $fileInfo[$file["name"][$e]]["size"] = filesize($targetPath);
                $fileInfo[$file["name"][$e]]["path"] = $targetPath;
                $fileInfo[$e]["data"] = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($targetPath));
            }
        }

        $this->updateBoardIds($boardId, $fileIds);

        return $fileInfo;
    }

    function procDir($file){
        $tmp_name = $file["tmp_name"];
        $targetDir = $this->PF_FILE_TEMP_PATH;
        $shortTargetDir = $this->PF_FILE_TEMP_SHORT;
        if(!self::createDir($targetDir)){
            return self::response(-99, "파일 처리 중 경로 오류가 발생하였습니다.");
            }

        $fName = $this->makeFileName();
        $targetPath = $targetDir."/".$fName;
        $short = $shortTargetDir."/".$fName;
        $movedFlag = move_uploaded_file($tmp_name, $targetPath);
        if($movedFlag){
            $tmp_name = $targetPath;
        }else{
            return self::response(-98, "파일 처리 중 오류가 발생하였습니다.", $movedFlag);
        }

        $this->imageFixOrientation($file, $targetPath);

        return array("targetPath" => $targetPath, "tmp_name" => $tmp_name, "short" => $short);
    }

    function imageFixOrientation(&$image, $filename) {
        $tmp_name = $image["tmp_name"];
        $check = getimagesize($filename);
        if($check !== false){
            $info = $check;
            if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($filename);
            elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($filename);
            elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($filename);
        }

        if($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/png') {
            $path = $filename . ".jpg";
            $test = move_uploaded_file($tmp_name, $path);
            echo json_encode($test);
            echo $path;

            $exif = exif_read_data($path);
            echo json_encode($exif);
            if(isset($exif['Orientation'])) {
                switch($exif['Orientation']){
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;

                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;

                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
            echo json_encode($image);
        }
    }

    function procFilesUnbound(){
        $userKey = $_REQUEST["userKey"] == "" ? 0 : $_REQUEST["userKey"];
        $file = $_FILES["file"];
        return $this->procFiles($file, $userKey);
    }

    function revertUploadData($id){
        $sql = "SELECT * FROM tblFile WHERE `id`='{$id}'";
        $file = $this->getRow($sql);
        $filePath = $file["path"];

        if($filePath == "" || $file == ""){
            // file not exists
        }else{
            unlink($filePath);
        }

        $sql = "DELETE FROM tblFile WHERE `id`='{$id}'";
        $this->update($sql);

        return true;
    }

    function revertUploadedData(){
        $this->revertUploadData($_REQUEST["id"]);

        return Routable::response(1, "삭제가 완료되었습니다.");
    }

    function applyUploadedData($originName, $filePath, $extension, $userKey, $short, $size = 0, $order = 0){
        $sql = "
                INSERT INTO `tblFile` 
                (
                `originName`, 
                `path`,
                `shortPath`, 
                `ext`,
                `size`,
                `order`,
                `userKey`
                )
                VALUES
                ( 
                '{$originName}', 
                '{$filePath}', 
                '{$short}',
                '{$extension}',
                '{$size}',
                '{$order}',
                '{$userKey}'
                );
            ";
        $this->update($sql);
        return $this->mysql_insert_id();
    }

    function updateBoardIds($boardId, $fileIds){
        $idsString = $fileIds;
        if(is_array($fileIds)){
            $idsString = implode(',', $fileIds);
        }

        $sql = "UPDATE tblFile SET boardId = '{$boardId}' 
                WHERE `id` IN (".$idsString.")";
        $this->update($sql);
    }

    function updateBoardId(){
        $bid = $_REQUEST["boardId"];
        $fid = $_REQUEST["fileId"];
        $this->updateBoardIds($bid, array($fid));

        return Routable::response(1, "갱신이 완료되었습니다.");
    }

    function downloadFile($fileName, $filePath, $disposition = "attachment"){
        $home = $this->PF_URL;
        if(strstr($_SERVER["HTTP_REFERER"], $home) != false){
            $fileName = urlencode($fileName);
            header("charset:utf-8");
            header("Content-Disposition: ".$disposition."; filename=\"".$fileName."\"");
            header('Content-type: application/octet-stream');
            header('Content-Description: File Transfer');
            header("Content-Transfer-Encoding: binary");
            readfile($filePath);
        }else{
            return self::response(-1, "abnormal approach detected");
        }
    }

    function getFile($id){
        $sql = "SELECT * FROM tblFile WHERE `id`='{$id}'";
        return $this->getRow($sql);
    }

    function downloadFileById(){
        $id = $_REQUEST["id"];
        $file = $this->getFile($id);
        return $this->downloadFile($file["originName"], $file["path"]);
    }

}
