# occompletionprogress

**occompletionprogress** ist ein Block-Plugin für Moodle, welches Lernenden eine Übersicht
über ihren Kursfortschritt gibt und Lehrenden eine Übersicht über den Fortschritt aller Lernender.

## Features

- Fasst den Kursfortschritt pro Sektion in einem Fortschrittsabschnitt zusammen
- Farben der einzelnen Zustände (abgeschlossen, unvollendet, kein) konfigurierbar
- Kursfortschritt der Kursteilnehmenden zusammengefasst auf einer separaten Übersichtseite  


## Installation

1. Clone das Repository in das `/block/occompletionprogress`-Verzeichnis der Moodle-Installation.
2. Ruf' **Website-Administration → Systemnachrichten** auf, um die Installation anzustoßen oder führ' `admin/cli/upgrade.php` aus.

## Voraussetzungen

- Keine, nicht abhängig von anderen Plugins

## Konfiguration

Nach Installation kann das Plugin auf folgenden Weg konfiguriert werden:  
**Website-Administration → Plugins → Blöcke → Kurs-Fortschrittsbalken**

Einstellungen:

- `completedcolor`: Farbe für abgeschlossene Abschnitte 
- `uncompletedcolor`: Farbe für unvollendete Abschnitte 
- `notrackingcolor`: Farbe für Abschnitte ohne Aktivitäten mit Abschlussverfolgung 
- `showinactive`: Inaktive Lernende in der Übersicht anzeigen
- `showlastincourse`: Letzter Kurszugriff der Lernenden in der Übersicht anzeigen

## Nutzung

1. Navigiere in einen Moodlekurs.
2. Füge den Block "Kurs-Fortschrittsbalken" hinzu

## Rechte

Dieses Plugin definiert folgende Rechte:

| Name des Rechts                         | Beschreibung                                         | Standardrolle                    |
|-----------------------------------------|------------------------------------------------------|----------------------------------|
| `block/occompletionprogress:addinstance`| Erlaubt es dem Nutzer, die Pluginseite aufzurufen    | editingteacher, teacher, manager |
| `block/occompletionprogress:overview`   | Erlaubt es dem Nutzer, die Übersichtseite anzuzeigen | editingteacher, teacher, manager                 |

## Cronjobs

Dieses Plugin definiert keine Cronjobs.

## Web Services

Dieses Plugin stellt keine Webservice-Funktionen zur Verfügung.

## Lizenz

Dieses Plugin ist lizensiert unter [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Credits

Autor: Markus Strehling ([markus.strehling@oncampus.de](mailto:markus.strehling@oncampus.de))
Inspired by / thanks to: ([Completion Progress](https://moodle.org/plugins/block_completion_progress)), Jonathon Fowler
