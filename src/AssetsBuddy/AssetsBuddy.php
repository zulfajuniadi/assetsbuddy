<?php

/**
 * Assets Buddy : A super simple minifier class. Supports Javascript, CSS and Templates!
 * @version v0.0.1
 * @author Zulfa Juniadi bin Zulkifli <zulfajuniadi@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @link https://github.com/zulfajuniadi/assetsbuddy Source code and Readme
 */

namespace AssetsBuddy;

class AssetsBuddy
{

    /* ==== START USER CONFIG ==== */

    /**
     * When $mergejs is set to true, all enqueued javascript files will be merged and named as per $mergedFileNamejs
     * set below. If set to false, all javascript files will retain their original file name.
     * @var boolean true
     */
    private $mergejs = true;

    /**
     * When $mergecss is set to true, all enqueued cascading style sheet files will be merged and named as per
     * $mergedFileNamecss set below. If set to false, all javascript files will retain their original file name.
     * @var boolean true
     */
    private $mergecss = true;

    /**
     * $mergedFileNamejs is used to set the merged javascript file name. This setting is ignored if the $mergejs value
     * above is set to false.
     * @var string 'style.min.css'
     */
    private $mergedFileNamejs = 'scripts.min.js';

    /**
     * $mergedFileNamecss is used to set the merged cascading style sheet file name. This setting is ignored if the
     * $mergecss value above is set to false.
     * @var string 'style.min.css'
     */
    private $mergedFileNamecss = 'style.min.css';

    /**
     * When $minifyjs is set to true, all enqueued javascript files will be minified.
     * @var boolean true
     */
    private $minifyjs = true;

    /**
     * When $minifycss is set to true, all enqueued cascading style sheet files will be minified.
     * @var boolean true
     */
    private $minifycss = true;

    /**
     * When $minifyTmpl is set to true, all enqueued template files (HTML) will be minified.
     * @var boolean true
     */
    private $minifytmpl = true;

    /**
     * $templateTypeAttr is used as the type attribute of the wrapping script tag for the template.
     * @var string 'text/x-handlebars'
     */
    private $templateTypeAttr = 'text/x-handlebars';

    /**
     * When $outputTemplateAsHTML is set to true, the template HTML string will be rendered inside the <script> tag. If
     * set to false, the file will be pointed using the src attribute in the <script> tag.
     * @var boolean true
     */
    private $outputTemplateAsHTML = true;

    /**
     * When $devModejs is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get a
     * fresh version of the file off the server.
     * @var boolean false
     */
    private $devModejs = false;

    /**
     * When $devModecss is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get
     * a fresh version of the file off the server.
     * @var boolean false
     */
    private $devModecss = false;

    /**
     * When $devModetmpl is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get
     * a fresh version of the file off the server.
     * @var boolean false
     */
    private $devModetmpl = false;

    /**
     * $baseDirectory is relative to your working directory. If in doubt, run getcwd() to get your current working
     * directory.
     * @var string '/'
     */
    private $baseUrl = '/';

    /**
     * $cacheDirectory is relative to your $baseDirectory set above
     * @var string 'cache'
     */
    private $cacheDirectory = 'cache/';

    /**
     * $mincache is relative to your $baseDirectory set above
     * @var string '.mincache'
     */
    private $mincache = '.mincache';

    /**
     * $assetDirectory is relative to your working directory. If in doubt, run getcwd() to get your current working
     * directory.
     * @var string './'
     */
    private $assetDirectory = './';

    /**
     * $skipStartup flag. When this is set to true, all directory checking and changed setting checks will be skipped.
     * Use only during production!.
     * @var boolean false
     */
    private $skipStartup = false;

    /* ===== END USER CONFIG ===== */

    // Here be dragons! Do not edit the following lines

    private $files = array();
    private $cacheDirectoryjs;
    private $cacheDirectorycss;
    private $cacheDirectorytmpl;
    private $assetDirectoryjs;
    private $assetDirectorycss;
    private $assetDirectorytmpl;
    private $isDirtyjs;
    private $isDirtycss;
    private $isDirtytmpl;
    private $mergetmpl = false;
    private static $Instance;

    /**
     * AssetsBuddy::enqueue method.
     * @access public
     * @param string $files javascript / cascading stylesheets files to enqueue. The files are relative to your working
     * directory If in doubt, run getcwd() to get your current working directory. The parameters of this method are
     * overloadable. If one of the files cannot be read by PHP, it will throw an Exception. Files enqueued will be
     * rendered according to the order it was enqueued. Allowed file extensions : .js, .css, .tmpl.
     * @example AssetsBuddy::enqueue('js/jquery.js', 'js/underscore.js', 'js/backbone.js', 'dashboard.tmpl', 'style.css');
     * @return void
     */
    public static function enqueue()
    {
        $T = self::getInstance();

        // Overloadable Arguments
        $num = func_num_args();

        if($num > 0) {
            for($i = 0; $i < $num; $i++){
                $file = app_path() . func_get_arg($i);
                if(file_exists($file)){
                    $fileType = $T->getFileType($file);
                    if(!isset($T->files[$fileType])) {
                        $T->files[$fileType] = array();
                    }
                    if(!in_array($file, $T->files[$fileType])) {
                        $T->checkForDirty($file, $fileType);
                        $T->files[$fileType][] = $file;
                    }
                } else {
                    throw new Exception("{$file} does not exists");
                }
            }
        }
    }

    /**
     * AssetsBuddy::dequeue method.
     * @access public
     * @param string $files javascript / cascading stylesheets files to dequeue. The files are relative to your working
     * directory If in doubt, run getcwd() to get your current working directory. The parameters of this method are
     * overloadable. All instances of the file will be removed from the AssetsBuddy::enqueue list;
     * @example AssetsBuddy::dequeue('style.css');
     * @return void
     */
    public static function dequeue()
    {
        $T = self::getInstance();

        // Overloadable Arguments
        $num = func_num_args();

        if($num > 0) {
            for($i = 0; $i < $num; $i++){
                $file = app_path() . func_get_arg($i);
                $fileType = $T->getFileType($file);
                if(in_array($fileType, $this->supportedTypes)) {
                    $tempFiles = array();
                    foreach ($T->files[$fileType] as $enqueuedFile) {
                        if($enqueuedFile !== $file) {
                            $tempFiles[] = $file;
                        }
                    }
                    $T->files[$fileType] = $tempFiles;
                }
            }
        }
    }

    /**
     * AssetsBuddy::render method.
     * @access public
     * @param string $fileType Supported filetypes are 'js' and 'css'. When running AssetsBuddy::render('js'), each
     * javascript file enqueued using the AssetsBuddy::enqueue() method will be rendered as a src attribute within a
     * <script> tag while AssetsBuddy::render('css') will render cascading style sheet files enqueued as a href attribute
     * inside a <link rel="stylesheet"> tag.
     * @param string $namespace will be appended infront of both javascript and css files if they are merged into a
     * singular file.
     * @example AssetsBuddy::render('css', 'dashboard'); outputs <link rel="stylesheet" src="dashboard.style.css">
     * @return string generated asset html tags
     */
    public static function render($fileType, $namespace = null)
    {
        $T = self::getInstance();
        $mergedFileName = 'mergedFileName'.$fileType;
        $isDirty = 'isDirty'.$fileType;
        $merge = 'merge'.$fileType;
        $cacheDirectory = 'cacheDirectory'.$fileType;
        $assetDirectory = 'assetDirectory'.$fileType;

        if(isset($T->files[$fileType])) {
            $str = '';
            $ofiles = array();
            $devMode = 'devMode' . $fileType;
            $namespace = ($namespace) ? $namespace . '.' : '';
            foreach ($T->files[$fileType] as $filePath) {
                $fileParts = explode(DIRECTORY_SEPARATOR, $filePath);
                $fileName = array_pop($fileParts);
                if($T->$merge) {
                    if($T->$isDirty ||
                        !file_exists($T->$assetDirectory . $namespace . $T->$mergedFileName)){
                        $cache_name = md5($filePath);
                        $cachedFileName = $T->$cacheDirectory . $cache_name;
                        $str .= file_get_contents($cachedFileName);
                        $str .= ($fileType === 'js') ? ";\n\n" : "\n\n";
                    }
                } else {
                    $cache_name = md5($filePath);
                    $cachedFileName = $T->$cacheDirectory . $cache_name;
                    $devModeStr = ($T->$devMode) ? '?' . $T->randAlphaNum() : '';
                    if ($fileType === 'tmpl' && $T->outputTemplateAsHTML) {
                        $templateName = str_replace('.tmpl', '', $fileName);
                        $ofiles[] = array('name' => $templateName, 'contents' => file_get_contents($cachedFileName));
                    } else {
                        $ofiles[] = $T->baseUrl.$fileType.'/min/'.$fileName . $devModeStr;
                        if($T->$isDirty || !file_exists($T->$assetDirectory.$fileName)) {
                            copy($cachedFileName, $T->$assetDirectory.$fileName);
                        }
                    }
                }
            }
            if($T->$merge) {
                if($T->$isDirty ||
                    !file_exists($T->$assetDirectory.$namespace.$T->$mergedFileName)) {
                    file_put_contents($T->$assetDirectory.$namespace.$T->$mergedFileName, $str);
                }
                $devModeStr = ($T->$devMode) ? '?' . $T->randAlphaNum() : '';
                $ofiles[] = $T->baseUrl.$fileType.'/min/'.$namespace.$T->$mergedFileName . $devModeStr;
            }
        }
        return $T->genHTML($ofiles, $fileType);
    }

    /**
     * AssetsBuddy::renderAll method.
     * @access public
     * @param string $namespace will be appended infront of all files if they are merged into a singular file.
     * @param boolean $reset if set to true, the reset method will be called after generation.
     * @example AssetsBuddy::renderAll('dashboard', true);
     * @return array of generated asset strings
     */
    public static function renderAll($namespace = '', $reset = false)
    {
        $ret = array();
        foreach ($this->supportedTypes as $type) {
            $ret[$type] = self::render($type, $namespace);
        }
        if($reset) {
            self::reset();
        }
        return $ret;
    }

    /**
     * AssetsBuddy::reset method. This method is to reset the internal Instance variable to null.
     * @access public
     * @example AssetsBuddy::reset();
     * @return array of generated asset strings
     */
    public static function reset()
    {
        self::$Instance = null;
    }

    public static function clearCache()
    {
        $T = self::getInstance();
        $T->internalClearCache();
    }

    private function randAlphaNum($random_string_length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        for ($i = 0; $i < $random_string_length; $i++) {
          $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    private function genHTML($files, $fileType)
    {
        $str = '';
        foreach ($files as $file) {
            switch ($fileType) {
                case 'js':
                    $str .= "<script type=\"text/javascript\" src=\"{$file}\"></script>\n";
                    break;
                case 'css':
                    $str .= "<link rel=\"stylesheet\" href=\"{$file}\">\n";
                    break;
                case 'tmpl':
                    if($this->outputTemplateAsHTML) {
                        $str .= "<script type=\"{$this->templateTypeAttr}\" id=\"{$file['name']}\">{$file['contents']}</script>\n";
                    } else {
                        $str .= "<script type=\"{$this->templateTypeAttr}\" src=\"{$file}\"></script>\n";
                    }
                    break;
                default:
                    break;
            }
        }
        return $str;
    }

    private function getFileType($file)
    {
        // check for type
        $fileType = substr($file,-3,3);
        if ($fileType === '.js') {
            $fileType = 'js';
        }
        if ($fileType === 'mpl') {
            $fileType = 'tmpl';
        }
        if(!in_array($fileType, $this->supportedTypes)) {
            throw new Exception("Type Unknown {$file} supported file extensions are .js, .css and .tmpl");
        }

        return $fileType;
    }

    public function minify($file, $type)
    {
        $content = '';
        switch ($type) {
            case 'js':
                $js = file_get_contents($file);
                $content = JSMin::mini($js);
                break;
            case 'css':
                $css = file_get_contents($file);
                $content = CSSMin::mini($css);
                break;
            case 'tmpl':
                $tmpl = file_get_contents($file);
                $content = HTMLMin::mini($tmpl);
                break;
            default:
                break;
        }
        return $content;
    }

    private function checkForDirty($file, $fileType)
    {
        $cache_name = md5($file);
        $cacheDirectory = 'cacheDirectory' . $fileType;
        $cachedFileName = $this->$cacheDirectory . $cache_name;

        $isDirty = 'isDirty'.$fileType;
        $minify = 'minify'.$fileType;

        if(!file_exists($cachedFileName)) {
            if($this->$minify) {
                if($this->$minify) {
                    $fileContents = $this->minify($file, $fileType);
                } else {
                    $fileContents = file_get_contents($file);
                }
            } else {
                $fileContents = file_get_contents($file);
            }
            file_put_contents($cachedFileName, $fileContents);
            $this->$isDirty = true;
        } else if (filemtime($file) > filemtime($cachedFileName)) {
            if($this->$minify) {
                if($this->$minify) {
                    $fileContents = $this->minify($file, $fileType);
                } else {
                    $fileContents = file_get_contents($file);
                }
            } else {
                $fileContents = file_get_contents($file);
            }
            file_put_contents($cachedFileName, $fileContents);
            $this->$isDirty = true;
        }
    }

    private function internalClearCache()
    {
        $files = glob($this->cacheDirectoryjs . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->cacheDirectorycss . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->cacheDirectorytmpl . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectoryjs . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectorycss . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectorytmpl . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        unlink($this->mincache);
    }

    private function createMincache($overwrite = false)
    {
        if($overwrite) {
            if(file_exists($this->mincache)) {
                unlink($this->mincache);
            }
        }
        file_put_contents($this->mincache, json_encode(array(
          'mergejs' => $this->mergejs,
          'mergecss' => $this->mergecss,
          'mergedFileNamejs' => $this->mergedFileNamejs,
          'mergedFileNamecss' => $this->mergedFileNamecss,
          'minifyjs' => $this->minifyjs,
          'minifycss' => $this->minifycss,
          'minifytmpl' => $this->minifytmpl,
          'templateTypeAttr' => $this->templateTypeAttr,
          'outputTemplateAsHTML' => $this->outputTemplateAsHTML,
          'devModejs' => $this->devModejs,
          'devModecss' => $this->devModecss,
          'devModetmpl' => $this->devModetmpl,
          'baseUrl' => $this->baseUrl,
          'cacheDirectory' => $this->cacheDirectory,
          'mincache' => $this->mincache,
          'assetDirectory' => $this->assetDirectory,
          'skipStartup' => $this->skipStartup
        )));
    }

    private function startUp()
    {
        if(!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
            mkdir($this->cacheDirectoryjs);
            mkdir($this->cacheDirectorycss);
            mkdir($this->cacheDirectorytmpl);
        }

        if(!file_exists($this->assetDirectory)) {
            mkdir($this->assetDirectory);
        }

        if (!file_exists($this->assetDirectory .'js')){
            mkdir($this->assetDirectory .'js');
            mkdir($this->assetDirectory .'js/min');
        }

        if (!file_exists($this->assetDirectory .'css')){
            mkdir($this->assetDirectory .'css');
            mkdir($this->assetDirectory .'css/min');
        }

        if (!file_exists($this->assetDirectory .'tmpl')){
            mkdir($this->assetDirectory .'tmpl');
            mkdir($this->assetDirectory .'tmpl/min');
        }

        if(!file_exists($this->mincache)) {
            $this->createMincache();
        }

        $lastSettings = json_decode(file_get_contents($this->mincache));
        if( !isset($lastSettings->mergejs) ||
            !isset($lastSettings->mergecss) ||
            !isset($lastSettings->mergedFileNamejs) ||
            !isset($lastSettings->mergedFileNamecss) ||
            !isset($lastSettings->minifyjs) ||
            !isset($lastSettings->minifycss) ||
            !isset($lastSettings->minifytmpl) ||
            !isset($lastSettings->templateTypeAttr) ||
            !isset($lastSettings->outputTemplateAsHTML) ||
            !isset($lastSettings->devModejs) ||
            !isset($lastSettings->devModecss) ||
            !isset($lastSettings->devModetmpl) ||
            !isset($lastSettings->baseUrl) ||
            !isset($lastSettings->cacheDirectory) ||
            !isset($lastSettings->mincache) ||
            !isset($lastSettings->assetDirectory) ||
            !isset($lastSettings->skipStartup)
        ) {
            $this->setAllDirty();
            $this->createMincache(true);
            $lastSettings = json_decode(file_get_contents($this->mincache));
        }
        if(is_object($lastSettings)) {
            if ($lastSettings->mergejs !== $this->mergejs ||
                $lastSettings->mergecss !== $this->mergecss ||
                $lastSettings->mergedFileNamejs !== $this->mergedFileNamejs ||
                $lastSettings->mergedFileNamecss !== $this->mergedFileNamecss ||
                $lastSettings->minifyjs !== $this->minifyjs ||
                $lastSettings->minifycss !== $this->minifycss ||
                $lastSettings->minifytmpl !== $this->minifytmpl ||
                $lastSettings->templateTypeAttr !== $this->templateTypeAttr ||
                $lastSettings->outputTemplateAsHTML !== $this->outputTemplateAsHTML ||
                $lastSettings->devModejs !== $this->devModejs ||
                $lastSettings->devModecss !== $this->devModecss ||
                $lastSettings->devModetmpl !== $this->devModetmpl ||
                $lastSettings->baseUrl !== $this->baseUrl ||
                $lastSettings->cacheDirectory !== $this->cacheDirectory ||
                $lastSettings->mincache !== $this->mincache ||
                $lastSettings->assetDirectory !== $this->assetDirectory ||
                $lastSettings->skipStartup !== $this->skipStartup
            ) {
                $this->setAllDirty();
                $this->createMincache(true);
                $this->createMincache(true);
            }
        } else {
            throw new Exception("Invalid .mincache file at {$this->mincache}");
        }
    }

    private function setAllDirty()
    {
        $this->internalClearCache();
        $this->isDirtyjs = true;
        $this->isDirtycss = true;
        $this->isDirtytmpl = true;
    }

    private static function getInstance()
    {
        if(self::$Instance){
            return self::$Instance;
        }
        $Instance = new Assets;
        self::$Instance = $Instance;
        return $Instance;
    }

    function __construct() {
        $this->cacheDirectory = realpath($_SERVER['DOCUMENT_ROOT'] . '/' . $this->cacheDirectory) . DIRECTORY_SEPARATOR;
        $this->cacheDirectoryjs = realpath($this->cacheDirectory . '/js/') . DIRECTORY_SEPARATOR;
        $this->cacheDirectorycss = realpath($this->cacheDirectory .'css') . DIRECTORY_SEPARATOR;
        $this->cacheDirectorytmpl = realpath($this->cacheDirectory .'tmpl') . DIRECTORY_SEPARATOR;
        $this->assetDirectory = realpath($_SERVER['DOCUMENT_ROOT'] . '/' . $this->assetDirectory) . DIRECTORY_SEPARATOR;
        $this->assetDirectoryjs = realpath($this->assetDirectory . '/js/min') . DIRECTORY_SEPARATOR;
        $this->assetDirectorycss = realpath($this->assetDirectory . '/css/min') . DIRECTORY_SEPARATOR;
        $this->assetDirectorytmpl = realpath($this->assetDirectory . '/tmpl/min') . DIRECTORY_SEPARATOR;
        $this->mincache = $this->cacheDirectory . $this->mincache;
        $this->supportedTypes = array('js', 'css', 'tmpl');
        if(!$this->skipStartup) {
            $this->startUp();
        }
    }
}

/* End of Assets.php */