<?php	
/*
 * Modified version mp3 class from http://www.sourcerally.net/Scripts/20-PHP-MP3-Class
 * This version was at http://www.ClickFM.co.il/temp/ofer/mp3.txt (dead link, retrieved from http://web.archive.org/web/http://www.ClickFM.co.il/temp/ofer/mp3.txt)
 * Suggested change from kamilmajewski (26-12-2008 12:45) in comments from http://www.sourcerally.net/Scripts/20-PHP-MP3-Class.
 * Usage comments removed. Some code formatting changes. Added obvious TODO changes. Corrected class case.
 * Licence: LGPL? (no licence information available, however original is LGPL)
 */

// TODO: Use fopen and fseek instead of reading in the whole file all at once.
class PHPMP3
{
	private $str;
	private $time;
	private $frames;
	
	public function __construct($path = '')
	{
		if($path != '')
		{
			$this->str = file_get_contents($path);
		}
	}
	
	private function setStr($str)
	{
		$this->str = $str;
	}
	
	public function getStart()
	{
		$currentStrPos = -1;
		while (true)
		{
			$currentStrPos = strpos($this->str, chr(255), $currentStrPos + 1);
			if ($currentStrPos === false)
			{
				return 0;
			}
			
			$str = substr($this->str, $currentStrPos, 4);
			$strlen = strlen($str);
			$parts = array();
			for($i = 0; $i < $strlen; $i++)
			{
				$parts[] = $this->decbinFill(ord($str[$i]), 8);
			}

			if ($this->doFrameStuff($parts) === false)
			{
				continue;
			}

			return $currentStrPos;
		}
	}
	
	public function setFileInfoExact()
	{
		$maxStrLen = strlen($this->str);
		$currentStrPos = $this->getStart();
		
		$framesCount = 0;
		$time = 0;
		while($currentStrPos < $maxStrLen)
		{
			$str = substr($this->str, $currentStrPos, 4);
			$strlen = strlen($str);
			$parts = array();
			for($i = 0; $i < $strlen; $i++)
			{
				$parts[] = $this->decbinFill(ord($str[$i]), 8);
			}
			
			if($parts[0] != '11111111')
			{
				if(($maxStrLen - 128) > $currentStrPos)
				{
					return false;
				}
				else
				{
					$this->time = $time;
					$this->frames = $framesCount;
					return true;
				}
			}
			$a = $this->doFrameStuff($parts);
			$currentStrPos += $a[0];
			$time += $a[1];
			$framesCount++;
		}
		$this->time = $time;
		$this->frames = $framesCount;
		return true;
	}
	
	public function extract($start, $length)
	{
		$maxStrLen = strlen($this->str);
		$currentStrPos = $this->getStart();
		$framesCount = 0;
		$time = 0;
		$startCount = -1;
		$endCount = -1;
		while($currentStrPos < $maxStrLen)
		{
			if($startCount == -1 && $time >= $start)
			{
				$startCount = $currentStrPos;
			}
			if($endCount == -1 && $time >= ($start + $length))
			{
				$endCount = $currentStrPos - $startCount;
			}
			$doFrame = true;
			$str = substr($this->str, $currentStrPos, 4);
			$strlen = strlen($str);
			$parts = array();
			for($i = 0; $i < $strlen; $i++)
			{
				$parts[] = $this->decbinFill(ord($str[$i]), 8);
			}
			if($parts[0] != '11111111')
			{
				if(($maxStrLen-128) > $currentStrPos)
				{
					$doFrame = false;
				}
				else
				{
					$doFrame = false;
				}
			}
			if($doFrame)
			{
				$a = $this->doFrameStuff($parts);
				$currentStrPos += $a[0];
				$time += $a[1];
				$framesCount++;
			}
			else
			{
				break;
			}
		}
		$mp3 = new PHPMP3();
		if($endCount == -1)
		{
			$endCount = $maxStrLen - $startCount;
		}
		if($startCount != -1 && $endCount != -1)
		{
			$mp3->setStr(substr($this->str, $startCount, $endCount));
		}
		return $mp3;
	}
	
	private function decbinFill($dec, $length = 0)
	{
		$str = decbin($dec);
		$nulls = $length - strlen($str);
		if($nulls > 0)
		{
			for($i = 0; $i < $nulls; $i++)
			{
				$str = '0' . $str;
			}
		}
		return $str;
	}
	
	private function doFrameStuff($parts)
	{
		//Get Audio Version
		$seconds = 0;
		$errors = array();
		switch(substr($parts[1], 3, 2))
		{
			case '01':
				$errors[] = 'Reserved audio version';
				break;
			case '00':
				$audio = 2.5;
				break;
			case '10':
				$audio = 2;
				break;
			case '11':
				$audio = 1;
				break;
		}
		//Get Layer
		switch(substr($parts[1], 5, 2))
		{
			case '01':
				$layer = 3;
				break;
			case '00':
				$errors[] = 'Reserved layer';
				break;
			case '10':
				$layer = 2;
				break;
			case '11':
				$layer = 1;
				break;
		}
		//Get Bitrate
		$bitFlag = substr($parts[2], 0, 4);
		$bitArray = array(
			'0000'	=> array(0,		0,		0,		0,		0),
			'0001'	=> array(32,	32,		32,		32,		8),
			'0010'	=> array(64,	48,		40,		48,		16),
			'0011'	=> array(96,	56,		48,		56,		24),
			'0100'	=> array(128,	64,		56,		64,		32),
			'0101'	=> array(160,	80,		64,		80,		40),
			'0110'	=> array(192,	96,		80,		96,		48),
			'0111'	=> array(224,	112,	96,		112,	56),
			'1000'	=> array(256,	128,	112,	128,	64),
			'1001'	=> array(288,	160,	128,	144,	80),
			'1010'	=> array(320,	192,	160,	160,	96),
			'1011'	=> array(352,	224,	192,	176,	112),
			'1100'	=> array(384,	256,	224,	192,	128),
			'1101'	=> array(416,	320,	256,	224,	144),
			'1110'	=> array(448,	384,	320,	256,	160),
			'1111'	=> array(-1,	-1,		-1,		-1,		-1)
		);
		$bitPart = $bitArray[$bitFlag];
		$bitArrayNumber; //  TODO: Remove this line or set a value
		if($audio == 1)
		{
			switch($layer)
			{
				case 1:
					$bitArrayNumber = 0;
					break;
				case 2:
					$bitArrayNumber = 1;
					break;
				case 3:
					$bitArrayNumber = 2;
					break;
			}
		}
		else
		{
			switch($layer)
			{
				case 1:
					$bitArrayNumber = 3;
					break;
				case 2:
					$bitArrayNumber = 4;
					break;
				case 3:
					$bitArrayNumber = 4;
					break;
			}
		}
		$bitRate = $bitPart[$bitArrayNumber];
		if ($bitRate <= 0)
			return false;
		//Get Frequency
		//Change from kamilmajewski (26-12-2008 12:45) in comments from http://www.sourcerally.net/Scripts/20-PHP-MP3-Class
		$frequencies = array(
			1 => array(
				'00' => 44100,
				'01' => 48000,
				'10' => 32000,
				'11' => 'reserved'
			),
			2 => array(
				'00' => 44100,
				'01' => 48000,
				'10' => 32000,
				'11' => 'reserved'
			),
			2.5=>array(
				'00' => 44100,
				'01' => 48000,
				'10' => 32000,
				'11' => 'reserved'
			)
		);
		$freq = $frequencies[$audio][substr($parts[2], 4, 2)];
		$frameLength = 0;
		//IsPadded?
		$padding = substr($parts[2], 6, 1);
		if($layer == 3 || $layer == 2)
		{
			//FrameLengthInBytes = 144 * BitRate / SampleRate + Padding //  TODO: remove this line
			$frameLength = 144 * $bitRate * 1000 / $freq + $padding;
		}
		$frameLength = floor($frameLength);
		if ($frameLength == 0)
			return false;
		$seconds += $frameLength * 8 / ($bitRate * 1000);
		return array($frameLength, $seconds);
		//Calculate next when next frame starts.
		//Capture next frame.	
	}
	
	// TODO: Could be replaced by PHP ID3 (http://php.net/manual/en/book.id3.php) or another library
	public function setIdv3_2($track, $title, $artist, $album, $year, $genre, $comments, $composer ,$origArtist, $copyright, $url, $encodedBy)
	{
		// TODO: If I use this function remove the casting and set defaults values for all strings.
		$urlLength 			= (int) (strlen($url) + 2);
		$copyrightLength 	= (int) (strlen($copyright) + 1);
		$origArtistLength 	= (int) (strlen($origArtist) + 1);
		$composerLength 	= (int) (strlen($composer) + 1);
		$commentsLength 	= (int) (strlen($comments) + 5);
		$titleLength 		= (int) (strlen($title) + 1);
		$artistLength 		= (int) (strlen($artist) + 1);
		$albumLength 		= (int) (strlen($album) + 1);
		$genreLength		= (int) (strlen($genre) + 1);
		$encodedByLength 	= (int) (strlen($encodedBy) + 1);
		$trackLength 		= (int) (strlen($track) + 1);
		$yearLength 		= (int) (strlen($year) + 1);
		
		// TODO: There must be a better way to do this.
		$str .= chr(73);//I
		$str .= chr(68);//D
		$str .= chr(51);//3
		$str .= chr(3);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(8);//.
		$str .= chr(53);//5
		$str .= chr(84);//T
		$str .= chr(82);//R
		$str .= chr(67);//C
		$str .= chr(75);//K
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($trackLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $track;
		$str .= chr(84);//T
		$str .= chr(69);//E
		$str .= chr(78);//N
		$str .= chr(67);//C
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($encodedByLength);//
		$str .= chr(64);//@
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $encodedBy;
		$str .= chr(87);//W
		$str .= chr(88);//X
		$str .= chr(88);//X
		$str .= chr(88);//X
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($urlLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $url;
		$str .= chr(84);//T
		$str .= chr(67);//C
		$str .= chr(79);//O
		$str .= chr(80);//P
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($copyrightLength);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $copyright;
		$str .= chr(84);//T
		$str .= chr(79);//O
		$str .= chr(80);//P
		$str .= chr(69);//E
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($origArtistLength);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $origArtist;
		$str .= chr(84);//T
		$str .= chr(67);//C
		$str .= chr(79);//O
		$str .= chr(77);//M
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($composerLength);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $composer;
		$str .= chr(67);//C
		$str .= chr(79);//O
		$str .= chr(77);//M
		$str .= chr(77);//M
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($commentsLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(9);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $comments;
		$str .= chr(84);//T

		$str .= chr(67);//C
		$str .= chr(79);//O
		$str .= chr(78);//N
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($genreLength);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $genre;
		$str .= chr(84);//T
		$str .= chr(89);//Y
		$str .= chr(69);//E
		$str .= chr(82);//R
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($yearLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $year;
		$str .= chr(84);//T
		$str .= chr(65);//A
		$str .= chr(76);//L
		$str .= chr(66);//B
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($albumLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $album;
		$str .= chr(84);//T
		$str .= chr(80);//P
		$str .= chr(69);//E
		$str .= chr(49);//1
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($artistLength);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $artist;
		$str .= chr(84);//T
		$str .= chr(73);//I
		$str .= chr(84);//T
		$str .= chr(50);//2
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr($titleLength);//.
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= chr(0);//
		$str .= $title;
		$this->str = $str . $this->str;
	}
	
	public function mergeBehind(mp3 $mp3)
	{
		$this->str .= $mp3->str;
	}
	
	public function mergeInfront(mp3 $mp3)
	{
		$this->str = $mp3->str . $this->str;
	}
	
	private function getIdvEnd()
	{
		$strlen = strlen($this->str);
		$str = substr($this->str, ($strlen - 128));
		$str1 = substr($str, 0, 3);
		if(strtolower($str1) == strtolower('TAG'))
		{
			return $str;
		}
		else
		{
			return false;
		}
	}
	
	public function striptags()
	{
		//Remove start stuff...
		$newStr = '';
		$s = $start = $this->getStart();
		if($s === false)
		{
			return false;
		}
		else
		{
			$this->str = substr($this->str, $start);
		}
		//Remove end tag stuff
		$end = $this->getIdvEnd();
		if($end !== false)
		{
			$this->str = substr($this->str, 0, (strlen($this->str) - 129));
		}
	}
	
	public function save($path)
	{
		$fp = fopen($path, 'w');
		fwrite($fp, $this->str);
		fclose($fp);
	}
	
	//join various MP3s
    public function multiJoin($newpath,$array)
    {
        foreach ($array as $path)
        {
            $mp3 = new PHPMP3($path);
            $mp3->striptags();
            $mp3_1 = new PHPMP3($newpath);
            $mp3->mergeBehind($mp3_1);
            $mp3->save($newpath);
        }
    }
}