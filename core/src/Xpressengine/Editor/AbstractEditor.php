<?php
/**
 * AbstractEditor
 *
 * PHP version 5
 *
 * @category    Editor
 * @package     Xpressengine\Editor
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Editor;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\Plugin\ComponentInterface;
use Xpressengine\Plugin\ComponentTrait;
use Xpressengine\Skin\SkinHandler;
use Xpressengine\Support\MobileSupportTrait;
use Xpressengine\Permission\Instance;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Auth\Access\Gate;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AbstractEditor
 *
 * @category    Editor
 * @package     Xpressengine\Editor
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */
abstract class AbstractEditor implements ComponentInterface
{
    use ComponentTrait, MobileSupportTrait;

    /**
     * EditorHandler instance
     *
     * @var EditorHandler
     */
    protected $editors;

    protected $urls;

    protected $gate;

    protected $skins;

    /**
     * Instance identifier
     *
     * @var string
     */
    protected $instanceId;

    /**
     * ConfigEntity instance
     *
     * @var ConfigEntity|null
     */
    protected $config;

    /**
     * Given arguments for the editor
     *
     * @var array
     */
    protected $arguments = [];

    protected $files = [];

    /**
     * Indicates if used only javascript.
     *
     * @var bool
     */
    protected $scriptOnly = false;

    /**
     * The registered tools for the editor
     *
     * @var AbstractTool[]
     */
    protected $tools;

    /**
     * Default editor options
     *
     * @var array
     */
    protected $defaultOptions = [
        'contentDomName' => 'content',
        'contentDomId' => 'xeContentEditor',
        'contentDomOptions' => [
            'class' => 'form-control',
            'rows' => '20',
            'cols' => '80'
        ],
        'editorOptions' => [],
    ];

    protected $fileInputName = '_files';

    protected $tagInputName = '_tags';

    /**
     * AbstractEditor constructor.
     *
     * @param EditorHandler $editors    EditorHandler instance
     * @param string        $instanceId Instance identifier
     */
    public function __construct(EditorHandler $editors, UrlGenerator $urls, Gate $gate, SkinHandler $skins, $instanceId)
    {
        $this->editors = $editors;
        $this->urls = $urls;
        $this->gate = $gate;
        $this->skins = $skins;
        $this->instanceId = $instanceId;
    }

    public function setConfig(ConfigEntity $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set arguments for the editor
     *
     * @param array $arguments arguments
     * @return $this
     */
    public function setArguments($arguments = [])
    {
        $this->arguments = $arguments;

        if ($arguments === false) {
            $this->scriptOnly = true;
        }

        return $this;
    }

    public function setFiles($files = [])
    {
        $this->files = $files;
    }

    /**
     * Get options
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge($this->defaultOptions, $this->arguments);
    }

    /**
     * Get a key string for the config
     *
     * @param string $instanceId instance identifier
     * @return string
     * 
     * @deprecated 
     */
    public static function getConfigKey($instanceId)
    {
        return static::getId() . '.' . $instanceId;
    }

    /**
     * Get a editor name
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Get config data for the editor
     *
     * @return array
     */
    public function getConfigData()
    {
        $data = array_except($this->config->all(), 'tools');
        $data['fontFamily'] = isset($data['fontFamily']) ? array_map(function ($v) {
            return trim($v);
        }, explode(',', $data['fontFamily'])) : [];
        $data['extensions'] = isset($data['extensions']) ? array_map(function ($v) {
            return trim($v);
        }, explode(',', $data['extensions'])) : [];
        $instance = new Instance($this->editors->getPermKey($this->instanceId));
        $data['perms'] = [
            'html' => $this->gate->allows('html', $instance),
            'tool' => $this->gate->allows('tool', $instance),
            'upload' => $this->gate->allows('upload', $instance),
        ];

        $data['files'] = $this->files;

        return $data;
    }

    /**
     * Get activated tool's identifier for the editor
     *
     * @return array
     */
    public function getActivateToolIds()
    {
        return $this->config->get('tools', []);
    }

    /**
     * Load tools
     *
     * @return void
     */
    protected function loadTools()
    {
        foreach ($this->getTools() as $tool) {
            $tool->initAssets();
        }
    }

    /**
     * Get activated tools for the editor
     *
     * @return AbstractTool[]
     */
    public function getTools()
    {
        if ($this->tools === null) {
            $this->tools = [];
            foreach ($this->getActivateToolIds() as $toolId) {
                if ($tool = $this->editors->getTool($toolId, $this->instanceId)) {
                    $this->tools[] = $tool;
                }
            }
        }

        return $this->tools;
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $this->loadTools();

        $htmlString = [];
        if ($this->scriptOnly === false) {
            $options = $this->getOptions();
            
            $options = array_merge($options, [
                'editorOptions' => [
                    'fileUpload' => [
                        'upload_url' => $this->urls->route('editor.file.upload'),
                        'source_url' => $this->urls->route('editor.file.source'),
                        'download_url' => $this->urls->route('editor.file.download'),
                        'destroy_url' => $this->urls->route('editor.file.destroy'),
                    ],
                    'suggestion' => [
                        'hashtag_api' => $this->urls->route('editor.hashTag'),
                        'mention_api' => $this->urls->route('editor.mention'),
                    ],
                ]
            ]);

            $htmlString[] = $this->getContentHtml(array_get($options, 'content'), $options);
            $htmlString[] = $this->getEditorScript($options);
        }

        return implode('', $htmlString);
    }

    /**
     * Compile the raw content to be useful
     *
     * @param string $content content
     * @return string
     */
    public function compile($content)
    {
        $content = $this->hashTag($content);
        $content = $this->mention($content);
        $content = $this->link($content);
        
        return $this->compileBody($content) . $this->getFileView();
    }

    abstract protected function compileBody($content);

    protected function getFileView()
    {
        if (count($this->files) < 1) {
            return '';
        }

        return $this->skins->getAssigned('editor')->setView('files')->setData(['files' => $this->files])->render();
    }

    /**
     * Get a content html tag string
     *
     * @param string $content content
     * @param array  $options dom options
     * @return string
     */
    protected function getContentHtml($content, $options)
    {
        $contentHtml = [];
        $contentHtml[] = '<textarea ';
        $contentHtml[] = 'name="' . $options['contentDomName'] . '" ';
        $contentHtml[] = 'id="' . $options['contentDomId'] . '" ';
        $contentHtml[] = $this->getContentDomHtmlOption($options['contentDomOptions']);
        $contentHtml[] = ' placeholder="' . xe_trans('xe::content') . '">';
        $contentHtml[] = $content;
        $contentHtml[] = '</textarea>';

        return implode('', $contentHtml);
    }

    /**
     * Get attributes string for content html tag
     *
     * @param array $domOptions dom options
     * @return string
     */
    protected function getContentDomHtmlOption($domOptions)
    {
        $optionsString = '';
        foreach ($domOptions as $key => $val) {
            $optionsString.= "$key='{$val}' ";
        }

        return $optionsString;
    }

    /**
     * Get script for running the editor
     *
     * @param array $options options
     * @return mixed
     */
    protected function getEditorScript(array $options)
    {
        $editorScript = '
        <script>
            $(function() {
                XEeditor.getEditor(\'%s\').create(\'%s\', %s, %s, %s);
            });
        </script>';

        return sprintf(
            $editorScript,
            $this->getName(),
            $options['contentDomId'],
            json_encode($options['editorOptions']),
            json_encode($this->getConfigData()),
            json_encode($this->getTools())
        );
    }

    protected function hashTag($content)
    {
        $tags = $this->getData($content, '.__xe_hashtag');
        foreach ($tags as $tag) {
            $word = ltrim($tag['text'], '#');
            $content = str_replace(
                $tag['html'],
                sprintf('<a href="#%s" class="__xe_hashtag" target="_blank">#%s</a>', $word, $word),
                $content
            );
        }

        return $content;
    }

    protected function mention($content)
    {
        $mentions = $this->getData($content, '.__xe_mention', 'data-id');
        foreach ($mentions as $mention) {
            $name = ltrim($mention['text'], '@');
            $content = str_replace(
                $mention['html'],
                sprintf(
                    '<span role="button" class="__xe_member __xe_mention" data-id="%s" data-text="%s">@%s</span>',
                    $mention['data-id'],
                    $name,
                    $name
                ),
                $content
            );
        }

        return $content;
    }

    protected function link($content)
    {
        return $content;
    }

    private function getData($content, $selector, $attributes = [])
    {
        $attributes = !is_array($attributes) ? [$attributes] : $attributes;

        $crawler = $this->createCrawler($content);
        return $crawler->filter($selector)->each(function ($node, $i) use ($attributes) {
            $dom = $node->getNode(0);
            $data = [
                'html' => $dom->ownerDocument->saveHTML($dom),
                'inner' => $node->html(),
                'text' => $node->text(),
            ];

            foreach ($attributes as $attr) {
                $data[$attr] = $node->attr($attr);
            }

            return $data;
        });
    }

    private function createCrawler($content)
    {
        return new Crawler($content);
    }

    public function getFileInputName()
    {
        return $this->fileInputName;
    }

    public function getTagInputName()
    {
        return $this->tagInputName;
    }
}
