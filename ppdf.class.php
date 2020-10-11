<?php
/**
 * @copyright Copyright 2020 Silvio Sparapano <ssilvio@libero.it>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License
 * 
 * 
 * @version 1.0.0
 */

require_once("fpdf/FPDF.class.php");

class ppdf 
{
    private $outputStream = STDOUT;
    private $version = "1.0.0";
    private $errorMsg = "";
    private $inputFiles = "";

    private $inputFolder = ".";
    private $outputFileName = "output.pdf";
    private $tmpDir = "./temp";
    private $filterExtension = array();
    private $filterName = array();
    private $orderBy = array('', 1);
    private $recursiveFolder = false;
    private $debugMode = false;

    private $cReset = "\033[0m";
    private $cWhite = "\033[37m";
    private $cRed = "\033[91m";
    private $cLight = "\033[97m";
    private $cGrey = "\033[90m";

    private $fpdf = null;

    private static $imageTypeArray = array
    (
        0=>'UNKNOWN',
        1=>'GIF',
        2=>'JPEG',
        3=>'PNG',
        4=>'SWF',
        5=>'PSD',
        6=>'BMP',
        7=>'TIFF_II',
        8=>'TIFF_MM',
        9=>'JPC',
        10=>'JP2',
        11=>'JPX',
        12=>'JB2',
        13=>'SWC',
        14=>'IFF',
        15=>'WBMP',
        16=>'XBM',
        17=>'ICO',
        18=>'COUNT' 
    );

    public function __construct($outputStream = STDOUT)
    {
        $this->outputStream = $outputStream;
        if(stream_isatty($this->outputStream)) {
        }else{
            $this->cReset = "";
            $this->cWhite = "";
            $this->cRed = "";
            $this->cLight = "";
            $this->cGrey = "";
        }
    }


    public function setInputFolder(string $inputFolderName) : bool {
        $ret = false;
        if(is_dir($inputFolderName)){
            $this->inputFolder = $inputFolderName;
            $ret = true;
        }else{
            $this->errorMsg = "Invalid input folder.";
            $this->showErrors();
        }
        return $ret;
    }

    public function setOutputFile(string $outputFileName) : bool {
        $ret = false;
        $pi = pathinfo($outputFileName);
        $po = filetype($pi['dirname']);
        if( ($po && $po=='dir') || (mkdir($pi['dirname'], 0777, true)) ){
            $this->outputFileName = $outputFileName;
            $ret = true;
        }else{
            $this->errorMsg = "Unable to create output file.";
            $this->showErrors();
        }
        return $ret;
    }

    public function setTempDir(string $tmpDir) : bool {
        $ret = false;
        $newT = $tmpDir . DIRECTORY_SEPARATOR . "temp";
        $pt = filetype($tmpDir);
        if( ($pt && $pt=='dir') || (mkdir($newT, 0777, true)) ){
            $this->tmpDir = $newT;
            $ret = true;
        }else{
            $this->errorMsg = "Unable to create temp folder.";
            $this->showErrors();
        }
        return $ret;
    }

    public function setFilterExtension(array $filter) {
        $this->filterExtension = $filter;
    }

    public function setFilterName(array $filter) {
        $this->filterName = $filter;
    }

    public function setSort(string $target, string $order = 'asc') : bool {
        $ret = true;
        $orderBy = array();
        if( strtolower($target) == "name" ){
            $orderBy[0] = 'BaseName';
        }elseif( strtolower($target) == "size" ){
            $orderBy[0] = 'Size';
        }elseif( strtolower($target) == "creation" ){
            $orderBy[0] = 'Creation';
        }elseif( strtolower($target) == "modify" ){
            $orderBy[0] = 'LastModified';
        }else{
            $this->errorMsg = "Invalid sort target.";
            $this->showErrors();
            $ret = false;
        }

        if( strtolower($order) == "desc" ) {
            $orderBy[1] = 2;
        }elseif(strtolower($order) == "asc"){
            $orderBy[1] = 1;
        }else{
            $this->errorMsg = "Invalid sort order.";
            $this->showErrors();
            $ret = false;
        }
        if($ret){
            $this->orderBy[0] = $orderBy[0];
            $this->orderBy[1] = $orderBy[1];
        }
        return $ret;
    }

    public function setRecursiveMode(bool $recursive = true) {
        $this->recursiveFolder = $recursive;
    }

    public function setDebugMode(bool $debug = true) {
        $this->debugMode = $debug;
    }

    public function getOutputStream(){
        return $this->outputStream;
    }

    function run(){
        $parseFolder = $this->parseFolder($this->inputFolder, $this->recursiveFolder);
        if(is_array($parseFolder)){
            //order by
            $this->inputFiles = $this->maSort($parseFolder, $this->orderBy[0], $this->orderBy[1]);
            fwrite($this->outputStream, "    ".$this->cLight."Input files: ".$this->cReset.PHP_EOL);

            $this->fpdf = new FPDF();
            $this->prepareTempDir($this->tmpDir);
            $mem_limit = ini_get ('memory_limit');
            ini_set ('memory_limit', '400M');
            foreach($this->inputFiles as $fileName => $fileProps){

                fwrite($this->outputStream, "    ".$this->cLight.$fileName.$this->cReset." # ".implode(", ", $fileProps).PHP_EOL);
                $this->addImage($fileName, $fileProps);

            }
            fwrite($this->outputStream, " ".$this->cLight."Processed files: ".$this->cReset.count($this->inputFiles).PHP_EOL);
            $out = $this->fpdf->Output("F", $this->outputFileName);
            $this->removeTempDir($this->tmpDir);
            ini_set ('memory_limit',$mem_limit);
        }else{
            $this->errorMsg = $parseFolder;
            $this->showErrors();
        }
    }



    function parseFolder($folderName, $recursive = true) {
        $ret = "Unable to read input folder: ".$folderName;
        fwrite($this->outputStream, "    ".$this->cLight."Input folder: ".$this->cReset.$folderName.PHP_EOL);
        $files = scandir($folderName);
        $returnedFiles = array();
        if($files){
            foreach($files as $fileName){
                if($fileName != "." && $fileName != ".."){
                    $fullFileName = $folderName.DIRECTORY_SEPARATOR.$fileName;
                    $fileType = filetype($fullFileName);

                    if( (!$recursive && $fileType == "file") || ($recursive && ($fileType == "file" || $fileType == "dir")) ){

                        if($fileType == "dir"){
                            $returnedFiles = array_merge($returnedFiles, $this->parseFolder($fullFileName, $recursive));

                        }elseif($fileType == "file"){
                            $fileInfo = $this->getFileInfo($fullFileName);

                            $bInclude = false;
                            //Filter on image
                            if(strpos($fileInfo['MimeType'], "image") !== false ){

                                //Filter Extension
                                if( (sizeof($this->filterExtension)>0 && in_array($fileInfo['Extension'], $this->filterExtension)) ||
                                    (sizeof($this->filterExtension) == 0) ){

                                    //Filter Name (substring)
                                    if( sizeof($this->filterName)>0 ){
                                        foreach ($this->filterName as $key => $value) {
                                            if(stristr($fileInfo['BaseNameNoExt'], $value)){
                                                $bInclude = true;
                                            }
                                        }
                                    }else{
                                        $bInclude = true;
                                    }
                                }

                            }
                            
                            if($bInclude){
                                $returnedFiles[$fullFileName] = $fileInfo;
                            }

                        }
                    }
                }
            }
        $ret = $returnedFiles;          
        }

        return $ret;
    }

    function getFileInfo($file) : array {
        $ret = array();
        if(file_exists($file)){
            $stat = stat($file);
            $pathinfo = pathinfo($file);
            $ret['BaseName'] = $pathinfo['basename'];
            $ret['BaseNameNoExt'] = $pathinfo['filename'];
            $ret['Type'] = filetype($file);
            $ret['MimeType'] = mime_content_type($file);
            $ret['LastModified'] = $stat['mtime'];
            $ret['LastAccess'] = $stat['atime'];
            $ret['Creation'] = $stat['ctime'];
            $ret['Size'] = $stat['size'];
            $ret['Extension'] = isset($pathinfo['extension']) ? $pathinfo['extension'] : "";
        }
        return $ret;
    }

    function maSort(array $ma, string $sortkey, $sortorder = 1) { // sortorder: 1=asc, 2=desc
        $ret = $ma;
        $temp = array();
        if (strlen($sortkey)>0) { // confirm inputs
            foreach ($ma as $k=>$a) $temp["$a[$sortkey]"][$k] = $a; // temp ma with sort value, quotes convert key to string in case numeric float
            if ($sortorder == 2) { // descending
                krsort($temp);
            } else { // ascending
                ksort($temp);
            }
            $newma = array(); // blank output multiarray to add to
            foreach ($temp as $sma) $newma += $sma; // add sorted arrays to output array
            unset($ma, $sma, $temp); // release memory
            $ret = $newma;
        }
        return $ret;
    }
    
    function addImage($file, $fileProps, $coords = array('x'=>null, 'y'=>null), $dimMax = array('w'=>180, 'h'=>260)){
        /** 
         * Supported format by FPDF are jpeg, png, gif.  
         * Bmp and other formats can be converted using GD.
         * 
         * We will use the original size if they're not greater than A4 page (default is 180x260). 
         * Warning the pixel of the PDF pages doesn't corresponds to the pixel of images.
         * If the image size is greater than page size, the image is proportionally resized until it takes the whole page.
        */
        if($this->debugMode){
            error_log("> addImage(".$file.")");
        }
        $ret = false;
        $fpdf = $this->fpdf;
        $headerLine = "N:".$fileProps['BaseName'];

        $gdInfo = getimagesize ($file);
        if(is_array($gdInfo) && sizeof($gdInfo)>0){
            $headerLine .= " D:".$gdInfo[0]."x".$gdInfo[1] . " T:" . self::$imageTypeArray[$gdInfo[2]] . " MT:".$fileProps['MimeType']; 

            $tmpFile = "";
            $scaledSize = array();
            $res = null;

            error_clear_last();

            if($gdInfo[2] == IMAGETYPE_BMP){
                $res = imagecreatefrombmp ($file);
            }elseif($gdInfo[2] == IMAGETYPE_JPEG){
                $res = imagecreatefromjpeg ($file);
            }elseif($gdInfo[2] == IMAGETYPE_GIF){
                $res = imagecreatefromgif ($file);
            }elseif($gdInfo[2] == IMAGETYPE_PNG){
                $res = imagecreatefrompng ($file);
            }
            $lastError =  error_get_last();

            if($res && !$lastError){
                $scaledSize = $this->computeScaledImageSize($dimMax['w'], $dimMax['h'], $gdInfo[0], $gdInfo[1]);
                $tmpFile = $this->tmpDir.'/'.bin2hex(random_bytes(16)).'.jpg';
                imagejpeg($res, $tmpFile);
                imagedestroy($res);

                $fpdf->AddPage();
                $fpdf->SetFont('helvetica','',8);
                if($this->debugMode) {
                    $fpdf->Write(4, $headerLine);
                };
                $fpdf->Ln();
                if($gdInfo[0]==$scaledSize['ws']){
                    $fpdf->Image($tmpFile, $coords['x'], $coords['y'] );
                }else{
                    $fpdf->Image($tmpFile, $coords['x'], $coords['y'], $scaledSize['ws'], $scaledSize['hs'] );
                }
                $ret = true;
    
            }else{

                if($this->debugMode){
                    if($lastError){ error_log("Error get last: <" . print_r($lastError, true) . ">"); }
                    error_log("File <".$file."> is skipped because has a not supported format (".$fileProps['MimeType'].").");
                }                
            }
    
        }else{
            if($this->debugMode){
                error_log("File <".$file."> is skipped because has a not supported format from gd (".$fileProps['MimeType'].").");
            }
        }
        return $ret;
    }

    function computeScaledImageSize($wMax, $hMax, $w, $h){
        $ret = array();

        $sf = 1;
        $ret['ws'] = $w;
        $ret['hs'] = $h;

        $rMax = $wMax / $hMax;
        $r = $w / $h;

        if($r>$rMax && $w>$wMax){
            $sf = $wMax / $w;
        }
        if($r<$rMax && $h>$hMax){
            $sf = $hMax / $h;
        }

        $ret['ws'] = $w * $sf;
        $ret['hs'] = $h * $sf;
        return($ret);
    }

    function prepareTempDir($dirPath){
        if(is_dir($dirPath)){
            $this->removeTempDir($dirPath, true);
        }else{
            mkdir($dirPath);
        }
    }
    function removeTempDir($dirPath, $onlyFiles = false){
        if(is_dir($dirPath)){
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                   $this->removeTempDir($file, $onlyFiles);
                } else {
                    unlink($file);
                }
            }
            if(!$onlyFiles){
                rmdir($dirPath);
            }
        }
    }


    function showErrors(){
        if(strlen($this->errorMsg)>0){
            fwrite($this->outputStream, PHP_EOL.$this->cRed . "Error: " . $this->errorMsg . $this->cReset.PHP_EOL);
        }
    }

    function showVersion(){
        fwrite($this->outputStream, $this->version);
    }
    
    function showMan(){
        $k =  
<<<MAN
   $this->cWhite NAME $this->cGrey
        ppdf.class.php
        This class creates PDF file from several set of files containing images.

   $this->cWhite DESCRIPTION $this->cGrey
        This class creates PDF file from several set of files containing images.  
        This can be very useful when multiple pages are scanned from a paper document, and you need to have all of them into a unique PDF file.
        It scans a directory (and optionally every sub-directory) from file system, searching for files of type images.
        Each image takes a new page into the target PDF file. The target PDF file page format is A4.
        If the image size is greater than an A4 page, the image is resized. If not, it remains with its original size.
    
       $this->cWhite Filters $this->cGrey
        Is it possibile to filter the images basing on file name and/or file extension.
        Multiple token can be used for matching (case insensitive). 
    
       $this->cWhite Sorting $this->cGrey
        Is it possibile to specify various sorting criteria: file name, file size, creation date, last moadify date.
        Ascending/descending order is optional.
    
       $this->cWhite Temporary files $this->cGrey
        In order to resize the images, a temporary folder is used. Is it possibile to change it, in each cases a folder "temp" is created into the 
        specified folder. After the processing, alle temporary files are deleted.
    
       $this->cWhite Output Stream $this->cGrey
        ppdf can use standard output or another different output stream for output messages. 
    
       $this->cWhite Debug Mode $this->cGrey
        Optionally is possible to set the debug mode in order to increase verbosity or activity into the deafult php error log file.
        If debug mode is active, each images into the target PDF has some basic information printed on top of the page. 
    
   $this->cWhite GENERAL INFO & LIMITATIONS $this->cGrey
        ppdf uses GD extension in order to manipulate images. 
        Actually the supported format are bmp, jpeg, gif and png. Other image format are skipped.  
        ppdf uses the free FPDF class by Olivier PLATHEY to create pdf document. Thanks to Olivier PLATHEY for this product.
$this->cReset    
MAN;
        fwrite($this->outputStream, $k);
    }



}
