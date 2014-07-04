<?php
	header('Content-Type: text/html; charset=utf-8');

	error_reporting(E_ALL); 

	function karakter_degistir($degisecek)
	{
        	$eski = array("ı", "ö", "ü", "İ", "Ö", "Ü", "ç", "ğ", "ş", "Ç", "Ğ", "Ş");
        	$yeni = array("i", "o", "u", "I", "O", "U", "c", "g", "s", "C", "G", "S");

        	$sonuc = str_replace($eski, $yeni, $degisecek);

		return $sonuc;
	}

	function veri_cek($adres)
	{
		$baglanti = curl_init();
		
		curl_setopt($baglanti, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36");
		curl_setopt($baglanti, CURLOPT_REFERER, "https://www.eksisozluk.com");
		curl_setopt($baglanti, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($baglanti, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($baglanti, CURLOPT_URL, $adres);
		
		$sonuc = curl_exec($baglanti);
		curl_close($baglanti);
		
		return $sonuc;
	}

	function entry_cek($id)
	{
		$sayfa = veri_cek("https://eksisozluk.com/entry/" . $id);	
		
		preg_match_all('/\<span itemprop\=\"name\"\>(.*)\<\/span\>/', $sayfa, $baslik);
		preg_match_all('/itemprop\=\"commentText\"\>(.*)\<\/div\>/', $sayfa, $icerik);
		preg_match_all('/itemprop\=\"name\"\>(.*)\<\/span\>/', $sayfa, $yazar);
		preg_match_all('/itemprop="commentTime">(.*)\<\/time\>/', $sayfa, $tarih);
		preg_match_all('/id="li' . $id . '\"\svalue\=\"(.*)\"\sitemprop/', $sayfa, $sira);

		$entry['id'] = $id;
		$entry['baslik'] = $baslik[1][0];
		$entry['icerik'] = $icerik[1][0];
		$entry['yazar'] = $yazar[1][1];
		$entry['tarih'] = $tarih[1][0];
		$entry['sira'] = $sira[1][0];
		
		return $entry;		
	}

	function baslik_kaydet($baslik, $konum = "eksi/basliklar/")
	{
		$sayfa = veri_cek("https://eksisozluk.com/" . $baslik);

		preg_match_all('/data-pagecount\=\"(.*)\"\>\<\/div\>/', $sayfa, $sayfa_sayisi);
	
		if($sayfa_sayisi[1] == array())
		{
			$sayfa_sayisi = 1;
		}
		
		else
		{
			$sayfa_sayisi = $sayfa_sayisi[1][0];
		}

		$ilk = 1;
		$metin = '<html><head><meta charset="utf-8"><title>';

		for($i = 0; $i < $sayfa_sayisi; $i++)
		{
			preg_match_all('/id\=\"li(.*)\"\svalue/', $sayfa, $id);

			foreach($id[1] as $j)
			{
				$entry = entry_cek("$j");
	
				if($ilk == 1)
				{
					$metin .= $entry['baslik'] . "</title></head><body><b><u>" . $entry['baslik']  . "</u></b><br /><br />";
					$ilk = 0;
				}

				$metin .= "<b>" . $entry['sira'] . "</b>. ";
				$metin .= $entry['icerik'] . "<br /><br />";
				$metin .= "<b><u>yazar:</u></b> " . $entry['yazar'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				$metin .= "<b><u>tarih:</u></b> " . $entry['tarih'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				$metin .= "<b><u>id:</u></b> " . $entry['id'] . "<br /><br /><hr /><br />";
			}

			if($konum == "eksi/basliklar/")
			{
				$sayfa_str = "?p=";
			}

			else
			{
				$sayfa_str = "&p=";
			}

			$sayfa = veri_cek("https://eksisozluk.com/" . $baslik . $sayfa_str . ($i + 2));
		}

		$metin .= "</body></html>";

		preg_match_all("/(.*)--/", $baslik, $dosya_adi);			

		$dosya_adi = $dosya_adi[1][0];

		$dosya_adi = str_replace("-", " ", $dosya_adi);

		$dosya_adi = $konum . $dosya_adi . ".html";

		if(!file_exists($dosya_adi))
		{
			touch($dosya_adi);
		}

		$dosya = fopen($dosya_adi, "w");
		fwrite($dosya, $metin);
		fclose($dosya);
	}

	function kullanici_kaydet($yazar_kategori)
	{

		$sayfa = veri_cek("https://eksisozluk.com/" . $yazar_kategori);

		preg_match_all('/\"topic-list-description\"\>(.*)&#39/', $sayfa, $yazar);

		preg_match_all('/\s#(.*)\skanalındaki/', $sayfa, $kategori);

		$kategori = karakter_degistir($kategori[1][0]);
		$kategori = "eksi/kategoriler/" . $kategori;

		if(!file_exists($kategori))
		{
			mkdir($kategori, 0777);
		}
		
		$yazar = $kategori . "/" . $yazar[1][0];

		if(!file_exists($yazar))
		{
			mkdir($yazar, 0777);
		}

		$konum = $yazar . "/";

		preg_match_all('/data-pagecount\=\"(.*)\"\>\<\/div\>/', $sayfa, $sayfa_sayisi);

		if($sayfa_sayisi[1] == array())
		{
			$sayfa_sayisi = 1;
		}
		
		else
		{
			$sayfa_sayisi = $sayfa_sayisi[1][0];
		}

		for($i = 0; $i < $sayfa_sayisi; $i++)
		{
			$ilgili = explode('<ul class="topic-list">', $sayfa);
			$ilgili = explode('</ul>', $ilgili[1]);

			preg_match_all('/\<a\shref\=\"\/(.*)\"\>/', $ilgili[0], $basliklar);
		
			$basliklar = $basliklar[1];
			
			foreach($basliklar as $j)
			{
				$j = str_replace("amp;", "", $j);
				baslik_kaydet($j, $konum);
			}

			$sayfa = veri_cek("https://eksisozluk.com/" . $yazar_kategori . "&p=" . ($i + 2));
		}
	}

	if(isset($_POST['basliklar']))
	{
		$basliklar = explode("\n", $_POST['basliklar']);

		for($i = 0; $i < count($basliklar) - 1; $i++)
		{
			$link = "";

                       	for($j = 0; $j < strlen($basliklar[$i]) - 1; $j++)
                        {
                                $link .= $basliklar[$i][$j];
                        }

                        baslik_kaydet($link);
		}

		baslik_kaydet($basliklar[count($basliklar) - 1]);
	}

	elseif(isset($_POST['kullanicilar']))
	{
		$kullanicilar = explode("\n", $_POST['kullanicilar']);
		
		for($i = 0; $i < count($kullanicilar) - 1; $i++)
                {
                        $link = "";

                        for($j = 0; $j < strlen($kullanicilar[$i]) - 1; $j++)
                        {
                                $link .= $kullanicilar[$i][$j];
                        }

                        kullanici_kaydet($link);
                }

                kullanici_kaydet($kullanicilar[count($kullanicilar) - 1]);
	}

	else
	{
?>
<html>
	<head>
		<title>ekşi bot</title>
	</head>
	<body>
		<h4>Başlık Kaydet</h4>
		<form name = "baslik" method = "post" action = "eksibot.php" >		
			<textarea name = "basliklar" cols = "50" rows = "15" ></textarea><br />
			<input type = "submit" value = "Kaydet" >
		</form>
		<h4>Kullanıcı-Kategori Kaydet</h4>
		<form name = "kulanici" method = "post" action = "eksibot.php" >
			<textarea name = "kullanicilar" cols = "50" rows = "15" ></textarea><br />
			<input type = "submit" value = "Kaydet" >
		</form>
	</body>
</html>
<?php
	}
?>
