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
            $userId = $_GET["userid"];
            $page = $_GET["page"];
            $postPerPage = 5;
            $postsCount = $postPerPage*$page;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT pfpurl,firstname,lastname,text,created_at,posts.id,path FROM posts,users,postimages WHERE posts.userid=? AND users.id = posts.userid AND postimages.postid = posts.id ORDER BY created_at DESC LIMIT $postsCount");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function deletePost()
    {
        try {
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
            if (!is_null($imagesToDelete[0])) {
                foreach ($imagesToDelete as $index => $img) {
                    unlink("./post-images/$img");
                }
            }
            $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id=?");
            $deleteStmt->execute([$postId]);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function likePost()
    {
        $data = json_decode(file_get_contents("php://input"));
        $postId = $data->postId;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $canLike = $pdo->prepare("SELECT postid from likes where postid=? AND userid=?");
        $canLike->execute([$postId, $userId]);
        $result = $canLike->fetchAll(PDO::FETCH_COLUMN);
        if (count($result)) {
            http_response_code(401);
            die;
        }
        $stmt = $pdo->prepare("INSERT INTO likes values(?,?)");
        $stmt->execute([$postId, $userId]);
    }
    public static function unlikePost()
    {
        $data = json_decode(file_get_contents("php://input"));
        $postId = $data->postId;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $canLike = $pdo->prepare("SELECT postid from likes where postid=? AND userid=?");
        $canLike->execute([$postId, $userId]);
        $result = $canLike->fetchAll(PDO::FETCH_COLUMN);
        if (!count($result)) {
            http_response_code(401);
            die;
        }
        $stmt = $pdo->prepare("DELETE FROM likes WHERE postid=? AND userid=?");
        $stmt->execute([$postId, $userId]);
    }
    public static function getUserLikes()
    {
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM likes WHERE userid=?");
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode($result);
    }
    public static function loadLikes()
    {
        $userId = AuthController::getTokenData()["user"]->id;
        $list = $_GET["list"];
        $pdo = Database::connect();
        $dataList = explode(',', $list);
        $query = "select count(userid) as likecount,postid FROM likes where postid in (";
        foreach ($dataList as $key => $id) {
            if ($key !== count($dataList) - 1) {
                $query = $query . "?,";
            } else {
                $query = $query . "?)";
            }
        }
        $query = $query . " group by postid;";
        $stmt = $pdo->prepare($query);
        $stmt->execute($dataList);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
    public static function uploadComment()
    {
        $data = json_decode(file_get_contents("php://input"));
        $text =  $data->text;
        $postId =  $data->postid;
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT comments (postid,userid,text) values (?,?,?)");
        $stmt->execute([$postId, $userId, $text]);
    }
    public static function loadComments()
    {
        $postId = $_GET["postid"];
        if (!$postId) {
            echo $postId;
            die;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT firstname,lastname,pfpurl,text,users.id,created_at
        from comments,users 
        where postid=? AND comments.userid = users.id
        ORDER BY created_at DESC");
        $stmt->execute([$postId]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
}
