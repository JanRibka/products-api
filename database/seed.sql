-- Products API - seed data (mirrors the application fixtures)
--
-- GENERATED FILE - do not edit by hand. Regenerate with:
--   php tracker fixtures:dump-sql
-- The data lives in src/api/app/Fixture/data/products.php (single source of truth).
SET NAMES utf8mb4;

TRUNCATE TABLE `product`;
DELETE FROM `brand`;
DELETE FROM `material`;
ALTER TABLE `brand` AUTO_INCREMENT = 1;
ALTER TABLE `material` AUTO_INCREMENT = 1;

INSERT INTO `brand` (`name`) VALUES
  ('Škoda'),
  ('Audi'),
  ('Volkswagen'),
  ('BMW'),
  ('Citroën'),
  ('Dacia'),
  ('Cupra');

INSERT INTO `material` (`name`) VALUES
  ('Plast'),
  ('Plech'),
  ('Hliník');

-- brandId:    1 Škoda, 2 Audi, 3 Volkswagen, 4 BMW, 5 Citroën, 6 Dacia, 7 Cupra
-- materialId: 1 Plast, 2 Plech, 3 Hliník
INSERT INTO `product` (`code`, `name`, `description`, `price`, `brandId`, `materialId`, `createdAt`, `updatedAt`) VALUES
  ('PM00431', 'Kryt pod motor SKODA FABIA I Hatchback (6Y2) - Plast (6Q0825237L)', 'Plastový kryt motorového prostoru s OEM číslem 6Q0825237L. Chrání před vodou a blátem.', 968.00, 1, 1, NOW(), NOW()),
  ('PM00433', 'Kryt pod motor SKODA FABIA I Kombi (6Y5) - Plast (6Q0825237P)', 'Plastový kryt pod motor, OEM číslo 6Q0825237P. Lehký a odolný, dle podvozku vozu.', 993.00, 1, 1, NOW(), NOW()),
  ('PM03448', 'Kryt pod motor SKODA CITIGO - Plast', 'Plastový kryt pod motor pro Škodu Citigo. Omezuje vnikání nečistot a soli.', 1331.00, 1, 1, NOW(), NOW()),
  ('MM00904', 'Kryt pod motor SKODA CITIGO - Plech', 'Ocelový kryt pod motor pro Škodu Citigo. Pevnější varianta pro vyšší ochranu.', 3930.00, 1, 2, NOW(), NOW()),
  ('MX04548', 'Kryt pod motor a převodovku SKODA KODIAQ (NS7, NV7) - Plech', 'Ocelový kryt chránící motor i převodovku Škody Kodiaq.', 4459.00, 1, 2, NOW(), NOW()),
  ('PM00432', 'Kryt pod motor AUDI A1 (8X1, 8XK) - Plast (6Q0825237R)', 'Plastový kryt pod motor pro Audi A1, OEM číslo 6Q0825237R.', 968.00, 2, 1, NOW(), NOW()),
  ('PM00412', 'Kryt pod motor AUDI A1 (8X1, 8XK) - Plast (6Q0825537P)', 'Plastový kryt pod motor pro Audi A1, OEM číslo 6Q0825537P.', 1452.00, 2, 1, NOW(), NOW()),
  ('MM04452', 'AUDI A1 Allstreet (GBH) Kryt pod motor - Plech', 'Ocelový kryt pod motor pro Audi A1 Allstreet.', 4840.00, 2, 2, NOW(), NOW()),
  ('MM04454', 'AUDI A8 (4H2, 4H8, 4HC, 4HL) Kryt pod motor - Plech', 'Ocelový kryt pod motor pro Audi A8 (řada 4H).', 6141.00, 2, 2, NOW(), NOW()),
  ('MM00587', 'Kryt diferenciálu a převodovky VOLKSWAGEN AMAROK (2HA, 2HB, S1B, S6B, S7A, S7B) - Plech', 'Ocelový kryt diferenciálu a převodovky pro VW Amarok.', 4152.00, 3, 2, NOW(), NOW()),
  ('MX04500', 'Kryt pod motor a převodovku VOLKSWAGEN ID.4 (E21) - Plech', 'Ocelový kryt pod pohonnou jednotku VW ID.4.', 6057.00, 3, 2, NOW(), NOW()),
  ('MX04533', 'Kryt pod motor a chladič VOLKSWAGEN CRAFTER Box (SY_, SX_) - Plech', 'Ocelový kryt chránící motor a chladič dodávky VW Crafter.', 8985.00, 3, 2, NOW(), NOW()),
  ('MX04547', 'Kryt pod motor a převodovku VOLKSWAGEN SHARAN (7N1, 7N2) - Plech', 'Ocelový kryt motoru a převodovky VW Sharan.', 3794.00, 3, 2, NOW(), NOW()),
  ('MM04458', 'CITROËN C4 SPACETOURER (3D_) Kryt pod motor - Plech', 'Ocelový kryt pod motor pro Citroën C4 SpaceTourer.', 5324.00, 5, 2, NOW(), NOW()),
  ('MX04509', 'Kryt pod motor a převodovku CITROËN C3 - Plech', 'Ocelový kryt motoru a převodovky pro Citroën C3.', 4326.00, 5, 2, NOW(), NOW()),
  ('MX04510', 'Kryt pod motor a převodovku CITROËN C3 - Plech', 'Ocelový kryt motoru a převodovky pro Citroën C3, jiná varianta než MX04509.', 5391.00, 5, 2, NOW(), NOW()),
  ('MM04460', 'DACIA SPRING Kryt pod motor - Plech', 'Ocelový kryt pod motor pro elektromobil Dacia Spring.', 4205.00, 6, 2, NOW(), NOW()),
  ('MX04511', 'Kryt pod motor a převodovku DACIA BIGSTER - Plech', 'Ocelový kryt motoru a převodovky pro Dacii Bigster.', 3727.00, 6, 2, NOW(), NOW()),
  ('MM04459', 'DACIA JOGGER Kryt pod motor - Plech', 'Ocelový kryt pod motor pro Dacii Jogger.', 4296.00, 6, 2, NOW(), NOW()),
  ('PX00063', 'Kryt pod motor a automatickou převodovku BMW 5 Sedan (E60) - Plast (51717200846, 51717033761)', 'Plastový kryt motoru a automatické převodovky, OEM čísla 51717200846 a 51717033761.', 1331.00, 4, 1, NOW(), NOW()),
  ('PX00065', 'Kryt pod motor BMW 1 (E87) - Plast (51757129318)', 'Plastový kryt pod motor, OEM číslo 51757129318.', 1331.00, 4, 1, NOW(), NOW()),
  ('MM04456', 'BMW 3 Sedan (E46) Kryt pod motor - Plech', 'Ocelový kryt pod motor pro BMW řady 3 (E46).', 5566.00, 4, 2, NOW(), NOW()),
  ('MM03462', 'Kryt pod motor BMW 1 (F20) - Plech', 'Ocelový kryt pod motor pro BMW řady 1 (F20).', 6194.00, 4, 2, NOW(), NOW()),
  ('MM04453', 'CUPRA FORMENTOR (KM7) Kryt pod motor - Plech', 'Ocelový kryt pod motor pro Cupru Formentor.', 4901.00, 7, 2, NOW(), NOW());
