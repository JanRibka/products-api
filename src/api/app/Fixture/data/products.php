<?php

/**
 * Single source of truth for the fixture seed data.
 *
 * The product data was collected by hand from the shop's brand listing pages
 * on krytypodmotor.cz (https://www.krytypodmotor.cz) on 2026-10-11. Prices are
 * the shop's customer prices including 21 % VAT in CZK and will change over
 * time. Codes are the shop's own codes (prefix PM/PX = plastic, MM/MX = steel).
 * The shop lists the same physical part under several vehicles, so one listing
 * per code was used - that keeps the code unique. Descriptions were written for
 * this project. No aluminium (Hliník) cover was found on the listing pages, so
 * the material exists in the lookup table but has no products.
 *
 * This file is the ONE place the data lives; the fixture classes only read it.
 */

declare(strict_types=1);

return [
  'brands' => [
    'Škoda',
    'Audi',
    'Volkswagen',
    'BMW',
    'Citroën',
    'Dacia',
    'Cupra',
  ],

  'materials' => [
    'Plast',
    'Plech',
    'Hliník',
  ],

  /**
   * @var array<int, array{code: string, name: string, brand: string,
   *   material: string, price: string, description: string}>
   */
  'products' => [
    [
      'code' => 'PM00431',
      'name' => 'Kryt pod motor SKODA FABIA I Hatchback (6Y2) - Plast (6Q0825237L)',
      'brand' => 'Škoda',
      'material' => 'Plast',
      'price' => '968.00',
      'description' => 'Plastový kryt motorového prostoru s OEM číslem 6Q0825237L. Chrání před vodou a blátem.',
    ],
    [
      'code' => 'PM00433',
      'name' => 'Kryt pod motor SKODA FABIA I Kombi (6Y5) - Plast (6Q0825237P)',
      'brand' => 'Škoda',
      'material' => 'Plast',
      'price' => '993.00',
      'description' => 'Plastový kryt pod motor, OEM číslo 6Q0825237P. Lehký a odolný, dle podvozku vozu.',
    ],
    [
      'code' => 'PM03448',
      'name' => 'Kryt pod motor SKODA CITIGO - Plast',
      'brand' => 'Škoda',
      'material' => 'Plast',
      'price' => '1331.00',
      'description' => 'Plastový kryt pod motor pro Škodu Citigo. Omezuje vnikání nečistot a soli.',
    ],
    [
      'code' => 'MM00904',
      'name' => 'Kryt pod motor SKODA CITIGO - Plech',
      'brand' => 'Škoda',
      'material' => 'Plech',
      'price' => '3930.00',
      'description' => 'Ocelový kryt pod motor pro Škodu Citigo. Pevnější varianta pro vyšší ochranu.',
    ],
    [
      'code' => 'MX04548',
      'name' => 'Kryt pod motor a převodovku SKODA KODIAQ (NS7, NV7) - Plech',
      'brand' => 'Škoda',
      'material' => 'Plech',
      'price' => '4459.00',
      'description' => 'Ocelový kryt chránící motor i převodovku Škody Kodiaq.',
    ],
    [
      'code' => 'PM00432',
      'name' => 'Kryt pod motor AUDI A1 (8X1, 8XK) - Plast (6Q0825237R)',
      'brand' => 'Audi',
      'material' => 'Plast',
      'price' => '968.00',
      'description' => 'Plastový kryt pod motor pro Audi A1, OEM číslo 6Q0825237R.',
    ],
    [
      'code' => 'PM00412',
      'name' => 'Kryt pod motor AUDI A1 (8X1, 8XK) - Plast (6Q0825537P)',
      'brand' => 'Audi',
      'material' => 'Plast',
      'price' => '1452.00',
      'description' => 'Plastový kryt pod motor pro Audi A1, OEM číslo 6Q0825537P.',
    ],
    [
      'code' => 'MM04452',
      'name' => 'AUDI A1 Allstreet (GBH) Kryt pod motor - Plech',
      'brand' => 'Audi',
      'material' => 'Plech',
      'price' => '4840.00',
      'description' => 'Ocelový kryt pod motor pro Audi A1 Allstreet.',
    ],
    [
      'code' => 'MM04454',
      'name' => 'AUDI A8 (4H2, 4H8, 4HC, 4HL) Kryt pod motor - Plech',
      'brand' => 'Audi',
      'material' => 'Plech',
      'price' => '6141.00',
      'description' => 'Ocelový kryt pod motor pro Audi A8 (řada 4H).',
    ],
    [
      'code' => 'MM00587',
      'name' => 'Kryt diferenciálu a převodovky VOLKSWAGEN AMAROK (2HA, 2HB, S1B, S6B, S7A, S7B) - Plech',
      'brand' => 'Volkswagen',
      'material' => 'Plech',
      'price' => '4152.00',
      'description' => 'Ocelový kryt diferenciálu a převodovky pro VW Amarok.',
    ],
    [
      'code' => 'MX04500',
      'name' => 'Kryt pod motor a převodovku VOLKSWAGEN ID.4 (E21) - Plech',
      'brand' => 'Volkswagen',
      'material' => 'Plech',
      'price' => '6057.00',
      'description' => 'Ocelový kryt pod pohonnou jednotku VW ID.4.',
    ],
    [
      'code' => 'MX04533',
      'name' => 'Kryt pod motor a chladič VOLKSWAGEN CRAFTER Box (SY_, SX_) - Plech',
      'brand' => 'Volkswagen',
      'material' => 'Plech',
      'price' => '8985.00',
      'description' => 'Ocelový kryt chránící motor a chladič dodávky VW Crafter.',
    ],
    [
      'code' => 'MX04547',
      'name' => 'Kryt pod motor a převodovku VOLKSWAGEN SHARAN (7N1, 7N2) - Plech',
      'brand' => 'Volkswagen',
      'material' => 'Plech',
      'price' => '3794.00',
      'description' => 'Ocelový kryt motoru a převodovky VW Sharan.',
    ],
    [
      'code' => 'MM04458',
      'name' => 'CITROËN C4 SPACETOURER (3D_) Kryt pod motor - Plech',
      'brand' => 'Citroën',
      'material' => 'Plech',
      'price' => '5324.00',
      'description' => 'Ocelový kryt pod motor pro Citroën C4 SpaceTourer.',
    ],
    [
      'code' => 'MX04509',
      'name' => 'Kryt pod motor a převodovku CITROËN C3 - Plech',
      'brand' => 'Citroën',
      'material' => 'Plech',
      'price' => '4326.00',
      'description' => 'Ocelový kryt motoru a převodovky pro Citroën C3.',
    ],
    [
      'code' => 'MX04510',
      'name' => 'Kryt pod motor a převodovku CITROËN C3 - Plech',
      'brand' => 'Citroën',
      'material' => 'Plech',
      'price' => '5391.00',
      'description' => 'Ocelový kryt motoru a převodovky pro Citroën C3, jiná varianta než MX04509.',
    ],
    [
      'code' => 'MM04460',
      'name' => 'DACIA SPRING Kryt pod motor - Plech',
      'brand' => 'Dacia',
      'material' => 'Plech',
      'price' => '4205.00',
      'description' => 'Ocelový kryt pod motor pro elektromobil Dacia Spring.',
    ],
    [
      'code' => 'MX04511',
      'name' => 'Kryt pod motor a převodovku DACIA BIGSTER - Plech',
      'brand' => 'Dacia',
      'material' => 'Plech',
      'price' => '3727.00',
      'description' => 'Ocelový kryt motoru a převodovky pro Dacii Bigster.',
    ],
    [
      'code' => 'MM04459',
      'name' => 'DACIA JOGGER Kryt pod motor - Plech',
      'brand' => 'Dacia',
      'material' => 'Plech',
      'price' => '4296.00',
      'description' => 'Ocelový kryt pod motor pro Dacii Jogger.',
    ],
    [
      'code' => 'PX00063',
      'name' => 'Kryt pod motor a automatickou převodovku BMW 5 Sedan (E60) - Plast (51717200846, 51717033761)',
      'brand' => 'BMW',
      'material' => 'Plast',
      'price' => '1331.00',
      'description' => 'Plastový kryt motoru a automatické převodovky, OEM čísla 51717200846 a 51717033761.',
    ],
    [
      'code' => 'PX00065',
      'name' => 'Kryt pod motor BMW 1 (E87) - Plast (51757129318)',
      'brand' => 'BMW',
      'material' => 'Plast',
      'price' => '1331.00',
      'description' => 'Plastový kryt pod motor, OEM číslo 51757129318.',
    ],
    [
      'code' => 'MM04456',
      'name' => 'BMW 3 Sedan (E46) Kryt pod motor - Plech',
      'brand' => 'BMW',
      'material' => 'Plech',
      'price' => '5566.00',
      'description' => 'Ocelový kryt pod motor pro BMW řady 3 (E46).',
    ],
    [
      'code' => 'MM03462',
      'name' => 'Kryt pod motor BMW 1 (F20) - Plech',
      'brand' => 'BMW',
      'material' => 'Plech',
      'price' => '6194.00',
      'description' => 'Ocelový kryt pod motor pro BMW řady 1 (F20).',
    ],
    [
      'code' => 'MM04453',
      'name' => 'CUPRA FORMENTOR (KM7) Kryt pod motor - Plech',
      'brand' => 'Cupra',
      'material' => 'Plech',
      'price' => '4901.00',
      'description' => 'Ocelový kryt pod motor pro Cupru Formentor.',
    ],
  ],
];
