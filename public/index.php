<?php
require '../vendor/autoload.php';


use Api\Router;
use Api\Controllers\AuthController;
use Api\Controllers\AuthHelper;
use Api\Controllers\FriendshipController;
use Api\Controllers\PostController;
use Api\Controllers\UserController;

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding, Authorization");
header("Content-type:application/json");
header("Access-Control-Allow-Credentials: true");
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

$router = new Router();

$router->route("/test", function () {
    print_r(json_decode(file_get_contents("php://input")));
});
$router->route('/tokenBack', function () {
    echo AuthHelper::getBearerToken();
});
$router->route('/login', function () {
    AuthController::login();
});
$router->route('/register', function () {
    AuthController::register();
});
$router->route("/userdata",function(){
    AuthController::isAuth();
    UserController::getUserData();
});
$router->route("/uploadPicture",function(){
    AuthController::isAuth();
    UserController::uploadPicture();
});
$router->route("/uploadBanner",function(){
    AuthController::isAuth();
    UserController::uploadBanner();
});
$router->route('/authornot', function () {
    AuthController::isAuth();
    print_r(json_encode(["data"=>AuthController::getTokenData()['user']]));
});
$router->route("/updateUser",function(){
    AuthController::isAuth();
    UserController::updateUser();
});
$router->route("/createPost",function(){
    AuthController::isAuth();
    PostController::createPost();
});
$router->route("/loadUserPosts",function(){
    AuthController::isAuth();
    PostController::loadUserPosts();
});
$router->route("/deletePost",function(){
    AuthController::isAuth();
    PostController::deletePost();
});
$router->route("/likePost",function(){
    AuthController::isAuth();
    PostController::likePost();
});
$router->route("/unlikePost",function(){
    AuthController::isAuth();
    PostController::unlikePost();
});
$router->route("/getUserLikes",function(){
    AuthController::isAuth();
    PostController::getUserLikes();
});
$router->route("/loadLikes",function(){
    AuthController::isAuth();
    PostController::loadLikes();
});
$router->route("/uploadComment",function(){
    AuthController::isAuth();
    PostController::uploadComment();
});
$router->route("/loadComments",function(){
    AuthController::isAuth();
    PostController::loadComments();
});
$router->route("/loadFriends",function(){
    AuthController::isAuth();
    FriendshipController::loadFriends();
});
$router->route("/userLoadFriends",function(){
    AuthController::isAuth();
    FriendshipController::loadFriendsLoggedIn();
});
$router->route("/sentRequests",function(){
    AuthController::isAuth();
    FriendshipController::loadFriendRequestsSent();
});
$router->route("/receivedRequests",function(){
    AuthController::isAuth();
    FriendshipController::loadFriendRequestsReceived();
});
$router->route("/sendFriendRequest",function(){
    AuthController::isAuth();
    FriendshipController::sendFriendRequest();
});
$router->route("/removeFriendRequest",function(){
    AuthController::isAuth();
    FriendshipController::removeFriendRequest();
});
$router->route("/removeFriend",function(){
    AuthController::isAuth();
    FriendshipController::removeFriend();
});
$router->route("/declineFriendRequest",function(){
    AuthController::isAuth();
    FriendshipController::declineFriendRequest();
});
$router->route("/acceptFriendRequest",function(){
    AuthController::isAuth();
    FriendshipController::acceptFriendRequest();
});
$router->route("/searchUsers",function(){
    AuthController::isAuth();
    UserController::searchUser();
});
try {
    $router->resolve();
} catch (Exception $e) {
}
