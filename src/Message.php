<?php
/**
 * Yii 2 log message object
 *
 * @see       https://github.com/sergeymakinen/yii2-log-message
 * @copyright Copyright (c) 2017 Sergey Makinen (https://makinen.ru)
 * @license   https://github.com/sergeymakinen/yii2-log-message/blob/master/LICENSE MIT License
 */

namespace sergeymakinen\log;

use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

/**
 * This class wraps a log message and exposes its properties as well as the current request/user details.
 * @property string $category the message category.
 * @property string|null $commandLine the command line.
 * @property bool $isConsoleRequest whether the current request is a console request.
 * @property string $level the text display of the message level.
 * @property string $prefix the messsage prefix string.
 * @property string|null $sessionId the session ID.
 * @property string|null $stackTrace the stack trace.
 * @property string $text the message text.
 * @property float $timestamp the message creation timestamp.
 * @property string|null $url the current absolute URL.
 * @property int|string|null $userId the user identity ID.
 * @property string|null $userIp the user IP address.
 */
class Message extends Object
{
    /**
     * @var array the message.
     */
    public $message;

    /**
     * @var Target the message target, a [[Target]] instance. May be `null`.
     */
    public $target;

    /**
     * @var bool whether the current request is a console request.
     */
    private $_isConsoleRequest;

    /**
     * Constructor.
     * @param array $message the message.
     * @param Target|null $target the message target, a [[Target]] instance.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(array $message, $target = null, $config = [])
    {
        $this->message = $message;
        $this->target = $target;
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        if ($this->target !== null && !$this->target instanceof Target) {
            throw new InvalidConfigException('`' . get_class($this) . '::target` should be an instance of `' . Target::className() . '`.');
        }
    }

    /**
     * Returns the message category.
     * @return string the message category.
     */
    public function getCategory()
    {
        return $this->message[2];
    }

    /**
     * Returns the command line.
     * @return string|null the command line, `null` if not available.
     */
    public function getCommandLine()
    {
        if (\Yii::$app === null || !$this->getIsConsoleRequest()) {
            return null;
        }

        if (isset($_SERVER['argv'])) {
            $params = $_SERVER['argv'];
        } else {
            $params = [];
        }
        return implode(' ', $params);
    }

    /**
     * Returns whether the current request is a console request.
     * @return bool whether the current request is a console request.
     * @throws InvalidConfigException if unable to determine.
     */
    public function getIsConsoleRequest()
    {
        if ($this->_isConsoleRequest === null && \Yii::$app !== null) {
            if (\Yii::$app->getRequest() instanceof \yii\console\Request) {
                $this->_isConsoleRequest = true;
            } elseif (\Yii::$app->getRequest() instanceof \yii\web\Request) {
                $this->_isConsoleRequest = false;
            }
        }
        if ($this->_isConsoleRequest === null) {
            throw new InvalidConfigException('Unable to determine if the application is a console or web application.');
        }

        return $this->_isConsoleRequest;
    }

    /**
     * Returns the text display of the message level.
     * @return string the text display of the message level.
     */
    public function getLevel()
    {
        return Logger::getLevelName($this->message[1]);
    }

    /**
     * Returns the string to be prefixed to the message.
     * @return string the messsage prefix string.
     */
    public function getPrefix()
    {
        if ($this->target !== null) {
            return $this->target->getMessagePrefix($this->message);
        } else {
            return '';
        }
    }

    /**
     * Returns the session ID.
     * @return string|null the session ID, `null` if not available.
     */
    public function getSessionId()
    {
        if (
            \Yii::$app !== null
            && \Yii::$app->has('session', true)
            && \Yii::$app->getSession() !== null
            && \Yii::$app->getSession()->getIsActive()
        ) {
            return \Yii::$app->getSession()->getId();
        } else {
            return null;
        }
    }

    /**
     * Returns the additional stack trace as a string.
     * @return string|null the stack trace, `null` if not available.
     */
    public function getStackTrace()
    {
        if (!isset($this->message[4]) || empty($this->message[4])) {
            return null;
        }

        $traces = array_map(function ($trace) {
            return "in {$trace['file']}:{$trace['line']}";
        }, $this->message[4]);
        return implode("\n", $traces);
    }

    /**
     * Returns the message text.
     * @return string the message text.
     */
    public function getText()
    {
        $text = $this->message[0];
        if (!is_string($text)) {
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string) $text;
            } else {
                $text = VarDumper::export($text);
            }
        }
        return $text;
    }

    /**
     * Returns the message creation timestamp.
     * @return float the message creation timestamp.
     */
    public function getTimestamp()
    {
        return $this->message[3];
    }

    /**
     * Returns the current absolute URL.
     * @return null|string the current absolute URL, `null` if not available.
     */
    public function getUrl()
    {
        if (\Yii::$app === null || $this->getIsConsoleRequest()) {
            return null;
        }

        return Url::current([], true);
    }

    /**
     * Returns the user identity ID.
     * @return int|string|null the user identity ID, `null` if not available.
     */
    public function getUserId()
    {
        if (
            \Yii::$app !== null
            && \Yii::$app->has('user', true)
            && \Yii::$app->getUser() !== null
        ) {
            $user = \Yii::$app->getUser()->getIdentity(false);
            if ($user !== null) {
                return $user->getId();
            }
        }
        return null;
    }

    /**
     * Returns the user IP address.
     * @return string|null the user IP address, `null` if not available.
     */
    public function getUserIp()
    {
        if (\Yii::$app === null || $this->getIsConsoleRequest()) {
            return null;
        }

        return \Yii::$app->getRequest()->getUserIP();
    }
}
