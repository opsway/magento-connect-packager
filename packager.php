<?php
// Copyright (c) 2012 - 2014 Pulse Storm LLC.
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//in progress, use at your own risk
if (!defined('DS')) define('DS','/');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');

class Mage_Archive_Helper_File
{
    /**
     * Full path to directory where file located
     *
     * @var string
     */
    protected $_fileLocation;
    /**
     * File name
     *
     * @var string
     */
    protected $_fileName;
    /**
     * Full path (directory + filename) to file
     *
     * @var string
     */
    protected $_filePath;
    /**
     * File permissions that will be set if file opened in write mode
     *
     * @var int
     */
    protected $_chmod;
    /**
     * File handler
     *
     * @var pointer
     */
    protected $_fileHandler;
    /**
     * Set file path via constructor
     *
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $pathInfo = pathinfo($filePath);
        $this->_filePath = $filePath;
        $this->_fileLocation = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
        $this->_fileName = isset($pathInfo['basename']) ? $pathInfo['basename'] : '';
    }
    /**
     * Close file if it's not closed before object destruction
     */
    public function __destruct()
    {
        if ($this->_fileHandler) {
            $this->_close();
        }
    }
    /**
     * Open file
     *
     * @param string $mode
     * @param int $chmod
     * @throws Mage_Exception
     */
    public function open($mode = 'w+', $chmod = 0666)
    {
        if ($this->_isWritableMode($mode)) {
            if (!is_writable($this->_fileLocation)) {
                throw new Mage_Exception('Permission denied to write to ' . $this->_fileLocation);
            }
            if (is_file($this->_filePath) && !is_writable($this->_filePath)) {
                throw new Mage_Exception("Can't open file " . $this->_fileName . " for writing. Permission denied.");
            }
        }
        if ($this->_isReadableMode($mode) && (!is_file($this->_filePath) || !is_readable($this->_filePath))) {
            if (!is_file($this->_filePath)) {
                throw new Mage_Exception('File ' . $this->_filePath . ' does not exist');
            }
            if (!is_readable($this->_filePath)) {
                throw new Mage_Exception('Permission denied to read file ' . $this->_filePath);
            }
        }
        $this->_open($mode);
        $this->_chmod = $chmod;
    }
    /**
     * Write data to file
     *
     * @param string $data
     */
    public function write($data)
    {
        $this->_checkFileOpened();
        $this->_write($data);
    }
    /**
     * Read data from file
     *
     * @param int $length
     * @return string|boolean
     */
    public function read($length = 4096)
    {
        $data = false;
        $this->_checkFileOpened();
        if ($length > 0) {
            $data = $this->_read($length);
        }
        return $data;
    }
    /**
     * Check whether end of file reached
     *
     * @return boolean
     */
    public function eof()
    {
        $this->_checkFileOpened();
        return $this->_eof();
    }
    /**
     * Close file
     */
    public function close()
    {
        $this->_checkFileOpened();
        $this->_close();
        $this->_fileHandler = false;
        @chmod($this->_filePath, $this->_chmod);
    }
    /**
     * Implementation of file opening
     *
     * @param string $mode
     * @throws Mage_Exception
     */
    protected function _open($mode)
    {
        $this->_fileHandler = @fopen($this->_filePath, $mode);
        if (false === $this->_fileHandler) {
            throw new Mage_Exception('Failed to open file ' . $this->_filePath);
        }
    }
    /**
     * Implementation of writing data to file
     *
     * @param string $data
     * @throws Mage_Exception
     */
    protected function _write($data)
    {
        $result = @fwrite($this->_fileHandler, $data);
        if (false === $result) {
            throw new Mage_Exception('Failed to write data to ' . $this->_filePath);
        }
    }
    /**
     * Implementation of file reading
     *
     * @param int $length
     * @throws Mage_Exception
     */
    protected function _read($length)
    {
        $result = fread($this->_fileHandler, $length);
        if (false === $result) {
            throw new Mage_Exception('Failed to read data from ' . $this->_filePath);
        }
        return $result;
    }
    /**
     * Implementation of EOF indicator
     *
     * @return boolean
     */
    protected function _eof()
    {
        return feof($this->_fileHandler);
    }
    /**
     * Implementation of file closing
     */
    protected function _close()
    {
        fclose($this->_fileHandler);
    }
    /**
     * Check whether requested mode is writable mode
     *
     * @param string $mode
     */
    protected function _isWritableMode($mode)
    {
        return preg_match('/(^[waxc])|(\+$)/', $mode);
    }
    /**
     * Check whether requested mode is readable mode
     *
     * @param string $mode
     */
    protected function _isReadableMode($mode) {
        return !$this->_isWritableMode($mode);
    }
    /**
     * Check whether file is opened
     *
     * @throws Mage_Exception
     */
    protected function _checkFileOpened()
    {
        if (!$this->_fileHandler) {
            throw new Mage_Exception('File not opened');
        }
    }
}

interface Mage_Archive_Interface
{
    /**
     * Pack file or directory.
     *
     * @param string $source
     * @param string $destination
     * @return string
     */
    public function pack($source, $destination);
    /**
     * Unpack file or directory.
     *
     * @param string $source
     * @param string $destination
     * @return string
     */
    public function unpack($source, $destination);
}

class Mage_Archive_Abstract
{
    /**
     * Write data to file. If file can't be opened - throw exception
     *
     * @param string $destination
     * @param string $data
     * @return boolean
     * @throws Mage_Exception
     */
    protected function _writeFile($destination, $data)
    {
        $destination = trim($destination);
        if(false === file_put_contents($destination, $data)) {
            throw new Mage_Exception("Can't write to file: " . $destination);
        }
        return true;
    }
    /**
     * Read data from file. If file can't be opened, throw to exception.
     *
     * @param string $source
     * @return string
     * @throws Mage_Exception
     */
    protected function _readFile($source)
    {
        $data = '';
        if (is_file($source) && is_readable($source)) {
            $data = @file_get_contents($source);
            if ($data === false) {
                throw new Mage_Exception("Can't get contents from: " . $source);
            }
        }
        return $data;
    }
    /**
     * Get file name from source (URI) without last extension.
     *
     * @param string $source
     * @param bool $withExtension
     * @return mixed|string
     */
    public function getFilename($source, $withExtension=false)
    {
        $file = str_replace(dirname($source) . DS, '', $source);
        if (!$withExtension) {
            $file = substr($file, 0, strrpos($file, '.'));
        }
        return $file;
    }
}

class Mage_Archive_Tar extends Mage_Archive_Abstract implements Mage_Archive_Interface
{
    /**
     * Tar block size
     *
     * @const int
     */
    const TAR_BLOCK_SIZE = 512;
    /**
     * Keep file or directory for packing.
     *
     * @var string
     */
    protected $_currentFile;
    /**
     * Keep path to file or directory for packing.
     *
     * @var mixed
     */
    protected $_currentPath;
    /**
     * Skip first level parent directory. Example:
     * use test/fip.php instead test/test/fip.php;
     *
     * @var mixed
     */
    protected $_skipRoot;
    /**
     * Tarball data writer
     *
     * @var Mage_Archive_Helper_File
     */
    protected $_writer;
    /**
     * Tarball data reader
     *
     * @var Mage_Archive_Helper_File
     */
    protected $_reader;
    /**
     * Path to file where tarball should be placed
     *
     * @var string
     */
    protected $_destinationFilePath;
    /**
     * Initialize tarball writer
     *
     * @return Mage_Archive_Tar
     */
    protected function _initWriter()
    {
        $this->_writer = new Mage_Archive_Helper_File($this->_destinationFilePath);
        $this->_writer->open('w');
        return $this;
    }
    /**
     * Returns string that is used for tar's header parsing
     *
     * @return string
     */
    protected static final function _getFormatParseHeader()
    {
        return 'a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2version/'
        . 'a32uname/a32gname/a8devmajor/a8devminor/a155prefix/a12closer';
    }
    /**
     * Destroy tarball writer
     *
     * @return Mage_Archive_Tar
     */
    protected function _destroyWriter()
    {
        if ($this->_writer instanceof Mage_Archive_Helper_File) {
            $this->_writer->close();
            $this->_writer = null;
        }
        return $this;
    }
    /**
     * Get tarball writer
     *
     * @return Mage_Archive_Helper_File
     */
    protected function _getWriter()
    {
        if (!$this->_writer) {
            $this->_initWriter();
        }
        return $this->_writer;
    }
    /**
     * Initialize tarball reader
     *
     * @return Mage_Archive_Tar
     */
    protected function _initReader()
    {
        $this->_reader = new Mage_Archive_Helper_File($this->_getCurrentFile());
        $this->_reader->open('r');
        return $this;
    }
    /**
     * Destroy tarball reader
     *
     * @return Mage_Archive_Tar
     */
    protected function _destroyReader()
    {
        if ($this->_reader instanceof Mage_Archive_Helper_File) {
            $this->_reader->close();
            $this->_reader = null;
        }
        return $this;
    }
    /**
     * Get tarball reader
     *
     * @return Mage_Archive_Helper_File
     */
    protected function _getReader()
    {
        if (!$this->_reader) {
            $this->_initReader();
        }
        return $this->_reader;
    }
    /**
     * Set option that define ability skip first catalog level.
     *
     * @param mixed $skipRoot
     * @return Mage_Archive_Tar
     */
    protected function _setSkipRoot($skipRoot)
    {
        $this->_skipRoot = $skipRoot;
        return $this;
    }
    /**
     * Set file which is packing.
     *
     * @param string $file
     * @return Mage_Archive_Tar
     */
    protected function _setCurrentFile($file)
    {
        $this->_currentFile = $file .((!is_link($file) && is_dir($file) && substr($file, -1) != DS) ? DS : '');
        return $this;
    }
    /**
     * Set path to file where tarball should be placed
     *
     * @param string $destinationFilePath
     * @return Mage_Archive_Tar
     */
    protected function _setDestinationFilePath($destinationFilePath)
    {
        $this->_destinationFilePath = $destinationFilePath;
        return $this;
    }
    /**
     * Retrieve file which is packing.
     *
     * @return string
     */
    protected function _getCurrentFile()
    {
        return $this->_currentFile;
    }
    /**
     * Set path to file which is packing.
     *
     * @param string $path
     * @return Mage_Archive_Tar
     */
    protected function _setCurrentPath($path)
    {
        if ($this->_skipRoot && is_dir($path)) {
            $this->_currentPath = $path.(substr($path, -1)!=DS?DS:'');
        } else {
            $this->_currentPath = dirname($path) . DS;
        }
        return $this;
    }
    /**
     * Retrieve path to file which is packing.
     *
     * @return string
     */
    protected function _getCurrentPath()
    {
        return $this->_currentPath;
    }
    /**
     * Walk through directory and add to tar file or directory.
     * Result is packed string on TAR format.
     *
     * @deprecated after 1.7.0.0
     * @param boolean $skipRoot
     * @return string
     */
    protected function _packToTar($skipRoot=false)
    {
        $file = $this->_getCurrentFile();
        $header = '';
        $data = '';
        if (!$skipRoot) {
            $header = $this->_composeHeader();
            $data = $this->_readFile($file);
            $data = str_pad($data, floor(((is_dir($file) ? 0 : filesize($file)) + 512 - 1) / 512) * 512, "\0");
        }
        $sub = '';
        if (is_dir($file)) {
            $treeDir = scandir($file);
            if (empty($treeDir)) {
                throw new Mage_Exception('Can\'t scan dir: ' . $file);
            }
            array_shift($treeDir); /* remove './'*/
            array_shift($treeDir); /* remove '../'*/
            foreach ($treeDir as $item) {
                $sub .= $this->_setCurrentFile($file.$item)->_packToTar(false);
            }
        }
        $tarData = $header . $data . $sub;
        $tarData = str_pad($tarData, floor((strlen($tarData) - 1) / 1536) * 1536, "\0");
        return $tarData;
    }
    /**
     * Recursively walk through file tree and create tarball
     *
     * @param boolean $skipRoot
     * @param boolean $finalize
     * @throws Mage_Exception
     */
    protected function _createTar($skipRoot = false, $finalize = false)
    {
        if (!$skipRoot) {
            $this->_packAndWriteCurrentFile();
        }
        $file = $this->_getCurrentFile();
        if (is_dir($file)) {
            $dirFiles = scandir($file);
            if (false === $dirFiles) {
                throw new Mage_Exception('Can\'t scan dir: ' . $file);
            }
            array_shift($dirFiles); /* remove './'*/
            array_shift($dirFiles); /* remove '../'*/
            foreach ($dirFiles as $item) {
                $this->_setCurrentFile($file . $item)->_createTar();
            }
        }
        if ($finalize) {
            $this->_getWriter()->write(str_repeat("\0", self::TAR_BLOCK_SIZE * 12));
        }
    }
    /**
     * Write current file to tarball
     */
    protected function _packAndWriteCurrentFile()
    {
        $archiveWriter = $this->_getWriter();
        $archiveWriter->write($this->_composeHeader());
        $currentFile = $this->_getCurrentFile();
        $fileSize = 0;
        if (is_file($currentFile) && !is_link($currentFile)) {
            $fileReader = new Mage_Archive_Helper_File($currentFile);
            $fileReader->open('r');
            while (!$fileReader->eof()) {
                $archiveWriter->write($fileReader->read());
            }
            $fileReader->close();
            $fileSize = filesize($currentFile);
        }
        $appendZerosCount = (self::TAR_BLOCK_SIZE - $fileSize % self::TAR_BLOCK_SIZE) % self::TAR_BLOCK_SIZE;
        $archiveWriter->write(str_repeat("\0", $appendZerosCount));
    }
    /**
     * Compose header for current file in TAR format.
     * If length of file's name greater 100 characters,
     * method breaks header into two pieces. First contains
     * header and data with long name. Second contain only header.
     *
     * @param boolean $long
     * @return string
     */
    protected function _composeHeader($long = false)
    {
        $file = $this->_getCurrentFile();
        $path = $this->_getCurrentPath();
        $infoFile = stat($file);
        $nameFile = str_replace($path, '', $file);
        $nameFile = str_replace('\\', '/', $nameFile);
        $packedHeader = '';
        $longHeader = '';
        if (!$long && strlen($nameFile)>100) {
            $longHeader = $this->_composeHeader(true);
            $longHeader .= str_pad($nameFile, floor((strlen($nameFile) + 512 - 1) / 512) * 512, "\0");
        }
        $header = array();
        $header['100-name'] = $long?'././@LongLink':substr($nameFile, 0, 100);
        $header['8-mode'] = $long ? ' '
            : str_pad(substr(sprintf("%07o", $infoFile['mode']),-4), 6, '0', STR_PAD_LEFT);
        $header['8-uid'] = $long || $infoFile['uid']==0?"\0\0\0\0\0\0\0":sprintf("%07o", $infoFile['uid']);
        $header['8-gid'] = $long || $infoFile['gid']==0?"\0\0\0\0\0\0\0":sprintf("%07o", $infoFile['gid']);
        $header['12-size'] = $long ? sprintf("%011o", strlen($nameFile)) : sprintf("%011o", is_dir($file)
            ? 0 : filesize($file));
        $header['12-mtime'] = $long?'00000000000':sprintf("%011o", $infoFile['mtime']);
        $header['8-check'] = sprintf('% 8s', '');
        $header['1-type'] = $long ? 'L' : (is_link($file) ? 2 : (is_dir($file) ? 5 : 0));
        $header['100-symlink'] = is_link($file) ? readlink($file) : '';
        $header['6-magic'] = 'ustar ';
        $header['2-version'] = ' ';
        $a=function_exists('posix_getpwuid')?posix_getpwuid (fileowner($file)):array('name'=>'');
        $header['32-uname'] = $a['name'];
        $a=function_exists('posix_getgrgid')?posix_getgrgid (filegroup($file)):array('name'=>'');
        $header['32-gname'] = $a['name'];
        $header['8-devmajor'] = '';
        $header['8-devminor'] = '';
        $header['155-prefix'] = '';
        $header['12-closer'] = '';
        $packedHeader = '';
        foreach ($header as $key=>$element) {
            $length = explode('-', $key);
            $packedHeader .= pack('a' . $length[0], $element);
        }
        $checksum = 0;
        for ($i = 0; $i < 512; $i++) {
            $checksum += ord(substr($packedHeader, $i, 1));
        }
        $packedHeader = substr_replace($packedHeader, sprintf("%07o", $checksum)."\0", 148, 8);
        return $longHeader . $packedHeader;
    }
    /**
     * Read TAR string from file, and unpacked it.
     * Create files and directories information about discribed
     * in the string.
     *
     * @param string $destination path to file is unpacked
     * @return array list of files
     * @throws Mage_Exception
     */
    protected function _unpackCurrentTar($destination)
    {
        $archiveReader = $this->_getReader();
        $list = array();
        while (!$archiveReader->eof()) {
            $header = $this->_extractFileHeader();
            if (!$header) {
                continue;
            }
            $currentFile = $destination . $header['name'];
            $dirname = dirname($currentFile);
            if (in_array($header['type'], array("0",chr(0), ''))) {
                if(!file_exists($dirname)) {
                    $mkdirResult = @mkdir($dirname, 0777, true);
                    if (false === $mkdirResult) {
                        throw new Mage_Exception('Failed to create directory ' . $dirname);
                    }
                }
                $this->_extractAndWriteFile($header, $currentFile);
                $list[] = $currentFile;
            } elseif ($header['type'] == '5') {
                if(!file_exists($dirname)) {
                    $mkdirResult = @mkdir($currentFile, $header['mode'], true);
                    if (false === $mkdirResult) {
                        throw new Mage_Exception('Failed to create directory ' . $currentFile);
                    }
                }
                $list[] = $currentFile . DS;
            } elseif ($header['type'] == '2') {
                $symlinkResult = @symlink($header['symlink'], $currentFile);
                if (false === $symlinkResult) {
                    throw new Mage_Exception('Failed to create symlink ' . $currentFile . ' to ' . $header['symlink']);
                }
            }
        }
        return $list;
    }
    /**
     * Get header from TAR string and unpacked it by format.
     *
     * @deprecated after 1.7.0.0
     * @param resource $pointer
     * @return string
     */
    protected function _parseHeader(&$pointer)
    {
        $firstLine = fread($pointer, 512);
        if (strlen($firstLine)<512){
            return false;
        }
        $fmt = self::_getFormatParseHeader();
        $header = unpack ($fmt, $firstLine);
        $header['mode']=$header['mode']+0;
        $header['uid']=octdec($header['uid']);
        $header['gid']=octdec($header['gid']);
        $header['size']=octdec($header['size']);
        $header['mtime']=octdec($header['mtime']);
        $header['checksum']=octdec($header['checksum']);
        if ($header['type'] == "5") {
            $header['size'] = 0;
        }
        $checksum = 0;
        $firstLine = substr_replace($firstLine, ' ', 148, 8);
        for ($i = 0; $i < 512; $i++) {
            $checksum += ord(substr($firstLine, $i, 1));
        }
        $isUstar = 'ustar' == strtolower(substr($header['magic'], 0, 5));
        $checksumOk = $header['checksum'] == $checksum;
        if (isset($header['name']) && $checksumOk) {
            if ($header['name'] == '././@LongLink' && $header['type'] == 'L') {
                $realName = substr(fread($pointer, floor(($header['size'] + 512 - 1) / 512) * 512), 0, $header['size']);
                $headerMain = $this->_parseHeader($pointer);
                $headerMain['name'] = $realName;
                return $headerMain;
            } else {
                if ($header['size']>0) {
                    $header['data'] = substr(fread($pointer, floor(($header['size'] + 512 - 1) / 512) * 512), 0, $header['size']);
                } else {
                    $header['data'] = '';
                }
                return $header;
            }
        }
        return false;
    }
    /**
     * Read and decode file header information from tarball
     *
     * @return array|boolean
     */
    protected function _extractFileHeader()
    {
        $archiveReader = $this->_getReader();
        $headerBlock = $archiveReader->read(self::TAR_BLOCK_SIZE);
        if (strlen($headerBlock) < self::TAR_BLOCK_SIZE) {
            return false;
        }
        $header = unpack(self::_getFormatParseHeader(), $headerBlock);
        $header['mode'] = octdec($header['mode']);
        $header['uid'] = octdec($header['uid']);
        $header['gid'] = octdec($header['gid']);
        $header['size'] = octdec($header['size']);
        $header['mtime'] = octdec($header['mtime']);
        $header['checksum'] = octdec($header['checksum']);
        if ($header['type'] == "5") {
            $header['size'] = 0;
        }
        $checksum = 0;
        $headerBlock = substr_replace($headerBlock, ' ', 148, 8);
        for ($i = 0; $i < 512; $i++) {
            $checksum += ord(substr($headerBlock, $i, 1));
        }
        $checksumOk = $header['checksum'] == $checksum;
        if (isset($header['name']) && $checksumOk) {
            if (!($header['name'] == '././@LongLink' && $header['type'] == 'L')) {
                $header['name'] = trim($header['name']);
                return $header;
            }
            $realNameBlockSize = floor(($header['size'] + self::TAR_BLOCK_SIZE - 1) / self::TAR_BLOCK_SIZE)
                * self::TAR_BLOCK_SIZE;
            $realNameBlock = $archiveReader->read($realNameBlockSize);
            $realName = substr($realNameBlock, 0, $header['size']);
            $headerMain = $this->_extractFileHeader();
            $headerMain['name'] = trim($realName);
            return $headerMain;
        }
        return false;
    }
    /**
     * Extract next file from tarball by its $header information and save it to $destination
     *
     * @param array $fileHeader
     * @param string $destination
     */
    protected function _extractAndWriteFile($fileHeader, $destination)
    {
        $fileWriter = new Mage_Archive_Helper_File($destination);
        $fileWriter->open('w', $fileHeader['mode']);
        $archiveReader = $this->_getReader();
        $filesize = $fileHeader['size'];
        $bytesExtracted = 0;
        while ($filesize > $bytesExtracted && !$archiveReader->eof()) {
            $block = $archiveReader->read(self::TAR_BLOCK_SIZE);
            $nonExtractedBytesCount = $filesize - $bytesExtracted;
            $data = substr($block, 0, $nonExtractedBytesCount);
            $fileWriter->write($data);
            $bytesExtracted += strlen($block);
        }
    }
    /**
     * Pack file to TAR (Tape Archiver).
     *
     * @param string $source
     * @param string $destination
     * @param boolean $skipRoot
     * @return string
     */
    public function pack($source, $destination, $skipRoot = false)
    {
        $this->_setSkipRoot($skipRoot);
        $source = realpath($source);
        $tarData = $this->_setCurrentPath($source)
            ->_setDestinationFilePath($destination)
            ->_setCurrentFile($source);
        $this->_initWriter();
        $this->_createTar($skipRoot, true);
        $this->_destroyWriter();
        return $destination;
    }
    /**
     * Unpack file from TAR (Tape Archiver).
     *
     * @param string $source
     * @param string $destination
     * @return string
     */
    public function unpack($source, $destination)
    {
        $this->_setCurrentFile($source)
            ->_setCurrentPath($source);
        $this->_initReader();
        $this->_unpackCurrentTar($destination);
        $this->_destroyReader();
        return $destination;
    }
    /**
     * Extract one file from TAR (Tape Archiver).
     *
     * @param string $file
     * @param string $source
     * @param string $destination
     * @return string
     */
    public function extract($file, $source, $destination)
    {
        $this->_setCurrentFile($source);
        $this->_initReader();
        $archiveReader = $this->_getReader();
        $extractedFile = '';
        while (!$archiveReader->eof()) {
            $header = $this->_extractFileHeader();
            if ($header['name'] == $file) {
                $extractedFile = $destination . basename($header['name']);
                $this->_extractAndWriteFile($header, $extractedFile);
                break;
            }
            if ($header['type'] != 5){
                $skipBytes = floor(($header['size'] + self::TAR_BLOCK_SIZE - 1) / self::TAR_BLOCK_SIZE)
                    * self::TAR_BLOCK_SIZE;
                $archiveReader->read($skipBytes);
            }
        }
        $this->_destroyReader();
        return $extractedFile;
    }
}

class Mage_Exception extends Exception
{}

/**
 * Still a lot of Magento users stuck on systems with 5.2, no no namespaces
 * @todo but we're using anonymous functions below, so this won't work with
 *       5.2 -- do we want this as a class, or a single file namespaced module?
 */
class Pulsestorm_MagentoTarToConnect
{
    static public $verbose=true;
    //from http://php.net/glob
    // Does not support flag GLOB_BRACE    
    static public function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, self::globRecursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }

    static public function input($string)
    {
        self::output($string);
        self::output('] ','');
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        fclose($handle);
        return $line;
    }

    static public function output($string, $newline="\n")
    {
        if(!self::$verbose)
        {
            return;
        }
        echo $string,$newline;
    }

    static public function error($string)
    {
        self::output("ERROR: " . $string);
        self::output("Execution halted at " . __FILE__ . '::' . __LINE__);
        exit;
    }


    static public function createPackageXmlAddNode($xml, $full_dir, $base_dir=false)
    {
        $parts = explode("/",str_replace($base_dir.'/','',$full_dir));
        $single_file  = array_pop($parts);
        $node = $xml;
        foreach($parts as $part)
        {
            $nodes = $node->xpath("dir[@name='".$part."']");
            if(count($nodes) > 0)
            {
                $node = array_pop($nodes);
            }
            else
            {
                $node = $node->addChild('dir');
                $node->addAttribute('name', $part);
            }
        }

        $node = $node->addChild('file');
        $node->addAttribute('name',$single_file);
        $node->addAttribute('hash',md5_file($full_dir));
    }

    static public function createPackageXml($files, $base_dir, $config)
    {
        $xml = simplexml_load_string('<package/>');
        $xml->name          = $config['extension_name'];
        $xml->version       = $config['extension_version'];
        $xml->stability     = $config['stability'];
        $xml->license       = $config['license'];
        $xml->channel       = $config['channel'];
        $xml->extends       = '';
        $xml->summary       = $config['summary'];
        $xml->description   = $config['description'];
        $xml->notes         = $config['notes'];

        $authors            = $xml->addChild('authors');
        foreach (self::getAuthorData($config) as $oneAuthor) {
            $author         = $authors->addChild('author');
            $author->name   = $oneAuthor['author_name'];
            $author->user   = $oneAuthor['author_user'];
            $author->email  = $oneAuthor['author_email'];
        }

        $xml->date          = date('Y-m-d');
        $xml->time          = date('G:i:s');
        $xml->compatible    = '';
        $dependencies       = $xml->addChild('dependencies');
        $required           = $dependencies->addChild('required');
        $php                = $required->addChild('php');
        $php->min           = $config['php_min'];   //'5.2.0';
        $php->max           = $config['php_max'];   //'6.0.0';

        // add php extension dependencies
        if (is_array($config['extensions'])) {
            foreach ($config['extensions'] as $extinfo) {
                $extension = $required->addChild('extension');
                if (is_array($extinfo)) {
                    $extension->name = $extinfo['name'];
                    $extension->min = isset($extinfo['min']) ? $extinfo['min'] : "";
                    $extension->max = isset($extinfo['max']) ? $extinfo['max'] : "";
                } else {
                    $extension->name = $extinfo;
                    $extension->min = "";
                    $extension->max = "";
                }
            }
        }

        $node = $xml->addChild('contents');
        $node = $node->addChild('target');
        $node->addAttribute('name', 'mage');

        //     $files = $this->recursiveGlob($temp_dir);
        //     $files = array_unique($files);              
        $temp_dir = false;
        foreach($files as $file)
        {
            //$this->addFileNode($node,$temp_dir,$file);
            self::createPackageXmlAddNode($node, $file, $base_dir);
        }
        //file_put_contents($temp_dir . '/package.xml', $xml->asXml());            

        return $xml->asXml();
    }

    static public function getTempDir()
    {
        $name = tempnam(sys_get_temp_dir(),'tmp');
        unlink($name);
        $name = $name;
        mkdir($name,0777,true);
        return $name;
    }

    static public function validateConfig($config)
    {
        $keys = array('extension_files','path_output',
        );
        foreach($keys as $key)
        {
            if(!array_key_exists($key, $config))
            {
                self::error("Config file missing key [$key]");
            }
        }

        if($config['author_email'] == 'foo@example.com')
        {
            $email = self::input("Email Address is configured with foo@example.com.  Enter a new address");
            if(trim($email) != '')
            {
                $config['author_email'] = trim($email);
            }
        }

        if(!array_key_exists('extensions', $config))
        {
            $config['extensions'] = null;
        }
        return $config;


    }

    static public function loadConfig($config_name=false)
    {
        if(!$config_name)
        {
            $config_name = basename(__FILE__,'php') . 'config.php';
        }
        if(!file_exists($config_name))
        {
            self::error("Could not find $config_name.  Create this file, or pass in an alternate");
        }
        $config = include $config_name;

        $config = self::validateConfig($config);
        return $config;
    }

    static public function getModuleVersion($files)
    {
        $configs = array();
        foreach($files as $file)
        {
            if(basename($file) == 'config.xml')
            {
                $configs[] = $file;
            }
        }

        foreach($configs as $file)
        {
            $xml = simplexml_load_file($file);
            $version_strings = $xml->xpath('//version');
            foreach($version_strings as $version)
            {
                $version = (string) $version;
                if(!empty($version))
                {
                    return (string)$version;
                }
            }
        }

        foreach($configs as $file)
        {
            $xml = simplexml_load_file($file);
            $modules = $xml->xpath('//modules');
            foreach($modules[0] as $module)
            {
                $version = (string)$module->version;
                if(!empty($version))
                {
                    return $version;
                }
            }
        }
    }

    static public function checkModuleVersionVsPackageVersion($files, $extension_version)
    {
        $configs = array();
        foreach($files as $file)
        {
            if(basename($file) == 'config.xml')
            {
                $configs[] = $file;
            }
        }

        foreach($configs as $file)
        {
            $xml = simplexml_load_file($file);
            $version_strings = $xml->xpath('//version');
            foreach($version_strings as $version)
            {
                if($version != $extension_version)
                {
                    self::error(
                        "Extension Version [$extension_version] does not match " .
                        "module version [$version] found in a config.xml file.  Add " .
                        "'skip_version_compare'   => true  to configuration to skip this check."
                    );
                }
            }
        }
    }

    static public function buildExtensionFromConfig($config)
    {
        ob_start();

        # extract and validate config values
        $base_dir           = __DIR__;          //current dir
        $archive_files      = $config['extension_files'];     // extension directory code;
        $path_output        = realpath($base_dir . DIRECTORY_SEPARATOR . $config['path_output']);       //'/Users/alanstorm/Desktop/working';
        $archive_connect    = $config['extension_name'] . '-' . $config['extension_version'] . '.tgz';
        ###--------------------------------------------------

        # make sure the archive we're creating exists
        if(!is_dir($base_dir . '/' . $archive_files))
        {
            self::error('Can\'t find specified folder, bailing' . "\n[" . $base_dir . '/' . $archive_files.']');
            exit;
        }
        ###--------------------------------------------------

        # create a temporary directory, move to temporary
        $temp_dir   = self::getTempDir();
        chdir($temp_dir);
        ###--------------------------------------------------

        # copy and extract archive               
        shell_exec('cp -Rf '        . $base_dir . '/' . $archive_files . '/* ' . $temp_dir.'/');
        ###--------------------------------------------------

        # get a lsit of all the files without directories
        $all        = self::globRecursive($temp_dir  . '/*');
        $dirs       = self::globRecursive($temp_dir .'/*',GLOB_ONLYDIR);
        $files      = array_diff($all, $dirs);
        ###--------------------------------------------------

        # now that we've extracted the files, yoink the version number from the config
        # this only works is auto_detect_version is true. Also, may not return what
        # you expect if your connect extension includes multiple Magento modules
        if(isset($config['auto_detect_version']) && $config['auto_detect_version'] == true)
        {
            $config['extension_version'] = self::getModuleVersion($files);
            $archive_connect = $config['extension_name'] . '-' . $config['extension_version'] . '.tgz';
        }
        ###--------------------------------------------------

        # checks that your Magento Connect extension version matches the version of your 
        # modules file.  Probably redundant if auto_detect_version is true
        if(!$config['skip_version_compare'])
        {
            self::checkModuleVersionVsPackageVersion($files, $config['extension_version']);
        }
        ###--------------------------------------------------

        # creates the base extension package.xml file            
        $xml        = self::createPackageXml($files,$temp_dir,$config);
        file_put_contents($temp_dir . '/package.xml',$xml);
        self::output($temp_dir);
        ###--------------------------------------------------

        # create the base output folder if it doesn't exist
        if(!is_dir($path_output))
        {
            mkdir($path_output, 0777, true);
        }
        ###--------------------------------------------------
        $archive_files = $config['extension_name'] . '.tar';
        # use Magento architve to tar up the files
        $archiver = new Mage_Archive_Tar;
        $archiver->pack($temp_dir,$path_output.'/'.$archive_files,true);
        ###--------------------------------------------------

        # zip up the archive
        shell_exec('gzip '  . $path_output . '/' . $archive_files);
        shell_exec('mv '    . $path_output . '/' . $archive_files.'.gz '.$path_output.'/' . $archive_connect);
        ###--------------------------------------------------

        # Creating extension xml for connect using the extension name
        self::createExtensionXml($files, $config, $temp_dir, $path_output);
        ###--------------------------------------------------

        # Report on what we did
        self::output('');
        self::output('Build Complete');
        self::output('--------------------------------------------------');
        self::output( "Built tgz in $path_output\n");

        self::output(
            "Built XML for Connect Manager in" . "\n\n" .

            "   $path_output/var/connect " . "\n\n" .

            "place in `/path/to/magento/var/connect to load extension in Connect Manager");

        ###--------------------------------------------------

        return ob_get_clean();
    }

    static public function main($argv)
    {
        $this_script = array_shift($argv);
        $config_file = array_shift($argv);
        $config = self::loadConfig($config_file);

        self::output(
            self::buildExtensionFromConfig($config)
        );

    }
    /**
     * extrapolate the target module using the file absolute path
     * @param  string $filePath
     * @return string
     */
    static public function extractTarget($filePath)
    {
        foreach (self::getTargetMap() as $tMap) {
            $pattern = '#' . $tMap['path'] . '#';
            if (preg_match($pattern, $filePath)) {
                return $tMap['target'];
            }
        }
        return 'mage';
    }
    /**
     * get target map
     * @return array
     */
    static public function getTargetMap()
    {
        return array(
            array('path' => 'app/etc', 'target' => 'mageetc'),
            array('path' => 'app/code/local', 'target' => 'magelocal'),
            array('path' => 'app/code/community', 'target' => 'magecommunity'),
            array('path' => 'app/code/core', 'target' => 'magecore'),
            array('path' => 'app/design', 'target' => 'magedesign'),
            array('path' => 'lib', 'target' => 'magelib'),
            array('path' => 'app/locale', 'target' => 'magelocale'),
            array('path' => 'media/', 'target' => 'magemedia'),
            array('path' => 'skin/', 'target' => 'mageskin'),
            array('path' => 'http://', 'target' => 'mageweb'),
            array('path' => 'https://', 'target' => 'mageweb'),
            array('path' => 'Test/', 'target' => 'magetest'),
        );
    }
    static public function createExtensionXml($files, $config, $tempDir, $path_output)
    {
        $extensionPath = $tempDir . DIRECTORY_SEPARATOR . 'var/connect/';
        if (!is_dir($extensionPath)) {
            mkdir($extensionPath, 0777, true);
        }
        $extensionFileName = $extensionPath . $config['extension_name'] . '.xml';
        file_put_contents($extensionFileName, self::buildExtensionXml($files, $config));

        shell_exec('cp -Rf '    . $tempDir . DIRECTORY_SEPARATOR . 'var '. $path_output);
    }
    static public function buildExtensionXml($files, $config)
    {
        $xml = simplexml_load_string('<_/>');
        $build_data = self::getBuildData($xml, $files, $config);

        foreach ($build_data as $key => $value) {
            if (is_array($value) && is_callable($key)) {
                call_user_func_array($key, $value);
            } else {
                self::addChildNode($xml, $key, $value);
            }
        }

        return $xml->asXml();
    }
    /**
     * Get an array of data to build the extension xml. The array of data will contains the key necessary
     * to build each node and key that are actual callback functions to be called to build sub-section  of the xml.
     * @param  SimpleXMLElement $xml
     * @param  array $files
     * @param  array $config
     * @return array
     */
    static public function getBuildData(SimpleXMLElement $xml, array $files, array $config)
    {
        return array(
            'form_key' => isset($config['form_key']) ? $config['form_key'] : uniqid(),
            '_create' => isset($config['_create']) ? $config['_create'] : '',
            'name' => $config['extension_name'],
            'channel'=> $config['channel'],
            'Pulsestorm_MagentoTarToConnect::buildVersionIdsNode' => array($xml),
            'summary' => $config['summary'],
            'description' => $config['description'],
            'license' => $config['license'],
            'license_uri' => isset($config['license_uri']) ? $config['license_uri'] : '',
            'version' => $config['extension_version'],
            'stability' => $config['stability'],
            'notes' => $config['notes'],
            'Pulsestorm_MagentoTarToConnect::buildAuthorsNode' => array($xml, $config),
            'Pulsestorm_MagentoTarToConnect::buildPhpDependsNode' => array($xml, $config),
            'Pulsestorm_MagentoTarToConnect::buildContentsNode' => array($xml, $files)
        );
    }
    /**
     * Remove a passed in file absolute path and return the relative path to the Magento application file context.
     * @param  string $file
     * @return string
     */
    static public function extractRelativePath($file)
    {
        $pattern = '/app\/etc\/|app\/code\/community\/|app\/code\/local\/|app\/design\/|lib\/|app\/locale\/|skin\/|js\//';
        $relativePath = self::splitFilePath($file, $pattern);
        if ($file !== $relativePath) {
            return $relativePath;
        }
        $shellDir = 'shell';
        $relativePath = self::splitFilePath($file, '/' . $shellDir . '\//');
        return ($file !== $relativePath) ? $shellDir . DIRECTORY_SEPARATOR . $relativePath : $file;
    }
    /**
     * Split a file path using the passed in pattern and file absolute path and return
     * the relative path to the file.
     * @param  string $file
     * @param  string $pattern
     * @return string The relative path to file
     */
    static public function splitFilePath($file, $pattern)
    {
        $splitPath = preg_split($pattern, $file, -1);
        return (count($splitPath) > 1) ? $splitPath[1] : $file;
    }
    /**
     * Build 'contents' node including all its child nodes.
     * @param  SimpleXMLElement $xml
     * @param  array $files
     * @return void
     */
    static public function buildContentsNode(SimpleXMLElement $xml, array $files)
    {
        $node = self::addChildNode($xml, 'contents', '');
        $call_backs = array(
            'target' => 'Pulsestorm_MagentoTarToConnect::extractTarget',
            'path'   => 'Pulsestorm_MagentoTarToConnect::extractRelativePath',
            'type'   => 'file',
            'include'=> '',
            'ignore' => ''
        );

        $parent_nodes = array_reduce(array_keys($call_backs), function ($item, $key) use ($node) {
            $item[$key] = Pulsestorm_MagentoTarToConnect::addChildNode($node, $key, '');
            return $item;
        });

        // Adding empty node, this is a workaround for the Magento connect bug. 
        // When no empty nodes are added the first file is removed from the package extension.
        foreach ($parent_nodes as $child_key => $child_node) {
            self::addChildNode($child_node, $child_key, '');
        }

        foreach ($files as $file) {
            foreach ($parent_nodes as $key => $child_node) {
                $call_back = $call_backs[$key];
                $value = ($call_back === 'file') ? $call_back : (is_callable($call_back) ? call_user_func_array($call_back, array($file)) : $call_back);
                self::addChildNode($child_node, $key, $value);
            }
        }
    }
    /**
     * Add a 'depends_php_min' node and a 'depends_php_max' to the passed in SimpleXMLElement class instance object.
     * @param  SimpleXMLElement $xml
     * @param  array $config
     * @return void
     */
    static public function buildPhpDependsNode(SimpleXMLElement $xml, array $config)
    {
        $data = array('depends_php_min' => 'php_min', 'depends_php_max' => 'php_max');
        foreach ($data as $key => $cfg_key) {
            self::addChildNode($xml, $key, $config[$cfg_key]);
        }
    }
    /**
     * Get author data, which is a combination of author data and additional authors data from the configuration.
     * @param  array $config
     * @return array
     */
    static public function getAuthorData(array $config)
    {
        $authorList[0]      = array(
            'author_name'   => $config['author_name'],
            'author_user'   => $config['author_user'],
            'author_email'  => $config['author_email'],
        );
        if (array_key_exists('additional_authors', $config)) {
            $authorList = array_merge($authorList, $config['additional_authors']);
        }
        return $authorList;
    }
    /**
     * Get a specific author information by key.
     * @param  array $authorList
     * @param  string $key
     * @return array
     */
    static public function getAuthorInfoByKey(array $authorList, $key)
    {
        return array_map(function($author) use ($key) { return $author[$key]; }, $authorList);
    }
    /**
     * Build 'authors' node including all its child nodes.
     * @param  SimpleXMLElement $xml
     * @param  array $config
     * @return void
     */
    static public function buildAuthorsNode(SimpleXMLElement $xml, array $config)
    {
        $meta = array('name' => 'author_name', 'user' => 'author_user', 'email' => 'author_email');
        $authorList = self::getAuthorData($config);
        $authors = self::addChildNode($xml, 'authors', '');
        foreach ($meta as $key => $cfg_key) {
            $parentNode = self::addChildNode($authors, $key, '');
            foreach (self::getAuthorInfoByKey($authorList, $cfg_key) as $value) {
                self::addChildNode($parentNode, $key, $value);
            }
        }
    }
    /**
     * Build 'version_ids' node including all its child nodes.
     * @param  SimpleXMLElement $xml
     * @return void
     */
    static public function buildVersionIdsNode(SimpleXMLElement $xml)
    {
        $key = 'version_ids';
        $parentNode = self::addChildNode($xml, $key, '');
        foreach (array(2, 1) as $version) {
            self::addChildNode($parentNode, $key, $version);
        }
    }
    /**
     * Add child node to a passed in SimpleXMLElement class instance object.
     * @param  SimpleXMLElement $context
     * @param  string $name
     * @param  string $value
     * @return SimpleXMLElement
     */
    static public function addChildNode(SimpleXMLElement $context, $name, $value='')
    {
        $child = $context->addChild($name);
        if (trim($value)) {
            $child->{0} = $value;
        }
        return $child;
    }
}
if(isset($argv))
{
    Pulsestorm_MagentoTarToConnect::main($argv);
}
