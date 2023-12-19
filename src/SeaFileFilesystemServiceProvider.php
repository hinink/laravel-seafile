<?php

namespace hinink\SeaFileStorage;

use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Storage;

class SeaFileFilesystemServiceProvider extends ServiceProvider
{
	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	public function boot()
	{
		Storage::extend('seaFile', function ($app, $config) {
			$sea_file_adapter = new SeaFileAdapter($config['server']);
			return new Filesystem($sea_file_adapter);
		});
	}
}
