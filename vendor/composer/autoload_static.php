<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfa8a6a6fed38f1d45842b14769b5150e
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'ODS\\' => 4,
        ),
        'A' => 
        array (
            'Appsero\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ODS\\' => 
        array (
            0 => __DIR__ . '/..' . '/oberonlai/wp-metabox/src',
        ),
        'Appsero\\' => 
        array (
            0 => __DIR__ . '/..' . '/appsero/client/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfa8a6a6fed38f1d45842b14769b5150e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfa8a6a6fed38f1d45842b14769b5150e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfa8a6a6fed38f1d45842b14769b5150e::$classMap;

        }, null, ClassLoader::class);
    }
}
