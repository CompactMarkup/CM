<?php

const _INDEX     = '_index.cm';   // index files
const _INDEX_OUT = 'index.html';  // output w/o '_' (gh-pages Jenkins!)
const _PROLOG    = '_prolog';     // prolog files
const _TOC_JS    = 'toc_.js';     // precompiled TOC

class Compiler {
  protected $pages;
  protected $outdir;

  public function __construct ($pages, $outdir) {
    $this->pages  = $pages;
    $this->outdir = $outdir;
    $this->isDebug = isset($_SERVER['SERVER_PORT']) && (8000 <= $_SERVER['SERVER_PORT']);
  }

  protected static function error ($msg) {
    throw new Exception($msg);
  }

  /* The compiled toc is saved in (js) book.toc as:
    lst: #   -> [id, file, title]
    ids: id  -> #
    sec: #   -> # (to its _INDEX; _INDEX to itself)
    pnt: #   -> # (to _INDEX of parent dir, top -> null)
    fil: file-> #
    ... and dynamically added by js ...
    mi:  # -> menuitem
    div: # -> div
  */
  private $lst = '', $cls = '', $ids = '', $sec = '', $pnt = '', $fil = '';

  public function compile () {
    $tocjs = $this->outdir._TOC_JS;

    try {
      $i = -1;
      $this->traverse(null, '', $i);
      file_put_contents($tocjs,
        "book.toc = {lst:[$this->lst], cls:[$this->cls], ids:{{$this->ids}}, sec:{{$this->sec}}, pnt:{{$this->pnt}}, fil:{{$this->fil}}};",
        LOCK_EX);
    } catch (Exception $e) {
      error_log(' ** ' . $e->getMessage() . ' **');
    }
  }

  // page ids must be unique
  private $haveIds = [];
  private function checkUniqueId ($id) {
    if (@$this->haveIds[$id])
      self::error('duplicate id: ' . $id);
    $this->haveIds[$id] = true;
  }

  private function getTocLine ($fpath) {
    if (!($f = @fopen($fpath, 'r')))
      self::error('bad file: ' . $fpath);

    $l = fgets($f);
    if ('@toc' == substr($l, 0, 4))
      $l = substr($l, 4);
    else if ('//toc' == substr($l, 0, 5))
      $l = substr($l, 5);
    else
      self::error('bad @toc: ' . $fpath);

    if (false !== ($pos = strpos($l, '#'))) // cut off comment
      $l = substr($l, 0, $pos);
    $l = trim($l);
    @list($id, $cl, $title) = array_map('trim', explode(';', $l));

    if (!$id || !$title)
      self::error('bad @toc: ' . $fpath);
    return [$id, $cl, $title];
  }

  private function traverse ($pntNo, $path, &$i, $level = 0) {
    $cwd = $this->pages.$path;

    list($id, $cl, $title) = $this->getTocLine($cwd._INDEX);
    self::checkUniqueId($id);

    $indNo = ++$i;

    $pf = $this->processFile($title, $path, _INDEX, _INDEX_OUT, $level);
    $this->lst .= "['$id','$pf','".htmlentities($title)."'],";
    $this->cls .= "'$cl',";
    $this->ids .= "'$id':$i,";
    $this->sec .= "$i:$indNo,";
    $this->pnt .= null !== $pntNo ? "$i:$pntNo," : "$i:null,";
    $this->fil .= "'$pf':$i,";


    // read dir
    if (!($dir = opendir($cwd)))
      self::error('opendir ' . $cwd);

    $entries = [];
    while (false !== ($fd = readdir($dir)))
      if (is_numeric($fd[0]))
        $entries[] = $fd;

    sort($entries);

    foreach ($entries as $fd) {
      if (is_dir($cwd.$fd)) {
        $this->traverse($indNo, $path.$fd.'/', $i, $level + 1);
      } else {
        list($id, $cl, $title) = $this->getTocLine($cwd.$fd);
        ++$i;
        self::checkUniqueId($id);
        $pf = $this->processFile($title, $path, $fd, substr($fd, 0, -3) . '.html', $level);

        $this->lst .= "['$id','$pf','".htmlentities($title)."'],";
        $this->cls .= "'$cl',";
        $this->ids .= "'$id':$i,";
        $this->sec .= "$i:$indNo,";
        $this->pnt .= "$i:$indNo,";
        $this->fil .= "'$pf':$i,";

      }
    }
  }

  function processFile ($title, $relPath, $fileIn, $fileOut, $level) {
    return $relPath.$fileIn;
  }
}

class TocCompiler extends Compiler {
  public function __construct ($pages) {
    Compiler::__construct($pages, $pages);
  }

  // scan the directory tree, get the max. change time of any _INDEX
  private function maxChangeTime ($path) {
    $time = 0;

    if ($d = @opendir($this->pages.$path)) {
      while (false !== ($f = readdir($d))) {
        if ('.' === $f || '..' === $f)
          continue;
        if (is_dir($this->pages.($pf = $path.$f)))
          $time = max($time, self::maxChangeTime($pf.'/'));
        else
          $time = max($time, filectime($this->pages.$pf));
      }
    }
    return $time;
  }

  public function compile () {
    // on localhost (local editing), check all files' change time
    // otherwise (in prodution) check only index.php
    $maxChangeTime = $this->isDebug
      ? self::maxChangeTime('') : @filectime('index.php');

    // does toc need recompiling?
    $tocjs = $this->pages._TOC_JS;
    $tocTime = @filectime($tocjs);
    if (0 == $tocTime || $tocTime < $maxChangeTime)
      Compiler::compile();
  }
}

class StaticCompiler extends Compiler {
  public function __construct ($pages, $outdir) {
    Compiler::__construct($pages, $outdir);
  }

  function saveHtml ($title, $relPath, $outFile, $tx, $level) {
    $tx = htmlentities($tx);
    $toRoot = str_repeat('../', $level);
    @mkdir($this->outdir);
    @mkdir($this->outdir.$relPath);
    file_put_contents($this->outdir.$relPath.$outFile,
<<<HEREDOC
<!DOCTYPE html><html lang="en">
<head>

<meta charset="UTF-8">
<style>body>pre{display:none;}</style>
<script>var book = { isStatic: true, title: '$title', pages: '$this->outdir', pagePath: '$relPath', pageFile: '$outFile'}</script>
<script src="${toRoot}../CM/load.js"></script>

</head>

<body><pre>
$tx
</pre>
</body>
</html>
HEREDOC
    );
  }

  function processFile ($title, $relPath, $fileIn, $fileOut, $level) {
    if (false === ($s = @file_get_contents($this->pages.$relPath.$fileIn)))
      self::error('bad file ' . $this->pages.$relPath.$fileIn);

    // fetch prologs
    $ps = ''; $pp = $this->pages; $pos = -1;
    for (;;) {
      if (($pro = @file_get_contents($pp._PROLOG)))
        $ps .= $pro . "\n";
      if (false === ($pos = strpos($relPath, '/', $pos+1)))
        break;
      $pp = substr($relPath,0,$pos+1);
    }

    $this->saveHtml($title, $relPath, $fileOut, $ps.$s, $level);
    return $relPath.$fileOut;
    }
}

// eof
