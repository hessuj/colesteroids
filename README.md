# Colesteroids OG+

![Colesteroids OG+](web/og-image.png)

**Syö hyvät, väistä kolesteroli. Aikaa 60 sekuntia.**

Colesteroids on selaimessa pelattava fysiikkapeli. Ohjaat vaaleanpunaista sydäntä nuolinäppäimillä kentässä, jossa leijuu vihreitä kolmioita eli hyviä lipoproteiineja ja punaisia kolesteroliökköjä.

▶ **Pelaa:** https://hessujarvinen.com/softa/colesteroids

## Pelin säännöt

| | |
|---|---|
| 💗 Sydän | Sinä. Nuolinäppäin lyö sydäntä haluamaasi suuntaan, painovoima vetää alas. |
| 🟩 Vihreä kolmio | Hyvä lipoproteiini. Syö: +100 pistettä ja lisää energiaa. |
| 🔴 Punainen pallo | Kolesteroliökkö. Törmäys räjäyttää sen ja vie 20 % energiasta. |

- Jokainen selvitty sekunti antaa 10 pistettä.
- Uusi kolesteroliökkö ilmestyy 15 sekunnin välein.
- Peli päättyy, kun 60 sekuntia on kulunut tai sydämen energia loppuu.
- Tuloksen voi tallentaa nimimerkillä tulostauluun ja jakaa Facebookissa.

Näppäimet: nuolet liikuttavat, **F1** näyttää ohjeet ja **Esc** lopettaa pelin.

## Tausta

Alkuperäinen Colesteroids syntyi vuonna 2019 Jyväskylän yliopiston Ohjelmointi 1 -kurssilla. Se kirjoitettiin C#-kielellä yliopiston [Jypeli](https://github.com/Jypeli-JYU/Jypeli)-pelikirjastolla, ja siitä puuttuivat vielä alkunäyttö, tulostaulu ja kunnollinen pistelasku.

Colesteroids OG+ (2026) on sen paranneltu selainversio, joka on vibekoodattu Clauden kanssa. Kenttä, hahmot ja fysiikan oletusarvot on siirretty alkuperäisestä koodista mahdollisimman uskollisesti, ja alkuperäisen koodin TODO-kommenteista on tehty varsinainen pelimekaniikka.

## Hakemistot

```
web/             Selainversio (yksi HTML-tiedosto, ei riippuvuuksia)
  index.html     Peli
  og-image.png   Jakokuva (Facebook / Open Graph)
original-2019/   Alkuperäinen C#/Jypeli-projekti (Visual Studio for Mac)
```

### Selainversion ajaminen

Avaa `web/index.html` selaimessa tai vie `web`-kansion sisältö mille tahansa staattiselle palvelimelle. Tulostaulu tallentuu silloin pelaajan omaan selaimeen (localStorage).

### Alkuperäinen versio

`original-2019` sisältää vuoden 2019 lähdekoodin sellaisenaan. Jypeli-kirjastot palautetaan NuGetillä (`packages.config`). Projektiin kuuluneet macOS-natiivikirjastot (`libSDL2-2.0.0.dylib`, `libopenal.1.dylib`) eivät ole repossa, koska ne ovat kolmannen osapuolen binäärejä.

## Lisenssi

[MIT](LICENSE) © 2026 Hessu Järvinen. Colesteroids on Hessu Järvisen alkuperäinen idea. Saat parannella koodia vapaasti, kunhan tekijänoikeusilmoitus säilyy mukana.
