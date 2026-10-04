<?php

return [
    // Prikazuje dugme "Vrati demo podatke" (briše SVE podatke). Na produkciji ostaviti false.
    'demo' => (bool) env('DUGOVANJA_DEMO', false),

    // Fakture koje dospevaju u narednih N dana su označene kao "uskoro".
    'upozorenje_dana' => (int) env('DUGOVANJA_UPOZORENJE_DANA', 7),

    // Podrazumevana valuta plaćanja (dana) za nove dobavljače i fakture.
    'podrazumevani_rok' => (int) env('DUGOVANJA_PODRAZUMEVANI_ROK', 15),

    'podsetnik' => [
        // Fakture koje dospevaju u narednih N dana ulaze u dnevni podsetnik (uz one koje kasne).
        'dana_pre' => (int) env('PODSETNIK_DANA_PRE', 3),
        'vreme' => env('PODSETNIK_VREME', '07:00'),
        'email' => env('PODSETNIK_EMAIL'),
        'telegram_token' => env('PODSETNIK_TELEGRAM_TOKEN'),
        'telegram_chat_id' => env('PODSETNIK_TELEGRAM_CHAT_ID'),
    ],
];
