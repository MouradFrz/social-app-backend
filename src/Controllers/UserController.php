<?php

namespace Api\Controllers;

use Api\Database\Database;
use PDO;
use Exception;
use Api\Controllers\AuthController;

class UserController
{
    public static function getUserData()
    {
        $id = $_GET["id"];
        if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
            http_response_code(400);
            die;
        }
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT * from users where id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        unset($result[0]["password"]);
        echo json_encode($result[0]);
    }
    public static function uploadPicture()
    {
        if (!isset($_FILES["image"])) {
            http_response_code(403);
            die;
        }
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        //Deleting the old picture from the server
        $statement = $pdo->prepare("SELECT pfpurl from users where id=?");
        $statement->execute([$userId]);
        $oldPfpUrl = $statement->fetchAll(PDO::FETCH_ASSOC)[0]["pfpurl"];
        if ($oldPfpUrl) {
            unlink("profile-images/{$oldPfpUrl}");
        }
        $ext = explode('.', $_FILES["image"]["name"]);
        $path = $userId . '.' . $ext[count($ext) - 1];
        move_uploaded_file($_FILES["image"]["tmp_name"], "profile-images/" . $path);
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE users SET pfpurl=? WHERE id=?");
        $stmt->execute([$path, $userId]);
        if ($stmt) {
            echo json_encode([
                "message" => "Image updated successfully",
            ]);
        }
        die;
    }
    public static function uploadBanner()
    {
        if (!isset($_FILES["image"])) {
            http_response_code(403);
            die;
        }
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        //Deleting the old picture from the server
        $statement = $pdo->prepare("SELECT bannerurl from users where id=?");
        $statement->execute([$userId]);
        $oldBannerUrl = $statement->fetchAll(PDO::FETCH_ASSOC)[0]["bannerurl"];
        if ($oldBannerUrl) {
            unlink("banner-images/{$oldBannerUrl}");
        }
        $ext = explode('.', $_FILES["image"]["name"]);
        $path = $userId . '.' . $ext[count($ext) - 1];
        move_uploaded_file($_FILES["image"]["tmp_name"], "banner-images/" . $path);


        $stmt = $pdo->prepare("UPDATE users SET bannerurl=? WHERE id=?");
        $stmt->execute([$path, $userId]);
        if ($stmt) {
            echo json_encode([
                "message" => "Image updated successfully",
            ]);
        }
        die;
    }
    public static function updateUser()
    {
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->firstname) || !isset($data->lastname) || !isset($data->bio)) {
            http_response_code(403);
            die;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE users SET firstname=?,lastname=?,email=?,bio=? where id=?");
        $stmt->execute([$data->firstname, $data->lastname, $data->email, $data->bio, AuthController::getTokenData()["user"]->id]);
        echo json_encode([
            "success" => true
        ]);
        die;
    }
    public static function searchUser()
    {
        $data = $_GET['keyword'];
        $keywords = explode(" ", $data);
        $pdo = Database::connect();
        $query = "SELECT * from users WHERE ";

        foreach ($keywords as $key => $keyword) {
                $query = $query . "firstname LIKE ? OR ";
        }
        foreach ($keywords as $key => $keyword) {
            if ($key !== count($keywords) - 1) {
                $query = $query . "lastname LIKE ? OR ";
            } else {
                $query = $query . "lastname LIKE ?";
            }
        }
        $dataArray = array_map(function($element){
            return "%$element%";
        },[...$keywords, ...$keywords]);
        $stmt = $pdo->prepare($query);
        $stmt->execute($dataArray);
        $resultat = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($resultat);
    }
}
