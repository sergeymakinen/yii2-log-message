<?php
/**
 * Yii 2 log message object
 *
 * @see       https://github.com/sergeymakinen/yii2-log-message
 * @copyright Copyright (c) 2017 Sergey Makinen (https://makinen.ru)
 * @license   https://github.com/sergeymakinen/yii2-log-message/blob/master/LICENSE MIT License
 */

namespace sergeymakinen\yii\logmessage;

use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\console\Request as ConsoleRequest;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;
use yii\web\Request as WebRequest;

/**
 * This class wraps a log message and exposes its properties as well as the current request/user details.
 * @property string $category message category.
 * @property string|null $commandLine command line.
 * @property bool $isConsoleRequest whether the current request is a console request.
 * @property string $level message level as a string.
 * @property string $prefix messsage prefix string.
 * @property string|null $sessionId session ID.
 * @property string|null $stackTrace stack trace as a string.
 * @property string $text message text.
 * @property float $timestamp message creation timestamp.
 * @property string|null $url absolute URL.
 * @property int|string|null $userId user identity ID.
 * @property string|null $userIp user IP address.
 */
class Message extends BaseObject
{
    /**
     * @var array raw message.
     */
    public $message;

    /**
     * @var Target message target.
     * Must be a [[Target]] instance or `null`.
     */
    public $target;

    /**
     * @var bool whether the current request is a console request.
     */
    private $_isConsoleRequest;

    /**
     * Constructor.
     * @param array $message message.
     * @param Target|null $target message target.
     * Must be a [[Target]] instance or `null`.
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
     * @return string message category.
     */
    public function getCategory()
    {
        return $this->message[2];
    }

    /**
     * Returns the command line.
     * @return string|null command line, `null` if not available.
     */
    public function getCommandLine()
    {
        if (\Yii::$app === null || !$this->getIsConsoleRequest()) {
            return null;
        }

        $params = [];
        if (isset($_SERVER['argv'])) {
            $params = $_SERVER['argv'];
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
            if (\Yii::$app->getRequest() instanceof ConsoleRequest) {
                $this->_isConsoleRequest = true;
            } elseif (\Yii::$app->getRequest() instanceof WebRequest) {
                $this->_isConsoleRequest = false;
            }
        }
        if ($this->_isConsoleRequest === null) {
            throw new InvalidConfigException('Unable to determine if the application is a console or web application.');
        }

        return $this->_isConsoleRequest;
    }

    /**
     * Returns the message level as a string.
     * @return string message level as a string.
     */
    public function getLevel()
    {
        return Logger::getLevelName($this->message[1]);
    }

    /**
     * Returns a string to be prefixed to the message.
     * @return string messsage prefix string.
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
     * @return string|null session ID, `null` if not available.
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
     * @return string|null stack trace, `null` if not available.
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
     * @return string message text.
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
     * @return float message creation timestamp.
     */
    public function getTimestamp()
    {
        return $this->message[3];
    }

    /**
     * Returns the current absolute URL.
     * @return null|string absolute URL, `null` if not available.
     */
    public function getUrl()
    {
        if (\Yii::$app === null || $this->getIsConsoleRequest()) {
            return null;
        }

        return \Yii::$app->getRequest()->getAbsoluteUrl();
    }

    /**
     * Returns the user identity ID.
     * @return int|string|null user identity ID, `null` if not available.
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
     * @return string|null user IP address, `null` if not available.
     */
    public function getUserIp()
    {
        if (\Yii::$app === null || $this->getIsConsoleRequest()) {
            return null;
        }

        return \Yii::$app->getRequest()->getUserIP();
    }
}
