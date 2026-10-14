<?php
/* Custom code: FC-2026-10-14: Self-contained, personal, Unicode sponsoring PDF. */
defined('ALTUMCODE') || PHP_SAPI === 'cli' || die();

/**
 * A deliberately small PDF 1.4 writer for this fixed two-page presentation.
 * It embeds local OFL DM Sans fonts and never fetches remote resources.
 * Coordinates are points, from the top left of A4. Text is Unicode CID text.
 */
final class FccSponsoringPdfDocument {
    private array $objects = [];
    private array $pages = [];
    private array $streams = [];
    private array $links = [];
    private array $fonts = [];
    private array $used = ['R'=>[], 'B'=>[]];
    private array $images = [];
    private int $page = -1;
    private const HEIGHT = 841.89;

    public function __construct() {
        foreach(['R'=>'Regular', 'B'=>'Bold'] as $key=>$style) {
            $path = __DIR__.'/fcc_sponsoring_pdf/DMSans-'.$style;
            if(!is_readable($path.'.ttf') || !is_readable($path.'.json')) throw new RuntimeException('Sponsoring PDF font is unavailable.');
            $metrics = json_decode((string) file_get_contents($path.'.json'), true, 512, JSON_THROW_ON_ERROR);
            $this->fonts[$key] = ['path'=>$path.'.ttf', 'metrics'=>$metrics];
        }
    }
    public function page(): void { $this->page++; $this->streams[$this->page]=''; $this->links[$this->page]=[]; }
    private function write(string $s): void { $this->streams[$this->page].=$s."\n"; }
    private function color(string $hex): string { return sprintf('%.4F %.4F %.4F',hexdec(substr($hex,0,2))/255,hexdec(substr($hex,2,2))/255,hexdec(substr($hex,4,2))/255); }
    private function number(float $n): string { return sprintf('%.3F',$n); }
    public static function clean(string $s, int $limit=1000): string {
        $s = mb_scrub($s,'UTF-8');
        if(class_exists('Normalizer')) $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$s) ?? '';
        return mb_substr(trim($s),0,$limit,'UTF-8');
    }
    private function chars(string $s): array { return preg_split('//u',$s,-1,PREG_SPLIT_NO_EMPTY) ?: []; }
    private function code(string $c): int { $p=mb_ord($c,'UTF-8'); return $p>65535 ? 63 : $p; }
    public function width(string $s, float $size=10, string $style='R'): float {
        $n=0; $map=$this->fonts[$style]['metrics']['cmap'];
        foreach($this->chars($s) as $char) { $code=$this->code($char); $n += ($map[$code] ?? $map[63])[1]; }
        return $n*$size/1000;
    }
    public function rect(float $x,float $y,float $w,float $h,string $color,float $radius=0): void {
        $x1=$x+$w; $y1=self::HEIGHT-$y; $y0=$y1-$h; $r=min($radius,$w/2,$h/2); $c=$r*0.55228475;
        $this->write($this->color($color).' rg');
        if(!$r) { $this->write(sprintf('%.3F %.3F %.3F %.3F re f',$x,$y0,$w,$h)); return; }
        $this->write(sprintf('%.3F %.3F m %.3F %.3F l %.3F %.3F %.3F %.3F %.3F %.3F c %.3F %.3F l %.3F %.3F %.3F %.3F %.3F %.3F c %.3F %.3F l %.3F %.3F %.3F %.3F %.3F %.3F c %.3F %.3F l %.3F %.3F %.3F %.3F %.3F %.3F c f',
            $x+$r,$y0,$x1-$r,$y0,$x1-$r+$c,$y0,$x1,$y0+$r-$c,$x1,$y0+$r,$x1,$y1-$r,$x1,$y1-$r+$c,$x1-$r+$c,$y1,$x1-$r,$y1,$x+$r,$y1,$x+$r-$c,$y1,$x,$y1-$r+$c,$x,$y1-$r,$x,$y0+$r,$x,$y0+$r-$c,$x+$r-$c,$y0,$x+$r,$y0));
    }
    public function circle(float $cx,float $cy,float $r,string $color): void { $this->rect($cx-$r,$cy-$r,2*$r,2*$r,$color,$r); }
    public function text(float $x,float $y,string $s,float $size=10,string $style='R',string $color='173F34'): void {
        $s=self::clean($s); $hex=''; $map=$this->fonts[$style]['metrics']['cmap'];
        foreach($this->chars($s) as $c) { $code=$this->code($c); if(!isset($map[$code])) $code=63; $this->used[$style][$code]=true; $hex.=sprintf('%04X',$code); }
        $this->write(sprintf('BT /F%s %.3F Tf %s rg 1 0 0 1 %.3F %.3F Tm <%s> Tj ET',$style,$size,$this->color($color),$x,self::HEIGHT-$y-$size*0.80,$hex));
    }
    public function wrap(string $s,float $width,float $size=10,string $style='R'): array {
        $lines=[]; $line='';
        foreach(preg_split('/\s+/u',self::clean($s)) ?: [] as $word) {
            if($word==='') continue;
            $try=$line==='' ? $word : $line.' '.$word;
            if($this->width($try,$size,$style)<=$width) { $line=$try; continue; }
            if($line!=='') { $lines[]=$line; $line=''; }
            foreach($this->chars($word) as $c) {
                if($line!=='' && $this->width($line.$c,$size,$style)>$width) { $lines[]=$line; $line=''; }
                $line.=$c;
            }
        }
        if($line!=='') $lines[]=$line;
        return $lines;
    }
    public function block(float $x,float $y,float $width,string $s,float $size=10,float $leading=14,string $style='R',string $color='173F34'): float {
        foreach($this->wrap($s,$width,$size,$style) as $line) { $this->text($x,$y,$line,$size,$style,$color); $y+=$leading; }
        return $y;
    }
    public function fit(float $x,float $y,float $width,float $height,string $s,float $max=15,float $min=6,string $style='B',string $color='173F34'): float {
        for($size=$max;$size>=$min;$size-=0.25) {
            $lines=$this->wrap($s,$width,$size,$style);
            if(count($lines)*$size*1.18 <= $height) { $this->block($x,$y,$width,$s,$size,$size*1.18,$style,$color); return $size; }
        }
        throw new RuntimeException('Sponsoring PDF text does not fit its reserved area.');
    }
    public function link(float $x,float $y,float $w,float $h,string $url): void {
        if(fcc_sponsoring_pdf_url($url)!=='') $this->links[$this->page][]=[$x,$y,$w,$h,$url];
    }
    public function button(float $x,float $y,float $w,float $h,string $label,string $url,string $fill='DCECC0',string $ink='173F34',float $size=10): void {
        $this->rect($x,$y,$w,$h,$fill,7); $this->fit($x+12,$y+($h-$size)/2,$w-24,$size*1.25,$label,$size,7,'B',$ink); $this->link($x,$y,$w,$h,$url);
    }
    public function qr(float $x,float $y,float $size,string $url): void {
        if(!class_exists('BaconQrCode\\Encoder\\Encoder')) throw new RuntimeException('Sponsoring PDF QR encoder is unavailable.');
        $m=\BaconQrCode\Encoder\Encoder::encode($url,\BaconQrCode\Common\ErrorCorrectionLevel::M(),'UTF-8')->getMatrix();
        $n=$m->getWidth(); $step=$size/($n+8); $this->rect($x,$y,$size,$size,'FFFFFF');
        for($j=0;$j<$n;$j++) for($i=0;$i<$n;$i++) if($m->get($i,$j)===1) $this->rect($x+($i+4)*$step,$y+($j+4)*$step,$step+0.01,$step+0.01,'123D32');
        $this->link($x,$y,$size,$size,$url);
    }
    /** A server-only validated local avatar. Never open a URL or an arbitrary path. */
    public function avatar(float $x,float $y,float $size,string $path): bool {
        if($path==='' || !function_exists('imagecreatefromstring')) return false;
        $base=realpath(dirname(__DIR__,2).'/uploads/avatars'); $real=realpath($path);
        if(!$base || !$real || !str_starts_with($real,$base.DIRECTORY_SEPARATOR) || !is_file($real) || filesize($real)>5*1024*1024) return false;
        $info=@getimagesize($real);
        if(!$info || $info[0]<1 || $info[1]<1 || $info[0]*$info[1]>8000000 || !in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true)) return false;
        $raw=file_get_contents($real); $source=$raw!==false ? @imagecreatefromstring($raw) : false;
        if(!$source) return false;
        $thumb=imagecreatetruecolor(256,256); $white=imagecolorallocate($thumb,255,255,255); imagefill($thumb,0,0,$white);
        $crop=min($info[0],$info[1]); imagecopyresampled($thumb,$source,0,0,(int)(($info[0]-$crop)/2),(int)(($info[1]-$crop)/2),256,256,$crop,$crop);
        ob_start(); imagejpeg($thumb,null,88); $jpg=(string)ob_get_clean(); imagedestroy($source); imagedestroy($thumb);
        $name='Im'.(count($this->images)+1); $this->images[$name]=$jpg;
        $this->write(sprintf('q %.3F 0 0 %.3F %.3F %.3F cm /%s Do Q',$size,$size,$x,self::HEIGHT-$y-$size,$name)); return true;
    }
    private function object(string $s): int { $this->objects[]=$s; return count($this->objects); }
    private function stream(string $s,string $extra=''): int { $z=gzcompress($s,6); return $this->object('<< /Length '.strlen($z).' /Filter /FlateDecode '.$extra.">>\nstream\n".$z."\nendstream"); }
    private function literal(string $s): string { return '('.strtr($s,['\\'=>'\\\\','('=>'\\(',')'=>'\\)',"\r"=>' ',"\n"=>' ']).')'; }
    public function output(string $title): string {
        if(count($this->streams)!==2) throw new RuntimeException('Sponsoring PDF must have exactly two pages.');
        $catalog=$this->object(''); $pageTree=$this->object(''); $fontIds=[];
        foreach($this->fonts as $key=>$font) {
            $metrics=$font['metrics']; $bytes=file_get_contents($font['path']);
            if($bytes===false) throw new RuntimeException('Sponsoring PDF font cannot be read.');
            $fontFile=$this->stream($bytes,'/Length1 '.strlen($bytes));
            $descriptor=$this->object('<< /Type /FontDescriptor /FontName /'.$metrics['name'].' /Flags 32 /FontBBox ['.implode(' ',$metrics['bbox']).'] /ItalicAngle 0 /Ascent '.$metrics['ascent'].' /Descent '.$metrics['descent'].' /CapHeight 720 /StemV '.($key==='B'?120:80).' /FontFile2 '.$fontFile.' 0 R >>');
            $gid=str_repeat("\0",131072); $widths=''; $cmapCodes=[];
            ksort($this->used[$key]);
            foreach($this->used[$key] as $cp=>$v) { [$glyph,$advance]=$metrics['cmap'][$cp]; $packed=pack('n',$glyph); $gid[$cp*2]=$packed[0]; $gid[$cp*2+1]=$packed[1]; $widths.=$cp.' ['.$advance.'] '; $cmapCodes[]=sprintf('<%04X> <%04X>',$cp,$cp); }
            $gidId=$this->stream($gid);
            $cid=$this->object('<< /Type /Font /Subtype /CIDFontType2 /BaseFont /'.$metrics['name'].' /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor '.$descriptor.' 0 R /CIDToGIDMap '.$gidId.' 0 R /DW 1000 /W ['.$widths.'] >>');
            $cmap="/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n/CMapName /FCCUnicode def\n/CMapType 2 def\n1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n";
            foreach(array_chunk($cmapCodes,100) as $chunk) $cmap.=count($chunk)." beginbfchar\n".implode("\n",$chunk)."\nendbfchar\n";
            $cmap.="endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
            $toUnicode=$this->stream($cmap);
            $fontIds[$key]=$this->object('<< /Type /Font /Subtype /Type0 /BaseFont /'.$metrics['name'].' /Encoding /Identity-H /DescendantFonts ['.$cid.' 0 R] /ToUnicode '.$toUnicode.' 0 R >>');
        }
        $imageResources='';
        foreach($this->images as $name=>$jpg) { $id=$this->object('<< /Type /XObject /Subtype /Image /Width 256 /Height 256 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpg).">>\nstream\n".$jpg."\nendstream"); $imageResources.='/'.$name.' '.$id.' 0 R '; }
        $pageIds=[];
        foreach($this->streams as $i=>$content) {
            $streamId=$this->stream($content); $ann=[];
            foreach($this->links[$i] as [$x,$y,$w,$h,$url]) $ann[]=$this->object(sprintf('<< /Type /Annot /Subtype /Link /Rect [%.3F %.3F %.3F %.3F] /Border [0 0 0] /A << /S /URI /URI %s >> >>',$x,self::HEIGHT-$y-$h,$x+$w,self::HEIGHT-$y,$this->literal($url)));
            $pageIds[]=$this->object('<< /Type /Page /Parent '.$pageTree.' 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /FR '.$fontIds['R'].' 0 R /FB '.$fontIds['B'].' 0 R >> /XObject << '.$imageResources.' >> >> /Contents '.$streamId.' 0 R /Annots ['.implode(' ',array_map(fn($id)=>$id.' 0 R',$ann)).'] >>');
        }
        $this->objects[$catalog-1]='<< /Type /Catalog /Pages '.$pageTree.' 0 R /PageLayout /SinglePage >>';
        $this->objects[$pageTree-1]='<< /Type /Pages /Count 2 /Kids ['.implode(' ',array_map(fn($id)=>$id.' 0 R',$pageIds)).'] >>';
        $info=$this->object('<< /Title <FEFF'.strtoupper(bin2hex(mb_convert_encoding(self::clean($title),'UTF-16BE','UTF-8'))).'> /Creator (Forever Card Club) /CreationDate (D:'.gmdate('YmdHis').'Z) >>');
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
        foreach($this->objects as $index=>$object) { $offsets[]=strlen($pdf); $pdf.=($index+1)." 0 obj\n".$object."\nendobj\n"; }
        $start=strlen($pdf); $pdf.='xref'."\n0 ".(count($this->objects)+1)."\n0000000000 65535 f \n";
        foreach(array_slice($offsets,1) as $offset) $pdf.=sprintf("%010d 00000 n \n",$offset);
        return $pdf.'trailer'."\n<< /Size ".(count($this->objects)+1).' /Root '.$catalog.' 0 R /Info '.$info." 0 R >>\nstartxref\n".$start."\n%%EOF\n";
    }
}

function fcc_sponsoring_pdf_url(string $url): string {
    if(strlen($url)>2048 || preg_match('/[\x00-\x20\x7F]/',$url)) return '';
    $parsed=parse_url($url);
    return $parsed && in_array(strtolower((string)($parsed['scheme'] ?? '')),['https','http'],true) && !empty($parsed['host']) && !isset($parsed['user']) && !isset($parsed['pass']) ? $url : '';
}

/** Print-specific short copy. The website has the full, matching explanation. */
function fcc_sponsoring_pdf_copy(string $locale): array {
    $all = [
      'en'=>[
        'invite'=>'YOUR PERSONAL INVITATION','title'=>['Your business.','Your signature.','Your beginning.'],
        'lead'=>'Start your Forever business with your own digital card, useful tools and people who help you take the first steps.',
        'with'=>'Your contact','card'=>'Your personal card','shop'=>'Explore the webshop','contact'=>'Let us talk','open_card'=>'Open the personal card',
        'everything'=>'A clearer start. Real support.', 'benefits'=>[
          ['Your personal card','One link for your profile: your introduction, contact details, product recommendations and webshop.'],
          ['Products you can explain','Share clear product information and your own links. Help people choose what suits them.'],
          ['Learn one step at a time','Guided education and practical tasks help you understand what to do next.'],
          ['People by your side','Your sponsor, team webinars and events give you a place to ask, learn and connect.'],
          ['Build your own team','Invite interested people with your personal presentation and help them take their first steps.']
        ],
        'next'=>'From interest to your first steps.', 'intro'=>'You do not need to know everything today. Start with a conversation and a plan you understand.',
        'steps'=>[
          ['Talk about your goal','Tell your sponsor what you would like to build and how much time you can set aside.'],
          ['Get to know the business','Understand the products, the independent business model and the current conditions in your country.'],
          ['Make your card your own','Add your introduction and contact details, then place your card link in your social profile.'],
          ['Learn, share and follow up','Follow the guided education, join a webinar and continue conversations with interested people.']
        ],
        'ready'=>'Let us see how you could start.', 'cta'=>'I would like to know more', 'scan'=>'Scan for your personal invitation', 'links'=>'Keep your contact close', 'phone'=>'Public phone', 'fbo'=>'Forever ID', 'page'=>'Personal presentation', 'card_link'=>'Personal card', 'shop_link'=>'Official webshop',
        'nature'=>'This is an independent business opportunity with Forever Living Products, not an employment offer. Results depend on your activity, time, knowledge and market. Your sponsor explains the current entry conditions before you decide.',
        'independent'=>'Forever Card Club is an independent support system. It is not the official website of Forever Living Products.', 'date'=>'Prepared', 'personal'=>'A personal introduction from'
      ],
      'hr'=>[
        'invite'=>'TVOJA OSOBNA POZIVNICA','title'=>['Tvoj posao.','Tvoj potpis.','Tvoj početak.'],
        'lead'=>'Pokreni svoj Forever posao uz vlastitu karticu, korisne alate i ljude koji ti pomažu napraviti prve korake.',
        'with'=>'Tvoj kontakt','card'=>'Tvoja osobna kartica','shop'=>'Pogledaj webshop','contact'=>'Razgovarajmo','open_card'=>'Otvori osobnu karticu',
        'everything'=>'Jasniji početak. Stvarna podrška.', 'benefits'=>[
          ['Tvoja osobna kartica','Jedna poveznica za tvoj profil: predstavljanje, kontakt, preporuke proizvoda i webshop.'],
          ['Proizvodi koje lako predstavljaš','Šalji razumljive informacije i svoje poveznice. Pomozi osobi odabrati ono što joj odgovara.'],
          ['Učiš korak po korak','Vođena edukacija i konkretni zadaci pomažu ti razumjeti što napraviti sljedeće.'],
          ['Ljudi uz koje napreduješ','Sponzor, timski webinari i događaji daju ti mjesto za pitanja, učenje i povezivanje.'],
          ['Razvijaj i svoj tim','Zainteresiranim ljudima pošalji osobnu prezentaciju i pomozi im napraviti prve korake.']
        ],
        'next'=>'Od interesa do prvih koraka.', 'intro'=>'Danas ne moraš znati sve. Kreni razgovorom i planom koji razumiješ.',
        'steps'=>[
          ['Razgovaraj o svojem cilju','Reci sponzoru što želiš izgraditi i koliko vremena možeš izdvojiti.'],
          ['Upoznaj način poslovanja','Upoznaj proizvode, način samostalnog poslovanja i aktualne uvjete u svojoj zemlji.'],
          ['Uredi svoju karticu','Dodaj predstavljanje i kontakt, pa poveznicu na karticu postavi u opis svojeg profila.'],
          ['Uči, dijeli i nastavi razgovor','Prati vođenu edukaciju, dođi na webinar i javi se ljudima koji su pokazali interes.']
        ],
        'ready'=>'Pogledajmo kako možeš krenuti.', 'cta'=>'Želim saznati više', 'scan'=>'Skeniraj za osobnu pozivnicu', 'links'=>'Sačuvaj svoj kontakt', 'phone'=>'Javni telefon', 'fbo'=>'Forever ID', 'page'=>'Osobna prezentacija', 'card_link'=>'Osobna kartica', 'shop_link'=>'Službeni webshop',
        'nature'=>'Ovo je prilika za samostalno poslovanje uz Forever Living Products, a ne ponuda zaposlenja. Rezultati ovise o tvojem radu, vremenu, znanju i tržištu. Prije odluke sponzor objašnjava aktualne uvjete početka.',
        'independent'=>'Forever Card Club neovisan je sustav podrške. Nije službena stranica kompanije Forever Living Products.', 'date'=>'Pripremljeno', 'personal'=>'Osobni poziv šalje'
      ],
      'sl'=>[
        'invite'=>'TVOJE OSEBNO POVABILO','title'=>['Tvoj posel.','Tvoj podpis.','Tvoj začetek.'],
        'lead'=>'Začni svoj posel Forever z lastno kartico, uporabnimi orodji in ljudmi, ki ti pomagajo pri prvih korakih.',
        'with'=>'Tvoj stik','card'=>'Tvoja osebna kartica','shop'=>'Poglej spletno trgovino','contact'=>'Pogovoriva se','open_card'=>'Odpri osebno kartico',
        'everything'=>'Jasnejši začetek. Prava podpora.', 'benefits'=>[
          ['Tvoja osebna kartica','Ena povezava za tvoj profil: predstavitev, kontakt, priporočila izdelkov in spletna trgovina.'],
          ['Izdelki, ki jih lažje predstaviš','Pošlji razumljive informacije in svoje povezave. Pomagaj osebi izbrati, kar ji ustreza.'],
          ['Učiš se korak za korakom','Vodeno izobraževanje in praktične naloge ti pomagajo razumeti naslednji korak.'],
          ['Ljudje, s katerimi napreduješ','Sponzor, ekipni spletni seminarji in dogodki so prostor za vprašanja, učenje in povezovanje.'],
          ['Razvijaj tudi svojo ekipo','Zainteresiranim pošlji osebno predstavitev in jim pomagaj narediti prve korake.']
        ],
        'next'=>'Od zanimanja do prvih korakov.', 'intro'=>'Danes ti ni treba vedeti vsega. Začni s pogovorom in načrtom, ki ga razumeš.',
        'steps'=>[
          ['Pogovori se o svojem cilju','Povej sponzorju, kaj želiš zgraditi in koliko časa lahko nameniš.'],
          ['Spoznaj način poslovanja','Spoznaj izdelke, samostojno poslovanje in veljavne pogoje v svoji državi.'],
          ['Uredi svojo kartico','Dodaj predstavitev in kontakt ter povezavo do kartice objavi v opisu profila.'],
          ['Uči se, deli in nadaljuj pogovor','Sledi vodenemu izobraževanju, udeleži se seminarja in se oglasi zainteresiranim ljudem.']
        ],
        'ready'=>'Poglejva, kako lahko začneš.', 'cta'=>'Želim izvedeti več', 'scan'=>'Skeniraj za osebno povabilo', 'links'=>'Shrani svoj stik', 'phone'=>'Javni telefon', 'fbo'=>'Forever ID', 'page'=>'Osebna predstavitev', 'card_link'=>'Osebna kartica', 'shop_link'=>'Uradna spletna trgovina',
        'nature'=>'To je priložnost za samostojno poslovanje s Forever Living Products, ne ponudba zaposlitve. Rezultati so odvisni od tvojega dela, časa, znanja in trga. Pred odločitvijo ti sponzor pojasni veljavne pogoje začetka.',
        'independent'=>'Forever Card Club je neodvisen sistem podpore. Ni uradna spletna stran podjetja Forever Living Products.', 'date'=>'Pripravljeno', 'personal'=>'Osebno povabilo pošilja'
      ],
      'de'=>[
        'invite'=>'DEINE PERSÖNLICHE EINLADUNG','title'=>['Dein Geschäft.','Deine Handschrift.','Dein Anfang.'],
        'lead'=>'Starte dein Forever Geschäft mit deiner eigenen digitalen Karte, nützlichen Werkzeugen und Menschen, die dich bei den ersten Schritten begleiten.',
        'with'=>'Dein Kontakt','card'=>'Deine persönliche Karte','shop'=>'Zum Webshop','contact'=>'Lass uns sprechen','open_card'=>'Persönliche Karte öffnen',
        'everything'=>'Ein klarer Start. Echte Begleitung.', 'benefits'=>[
          ['Deine persönliche Karte','Ein Link für dein Profil: Vorstellung, Kontakt, Produktempfehlungen und Webshop.'],
          ['Produkte verständlich vorstellen','Teile klare Informationen und deine eigenen Links. Hilf Menschen, passend auszuwählen.'],
          ['Schritt für Schritt lernen','Geführte Schulungen und praktische Aufgaben zeigen dir, was du als Nächstes tun kannst.'],
          ['Menschen an deiner Seite','Dein Sponsor, Teamwebinare und Veranstaltungen bieten Raum für Fragen und Austausch.'],
          ['Dein eigenes Team aufbauen','Sende Interessierten deine persönliche Vorstellung und begleite ihre ersten Schritte.']
        ],
        'next'=>'Vom Interesse zu deinen ersten Schritten.', 'intro'=>'Du musst heute nicht alles wissen. Beginne mit einem Gespräch und einem verständlichen Plan.',
        'steps'=>[
          ['Über dein Ziel sprechen','Erzähle deinem Sponsor, was du aufbauen möchtest und wie viel Zeit du einplanen kannst.'],
          ['Das Geschäft kennenlernen','Lerne die Produkte, das selbstständige Geschäftsmodell und die Bedingungen in deinem Land kennen.'],
          ['Deine Karte gestalten','Ergänze Vorstellung und Kontakt und setze deinen Kartenlink in dein Profil.'],
          ['Lernen, teilen und nachfragen','Nutze die Schulungen, besuche ein Webinar und führe Gespräche mit Interessierten weiter.']
        ],
        'ready'=>'Finden wir heraus, wie du starten kannst.', 'cta'=>'Ich möchte mehr erfahren', 'scan'=>'Für deine persönliche Einladung scannen', 'links'=>'Deinen Kontakt speichern', 'phone'=>'Öffentliche Telefonnummer', 'fbo'=>'Forever ID', 'page'=>'Persönliche Präsentation', 'card_link'=>'Persönliche Karte', 'shop_link'=>'Offizieller Webshop',
        'nature'=>'Dies ist eine Möglichkeit zur selbstständigen Tätigkeit mit Forever Living Products, kein Stellenangebot. Ergebnisse hängen von deinem Einsatz, deiner Zeit, deinem Wissen und dem Markt ab. Dein Sponsor erklärt vor deiner Entscheidung die aktuellen Einstiegsbedingungen.',
        'independent'=>'Forever Card Club ist ein unabhängiges Unterstützungssystem und keine offizielle Website von Forever Living Products.', 'date'=>'Erstellt', 'personal'=>'Eine persönliche Einladung von'
      ],
      'es'=>[
        'invite'=>'TU INVITACIÓN PERSONAL','title'=>['Tu negocio.','Tu sello.','Tu comienzo.'],
        'lead'=>'Empieza tu negocio Forever con tu propia tarjeta digital, herramientas útiles y personas que te acompañan en los primeros pasos.',
        'with'=>'Tu contacto','card'=>'Tu tarjeta personal','shop'=>'Ver la tienda online','contact'=>'Hablemos','open_card'=>'Abrir la tarjeta personal',
        'everything'=>'Un comienzo claro. Apoyo real.', 'benefits'=>[
          ['Tu tarjeta personal','Un enlace para tu perfil: presentación, contacto, recomendaciones de productos y tienda online.'],
          ['Productos fáciles de presentar','Comparte información clara y tus propios enlaces. Ayuda a cada persona a elegir lo que le encaja.'],
          ['Aprende paso a paso','La formación guiada y las tareas prácticas te ayudan a entender qué hacer después.'],
          ['Personas a tu lado','Tu patrocinador, los seminarios del equipo y los eventos te permiten preguntar, aprender y conectar.'],
          ['Desarrolla tu propio equipo','Envía tu presentación personal a quienes tengan interés y acompaña sus primeros pasos.']
        ],
        'next'=>'Del interés a tus primeros pasos.', 'intro'=>'No necesitas saberlo todo hoy. Empieza con una conversación y un plan que entiendas.',
        'steps'=>[
          ['Habla de tu objetivo','Cuenta a tu patrocinador qué te gustaría construir y cuánto tiempo puedes dedicar.'],
          ['Conoce la actividad','Entiende los productos, el modelo de negocio independiente y las condiciones actuales de tu país.'],
          ['Prepara tu tarjeta','Añade tu presentación y contacto y coloca el enlace de tu tarjeta en tu perfil.'],
          ['Aprende, comparte y continúa','Sigue la formación, participa en un seminario y retoma las conversaciones con personas interesadas.']
        ],
        'ready'=>'Veamos cómo puedes empezar.', 'cta'=>'Quiero saber más', 'scan'=>'Escanea para ver tu invitación personal', 'links'=>'Guarda tu contacto', 'phone'=>'Teléfono público', 'fbo'=>'Forever ID', 'page'=>'Presentación personal', 'card_link'=>'Tarjeta personal', 'shop_link'=>'Tienda oficial',
        'nature'=>'Esta es una oportunidad de negocio independiente con Forever Living Products, no una oferta de empleo. Los resultados dependen de tu actividad, tiempo, conocimientos y mercado. Tu patrocinador explica las condiciones actuales antes de que decidas.',
        'independent'=>'Forever Card Club es un sistema de apoyo independiente. No es el sitio oficial de Forever Living Products.', 'date'=>'Preparado', 'personal'=>'Una invitación personal de'
      ]
    ];
    $copy=$all[$locale] ?? $all['en'];
    if(function_exists('fcc_sponsoring_copy_catalog')) {
        $shared=fcc_sponsoring_copy_catalog($locale);
        if(isset($shared['hero_title_a'],$shared['hero_title_b'],$shared['hero_title_c'])) $copy['title']=[$shared['hero_title_a'],$shared['hero_title_b'],$shared['hero_title_c']];
        if(isset($shared['hero_lead'])) $copy['lead']=$shared['hero_lead'];
        foreach(['card_title','products_title','learn_title','webinars_title','grow_title'] as $index=>$key) if(isset($shared[$key])) $copy['benefits'][$index][0]=$shared[$key];
    }
    return $copy;
}

/** Complete PDF bytes. Only public sponsor data supplied by the canonical model is printed. */
function fcc_sponsoring_pdf(array $sp): string {
    foreach(['mb_scrub','mb_ord','mb_convert_encoding','gzcompress'] as $fn) if(!function_exists($fn)) throw new RuntimeException('Sponsoring PDF prerequisite is unavailable.');
    $c=fcc_sponsoring_pdf_copy((string)($sp['locale'] ?? 'en')); $s=(array)($sp['sponsor'] ?? []);
    $name=FccSponsoringPdfDocument::clean((string)($s['name'] ?? ''),160);
    $url=fcc_sponsoring_pdf_url((string)($sp['url'] ?? ''));
    if($name==='' || $url==='') throw new InvalidArgumentException('Sponsoring PDF needs a public name and a valid personal URL.');
    $card=fcc_sponsoring_pdf_url((string)($s['card_url'] ?? ''));
    $shop=fcc_sponsoring_pdf_url((string)($s['shop_url'] ?? ''));
    $contact=fcc_sponsoring_pdf_url((string)($sp['contact']['whatsapp_url'] ?? '')) ?: $url.'#razgovor';
    $phone=FccSponsoringPdfDocument::clean((string)($s['phone'] ?? ''),50);
    $fbo=FccSponsoringPdfDocument::clean((string)($s['fbo'] ?? ''),40);
    $initials=FccSponsoringPdfDocument::clean((string)($s['initials'] ?? ''),3);
    if($initials==='') { $parts=preg_split('/\s+/u',$name) ?: []; foreach(array_slice($parts,0,2) as $part) $initials.=mb_substr($part,0,1,'UTF-8'); }
    $p=new FccSponsoringPdfDocument(); $p->page(); $p->rect(0,0,595.28,841.89,'F7F7ED');
    $p->text(38,29,'FOREVER CARD CLUB',11,'B'); $p->text(38,47,$c['invite'],7.5,'R','587468');
    $p->rect(0,72,595.28,321,'123D32'); $p->text(38,100,'FOREVER LIVING PRODUCTS',8,'B','DCECC0');
    $titleSize=37; foreach($c['title'] as $line) while($p->width($line,$titleSize,'B')>290) $titleSize-=0.25;
    foreach($c['title'] as $i=>$line) $p->text(38,129+$i*43,$line,$titleSize,'B',$i===2?'DCECC0':'FFFFFF');
    $p->block(38,283,275,$c['lead'],11,15,'R','E0E9DA');
    $p->text(38,360,$c['with'],7.5,'R','DCECC0'); $p->fit(38,372,278,15,$name,11,5,'B','FFFFFF');
    // Large, personal card using actual sponsor details and working links.
    $p->rect(351,97,208,345,'D9E1D3',21); $p->rect(347,93,208,345,'FFFFFF',21);
    $p->rect(352,98,198,142,'DCECC0',17); $p->text(366,113,'FOREVER CARD CLUB',7.5,'B');
    if(!$p->avatar(366,133,55,(string)($s['avatar_path'] ?? ''))) { $p->circle(393.5,160.5,27.5,'123D32'); $p->text(393.5-$p->width($initials,18,'B')/2,153,$initials,18,'B','FFFFFF'); }
    $p->fit(366,197,170,34,$name,16,5,'B');
    if($fbo!=='') $p->fit(366,243,170,13,$c['fbo'].': '.$fbo,7.5,4,'R','587468');
    $headline=FccSponsoringPdfDocument::clean((string)($s['headline'] ?? ''),120);
    if($headline!=='') $p->fit(366,259,170,30,$headline,9,5,'R','587468');
    $p->button(366,299,170,33,$c['shop'],$shop ?: $card ?: $url,'123D32','FFFFFF',9);
    $p->button(366,340,170,33,$c['contact'],$contact,'DCECC0','123D32',9);
    $p->fit(366,388,170,20,$c['open_card'],9,6,'B'); $p->link(366,383,170,30,$card ?: $url);
    $socials=array_values(array_filter((array)($sp['card']['socials'] ?? []),static fn($social)=>is_array($social) && fcc_sponsoring_pdf_url((string)($social['url'] ?? ''))!==''));
    foreach(array_slice($socials,0,4) as $index=>$social) {
        $sx=366+$index*43; $label=FccSponsoringPdfDocument::clean((string)($social['name'] ?? $social['label'] ?? ''),30);
        if($label==='') continue;
        $p->fit($sx,416,40,12,$label,6.5,4,'R','587468'); $p->link($sx,412,41,17,(string)$social['url']);
    }
    $p->fit(38,426,290,35,$c['everything'],19,13,'B');
    // Five short benefits, a spacious two-column editorial layout.
    foreach($c['benefits'] as $i=>[$title,$body]) {
        $col=$i%2; $row=intdiv($i,2); $x=38+$col*265; $y=473+$row*87;
        $p->circle($x+9,$y+8,9,'DCECC0'); $p->text($x+6.5,$y+3.5,(string)($i+1),8,'B');
        $p->fit($x+26,$y,220,27,$title,11,8,'B'); $p->block($x+26,$y+29,215,$body,9,12,'R','587468');
    }
    $p->rect(38,756,519,1,'D7DDCF'); $p->block(38,769,470,$c['independent'],7.2,9,'R','587468'); $p->text(545,801,'01',8,'B');

    $p->page(); $p->rect(0,0,595.28,841.89,'F7F7ED'); $p->text(38,29,'FOREVER CARD CLUB',11,'B');
    $p->fit(38,78,519,66,$c['next'],29,20,'B'); $p->block(38,155,495,$c['intro'],11,15,'R','587468');
    foreach($c['steps'] as $i=>[$title,$body]) {
        $y=215+$i*73; $p->circle(51,$y+11,13,'DCECC0'); $p->text(47.5,$y+6,(string)($i+1),11,'B');
        $p->fit(78,$y,460,29,$title,13,9,'B'); $p->block(78,$y+28,460,$body,10,13,'R','587468');
    }
    $p->rect(38,524,519,177,'123D32',13); $p->fit(58,546,336,39,$c['ready'],20,13,'B','FFFFFF');
    $p->text(58,594,$c['personal'],8,'R','DCECC0'); $p->fit(58,611,330,27,$name,15,5,'B','FFFFFF');
    if($phone!=='') $p->fit(58,648,330,16,$phone,10,5,'R','FFFFFF');
    if($fbo!=='') $p->text(58,670,$c['fbo'].': '.$fbo,8.5,'R','DCECC0');
    $p->qr(420,543,118,$url); $p->fit(412,671,133,20,$c['scan'],7.2,5,'R','DCECC0');
    // Always retain the actual personal URL in visible, selectable text.
    $p->link(38,524,369,177,$contact);
    $p->text(38,718,$c['page'],7.5,'B'); $p->fit(38,733,519,20,$url,8.5,5,'R','173F34'); $p->link(38,728,519,27,$url);
    $p->block(38,765,519,$c['nature'],7.4,10,'R','587468');
    $p->text(38,816,$c['date'].': '.gmdate('d.m.Y.'),7,'R','587468'); $p->text(545,816,'02',8,'B');
    return $p->output($c['card'].' | '.$name);
}
/* /Custom code: FC-2026-10-14 */
