<?php

$daten = request()->input('daten');
$haus_raus = request()->input('haus_raus');
if (request()->has('objekt_id')) {
    $objekt_id = request()->input('objekt_id');
} else {
    $objekt_id = '';
}

switch ($haus_raus) {

    /* Liste der Häuser anzeigen */
    case "haus_kurz" :
        $form = new mietkonto ();
        $form->erstelle_formular("Häuserliste", NULL);
        haus_kurz($objekt_id);
        $form->ende_formular();
        break;

    /* Formular zum Ändern des Hauses aufrufen */
    case "haus_aendern" :
        $f = new formular ();

        $bk = new berlussimo_global ();
        $bk->objekt_auswahl_liste();
        if (!request()->has('haus_id')) {
            if (session()->has('objekt_id')) {
                $f->fieldset('Häuser zum Ändern wählen', 'hww');
                haus_kurz(session()->get('objekt_id'));
                $f->fieldset_ende();
            }
        } else {
            $h = new haus ();
            $haus_id = request()->input('haus_id');
            $h->form_haus_aendern($haus_id);
        }

        break;

    /* Änderungen des Hauses speichern */
    case "haus_aend_speichern" :
        if (request()->has('haus_id') && !empty(request()->input('haus_id')) && request()->has('strasse') && !empty (request()->input('strasse')) && request()->has('haus_nr') && !empty(request()->input('haus_nr')) && request()->has('ort') && !empty(request()->input('ort')) && request()->has('plz') && !empty (request()->input('plz')) && request()->has('qm') && !empty (request()->input('qm')) && request()->input('Objekt') && !empty (request()->input('Objekt'))) {
            $haus_id = request()->input('haus_id');
            $strasse = request()->input('strasse');
            $haus_nr = request()->input('haus_nr');
            $ort = request()->input('ort');
            $plz = request()->input('plz');
            $qm = nummer_komma2punkt(request()->input('qm'));
            $objekt_id = request()->input('Objekt');

            $h = new haus ();
            $h->haus_aenderung_in_db($strasse, $haus_nr, $ort, $plz, $qm, $objekt_id, $haus_id);
            fehlermeldung_ausgeben("Haus geändert!");
            weiterleiten_in_sec(route('legacy::haeuser::index', ['haus_raus' => 'haus_kurz', 'objekt_id' => $objekt_id], false), 3);
        } else {
            fehlermeldung_ausgeben("Eingegebene Daten unvollständig, erneut versuchen bitte!");
            $haus_id = request()->input('haus_id');
            weiterleiten_in_sec(route('legacy::haeuser::index', ['haus_raus' => 'haus_aendern', 'haus_id' => $haus_id], false), 3);
        }
        break;
}
function haus_kurz($objekt_id = '')
{
    if (empty ($objekt_id)) {
        $db_abfrage = "SELECT OBJEKT_ID, HAUS_ID, HAUS_STRASSE, HAUS_NUMMER, HAUS_PLZ, HAUS_QM FROM HAUS WHERE HAUS_AKTUELL='1' ORDER BY HAUS_STRASSE,  0+HAUS_NUMMER, OBJEKT_ID ASC";
        $title = "Alle Häuser";
    } else {
        $db_abfrage = "SELECT OBJEKT_ID, HAUS_ID, HAUS_STRASSE, HAUS_NUMMER, HAUS_PLZ, HAUS_QM FROM HAUS where OBJEKT_ID='$objekt_id' && HAUS_AKTUELL='1' ORDER BY HAUS_STRASSE, 0+HAUS_NUMMER, OBJEKT_ID ASC";
        $objekt_kurzname = objekt_namen_by_id($objekt_id);
        $title = "Häuser vom Objekt:  $objekt_kurzname";
    }

    $resultat = mysql_query($db_abfrage) or die (mysql_error());

    $numrows = mysql_numrows($resultat);
    if ($numrows < 1) {
        echo "<h1><b>Keine Häuser vorhanden!!!</b></h1>\n";
    } else {
        // echo "<div id=\"iframe_1\">";
        // echo "<div class=\"abstand_iframe\">";
        // echo "<div class=\"scrollbereich\">";
        iframe_start();
        echo "<table class=\"sortable striped\">\n";
        // echo "<tr class=\"feldernamen\"><td colspan=8>$title</td></tr>\n";
        // echo "<tr class=\"feldernamen\"><td width=155>Straße</td><td width=60>Nr.</td><td width=60>PLZ</td><td width=60>H m²</td><td width=100>E m²</td><td colspan=2>Zusatzinfo</td></tr>\n";
        echo "<tr><th>Strasse</th><th>Nr.</th><th>PLZ</th><th>m²</th><th>Em²</th><th>Einheiten</th><th>INFOS</th><th>OPTION</th></tr>";

        $counter = 0;
        while (list ($OBJEKT_ID, $HAUS_ID, $HAUS_STRASSE, $HAUS_NUMMER, $HAUS_PLZ, $HAUS_QM) = mysql_fetch_row($resultat)) {
            $detail_check = detail_check("HAUS", $HAUS_ID);
            if ($detail_check > 0) {
                $detail_link = "<a class=\"table_links\" href='" . route('legacy::details::index', ['option' => 'details_anzeigen', 'detail_tabelle' => 'HAUS', 'detail_id' => $HAUS_ID]) . "'>Details</a>";
            } else {
                $detail_link = "<a class=\"table_links\" href='" . route('legacy::details::index', ['option' => 'details_hinzu', 'detail_tabelle' => 'HAUS', 'detail_id' => $HAUS_ID]) . "'>Neues Detail</a>";
            }
            $einheiten_im_haus = anzahl_einheiten_im_haus($HAUS_ID);
            $gesammtflaeche_einheiten = einheiten_gesamt_qm($HAUS_ID);
            if (empty ($gesammtflaeche_einheiten)) {
                $gesammtflaeche_einheiten = "0";
            }
            $counter++;

            $link_haus_aendern = "<a href='" . route('legacy::haeuser::index', ['haus_raus' => 'haus_aendern', 'haus_id' => $HAUS_ID]) . "'>Haus ändern</th>";

            if ($counter == 1) {
                echo "<tr class=\"zeile1\"><td width=150>$HAUS_STRASSE</td><td width=60>$HAUS_NUMMER</td><td width=60>$HAUS_PLZ</td><td width=60>$HAUS_QM m²</td><td width=100>$gesammtflaeche_einheiten m²</td><td><a class=\"table_links\" href='" . route('legacy::einheiten::index', ['einheit_raus' => 'einheit_kurz', 'haus_id' => $HAUS_ID]) . "'>Einheiten (<b>$einheiten_im_haus</b>)</a></td><td>$detail_link</td><td>$link_haus_aendern</td></tr>";
            }
            if ($counter == 2) {
                echo "<tr class=\"zeile2\"><td width=150>$HAUS_STRASSE</td><td width=60>$HAUS_NUMMER</td><td width=60>$HAUS_PLZ</td><td width=60>$HAUS_QM m²</td><td width=60>$gesammtflaeche_einheiten m²</td><td><a class=\"table_links\" href='" . route('legacy::einheiten::index', ['einheit_raus' => 'einheit_kurz', 'haus_id' => $HAUS_ID]) . "'>Einheiten (<b>$einheiten_im_haus</b>)</a></td><td>$detail_link</td><td>$link_haus_aendern</td></tr>";
                $counter = 0;
            }
        }
        echo "</table>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
}

function anzahl_einheiten_im_haus($haus_id)
{
    $db_abfrage = "SELECT * FROM EINHEIT WHERE HAUS_ID='$haus_id' && EINHEIT_AKTUELL='1'";
    $resultat = mysql_query($db_abfrage) or die (mysql_error());

    $numrows = mysql_numrows($resultat);
    return $numrows;
}

function einheiten_gesamt_qm($haus_id)
{
    $db_abfrage = "SELECT SUM(EINHEIT_QM) AS SUMME FROM EINHEIT where HAUS_ID='$haus_id' && EINHEIT_AKTUELL='1'";
    $resultat = mysql_query($db_abfrage) or die (mysql_error());
    while (list ($SUMME) = mysql_fetch_row($resultat))
        return $SUMME;
}

function objekt_namen_by_id($objekt_id)
{
    $db_abfrage = "SELECT OBJEKT_KURZNAME FROM OBJEKT where OBJEKT_ID='$objekt_id' && OBJEKT_AKTUELL='1'";
    $resultat = mysql_query($db_abfrage) or die (mysql_error());
    while (list ($OBJEKT_KURZNAME) = mysql_fetch_row($resultat))
        return $OBJEKT_KURZNAME;
}

?>
