<?php

/**
 * @package modx
 * @subpackage sources
 */
require_once MODX_CORE_PATH . 'model/modx/sources/modmediasource.class.php';

/**
 * Implements FTP Remote directorys as Mediasource
 *
 * @package modx
 * @subpackage sources
 */
class ftpSourceMediaSource extends modMediaSource implements modMediaSourceInterface
{
    var $ftpConnection;

    /**
     * Override the constructor to always force S3 sources to not be streams.
     *
     * {@inheritDoc}
     *
     * @param xPDO $xpdo
     */
    public function __construct(xPDO & $xpdo)
    {
        parent::__construct($xpdo);


        $this->set('is_stream', false);
    }

    function __destruct()
    {

    }

    public function initialize()
    {
        parent::initialize();
        $properties = $this->getPropertyList();

        // Verbindung aufbauen
        $this->ftpConnection = ftp_connect($properties['hostname'], $properties['port']);
        if (!$this->ftpConnection) {
            $this->xpdo->log(modX::LOG_LEVEL_ERROR, '[ftpSourceMediaSource] connection failed');
            return false;
        }


        // Login mit Benutzername und Passwort
        $login_result = ftp_login($this->ftpConnection, $properties['username'], $properties['password']);

        if (!$login_result) {
            $this->xpdo->log(modX::LOG_LEVEL_ERROR, '[ftpSourceMediaSource] authentication failed');
            return false;
        }

        // Schalte passiven Modus ein
        ftp_pasv($this->ftpConnection, $properties['passive_mode'] == '1');

        return true;
    }

    /**
     * Get the name of this source type
     * @return string
     */
    public function getTypeName()
    {
        return 'ftpSource';
    }

    /**
     * Get the description of this source type
     * @return string
     */
    public function getTypeDescription()
    {
        return 'FTP CDN Browser';
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return array(
            'hostname'         => array(
                'name'    => 'hostname',
                'desc'    => 'FTP hostname',
                'type'    => 'textfield',
                'value'   => '',
                'lexicon' => 'core:source',
            ),
            'username'         => array(
                'name'    => 'username',
                'desc'    => 'FTP username',
                'type'    => 'textfield',
                'value'   => '',
                'lexicon' => 'core:source',
            ),
            'password'         => array(
                'name'    => 'password',
                'desc'    => 'FTP password',
                'type'    => 'textfield',
                'value'   => '',
                'lexicon' => 'core:source',
            ),
            'port'             => array(
                'name'    => 'port',
                'desc'    => 'FTP port',
                'type'    => 'textfield',
                'value'   => '21',
                'lexicon' => 'core:source',
            ),
            'remote_directory' => array(
                'name'    => 'remote_directory',
                'desc'    => 'FTP remote_directory',
                'type'    => 'textfield',
                'value'   => '/',
                'lexicon' => 'core:source',
            ),
            'remoteURL'        => array(
                'name'    => 'remoteURL',
                'desc'    => 'URL to access selected Files via Browser. Should be something like http://cdn.yourdomain.tld/ or ftp://cdn.yourdomain.tld/',
                'type'    => 'textfield',
                'value'   => '',
                'lexicon' => 'core:source',
            ),
            'passive_mode'     => array(
                'name'    => 'passive_mode',
                'desc'    => 'Passiven FTP Modus verwenden',
                'type'    => 'list',
                'options' => array(
                    array('name' => 'Passiver FTP Modus', 'value' => '1'),
                    array('name' => 'Aktiver FTP Modus', 'value' => '0'),
                ),
                'value'   => '1',
                'lexicon' => 'core:source',
            ),
            'allowedFileTypes' => array(
                'name'    => 'allowedFileTypes',
                'desc'    => 'prop_file.allowedFileTypes_desc',
                'type'    => 'textfield',
                'options' => '',
                'value'   => '',
                'lexicon' => 'core:source',
            ),
            'imageExtensions'  => array(
                'name'    => 'imageExtensions',
                'desc'    => 'prop_file.imageExtensions_desc',
                'type'    => 'textfield',
                'value'   => 'jpg,jpeg,png,gif',
                'lexicon' => 'core:source',
            ),
            'skipFiles'        => array(
                'name'    => 'skipFiles',
                'desc'    => 'prop_file.skipFiles_desc',
                'type'    => 'textfield',
                'options' => '',
                'value'   => '.svn,.git,_notes,nbproject,.idea,.DS_Store',
                'lexicon' => 'core:source',
            ),
        );
    }

    /**
     * @param string $path
     * @return array
     */
    public function getContainerList($path)
    {
        $properties = $this->getPropertyList();

        $result = array();
        $menu   = array();

        if ($this->hasPermission('directory_create')) {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_folder_create_here'),
                'handler' => 'this.createDirectory',
            );
        }

        $menu[] = array(
                'text'    => $this->xpdo->lexicon('directory_refresh'),
                'handler' => 'this.refreshActiveNode',
            );
        if ($this->hasPermission('file_upload')) {
            $menu[] = '-';
            $menu[] = array(
                'text' => $this->xpdo->lexicon('upload_files'),
                'handler' => 'this.uploadFiles',
            );
        }
        if ($this->hasPermission('file_create')) {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_create'),
                'handler' => 'this.createFile',
            );
            $menu[] = array(
                'text' => $this->xpdo->lexicon('quick_create_file'),
                'handler' => 'this.quickCreateFile',
            );
        }
        if ($this->hasPermission('directory_remove')) {
            $menu[] = '-';
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_folder_remove'),
                'handler' => 'this.removeDirectory',
            );
        }


        $menu = array('items' => $menu);
        $files = $this->listFTPDir($properties['remote_directory'] . $path);

        #var_dump($files);
        foreach ($files as $file) {
            # if(ftp_size($this->ftpConnection, $file) != -1) continue;
            if ($file['type'] == 'file') continue;

            #print_r($file);


            $result[] = array(
                'id'      => $path . $file['file'] . '/',
                'path'    => $path . $file['file'] . '/',
                'pathRelative' => $file['file'] . '/',
                'text'    => $file['file'],
                'cls'     => 'folder',
                'iconCls' => 'icon icon-folder',
                'type'    => 'dir',
                'leaf'    => false,
                'perms'   => '0755',
                'menu'    => $menu
            );

        }

        #die();
        if ($properties['hideFiles'] == 'true') return $result;

        foreach ($this->getObjectsInContainer($path) as $file) {
            $result[] = $file;
        }


        return $result;
    }

    function listFTPDirBasicMode($path) {
        // using static variable cache for double directory requests for files & directory
        ftp_chdir($this->ftpConnection, $path);
        $list  = ftp_nlist($this->ftpConnection, $path);

        $items = array();
        foreach ($list as $l) {
            if($l == '.' || $l == '..') continue;

            $size    = ftp_size($this->ftpConnection,  $l);
            $items[] = array(
                'file'      => str_replace($path, '', $l),
                'type'      => ($size < 0 ? 'dir' : 'file'),
                'file_size' => $size
            );
        }

        return $items;
    }
    function listFTPDirRawMode($path){
        ftp_chdir($this->ftpConnection, $path);
        $list  = @ftp_rawlist($this->ftpConnection, $path);
        $items = array();


        foreach ($list as $_) {
            preg_replace(
                '%^(.{10})(\s*)(\d+)(\s*)(\d*|\w*)' .
                '(\s*)(\d*|\w*)(\s*)(\d*)\s' .
                '([a-zA-Z]{3})(\s*)([0-9]{1,2})' .
                '(\s*)([0-9]{2}):?([0-9]{2}+)(\s*)(.*)$%Ue',

                '$items[]=array(
                    "rights"=>"$1",
                    "number"=>"$3",
                    "owner"=>"$5", "group"=>"$7",
                    "file_size"=>"$9",
                    "mod_time"=>"$10 $12 $14:$15",
                    "file"=>trim("$17"),
                    "type"=>print_r((preg_match("/^d/","$1"))?"dir":"file",1)
                );',

                $_);
        }
        foreach($items as $key => $file) {
            $filename = $file['file'];
            if($filename == '.' || $filename == '..' ) unset($items[$key]);
        }

        $lastCallCache[$path] = $items;
        return $items;
    }

    /**
     * @param $path
     * @return array array
     */
    function listFTPDir($path)
    {
        static $lastCallCache = array();

        if(substr($path,0,2) == '//') $path = substr($path, 1);

        if (isset($lastCallCache[$path])) return $lastCallCache[$path];

        $items = $this->listFTPDirBasicMode($path);
        $lastCallCache[$path] = $items;
        return $items;

    }

    /**
     * Get all files in the directory and prepare thumbnail views
     *
     * @param string $path
     * @return array
     */
    public function getObjectsInContainer($path)
    {
        $properties = $this->getPropertyList();
        $result     = array();

        // List Files
        $menu = array();
        if ($this->hasPermission('file_update')) {
            /* error in edit page
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_edit'),
                'handler' => 'this.editFile',
            );*/
            $menu[] = array(
                'text' => $this->xpdo->lexicon('quick_update_file'),
                'handler' => 'this.quickUpdateFile',
            );
            $menu[] = array(
                'text' => $this->xpdo->lexicon('rename'),
                'handler' => 'this.renameFile',
            );
        }

        if ($this->hasPermission('file_view')) {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_download'),
                'handler' => 'this.downloadFile',
            );
        }
        if ($this->hasPermission('file_remove')) {
            if (!empty($menu)) $menu[] = '-';
            $menu[] = array(
                'text' => $this->xpdo->lexicon('file_remove'),
                'handler' => 'this.removeFile',
            );
        }

        $menu = array('items' => $menu );

        $imageExtensions = $this->getOption('imageExtensions', $properties, 'jpg,jpeg,png,gif');
        $imageExtensions = explode(',', $imageExtensions);
        $modAuth         = $this->xpdo->user->getUserToken($this->xpdo->context->get('key'));


        $files = $this->listFTPDir($properties['remote_directory'] . $path);
        foreach ($files as $file) {
            if ($file['type'] != 'file') continue;
            $file_extension = strtolower(substr($file['file'], strrpos($file['file'], '.') + 1));
            $relativePath   = $path . $file['file'];


            if (in_array($file_extension, $imageExtensions)) {
                $preview     = 1;
                $imageWidth  = $this->ctx->getOption('filemanager_image_width', 400);
                $imageHeight = $this->ctx->getOption('filemanager_image_height', 300);
                $thumbWidth  = $this->ctx->getOption('filemanager_thumb_width', 100);
                $thumbHeight = $this->ctx->getOption('filemanager_thumb_height', 80);


                /* ensure max h/w */
                if ($thumbWidth > $imageWidth) $thumbWidth = $imageWidth;
                if ($thumbHeight > $imageHeight) $thumbHeight = $imageHeight;

                /* generate thumb/image URLs */
                $thumbQuery = http_build_query(array(
                    'src'          => $properties['remoteURL'] . $relativePath,
                    'w'            => $thumbWidth,
                    'h'            => $thumbHeight,
                    'f'            => 'png',
                    'q'            => 90,
                    'far'          => 1,
                    'HTTP_MODAUTH' => $modAuth,
                    'wctx'         => $this->ctx->get('key'),
                    'source'       => $this->get('id'),
                ));
                $imageQuery = http_build_query(array(
                    'src'          => $properties['remoteURL'] . $relativePath,
                    'w'            => $imageWidth,
                    'h'            => $imageHeight,
                    'HTTP_MODAUTH' => $modAuth,
                    'f'            => 'png',
                    'q'            => 80,
                    'wctx'         => $this->ctx->get('key'),
                    'source'       => $this->get('id'),
                ));
                $thumb      = $this->ctx->getOption('connectors_url', MODX_CONNECTORS_URL) . 'system/phpthumb.php?' . urldecode($thumbQuery);
                $image      = $this->ctx->getOption('connectors_url', MODX_CONNECTORS_URL) . 'system/phpthumb.php?' . urldecode($imageQuery);
            } else {
                $preview     = 0;
                $thumb       = $image = $this->ctx->getOption('manager_url', MODX_MANAGER_URL) . 'templates/default/images/restyle/nopreview.jpg';
                $thumbWidth  = $imageWidth = $this->ctx->getOption('filemanager_thumb_width', 100);
                $thumbHeight = $imageHeight = $this->ctx->getOption('filemanager_thumb_height', 80);
            }


            if ($properties['action'] == 'browser/directory/getList') {
                $result[] = array(
                    "cls"          => "",
                    "directory"    => "",
                    "file"         => $path . $file['file'],
                    "iconCls"      => "icon icon-file icon-" . $file_extension,
                    "id"           => $path . $file['file'],
                    "leaf"         => true,
                    "menu"         => $menu,
                    "page"         => NULL,
                    "path"         => $relativePath,
                    "pathRelative" => $relativePath,
                    "perms"        => "0644",
                    "qtip"         => $preview ? "<img src='" . $thumb . "' />" : false,
                    "text"         => $file['file'],
                    "type"         => "file",
                    "url"          => $properties['remoteURL'] . $relativePath,
                    "urlAbsolute"  => $properties['remoteURL'] . $relativePath,
                );
            } else {
                $result[] = array(
                    "id"              => $path . $file['file'],
                    'name'            => $file['file'],
                    'cls'             => "icon icon-file icon-" . $file_extension,

                    'image'           => $file['file'],
                    'image_width'     => 0,
                    'image_height'    => 0,

                    'thumb'           => $thumb,
                    'thumb_width'     => $thumbWidth,
                    'thumb_height'    => $thumbHeight,
                    'text'            => $file['file'],

                    'url'             => $file['file'],
                    'urlAbsolute'     => $file['file'],
                    'relativeUrl'     => $file['file'],
                    'fullRelativeUrl' => $file['file'],
                    'path'            => $file['file'],


                    "qtip"            => $preview ? "<img src='" . $thumb . "' />" : false,
                    'ext'             => $file_extension,

                    'pathname'        => $file['file'],
                    'lastmod'         => strtotime($file['mod_time']),
                    'disabled'        => false,
                    'perms'           => '0644',
                    'leaf'            => true,
                    'size'            => $file['file_size'],
                    'menu'            => array(),
                    'type'            => "file",
                    'preview'         => $preview,

                );
            }


        }
        /*
        $result[] = array(
            "id" => 'TIME',
            "text" => microtime(true) - $start
        );
        */
        return $result;
    }

    /**
     * Prepare a src parameter to be rendered with phpThumb
     *
     * @param string $src
     * @return string
     */
    public function prepareSrcForThumb($src)
    {
        return $src;
    }

    /**
     * Get the base URL for this source. Only applicable to sources that are streams.
     *
     * @param string $object An optional object to find the base url of
     * @return string
     */
    public function getBaseUrl($object = '')
    {
        $properties = $this->getPropertyList();
        return $properties['Hostname'];
    }

    /**
     * Get the absolute URL for a specified object. Only applicable to sources that are streams.
     *
     * @param string $object
     * @return string
     */
    public function getObjectUrl($object = '')
    {
        $properties = $this->getPropertyList();
        return $properties['url'] . $object;
    }

    /**
     * Get the contents of a specified file
     *
     * @param string $objectPath
     * @return array
     */
    public function getObjectContents($objectPath)
    {
        $properties = $this->getPropertyList();

        $objectUrl = $properties['remoteURL'] . $objectPath;
        $contents  = @file_get_contents($objectUrl);

        $imageExtensions = $this->getOption('imageExtensions', $this->properties, 'jpg,jpeg,png,gif');
        $imageExtensions = explode(',', $imageExtensions);
        $fileExtension   = pathinfo($objectPath, PATHINFO_EXTENSION);

        return array(
            'name'          => $objectPath,
            'basename'      => basename($objectPath),
            'path'          => $objectPath,
            'size'          => '',
            'last_accessed' => '',
            'last_modified' => '',
            'content'       => $contents,
            'image'         => in_array(strtolower($fileExtension), $imageExtensions) ? true : false,
            'is_writable'   => false,
            'is_readable'   => false,
        );
    }

    /**
     * NYI
     *
     * @param string $name
     * @param string $parentContainer
     * @return bool
     */
    public function createContainer($name, $parentContainer)
    {
        ftp_chdir($this->ftpConnection, $parentContainer);
        return ftp_mkdir($this->ftpConnection, $name);

    }

    /**
     * NYI
     *
     * @param string $path
     * @return bool
     */
    public function removeContainer($path)
    {
        $name = basename($path);
        $parentContainer = dirname($path);

        ftp_chdir($this->ftpConnection, $parentContainer);
        return ftp_rmdir($this->ftpConnection, $name);
    }

    /**
     * NYI
     *
     * @param string $objectPath
     * @return bool
     */
    public function removeObject($objectPath)
    {
        $name = basename($objectPath);
        $parentContainer = dirname($objectPath);

        ftp_chdir($this->ftpConnection, $parentContainer);
        return ftp_delete($this->ftpConnection, $name);
    }

    public function updateObject($objectPath, $content)
    {
        ftp_chdir($this->ftpConnection, dirname($objectPath));

        $tempHandle = fopen('php://temp', 'r+');
        fwrite($tempHandle, $content);
        rewind($tempHandle);

        return ftp_fput($this->ftpConnection, basename($objectPath), $tempHandle, FTP_ASCII);
    }

    /**
     * NYI
     *
     * @param string $oldPath
     * @param string $newName
     * @return bool
     */
    public function renameObject($oldPath, $newName)
    {
        ftp_chdir($this->ftpConnection, dirname($oldPath));
        return ftp_rename($this->ftpConnection, basename($oldPath), $newName);
    }

    /**
     * NYI
     *
     * @param string $container
     * @param array $objects
     * @return bool
     */
    public function uploadObjectsToContainer($objectPath, array $objects = array())
    {
        ftp_chdir($this->ftpConnection, $objectPath);
        foreach($objects as $file) {
            ftp_fput($this->ftpConnection, $file['name'], fopen($file['tmp_name'],'r'), FTP_BINARY);
        }
        return true;
    }

    public function createObject($objectPath, $name, $content)
    {
        ftp_chdir($this->ftpConnection, $objectPath);

        $tempHandle = fopen('php://temp', 'r+');
        fwrite($tempHandle, $content);
        rewind($tempHandle);

        return ftp_fput($this->ftpConnection, $name, $tempHandle, FTP_ASCII);

    }

    /**
     * NYI
     *
     * @param string $from
     * @param string $to
     * @param string $point
     * @return bool
     */
    public function moveObject($from, $to, $point = 'append')
    {
        if($point != 'append') $to = dirname($to).'/';
        $to = $to . basename($from);

        return ftp_rename($this->ftpConnection, $from, $to);
    }
}