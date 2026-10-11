# Colesteroids OG+

![Colesteroids OG+](web/og-image.png)

**Syö hyvät, väistä kolesteroli. Kuinka monta kenttää selviät?**

Colesteroids on selaimessa pelattava fysiikkapeli. Ohjaat vaaleanpunaista sydäntä nuolinäppäimillä kentässä, jossa leijuu vihreitä kolmioita eli hyviä lipoproteiineja ja punaisia kolesteroliökköjä.

▶ **Pelaa:** https://hessujarvinen.com/softa/colesteroids

## Pelin säännöt

| | |
|---|---|
| 💗 Sydän | Sinä. Nuolinäppäin lyö sydäntä haluamaasi suuntaan, painovoima vetää alas. |
| 🟩 Vihreä kolmio | Hyvä lipoproteiini. Syö: pisteitä ja +10 % energiaa. |
| 🔴 Punainen pallo | Kolesteroliökkö. Törmäys räjäyttää sen ja vie energiaa. |

- Jokainen kenttä kestää 60 sekuntia. Jos energiaa on jäljellä, pääset seuraavaan kenttään ja saat +20 % energiaa.
- Peli päättyy, kun sydämen energia loppuu. Tulos on kaikkien kenttien pisteet yhteensä.
- Jokainen selvitty sekunti antaa 10 pistettä. Uusi kolesteroliökkö ilmestyy 15 sekunnin välein.
- Seiniin aukeaa välillä aukko samalle korkeudelle molemmille puolille. Aukosta lentänyt tulee sisään toiselta puolelta – myös ökköt.
- Tuloksen voi tallentaa nimimerkillä tulostauluun ja jakaa Facebookissa.

| Kenttä | 1 | 2 | 3 | 4 | 5+ |
|---|---|---|---|---|---|
| Layout | Alkuperäinen (2019) | Portaat | Käytävät | Kammiot | Sokkelo, sitten alusta |
| Punaisia alussa | 20 | 24 | 28 | 32 | +4 / kenttä |
| Punaisten nopeus | 70 | 85 | 100 | 115 | +15 / kenttä |
| Osuman energiahävikki | 25 % | 25 % | 30 % | 30 % | 35 % |
| Vihreän arvo | 100 | 150 | 200 | 250 | +50 / kenttä |
| Vihreitä | 20 | 18 | 16 | 14 | –2 / kenttä, väh. 10 |

Näppäimet: nuolet liikuttavat, **F1** näyttää ohjeet ja **Esc** lopettaa pelin.

## Tausta

Alkuperäinen Colesteroids syntyi vuonna 2019 Jyväskylän yliopiston Ohjelmointi 1 -kurssilla. Se kirjoitettiin C#-kielellä yliopiston [Jypeli](https://github.com/Jypeli-JYU/Jypeli)-pelikirjastolla, ja siitä puuttuivat vielä alkunäyttö, tulostaulu ja kunnollinen pistelasku.

Colesteroids OG+ (2026) on sen paranneltu selainversio, joka on vibekoodattu Clauden kanssa. Kenttä, hahmot ja fysiikan oletusarvot on siirretty alkuperäisestä koodista mahdollisimman uskollisesti, ja alkuperäisen koodin TODO-kommenteista on tehty varsinainen pelimekaniikka.

## Hakemistot

```
web/             Selainversio
  index.html     Peli (yksi HTML-tiedosto, ei riippuvuuksia)
  scores.php     Yhteinen tulostaulu (PHP, tallentaa tiedostoon data/scores.json)
  og-image.png   Jakokuva (Facebook / Open Graph)
original-2019/   Alkuperäinen C#/Jypeli-projekti (Visual Studio for Mac)
```

### Selainversion ajaminen

Vie `web`-kansion sisältö palvelimelle, jossa on PHP 7.4 tai uudempi. Peli käyttää silloin yhteistä tulostaulua `scores.php`:n kautta. Palvelin laskee pisteet kentittäin samalla kaavalla kuin peli. Tulostaulussa on yksi rivi per nimimerkki, ja se päivittyy vain paremmalla tuloksella. Nimimerkki varataan selaimelle, joka tallentaa sillä ensimmäisen kerran: selain lähettää satunnaisen tunnisteen, josta palvelin säilyttää vain tiivisteen. `scores.php` luo ensimmäisellä tallennuksella `data`-kansion ja suojaa sen `.htaccess`-tiedostolla. Palvelin tarkistaa, että pisteet vastaavat pelin sääntöjä, ja rajoittaa tallennustahtia.

Jos PHP:tä ei ole tai avaat `index.html`:n suoraan tiedostona, tulostaulu tallentuu pelaajan omaan selaimeen (localStorage).

Julkaisu sivustolle tapahtuu GitHub Actionsilla (`.github/workflows/deploy.yml`) aina, kun `web`-kansio muuttuu `main`-haarassa.

### Alkuperäinen versio

`original-2019` sisältää vuoden 2019 lähdekoodin sellaisenaan. Jypeli-kirjastot palautetaan NuGetillä (`packages.config`). Projektiin kuuluneet macOS-natiivikirjastot (`libSDL2-2.0.0.dylib`, `libopenal.1.dylib`) eivät ole repossa, koska ne ovat kolmannen osapuolen binäärejä.

## Lisenssi

[MIT](LICENSE) © 2026 Hessu Järvinen. Colesteroids on Hessu Järvisen alkuperäinen idea. Saat parannella koodia vapaasti, kunhan tekijänoikeusilmoitus säilyy mukana.
