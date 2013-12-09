<h3>De Goede Woning - Laden koopovereenkomsten uit First Noa</h3>
<br />
<p>Dagelijks moeten er koopovereenkomsten vanuit First geladen worden in CiviCRM. Dat gebeurt door een CSV-bestand via Business Objects uit First Noa te halen, en deze te laden in CiviCRM.</p>
<p>Het laadscript controleert of het bestand bestaat, en verwerkt het in CiviCRM. Dat kan wel <strong>enige tijd</strong> duren, gemiddeld vermoedelijk tussen de 10 en 20 minuten.</p>
<br />
<form method="post" action={$processUrl}>
    <input name="submit_Kov" value="Laden koopovereenkomsten starten" class="form-submit" type="submit">
</form>




