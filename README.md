# Yii 2 log message object

Log message object that wraps a log message and exposes its properties as well as the current request/user details to Yii 2 log targets.

[![Code Quality](https://img.shields.io/scrutinizer/g/sergeymakinen/yii2-log-message.svg?style=flat-square)](https://scrutinizer-ci.com/g/sergeymakinen/yii2-log-message) [![Build Status](https://img.shields.io/travis/sergeymakinen/yii2-log-message.svg?style=flat-square)](https://travis-ci.org/sergeymakinen/yii2-log-message) [![Code Coverage](https://img.shields.io/codecov/c/github/sergeymakinen/yii2-log-message.svg?style=flat-square)](https://codecov.io/gh/sergeymakinen/yii2-log-message) [![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9900d5c1-2a54-4de4-9184-7815e1b22650.svg?style=flat-square)](https://insight.sensiolabs.com/projects/9900d5c1-2a54-4de4-9184-7815e1b22650)

[![Packagist Version](https://img.shields.io/packagist/v/sergeymakinen/yii2-log-message.svg?style=flat-square)](https://packagist.org/packages/sergeymakinen/yii2-log-message) [![Total Downloads](https://img.shields.io/packagist/dt/sergeymakinen/yii2-log-message.svg?style=flat-square)](https://packagist.org/packages/sergeymakinen/yii2-log-message) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Installation

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```bash
composer require "sergeymakinen/yii2-log-message:^2.0"
```

or add

```json
"sergeymakinen/yii2-log-message": "^2.0"
```

to the require section of your `composer.json` file.

## Usage

Let's take a look at the [Slack log target](https://github.com/sergeymakinen/yii2-slack-log) code excerpt:

```php
// ...
// $message is the sergeymakinen\yii\logmessage\Message class instance
$attachment = [
    'fallback' => $this->encode($this->formatMessage($message->message)),
    'title' => ucwords($message->level),
    'fields' => [],
    'text' => "```\n" . $this->encode($message->text . "\n```",
    'footer' => static::className(),
    'ts' => (int) round($message->timestamp),
    'mrkdwn_in' => [
        'fields',
        'text',
    ],
];
if ($message->isConsoleRequest) {
    $attachment['author_name'] = $message->commandLine;
} else {
    $attachment['author_name'] = $attachment['author_link'] = $message->url;
}
if (isset($this->colors[$message->message[1]])) {
    $attachment['color'] = $this->colors[$message->message[1]];
}
$this
    ->insertField($attachment, 'Level', $message->level, true, false)
    ->insertField($attachment, 'Category', $message->category, true)
    ->insertField($attachment, 'Prefix', $message->prefix, true)
    ->insertField($attachment, 'User IP', $message->userIp, true, false)
    ->insertField($attachment, 'User ID', $message->userId, true, false)
    ->insertField($attachment, 'Session ID', $message->sessionId, true)
    ->insertField($attachment, 'Stack Trace', $message->stackTrace, false);
// ...
```
