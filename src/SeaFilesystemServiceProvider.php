<?php

namespace hinink\SeaFileStorage;

use hinink\SeaFileStorage\Plugins\Download;
use hinink\SeaFileStorage\Plugins\GetToken;
use hinink\SeaFileStorage\Plugins\UploadUrl;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Storage;

class SeaFilesystemServiceProvider extends ServiceProvider
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
			$sea_file_adapter = new SeaFileAdapter(
				$config['server'],
				$config['repo_id'],
				$config['token'],
				$config['username'],
				$config['password'],
			);
			$file_system      = new Filesystem($sea_file_adapter);
			$file_system->addPlugin(new UploadUrl());
			$file_system->addPlugin(new GetToken());
			return $file_system;
		});
	}
}