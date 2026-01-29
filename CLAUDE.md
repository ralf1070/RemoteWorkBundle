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
├── Controller/
│   └── RemoteWorkController.php       # CRUD-Controller
├── DependencyInjection/
│   ├── Configuration.php
│   └── RemoteWorkExtension.php
├── Entity/
│   └── RemoteWork.php                 # Doctrine Entity
├── EventSubscriber/
│   ├── MenuSubscriber.php             # Menü-Integration
│   ├── RemoteWorkActionSubscriber.php # Tabellen-Aktionen
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

- [ ] Excel-Export testen
- [ ] Anzeige in Arbeitszeitübersicht (WorkingTimeYearSubscriber)
- [ ] PDF-Report Integration
- [ ] Tests schreiben
