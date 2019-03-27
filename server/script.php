<?php

include_once 'connect.php';

function checkLogin($user, $pass) {
    global $conn;
    $sql = 'SELECT * FROM users WHERE `username`="' . $user . '" AND `password`="' . md5($pass) . '"';
    if ($result = mysqli_query($conn, $sql)) {
        if (mysqli_num_rows($result) > 0) {
            return true;
            mysqli_free_result($result);
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function insertScheduling($agent, $visitor, $agenturl, $visitorurl, $password, $session, $datetime, $duration) {
    global $conn;
    $sql = "INSERT INTO rooms (agent, visitor, agenturl, visitorurl, password, roomId, datetime, duration) VALUES ('".$agent."', '".$visitor."', '".$agenturl."', '".$visitorurl."', '".$password."', '".$session."', '".$datetime."', '".$duration."')";
    if(mysqli_query($conn, $sql)){
        return true;
    } else{
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['type']) && $_POST['type'] == 'login') {
        echo checkLogin($_POST['email'], $_POST['password']);
    }
    if (isset($_POST['type']) && $_POST['type'] == 'scheduling') {
        echo insertScheduling($_POST['agent'], $_POST['visitor'], $_POST['agenturl'], $_POST['visitorurl'], $_POST['password'],  $_POST['session'], $_POST['datetime'],  $_POST['duration']);
    }
}
