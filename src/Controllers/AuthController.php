<?php

namespace Api\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\Controllers\AuthHelper;
use Api\Database\Database;
use Exception;
use PDO;

define("SECRET", "Hoegf435pi1");
class AuthController
{
    public static function register()
    {
        $data = json_decode(file_get_contents("php://input"));
        $validFirstName =  preg_match("/^([a-zA-Z' ]+)$/", $data->firstName);
        $validLastName =  preg_match("/^([a-zA-Z' ]+)$/", $data->lastName);
        $validPassword = preg_match("/^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{6,16}$/", $data->password);
        $validConfirmation = $data->password === $data->confirmPassword;
        $validEmail = filter_var($data->email, FILTER_VALIDATE_EMAIL);
        if (!($validFirstName && $validLastName && $validPassword && $validConfirmation && $validEmail)) {
            http_response_code(400);
            echo json_encode(["message" => "Something went wrong! Try again later"]);
            die;
        }
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT email FROM users");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array($data->email, $result)) {
            http_response_code(400);
            echo json_encode(["message" => "Email is already taken"]);
            die;
        }
        $statement = $pdo->prepare("INSERT INTO users (email,password,firstname,lastname) values (?,?,?,?)");
        $statement->execute([$data->email, password_hash($data->password, PASSWORD_DEFAULT), $data->firstName, $data->lastName]);
        echo json_encode(["message" => "User registered successfully"]);
        die;
    }
    public static function login()
    {
        $data = json_decode(file_get_contents("php://input"));
        $email = $data->email;
        $password = $data->password;
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
            $stmt->execute([$email]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if ($stmt->rowCount() == 0) {
            $error = "Your account doesn't exist";
            echo json_encode([
                "message" => $error
            ]);
            die;
        } elseif (!password_verify($password, $users[0]['password'])) {
            $error = "Wrong password";
            echo json_encode([
                "message" => $error
            ]);
            die;
        } else {
            $payload = [
                "iat" => time(),
                "iss" => "localhost",
                "exp" => time() + 60 * 60 * 24 * 30,
                "user" => [
                    "id" => $users[0]['id'],
                    "firstName" => $users[0]['firstname'],
                    "lastName" => $users[0]['lastname'],
                    "email" => $users[0]['email'],
                ]
            ];
            $token = JWT::encode($payload, SECRET, "HS256");
            echo json_encode([
                "status" => "Success",
                "token" => $token,
                "user" => [
                    "id" => $users[0]['id'],
                    "firstName" => $users[0]['firstname'],
                    "lastName" => $users[0]['lastname'],
                    "email" => $users[0]['email'],
                ]
            ]);
            die;
        }
    }
    public static function isAuth()
    {
        $auth = false;
        if (isset(Self::getTokenData()['error'])) {
            $auth = false;
        } else {
            $auth = true;
        }
        if (!$auth) {
            http_response_code(401);
            die;
        }
    }
    public static function getTokenData()
    {
        $token = AuthHelper::getBearerToken();
        $decoded = false;
        try {
            if (gettype($token) === "string") {
                $decoded = JWT::decode($token, new Key(SECRET, 'HS256'));
            } else {
                throw new Exception;
            }
        } catch (Exception $e) {
            $decoded = ['error' => $e->getMessage()];
        }
        return (array) $decoded;
    }
    public static function getCurrentUserData()
    {
        $id = $_GET["id"];
        if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
            http_response_code(400);
            die;
        }
        if (Self::getTokenData()["user"]->id !== (int)$id) {
            http_response_code(401);
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
        $userId =Self::getTokenData()["user"]->id;
        $ext = explode('.',$_FILES["image"]["name"]);
        $path = $userId.'.'.$ext[count($ext)-1];
        move_uploaded_file($_FILES["image"]["tmp_name"], "profile-images/".$path);
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE users SET pfpurl=? WHERE id=?");
        $stmt->execute([$path,$userId]);
        if($stmt){
            echo json_encode([
                "message"=>"Image updated successfully",
            ]);
        }
        die;
    }
}
