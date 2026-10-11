<?php
defined('ALTUMCODE') || die();
return array_replace(fcc_event_defaults('seminar'),[
 'title'=>'Success Day Opatija 2026',
 'description'=>'Jedan dan za nove ideje, susrete i tvoj sljedeći korak. U Opatiji nas čekaju novosti, iskustva Forever lidera i priznanja za ostvareni napredak.',
 'mode'=>'in_person','location'=>'Amadria Park Hotel Royal, Opatija',
 'date'=>'2026-10-17','time'=>'11:00','time_pending'=>0,'duration'=>360,
 'audience'=>'team','guests'=>1,'announce'=>0,'reminders'=>[10080,1440],
 'organizer'=>'Forever Living Products Hrvatska',
 'ticket_url'=>'https://foreverliving.com/shop/hrv/hr-hr/success-day',
 'cover'=>'uploads/events/success-day-opatija-2026/4382.jpg',
 'gallery'=>array_map(fn($n)=>'uploads/events/success-day-opatija-2026/'.$n.'.jpg',range(4382,4391)),
 'program'=>implode("\n",[
 'Manda Korenić i Snježana Ivetac | Sapphire Manager i Eagle Manager | Voditeljice programa',
 'Branka Džaja, dipl. oec. | Area Manager | Novosti iz ureda',
 'Miran Arbeiter, dr. med. | Senior Manager | Novi proizvodi',
 'Simona Videnšek | Manager | Sponzor koji stvara…',
 'Silvija Mikuličić | Manager | Eagle se ne događa…',
 'Ismeta Kapetanović Božić | Manager | Eye on Athens',
 'Tena Balov | Assistant Manager | Eye on Athens',
 'Jadranka Kraljić Pavletić | Soaring Manager | Global Rally svake godine',
 'Stjepan Beloša | Diamond Sapphire Manager | Kako graditi Forever posao online',
 ]),
]);
