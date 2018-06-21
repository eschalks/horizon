<?php

namespace Laravel\Horizon;

use Closure;
use Exception;

class Horizon
{
    /**
     * The callback that should be used to authenticate Horizon users.
     *
     * @var \Closure
     */
    public static $authUsing;

    /**
     * The Slack notifications webhook URL.
     *
     * @var string
     */
    public static $slackWebhookUrl;

    /**
     * The Slack notifications channel.
     *
     * @var string
     */
    public static $slackChannel;

    /**
     * The SMS notifications phone number.
     *
     * @var string
     */
    public static $smsNumber;

    /**
     * The email address for notifications.
     *
     * @var string
     */
    public static $email;

    /**
     * The database configuration methods.
     *
     * @var array
     */
    public static $databases = [
        'Jobs', 'Supervisors', 'CommandQueue', 'Tags',
        'Metrics', 'Locks', 'Processes',
    ];

    /**
     * Places in the configuration where Redis connection information may be found.
     *
     * @var array
     */
    public static $configKeys = [
        'database.redis',
        'database.redis.clusters',
    ];

    /**
     * Determine if the given request can access the Horizon dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate Horizon users.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function auth(Closure $callback)
    {
        static::$authUsing = $callback;

        return new static;
    }

    /**
     * Configure the Redis databases that will store Horizon data.
     *
     * @param  string  $connection
     * @return void
     * @throws Exception
     */
    public static function use($connection)
    {
        $config = null;
        foreach(self::$configKeys as $configKey) {
            if (!is_null($config = config("{$configKey}.{$connection}"))) {
                self::storeHorizonConfiguration($configKey, $config);
                return;
            }
        }

        throw new Exception("Redis connection [{$connection}] has not been configured.");
    }

    /**
     * Specify the email address to which email notifications should be routed.
     *
     * @param  string  $email
     * @return static
     */
    public static function routeMailNotificationsTo($email)
    {
        static::$email = $email;

        return new static;
    }

    /**
     * Specify the webhook URL and channel to which Slack notifications should be routed.
     *
     * @param  string  $url
     * @param  string  $channel
     * @return static
     */
    public static function routeSlackNotificationsTo($url, $channel = null)
    {
        static::$slackWebhookUrl = $url;
        static::$slackChannel = $channel;

        return new static;
    }

    /**
     * Specify the phone number to which SMS notifications should be routed.
     *
     * @param  string  $number
     * @return static
     */
    public static function routeSmsNotificationsTo($number)
    {
        static::$smsNumber = $number;

        return new static;
    }

    /**
     * Copies the provided configuration to a new connection that will be used by Horizon.
     *
     * @param string $configKey
     * @param array $config
     */
    private static function storeHorizonConfiguration($configKey, $config)
    {
        config(["{$configKey}.horizon" => array_merge($config, [
            'options' => [
                'prefix' => config('horizon.prefix') ?: 'horizon:',
            ],
        ])]);
    }
}
