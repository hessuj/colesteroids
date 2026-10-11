# Colesteroids OG+

![Colesteroids OG+](web/og-image.png)

**Syö hyvät, väistä kolesteroli. Kuinka monta kenttää selviät?**

Colesteroids on selaimessa pelattava fysiikkapeli. Ohjaat vaaleanpunaista sydäntä nuolinäppäimillä kentässä, jossa leijuu vihreitä kolmioita eli hyviä lipoproteiineja ja punaisia kolesteroliökköjä.

▶ **Pelaa:** https://hessujarvinen.com/softa/colesteroids

> 🇬🇧 **In English:** Colesteroids OG+ is a browser physics game: steer a pink heart through 50 levels of the circulatory system, eat the good lipoproteins, dodge cholesterol blobs and grab Rosuvastatin pills for a safety shield. The game is available in Finnish and English (FI / EN in the menu, or [?lang=en](https://hessujarvinen.com/softa/colesteroids/?lang=en)).

## Pelin säännöt

| | |
|---|---|
| 💗 Sydän | Sinä. Nuolinäppäin lyö sydäntä haluamaasi suuntaan, painovoima vetää alas. |
| 🟩 Vihreä kolmio | Hyvä lipoproteiini. Syö: pisteitä ja +10 % energiaa. |
| 🔴 Punainen pallo | Kolesteroliökkö. Törmäys räjäyttää sen ja vie energiaa. |
| 💊 Rosuvastatiini | Pilleri, joka ilmestyy kenttään silloin tällöin ja katoaa 9 sekunnin jälkeen. Nappaa se, niin saat 10 sekunniksi turvakaaren: kolesteroliökköihin voi törmätä ilman energian menetystä. Kaari vilkkuu ja tikittää viimeiset 3 sekuntia. |

- Jokainen kenttä kestää 60 sekuntia. Jos energiaa on jäljellä, pääset seuraavaan kenttään ja saat +20 % energiaa.
- Peli päättyy, kun sydämen energia loppuu. Tulos on kaikkien kenttien pisteet yhteensä.
- Jokainen selvitty sekunti antaa 10 pistettä. Uusi kolesteroliökkö ilmestyy 15 sekunnin välein.
- Seiniin aukeaa välillä aukko samalle korkeudelle molemmille puolille. Aukosta lentänyt tulee sisään toiselta puolelta – myös ökköt.
- Tuloksen voi tallentaa nimimerkillä tulostauluun ja jakaa Facebookissa. Jaossa näkyvät nimimerkki, pisteet, kenttä ja leikkimielinen "diagnoosi" tuloksen mukaan.

| Kenttä | 1 | 2 | 3 | 4 | 5 | 6–19 | 20+ |
|---|---|---|---|---|---|---|---|
| Punaisia alussa | 20 | 24 | 28 | 32 | 36 | +2 / kenttä | enintään 70 |
| Punaisten nopeus | 70 | 85 | 100 | 115 | 130 | +5 / kenttä | enintään 250 |
| Osuman energiahävikki | 25 % | 25 % | 30 % | 30 % | 35 % | 35 % | 40 % |
| Vihreän arvo | 100 | 150 | 200 | 250 | 300 | +50 / kenttä | +50 / kenttä |
| Vihreitä | 20 | 18 | 16 | 14 | 12 | 10 | 10 |

### Kentät

Pelissä on 50 kenttää. Kenttä 1 on alkuperäinen vuoden 2019 kenttä, kentät 2–5 tehtiin käsin ja kentät 6–50 generoitiin ohjelmallisesti. Jokainen generoitu kenttä on tarkistettu: aloituspaikka on vapaa, seinien vierustat ovat auki aukkoja varten ja kaikki alueet ovat saavutettavissa. Kentän 50 jälkeen kentät kiertävät alusta.

<details>
<summary>Kaikki 50 kenttää</summary>

1. Alkuperäinen sydänalue (2019)
2. Aortan portaat
3. Laskimoiden käytävät
4. Sydämen kammiot
5. Hiussuonisokkelo
6. Keuhkovaltimon kiito
7. Hemoglobiinihissi
8. Punasolujen ruuhkatunti
9. Läppävikalabyrintti
10. Kapillaarikarnevaali
11. Imusuonten ihmemaa
12. Maksan suodatinmaasto
13. Munuaiskerästen kiemurat
14. Ohutsuolen mutkat
15. Pernan pallomeri
16. Kaulavaltimon kaarre
17. Sepelvaltimoiden solmu
18. Verihiutaleiden hyppyrit
19. Plasmaputki
20. Valkosolujen vartiovuoro
21. Palleaplaneetta
22. Aivokuoren poimut
23. Selkäytimen pikakaista
24. Hermoratojen hyppely
25. Synapsisilta
26. Sydänpussin pomppulinna
27. Eteisvärinäkaaos
28. Kammiovärinän vuoristorata
29. Diastolen lepotauko
30. Systolen syöksy
31. Verenpainepiikki
32. Kolesterolikaupunki
33. Plakkiputken ahdinko
34. Rasvahappojen rodeo
35. Insuliinipuisto
36. Glukoosigalleria
37. Hapenottoareena
38. Hiilidioksidihuuhtelu
39. Keuhkorakkuloiden arvoitus
40. Henkitorven tuulitunneli
41. Luuytimen lastentarha
42. Fibriiniverkko
43. Hyytymiskaskadi
44. Vasemman kammion valtatie
45. Oikean eteisen odotushuone
46. Ohitusleikkauksen kiertotie
47. Pallolaajennuksen paikka
48. Stenttien sokkelo
49. Sykevälivaihtelu
50. Ison verenkierron finaali

</details>

### Kielet

Peli on suomeksi ja englanniksi. Kieli valitaan valikon FI / EN -napeista, ja peli muistaa valinnan. Ensimmäisellä kerralla kieli päätellään selaimen kielestä (suomi → suomi, muut → englanti), ja osoitteella `?lang=en` peli avautuu suoraan englanniksi.

Kaikki tekstit ovat `index.html`:n `I18N`-sanastossa avaimen takana, ja peli hakee ne funktiolla `t(avain)`. HTML-elementit merkitään `data-i18n`-attribuuteilla, joten kielen vaihto päivittää koko näkymän. Uuden kielen saa lisäämällä sanastoon uuden lohkon; puuttuvat tekstit näytetään suomeksi. Facebook-jakokortit tehdään sillä kielellä, jolla tulos pelattiin.

### Musiikki ja äänet

Taustamusiikin on tehnyt **V3nlukk4** Logic Prolla. Kappale soi kokonaan ja alkaa saumattomasti alusta. Musiikki hiljenee hetkeksi tärkeiden äänitehosteiden ajaksi.

Äänitehosteet syntetisoidaan pelissä Web Audio -rajapinnalla, joten niille ei ole erillisiä tiedostoja: syöminen, räjähdys, pomput seinistä, nuolinäppäimen puhallus, seinäaukon varoitus ja avautuminen, läpilento, uusi kolesteroliökkö, kentän läpäisy ja pelin loppu.

Musiikin ja efektit saa pois vasemman alakulman painikkeista tai näppäimillä **M** (musiikki) ja **N** (äänitehosteet).

### Selainversion ajaminen

Vie `web`-kansion sisältö palvelimelle, jossa on PHP 7.4 tai uudempi. Peli käyttää silloin yhteistä tulostaulua `scores.php`:n kautta. Palvelin laskee pisteet kentittäin samalla kaavalla kuin peli. Tulostaulussa on yksi rivi per nimimerkki, ja se päivittyy vain paremmalla tuloksella. Nimimerkki varataan selaimelle, joka tallentaa sillä ensimmäisen kerran: selain lähettää satunnaisen tunnisteen, josta palvelin säilyttää vain tiivisteen. `scores.php` luo ensimmäisellä tallennuksella `data`-kansion ja suojaa sen `.htaccess`-tiedostolla. Palvelin tarkistaa, että pisteet vastaavat pelin sääntöjä, ja rajoittaa tallennustahtia.

Jos PHP:tä ei ole tai avaat `index.html`:n suoraan tiedostona, tulostaulu tallentuu pelaajan omaan selaimeen (localStorage).

Julkaisu sivustolle tapahtuu GitHub Actionsilla (`.github/workflows/deploy.yml`) aina, kun `web`-kansio muuttuu `main`-haarassa.

### Alkuperäinen versio

`original-2019` sisältää vuoden 2019 lähdekoodin sellaisenaan. Jypeli-kirjastot palautetaan NuGetillä (`packages.config`). Projektiin kuuluneet macOS-natiivikirjastot (`libSDL2-2.0.0.dylib`, `libopenal.1.dylib`) eivät ole repossa, koska ne ovat kolmannen osapuolen binäärejä.

## Lisenssi

**Musiikki:** `web/colesteroids-music.mp3` © V3nlukk4, lisensoitu [Creative Commons Nimeä 4.0 Kansainvälinen (CC BY 4.0)](https://creativecommons.org/licenses/by/4.0/deed.fi) -lisenssillä. MIT-lisenssi koskee vain koodia, ei musiikkia.

Jakokuvissa käytetty Inter-fontti on The Inter Project Authorsin ja julkaistu SIL Open Font License 1.1 -lisenssillä (`web/fonts/OFL.txt`).


[MIT](LICENSE) © 2026 Hessu Järvinen. Colesteroids on Hessu Järvisen alkuperäinen idea. Saat parannella koodia vapaasti, kunhan tekijänoikeusilmoitus säilyy mukana.
