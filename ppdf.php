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
 * @file doxphp.php
 * This script is a general language filter for Doxygen documentation, useful for languages not supported by Doxygen.
 * It creates a php-like source code, starting from a DocBlock documented source file.  
 * Doesn't matter the programming language of the source file, the script analyze only the DocBlocks inside and create minimal source code declaration for Doxygen.
 * The output can be interpreted by Doxygen as standard PHP code.
 * 
 * **General info & limitations**  
 * Only `@class`, `@fn`, and `@var` Doxygen commands are managed by this script (with '@', not '\'!).
 * All other Doxygen command can exists into DocBlocks but will be ignored by the script (not by Doxygen).
 * The whole source code is *not* reported to the output.     
 * The output contains only the original DocBlocks, and below of each of them, one row representing the declaration of Class, Function or Variable to be documented.
 * Only documented section of the source file will be processed. 
 * DocBlocks must be defined by '/** ... *' multi-line sections (not "//").
 *
 * **Classes**  
 * Use the command `@class` followed by the class name.
 * The script will report to the output the DocBlock, followed by the class definition.
 * IMPORTANT: do not place other comments after `@class <className>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Functions**
 * Use the command `@fn` followed by the function name, including parameters. 
 * Example: @fn foo(bar)
 * This script will report to the output the DocBlock, followed by the function definition.
 * If the function belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
 * IMPORTANT: do not place other comments after `@fn <functionName>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Variables**
 * Use the command `@var` followed by the variable name.
 * Example: @var foo
 * If the variable belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
 * IMPORTANT: do not place other comments after `@var <variableName>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Doxygen Configuration**
 * From Doxygen configuration file (e.g. for javascript source code):
 * FILTER_PATTERNS        = *.js="php /doxphp.php"
 * 
 * @version 1.0.0
 */

require_once("ppdf.class.php");

$o = fopen('e:\test\outstream.txt', 'w');
$dox = new ppdf();
$opt = parseOptions($dox);
if($opt[0]){
    $dox->run();
}elseif($opt[1]){
    showHelp($dox);
}

function cReset() {return "\033[0m";};
function cWhite() {return "\033[37m";};
function cRed() {return "\033[91m";};
function cLight() {return "\033[97m";};
function cGrey() {return "\033[90m";};

function showHelp($dox){
    fwrite($dox->getOutputStream(), PHP_EOL);
    fwrite($dox->getOutputStream(), cGrey()."Usage:".cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'> php ppdf.php [options] [-i "<input>"] [-o "<output>"] [-t "<temp>"] [-x "<filter>, <filter>, ..."] [-n "<filter>, <filter>, ..."] [-s "name|size|creation|modify[,asc|desc"]'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), PHP_EOL);
    fwrite($dox->getOutputStream(), cGrey()."Options:".cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-i "<input>"                                Folder to parse for input files (default current)'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-o "<output>"                               Output file name whith full path (default "./output.pdf")'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-t "<temp>"                                 Folder for temporary files (default "./temp")'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-x "<filter>, <filter>, ..."                Filters for extension input filenames'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-n "<filter>, <filter>, ..."                Filters for file input filenames'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-s "name|size|creation|modify[,asc|desc]"   Ordering files mode'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-r                                          Set recursive input folders mode'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-d                                          Enable debug mode'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-h, --help                                  Show usage'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-v, --version                               Show version'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), '    '.cLight().'-v, --manual                                Show manual'.cReset().PHP_EOL);
    fwrite($dox->getOutputStream(), PHP_EOL);
}

function parseOptions($dox) : array {

    $ret = false;
    $showhelp = false;
    $options = getopt("i:o:t:x:n:s:hvmrd", ["help", "version", "manual"]);

    if(array_key_exists("version", $options) || array_key_exists("v", $options)){
        $dox->showVersion();
    }elseif(array_key_exists("help", $options) || array_key_exists("h", $options)){
        $showhelp = true;
    }elseif(array_key_exists("manual", $options) || array_key_exists("m", $options)){
        $dox->showMan();
        $showhelp = true;
    }else{

        $ret = true;

        if(array_key_exists("i", $options)){
            if(!$dox->setInputFolder($options['i'])){
                $ret = false;
                $showhelp = true;
            }
        }
        if(array_key_exists("o", $options)){
            if(!$dox->setOutputFile($options['o'])){
                $ret = false;
                $showhelp = true;
            }
        }
        if(array_key_exists("t", $options)){
            if(!$dox->setTempDir($options['t'])){
                $ret = false;
                $showhelp = true;
            }
        }
        if(array_key_exists("x", $options)){
            //Filter on extension of the files
            $dox->setFilterExtension(array_map('trim', explode(",", $options['x'])));
        }            
        if(array_key_exists("n", $options)){
            //Filter on the name of the files
            $dox->setFilterName(array_map('trim', explode(",", $options['n'])));
        }
        if(array_key_exists("s", $options)){
            //Sorting mode
            $sort = array_map('strtolower', array_map('trim', explode(",", $options['s'])));
            if(isset($sort[0])){
                if(isset($sort[1])){
                    if(!$dox->setSort($sort[0], $sort[1])){
                        $ret = false;
                        $showhelp = true;
                    }
                }else{
                    if(!$dox->setSort($sort[0])){
                        $ret = false;
                        $showhelp = true;
                    }
                }
            }else{
                $ret = false;
                $showhelp = true;
            }
        }
        if(array_key_exists("r", $options)){
            //Option to search in recursive subdirectory mode
            $dox->setRecursiveMode(true);
        }else{
            $dox->setRecursiveMode(false);
        }
        if(array_key_exists("d", $options)){
            //Option to set the debug mode
            $dox->setDebugMode(true);
        }else{
            $dox->setDebugMode(false);
        }
    }
    return [$ret, $showhelp];
}


