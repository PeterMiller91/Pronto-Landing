# Pizzeria Pronto Pronto – Website mit Inhaber-Backend

Mobile-first Website für die Pizzeria Pronto Pronto (Marktplatz 6, 92648 Vohenstrauß)
mit geschütztem Backend, über das der Inhaber Speisekarte, Öffnungszeiten,
Hinweis-Leiste und Fotos selbst pflegt.

**Technik:** PHP 8.1+ und MySQL/MariaDB, ohne Framework und ohne Build-Schritt.
Läuft direkt auf Netcup-Webhosting mit Plesk.

## Funktionen

**Website**
- Live-Status „Aperto! Jetzt geöffnet bis 23 Uhr“ bzw. „Heute Ruhetag – morgen ab 17 Uhr“ (berücksichtigt Wochenplan, Sondertage und „heute spontan geschlossen“)
- Speisekarte mit Kategorien, Nummern, Preisen (klein/groß), Kennzeichnungen und „heute ausverkauft“
- Bestellzettel mit Preisen und Summe: Gäste tippen auf **+** (bei zwei Größen: klein/groß wählen), sehen die Summe und lesen die Nummern beim Anruf vor (bleibt im Browser gespeichert)
- **Pizza der Woche**: große Aktionskarte unter dem Startbild mit Aktionspreis („Du sparst …“), Enddatum und Knopf „Auf den Bestellzettel“
- **Als App speicherbar (PWA)**: Pizza-Symbol auf dem Startbildschirm, Kurzbefehle „Speisekarte“ und „Pizza der Woche“, Speisekarte auch ohne Netz (zuletzt geladener Stand)
- Echte Google-Bewertungen (Karussell) + Link zu allen Bewertungen
- Google Maps per **2-Klick-Lösung** (lädt erst nach Klick, DSGVO) + „Route planen“
- Hinweis-Leiste oben (z. B. Betriebsurlaub), automatisch ausblendbar
- Animationen (Dampf, Laufband, Basilikum …), respektieren „Bewegung reduzieren“
- Schriften lokal eingebunden (kein Google-Fonts-Abruf), keine Cookies, kein Tracking
- SEO: Meta-Description, Open Graph, strukturierte Daten (schema.org/Restaurant)

**Backend (`/admin/`)**
- Login mit Passwort-Hash, Sperre nach 5 Fehlversuchen (15 Min.), CSRF-Schutz, „Angemeldet bleiben“
- Übersicht: Status der Website, Schalter „Heute spontan geschlossen“, letzte Änderungen
- Speisekarte: Gerichte anlegen/bearbeiten/löschen, Preise, Kennzeichnungen, Verfügbarkeit per Schalter, Suche
- Öffnungszeiten: Wochenplan inkl. optionaler 2. Schicht (Mittag), Sondertage & Urlaub
- Hinweis-Leiste mit Live-Vorschau, Farbe und Ablaufdatum
- Pizza der Woche: Pizza wählen, Verkaufstext, Aktionspreis, Enddatum und Foto (`/admin/special.php`, Kachel auf der Übersicht)
- Fotos: Upload (Handy-Fotos werden automatisch verkleinert), Startbild wählen, Titel, Löschen
- Passwort ändern

## Ordnerstruktur

```
pronto-landing/
├── app/              PHP-Logik (nicht öffentlich)
│   ├── config.php                 Standard-Einstellungen
│   ├── config.local.example.php   Vorlage für Zugangsdaten
│   └── views/                     Kopf-/Fußbereiche
├── database/         SQL-Schema (MySQL + SQLite)
├── public/           ← Dokumentenstamm (Document Root)
│   ├── index.php                  Website
│   ├── admin/                     Backend
│   ├── setup.php                  Ersteinrichtung / Passwort-Notfall
│   ├── assets/                    CSS, JS, Schriften, Bilder
│   └── uploads/                   hochgeladene Fotos
└── storage/          nur für lokale SQLite-Tests
```

## Installation auf Netcup (Plesk)

1. **Datenbank anlegen:** Plesk → *Datenbanken* → *Datenbank hinzufügen* (MySQL/MariaDB). Name, Benutzer und Passwort notieren.
2. **Dateien hochladen:** Dieses Repository per Plesk-Git (Websites & Domains → Git), Dateimanager oder FTP nach `httpdocs/` übertragen.
3. **Dokumentenstamm setzen:** Plesk → *Hosting & DNS* → *Hosting* → Dokumentenstamm auf `httpdocs/public` stellen.
   (Falls das nicht geht, greift die `.htaccess` im Hauptordner als Fallback und sperrt `app/`, `database/`, `storage/`.)
4. **PHP-Version:** Plesk → *PHP* → PHP 8.1 oder neuer wählen. Empfohlen: `upload_max_filesize` = 16M, `post_max_size` = 20M.
5. **Zugangsdaten eintragen:** `app/config.local.example.php` nach `app/config.local.php` kopieren und Datenbankdaten sowie einen langen Zufallswert als `setup_token` eintragen.
6. **SSL:** Plesk → *SSL/TLS-Zertifikate* → kostenloses Let's-Encrypt-Zertifikat aktivieren.
7. **Einrichten:** `https://deine-domain.de/setup.php` aufrufen, `setup_token` eingeben und den Inhaber-Zugang (E-Mail + Passwort) anlegen. Tabellen, Speisekarte, Öffnungszeiten und Bewertungen werden automatisch angelegt.
8. **Absichern:** Danach den `setup_token` in `app/config.local.php` wieder leeren (`''`). Damit ist `setup.php` gesperrt.
9. **Login:** `https://deine-domain.de/admin/`, oder unten auf der Website „Inhaber-Login“.

**Passwort vergessen?** `setup_token` vorübergehend wieder eintragen, `setup.php` aufrufen und mit derselben E-Mail ein neues Passwort setzen. Danach den Token wieder leeren.

## Lokal testen

```bash
cd pronto-landing
php -S 127.0.0.1:8080 -t public
# Browser: http://127.0.0.1:8080/setup.php  (lokal ohne Token, SQLite in storage/)
```

## Vor dem Livegang ergänzen

- [ ] **Impressum** (`public/impressum.php`): Inhabername, E-Mail, ggf. USt-IdNr.
- [ ] **Datenschutzerklärung** (`public/datenschutz.php`): Entwurf prüfen und ergänzen lassen
- [ ] **Preise** im Backend bei allen Gerichten eintragen (ohne Preis zeigt die Website keinen an)
- [ ] Speisekarte mit der echten Karte abgleichen (Pizza Bianca, Rigatoni alla Panna und Chef-Salat stammen aus den Gäste-Bewertungen)
- [ ] Lieferinfo prüfen (`delivery_note` in der Tabelle `settings`)
- [ ] Weitere Fotos hochladen (Diavola, Ofen, Gastraum …)

## Hinweise zu den Bewertungen

Die Gästestimmen sind echte Google-Bewertungen, gekürzt und mit abgekürzten Nachnamen.
Sie liegen in der Tabelle `reviews` und lassen sich dort ein- und ausblenden (`is_visible`).
