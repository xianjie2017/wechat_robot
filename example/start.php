<?php
include(dirname(dirname(__FILE__ )) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Message\Formatter\Image;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Task\TaskQueue;

$robot = new Robot([
    'cookie_path' => __DIR__,
    'log_path' => __DIR__ . DIRECTORY_SEPARATOR . 'message_log.txt',
    'json_path' => __DIR__ . DIRECTORY_SEPARATOR . 'json.txt',
]);

$robot->onMessage(function (Message $message) use ($robot) {

    //不给自己回复消息
    if ($message->getFromUserName() == Account::username()) {
        return false;
    }

    //只给体验群发消息
    $white_list = [
        '机器人体验群',
        '杨杰',
        'happyday'
    ];

    $member = $robot->getConcat()->getByUserName($message->getFromUserName());

    if (!$member) {
        return false;
    }

    if (!in_array($member->getRemarkName(), $white_list)) {
        return false;
    }

    if ($message instanceof Text) {
        try {
            //图灵机器人自动回复
            TaskQueue::run('RobotReply', [
                'username' => $message->getFromUserName(),
                'from_message' => $message->string(),
                'userid' => md5($message->getFromUserName())
            ]);

            //普通发送消息
            /*
            TaskQueue::run('SendMessage', [
                'username' => $message->getFromUserName(),
                'content' => '消息主体'
            ]);
            */
        } catch (Exception $e) {
            Console::log("发送消息失败");
        }
    } else {
        try {
            TaskQueue::run('SendMessage', [
                'username' => $message->getFromUserName(),
                'content'  => '你发的是什么鬼，我看不懂啦。'
            ]);
        } catch (Exception $e) {
            Console::log("发送消息失败");
        }
    }

    return true;
});

$robot->run();