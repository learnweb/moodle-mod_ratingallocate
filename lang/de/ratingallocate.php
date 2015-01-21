<?php	
// This file is part of Moodle - http://moodle.org/	
//	
// Moodle is free software: you can redistribute it and/or modify	
// it under the terms of the GNU General Public License as published by	
// the Free Software Foundation, either version 3 of the License, or	
// (at your option) any later version.	
//	
// Moodle is distributed in the hope that it will be useful,	
// but WITHOUT ANY WARRANTY; without even the implied warranty of	
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the	
// GNU General Public License for more details.	
//	
// You should have received a copy of the GNU General Public License	
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.	
	
	
/**	
 * English strings for ratingallocate	
 *	
 *	
 * @package mod_ratingallocate	
 * @copyright 2014 M Schulze, 2015 T Reischmann	
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later	
 */	
defined('MOODLE_INTERNAL') || die();	
	
// <editor-fold defaultstate=	
$string['ratingallocate'] = 'Gerechte Zuordnung';
$string['ratingallocatename'] = 'Name des Zuordnungsproblems';
$string['ratingallocatename_help'] = 'Wählen Sie einen aussagekräftigen Namen.';
$string['modulename'] = 'Gerechte Zuordnung';
$string['modulename_help'] = 'Stellt Nutzern Wahlmöglichkeiten zur Verfügung, die diese mittels verschiedener Methoden während eines Wahlzeitraums bewerten. Nach Ablauf des Zeitraums können Sie die Nutzer automatisch fair zuteilen lassen.';
$string['modulenameplural'] = 'Gerechte Zuordnungen';
$string['pluginadministration'] = 'Administration für gerechte Zuordnung';
$string['pluginname'] = 'Gerechte Zuordnung';
$string['ratingallocate:view'] = 'Ratingallocation Instanz anzeigen.';
$string['ratingallocate:give_rating'] = 'Eigene Präferenzen erstellen/bearbeiten';
$string['ratingallocate:start_distribution'] = 'Zuordnung von Nutzern zu Wahlmöglichkeiten starten';
$string['ratingallocate:export_ratings'] = 'Möglichkeit Nutzerpräferenzen zu exportieren.';
// </editor-fold>	
// <editor-fold defaultstate=	
$string['choicestatusheading'] = 'Status';
$string['timeremaining'] = 'Verbleibende Zeit';
$string['publishdate_estimated'] = 'Geschätztes Veröffentlichungsdatum';
$string['rateable_choices'] = 'Zur Verfügung stehende Wahlmöglichkeiten';
$string['rating_is_over'] = 'Die Bewertungsphase ist vorbei';
$string['ratings_saved'] = 'Ihre Präferenzen wurden gespeichert.';
$string['strategyname'] = 'Die Bewertungsstrategy lautet "{$a}"';
$string['too_early_to_rate'] = 'Zu früh für die Bewertung.';
$string['your_allocated_choice'] = 'Ihre Zuordnung';
$string['your_rating'] = 'Ihre Bewertung';
$string['results_not_yet_published'] = 'Die Ergebnisse wurden noch nicht veröffentlicht.';
$string['no_choice_to_rate'] = 'Es gibt keine Wahlmöglichkeit, die bewertet werden könnte.';
// </editor-fold>	
// <editor-fold defaultstate=	
$string['allocation_manual_explain_only_raters'] = 'Ordne den Nutzern eine Wahlmöglichkeit zu. Es werden nur die Nutzer angezeigt, welche mindestens eine Wahlmöglichkeit bewertet haben.';
$string['allocation_manual_explain_all'] = 'Ordne den Nutzern eine Wahlmöglichkeit zu.';
$string['distribution_algorithm'] = 'Verteilungsalgorithmus';
$string['distribution_saved'] = 'Zuordnung gespeichert (in {$a}s).';
$string['distribution_table'] = 'Zuordnungstabelle';
$string['download_problem_mps_format'] = 'Exportiere Gleichungen im mps-Format (txt)';
$string['download_votetest_allocation'] = 'Exportiere Präferenzen und Zuordnungen (csv)';
$string['no_user_to_allocate'] = 'Es gibt keine Benutzer, die Sie zuordnen könnten.';
$string['ratings_table'] = 'Bewertungstabelle';
$string['start_distribution'] = 'Verteilung starten';
$string['start_distribution_explanation'] = 'Ein Algorithmus wird eine möglichst faire Zuordnung vornehmen.';
$string['too_early_to_distribute'] = 'Zuordnung noch nicht möglich.';
$string['unassigned_users'] = 'Nicht zugeordnete Nutzer';
$string['invalid_publishdate'] = 'Veröffentlichungsdatum ungültig. Dieses muss nach der Auswahlperiode liegen.';
$string['rated'] = 'bewertet mit {$a}';
$string['no_rating_given'] = 'k. A.';
$string['export_options'] = 'Exportiere Optionen';
$string['manual_allocation_saved'] = 'Ihre manuelle Zuordnung wurde gespeichert.';
$string['publish_allocation'] = 'Zuordnung veröffentlichen';
$string['distribution_published'] = 'Die Zuordnung wurde veröffentlicht.';
$string['create_moodle_groups'] = 'Erstelle Moodle Gruppen aus den Zuordnungen';
$string['moodlegroups_created'] = 'Die entsprechenden Moodle Gruppen wurden erstellt.';
	
$string['manual_allocation_filter_only_raters'] = 'Zeige nur Nutzer mit Präferenzen an';
$string['manual_allocation_filter_all'] = 'Zeige alle Nutzer';
	
$string['rating_raw'] = '{$a}';
// </editor-fold>	
// <editor-fold defaultstate=	
$string['choice_active'] = 'Wahlmöglichkeit aktiv';
$string['choice_active_help'] = 'Nutzer können nur aktive Wahlmöglichkeiten präferieren. Die anderen sind versteckt.';
$string['choice_explanation'] = 'Zusätzliche Beschreibung';
$string['choice_maxsize'] = 'Maximalzahl an zugeordneten Nutzern';
$string['choice_title'] = 'Titel';
$string['choice_title_help'] = 'Titel der Wahlmöglichkeit. *Achtung* Wahlmöglichkeiten werden aufsteigend nach Titel sortiert.';
$string['edit_choice'] = 'Wahlmöglichkeit "{$a}" bearbeiten';
$string['rating_endtime'] = 'Bewertung endet am';
$string['rating_begintime'] = 'Bewertung startet am';
$string['manual_allocation'] = 'Manuelle Zuordnung';
$string['manual_allocation_form'] = 'Formular für die manuelle Zuordnung';
$string['newchoice'] = 'Neue Wahlmöglichkeit hinzufügen';
$string['newchoicetitle'] = 'Neue Wahlmöglichkeit {$a}';
$string['deletechoice'] = 'Lösche Wahlmöglichkeit';
$string['publishdate'] = 'Veröffentlichungsdatum';
$string['select_strategy'] = 'Bewertungsstrategy';
$string['select_strategy_help'] = 'Nach welcher Strategie sollen die Nutzer bewerten?';
$string['show_table'] = 'Bewertungstabelle anzeigen';
$string['strategy_not_specified'] = 'Sie müssen eine Strategie auswählen!';
$string['strategyoptions_for_strategy'] = 'Optionen der Strategie "{$a}"';
$string['err_required'] = 'In dieses Feld muss ein Wert eingetragen werden.';
$string['err_minimum'] = 'Der minimale Wert für dieses Feld ist {$a}.';
$string['err_maximum'] = 'Der maximale Wert für dieses Feld ist {$a}.';
// </editor-fold>	
	
	
/* Specific to Strategy01, YesNo */	
$string['strategy_yesno_name'] = 'Ja-Nein';
$string['strategy_yesno_setting_crossout'] = 'Maximum an Wahlmöglichkeiten, die Nutzer ablehnen können';
$string['strategy_yesno_max_no'] = 'Sie können höchstens {$a} Wahlmöglichkeit(en) ablehnen';
$string['strategy_yesno_rating_crossout'] = 'Nein';
$string['strategy_yesno_rating_choose'] = 'Ja';
$string['strategy_yesno_maximum_crossout'] = 'Sie können höchstens {$a} Wahlmöglichkeit(en) ablehnen';
	
/* Specific to Strategy02, YesMayBeNo */	
$string['strategy_yesmaybeno_name'] = 'Ja-Vielleicht-Nein';
$string['strategy_yesmaybeno_max_no'] = 'Sie können höchstens {$a} Wahlmöglichkeit(en) ablehnen';
$string['strategy_yesmaybeno_rating_no'] = 'Nein';
$string['strategy_yesmaybeno_rating_yes'] = 'Ja';
$string['strategy_yesmaybeno_rating_maybe'] = 'Vielleicht';
$string['strategy_yesmaybeno_setting_maxno'] = 'Maximum an Wahlmöglichkeiten, die Nutzer ablehnen können';
$string['strategy_yesmaybeno_max_count_no'] = 'Sie können höchstens {$a} Wahlmöglichkeit(en) ablehnen.';
	
// Specific to Strategy03, Lickert	
$string['strategy_lickert_name'] = 'Lickert-Skala';
$string['strategy_lickert_setting_maxno'] = 'Maximum an Wahlmöglichkeiten, denen Nutzer "0" zuordnen können';
$string['strategy_lickert_setting_maxlickert'] = 'Höchste Zahl auf der Lickert-Skala (3, 5 oder 7 sind gebräuchlich)';
$string['strategy_lickert_max_no'] = 'Sie können höchstens {$a} Wahlmöglichkeit(en) mit 0/Ablehnung bewerten.';
$string['strategy_lickert_rating_biggestwish'] = 'Starke Präferenz';
$string['strategy_lickert_rating_exclude'] = 'Ablehnung';
	
// Specific to Strategy04, Points	
$string['strategy_points_name'] = 'Punkte verteilen';
$string['strategy_points_explain_distribute_points'] = 'Vergeben Sie zu jeder Wahlmöglichkeit Punkte. Sie müssen insgesamt {$a} Punkte verteilen. Verteilen Sie hohe Punkte auf stark präferierte Wahlmöglichkeiten.';
$string['strategy_points_explain_max_zero'] = 'Sie können höchstens {$a} Wahlmöglichkeiten 0 Punkte geben.';
$string['strategy_points_incorrect_totalpoints'] = 'Die Summe Ihrer Punkte muss {$a} ergeben';
$string['strategy_points_setting_maxzero'] = 'Maximalzahl an Wahlmöglichkeiten, die die Nutzer mit 0 Punkten bewerten können.';
$string['strategy_points_setting_totalpoints'] = 'Gesamtzahl der Punkte, die Nutzer verteilen können.';
$string['strategy_points_max_count_zero'] = 'Sie können höchstens {$a} Wahlmöglichkeiten mit 0 bewerten.';
	
// Specific to Strategy05, Order	
$string['strategy_order_name'] = 'Reihenfolge';
$string['strategy_order_no_choice'] = '{$a}. Wahl';
$string['strategy_order_use_only_once'] = 'Sie können keine Wahlmöglichkeit doppelt zuordnen';
$string['strategy_order_explain_choices'] = 'Wählen Sie zu jeder Stufe eine Wahlmöglichkeit. 1. Wahl ist Ihre höchste Präferenz';
$string['strategy_order_setting_countoptions'] = 'Wie viele Wahlmöglichkeiten soll der Nutzer angeben (kleiner als Gesamtzahl an Wahlmöglichkeiten!)';
	
	
// Specific to Strategy06, tickyes	
$string['strategy_tickyes_name'] = 'Check-Ja';
$string['strategy_tickyes_accept'] = 'Akzeptieren';
$string['strategy_tickyes_not_accept'] = '-';
$string['strategy_tickyes_setting_mintickyes'] = 'Minimum zu akzeptierende Wahlmöglichkeiten';
$string['strategy_tickyes_error_mintickyes'] = 'Sie müssen mindestens {$a} Wahlmöglichkeit(en) akzeptieren';
$string['strategy_tickyes_explain_mintickyes'] = 'Sie müssen mindestens {$a} Wahlmöglichkeit(en) anhaken';
	
// As message provider, for the notification after allocation	
$string['messageprovider:notifyalloc'] = 'Benachrichtugung für die Option Zuweisung';
$string['allocation_notification_message_subject'] = 'Nachricht über die fertige Zuordnung von {$a}';
$string['allocation_notification_message'] = 'Dir wurde in "{$a->ratingallocate}" die Wahlmöglichkeit "{$a->choice}" zugeordnet.';
	
// Logging	
$string['log_rating_saved'] = 'Nutzerpräferenzen gespeichert';
$string['log_rating_saved_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat seine Präferenzen für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' gespeichert.';
	
$string['log_rating_viewed'] = 'Nutzerpräferenzen angezeigt';
$string['log_rating_viewed_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat seine Präferenzen für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' angezeigt.';
	
$string['log_allocation_published'] = 'Zuordnung veröffentlicht';
$string['log_allocation_published_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat die Verteilung für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' veröffentlicht.';
	
$string['log_distribution_triggered'] = 'Verteilung gestartet';
$string['log_distribution_triggered_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat die Verteilung für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' gestartet. Der Algorithmus hat {$a->time_needed}Sekunden benötigt.';
	
$string['log_manual_allocation_saved'] = 'Manuelle Zuordnung gespeichert';
$string['log_manual_allocation_saved_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat eine manuelle Zuordnung für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' gespeichert.';
	
$string['log_ratingallocate_viewed'] = 'Ratingallocate angezeigt';
$string['log_ratingallocate_viewed_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat das Ratingallocate mit der Id \'{$a->ratingallocateid}\' angezeigt.';
	
$string['log_allocation_table_viewed'] = 'Zuordnungstabelle angezeigt';
$string['log_allocation_table_viewed_description'] = 'Der Nutzer mit der Id \'{$a->userid}\' hat die Zuordnungstabelle für das Ratingallocate mit der Id \'{$a->ratingallocateid}\' angezeigt.';
	
