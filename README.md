# Contao Status-Updates Bundle

Zeige wichtige Status-Updates wie anstehende Contao Updates im Contao Backend-Dashboard an.

## Funktionen

- **Backend-Modul**: Verwalte Status-Updates über das Menü "Status-Updates" im Backend
- **Datumsbasierte Sichtbarkeit**: Steuere, wann Updates erscheinen (X Tage vor/nach einem Ereignis)
- **Rich-Text-Editor**: TinyMCE-Integration für detaillierte Beschreibungen
- **Dashboard-Widget**: Automatische Anzeige im Backend basierend auf Sichtbarkeitsregeln
- **Mehrsprachig**: Deutsche und englische Übersetzungen enthalten
- **Flexible Anzeigeregeln**: Konfiguriere, wie lange Updates sichtbar bleiben
- **Farbcodierte Anzeige**: Visuelle Indikatoren für bevorstehende, aktuelle und vergangene Ereignisse

## Installation

Installation über Composer:

```bash
composer require erdmannfreunde/contao-status-update-bundle
```

Nach der Installation führe das Contao-Installtool oder den Migrate-Befehl aus:

```bash
vendor/bin/contao-console contao:migrate
```

## Verwendung

### Status-Updates erstellen

1. Navigiere zu **Backend → Inhalte → Status-Updates**
2. Klicke auf "Neu", um ein Status-Update zu erstellen
3. Fülle die Felder aus:

- **Titel**: Kurzer beschreibender Titel
- **Beschreibung**: Detaillierte Informationen (unterstützt HTML über TinyMCE)
- **Ereignisdatum**: Das Datum des Ereignisses
- **Benachrichtigung vor Ereignis anzeigen**: Wie viele Tage vor dem Ereignis das Update angezeigt werden soll
  - 7 Tage vorher (Standard)
  - 10 Tage vorher
  - 30 Tage vorher
  - Nur am Ereignistag
- **Benachrichtigung nach Ereignis anzeigen**: Wie viele Tage nach dem Ereignis das Update weiterhin angezeigt werden soll
  - Nur am Ereignistag (Standard)
  - 1 Tag danach
  - 7 Tage danach
- **Veröffentlicht**: Aktivieren, um im Dashboard sichtbar zu machen

4. Speichere das Status-Update

### Dashboard-Anzeige

Status-Updates erscheinen automatisch im Backend-Dashboard basierend auf den Sichtbarkeitsregeln:

- **Rot/Wichtig**: Ereignisse, die heute stattfinden
- **Gelb/Warnung**: Ereignisse innerhalb der nächsten 3 Tage
- **Blau/Info**: Zukünftige Ereignisse
- **Grün/Erfolg**: Vergangene Ereignisse (innerhalb der "Danach"-Anzeigedauer)

## Sichtbarkeitslogik

Das Bundle berechnet, welche Status-Updates angezeigt werden, basierend auf:

1. **Aktuelles Datum** vs. **Ereignisdatum**
2. **Vorher anzeigen**-Einstellung (wie viele Tage im Voraus)
3. **Danach anzeigen**-Einstellung (wie viele Tage nach dem Ereignis)

Beispiel: Wenn du "7 Tage vorher" und "1 Tag danach" für ein Ereignis am 15. Januar einstellst:

- Das Status-Update wird ab dem 8. Januar angezeigt
- Das Status-Update wird nach dem 16. Januar nicht mehr angezeigt

## Anforderungen

- PHP 8.2 oder höher
- Contao 5.3 oder höher
- Symfony 6.4 oder 7.0

## Konfiguration

Keine zusätzliche Konfiguration erforderlich. Das Bundle funktioniert nach der Installation sofort.

## Lizenz

Dieses Bundle ist unter LGPL-3.0-or-later lizenziert.
