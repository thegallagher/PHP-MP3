PHP MP3
=======
A PHP manipulation library.

Usage
-----
```PHP
//Merge two files
$path = 'path.mp3';
$path1 = 'path1.mp3';
$mp3 = new PHPMP3($path);

$newpath = 'path.mp3';
$mp3->striptags();

$mp3_1 = new PHPMP3($path1);
$mp3->mergeBehind($mp3_1);
$mp3->striptags();
$mp3->setIdv3_2('01','Track Title','Artist','Album','Year','Genre','Comments','Composer','OrigArtist',
'Copyright','url','encodedBy');
$mp3->save($newpath);

//Extract 30 seconds starting after 10 seconds.
$path = 'path.mp3';
$mp3 = new PHPMP3($path);
$mp3_1 = $mp3->extract(10,30);
$mp3_1->save('newpath.mp3');

//Extract the exact length of time
$path = 'path.mp3';
$mp3 = new PHPMP3($path);
$mp3->setFileInfoExact();
echo $mp3->time;
//note that this is the exact length!
```

Todo
----
* Add tests
* Use fopen and fseek instead of reading in the whole file all at once.
* Implement PSR-0 and PSR-4
* Add composer
* Create separate classes for manipulating ID3 and cutting mp3s
* Deprecate setIdv3_2 and replace with method which can write each tag individually

Credits
-------
Class originated from regin [http://www.sourcerally.net/Scripts/20-PHP-MP3-Class](https://web.archive.org/web/20120211192505/http://www.sourcerally.net/Scripts/20-PHP-MP3-Class)
This class was forked from [http://www.ClickFM.co.il/temp/ofer/mp3.txt](http://web.archive.org/web/http://www.ClickFM.co.il/temp/ofer/mp3.txt)
A small fix was taken from comment by kamilmajewski on [http://www.sourcerally.net/Scripts/20-PHP-MP3-Class](https://web.archive.org/web/20120211192505/http://www.sourcerally.net/Scripts/20-PHP-MP3-Class)