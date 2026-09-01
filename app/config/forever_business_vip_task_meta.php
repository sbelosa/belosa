<?php
/* Machine-readable completion rules for the FCC VIP 4 Core curriculum.
 * The member-facing wording remains in forever_business_vip_tasks.php; these
 * values prevent a Croatian number inside the copy (for example "4 CC") from
 * being mistaken for the number of actions required to complete a task. */
defined('ALTUMCODE') || die();

return [
    'starter' => [
        'targets' => [1, 3, 1, 1, 1, 1, 2, 3, 1, 1, 1, 2, 1, 2, 4, 2, 2, 1, 1, 1, 2, 3, 2, 1, 2, 1, 1, 1, 5, 1],
        'quick_targets' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1],
        'result_types' => [
            'planning', 'planning', 'planning', 'contact', 'content', 'planning',
            'customer_checkin', 'conversation', 'invitation', 'planning', 'follow_up',
            'recommendation', 'planning', 'customer_checkin', 'conversation', 'invitation',
            'invitation', 'follow_up', 'planning', 'planning', 'customer_checkin',
            'conversation', 'invitation', 'planning', 'follow_up', 'content', 'planning',
            'training', 'follow_up', 'planning',
        ],
        'examples' => [
            4 => 'prvi osobni kontakt', 7 => 'korisnički check-in', 8 => 'prvi osobni kontakt',
            9 => 'poziv na marketing plan', 10 => 'follow-up nakon marketing plana',
            14 => 'korisnički check-in', 15 => 'prvi osobni kontakt', 16 => 'poziv na marketing plan',
            17 => 'priprema gosta za marketing plan', 18 => 'follow-up s mentorom',
            21 => 'korisnički check-in', 22 => 'prvi osobni kontakt',
            23 => 'poziv na marketing plan', 29 => 'kulturno zatvaranje razgovora',
        ],
        'allowed_result_types' => [
            7 => ['customer_checkin', 'follow_up', 'conversation', 'recommendation', 'order', 'training', 'no_response'],
            14 => ['customer_checkin', 'follow_up', 'conversation', 'recommendation', 'order', 'training', 'no_response'],
            21 => ['customer_checkin', 'follow_up', 'conversation', 'recommendation', 'order', 'training', 'no_response'],
        ],
        'fallbacks' => [
            2 => 'Za verziju brzog cilja kreni s jednom osobom koju dobro poznaješ i zapiši jednu temu o kojoj prirodno razgovarate.',
            3 => 'Ako ti se još nitko ne izdvaja, s mentorom prođi jedan primjer i odredi kakvoj bi osobi Marketing plan mogao biti zanimljiv.',
            4 => 'Ako danas još nisi spreman/na poslati poruku, napiši je kao nacrt i prođi s mentorom prije slanja.',
            7 => 'Za verziju brzog cilja odradi jedan check-in, jedan stvarni nastavak razgovora ili jednu mentorsku vježbu.',
            11 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je već pokazala interes.',
            14 => 'Za verziju brzog cilja odradi jednu od ponuđenih mogućnosti. Ako još nemaš kupca ni otvoreni razgovor, s mentorom pripremi jedan prijateljski check-in za svojeg prvog kupca.',
            17 => 'Za verziju brzog cilja, ako još nemaš potvrđenog gosta, spremi dvije rečenice i uvježbaj ih s mentorom; poslat ćeš ih kada gost potvrdi dolazak.',
            18 => 'Ako još nemaš gosta, s mentorom uvježbaj poziv na zajednički Zoom i dogovorite jedan termin koji možeš ponuditi.',
            20 => 'Ako ne nalaziš neki pregled ili postavku, otvori službeni račun zajedno s mentorom i zapiši što još treba provjeriti s Forever podrškom.',
            21 => 'Za verziju brzog cilja odradi jednu od ponuđenih mogućnosti. Ako još nemaš kupca ni otvoreni razgovor, s mentorom uvježbaj kako ćeš prvom kupcu objasniti podršku nakon odabira proizvoda.',
            28 => 'Ako danas nemaš osobu za pokazivanje, uvježbaj korak s mentorom i zatraži jednu konkretnu povratnu informaciju.',
        ],
        'checklists' => [
            20 => [
                'Pronađi službeni pregled osobnog CC-a, napretka prema 4 CC i evidentiranih kupaca.',
                'U profilu provjeri jesu li dostupna tržišta na kojima stvarno posluješ.',
                'Vrati se u Moj Forever i provjeri datum posljednje sinkronizacije.',
                'Ako je počeo novi mjesec, provjeri prikazuje li FCC početnih 0 CC dok ne stignu nove narudžbe.',
            ],
        ],
    ],
    'activator' => [
        'targets' => [1, 15, 2, 3, 3, 1, 3, 5, 2, 1, 5, 3, 1, 3, 6, 3, 1, 1, 1, 1, 5, 5, 3, 3, 5, 3, 1, 1, 10, 1],
        'quick_targets' => [1, 4, 1, 1, 1, 1, 1, 2, 1, 1, 2, 1, 1, 1, 2, 1, 1, 1, 1, 1, 2, 2, 1, 1, 2, 1, 1, 1, 3, 1],
        'result_types' => [
            'content', 'planning', 'invitation', 'follow_up', 'conversation', 'planning',
            'customer_checkin', 'conversation', 'invitation', 'planning', 'follow_up',
            'recommendation', 'planning', 'customer_checkin', 'conversation', 'invitation',
            'planning', 'follow_up', 'content', 'planning', 'customer_checkin', 'conversation',
            'invitation', 'planning', 'follow_up', 'recommendation', 'planning', 'onboarding',
            'follow_up', 'planning',
        ],
        'allowed_result_types' => [
            1 => ['content'],
        ],
        'checklists' => [
            1 => [
                'U FCC Aplikacijama odaberi „Kopiraj link aplikacije”.',
                'Zalijepi link u bio ili opis jednog profila i spremi promjenu.',
                'Otvori link iz javnog prikaza profila i potvrdi da radi.',
            ],
        ],
        'examples' => [
            3 => 'poziv na marketing plan', 5 => 'prvi osobni kontakt',
            7 => 'korisnički check-in', 8 => 'prvi osobni kontakt', 9 => 'poziv na marketing plan',
            10 => 'follow-up nakon marketing plana', 14 => 'korisnički check-in',
            15 => 'topla preporuka', 16 => 'poziv na marketing plan', 17 => 'follow-up nakon marketing plana',
            21 => 'korisnički check-in', 22 => 'prvi osobni kontakt',
            23 => 'poziv na marketing plan', 29 => 'kulturno zatvaranje razgovora',
        ],
        'fallbacks' => [
            1 => 'Za lakšu verziju na jednom profilu postavi samo link i nakon spremanja ga jednom otvori; kratki opis možeš doraditi kasnije.',
            2 => 'Ako ti popis danas ne dolazi lako, s mentorom prođi broj ljudi iz brzog cilja i za svakoga pronađi jednu prirodnu temu za razgovor.',
            17 => 'Ako još nemaš gosta, pripremi poruku dobrodošlice i pitanje s mentorom za sljedeći Marketing plan.',
            18 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je već pokazala interes.',
        ],
    ],
    'builder' => [
        'targets' => [1, 20, 3, 5, 1, 1, 5, 5, 3, 1, 1, 3, 1, 5, 7, 3, 2, 5, 1, 1, 5, 5, 3, 1, 1, 3, 1, 1, 10, 1],
        'quick_targets' => [1, 5, 1, 2, 1, 1, 2, 2, 1, 1, 1, 1, 1, 2, 3, 1, 1, 2, 1, 1, 2, 2, 1, 1, 1, 1, 1, 1, 3, 1],
        'result_types' => [
            'planning', 'planning', 'invitation', 'follow_up', 'content', 'planning',
            'customer_checkin', 'conversation', 'invitation', 'planning', 'follow_up',
            'recommendation', 'planning', 'customer_checkin', 'conversation', 'invitation',
            'planning', 'follow_up', 'onboarding', 'coaching', 'customer_checkin',
            'conversation', 'invitation', 'planning', 'follow_up', 'coaching', 'planning',
            'coaching', 'follow_up', 'planning',
        ],
        'examples' => [
            3 => 'poziv na marketing plan', 7 => 'korisnički check-in',
            9 => 'poziv na marketing plan', 11 => 'nastavak ranijeg interesa',
            14 => 'korisnički check-in', 15 => 'topla preporuka', 16 => 'poziv na marketing plan',
            21 => 'korisnički check-in',
            22 => 'poslovni razgovor', 23 => 'poziv na marketing plan',
            29 => 'kulturno zatvaranje razgovora',
        ],
        'fallbacks' => [
            2 => 'Ako ti pregled danas ne dolazi lako, s mentorom prođi broj ljudi iz brzog cilja i odredi kojoj skupini prirodno pripadaju.',
            11 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je već pokazala poslovni interes.',
            13 => 'Ako mentor trenutačno nije dostupan, pošalji mu svoj kratki pregled i predloži termin razgovora.',
            17 => 'Za verziju brzog cilja, ako još nemaš potvrđenog gosta, pripremi poruku dobrodošlice i jedan termin koji ćeš moći ponuditi prvom gostu.',
            20 => 'Ako još nemaš osobu koju razvijaš, odaberi nekoga iz tima kome bi odgovarao jedan mali korak uz tvoju podršku.',
            24 => 'Ako tim trenutačno nema gostiju, sa suradnicima unaprijed dogovori tko vodi follow-up, a kada se uključuje tvoja podrška.',
            28 => 'Ako još nemaš osobu za razvojni ciklus, odaberi nekoga iz tima i najprije dogovorite mali prvi korak i termin provjere.',
        ],
    ],
    'leader' => [
        'targets' => [8, 3, 1, 1, 2, 1, 1, 3, 2, 1, 2, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 3, 1, 1],
        'quick_targets' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        'result_types' => [
            'coaching', 'coaching', 'planning', 'planning', 'coaching', 'planning',
            'coaching', 'coaching', 'coaching', 'planning', 'coaching', 'coaching',
            'training', 'coaching', 'coaching', 'coaching', 'coaching', 'coaching',
            'onboarding', 'coaching', 'planning', 'coaching', 'planning', 'coaching',
            'coaching', 'coaching', 'planning', 'coaching', 'planning', 'planning',
        ],
        'examples' => [
            7 => 'korisnički check-in',
            8 => 'prvi osobni kontakt', 9 => 'poziv na marketing plan',
            12 => 'korisnički check-in', 16 => 'poziv na marketing plan',
        ],
        'fallbacks' => [
            3 => 'Ako tim još nije aktivan, s mentorom uvježbaj jednu kratku koordinacijsku provjeru za sljedeći Marketing plan.',
            4 => 'Ako danas nema gostiju, pregledaj jedan otvoreni razgovor zajedno s osobom koja ga vodi i pronađite najbolji sljedeći korak.',
            16 => 'Ako danas nema osobe za vježbu, napiši ili snimi svoju verziju poziva i zatraži povratnu informaciju mentora.',
            17 => 'Ako tim još nema potvrđenog gosta, s jednim suradnikom pripremi jedan osobni poziv i jedan termin za probni follow-up Zoom.',
            20 => 'Ako još nema jasnog kandidata, s mentorom prođi jedan primjer i uvježbaj kako prepoznati pouzdanost, dosljednost i želju za pomaganjem.',
            22 => 'Ako trenutačno nemaš osobu u razvoju za Supervisor ili Manager poziciju, odradi vježbu s mentorom i zatraži jednu konkretnu povratnu informaciju.',
            23 => 'Ako tim još nema potvrđenih gostiju, s mentorom uvježbaj kako organizirati završni krug toplih, osobnih poziva.',
            24 => 'Ako za ovaj Marketing plan nema dogovorene uloge, napravi privatnu probu kratkog osobnog iskustva i pošalji je Stjepanu ili mentoru na povratnu informaciju.',
            25 => 'Ako trenutačno nema otvorenih razgovora, s jednim suradnikom uvježbaj follow-up i dogovorite kada se trebaš uključiti.',
            26 => 'Za verziju brzog cilja, ako danas nema dva otvorena razgovora, odradite jedan stvarni razgovor ili kratku simulaciju s mentorom.',
        ],
    ],
    'reactivation' => [
        'targets' => [1, 10, 1, 2, 1, 1, 3, 3, 1, 1, 1, 1, 1, 3, 3, 1, 1, 2, 1, 1, 5, 3, 2, 2, 3, 1, 1, 1, 5, 1],
        'quick_targets' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1],
        'result_types' => [
            'planning', 'planning', 'invitation', 'contact', 'planning', 'planning',
            'customer_checkin', 'contact', 'invitation', 'planning', 'follow_up', 'planning',
            'planning', 'customer_checkin', 'conversation', 'invitation', 'planning',
            'follow_up', 'content', 'planning', 'follow_up', 'conversation', 'invitation',
            'planning', 'follow_up', 'recommendation', 'planning', 'coaching', 'follow_up',
            'planning',
        ],
        'examples' => [
            3 => 'poziv na marketing plan', 7 => 'korisnički check-in',
            9 => 'poziv na marketing plan', 14 => 'korisnički check-in',
            4 => 'poruka za ponovno povezivanje', 8 => 'poruka za ponovno povezivanje',
            15 => 'prvi osobni kontakt', 16 => 'poziv na marketing plan', 17 => 'follow-up s mentorom',
            22 => 'prvi osobni kontakt',
            23 => 'poziv na marketing plan', 29 => 'kulturno zatvaranje razgovora',
        ],
        'allowed_result_types' => [
            7 => ['customer_checkin', 'follow_up', 'conversation', 'recommendation', 'order', 'training', 'no_response'],
            14 => ['customer_checkin', 'follow_up', 'conversation', 'recommendation', 'order', 'training', 'no_response'],
            21 => ['follow_up', 'conversation', 'contact', 'customer_checkin', 'recommendation', 'order', 'new_partner', 'no_response', 'training'],
        ],
        'fallbacks' => [
            2 => 'Ako ti se ljudi za ponovno povezivanje danas ne izdvajaju lako, s mentorom prođi broj primjera iz brzog cilja i pronađi prirodan razlog za svaki razgovor.',
            7 => 'Za verziju brzog cilja odradi jedan check-in ili jedan nastavak razgovora. Ako danas nemaš kupca ni raniji razgovor za nastavak, s mentorom uvježbaj jedan prijateljski korisnički check-in.',
            10 => 'Ako još ne znaš što pitati, zapiši jednu temu u kojoj želiš više sigurnosti i uz pomoć mentora pretvori je u kratko pitanje.',
            11 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je odgovorila na tvoju poruku za ponovno povezivanje.',
            14 => 'Za verziju brzog cilja odradi jedan check-in ili jedan nastavak razgovora. Ako danas nemaš kupca ni raniji razgovor za nastavak, s mentorom pripremi jedan prijateljski korisnički check-in.',
            17 => 'Ako još nemaš gosta, s mentorom pripremi pitanje i jedan termin koji ćeš ponuditi nakon sljedećeg Marketing plana.',
            26 => 'Ako danas nitko ne čeka preporuku, pripremi jednu probnu rutinu iz aktualnog Forever materijala i provjeri je s mentorom.',
        ],
    ],
];
