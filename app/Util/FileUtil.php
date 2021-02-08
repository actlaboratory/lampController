<?php

namespace Util;

use Model\Dao\User;
use Model\Dao\Directory;
use Model\Dao\File;

class FileUtil{

    // ファイル削除
    static function deleteDirectoryFromId($id, $db, $parentId=NULL){
        $dirTable = new Directory($db);
        if ($id===NULL){
            $dirData = $dirTable->select([
                "parent_id"=> $parentId
            ], "", "ASC", "", TRUE);
        } else{
            $dirData = $dirTable->select([
                "id"=> $id,
                "user_id"=> $_SESSION["userId"]
            ]);
            if (!empty($dirData)){
                self::deleteDirectoryFromId(NULL, $db, $dirData["id"]);
                $dirTable->delete([
                "id"=> $dirData["id"]
                ]);
                return TRUE;
            } else{
                return FALSE;
            }
        }
        if (empty($dirData)){
            return TRUE;
        }
        foreach($dirData as $d){
            self::deleteDirectoryFromId(NULL, $db, $d["id"]);
            $dirTable->delete([
                "id"=> $d["id"]
            ]);
        }
    }
}
