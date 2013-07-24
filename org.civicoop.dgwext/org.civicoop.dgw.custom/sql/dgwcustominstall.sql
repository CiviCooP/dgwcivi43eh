CREATE  TABLE `dgw_config` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `label` VARCHAR(128) NULL ,
  `value` VARCHAR(256) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

INSERT INTO `dgw_config` SET label = 'tabel data first', value = 'Aanvullende persoonsgegevens';
INSERT INTO `dgw_config` SET label = 'persoonsnummer first', value = 'Persoonsnummer First';
INSERT INTO `dgw_config` SET label = 'persoonsnummer first org', value = 'Nr. in First';
INSERT INTO `dgw_config` SET label = 'bsn', value = 'BSN';
INSERT INTO `dgw_config` SET label = 'burgerlijke staat', value = 'Burgerlijke staat';

INSERT INTO `dgw_config` SET label = 'tabel huurovereenkomst huishouden', value = 'Huurovereenkomst (huishouden)';
INSERT INTO `dgw_config` SET label = 'hovnummer huishouden', value = 'HOV nummer First';
INSERT INTO `dgw_config` SET label = 'vgenummer hov huishouden', value = 'VGE nummer First';
INSERT INTO `dgw_config` SET label = 'hov adres huishouden', value = 'VGE adres First';
INSERT INTO `dgw_config` SET label = 'begindatum hov huishouden', value = 'Begindatum HOV';
INSERT INTO `dgw_config` SET label = 'einddatum hov huishouden', value = 'Einddatum HOV';
INSERT INTO `dgw_config` SET label = 'naam hov huishouden', value = 'Correspondentienaam First';

INSERT INTO `dgw_config` SET label = 'tabel koopovereenkomst', value = 'Koopovereenkomst';
INSERT INTO `dgw_config` SET label = 'kovnummer', value = 'KOV nummer First';
INSERT INTO `dgw_config` SET label = 'vgenummer kov', value = 'VGE nummer KOV';
INSERT INTO `dgw_config` SET label = 'kov adres', value = 'VGE adres KOV';
INSERT INTO `dgw_config` SET label = 'datum overdracht', value = 'Datum overdracht';
INSERT INTO `dgw_config` SET label = 'naam kov', value = 'Correspondentienaam KOV';
INSERT INTO `dgw_config` SET label = 'definitief', value = 'Definitief';
INSERT INTO `dgw_config` SET label = 'type kov', value = 'Type KOV';
INSERT INTO `dgw_config` SET label = 'verkoopprijs', value = 'Verkoopprijs';
INSERT INTO `dgw_config` SET label = 'notaris', value = 'Notaris';
INSERT INTO `dgw_config` SET label = 'taxatiewaarde', value = 'Taxatiewaarde';
INSERT INTO `dgw_config` SET label = 'taxateur', value = 'Taxateur';
INSERT INTO `dgw_config` SET label = 'taxatiedatum', value = 'Taxatiedatum';
INSERT INTO `dgw_config` SET label = 'bouwkundige', value= 'Bouwkundige';
INSERT INTO `dgw_config` SET label = 'datum bouwkeuring', value = 'Datum bouwkundige keuring';

INSERT INTO `dgw_config` SET label = 'default burgerlijke staat', value = 'Onbekend';
INSERT INTO `dgw_config` SET label = 'groep sync first', value = 'FirstSync';
INSERT INTO `dgw_config` SET label = 'synchronisatietabel first', value = 'Synchronisatie First Noa';
INSERT INTO `dgw_config` SET label = 'sync first entity veld', value = 'entity';
INSERT INTO `dgw_config` SET label = 'sync first action veld', value = 'action';
INSERT INTO `dgw_config` SET label = 'sync first entity_id veld', value = 'entity_id';
INSERT INTO `dgw_config` SET label = 'sync first key_first veld', value = 'key_first';
INSERT INTO `dgw_config` SET label = 'sync first change_date veld', value = 'change_date';
INSERT INTO `dgw_config` SET label = 'sync first error tabel', value = 'Fouten synchronisatie First';
INSERT INTO `dgw_config` SET label = 'sync first error action veld', value = 'action_err';
INSERT INTO `dgw_config` SET label = 'sync first error entity veld', value = 'Entiteit fout';
INSERT INTO `dgw_config` SET label = 'sync first error entity_id veld', value = 'entity_id_err';
INSERT INTO `dgw_config` SET label = 'sync first error key_first veld', value = 'key_first_err';
INSERT INTO `dgw_config` SET label = 'sync first error datum veld', value = 'datum synchronisatieprobleem';
INSERT INTO `dgw_config` SET label = 'sync first error foutboodschap', value = 'Foutboodschap';
INSERT INTO `dgw_config` SET label = 'relatie hoofdhuurder', value = 'Hoofdhuurder';
INSERT INTO `dgw_config` SET label = 'relatie medehuurder', value = 'Medehuurder';
INSERT INTO `dgw_config` SET label = 'relatie koopovereenkomst', value = 'Koopovereenkomst partner';
INSERT INTO `dgw_config` SET label = 'default location type', value = 'Thuis';
INSERT INTO `dgw_config` SET label = 'helpdesk mail', value = 'helpdesk@degoedewoning.nl';

INSERT INTO `dgw_config` SET label = 'vjt woonkeusnummer', value = 'Inschrijfnummer Woonkeus';
INSERT INTO `dgw_config` SET label = 'vjt woonkeusdatum', value = 'Datum inschrijving woonkeus';
INSERT INTO `dgw_config` SET label = 'vjt situatie', value = 'Huidige woonsituatie';
INSERT INTO `dgw_config` SET label = 'vjt hoofdhuurder', value = 'Hoofdhuurder';
INSERT INTO `dgw_config` SET label = 'vjt andere', value = 'Welke andere corporatie';
INSERT INTO `dgw_config` SET label = 'vjt huishoudgrootte', value = 'Huishoudgrootte';
INSERT INTO `dgw_config` SET label = 'vjt bekend', value = 'Bekend met koopaanbod';
INSERT INTO `dgw_config` SET label = 'vjt particulier', value = 'Particuliere markt';
INSERT INTO `dgw_config` SET label = 'vjt bruto jaarinkomen', value = 'Bruto jaarinkomen';
INSERT INTO `dgw_config` SET label = 'vjt check', value = 'Check';

INSERT INTO `dgw_config` SET label = 'locatie oud', value = 'Oud';
INSERT INTO `dgw_config` SET label = 'locatie toekomst', value = 'Toekomst';
INSERT INTO `dgw_config` SET label = 'kov bestandsnaam', value = 'kov_';
INSERT INTO `dgw_config` SET label = 'kov pad', value = '/home/kov/';
INSERT INTO `dgw_config` SET label = 'kov tabel', value = 'kovimport';
INSERT INTO `dgw_config` SET label = 'kov header', value = 'kovhdr';
INSERT INTO `dgw_config` SET label = 'kov foutgroep', value = 'Koopovereenkomst Huishouden Fout';
INSERT INTO `dgw_config` SET label = 'kov nummer veld', value = 'KOV nummer First';
INSERT INTO `dgw_config` SET label = 'kov vge nummer veld', value = 'VGE nummer KOV';
INSERT INTO `dgw_config` SET label = 'kov vge adres veld', value = 'VGE adres KOV';
INSERT INTO `dgw_config` SET label = 'kov overdracht veld', value = 'Datum overdracht';
INSERT INTO `dgw_config` SET label = 'kov naam veld', value = 'Correspondentienaam KOV';
INSERT INTO `dgw_config` SET label = 'kov definitief veld', value = 'Definitief';
INSERT INTO `dgw_config` SET label = 'kov type veld', value = 'Type KOV';
INSERT INTO `dgw_config` SET label = 'kov prijs veld', value = 'Verkoopprijs';
INSERT INTO `dgw_config` SET label = 'kov notaris veld', value = 'Notaris';
INSERT INTO `dgw_config` SET label = 'kov waarde veld', value = 'Taxatiewaarde';
INSERT INTO `dgw_config` SET label = 'kov taxateur veld', value = 'Taxateur';
INSERT INTO `dgw_config` SET label = 'kov taxatiedatum veld', value = 'Taxatiedatum';
INSERT INTO `dgw_config` SET label = 'kov bouwkundige veld', value = 'Bouwkundige';
INSERT INTO `dgw_config` SET label = 'kov bouwdatum veld', value = 'Datum bouwkundige keuring';

INSERT INTO `dgw_config` SET label = 'groep toewijzen activiteit', value = 'Toewijzen activiteit';

CREATE TABLE `kovhdr` (
  `kov_nr` int(11) NOT NULL DEFAULT '0',
  `vge_nr` int(11) DEFAULT NULL,
  `corr_naam` varchar(128) DEFAULT NULL,
  `ov_datum` varchar(45) DEFAULT NULL,
  `vge_adres` varchar(128) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `prijs` varchar(45) DEFAULT NULL,
  `notaris` varchar(75) DEFAULT NULL,
  `tax_waarde` varchar(45) DEFAULT NULL,
  `taxateur` varchar(45) DEFAULT NULL,
  `tax_datum` varchar(45) DEFAULT NULL,
  `bouwkundige` varchar(45) DEFAULT NULL,
  `bouw_datum` varchar(45) DEFAULT NULL,
  `definitief` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`kov_nr`)
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE TABLE `kovimport` (
  `kov_nr` int(11) DEFAULT NULL,
  `vge_nr` int(11) DEFAULT NULL,
  `pers_nr` int(11) DEFAULT NULL,
  `corr_naam` varchar(128) DEFAULT NULL,
  `ov_datum` varchar(45) DEFAULT NULL,
  `vge_adres` varchar(128) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `spec` varchar(45) DEFAULT NULL,
  `prijs` varchar(45) DEFAULT NULL,
  `notaris` varchar(75) DEFAULT NULL,
  `tax_waarde` varchar(45) DEFAULT NULL,
  `taxateur` varchar(45) DEFAULT NULL,
  `tax_datum` varchar(45) DEFAULT NULL,
  `bouwkundige` varchar(45) DEFAULT NULL,
  `bouw_datum` varchar(45) DEFAULT NULL,
  `definitief` varchar(45) DEFAULT NULL
) ENGINE=MyISAM DEFAULT
CHARSET=utf8
COLLATE=utf8_unicode_ci;




