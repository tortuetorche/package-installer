<?php namespace Rtablada\PackageInstaller;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PackageInstallCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'package:install';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Installs a package and sets configuration.';

	/**
	 * The ProviderCreator instance
	 *
	 * @var ProviderCreator
	 */
	protected $providerCreator;

	/**
	 * The package name
	 *
	 * @var string
	 */
	protected $packageName;

	/**
	 * The package version constraint
	 *
	 * @var string|null
	 */
	protected $packageVersion = null;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(ProviderCreator $providerCreator, PackageInstaller $installer)
	{
		parent::__construct();

		$this->providerCreator = $providerCreator;
		$this->installer = $installer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$package = $this->argument('package');
		// Calls composer require
		$this->call('package:require', compact('package'));

		$this->extractPackageInfos($package);

		$path = $this->getPackagePath();
		$provider = $this->providerCreator->buildProviderFromJsonFile($path);

		if (is_null($provider)) {
			return $this->comment('This package has no provides.json file.');
		}

		$this->installer->updateConfigurations($provider);
	}

	/**
	 * Returns path to provides.json for installed package
	 *
	 * @param  string $packageName
	 * @return string
	 */
	protected function getPackagePath($packageName = null)
	{
		$packageName = $packageName ?: $this->packageName;

		return base_path() . "/vendor/{$packageName}/provides.json";
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('package', InputArgument::REQUIRED, 'Name of the composer package and its version constraint to be installed.'),
		);
	}

	/**
	 * Extract package name and version constraint from the 'package' argument
	 * Following the composer convention:
	 *
	 *   foo/bar:1.0.0 or foo/bar=1.0.0 or "foo/bar 1.0.0"
	 *
	 * @return void
	 */
	protected function extractPackageInfos($packageArgument)
	{
		$package = preg_split("/[:= ]/", $packageArgument);
		$this->packageName = $package[0];
		if(array_key_exists(1, $package)) {
			$this->packageVersion = $package[1];
		}
	}

}
