#!/usr/bin/php
<?php
// chdir to site root
chdir(dirname(__FILE__).'/..');

require('book.php');
require('CM/compiler.php');

function saveIndex ($book, $outFile, $outdir) {
  list ($title, $banner, $description, $keywords, $author) =
    [$book['title'], $book['banner'], $book['description'], $book['keywords'], $book['author']];
  file_put_contents($outFile,
<<<HEREDOC
<!DOCTYPE html><html lang="en">
<head>

<title>$title</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="UTF-8">

<meta name="description" content="$description">
<meta name="keywords" content="$keywords">
<meta name="author" content="$author">
<meta name="generator" content="https://github.com/CompactMarkup/CM (ver. 0.9)">

<style>body>pre{display:none;}</style>
<script>var book = {
  isStatic: true,
  title:  '$title',
  banner: '$banner',
  root:   '',
  pages:  '$outdir',
  pagePath: '', pageFile: '',
};
</script>
<script src="CM/load.js?frame"></script>
</head>

<body>
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

</body>
</html>
HEREDOC
  );
}

$pages = $book['pages'];
$outdir = $pages; // static .html side-by-side with .cm

saveIndex($book, 'index.html', $pages);

(new StaticCompiler($pages, $pages))->compile();
// eof
