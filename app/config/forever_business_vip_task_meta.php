<?php
/* Machine-readable completion rules for the FCC VIP 4 Core curriculum.
 * The member-facing wording remains in forever_business_vip_tasks.php; these
 * values prevent a Croatian number inside the copy (for example "4 CC") from
 * being mistaken for the number of actions required to complete a task. */
defined('ALTUMCODE') || die();

return [
    'starter' => [
        'targets' => [1, 10, 1, 3, 1, 1, 2, 3, 1, 1, 1, 2, 1, 2, 4, 2, 2, 1, 1, 1, 2, 3, 2, 1, 2, 1, 1, 1, 5, 1],
        'quick_targets' => [1, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1],
        'result_types' => [
            'planning', 'planning', 'planning', 'contact', 'content', 'planning',
            'customer_checkin', 'conversation', 'invitation', 'planning', 'follow_up',
            'recommendation', 'planning', 'customer_checkin', 'conversation', 'invitation',
            'planning', 'follow_up', 'planning', 'planning', 'customer_checkin',
            'conversation', 'invitation', 'planning', 'follow_up', 'content', 'planning',
            'training', 'follow_up', 'planning',
        ],
        'examples' => [
            4 => 'prvi osobni kontakt', 7 => 'korisnički check-in', 8 => 'prvi osobni kontakt',
            9 => 'poziv na marketing plan', 10 => 'follow-up nakon marketing plana',
            14 => 'korisnički check-in', 15 => 'prvi osobni kontakt', 16 => 'poziv na marketing plan',
            21 => 'korisnički check-in', 22 => 'prvi osobni kontakt',
            23 => 'poziv na marketing plan', 29 => 'kulturno zatvaranje razgovora',
        ],
        'fallbacks' => [
            2 => 'Ako ti popis danas ne dolazi lako, s mentorom prođi broj ljudi iz brzog cilja i za svakoga pronađi jednu prirodnu temu za razgovor.',
            3 => 'Ako ti se još nitko ne izdvaja, s mentorom prođi jedan primjer i odredi kakvoj bi osobi Marketing plan mogao biti zanimljiv.',
            11 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je već pokazala interes.',
            28 => 'Ako danas nemaš osobu za pokazivanje, uvježbaj korak s mentorom i zatraži jednu konkretnu povratnu informaciju.',
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
            20 => 'Ako još nemaš osobu koju razvijaš, odaberi nekoga iz tima kome bi odgovarao jedan mali korak uz tvoju podršku.',
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
            17 => 'Ako još nema budućeg voditelja, odaberi jednu osobu koja pokazuje inicijativu i ponudi joj malu ulogu koja joj odgovara.',
            20 => 'Ako još nema jasnog kandidata, s mentorom prođi jedan primjer i uvježbaj kako prepoznati pouzdanost, dosljednost i želju za pomaganjem.',
            22 => 'Ako danas nema osobe za razvojni razgovor, odradi desetominutnu vježbu s mentorom i zatraži konkretnu povratnu informaciju.',
            23 => 'Ako tim još nema potvrđenih gostiju, s mentorom uvježbaj kako organizirati završni krug toplih, osobnih poziva.',
            24 => 'Ako budući voditelj još nije spreman za vidljivu ulogu, dogovorite privatnu probu i prvi korak u kojem se osjeća ugodno.',
        ],
    ],
    'reactivation' => [
        'targets' => [1, 10, 1, 2, 1, 1, 3, 3, 1, 1, 1, 1, 1, 3, 3, 1, 1, 2, 1, 1, 5, 3, 2, 2, 3, 1, 1, 1, 5, 1],
        'quick_targets' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 2, 1],
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
            15 => 'prvi osobni kontakt', 16 => 'poziv na marketing plan', 17 => 'follow-up nakon marketing plana',
            22 => 'prvi osobni kontakt',
            23 => 'poziv na marketing plan', 29 => 'kulturno zatvaranje razgovora',
        ],
        'fallbacks' => [
            2 => 'Ako ti se ljudi za ponovno povezivanje danas ne izdvajaju lako, s mentorom prođi broj primjera iz brzog cilja i pronađi prirodan razlog za svaki razgovor.',
            11 => 'Ako danas nemaš gosta, nastavi razgovor s jednom osobom koja je odgovorila na tvoju poruku za ponovno povezivanje.',
            26 => 'Ako danas nitko ne čeka preporuku, pripremi jednu probnu rutinu iz aktualnog Forever materijala i provjeri je s mentorom.',
        ],
    ],
];
