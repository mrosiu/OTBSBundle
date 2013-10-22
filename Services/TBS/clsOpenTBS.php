<?php

namespace DevDash\OTBSBundle\Services\TBS;

/**
 * Constants to drive the plugin.
 */
define('OPENTBS_PLUGIN','clsOpenTBS');
define('OPENTBS_DOWNLOAD',1);   // download (default) = TBS_OUTPUT
define('OPENTBS_NOHEADER',4);   // option to use with DOWNLOAD: no header is sent
define('OPENTBS_FILE',8);       // output to file   = TBSZIP_FILE
define('OPENTBS_DEBUG_XML',16); // display the result of the current subfile
define('OPENTBS_STRING',32);    // output to string = TBSZIP_STRING
define('OPENTBS_DEBUG_AVOIDAUTOFIELDS',64); // avoit auto field merging during the Show() method
define('OPENTBS_INFO','clsOpenTBS.Info');       // command to display the archive info
define('OPENTBS_RESET','clsOpenTBS.Reset');      // command to reset the changes in the current archive
define('OPENTBS_ADDFILE','clsOpenTBS.AddFile');    // command to add a new file in the archive
define('OPENTBS_DELETEFILE','clsOpenTBS.DeleteFile'); // command to delete a file in the archive
define('OPENTBS_REPLACEFILE','clsOpenTBS.ReplaceFile'); // command to replace a file in the archive
define('OPENTBS_FILEEXISTS','clsOpenTBS.FileExists');
define('OPENTBS_CHART','clsOpenTBS.Chart'); // command to delete a file in the archive
define('OPENTBS_DEFAULT','');   // Charset
define('OPENTBS_ALREADY_XML',false);
define('OPENTBS_ALREADY_UTF8','already_utf8');
define('OPENTBS_DEBUG_XML_SHOW','clsOpenTBS.DebugXmlShow');
define('OPENTBS_DEBUG_XML_CURRENT','clsOpenTBS.DebugXmlCurrent');
define('OPENTBS_DEBUG_INFO','clsOpenTBS.DebugInfo');
define('OPENTBS_DEBUG_CHART_LIST','clsOpenTBS.DebugInfo'); // deprecated
define('OPENTBS_FORCE_DOCTYPE','clsOpenTBS.ForceDocType');
define('OPENTBS_DELETE_ELEMENTS','clsOpenTBS.DeleteElements');
define('OPENTBS_SELECT_SHEET','clsOpenTBS.SelectSheet');
define('OPENTBS_SELECT_SLIDE','clsOpenTBS.SelectSlide');
define('OPENTBS_SELECT_MAIN','clsOpenTBS.SelectMain');
define('OPENTBS_DISPLAY_SHEETS','clsOpenTBS.DisplaySheets');
define('OPENTBS_DELETE_SHEETS','clsOpenTBS.DeleteSheets');
define('OPENTBS_DELETE_COMMENTS','clsOpenTBS.DeleteComments');
define('OPENTBS_MERGE_SPECIAL_ITEMS','clsOpenTBS.MergeSpecialItems');
define('OPENTBS_CHANGE_PICTURE','clsOpenTBS.ChangePicture');
define('OPENTBS_COUNT_SLIDES','clsOpenTBS.CountSlides');
define('OPENTBS_SEARCH_IN_SLIDES','clsOpenTBS.SearchInSlides');
define('OPENTBS_DISPLAY_SLIDES','clsOpenTBS.DisplaySlides');
define('OPENTBS_DELETE_SLIDES','clsOpenTBS.DeleteSlides');
define('OPENTBS_FIRST',1);
define('OPENTBS_GO',2);
define('OPENTBS_ALL',4);

/**
 *   Main class which is a TinyButStrong plug-in.
 *   It is also a extension of clsTbsZip so it can directly manage the archive underlying the template.
 *
 * @version 1.8.1
 * @date 2013-08-31
 * @see     http://www.tinybutstrong.com/plugins.php
 * @author  Skrol29 http://www.tinybutstrong.com/onlyyou.html
 * @license LGPL
 */
class clsOpenTBS extends clsTbsZip
{
    public function OnInstall()
    {
        $TBS =& $this->TBS;

        if (!isset($TBS->OtbsAutoLoad))           $TBS->OtbsAutoLoad = true; // TBS will load the subfile regarding to the extension of the archive
        if (!isset($TBS->OtbsConvBr))             $TBS->OtbsConvBr = false;  // string for NewLine conversion
        if (!isset($TBS->OtbsAutoUncompress))     $TBS->OtbsAutoUncompress = $this->Meth8Ok;
        if (!isset($TBS->OtbsConvertApostrophes)) $TBS->OtbsConvertApostrophes = true;
        if (!isset($TBS->OtbsSpacePreserve))      $TBS->OtbsSpacePreserve = true;
        if (!isset($TBS->OtbsClearMsWord))        $TBS->OtbsClearMsWord = true;
        if (!isset($TBS->OtbsMsExcelConsistent))  $TBS->OtbsMsExcelConsistent = true;
        if (!isset($TBS->OtbsMsExcelExplicitRef)) $TBS->OtbsMsExcelExplicitRef = true;
        if (!isset($TBS->OtbsClearMsPowerpoint))  $TBS->OtbsClearMsPowerpoint = true;
        if (!isset($TBS->OtbsGarbageCollector))   $TBS->OtbsGarbageCollector = true;
        $this->Version = '1.8.1';
        $this->DebugLst = false; // deactivate the debug mode
        $this->ExtInfo = false;
        $TBS->TbsZip = &$this; // a shortcut

        return array('BeforeLoadTemplate','BeforeShow', 'OnCommand', 'OnOperation', 'OnCacheField');
    }

    public function BeforeLoadTemplate(&$File,&$Charset)
    {
        $TBS =& $this->TBS;
        if ($TBS->_Mode!=0) return; // If we are in subtemplate mode, the we use the TBS default process

        // Decompose the file path. The syntaxe is 'Archive.ext#subfile', or 'Archive.ext', or '#subfile'
        $FilePath = $File;
        $SubFileLst = false;
        if (is_string($File)) {
            $p = strpos($File, '#');
            if ($p!==false) {
                $FilePath = substr($File,0,$p);
                $SubFileLst = substr($File,$p+1);
            }
        }

        // Open the archive
        if ($FilePath!=='') {
            $ok = @$this->Open($FilePath);  // Open the archive
            if (!$ok) {
                if ($this->ArchHnd===false) {
                    return $this->RaiseError("The template '".$this->ArchFile."' cannot be found.");
                } else {
                    return false;
                }
            }
            $this->TbsInitArchive(); // Initialize other archive informations
            if ($TBS->OtbsAutoLoad && ($this->ExtInfo!==false) && ($SubFileLst===false)) {
                // auto load files from the archive
                $SubFileLst = $this->ExtInfo['load'];
                $TBS->OtbsConvBr = $this->ExtInfo['br'];
            }
            $TBS->OtbsSubFileLst = $SubFileLst;
        } elseif ($this->ArchFile==='') {
            $this->RaiseError('Cannot read file(s) "'.$SubFileLst.'" because no archive is opened.');
        }

        // Change the Charset if a new archive is opended, or if LoadTemplate is called explicitely for that
        if (($FilePath!=='') || ($File==='')) {
            if ($Charset===OPENTBS_ALREADY_XML) {
                $TBS->LoadTemplate('', false);                       // Define the function for string conversion
            } elseif ($Charset===OPENTBS_ALREADY_UTF8) {
                $TBS->LoadTemplate('', array(&$this,'ConvXmlOnly')); // Define the function for string conversion
            } else {
                $TBS->LoadTemplate('', array(&$this,'ConvXmlUtf8')); // Define the function for string conversion
            }
        }

        // Load the subfile(s)
        if (($SubFileLst!=='') && ($SubFileLst!==false)) {
            if (is_string($SubFileLst)) $SubFileLst = explode(';',$SubFileLst);
            $this->TbsLoadSubFileAsTemplate($SubFileLst);
        }

        if ($FilePath!=='') $TBS->_LastFile = $this->ArchFile;
        return false; // default LoadTemplate() process is not executed

    }

    public function BeforeShow(&$Render, $File='')
    {
        $TBS =& $this->TBS;

        if ($TBS->_Mode!=0) return; // If we are in subtemplate mode, the we use the TBS default process

        $this->TbsStorePark(); // Save the current loaded subfile if any

        $TBS->Plugin(-4); // deactivate other plugins

        $Debug = (($Render & OPENTBS_DEBUG_XML)==OPENTBS_DEBUG_XML);
        if ($Debug) $this->DebugLst = array();

        $TbsShow = (($Render & OPENTBS_DEBUG_AVOIDAUTOFIELDS)!=OPENTBS_DEBUG_AVOIDAUTOFIELDS);

        switch ($this->ExtEquiv) {
            case 'ods':  $this->OpenDoc_SheetSlides_DeleteAndDisplay(true); break;
            case 'odp':  $this->OpenDoc_SheetSlides_DeleteAndDisplay(false); break;
            case 'xlsx': $this->MsExcel_SheetDeleteAndDisplay(); break;
            case 'pptx': $this->MsPowerpoint_SlideDelete(); break;
            case 'docx': $this->MsWord_RenumDocPr(); break;
        }

        $explicitRef = ($TBS->OtbsMsExcelExplicitRef && ($this->ExtEquiv==='xlsx'));

        // Commit special OpenXML features if any
        // Must be done before the loop because some REL file can also be in TbsStoreLst
        if ($this->OpenXmlRid!==false) $this->OpenXML_RidCommit($Debug);

        // Merges all modified subfiles
        $idx_lst = array_keys($this->TbsStoreLst);
        foreach ($idx_lst as $idx) {
            $TBS->Source = $this->TbsStoreLst[$idx]['src'];
            $onshow = $this->TbsStoreLst[$idx]['onshow'];
            unset($this->TbsStoreLst[$idx]); // save memory space
            $TBS->OtbsCurrFile = $this->TbsGetFileName($idx); // usefull for TbsPicAdd()
            $this->TbsCurrIdx = $idx; // usefull for debug mode
            if ($TbsShow && $onshow) $TBS->Show(TBS_NOTHING);
            if ($explicitRef) $this->MsExcel_ConvertToExplicit($TBS->Source);
            if ($Debug) $this->DebugLst[$this->TbsGetFileName($idx)] = $TBS->Source;
            $this->FileReplace($idx, $TBS->Source, TBSZIP_STRING, $TBS->OtbsAutoUncompress);
        }
        $TBS->Plugin(-10); // reactivate other plugins
        $this->TbsCurrIdx = false;

        if ($this->OpenXmlCTypes!==false) $this->OpenXML_CTypesCommit($Debug);    // Commit special OpenXML features if any
        if ($this->OpenDocManif!==false)  $this->OpenDoc_ManifestCommit($Debug);  // Commit special OpenDocument features if any
        if ($this->OpenXmlRid!==false) $this->OpenXML_RidCommit($Debug); // Must be done also after the loop because some Rid can be added with [onshow]

        if ($TBS->OtbsGarbageCollector) {
            if ($this->ExtType=='openxml') $this->OpenMXL_GarbageCollector();
        }

        if ( ($TBS->ErrCount>0) && (!$TBS->NoErr) && (!$Debug)) {
            $TBS->meth_Misc_Alert('Show() Method', 'The output is cancelled by the OpenTBS plugin because at least one error has occured.');
            exit;
        }

        if ($Debug) {
            // Do the debug even if other options are used
            $this->TbsDebug_Merge(true, false);
        } elseif (($Render & TBS_OUTPUT)==TBS_OUTPUT) { // notice that TBS_OUTPUT = OPENTBS_DOWNLOAD
            // download
            $ContentType = (isset($this->ExtInfo['ctype'])) ? $this->ExtInfo['ctype'] : '';
            $this->Flush($Render, $File, $ContentType); // $Render is used because it can contain options OPENTBS_DOWNLOAD and OPENTBS_NOHEADER.
            $Render = $Render - TBS_OUTPUT; //prevent TBS from an extra output.
        } elseif (($Render & OPENTBS_FILE)==OPENTBS_FILE) {
            // to file
            $this->Flush(TBSZIP_FILE, $File);
        } elseif (($Render & OPENTBS_STRING)==OPENTBS_STRING) {
            // to string
            $this->Flush(TBSZIP_STRING);
            $TBS->Source = $this->OutputSrc;
            $this->OutputSrc = '';
        }

        if (($Render & TBS_EXIT)==TBS_EXIT) {
            $this->Close();
            exit;
        }

        return false; // cancel the default Show() process

    }

    public function OnCacheField($BlockName,&$Loc,&$Txt,$PrmProc)
    {
        if (isset($Loc->PrmLst['ope'])) {

            $ope_lst = explode(',', $Loc->PrmLst['ope']); // in this event, ope is not exploded

            // Prepare to change picture
            if (in_array('changepic', $ope_lst)) {
                $this->TbsPicFound($Txt, $Loc, true); // add parameter "att" which will be processed just after this event, when the field is cached
                $Loc->PrmLst['pic_change'] = true;
            } elseif (in_array('mergecell', $ope_lst)) {
                $this->TbsPrepareMergeCell($Txt, $Loc);
            }

            // Change cell type
            foreach ($ope_lst as $ope) {
                $x = substr($ope,0,4);
                if ( ($x==='tbs:') || ($x==='xlsx') || (substr($ope,0,3)==='ods') ) {
                    if ($this->ExtEquiv==='ods') {
                        $z = '';
                        $this->OpenDoc_ChangeCellType($Txt, $Loc, $ope, false, $z);
                    } elseif ($this->ExtEquiv==='xlsx') {
                        $this->MsExcel_ChangeCellType($Txt, $Loc, $ope);
                    }

                    return; // do only one change
                }
            }

        }

    }

    public function OnOperation($FieldName,&$Value,&$PrmLst,&$Txt,$PosBeg,$PosEnd,&$Loc)
    {
    // in this event, ope is exploded, there is one function call for each ope command
        $ope = $PrmLst['ope'];
        if ($ope==='addpic') {
            $this->TbsPicAdd($Value, $PrmLst, $Txt, $Loc, 'ope=addpic');
        } elseif ($ope==='changepic') {
            if (!isset($PrmLst['pic_change'])) {
                $this->TbsPicFound($Txt, $Loc, false);  // add parameter "att" which will be processed just before the value is merged
                $PrmLst['pic_change'] = true;
            }
            $this->TbsPicAdd($Value, $PrmLst, $Txt, $Loc, 'ope=changepic');
        } elseif ($ope==='delcol') {
            $this->TbsDeleteColumns($Txt, $Value, $PrmLst, $PosBeg, $PosEnd);

            return false; // prevent TBS from merging the field
        } elseif ($ope==='mergecell') {
            if (isset($this->PrevVals[$Loc->FullName])) {
                if ($Value==$this->PrevVals[$Loc->FullName]) {
                    $Value = '<w:vMerge w:val="continue"/>';
                } else {
                    $this->PrevVals[$Loc->FullName] = $Value;
                    $Value = '<w:vMerge w:val="restart"/>';
                }
            }
        } else {
            $x = substr($ope,0,4);
            if ( ($x==='tbs:') || ($x==='xlsx') || (substr($ope,0,3)==='ods') ) {
                if ($this->ExtEquiv==='ods') {
                    if (!isset($Loc->PrmLst['cellok'])) $this->OpenDoc_ChangeCellType($Txt, $Loc, $ope, true, $Value);
                } elseif ($this->ExtEquiv==='xlsx') {
                    if (!isset($Loc->PrmLst['cellok'])) $this->MsExcel_ChangeCellType($Txt, $Loc, $ope);
                    $this->MsExcel_ChangeCellValue($Loc, $Value);
                }
            }
        }
    }

    public function OnCommand($Cmd, $x1=null, $x2=null, $x3=null, $x4=null, $x5=null)
    {
        if ($Cmd==OPENTBS_INFO) {

            // Display debug information
            echo "<strong>OpenTBS plugin Information</strong><br>\r\n";

            return $this->Debug();

        } elseif ($Cmd==OPENTBS_RESET) {

            // Reset all mergings
            $this->ArchCancelModif();
            $this->TbsStoreLst = array();
            $TBS =& $this->TBS;
            $TBS->Source = '';
            $TBS->OtbsCurrFile = false;
            if (is_string($TBS->OtbsSubFileLst)) {
                $f = '#'.$TBS->OtbsSubFileLst;
                $h = '';
                $this->BeforeLoadTemplate($f,$h);
            }

            return true;

        } elseif ( ($Cmd==OPENTBS_ADDFILE) || ($Cmd==OPENTBS_REPLACEFILE) ) {

            // Add a new file or cancel a previous add
            $Name = (is_null($x1)) ? false : $x1;
            $Data = (is_null($x2)) ? false : $x2;
            $DataType = (is_null($x3)) ? TBSZIP_STRING : $x3;
            $Compress = (is_null($x4)) ? true : $x4;

            if ($Cmd==OPENTBS_ADDFILE) {
                return $this->FileAdd($Name, $Data, $DataType, $Compress);
            } else {
                return $this->FileReplace($Name, $Data, $DataType, $Compress);
            }

        } elseif ($Cmd==OPENTBS_DELETEFILE) {

            // Delete an existing file in the archive
            $Name = (is_null($x1)) ? false : $x1;
            $this->FileCancelModif($Name, false);    // cancel added files

            return $this->FileReplace($Name, false); // mark the file as to be deleted

        } elseif ($Cmd==OPENTBS_FILEEXISTS) {
            return $this->FileExists($x1);

        } elseif ($Cmd==OPENTBS_CHART) {

            $ChartRef = $x1;
            $SeriesNameOrNum = $x2;
            $NewValues = (is_null($x3)) ? false : $x3;
            $NewLegend = (is_null($x4)) ? false : $x4;

            if ($this->ExtType=='odf') {
                return $this->OpenDoc_ChartChangeSeries($ChartRef, $SeriesNameOrNum, $NewValues, $NewLegend);
            } else {
                return $this->OpenXML_ChartChangeSeries($ChartRef, $SeriesNameOrNum, $NewValues, $NewLegend);
            }

        } elseif ( ($Cmd==OPENTBS_DEBUG_INFO) || ($Cmd==OPENTBS_DEBUG_CHART_LIST) ) {

            if (is_null($x1)) $x1 = true;
            $this->TbsDebug_Info($x1);

        } elseif ($Cmd==OPENTBS_DEBUG_XML_SHOW) {

            $this->TBS->Show(OPENTBS_DEBUG_XML);

        } elseif ($Cmd==OPENTBS_DEBUG_XML_CURRENT) {

            $this->TbsStorePark();
            $this->DebugLst = array();
            foreach ($this->TbsStoreLst as $idx=>$park) $this->DebugLst[$this->TbsGetFileName($idx)] = $park['src'];
            $this->TbsDebug_Merge(true, true);

            if (is_null($x1) || $x1) exit();

        } elseif ($Cmd==OPENTBS_FORCE_DOCTYPE) {
            return $this->Ext_PrepareInfo($x1);

        } elseif ($Cmd==OPENTBS_DELETE_ELEMENTS) {

            if (is_string($x1)) $x1 = explode(',', $x1);
            if (is_null($x2)) $x2 = false; // OnlyInner
            return $this->XML_DeleteElements($this->TBS->Source, $x1, $x2);

        } elseif ($Cmd==OPENTBS_SELECT_MAIN) {

            if ( ($this->ExtInfo!==false) && isset($this->ExtInfo['main']) ) {
                $this->TbsLoadSubFileAsTemplate($this->ExtInfo['main']);

                return true;
            } else {
                return false;
            }

        } elseif ($Cmd==OPENTBS_SELECT_SHEET) {

            // Only XLSX files have sheets in separated subfiles.
            if ($this->ExtEquiv==='xlsx') {
                $o = $this->MsExcel_SheetGet($x1);
                if ($o===false) return;
                if ($o->file===false) return $this->RaiseError("($Cmd) Error with sheet '$x1'. The corresponding XML subfile is not referenced.");
                return $this->TbsLoadSubFileAsTemplate('xl/'.$o->file);
            }

            return true;

        } elseif ( ($Cmd==OPENTBS_DELETE_SHEETS) || ($Cmd==OPENTBS_DISPLAY_SHEETS) || ($Cmd==OPENTBS_DELETE_SLIDES) || ($Cmd==OPENTBS_DISPLAY_SLIDES) ) {

            $delete = ( ($Cmd==OPENTBS_DELETE_SHEETS) || ($Cmd==OPENTBS_DELETE_SLIDES) ) ;
            $this->TbsSheetSlide_DeleteDisplay($x1, $x2, $delete);

        } elseif ($Cmd==OPENTBS_MERGE_SPECIAL_ITEMS) {

            if ($this->ExtEquiv!='xlsx') return 0;
            $lst = $this->MsExcel_GetDrawingLst();
            $this->TbsQuickLoad($lst);

        } elseif ($Cmd==OPENTBS_SELECT_SLIDE) {

            if ($this->ExtEquiv!='pptx') return false;

            $this->MsPowerpoint_InitSlideLst();

            $s = intval($x1)-1;
            if (isset($this->OpenXmlSlideLst[$s])) {
                $this->TbsLoadSubFileAsTemplate($this->OpenXmlSlideLst[$s]['idx']);

                return true;
            } else {
                return $this->RaiseError("($Cmd) slide number $x1 is not found inside the Presentation.");
            }

        } elseif ($Cmd==OPENTBS_DELETE_COMMENTS) {

            // Default values
            $MainTags = false;
            $CommFiles = false;
            $CommTags = false;
            $Inner = false;

            if ($this->ExtType=='odf') {
                $MainTags = array('office:annotation', 'officeooo:annotation'); // officeooo:annotation is used in ODP Presentations
            } else {
                switch ($this->ExtEquiv) {
                case 'docx':
                    $MainTags = array('w:commentRangeStart', 'w:commentRangeEnd', 'w:commentReference');
                    $CommFiles = array('wordprocessingml.comments+xml');
                    $CommTags = array('w:comment');
                    $Inner = true;
                    break;
                case 'xlsx':
                    $CommFiles = array('spreadsheetml.comments+xml');
                    $CommTags = array('comment', 'author');
                    break;
                case 'pptx':
                    $CommFiles = array('presentationml.comments+xml');
                    $CommTags = array('p:cm');
                    break;
                default:
                    return 0;
                }
            }

            return $this->TbsDeleteComments($MainTags, $CommFiles, $CommTags, $Inner);

        } elseif ($Cmd==OPENTBS_CHANGE_PICTURE) {

            static $img_num = 0;

            $code = $x1;
            $file = $x2;
            $default = (is_null($x3)) ? 'current' : $x3;
            $adjust = (is_null($x4)) ? 'inside' : $x4;

            $img_num++;
            $name = 'OpenTBS_Change_Picture_'.$img_num;
            $tag = "[$name;ope=changepic;tagpos=inside;default=$default;adjust=$adjust]";

            $nbr = false;
            $TBS =& $this->TBS;
            $TBS->Source = str_replace($code, $tag, $TBS->Source, $nbr); // argument $nbr supported buy PHP >= 5
            if ($nbr!==0) $TBS->MergeField($name, $file);
            return $nbr;

        } elseif ($Cmd==OPENTBS_COUNT_SLIDES) {

            if ($this->ExtEquiv=='pptx') {
                $this->MsPowerpoint_InitSlideLst();

                return count($this->OpenXmlSlideLst);
            } elseif ($this->ExtEquiv=='odp') {
                return substr_count($TBS->Source, '</draw:page>');
            } else {
                return 0;
            }

        } elseif ($Cmd==OPENTBS_SEARCH_IN_SLIDES) {

            if ($this->ExtEquiv=='pptx') {
                $option = (is_null($x2)) ? OPENTBS_FIRST : $x2;
                $returnFirstFound = (($option & TBS_ALL)!=TBS_ALL);
                $find = $this->MsPowerpoint_SearchInSlides($x1, $returnFirstFound);
                if ($returnFirstFound) {
                    $slide = $find['key'];
                    if ( ($slide!==false) && (($option & TBS_GO)!=TBS_GO) ) $this->OnCommand(OPENTBS_SELECT_SLIDE, $slide);
                    return ($slide);
                } else {
                    $res = array();
                    foreach($find as $f) $res[] = $f['key'];

                    return $res;
                }
            } elseif ($this->ExtEquiv=='odp') {
                // Only for compatibility
                $p = instr($TBS->Source, $str);

                return ($p===false) ? false : 1;
            } else {
                return false;
            }

        }

    }

    // Initialize template information
    public function TbsInitArchive()
    {
        $TBS =& $this->TBS;

        $TBS->OtbsCurrFile = false;

        $this->TbsStoreLst = array();
        $this->TbsCurrIdx = false;
        $this->TbsNoField = array(); // idx of sub-file having no TBS fields
        $this->IdxToCheck = array(); // index of files to check
        $this->PrevVals = array(); // Previous values for 'mergecell' operator

        $this->ExtEquiv = false;
        $this->ExtType = false;

        $this->OtbsSheetSlidesDelete = array();
        $this->OtbsSheetSlidesVisible = array();

        $this->OpenDocCharts = false;
        $this->OpenDocManif = false;
        $this->OpenDoc_SheetSlides = false;
        $this->OpenDoc_Styles = false;

        $this->OpenXmlRid = false;
        $this->OpenXmlCTypes = false;
        $this->OpenXmlCharts = false;
        $this->OpenXmlSharedStr = false;
        $this->OpenXmlSlideLst = false;
        $this->MsExcel_Sheets = false;

        $this->Ext_PrepareInfo(); // Set extension information

    }

    /**
     * Load one or several sub-files of the archive as the current template.
     * If a sub-template is loaded for the first time, then automatic merges and clean-up are performed.
     * Return true if the file is correctly loaded.
     * @param $SubFileLst Can be an index or a name or a file, or an array of such values.
     */
    public function TbsLoadSubFileAsTemplate($SubFileLst)
    {
        if (!is_array($SubFileLst)) $SubFileLst = array($SubFileLst);

        $ok = true;
        $TBS = false;

        foreach ($SubFileLst as $SubFile) {

            $idx = $this->FileGetIdx($SubFile);
            if ($idx===false) {
                $ok = $this->RaiseError('Cannot load "'.$SubFile.'". The file is not found in the archive "'.$this->ArchFile.'".');
            } elseif ($idx!==$this->TbsCurrIdx) {
                // Save the current loaded subfile if any
                $this->TbsStorePark();
                // load the subfile
                if (!is_string($SubFile)) $SubFile = $this->TbsGetFileName($idx);
                $this->TbsStoreLoad($idx, $SubFile);
                if ($this->LastReadNotStored) {
                    if ($TBS===false) {
                        $this->TbsSwitchMode(true); // Configuration which prevents from other plug-ins when calling LoadTemplate()
                        $MergeAutoFields = $this->TbsMergeAutoFields();
                        $TBS =& $this->TBS;
                    }
                    if ($this->LastReadComp<=0) { // the contents is not compressed
                        if ($this->ExtInfo!==false) {
                            $i = $this->ExtInfo;
                            $e = $this->ExtEquiv;
                            if (isset($i['rpl_what'])) $TBS->Source = str_replace($i['rpl_what'], $i['rpl_with'], $TBS->Source); // auto replace strings in the loaded file
                            if (($e==='docx') && $TBS->OtbsClearMsWord) $this->MsWord_Clean($TBS->Source);
                            if (($e==='pptx') && $TBS->OtbsClearMsPowerpoint) $this->MsPowerpoint_Clean($TBS->Source);
                            if (($e==='xlsx') && $TBS->OtbsMsExcelConsistent) {
                                $this->MsExcel_DeleteFormulaResults($TBS->Source);
                                $this->MsExcel_ConvertToRelative($TBS->Source);
                            }
                        }
                        // apply default TBS behaviors on the uncompressed content: other plug-ins + [onload] fields
                        if ($MergeAutoFields) $TBS->LoadTemplate(null,'+');
                    }
                }
            }

        }

        if ($TBS!==false) $this->TbsSwitchMode(false); // Reactivate default configuration
        return $ok;

    }

    // Return true if automatic fields must be merged
    public function TbsMergeAutoFields()
    {
        return (($this->TBS->Render & OPENTBS_DEBUG_AVOIDAUTOFIELDS)!=OPENTBS_DEBUG_AVOIDAUTOFIELDS);
    }

    public function TbsSwitchMode($PluginMode)
    {
        $TBS = &$this->TBS;
        if ($PluginMode) {
            $this->_ModeSave = $TBS->_Mode;
            $TBS->_Mode++;    // deactivate TplVars[] reset and Charset reset.
            $TBS->Plugin(-4); // deactivate other plugins
        } else {
            // Reactivate default configuration
            $TBS->_Mode = $this->_ModeSave;
            $TBS->Plugin(-10); // reactivate other plugins
        }
    }

    // Save the last opened subfile into the store, and close the subfile
    public function TbsStorePark()
    {
        if ($this->TbsCurrIdx!==false) {
            $this->TbsStoreLst[$this->TbsCurrIdx] = array('src'=>$this->TBS->Source, 'onshow'=>true);
            $this->TBS->Source = '';
            $this->TbsCurrIdx = false;
        }
    }

    // Load a subfile from the store to be the current subfile
    public function TbsStoreLoad($idx, $file=false)
    {
        $this->TBS->Source = $this->TbsStoreGet($idx, false);
        $this->TbsCurrIdx = $idx;
        if ($file===false) $file = $this->TbsGetFileName($idx);
        $this->TBS->OtbsCurrFile = $file;
    }

    /**
     * Save a given source in the store.
     * $onshow=true means [onshow] are merged before the output.
     * If $onshow is null, then the 'onshow' option stays unchanged.
     */
    public function TbsStorePut($idx, $src, $onshow = null)
    {
        if ($idx===$this->TbsCurrIdx) {
            $this->TBS->Source = $src;
        } else {
            if (is_null($onshow)) {
                if (isset($this->TbsStoreLst[$idx])) {
                    $onshow = $this->TbsStoreLst[$idx]['onshow'];
                } else {
                    $onshow = false;
                }
            }
            $this->TbsStoreLst[$idx] = array('src'=>$src, 'onshow'=>$onshow);
        }
    }

    /**
     * Return a source from the current merging, the store, or the archive.
     * Take care that if the source it taken from the archive, then it is not saved in the store.
     * @param {integer}      $idx    The index of the file to read.
     * @param {string|false} $caller A text describing the calling function, for error reporting purpose. If caller=false it means TbsStoreLoad().
     */
    public function TbsStoreGet($idx, $caller)
    {
        $this->LastReadNotStored = false;
        if ($idx===$this->TbsCurrIdx) {
            return $this->TBS->Source;
        } elseif (isset($this->TbsStoreLst[$idx])) {
            $txt = $this->TbsStoreLst[$idx]['src'];
            if ($caller===false) $this->TbsStoreLst[$idx]['src'] = ''; // save memory space
            return $txt;
        } else {
            $this->LastReadNotStored = true;
            $txt = $this->FileRead($idx, true);
            if ($this->LastReadComp>0) {
                if ($caller===false) {
                    return $txt; // return the uncompressed contents
                } else {
                    return $this->RaiseError("(".$caller.") unable to uncompress '".$this->TbsGetFileName($idx)."'.");
                }
            } else {
                return $txt;
            }
        }
    }

    // Load a list of sub-files, but only if they have TBS fields.
    // This is in order to merge automatic fields in special XML sub-files that are not usually loaded manually.
    public function TbsQuickLoad($NameLst)
    {
        if (!is_array($NameLst)) $NameLst = array($NameLst);
        $nbr = 0;
        $TBS = &$this->TBS;

        foreach ($NameLst as $FileName) {
            $idx = $this->FileGetIdx($FileName);
            if ( (!isset($this->TbsStoreLst[$idx])) && (!isset($this->TbsNoField[$idx])) ) {
                $txt = $this->FileRead($idx, true);
                if (strpos($txt, $TBS->_ChrOpen)!==false) {
                    // merge
                    $nbr++;
                    if ($nbr==1) {
                        $MergeAutoFields = $this->TbsMergeAutoFields();
                        $SaveIdx = $this->TbsCurrIdx; // save the index of sub-file before the QuickLoad
                        $SaveName = $TBS->OtbsCurrFile;
                        $this->TbsSwitchMode(true);
                    }
                    $this->TbsStorePark(); // save the current file in the store
                    $TBS->Source = $txt;
                    unset($txt);
                    $TBS->OtbsCurrFile = $FileName; // may be needed for [onload] parameters
                    $this->TbsCurrIdx = $idx;
                    if ($MergeAutoFields) $TBS->LoadTemplate(null,'+');
                } else {
                    $this->TbsNoField[$idx] = true;
                }
            }
        }

        if ($nbr>0) {
            $this->TbsSwitchMode(false);
            $this->TbsStorePark(); // save the current file in the store
            $this->TbsStoreLoad($SaveIdx, $SaveName); // restore the sub-file as before the QuickLoad
        }

        return $nbr;

    }

    public function TbsGetFileName($idx)
    {
        if (isset($this->CdFileLst[$idx])) {
            return $this->CdFileLst[$idx]['v_name'];
        } else {
            return '(id='.$idx.')';
        }
    }

    /**
     * Display the header of the debug mode (only once)
     */
    public function TbsDebug_Init(&$nl, &$sep, &$bull, $type)
    {
        static $DebugInit = false;

        if ($DebugInit) return;
        $DebugInit = true;

        $nl = "\n";
        $sep = str_repeat('-',30);
        $bull = $nl.'  - ';

        if (!headers_sent()) header('Content-Type: text/plain; charset="UTF-8"');

        echo "* OPENTBS DEBUG MODE: if the star, (*) on the left before the word OPENTBS, is not the very first character of this page, then your
merged Document will be corrupted when you use the OPENTBS_DOWNLOAD option. If there is a PHP error message, then you have to fix it.
If they are blank spaces, line beaks, or other unexpected characters, then you have to check your code in order to avoid them.";
        echo $nl;
        echo $nl.$sep.$nl.'INFORMATION'.$nl.$sep;
        echo $nl.'* Debug command: '.$type;
        echo $nl.'* OpenTBS version: '.$this->Version;
        echo $nl.'* TinyButStrong version: '.$this->TBS->Version;
        echo $nl.'* PHP version: '.PHP_VERSION;
        echo $nl.'* Opened document: '.$this->ArchFile;
        echo $nl.'* Activated features for document type: '.(($this->ExtEquiv===false) ? '(none)' : $this->ExtType.'/'.$this->ExtEquiv);

    }

    public function TbsDebug_Info($Exit)
    {
        $this->TbsDebug_Init($nl, $sep, $bull, 'OPENTBS_DEBUG_INFO');

        switch ($this->ExtEquiv) {
        case 'docx': $this->MsWord_DocDebug($nl, $sep, $bull); break;
        case 'xlsx': $this->MsExcel_SheetDebug($nl, $sep, $bull); break;
        case 'pptx': $this->MsPowerpoint_SlideDebug($nl, $sep, $bull); break;
        case 'ods' : $this->OpenDoc_SheetSlides_Debug(true, $nl, $sep, $bull); break;
        case 'odp' : $this->OpenDoc_SheetSlides_Debug(false, $nl, $sep, $bull); break;
        }

        switch ($this->ExtType) {
        case 'openxml': $this->OpenXML_ChartDebug($nl, $sep, $bull); break;
        case 'odf':     $this->OpenDoc_ChartDebug($nl, $sep, $bull); break;
        }

        if ($Exit) exit;

    }

    public function TbsDebug_Merge($XmlFormat = true, $Current)
    {
    // display modified and added files

        $this->TbsDebug_Init($nl, $sep, $bull, ($Current ? 'OPENTBS_DEBUG_XML_CURRENT' :'OPENTBS_DEBUG_XML_SHOW'));

        // scann files for collecting information
        $mod_lst = ''; // id of modified files
        $del_lst = ''; // id of deleted  files
        $add_lst = ''; // id of added    files

        // files marked as replaced in TbsZip
        $idx_lst = array_keys($this->ReplInfo);
        foreach ($idx_lst as $idx) {
            $name = $this->TbsGetFileName($idx);
            if ($this->ReplInfo[$idx]===false) {
                $del_lst .= $bull.$name;
            } else {
                $mod_lst .= $bull.$name;
            }
        }

        // files marked as modified in the Park
        $idx_lst = array_keys($this->TbsStoreLst);
        foreach ($idx_lst as $idx) {
            if (!isset($this->ReplInfo[$idx])) {
                $mod_lst .= $bull.$this->TbsGetFileName($idx);
            }
        }

        // files marked as added in TbsZip
        $idx_lst = array_keys($this->AddInfo);
        foreach ($idx_lst as $idx) {
            $name = $this->AddInfo[$idx]['name'];
            $add_lst .= $bull.$name;
        }

        if ($mod_lst==='')  $mod_lst = ' none';
        if ($del_lst==='')  $del_lst = ' none';
        if ($add_lst==='')  $add_lst = ' none';

        echo $nl.'* Deleted files in the archive:'.$del_lst;
        echo $nl.'* Added files in the archive:'.$add_lst;
        echo $nl.'* Modified files in the archive:'.$mod_lst;
        echo $nl;

        // display contents merged with OpenTBS
        foreach ($this->DebugLst as $name=>$src) {
            $x = trim($src);
            $info = '';
            $xml = ((strlen($x)>0) && $x[0]==='<');
            if ($XmlFormat && $xml) {
                $info = ' (XML reformated for debuging only)';
                $src = $this->XmlFormat($src);
            }
            echo $nl.$sep;
            echo $nl.'File merged with OpenTBS'.$info.': '.$name;
            echo $nl.$sep;
            echo $nl.$src;
        }

    }

    public function ConvXmlOnly($Txt, $ConvBr)
    {
    // Used by TBS to convert special chars and new lines.
        $x = htmlspecialchars($Txt);
        if ($ConvBr) $this->ConvBr($x);
        return $x;
    }

    public function ConvXmlUtf8($Txt, $ConvBr)
    {
    // Used by TBS to convert special chars and new lines.
        $x = htmlspecialchars(utf8_encode($Txt));
        if ($ConvBr) $this->ConvBr($x);
        return $x;
    }

    public function ConvBr(&$x)
    {
        $z = $this->TBS->OtbsConvBr;
        if ($z===false) return;
        $x = nl2br($x); // Convert any type of line break
        $x = str_replace("\r", '' ,$x);
        $x = str_replace("\n", '' ,$x);
        $x = str_replace('<br />',$z ,$x);

    }

    public function XmlFormat($Txt)
    {
    // format an XML source the be nicely aligned

        // delete line breaks
        $Txt = str_replace("\r",'',$Txt);
        $Txt = str_replace("\n",'',$Txt);

        // init values
        $p = 0;
        $lev = 0;
        $Res = '';

        $to = true;
        while ($to!==false) {
            $to = strpos($Txt,'<',$p);
            if ($to!==false) {
                $tc = strpos($Txt,'>',$to);
                if ($to===false) {
                    $to = false; // anomaly
                } else {
                    // get text between the tags
                    $x = trim(substr($Txt, $p, $to-$p),' ');
                    if ($x!=='') $Res .= "\n".str_repeat(' ',max($lev,0)).$x;
                    // get the tag
                    $x = substr($Txt, $to, $tc-$to+1);
                    if ($Txt[$to+1]==='/') $lev--;
                    $Res .= "\n".str_repeat(' ',max($lev,0)).$x;
                    // change the level
                    if (($Txt[$to+1]!=='?') && ($Txt[$to+1]!=='/') && ($Txt[$tc-1]!=='/')) $lev++;
                    // next position
                    $p = $tc + 1;
                }
            }
        }

        $Res = substr($Res, 1); // delete the first line break
        if ($p<strlen($Txt)) $Res .= trim(substr($Txt, $p), ' '); // complete the end
        return $Res;

    }

    public function RaiseError($Msg, $NoErrMsg=false)
    {
        // Overwrite the parent RaiseError() method.
        $exit = (!$this->TBS->NoErr);
        if ($exit) $Msg .= ' The process is ending, unless you set NoErr property to true.';
        $this->TBS->meth_Misc_Alert('OpenTBS Plugin', $Msg, $NoErrMsg);
        if ($exit) {
            if ($this->DebugLst!==false) {
                if ($this->TbsCurrIdx!==false) $this->DebugLst[$this->TbsGetFileName($this->TbsCurrIdx)] = $this->TBS->Source;
                $this->TbsDebug_Merge(true, false);
            }
            exit;
        }

        return false;
    }

    public function TbsPicFound($Txt, &$Loc, $IsCaching)
    {
    // Found the relevent attribute for the image source, and then add parameter 'att' to the TBS locator.

        $att = false;
        $backward = true;

        if (isset($Loc->PrmLst['tagpos'])) {
            $s = $Loc->PrmLst['tagpos'];
            if ($s=='before') {
                $backward = false;
            } elseif ($s=='inside') {
                if ($this->ExtType=='openxml') $backward = false;
            }
        }

        // In caching mode, the tag will be moved before the merging by parameter att. Thus Dim info must considerate the tbs tag as length zero.
        $shift = ($IsCaching && (!$backward) ) ? ($Loc->PosEnd-$Loc->PosBeg+1) : 0;

        if ($this->ExtType==='odf') {
            $att = 'draw:image#xlink:href';
            if (isset($Loc->PrmLst['adjust'])) $Loc->otbsDim = $this->TbsPicGetDim_ODF($Txt, $Loc->PosBeg, !$backward, $shift);
        } elseif ($this->ExtType==='openxml') {
            $att = $this->OpenXML_FirstPicAtt($Txt, $Loc->PosBeg, $backward, $shift);
            if ($att===false) return $this->RaiseError('Parameter ope=changepic used in the field ['.$Loc->FullName.'] has failed to found the picture.');
            if (isset($Loc->PrmLst['adjust'])) {
                if (strpos($att,'v:imagedata')!==false) {
                    $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_vml($Txt, $Loc->PosBeg, !$backward, $shift);
                } else {
                    $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_dml($Txt, $Loc->PosBeg, !$backward, $shift);
                }
            }
        } else {
            return $this->RaiseError('Parameter ope=changepic used in the field ['.$Loc->FullName.'] is not supported with the current document type.');
        }

        if ($att!==false) {
            if (isset($Loc->PrmLst['att'])) {
                return $this->RaiseError('Parameter att is used with parameter ope=changepic in the field ['.$Loc->FullName.']. changepic will be ignored');
            } else {
                $prefix = ($backward) ? '' : '+';
                $Loc->PrmLst['att'] = $prefix.$att;
            }
        }

        return true;

    }

    public function TbsPicAdjust(&$Txt, &$Loc, &$File)
    {
        // Adjust the dimensions if the picture
        $fDim = @getimagesize($File); // file dimensions
        if (!is_array($fDim)) return;
        $w = (float) $fDim[0];
        $h = (float) $fDim[1];
        $r = ($w/$h);
        $delta = 0;
        $adjust = $Loc->PrmLst['adjust'];
        if ( (!is_string($adjust)) || ($adjust=='') ) $adjust = 'inside';
        if (strpos($adjust, '%')!==false) {
            $adjust_coef = floatval(str_replace('%','',$adjust))/100.0;
            $adjust = '%';
        }
        foreach ($Loc->otbsDim as $tDim) { // template dimensions. They must be sorted in reverse order of location
            if ($tDim!==false) {
                // find what dimensions should be edited
                if ($adjust=='%') {
                    if ($tDim['wb']>$tDim['hb']) { // the last attribute must be processed first
                        $edit_lst = array('w' =>  $adjust_coef * $w, 'h' =>  $adjust_coef * $h );
                    } else {
                        $edit_lst = array('h' =>  $adjust_coef * $h, 'w' =>  $adjust_coef * $w );
                    }
                } elseif ($adjust=='samewidth') {
                    $edit_lst = array('h' => $tDim['wv'] * $h / $w );
                } elseif ($adjust=='sameheight') {
                    $edit_lst = array('w' =>  $r * $tDim['hv'] );
                } else { // default value
                    if ($tDim['r']>=$r) {
                        $edit_lst = array('w' =>  $r * $tDim['hv'] ); // adjust width
                    } else {
                        $edit_lst = array('h' => $tDim['wv'] * $h / $w ); // adjust height
                    }
                }
                // edit dimensions
                foreach ($edit_lst as $what=>$new) {
                    $beg  = $tDim[$what.'b'];
                    $len  = $tDim[$what.'l'];
                    $unit = $tDim[$what.'u'];
                    if ($adjust=='%') {
                        if ($tDim['cpt']!==false) $new = $new * $tDim['cpt']; // apply the coef to Point conversion if any
                        if ($unit!=='') { // force unit to pt, if units are allowed
                            $unit = 'pt';
                        }
                    }
                    $new = $new + $tDim[$what.'o']; // add the offset (xlsx only)
                    $new = number_format($new, $tDim['dec'], '.', '').$unit;
                    $Txt = substr_replace($Txt, $new, $beg, $len);
                    if ($Loc->PosBeg>$beg) $delta = $delta + strlen($new) - $len;
                }
            }
        }
        if ($delta<>0) {
            $Loc->PosBeg = $Loc->PosBeg + $delta;
            $Loc->PosEnd = $Loc->PosEnd + $delta;
        }
    }

    public function TbsPicGetDim_ODF($Txt, $Pos, $Forward, $Shift)
    {
    // Found the attributes for the image dimensions, in an ODF file
        // unit (can be: mm, cm, in, pi, pt)
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $Shift, 'draw:frame', 'svg:width="', 'svg:height="', 3, false, false);

        return array($dim);
    }

    public function TbsPicGetDim_OpenXML_vml($Txt, $Pos, $Forward, $Shift)
    {
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $Shift, 'v:shape', 'width:', 'height:', 2, false, false);

        return array($dim);
    }

    public function TbsPicGetDim_OpenXML_dml($Txt, $Pos, $Forward, $Shift)
    {
        $dim_shape = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $Shift, 'wp:extent', 'cx="', 'cy="', 0, 12700, false);
        $dim_inner = $this->TbsPicGetDim_Any($Txt, $Pos, $Forward, $Shift, 'a:ext'    , 'cx="', 'cy="', 0, 12700, 'uri="');
        if ( ($dim_inner!==false) && ($dim_inner['wb']<$dim_shape['wb']) ) $dim_inner = false; // <a:ext> isoptional but must always be after the corresponding <wp:extent>, otherwise it may be the <a:ext> of another picture
        $dim_drawing = $this->TbsPicGetDim_Drawings($Txt, $Pos, $dim_inner);
        //var_export($dim_drawing); exit;
        return array($dim_inner, $dim_shape, $dim_drawing); // dims must be sorted in reverse order of location
    }

    public function TbsPicGetDim_Any($Txt, $Pos, $Forward, $Shift, $Element, $AttW, $AttH, $AllowedDec, $CoefToPt, $IgnoreIfAtt)
    {
    // Found the attributes for the image dimensions, in an ODF file

        while (true) {

            $p = clsTinyButStrong::f_Xml_FindTagStart($Txt, $Element, true, $Pos, $Forward, true);
            if ($p===false) return false;

            $pe = strpos($Txt, '>', $p);
            if ($pe===false) return false;

            $x = substr($Txt, $p, $pe -$p);

            if ( ($IgnoreIfAtt===false) || (strpos($x, $IgnoreIfAtt)===false) ) {

                $att_lst = array('w'=>$AttW, 'h'=>$AttH);
                $res_lst = array();

                foreach ($att_lst as $i=>$att) {
                        $l = strlen($att);
                        $b = strpos($x, $att);
                        if ($b===false) return false;
                        $b = $b + $l;
                        $e = strpos($x, '"', $b);
                        $e2 = strpos($x, ';', $b); // in case of VML format, width and height are styles separted by ;
                        if ($e2!==false) $e = min($e, $e2);
                        if ($e===false) return false;
                        $lt = $e - $b;
                        $t = substr($x, $b, $lt);
                        $pu = $lt; // unit first char
                        while ( ($pu>1) && (!is_numeric($t[$pu-1])) ) $pu--;
                        $u = ($pu>=$lt) ? '' : substr($t, $pu);
                        $v = floatval(substr($t, 0, $pu));
                        $res_lst[$i.'b'] = ($p+$b-$Shift); // start
                        $res_lst[$i.'l'] = $lt; // length of the text
                        $res_lst[$i.'u'] = $u; // unit
                        $res_lst[$i.'v'] = $v; // value
                        $res_lst[$i.'t'] = $t; // text
                        $res_lst[$i.'o'] = 0; // offset
                }

                $res_lst['r'] = ($res_lst['hv']==0) ? 0.0 : $res_lst['wv']/$res_lst['hv']; // ratio W/H
                $res_lst['dec'] = $AllowedDec; // save the allowed decimal for this attribute
                $res_lst['cpt'] = $CoefToPt;

                return $res_lst;

            } else {

                // Next try
                $Pos = $p + (($Forward) ? +1 : -1);

            }

        }

    }

    // Get Dim in a OpenXML Drawing (pictures in an XLSX)
    public function TbsPicGetDim_Drawings($Txt, $Pos, $dim_inner)
    {
        if ($dim_inner===false) return false;
        if (strpos($this->TBS->OtbsCurrFile, 'xl/drawings/')!==0) return false;

        $cx = $dim_inner['wv'];
        $cy = $dim_inner['hv'];

        $loc = clsTbsXmlLoc::FindStartTag($Txt, 'xdr:twoCellAnchor', $Pos, false);
        if ($loc===false) return false;
        $loc = clsTbsXmlLoc::FindStartTag($Txt, 'xdr:to', $loc->PosBeg, true);
        if ($loc===false) return false;
        $p = $loc->PosBeg;

        $res = array();

        $loc = clsTbsXmlLoc::FindElement($Txt, 'xdr:colOff', $p, true);
        if ($loc===false) return false;
        $res['wb'] = $loc->GetInnerStart();
        $res['wl'] = $loc->GetInnerLen();
        $res['wu'] = '';
        $res['wv'] = $cx;
        $res['wt'] = $loc->GetInnerSrc();
        $res['wo'] = intval($res['wt']) - $cx;

        $loc = clsTbsXmlLoc::FindElement($Txt, 'xdr:rowOff', $p, true);
        if ($loc===false) return false;
        $res['hb'] = $loc->GetInnerStart();
        $res['hl'] = $loc->GetInnerLen();
        $res['hu'] = '';
        $res['hv'] = $cy;
        $res['ht'] = $loc->GetInnerSrc();
        $res['ho'] = intval($res['ht']) - $cy;

        $res['r'] = ($res['hv']==0) ? 0.0 : $res['wv']/$res['hv']; // ratio W/H;
        $res['dec'] = 0;
        $res['cpt'] = 12700;

        return $res;

    }

    public function TbsPicAdd(&$Value, &$PrmLst, &$Txt, &$Loc, $Prm)
    {
    // Add a picture inside the archive, use parameters 'from' and 'as'.
    // Argument $Prm is only used for error messages.

        $TBS = &$this->TBS;

        // set the path where files should be taken
        if (isset($PrmLst['from'])) {
            if (!isset($PrmLst['pic_prepared'])) $TBS->meth_Merge_AutoVar($PrmLst['from'],true); // merge automatic TBS fields in the path
            $FullPath = str_replace($TBS->_ChrVal,$Value,$PrmLst['from']); // merge [val] fields in the path
        } else {
            $FullPath = $Value;
        }
        if ( (!isset($PrmLst['pic_prepared'])) && isset($PrmLst['default']) ) $TBS->meth_Merge_AutoVar($PrmLst['default'],true); // merge automatic TBS fields in the path

        $ok = true; // true if the picture file is actually inserted and ready to be changed

        // check if the picture exists, and eventually use the default picture
        if (!file_exists($FullPath)) {
            if (isset($PrmLst['default'])) {
                $x = $PrmLst['default'];
                if ($x==='current') {
                    $ok = false;
                } elseif (file_exists($x)) {
                    $FullPath = $x;
                } else {
                    $ok = $this->RaiseError('The default picture "'.$x.'" defined by parameter "default" of the field ['.$Loc->FullName.'] is not found.');
                }
            } else {
                $ok = false;
            }
        }

        // set the name of the internal file
        if (isset($PrmLst['as'])) {
            if (!isset($PrmLst['pic_prepared'])) $TBS->meth_Merge_AutoVar($PrmLst['as'],true); // merge automatic TBS fields in the path
            $InternalPath = str_replace($TBS->_ChrVal,$Value,$PrmLst['as']); // merge [val] fields in the path
        } else {
            $InternalPath = basename($FullPath);
        }

        if ($ok) {

            // the value of the current TBS field becomes the full internal path
            if (isset($this->ExtInfo['pic_path'])) $InternalPath = $this->ExtInfo['pic_path'].$InternalPath;

            // actually add the picture inside the archive
            if ($this->FileGetIdxAdd($InternalPath)===false) $this->FileAdd($InternalPath, $FullPath, TBSZIP_FILE, true);

            // preparation for others file in the archive
            $Rid = false;
            if ($this->ExtType==='odf') {
                // OpenOffice document
                $this->OpenDoc_ManifestChange($InternalPath,'');
            } elseif ($this->ExtType==='openxml') {
                // Microsoft Office document
                $this->OpenXML_CTypesPrepareExt($InternalPath, '');
                $BackNbr = max(substr_count($TBS->OtbsCurrFile, '/') - 1, 0); // docx=>"media/img.png", xlsx & pptx=>"../media/img.png"
                $TargetDir = str_repeat('../', $BackNbr).'media/';
                $FileName = basename($InternalPath);
                $Rid = $this->OpenXML_Rels_AddNew($TBS->OtbsCurrFile, $TargetDir, $FileName);
            }

            // change the value of the field for the merging process
            if ($Rid===false) {
                $Value = $InternalPath;
            } else {
                $Value = $Rid; // the Rid is used instead of the file name for the merging
            }

        }

        // Change the dimensions of the picture
        if (isset($Loc->otbsDim)) {
            if (isset($Loc->AttForward)) { // the field has been already moved by parameter att
                if (!isset($Loc->otbsRealBeg)) { // save the real position of the field
                    $Loc->otbsRealBeg = $Loc->PosBeg;
                    $Loc->otbsRealEnd = $Loc->PosEnd;
                } else { // restore the real position of the field
                    $Loc->PosBeg = $Loc->otbsRealBeg;
                    $Loc->PosEnd = $Loc->otbsRealEnd;
                }
            }
            if ($ok) $this->TbsPicAdjust($Txt, $Loc, $FullPath);
        }

        // Unchanged value (must be done after redim)
        if (!$ok) {
            if (isset($PrmLst['att'])) {
                // can happen when using MergeField()
                unset($PrmLst['att']);
                $Value = '';
            } else {
                // parameter att already applied during Field caching
                $Value = substr($Txt, $Loc->PosBeg, $Loc->PosEnd - $Loc->PosBeg + 1);
            }
        }

        $PrmLst['pic_prepared'] = true; // mark the locator as Picture prepared

        return $ok;

    }

    /**
     * Search a string in a list if several sub-file in the archive.
     * @param $files An associated array of sub-files to scann. Structure: $key => IdxOrName
     * @param $str   The string to search.
     * @param $returnFirstFind  true to return only the first record fund.
     * @return a single record or a recordset structured like: array('key'=>, 'idx'=>, 'src'=>, 'pos'=>, 'curr'=>)
     */
    public function TbsSearchInFiles($files, $str, $returnFirstFound = true)
    {
        $keys_ok = array();

        // transform the list of files into a list of available idx
        $keys_todo = array();
        $idx_keys = array();
        foreach ($files as $k=>$f) {
            $idx = $this->FileGetIdx($f);
            if ($idx!==false) {
                $keys_todo[$k] = $idx;
                $idx_keys[$idx] = $k;
            }
        }

        // Search in the current sub-file
        if ( ($this->TbsCurrIdx!==false) && isset($idx_keys[$this->TbsCurrIdx]) ) {
            $key = $idx_keys[$this->TbsCurrIdx];
            $p = strpos($this->TBS->Source, $str);
            if ($p!==false) {
                $keys_ok[] = array('key' => $key, 'idx' => $this->TbsCurrIdx, 'src' => &$this->TBS->Source, 'pos' => $p, 'curr'=>true);
                if ($returnFirstFound) return $keys_ok[0];
            }
            unset($keys_todo[$key]);
        }

        // Search in the store
        foreach ($this->TbsStoreLst as $idx => $s) {
            if ( ($idx!==$this->TbsCurrIdx) && isset($idx_keys[$idx]) ) {
                $key = $idx_keys[$idx];
                $p = strpos($s['src'], $str);
                if ($p!==false) {
                    $keys_ok[] = array('key' => $key, 'idx' => $idx, 'src' => &$s['src'], 'pos' => $p, 'curr'=>false);
                    if ($returnFirstFound) return $keys_ok[0];
                }
                unset($keys_todo[$key]);
            }
        }

        // Search in other sub-files (never opened)
        foreach ($keys_todo as $key => $idx) {
            $txt = $this->FileRead($idx);
            $p = strpos($txt, $str);
            if ($p!==false) {
                $keys_ok[] = array('key' => $key, 'idx' => $idx, 'src' => $txt, 'pos' => $p, 'curr'=>false);
                if ($returnFirstFound) return $keys_ok[0];
            }
        }

        if ($returnFirstFound) {
            return  array('key'=>false, 'idx'=>false, 'src'=>false, 'pos'=>false, 'curr'=>false);
        } else {
            return $keys_ok;
        }

    }

    // Check after the sheet process
    public function TbsSheetCheck()
    {
        if (count($this->OtbsSheetSlidesDelete)>0) $this->RaiseError("Unable to delete the following sheets because they are not found in the workbook: ".(str_replace(array('i:','n:'),'',implode(', ',$this->OtbsSheetSlidesDelete))).'.');
        if (count($this->OtbsSheetSlidesVisible)>0) $this->RaiseError("Unable to change visibility of the following sheets because they are not found in the workbook: ".(str_replace(array('i:','n:'),'',implode(', ',array_keys($this->OtbsSheetSlidesVisible)))).'.');
    }

    public function TbsDeleteComments($MainTags, $CommFiles, $CommTags, $Inner)
    {
        $nbr = 0;

        // Retrieve the Comment sub-file (OpenXML only)
        if ($CommFiles!==false) {
            $Files = $this->OpenXML_MapGetFiles($CommFiles);
            foreach ($Files as $file) {
                $idx = $this->FileGetIdx($file);
                if ($idx!==false) {
                    // Delete inner text of the comments to be sure that contents is deleted
                    // we only empty the comment elements in case some comments are referenced in other special part of the document
                    $Txt = $this->TbsStoreGet($idx, "Delete Comments");
                    $nbr = $nbr + $this->XML_DeleteElements($Txt, $CommTags, $Inner);
                    $this->TbsStorePut($idx, $Txt);
                }
            }
        }

        // Retrieve the Main sub-file
        if ($MainTags!==false) {
            $idx = $this->FileGetIdx($this->ExtInfo['main']);
            if ($idx===false) return false;
            // Delete Comment locators
            $Txt = $this->TbsStoreGet($idx, "Delete Comments");
            $nbr2 = $this->XML_DeleteElements($Txt, $MainTags);
            $this->TbsStorePut($idx, $Txt);
            if ($CommFiles===false) $nbr = $nbr2;
        }

        return $nbr;

    }

    /**
     * Replace var fields in a Parameter of a block.
     * @param  string $PrmVal The parameter value.
     * @param  string $FldVal The value of the field that holds the value.
     * @return string The merged value of the parameter.
     */
    public function TbsMergeVarFields($PrmVal, $FldVal)
    {
        $this->TBS->meth_Merge_AutoVar($PrmVal, true);
        $PrmVal = str_replace($this->TBS->_ChrVal, $FldVal, $PrmVal);

        return $PrmVal;
    }

    public function TbsDeleteColumns(&$Txt, $Value, $PrmLst, $PosBeg, $PosEnd)
    {
        $ext = $this->ExtEquiv;
        if ($ext==='docx') {
            $el_table = 'w:tbl';
            $el_delete = array(
                array('p'=>'w:tblGrid', 'c'=>'w:gridCol', 's'=>false),
                array('p'=>'w:tr', 'c'=>'w:tc', 's'=>false),
            );
        } elseif ($ext==='odt') {
            $el_table = 'table:table';
            $el_delete = array(
                array('p'=>false, 'c'=>'table:table-column', 's'=>'table:number-columns-repeated'),
                array('p'=>'table:table-row', 'c'=>'table:table-cell', 's'=>false),
            );
        } else {
            return false;
        }

        if (is_array($Value)) $Value = implode(',', $Value);

        // Retreive the list of columns id to delete
        $col_lst = $this->TbsMergeVarFields($PrmLst['colnum'], $Value);
        $col_lst = str_replace(' ', '', $col_lst);
        if ( ($col_lst=='') || ($col_lst=='0') ) return false; // there is nothing to do
        $col_lst = explode(',', $col_lst);
        $col_nbr = count($col_lst);
        for ($c=0; $c<$col_nbr; $c++) $col_lst[$c] = intval($col_lst[$c]); // Conversion into numerical

        // Add columns by shifting
        if (isset($PrmLst['colshift'])) {
            $col_shift = intval($this->TbsMergeVarFields($PrmLst['colshift'], $Value));
            if ($col_shift<>0) {
                $step = ($col_shift>0) ? -1 : +1;
                for ($s = $col_shift; $s<>0; $s = $s + $step) {
                    for ($c=0; $c<$col_nbr; $c++) $col_lst[] = $col_lst[$c] + $s;
                }
            }
        }

        // prepare column info
        $col_lst = array_unique($col_lst, SORT_NUMERIC); // Delete duplicated columns
        sort($col_lst, SORT_NUMERIC); // Sort colmun id in order
        $col_max = $col_lst[(count($col_lst)-1)]; // Last column to delete

        // Delete the TBS tag
        $Txt = substr_replace($Txt, '', $PosBeg, $PosEnd - $PosBeg + 1);

        // Look for the source of the table
        $Loc = clsTbsXmlLoc::FindElement($Txt, $el_table, $PosBeg, false);
        if ($Loc===false) return false;

        $Src = $Loc->GetSrc();

        foreach ($el_delete as $info) {
            if ($info['p']===false) {
                $this->XML_DeleteColumnElements($Src, $info['c'], $info['s'], $col_lst, $col_max);
            } else {
                $ParentPos = 0;
                while ($ParentLoc = clsTbsXmlLoc::FindElement($Src, $info['p'], $ParentPos, true)) {
                    $ParentSrc = $ParentLoc->GetSrc();
                    $ModifNbr = $this->XML_DeleteColumnElements($ParentSrc, $info['c'], $info['s'], $col_lst, $col_max);
                    if ($ModifNbr>0) $ParentLoc->ReplaceSrc($ParentSrc);
                    $ParentPos = $ParentLoc->PosEnd + 1;
                }
            }
        }

        $Loc->ReplaceSrc($Src);

    }

    /**
     * Delete or Display a Sheet or a Slide according to its numbre or its name
     * @param $id_or_name Id or Name of the Sheet/Slide
     * @param $ok         true to Keep or Display, false to Delete or Hide
     * @param $delete     true to Delete/Keep, false to Display/Hide
     */
    public function TbsSheetSlide_DeleteDisplay($id_or_name, $ok, $delete)
    {
        if (is_null($ok)) $ok = true; // default value

        $ext = $this->ExtEquiv;

        $ok = (boolean) $ok;
        if (!is_array($id_or_name)) $id_or_name = array($id_or_name);

        foreach ($id_or_name as $item=>$action) {
            if (!is_bool($action)) {
                $item = $action;
                $action = $ok;
            }
            $item_ref = (is_string($item)) ? 'n:'.htmlspecialchars($item) : 'i:'.$item; // help to make the difference beetween id and name
            if ($delete) {
                if ($ok) {
                    $this->OtbsSheetSlidesDelete[$item_ref] = $item;
                } else {
                    unset($this->OtbsSheetSlidesVisible[$item_ref]);
                }
            } else {
                $this->OtbsSheetSlidesVisible[$item_ref] = $ok;
            }
        }

    }

    /**
     * Prepare the locator for merging cells.
     */
    public function TbsPrepareMergeCell(&$Txt, &$Loc)
    {
        if ($this->ExtEquiv=='docx') {
            // Move the locator just inside the <w:tcPr> element.
            // See OnOperation() for other process
            $xml = clsTbsXmlLoc::FindStartTag($Txt, 'w:tcPr', $Loc->PosBeg, false);
            if ($xml) {
                $Txt = substr_replace($Txt, '', $Loc->PosBeg, $Loc->PosEnd - $Loc->PosBeg + 1);
                $Loc->PosBeg = $xml->PosEnd+1;
                $Loc->PosEnd = $xml->PosEnd;
                $this->PrevVals[$Loc->FullName] = ''; // the previous value is saved in property because they can be several sections, and thus several Loc for the same column.
                //$Loc->Prms['strconv']='no'; // should work
                $Loc->ConvStr=false;
            }
        }
    }

    /**
     * Actualize property ExtInfo (Extension Info).
     * ExtInfo will be an array with keys 'load', 'br', 'ctype' and 'pic_path'. Keys 'rpl_what' and 'rpl_with' are optional.
     *  load:     files in the archive to be automatically loaded by OpenTBS when the archive is loaded. Separate files with comma ';'.
     *  br:       string that replace break-lines in the values merged by TBS, set to false if no conversion.
     *  frm:      format of the file ('odf' or 'openxml'), for now it is used only to activate a special feature for openxml files
     *  ctype:    (optional) the Content-Type header name that should be use for HTTP download. Omit or set to '' if not specified.
     *  pic_path: (optional) the folder nale in the archive where to place pictures
     *  rpl_what: (optional) string to replace automatically in the files when they are loaded. Can be a string or an array.
     *  rpl_with: (optional) to be used with 'rpl_what',  Can be a string or an array.
     * User can define his own Extension Information, they are taken in acount if saved int the global variable $_OPENTBS_AutoExt.
     */
    public function Ext_PrepareInfo($Ext=false)
    {
        $this->ExtEquiv = false;
        $this->ExtType = false;

        if ($Ext===false) {
            // Get the extension of the current archive
            if ($this->ArchIsStream) {
                $Ext = '';
            } else {
                $Ext = basename($this->ArchFile);
                $p = strrpos($Ext, '.');
                $Ext = ($p===false) ? '' : strtolower(substr($Ext, $p + 1));
            }
            $Frm = $this->Ext_DeductFormat($Ext, true);
            // Rename the name of the phantom file if it is a stream
            if ( $this->ArchIsStream && (strlen($Ext)>2) ) $this->ArchFile = str_replace('.zip', '.'.$Ext, $this->ArchFile);
        } else {
            // The extension is forced
            $Frm = $this->Ext_DeductFormat($Ext, false);
        }

        $TBS = &$this->TBS;

        $i = false;
        $block_alias = false;

        if (isset($GLOBAL['_OPENTBS_AutoExt'][$Ext])) {
            // User defined information
            $i = $GLOBAL['_OPENTBS_AutoExt'][$Ext];
            if (isset($i['equiv'])) $this->ExtEquiv = $i['equiv'];
            if (isset($i['frm'])) $this->ExtType = $i['frm'];
        } elseif ($Frm==='odf') {
            // OpenOffice & LibreOffice documents
            $i = array('main' => 'content.xml', 'br' => '<text:line-break/>', 'ctype' => 'application/vnd.oasis.opendocument.', 'pic_path' => 'Pictures/', 'rpl_what' => '&apos;', 'rpl_with' => '\'');
            if ($this->FileExists('styles.xml')) $i['load'] = array('styles.xml'); // styles.xml may contain header/footer contents
            if ($Ext==='odf') $i['br'] = false;
            if ($Ext==='odm') $this->ExtEquiv = 'odt';
            $this->ExtType = 'odf';
            $ctype = array('t' => 'text', 's' => 'spreadsheet', 'g' => 'graphics', 'f' => 'formula', 'p' => 'presentation', 'm' => 'text-master');
            $i['ctype'] .= $ctype[($Ext[2])];
            $i['pic_ext'] = array('png' => 'png', 'bmp' => 'bmp', 'gif' => 'gif', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'jpe' => 'jpeg', 'jfif' => 'jpeg', 'tif' => 'tiff', 'tiff' => 'tiff');
            $block_alias = array(
                'tbs:p' => 'text:p',              // ODT+ODP
                'tbs:title' => 'text:h',          // ODT+ODP
                'tbs:section' => 'text:section',  // ODT
                'tbs:table' => 'table:table',     // ODT (sheet for ODS)
                'tbs:row' => 'table:table-row',   // ODT+ODS
                'tbs:cell' => 'table:table-cell', // ODT+ODS
                'tbs:comment' => 'office:annotation',
                'tbs:page' => array(&$this, 'OpenDoc_GetPage'), // ODT
                'tbs:slide' => 'draw:page',       // ODP
                'tbs:sheet' => 'table:table',     // ODS (table for ODT)
                'tbs:draw' => array(&$this, 'OpenDoc_GetDraw'),
                'tbs:drawgroup' => 'draw:g',
                'tbs:drawitem' => array(&$this, 'OpenDoc_GetDraw'),
                'tbs:listitem' => 'text:list-item', // ODT+ODP
            );
        } elseif ($Frm==='openxml') {
            // Microsoft Office documents
            $this->OpenXML_MapInit();
            if ($TBS->OtbsConvertApostrophes) {
                $x = array(chr(226) . chr(128) . chr(152), chr(226) . chr(128) . chr(153));
            } else {
                $x = null;
            }
            $ctype = 'application/vnd.openxmlformats-officedocument.';
            if ($Ext==='docx') {
                $i = array('br' => '<w:br/>', 'ctype' => $ctype . 'wordprocessingml.document', 'pic_path' => 'word/media/', 'rpl_what' => $x, 'rpl_with' => '\'');
                $i['main'] = $this->OpenXML_MapGetMain('wordprocessingml.document.main+xml', 'word/document.xml');
                $i['load'] = $this->OpenXML_MapGetFiles(array('wordprocessingml.header+xml', 'wordprocessingml.footer+xml'));
                $block_alias = array(
                    'tbs:p' => 'w:p',
                    'tbs:title' => 'w:p',
                    'tbs:section' => array(&$this, 'MsWord_GetSection'),
                    'tbs:table' => 'w:tbl',
                    'tbs:row' => 'w:tr',
                    'tbs:page' => array(&$this, 'MsWord_GetPage'),
                    'tbs:draw' => 'mc:AlternateContent',
                    'tbs:drawgroup' => 'mc:AlternateContent',
                    'tbs:drawitem' => 'wps:wsp',
                    'tbs:listitem' => 'w:p',
                );
            } elseif ( ($Ext==='xlsx') || ($Ext==='xlsm')) {
                $i = array('br' => false, 'ctype' => $ctype . 'spreadsheetml.sheet', 'pic_path' => 'xl/media/');
                $i['main'] = $this->OpenXML_MapGetMain('spreadsheetml.worksheet+xml', 'xl/worksheets/sheet1.xml');
                $this->ExtEquiv = 'xlsx';
                $block_alias = array(
                    'tbs:row' => 'row',
                    'tbs:cell' => 'c',
                    'tbs:draw' => 'xdr:twoCellAnchor',
                    'tbs:drawgroup' => 'xdr:twoCellAnchor',
                    'tbs:drawitem' => 'xdr:sp',
                );
            } elseif ($Ext==='pptx') {
                $i = array('br' => false, 'ctype' => $ctype . 'presentationml.presentation', 'pic_path' => 'ppt/media/', 'rpl_what' => $x, 'rpl_with' => '\'');
                $this->MsPowerpoint_InitSlideLst();
                $i['main'] = (isset($this->OpenXmlSlideLst[0])) ? $this->OpenXmlSlideLst[0]['file'] : 'ppt/slides/slide1.xml';
                $i['load'] = $this->OpenXML_MapGetFiles(array('presentationml.notesSlide+xml')); // auto-load comments
                $block_alias = array(
                    'tbs:p' => 'a:p',
                    'tbs:title' => 'a:p',
                    'tbs:table' => 'a:tbl',
                    'tbs:row' => 'a:tr',
                    'tbs:cell' => 'a:tc',
                    'tbs:draw' => 'p:sp',
                    'tbs:drawgroup' => 'p:grpSp',
                    'tbs:drawitem' => 'p:sp',
                    'tbs:listitem' => 'a:p',
                );
            }
            $i['pic_ext'] = array('png' => 'png', 'bmp' => 'bmp', 'gif' => 'gif', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'jpe' => 'jpeg', 'tif' => 'tiff', 'tiff' => 'tiff', 'ico' => 'x-icon', 'svg' => 'svg+xml');
        }

        if ($i!==false) {
            $i['ext'] = $Ext;
            if (!isset($i['load'])) $i['load'] = array();
            $i['load'][] = $i['main']; // add to main file at the end of the files to load
        }

        if ( ($block_alias!==false) && method_exists($TBS, 'SetOption') ) $TBS->SetOption('block_alias', $block_alias);

        $this->ExtInfo = $i;
        if ($this->ExtEquiv===false) $this->ExtEquiv = $Ext;
        if ($this->ExtType===false)  $this->ExtType = $Frm;
        return (is_array($i)); // return true if the extension is supported
    }

    // Return the type of document corresponding to the given extension.
    public function Ext_DeductFormat(&$Ext, $Search)
    {
        if (strpos(',odt,ods,odg,odf,odp,odm,ott,ots,otg,otp,', ',' . $Ext . ',') !== false) return 'odf';
        if (strpos(',docx,xlsx,xlsm,pptx,', ',' . $Ext . ',') !== false) return 'openxml';
        if (!$Search) return false;
        if ($this->FileExists('content.xml')) {
            // OpenOffice documents
            if ($this->FileExists('META-INF/manifest.xml')) {
                $Ext = '?'; // not needed for processing OpenOffice documents

                return 'odf';
            }
        } elseif ($this->FileExists('[Content_Types].xml')) {
            // Ms Office documents
            if ($this->FileExists('word/document.xml')) {
                $Ext = 'docx';

                return 'openxml';
            } elseif ($this->FileExists('xl/workbook.xml')) {
                $Ext = 'xlsx';

                return 'openxml';
            } elseif ($this->FileExists('ppt/presentation.xml')) {
                $Ext = 'pptx';

                return 'openxml';
            }
        }

        return false;
    }

    // Return the idx of the main document, if any.
    public function Ext_GetMainIdx()
    {
        if ( ($this->ExtInfo!==false) && isset($this->ExtInfo['main']) ) {
            return $this->FileGetIdx($this->ExtInfo['main']);
        } else {
            return false;
        }
    }

    public function XML_FoundTagStart($Txt, $Tag, $PosBeg)
    {
    // Found the next tag of the asked type. (Not specific to MsWord, works for any XML)
    // Tag must be prefixed with '<' or '</'.
        $len = strlen($Tag);
        $p = $PosBeg;
        while ($p!==false) {
            $p = strpos($Txt, $Tag, $p);
            if ($p===false) return false;
            $x = substr($Txt, $p+$len, 1);
            if (($x===' ') || ($x==='/') || ($x==='>') ) {
                return $p;
            } else {
                $p = $p+$len;
            }
        }

        return false;
    }

    public function XML_DeleteElements(&$Txt, $TagLst, $OnlyInner=false)
    {
    // Delete all tags of the types given in the list. In fact the entire element is deleted if it's an opening+closing tag.
        $nbr_del = 0;
        foreach ($TagLst as $tag) {
            $t_open = '<'.$tag;
            $t_close = '</'.$tag;
            $p1 = 0;
            while (($p1=$this->XML_FoundTagStart($Txt, $t_open, $p1))!==false) {
                // get the end of the tag
                $pe1 = strpos($Txt, '>', $p1);
                if ($pe1===false) return false; // error in the XML formating
                $p2 = false;
                if (substr($Txt, $pe1-1, 1)=='/') {
                    $pe2 = $pe1;
                } else {
                    // it's an opening+closing
                    $p2 = $this->XML_FoundTagStart($Txt, $t_close, $pe1);
                    if ($p2===false) return false; // error in the XML formating
                    $pe2 = strpos($Txt, '>', $p2);
                }
                if ($pe2===false) return false; // error in the XML formating
                // delete the tag
                if ($OnlyInner) {
                    if ($p2!==false) $Txt = substr_replace($Txt, '', $pe1+1, $p2-$pe1-1);
                    $p1 = $pe1; // for next search
                } else {
                    $Txt = substr_replace($Txt, '', $p1, $pe2-$p1+1);
                }
            }
        }

        return $nbr_del;
    }

    /**
     * Delete all column elements  according to their position.
     * Return the number of deleted elements.
     */
    public function XML_DeleteColumnElements(&$Txt, $Tag, $SpanAtt, $ColLst, $ColMax)
    {
        $ColNum = 0;
        $ColPos = 0;
        $ColQty = 1;
        $Continue = true;
        $ModifNbr = 0;

        while ($Continue && ($Loc = clsTbsXmlLoc::FindElement($Txt, $Tag, $ColPos, true)) ) {

            // get colmun quantity covered by the element (1 by default)
            if ($SpanAtt!==false) {
                $ColQty = $Loc->GetAttLazy($SpanAtt);
                $ColQty = ($ColQty===false) ? 1 : intval($ColQty);
            }
            // count column to keep
            $KeepQty = 0;
            for ($i=1; $i<=$ColQty ;$i++) {
                if (array_search($ColNum+$i, $ColLst)===false) $KeepQty++;
            }
            if ($KeepQty==0) {
                // delete the tag
                $Loc->ReplaceSrc('');
                $ModifNbr++;
            } else {
                if ($KeepQty!=$ColQty) {
                    // edit the attribute
                    $Loc->ReplaceAtt($SpanAtt, $KeepQty);
                    $ModifNbr++;
                }
                $ColPos = $Loc->PosEnd + 1;
            }

            $ColNum += $ColQty;
            if ($ColNum>$ColMax) $Continue = false;
        }

        return $ModifNbr;

    }

    /**
     * Delete attributes in an XML element. The XML element is located by $Pos.
     * @param  string $Txt    Text containing XML elements.
     * @param  int    $Pos    Start of the XML element.
     * @param  array  $AttLst List of attributes to search an delete
     * @param  array  $StrLst List of strings to search an delete
     * @return int    The new end of the element.
     */
    public function XML_DeleteAttributes(&$Txt, $Pos, $AttLst, $StrLst)
    {
        $end = strpos($Txt, '>', $Pos); // end of the element
        if ($end===false) return (strlen($Txt)-1);
        $x_len = $end - $Pos + 1;
        $x = substr($Txt, $Pos, $x_len);
        // delete attributes
        foreach ($AttLst as $att) {
            $a = ' '.$att.'="';
            $p1 = strpos($x, $a);
            if ($p1!==false) {
                $p2 = strpos($x, '"', $p1+strlen($a));
                if ($p2!==false) $x = substr_replace($x, '', $p1, $p2-$p1+1);
            }
        }
        // Delete strings
        foreach ($StrLst as $str) $x = str_replace('', $str, $x);
        $x_len2 = strlen($x);
        if ($x_len2!=$x_len) $Txt = substr_replace($Txt, $x, $Pos, $x_len);
        return $Pos + $x_len2;
    }

    /**
     * Function used by Block Alias
     * The first start tag on the left is supposed to be the good one.
     * Note: encapuslation is not yet supported in this version.
     */
    public function XML_BlockAlias_Prefix($TagPrefix, $Txt, $PosBeg, $Forward, $LevelStop)
    {
        $loc = clsTbsXmlLoc::FindStartTagByPrefix($Txt, $TagPrefix, $PosBeg, false);

        if ($Forward) {
            $loc->FindEndTag();

            return $loc->PosEnd;
        } else {
            return $loc->PosBeg;
        }

    }

    /**
     * Return the column number from a cell reference like "B3".
     */
    public function Misc_ColNum($ColRef, $IsODF)
    {
        if ($IsODF) {
            $p = strpos($ColRef, '.');
            if ($p!==false) $ColRef = substr($ColRef, $p); // delete the table name wich is in prefix
            $ColRef = str_replace( array('.','$'), '', $ColRef);
            $ColRef = explode(':', $ColRef);
            $ColRef = $ColRef[0];
        }

        $num = 0;
        $rank = 0;
        for ($i=strlen($ColRef)-1;$i>=0;$i--) {
            $l = $ColRef[$i];
            if (!is_numeric($l)) {
                $l = ord(strtoupper($l)) -64;
                if ($l>0 && $l<27) {
                    $num = $num + $l*pow(26,$rank);
                } else {
                    return $this->RaiseError('(Sheet) Reference of cell \''.$ColRef.'\' cannot be recognized.');
                }
                $rank++;
            }
        }

        return $num;

    }

    /**
     * Return the refrence of the cell, such as 'A10'.
     */
    public function Misc_CellRef($Col, $Row)
    {
        $r = '';
        $x = $Col;
        do {
            $x = $x - 1;
            $c = ($x % 26);
            $x = ($x - $c)/26;
            $r = chr(65 + $c) . $r; // chr(65)='A'
        } while ($x>0);

        return $r.$Row;
    }

    /**
     * Return the path of the Rel file in the archive for a given XML document.
     * @param $DocPath      Full path of the sub-file in the archive
     */
    public function OpenXML_Rels_GetPath($DocPath)
    {
        $DocName = basename($DocPath);

        return str_replace($DocName,'_rels/'.$DocName.'.rels',$DocPath);
    }

    /**
     * Return an object that represents the informations of an .rels file, but for optimization, targets are scanned only for asked directories.
     * The result is stored in a cache so that a second call will not compute again.
     * The function stores Rids of files existing in a the $TargetPrefix directory of the archive (image, ...).
     * @param $DocPath      Full path of the sub-file in the archive
     * @param $TargetPrefix Prefix of the 'Target' attribute. For example $TargetPrefix='../drawings/'
     */
    public function OpenXML_Rels_GetObj($DocPath, $TargetPrefix)
    {
        if ($this->OpenXmlRid===false) $this->OpenXmlRid = array();

        // Create the object if it does not exist yet
        if (!isset($this->OpenXmlRid[$DocPath])) {

            $o = (object) null;
            $o->RidLst = array();    // Current Rids in the template ($Target=>$Rid)
            $o->TargetLst = array(); // Current Targets in the template ($Rid=>$Target)
            $o->RidNew = array();    // New Rids to add at the end of the merge
            $o->DirLst = array();    // Processed target dir
            $o->ChartLst = false;    // Chart list, computed in another method

            $o->FicPath = $this->OpenXML_Rels_GetPath($DocPath);

            $FicIdx = $this->FileGetIdx($o->FicPath);
            if ($FicIdx===false) {
                $o->FicType = 1;
                $Txt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            } else {
                $o->FicIdx = $FicIdx;
                $o->FicType = 0;
                $Txt = $this->FileRead($FicIdx, true);
            }
            $o->FicTxt = $Txt;
            $o->ParentIdx = $this->FileGetIdx($DocPath);

            $this->OpenXmlRid[$DocPath] = &$o;

        } else {

            $o = &$this->OpenXmlRid[$DocPath];
            $Txt = &$o->FicTxt;

        }

        // Feed the Rid and Target lists for the asked directory
        if (!isset($o->DirLst[$TargetPrefix])) {

            $o->DirLst[$TargetPrefix] = true;

            // read existing Rid in the file
            $zTarget = ' Target="'.$TargetPrefix;
            $zId  = ' Id="';
            $p = -1;
            while (($p = strpos($Txt, $zTarget, $p+1))!==false) {
                // Get the target name
                $p1 = $p + strlen($zTarget);
                $p2 = strpos($Txt, '"', $p1);
                if ($p2===false) return $this->RaiseError("(OpenXML) end of attribute Target not found in position ".$p1." of subfile ".$o->FicPath);
                $Target = $TargetPrefix.substr($Txt, $p1, $p2 -$p1);
                // Get the Id
                $p1 = strrpos(substr($Txt,0,$p), '<');
                if ($p1===false) return $this->RaiseError("(OpenXML) begining of tag not found in position ".$p." of subfile ".$o->FicPath);
                $p1 = strpos($Txt, $zId, $p1);
                if ($p1!==false) {
                    $p1 = $p1 + strlen($zId);
                    $p2 = strpos($Txt, '"', $p1);
                    if ($p2===false) return $this->RaiseError("(OpenXML) end of attribute Id not found in position ".$p1." of subfile ".$o->FicPath);
                    $Rid = substr($Txt, $p1, $p2 - $p1);
                    $o->RidLst[$Target] = $Rid;
                    $o->TargetLst[$Rid] = $Target;
                }
            }

        }

        return $o;

    }

    /* Add a new Rid in the file in the Rels file. Return the Rid.
    * Rels files are attached to XML files and are listing, and gives all rids and their corresponding targets used in the XML file.
    */
    public function OpenXML_Rels_AddNew($DocPath, $TargetDir, $FileName)
    {
        $o = $this->OpenXML_Rels_GetObj($DocPath, $TargetDir);

        $Target = $TargetDir.$FileName;

        if (isset($o->RidLst[$Target])) return $o->RidLst[$Target];

        // Add the Rid in the information
        $NewRid = 'opentbs'.(1+count($o->RidNew));
        $o->RidLst[$Target] = $NewRid;
        $o->RidNew[$Target] = $NewRid;

        $this->IdxToCheck[$o->ParentIdx] = $o->FicIdx;

        return $NewRid;

    }

    // Save the changes in the rels files (works only for images for now)
    public function OpenXML_RidCommit ($Debug)
    {
        foreach ($this->OpenXmlRid as $doc => $o) {

            if (count($o->RidNew)>0) {

                $store = isset($this->TbsStoreLst[$o->FicIdx]);
                $Txt = ($store) ? $this->TbsStoreLst[$o->FicIdx]['src'] : $o->FicTxt;

                // search position for insertion
                $p = strpos($Txt, '</Relationships>');
                if ($p===false) return $this->RaiseError("(OpenXML) closing tag </Relationships> not found in subfile ".$o->FicPath);

                // build the string to insert
                $x = '';
                foreach ($o->RidNew as $target=>$rid) {
                    $x .= '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="'.$target.'"/>';
                }

                // insert
                $Txt = substr_replace($Txt, $x, $p, 0);

                // save
                if ($store) {
                    $this->TbsStoreLst[$o->FicIdx]['src'] = $Txt;
                } else {
                    if ($o->FicType==1) {
                        $this->FileAdd($o->FicPath, $Txt);
                    } else {
                        $this->FileReplace($o->FicIdx, $Txt);
                    }
                }

                // debug mode
                if ($Debug) $this->DebugLst[$o->FicPath] = $Txt;

                $this->OpenXmlRid[$doc]->RidNew = array(); // Erase the Rid done because there can be another commit

            }
        }

    }

    /**
     * Initialize modifications in '[Content_Types].xml'.
     */
    public function OpenXML_CTypesInit()
    {
        if ($this->OpenXmlCTypes===false) {
            $this->OpenXmlCTypes = array(
                'Extension'=>array(),
                'PartName'=>array()
            );
        }
    }

    /**
     * Prepare information for adding a content type for an extension.
     * It needs to be completed when a new picture file extension is added in the document.
     */
    public function OpenXML_CTypesPrepareExt($FileOrExt, $ct='')
    {
        $p = strrpos($FileOrExt, '.');
        $ext = ($p===false) ? $FileOrExt : substr($FileOrExt, $p+1);
        $ext = strtolower($ext);

        $this->OpenXML_CTypesInit();

        $lst =& $this->OpenXmlCTypes['Extension'];
        if (isset($lst[$ext]) && ($lst[$ext]!=='') ) return;

        if (($ct==='') && isset($this->ExtInfo['pic_ext'][$ext])) $ct = 'image/'.$this->ExtInfo['pic_ext'][$ext];

        $lst[$ext] = $ct;

    }

    /**
     * Delete a file in the declaration file.
     * @param $PartName : path of the file to delete
     */
    public function OpenXML_CTypesDeletePart($PartName)
    {
        $this->OpenXML_CTypesInit();
        $this->OpenXmlCTypes['PartName'][$PartName] = false;
    }

    public function OpenXML_CTypesCommit($Debug)
    {
        $file = '[Content_Types].xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) {
            $Txt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>';
        } else {
            $Txt = $this->FileRead($idx, true);
        }

        $ok = false;

        // Delete PartNames
        foreach ($this->OpenXmlCTypes['PartName'] as $part=>$val) {
            if ($val===false) {
                $loc = clsTbsXmlLoc::FindElementHavingAtt($Txt, 'PartName="'.$part.'"', 0);
                if ($loc!==false) {
                    $loc->ReplaceSrc('');
                    $ok = true;
                }
            }
        }

        // Add missing extensions
        $x = '';
        foreach ($this->OpenXmlCTypes['Extension'] as $ext=>$ct) {
            $p = strpos($Txt, ' Extension="'.$ext.'"');
            if ($p===false) {
                if ($ct==='') {
                    $this->RaiseError("(OpenXML) '"+$ext+"' is not an picture's extension recognize by OpenTBS.");
                } else {
                    $x .= '<Default Extension="'.$ext.'" ContentType="'.$ct.'"/>';
                }
            }
        }
        if ($x!=='') {
            $p = strpos($Txt, '</Types>'); // search position for insertion
            if ($p===false) return $this->RaiseError("(OpenXML) closing tag </Types> not found in subfile ".$file);
            $Txt = substr_replace($Txt, $x, $p ,0);
            $ok = true;
        }

        if ($ok) {
            // debug mode
            if ($Debug) $this->DebugLst[$file] = $Txt;

            if ($idx===false) {
                $this->FileAdd($file, $Txt);
            } else {
                $this->FileReplace($idx, $Txt);
            }

        }

    }

    public function OpenXML_FirstPicAtt($Txt, $Pos, $Backward)
    {
    // search the first image element in the given direction. Two types of image can be found. Return the value required for "att" parameter.
        $TypeVml = '<v:imagedata ';
        $TypeDml = '<a:blip ';

        if ($Backward) {
            // search the last image position this code is compatible with PHP 4
            $p = -1;
            $pMax = -1;
            $t_curr = $TypeVml;
            $t = '';
            do {
                $p = strpos($Txt, $t_curr, $p+1);
                if ( ($p===false) || ($p>=$Pos) ) {
                    if ($t_curr===$TypeVml) {
                        // we take a new search for the next type of image
                        $t_curr = $TypeDml;
                        $p = -1;
                    } else {
                        $p = false;
                    }
                } elseif ($p>$pMax) {
                    $pMax = $p;
                    $t = $t_curr;
                }
            } while ($p!==false);
        } else {
            $p1 = strpos($Txt, $TypeVml, $Pos);
            $p2 = strpos($Txt, $TypeDml, $Pos);
            if (($p1===false) && ($p2===false)) {
                $t = '';
            } elseif ($p1===false) {
                $t = $TypeDml;
            } elseif ($p2===false) {
                $t = $TypeVml;
            } else {
                $t = ($p1<$p2) ? $TypeVml : $TypeDml;
            }
        }

        if ($t===$TypeVml) {
            return 'v:imagedata#r:id';
        } elseif ($t===$TypeDml) {
            return 'a:blip#r:embed';
        } else {
            return false;
        }

    }

    public function OpenXML_MapInit()
    {
    // read the Content_Type XML file and save a sumup in the OpenXmlMap property.

        $this->OpenXmlMap = array();
        $Map =& $this->OpenXmlMap;

        $file = '[Content_Types].xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->FileRead($idx, true);

        $type = ' ContentType="application/vnd.openxmlformats-officedocument.';
        $type_l = strlen($type);
        $name = ' PartName="';
        $name_l = strlen($name);

        $p = -1;
        while ( ($p=strpos($Txt, '<', $p+1))!==false) {
            $pe = strpos($Txt, '>', $p);
            if ($pe===false) return; // syntax error in the XML
            $x = substr($Txt, $p+1, $pe-$p-1);
            $pi = strpos($x, $type);
            if ($pi!==false) {
                $pi = $pi + $type_l;
                $pc = strpos($x, '"', $pi);
                if ($pc===false) return; // syntax error in the XML
                $ShortType = substr($x, $pi, $pc-$pi); // content type's short value
                $pi = strpos($x, $name);
                if ($pi!==false) {
                    $pi = $pi + $name_l;
                    $pc = strpos($x, '"', $pi);
                    if ($pc===false) return; // syntax error in the XML
                    $Name = substr($x, $pi, $pc-$pi); // name
                    if ($Name[0]=='/') $Name = substr($Name,1); // fix the file path
                    if (!isset($Map[$ShortType])) $Map[$ShortType] = array();
                    $Map[$ShortType][] = $Name;
                }
            }
            $p = $pe;
        }

    }

    public function OpenXML_MapGetFiles($ShortTypes)
    {
    // Return all values for a given type (or array of types) in the map.
        if (is_string($ShortTypes)) $ShortTypes = array($ShortTypes);
        $res = array();
        foreach ($ShortTypes as $type) {
            if (isset($this->OpenXmlMap[$type])) {
                $val = $this->OpenXmlMap[$type];
                foreach ($val as $file) $res[] = $file;
            }
        }

        return $res;
    }

    public function OpenXML_MapGetMain($ShortType, $Default)
    {
    // Return all values for a given type (or array of types) in the map.
        if (isset($this->OpenXmlMap[$ShortType])) {
            return $this->OpenXmlMap[$ShortType][0];
        } else {
            return $Default;
        }
    }

    public function OpenXML_ChartInit()
    {
        $this->OpenXmlCharts = array();

        foreach ($this->CdFileByName as $f => $i) {
            if (strpos($f, '/charts/')!==false) {
                $f = explode('/',$f);
                $n = count($f) -1;
                if ( ($n>=2) && ($f[$n-1]==='charts') ) {
                    $f = $f[$n]; // name of the xml file
                    if (substr($f,-4)==='.xml') {
                        $f = substr($f,0,strlen($f)-4);
                        $this->OpenXmlCharts[$f] = array('idx'=>$i, 'clean'=>false, 'series'=>false);
                    }
                }
            }
        }

    }

    public function OpenXML_ChartDebug($nl, $sep, $bull)
    {
        if ($this->OpenXmlCharts===false) $this->OpenXML_ChartInit();

        echo $nl;
        echo $nl."Charts technically stored in the document:";
        echo $nl."------------------------------------------";

        // list of supported charts
        $nbr = 0;
        foreach ($this->OpenXmlCharts as $key => $info) {
            $ok = true;
            if (!isset($info['series_nbr'])) {
                $txt = $this->FileRead($info['idx'], true);
                $info['series_nbr'] = substr_count($txt, '<c:ser>');
                $ok = (strpos($txt, '<c:chart>')!==false);
            }
            if ($ok) {
                $nbr++;
                echo $bull."name: '".$key."' , number of series: ".$info['series_nbr'];
            }
        }

        if ($this->TbsCurrIdx===false) {
            echo $bull."(unable to scann more because no subfile is loaded)";
        } else {
            $x = ' ProgID="MSGraph.Chart.';
            $x_len = strlen($x);
            $p = 0;
            $txt = $this->TBS->Source;
            while (($p=strpos($txt, $x, $p))!==false) {
                // check that the text is inside an xml tag
                $p = $p + $x_len;
                $p1 = strpos($txt, '>', $p);
                $p2 = strpos($txt, '<', $p);
                if ( ($p1!==false) && ($p2!==false) && ($p1<$p2) ) {
                    $nbr++;
                    $p1 = strpos($txt, '"', $p);
                    $z = substr($txt, $p, $p1-$p);
                    echo $bull."1 chart created using MsChart version ".$z." (series can't be merged with OpenTBS)";
                }
            }
        }

        if ($nbr==0) echo $bull."(none)";

    }

    public function OpenXML_ChartSeriesFound(&$Txt, $SeriesNameOrNum, $OnlyBounds=false)
    {
        $IsNum = is_numeric($SeriesNameOrNum);
        if ($IsNum) {
            $p = strpos($Txt, '<c:order val="'.($SeriesNameOrNum-1).'"/>');
        } else {
            $SeriesNameOrNum = htmlentities($SeriesNameOrNum);
            $p = strpos($Txt, '>'.$SeriesNameOrNum.'<');
        }
        if ($p===false) return false;

        if (!$IsNum) $p++;
        $res = array('p'=>$p);

        if ($OnlyBounds) {
            $p1 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'c:ser', true, $p, false, true);
            $x = '</c:ser>';
            $p2 = strpos($Txt, '</c:ser>', $p1);
            if ($p2===false) return false;
            $res['l'] = $p2 + strlen($x) - $p1;

            return $res;
        }

        $end_tag = '</c:ser>';
        $end = strpos($Txt, '</c:ser>', $p);
        $len = $end + strlen($end_tag) - $p;
        $res['l'] = $len;

        $x = substr($Txt, $p, $len);

        // Legend, may be abensent
        $p = 0;
        if ($IsNum) {
            $p1 = strpos($x, '<c:tx>');
            if ($p1>0) {
                $p2 = strpos($x, '</c:tx>', $p1);
                $tag = '<c:v>';
                $p1 = strpos($x, $tag, $p1);
                if ( ($p1!==false) && ($p1<$p2) ) {
                    $p1 = $p1 + strlen($tag);
                    $p2 = strpos($x, '<', $p1);
                    $res['leg_p'] = $p1;
                    $res['leg_l'] = $p2 - $p1;
                    $p = $p2;
                }
            }
        } else {
            $res['leg_p'] = 0;
            $res['leg_l'] = strlen($SeriesNameOrNum);
        }

        // Data X & Y, we assume that (X or Category) are always first and (Y or Value) are always second
        for ($i=1; $i<=2; $i++) {
            $p1 = strpos($x, '<c:ptCount ', $p);
            if ($p1===false) return false;
            $p2 = strpos($x, 'Cache>', $p1); // the closing tag can be </c:numCache> or </c:strCache>
            if ($p2===false) return false;
            $p2 = $p2 - 7;
            $res['point'.$i.'_p'] = $p1;
            $res['point'.$i.'_l'] = $p2 - $p1;
            $p = $p2;
        }

        return $res;

    }

    public function OpenXML_ChartChangeSeries($ChartRef, $SeriesNameOrNum, $NewValues, $NewLegend=false)
    {
        if ($this->OpenXmlCharts===false) $this->OpenXML_ChartInit();

        // search the chart
        $ref = ''.$ChartRef;
        if (!isset($this->OpenXmlCharts[$ref])) $ref = 'chart'.$ref; // try with $ChartRef as number
        if (!isset($this->OpenXmlCharts[$ref])) {
            // try with $ChartRef as name of the file
            $charts = array();
            if ($this->ExtEquiv=='pptx') {
                // search in slides
                $find = $this->MsPowerpoint_SearchInSlides(' title="'.$ChartRef.'"');
                if ($find['idx']!==false) $charts = $this->OpenXML_ChartGetInfoFromFile($find['idx']);
            } else {
                $charts = $this->OpenXML_ChartGetInfoFromFile($this->Ext_GetMainIdx());
            }
            foreach($charts as $c) if ($c['title']===$ChartRef) $ref = $c['name']; // try with $ChartRef as title
            if (!isset($this->OpenXmlCharts[$ref])) return $this->RaiseError("(ChartChangeSeries) : unable to found the chart corresponding to '".$ChartRef."'.");
        }

        $chart =& $this->OpenXmlCharts[$ref];
        $Txt = $this->TbsStoreGet($chart['idx'], 'ChartChangeSeries');
        if ($Txt===false) return false;

        if (!$chart['clean']) {
            // delete tags that refere to the XLSX file containing original data
            //$this->XML_DeleteElements($Txt, array('c:externalData', 'c:f'));
            $chart['nbr'] = substr_count($Txt, '<c:ser>');
            $chart['clean'] = true;
        }

        $Delete = ($NewValues===false);
        if (is_array($SeriesNameOrNum)) return $this->RaiseError("(ChartChangeSeries) '$ChartRef' : The series reference is an array, a string or a number is expected. ".$ChartRef."'."); // usual mistake in arguments
        $ser = $this->OpenXML_ChartSeriesFound($Txt, $SeriesNameOrNum, $Delete);
        if ($ser===false) return $this->RaiseError("(ChartChangeSeries) '$ChartRef' : unable to found series '".$SeriesNameOrNum."' in the chart '".$ref."'.");

        if ($Delete) {

            $Txt = substr_replace($Txt, '', $ser['p'], $ser['l']);

        } else {


            $point1 = '';
            $point2 = '';
            $i = 0;
            $v = reset($NewValues);
            if (is_array($v)) {
                // syntax 2: $NewValues = array( array('cat1','cat2',...), array(val1,val2,...) );
                $k = key($NewValues);
                $key_lst = &$NewValues[$k];
                $val_lst = &$NewValues[1];
                $simple = false;
            } else {
                // syntax 1: $NewValues = array('cat1'=>val1, 'cat2'=>val2, ...);
                $key_lst = &$NewValues;
                $val_lst = &$NewValues;
                $simple = true;
            }
            foreach ($key_lst as $k=>$v) {
                if ($simple) {
                    $x = $k;
                    $y = $v;
                } else {
                    $x = $v;
                    $y = isset($val_lst[$k]) ? $val_lst[$k] : null;
                }
                if ( (!is_null($y)) && ($y!==false) && ($y!=='') && ($y!=='NULL') ) {
                    $point1 .= '<c:pt idx="'.$i.'"><c:v>'.$x.'</c:v></c:pt>';
                    $point2 .= '<c:pt idx="'.$i.'"><c:v>'.$y.'</c:v></c:pt>';
                    $i++;
                }
            }
            $point1 = '<c:ptCount val="'.$i.'"/>'.$point1;
            $point2 = '<c:ptCount val="'.$i.'"/>'.$point2;

            // change info in reverse order of placement in order to avoid exention problems
            $p = $ser['p'];
            $Txt = substr_replace($Txt, $point2, $p+$ser['point2_p'], $ser['point2_l']);
            $Txt = substr_replace($Txt, $point1, $p+$ser['point1_p'], $ser['point1_l']);
            if ( (is_string($NewLegend)) && isset($ser['leg_p']) && ($ser['leg_p']<$ser['point1_p']) ) {
                $NewLegend = htmlspecialchars($NewLegend);
                $Txt = substr_replace($Txt, $NewLegend, $p+$ser['leg_p'], $ser['leg_l']);
            }

        }

        $this->TbsStorePut($chart['idx'], $Txt, true);

        return true;

    }

    /**
     * Return the list of all charts in the current sub-file, with title and description if any.
     */
    public function OpenXML_ChartGetInfoFromFile($idx, $Txt=false)
    {
        if ($idx===false) return false;

        $file = $this->CdFileLst[$idx]['v_name'];
        $relative = (substr_count($file, '/')==1) ? '' : '../';
        $o = $this->OpenXML_Rels_GetObj($file, $relative.'charts/');

        if ($o->ChartLst===false) {

            if ($Txt===false) $Txt = $this->TbsStoreGet($idx, 'OpenXML_ChartGetInfoFromFile');

            $o->ChartLst = array();

            $p = 0;
            while ($t = clsTbsXmlLoc::FindStartTag($Txt, 'c:chart', $p)) {
                $rid = $t->GetAttLazy('r:id');
                $name = false;
                $title = false;
                $descr = false;
                $parent = clsTbsXmlLoc::FindStartTag($Txt, 'wp:inline', $t->PosBeg, false); // docx
                if ($parent===false) $parent = clsTbsXmlLoc::FindStartTag($Txt, 'p:nvGraphicFramePr', $t->PosBeg, false); // pptx
                if ($parent!==false) {
                    $parent->FindEndTag();
                    $src = $parent->GetInnerSrc();
                    $el = clsTbsXmlLoc::FindStartTagHavingAtt($src, 'title', 0);
                    if ($el!==false) $title = $el->GetAttLazy('title');
                    $el = clsTbsXmlLoc::FindStartTagHavingAtt($src, 'descr', 0);
                    if ($el!==false) $descr = $el->GetAttLazy('descr');
                }

                if (isset($o->TargetLst[$rid])) {
                    $name = basename($o->TargetLst[$rid]);
                    if (substr($name,-4)==='.xml') $name = substr($name,0,strlen($name)-4);
                }
                $o->ChartLst[] = array('rid'=>$rid, 'title'=>$title, 'descr'=>$descr, 'name'=>$name);
                $p = $t->PosEnd;
            }

        }

        return $o->ChartLst;

    }

    public function OpenXML_SharedStrings_Prepare()
    {
        $file = 'xl/sharedStrings.xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'Excel SharedStrings');
        if ($Txt===false) return false;
        $this->TbsStorePut($idx, $Txt); // save for any further usage

        $this->OpenXmlSharedStr = array();
        $this->OpenXmlSharedSrc =& $this->TbsStoreLst[$idx]['src'];

    }

    public function OpenXML_SharedStrings_GetVal($id)
    {
    // this function return the XML content of the string and put previous met values in cache
        if ($this->OpenXmlSharedStr===false) $this->OpenXML_SharedStrings_Prepare();

        $Txt =& $this->OpenXmlSharedSrc;

        if (!isset($this->OpenXmlSharedStr[$id])) {
            $last_id = count($this->OpenXmlSharedStr) - 1; // last id in the cache
            if ($last_id<0) {
                $p2 = 0; // no items found yet
            } else {
                $p2 = $this->OpenXmlSharedStr[$last_id]['end'];
            }
            $x1 = '<si'; // SharedString Item
            $x1_len = strlen($x1);
            $x2 = '</si>';
            while ($last_id<$id) {
                $last_id++;
                $p1 = strpos($Txt, $x1, $p2+1);
                if ($p1===false) return $this->RaiseError("(Excel SharedStrings) id $id is searched but id $last_id is not found.");
                $p1 = strpos($Txt, '>', $p1+$x1_len)+1;
                $p2 = strpos($Txt, $x2, $p1);
                if ($p2===false) return $this->RaiseError("(Excel SharedStrings) id $id is searched but no closing tag found for id $last_id.");
                $this->OpenXmlSharedStr[$last_id] = array('beg'=>$p1, 'end'=>$p2, 'len'=>($p2-$p1));
            }
        }

        $str =& $this->OpenXmlSharedStr[$id];

        return substr($Txt, $str['beg'], $str['len']);

    }

    /**
     * Clear Rid in the Rels file that are missing in the parent file.
     */
    public function OpenXML_ClearRels()
    {
        // TODO
        // foreach ($this->IdxToCheck as $ParentIdx=>$RelsIdx) {
        // }
    }

    // Delete unreferenced images
    public function OpenMXL_GarbageCollector()
    {
        if ( (count($this->IdxToCheck)==0) && (count($this->OtbsSheetSlidesDelete)==0) ) return;

        // Key for Pictures
        $pic_path = $this->ExtInfo['pic_path'];
        $pic_path_len = strlen($pic_path);

        // Key for Rels
        $rels_ext = '.rels';
        $rels_ext_len = strlen($rels_ext);

        // List all Pictures and Rels files
        $pictures = array();
        $rels = array();
        foreach ($this->CdFileLst as $idx=>$f) {
            $n = $f['v_name'];
            if (substr($n, 0, $pic_path_len)==$pic_path) {
                $short = basename($pic_path).'/'.basename($n);
                $pictures[] = array('name'=>$n, 'idx'=>$idx, 'nbr'=>0, 'short'=>$short);
            } elseif (substr($n, -$rels_ext_len)==$rels_ext) {
                if ($this->FileGetState($idx)!='d') $rels[$n] = $idx;
            }
        }

        // Read contents or Rels files
        foreach ($rels as $n=>$idx) {
            $txt = $this->TbsStoreGet($idx, 'GarbageCollector');
            foreach ($pictures as $i=>$info) {
                if (strpos($txt, $info['short'].'"')!==false) $pictures[$i]['nbr']++;
            }
        }

        // Delete unused Picture files
        foreach ($pictures as $info) {
            if ($info['nbr']==0) $this->FileReplace($info['idx'], false);
        }


    }

    public function MsExcel_ConvertToRelative(&$Txt)
    {
        // <row r="10" ...> attribute "r" is optional since missing row are added using <row />
        // <c r="D10" ...> attribute "r" is optional since missing cells are added using <c />
        $Loc = new clsTbsLocator;
        $this->MsExcel_ConvertToRelative_Item($Txt, $Loc, 'row', 'r', true);
    }

    public function MsExcel_ConvertToRelative_Item(&$Txt, &$Loc, $Tag, $Att, $IsRow)
    {
    // convert tags $Tag which have a position (defined with attribute $Att) into relatives tags without attribute $Att. Missing tags are added as empty tags.
        $item_num = 0;
        $tag_len = strlen($Tag);
        $missing = '<'.$Tag.'/>';
        $closing = '</'.$Tag.'>';
        $p = 0;
        while (($p=clsTinyButStrong::f_Xml_FindTagStart($Txt, $Tag, true, $p, true, true))!==false) {

            $Loc->PrmPos = array();
            $Loc->PrmLst = array();
            $p2 = $p + $tag_len + 2; // count the char '<' before and the char ' ' after
            $PosEnd = strpos($Txt, '>', $p2);
            clsTinyButStrong::f_Loc_PrmRead($Txt,$p2,true,'\'"','<','>',$Loc, $PosEnd, true); // read parameters
            if (isset($Loc->PrmPos[$Att])) {
                // attribute found
                $r = $Loc->PrmLst[$Att];
                if ($IsRow) {
                    $r = intval($r);
                } else {
                    $r = $this->Misc_ColNum($r, false);
                }
                $missing_nbr = $r - $item_num -1;
                if ($missing_nbr<0) {
                    return $this->RaiseError('(Excel Consistency) error in counting items <'.$Tag.'>, found number '.$r.', previous was '.$item_num);
                } else {
                    // delete the $Att attribute
                    $pp = $Loc->PrmPos[$Att];
                    $pp[3]--; //while ($Txt[$pp[3]]===' ') $pp[3]--; // external end of the attribute, may has an extra spaces
                    $x_p = $pp[0]-1; // we take out the space
                    $x_len = $pp[3] - $x_p +1;
                    $Txt = substr_replace($Txt, '', $x_p, $x_len);
                    $PosEnd = $PosEnd - $x_len;
                    // If it's a cell, we look if it's a good idea to replace the shared string
                    if ( (!$IsRow) && isset($Loc->PrmPos['t']) && ($Loc->PrmLst['t']==='s') ) $this->MsExcel_ReplaceString($Txt, $p, $PosEnd);
                    // add missing items before the current item
                    if ($missing_nbr>0) {
                        $x = str_repeat($missing, $missing_nbr);
                        $x_len = strlen($x);
                        $Txt = substr_replace($Txt, $x, $p, 0);
                        $PosEnd = $PosEnd + $x_len;
                        $x = ''; // empty the memory
                    }
                }
                $item_num = $r;
            } else {
                // nothing to change the item is already relative
                $item_num++;
            }

            if ($IsRow && ($Txt[$PosEnd-1]!=='/')) {
                // It's a row item that may contain columns
                $x_p = strpos($Txt, $closing, $PosEnd);
                if ($x_p===false) return $this->RaiseError('(Excel Consistency) closing row tag is not found.');
                $x_len0 = $x_p - $PosEnd -1;
                $x = substr($Txt, $PosEnd+1, $x_len0);
                $this->MsExcel_ConvertToRelative_Item($x, $Loc, 'c', 'r', false);
                $Txt = substr_replace($Txt, $x, $PosEnd+1, $x_len0);
                $x_len = strlen($x);
                $p = $x_p + $x_len - $x_len0;
            } else {
                $p = $PosEnd;
            }

        }

    }

    /**
     * Add the attribute in all <row> and <c> items, and delete empty items.
     */
    public function MsExcel_ConvertToExplicit(&$Txt)
    {
        if (strpos($Txt, '<sheetData>')===false) return;
        $this->MsExcel_ConvertToExplicit_Item($Txt, 'row', 'r', false);
    }

    /**
     * Add the attribute that gives the reference of the item.
     * Return the number of inserted attributes.
     */
    public function MsExcel_ConvertToExplicit_Item(&$Txt, $Tag, $Att, $CellRow)
    {
        $tag_pc = strlen($Tag) + 1;
        $rpl = '<'.$Tag.' '.$Att.'="';
        $rpl_len = strlen($rpl);
        $rpl_nbr = 0;
        $p = 0;
        $empty_first_pos = false;
        $empty_nbr = 0;
        $item_num = 0;
        $rpl_nbr = 0;

        while (($p=clsTinyButStrong::f_Xml_FindTagStart($Txt, $Tag, true, $p, true, true))!==false) {

            $item_num++;

            if ($empty_first_pos===false) $empty_first_pos = $p;

            $p = $p + $tag_pc;
            if (substr($Txt, $p, 1) == '/') {
                // It's an empty item
                $empty_nbr++;
            } else {
                // The item is not empty => relace attribute and delete the previus empty item in the same time
                $ref = ($CellRow===false) ? $item_num : $this->Misc_CellRef($item_num, $CellRow);
                $x = $rpl.$ref.'"';
                $len = $p - $empty_first_pos;
                $Txt = substr_replace($Txt, $x, $empty_first_pos, $len);
                $rpl_nbr++;

                // If it's a row => search for cells
                if ($CellRow===false) {
                    $loc = new clsTbsXmlLoc($Txt, $Tag, $p);
                    $loc->FindEndTag();
                    $src = $loc->GetSrc();
                    $nbr = $this->MsExcel_ConvertToExplicit_Item($src, 'c', 'r', $item_num);
                    if ($nbr>0) {
                        $loc->ReplaceSrc($src);
                    }
                    $p = $loc->PosEnd;
                } else {
                    $p = $empty_first_pos + $tag_pc;
                }
                // Ini variables
                $empty_nbr = 0;
                $empty_first_pos = false;
            }

        }

        return $rpl_nbr;

    }

    public function MsExcel_DeleteFormulaResults(&$Txt)
    {
    // In order to refresh the formula results when the merged XLSX is opened, then we delete all <v> elements having a formula.
        $c_close = '</c>';
        $p = 0;
        while (($p=clsTinyButStrong::f_Xml_FindTagStart($Txt, 'f', true, $p, true, true))!==false) {
            $c_p = strpos($Txt, $c_close, $p);
            if ($c_p===false) return false; // error in the XML
            $x_len0 = $c_p - $p;
            $x = substr($Txt, $p, $x_len0);
            $this->XML_DeleteElements($x, array('v'));
            $Txt = substr_replace($Txt, $x, $p, $x_len0);
            $p = $p + strlen($x);
        }
    }

    public function MsExcel_ReplaceString(&$Txt, $p, &$PosEnd)
    {
    // replace a SharedString into an InlineStr only if the string contains a TBS field
        static $c = '</c>';
        static $v1 = '<v>';
        static $v1_len = 3;
        static $v2 = '</v>';
        static $v2_len = 4;
        static $notbs = array();

        // found position of the <c> element, and extract its contents
        $p_close = strpos($Txt, $c, $PosEnd);
        if ($p_close===false) return;
        $x_len = $p_close - $p;
        $x = substr($Txt, $p, $x_len); // [<c ...> ... ]</c>

        // found position of the <v> element, and extract its contents
        $v1_p = strpos($x, $v1);
        if ($v1_p==false) return false;
        $v2_p = strpos($x, $v2, $v1_p);
        if ($v2_p==false) return false;
        $vt = substr($x, $v1_p+$v1_len, $v2_p - $v1_p - $v1_len);

        // extract the SharedString id, and retrieve the corresponding text
        $v = intval($vt);
        if (($v==0) && ($vt!='0')) return false;
        if (isset($notbs[$v])) return true;
        $s = $this->OpenXML_SharedStrings_GetVal($v);

        // if the SharedSring has no TBS field, then we save the id in a list of known id, and we leave the function
        if (strpos($s, $this->TBS->_ChrOpen)===false) {
            $notbs[$v] = true;

            return true;
        }

        // prepare the replace
        $x1 = substr($x, 0, $v1_p);
        $x3 = substr($x, $v2_p + $v2_len);
        $x2 = '<is>'.$s.'</is>';
        $x = str_replace(' t="s"', ' t="inlineStr"', $x1).$x2.$x3;

        $Txt = substr_replace($Txt, $x, $p, $x_len);

        $PosEnd = $p + strlen($x); // $PosEnd is used to search the next item, so we update it

    }

    public function MsExcel_ChangeCellType(&$Txt, &$Loc, $Ope)
    {
    // change the type of a cell in an XLSX file

        $Loc->PrmLst['cellok'] = $Ope; // avoid the field to be processed twice

        if ( ($Ope==='xlsxString') || ($Ope==='tbs:string')) return true;

        static $OpeLst = array(
            'tbs:bool'=>' t="b"',
            'xlsxBool'=>' t="b"',
            'xlsxDate'=>'',
            'xlsxNum'=>'',
            'tbs:date'=>'',
            'tbs:num'=>'',
            // compatibility with ODF format
            'tbs:time'=>'',
            'tbs:percent'=>'',
            'tbs:curr'=>'',
        );

        if (!isset($OpeLst[$Ope])) return false;

        $t0 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'c', true, $Loc->PosBeg, false, true);
        if ($t0===false) return false; // error in the XML structure

        $te = strpos($Txt, '>', $t0);
        if ( ($te===false) || ($te>$Loc->PosBeg) ) return false; // error in the XML structure

        $len = $te - $t0 + 1;
        $c_open = substr($Txt, $t0, $len); // '<c ...>'
        $c_open = str_replace(' t="inlineStr"', $OpeLst[$Ope], $c_open);

        $t1 = strpos($Txt, '</c>', $te);
        if ($t1===false) return false; // error in the XML structure

        $p_is1 = strpos($Txt, '<is>', $te);
        if (($p_is1===false) || ($p_is1>$t1) ) return false; // error in the XML structure

        $is2 = '</is>';
        $p_is2 = strpos($Txt, $is2, $p_is1);
        if (($p_is2===false) || ($p_is2>$t1) ) return false; // error in the XML structure
        $p_is2 = $p_is2 + strlen($is2); // move to end the of the tag

        $middle_len = $p_is1 - $te - 1;
        $middle = substr($Txt, $te + 1, $middle_len); // text bewteen <c...> and <is>

        // new tag to replace <is>...</is>
        static $v = '<v>[]</v>';
        $v_len = strlen($v);
        $v_pos = strpos($v, '[]');

        $x = $c_open.$middle.$v;

        $Txt = substr_replace($Txt, $x, $t0, $p_is2 - $t0);

        // move the TBS field
        $p_fld = $t0 + strlen($c_open) + $middle_len + $v_pos;
        $Loc->PosBeg = $p_fld;
        $Loc->PosEnd = $p_fld +1;

    }

    public function MsExcel_ChangeCellValue(&$Loc, &$Value)
    {
        switch ($Loc->PrmLst['cellok']) {
        case 'tbs:num':
        case 'tbs:curr':
        case 'tbs:percent':
        case 'xlsxNum':
            if (is_numeric($Value)) {
                // we have to check contents in order to avoid Excel errors. Note that value '0.00000000000000' makes an Excel error.
                if (strpos($Value,'e')!==false) { // exponential representation
                    $Value = (float) $Value;
                } elseif (strpos($Value,'x')!==false) { // hexa representation
                    $Value = hexdec($Value);
                } elseif (strpos($Value,'.')===false) {
                    $Value = (integer) $Value;
                } else {
                    $Value = (float) $Value;
                }
            } else {
                $Value = '';
            }
            $Value = (is_numeric($Value)) ? ''.$Value : '';
            break;
        case 'tbs:bool':
        case 'xlsxBool':
            $Value = ($Value) ? 1 : 0;
            break;
        case 'tbs:date':
        case 'tbs:time':
        case 'xlsxDate':
            if (is_string($Value)) {
                $t = strtotime($Value); // We look if it's a date
            } else {
                $t = $Value;
            }
            if (($t===-1) or ($t===false)) { // Date not recognized
                $Value = '';
            } elseif ($t===943916400) { // Date to zero
                $Value = '';
            } else { // It's a date
                $Value = ($t/86400.00)+25569; // unix: 1 means 01/01/1970, xls: 1 means 01/01/1900
            }
            break;
        default:
            // do nothing
        }
    }

    public function MsExcel_SheetInit()
    {
        if ($this->MsExcel_Sheets!==false) return;

        $this->MsExcel_Sheets = array();   // sheet info sorted by location

        $idx = $this->FileGetIdx('xl/workbook.xml');
        $this->MsExcel_Sheets_WkbIdx = $idx;
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'SheetInfo'); // use the store, so the file will be available for editing if needed
        if ($Txt===false) return false;
        $this->TbsStorePut($idx, $Txt);

        // scann sheet list
        $p = 0;
        $i = 0;
        $rels = array();
        while ($loc=clsTbsXmlLoc::FindStartTag($Txt, 'sheet', $p, true) ) {
            $o = (object) null;
            $o->sheetId   = $loc->GetAttLazy('sheetId');
            $o->rid   = $loc->GetAttLazy('r:id');
            $o->name  = $loc->GetAttLazy('name');
            $o->state = $loc->GetAttLazy('state');
            $o->stateR = ($o->state===false) ? 'visible' : $o->state;
            $o->file  = false;
            $this->MsExcel_Sheets[$i] = $o;
            $rels[$o->rid] =& $this->MsExcel_Sheets[$i];
            $i++;
            $p = $loc->PosEnd;
        }

        // Retrieve Sheet files
        $idx = $this->FileGetIdx('xl/_rels/workbook.xml.rels');
        $this->MsExcel_Sheets_WkbRelsIdx = $idx;
        $Txt = $this->FileRead($idx);
        if ($Txt===false) return false;

        $p = 0;
        while ($loc=clsTbsXmlLoc::FindStartTag($Txt, 'Relationship', $p, true) ) {
            $rid = $loc->GetAttLazy('Id');
            if (isset($rels[$rid])) $rels[$rid]->file =  $loc->GetAttLazy('Target');
            $p = $loc->PosEnd;
        }

    }

    public function MsExcel_SheetGet($IdOrName)
    {
        $this->MsExcel_SheetInit();
        foreach ($this->MsExcel_Sheets as $o) {
            if ($o->name==$IdOrName) return $o;
            if ($o->sheetId==$IdOrName) return $o;
        }

        return $this->RaiseError("($Caller) The sheet '$IdOrName' is not found inside the Workbook. Try command OPENTBS_DEBUG_INFO to check all sheets inside the current Workbook.");
    }

    public function MsExcel_SheetDebug($nl, $sep, $bull)
    {
        $this->MsExcel_SheetInit();

        echo $nl;
        echo $nl."Sheets in the Workbook:";
        echo $nl."-----------------------";
        foreach ($this->MsExcel_Sheets as $o) {
            $name = str_replace(array('&amp;','&quot;','&lt;','&gt;'), array('&','"','<','>'), $o->name);
            echo $bull."id: ".$o->sheetId.", name: [".$name."], state: ".$o->stateR;
        }

    }

    // Actally delete, display of hide sheet marked for this operations.
    public function MsExcel_SheetDeleteAndDisplay()
    {
        if ( (count($this->OtbsSheetSlidesDelete)==0) && (count($this->OtbsSheetSlidesVisible)==0) ) return;

        $this->MsExcel_SheetInit();

        $WkbTxt = $this->TbsStoreGet($this->MsExcel_Sheets_WkbIdx, 'Sheet Delete and Display');
        $WkbRelsTxt = false;
        $nothing = false;

        $change = false;
        $refToDel = array();

        // process sheet in reverse order of their positions
        foreach ($this->MsExcel_Sheets as $o) {
            $zid = 'i:'.$o->sheetId;
            $zname = 'n:'.$o->name; // the value in the name attribute is XML protected
            if ( isset($this->OtbsSheetSlidesDelete[$zname]) || isset($this->OtbsSheetSlidesDelete[$zid]) ) {
                if ($WkbRelsTxt===false) {
                    $WkbRelsTxt = $this->TbsStoreGet($this->MsExcel_Sheets_WkbRelsIdx, 'Sheet Delete and Display');
                }
                // Delete the sheet
                $this->MsExcel_DeleteFile($o->file, $WkbRelsTxt, $o->rid, $WkbTxt);
                $change = true;
                $ref1 = str_replace(array('&quot;','\''), array('"','\'\''), $o->name);
                $ref2 = "'".$ref1."'";
                $refToDel[] = $ref1;
                $refToDel[] = $ref2;
                unset($this->OtbsSheetSlidesDelete[$zname]);
                unset($this->OtbsSheetSlidesDelete[$zid]);
                unset($this->OtbsSheetSlidesVisible[$zname]);
                unset($this->OtbsSheetSlidesVisible[$zid]);
            } elseif ( isset($this->OtbsSheetSlidesVisible[$zname]) || isset($this->OtbsSheetSlidesVisible[$zid]) ) {
                // Hide or display the sheet
                $visible = (isset($this->OtbsSheetSlidesVisible[$zname])) ? $this->OtbsSheetSlidesVisible[$zname] : $this->OtbsSheetSlidesVisible[$zid];
                $state = ($visible) ? 'visible' : 'hidden';
                if ($o->stateR!=$state) {
                    if (!$visible) $change = true;
                    $loc = clsTbsXmlLoc::FindStartTagHavingAtt($WkbTxt, 'r:id="'.$o->rid.'"', 0);
                    if ($loc!==false) $loc->ReplaceAtt('state', $state, true);
                }
                unset($this->OtbsSheetSlidesVisible[$zname]);
                unset($this->OtbsSheetSlidesVisible[$zid]);
            }
        }

        // If they are deleted or hidden sheet, then it could be the active sheet, so we delete the active tab information
        // Note: activeTab attribute seems to not be a sheet id, but rather a tab id.
        if ($change) {
            $loc = clsTbsXmlLoc::FindStartTag($WkbTxt, 'workbookView', 0);
            if ($loc!==false) $loc->DeleteAtt('activeTab');
        }

        // Delete name of cells (<definedName>) that refer to a deleted sheet
        foreach ($refToDel as $ref) {
            // The name of the sheets is used in the reference, but with small changes
            $p = 0;
            while ( ($p = strpos($WkbTxt, '>'.$ref.'!', $p)) !==false ) {
                $p2 = strpos($WkbTxt, '>', $p+1);
                $p1 = strrpos(substr($WkbTxt, 0, $p), '<');
                if ( ($p1!==false) && ($p2!==false) ) {
                    $WkbTxt = substr_replace($WkbTxt, '', $p1, $p2 - $p1 +1);
                } else {
                    $p++;
                }
            }
            //<pivotCaches><pivotCache cacheId="1" r:id="rId5"/></pivotCaches>
        }

        // can make Excel error, no problem with <definedNames>
        $WkbTxt = str_replace('<pivotCaches></pivotCaches>', '', $WkbTxt);

        // see http://ankushbhatia.wordpress.com/2010/02/11/how-to-delete-a-worksheet-from-excel-using-open-xml-sdk-2-0/
        // Delete the CalcChain file if any. Ms Excel display an error if this file contains bad referenced cells.
        // But since the cells in this file may not all contain sheet id, it is better to delete the whole file (it is optional).
        if (count($refToDel)>0) {
            $this->MsExcel_DeleteFile('calcChain.xml', $WkbRelsTxt, false, $nothing);
        }

        // store the result
        $this->TbsStorePut($this->MsExcel_Sheets_WkbIdx, $WkbTxt);
        if ($WkbRelsTxt!==false) $this->TbsStorePut($this->MsExcel_Sheets_WkbRelsIdx, $WkbRelsTxt);

        $this->TbsSheetCheck();

    }

    public function MsExcel_DeleteFile($file, &$WkbRelsTxt, $rid, &$WkbTxt)
    {
        // Delete the file
        $idx = $this->FileGetIdx('xl/'.$file);
        if ($idx==false) return false;
        $this->FileReplace($idx, false);

        // Delete the Rels file if any
        $relsFile = $this->OpenXML_Rels_GetPath('xl/'.$file);
        $idx = $this->FileGetIdx($relsFile);
        if ($idx!==false) $this->FileReplace($idx, false);

        // Delete in [Content_Types].xml
        $this->OpenXML_CTypesDeletePart('/xl/'.$file, false);

        // Delete in workbook.xml.rels
        if ($WkbRelsTxt!==false) {
            $loc = clsTbsXmlLoc::FindElementHavingAtt($WkbRelsTxt, 'Target="'.$file.'"', 0);
            if ($loc!==false) $loc->ReplaceSrc('');
        }

        // Delete in workbook.xml
        if ($rid!=false) {
            $loc = clsTbsXmlLoc::FindElementHavingAtt($WkbTxt, 'r:id="'.$rid.'"', 0);
            if ($loc!==false) $loc->ReplaceSrc('');
        }

    }

    // Return the list of images in the current sheet
    public function MsExcel_GetDrawingLst()
    {
        $lst = array();

        $dir = '../drawings/';
        $dir_len = strlen($dir);
        $o = $this->OpenXML_Rels_GetObj($this->TBS->OtbsCurrFile, $dir);
        foreach ($o->TargetLst as $t) {
            if ( (substr($t, 0, $dir_len)===$dir) && (substr($t, -4)==='.xml') ) $lst[] = 'xl/drawings/'.substr($t, $dir_len);
        }

        return $lst;
    }

    // Return the list of slides in the Ms Powerpoint presentation
    public function MsPowerpoint_InitSlideLst()
    {
        if ($this->OpenXmlSlideLst!==false) return true;

        $PresFile = 'ppt/presentation.xml';

        $o = $this->OpenXML_Rels_GetObj('ppt/presentation.xml', 'slides/');

        $Txt = $this->FileRead($PresFile);
        if ($Txt===false) return false;

        $p = 0;
        $i = 0;
        $lst = array();
        while ($loc = clsTbsXmlLoc::FindStartTag($Txt, 'p:sldId', $p)) {
            $i++;
            $rid = $loc->GetAttLazy('r:id');
            if ($rid===false) {
                $this->RaiseError("(Init Slide List) attribute 'r:id' is missing for slide #$i in 'ppt/presentation.xml'.");
            } elseif (isset($o->TargetLst[$rid])) {
                $f = 'ppt/'.$o->TargetLst[$rid];
                $lst[] = array('file' => $f, 'idx' => $this->FileGetIdx($f), 'rid' => $rid);
            } else {
                $this->RaiseError("(Init Slide List) Slide corresponding to rid=$rid is not found in the Rels file of 'ppt/presentation.xml'.");
            }
            $p = $loc->PosEnd;
        }

        $this->OpenXmlSlideLst = $lst;

        return true;

    }

    // Clean tags in an Ms Powerpoint slide
    public function MsPowerpoint_Clean(&$Txt)
    {
        $this->MsPowerpoint_CleanRpr($Txt, 'a:rPr');
        $Txt = str_replace('<a:rPr/>', '', $Txt);

        $this->MsPowerpoint_CleanRpr($Txt, 'a:endParaRPr');
        $Txt = str_replace('<a:endParaRPr/>', '', $Txt); // do not delete, can change layout

        // join split elements
        $Txt = str_replace('</a:t><a:t>', '', $Txt);
        $Txt = str_replace('</a:t></a:r><a:r><a:t>', '', $Txt); // this join TBS split tags

        // delete empty elements
        $Txt = str_replace('<a:t></a:t>', '', $Txt);
        $Txt = str_replace('<a:r></a:r>', '', $Txt);
    }

    public function MsPowerpoint_CleanRpr(&$Txt, $elem)
    {
        $pe = 0;
        while (($p=$this->XML_FoundTagStart($Txt, '<'.$elem, $pe))!==false) {
            $pe = $this->XML_DeleteAttributes($Txt, $p, array('noProof', 'lang', 'err', 'smtClean', 'dirty'), array());
        }
    }

    /**
     * Search a string in all slides of the Presentation.
     */
    public function MsPowerpoint_SearchInSlides($str, $returnFirstFound = true)
    {
        // init the list of slides
        $this->MsPowerpoint_InitSlideLst(); // List of slides

        // build the list of files in the expected structure
        $files = array();
        foreach($this->OpenXmlSlideLst as $i=>$s) $files[$i+1] = $s['idx'];

        // search
        $find = $this->TbsSearchInFiles($files, $str, $returnFirstFound);

        return $find;

    }

    public function MsPowerpoint_SlideDebug($nl, $sep, $bull)
    {
        $this->MsPowerpoint_InitSlideLst(); // List of slides

        echo $nl;
        echo $nl.count($this->OpenXmlSlideLst)." slide(s) in the Presentation:";
        echo $nl."-------------------------------";
        foreach ($this->OpenXmlSlideLst as $i => $s) {
            echo $bull."#".($i+1).": ".basename($s['file']);
        }
        if (count($this->OpenXmlSlideLst)==0) echo $bull."(none)";

        // List of charts
        echo $nl;
        echo $nl."Charts found in slides:";
        echo $nl."-------------------------";

        $nbr = 0;
        for ($s=1; $s <= count($this->OpenXmlSlideLst); $s++) {
            $this->OnCommand(OPENTBS_SELECT_SLIDE, $s);
            $ChartLst = $this->OpenXML_ChartGetInfoFromFile($this->TbsCurrIdx);
            foreach ($ChartLst as $i=>$c) {
                $name = ($c['name']===false) ? '(not found)' : $c['name'];
                $title = ($c['title']===false) ? '(not found)' : var_export($c['title'], true);
                echo $bull."slide: $s, chart name: '$name', title: $title";
                if ($c['descr']!==false) echo ", description: ".$c['descr'];
                $nbr++;
            }
        }
        if ($nbr==0) echo $bull."(none)";

    }

    // Actually delete slides in the Presentation
    public function MsPowerpoint_SlideDelete()
    {
        if ( (count($this->OtbsSheetSlidesDelete)==0) && (count($this->OtbsSheetSlidesVisible)==0) ) return;

        $this->MsPowerpoint_InitSlideLst();

        // Edit both XML and REL of file 'presentation.xml'

        $xml_file = 'ppt/presentation.xml';
        $xml_idx = $this->FileGetIdx($xml_file);
        $rel_idx = $this->FileGetIdx($this->OpenXML_Rels_GetPath($xml_file));

        $xml_txt = $this->TbsStoreGet($xml_idx, 'Slide Delete and Display / XML');
        $rel_txt = $this->TbsStoreGet($rel_idx, 'Slide Delete and Display / REL');

        $del_lst = array();
        $del_lst2 = array();
        $first_kept = false;
        foreach ($this->OpenXmlSlideLst as $i=>$s) {
            $ref = 'i:'.($i+1);
            if (isset($this->OtbsSheetSlidesDelete[$ref]) && $this->OtbsSheetSlidesDelete[$ref] ) {

                // the rid may be used several time in the fiel. i.e.: in <p:sldIdLst><p:sldIdLst>, but also in <p:custShow><p:sldLst>
                while ( ($x = clsTbsXmlLoc::FindElementHavingAtt($xml_txt, 'r:id="'.$s['rid'].'"', 0))!==false ) {
                    $x->ReplaceSrc(''); // delete the element
                }

                $x = clsTbsXmlLoc::FindElementHavingAtt($rel_txt, 'Id="'.$s['rid'].'"', 0);
                if ($x!==false) $x->ReplaceSrc(''); // delete the element

                $del_lst[] = $s['file'];
                $del_lst[] = $this->OpenXML_Rels_GetPath($s['file']);
                $del_lst2[] = basename($s['file']);

            } else {
                $first_kept = basename($s['file']);
            }
        }

        $this->TbsStorePut($xml_idx, $xml_txt);
        $this->TbsStorePut($rel_idx, $rel_txt);
        unset($xml_txt, $rel_txt);

        // Delete references in '[Content_Types].xml'
        $idx = $this->FileGetIdx('[Content_Types].xml');
        $txt = $this->TbsStoreGet($idx, 'Slide Delete and Display / Content_Types');
        foreach ($del_lst as $f) {
            $x = clsTbsXmlLoc::FindElementHavingAtt($txt, 'PartName="/'.$f.'"', 0);
            if ($x!==false) $x->ReplaceSrc(''); // delete the element
        }
        $this->TbsStorePut($idx, $txt);

        // Change references in 'viewProps.xml.rels'
        $idx = $this->FileGetIdx('ppt/_rels/viewProps.xml.rels');
        $txt = $this->TbsStoreGet($idx, 'Slide Delete and Display / viewProps');
        $ok = false;
        foreach ($del_lst2 as $f) {
            $z = 'Target="slides/'.$f.'"';
            if (strpos($txt, $z)) {
                if ($first_kept===false) return $this->RaiseError("(Slide Delete and Display) : no slide left to replace the default slide in 'viewProps.xml.rels'.");
                $ok = true;
                $txt = str_replace($z, 'Target="slides/'.$first_kept.'"' , $txt);
            }
        }
        if ($ok) $this->TbsStorePut($idx, $txt);

        // Actually delete the slide files
        foreach ($del_lst as $f) {
            $idx = $this->FileGetIdx($f);
            unset($this->TbsStoreLst[$idx]); // delete the slide from the merging if any
            $this->FileReplace($idx, false);
        }

    }

    // Cleaning tags in MsWord

    public function MsWord_Clean(&$Txt)
    {
        $Txt = str_replace('<w:lastRenderedPageBreak/>', '', $Txt); // faster
        $this->XML_DeleteElements($Txt, array('w:proofErr', 'w:noProof', 'w:lang', 'w:lastRenderedPageBreak'));
        $this->MsWord_CleanSystemBookmarks($Txt);
        $this->MsWord_CleanRsID($Txt);
        $this->MsWord_CleanDuplicatedLayout($Txt);
    }

    public function MsWord_CleanSystemBookmarks(&$Txt)
    {
    // Delete GoBack hidden bookmarks that appear since Office 2010. Example: <w:bookmarkStart w:id="0" w:name="_GoBack"/><w:bookmarkEnd w:id="0"/>

        $x = ' w:name="_GoBack"/><w:bookmarkEnd ';
        $x_len = strlen($x);

        $b = '<w:bookmarkStart ';
        $b_len = strlen($b);

        $nbr_del = 0;

        $p = 0;
        while ( ($p=strpos($Txt, $x, $p))!==false ) {
            $pe = strpos($Txt, '>', $p + $x_len);
            if ($pe===false) return false;
            $pb = strrpos(substr($Txt,0,$p) , '<');
            if ($pb===false) return false;
            if (substr($Txt, $pb, $b_len)===$b) {
                $Txt = substr_replace($Txt, '', $pb, $pe - $pb + 1);
                $p = $pb;
                $nbr_del++;
            } else {
                $p = $pe +1;
            }
        }

        return $nbr_del;

    }

    public function MsWord_CleanRsID(&$Txt)
    {
    /* Delete XML attributes relative to log of user modifications. Returns the number of deleted attributes.
    In order to insert such information, MsWord does split TBS tags with XML elements.
    After such attributes are deleted, we can concatenate duplicated XML elements. */

        $rs_lst = array('w:rsidR', 'w:rsidRPr');

        $nbr_del = 0;
        foreach ($rs_lst as $rs) {

            $rs_att = ' '.$rs.'="';
            $rs_len = strlen($rs_att);

            $p = 0;
            while ($p!==false) {
                // search the attribute
                $ok = false;
                $p = strpos($Txt, $rs_att, $p);
                if ($p!==false) {
                    // attribute found, now seach tag bounds
                    $po = strpos($Txt, '<', $p);
                    $pc = strpos($Txt, '>', $p);
                    if ( ($pc!==false) && ($po!==false) && ($pc<$po) ) { // means that the attribute is actually inside a tag
                        $p2 = strpos($Txt, '"', $p+$rs_len); // position of the delimiter that closes the attribute's value
                        if ( ($p2!==false) && ($p2<$pc) ) {
                            // delete the attribute
                            $Txt = substr_replace($Txt, '', $p, $p2 -$p +1);
                            $ok = true;
                            $nbr_del++;
                        }
                    }
                    if (!$ok) $p = $p + $rs_len;
                }
            }

        }

        // delete empty tags
        $Txt = str_replace('<w:rPr></w:rPr>', '', $Txt);
        $Txt = str_replace('<w:pPr></w:pPr>', '', $Txt);

        return $nbr_del;

    }

    /**
     * MsWord cut the source of the text when a modification is done. This is splitting TBS tags.
     * This function repare the split text by searching and delete duplicated layout.
     * Return the number of deleted dublicates.
     */
    public function MsWord_CleanDuplicatedLayout(&$Txt)
    {
        $wro = '<w:r';
        $wro_len = strlen($wro);

        $wrc = '</w:r';
        $wrc_len = strlen($wrc);

        $wto = '<w:t';
        $wto_len = strlen($wto);

        $wtc = '</w:t';
        $wtc_len = strlen($wtc);

        $preserve = 'xml:space="preserve"';

        $nbr = 0;
        $wro_p = 0;
        while ( ($wro_p=$this->XML_FoundTagStart($Txt,$wro,$wro_p))!==false ) { // next <w:r> tag
            $wto_p = $this->XML_FoundTagStart($Txt,$wto,$wro_p); // next <w:t> tag
            if ($wto_p===false) return false; // error in the structure of the <w:r> element
            $first = true;
            $last_att = '';
            $first_att = '';
            do {
                $ok = false;
                $wtc_p = $this->XML_FoundTagStart($Txt,$wtc,$wto_p); // next </w:t> tag
                if ($wtc_p===false) return false;
                $wrc_p = $this->XML_FoundTagStart($Txt,$wrc,$wro_p); // next </w:r> tag (only to check inclusion)
                if ($wrc_p===false) return false;
                if ( ($wto_p<$wrc_p) && ($wtc_p<$wrc_p) ) { // if the <w:t> is actually included in the <w:r> element
                    if ($first) {
                        // text that is concatened and can be simplified
                        $superflous = '</w:t></w:r>'.substr($Txt, $wro_p, ($wto_p+$wto_len)-$wro_p); // without the last symbol, like: '</w:t></w:r><w:r>....<w:t'
                        $superflous = str_replace('<w:tab/>', '', $superflous); // tabs must not be deleted between parts => they nt be in the superflous string
                        $superflous_len = strlen($superflous);
                        $first = false;
                        $p_first_att = $wto_p+$wto_len;
                        $p =  strpos($Txt, '>', $wto_p);
                        if ($p!==false) $first_att = substr($Txt, $p_first_att, $p-$p_first_att);
                    }
                    // if the <w:r> layout is the same than the next <w:r>, then we join them
                    $p_att = $wtc_p + $superflous_len;
                    $x = substr($Txt, $p_att, 1); // must be ' ' or '>' if the string is the superfluous AND the <w:t> tag has or not attributes
                    if ( (($x===' ') || ($x==='>')) && (substr($Txt, $wtc_p, $superflous_len)===$superflous) ) {
                        $p_end = strpos($Txt, '>', $wtc_p+$superflous_len); //
                        if ($p_end===false) return false; // error in the structure of the <w:t> tag
                        $last_att = substr($Txt,$p_att,$p_end-$p_att);
                        $Txt = substr_replace($Txt, '', $wtc_p, $p_end-$wtc_p+1); // delete superflous part + <w:t> attributes
                        $nbr++;
                        $ok = true;
                    }
                }
            } while ($ok);

            // Recover the 'preserve' attribute if the last join element was having it. We check alo the first one because the attribute must not be twice.
            if ( ($last_att!=='') && (strpos($first_att, $preserve)===false)  && (strpos($last_att, $preserve)!==false) ) {
                $Txt = substr_replace($Txt, ' '.$preserve, $p_first_att, 0);
            }

            $wro_p = $wro_p + $wro_len;

        }

        return $nbr; // number of replacements

    }

    public function MsWord_CleanSpacePreserve(&$Txt)
    {
        // apply xml:space="preserve" by default for the entire document
        // unfotunately, it doesn't work on headers (<w:hdr>) and footers (<w:ftr>)
        $p = $this->XML_FoundTagStart($Txt, '<w:document', 0);
        if ($p===false) return;
        $pe = strpos($Txt, '>', $p);
        $x = substr($Txt, $p, $pe-$p+1);
        if (strpos($x, 'xml:space=')===false) {
            // insert the default value
            $Txt = substr_replace($Txt, ' xml:space="preserve"', $pe, 0);
            $Txt = str_replace('<w:t xml:space="preserve">', '<w:t>', $Txt); // not obligatory but cleanner and save space
        }
    }

    public function MsWord_RenumDocPr()
    {
    /* Renumber attribute "id " of elements <wp:docPr> in order to ensure unicity.
       Such elements are used in objects.
    */

        $file = $this->ExtInfo['main'];
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'Word renume DocPr ids');
        if ($Txt===false) return false;

        $el = '<wp:docPr ';
        $el_len = strlen($el);

        $id = ' id="';
        $id_len = strlen($id);

        $nbr = 0;
        $last_id = 0;

        $p = 0;
        while ($p!==false) {
            // search the element
            $p = strpos($Txt, $el, $p);
            if ($p!==false) {
                // attribute found, now seach tag bounds
                $p = $p + $el_len -1; // don't take the space, it is used for the next search
                $pc = strpos($Txt, '>', $p);
                if ($pc!==false) {
                    $x = substr($Txt, $p, $pc - $p);
                    $pi = strpos($x, $id);
                    if ($pi!==false) {
                        $pi = $pi + $id_len;
                        $pq = strpos($x, '"', $pi);
                        if ($pq!==false) {
                            $i_len = $pq - $pi;
                            $i = intval(substr($x, $pi, $i_len));
                            if ($i>0) { // id="0" is erroneous
                                if ($i>$last_id) {
                                    $last_id = $i; // nothing else to do
                                } else {
                                    $last_id++;
                                    $id_txt = ''.$last_id;
                                    $Txt = substr_replace($Txt, $id_txt, $p + $pi, $i_len);
                                    $nbr++;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($nbr>0) $this->TbsStorePut($idx, $Txt);
        return $nbr;

    }

    // Alias of block: 'tbs:page'
    public function MsWord_GetPage($Tag, $Txt, $Pos, $Forward, $LevelStop)
    {
        // Search the two possible tags for having a page-break
        $loc1 = clsTbsXmlLoc::FindStartTagHavingAtt($Txt, 'w:type="page"', $Pos, $Forward);
        $loc2 = clsTbsXmlLoc::FindStartTag($Txt, 'w:pageBreakBefore', $Pos, $Forward);

        // Define the position of start for the corresponding paragraph
        if ( ($loc1===false) && ($loc2===false) ) {
            if ($Forward) {
                // End of the last paragraph of the document.
                // The <w:p> elements can be embeded, and it can be a single tag if it cnotains no text.
                $loc = clsTbsXmlLoc::FindElement($Txt, 'w:p', strlen($Txt), false);
                if ($loc===false) return false;
                return $loc->PosEnd;
            } else {
                // start of the first paragraph of the document
                $loc = clsTbsXmlLoc::FindStartTag($Txt, 'w:p', 0, true);
                if ($loc===false) return false;
                return $loc->PosBeg;
            }
        }

        // Take care that <w:p> elements can be sef-embeded.
        // 	That's why we assume that there is no page-break in an embeded paragraph while it is useless but possible.
        if ($loc1===false) {
            $s = $loc2->PosBeg;
        } elseif ($loc2===false) {
            $s = $loc1->PosBeg;
        } else {
            if ($Forward) {
                $s = ($loc1->PosBeg < $loc2->PosBeg) ? $loc1->PosBeg : $loc2->PosBeg;
            } else {
                $s = ($loc1->PosBeg > $loc2->PosBeg) ? $loc1->PosBeg : $loc2->PosBeg;
            }
        }
        $loc = clsTbsXmlLoc::FindStartTag($Txt, 'w:p', $s, false);

        $p = $loc->PosBeg;
        if ($Forward) $p--; // if it's forward, we stop the block before the paragraph with page-break
        return $p;

    }

    /**
     * Alias of block: 'tbs:section'
     * In Docx, section-breaks <w:sectPr> can be saved in the last <w:p> of the section, or just after the last <w:p> of the section.
     * In practice, there is always at least one sectin-break and only the last section-break is saved outside the <w:p>.
     */
    public function MsWord_GetSection($Tag, $Txt, $Pos, $Forward, $LevelStop)
    {
        // First we check if the TBS tag is inside a <w:p> and if this <w:p> has a <w:sectPr>
        $case = false;
        $locP = clsTbsXmlLoc::FindStartTag($Txt, 'w:p', $Pos, false);
        if ($locP!==false) {
            $locP->FindEndTag(true);
            if ($locP->PosEnd>$Pos) {
                $src = $locP->GetSrc();
                $loc = clsTbsXmlLoc::FindStartTag($src, 'w:sectPr', 0, true);
                if ($loc!==false) $case = true;
            }
        }

        if ($case && $Forward) return $locP->PosEnd;

        // Look for the next section-break
        $p = ($Forward) ? $locP->PosEnd : $locP->PosBeg;
        $locS = clsTbsXmlLoc::FindStartTag($Txt, 'w:sectPr', $p, $Forward);

        if ($locS===false) {
            if ($Forward) {
                // end of the body
                $p = strpos($Txt, '</w:body>', $Pos);

                return ($p===false) ? false : $p - 1;
            } else {
                // start of the body
                $loc2 = clsTbsXmlLoc::FindStartTag($Txt, 'w:body', 0, true);

                return ($loc2===false) ? false : $loc2->PosEnd + 1;
            }
        }

        // is <w:sectPr> inside a <w:p> ?
        $ewp = '</w:p>';
        $inside = false;
        $p = strpos($Txt, $ewp, $locS->PosBeg);
        if ($p!==false) {
            $loc2 = clsTbsXmlLoc::FindStartTag($Txt, 'w:p', $locS->PosBeg, true);
            if ( ($loc2===false) || ($loc2->PosBeg>$p) ) $inside = true;
        }

        $offset = ($Forward) ? 0 : 1;
        if ($inside) {
            return $p + strlen($ewp) - 1 + $offset;
        } else {
            // not inside
            $locS->FindEndTag();

            return $locS->PosEnd + $offset;
        }

    }

    public function MsWord_DocDebug($nl, $sep, $bull)
    {
        $ChartLst = $this->OpenXML_ChartGetInfoFromFile($this->Ext_GetMainIdx());

        echo $nl;
        echo $nl."Charts found in the body:";
        echo $nl."-------------------------";
        foreach ($ChartLst as $i=>$c) {
            $name = ($c['name']===false) ? '(not found)' : $c['name'];
            $title = ($c['title']===false) ? '(not found)' : var_export($c['title'], true);
            echo $bull."name: '$name', title: $title";
            if ($c['descr']!==false) echo ", description: ".$c['descr'];
        }

    }

    // OpenOffice documents

    public function OpenDoc_ManifestChange($Path, $Type)
    {
    // Set $Type=false in order to mark the the manifest entry to be deleted.
    // Video and sound files are not to be registered in the manifest since the contents is not saved in the document.

        // Initialization
        if ($this->OpenDocManif===false) $this->OpenDocManif = array();

        // We try to found the type of image
        if (($Type==='') && (substr($Path,0,9)==='Pictures/')) {
            $ext = basename($Path);
            $p = strrpos($ext, '.');
            if ($p!==false) {
                $ext = strtolower(substr($ext,$p+1));
                if (isset($this->ExtInfo['pic_ext'][$ext])) $Type = 'image/'.$this->ExtInfo['pic_ext'][$ext];
            }
        }

        $this->OpenDocManif[$Path] = $Type;

    }

    public function OpenDoc_ManifestCommit($Debug)
    {
        // Retrieve the content of the manifest
        $name = 'META-INF/manifest.xml';
        $idx = $this->FileGetIdx($name);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'OpenDocumentFormat');
        if ($Txt===false) return false;

        // Perform all changes
        foreach ($this->OpenDocManif as $Path => $Type) {
            $x = 'manifest:full-path="'.$Path.'"';
            $p = strpos($Txt,$x);
            if ($Type===false) {
                // the entry should be deleted
                if ($p!==false) {
                    $p1 = strrpos(substr($Txt,0,$p), '<');
                    $p2 = strpos($Txt,'>',$p);
                    if (($p1!==false) && ($p2!==false)) $Txt = substr($Txt,0,$p1).substr($Txt,$p2+1);
                }
            } else {
                // the entry should be added
                if ($p===false) {
                    $p = strpos($Txt,'</manifest:manifest>');
                    if ($p!==false) {
                        $x = ' <manifest:file-entry manifest:media-type="'.$Type.'" '.$x.'/>'."\n";
                        $Txt = substr_replace($Txt, $x, $p, 0);
                    }
                }
            }
        }

        // Save changes (no need to save it in the park because this fct is called after merging)
        $this->FileReplace($idx, $Txt);

        if ($Debug) $this->DebugLst[$name] = $Txt;

    }

    public function OpenDoc_ChangeCellType(&$Txt, &$Loc, $Ope, $IsMerging, &$Value)
    {
    // change the type of a cell in an ODS file

        $Loc->PrmLst['cellok'] = true; // avoid the field to be processed twice

        if ($Ope==='odsStr') return true;

        static $OpeLst = array(
            'tbs:num'=>'float',
            'tbs:percent'=>'percentage',
            'tbs:curr'=>'currency',
            'tbs:bool'=>'boolean',
            'tbs:date'=>'date',
            'tbs:time'=>'time',
            // for compatibility
            'odsNum'=>'float',
            'odsPercent'=>'percentage',
            'odsCurr'=>'currency',
            'odsBool'=>'boolean',
            'odsDate'=>'date',
            'odsTime'=>'time',
        );
        $AttStr = 'office:value-type="string"';
        $AttStr_len = strlen($AttStr);

        if (!isset($OpeLst[$Ope])) return false;

        $t0 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'table:table-cell', true, $Loc->PosBeg, false, true);
        if ($t0===false) return false; // error in the XML structure

        $te = strpos($Txt, '>', $t0);
        if ( ($te===false) || ($te>$Loc->PosBeg) ) return false; // error in the XML structure

        $len = $te - $t0 + 1;
        $tag = substr($Txt, $t0, $len);

        $p = strpos($tag, $AttStr);
        if ($p===false) return false; // error: the cell was expected to have a string contents since it contains a TBS tag.

        // replace the current string with blanck chars
        $len = $Loc->PosEnd - $Loc->PosBeg + 1;
        $Txt = substr_replace($Txt, str_repeat(' ',$len), $Loc->PosBeg, $len);

        // prepare special formating for the value
        $type = $OpeLst[$Ope];
        $att_new = 'office:value-type="'.$type.'"';
        $newfrm = false;
        switch ($type) {
        case 'float':      $att_new .= ' office:value="[]"'; break;
        case 'percentage': $att_new .= ' office:value="[]"'; break;
        case 'currency':   $att_new .= ' office:value="[]"'; if (isset($Loc->PrmLst['currency'])) $att_new .= ' office:currency="'.$Loc->PrmLst['currency'].'"'; break;
        case 'boolean':    $att_new .= ' office:boolean-value="[]"'; break;
        case 'date':       $att_new .= ' office:date-value="[]"'; $newfrm = 'yyyy-mm-ddThh:nn:ss'; break;
        case 'time';       $att_new .= ' office:time-value="[]"'; $newfrm = '"PT"hh"H"nn"M"ss"S"'; break;
        }

        // replace the sring attribute with the new attribute
        //$diff = strlen($att_new) - $AttStr_len;
        $p_att = $t0 + $p;
        $p_fld = $p_att + strpos($att_new, '['); // new position of the fields in $Txt
        $Txt = substr_replace($Txt, $att_new, $p_att, $AttStr_len);

        // move the TBS field
        $Loc->PosBeg = $p_fld;
        $Loc->PosEnd = $p_fld +1;

        if ($IsMerging) {
            // the field is currently being merged
            if ($type==='boolean') {
                if ($Value) {
                    $Value = 'true';
                } else {
                    $Value = 'false';
                }
            } elseif ($newfrm!==false) {
                $prm = array('frm'=>$newfrm);
                $Value = $this->TBS->meth_Misc_Format($Value,$prm);
            }
            $Loc->ConvStr = false;
            $Loc->ConvProtect = false;
        } else {
            if ($newfrm!==false) $Loc->PrmLst['frm'] = $newfrm;
        }

    }

    public function OpenDoc_SheetSlides_Init($sheet, $force = false)
    {
        if (($this->OpenDoc_SheetSlides!==false) && (!$force) ) return;

        $this->OpenDoc_SheetSlides = array();     // sheet/slide info sorted by location

        $idx = $this->FileGetIdx($this->ExtInfo['main']);
        if ($idx===false) return;
        $Txt = $this->TbsStoreGet($idx, 'Sheet/Slide Info');
        if ($Txt===false) return false;
        if ($this->LastReadNotStored) $this->TbsStorePut($idx, $Txt);
        $this->OpenDoc_SheetSlides_FileId = $idx;

        $tag = ($sheet) ? 'table:table' : 'draw:page';

        // scann sheet/slide list
        $p = 0;
        $idx = 0;
        while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, $tag, true, $p, true, false, true, true) ) {
            $this->OpenDoc_SheetSlides[$idx] = $loc;
            $idx++;
            $p = $loc->PosEnd;
        }

    }

    // Actally delete hide or display Sheets and Slides in a ODS or ODP
    public function OpenDoc_SheetSlides_DeleteAndDisplay($sheet)
    {
        if ( (count($this->OtbsSheetSlidesDelete)==0) && (count($this->OtbsSheetSlidesVisible)==0) ) return;

        $this->OpenDoc_SheetSlides_Init($sheet, true);
        $Txt = $this->TbsStoreGet($this->OpenDoc_SheetSlides_FileId, 'Sheet Delete and Display');

        if ($sheet) {
            // Sheet
            $tag_close = '</table:table>';
            $att_name = 'table:name';
            $att_style = 'table:style-name';
            $att_display = 'table:display';
            $yes_display = 'true';
            $not_display = 'false';
            $tag_property = 'style:table-properties';
        } else {
            // Slide
            $tag_close = '</draw:page>';
            $att_name = 'draw:name';
            $att_style = 'draw:style-name';
            $att_display = 'presentation:visibility';
            $yes_display = 'visible';
            $not_display = 'hidden';
            $tag_property = 'style:drawing-page-properties';
        }
        $tag_close_len = strlen($tag_close);

        $styles_to_edit = array();
        // process sheet in rever order of their positions
        for ($idx = count($this->OpenDoc_SheetSlides) - 1; $idx>=0; $idx--) {
            $loc = $this->OpenDoc_SheetSlides[$idx];
            $id = 'i:'.($idx + 1);
            $name = 'n:'.$loc->PrmLst[$att_name];
            if ( isset($this->OtbsSheetSlidesDelete[$name]) || isset($this->OtbsSheetSlidesDelete[$id]) ) {
                // Delete the sheet
                $p = strpos($Txt, $tag_close, $loc->PosEnd);
                if ($p===false) return; // XML error
                $Txt = substr_replace($Txt, '', $loc->PosBeg, $p + $tag_close_len - $loc->PosBeg);
                unset($this->OtbsSheetSlidesDelete[$name]);
                unset($this->OtbsSheetSlidesDelete[$id]);
                unset($this->OtbsSheetSlidesVisible[$name]);
                unset($this->OtbsSheetSlidesVisible[$id]);
            } elseif ( isset($this->OtbsSheetSlidesVisible[$name]) || isset($this->OtbsSheetSlidesVisible[$id]) ) {
                // Hide or dispay the sheet
                $visible = (isset($this->OtbsSheetSlidesVisible[$name])) ? $this->OtbsSheetSlidesVisible[$name] : $this->OtbsSheetSlidesVisible[$id];
                $visible = ($visible) ? $yes_display : $not_display;
                if (isset($loc->PrmLst[$att_style])) {
                    $style = $loc->PrmLst[$att_style];
                    $new = $style.'_tbs_'.$visible;
                    if (!isset($styles_to_edit[$style])) $styles_to_edit[$style] = array();
                    $styles_to_edit[$style][$visible] = $new; // mark the style to be edited
                    $pi = $loc->PrmPos[$att_style];
                    $Txt = substr_replace($Txt, $pi[4].$new.$pi[4], $pi[2], $pi[3]-$pi[2]);
                }
                unset($this->OtbsSheetSlidesVisible[$name]);
                unset($this->OtbsSheetSlidesVisible[$id]);
            }
        }

        // process styles to edit
        if (count($styles_to_edit)>0) {
            $tag_close = '</style:style>';
            $tag_close_len = strlen($tag_close);
            $p = 0;
            while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, 'style:style', true, $p, true, false, true, false) ) {
                $p = $loc->PosEnd;
                if (isset($loc->PrmLst['style:name'])) {
                    $name = $loc->PrmLst['style:name'];
                    if (isset($styles_to_edit[$name])) {
                        // retrieve the full source of the <style:style> element
                        $p = strpos($Txt, $tag_close, $p);
                        if ($p===false) return; // bug in the XML contents
                        $p = $p + $tag_close_len;
                        $src = substr($Txt, $loc->PosBeg, $p - $loc->PosBeg);
                        // add the attribute, if missing
                        if (strpos($src, ' '.$att_display.'="')===false)  $src = str_replace('<'.$tag_property.' ', '<'.$tag_property.' '.$att_display.'="'.$yes_display.'" ', $src);
                        // add new styles
                        foreach ($styles_to_edit[$name] as $visible => $newName) {
                            $not = ($visible===$not_display) ? $yes_display : $not_display;
                            $src2 = str_replace(' style:name="'.$name.'"', ' style:name="'.$newName.'"', $src);
                            $src2 = str_replace(' '.$att_display.'="'.$not.'"', ' '.$att_display.'="'.$visible.'"', $src2);
                            $Txt = substr_replace($Txt, $src2, $loc->PosBeg, 0);
                            $p = $p + strlen($src2);
                        }
                    }
                }
            }

        }

        // store the result
        $this->TbsStorePut($this->OpenDoc_SheetSlides_FileId, $Txt);

        $this->TbsSheetCheck();

    }

    public function OpenDoc_SheetSlides_Debug($sheet, $nl, $sep, $bull)
    {
        $this->OpenDoc_SheetSlides_Init($sheet);

        $text = ($sheet) ? "Sheets in the Workbook" : "Slides in the Presentation";
        $att = ($sheet) ? 'table:name' : 'draw:name';

        echo $nl;
        echo $nl.$text.":";
        echo $nl."-----------------------";
        foreach ($this->OpenDoc_SheetSlides as $idx => $loc) {
            $name = str_replace(array('&amp;','&quot;','&lt;','&gt;'), array('&','"','<','>'), $loc->PrmLst[$att]);
            echo $bull."id: ".($idx+1).", name: [".$name."]";
        }

    }

    public function OpenDoc_StylesInit()
    {
        if ($this->OpenDoc_Styles!==false) return;

        $this->OpenDoc_Styles = array();     // sheet info sorted by location

        $Styles = array();

        // Read styles in 'styles.xml'
        $idx = $this->FileGetIdx('styles.xml');
        if ($idx!==false) {
            $Txt = $this->TbsStoreGet($idx, 'Style Init styles.xml');
            if ($Txt==!false) $this->OpenDoc_StylesFeed($Styles, $Txt);
        }

        // Read styles in 'content.xml'
        $idx = $this->FileGetIdx('content.xml');
        if ($idx!==false) {
            $Txt = $this->TbsStoreGet($idx, 'Style Init content.xml');
            if ($Txt!==false) $this->OpenDoc_StylesFeed($Styles, $Txt);
        }

        // define childs
        foreach ($Styles as $n => $s) {
            if ( ($s->parentName!==false) && isset($Styles[$s->parentName]) ) $Styles[$s->parentName]->childs[$s->name] = &$s;
        }

        // propagate page-break property to alla childs
        $this->OpenDoc_StylesPropagate($Styles);

        $this->OpenDoc_Styles = $Styles;

    }

    // Feed $Styles with styles found in $Txt
    public function OpenDoc_StylesFeed(&$Styles, $Txt)
    {
        $p = 0;
        while ($loc = clsTbsXmlLoc::FindElement($Txt, 'style:style', $p)) {
            unset($o);
            $o = (object) null;
            $o->name = $loc->GetAttLazy('style:name');
            $o->parentName = $loc->GetAttLazy('style:parent-style-name');
            $o->childs = array();
            $o->pbreak = false;
            $o->ctrl = false;
            $src = $loc->GetSrc();
            if (strpos($src, ' fo:break-before="page"')!==false) $o->pbreak = 'before';
            if (strpos($src, ' fo:break-after="page"')!==false) $o->pbreak = 'after';
            if ($o->name!==false) $Styles[$o->name] = $o;
            $p = $loc->PosEnd;
        }
    }

    public function OpenDoc_StylesPropagate(&$Styles)
    {
        foreach ($Styles as $i => $s) {
            if (!$s->ctrl) {
                $s->ctrl = true; // avoid circular reference
                if ($s->pbreak!==false) {
                    foreach ($s->childs as $j => $c) {
                        if ($c->pbreak!==false) $c->pbreak = $s->pbreak;
                        $this->OpenDoc_StylesPropagate($c);
                    }
                }
                $s->childs = false;
            }
        }
    }

    // TBS Block Alias for pages
    public function OpenDoc_GetPage($Tag, $Txt, $Pos, $Forward, $LevelStop)
    {
        $this->OpenDoc_StylesInit();

        $p = $Pos;

        while (	($loc = clsTbsXmlLoc::FindStartTagHavingAtt($Txt, 'text:style-name', $p, $Forward))!==false) {

            $style = $loc->GetAttLazy('text:style-name');

            if ( ($style!==false) && isset($this->OpenDoc_Styles[$style]) ) {
                $pbreak = $this->OpenDoc_Styles[$style]->pbreak;
                if ($pbreak!==false) {
                    if ($Forward) {
                        // Forward
                        if ($pbreak==='before') {
                            return $loc->PosBeg -1; // note that the page-break is not in the block
                        } else {
                            $loc->FindEndTag();

                            return $loc->PosEnd;
                        }
                    } else {
                        // Backward
                        if ($pbreak==='before') {
                            return $loc->PosBeg;
                        } else {
                            $loc->FindEndTag();

                            return $loc->PosEnd+1; // note that the page-break is not in the block
                        }
                    }
                }
            }

            $p = ($Forward) ? $loc->PosEnd : $loc->PosBeg;

        }

        // If we are here, then no tag is found, we return the boud of the main element
        if ($Forward) {
            $p = strpos($Txt, '</office:text');
            if ($p===false) return false;
            return $p-1;
        } else {
            $loc = clsTbsXmlLoc::FindStartTag($Txt, 'office:text', $Pos, false);
            if ($loc===false) return false;
            return $loc->PosEnd + 1;
        }

    }

    // TBS Block Alias for draws
    public function OpenDoc_GetDraw($Tag, $Txt, $Pos, $Forward, $LevelStop)
    {
        return $this->XML_BlockAlias_Prefix('draw:', $Txt, $Pos, $Forward, $LevelStop);
    }

    public function OpenDoc_ChartChangeSeries($ChartRef, $SeriesNameOrNum, $NewValues, $NewLegend=false)
    {
        if ($this->OpenDocCharts===false) $this->OpenDoc_ChartInit();

        // Find the chart
        if (is_numeric($ChartRef)) {
            $ChartCaption = 'number '.$ChartRef;
            $idx = intval($ChartRef) -1;
            if (!isset($this->OpenDocCharts[$idx])) return $this->RaiseError("(ChartChangeSeries) : unable to found the chart $ChartCaption.");
        } else {
            $ChartCaption = 'with title "'.$ChartRef.'"';
            $idx = false;
            $x = htmlspecialchars($ChartRef);
            foreach ($this->OpenDocCharts as $i=>$c) {
                if ($c['title']==$x) $idx = $i;
            }
            if ($idx===false) return $this->RaiseError("(ChartChangeSeries) : unable to found the chart $ChartCaption.");
        }
        $this->_ChartCaption = $ChartCaption; // for error messages

        // Retrieve chart information
        $chart = &$this->OpenDocCharts[$idx];
        if ($chart['to_clear']) $this->OpenDoc_ChartClear($chart);

        // Retrieve the XML of the data
        $data_idx = $this->FileGetIdx($chart['href'].'/content.xml');
        if ($data_idx===false) return $this->RaiseError("(ChartChangeSeries) : unable to found the data in the chart $ChartCaption.");
        $Txt = $this->TbsStoreGet($data_idx, 'OpenDoc_ChartChangeSeries');

        // Found all chart series
        if (!isset($chart['series'])) {
            $ok = $this->OpenDoc_ChartFindSeries($chart, $Txt);
            if (!$ok) return;
        }
        $series = &$chart['series'];

        // Found the asked series
        $s_info = false;
        if (is_numeric($SeriesNameOrNum)) {
            $s_caption = 'number '.$SeriesNameOrNum;
            $idx = $SeriesNameOrNum -1;
            if (isset($series[$idx])) $s_info = &$series[$idx];
        } else {
            $s_caption = '"'.$SeriesNameOrNum.'"';
            foreach ($series as $idx => $s) {
                if ( ($s_info===false) && ($s['name']==$SeriesNameOrNum) ) $s_info = &$series[$idx];
            }
        }
        if ($s_info===false) return $this->RaiseError("(ChartChangeSeries) : unable to found the series $s_caption in the chart ".$this->_ChartCaption.".");

        if ($NewLegend!==false) $this->OpenDoc_ChartRenameSeries($Txt, $s_info, $NewLegend);

        // simplified variables
        $col_cat  = $chart['col_cat']; // column Category (always 1)
        $col_nbr  = $chart['col_nbr']; // number of columns
        $s_col    = $s_info['cols'][0];  // first column of the series
        $s_col_nbr = count($s_info['cols']);
        $s_colend  = $s_col + $s_col_nbr - 1;  // last column of the series
        $s_use_cat = (count($s_info['cols'])==1); // true is the series uses the column Category

        // Force syntax of data
        if (!is_array($NewValues)) {
            $data = array();
            if ($NewValues===false) $this->OpenDoc_ChartDelSeries($Txt, $s_info);
        } elseif ( $s_use_cat && isset($NewValues[0]) && isset($NewValues[1]) && is_array($NewValues[0]) && is_array($NewValues[1]) ) {
            // syntax 2: $NewValues = array( array('cat1','cat2',...), array(val1,val2,...) );
            $k = $NewValues[0];
            $v = $NewValues[1];
            $data = array();
            foreach($k as $i=>$x) $data[$x] = isset($v[$i]) ? $v[$i] : false;
            unset($k, $v);
        } else {
            // syntax 1: $NewValues = array( 'cat1'=>val1, 'cat2'=>val2, ... );
            $data = $NewValues;
        }
        unset($NewValues);

        // Scann all rows for changing cells
        $elData = clsTbsXmlLoc::FindElement($Txt, 'table:table-rows', 0);
        $p_row = 0;
        while (($elRow=clsTbsXmlLoc::FindElement($elData, 'table:table-row', $p_row))!==false) {
            $p_cell = 0;
            $category = false;
            $data_r = false;
            for ($i=1; $i<=$s_colend; $i++) {
                if ($elCell = clsTbsXmlLoc::FindElement($elRow, 'table:table-cell', $p_cell)) {
                    if ($i==$col_cat) {
                        // Category
                        if ($elP = clsTbsXmlLoc::FindElement($elCell, 'text:p', 0)) {
                            $category = $elP->GetInnerSrc();
                        }
                    } elseif ($i>=$s_col) {
                        // Change the value
                        $x = 'NaN'; // default value
                        if ($s_use_cat) {
                            if ( ($category!==false) && isset($data[$category]) ) {
                                $x = $data[$category];
                                unset($data[$category]); // delete the category in order to keep only unused
                            }
                        } else {
                            $val_idx = $i - $s_col;
                            if ($data_r===false) $data_r = array_shift($data); // (may return null) delete the row in order to keep only unused
                            if ( (!is_null($data_r)) && isset($data_r[$val_idx])) $x = $data_r[$val_idx];
                        }
                        if ( ($x===false) || is_null($x) ) $x = 'NaN';
                        $elCell->ReplaceAtt('office:value', $x);
                        // Delete the cached legend
                        if ($elP = clsTbsXmlLoc::FindElement($elCell, 'text:p', 0)) {
                            $elP->ReplaceSrc('');
                            $elP->UpdateParent(); // update $elCell source
                        }
                        $elCell->UpdateParent(); // update $elRow source
                    }
                    $p_cell = $elCell->PosEnd;
                } else {
                    $i = $s_colend+1; // ends the loops
                }
            }
            $elRow->UpdateParent(); // update $elData source
            $p_row = $elRow->PosEnd;
        }

        // Add unused data
        $x = '';
        $x_nan = '<table:table-cell office:value-type="float" office:value="NaN"></table:table-cell>';
        foreach ($data as $cat=>$val) {
            $x .= '<table:table-row>';
            if ($s_use_cat) $val = array($val);
            for ($i=1; $i<=$col_nbr; $i++) {
                if ( ($s_col<=$i) && ($i<=$s_colend) ) {
                    $val_idx = $i - $s_col;
                    if (isset($val[$val_idx])) {
                        $x .= '<table:table-cell office:value-type="float" office:value="'.$val[$val_idx].'"></table:table-cell>';
                    } else {
                        $x .= $x_nan;
                    }
                } else {
                    if ($s_use_cat && ($i==$col_cat) ) {
                        $x .= '<table:table-cell office:value-type="string"><text:p>'.htmlspecialchars($cat).'</text:p></table:table-cell>';
                    } else {
                        $x .= $x_nan;
                    }
                }
            }
            $x .= '</table:table-row>';
        }
        $p = strpos($Txt, '</table:table-rows>', $elData->PosBeg);
        if ($x!=='') $Txt = substr_replace($Txt, $x, $p, 0);

        // Save the result
        $this->TbsStorePut($data_idx, $Txt);

    }

    /**
     * Look for all chart in the document, and store information.
     */
    public function OpenDoc_ChartInit()
    {
        $this->OpenDocCharts = array();

        $idx = $this->Ext_GetMainIdx();
        $Txt = $this->TbsStoreGet($idx, 'OpenDoc_ChartInit');

        $p = 0;
        while ($drEl = clsTbsXmlLoc::FindElement($Txt, 'draw:frame', $p)) {

            $src = $drEl->GetInnerSrc();
            $objEl = clsTbsXmlLoc::FindStartTag($src, 'draw:object', 0);

            if ($objEl) { // Picture have <draw:frame> without <draw:object>
                $href = $objEl->GetAttLazy('xlink:href'); // example "./Object 1"
                if ($href) {

                    $imgEl = clsTbsXmlLoc::FindElement($src, 'draw:image', 0);
                    $img_href = ($imgEl) ? $imgEl->GetAttLazy('xlink:href') : false; // "./ObjectReplacements/Object 1"
                    $img_src = ($imgEl) ? $imgEl->GetSrc('xlink:href') : false;

                    $titEl = clsTbsXmlLoc::FindElement($src, 'svg:title', 0);
                    $title = ($titEl) ? $titEl->GetInnerSrc() : '';

                    if (substr($href,0,2)=='./') $href = substr($href, 2);
                    if ( is_string($img_href) && (substr($img_href,0,2)=='./') ) $img_href = substr($img_href, 2);
                    $this->OpenDocCharts[] = array('href'=>$href, 'title'=>$title, 'img_href'=>$img_href, 'img_src'=>$img_src, 'to_clear'=> ($img_href!==false) );

                }
            }
            $p = $drEl->PosEnd;
        }


    }

    public function OpenDoc_ChartClear(&$chart)
    {
        $chart['to_clear'] = false;

        // Delete the file in the archive
        $this->FileReplace($chart['img_href'], false);

        // Delete the element in the main file
        $main = $this->Ext_GetMainIdx();
        $Txt = $this->TbsStoreGet($main, 'OpenDoc_ChartClear');
        $Txt = str_replace($chart['img_src'], '', $Txt);
        $this->TbsStorePut($main, $Txt);

        // Delete the element in the Manifest file
        $manifest = $this->FileGetIdx('META-INF/manifest.xml');
        if ($manifest!==false) {
            $Txt = $this->TbsStoreGet($manifest, 'OpenDoc_ChartClear');
            $el = clsTbsXmlLoc::FindStartTagHavingAtt($Txt, 'manifest:full-path="'.$chart['img_href']."'", 0);
            if ($el) {
                $el->ReplaceSrc('');
                $this->TbsStorePut($manifest, $Txt);
            }
        }

    }

    /**
     * Find and save informations abouts all series in the chart.
     */
    public function OpenDoc_ChartFindSeries(&$chart, $Txt)
    {
        // Find series declarations
        $p = 0;
        $s_idx = 0;
        $series = array();
        $cols = array(); // all columns attached to a series
        $cols_name = array();
        while ($elSeries = clsTbsXmlLoc::FindElement($Txt, 'chart:series', $p)) {
            $s_cols = array();
            // Column of main value
            $col = $this->OpenDoc_ChartFindCol($cols, $elSeries, 'chart:values-cell-range-address', $s_idx);
            $s_cols[$col] = true;
            // Column of series's name
            $col_name = $this->OpenDoc_ChartFindCol($cols, $elSeries, 'chart:label-cell-address', $s_idx);
            // Columns for other values
            $src = $elSeries->GetInnerSrc();
            $p2 = 0;
            while ($elDom = clsTbsXmlLoc::FindStartTag($src, 'chart:domain', $p2)) {
                $col = $this->OpenDoc_ChartFindCol($cols, $elDom, 'table:cell-range-address', $s_idx);
                $s_cols[$col] = true;
                $p2 = $elDom->PosEnd;
            }
            // rearrange col numbers
            ksort($s_cols);
            $s_cols = array_keys($s_cols); // nedded for havinf first col on index 0
            // Attribute to re-find the series
            $ref = $elSeries->GetAttLazy('chart:label-cell-address');
            // Add the series
            $series[$s_idx] = array('name'=>false, 'col_name'=>$col_name, 'cols'=>$s_cols, 'ref'=>$ref);
            $cols_name[$col_name] = $s_idx;
            $p = $elSeries->PosEnd;
            $s_idx++;
        }
        $chart['cols'] = $cols;

        // Column of categories
        $col_cat = false;
        $elCat = clsTbsXmlLoc::FindStartTag($Txt, 'chart:categories', 0);
        if ($elCat!==false) {
            $att = $elCat->GetAttLazy('table:cell-range-address');
            $col_cat = $this->Misc_ColNum($att, true); // the column of categories is always #1
        }
        $chart['col_cat'] = $col_cat;


        // Brows headers columns
        $elHeaders = clsTbsXmlLoc::FindElement($Txt, 'table:table-header-rows', 0);
        if ($elHeaders===false) return $this->RaiseError("(ChartFindSeries) : unable to found the series names in the chart ".$this->_ChartCaption.".");
        $p = 0;
        $col_num = 0;
        while (($elCell=clsTbsXmlLoc::FindElement($elHeaders, 'table:table-cell', $p))!==false) {
            $col_num++;
            if (isset($cols_name[$col_num])) {
                $elP = clsTbsXmlLoc::FindElement($elCell, 'text:p', 0);
                $name = ($elP===false) ? '' : $elP->GetInnerSrc();
                $s_idx = $cols_name[$col_num];
                $series[$s_idx]['name'] = $name;
            }
            $p = $elCell->PosEnd;
        }
        $chart['series'] = $series;
        $chart['col_nbr'] = $col_num;

        return true;

    }

    public function OpenDoc_ChartFindCol(&$cols, &$el, $att, $s_idx)
    {
        $x = $el->GetAttLazy($att);
        if ($x===false) return $this->RaiseError("(ChartFindCol) : unable to find cell references for series number #".($idx+1)." in the chart ".$this->_ChartCaption.".");
        $c = $this->Misc_ColNum($x, true);
        if ($s_idx!==false) $cols[$c] = $s_idx;
        return $c;
    }

    public function OpenDoc_ChartDelSeries(&$Txt, &$series)
    {
        $att = 'chart:label-cell-address="'.$series['ref'].'"';
        $elSeries = clsTbsXmlLoc::FindElementHavingAtt($Txt, $att, 0);

        if ($elSeries!==false) $elSeries->ReplaceSrc('');

    }

    public function OpenDoc_ChartRenameSeries(&$Txt, &$series, $NewName)
    {
        $NewName = htmlspecialchars($NewName);
        $col_name = $series['col_name'];

        $el = clsTbsXmlLoc::FindStartTag($Txt, 'table:table-header-rows', 0);
        $el = clsTbsXmlLoc::FindStartTag($Txt, 'table:table-row', $el->PosEnd);
        for ($i=1; $i<$col_name; $i++) $el = clsTbsXmlLoc::FindStartTag($Txt, 'table:table-cell', $el->PosEnd);
        $elCell = clsTbsXmlLoc::FindElement($Txt, 'table:table-cell', $el->PosEnd);

        $elP = clsTbsXmlLoc::FindElement($elCell, 'text:p', 0);
        if ($elP===false) {
            $elCell->ReplaceInnerSrc($elCell->InnerSrc.'<text:p>'.$NewName.'</text:p>');
        } else {
            if ($elP->SelfClosing) {
                $elP->ReplaceSrc('<text:p>'.$NewName.'</text:p>');
            } else {
                $elP->ReplaceInnerSrc($NewName);
            }
            $elP->UpdateParent();
        }

    }

    public function OpenDoc_ChartDebug($nl, $sep, $bull)
    {
        if ($this->OpenDocCharts===false) $this->OpenDoc_ChartInit();

        $ChartLst = $this->OpenDocCharts;

        echo $nl;
        echo $nl."Charts found in the contents:";
        echo $nl."-----------------------------";
        foreach ($ChartLst as $i=>$c) {
            $title = ($c['title']===false) ? '(not found)' : var_export($c['title'], true);
            echo $bull."title: $title";
        }
        if (count($ChartLst)==0) echo $bull."(none)";

    }

}
