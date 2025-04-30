<?php

namespace Nerdcel\OAuth2Email;

use Kirby\Cms\App;
use Kirby\Toolkit\Dir;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;


class Log
{
    private App $kirby;
    private ?string $storage;
    private static ?Log $instance = null;
    private Logger $monolog;

    /**
     * @return Log
     */
    public static function singleton(string $channel): Log
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($channel);
        }

        return self::$instance;
    }

    private function __construct(string $channel = 'debug')
    {
        $this->kirby = App::instance();
        $this->storage = ($this->kirby->root('logs') ?? $this->kirby->root('site').'/logs').DIRECTORY_SEPARATOR.'oauth2-email';

        if (! is_dir($this->storage)) {
            Dir::make($this->storage);
        }

        $logFile = $this->storage.DIRECTORY_SEPARATOR.$channel.'-'.date('Y-m-d').'.log';

        // create a log channel
        $this->monolog = new Logger($channel, [
            new RotatingFileHandler($logFile, 7, Level::Debug),
        ]);
    }

    public function it($message, $context = []): void
    {
        $this->monolog?->info($message, $context);
    }
}
