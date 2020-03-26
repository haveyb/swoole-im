<?php

namespace WebIM;

use Swoole\WebSocket\Server;

class WebSocket
{
    private $server;

    private $table;

    protected $config;

    CONST IP = '0.0.0.0';
    CONST PORT = 9501;

    // 头像列表
    private $avatars = [
        './images/avatar/1.jpg',
        './images/avatar/2.jpg',
        './images/avatar/3.jpg',
        './images/avatar/4.jpg',
        './images/avatar/5.jpg',
        './images/avatar/6.jpg'
    ];

    // 用户名列表
    private $names = ['科比', '库里', 'KD', 'KG', '乔丹', '邓肯', '格林', '汤普森', '伊戈达拉', '麦迪', '艾弗森', '卡哇伊'];

    public function __construct()
    {
        // 创建内存表，进程关闭后自动释放
        $this->createTable();
    }

    /**
     * 启动
     */
    public function run()
    {
        $this->server = new Server(self::IP, self::PORT);

        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);

        $this->server->start();
    }

    /**
     * @param Server $server
     * @param $request
     */
    public function onOpen(Server $server, $request)
    {
        $user = [
            'fd' => $request->fd,
            'name' => $this->names[rand(0, count($this->names)-1)] . $request->fd,
            'avatar' => $this->avatars[rand(0, count($this->avatars)-1)]
        ];
        $this->table->set($request->fd, $user);

        $server->push($request->fd, json_encode(
                array_merge(['user' => $user], ['all' => $this->allUser()], ['type' => 'openSuccess'])
            )
        );
        $this->pushMessage($server, "欢迎".$user['name']."进入聊天室", 'open', $request->fd);
    }

    /**
     * @param Server $server
     * @param $frame
     */
    public function onMessage(Server $server, $frame)
    {
        $this->pushMessage($server, $frame->data, 'message', $frame->fd);
    }


    /**
     * @param Server $server
     * @param $fd
     */
    public function onClose(Server $server, $fd)
    {
        $user = $this->table->get($fd);
        $this->pushMessage($server, $user['name']."离开聊天室", 'close', $fd);
        $this->table->del($fd);
    }

    private function allUser()
    {
        $users = [];
        foreach ($this->table as $row) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * 遍历发送消息
     *
     * @param Server $server
     * @param $message
     * @param $messageType
     * @param int $skip
     */
    private function pushMessage(Server $server, $message, $messageType, $frameFd)
    {
        $message = htmlspecialchars($message);
        $datetime = date('Y-m-d H:i:s', time());
        $user = $this->table->get($frameFd);
        foreach ($this->table as $row) {
            if ($frameFd == $row['fd']) {
                continue;
            }
            $server->push($row['fd'], json_encode([
                    'type' => $messageType,
                    'message' => $message,
                    'datetime' => $datetime,
                    'user' => $user
                ])
            );
        }
    }

    /**
     * 创建内存表
     */
    private function createTable()
    {
        $this->table = new \swoole_table(1024);
        $this->table->column('fd', \swoole_table::TYPE_INT);
        $this->table->column('name', \swoole_table::TYPE_STRING, 255);
        $this->table->column('avatar', \swoole_table::TYPE_STRING, 255);
        $this->table->create();
    }
}