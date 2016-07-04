<?php

namespace App\Console\Commands;

use Xpressengine\Plugin\Composer\ComposerFileWriter;
use Xpressengine\Plugin\PluginHandler;
use Xpressengine\Plugin\PluginProvider;

class PluginInstall extends PluginCommand
{
    /**
     * The console command name.
     * php artisan plugin:install [--no-activate] <plugin name> [<version>]
     *
     * @var string
     */
    protected $signature = 'plugin:install
                        {plugin_id : The plugin id for install}
                        {version? : The version of plugin for install}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install new plugin of XpressEngine';

    /**
     * Create a new controller creator command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param PluginHandler      $handler
     * @param PluginProvider     $provider
     * @param ComposerFileWriter $writer
     *
     * @return bool|null
     * @throws \Exception
     */
    public function fire(PluginHandler $handler, PluginProvider $provider, ComposerFileWriter $writer)
    {
        $this->init($handler, $provider, $writer);

        // php artisan plugin:install <plugin name> [<version>]

        $id = $this->argument('plugin_id');

        $version = $this->argument('version');

        // 플러그인이 이미 설치돼 있는지 검사
        if ($handler->getPlugin($id) !== null) {
            throw new \Exception('Already installed plug-in');
        }

        // 설치가능 환경인지 검사
        // - check writable of composer.plugin.json
        if (!is_writable($composerFile = storage_path('app/composer.plugins.json'))) {
            throw new \Exception("[$composerFile] You do not have write access to the file. To install the plug-in, you must have write permission of this file.");
        }

        // - check writable of plugins/ directory
        if (!is_writable($pluginDir = base_path('plugins'))) {
            throw new \Exception("[$pluginDir] You do not have write permissions to the directory. In order to install the plug-in, you must have write permissions for this directory.");
        }

        // 자료실에서 플러그인 정보 조회
        $pluginData = $provider->find($id);

        if ($pluginData === null) {
            throw new \Exception("Could not find the installation plug-in(".$id.") from the Market-place.");
        }

        $title = $pluginData->title;
        $name = $pluginData->name;

        if ($version) {
            $releaseData = $provider->findRelease($id, $version);
            if ($releaseData === null) {
                throw new \Exception("The version(".$version.") of the plug-in(".$id.") could not be found from the Market-place.");
            }
        }
        $version = $version ?: $pluginData->latest_release->version;

        // 플러그인 정보 출력
        $this->warn(PHP_EOL." Install plug-in information:");
        $this->line("  $title - $name:$version".PHP_EOL);

        // 안내 멘트 출력
        if ($this->input->isInteractive() && $this->confirm(
                "Download and install the plug-ins. \r\nThe above plug-ins can be downloaded together other plug-ins that are dependent, you may water it takes. \r\nDo you want to install the plug-in?"
            ) === false
        ) {
            return;
        }

        // - plugins require info 갱신
        $writer->resolvePlugins();

        // composer.plugins.json 업데이트
        // - require에 설치할 플러그인 추가
        $writer->install($name, $version);

        $writer->write();

        $vendorName = PluginHandler::PLUGIN_VENDOR_NAME;

        // composer update실행(composer update --prefer-lowest --with-dependencies xpressengine-plugin/*)
        $this->warn('Run the composer update. There is a maximum moisture is applied.');
        $this->line(" composer update --prefer-lowest --with-dependencies $vendorName/*");
        try {
            $result = $this->runComposer(base_path(), "update --prefer-lowest --with-dependencies $vendorName/*");
        } catch (\Exception $e) {
            ;
        }

        $this->warn('The execution of the composer is now complete.'.PHP_EOL);

        // composer.plugins.json 파일을 다시 읽어들인다.
        $writer->reload();

        // changed plugin list 정보 출력
        $changed = $this->getChangedPlugins($writer);
        $this->printChangedPlugins($changed);

        if (array_get($changed, 'installed.'.$name) === $version) {
            // 설치 성공 문구 출력
            $this->output->success("$title - $name:$version Plug-in installed");
        } elseif (array_get($changed, 'installed.'.$name)) {
            $this->output->success(
                "You install the plug-in of the \"".$name."\", but has now been installed on other versions(".array_get($changed, 'installed.'.$name)."). Because of the dependencies between plug-ins, there is a possibility that has been installed in the other version. Please check the plug-in dependencies."
            );
        } else {
            $this->output->error(
                "Do not install the plug-in of the $name:$version . Because of the dependencies between plug-ins, you may not be able to install. Please check the plug-in dependencies."
            );
        }
    }
}
