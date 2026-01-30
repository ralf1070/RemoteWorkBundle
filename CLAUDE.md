# RemoteWorkBundle - Kimai Plugin

## Übersicht

Plugin zur Erfassung von Remote-Arbeit (Homeoffice und Dienstreisen) in Kimai.

## Funktionen

### Homeoffice & Dienstreisen
- Tagesgenaue Erfassung (volle oder halbe Tage)
- Datumsbereich möglich (von-bis)
- Nur Arbeitstage werden eingetragen (Wochenenden werden übersprungen)
- Beide Typen verwenden das gleiche Formular und die gleiche Logik

### Überlappungsprüfung
- Warnung bei bestehenden Remote-Work-Einträgen
- Warnung bei Abwesenheiten (Urlaub, Krankheit) - wenn WorkContractBundle installiert
- Warnung bei Feiertagen - wenn WorkContractBundle installiert
- Benutzer kann Warnung ignorieren und trotzdem speichern

### Genehmigungsworkflow (optional)
- Konfigurierbar über Systemeinstellungen
- Status: Neu, Genehmigt, Abgelehnt
- Batch-Aktionen für Genehmigung/Ablehnung

### Excel-Export
- Export-Button oben rechts (Page Actions)
- Exportiert alle Einträge eines Jahres für einen Benutzer
- Verwendet Kimai's `AnnotatedObjectExporter` mit Entity-Annotations
- Route: `remote_work_export`

### iCal-Export
- iCal-Button oben rechts (Page Actions)
- Exportiert alle Einträge eines Jahres als .ics Datei
- RFC 5545 konform
- Route: `remote_work_ical`

**iCal-Eigenschaften:**
| Property | Wert |
|----------|------|
| UID | `remote-work-{user_id}-{YYYYMMDD}-{type}@{domain}` |
| SEQUENCE | `current_timestamp / 10` (monoton steigend) |
| DTSTAMP | Export-Zeitpunkt |
| DTSTART/DTEND | Ganztägige Events |
| SUMMARY | Übersetzter Typ + Kommentar (z.B. "Homeoffice: Projektarbeit") |
| DESCRIPTION | Kommentar (falls vorhanden) |

**Hinweise:**
- Alle Texte werden übersetzt (DE: "Homeoffice", EN: "Working from home")
- Bei halben Tagen: "(Halber Tag)" bzw. "(Half day)" im SUMMARY
- Da die Entity kein `modifiedAt` Feld hat, wird bei jedem Export der aktuelle Timestamp verwendet

### CalDAV-Synchronisation (optional)
- Automatische Synchronisation mit Benutzer-Kalendern (z.B. Kopano, Nextcloud)
- Konfigurierbar über Systemeinstellungen
- Verwendet einen Service-Account für den Zugriff auf alle Benutzer-Kalender
- Einträge werden in den persönlichen Kalender des jeweiligen Benutzers geschrieben

**Konfiguration:**
| Einstellung | Beschreibung |
|-------------|--------------|
| `caldav_enabled` | CalDAV-Sync aktivieren/deaktivieren |
| `caldav_url` | CalDAV-URL mit `{username}` Platzhalter |
| `caldav_username` | Service-Account Benutzername |
| `caldav_password` | Service-Account Passwort |

**Beispiel-URL:** `https://kopano.example.com:8443/caldav/{username}/Calendar/`

**Sync-Verhalten:**
- **Mit Genehmigungsworkflow:** Einträge werden erst nach Genehmigung synchronisiert
- **Ohne Genehmigungsworkflow:** Einträge werden sofort synchronisiert (auto-approved)
- **Bei Ablehnung:** Bereits synchronisierte Einträge werden aus dem Kalender entfernt
- **Bei Löschung:** Einträge werden aus dem Kalender entfernt

**Manueller Resync:**
- Sync-Button (Icon) bei jedem Monat in der Übersicht
- Synchronisiert alle genehmigten Einträge des Monats
- Nützlich wenn CalDAV nachträglich aktiviert wurde
- Route: `remote_work_sync`

**Technische Details:**
- Verwendet Symfony HttpClient für HTTP-Requests
- PUT für Erstellen/Aktualisieren, DELETE für Löschen
- iCal-Generierung über `IcalHelper` Service (gemeinsam mit iCal-Export)
- Alle Texte (Homeoffice, Dienstreise, etc.) werden übersetzt
- Kommentar wird in SUMMARY und DESCRIPTION aufgenommen
- Fehler werden geloggt, blockieren aber nicht die Speicherung

## Datenbankstruktur

Tabelle: `kimai2_remote_work`
- `id` - Primary Key
- `user_id` - Benutzer (Foreign Key)
- `type` - 'homeoffice' oder 'business_trip'
- `date` - Datum (datetime, Zeit ist immer 00:00:00)
- `half_day` - Halber Tag (boolean, default false)
- `comment` - Kommentar (max 250 Zeichen)
- `status` - 'new', 'approved', 'rejected'
- `created_by` - Ersteller
- `created_date` - Erstellungsdatum
- `approved_by` - Genehmiger
- `approved_date` - Genehmigungsdatum

## Dateistruktur

```
RemoteWorkBundle/
├── CLAUDE.md                          # Diese Datei
├── RemoteWorkBundle.php               # Bundle-Klasse
├── Constants.php                      # Konstanten (Typen, Status, Farben)
├── RemoteWorkConfiguration.php        # Konfiguration (approval_required)
├── RemoteWorkService.php              # Business-Logik
├── RemoteWorkTypeFactory.php          # Factory für Typen
├── composer.json                      # Composer-Konfiguration
├── CalDav/
│   ├── CalDavConfiguration.php        # CalDAV-Konfiguration
│   ├── CalDavService.php              # CalDAV-Operationen (PUT/DELETE)
│   └── IcalHelper.php                 # iCal-Generierung (gemeinsam genutzt)
├── Controller/
│   └── RemoteWorkController.php       # CRUD-Controller
├── DependencyInjection/
│   ├── Configuration.php
│   └── RemoteWorkExtension.php
├── Entity/
│   └── RemoteWork.php                 # Doctrine Entity
├── EventSubscriber/
│   ├── MenuSubscriber.php             # Menü-Integration
│   ├── RemoteWorkActionSubscriber.php # Tabellen-Aktionen (Zeilen)
│   ├── RemoteWorkPageActionSubscriber.php # Seiten-Aktionen (Export)
│   ├── SystemConfigurationSubscriber.php # Einstellungen
│   ├── UserSubscriber.php             # Benutzer-Events
│   └── WorkingTimeYearSubscriber.php  # Arbeitszeitübersicht
├── Form/
│   └── RemoteWorkForm.php             # Formular
├── Migrations/
│   └── Version20260128000000.php      # DB-Migration
├── Model/
│   ├── OverlapWarning.php             # Überlappungswarnung
│   ├── RemoteWorkStatistic.php        # Statistik-DTO
│   ├── RemoteWorkType.php             # Basis-Typ
│   └── Type/
│       ├── BusinessTripType.php       # Dienstreise-Typ
│       └── HomeofficeType.php         # Homeoffice-Typ
├── Repository/
│   └── RemoteWorkRepository.php       # Doctrine Repository
├── Voter/
│   └── RemoteWorkVoter.php            # Berechtigungs-Voter
├── Resources/
│   ├── config/
│   │   ├── routes.yaml                # Routen
│   │   └── services.yaml              # Service-Konfiguration
│   ├── translations/
│   │   ├── messages.de.xlf            # Deutsche Übersetzungen
│   │   └── messages.en.xlf            # Englische Übersetzungen
│   └── views/
│       ├── remote-work.html.twig      # Hauptseite
│       └── remote-work-edit.html.twig # Formular-Modal
└── Validator/
    ├── OverlapValidator.php           # Überlappungsprüfung
    └── Constraints/
        ├── RemoteWork.php             # Constraint-Definition
        └── RemoteWorkValidator.php    # Constraint-Validator
```

## Bekannte Besonderheiten

### Template/CSS
- Kimai's Alert-Box verwendet Flexbox - für Block-Elemente müssen inline-styles verwendet werden
- Kimai's JS erkennt nur `alert-danger` für Fehler - Warnungen müssen auch `alert-danger` verwenden damit der Modal offen bleibt

### Sortierung
- Einträge werden absteigend nach Datum sortiert (neueste zuerst)

### Validierung
- Arbeitstag-Prüfung basiert auf WorkingTimeService
- Benutzer ohne Arbeitsvertrag: alle Tage gelten als Arbeitstage
- Benutzer mit Arbeitsvertrag: nur konfigurierte Arbeitstage

## Berechtigungen

| Permission | USER | TEAMLEAD | ADMIN |
|------------|------|----------|-------|
| view_own_remote_work | ✓ | ✓ | ✓ |
| create_own_remote_work | ✓ | ✓ | ✓ |
| edit_own_remote_work | ✓ | ✓ | ✓ |
| delete_own_remote_work | ✓ | ✓ | ✓ |
| view_other_remote_work | | ✓ | ✓ |
| edit_other_remote_work | | | ✓ |
| delete_other_remote_work | | | ✓ |
| approve_remote_work | | ✓ | ✓ |
| remote_work_settings | | | ✓ |

## Abhängigkeiten

### Erforderlich
- Kimai >= 2.32.00 (VERSION_ID: 23200)

### Optional
- WorkContractBundle - für Überlappungsprüfung mit Abwesenheiten/Feiertagen

## Offene Punkte / TODOs

- [x] Excel-Export (via RemoteWorkPageActionSubscriber und AnnotatedObjectExporter)
- [ ] Anzeige in Arbeitszeitübersicht (WorkingTimeYearSubscriber)
- [ ] PDF-Report Integration
- [ ] Tests schreiben

## Technische Details

### Page Actions vs Form Addon

Kimai hat zwei verschiedene Action-Bereiche:

1. **Page Actions** (`block page_actions` in base.html.twig)
   - Oben rechts auf der Seite
   - Konfiguriert via `PageSetup::setActionName()` und `setActionPayload()`
   - Event wird automatisch dispatcht wenn `actionName` gesetzt ist
   - Verwendet `widgets.page_actions()` Macro
   - Beispiel: Export-Button

2. **Form Addon** (`block form_addon` in page_setup.html.twig)
   - Im Formular-Bereich (neben Datums-/User-Auswahl)
   - Manuell im Template via `actions()` Twig-Funktion
   - Verwendet `widgets.actions()` Macro
   - Beispiel: Navigations-Links (Arbeitszeiten, Arbeitsvertrag)
