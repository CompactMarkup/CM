(function(){if(book.isStatic){const a=document.location.href,b=a.length-book.pagePath.length-book.pageFile.length;book.root=a.substr(0,b-book.pages.length),book.pages=a.substr(0,b)}const a=function(a){const b=new CM.Parser(book),c=b.parse(a);document.title=book.title+" - "+book.toc.lst[b.idx][2],document.body.innerHTML=`<article>${c}</article>`,b.out.scrollTo(location.hash),b.hasMath&&(CM.loadCSS("3rd/katex.min.css"),CM.loadScripts(["3rd/katex.min.js","3rd/katex-render.min.js"],()=>document.querySelectorAll("span.math").forEach(a=>a.style.display="initial"))),b.hasPre&&CM.loadScript("3rd/highlight.pack.js",()=>document.querySelectorAll("pre code").forEach(a=>hljs.highlightBlock(a)));const[d]=document.getElementsByTagName("article"),e=document.querySelectorAll("article > h2");if(e.length){const a=document.createElement("div");a.id="localMenu",d.appendChild(a);const b=document.createElement("a");a.appendChild(b);const c=document.createElement("div");a.appendChild(c),b.onclick=()=>{b.classList.toggle("open"),c.classList.toggle("open")},a.onmouseenter=()=>{b.classList.add("open"),c.classList.add("open")},a.onmouseleave=()=>{b.classList.remove("open"),c.classList.remove("open")};for(const b of e){const d=document.createElement("a");d.innerText=b.innerText,c.appendChild(d),d.onclick=()=>b.scrollIntoView(!0)}}};if("undefined"!=typeof document){let b,c=book.source;if(!c&&(b=document.querySelector("body > pre"))&&(c=b.innerText),c)try{a(c)}catch(a){book.isDebug||"reload_home"!==a.message||(top.location=top.origin)}}top.postMessage(["menu",book.toc.lst[book.toc.fil[book.pagePath+book.pageFile]][0]],"*")})();