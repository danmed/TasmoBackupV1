<?php

namespace TasmoBackup\Config;

/**
 * Reads config.json from HA_addon folder and provides information
 */
final class HomeAssistantConfigReader
{
    private static $config;

    /**
     * Reads config from HA_addon folder
     */
    private static function readConfig(): void
    {
        $content = file_get_contents(__DIR__ . '/../../HA_addon/config.json');
        self::$config = json_decode($content, true);
    }

    /**
     * Provides config values
     */
    public static function getConfig(): array
    {
        if (null === self::$config) {
            self::readConfig();
        }
        return self::$config;
    }

    /**
     * Provide current addon version
     */
    public static function getVersion(): string
    {
        return self::getConfig()['version'] ?: '';
    }
}
