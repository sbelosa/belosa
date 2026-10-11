<?php
defined('ALTUMCODE') || die();
return array_replace(fcc_event_defaults('seminar'),[
 'title'=>'Success Day Budapest 2026',
 'description'=>'Provedi dan s Forever zajednicom u Budimpešti. Success Day prilika je za susret s timom, nove ideje i razgovore o daljnjem razvoju poslovanja. Među najavljenim gostima su Aidan O’Hare, Peter Boots i Holly Stout.' . "\n\n" . 'Vrata se otvaraju u 9:30. Program traje od 11:00 do 17:00.',
 'mode'=>'in_person','location'=>'Riz Levente Sport- és Rendezvényközpont, Budapest',
 'date'=>'2026-10-31','time'=>'11:00','time_pending'=>0,'duration'=>360,
 'audience'=>'team','guests'=>1,'announce'=>0,'reminders'=>[10080,1440],
 'organizer'=>'Forever Living Products',
 'ticket_url'=>'https://www.eventim.hu/en/eventseries/forever-success-day-4241769/',
 'cover'=>'uploads/events/success-day-budapest-2026/1.jpg',
 'gallery'=>array_map(fn($n)=>'uploads/events/success-day-budapest-2026/'.$n.'.jpg',range(1,4)),
 'program'=>implode("\n",[
 'Aidan O’Hare | Predsjednik Forever Living Products |',
 'Peter Boots | Potpredsjednik za Europu |',
 'Holly Stout | Potpredsjednica za razvoj proizvoda |',
 ]),
]);
