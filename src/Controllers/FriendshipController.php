<?php

namespace Api\Controllers;

use Api\Database\Database;
use Exception;
use PDO;

class FriendshipController
{
    public static function sendFriendRequest()
    {
        $receiverId = json_decode(file_get_contents("php://input"));
        $senderId = AuthController::getTokenData()['user']->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO friendrequests (sender,receiver) values (?,?)");
        $stmt->execute([$senderId, $receiverId]);
    }
    public static function removeFriendRequest()
    {
        $receiverId = json_decode(file_get_contents("php://input"));
        $senderId = AuthController::getTokenData()['user']->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM friendrequests WHERE sender=? AND receiver=?");
        $stmt->execute([$senderId, $receiverId]);
    }
    public static function acceptFriendRequest()
    {
        try {
            $senderId = json_decode(file_get_contents("php://input"));
            $receiverId = AuthController::getTokenData()['user']->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("DELETE FROM friendrequests WHERE sender=? AND receiver=?");
            $stmt->execute([$senderId, $receiverId]);
            $stmt = $pdo->prepare("INSERT INTO friendships (user1,user2) values (?,?)");
            $stmt->execute([$senderId, $receiverId]);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function declineFriendRequest()
    {
        $senderId = json_decode(file_get_contents("php://input"));
        $receiverId = AuthController::getTokenData()['user']->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM friendrequests WHERE sender=? AND receiver=?");
        $stmt->execute([$senderId, $receiverId]);
    }
    public static function removeFriend()
    {
        try {
            $friendId = json_decode(file_get_contents("php://input"));
            $userId = AuthController::getTokenData()['user']->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("DELETE FROM friendships WHERE user1=? AND user2=? OR user2=? AND user1=?");
            $stmt->execute([$friendId, $userId, $friendId, $userId]);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function loadFriendRequestsSent()
    {
        try {
            $userId = AuthController::getTokenData()["user"]->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT receiver FROM friendrequests WHERE sender = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function loadFriendRequestsReceived()
    {
        try {
            $userId = AuthController::getTokenData()["user"]->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT id,firstname,lastname,pfpurl FROM users WHERE id in (SELECT sender FROM friendrequests WHERE receiver=?)");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function loadFriends()
    {
        try {
            $userId = $_GET["userid"];
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT id,firstname,lastname,pfpurl FROM users WHERE id in (SELECT user2 FROM friendships WHERE user1=? UNION SELECT user1 from friendships WHERE user2=?)");
            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function loadFriendsLoggedIn()
    {
        try {
            $userId = AuthController::getTokenData()["user"]->id;
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id in (SELECT user2 FROM friendships WHERE user1=? UNION SELECT user1 from friendships WHERE user2=?)");
            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public static function loadAllFriendRequests()
    {
        $userId = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id,pfpurl,firstname,lastname FROM friendrequests,users WHERE users.id = friendrequests.sender AND receiver = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
}
