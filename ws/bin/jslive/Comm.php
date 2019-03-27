<?php

namespace ShameemReza;

/**
 * Description of Comm
 */
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Comm implements MessageComponentInterface
{

    protected $clients;
    private $rooms;
    private $expiryTime;
    private $maxUsers;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->visitors = [];
        $this->expiryTime = 30 * 60 * 1000;
        $this->maxUsers = 8;
    }

    /**
     * 
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
    }

    private function checkArray($array) {
        foreach ($array as $key => $value) {
            if ($array[$key] && (!$value['pageRef'] || $value['added'] + $this->expiryTime < time())) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * 
     * @param ConnectionInterface $from
     * @param type $msg
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        $action = $data->action;
        $room = isset($data->room) ? $data->room : "";


        if ($action == 'imOffline') {
            $visitors = $this->visitors[$room];

            if (isset($data->visitorId) && isset($visitors['visitorId']) && ($key = array_search($data->visitorId, $visitors['visitorId'])) !== false) {
                unset($this->visitors[$room][$key]);
                $this->visitors[$room] = $this->checkArray($this->visitors[$room]);
            }
        }

        if ($action == 'giveOnline') {
            $this->notifyWhoIsonline($room, $from);
        }

        if ($action == 'giveCountOnline') {
            $this->notifyWhoIsRoom($room, $from);
        }

        if ($action == 'setCallerInfo') {
            $visitors = $this->visitors[$room];

            if ($visitors) {
                foreach ($visitors as $visitor) {
                    if ($visitor->sessionId == @$data->sessionId) {
                        $visitor->name = @$data->callerInfo->name;
                    }
                }
            }
        }

        if (($action == 'subscribe') && $room) {
            //subscribe user to room only if he hasn't subscribed
            //if room exist and user is yet to subscribe, then subscibe him to room
            //OR
            //if room does not exist, create it by adding user to it
            if ((array_key_exists($room, $this->rooms) && !in_array($from, $this->rooms[$room])) || !array_key_exists($room, $this->rooms)) {
                if (isset($this->rooms[$room]) && count($this->rooms[$room]) >= $this->maxUsers) {
                    //maximum number of connection reached
                    $msg_to_send = json_encode(['action' => 'subRejected']);

                    $from->send($msg_to_send);
                } else {
                    $this->rooms[$room][] = $from; //subscribe user to room

                    $visitorId = isset($data->visitorId) ? $data->visitorId : "";
                    if ($visitorId) {
                        $vis = array('visitorId' => $visitorId, 'pageRef' => @$data->referrer, 'sessionId' => @$data->sessionId, 'added' => time());
                        $this->visitors[$room][] = $vis;
                        $this->visitors[$room] = $this->checkArray($this->visitors[$room]);
                    }

                    $this->notifyUsersOfConnection($room, $from, @$data->sessionId, @$data->visitorId);
                }
            } else {
                //tell user he has subscribed on another device/browser
                $msg_to_send = json_encode(['action' => 'subRejected']);

                $from->send($msg_to_send);
            }
        } else if ($room && isset($this->rooms[$room])) {
            //send to everybody subscribed to the room received except the sender
            foreach ($this->rooms[$room] as $client) {
                if ($client !== $from) {
                    $client->send($msg);
                }
            }
        }
    }

    /**
     * 
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove connection
        $this->clients->detach($conn);

        if (count($this->rooms)) {//if there is at least one room created
            foreach ($this->rooms as $room => $arr_of_subscribers) {//loop through the rooms
                foreach ($arr_of_subscribers as $key => $ratchet_conn) {//loop through the users connected to each room
                    if ($ratchet_conn == $conn) {//if the disconnecting user subscribed to this room
                        unset($this->rooms[$room][$key]); //remove him from the room
                        $this->visitors[$room] = $this->checkArray($this->visitors[$room]);
                        //notify other subscribers that he has disconnected
                        $this->notifyUsersOfDisconnection($room, $conn, $key);
                    }
                }
            }
        }
    }

    /**
     * 
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        //echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    /**
     * 
     * @param type $room
     * @param type $from
     */
    private function notifyUsersOfConnection($room, $from, $sessionId, $visitorId) {

        //echo "User subscribed to room ".$room ."\n";

        $msg_to_broadcast = json_encode(['action' => 'newSub', 'room' => $room, 'count' => count($this->visitors[$room]), 'sessionId' => $sessionId, 'visitorId' => $visitorId]);

        //notify user that someone has joined room
        foreach ($this->rooms[$room] as $client) {
            if ($client !== $from) {
                $client->send($msg_to_broadcast);
            }
        }
    }

    /**
     * 
     * @param type $room
     * @param type $from
     */
    private function notifyWhoIsonline($room, $from) {

        //echo "User subscribed to room ".$room ."\n";
        $msg_to_broadcast = json_encode(['action' => 'whoIsonline', 'room' => $room, 'visitors' => $this->visitors[$room]]);

        //notify user that someone has joined room
        foreach ($this->rooms[$room] as $client) {
            if ($client == $from) {
                $client->send($msg_to_broadcast);
            }
        }
    }

    /**
     * 
     * @param type $room
     * @param type $from
     */
    private function notifyWhoIsRoom($room, $from) {

        //echo "User subscribed to room ".$room ."\n";
        $msg_to_broadcast = json_encode(['action' => 'whoIsonline', 'room' => $room, 'count' => count($this->visitors[$room])]);

        //notify user that someone has joined room
        foreach ($this->rooms[$room] as $client) {
            if ($client == $from) {
                $client->send($msg_to_broadcast);
            }
        }
    }

    private function notifyUsersOfDisconnection($room, $from, $key) {

        $visitorId = null;
        if (isset($this->visitors[$room][$key])) {
            $visitorId = $this->visitors[$room][$key];
            unset($this->visitors[$room][$key]);
        }
        $msg_to_broadcast = json_encode(['action' => 'imOffline', 'room' => $room, 'visitors' => $visitorId, 'count' => count($this->visitors[$room])]);
        //notify user that remote has left the room
        foreach ($this->rooms[$room] as $client) {
            $client->send($msg_to_broadcast);
        }
    }

}
