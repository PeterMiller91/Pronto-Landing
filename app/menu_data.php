<?php
/**
 * Die echte Speisekarte der Pizzeria Pronto Pronto (Stand: gedruckte Karte).
 * Format je Gericht: [Nr., Name, Beschreibung, Preis klein (Cent|null), Preis groß (Cent|null), Zusatzstoffe, vegetarisch, scharf]
 * Wird bei der Einrichtung bzw. einmalig per Migration eingespielt – danach pflegt der Inhaber alles im Backend.
 */
declare(strict_types=1);

const ADDITIVES_LEGEND = [
    1 => 'mit Farbstoff',
    2 => 'mit Konservierungsstoff',
    3 => 'mit Antioxidationsmittel',
    4 => 'mit Geschmacksverstärker',
    5 => 'mit Phosphat',
    6 => 'mit Milcheiweiß',
    7 => 'mit Nitritpökelsalz',
];

function real_menu(): array
{
    $pizzaExtras = 'Extra-Zutaten je 1,50 € · Fisch 2,00 € · Oliven 1,00 € · Mozzarella 2,00 € · Käse 2,00 € · Joghurt-Dressing 1,00 €. Halb/halb mit verschiedenen Belägen: Aufpreis ab 1,50 €.';
    $familyExtras = 'Extra-Belag 49 × 32 cm ab 2,50 € (Fisch ab 3,50 €), 60 × 40 cm ab 4,00 € (Fisch ab 5,50 €). Halb/halb mit verschiedenen Belägen: Aufpreis ab 4,00 €. King-Kong-Pizza Ø 60 cm rund: Preise auf Anfrage.';

    return [
        [
            'slug' => 'pizza', 'name' => 'Pizza', 'size_small' => 'Ø 26 cm', 'size_large' => 'Ø 30 cm', 'note' => $pizzaExtras,
            'dishes' => [
                ['1', 'Natura', 'Tomatensoße, Käse', 750, 850, '2,6', 1, 0],
                ['2', 'Margherita', 'Tomatensoße, Mozzarella, Käse', 800, 900, '2,6', 1, 0],
                ['3', 'Salami', 'Salami', 900, 1000, '2,6,7', 0, 0],
                ['4', 'Rustika', 'Salami, frische Pilze', 900, 1000, '2,6,7', 0, 0],
                ['5', 'Delicia', 'Salami, Zwiebeln', 900, 1000, '2,6,7', 0, 0],
                ['6', 'Verona', 'Salami, Schinken, Peperoni', 900, 1000, '2,6,7', 0, 0],
                ['7', 'O sole mio', 'Salami, Schinken, frische Pilze', 900, 1000, '2,6,7', 0, 0],
                ['8', 'Diavolo', 'Salami, Pilze, Mozzarella – extra scharf', 900, 1000, '2,6,7', 0, 1],
                ['9', 'Vinese', 'Salami, Schinken, Ei', 900, 1000, '2,6,7', 0, 0],
                ['10', 'Prosciutto', 'Schinken', 900, 1000, '2,6,7', 0, 0],
                ['11', 'Regina', 'Schinken, Salami', 900, 1000, '2,6,7', 0, 0],
                ['12', 'Roma', 'Schinken, frische Pilze', 900, 1000, '2,6,7', 0, 0],
                ['13', 'Modena', 'Schinken, Zwiebeln', 900, 1000, '2,6,7', 0, 0],
                ['14', 'Montanara', 'Schinken, Peperoni', 900, 1000, '2,6,7', 0, 0],
                ['15', 'Venezia', 'Schinken, Sardellen', 1000, 1100, '2,6,7', 0, 0],
                ['16', 'Fiorentina', 'Schinken, frische Pilze, Paprika', 900, 1000, '2,6,7', 0, 0],
                ['17', 'Golosa', 'Schinken, Paprika, Broccoli', 900, 1000, '2,6,7', 0, 0],
                ['18', 'Hawaii', 'Schinken, Ananas', 900, 1000, '2,6,7', 0, 0],
                ['19', 'Funghi', 'Frische Pilze', 900, 950, '2,6', 1, 0],
                ['20', 'Capri', 'Schinken, frische Pilze, Ei', 900, 1000, '2,6,7', 0, 0],
                ['21', 'Bonito', 'Pilze, Paprika, Oliven', 900, 1050, '2,6', 1, 0],
                ['22', 'Amore', 'Pilze, Artischocken, Zwiebeln', 900, 1000, '2,6', 1, 0],
                ['23', 'Vegetaria', 'Frisches Gemüse, Cocktailtomaten', 900, 1000, '', 1, 0],
                ['24', 'Mix', 'Salami, Schinken, frische Pilze, Paprika', 900, 1000, '2,6,7', 0, 0],
                ['25', 'Speciale', 'Salami, Schinken, frische Pilze, Peperoni, Zwiebeln', 900, 1000, '2,6,7', 0, 0],
                ['26', 'Vulkano', 'Salami, Schinken, frische Pilze, Paprika, Oliven', 900, 1000, '2,6,7', 0, 0],
                ['27', 'Calzone', 'Gefüllte Pizzatasche mit Salami, Schinken, frischen Pilzen, Peperoni', 900, 1000, '2,6,7', 0, 0],
                ['27a', 'Calzone bianco', 'Gefüllte Pizzatasche mit Salami, Schinken, frischen Pilzen, Peperoni und Sahnesoße', 900, 1000, '2,6,7', 0, 0],
                ['28', 'Bolognese', 'Hackfleisch, Zwiebeln', 900, 950, '2,6', 0, 0],
                ['29', 'Bolognese Speziale', 'Hackfleisch, Salami, Schinken, frische Pilze, Peperoni', 900, 1000, '2,6,7', 0, 0],
                ['30', 'Pizza Bianca', 'Sahne, Salami, Schinken, frische Pilze', 900, 1000, '2,6,7', 0, 0],
                ['31', 'Quattro Formaggi', 'Vier verschiedene Käsesorten', 1000, 1100, '2,6', 1, 0],
                ['32', 'Rigoleto', 'Krabben, Knoblauch, Zwiebeln', 1050, 1150, '2,6', 0, 0],
                ['33', 'Tivoli', 'Krabben, Venusmuscheln, Knoblauch, Peperoni – scharf', 1050, 1150, '2,6', 0, 1],
                ['34', 'Calabrese', 'Krabben, Sardellen, Knoblauch, Zwiebeln', 1050, 1200, '2,6', 0, 0],
                ['35', 'Sicilia', 'Krabben, Sardellen, Oliven, Peperoni – scharf', 1050, 1200, '2,6', 0, 1],
                ['36', 'Marinara', 'Meeresfrüchte, Knoblauch', 1050, 1200, '2,6', 0, 0],
                ['37', 'Peskatore', 'Meeresfrüchte, Thunfisch, Sardellen, Knoblauch', 1050, 1200, '2,6', 0, 0],
                ['38', 'Tono e Cippola', 'Thunfisch, Zwiebeln', 1000, 1100, '2,6', 0, 0],
                ['39', 'Vongole', 'Venusmuscheln, Knoblauch', 1000, 1100, '2,6', 0, 0],
                ['40', 'Cortina', 'Tomatenscheiben, Mozzarella, Edamer, frischer Parmesan', 1000, 1100, '2,6', 1, 0],
                ['41', 'Ramazotti', 'Hackfleisch, Salami, Sardellen, Peperoni – scharf', 1000, 1150, '2,6,7', 0, 1],
                ['42', 'Panna', 'Sahne, Schinken', 900, 1000, '2,6,7', 0, 0],
                ['43', 'Pizzabrot', '', 450, 550, '', 1, 0],
                ['44', 'Pizzabrot mit Knoblauch', '', 500, 650, '', 1, 0],
                ['046', 'Trinacria', 'Sardellen, Oliven, Cocktailtomaten', 1000, 1150, '2', 0, 0],
                ['047', 'Sorrento', 'Schinken, Cocktailtomaten, Zwiebeln, Schafskäse', 900, 1000, '2,6,7', 0, 0],
                ['048', 'Chef', 'Schinken, Cocktailtomaten, Schafskäse, Broccoli', 1000, 1100, '2,6,7', 0, 0],
                ['049', 'Palermo', 'Paprika, Oliven, Cocktailtomaten, Schafskäse', 1000, 1100, '2,6', 1, 0],
                ['050', 'Catania', 'Cocktailtomaten, scharfe Salami, Oliven, Rucola', 1000, 1100, '', 0, 1],
                ['051', 'Toscana', 'Scharfe Salami, gebratene frische Pilze', 1000, 1100, '', 0, 1],
                ['052', 'Nudelpizza', 'Penne-Nudeln, Hackfleisch, Sahnesoße, Käse', 1000, 1100, '', 0, 0],
            ],
        ],
        [
            'slug' => 'familienpizza', 'name' => 'Familienpizza', 'size_small' => '49 × 32 cm', 'size_large' => '60 × 40 cm', 'note' => $familyExtras,
            'dishes' => [
                ['1', 'Natura', 'Tomatensoße, Käse', 1900, 2600, '2,6', 1, 0],
                ['2', 'Margherita', 'Tomatensoße, Mozzarella, Käse', 1900, 2600, '2,6', 1, 0],
                ['3', 'Salami', 'Salami', 2300, 3000, '2,6,7', 0, 0],
                ['4', 'Rustika', 'Salami, frische Pilze', 2300, 3000, '2,6,7', 0, 0],
                ['5', 'Delicia', 'Salami, Zwiebeln', 2300, 3000, '2,6,7', 0, 0],
                ['6', 'Verona', 'Salami, Schinken, Peperoni', 2300, 3000, '2,6,7', 0, 0],
                ['7', 'O sole mio', 'Salami, Schinken, frische Pilze', 2300, 3000, '2,6,7', 0, 0],
                ['8', 'Diavolo', 'Salami, frische Pilze, Mozzarella – extra scharf', 2300, 3000, '2,6,7', 0, 1],
                ['9', 'Vinese', 'Salami, Schinken, Ei', 2300, 3000, '2,6,7', 0, 0],
                ['10', 'Prosciutto', 'Schinken', 2300, 3000, '2,6,7', 0, 0],
                ['11', 'Regina', 'Schinken, Salami', 2300, 3000, '2,6,7', 0, 0],
                ['12', 'Roma', 'Schinken, frische Pilze', 2300, 3000, '2,6,7', 0, 0],
                ['13', 'Modena', 'Schinken, Zwiebeln', 2300, 3000, '2,6,7', 0, 0],
                ['14', 'Montanara', 'Schinken, Peperoni', 2300, 3000, '2,6,7', 0, 0],
                ['15', 'Venezia', 'Schinken, Sardellen', 2550, 3150, '2,6,7', 0, 0],
                ['16', 'Fiorentina', 'Schinken, frische Pilze, Paprika', 2300, 3000, '2,6,7', 0, 0],
                ['17', 'Golosa', 'Schinken, Paprika, Broccoli', 2300, 3000, '2,6,7', 0, 0],
                ['18', 'Hawaii', 'Schinken, Ananas', 2300, 3000, '2,6,7', 0, 0],
                ['19', 'Funghi', 'Frische Pilze', 2300, 3000, '2,6', 1, 0],
                ['20', 'Capri', 'Schinken, frische Pilze, Ei', 2300, 3000, '2,6,7', 0, 0],
                ['21', 'Bonito', 'Frische Pilze, Paprika, Oliven', 2300, 3000, '2,6', 1, 0],
                ['22', 'Amore', 'Frische Pilze, Artischocken, Zwiebeln', 2300, 3000, '2,6', 1, 0],
                ['23', 'Vegetaria', 'Frisches Gemüse, Cocktailtomaten', 2300, 3000, '', 1, 0],
                ['24', 'Mix', 'Salami, Schinken, frische Pilze, Paprika', 2400, 3000, '2,6,7', 0, 0],
                ['25', 'Speciale', 'Salami, Schinken, frische Pilze, Peperoni, Zwiebeln', 2400, 3000, '2,6,7', 0, 0],
                ['26', 'Vulkano', 'Salami, Schinken, frische Pilze, Paprika, Oliven', 2400, 3000, '2,6,7', 0, 0],
                ['28', 'Bolognese', 'Hackfleisch, Zwiebeln', 2400, 3000, '2,6', 0, 0],
                ['29', 'Bolognese Speziale', 'Hackfleisch, Salami, Schinken, frische Pilze, Peperoni', 2400, 3000, '2,6,7', 0, 0],
                ['30', 'Pizza Bianca', 'Sahne, Salami, Schinken, frische Pilze', 2300, 3000, '2,6,7', 0, 0],
                ['31', 'Quattro Formaggi', 'Vier verschiedene Käsesorten', 2500, 3100, '2,6', 1, 0],
                ['32', 'Rigoleto', 'Krabben, Knoblauch, Zwiebeln', 2950, 3300, '2,6', 0, 0],
                ['33', 'Tivoli', 'Krabben, Venusmuscheln, Knoblauch, Peperoni – scharf', 2950, 3300, '2,6', 0, 1],
                ['34', 'Calabrese', 'Krabben, Sardellen, Knoblauch, Zwiebeln', 2950, 3300, '2,6', 0, 0],
                ['35', 'Sicilia', 'Krabben, Sardellen, Oliven, Peperoni – scharf', 2950, 3300, '2,6', 0, 1],
                ['36', 'Marinara', 'Meeresfrüchte, Knoblauch', 2950, 3300, '2,6', 0, 0],
                ['37', 'Peskatore', 'Meeresfrüchte, Thunfisch, Sardellen, Knoblauch', 2950, 3300, '2,6', 0, 0],
                ['38', 'Tono e Cippola', 'Thunfisch, Zwiebeln', 2950, 3300, '2,6', 0, 0],
                ['39', 'Vongole', 'Venusmuscheln, Knoblauch', 2950, 3300, '2,6', 0, 0],
                ['40', 'Cortina', 'Tomatenscheiben, Mozzarella, Edamer, frischer Parmesan', 2600, 3200, '2,6', 1, 0],
                ['41', 'Ramazotti', 'Hackfleisch, Salami, Sardellen, Peperoni – scharf', 2800, 3200, '2,6,7', 0, 1],
                ['42', 'Panna', 'Sahne, Schinken', 2200, 3000, '2,6,7', 0, 0],
                ['046', 'Trinacria', 'Sardellen, Oliven, Cocktailtomaten', 2500, 3200, '2', 0, 0],
                ['047', 'Sorrento', 'Schinken, Cocktailtomaten, Zwiebeln, Schafskäse', 2300, 3100, '2,6,7', 0, 0],
                ['048', 'Chef', 'Schinken, Cocktailtomaten, Schafskäse, Broccoli', 2350, 3150, '2,6,7', 0, 0],
                ['049', 'Palermo', 'Paprika, Oliven, Cocktailtomaten, Schafskäse', 2350, 3150, '2,6', 1, 0],
                ['050', 'Catania', 'Cocktailtomaten, scharfe Salami, Oliven, Rucola', 2300, 2950, '', 0, 1],
                ['051', 'Toscana', 'Scharfe Salami, gebratene frische Pilze', 2300, 2950, '', 0, 1],
            ],
        ],
        [
            'slug' => 'pasta', 'name' => 'Pasta', 'size_small' => '', 'size_large' => '', 'note' => '',
            'dishes' => [
                ['45', 'Spaghetti Napoletana', 'Tomatensoße', 700, null, '2', 1, 0],
                ['46', 'Spaghetti Bolognese', 'Hackfleischsoße', 850, null, '2', 0, 0],
                ['47', 'Spaghetti Carbonara', 'Schinken, Sahne, Ei', 850, null, '2,6,7', 0, 0],
                ['48', 'Spaghetti Gorgonzola', 'Schinken, Sahne, Gorgonzola', 900, null, '1,6,7', 0, 0],
                ['49', 'Spaghetti alla Panna', 'Schinken, Sahne', 800, null, '1,6,7', 0, 0],
                ['50', 'Penne Napoletana', 'Tomatensoße', 700, null, '2', 1, 0],
                ['51', 'Penne Bolognese', 'Hackfleischsoße', 850, null, '2', 0, 0],
                ['52', 'Penne alla Panna', 'Schinken, Sahne', 850, null, '2,6,7', 0, 0],
                ['53', 'Penne Gorgonzola', 'Schinken, Sahne, Gorgonzola', 900, null, '2,6,7', 0, 0],
                ['54', 'Penne alla Chef', 'Tomaten, Sahne, Pilze', 850, null, '2,6', 1, 0],
                ['55', 'Penne al Forno', 'Überbacken', 900, null, '2,6', 0, 0],
                ['56', 'Penne all’Arrabbiata', 'Tomaten, Peperoni, Oliven – pikant', 850, null, '2', 1, 1],
                ['57', 'Tortellini Veneziane', 'Hackfleisch, Gorgonzola, Sahne', 1000, null, '2,6', 0, 0],
                ['58', 'Tortellini alla Panna', 'Schinken, Sahne', 900, null, '2,6,7', 0, 0],
                ['59', 'Tortellini Bolognese', 'Hackfleischsoße', 900, null, '2', 0, 0],
                ['60', 'Tortellini al Forno', 'Überbacken', 900, null, '2,6', 0, 0],
            ],
        ],
        [
            'slug' => 'insalate', 'name' => 'Insalate', 'size_small' => 'klein', 'size_large' => 'groß',
            'note' => 'Bitte beim Bestellen das gewünschte Dressing angeben: nur Essig und Öl oder Joghurt-Dressing.',
            'dishes' => [
                ['62', 'Insalata mista', 'Grüner Salat, Tomaten, Gurken, rote Zwiebeln', 500, 600, '', 1, 0],
                ['63', 'Insalata di pomodoro', 'Tomaten, Zwiebeln', 500, 600, '', 1, 0],
                ['64', 'Insalata di Cetrioli', 'Gurken, Zwiebeln', 500, 600, '', 1, 0],
                ['65', 'Insalata Italia', 'Grüner Salat, Gurken, Tomaten, Thunfisch, Vorderschinken, Käse, rote Zwiebeln', 600, 750, '2,7', 0, 0],
                ['66', 'Insalata Chef', 'Grüner Salat, Gurken, Eier, Tomaten, Artischocken, Mozzarella, Oliven', 600, 750, '2,6', 1, 0],
                ['67', 'Mozzarella alla Caprese', 'Tomaten, Mozzarella, Aceto Balsamico, Olivenöl, Basilikum', 600, 750, '', 1, 0],
                ['68', 'Insalata Museta', 'Grüner Salat, Tomaten, Gurken, Thunfisch, Zwiebeln, Mais', 600, 750, '2', 0, 0],
                ['69', 'Bauernsalat', 'Gemischter Salat, Schafskäse, Peperoni, Oliven, Zwiebeln, Mais', 600, 750, '2,6', 1, 0],
            ],
        ],
        [
            'slug' => 'weine', 'name' => 'Weine', 'size_small' => '', 'size_large' => '', 'note' => 'Flasche 0,7 l – Auswahl und genaue Preise gerne am Telefon.',
            'dishes' => [
                ['', 'Rotwein süß', '0,7 l – ab 10,00 €', null, null, '', 0, 0],
                ['', 'Rotwein trocken', '0,7 l – ab 10,00 €', null, null, '', 0, 0],
                ['', 'Weißwein süß', '0,7 l – ab 10,00 €', null, null, '', 0, 0],
                ['', 'Weißwein trocken', '0,7 l – ab 10,00 €', null, null, '', 0, 0],
            ],
        ],
    ];
}

/** Ersetzt Kategorien und Gerichte durch die echte Karte. */
function import_real_menu(): void
{
    $now = now();
    db()->beginTransaction();
    try {
        db_exec('DELETE FROM dishes');
        db_exec('DELETE FROM categories');
        foreach (real_menu() as $sort => $cat) {
            $catId = db_insert('categories', [
                'slug' => $cat['slug'], 'name' => $cat['name'], 'sort' => $sort + 1,
                'size_small' => $cat['size_small'], 'size_large' => $cat['size_large'], 'note' => $cat['note'],
            ]);
            foreach ($cat['dishes'] as $i => [$nr, $name, $desc, $small, $large, $add, $veg, $hot]) {
                db_insert('dishes', [
                    'category_id' => $catId, 'nr' => $nr, 'name' => $name, 'description' => $desc,
                    'price_cents' => $small, 'price_large_cents' => $large, 'additives' => $add,
                    'is_vegetarian' => $veg, 'is_spicy' => $hot, 'is_new' => 0, 'is_recommended' => 0,
                    'is_available' => 1, 'sort' => $i, 'updated_at' => $now,
                ]);
            }
        }
        db()->commit();
    } catch (Throwable $ex) {
        db()->rollBack();
        throw $ex;
    }
}
