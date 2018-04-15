<?php
// special CM files
const _INDEX  = '_index.cm';    // index files
const _PROLOG = '_prolog';      // prolog files
const _TOC_JS = 'toc_.js';      // precompiled TOC

$pages = $book['pages'];

if ($isFrame = !($pg = @$_REQUEST['pg'])) {
  // no 'pg' request, this is the book frame
  $pagePath = $pageFile = '';
} else {
  // this is a book page
  if (false === ($pos = strrpos($pg, '/')))
    $pos = -1;
  $pagePath = substr($pg, 0, ++$pos); // '' or path
  $pageFile = substr($pg, $pos);      // plain name
}

// precompile files (if needed)
if ($isFrame) {
  require(dirname(__FILE__).'/compiler.php');
  (new TocCompiler($book['pages']))->compile();
}

// cache busting if index.php has been touched
$cacheBuster = @filectime('index.php');

?>
<!DOCTYPE html><html lang="en">
<head>

<title></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="UTF-8">

<meta name="description" content="<?=@$book['description']?>">
<meta name="keywords" content="<?=@$book['keywords']?>">
<meta name="author" content="<?=@$book['author']?>">
<meta name="generator" content="https://github.com/CompactMarkup/CM (ver. 0.9)">

<style>body>pre{display:none;}</style>
<script>var book = {
  title:  '<?=@$book['title']?>',
  banner: '<?=@$book['banner']?>',
  root:   '/',
  pages:  '<?=$book['pages']?>',
  pagePath: '<?=$pagePath?>', pageFile: '<?=$pageFile?>',
};
</script>
<script src="CM/load.js?<?=$isFrame ? 'frame&amp;' : ''?>v=<?=$cacheBuster?>"></script>
</head>

<body>
<?php if ($isFrame): ?>
  <header>
    <span></span>
    <span id="banner"></span>
    <span><a id="navToggle"></a></span>
  </header>
  <main>
    <nav id="nav">
      <menu></menu>
      <footer></footer>
    </nav>
    <iframe id="article"></iframe>
  </main>
<?php elseif ('.php' == substr($pageFile, -4)): // page, php ?>
  <article>
  <?=eval(@file_get_contents($pages.$pagePath.$pageFile)) ?>
  </article>
<?php else: // page, CM ?>
<pre>
<?php
  // fetch prologs + page text
  $path = ''; $pageText = ''; $pos = -1;
  for (;;) {
    if (($prolog = @file_get_contents($pages.$path._PROLOG)))
      $pageText .= $prolog."\n";
    if (false === ($pos = strpos($pagePath, '/', $pos+1)))
      break;
    $path = substr($pagePath,0,$pos+1);
  }
  $pageText .= @file_get_contents($pages.$pagePath.$pageFile);

  // optional php hook
  if ($hook = @$book['hook']) {
    list ($hookBeg, $hookEnd, $hookFun) = $hook;
    $lenBeg = strlen($hookBeg); $lenEnd = strlen($hookEnd);
    // page text will be processed
    $t = $pageText; $pageText = '';
    // search for the next hook mark
    while (false !== ($pos = strpos($t, $hookBeg))) {
      // copy the text before the mark
      $pageText .= substr($t, 0, $pos);
      $t = substr($t, $pos+$lenBeg);
      // search for the end hook mark
      if (false !== ($pos = strpos($t, $hookEnd))) {
        // text between marks
        $hookText = substr($t, 0, $pos);
        // after the end mark
        $t = substr($t, $pos+$lenEnd);
        // add processed text
        $pageText .= $hookFun($hookText);
      } else {
        // missing end mark - simple copy of the rest
        $pageText .= $t; $t = '';
      }
    }
    // copy the remainder
    $pageText .= $t;
  }

  echo htmlentities($pageText);
?>
</pre>
<?php endif // pages, CM ?>

<?php
  if (!$isFrame && ($pe = @$book['page_end']))
    echo $pe;
?>
</body>
</html>
