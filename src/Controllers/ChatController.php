<?php

namespace Api\Controllers;

use Api\Database\Database;
use Exception;
use PDO;
use Pusher\Pusher as Pusher;

class ChatController
{
    public static function loadAllConversations()
    {
        $id = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM messages m,users
        WHERE convoid IN (SELECT id FROM friendships WHERE user1=? OR user2=?) 
        AND date=(Select max(date) FROM messages WHERE messages.convoid=m.convoid)
        AND users.id = (SELECT id from users u where id in 
                            (SELECT user1 
                            FROM friendships 
                            WHERE friendships.id = convoid union 
                            SELECT user2 
                            FROM friendships 
                            WHERE friendships.id = convoid) AND u.id <> ?)
                            GROUP BY convoid ORDER BY date DESC;");
        $stmt->execute([$id, $id, $id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
    public static function loadMessages()
    {
        $convoid = $_GET['convoid'];
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE convoid=?");
        $stmt->execute([$convoid]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
    public static function loadConvoContact()
    {
        $id = AuthController::getTokenData()["user"]->id;
        $convoid = $_GET['convoid'];
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id,firstname,lastname,pfpurl FROM users WHERE id IN 
        (SELECT user1 as userid FROM friendships WHERE friendships.id=?
        UNION SELECT user2 as userid FROM friendships WHERE friendships.id=?)
        AND id<>?");
        $stmt->execute([$convoid, $convoid, $id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result[0]);
    }
    public static function loadEmptyConvos()
    {
        $id = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("Select u.id as userid,firstname,lastname,pfpurl,temp.id as convoid from users u 
        INNER JOIN (SELECT * FROM friendships f1 WHERE f1.id not in 
            (SELECT DISTINCT convoid FROM messages WHERE convoid IN
                (SELECT f2.id FROM friendships f2 WHERE f2.user1=? OR f2.user2=?)
                ) AND f1.id IN (SELECT f3.id FROM friendships f3 WHERE user1=? OR user2=?)) temp 
                ON temp.user1 = u.id OR temp.user2 = u.id
                WHERE u.id<>?;");
        $stmt->execute([$id, $id, $id, $id, $id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    }
    public static function canAccessConvo()
    {
        $pdo = Database::connect();
        $id = AuthController::getTokenData()["user"]->id;
        $convoid = $_GET['convoid'];
        $stmt = $pdo->prepare("SELECT user1 FROM friendships WHERE id=? UNION SELECT user2 FROM friendships where id=?");
        $stmt->execute([$convoid, $convoid]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $canAccess = in_array($id, $result);
        echo json_encode($canAccess);
    }
    public static function sendMessage()
    {
        $data = json_decode(file_get_contents("php://input"));
        $id = AuthController::getTokenData()["user"]->id;
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO messages (convoid,text,userid) values (?,?,?)");
        $stmt->execute([$data->convoid, $data->text, $id]);
        $options = array(
            'cluster' => 'eu',
            'useTLS' => true
        );
        $pusher = new Pusher(
            '0eea2cdb4546d917675d',
            '18738ecc93343377dde6',
            '1548126',
            $options
        );
        try {
            $pusher->trigger("conversation-$data->convoid", 'new-message', "");
            $stmt = $pdo->prepare("SELECT * FROM (SELECT user1 as user FROM friendships WHERE id=? UNION SELECT user2 as user FROM friendships where id=?) as participants WHERE user<>?");
            $stmt->execute([$data->convoid, $data->convoid, $id]);
            $recepientId = $stmt->fetchColumn();
            $pusher->trigger("general-$recepientId", 'new-message', "");
            $pusher->trigger("general-$id", 'new-message', "");
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
