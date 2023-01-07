<?php

namespace Api\Controllers;

use Api\Database\Database;
use Exception;
use PDO;

class PostController
{
    public static function createPost()
    {
        if (!$_POST["text"] && !isset($_FILES["images"])) {
            http_response_code(403);
            die;
        }
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO posts (text,userid) values (?,?)");
        $stmt->execute([$_POST["text"], $userId]);
        $id = $pdo->lastInsertId();
        if (isset($_FILES["images"])) {
            foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
                $file_tmp = $_FILES["images"]["tmp_name"][$key];
                $file_name = $_FILES["images"]["name"][$key];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $savedname = "{$key}-{$userId}-" . time() . "." . $ext;
                move_uploaded_file($_FILES["images"]["tmp_name"][$key], "./post-images/{$savedname}");
                $statement = $pdo->prepare("INSERT INTO postimages (path,postid) values (?,?)");
                $statement->execute([$savedname, $id]);
            }
        } else {
            $statement = $pdo->prepare("INSERT INTO postimages (postid) values (?)");
            $statement->execute([$id]);
        }
    }
    public static function loadUserPosts()
    {
        try {
            $userId = AuthController::getTokenData()["user"]->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT pfpurl,firstname,lastname,text,created_at,posts.id,path FROM posts,users,postimages WHERE posts.userid=? AND users.id = posts.userid AND postimages.postid = posts.id ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function deletePost()
    {
        $data = json_decode(file_get_contents("php://input"));

        $postId = $data->postId;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT userid FROM posts where id=?");
        $stmt->execute([$postId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($userId !== $result[0]['userid']) {
            http_response_code(401);
            die;
        }
        $imagesStmt = $pdo->prepare("SELECT path FROM postimages where postid=?");
        $imagesStmt->execute([$postId]);
        $imagesToDelete = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);
        if(!is_null($imagesToDelete[0])){
            foreach($imagesToDelete as $index=>$img){
                unlink("./post-images/$img");
            }
        }
        $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id=?");
        $deleteStmt->execute([$postId]);
        die;
    }
    public static function likePost(){
        $data = json_decode(file_get_contents("php://input"));
        $postId = $data->postId;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $canLike = $pdo->prepare("SELECT postid from likes where postid=? AND userid=?");
        $canLike->execute([$postId,$userId]);
        $result = $canLike->fetchAll(PDO::FETCH_COLUMN);
        if(count($result)){
            http_response_code(401);
            die;
        }
        $stmt = $pdo->prepare("INSERT INTO likes values(?,?)");
        $stmt->execute([$postId,$userId]);
    }
    public static function unlikePost(){
        $data = json_decode(file_get_contents("php://input"));
        $postId = $data->postId;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $canLike = $pdo->prepare("SELECT postid from likes where postid=? AND userid=?");
        $canLike->execute([$postId,$userId]);
        $result = $canLike->fetchAll(PDO::FETCH_COLUMN);
        if(!count($result)){
            http_response_code(401);
            die;
        }
        $stmt = $pdo->prepare("DELETE FROM likes WHERE postid=? AND userid=?");
        $stmt->execute([$postId,$userId]);
    }
    public static function getUserLikes(){
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM likes WHERE userid=?");
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode($result);
    }
}
